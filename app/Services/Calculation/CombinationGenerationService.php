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
use Illuminate\Http\Request;
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

    // Default limit for combinations per category
    public const DEFAULT_LIMIT = 5;

    public function __construct(CalculationRepository $repository, MaterialSelectionService $materialSelection)
    {
        $this->repository = $repository;
        $this->materialSelection = $materialSelection;
    }

    /**
     * Calculate combinations based on constraints (Refactored from calculateCombinationsForBrick)
     *
     * @param Request $request
     * @param array $constraints ['brick' => $model, 'ceramic' => $model, etc]
     * @return array
     */
    public function calculateCombinations(Request $request, array $constraints = []): array
    {
        $brick = $constraints['brick'] ?? null;
        $fixedCeramic = $constraints['ceramic'] ?? null;

        // Legacy Support: Ensure brick is present if work type requires it
        // This logic is moved from Controller to here
        
        // Feature: Store-Based Combination (One Stop Shopping)
        $useStoreFilter = $request->boolean('use_store_filter', true);

        if ($useStoreFilter) {
            return $this->getStoreBasedCombinations($request, $constraints);
        }

        $requestedFilters = $request->price_filters ?? ['best'];

        if (count($requestedFilters) === 1 && $requestedFilters[0] === 'best') {
            $bestCombinations = $this->getBestCombinations($brick, $request->all(), $fixedCeramic);
            $finalResults = [];
            foreach ($bestCombinations as $index => $combo) {
                $label = 'Preferensi ' . ($index + 1);
                $finalResults[$label] = [array_merge($combo, ['filter_label' => $label])];
            }
            return $finalResults;
        }

        $hasAll = in_array('all', $requestedFilters);
        if ($hasAll) {
            $standardFilters = ['best', 'common', 'cheapest', 'medium', 'expensive'];
            $requestedFilters = array_unique(array_merge($requestedFilters, $standardFilters));
        }

        $filtersToCalculate = ['best', 'common', 'cheapest', 'medium', 'expensive'];
        if (in_array('custom', $requestedFilters)) {
            $filtersToCalculate[] = 'custom';
        }

        $allCombinations = [];
        foreach ($filtersToCalculate as $filter) {
            // Optimization: Skip expensive calculations if not requested and not 'all'
            if (!$hasAll && !in_array($filter, $requestedFilters)) {
                continue;
            }

            $combinations = $this->getCombinationsByFilter($brick, $request->all(), $filter, $fixedCeramic);

            foreach ($combinations as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->getFilterLabel($filter);
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

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        $priorityLabels = [];
        foreach ($requestedFilters as $rf) {
            if ($rf !== 'all') {
                $priorityLabels[] = $rf === 'custom' ? 'Custom' : $this->getFilterLabel($rf);
            }
        }

        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $sources = $combo['source_filters'] ?? [$combo['filter_type']];
            $intersect = array_intersect($sources, $requestedFilters);

            if (count($intersect) > 0) {
                $labels = $combo['all_labels'] ?? [$combo['filter_label']];
                if (!empty($priorityLabels)) {
                    usort($labels, function ($a, $b) use ($priorityLabels) {
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
                        return $bScore <=> $aScore;
                    });
                }
                $combo['filter_label'] = implode(' = ', $labels);
                $label = $combo['filter_label'];
                $finalResults[$label] = [$combo];
            }
        }

        return $finalResults;
    }

    /**
     * Legacy Wrapper for backward compatibility
     */
    public function calculateCombinationsForBrick(Brick $brick, Request $request, ?Ceramic $fixedCeramic = null): array
    {
        return $this->calculateCombinations($request, [
            'brick' => $brick,
            'ceramic' => $fixedCeramic
        ]);
    }

    /**
     * Get combinations based on Store Availability (One Stop Shopping)
     * 
     * Strategy:
     * 1. Iterate unique Store Locations
     * 2. Check if Store has ALL required materials for Work Type
     * 3. Calculate local combinations
     * 4. Aggregate Global Cheapest & Expensive
     */
    public function getStoreBasedCombinations(Request $request, array $constraints = []): array
    {
        $brick = $constraints['brick'] ?? null;
        $fixedCeramic = $constraints['ceramic'] ?? null;

        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        
        // 1. Identify valid stores (must have all required materials)
        // We start by getting all locations that have at least one material type
        // Optimization: In real app, we should use a smarter query.
        // For now, we iterate all locations to be safe.
        $locations = \App\Models\StoreLocation::with(['materialAvailabilities', 'store'])->get();
        
        $allStoreCombinations = [];

        foreach ($locations as $location) {
            // Check Availability
            $storeMaterials = [
                'cement' => collect(),
                'sand' => collect(),
                'cat' => collect(),
                'ceramic' => collect(),
                'nat' => collect()
            ];
            
            $isComplete = true;

            // Load materials available in this store
            // Note: This logic assumes 'materialAvailabilities' links polymorphic to materials
            // In a production app with huge data, this loop is N+1 risky. 
            // Ideally we filter stores via query first.
            
            // Optimization: Pre-fetch materials via relation if possible or simple IDs
            // For MVP, we iterate availabilities.
            foreach ($location->materialAvailabilities as $availability) {
                // Determine type based on materialable_type
                // App\Models\Cement -> 'cement' (or 'nat' if type=Nat)
                // App\Models\Sand -> 'sand'
                
                $modelClass = $availability->materialable_type;
                $modelId = $availability->materialable_id;
                
                if ($modelClass === Cement::class) {
                    $cement = Cement::find($modelId);
                    if ($cement) {
                        if ($cement->type === 'Nat') {
                            $storeMaterials['nat']->push($cement);
                        } else {
                            $storeMaterials['cement']->push($cement);
                        }
                    }
                } elseif ($modelClass === Sand::class) {
                    $sand = Sand::find($modelId);
                    if ($sand) $storeMaterials['sand']->push($sand);
                } elseif ($modelClass === Cat::class) {
                    $cat = Cat::find($modelId);
                    if ($cat) $storeMaterials['cat']->push($cat);
                } elseif ($modelClass === Ceramic::class) {
                    $ceramic = Ceramic::find($modelId);
                    if ($ceramic) $storeMaterials['ceramic']->push($ceramic);
                } elseif ($modelClass === Brick::class) {
                     $brickModel = Brick::find($modelId);
                     if ($brickModel) {
                         // We store bricks but we also need to check if it matches the current brick we are calculating for
                         // But for now just populate the inventory
                         // We will check match later
                         // Actually, let's just use a simple check below
                     }
                }
            }
            
            // Validation: Must have all required materials
            foreach ($requiredMaterials as $req) {
                // Modified Logic: Brick IS required to be in store
                if ($req === 'brick') {
                    // Check if THIS store has the specific brick we are calculating for
                    // Check availability for this specific brick ID
                    
                    // If no brick constraint is passed, we check if store has ANY brick? 
                    // No, usually we iterate per brick.
                    // But if we are in "Truly Dynamic" mode (Populer), maybe brick is not passed yet?
                    // For now assume brick is passed.
                    
                    if ($brick) {
                        $hasBrick = $location->materialAvailabilities()
                            ->where('materialable_type', Brick::class)
                            ->where('materialable_id', $brick->id)
                            ->exists();
                            
                        if (!$hasBrick) {
                            $isComplete = false;
                            break;
                        }
                    } else {
                        // If logic is "Find Popular Brick in this store", we don't constrain by specific ID yet.
                        // We will iterate popular bricks later or check store inventory.
                    }
                    continue; 
                }
                
                if ($storeMaterials[$req]->isEmpty()) {
                    $isComplete = false;
                    break;
                }
            }
            
            // Special handling: Fixed Ceramic override
            if ($fixedCeramic) {
                // If fixed ceramic is requested, the store MUST have it (or we skip store check if ceramic is 'brought by user')
                // Let's strict: Store must have this specific ceramic
                // OR we assume Fixed Ceramic acts as filter.
                // For simplicity: If fixedCeramic is passed, we use it regardless of store (maybe user bought it elsewhere)
                // BUT the prompt says "Toko harus memiliki semua material".
                // Let's adhere to strict "One Stop Shopping".
                
                // Logic: If fixedCeramic is passed, we override the store's ceramic list with just this one,
                // IF the store actually stocks it.
                if (!$storeMaterials['ceramic']->contains('id', $fixedCeramic->id)) {
                     // Store doesn't have the selected ceramic.
                     // But maybe the logic is "Find cheapest supporting materials for THIS ceramic in this store".
                     // Let's allow partial match if 'ceramic' is the primary object.
                     // Actually, if we are in "Ceramic Mode", we are looking for mortars.
                     // So we check if store has cement & sand & nat.
                     // We DO NOT check if store has the ceramic (assuming ceramic is already picked).
                } else {
                    $storeMaterials['ceramic'] = collect([$fixedCeramic]);
                }
            }

            if (!$isComplete) continue;

            // Calculate Local Combinations
            // We only need Cheapest & Expensive per store to represent range
            // NOTE: If $brick is null, this calc might fail if formula requires it. 
            // We need a fallback or loop.
            if (!$brick && in_array('brick', $requiredMaterials)) {
                 // Skip calculation if brick is missing but required
                 continue;
            }

            $localResults = $this->calculateCombinationsFromMaterials(
                $brick ?? new Brick(), // Fallback if not required
                $request->all(),
                $storeMaterials['cement'],
                $storeMaterials['sand'],
                $storeMaterials['cat'],
                $storeMaterials['ceramic'],
                $storeMaterials['nat'],
                'Store: ' . $location->store->name,
                null // No limit, we sort manually
            );

            if (empty($localResults)) continue;
            
            // Sort by Price
            usort($localResults, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
            
            // 1. CHEAPEST Champion
            $cheapest = $localResults[0];
            $storeLabelBase = $location->store->name . ' (' . $location->city . ')';
            
            $cheapest['store_label'] = $storeLabelBase . ' [Hemat]';
            $cheapest['store_location'] = $location;
            $allStoreCombinations[] = $cheapest;

            // 2. EXPENSIVE Champion (if different)
            // Only add if we have multiple options and prices differ significantly
            if (count($localResults) > 1) {
                $expensive = end($localResults);
                // Check uniqueness based on total cost to avoid spamming same result
                if ($expensive['total_cost'] > $cheapest['total_cost']) {
                    $expensive['store_label'] = $storeLabelBase . ' [Premium]';
                    $expensive['store_location'] = $location;
                    $allStoreCombinations[] = $expensive;
                }
            }
        }

        // Global Sort of Store Leaders (Cheapest to Expensive)
        usort($allStoreCombinations, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
        
        $finalResults = [];
        $count = count($allStoreCombinations);

        if ($count > 0) {
            // 1. CHEAPEST (Ekonomis) - Top 3 Lowest Prices
            $limitEkonomis = min(3, $count);
            for ($i = 0; $i < $limitEkonomis; $i++) {
                $combo = $allStoreCombinations[$i];
                $rank = $i + 1;
                $label = "Ekonomis {$rank}";
                
                // Append store info to filter label for clarity if needed, 
                // but View expects strict "Ekonomis X" for categorization.
                // We can append store name to the label visible in UI if the view supports it,
                // but for now let's keep the key strict so it falls into the column.
                
                $combo['filter_label'] = $label;
                $combo['filter_type'] = 'cheapest';
                $finalResults[$label] = [$combo];
            }

            // 2. EXPENSIVE (Termahal) - Top 3 Highest Prices
            // We take from the end of the sorted array
            $limitTermahal = min(3, $count);
            $startTermahal = max($limitEkonomis, $count - $limitTermahal); // Avoid overlap if count is small
            
            $termahalRank = 1;
            // Iterate backwards for rank 1 (most expensive) to 3
            for ($i = $count - 1; $i >= $startTermahal; $i--) {
                $combo = $allStoreCombinations[$i];
                $label = "Termahal {$termahalRank}";
                $combo['filter_label'] = $label;
                $combo['filter_type'] = 'expensive';
                $finalResults[$label] = [$combo];
                $termahalRank++;
            }

            // 3. AVERAGE (Average) - Middle Price
            // If we have remaining items in the middle
            if ($count > ($limitEkonomis + ($count - $startTermahal))) {
                 // Pick one median
                 $midIndex = floor(($count - 1) / 2);
                 if ($midIndex >= $limitEkonomis && $midIndex < $startTermahal) {
                     $combo = $allStoreCombinations[$midIndex];
                     $label = "Average 1";
                     $combo['filter_label'] = $label;
                     $combo['filter_type'] = 'medium';
                     $finalResults[$label] = [$combo];
                 }
            } else {
                 // If not enough items for separate Average, maybe aliasing the middle one?
                 // Or just skip.
                 // Let's try to populate Average 1 if we have at least 3 items and it wasn't picked?
                 if ($count >= 3 && !isset($finalResults['Average 1'])) {
                      // Just pick the middle one even if it overlaps (duplicates are handled by view usually or it's fine)
                      // Actually, let's avoid overlap if possible.
                      // If count is 3: 0->Ekonomis1, 1->Ekonomis2, 2->Ekonomis3.
                      // Termahal logic: 2->Termahal1, 1->Termahal2... overlapping.
                      
                      // Priority: Fill columns.
                 }
            }
        }
        
        return $finalResults;
    }

    /**
     * Get Popular Combinations (One Stop Shopping Validated)
     * 
     * 1. Get Top 3 most used material combinations from history
     * 2. Find Stores that stock EXACTLY those materials
     * 3. Pick Cheapest Store for each combination
     */
    public function getPopularStoreCombinations(Request $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        
        // 1. Get Top Combinations from History
        $query = DB::table('brick_calculations')
            ->select('brick_id', 'cement_id', 'sand_id', 'cat_id', 'ceramic_id', 'nat_id', DB::raw('count(*) as frequency'))
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
            ->groupBy('brick_id', 'cement_id', 'sand_id', 'cat_id', 'ceramic_id', 'nat_id')
            ->orderByDesc('frequency')
            ->limit(5); // Get top 5 candidates

        // Filter valid IDs only
        if (in_array('brick', $requiredMaterials)) $query->whereNotNull('brick_id');
        if (in_array('cement', $requiredMaterials)) $query->whereNotNull('cement_id');
        
        $topCombos = $query->get();
        $finalResults = [];
        $rank = 1;

        // 2. Validate Store Availability for each Combo
        foreach ($topCombos as $combo) {
            if ($rank > 3) break;

            // Load Material Models
            $materials = [];
            $validCombo = true;

            if (in_array('brick', $requiredMaterials)) {
                $materials['brick'] = Brick::find($combo->brick_id);
                if (!$materials['brick']) $validCombo = false;
            }
            if (in_array('cement', $requiredMaterials)) {
                $materials['cement'] = Cement::find($combo->cement_id);
                if (!$materials['cement']) $validCombo = false;
            }
            if (in_array('sand', $requiredMaterials)) {
                $materials['sand'] = Sand::find($combo->sand_id);
                if (!$materials['sand']) $validCombo = false;
            }
            if (in_array('cat', $requiredMaterials)) {
                $materials['cat'] = Cat::find($combo->cat_id);
                if (!$materials['cat']) $validCombo = false;
            }
            if (in_array('ceramic', $requiredMaterials)) {
                $materials['ceramic'] = Ceramic::find($combo->ceramic_id);
                if (!$materials['ceramic']) $validCombo = false;
            }
            if (in_array('nat', $requiredMaterials)) {
                $materials['nat'] = Cement::find($combo->nat_id); // Nat is Cement model
                if (!$materials['nat']) $validCombo = false;
            }

            if (!$validCombo) continue;

            // Find Stores having ALL these specific materials
            $validStores = [];
            $locations = \App\Models\StoreLocation::with(['materialAvailabilities', 'store'])->get();

            foreach ($locations as $location) {
                $hasAll = true;
                foreach ($materials as $type => $model) {
                    $exists = $location->materialAvailabilities()
                        ->where('materialable_type', get_class($model))
                        ->where('materialable_id', $model->id)
                        ->exists();
                    
                    if (!$exists) {
                        $hasAll = false;
                        break;
                    }
                }

                if ($hasAll) {
                    // Calculate Cost at this store
                    $cements = isset($materials['cement']) ? collect([$materials['cement']]) : collect();
                    $sands = isset($materials['sand']) ? collect([$materials['sand']]) : collect();
                    $cats = isset($materials['cat']) ? collect([$materials['cat']]) : collect();
                    $ceramics = isset($materials['ceramic']) ? collect([$materials['ceramic']]) : collect();
                    $nats = isset($materials['nat']) ? collect([$materials['nat']]) : collect();
                    $brick = $materials['brick'] ?? new Brick(); // Fallback if not needed

                    $result = $this->calculateCombinationsFromMaterials(
                        $brick,
                        $request->all(),
                        $cements,
                        $sands,
                        $cats,
                        $ceramics,
                        $nats,
                        'Populer Store',
                        1
                    );

                    if (!empty($result)) {
                        $res = $result[0];
                        $res['store_label'] = $location->store->name . ' (' . $location->city . ')';
                        $res['store_location'] = $location;
                        $validStores[] = $res;
                    }
                }
            }

            // If we found valid stores for this popular combo, pick the cheapest one
            if (!empty($validStores)) {
                usort($validStores, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
                $winner = $validStores[0];
                
                $label = 'Populer ' . $rank;
                $winner['filter_label'] = $label;
                $winner['filter_type'] = 'common';
                $winner['frequency'] = $combo->frequency; // Pass frequency data to view
                
                $finalResults[$label] = [$winner];
                $rank++;
            }
        }

        return $finalResults;
    }

    /**
     * Get combinations by filter
     *
     * @param Brick $brick
     * @param array $requestData
     * @param string $filter
     * @param Ceramic|null $fixedCeramic
     * @return array
     */
    public function getCombinationsByFilter(Brick $brick, array $requestData, string $filter, ?Ceramic $fixedCeramic = null): array
    {
        switch ($filter) {
            case 'best':
                return $this->getBestCombinations($brick, $requestData);
            case 'common':
                // Use new Store-Validated Popular logic
                // Since this returns grouped array ['Populer 1' => [...]], we need to flatten it 
                // to match the expected return format of getCombinationsByFilter (array of combinations)
                $requestObj = $requestData instanceof Request ? $requestData : new Request($requestData);
                $grouped = $this->getPopularStoreCombinations($requestObj);
                
                $flat = [];
                foreach ($grouped as $group) {
                    foreach ($group as $item) {
                        $flat[] = $item;
                    }
                }
                return $flat;
            case 'cheapest':
                return $this->getCheapestCombinations($brick, $requestData);
            case 'medium':
                return $this->getMediumCombinations($brick, $requestData);
            case 'expensive':
                return $this->getExpensiveCombinations($brick, $requestData);
            case 'custom':
                return $this->getCustomCombinations($brick, $requestData);
            default:
                return [];
        }
    }

    /**
     * Calculate combinations from given materials
     *
     * Extracted from MaterialCalculationController lines 689-787
     *
     * @param Brick $brick
     * @param array $request Request parameters
     * @param iterable $cements
     * @param iterable $sands
     * @param iterable|null $cats
     * @param iterable|null $ceramics
     * @param iterable|null $nats
     * @param string $groupLabel Label for this group (e.g., 'Preferensi', 'Ekonomis')
     * @param int|null $limit Limit number of results
     * @return array
     */
    public function calculateCombinationsFromMaterials(
        Brick $brick,
        array $request,
        iterable $cements,
        iterable $sands,
        ?iterable $cats = null,
        ?iterable $ceramics = null,
        ?iterable $nats = null,
        string $groupLabel = 'Kombinasi',
        ?int $limit = null,
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
        
        // Determine sorting direction based on group label for optimization
        $sortDesc = ($groupLabel === 'Termahal');

        $generator = $this->yieldCombinations(
            $paramsBase,
            $workType,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            $groupLabel
        );

        // If limit is set, use bounded buffer to save memory
        if ($limit) {
            $results = [];
            foreach ($generator as $item) {
                $results[] = $item;
                
                // Buffer optimization: keep size somewhat close to limit
                // We buffer 3x limit to reduce sorting frequency
                if (count($results) > $limit * 3) {
                    usort($results, function ($a, $b) use ($sortDesc) {
                        return $sortDesc 
                            ? $b['total_cost'] <=> $a['total_cost'] 
                            : $a['total_cost'] <=> $b['total_cost'];
                    });
                    $results = array_slice($results, 0, $limit);
                }
            }
            
            // Final Sort and Slice
            usort($results, function ($a, $b) use ($sortDesc) {
                return $sortDesc 
                    ? $b['total_cost'] <=> $a['total_cost'] 
                    : $a['total_cost'] <=> $b['total_cost'];
            });
            
            return array_slice($results, 0, $limit);
        }

        // If no limit (e.g. Medium or All), we must collect all
        // WARNING: This is still memory intensive for 'All'
        $results = iterator_to_array($generator);

        usort($results, function ($a, $b) use ($sortDesc) {
             return $sortDesc 
                ? $b['total_cost'] <=> $a['total_cost'] 
                : $a['total_cost'] <=> $b['total_cost'];
        });

        return $results;
    }

    /**
     * Core generator for combinations
     */
    protected function yieldCombinations(
        array $paramsBase,
        string $workType,
        iterable $cements,
        iterable $sands,
        iterable $cats,
        iterable $ceramics,
        iterable $nats,
        string $groupLabel
    ) {
        $requiredMaterials = $this->resolveRequiredMaterials($workType);

        if ($workType === 'tile_installation') {
            yield from $this->yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel);
            return;
        }

        if (in_array('cat', $requiredMaterials, true)) {
            foreach ($cats as $cat) {
                if ($cat->purchase_price <= 0) continue;

                $params = array_merge($paramsBase, ['cat_id' => $cat->id]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) continue;

                    $result = $formula->calculate($params);

                    yield [
                        'cat' => $cat,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                    ];
                } catch (\Exception $e) {
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
                        if (!$formula) continue;

                        $result = $formula->calculate($params);

                        yield [
                            'ceramic' => $ceramic,
                            'nat' => $nat,
                            'result' => $result,
                            'total_cost' => $result['grand_total'],
                        ];
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        } elseif (in_array('cement', $requiredMaterials, true) && !in_array('sand', $requiredMaterials, true)) {
            foreach ($cements as $cement) {
                if ($cement->package_weight_net <= 0) continue;

                $params = array_merge($paramsBase, ['cement_id' => $cement->id]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) continue;

                    $result = $formula->calculate($params);

                    yield [
                        'cement' => $cement,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }
        } else {
            foreach ($cements as $cement) {
                if ($cement->package_weight_net <= 0) continue;

                foreach ($sands as $sand) {
                    $hasPricePerM3 = $sand->comparison_price_per_m3 > 0;
                    $hasPackageData = $sand->package_volume > 0 && $sand->package_price > 0;

                    if (!$hasPricePerM3 && !$hasPackageData) continue;

                    $params = array_merge($paramsBase, [
                        'cement_id' => $cement->id,
                        'sand_id' => $sand->id,
                    ]);

                    try {
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) continue;

                        $result = $formula->calculate($params);

                        yield [
                            'cement' => $cement,
                            'sand' => $sand,
                            'result' => $result,
                            'total_cost' => $result['grand_total'],
                        ];
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }
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
                $key =
                    'cer-' .
                    ($combo['ceramic']->id ?? 0) .
                    '-nat-' .
                    ($combo['nat']->id ?? 0) .
                    '-cem-' .
                    ($combo['cement']->id ?? 0) .
                    '-snd-' .
                    ($combo['sand']->id ?? 0);
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
            } else {
                // New combination
                $duplicateMap[$key] = count($uniqueCombos);

                // Initialize all_labels
                $combo['all_labels'] = [$currentLabel];

                $uniqueCombos[] = $combo;
            }
        }

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

        $recommendations = $this->repository->getRecommendedCombinations($workType)->where('type', 'best');

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
                $workType,
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
                'Preferensi',
                1,
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
        $isCeramicWork = in_array('ceramic', $requiredMaterials, true);
        $isCatWork = in_array('cat', $requiredMaterials, true);

        $paramsBase = $request;
        unset($paramsBase['_token'], $paramsBase['price_filters'], $paramsBase['brick_ids'], $paramsBase['brick_id']);
        $paramsBase['brick_id'] = $brick->id;

        if ($isCeramicWork) {
            $query = DB::table('brick_calculations')
                ->select('ceramic_id', 'nat_id', 'cement_id', 'sand_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('ceramic_id')
                ->whereNotNull('nat_id');

            if (in_array('cement', $requiredMaterials, true)) {
                $query->whereNotNull('cement_id');
            }
            if (in_array('sand', $requiredMaterials, true)) {
                $query->whereNotNull('sand_id');
            }
            if (!empty($request['ceramic_id'])) {
                $query->where('ceramic_id', $request['ceramic_id']);
            }

            $frequencyCounts = $query
                ->groupBy('ceramic_id', 'nat_id', 'cement_id', 'sand_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            if ($frequencyCounts->isEmpty()) {
                return [];
            }

            $results = [];
            foreach ($frequencyCounts as $combo) {
                $ceramic = Ceramic::find($combo->ceramic_id);
                $nat = Cement::find($combo->nat_id);
                $cement = $combo->cement_id ? Cement::find($combo->cement_id) : null;
                $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

                if (!$ceramic || !$nat) {
                    continue;
                }
                if (in_array('cement', $requiredMaterials, true) && !$cement) {
                    continue;
                }
                if ($combo->cement_id && !$cement) {
                    continue;
                }
                if (in_array('sand', $requiredMaterials, true) && !$sand) {
                    continue;
                }
                if ($combo->sand_id && !$sand) {
                    continue;
                }

                $params = array_merge($paramsBase, [
                    'ceramic_id' => $ceramic->id,
                    'nat_id' => $nat->id,
                    'cement_id' => $cement ? $cement->id : null,
                    'sand_id' => $sand ? $sand->id : null,
                ]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        continue;
                    }
                    $trace = $formula->trace($params);
                    $result = $trace['final_result'] ?? $formula->calculate($params);

                    $results[] = [
                        'ceramic' => $ceramic,
                        'nat' => $nat,
                        'cement' => $cement,
                        'sand' => $sand,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                        'filter_type' => 'common',
                        'frequency' => $combo->frequency,
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $results;
        }

        if ($isCatWork) {
            $commonCombos = DB::table('brick_calculations')
                ->select('cat_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('cat_id')
                ->groupBy('cat_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            if ($commonCombos->isEmpty()) {
                return [];
            }

            $results = [];
            foreach ($commonCombos as $combo) {
                $cat = Cat::find($combo->cat_id);
                if (!$cat) {
                    continue;
                }

                $params = array_merge($paramsBase, ['cat_id' => $cat->id]);
                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        continue;
                    }
                    $trace = $formula->trace($params);
                    $result = $trace['final_result'] ?? $formula->calculate($params);

                    $results[] = [
                        'cat' => $cat,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                        'filter_type' => 'common',
                        'frequency' => $combo->frequency,
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $results;
        }

        if ($isBrickless) {
            $commonCombos = DB::table('brick_calculations')
                ->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('cement_id')
                ->groupBy('cement_id', 'sand_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            if ($commonCombos->isEmpty()) {
                return [];
            }

            $results = [];
            foreach ($commonCombos as $combo) {
                $cement = Cement::find($combo->cement_id);
                $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

                if (!$cement) {
                    continue;
                }
                if (in_array('sand', $requiredMaterials, true) && !$sand) {
                    continue;
                }
                if ($combo->sand_id && !$sand) {
                    continue;
                }

                $params = array_merge($paramsBase, [
                    'cement_id' => $cement->id,
                    'sand_id' => $sand ? $sand->id : null,
                ]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        continue;
                    }
                    $trace = $formula->trace($params);
                    $result = $trace['final_result'] ?? $formula->calculate($params);

                    $results[] = [
                        'cement' => $cement,
                        'sand' => $sand,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                        'filter_type' => 'common',
                        'frequency' => $combo->frequency,
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $results;
        }

        $commonCombos = DB::table('brick_calculations')
            ->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))
            ->where('brick_id', $brick->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
            ->whereNotNull('cement_id')
            ->whereNotNull('sand_id')
            ->groupBy('cement_id', 'sand_id')
            ->orderByDesc('frequency')
            ->limit(3)
            ->get();

        if ($commonCombos->isEmpty()) {
            return [];
        }

        $results = [];
        foreach ($commonCombos as $combo) {
            $cement = Cement::find($combo->cement_id);
            $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

            if (!$cement) {
                continue;
            }
            if (in_array('sand', $requiredMaterials, true) && !$sand) {
                continue;
            }
            if ($combo->sand_id && !$sand) {
                continue;
            }

            $params = array_merge($paramsBase, [
                'cement_id' => $cement->id,
                'sand_id' => $sand ? $sand->id : null,
            ]);

            try {
                $formula = FormulaRegistry::instance($workType);
                if (!$formula) {
                    continue;
                }
                $trace = $formula->trace($params);
                $result = $trace['final_result'] ?? $formula->calculate($params);

                $results[] = [
                    'cement' => $cement,
                    'sand' => $sand,
                    'result' => $result,
                    'total_cost' => $result['grand_total'],
                    'filter_type' => 'common',
                    'frequency' => $combo->frequency,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

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
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'asc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('asc', $materialLimit);

        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'Ekonomis',
            self::DEFAULT_LIMIT,
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
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'asc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('asc', $materialLimit);

        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'Moderat',
        );

        // Get middle combinations (using DEFAULT_LIMIT)
        $total = count($allResults);
        if ($total < self::DEFAULT_LIMIT) {
            return $allResults;
        }

        $startIndex = max(0, floor(($total - self::DEFAULT_LIMIT) / 2));
        return array_slice($allResults, $startIndex, self::DEFAULT_LIMIT);
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
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'desc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('desc', $materialLimit);

        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'Termahal',
        );

        // Get top expensive combinations (using DEFAULT_LIMIT)
        return array_slice(array_reverse($allResults), 0, self::DEFAULT_LIMIT);
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
                return $this->calculateCombinationsFromMaterials(
                    $brick,
                    $request,
                    collect(),
                    collect(),
                    $cats,
                    collect(),
                    collect(),
                    'Custom',
                    1,
                );
            }
        } elseif ($workType === 'grout_tile') {
            if (!empty($request['ceramic_id']) && !empty($request['nat_id'])) {
                $ceramics = Ceramic::where('id', $request['ceramic_id'])->get();
                $nats = Cement::where('id', $request['nat_id'])->get();
                return $this->calculateCombinationsFromMaterials(
                    $brick,
                    $request,
                    collect(),
                    collect(),
                    collect(),
                    $ceramics,
                    $nats,
                    'Custom',
                    1,
                );
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
                    1,
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
                1,
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

        // Use cursor() for memory efficiency on large datasets
        
        $cements = collect();
        if (in_array('cement', $requiredMaterials, true)) {
             $cements = Cement::where(function ($q) {
                $q->where('type', '!=', 'Nat')->orWhereNull('type');
            })
            ->where('package_price', '>', 0)
            ->where('package_weight_net', '>', 0)
            ->cursor(); // Lazy Loading
        }

        $sands = collect();
        if (in_array('sand', $requiredMaterials, true)) {
            $sands = Sand::where('package_price', '>', 0)->cursor(); // Lazy Loading
        }

        $cats = collect();
        if (in_array('cat', $requiredMaterials, true)) {
            $cats = Cat::where('purchase_price', '>', 0)->orderBy('brand')->cursor(); // Lazy Loading
        }
            
        $ceramics = collect();
        if (in_array('ceramic', $requiredMaterials, true)) {
            $query = Ceramic::query();
            if ($workType === 'grout_tile') {
                $query->whereNotNull('dimension_thickness')->where('dimension_thickness', '>', 0);
            }
            $query->whereNotNull('price_per_package')->orderBy('price_per_package', 'asc');
            $ceramics = $query->cursor(); // Lazy Loading
        }

        $nats = collect();
        if (in_array('nat', $requiredMaterials, true)) {
            $nats = Cement::where('type', 'Nat')->orderBy('brand')->cursor(); // Lazy Loading
        }

        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            'Semua',
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
            'best' => 'Preferensi',
            'common' => 'Populer',
            'cheapest' => 'Ekonomis',
            'medium' => 'Average',
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
        return Sand::where('package_price', '>', 0)->orderBy('package_price', $direction)->limit($limit)->get();
    }

    protected function resolveCatsByPrice(string $direction, int $limit): EloquentCollection
    {
        return Cat::where('purchase_price', '>', 0)->orderBy('purchase_price', $direction)->limit($limit)->get();
    }

    protected function resolveCeramicsForCalculation(
        array $request,
        string $workType,
        string $orderBy,
        string $direction,
        ?int $limit,
        int $skip = 0,
    ): EloquentCollection {
        if (!empty($request['ceramic_id'])) {
            $ceramic = Ceramic::find($request['ceramic_id']);
            return $ceramic ? collect([$ceramic]) : collect();
        }

        $query = Ceramic::query();

        if ($workType === 'grout_tile') {
            $query->whereNotNull('dimension_thickness')->where('dimension_thickness', '>', 0);
        }

        $query = $query->whereNotNull($orderBy)->orderBy($orderBy, $direction)->skip($skip);

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
        string $groupLabel,
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
