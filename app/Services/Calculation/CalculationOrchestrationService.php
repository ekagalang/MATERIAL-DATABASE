<?php

namespace App\Services\Calculation;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Repositories\CalculationRepository;
use App\Services\FormulaRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Calculation Orchestration Service
 *
 * Main orchestrator for calculation workflows:
 * - Generate combinations for multiple bricks
 * - Handle single calculation with preview
 * - Handle comparison between bricks
 * - Coordinate MaterialSelectionService and CombinationGenerationService
 *
 * Extracted from MaterialCalculationController lines 200-450
 */
class CalculationOrchestrationService
{
    protected CalculationRepository $repository;
    protected MaterialSelectionService $materialSelection;
    protected CombinationGenerationService $combinationGeneration;

    public function __construct(
        CalculationRepository $repository,
        MaterialSelectionService $materialSelection,
        CombinationGenerationService $combinationGeneration
    ) {
        $this->repository = $repository;
        $this->materialSelection = $materialSelection;
        $this->combinationGeneration = $combinationGeneration;
    }

    /**
     * Generate combinations for display
     *
     * Extracted from MaterialCalculationController lines 279-367
     *
     * @param array $request Request data
     * @return array ['projects' => [...], 'requestData' => [...], 'formulaName' => '...']
     */
    public function generateCombinations(array $request): array
    {
        // Tentukan Bata mana saja yang akan dihitung
        $priceFilters = $request['price_filters'] ?? [];
        $workType = $request['work_type'] ?? 'brick_half';

        // Check if formula is brickless (Plastering / Skim Coating)
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating']);

        if ($isBrickless) {
            // Use dummy brick placeholder to maintain data structure
            // The formula will return 0 bricks anyway
            $targetBricks = $this->repository->getBricksByIds([1]); // First brick as placeholder
            if ($targetBricks->isEmpty()) {
                $targetBricks = collect([Brick::first()]);
            }
        } else {
            // Select bricks based on request and filters
            $targetBricks = $this->materialSelection->selectBricks($request, $priceFilters);
        }

        // Struktur Project untuk View (agar support Multi-Tab)
        $projects = [];

        foreach ($targetBricks as $brick) {
            $projects[] = [
                'brick' => $brick,
                'combinations' => $this->calculateCombinationsForBrick($brick, $request),
            ];
        }

        // Get Formula Name
        $formulaInstance = FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';

        return [
            'projects' => $projects,
            'requestData' => array_diff_key($request, array_flip(['brick_ids', 'brick_id'])), // Exclude brick IDs
            'formulaName' => $formulaName,
        ];
    }

    /**
     * Calculate combinations for a single brick
     *
     * Extracted from MaterialCalculationController lines 447-558
     *
     * @param Brick $brick
     * @param array $request
     * @return array Associative array with filter labels as keys
     */
    public function calculateCombinationsForBrick(Brick $brick, array $request): array
    {
        $requestedFilters = $request['price_filters'] ?? ['best'];

        // Special, independent handling for 'best' filter when it's the only one selected
        if (count($requestedFilters) === 1 && $requestedFilters[0] === 'best') {
            $bestCombinations = $this->combinationGeneration->getBestCombinations($brick, $request);
            $finalResults = [];
            foreach ($bestCombinations as $index => $combo) {
                $label = 'TerBAIK ' . ($index + 1);
                $finalResults[$label] = [array_merge($combo, ['filter_label' => $label])];
            }
            return $finalResults;
        }

        $hasAll = in_array('all', $requestedFilters);

        if ($hasAll) {
            $standardFilters = ['best', 'common', 'cheapest', 'medium', 'expensive'];
            $requestedFilters = array_unique(array_merge($requestedFilters, $standardFilters));
        }

        // Always calculate ALL standard filters to generate cross-reference labels
        $filtersToCalculate = ['best', 'common', 'cheapest', 'medium', 'expensive'];

        // Add custom only if requested (because custom depends on specific user input)
        if (in_array('custom', $requestedFilters)) {
            $filtersToCalculate[] = 'custom';
        }

        $allCombinations = [];

        foreach ($filtersToCalculate as $filter) {
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter);

            foreach ($combinations as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->combinationGeneration->getFilterLabel($filter);

                // Special handling for custom label to match previous behavior
                if ($filter === 'custom') {
                    $filterLabel = 'Custom';
                }

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        // Merge duplicates (and collect source_filters)
        $uniqueCombos = $this->combinationGeneration->detectAndMergeDuplicates($allCombinations);

        // Pre-calculate mapped labels for requested filters to fast check priority
        $priorityLabels = [];
        $userOriginalFilters = $request['price_filters'] ?? [];
        foreach ($userOriginalFilters as $rf) {
            if ($rf !== 'all') {
                $priorityLabels[] = $rf === 'custom' ? 'Custom' : $this->combinationGeneration->getFilterLabel($rf);
            }
        }

        // Filter output based on User's Request & Re-order Labels
        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            // Check intersection between combo's sources and requested filters
            $sources = $combo['source_filters'] ?? [$combo['filter_type']];

            // If any source is in requested filters, show the item
            if (count(array_intersect($sources, $requestedFilters)) > 0) {
                // Re-order label based on priority
                $labels = $combo['all_labels'] ?? [$combo['filter_label']];

                if (!empty($priorityLabels)) {
                    usort($labels, function ($a, $b) use ($priorityLabels) {
                        // Check priority score
                        $aScore = 0;
                        foreach ($priorityLabels as $pl) {
                            if (str_starts_with($a, $pl)) {
                                $aScore = 1;
                                break;
                            }
                        }

                        $bScore = 0;
                        foreach ($priorityLabels as $pl) {
                            if (str_starts_with($b, $pl)) {
                                $bScore = 1;
                                break;
                            }
                        }

                        // Higher score first (descending)
                        return $bScore <=> $aScore;
                    });
                }

                // Update label string
                $combo['filter_label'] = implode(' = ', $labels);

                // Use the new label as key
                $label = $combo['filter_label'];
                $finalResults[$label] = [$combo];
            }
        }

        return $finalResults;
    }

