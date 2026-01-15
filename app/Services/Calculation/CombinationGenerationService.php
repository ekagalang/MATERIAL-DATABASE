<?php

namespace App\Services\Calculation;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
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
     * @param EloquentCollection|null $cats
     * @param EloquentCollection|null $ceramics
     * @param EloquentCollection|null $nats
     * @param string $groupLabel Label for this group (e.g., 'TerBAIK', 'TerMURAH')
     * @param int|null $limit Limit number of results
     * @return array
     */
    public function calculateCombinationsFromMaterials(
        Brick $brick,
        array $request,
        EloquentCollection $cements,
        EloquentCollection $sands,
        ?EloquentCollection $cats = null,
        ?EloquentCollection $ceramics = null,
        ?EloquentCollection $nats = null,
        string $groupLabel = 'Kombinasi',
        ?int $limit = null
    ): array {
        $workType = $request['work_type'] ?? 'brick_half';
        $wallHeight = $request['wall_height'] ?? null;
        if ($workType === 'brick_rollag') {
            $brickLength = $brick->dimension_length ?? 0;
            if ($brickLength <= 0) {
                $brickLength = 19.2;
            }
            $wallHeight = $brickLength / 100;
        }

        $paramsBase = [
            'wall_length' => $request['wall_length'],
            'wall_height' => $wallHeight,
            'mortar_thickness' => $request['mortar_thickness'],
            'installation_type_id' => $request['installation_type_id'],
            'mortar_formula_id' => $request['mortar_formula_id'],
            'work_type' => $workType,
            'brick_id' => $brick->id,
            'layer_count' => $request['layer_count'] ?? 1, // For Rollag formula
            'plaster_sides' => $request['plaster_sides'] ?? 1, // For Wall Plastering
            'skim_sides' => $request['skim_sides'] ?? 1, // For Skim Coating
        ];

        $cats = $cats ?? collect();
        $ceramics = $ceramics ?? collect();
        $nats = $nats ?? collect();

        $workType = $paramsBase['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);

        if ($workType === 'tile_installation') {
            return $this->processGeneratorResults(
                $this->yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel),
                $limit
            );
        }

        $results = [];

        if (in_array('cat', $requiredMaterials, true)) {
            foreach ($cats as $cat) {
                if ($cat->purchase_price <= 0) {
                    continue;
                }

                $params = array_merge($paramsBase, [
                    'cat_id' => $cat->id,
                ]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        throw new \Exception("Formula '{$workType}' tidak ditemukan");
                    }

                    $result = $formula->calculate($params);

                    $results[] = [
                        'cat' => $cat,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                    ];
                } catch (\Exception $e) {
                    Log::error('Preview Calculation Error:', [
                        'cat_id' => $cat->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        } elseif (
            in_array('ceramic', $requiredMaterials, true) &&
            in_array('nat', $requiredMaterials, true) &&
            !in_array('cement', $requiredMaterials, true) &&
            !in_array('sand', $requiredMaterials, true)
        ) {
            foreach ($ceramics as $ceramic) {
                foreach ($nats as $nat) {
                    $params = array_merge($paramsBase, [
                        'ceramic_id' => $ceramic->id,
                        'nat_id' => $nat->id,
                    ]);

                    try {
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) {
                            throw new \Exception("Formula '{$workType}' tidak ditemukan");
                        }

                        $result = $formula->calculate($params);

                        $results[] = [
                            'ceramic' => $ceramic,
                            'nat' => $nat,
                            'result' => $result,
                            'total_cost' => $result['grand_total'],
                        ];
                    } catch (\Exception $e) {
                        Log::error('Preview Calculation Error:', [
                            'ceramic_id' => $ceramic->id,
                            'nat_id' => $nat->id,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }
        } elseif (in_array('cement', $requiredMaterials, true) && !in_array('sand', $requiredMaterials, true)) {
            foreach ($cements as $cement) {
                if ($cement->package_weight_net <= 0) {
                    continue;
                }

                $params = array_merge($paramsBase, [
                    'cement_id' => $cement->id,
                ]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        throw new \Exception("Formula '{$workType}' tidak ditemukan");
                    }

                    $result = $formula->calculate($params);

                    $results[] = [
                        'cement' => $cement,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                    ];
                } catch (\Exception $e) {
                    Log::error('Preview Calculation Error:', [
                        'cement_id' => $cement->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        } else {
            foreach ($cements as $cement) {
                if ($cement->package_weight_net <= 0) {
                    continue;
                }

                foreach ($sands as $sand) {
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
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) {
                            throw new \Exception("Formula '{$workType}' tidak ditemukan");
                        }

                        $result = $formula->calculate($params);

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
                        ]);
                        continue;
                    }
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
            if (isset($combo['cat'])) {
                $key = 'cat-' . $combo['cat']->id;
            } elseif (isset($combo['ceramic'])) {
                $key = 'cer-' . ($combo['ceramic']->id ?? 0) .
                    '-nat-' . ($combo['nat']->id ?? 0) .
                    '-cem-' . ($combo['cement']->id ?? 0) .
                    '-snd-' . ($combo['sand']->id ?? 0);
            } elseif (isset($combo['cement']) && !isset($combo['sand'])) {
                $key = 'cement-' . ($combo['cement']->id ?? 0);
            } else {
                $key = ($combo['cement']->id ?? 0) . '-' . ($combo['sand']->id ?? 0);
            }

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
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);

        $recommendations = $this->repository->getRecommendedCombinations($workType)
            ->where('type', 'best');

        if (in_array('brick', $requiredMaterials, true)) {
            $recommendations = $recommendations->filter(function ($rec) use ($brick) {
                return empty($rec->brick_id) || $rec->brick_id === $brick->id;
            });
        }

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
            $missingRequired = false;
            $cements = collect();
            $sands = collect();
            $cats = collect();
            $ceramics = collect();
            $nats = collect();

            if (in_array('cement', $requiredMaterials, true)) {
                if (empty($rec->cement_id)) {
                    $missingRequired = true;
                } else {
                    $cements = $this->repository->getCementsByIds([$rec->cement_id]);
                }
            }

            if (in_array('sand', $requiredMaterials, true)) {
                if (empty($rec->sand_id)) {
                    $missingRequired = true;
                } else {
                    $sands = $this->repository->getSandsByIds([$rec->sand_id]);
                }
            }

            if (in_array('cat', $requiredMaterials, true)) {
                if (empty($rec->cat_id)) {
                    $missingRequired = true;
                } else {
                    $cats = Cat::where('id', $rec->cat_id)->get();
                }
            }

            if (in_array('ceramic', $requiredMaterials, true)) {
                if (empty($rec->ceramic_id)) {
                    $missingRequired = true;
                } else {
                    $ceramics = Ceramic::where('id', $rec->ceramic_id)->get();
                }
            }

            if (in_array('nat', $requiredMaterials, true)) {
                if (empty($rec->nat_id)) {
                    $missingRequired = true;
                } else {
                    $nats = Cement::where('id', $rec->nat_id)->get();
                }
            }

            if ($missingRequired) {
                continue;
            }

            $results = $this->calculateCombinationsFromMaterials(
                $brick,
                $request,
                $cements,
                $sands,
                $cats,
                $ceramics,
                $nats,
                'TerBAIK',
                1
            );

            Log::info('Calculation result count: ' . count($results));

            foreach ($results as &$res) {
                $res['source_filter'] = 'best';
                $allRecommendedResults[] = $res;
            }
        }

        if (!empty($allRecommendedResults)) {
            Log::info('Returning ' . count($allRecommendedResults) . " admin-defined 'best' combinations.");
            return $allRecommendedResults;
        }

        if ($isBrickless) {
            Log::info('No admin recommendations for brickless work. Falling back to cheapest.');
            if ($workType === 'grout_tile') {
                $medium = $this->getMediumCombinations($brick, $request);
                $medium = array_slice($medium, 0, 3);
                return array_map(function ($combo) {
                    $combo['source_filter'] = 'best';
                    return $combo;
                }, $medium);
            }

            $cheapest = $this->getCheapestCombinations($brick, $request);
            $cheapest = array_slice($cheapest, 0, 3);
            return array_map(function ($combo) {
                $combo['source_filter'] = 'best';
                return $combo;
            }, $cheapest);
        }

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
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);

        if ($isBrickless) {
            if ($workType === 'grout_tile') {
                $materialLimit = $this->resolveMaterialLimit($workType);
                $ceramics = $this->resolveCeramicsForCalculation(
                    $request,
                    $workType,
                    'price_per_package',
                    'asc',
                    $materialLimit,
                    3
                );
                $nats = Cement::where('type', 'Nat')
                    ->orderBy('package_price')
                    ->skip(2)
                    ->limit($materialLimit)
                    ->get();

                if ($ceramics->isEmpty() && empty($request['ceramic_id'])) {
                    $ceramics = $this->resolveCeramicsForCalculation(
                        $request,
                        $workType,
                        'price_per_package',
                        'asc',
                        $materialLimit
                    );
                }
                if ($nats->isEmpty()) {
                    $nats = $this->resolveNatsByPrice('asc', $materialLimit);
                }

                $results = $this->calculateCombinationsFromMaterials(
                    $brick,
                    $request,
                    collect(),
                    collect(),
                    collect(),
                    $ceramics,
                    $nats,
                    'TerUMUM',
                    3
                );

                return array_map(function ($combo) {
                    $combo['source_filter'] = 'common';
                    return $combo;
                }, $results);
            }

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
            $calculated = $this->calculateCombinationsFromMaterials(
                $brick,
                $request,
                $cements,
                $sands,
                collect(),
                collect(),
                collect(),
                'TerUMUM',
                1
            );

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
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);

        $cements = $this->resolveCementsByPrice('asc', $materialLimit);
        $sands = $this->resolveSandsByPrice('asc', $materialLimit);
        $cats = $this->resolveCatsByPrice('asc', $materialLimit);
        $ceramics = $this->resolveCeramicsForCalculation($request, $workType, 'price_per_package', 'asc', $materialLimit);
        $nats = $this->resolveNatsByPrice('asc', $materialLimit);

        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'TerMURAH',
            3
        );
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
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);

        $cements = $this->resolveCementsByPrice('asc', $materialLimit);
        $sands = $this->resolveSandsByPrice('asc', $materialLimit);
        $cats = $this->resolveCatsByPrice('asc', $materialLimit);
        $ceramics = $this->resolveCeramicsForCalculation($request, $workType, 'price_per_package', 'asc', $materialLimit);
        $nats = $this->resolveNatsByPrice('asc', $materialLimit);

        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'TerSEDANG'
        );

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
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);

        $cements = $this->resolveCementsByPrice('desc', $materialLimit);
        $sands = $this->resolveSandsByPrice('desc', $materialLimit);
        $cats = $this->resolveCatsByPrice('desc', $materialLimit);
        $ceramics = $this->resolveCeramicsForCalculation($request, $workType, 'price_per_package', 'desc', $materialLimit);
        $nats = $this->resolveNatsByPrice('desc', $materialLimit);

        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'TerMAHAL'
        );

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
        $workType = $request['work_type'] ?? 'brick_half';

        if ($workType === 'painting') {
            if (!empty($request['cat_id'])) {
                $cats = Cat::where('id', $request['cat_id'])->get();
                return $this->calculateCombinationsFromMaterials($brick, $request, collect(), collect(), $cats, collect(), collect(), 'Custom', 1);
            }
        } elseif ($workType === 'grout_tile') {
            if (!empty($request['ceramic_id']) && !empty($request['nat_id'])) {
                $ceramics = Ceramic::where('id', $request['ceramic_id'])->get();
                $nats = Cement::where('id', $request['nat_id'])->get();
                return $this->calculateCombinationsFromMaterials($brick, $request, collect(), collect(), collect(), $ceramics, $nats, 'Custom', 1);
            }
        } elseif ($workType === 'tile_installation') {
            if (
                !empty($request['ceramic_id']) &&
                !empty($request['nat_id']) &&
                !empty($request['cement_id']) &&
                !empty($request['sand_id'])
            ) {
                $ceramics = Ceramic::where('id', $request['ceramic_id'])->get();
                $nats = Cement::where('id', $request['nat_id'])->get();
                $cements = $this->repository->getCementsByIds([$request['cement_id']]);
                $sands = $this->repository->getSandsByIds([$request['sand_id']]);
                return $this->calculateCombinationsFromMaterials(
                    $brick,
                    $request,
                    $cements,
                    $sands,
                    collect(),
                    $ceramics,
                    $nats,
                    'Custom',
                    1
                );
            }
        } elseif (!empty($request['cement_id']) && !empty($request['sand_id'])) {
            $cements = $this->repository->getCementsByIds([$request['cement_id']]);
            $sands = $this->repository->getSandsByIds([$request['sand_id']]);
            return $this->calculateCombinationsFromMaterials(
                $brick,
                $request,
                $cements,
                $sands,
                collect(),
                collect(),
                collect(),
                'Custom',
                1
            );
        }

        return $this->getAllCombinations($brick, $request);
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
        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);

        $cements = in_array('cement', $requiredMaterials, true)
            ? $this->repository->getCementsForCombination()
            : collect();
        $sands = in_array('sand', $requiredMaterials, true)
            ? $this->repository->getSandsForCombination()
            : collect();
        $cats = in_array('cat', $requiredMaterials, true)
            ? Cat::where('purchase_price', '>', 0)->orderBy('brand')->get()
            : collect();
        $ceramics = in_array('ceramic', $requiredMaterials, true)
            ? $this->resolveCeramicsForCalculation($request, $workType, 'price_per_package', 'asc', null)
            : collect();
        $nats = in_array('nat', $requiredMaterials, true)
            ? Cement::where('type', 'Nat')->orderBy('brand')->get()
            : collect();

        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'Semua'
        );
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

    protected function resolveRequiredMaterials(string $workType): array
    {
        $materials = FormulaRegistry::materialsFor($workType);
        return !empty($materials) ? $materials : ['brick', 'cement', 'sand'];
    }

    protected function resolveMaterialLimit(string $workType): int
    {
        return $workType === 'tile_installation' ? 10 : 5;
    }

    protected function resolveCementsByPrice(string $direction, int $limit): EloquentCollection
    {
        return Cement::where(function ($q) {
                $q->where('type', '!=', 'Nat')->orWhereNull('type');
            })
            ->where('package_price', '>', 0)
            ->where('package_weight_net', '>', 0)
            ->orderBy('package_price', $direction)
            ->limit($limit)
            ->get();
    }

    protected function resolveNatsByPrice(string $direction, int $limit): EloquentCollection
    {
        return Cement::where('type', 'Nat')
            ->where('package_price', '>', 0)
            ->orderBy('package_price', $direction)
            ->limit($limit)
            ->get();
    }

    protected function resolveSandsByPrice(string $direction, int $limit): EloquentCollection
    {
        return Sand::where('package_price', '>', 0)
            ->orderBy('package_price', $direction)
            ->limit($limit)
            ->get();
    }

    protected function resolveCatsByPrice(string $direction, int $limit): EloquentCollection
    {
        return Cat::where('purchase_price', '>', 0)
            ->orderBy('purchase_price', $direction)
            ->limit($limit)
            ->get();
    }

    protected function resolveCeramicsForCalculation(
        array $request,
        string $workType,
        string $orderBy,
        string $direction,
        ?int $limit,
        int $skip = 0
    ): EloquentCollection {
        if (!empty($request['ceramic_id'])) {
            $ceramic = Ceramic::find($request['ceramic_id']);
            return $ceramic ? collect([$ceramic]) : collect();
        }

        $query = Ceramic::query();

        if ($workType === 'grout_tile') {
            $query->whereNotNull('dimension_thickness')
                ->where('dimension_thickness', '>', 0);
        }

        $query = $query->whereNotNull($orderBy)
            ->orderBy($orderBy, $direction)
            ->skip($skip);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function yieldTileInstallationCombinations(
        array $paramsBase,
        EloquentCollection $ceramics,
        EloquentCollection $nats,
        EloquentCollection $cements,
        EloquentCollection $sands,
        string $groupLabel
    ) {
        foreach ($ceramics as $ceramic) {
            foreach ($nats as $nat) {
                foreach ($cements as $cement) {
                    if ($cement->package_weight_net <= 0) {
                        continue;
                    }

                    foreach ($sands as $sand) {
                        $hasPricePerM3 = $sand->comparison_price_per_m3 > 0;
                        $hasPackageData = $sand->package_volume > 0 && $sand->package_price > 0;
                        if (!$hasPricePerM3 && !$hasPackageData) {
                            continue;
                        }

                        $params = array_merge($paramsBase, [
                            'ceramic_id' => $ceramic->id,
                            'nat_id' => $nat->id,
                            'cement_id' => $cement->id,
                            'sand_id' => $sand->id,
                        ]);

                        try {
                            $formula = FormulaRegistry::instance('tile_installation');
                            $result = $formula->calculate($params);

                            yield [
                                'ceramic' => $ceramic,
                                'nat' => $nat,
                                'cement' => $cement,
                                'sand' => $sand,
                                'result' => $result,
                                'total_cost' => $result['grand_total'],
                                'filter_type' => $groupLabel,
                            ];
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }
    }

    protected function processGeneratorResults($generator, ?int $limit = null): array
    {
        $results = [];
        $batchSize = 100;
        $targetSize = $limit ?? 10;
        $keepSize = max($targetSize * 3, 30);

        foreach ($generator as $combination) {
            $results[] = $combination;

            if (count($results) >= $batchSize) {
                usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
                $results = array_slice($results, 0, $keepSize);
            }
        }

        usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);

        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }
}
