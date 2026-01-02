<?php

namespace App\Services\Calculation;

use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;
use App\Repositories\CalculationRepository;
use App\Services\FormulaRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Combination Generation Service
 *
 * Handle complex combination logic:
 * - Generate cement x sand combinations
 * - Calculate each combination using formulas
 * - Deduplicate combinations
 * - Apply filters (best, common, cheapest, medium, expensive, custom, all)
 *
 * Extracted from MaterialCalculationController lines 627-978
 */
class CombinationGenerationService
{
    protected CalculationRepository $repository;
    protected MaterialSelectionService $materialSelection;

    public function __construct(
        CalculationRepository $repository,
        MaterialSelectionService $materialSelection
    ) {
        $this->repository = $repository;
        $this->materialSelection = $materialSelection;
    }

    /**
     * Calculate combinations from given materials
     *
     * Extracted from MaterialCalculationController lines 689-787
     *
     * @param Brick $brick
     * @param array $request Request parameters
     * @param EloquentCollection $cements
     * @param EloquentCollection $sands
     * @param string $groupLabel Label for this group (e.g., 'TerBAIK', 'TerMURAH')
     * @param int|null $limit Limit number of results
     * @return array
     */
    public function calculateCombinationsFromMaterials(
        Brick $brick,
        array $request,
        EloquentCollection $cements,
        EloquentCollection $sands,
        string $groupLabel = 'Kombinasi',
        ?int $limit = null
    ): array {
        $paramsBase = [
            'wall_length' => $request['wall_length'],
            'wall_height' => $request['wall_height'],
            'mortar_thickness' => $request['mortar_thickness'],
            'installation_type_id' => $request['installation_type_id'],
            'mortar_formula_id' => $request['mortar_formula_id'],
            'work_type' => $request['work_type'] ?? 'brick_half',
            'brick_id' => $brick->id,
            'layer_count' => $request['layer_count'] ?? 1, // For Rollag formula
            'plaster_sides' => $request['plaster_sides'] ?? 1, // For Wall Plastering
            'skim_sides' => $request['skim_sides'] ?? 1, // For Skim Coating
        ];

        $results = [];

        foreach ($cements as $cement) {
            // VALIDASI DATA SEMEN
            // Skip jika berat bersih 0 atau kosong (menyebabkan division by zero di formula)
            if ($cement->package_weight_net <= 0) {
                continue;
            }

            foreach ($sands as $sand) {
                // VALIDASI DATA PASIR
                // Skip jika tidak ada harga per m3 DAN (volume 0 atau harga 0)
                // Ini untuk mencegah error kalkulasi harga pasir
                $hasPricePerM3 = $sand->comparison_price_per_m3 > 0;
                $hasPackageData = $sand->package_volume > 0 && $sand->package_price > 0;

                if (!$hasPricePerM3 && !$hasPackageData) {
                    continue;
                }

                $params = array_merge($paramsBase, [
                    'cement_id' => $cement->id,
                    'sand_id' => $sand->id,
                ]);

                try {
                    // Use the same calculation method as save for consistency
                    $formulaCode = $params['work_type'] ?? 'brick_half';
                    $formula = FormulaRegistry::instance($formulaCode);

                    if (!$formula) {
                        throw new \Exception("Formula '{$formulaCode}' tidak ditemukan");
                    }

                    $result = $formula->calculate($params);

                    // Debug logging untuk preview
                    Log::info('Preview Calculation:', [
                        'formula_code' => $formulaCode,
                        'cement_id' => $cement->id,
                        'cement_brand' => $cement->brand,
                        'cement_price' => $cement->package_price,
                        'sand_id' => $sand->id,
                        'sand_brand' => $sand->brand,
                        'total_cost' => $result['grand_total'],
                        'params' => $params,
                    ]);

                    $results[] = [
                        'cement' => $cement,
                        'sand' => $sand,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                    ];
                } catch (\Exception $e) {
                    Log::error('Preview Calculation Error:', [
                        'cement_id' => $cement->id,
                        'sand_id' => $sand->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }
            }
        }

        // Sort by total cost
        usort($results, function ($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        // Apply limit if specified
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Detect and merge duplicate combinations
     *
     * Extracted from MaterialCalculationController lines 627-684
     *
     * @param array $combinations
     * @return array
     */
    public function detectAndMergeDuplicates(array $combinations): array
    {
        $uniqueCombos = [];
        $duplicateMap = [];

        foreach ($combinations as $combo) {
            $key = $combo['cement']->id . '-' . $combo['sand']->id;

            // Ensure source_filters is initialized
            if (!isset($combo['source_filters'])) {
                $combo['source_filters'] = [$combo['filter_type']];
            }

            $currentLabel = $combo['filter_label'];

            if (isset($duplicateMap[$key])) {
                // Duplicate found, merge labels
                $existingIndex = $duplicateMap[$key];

                // Add to all_labels collection
                $uniqueCombos[$existingIndex]['all_labels'][] = $currentLabel;

                // Keep default string merging (will be overwritten by display logic later)
                $uniqueCombos[$existingIndex]['filter_label'] .= ' = ' . $currentLabel;

                // Merge Source Filters
                if (!in_array($combo['filter_type'], $uniqueCombos[$existingIndex]['source_filters'])) {
                    $uniqueCombos[$existingIndex]['source_filters'][] = $combo['filter_type'];
                }

                Log::info('Duplicate Detected:', [
                    'key' => $key,
                    'merged_label' => $uniqueCombos[$existingIndex]['filter_label'],
                ]);
            } else {
                // New combination
                $duplicateMap[$key] = count($uniqueCombos);

                // Initialize all_labels
                $combo['all_labels'] = [$currentLabel];

                $uniqueCombos[] = $combo;

                Log::info('New Combination:', [
                    'key' => $key,
                    'label' => $combo['filter_label'],
                ]);
            }
        }

        Log::info('Duplicate Detection Summary:', [
            'total_input' => count($combinations),
            'total_unique' => count($uniqueCombos),
            'duplicates_merged' => count($combinations) - count($uniqueCombos),
        ]);

        return $uniqueCombos;
    }

    /**
     * Get 3 best (recommended) combinations
     *
     * Extracted from MaterialCalculationController lines 792-859
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getBestCombinations(Brick $brick, array $request): array
    {
        // Get work_type from request
        $workType = $request['work_type'] ?? 'brick_half';
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating']);

        // 1. Check for Admin Recommendations - filtered by work_type
        $recommendations = $this->repository->getRecommendedCombinations($workType);
        $recommendations = $recommendations->where('brick_id', $brick->id)
            ->where('type', 'best');

        Log::info(
            'GetBestCombinations: Found ' .
                $recommendations->count() .
                ' recommendations for brick ID: ' .
                $brick->id .
                ' and work_type: ' .
                $workType
        );

        $allRecommendedResults = [];

        foreach ($recommendations as $rec) {
            $cements = $this->repository->getCementsByIds([$rec->cement_id]);
            $sands = $this->repository->getSandsByIds([$rec->sand_id]);

            Log::info("Processing recommendation: Cement ID {$rec->cement_id}, Sand ID {$rec->sand_id}");

            // Calculate for this specific pair
            $results = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerBAIK', 1);

            Log::info('Calculation result count: ' . count($results));

            // Mark as 'best' source and add to collection
            foreach ($results as &$res) {
                $res['source_filter'] = 'best';
                $allRecommendedResults[] = $res;
            }
        }

        // If there are admin recommendations, return them
        if (!empty($allRecommendedResults)) {
            Log::info('Returning ' . count($allRecommendedResults) . " admin-defined 'best' combinations.");
            return $allRecommendedResults;
        }

        // FALLBACK FOR BRICKLESS: If no recommendations, use Cheapest
        if ($isBrickless) {
            Log::info('No admin recommendations for brickless work. Falling back to cheapest.');
            $cheapest = $this->getCheapestCombinations($brick, $request);
            // Limit to 3 and mark as best
            $cheapest = array_slice($cheapest, 0, 3);
            return array_map(function ($combo) {
                $combo['source_filter'] = 'best';
                return $combo;
            }, $cheapest);
        }

        // 2. No recommendations found
        Log::info('No admin recommendations found.');
        return [];
    }

    /**
     * Get 3 most commonly used combinations
     *
     * Extracted from MaterialCalculationController lines 864-917
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getCommonCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating']);

        if ($isBrickless) {
            // Fallback to cheapest for brickless work (since history tracking is complex without brick_id)
            $cheapest = $this->getCheapestCombinations($brick, $request);
            return array_map(function ($combo) {
                $combo['source_filter'] = 'common';
                return $combo;
            }, array_slice($cheapest, 0, 3));
        }

        // Query most frequent combinations from brick_calculations table
        $commonCombos = DB::table('brick_calculations')
            ->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))
            ->where('brick_id', $brick->id)
            ->groupBy('cement_id', 'sand_id')
            ->orderByDesc('frequency')
            ->limit(3)
            ->get();

        if ($commonCombos->isEmpty()) {
            // Fallback to cheapest if no history
            $cheapest = $this->getCheapestCombinations($brick, $request);
            return array_map(function ($combo) {
                $combo['source_filter'] = 'cheapest';
                return $combo;
            }, $cheapest);
        }

        $results = [];
        foreach ($commonCombos as $combo) {
            $cement = $this->repository->findCement($combo->cement_id);
            $sand = $this->repository->findSand($combo->sand_id);

            if (!$cement || !$sand) {
                continue;
            }

            // Create Eloquent Collections instead of Support Collections
            $cements = Cement::whereIn('id', [$cement->id])->get();
            $sands = Sand::whereIn('id', [$sand->id])->get();
            $calculated = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerUMUM', 1);

            if (!empty($calculated)) {
                $results[] = $calculated[0];
            }
        }

        // Return whatever results we have (1, 2, or 3 combinations)
        return $results;
    }

    /**
     * Get 3 cheapest combinations
     *
     * Extracted from MaterialCalculationController lines 922-928
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getCheapestCombinations(Brick $brick, array $request): array
    {
        $cements = $this->repository->getCementsByPrice('asc');
        $sands = $this->repository->getSandsByPrice('asc');

        return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerMURAH', 3);
    }

    /**
     * Get 3 medium-priced combinations
     *
     * Extracted from MaterialCalculationController lines 933-948
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getMediumCombinations(Brick $brick, array $request): array
    {
        $cements = $this->repository->getCementsByPrice('asc');
        $sands = $this->repository->getSandsByPrice('asc');

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerSEDANG');

        // Get middle 3 combinations
        $total = count($allResults);
        if ($total < 3) {
            return $allResults;
        }

        $startIndex = max(0, floor(($total - 3) / 2));
        return array_slice($allResults, $startIndex, 3);
    }

    /**
     * Get 3 most expensive combinations
     *
     * Extracted from MaterialCalculationController lines 953-962
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getExpensiveCombinations(Brick $brick, array $request): array
    {
        $cements = $this->repository->getCementsByPrice('desc');
        $sands = $this->repository->getSandsByPrice('desc');

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerMAHAL');

        // Get top 3 most expensive
        return array_slice(array_reverse($allResults), 0, 3);
    }

    /**
     * Get custom combinations
     *
     * Extracted from MaterialCalculationController lines 967-978
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getCustomCombinations(Brick $brick, array $request): array
    {
        if (!empty($request['cement_id']) && !empty($request['sand_id'])) {
            // Specific materials selected
            $cements = $this->repository->getCementsByIds([$request['cement_id']]);
            $sands = $this->repository->getSandsByIds([$request['sand_id']]);
            return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'Custom', 1);
        } else {
            // Show all combinations
            return $this->getAllCombinations($brick, $request);
        }
    }

    /**
     * Get all combinations
     *
     * @param Brick $brick
     * @param array $request
     * @return array
     */
    public function getAllCombinations(Brick $brick, array $request): array
    {
        $cements = $this->repository->getCementsForCombination();
        $sands = $this->repository->getSandsForCombination();

        return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'Semua');
    }

    /**
     * Get filter label for display
     *
     * @param string $filter
     * @return string
     */
    public function getFilterLabel(string $filter): string
    {
        return match ($filter) {
            'best' => 'Terbaik',
            'common' => 'Populer',
            'cheapest' => 'Termurah',
            'medium' => 'Sedang',
            'expensive' => 'Termahal',
            'custom' => 'Custom',
            'all' => 'Semua',
            default => ucfirst($filter),
        };
    }
}