    /**
     * Get combinations by filter type
     *
     * @param Brick $brick
     * @param array $request
     * @param string $filter
     * @return array
     */
    protected function getCombinationsByFilter(Brick $brick, array $request, string $filter): array
    {
        return match ($filter) {
            'best' => $this->combinationGeneration->getBestCombinations($brick, $request),
            'common' => $this->combinationGeneration->getCommonCombinations($brick, $request),
            'cheapest' => $this->combinationGeneration->getCheapestCombinations($brick, $request),
            'medium' => $this->combinationGeneration->getMediumCombinations($brick, $request),
            'expensive' => $this->combinationGeneration->getExpensiveCombinations($brick, $request),
            'custom' => $this->combinationGeneration->getCustomCombinations($brick, $request),
            default => [],
        };
    }

    /**
     * Calculate single brick with specific materials (for save/preview)
     *
     * @param array $params Calculation parameters
     * @param bool $save Whether to save to database
     * @return BrickCalculation
     * @throws \Exception
     */
    public function calculateSingle(array $params, bool $save = false): BrickCalculation
    {
        Log::info('Calculate Single - Request Data:', [
            'work_type' => $params['work_type'] ?? null,
            'brick_id' => $params['brick_id'] ?? null,
            'cement_id' => $params['cement_id'] ?? null,
            'sand_id' => $params['sand_id'] ?? null,
            'wall_length' => $params['wall_length'] ?? null,
            'wall_height' => $params['wall_height'] ?? null,
        ]);

        $calculation = BrickCalculation::performCalculation($params);

        Log::info('Calculate Single - Result:', [
            'total_cost' => $calculation->total_material_cost,
            'brick_quantity' => $calculation->brick_quantity,
            'cement_quantity' => $calculation->cement_quantity_sak,
            'sand_quantity' => $calculation->sand_m3,
        ]);

        if ($save) {
            $calculation->save();
        }

        return $calculation;
    }

    /**
     * Compare multiple bricks with same materials
     *
     * Extracted from MaterialCalculationController lines 369-445
     *
     * @param array $request Request data with brick_ids
     * @return array Comparison results
     */
    public function compareBricks(array $request): array
    {
        $wallArea = $request['wall_length'] * $request['wall_height'];
        $bricks = $this->repository->getBricksByIds($request['brick_ids']);

        // Auto-select cheapest mortar materials for fair comparison
        $materials = $this->materialSelection->selectMaterialsByPrice('cheapest');

        // Use default mortar formula (1:3 or available)
        $defaultMortar = $this->materialSelection->getDefaultMortarFormula();

        $comparisons = [];

        foreach ($bricks as $brick) {
            $params = [
                'wall_length' => $request['wall_length'],
                'wall_height' => $request['wall_height'],
                'mortar_thickness' => $request['mortar_thickness'],
                'installation_type_id' => $request['installation_type_id'],
                'mortar_formula_id' => $defaultMortar?->id,
                'work_type' => $request['work_type'] ?? 'brick_half',
                'brick_id' => $brick->id,
                'cement_id' => $materials['cement_id'],
                'sand_id' => $materials['sand_id'],
                'layer_count' => $request['layer_count'] ?? 1, // For Rollag formula
            ];

            try {
                $formulaCode = $params['work_type'];
                $formula = FormulaRegistry::instance($formulaCode);

                if (!$formula) {
                    throw new \Exception("Formula '{$formulaCode}' tidak ditemukan");
                }

                $result = $formula->calculate($params);

                $comparisons[] = [
                    'brick' => $brick,
                    'result' => $result,
                    'total_cost' => $result['grand_total'],
                    'cost_per_m2' => $wallArea > 0 ? $result['grand_total'] / $wallArea : 0,
                ];
            } catch (\Exception $e) {
                Log::error('Compare Bricks Error:', [
                    'brick_id' => $brick->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Sort by total cost
        usort($comparisons, function ($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        return [
            'comparisons' => $comparisons,
            'materials' => [
                'cement' => $this->repository->findCement($materials['cement_id']),
                'sand' => $this->repository->findSand($materials['sand_id']),
                'mortar_formula' => $defaultMortar,
            ],
        ];
    }

    /**
     * Store calculation result
     *
     * @param array $params
     * @return BrickCalculation
     */
    public function store(array $params): BrickCalculation
    {
        return $this->calculateSingle($params, true);
    }

    /**
     * Preview calculation (without saving)
     *
     * @param array $params
     * @return BrickCalculation
     */
    public function preview(array $params): BrickCalculation
    {
        $calculation = $this->calculateSingle($params, false);
        $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);
        return $calculation;
    }
}
