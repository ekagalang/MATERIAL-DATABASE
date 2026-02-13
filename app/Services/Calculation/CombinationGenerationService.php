<?php

namespace App\Services\Calculation;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
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

    protected StoreProximityService $storeProximityService;

    protected array $storeMaterialCache = [];

    // Default limit for combinations per category
    public const DEFAULT_LIMIT = 5;

    public function __construct(
        CalculationRepository $repository,
        MaterialSelectionService $materialSelection,
        StoreProximityService $storeProximityService,
    )
    {
        $this->repository = $repository;
        $this->materialSelection = $materialSelection;
        $this->storeProximityService = $storeProximityService;
    }

    protected function normalizeMaterialTypeFilterValues($value): array
    {
        if (is_array($value)) {
            $flattened = [];
            foreach ($value as $item) {
                $flattened = array_merge($flattened, $this->normalizeMaterialTypeFilterValues($item));
            }

            return array_values(array_unique($flattened));
        }

        if ($value === null) {
            return [];
        }

        $text = trim((string) $value);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s*\|\s*/', $text) ?: [];
        $tokens = array_values(
            array_filter(array_map(static fn($part) => trim((string) $part), $parts), static fn($part) => $part !== ''),
        );

        return array_values(array_unique($tokens));
    }

    protected function normalizeNatTypeFilterValues($value): array
    {
        $values = $this->normalizeMaterialTypeFilterValues($value);
        if (empty($values)) {
            return [];
        }

        // Backward compatibility: plain "Nat" means no specific nat type filter.
        $values = array_values(
            array_filter($values, static function ($item) {
                return strtolower(trim((string) $item)) !== 'nat';
            }),
        );

        return array_values(array_unique($values));
    }

    protected function buildGroutTileFallbackCeramic(array $params): Ceramic
    {
        $ceramic = new Ceramic();
        $ceramic->brand = 'Input Keramik';
        $ceramic->type = 'Input';
        $ceramic->dimension_length = $params['ceramic_length'] ?? null;
        $ceramic->dimension_width = $params['ceramic_width'] ?? null;
        $ceramic->dimension_thickness = $params['ceramic_thickness'] ?? null;
        $ceramic->price_per_package = $params['ceramic_price_per_package'] ?? 0;
        $ceramic->pieces_per_package = $params['ceramic_pieces_per_package'] ?? 0;

        return $ceramic;
    }

    protected function normalizeStoreName(?string $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s*\(.*?\)\s*/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return strtolower(trim((string) $text));
    }

    protected function getMaterialsByStoreName(string $modelClass, string $storeNameNorm)
    {
        if ($storeNameNorm === '') {
            return collect();
        }

        if (!isset($this->storeMaterialCache[$modelClass])) {
            $this->storeMaterialCache[$modelClass] = $modelClass::query()->whereNotNull('store')->get();
        }

        return $this->storeMaterialCache[$modelClass]
            ->filter(function ($item) use ($storeNameNorm) {
                return $this->normalizeStoreName($item->store ?? '') === $storeNameNorm;
            })
            ->values();
    }

    protected function collectMaterialsForStoreGroup(string $modelClass, $locationIds, string $storeNameNorm)
    {
        $items = collect();
        if ($locationIds && $locationIds->isNotEmpty()) {
            $items = $items->merge($modelClass::query()->whereIn('store_location_id', $locationIds)->get());
        }
        if ($storeNameNorm !== '') {
            $items = $items->merge($this->getMaterialsByStoreName($modelClass, $storeNameNorm));
        }

        return $items->unique('id')->values();
    }

    protected function normalizeCeramicSizeToken(string $value): string
    {
        return strtolower(str_replace(['Ã—', '×', ','], ['x', 'x', '.'], trim($value)));
    }

    protected function matchesMaterialTypeFilter(?string $actualValue, $filterValue): bool
    {
        $filterValues = $this->normalizeMaterialTypeFilterValues($filterValue);
        if (empty($filterValues)) {
            return true;
        }
        if ($actualValue === null || $actualValue === '') {
            return false;
        }

        return in_array($actualValue, $filterValues, true);
    }

    protected function matchesCeramicSizeFilter(?string $actualSize, $filterValue): bool
    {
        $filterValues = $this->normalizeMaterialTypeFilterValues($filterValue);
        if (empty($filterValues)) {
            return true;
        }
        if ($actualSize === null || $actualSize === '') {
            return false;
        }

        $normalizedActual = $this->normalizeCeramicSizeToken($actualSize);
        foreach ($filterValues as $value) {
            if ($normalizedActual === $this->normalizeCeramicSizeToken((string) $value)) {
                return true;
            }
        }

        return false;
    }

    protected function applyTypeFilterToQuery($query, $filterValue, string $column = 'type'): void
    {
        $filterValues = $this->normalizeMaterialTypeFilterValues($filterValue);
        if (empty($filterValues)) {
            return;
        }
        $query->whereIn($column, $filterValues);
    }

    protected function applyCeramicSizeFilterToQuery($query, $filterValue): void
    {
        $filterValues = $this->normalizeMaterialTypeFilterValues($filterValue);
        if (empty($filterValues)) {
            return;
        }

        $dimensionsList = [];
        foreach ($filterValues as $sizeFilter) {
            $normalized = str_replace(',', '.', (string) $sizeFilter);
            $dimensions = array_map('trim', explode('x', strtolower(str_replace(['Ã—', '×'], 'x', $normalized))));
            if (count($dimensions) !== 2) {
                continue;
            }
            $dim1 = (float) $dimensions[0];
            $dim2 = (float) $dimensions[1];
            if ($dim1 > 0 && $dim2 > 0) {
                $dimensionsList[] = [$dim1, $dim2];
            }
        }

        if (empty($dimensionsList)) {
            return;
        }

        $query->where(function ($q) use ($dimensionsList) {
            foreach ($dimensionsList as [$dim1, $dim2]) {
                $q->orWhere(function ($sq) use ($dim1, $dim2) {
                    $sq->where('dimension_length', $dim1)->where('dimension_width', $dim2);
                })->orWhere(function ($sq) use ($dim1, $dim2) {
                    $sq->where('dimension_length', $dim2)->where('dimension_width', $dim1);
                });
            }
        });
    }

    /**
     * Calculate combinations based on constraints (Refactored from calculateCombinationsForBrick)
     *
     * @param  array  $constraints  ['brick' => $model, 'ceramic' => $model, etc]
     */
    public function calculateCombinations(Request $request, array $constraints = []): array
    {
        $brick = $constraints['brick'] ?? null;
        $fixedCeramic = $constraints['ceramic'] ?? null;

        // Legacy Support: Ensure brick is present if work type requires it
        // This logic is moved from Controller to here

        // Feature: Store-Based Combination (One Stop Shopping)
        $workType = $request->input('work_type') ?? $request->input('work_type_select');
        $useStoreFilter = $request->boolean('use_store_filter', true);
        if ($workType === 'grout_tile') {
            $useStoreFilter = false;
        }

        if ($useStoreFilter) {
            $hasProjectCoordinates = is_numeric($request->input('project_latitude')) &&
                is_numeric($request->input('project_longitude'));

            if (!$hasProjectCoordinates) {
                Log::info('Store-based combinations skipped due to missing project coordinates', [
                    'brick_id' => $brick?->id,
                    'brick_brand' => $brick?->brand,
                    'material_type_filters' => $request['material_type_filters'] ?? [],
                ]);

                return [];
            }

            $storeResults = $this->getStoreBasedCombinations($request, $constraints);
            if (!empty($storeResults)) {
                return $storeResults;
            }

            Log::info('Store-based combinations empty', [
                'brick_id' => $brick?->id,
                'brick_brand' => $brick?->brand,
                'material_type_filters' => $request['material_type_filters'] ?? [],
                'allow_mixed_store' => $request->boolean('allow_mixed_store', false),
            ]);

            // Keep strict store/radius behavior: never fallback to global non-store combinations.
            return [];
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
            'ceramic' => $fixedCeramic,
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
        $materialTypeFilters = $request['material_type_filters'] ?? [];
        $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);

        // DEBUG: Log material type filters in store-based combinations
        Log::info('getStoreBasedCombinations - Material Type Filters', [
            'filters' => $materialTypeFilters,
            'work_type' => $workType,
        ]);

        $locations = \App\Models\StoreLocation::with(['materialAvailabilities', 'store'])->get();
        if ($locations->isEmpty()) {
            return $this->getStoreBasedCombinationsByStoreName($request, $constraints);
        }

        $hasProjectCoordinates = is_numeric($request->input('project_latitude')) &&
            is_numeric($request->input('project_longitude'));
        $projectLatitude = $hasProjectCoordinates ? (float) $request->input('project_latitude') : null;
        $projectLongitude = $hasProjectCoordinates ? (float) $request->input('project_longitude') : null;
        $allowStoreNameFallback = !$hasProjectCoordinates;
        $allowMixedStore = $request->boolean('allow_mixed_store', false);

        $rankedLocations = $locations->map(function ($location) {
            return [
                'location' => $location,
                'distance_km' => null,
            ];
        });

        if ($hasProjectCoordinates) {
            $rankedLocations = $this->storeProximityService->sortReachableLocations(
                $locations,
                $projectLatitude,
                $projectLongitude,
            );
        }

        if ($rankedLocations->isEmpty()) {
            return [];
        }

        $allStoreCombinations = [];
        $preparedLocations = [];

        foreach ($rankedLocations as $row) {
            $location = $row['location'];
            $distanceKm = isset($row['distance_km']) ? (float) $row['distance_km'] : null;
            $storeMaterials = $this->extractStoreMaterialsFromAvailability(
                $location,
                $materialTypeFilters,
                $natTypeFilterValues,
            );

            // Fallback: if availability mapping is incomplete, load materials by store_location_id/store name
            $fallbackMaterials = $this->loadStoreMaterialsForLocation(
                $location,
                $materialTypeFilters,
                $natTypeFilterValues,
                $allowStoreNameFallback,
            );
            foreach (['cement', 'sand', 'cat', 'ceramic', 'nat'] as $type) {
                if ($storeMaterials[$type]->isEmpty() && $fallbackMaterials[$type]->isNotEmpty()) {
                    $storeMaterials[$type] = $fallbackMaterials[$type];
                }
            }

            $hasBrickAtLocation = $this->storeHasBrick($location, $brick, $allowStoreNameFallback);

            // DEBUG: Log filtered store materials
            Log::info('Store materials after filtering', [
                'store' => $location->store->name ?? 'Unknown',
                'location' => $location->city ?? 'Unknown',
                'distance_km' => $distanceKm,
                'cement_count' => $storeMaterials['cement']->count(),
                'sand_count' => $storeMaterials['sand']->count(),
                'cat_count' => $storeMaterials['cat']->count(),
                'ceramic_count' => $storeMaterials['ceramic']->count(),
                'nat_count' => $storeMaterials['nat']->count(),
                'cement_types' => $storeMaterials['cement']->pluck('type')->unique()->values()->toArray(),
                'sand_types' => $storeMaterials['sand']->pluck('type')->unique()->values()->toArray(),
                'has_brick' => $hasBrickAtLocation,
            ]);

            // Special handling: Fixed Ceramic override
            $isComplete = true;
            if ($fixedCeramic) {
                // If fixed ceramic is requested, the store MUST have it (or we skip store check if ceramic is 'brought by user')
                // Let's strict: Store must have this specific ceramic
                // OR we assume Fixed Ceramic acts as filter.
                // For simplicity: If fixedCeramic is passed, we use it regardless of store (maybe user bought it elsewhere)
                // BUT the prompt says "Toko harus memiliki semua material".
                // Let's adhere to strict "One Stop Shopping".

                // Logic: If fixedCeramic is passed, we override the store's ceramic list with just this one,
                // IF the store actually stocks it.
                $hasFixedCeramic = $storeMaterials['ceramic']->contains('id', $fixedCeramic->id);
                if (!$hasFixedCeramic && !empty($fixedCeramic->store_location_id)) {
                    $hasFixedCeramic = (int) $fixedCeramic->store_location_id === (int) $location->id;
                }
                if ($allowStoreNameFallback && !$hasFixedCeramic) {
                    $storeName = trim((string) ($location->store->name ?? ''));
                    if ($storeName !== '' && trim((string) ($fixedCeramic->store ?? '')) === $storeName) {
                        $hasFixedCeramic = true;
                    }
                }

                if (!$hasFixedCeramic) {
                    $isComplete = false;
                } else {
                    $storeMaterials['ceramic'] = collect([$fixedCeramic]);
                }
            }

            $preparedLocations[] = [
                'location' => $location,
                'distance_km' => $distanceKm,
                'has_brick' => $hasBrickAtLocation,
                'materials' => $storeMaterials,
            ];

            // Validation: Must have all required materials in one store
            foreach ($requiredMaterials as $req) {
                if ($req === 'brick') {
                    if (!$hasBrickAtLocation) {
                        $isComplete = false;
                        break;
                    }

                    continue;
                }

                if (($storeMaterials[$req] ?? collect())->isEmpty()) {
                    $isComplete = false;
                    break;
                }
            }

            if (!$isComplete) {
                continue;
            }

            // Calculate Local Combinations
            // We only need Cheapest & Expensive per store to represent range
            if (!$brick && in_array('brick', $requiredMaterials)) {
                continue;
            }

            // Cap materials per store to prevent combinatorial explosion
            $storeMaterials = $this->capStoreMaterials($storeMaterials, $workType);

            $localResults = $this->calculateCombinationsFromMaterials(
                $brick ?? new Brick(), // Fallback if not required
                $request->all(),
                $storeMaterials['cement'],
                $storeMaterials['sand'],
                $storeMaterials['cat'],
                $storeMaterials['ceramic'],
                $storeMaterials['nat'],
                'Store: ' . $location->store->name,
                10,
            );

            if (empty($localResults)) {
                continue;
            }

            // Sort by Price
            usort($localResults, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);

            $storeLabelBase = $this->formatStoreLabelBase($location, $distanceKm);
            $isSingleMaterialWork = count($requiredMaterials) <= 1;
            $singleStorePlan = [[
                'store_location_id' => $location->id ?? null,
                'store_name' => $location->store->name ?? 'Unknown',
                'city' => $location->city ?? null,
                'distance_km' => $distanceKm !== null ? round($distanceKm, 3) : null,
                'service_radius_km' => $location->service_radius_km ?? null,
                'provided_materials' => $requiredMaterials,
            ]];

            if ($isSingleMaterialWork) {
                // For single-material work, keep more options per store
                foreach ($localResults as $result) {
                    $result['store_label'] = $storeLabelBase;
                    $result['store_location'] = $location;
                    $result['store_plan'] = $singleStorePlan;
                    $result['store_coverage_mode'] = 'single_store';
                    $result['store_cost_breakdown'] = $this->buildStoreCostBreakdown($result, $singleStorePlan);
                    $allStoreCombinations[] = $result;
                }
            } else {
                // 1. CHEAPEST Champion
                $cheapest = $localResults[0];
                $cheapest['store_label'] = $storeLabelBase . ' [Hemat]';
                $cheapest['store_location'] = $location;
                $cheapest['store_plan'] = $singleStorePlan;
                $cheapest['store_coverage_mode'] = 'single_store';
                $cheapest['store_cost_breakdown'] = $this->buildStoreCostBreakdown($cheapest, $singleStorePlan);
                $allStoreCombinations[] = $cheapest;

                // 2. EXPENSIVE Champion (if different)
                // Only add if we have multiple options and prices differ significantly
                if (count($localResults) > 1) {
                    $expensive = end($localResults);
                    // Check uniqueness based on total cost to avoid spamming same result
                    if ($expensive['total_cost'] > $cheapest['total_cost']) {
                        $expensive['store_label'] = $storeLabelBase . ' [Premium]';
                        $expensive['store_location'] = $location;
                        $expensive['store_plan'] = $singleStorePlan;
                        $expensive['store_coverage_mode'] = 'single_store';
                        $expensive['store_cost_breakdown'] = $this->buildStoreCostBreakdown($expensive, $singleStorePlan);
                        $allStoreCombinations[] = $expensive;
                    }
                }
            }
        }

        if ($allowMixedStore && empty($allStoreCombinations) && $hasProjectCoordinates) {
            $requiresBrick = in_array('brick', $requiredMaterials, true) &&
                $brick !== null &&
                (!empty($brick->store_location_id) || !empty($brick->store));
            $coverage = $this->storeProximityService->buildNearestCoveragePlan(
                collect($preparedLocations),
                $requiredMaterials,
                $requiresBrick,
            );

            if ($coverage['is_complete'] ?? false) {
                $selectedMaterials = $this->capStoreMaterials($coverage['selected_materials'], $workType);
                $localResults = $this->calculateCombinationsFromMaterials(
                    $brick ?? new Brick(),
                    $request->all(),
                    $selectedMaterials['cement'] ?? collect(),
                    $selectedMaterials['sand'] ?? collect(),
                    $selectedMaterials['cat'] ?? collect(),
                    $selectedMaterials['ceramic'] ?? collect(),
                    $selectedMaterials['nat'] ?? collect(),
                    'Store: Gabungan Toko Terdekat',
                    10,
                );

                foreach ($localResults as $result) {
                    $result['store_label'] = 'Gabungan Toko Terdekat';
                    $result['store_plan'] = $coverage['store_plan'] ?? [];
                    $result['store_coverage_mode'] = 'nearest_radius_chain';
                    $result['store_cost_breakdown'] = $this->buildStoreCostBreakdown(
                        $result,
                        $coverage['store_plan'] ?? [],
                    );
                    $allStoreCombinations[] = $result;
                }
            }
        }

        if (empty($allStoreCombinations) && $hasProjectCoordinates) {
            return [];
        }

        if (empty($allStoreCombinations)) {
            return $this->getStoreBasedCombinationsByStoreName($request, $constraints);
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
            if ($count > $limitEkonomis + ($count - $startTermahal)) {
                // Pick one median
                $midIndex = floor(($count - 1) / 2);
                if ($midIndex >= $limitEkonomis && $midIndex < $startTermahal) {
                    $combo = $allStoreCombinations[$midIndex];
                    $label = 'Average 1';
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

    protected function extractStoreMaterialsFromAvailability(
        \App\Models\StoreLocation $location,
        array $materialTypeFilters,
        array $natTypeFilterValues,
    ): array {
        $storeMaterials = [
            'cement' => collect(),
            'sand' => collect(),
            'cat' => collect(),
            'ceramic' => collect(),
            'nat' => collect(),
        ];

        foreach ($location->materialAvailabilities as $availability) {
            $modelClass = $availability->materialable_type;
            $modelId = $availability->materialable_id;

            if ($modelClass === Cement::class) {
                $cement = Cement::find($modelId);
                if ($cement && $this->matchesMaterialTypeFilter($cement->type, $materialTypeFilters['cement'] ?? null)) {
                    $storeMaterials['cement']->push($cement);
                }
                continue;
            }

            if ($modelClass === Nat::class) {
                $nat = Nat::find($modelId);
                if ($nat && $this->matchesMaterialTypeFilter($nat->type ?? null, $natTypeFilterValues)) {
                    $storeMaterials['nat']->push($nat);
                }
                continue;
            }

            if ($modelClass === Sand::class) {
                $sand = Sand::find($modelId);
                if ($sand && $this->matchesMaterialTypeFilter($sand->type, $materialTypeFilters['sand'] ?? null)) {
                    $storeMaterials['sand']->push($sand);
                }
                continue;
            }

            if ($modelClass === Cat::class) {
                $cat = Cat::find($modelId);
                if ($cat && $this->matchesMaterialTypeFilter($cat->type, $materialTypeFilters['cat'] ?? null)) {
                    $storeMaterials['cat']->push($cat);
                }
                continue;
            }

            if ($modelClass !== Ceramic::class) {
                continue;
            }

            $ceramic = Ceramic::find($modelId);
            if (!$ceramic) {
                continue;
            }

            $shouldInclude = true;
            if (!empty($materialTypeFilters['ceramic'])) {
                $ceramicSize = $this->formatCeramicSize($ceramic);
                $shouldInclude = $this->matchesCeramicSizeFilter($ceramicSize, $materialTypeFilters['ceramic']);
            }
            if ($shouldInclude) {
                $storeMaterials['ceramic']->push($ceramic);
            }
        }

        return $storeMaterials;
    }

    protected function storeHasBrick(\App\Models\StoreLocation $location, ?Brick $brick, bool $allowStoreNameFallback = true): bool
    {
        if (!$brick) {
            return false;
        }

        $hasBrickLocationInfo = !empty($brick->store_location_id) || !empty($brick->store);
        if (!$hasBrickLocationInfo) {
            return true;
        }

        $hasBrick = $location
            ->materialAvailabilities()
            ->where('materialable_type', Brick::class)
            ->where('materialable_id', $brick->id)
            ->exists();

        if (!$hasBrick && !empty($brick->store_location_id)) {
            $hasBrick = (int) $brick->store_location_id === (int) $location->id;
        }

        if ($allowStoreNameFallback && !$hasBrick) {
            $storeName = $this->normalizeStoreName($location->store->name ?? '');
            if ($storeName !== '' && $this->normalizeStoreName($brick->store ?? '') === $storeName) {
                $hasBrick = true;
            }
        }

        return $hasBrick;
    }

    protected function formatStoreLabelBase(\App\Models\StoreLocation $location, ?float $distanceKm = null): string
    {
        $label = ($location->store->name ?? 'Toko') . ' (' . ($location->city ?? '-') . ')';

        if ($distanceKm === null) {
            return $label;
        }

        return $label . ' - ' . round($distanceKm, 2) . ' km';
    }

    protected function buildStoreCostBreakdown(array $combination, array $storePlan): array
    {
        if (empty($storePlan)) {
            return [];
        }

        $materialCosts = $this->extractMaterialCostsByType($combination['result'] ?? []);
        $hasCost = array_sum($materialCosts) > 0;
        if (!$hasCost) {
            return [];
        }

        $breakdown = [];
        foreach ($storePlan as $index => $entry) {
            $breakdown[$index] = [
                'store_location_id' => $entry['store_location_id'] ?? null,
                'store_name' => $entry['store_name'] ?? ('Toko ' . ($index + 1)),
                'distance_km' => $entry['distance_km'] ?? null,
                'provided_materials' => $entry['provided_materials'] ?? [],
                'estimated_cost' => 0.0,
                'material_costs' => [],
            ];
        }

        foreach ($materialCosts as $type => $cost) {
            if ($cost <= 0) {
                continue;
            }

            $allocated = false;
            foreach ($breakdown as $index => $entry) {
                if (in_array($type, $entry['provided_materials'] ?? [], true)) {
                    $breakdown[$index]['estimated_cost'] += $cost;
                    $breakdown[$index]['material_costs'][$type] = round($cost, 2);
                    $allocated = true;
                    break;
                }
            }

            if (!$allocated && isset($breakdown[0])) {
                $breakdown[0]['estimated_cost'] += $cost;
                $breakdown[0]['material_costs'][$type] = round($cost, 2);
            }
        }

        return array_values(
            array_filter(
                array_map(function (array $entry) {
                    $entry['estimated_cost'] = round((float) ($entry['estimated_cost'] ?? 0), 2);
                    $entry['material_costs'] = $entry['material_costs'] ?? [];

                    return $entry;
                }, $breakdown),
                fn(array $entry) => ($entry['estimated_cost'] ?? 0) > 0,
            ),
        );
    }

    protected function extractMaterialCostsByType(array $result): array
    {
        return [
            'brick' => $this->resolveNumericResultValue($result, ['total_brick_cost']),
            'cement' => $this->resolveNumericResultValue($result, ['total_cement_price']),
            'sand' => $this->resolveNumericResultValue($result, ['total_sand_price']),
            'cat' => $this->resolveNumericResultValue($result, ['total_cat_price']),
            'ceramic' => $this->resolveNumericResultValue($result, ['total_ceramic_price']),
            'nat' => $this->resolveNumericResultValue($result, ['total_grout_price', 'total_nat_price']),
        ];
    }

    protected function resolveNumericResultValue(array $result, array $candidateKeys): float
    {
        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $result)) {
                continue;
            }
            if (!is_numeric($result[$key])) {
                continue;
            }

            return max(0.0, (float) $result[$key]);
        }

        return 0.0;
    }

    protected function loadStoreMaterialsForLocation(
        \App\Models\StoreLocation $location,
        array $materialTypeFilters,
        array $natTypeFilterValues,
        bool $allowStoreNameFallback = true,
    ): array {
        $storeNameNorm = $this->normalizeStoreName($location->store->name ?? '');

        $loadByLocationOrStore = function (string $modelClass, callable $filter) use ($location, $storeNameNorm, $allowStoreNameFallback) {
            $items = collect();
            $items = $items->merge($modelClass::query()->where('store_location_id', $location->id)->get());
            if ($allowStoreNameFallback && $storeNameNorm !== '') {
                $items = $items->merge($this->getMaterialsByStoreName($modelClass, $storeNameNorm));
            }
            if ($items->isEmpty()) {
                return $items;
            }

            return $items->filter($filter)->unique('id')->values();
        };

        return [
            'cement' => $loadByLocationOrStore(Cement::class, function ($item) use ($materialTypeFilters) {
                return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['cement'] ?? null);
            }),
            'sand' => $loadByLocationOrStore(Sand::class, function ($item) use ($materialTypeFilters) {
                return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['sand'] ?? null);
            }),
            'cat' => $loadByLocationOrStore(Cat::class, function ($item) use ($materialTypeFilters) {
                return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['cat'] ?? null);
            }),
            'ceramic' => $loadByLocationOrStore(Ceramic::class, function ($item) use ($materialTypeFilters) {
                if (empty($materialTypeFilters['ceramic'])) {
                    return true;
                }
                $ceramicSize = $this->formatCeramicSize($item);

                return $this->matchesCeramicSizeFilter($ceramicSize, $materialTypeFilters['ceramic']);
            }),
            'nat' => $loadByLocationOrStore(Nat::class, function ($item) use ($natTypeFilterValues) {
                return $this->matchesMaterialTypeFilter($item->type ?? null, $natTypeFilterValues);
            }),
        ];
    }

    protected function getStoreBasedCombinationsByStoreName(Request $request, array $constraints = []): array
    {
        $brick = $constraints['brick'] ?? null;
        $fixedCeramic = $constraints['ceramic'] ?? null;
        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $materialTypeFilters = $request['material_type_filters'] ?? [];
        $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);
        $storeLocationsByName = \App\Models\StoreLocation::with('store')
            ->get()
            ->groupBy(function ($location) {
                return $this->normalizeStoreName($location->store->name ?? '');
            })
            ->map(fn($items) => $items->pluck('id')->map(fn($id) => (int) $id)->values());

        $storeNames = collect();
        if ($brick && !empty($brick->store)) {
            $storeNames->push($this->normalizeStoreName($brick->store));
        } else {
            $storeNames = $storeNames
                ->merge(
                    Brick::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                )
                ->merge(
                    Cement::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                )
                ->merge(
                    Sand::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                )
                ->merge(
                    Cat::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                )
                ->merge(
                    Ceramic::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                )
                ->merge(
                    Nat::query()
                        ->whereNotNull('store')
                        ->pluck('store')
                        ->map(fn($name) => $this->normalizeStoreName($name)),
                );
        }
        $storeNames = $storeNames->filter(fn($name) => $name !== '')->unique()->values();

        if ($storeNames->isEmpty()) {
            return [];
        }

        $allStoreCombinations = [];
        foreach ($storeNames as $storeName) {
            $locationIdsForStore = $storeLocationsByName[$storeName] ?? collect();
            $storeMaterials = [
                'cement' => $this->collectMaterialsForStoreGroup(Cement::class, $locationIdsForStore, $storeName),
                'sand' => $this->collectMaterialsForStoreGroup(Sand::class, $locationIdsForStore, $storeName),
                'cat' => $this->collectMaterialsForStoreGroup(Cat::class, $locationIdsForStore, $storeName),
                'ceramic' => $this->collectMaterialsForStoreGroup(Ceramic::class, $locationIdsForStore, $storeName),
                'nat' => $this->collectMaterialsForStoreGroup(Nat::class, $locationIdsForStore, $storeName),
            ];

            // Apply filters
            $storeMaterials['cement'] = $storeMaterials['cement']
                ->filter(function ($item) use ($materialTypeFilters) {
                    return $this->matchesMaterialTypeFilter(
                        $item->type ?? null,
                        $materialTypeFilters['cement'] ?? null,
                    );
                })
                ->values();
            $storeMaterials['sand'] = $storeMaterials['sand']
                ->filter(function ($item) use ($materialTypeFilters) {
                    return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['sand'] ?? null);
                })
                ->values();
            $storeMaterials['cat'] = $storeMaterials['cat']
                ->filter(function ($item) use ($materialTypeFilters) {
                    return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['cat'] ?? null);
                })
                ->values();
            $storeMaterials['ceramic'] = $storeMaterials['ceramic']
                ->filter(function ($item) use ($materialTypeFilters) {
                    if (empty($materialTypeFilters['ceramic'])) {
                        return true;
                    }
                    $ceramicSize = $this->formatCeramicSize($item);

                    return $this->matchesCeramicSizeFilter($ceramicSize, $materialTypeFilters['ceramic']);
                })
                ->values();
            $storeMaterials['nat'] = $storeMaterials['nat']
                ->filter(function ($item) use ($natTypeFilterValues) {
                    return $this->matchesMaterialTypeFilter($item->type ?? null, $natTypeFilterValues);
                })
                ->values();

            $isComplete = true;
            foreach ($requiredMaterials as $req) {
                if ($req === 'brick') {
                    if ($brick) {
                        $hasBrickLocationInfo = !empty($brick->store_location_id) || !empty($brick->store);
                        if ($hasBrickLocationInfo) {
                            $hasBrick = false;
                            if ($this->normalizeStoreName($brick->store ?? '') === $storeName) {
                                $hasBrick = true;
                            }
                            if (!$hasBrick && $locationIdsForStore->isNotEmpty() && !empty($brick->store_location_id)) {
                                $hasBrick = $locationIdsForStore->contains((int) $brick->store_location_id);
                            }
                            if (!$hasBrick) {
                                $isComplete = false;
                                break;
                            }
                        }
                    }

                    continue;
                }
                if ($storeMaterials[$req]->isEmpty()) {
                    $isComplete = false;
                    break;
                }
            }

            if ($fixedCeramic) {
                if ($this->normalizeStoreName($fixedCeramic->store ?? '') !== $storeName) {
                    $isComplete = false;
                } else {
                    $storeMaterials['ceramic'] = collect([$fixedCeramic]);
                }
            }

            if (!$isComplete) {
                continue;
            }

            if (!$brick && in_array('brick', $requiredMaterials, true)) {
                continue;
            }

            // Cap materials per store to prevent combinatorial explosion
            $storeMaterials = $this->capStoreMaterials($storeMaterials, $workType);

            $localResults = $this->calculateCombinationsFromMaterials(
                $brick ?? new Brick(),
                $request->all(),
                $storeMaterials['cement'],
                $storeMaterials['sand'],
                $storeMaterials['cat'],
                $storeMaterials['ceramic'],
                $storeMaterials['nat'],
                'Store: ' . $storeName,
                10,
            );

            if (empty($localResults)) {
                continue;
            }

            usort($localResults, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
            $isSingleMaterialWork = count($requiredMaterials) <= 1;

            if ($isSingleMaterialWork) {
                foreach ($localResults as $result) {
                    $result['store_label'] = $storeName;
                    $allStoreCombinations[] = $result;
                }
            } else {
                $cheapest = $localResults[0];
                $cheapest['store_label'] = $storeName . ' [Hemat]';
                $allStoreCombinations[] = $cheapest;

                if (count($localResults) > 1) {
                    $expensive = end($localResults);
                    if ($expensive['total_cost'] > $cheapest['total_cost']) {
                        $expensive['store_label'] = $storeName . ' [Premium]';
                        $allStoreCombinations[] = $expensive;
                    }
                }
            }
        }

        if (empty($allStoreCombinations)) {
            return [];
        }

        usort($allStoreCombinations, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);

        $finalResults = [];
        $count = count($allStoreCombinations);
        $limitEkonomis = min(3, $count);
        for ($i = 0; $i < $limitEkonomis; $i++) {
            $combo = $allStoreCombinations[$i];
            $label = 'Ekonomis ' . ($i + 1);
            $combo['filter_label'] = $label;
            $combo['filter_type'] = 'cheapest';
            $finalResults[$label] = [$combo];
        }

        $limitTermahal = min(3, $count);
        $startTermahal = max($limitEkonomis, $count - $limitTermahal);
        $termahalRank = 1;
        for ($i = $count - 1; $i >= $startTermahal; $i--) {
            $combo = $allStoreCombinations[$i];
            $label = "Termahal {$termahalRank}";
            $combo['filter_label'] = $label;
            $combo['filter_type'] = 'expensive';
            $finalResults[$label] = [$combo];
            $termahalRank++;
        }

        if ($count > 0 && !isset($finalResults['Average 1'])) {
            $midIndex = floor(($count - 1) / 2);
            $combo = $allStoreCombinations[$midIndex];
            $label = 'Average 1';
            $combo['filter_label'] = $label;
            $combo['filter_type'] = 'medium';
            $finalResults[$label] = [$combo];
        }

        return $finalResults;
    }

    /**
     * Apply material type filters to a collection
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string  $materialType  'brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'
     * @param  string|null  $filterValue  The value to filter by (e.g., 'Merah', '20 x 30')
     * @return \Illuminate\Support\Collection
     */
    protected function applyMaterialTypeFilter($collection, string $materialType, ?string $filterValue)
    {
        if (empty($filterValue) || $collection->isEmpty()) {
            return $collection;
        }

        return $collection->filter(function ($item) use ($materialType, $filterValue) {
            $itemValue = match ($materialType) {
                'brick' => $item->type ?? null,
                'cement' => $item->type ?? null,
                'sand' => $item->type ?? null,
                'cat' => $item->type ?? null,
                'nat' => $item->type ?? null,
                'ceramic' => $this->formatCeramicSize($item),
                default => null,
            };

            return $itemValue === $filterValue;
        });
    }

    /**
     * Format ceramic size for filtering (e.g., "20 x 30")
     */
    protected function formatCeramicSize($ceramic): ?string
    {
        $length = $ceramic->dimension_length ?? null;
        $width = $ceramic->dimension_width ?? null;

        if (empty($length) || empty($width)) {
            return null;
        }

        $min = min($length, $width);
        $max = max($length, $width);

        return \App\Helpers\NumberHelper::format($min) . ' x ' . \App\Helpers\NumberHelper::format($max);
    }

    protected function normalizeIdentityText(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    protected function loadBrickCandidatesForLocation(\App\Models\StoreLocation $location, array $materialTypeFilters)
    {
        $storeNameNorm = $this->normalizeStoreName($location->store->name ?? '');
        $items = collect();
        $items = $items->merge(Brick::query()->where('store_location_id', $location->id)->get());
        if ($storeNameNorm !== '') {
            $items = $items->merge($this->getMaterialsByStoreName(Brick::class, $storeNameNorm));
        }

        return $items
            ->unique('id')
            ->filter(function ($item) use ($materialTypeFilters) {
                return $this->matchesMaterialTypeFilter($item->type ?? null, $materialTypeFilters['brick'] ?? null);
            })
            ->values();
    }

    protected function materialUnitPrice(string $type, $material): float
    {
        if (!$material) {
            return INF;
        }

        return match ($type) {
            'brick' => (float) ($material->price_per_piece ?? 0),
            'cement' => (float) ($material->package_price ?? 0),
            'sand' => (float) ($material->comparison_price_per_m3 ??
                (((float) ($material->package_volume ?? 0)) > 0
                    ? ((float) ($material->package_price ?? 0)) / ((float) ($material->package_volume ?? 0))
                    : 0)),
            'cat' => (float) ($material->purchase_price ?? 0),
            'ceramic' => (float) ($material->price_per_package ?? 0),
            'nat' => (float) ($material->package_price ?? 0),
            default => 0,
        };
    }

    protected function materialIdentityMatches(string $type, $candidate, $reference, bool $strict = true): bool
    {
        if (!$candidate || !$reference) {
            return false;
        }

        $sameText = function ($left, $right): bool {
            return $this->normalizeIdentityText((string) $left) === $this->normalizeIdentityText((string) $right);
        };
        $sameFloat = function ($left, $right): bool {
            return round((float) $left, 4) === round((float) $right, 4);
        };

        return match ($type) {
            'brick' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict ||
                    ($sameText($candidate->type ?? '', $reference->type ?? '') &&
                        $sameFloat($candidate->dimension_length ?? 0, $reference->dimension_length ?? 0) &&
                        $sameFloat($candidate->dimension_width ?? 0, $reference->dimension_width ?? 0) &&
                        $sameFloat($candidate->dimension_height ?? 0, $reference->dimension_height ?? 0))),
            'cement' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict ||
                    ($sameText($candidate->type ?? '', $reference->type ?? '') &&
                        $sameFloat($candidate->package_weight_net ?? 0, $reference->package_weight_net ?? 0))),
            'sand' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict || $sameText($candidate->type ?? '', $reference->type ?? '')),
            'cat' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict ||
                    ($sameText($candidate->type ?? '', $reference->type ?? '') &&
                        $sameText($candidate->sub_brand ?? '', $reference->sub_brand ?? '') &&
                        $sameText($candidate->color_code ?? '', $reference->color_code ?? '') &&
                        $sameText($candidate->color_name ?? '', $reference->color_name ?? ''))),
            'ceramic' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict ||
                    ($sameText($candidate->type ?? '', $reference->type ?? '') &&
                        $sameFloat(
                            min(
                                (float) ($candidate->dimension_length ?? 0),
                                (float) ($candidate->dimension_width ?? 0),
                            ),
                            min(
                                (float) ($reference->dimension_length ?? 0),
                                (float) ($reference->dimension_width ?? 0),
                            ),
                        ) &&
                        $sameFloat(
                            max(
                                (float) ($candidate->dimension_length ?? 0),
                                (float) ($candidate->dimension_width ?? 0),
                            ),
                            max(
                                (float) ($reference->dimension_length ?? 0),
                                (float) ($reference->dimension_width ?? 0),
                            ),
                        ))),
            'nat' => $sameText($candidate->brand ?? '', $reference->brand ?? '') &&
                (!$strict ||
                    ($sameText($candidate->type ?? '', $reference->type ?? '') &&
                        $sameText($candidate->color ?? '', $reference->color ?? ''))),
            default => false,
        };
    }

    protected function pickEquivalentMaterialForStore(string $type, $reference, $storeCandidates)
    {
        if (!$reference || !$storeCandidates || $storeCandidates->isEmpty()) {
            return null;
        }

        $strictMatches = $storeCandidates
            ->filter(fn($candidate) => $this->materialIdentityMatches($type, $candidate, $reference, true))
            ->values();
        if ($strictMatches->isNotEmpty()) {
            return $strictMatches->sortBy(fn($candidate) => $this->materialUnitPrice($type, $candidate))->first();
        }

        $fallbackMatches = $storeCandidates
            ->filter(fn($candidate) => $this->materialIdentityMatches($type, $candidate, $reference, false))
            ->values();
        if ($fallbackMatches->isEmpty()) {
            return null;
        }

        return $fallbackMatches->sortBy(fn($candidate) => $this->materialUnitPrice($type, $candidate))->first();
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
        $materialTypeFilters = $request['material_type_filters'] ?? [];
        $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);

        // DEBUG: Log material type filters in popular store combinations
        Log::info('getPopularStoreCombinations - Material Type Filters', [
            'filters' => $materialTypeFilters,
            'work_type' => $workType,
        ]);

        // 1. Get Top Combinations from History
        $query = DB::table('brick_calculations')
            ->select(
                'brick_id',
                'cement_id',
                'sand_id',
                'cat_id',
                'ceramic_id',
                'nat_id',
                DB::raw('count(*) as frequency'),
            )
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
            ->groupBy('brick_id', 'cement_id', 'sand_id', 'cat_id', 'ceramic_id', 'nat_id')
            ->orderByDesc('frequency')
            ->limit(5); // Get top 5 candidates

        // Filter valid IDs only
        if (in_array('brick', $requiredMaterials)) {
            $query->whereNotNull('brick_id');
        }
        if (in_array('cement', $requiredMaterials)) {
            $query->whereNotNull('cement_id');
        }

        $topCombos = $query->get();
        $finalResults = [];
        $rank = 1;

        $locations = \App\Models\StoreLocation::with('store')->get();

        // 2. Validate Store Availability for each Combo
        foreach ($topCombos as $combo) {
            if ($rank > 3) {
                break;
            }

            // Load Material Models
            $materials = [];
            $validCombo = true;

            if (in_array('brick', $requiredMaterials)) {
                $materials['brick'] = Brick::find($combo->brick_id);
                if (!$materials['brick']) {
                    $validCombo = false;
                }
            }
            if (in_array('cement', $requiredMaterials)) {
                $materials['cement'] = Cement::find($combo->cement_id);
                if (!$materials['cement']) {
                    $validCombo = false;
                }
                // Apply cement filter
                if (
                    $validCombo &&
                    !$this->matchesMaterialTypeFilter(
                        $materials['cement']->type ?? null,
                        $materialTypeFilters['cement'] ?? null,
                    )
                ) {
                    $validCombo = false;
                }
            }
            if (in_array('sand', $requiredMaterials)) {
                $materials['sand'] = Sand::find($combo->sand_id);
                if (!$materials['sand']) {
                    $validCombo = false;
                }
                // Apply sand filter
                if (
                    $validCombo &&
                    !$this->matchesMaterialTypeFilter(
                        $materials['sand']->type ?? null,
                        $materialTypeFilters['sand'] ?? null,
                    )
                ) {
                    $validCombo = false;
                }
            }
            if (in_array('cat', $requiredMaterials)) {
                $materials['cat'] = Cat::find($combo->cat_id);
                if (!$materials['cat']) {
                    $validCombo = false;
                }
                // Apply cat filter
                if (
                    $validCombo &&
                    !$this->matchesMaterialTypeFilter(
                        $materials['cat']->type ?? null,
                        $materialTypeFilters['cat'] ?? null,
                    )
                ) {
                    $validCombo = false;
                }
            }
            if (in_array('ceramic', $requiredMaterials)) {
                $materials['ceramic'] = Ceramic::find($combo->ceramic_id);
                if (!$materials['ceramic']) {
                    $validCombo = false;
                }
                // Apply ceramic filter (size-based)
                if ($validCombo && !empty($materialTypeFilters['ceramic'])) {
                    $ceramicSize = $this->formatCeramicSize($materials['ceramic']);
                    if (!$this->matchesCeramicSizeFilter($ceramicSize, $materialTypeFilters['ceramic'])) {
                        $validCombo = false;
                    }
                }
            }
            if (in_array('nat', $requiredMaterials)) {
                $materials['nat'] = isset($combo->nat_id) ? Nat::find($combo->nat_id) : null;
                if (!$materials['nat']) {
                    $validCombo = false;
                }
                // Apply nat filter
                if (
                    $validCombo &&
                    !$this->matchesMaterialTypeFilter($materials['nat']->type ?? null, $natTypeFilterValues)
                ) {
                    $validCombo = false;
                }
            }

            if (!$validCombo) {
                continue;
            }

            // Find one-stop stores that can provide equivalent material identities.
            $validStores = [];

            foreach ($locations as $location) {
                $hasAll = true;
                $storeMaterials = $this->loadStoreMaterialsForLocation(
                    $location,
                    $materialTypeFilters,
                    $natTypeFilterValues,
                );
                $selectedStoreMaterials = [];

                if (in_array('brick', $requiredMaterials, true)) {
                    $brickCandidates = $this->loadBrickCandidatesForLocation($location, $materialTypeFilters);
                    $matchedBrick = $this->pickEquivalentMaterialForStore(
                        'brick',
                        $materials['brick'] ?? null,
                        $brickCandidates,
                    );
                    if (!$matchedBrick) {
                        $hasAll = false;
                    } else {
                        $selectedStoreMaterials['brick'] = $matchedBrick;
                    }
                }

                foreach (['cement', 'sand', 'cat', 'ceramic', 'nat'] as $type) {
                    if (!$hasAll || !in_array($type, $requiredMaterials, true)) {
                        continue;
                    }
                    $matched = $this->pickEquivalentMaterialForStore(
                        $type,
                        $materials[$type] ?? null,
                        $storeMaterials[$type] ?? collect(),
                    );
                    if (!$matched) {
                        $hasAll = false;
                        break;
                    }
                    $selectedStoreMaterials[$type] = $matched;
                }

                if ($hasAll) {
                    // Calculate Cost at this store using store-scoped material IDs.
                    $cements = isset($selectedStoreMaterials['cement'])
                        ? collect([$selectedStoreMaterials['cement']])
                        : collect();
                    $sands = isset($selectedStoreMaterials['sand'])
                        ? collect([$selectedStoreMaterials['sand']])
                        : collect();
                    $cats = isset($selectedStoreMaterials['cat'])
                        ? collect([$selectedStoreMaterials['cat']])
                        : collect();
                    $ceramics = isset($selectedStoreMaterials['ceramic'])
                        ? collect([$selectedStoreMaterials['ceramic']])
                        : collect();
                    $nats = isset($selectedStoreMaterials['nat'])
                        ? collect([$selectedStoreMaterials['nat']])
                        : collect();
                    $brick = $selectedStoreMaterials['brick'] ?? ($materials['brick'] ?? new Brick());

                    $result = $this->calculateCombinationsFromMaterials(
                        $brick,
                        $request->all(),
                        $cements,
                        $sands,
                        $cats,
                        $ceramics,
                        $nats,
                        'Populer Store',
                        1,
                    );

                    if (!empty($result)) {
                        $res = $result[0];
                        $res['store_label'] = $location->store->name . ' (' . $location->city . ')';
                        $res['store_location'] = $location;
                        $res['frequency'] = (int) $combo->frequency;
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

                $finalResults[$label] = [$winner];
                $rank++;
            }
        }

        return $finalResults;
    }

    /**
     * Get combinations by filter
     */
    public function getCombinationsByFilter(
        Brick $brick,
        array $requestData,
        string $filter,
        ?Ceramic $fixedCeramic = null,
    ): array {
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
     * @param  array  $request  Request parameters
     * @param  string  $groupLabel  Label for this group (e.g., 'Preferensi', 'Ekonomis')
     * @param  int|null  $limit  Limit number of results
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
            'grout_thickness' => $request['grout_thickness'] ?? 0, // For Grout Tile formula
            'ceramic_length' => $request['ceramic_length'] ?? 0, // For Grout Tile formula (from form input)
            'ceramic_width' => $request['ceramic_width'] ?? 0, // For Grout Tile formula (from form input)
            'ceramic_thickness' => $request['ceramic_thickness'] ?? 0, // For Grout Tile formula (from form input)
        ];

        $cats = $cats ?? collect();
        $ceramics = $ceramics ?? collect();
        $nats = $nats ?? collect();

        if ($workType === 'grout_tile') {
            $ceramics = collect([$this->buildGroutTileFallbackCeramic($paramsBase)]);
        }

        // Determine sorting direction based on group label for optimization
        $sortDesc = $groupLabel === 'Termahal';

        $generator = $this->yieldCombinations(
            $paramsBase,
            $workType,
            $cements,
            $sands,
            $cats,
            $ceramics,
            $nats,
            $groupLabel,
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
                return $sortDesc ? $b['total_cost'] <=> $a['total_cost'] : $a['total_cost'] <=> $b['total_cost'];
            });

            return array_slice($results, 0, $limit);
        }

        // If no limit (e.g. Medium or All), we must collect all
        // WARNING: This is still memory intensive for 'All'
        $results = iterator_to_array($generator);

        usort($results, function ($a, $b) use ($sortDesc) {
            return $sortDesc ? $b['total_cost'] <=> $a['total_cost'] : $a['total_cost'] <=> $b['total_cost'];
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
        string $groupLabel,
    ) {
        $requiredMaterials = $this->resolveRequiredMaterials($workType);

        // Work types requiring ceramic + nat + cement + sand (4-way product)
        if (
            in_array('ceramic', $requiredMaterials, true) &&
            in_array('nat', $requiredMaterials, true) &&
            in_array('cement', $requiredMaterials, true) &&
            in_array('sand', $requiredMaterials, true)
        ) {
            yield from $this->yieldCeramicNatCementSandCombinations(
                $paramsBase,
                $workType,
                $ceramics,
                $nats,
                $cements,
                $sands,
                $groupLabel,
            );

            return;
        }

        if (in_array('cat', $requiredMaterials, true)) {
            foreach ($cats as $cat) {
                if ($cat->purchase_price <= 0) {
                    continue;
                }

                $params = array_merge($paramsBase, ['cat_id' => $cat->id]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        continue;
                    }

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
            // This handles grout_tile work type
            // Note: ceramic dimensions come from request params (user input), not from ceramic model
            foreach ($ceramics as $ceramic) {
                foreach ($nats as $nat) {
                    $params = array_merge($paramsBase, [
                        'ceramic_id' => $ceramic->id,
                        'nat_id' => $this->extractNatIdFromModel($nat),
                        // ceramic_length, ceramic_width, ceramic_thickness already in paramsBase from request
                    ]);

                    try {
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) {
                            continue;
                        }

                        $result = $formula->calculate($params);

                        yield [
                            'ceramic' => $ceramic,
                            'nat' => $nat,
                            'result' => $result,
                            'total_cost' => $result['grand_total'],
                        ];
                    } catch (\Exception $e) {
                        Log::warning('GroutTile calculation failed', [
                            'ceramic_id' => $ceramic->id,
                            'nat_id' => $params['nat_id'] ?? null,
                            'error' => $e->getMessage(),
                            'params' => $params,
                        ]);

                        continue;
                    }
                }
            }
        } elseif (
            in_array('ceramic', $requiredMaterials, true) &&
            in_array('cement', $requiredMaterials, true) &&
            !in_array('nat', $requiredMaterials, true) &&
            !in_array('sand', $requiredMaterials, true)
        ) {
            // Cement + Ceramic only (e.g., adhesive_mix / pasang keramik saja)
            foreach ($ceramics as $ceramic) {
                foreach ($cements as $cement) {
                    if ($cement->package_weight_net <= 0) {
                        continue;
                    }

                    $params = array_merge($paramsBase, [
                        'cement_id' => $cement->id,
                        'ceramic_id' => $ceramic->id,
                    ]);

                    try {
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) {
                            continue;
                        }

                        $result = $formula->calculate($params);

                        yield [
                            'cement' => $cement,
                            'ceramic' => $ceramic,
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
                if ($cement->package_weight_net <= 0) {
                    continue;
                }

                $params = array_merge($paramsBase, ['cement_id' => $cement->id]);

                try {
                    $formula = FormulaRegistry::instance($workType);
                    if (!$formula) {
                        continue;
                    }

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
                            continue;
                        }

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
                    $nats = Nat::where('id', $rec->nat_id)->get();
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
     */
    public function getCommonCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);
        $isCeramicWork = in_array('ceramic', $requiredMaterials, true);
        $isCatWork = in_array('cat', $requiredMaterials, true);
        $materialTypeFilters = $request['material_type_filters'] ?? [];
        $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);

        Log::info('getCommonCombinations - Material Type Filters', [
            'filters' => $materialTypeFilters,
        ]);

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
                $nat = isset($combo->nat_id) ? Nat::find($combo->nat_id) : null;
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

                // Apply material type filters
                if (
                    $cement &&
                    !$this->matchesMaterialTypeFilter($cement->type, $materialTypeFilters['cement'] ?? null)
                ) {
                    continue;
                }
                if ($sand && !$this->matchesMaterialTypeFilter($sand->type, $materialTypeFilters['sand'] ?? null)) {
                    continue;
                }
                if ($nat && !$this->matchesMaterialTypeFilter($nat->type ?? null, $natTypeFilterValues)) {
                    continue;
                }
                if (!empty($materialTypeFilters['ceramic']) && $ceramic) {
                    $ceramicSize = $this->formatCeramicSize($ceramic);
                    if (!$this->matchesCeramicSizeFilter($ceramicSize, $materialTypeFilters['ceramic'])) {
                        continue;
                    }
                }

                $params = array_merge($paramsBase, [
                    'ceramic_id' => $ceramic->id,
                    'nat_id' => $this->extractNatIdFromModel($nat),
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

                // Apply material type filter for cat
                if (!$this->matchesMaterialTypeFilter($cat->type, $materialTypeFilters['cat'] ?? null)) {
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

                // Apply material type filters
                if (!$this->matchesMaterialTypeFilter($cement->type, $materialTypeFilters['cement'] ?? null)) {
                    continue;
                }
                if ($sand && !$this->matchesMaterialTypeFilter($sand->type, $materialTypeFilters['sand'] ?? null)) {
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

            // Apply material type filters
            if (!$this->matchesMaterialTypeFilter($cement->type, $materialTypeFilters['cement'] ?? null)) {
                Log::info('getCommonCombinations - Skipping cement', [
                    'cement_type' => $cement->type,
                    'filter' => $materialTypeFilters['cement'] ?? null,
                ]);

                continue;
            }
            if ($sand && !$this->matchesMaterialTypeFilter($sand->type, $materialTypeFilters['sand'] ?? null)) {
                Log::info('getCommonCombinations - Skipping sand', [
                    'sand_type' => $sand->type,
                    'filter' => $materialTypeFilters['sand'] ?? null,
                ]);

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
     */
    public function getCheapestCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);
        $materialTypeFilters = $request['material_type_filters'] ?? [];

        // DEBUG: Get available types from database
        $availableCementTypes = Cement::query()->distinct()->pluck('type')->filter()->values()->toArray();

        $availableSandTypes = Sand::distinct()->pluck('type')->filter()->values()->toArray();

        Log::info('getCheapestCombinations - DEBUGGING', [
            'materialTypeFilters' => $materialTypeFilters,
            'cement_filter_value' => $materialTypeFilters['cement'] ?? 'NONE',
            'sand_filter_value' => $materialTypeFilters['sand'] ?? 'NONE',
            'available_cement_types_in_db' => $availableCementTypes,
            'available_sand_types_in_db' => $availableSandTypes,
            'cement_filter_exists_in_db' =>
                count(
                    array_intersect(
                        $this->normalizeMaterialTypeFilterValues($materialTypeFilters['cement'] ?? null),
                        $availableCementTypes,
                    ),
                ) > 0,
            'sand_filter_exists_in_db' =>
                count(
                    array_intersect(
                        $this->normalizeMaterialTypeFilterValues($materialTypeFilters['sand'] ?? null),
                        $availableSandTypes,
                    ),
                ) > 0,
        ]);

        $cements = $this->resolveCementsByPrice('asc', $materialLimit, $materialTypeFilters['cement'] ?? null);
        $sands = $this->resolveSandsByPrice('asc', $materialLimit, $materialTypeFilters['sand'] ?? null);
        $cats = $this->resolveCatsByPrice('asc', $materialLimit, $materialTypeFilters['cat'] ?? null);
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'asc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('asc', $materialLimit, $materialTypeFilters['nat'] ?? null);
        if ($workType === 'grout_tile' && $nats->isEmpty()) {
            $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);
            $fallbackQuery = Nat::query();
            if (!empty($natTypeFilterValues)) {
                $fallbackQuery->whereIn('type', $natTypeFilterValues);
            }
            $nats = $fallbackQuery->orderBy('brand')->limit($materialLimit)->get();
        }

        Log::info('getCheapestCombinations - Materials Retrieved', [
            'cements_count' => $cements->count(),
            'sands_count' => $sands->count(),
            'cements_types' => $cements->pluck('type')->unique()->values()->toArray(),
            'sands_types' => $sands->pluck('type')->unique()->values()->toArray(),
        ]);

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
     */
    public function getMediumCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);
        $materialTypeFilters = $request['material_type_filters'] ?? [];

        $cements = $this->resolveCementsByPrice('asc', $materialLimit, $materialTypeFilters['cement'] ?? null);
        $sands = $this->resolveSandsByPrice('asc', $materialLimit, $materialTypeFilters['sand'] ?? null);
        $cats = $this->resolveCatsByPrice('asc', $materialLimit, $materialTypeFilters['cat'] ?? null);
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'asc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('asc', $materialLimit, $materialTypeFilters['nat'] ?? null);
        if ($workType === 'grout_tile' && $nats->isEmpty()) {
            $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);
            $fallbackQuery = Nat::query();
            if (!empty($natTypeFilterValues)) {
                $fallbackQuery->whereIn('type', $natTypeFilterValues);
            }
            $nats = $fallbackQuery->orderBy('brand')->limit($materialLimit)->get();
        }

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
     */
    public function getExpensiveCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $materialLimit = $this->resolveMaterialLimit($workType);
        $materialTypeFilters = $request['material_type_filters'] ?? [];

        $cements = $this->resolveCementsByPrice('desc', $materialLimit, $materialTypeFilters['cement'] ?? null);
        $sands = $this->resolveSandsByPrice('desc', $materialLimit, $materialTypeFilters['sand'] ?? null);
        $cats = $this->resolveCatsByPrice('desc', $materialLimit, $materialTypeFilters['cat'] ?? null);
        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            'price_per_package',
            'desc',
            $materialLimit,
        );
        $nats = $this->resolveNatsByPrice('desc', $materialLimit, $materialTypeFilters['nat'] ?? null);
        if ($workType === 'grout_tile' && $nats->isEmpty()) {
            $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);
            $fallbackQuery = Nat::query();
            if (!empty($natTypeFilterValues)) {
                $fallbackQuery->whereIn('type', $natTypeFilterValues);
            }
            $nats = $fallbackQuery->orderBy('brand')->limit($materialLimit)->get();
        }

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
     */
    public function getCustomCombinations(Brick $brick, array $request): array
    {
        $request = $this->normalizeNatRequestIds($request);
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
            if (!empty($request['nat_id'])) {
                $nats = Nat::where('id', $request['nat_id'])->get();
                $ceramics = collect([$this->buildGroutTileFallbackCeramic($request)]);

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
                $nats = Nat::where('id', $request['nat_id'])->get();
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
     */
    public function getAllCombinations(Brick $brick, array $request): array
    {
        $workType = $request['work_type'] ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $materialTypeFilters = $request['material_type_filters'] ?? [];

        // DEBUG: Log material type filters
        Log::info('getAllCombinations - Material Type Filters', [
            'filters' => $materialTypeFilters,
            'work_type' => $workType,
            'has_cement_filter' => !empty($materialTypeFilters['cement']),
            'has_sand_filter' => !empty($materialTypeFilters['sand']),
        ]);

        // Use cursor() for memory efficiency on large datasets

        $cements = collect();
        if (in_array('cement', $requiredMaterials, true)) {
            $query = Cement::query()->where('package_price', '>', 0)->where('package_weight_net', '>', 0);

            // Apply material type filter for cement
            if (!empty($materialTypeFilters['cement'])) {
                Log::info('Applying cement filter', ['type' => $materialTypeFilters['cement']]);
                $this->applyTypeFilterToQuery($query, $materialTypeFilters['cement']);
            }

            $cements = $query->cursor(); // Lazy Loading
        }

        $sands = collect();
        if (in_array('sand', $requiredMaterials, true)) {
            $query = Sand::where('package_price', '>', 0);

            // Apply material type filter for sand
            if (!empty($materialTypeFilters['sand'])) {
                $this->applyTypeFilterToQuery($query, $materialTypeFilters['sand']);
            }

            $sands = $query->cursor(); // Lazy Loading
        }

        $cats = collect();
        if (in_array('cat', $requiredMaterials, true)) {
            $query = Cat::where('purchase_price', '>', 0)->orderBy('brand');

            // Apply material type filter for cat
            if (!empty($materialTypeFilters['cat'])) {
                $this->applyTypeFilterToQuery($query, $materialTypeFilters['cat']);
            }

            $cats = $query->cursor(); // Lazy Loading
        }

        $ceramics = collect();
        if (in_array('ceramic', $requiredMaterials, true)) {
            $query = Ceramic::query();
            if ($workType === 'grout_tile') {
                $query->whereNotNull('dimension_thickness')->where('dimension_thickness', '>', 0);
            }
            $query->whereNotNull('price_per_package')->orderBy('price_per_package', 'asc');

            // Apply material type filter for ceramic (size-based)
            if (!empty($materialTypeFilters['ceramic'])) {
                $this->applyCeramicSizeFilterToQuery($query, $materialTypeFilters['ceramic']);
            }

            $ceramics = $query->cursor(); // Lazy Loading
        }

        $nats = collect();
        if (in_array('nat', $requiredMaterials, true)) {
            $query = Nat::query()->orderBy('brand');

            // Apply material type filter for nat
            $natTypeFilterValues = $this->normalizeNatTypeFilterValues($materialTypeFilters['nat'] ?? null);
            if (!empty($natTypeFilterValues)) {
                $query->whereIn('type', $natTypeFilterValues);
            }

            $nats = $query->cursor(); // Lazy Loading
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

    protected function normalizeNatRequestIds(array $request): array
    {
        if (!empty($request['nat_id'])) {
            $request['nat_id'] = (int) $request['nat_id'];
        }

        return $request;
    }

    protected function extractNatIdFromModel($nat): ?int
    {
        if (!$nat) {
            return null;
        }

        return isset($nat->id) ? (int) $nat->id : null;
    }

    /**
     * Get filter label for display
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
        return in_array($workType, ['tile_installation', 'plinth_ceramic'], true) ? 10 : 5;
    }

    /**
     * Cap store materials to prevent combinatorial explosion.
     *
     * For 4-way products (ceramic × nat × cement × sand), even moderate
     * per-store counts can explode: 20×18×13×11 = 51,480 iterations.
     * This method caps each material type to N items per store,
     * taking a mix of cheapest + most expensive for price range coverage.
     *
     * @param  array<string, \Illuminate\Support\Collection>  $storeMaterials
     * @return array<string, \Illuminate\Support\Collection>
     */
    protected function capStoreMaterials(array $storeMaterials, string $workType): array
    {
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $materialTypeCount = count($requiredMaterials);

        // Only cap for 3+ material types (multi-way products)
        if ($materialTypeCount < 3) {
            return $storeMaterials;
        }

        // Cap per type: keep total product under ~1000 combinations
        // For 4 types: cap=5 → 5^4=625, cap=6 → 6^4=1296
        $capPerType = 5;

        $priceFields = [
            'cement' => 'package_price',
            'sand' => 'comparison_price_per_m3',
            'cat' => 'purchase_price',
            'ceramic' => 'price_per_package',
            'nat' => 'package_price',
        ];

        foreach ($storeMaterials as $type => $collection) {
            if ($collection->count() <= $capPerType) {
                continue;
            }

            $priceField = $priceFields[$type] ?? 'package_price';
            $sorted = $collection->sortBy($priceField)->values();

            // Take cheapest half + most expensive half
            $halfCap = (int) ceil($capPerType / 2);
            $cheapest = $sorted->take($halfCap);
            $expensive = $sorted->reverse()->take($capPerType - $halfCap);

            $storeMaterials[$type] = $cheapest->merge($expensive)->unique('id')->values();
        }

        return $storeMaterials;
    }

    protected function resolveCementsByPrice(
        string $direction,
        int $limit,
        string|array|null $typeFilter = null,
    ): EloquentCollection {
        $query = Cement::query()->where('package_price', '>', 0)->where('package_weight_net', '>', 0);

        // Apply material type filter
        if (!empty($typeFilter)) {
            Log::info('resolveCementsByPrice - Applying filter', [
                'direction' => $direction,
                'typeFilter' => $typeFilter,
                'sql_before_filter' => $query->toSql(),
            ]);
            $this->applyTypeFilterToQuery($query, $typeFilter);
            Log::info('resolveCementsByPrice - After filter', [
                'sql_after_filter' => $query->toSql(),
                'result_count' => $query->count(),
            ]);
        } else {
            Log::info('resolveCementsByPrice - NO FILTER APPLIED', [
                'direction' => $direction,
                'typeFilter_is_null' => is_null($typeFilter),
                'typeFilter_is_empty' => empty($typeFilter),
            ]);
        }

        return $query->orderBy('package_price', $direction)->limit($limit)->get();
    }

    protected function resolveNatsByPrice(
        string $direction,
        int $limit,
        string|array|null $typeFilter = null,
    ): EloquentCollection {
        $query = Nat::query()->where('package_price', '>', 0);

        // Apply nat type filter. Plain "Nat" means no specific type filtering.
        $natTypeFilterValues = $this->normalizeNatTypeFilterValues($typeFilter);
        if (!empty($natTypeFilterValues)) {
            $query->whereIn('type', $natTypeFilterValues);
        }

        return $query->orderBy('package_price', $direction)->limit($limit)->get();
    }

    protected function resolveSandsByPrice(
        string $direction,
        int $limit,
        string|array|null $typeFilter = null,
    ): EloquentCollection {
        $query = Sand::where('package_price', '>', 0);

        // Apply material type filter
        if (!empty($typeFilter)) {
            Log::info('resolveSandsByPrice - Applying filter', [
                'direction' => $direction,
                'typeFilter' => $typeFilter,
                'result_count_before' => Sand::where('package_price', '>', 0)->count(),
            ]);
            $this->applyTypeFilterToQuery($query, $typeFilter);
            Log::info('resolveSandsByPrice - After filter', [
                'result_count_after' => $query->count(),
            ]);
        } else {
            Log::info('resolveSandsByPrice - NO FILTER APPLIED');
        }

        return $query->orderBy('package_price', $direction)->limit($limit)->get();
    }

    protected function resolveCatsByPrice(
        string $direction,
        int $limit,
        string|array|null $typeFilter = null,
    ): EloquentCollection {
        $query = Cat::where('purchase_price', '>', 0);

        // Apply material type filter
        if (!empty($typeFilter)) {
            $this->applyTypeFilterToQuery($query, $typeFilter);
        }

        return $query->orderBy('purchase_price', $direction)->limit($limit)->get();
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

        // Apply "Jenis Keramik" filter (single-value dropdown in form, but still normalized as list).
        $materialTypeFilters = $request['material_type_filters'] ?? [];
        $ceramicTypeFilters = $this->normalizeMaterialTypeFilterValues(
            $request['ceramic_types'] ?? ($request['ceramic_type'] ?? ($materialTypeFilters['ceramic_type'] ?? null)),
        );
        if (!empty($ceramicTypeFilters)) {
            $query->whereIn('type', $ceramicTypeFilters);
        }

        // Apply material type filter for ceramic (size-based)
        if (!empty($materialTypeFilters['ceramic'])) {
            $this->applyCeramicSizeFilterToQuery($query, $materialTypeFilters['ceramic']);
        }

        $query = $query->whereNotNull($orderBy)->orderBy($orderBy, $direction)->skip($skip);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function yieldCeramicNatCementSandCombinations(
        array $paramsBase,
        string $workType,
        iterable $ceramics,
        iterable $nats,
        iterable $cements,
        iterable $sands,
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
                            'nat_id' => $this->extractNatIdFromModel($nat),
                            'cement_id' => $cement->id,
                            'sand_id' => $sand->id,
                        ]);

                        try {
                            $formula = FormulaRegistry::instance($workType);
                            if (!$formula) {
                                continue;
                            }

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






