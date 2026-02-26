<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use App\Models\Nat;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Models\StoreLocation;
use App\Models\WorkArea;
use App\Models\WorkField;
use App\Models\WorkFloor;
use App\Models\WorkItemGrouping;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaterialCalculationController extends Controller
{
    protected const CALCULATION_CACHE_KEY_PREFIX = 'material_calc:v3:';

    protected CalculationRepository $calculationRepository;

    protected CombinationGenerationService $combinationGenerationService;

    public function __construct(
        CalculationRepository $calculationRepository,
        CombinationGenerationService $combinationGenerationService,
    ) {
        $this->calculationRepository = $calculationRepository;
        $this->combinationGenerationService = $combinationGenerationService;
    }

    protected function shouldLogMaterialPerformanceDebug(): bool
    {
        if (!(bool) config('materials.performance_log_debug', false)) {
            return false;
        }

        return app()->environment(['local', 'staging']);
    }

    /**
     * @return array<string, float|int>
     */
    protected function materialPerformanceSnapshot(float $startedAt): array
    {
        return [
            'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ];
    }

    protected function logMaterialPerformanceStage(float $startedAt, string $stage, array $context = []): void
    {
        if (!$this->shouldLogMaterialPerformanceDebug()) {
            return;
        }

        \Log::info('Material calculation performance', array_merge([
            'stage' => $stage,
        ], $this->materialPerformanceSnapshot($startedAt), $context));
    }

    /**
     * Log riwayat perhitungan (sebelumnya index)
     * Now using CalculationRepository for cleaner code
     */
    public function log(Request $request)
    {
        // Prepare filters array from request
        $filters = [
            'search' => $request->input('search'),
            'work_type' => $request->input('work_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        // Get paginated calculations from repository
        $calculations = $this->calculationRepository->getCalculationLog($filters, 15);

        // Append query parameters to pagination links
        $calculations->appends($request->query());

        // Get available formulas from Formula Registry
        $availableFormulas = FormulaRegistry::all();

        return view('material_calculations.log', compact('calculations', 'availableFormulas'));
    }

    /**
     * Redirect index to last preview (if available) or create form.
     */
    public function indexRedirect()
    {
        $cacheKey = session('material_calc_last_key');
        if ($cacheKey) {
            if (!str_starts_with((string) $cacheKey, self::CALCULATION_CACHE_KEY_PREFIX)) {
                session()->forget('material_calc_last_key');

                return redirect()->route('material-calculations.create');
            }
            $cachedPayload = Cache::get($cacheKey);
            if (is_array($cachedPayload)) {
                return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
            }
        }

        return redirect()->route('material-calculations.create');
    }

    /**
     * Show the form for creating a new calculation
     */
    public function create(Request $request)
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::query()->orderBy('brand')->get();
        $nats = Nat::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();
        $workFloors = WorkFloor::orderBy('name')->get(['id', 'name']);
        $workAreas = WorkArea::orderBy('name')->get(['id', 'name']);
        $workFields = WorkField::orderBy('name')->get(['id', 'name']);
        $workItemGroupings = WorkItemGrouping::query()
            ->with(['workFloor:id,name', 'workArea:id,name', 'workField:id,name'])
            ->get()
            ->map(function (WorkItemGrouping $grouping): array {
                return [
                    'formula_code' => (string) $grouping->formula_code,
                    'work_floor' => trim((string) optional($grouping->workFloor)->name),
                    'work_area' => trim((string) optional($grouping->workArea)->name),
                    'work_field' => trim((string) optional($grouping->workField)->name),
                ];
            })
            ->values()
            ->all();
        $storeLocationsForMap = StoreLocation::query()
            ->with('store:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->filter(
                fn(StoreLocation $location) => is_numeric($location->latitude) && is_numeric($location->longitude),
            )
            ->map(function (StoreLocation $location): array {
                return [
                    'id' => (int) $location->id,
                    'store_name' => trim((string) optional($location->store)->name),
                    'address' => trim((string) ($location->formatted_address ?: $location->address)),
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'service_radius_km' => $location->service_radius_km !== null ? (float) $location->service_radius_km : null,
                ];
            })
            ->values()
            ->all();

        // Get distinct ceramic types and sizes for filters
        $ceramicTypes = Ceramic::whereNotNull('type')->distinct()->pluck('type')->filter()->sort()->values();

        $ceramicSizes = Ceramic::whereNotNull('dimension_length')
            ->whereNotNull('dimension_width')
            ->where('dimension_length', '>', 0)
            ->where('dimension_width', '>', 0)
            ->select('dimension_length', 'dimension_width')
            ->distinct()
            ->get()
            ->map(function ($ceramic) {
                // Format as "30x30" or "20x25"
                $length = (int) $ceramic->dimension_length;
                $width = (int) $ceramic->dimension_width;

                return min($length, $width) . 'x' . max($length, $width);
            })
            ->unique()
            ->sort()
            ->values();

        $defaultInstallationType = BrickInstallationType::getDefault();
        $defaultMortarFormula = $this->getPreferredMortarFormula();

        // LOGIC BARU: Handle Multi-Select Bricks dari Price Analysis
        // Kita kirim variable $selectedBricks ke View
        $selectedBricks = collect();
        $selectedBrickIds = $request->input('brick_ids', old('brick_ids', []));
        if (!is_array($selectedBrickIds)) {
            $selectedBrickIds = [$selectedBrickIds];
        }
        $selectedBrickIds = array_values(array_filter(array_map('intval', $selectedBrickIds), static fn($id) => $id > 0));
        if (!empty($selectedBrickIds)) {
            $selectedBricks = Brick::whereIn('id', $selectedBrickIds)->get();
        }

        // Check availability of 'best' recommendations per work type
        $bestRecommendations = RecommendedCombination::where('type', 'best')
            ->select('work_type')
            ->distinct()
            ->pluck('work_type')
            ->toArray();

        return view(
            'material_calculations.create',
            compact(
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'bricks',
                'cements',
                'nats',
                'sands',
                'cats',
                'ceramics',
                'workFloors',
                'workAreas',
                'workFields',
                'workItemGroupings',
                'storeLocationsForMap',
                'ceramicTypes',
                'ceramicSizes',
                'defaultInstallationType',
                'defaultMortarFormula',
                'selectedBricks',
                'bestRecommendations',
            ),
        );
    }

    /**
     * Store a newly created calculation
     */
    public function store(Request $request)
    {
        // Increase execution time for complex calculations
        set_time_limit(300); // 5 minutes

        try {
            DB::beginTransaction();

            // Handle work_type_select from form and convert to work_type
            if ($request->has('work_type_select') && !$request->has('work_type')) {
                $request->merge(['work_type' => $request->work_type_select]);
            }

            // DEBUG: Store request data to session (AFTER conversion)
            session()->put('debug_last_request', [
                'work_type' => $request->work_type,
                'work_type_select' => $request->work_type_select,
                'price_filters' => $request->price_filters,
                'ceramic_types' => $request->ceramic_types,
                'ceramic_sizes' => $request->ceramic_sizes,
                'timestamp' => now()->toDateTimeString(),
            ]);

            // CRITICAL: Validate work_type is not null
            if (empty($request->work_type)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        'Jenis Pekerjaan harus dipilih dari daftar. Mohon klik Item Pekerjaan dan pilih dari dropdown yang muncul (contoh: Pasang Keramik Lantai / Dinding).',
                    );
            }

            if (!$request->has('mortar_formula_type')) {
                $request->merge(['mortar_formula_type' => 'default']);
            }

            $this->normalizeNatIdentifiers($request);

            // 1. VALIDASI
            $rules = [
                'work_type' => 'required',
                'project_address' => 'nullable|string',
                'project_latitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-90,90',
                'project_longitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-180,180',
                'project_place_id' => 'nullable|string|max:255',
                'use_store_filter' => 'nullable|boolean',
                'store_radius_scope' => 'nullable|string|in:within,outside',
                'allow_mixed_store' => 'nullable|boolean',
                'price_filters' => 'required|array|min:1',
                'price_filters.*' => 'in:all,best,common,cheapest,medium,expensive,custom',
                'material_type_filters' => 'nullable|array',
                'material_type_filters.*' => 'nullable',
                'material_type_filters.*.*' => 'nullable|string',
                'material_type_filters_extra' => 'nullable|array',
                'material_type_filters_extra.*' => 'nullable|array',
                'material_type_filters_extra.*.*' => 'nullable|string',
                'material_customize_filters_payload' => 'nullable|string',
                'material_customize_filters' => 'nullable|array',
                'work_floors' => 'nullable|array',
                'work_floors.*' => 'nullable|string|max:120',
                'work_areas' => 'nullable|array',
                'work_areas.*' => 'nullable|string|max:120',
                'work_fields' => 'nullable|array',
                'work_fields.*' => 'nullable|string|max:120',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1',
                'skim_sides' => 'nullable|integer|min:1',
            ];

            // Remove default 'best' merge to force user selection
            // if (!$request->has('price_filters') || empty($request->price_filters)) {
            //    $request->merge(['price_filters' => ['best']]);
            // }

            // NEW LOGIC: Dynamic Material Validation based on Work Type
            $workType = $request->work_type;
            if ($workType === 'grout_tile') {
                $request->request->remove('ceramic_id');
            }
            $requiredMaterials = $this->resolveRequiredMaterials($workType);
            $needsBrick = in_array('brick', $requiredMaterials, true);
            $needsSand = in_array('sand', $requiredMaterials, true);
            $needsCement = in_array('cement', $requiredMaterials, true);
            $needsCat = in_array('cat', $requiredMaterials, true);
            $needsCeramic = in_array('ceramic', $requiredMaterials, true);
            $needsNat = in_array('nat', $requiredMaterials, true);

            // Brick Validation
            if (!$needsBrick) {
                $rules['brick_id'] = 'nullable';
                $rules['brick_ids'] = 'nullable|array';
            } else {
                $priceFilters = $request->price_filters ?? [];
                $hasCustom = in_array('custom', $priceFilters);
                $hasOtherFilters = count(array_diff($priceFilters, ['custom'])) > 0;

                if ($hasCustom && !$hasOtherFilters) {
                    if ($request->has('brick_ids')) {
                        $rules['brick_ids'] = 'required|array';
                        $rules['brick_ids.*'] = 'exists:bricks,id';
                    } else {
                        $rules['brick_id'] = 'required|exists:bricks,id';
                    }
                } else {
                    if ($request->has('brick_ids')) {
                        $rules['brick_ids'] = 'nullable|array';
                        $rules['brick_ids.*'] = 'exists:bricks,id';
                    } else {
                        $rules['brick_id'] = 'nullable|exists:bricks,id';
                    }
                }
            }

            if (!$needsSand) {
                $rules['sand_id'] = 'nullable';
            }

            if (!$needsCement) {
                $rules['cement_id'] = 'nullable';
            }

            // Ceramic Validation
            if ($needsCeramic) {
                if ($workType === 'grout_tile') {
                    $rules['ceramic_id'] = 'nullable|exists:ceramics,id';
                } elseif (in_array('custom', $request->price_filters ?? [])) {
                    $rules['ceramic_id'] = 'required|exists:ceramics,id';
                } else {
                    $rules['ceramic_id'] = 'nullable|exists:ceramics,id';
                }
            } else {
                $rules['ceramic_id'] = 'nullable';
            }

            // Nat Validation
            if ($needsNat) {
                if (in_array('custom', $request->price_filters ?? [])) {
                    $rules['nat_id'] = [
                        'required',
                        Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND),
                    ];
                } else {
                    $rules['nat_id'] = [
                        'nullable',
                        Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND),
                    ];
                }
            } else {
                $rules['nat_id'] = 'nullable';
            }

            $this->mergeMaterialTypeFilters($request);
            $this->mergeMaterialCustomizeFilters($request);
            $this->mergeWorkTaxonomyFilters($request);
            $request->validate($rules);
            $workFloors = $this->normalizeWorkTaxonomyValues($request->input('work_floors', []));
            $workAreas = $this->normalizeWorkTaxonomyValues($request->input('work_areas', []));
            $workFields = $this->normalizeWorkTaxonomyValues($request->input('work_fields', []));

            // 2. SETUP DEFAULT
            $defaultInstallationType = BrickInstallationType::where('is_active', true)->orderBy('id')->first();

            $mortarFormulaType = $request->input('mortar_formula_type');
            if ($mortarFormulaType === 'custom') {
                $request->merge(['use_custom_ratio' => true]);
                $defaultMortarFormula = MortarFormula::where('is_active', true)->orderBy('id')->first();
            } else {
                $defaultMortarFormula = MortarFormula::where('is_active', true)
                    ->where('cement_ratio', 1)
                    ->where('sand_ratio', 3)
                    ->first();
                if (!$defaultMortarFormula) {
                    $defaultMortarFormula = MortarFormula::first();
                }
                $request->merge(['use_custom_ratio' => false]);
            }

            if (!$request->has('installation_type_id')) {
                $request->merge(['installation_type_id' => $defaultInstallationType?->id]);
            }
            if (!$request->has('mortar_formula_id')) {
                $request->merge(['mortar_formula_id' => $defaultMortarFormula?->id]);
            }

            // 3. AUTO SELECT MATERIAL OR GENERATE COMBINATIONS
            $priceFilters = $request->price_filters ?? [];
            $hasCustom = in_array('custom', $priceFilters);
            $hasOtherFilters = count(array_diff($priceFilters, ['custom'])) > 0;

            // Check if we need to generate combinations
            $isMultiBrick = $request->has('brick_ids') && count($request->brick_ids) > 0;
            $isCustomEmpty = false;

            if ($hasCustom) {
                foreach ($requiredMaterials as $material) {
                    if ($material === 'brick') {
                        continue;
                    }
                    $isMissing = false;
                    if ($material === 'nat') {
                        $isMissing = empty($request->nat_id);
                    } else {
                        $key = $material . '_id';
                        $isMissing = empty($request->$key);
                    }
                    if ($isMissing) {
                        $isCustomEmpty = true;
                        break;
                    }
                }
            }

            $needCombinations = $hasOtherFilters || $isMultiBrick || $hasCustom || $isCustomEmpty;

            if ($request->boolean('confirm_save')) {
                $needCombinations = false;
            }

            if ($needCombinations) {
                DB::rollBack();
                $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);

                return $this->generateCombinations($request);
            }

            // 5. SAVE NORMAL
            $calculation = BrickCalculation::performCalculation($request->all());

            if (!$request->boolean('confirm_save')) {
                DB::rollBack();
                $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);
                $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);

                return view('material_calculations.preview', [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                    'formData' => $request->all(),
                ]);
            }

            $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);
            $calculation->save();
            DB::commit();

            return redirect()
                ->route('material-calculations.show', $calculation)
                ->with('success', 'Perhitungan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Show preview hasil perhitungan dari cache
     * Method ini support GET request sehingga bisa di-refresh dan pagination
     */
    public function showPreview(string $cacheKey)
    {
        \Log::info('showPreview called', ['cacheKey' => $cacheKey]);

        if (!str_starts_with($cacheKey, self::CALCULATION_CACHE_KEY_PREFIX)) {
            \Log::warning('Rejected stale preview cache key prefix', ['cacheKey' => $cacheKey]);

            return redirect()
                ->route('material-calculations.create')
                ->with('error', 'Hasil preview lama terdeteksi. Silakan hitung ulang untuk data terbaru.');
        }

        $cachedPayload = Cache::get($cacheKey);

        \Log::info('Cache check', [
            'exists' => $cachedPayload !== null,
            'isArray' => is_array($cachedPayload),
            'keys' => $cachedPayload ? array_keys($cachedPayload) : [],
        ]);

        if (!$cachedPayload || !is_array($cachedPayload)) {
            \Log::warning('Cache not found or invalid', ['cacheKey' => $cacheKey]);

            return redirect()
                ->route('material-calculations.create')
                ->with('error', 'Hasil perhitungan tidak ditemukan atau sudah kadaluarsa. Silakan hitung ulang.');
        }

        \Log::info('Rendering preview_combinations view', [
            'hasProjects' => !empty($cachedPayload['projects'] ?? []),
            'hasCeramicProjects' => !empty($cachedPayload['ceramicProjects'] ?? []),
            'isMultiCeramic' => $cachedPayload['isMultiCeramic'] ?? false,
        ]);

        return view('material_calculations.preview_combinations', $cachedPayload);
    }

    protected function generateCombinations(Request $request)
    {
        $perfStartedAt = microtime(true);
        $cacheKey = $this->buildCalculationCacheKey($request);
        $cachedPayload = $this->getCalculationCachePayload($cacheKey);
        if ($cachedPayload) {
            $this->logMaterialPerformanceStage($perfStartedAt, 'generate_combinations.cache_hit', [
                'cache_key' => $cacheKey,
            ]);
            // Redirect to GET route untuk support pagination dan refresh
            return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
        }

        \Log::info('generateCombinations called', [
            'work_type' => $request->work_type,
            'price_filters' => $request->price_filters,
            'has_ceramic_types' => $request->has('ceramic_types'),
            'has_ceramic_sizes' => $request->has('ceramic_sizes'),
            'ceramic_types_value' => $request->ceramic_types,
            'ceramic_sizes_value' => $request->ceramic_sizes,
            'material_type_filters' => $request->input('material_type_filters'),
            'has_material_type_filters' => $request->has('material_type_filters'),
        ]);

        $targetBricks = collect();
        $priceFilters = $request->price_filters ?? [];
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);
        $useStoreFilter = $request->boolean('use_store_filter', true) && $workType !== 'grout_tile';
        $hasExplicitBrickSelection =
            !$isBrickless &&
            (($request->has('brick_ids') && !empty($request->brick_ids)) ||
                ($request->has('brick_id') && !empty($request->brick_id)));
        $materialTypeFilters = $request->input('material_type_filters', []);

        // NEW LOGIC: "Jenis Keramik" now behaves like other single-value material type filters.
        // It should use normal preview flow (projects in preview_combinations), not multi-ceramic tab lazy loader.

        // OLD LOGIC (disabled by request):
        // $hasCeramicFilters = $request->has('ceramic_types') || $request->has('ceramic_sizes');
        // $isMultiCeramic =
        //     $isCeramicWork &&
        //     $hasCeramicFilters &&
        //     ((is_array($request->ceramic_types) && count($request->ceramic_types) > 0) ||
        //         (is_array($request->ceramic_sizes) && count($request->ceramic_sizes) > 0));
        //
        // if ($isMultiCeramic) {
        //     return $this->generateMultiCeramicCombinations($request);
        // }

        if ($isBrickless) {
            $targetBricks = collect([$this->resolveFallbackBrick()]);
        } else {
            $targetBricks = collect();
            $hasBrickIds = $request->has('brick_ids') && !empty($request->brick_ids);
            $hasBrickId = $request->has('brick_id') && !empty($request->brick_id);
            $brickTypeFilterValues = $this->normalizeMaterialTypeFilterValues($materialTypeFilters['brick'] ?? null);

            if ($hasBrickIds) {
                $query = Brick::whereIn('id', $request->brick_ids);
                if (!empty($brickTypeFilterValues)) {
                    $query->whereIn('type', $brickTypeFilterValues);
                }
                $targetBricks = $query->get();
            } elseif ($hasBrickId) {
                $query = Brick::where('id', $request->brick_id);
                if (!empty($brickTypeFilterValues)) {
                    $query->whereIn('type', $brickTypeFilterValues);
                }
                $targetBricks = $query->get();
            } else {
                // 1. Filter Preferensi (Best)
                if (in_array('best', $priceFilters, true)) {
                    $recommendedBrickIds = RecommendedCombination::where('type', 'best')
                        ->where('work_type', $workType)
                        ->pluck('brick_id')
                        ->unique()
                        ->filter();

                    if ($recommendedBrickIds->isNotEmpty()) {
                        $query = Brick::whereIn('id', $recommendedBrickIds);
                        if (!empty($brickTypeFilterValues)) {
                            $query->whereIn('type', $brickTypeFilterValues);
                        }
                        $recBricks = $query->get();
                        $targetBricks = $targetBricks->merge($recBricks);
                    }
                }

                // 2. Filter Populer (Common) - Get bricks from historical data
                if (in_array('common', $priceFilters, true)) {
                    $commonBrickIds = DB::table('brick_calculations')
                        ->select('brick_id', DB::raw('count(*) as frequency'))
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                        ->whereNotNull('brick_id')
                        ->groupBy('brick_id')
                        ->orderByDesc('frequency')
                        ->limit(5)
                        ->pluck('brick_id');

                    if ($commonBrickIds->isNotEmpty()) {
                        $query = Brick::whereIn('id', $commonBrickIds);
                        if (!empty($brickTypeFilterValues)) {
                            $query->whereIn('type', $brickTypeFilterValues);
                        }
                        $commonBricks = $query->get();
                        $targetBricks = $targetBricks->merge($commonBricks);
                    }
                }

                // 3. Filter Termahal (Expensive) - Butuh bata mahal
                if (in_array('expensive', $priceFilters, true)) {
                    $query = Brick::orderBy('price_per_piece', 'desc');
                    if (!empty($brickTypeFilterValues)) {
                        $query->whereIn('type', $brickTypeFilterValues);
                    }
                    $expensiveBricks = $query->limit(5)->get();
                    $targetBricks = $targetBricks->merge($expensiveBricks);
                }

                // 4. Filter Lainnya (Cheapest, Medium, atau Default jika kosong)
                // Kita ambil bata Ekonomis sebagai base comparison
                $otherFilters = array_diff($priceFilters, ['best', 'common', 'expensive', 'custom']);
                $needsDefaultPool = !empty($otherFilters) || $targetBricks->isEmpty();

                if ($needsDefaultPool) {
                    $query = Brick::orderBy('price_per_piece', 'asc');
                    if (!empty($brickTypeFilterValues)) {
                        $query->whereIn('type', $brickTypeFilterValues);
                    }
                    $defaultBricks = $query->limit(5)->get();
                    $targetBricks = $targetBricks->merge($defaultBricks);
                }
            }

            // Ensure common bricks from history are included when Populer filter is selected
            if (!$isBrickless && in_array('common', $priceFilters, true)) {
                $commonBrickIds = DB::table('brick_calculations')
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
                    ->whereNotNull('brick_id')
                    ->groupBy('brick_id', 'cement_id', 'sand_id', 'cat_id', 'ceramic_id', 'nat_id')
                    ->orderByDesc('frequency')
                    ->limit(10)
                    ->get()
                    ->pluck('brick_id')
                    ->unique()
                    ->filter();

                if ($commonBrickIds->isNotEmpty()) {
                    $query = Brick::whereIn('id', $commonBrickIds);
                    if (!empty($brickTypeFilterValues)) {
                        $query->whereIn('type', $brickTypeFilterValues);
                    }
                    $commonBricks = $query->get();
                    $targetBricks = $targetBricks->merge($commonBricks);
                }
            }

            // Ensure unique bricks
            $targetBricks = $targetBricks->unique('id')->values();

            // DEBUG: Log brick pool before type filtering
            \Log::info('Bricks before type filtering', [
                'count' => $targetBricks->count(),
                'brick_ids' => $targetBricks->pluck('id')->toArray(),
                'brick_types' => $targetBricks->pluck('type')->unique()->values()->toArray(),
            ]);
        }

        if (!$isBrickless && !empty($materialTypeFilters['brick'])) {
            $brickTypeFilterValues = $this->normalizeMaterialTypeFilterValues($materialTypeFilters['brick']);

            // DEBUG: Log brick type filter
            \Log::info('Applying brick type filter', [
                'filter' => $brickTypeFilterValues,
                'bricks_before' => $targetBricks->count(),
            ]);

            $targetBricks = $targetBricks
                ->filter(function ($brick) use ($brickTypeFilterValues) {
                    return $this->matchesMaterialTypeFilter(
                        $this->resolveMaterialTypeValue($brick, 'brick'),
                        $brickTypeFilterValues,
                    );
                })
                ->values();

            // DEBUG: Log result after filtering
            \Log::info('Bricks after type filtering', [
                'count' => $targetBricks->count(),
                'brick_ids' => $targetBricks->pluck('id')->toArray(),
            ]);
        }

        $this->logMaterialPerformanceStage($perfStartedAt, 'generate_combinations.target_bricks_ready', [
            'work_type' => $workType,
            'price_filters' => array_values($priceFilters),
            'target_bricks_count' => $targetBricks->count(),
            'is_brickless' => $isBrickless,
            'use_store_filter' => $useStoreFilter,
        ]);

        $projects = [];
        $complexityGuardEvents = [];
        $complexityFastModeEvents = [];
        if (!$isBrickless && $useStoreFilter && !$hasExplicitBrickSelection) {
            // In store-radius mode without explicit brick selection, avoid global brick pool iteration.
            // Let store-based engine pick bricks per reachable store so results stay within radius scope.
            $combinations = $this->combinationGenerationService->calculateCombinations($request, ['brick' => null]);
            $complexityGuardEvents = array_merge(
                $complexityGuardEvents,
                $this->combinationGenerationService->consumeComplexityGuardEvents(),
            );
            $complexityFastModeEvents = array_merge(
                $complexityFastModeEvents,
                $this->combinationGenerationService->consumeComplexityFastModeEvents(),
            );
            $displayBrick = $this->resolveDisplayBrickFromCombinations($combinations);

            \Log::info('Project combinations for store-filter single pass', [
                'combination_labels' => array_keys($combinations),
                'total_combinations' => count($combinations),
                'display_brick_id' => $displayBrick?->id,
            ]);

            $projects[] = [
                'brick' => $displayBrick,
                'combinations' => $combinations,
            ];
        } else {
            foreach ($targetBricks as $brick) {
                $combinations = $this->combinationGenerationService->calculateCombinationsForBrick($brick, $request);
                $complexityGuardEvents = array_merge(
                    $complexityGuardEvents,
                    $this->combinationGenerationService->consumeComplexityGuardEvents(),
                );
                $complexityFastModeEvents = array_merge(
                    $complexityFastModeEvents,
                    $this->combinationGenerationService->consumeComplexityFastModeEvents(),
                );
                $displayBrick = $this->resolveDisplayBrickFromCombinations($combinations) ?? $brick;

                \Log::info('Project combinations for brick', [
                    'brick_id' => $brick->id,
                    'brick_brand' => $brick->brand,
                    'display_brick_id' => $displayBrick?->id,
                    'combination_labels' => array_keys($combinations),
                    'total_combinations' => count($combinations),
                ]);

                $projects[] = [
                    'brick' => $displayBrick,
                    'combinations' => $combinations,
                ];
            }
        }

        $projectCombinationGroupCount = 0;
        $projectCombinationRowCount = 0;
        foreach ($projects as $projectRow) {
            $groups = is_array($projectRow['combinations'] ?? null) ? $projectRow['combinations'] : [];
            $projectCombinationGroupCount += count($groups);
            foreach ($groups as $groupRows) {
                if (is_array($groupRows)) {
                    $projectCombinationRowCount += count($groupRows);
                }
            }
        }
        $this->logMaterialPerformanceStage($perfStartedAt, 'generate_combinations.projects_built', [
            'projects_count' => count($projects),
            'combination_group_count' => $projectCombinationGroupCount,
            'combination_row_count' => $projectCombinationRowCount,
            'complexity_guard_event_count' => count($complexityGuardEvents),
            'complexity_fast_mode_event_count' => count($complexityFastModeEvents),
        ]);

        $hasAnyCombinationRows = false;
        foreach ($projects as $projectRow) {
            if (!is_array($projectRow)) {
                continue;
            }
            $combinationGroups = $projectRow['combinations'] ?? [];
            if (is_array($combinationGroups) && !empty($combinationGroups)) {
                $hasAnyCombinationRows = true;
                break;
            }
        }

        if (!$hasAnyCombinationRows && !empty($complexityGuardEvents)) {
            $maxEstimateSeen = 0;
            foreach ($complexityGuardEvents as $event) {
                $estimate = (int) data_get($event, 'estimate.estimated_combinations', 0);
                if ($estimate > $maxEstimateSeen) {
                    $maxEstimateSeen = $estimate;
                }
            }

            \Log::warning('Preview generation halted by complexity guard', [
                'work_type' => $workType,
                'price_filters' => $priceFilters,
                'guard_events' => $complexityGuardEvents,
                'max_estimate_seen' => $maxEstimateSeen,
            ]);

            $message =
                'Perhitungan dihentikan karena kombinasi material terlalu banyak (complexity guard aktif). ' .
                'Persempit filter material/harga atau kurangi variasi item.';
            if ($maxEstimateSeen > 0) {
                $message .= ' Estimasi kombinasi tertinggi: ' . number_format($maxEstimateSeen, 0, ',', '.');
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';

        $payload = [
            'projects' => $projects,
            'requestData' => array_merge(
                $request->except(['brick_ids', 'brick_id']),
                ['work_type' => $request->work_type], // Explicitly include work_type from merged request
            ),
            'formulaName' => $formulaName,
            'isBrickless' => $isBrickless,
            'ceramicProjects' => [],
            'calculationDiagnostics' => [
                'complexity_guard_events' => array_values($complexityGuardEvents),
                'complexity_fast_mode_events' => array_values($complexityFastModeEvents),
            ],
        ];

        $this->storeCalculationCachePayload($cacheKey, $payload);

        $this->logMaterialPerformanceStage($perfStartedAt, 'generate_combinations.payload_cached', [
            'cache_key' => $cacheKey,
            'projects_count' => count($payload['projects'] ?? []),
            'formula_name' => $formulaName,
            'complexity_guard_event_count' => count($complexityGuardEvents),
            'complexity_fast_mode_event_count' => count($complexityFastModeEvents),
        ]);

        \Log::info('Preview payload cached', [
            'cacheKey' => $cacheKey,
            'projects_count' => count($payload['projects'] ?? []),
            'has_combinations' => !empty($payload['projects']),
        ]);

        // Redirect to GET route untuk support pagination dan refresh
        return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
    }

    /**
     * Generate combinations for multiple ceramics
     * Group by Type -> Size and display in tabs
     * OPTIMIZED: Limits ceramics and combinations to prevent memory exhaustion
     */
    protected function generateMultiCeramicCombinations(Request $request)
    {
        \Log::info('generateMultiCeramicCombinations called', [
            'work_type' => $request->work_type,
            'ceramic_types' => $request->ceramic_types,
            'ceramic_sizes' => $request->ceramic_sizes,
        ]);

        $cacheKey = $this->buildCalculationCacheKey($request);
        \Log::info('Cache key generated', ['cacheKey' => $cacheKey]);

        $cachedPayload = $this->getCalculationCachePayload($cacheKey);
        if ($cachedPayload) {
            \Log::info('Using cached payload', ['cacheKey' => $cacheKey]);

            // Redirect to GET route untuk support pagination dan refresh
            return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
        }

        $workType = $request->work_type ?? 'tile_installation';

        // Build ceramic query based on filters
        $ceramicQuery = Ceramic::query();

        // For grout_tile, filter ceramics with valid dimension_thickness
        if ($workType === 'grout_tile') {
            $ceramicQuery->whereNotNull('dimension_thickness')->where('dimension_thickness', '>', 0);
        }

        // Apply ceramic type and size filters
        $ceramicQuery = $this->applyCeramicFilters($ceramicQuery, $request);

        // Get ALL matching ceramics - no limit needed with lazy loading
        // Combinations are calculated on-demand via AJAX per ceramic
        $targetCeramics = $ceramicQuery
            ->orderBy('type')
            ->orderBy('price_per_package')
            ->orderBy('dimension_length')
            ->orderBy('dimension_width')
            ->get();

        \Log::info('Ceramics filtered', [
            'count' => $targetCeramics->count(),
            'ids' => $targetCeramics->pluck('id')->toArray(),
        ]);

        if ($targetCeramics->isEmpty()) {
            \Log::warning('No ceramics found matching filters');

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Tidak ada keramik yang sesuai dengan filter yang dipilih.');
        }

        // LAZY LOAD: DON'T calculate combinations here
        // Combinations will be loaded via AJAX when user clicks tab
        $ceramicProjects = [];

        foreach ($targetCeramics as $ceramic) {
            // Format size as "30x30"
            $length = (int) $ceramic->dimension_length;
            $width = (int) $ceramic->dimension_width;
            $sizeLabel = min($length, $width) . 'x' . max($length, $width);

            $ceramicProjects[] = [
                'ceramic' => $ceramic,
                'type' => $ceramic->type ?? 'Lainnya',
                'size' => $sizeLabel,
                'combinations' => [], // Empty - will be loaded via AJAX
            ];
        }

        // Group by Type -> Size
        $groupedByType = collect($ceramicProjects)->groupBy('type');

        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Keramik';

        $payload = [
            'projects' => [], // Empty for ceramic-only work
            'ceramicProjects' => $ceramicProjects,
            'groupedCeramics' => $groupedByType,
            'isMultiCeramic' => true,
            'isLazyLoad' => true, // Enable lazy loading
            'requestData' => array_merge(
                $request->except(['ceramic_types', 'ceramic_sizes']),
                ['work_type' => $request->work_type], // Explicitly include work_type from merged request
            ),
            'formulaName' => $formulaName,
            'totalCeramics' => $targetCeramics->count(),
        ];

        \Log::info('Payload prepared', [
            'ceramicProjectsCount' => count($ceramicProjects),
            'groupedCeramicsCount' => $groupedByType->count(),
            'totalCeramics' => $targetCeramics->count(),
        ]);

        $this->storeCalculationCachePayload($cacheKey, $payload);

        \Log::info('Preview payload cached (Multi Ceramic)', [
            'cacheKey' => $cacheKey,
            'ceramicProjectsCount' => count($payload['ceramicProjects'] ?? []),
        ]);

        // Redirect to GET route untuk support pagination dan refresh
        return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
    }

    protected function buildCalculationCacheKey(Request $request): string
    {
        $payload = $request->except(['_token', 'confirm_save']);
        $payload['_engine_version'] = self::CALCULATION_CACHE_KEY_PREFIX;
        $normalized = $this->normalizeCalculationPayload($payload);

        return self::CALCULATION_CACHE_KEY_PREFIX . hash('sha256', json_encode($normalized));
    }

    protected function resolveFallbackBrick(): Brick
    {
        $brick = Brick::first();
        if ($brick) {
            return $brick;
        }

        $fallback = new Brick();
        $fallback->id = 0;
        $fallback->brand = 'N/A';

        return $fallback;
    }

    protected function normalizeCalculationPayload($value)
    {
        if (is_array($value)) {
            if (Arr::isAssoc($value)) {
                ksort($value);
                foreach ($value as $key => $item) {
                    $value[$key] = $this->normalizeCalculationPayload($item);
                }

                return $value;
            }
            $normalized = array_map([$this, 'normalizeCalculationPayload'], $value);
            sort($normalized);

            return $normalized;
        }

        return $value;
    }

    protected function resolveDisplayBrickFromCombinations(array $combinations): ?Brick
    {
        foreach ($combinations as $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (($item['brick'] ?? null) instanceof Brick) {
                    return $item['brick'];
                }
            }
        }

        return null;
    }

    protected function getCalculationCachePayload(string $cacheKey): ?array
    {
        $cached = Cache::get($cacheKey);

        return is_array($cached) ? $cached : null;
    }

    protected function storeCalculationCachePayload(string $cacheKey, array $payload): void
    {
        Cache::put($cacheKey, $payload, now()->addMinutes(360));
        session()->put('material_calc_last_key', $cacheKey);
    }

    /**
     * AJAX Endpoint: Get combinations for a specific ceramic OR a group of ceramics (Type + Size)
     * Called when user clicks a ceramic tab
     */
    public function getCeramicCombinations(Request $request)
    {
        try {
            // Ensure work_type is present (convert from work_type_select if needed)
            if ($request->has('work_type_select') && !$request->has('work_type')) {
                $request->merge(['work_type' => $request->work_type_select]);
            }

            \Log::info('getCeramicCombinations', [
                'work_type' => $request->work_type,
                'work_type_select' => $request->work_type_select,
                'has_ceramic_id' => $request->has('ceramic_id'),
                'has_type' => $request->has('type'),
                'has_size' => $request->has('size'),
            ]);

            $brick = $this->resolveFallbackBrick(); // Dummy brick for ceramic work

            if ($request->has('type') && $request->has('size')) {
                // GROUP MODE: Compare all brands within this size
                $type = $request->type;
                $size = $request->size; // e.g., "30x30"
                $dims = explode('x', $size);
                $dim1 = isset($dims[0]) ? trim($dims[0]) : 0;
                $dim2 = isset($dims[1]) ? trim($dims[1]) : 0;

                // Find all ceramics matching type and dimensions (flexible LxW or WxL)
                $ceramics = Ceramic::where('type', $type)
                    ->where(function ($q) use ($dim1, $dim2) {
                        $q->where(function ($sq) use ($dim1, $dim2) {
                            $sq->where('dimension_length', $dim1)->where('dimension_width', $dim2);
                        })->orWhere(function ($sq) use ($dim1, $dim2) {
                            $sq->where('dimension_length', $dim2)->where('dimension_width', $dim1);
                        });
                    })
                    ->orderBy('brand')
                    ->get();

                if ($ceramics->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Data keramik tidak ditemukan'], 404);
                }

                $combinations = $this->calculateCombinationsForCeramicGroup($brick, $request, $ceramics);

                \Log::info('Combinations calculated (GROUP)', [
                    'type' => $type,
                    'size' => $size,
                    'ceramics_count' => $ceramics->count(),
                    'combinations_count' => count($combinations),
                    'combinations_keys' => array_keys($combinations),
                ]);

                $contextCeramic = $ceramics->first(); // Context for view
                $isGroupMode = true;
            } else {
                // SINGLE MODE: Specific ceramic ID
                $ceramicId = $request->ceramic_id;
                $ceramic = Ceramic::findOrFail($ceramicId);
                $ceramics = collect([$ceramic]);

                $combinations = $this->combinationGenerationService->calculateCombinationsForBrick(
                    $brick,
                    $request,
                    $ceramic,
                );
                $contextCeramic = $ceramic;
                $isGroupMode = false;
            }

            // Return HTML fragment for the combinations table
            return response()->json([
                'success' => true,
                'html' => view('material_calculations.partials.ceramic_combinations_table', [
                    'ceramic' => $contextCeramic,
                    'combinations' => $combinations,
                    'requestData' => array_merge(
                        $request->except(['ceramic_id', '_token']),
                        ['work_type' => $request->work_type], // Explicitly include work_type
                    ),
                    'isGroupMode' => $isGroupMode,
                ])->render(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine(),
                ],
                500,
            );
        }
    }

    /**
     * Calculate and compare combinations for a GROUP of ceramics (e.g. all 30x30 Glossy)
     * Competes across brands.
     */
    protected function calculateCombinationsForCeramicGroup($brick, $request, $ceramics)
    {
        $priceFilters = $request->price_filters ?? ['best'];
        $allCombinations = [];
        $groupCeramicIds = $ceramics->pluck('id')->all();
        $materialTypeFilters = $request->input('material_type_filters', []);

        // Pre-fetch related materials to avoid N+1 in loops
        // Apply material type filters
        $cementQuery = Cement::query();
        $cementFilterValues = $this->normalizeMaterialTypeFilterValues($materialTypeFilters['cement'] ?? null);
        if (!empty($cementFilterValues)) {
            $cementQuery->whereIn('type', $cementFilterValues);
        }
        $cements = $cementQuery->orderBy('package_price')->get();

        $natQuery = Nat::query();
        $natFilterValues = $this->normalizeMaterialTypeFilterValues($materialTypeFilters['nat'] ?? null);
        // Backward compatibility: plain "Nat" means no specific nat type filter.
        $natFilterValues = array_values(
            array_filter($natFilterValues, static function ($value) {
                return strtolower(trim((string) $value)) !== 'nat';
            }),
        );
        if (!empty($natFilterValues)) {
            $natQuery->whereIn('type', $natFilterValues);
        }
        $nats = $natQuery->orderBy('package_price')->get();

        $sandQuery = Sand::query();
        $sandFilterValues = $this->normalizeMaterialTypeFilterValues($materialTypeFilters['sand'] ?? null);
        if (!empty($sandFilterValues)) {
            $sandQuery->whereIn('type', $sandFilterValues);
        }
        $sands = $sandQuery->orderBy('package_price')->get();

        \Log::info('calculateCombinationsForCeramicGroup START', [
            'work_type' => $request->work_type,
            'price_filters' => $priceFilters,
            'material_type_filters' => $materialTypeFilters,
            'ceramics_count' => $ceramics->count(),
            'cements_count' => $cements->count(),
            'nats_count' => $nats->count(),
            'sands_count' => $sands->count(),
        ]);

        foreach ($priceFilters as $filter) {
            // For 'best' filter, use RecommendedCombination
            if ($filter === 'best') {
                $workType = $request->work_type ?? 'tile_installation';
                $recommendations = RecommendedCombination::where('work_type', $workType)->where('type', 'best')->get();

                $bestCombos = [];

                foreach ($recommendations as $rec) {
                    // Resolve Materials from Recommendation
                    // If ID is present, use specific. If null, fallback to ALL available (pre-fetched)
                    $recCements = $rec->cement_id ? Cement::where('id', $rec->cement_id)->get() : $cements;
                    $recNats = $rec->nat_id ? Nat::where('id', $rec->nat_id)->get() : $nats;
                    $recSands = $rec->sand_id ? Sand::where('id', $rec->sand_id)->get() : $sands;

                    // Resolve Ceramics
                    // UPDATE: Always apply recommended materials to the CURRENT group of ceramics.
                    // This ensures "Best" materials (Cement/Sand/Nat) are used even if the
                    // recommendation's specific ceramic_id doesn't match the current tab.
                    $targetCeramics = $ceramics;

                    if ($targetCeramics->isEmpty()) {
                        continue;
                    }

                    // Calculate
                    $recResults = $this->combinationGenerationService->calculateCombinationsFromMaterials(
                        $brick,
                        $request->all(),
                        $recCements,
                        $recSands,
                        collect(),
                        $targetCeramics,
                        $recNats,
                        'Preferensi',
                        1,
                    );

                    foreach ($recResults as $res) {
                        $bestCombos[] = $res;
                    }
                }

                if (!empty($bestCombos)) {
                    // Sort best combos by total price and take top 1
                    usort($bestCombos, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
                    $bestCombos = array_slice($bestCombos, 0, 1);

                    foreach ($bestCombos as $index => $combo) {
                        $number = $index + 1;
                        $filterLabel = $this->combinationGenerationService->getFilterLabel($filter);
                        $allCombinations[] = array_merge($combo, [
                            'filter_label' => "{$filterLabel} {$number}",
                            'filter_type' => $filter,
                            'filter_number' => $number,
                        ]);
                    }

                    continue; // Skip default generation for 'best'
                }
            }

            // For 'common' filter, use historical frequency data
            if ($filter === 'common') {
                $commonCombos = $this->combinationGenerationService->getCommonCombinations($brick, $request->all());
                if (!empty($groupCeramicIds)) {
                    $commonCombos = array_values(
                        array_filter($commonCombos, function ($combo) use ($groupCeramicIds) {
                            $ceramicId = $combo['ceramic']->id ?? null;

                            return $ceramicId && in_array($ceramicId, $groupCeramicIds, true);
                        }),
                    );
                }
                foreach ($commonCombos as $index => $combo) {
                    $number = $index + 1;
                    $filterLabel = $this->combinationGenerationService->getFilterLabel($filter);
                    $allCombinations[] = array_merge($combo, [
                        'filter_label' => "{$filterLabel} {$number}",
                        'filter_type' => $filter,
                        'filter_number' => $number,
                    ]);
                }

                continue;
            }

            if ($filter === 'custom') {
                $workType = $request->work_type ?? 'tile_installation';
                $requiredMaterials = $this->resolveRequiredMaterials($workType);
                $missingRequired = false;

                $customCements = collect();
                $customSands = collect();
                $customNats = collect();

                foreach ($requiredMaterials as $material) {
                    if ($material === 'brick' || $material === 'ceramic') {
                        continue;
                    }

                    $key = $material . '_id';
                    if ($material === 'nat') {
                        if (empty($request->nat_id)) {
                            $missingRequired = true;
                            break;
                        }

                        continue;
                    }
                    if (empty($request->$key)) {
                        $missingRequired = true;
                        break;
                    }

                    switch ($material) {
                        case 'cement':
                            $customCements = Cement::where('id', $request->cement_id)->get();
                            break;
                        case 'sand':
                            $customSands = Sand::where('id', $request->sand_id)->get();
                            break;
                        case 'nat':
                            $customNats = Nat::where('id', $request->nat_id)->get();
                            break;
                    }
                }

                if ($missingRequired) {
                    continue;
                }

                $customCeramics = $ceramics;
                if ($request->filled('ceramic_id')) {
                    $customCeramics = $ceramics->where('id', (int) $request->ceramic_id)->values();
                }

                if ($customCeramics->isEmpty()) {
                    continue;
                }

                $customCombos = $this->combinationGenerationService->calculateCombinationsFromMaterials(
                    $brick,
                    $request->all(),
                    $customCements,
                    $customSands,
                    collect(),
                    $customCeramics,
                    $customNats,
                    'Custom',
                    1,
                );

                foreach ($customCombos as $index => $combo) {
                    $number = $index + 1;
                    $filterLabel = $this->combinationGenerationService->getFilterLabel($filter);

                    $allCombinations[] = array_merge($combo, [
                        'filter_label' => "{$filterLabel} {$number}",
                        'filter_type' => $filter,
                        'filter_number' => $number,
                    ]);
                }

                continue;
            }

            $groupCombos = [];

            // 1. Generate combos for ALL ceramics in this group
            // We use a generator that yields results from all ceramics sequentially
            // NOTE: yieldGroupCombinations is NOT public in service, so we might need to rely on controller method or make it public.
            // For now, let's assume we keep the controller helper method or move it.
            // Actually, yieldGroupCombinations calls yieldTileInstallationCombinations (also protected).
            // This suggests we need to migrate these to service public methods or keep duplicate private methods.
            // To fix OOM, we just need to stop the logging.
            // Let's replace the logic with service calls where possible.

            // Actually, since calculateCombinationsForBrick logic is done, we might not need to touch this deeply right now
            // unless this is also causing OOM. The logs suggested the OOM was in the main brick loop.
            // But let's fix the calls we see here.

            // To properly fix this, I need yieldGroupCombinations in the Service to be public.
            // Checking service... it is protected.
            // So I can't call it.

            // I'll leave the generator logic here for now but use service for filtering labels.
            // Wait, I can't mix usage easily.

            // Let's just fix the method calls I CAN fix.

            $groupGenerator = $this->yieldGroupCombinations(
                $brick,
                $request,
                $ceramics,
                $cements,
                $sands,
                $nats,
                $this->combinationGenerationService->getFilterLabel($filter),
            );

            // ... (rest of logic same) ...

            $candidates = [];
            foreach ($groupGenerator as $combo) {
                $candidates[] = $combo;
                if (count($candidates) > 200) {
                    $this->sortCandidates($candidates, $filter);
                    $candidates = array_slice($candidates, 0, 50);
                }
            }

            $this->sortCandidates($candidates, $filter);

            $limit = $filter === 'best' ? 1 : null;
            $topCandidates = $limit ? array_slice($candidates, 0, $limit) : $candidates;

            foreach ($topCandidates as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->combinationGenerationService->getFilterLabel($filter);

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        $uniqueCombos = $this->combinationGenerationService->detectAndMergeDuplicates($allCombinations);

        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $label = $combo['filter_label'];
            $finalResults[$label] = [$combo];
        }

        return $finalResults;
    }

    /**
     * Helper to yield combinations from MULTIPLE ceramics
     */
    protected function yieldGroupCombinations($brick, $request, $ceramics, $cements, $sands, $nats, $label)
    {
        $paramsBase = $request->except([
            '_token',
            'price_filters',
            'brick_ids',
            'brick_id',
            'material_type_filters',
            'ceramic_id',
            'ceramic_ids',
        ]);
        $paramsBase['brick_id'] = $brick->id;

        foreach ($ceramics as $ceramic) {
            foreach (
                $this->yieldTileInstallationCombinations($paramsBase, [$ceramic], $nats, $cements, $sands, $label)
                as $combo
            ) {
                yield $combo;
            }
        }
    }

    /**
     * Local generator for tile installation combinations.
     * Kept in controller because group preview uses lazy/AJAX flow and needs a streamable generator.
     */
    protected function yieldTileInstallationCombinations(
        array $paramsBase,
        iterable $ceramics,
        iterable $nats,
        iterable $cements,
        iterable $sands,
        string $groupLabel,
    ) {
        foreach ($ceramics as $ceramic) {
            foreach ($nats as $nat) {
                foreach ($cements as $cement) {
                    if (($cement->package_weight_net ?? 0) <= 0) {
                        continue;
                    }

                    foreach ($sands as $sand) {
                        $hasPricePerM3 = ($sand->comparison_price_per_m3 ?? 0) > 0;
                        $hasPackageData = ($sand->package_volume ?? 0) > 0 && ($sand->package_price ?? 0) > 0;
                        if (!$hasPricePerM3 && !$hasPackageData) {
                            continue;
                        }

                        $natId = $nat->nat_id ?? ($nat->id ?? null);
                        if (!$natId) {
                            continue;
                        }

                        $params = array_merge($paramsBase, [
                            'ceramic_id' => $ceramic->id,
                            'nat_id' => $natId,
                            'cement_id' => $cement->id,
                            'sand_id' => $sand->id,
                        ]);

                        try {
                            $formula = FormulaRegistry::instance('tile_installation');
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
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }
                }
            }
        }
    }

    /**
     * Helper to sort combination candidates based on filter type
     */
    protected function sortCandidates(&$candidates, $filter)
    {
        // Calculate Median for Medium filter
        $medianPrice = 0;
        if ($filter === 'medium' && count($candidates) > 0) {
            // First, sort by price to find median
            usort($candidates, function ($a, $b) {
                return $a['total_cost'] <=> $b['total_cost'];
            });
            $middleIndex = floor((count($candidates) - 1) / 2);
            $medianPrice = $candidates[$middleIndex]['total_cost'];
        }

        usort($candidates, function ($a, $b) use ($filter, $medianPrice) {
            if ($filter === 'expensive') {
                return $b['total_cost'] <=> $a['total_cost']; // Descending
            } elseif ($filter === 'best') {
                // For Best, prioritize recommendation, then price
                // Since we don't have recommendation score in yield, fallback to price ASC
                return $a['total_cost'] <=> $b['total_cost'];
            } elseif ($filter === 'medium') {
                // Sort by distance from median
                $diffA = abs($a['total_cost'] - $medianPrice);
                $diffB = abs($b['total_cost'] - $medianPrice);

                if ($diffA == $diffB) {
                    return $a['total_cost'] <=> $b['total_cost'];
                }

                return $diffA <=> $diffB;
            } else {
                return $a['total_cost'] <=> $b['total_cost']; // Ascending (Cheapest, Common)
            }
        });
    }

    // Methods for ceramic/common combinations moved to Service

    /**
     * Apply ceramic filters based on request parameters
     * Filters by type and size (dimensions)
     */
    protected function applyCeramicFilters($query, $request)
    {
        // Filter by ceramic types (Jenis Keramik)
        if ($request->has('ceramic_types') && is_array($request->ceramic_types) && !empty($request->ceramic_types)) {
            $query->whereIn('type', $request->ceramic_types);
        }

        // Filter by ceramic sizes (Ukuran Keramik)
        if ($request->has('ceramic_sizes') && is_array($request->ceramic_sizes) && !empty($request->ceramic_sizes)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->ceramic_sizes as $size) {
                    // Parse size like "30x30" or "20x25"
                    $dimensions = explode('x', $size);
                    if (count($dimensions) === 2) {
                        $length = trim($dimensions[0]);
                        $width = trim($dimensions[1]);

                        // Match ceramics with these dimensions (either length x width or width x length)
                        $q->orWhere(function ($subQ) use ($length, $width) {
                            $subQ->where('dimension_length', $length)->where('dimension_width', $width);
                        })->orWhere(function ($subQ) use ($length, $width) {
                            $subQ->where('dimension_length', $width)->where('dimension_width', $length);
                        });
                    }
                }
            });
        }

        return $query;
    }

    protected function resolveCeramicsForCalculation(
        $request,
        $workType,
        $fixedCeramic,
        $orderBy,
        $direction = 'asc',
        $limit = null,
        $skip = null,
    ) {
        if ($fixedCeramic) {
            return collect([$fixedCeramic]);
        }

        if ($request->filled('ceramic_id')) {
            $ceramic = Ceramic::find($request->ceramic_id);

            return $ceramic ? collect([$ceramic]) : collect();
        }

        $ceramicQuery = Ceramic::query();

        if ($workType === 'grout_tile') {
            $ceramicQuery->whereNotNull('dimension_thickness')->where('dimension_thickness', '>', 0);
        }

        if ($workType === 'tile_installation') {
            $ceramicQuery = $this->applyCeramicFilters($ceramicQuery, $request);
        }

        $ceramicQuery->orderBy($orderBy, $direction);

        if (!is_null($skip) && $skip > 0) {
            $ceramicQuery->skip($skip);
        }

        if (!is_null($limit)) {
            $ceramicQuery->limit($limit);
        }

        return $ceramicQuery->get();
    }

    // Methods moved to CombinationGenerationService

    protected function resolveRequiredMaterials(string $workType): array
    {
        $materials = FormulaRegistry::materialsFor($workType);

        return !empty($materials) ? $materials : ['brick', 'cement', 'sand'];
    }

    public function show(BrickCalculation $materialCalculation)
    {
        $materialCalculation->load([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
            'cat',
            'ceramic',
            'nat',
        ]);
        $summary = $materialCalculation->getSummary();

        return view('material_calculations.show_log', compact('materialCalculation', 'summary'));
    }

    public function edit(BrickCalculation $materialCalculation)
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        return view(
            'material_calculations.edit',
            compact(
                'materialCalculation',
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'bricks',
                'cements',
                'sands',
            ),
        );
    }

    public function update(Request $request, BrickCalculation $materialCalculation)
    {
        $request->validate($this->updateValidationRules());

        try {
            DB::beginTransaction();
            $newCalculation = BrickCalculation::performCalculation($request->all());
            $materialCalculation->fill($newCalculation->toArray());
            $materialCalculation->save();
            DB::commit();

            return redirect()
                ->route('material-calculations.show', $materialCalculation)
                ->with('success', 'Perhitungan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(BrickCalculation $materialCalculation)
    {
        try {
            $materialCalculation->delete();

            return redirect()->route('material-calculations.log')->with('success', 'Perhitungan berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus perhitungan: ' . $e->getMessage());
        }
    }

    public function calculate(Request $request)
    {
        // Increase execution time for complex calculations
        set_time_limit(300); // 5 minutes

        $request->validate($this->calculateValidationRules());

        try {
            $calculation = BrickCalculation::performCalculation($request->all());
            $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);
            $summary = $calculation->getSummary();

            return response()->json(['success' => true, 'data' => $calculation, 'summary' => $summary]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function compare(Request $request)
    {
        $request->validate($this->compareValidationRules());

        try {
            $installationTypes = BrickInstallationType::getActive();
            $comparisons = [];
            foreach ($installationTypes as $type) {
                $params = array_merge($request->all(), ['installation_type_id' => $type->id]);
                $calculation = BrickCalculation::performCalculation($params);
                $comparisons[] = [
                    'installation_type' => $type->name,
                    'brick_quantity' => $calculation->brick_quantity,
                    'mortar_volume' => $calculation->mortar_volume,
                    'cement_50kg' => $calculation->cement_quantity_50kg,
                    'sand_m3' => $calculation->sand_m3,
                    'water_liters' => $calculation->water_liters,
                    'total_cost' => $calculation->total_material_cost,
                ];
            }

            return response()->json(['success' => true, 'data' => $comparisons]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getBrickDimensions($brickId)
    {
        try {
            $brick = Brick::findOrFail($brickId);

            return response()->json([
                'success' => true,
                'data' => [
                    'length' => $brick->dimension_length,
                    'width' => $brick->dimension_width,
                    'height' => $brick->dimension_height,
                    'price_per_piece' => $brick->price_per_piece,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bata tidak ditemukan'], 404);
        }
    }

    public function exportPdf(BrickCalculation $materialCalculation)
    {
        return redirect()->back()->with('info', 'Fitur export PDF akan ditambahkan di fase berikutnya');
    }

    public function traceView()
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $defaultMortarFormula = $this->getPreferredMortarFormula();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::query()->orderBy('cement_name')->get();
        $nats = Nat::orderBy('brand')->get();
        $sands = Sand::orderBy('sand_name')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();

        return view(
            'material_calculations.trace',
            compact(
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'defaultMortarFormula',
                'bricks',
                'cements',
                'nats',
                'sands',
                'cats',
                'ceramics',
            ),
        );
    }

    protected function getPreferredMortarFormula(): ?MortarFormula
    {
        return MortarFormula::where('is_active', true)->where('cement_ratio', 1)->where('sand_ratio', 3)->first() ??
            MortarFormula::getDefault();
    }

    protected function normalizeNatIdentifiers(Request $request): void
    {
        if ($request->filled('nat_id')) {
            $request->merge(['nat_id' => (int) $request->input('nat_id')]);
        }
    }

    protected function applyMaterialTypeFiltersToCollections(
        Request $request,
        array $requiredMaterials,
        $materialTypeFilters,
        array $collections,
    ): array {
        $filters = is_array($materialTypeFilters) ? $materialTypeFilters : [];
        if (empty($filters)) {
            return $collections;
        }

        foreach ($filters as $type => $value) {
            $filterValues = $this->normalizeMaterialTypeFilterValues($value);
            if (empty($filterValues) || !in_array($type, $requiredMaterials, true)) {
                continue;
            }
            if (array_key_exists($type, $collections)) {
                $collections[$type] = $this->filterCollectionByMaterialType($collections[$type], $type, $filterValues);
            }
        }

        return $collections;
    }

    protected function filterCollectionByMaterialType($collection, string $type, array $filterValues)
    {
        $collection = $collection instanceof \Illuminate\Support\Collection ? $collection : collect($collection);
        if ($collection->isEmpty()) {
            return $collection;
        }

        return $collection
            ->filter(function ($model) use ($type, $filterValues) {
                return $this->matchesMaterialTypeFilter($this->resolveMaterialTypeValue($model, $type), $filterValues);
            })
            ->values();
    }

    protected function resolveMaterialTypeValue($model, string $type): ?string
    {
        if (!$model) {
            return null;
        }

        return match ($type) {
            'brick' => $model->type ?? null,
            'cement' => $model->type ?? null,
            'sand' => $model->type ?? null,
            'cat' => $model->type ?? null,
            'ceramic' => $this->formatCeramicSizeValue(
                $model->dimension_length ?? null,
                $model->dimension_width ?? null,
            ),
            'nat' => $model->type ?? null,
            default => null,
        };
    }

    protected function formatCeramicSizeValue($length, $width): ?string
    {
        $lengthValue = is_numeric($length) ? (float) $length : null;
        $widthValue = is_numeric($width) ? (float) $width : null;
        if (empty($lengthValue) || empty($widthValue)) {
            return null;
        }

        $min = min($lengthValue, $widthValue);
        $max = max($lengthValue, $widthValue);
        if ($min <= 0 || $max <= 0) {
            return null;
        }

        $minText = \App\Helpers\NumberHelper::format($min);
        $maxText = \App\Helpers\NumberHelper::format($max);
        if ($minText === '' || $maxText === '') {
            return null;
        }

        return $minText . ' x ' . $maxText;
    }

    protected function passesMaterialTypeFilters(array $filters, array $models, array $requiredMaterials): bool
    {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $type => $value) {
            $filterValues = $this->normalizeMaterialTypeFilterValues($value);
            if (empty($filterValues) || !in_array($type, $requiredMaterials, true)) {
                continue;
            }
            $model = $models[$type] ?? null;
            if (!$model) {
                return false;
            }
            $modelType = $this->resolveMaterialTypeValue($model, $type);
            if (!$this->matchesMaterialTypeFilter($modelType, $filterValues)) {
                return false;
            }
        }

        return true;
    }

    protected function mergeMaterialTypeFilters(Request $request): void
    {
        $baseFilters = $request->input('material_type_filters', []);
        $extraFilters = $request->input('material_type_filters_extra', []);

        $baseFilters = is_array($baseFilters) ? $baseFilters : [];
        $extraFilters = is_array($extraFilters) ? $extraFilters : [];
        $keys = array_unique(array_merge(array_keys($baseFilters), array_keys($extraFilters)));

        $mergedFilters = [];
        foreach ($keys as $key) {
            $values = [];
            $values = array_merge($values, $this->normalizeMaterialTypeFilterValues($baseFilters[$key] ?? null));
            $values = array_merge($values, $this->normalizeMaterialTypeFilterValues($extraFilters[$key] ?? null));
            $values = array_values(array_unique($values));

            if (count($values) === 1) {
                $mergedFilters[$key] = $values[0];
            } elseif (count($values) > 1) {
                $mergedFilters[$key] = $values;
            } else {
                $mergedFilters[$key] = null;
            }
        }

        $request->merge(['material_type_filters' => $mergedFilters]);
    }

    protected function mergeMaterialCustomizeFilters(Request $request): void
    {
        $merged = [];

        $rawDirect = $request->input('material_customize_filters');
        $rawPayload = $request->input('material_customize_filters_payload');

        $sources = [];
        if (is_array($rawDirect)) {
            $sources[] = $rawDirect;
        }
        if (is_string($rawPayload) && trim($rawPayload) !== '') {
            $decoded = json_decode($rawPayload, true);
            if (!is_array($decoded)) {
                $decoded = json_decode(html_entity_decode($rawPayload, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            }
            if (is_array($decoded)) {
                $sources[] = $decoded;
            }
        }

        $allowedFields = $this->allowedMaterialCustomizeFields();
        foreach ($sources as $source) {
            foreach ($source as $materialKey => $fieldMap) {
                $material = trim((string) $materialKey);
                if ($material === '' || !isset($allowedFields[$material]) || !is_array($fieldMap)) {
                    continue;
                }

                foreach ($allowedFields[$material] as $fieldKey) {
                    $values = $this->normalizeMaterialTypeFilterValues($fieldMap[$fieldKey] ?? null);
                    if (empty($values)) {
                        continue;
                    }

                    $existing = $merged[$material][$fieldKey] ?? [];
                    $merged[$material][$fieldKey] = array_values(array_unique(array_merge($existing, $values)));
                }
            }
        }

        $normalized = [];
        foreach ($merged as $material => $fieldMap) {
            $normalizedFieldMap = [];
            foreach ($fieldMap as $field => $values) {
                $tokens = $this->normalizeMaterialTypeFilterValues($values);
                if (empty($tokens)) {
                    continue;
                }
                $normalizedFieldMap[$field] = count($tokens) === 1 ? $tokens[0] : array_values($tokens);
            }

            if (!empty($normalizedFieldMap)) {
                $normalized[$material] = $normalizedFieldMap;
            }
        }

        $request->merge(['material_customize_filters' => $normalized]);
    }

    protected function mergeWorkTaxonomyFilters(Request $request): void
    {
        $floors = $this->normalizeWorkTaxonomyValues($request->input('work_floors', []));
        $areas = $this->normalizeWorkTaxonomyValues($request->input('work_areas', []));
        $fields = $this->normalizeWorkTaxonomyValues($request->input('work_fields', []));

        $request->merge([
            'work_floors' => $floors,
            'work_areas' => $areas,
            'work_fields' => $fields,
        ]);
    }

    protected function normalizeWorkTaxonomyValues(mixed $raw): array
    {
        if (is_array($raw)) {
            $tokens = [];
            foreach ($raw as $value) {
                $tokens = array_merge($tokens, $this->normalizeWorkTaxonomyValues($value));
            }

            return array_values(array_unique($tokens));
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }

        return [$text];
    }

    protected function persistWorkItemTaxonomy(string $workType, array $floors = [], array $areas = [], array $fields = []): void
    {
        $formulaCode = trim($workType);
        if ($formulaCode === '') {
            return;
        }

        $floorIds = collect($floors)
            ->map(fn($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->map(function (string $name): int {
                $floor = WorkFloor::firstOrCreate(['name' => $name]);

                return (int) $floor->id;
            })
            ->all();

        $areaIds = collect($areas)
            ->map(fn($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->map(function (string $name): int {
                $area = WorkArea::firstOrCreate(['name' => $name]);

                return (int) $area->id;
            })
            ->all();

        $fieldIds = collect($fields)
            ->map(fn($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->map(function (string $name): int {
                $field = WorkField::firstOrCreate(['name' => $name]);

                return (int) $field->id;
            })
            ->all();

        if (empty($floorIds) && empty($areaIds) && empty($fieldIds)) {
            return;
        }

        $floorTargets = !empty($floorIds) ? $floorIds : [null];
        $areaTargets = !empty($areaIds) ? $areaIds : [null];
        $fieldTargets = !empty($fieldIds) ? $fieldIds : [null];

        foreach ($floorTargets as $floorId) {
            foreach ($areaTargets as $areaId) {
                foreach ($fieldTargets as $fieldId) {
                    WorkItemGrouping::firstOrCreate([
                        'formula_code' => $formulaCode,
                        'work_floor_id' => $floorId,
                        'work_area_id' => $areaId,
                        'work_field_id' => $fieldId,
                    ]);
                }
            }
        }
    }

    protected function normalizeMaterialCustomizeFilters(mixed $rawFilters): array
    {
        if (!is_array($rawFilters)) {
            return [];
        }

        $allowedFields = $this->allowedMaterialCustomizeFields();
        $normalized = [];

        foreach ($rawFilters as $materialKey => $fieldMap) {
            $material = trim((string) $materialKey);
            if ($material === '' || !isset($allowedFields[$material]) || !is_array($fieldMap)) {
                continue;
            }

            $normalizedFieldMap = [];
            foreach ($allowedFields[$material] as $fieldKey) {
                $tokens = $this->normalizeMaterialTypeFilterValues($fieldMap[$fieldKey] ?? null);
                if (empty($tokens)) {
                    continue;
                }
                $normalizedFieldMap[$fieldKey] = count($tokens) === 1 ? $tokens[0] : array_values($tokens);
            }

            if (!empty($normalizedFieldMap)) {
                $normalized[$material] = $normalizedFieldMap;
            }
        }

        return $normalized;
    }

    protected function allowedMaterialCustomizeFields(): array
    {
        return [
            'brick' => ['brand', 'dimension'],
            'cement' => ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
            'sand' => ['brand'],
            'cat' => ['brand', 'sub_brand', 'color_code', 'color_name', 'package_unit', 'volume_display', 'package_weight_net'],
            'ceramic_type' => ['brand', 'dimension', 'sub_brand', 'surface', 'code', 'color'],
            'ceramic' => ['brand', 'dimension', 'sub_brand', 'surface', 'code', 'color'],
            'nat' => ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
        ];
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

    protected function matchesMaterialTypeFilter(?string $actualValue, array $filterValues): bool
    {
        if (empty($filterValues)) {
            return true;
        }
        if ($actualValue === null || $actualValue === '') {
            return false;
        }

        return in_array($actualValue, $filterValues, true);
    }

    private function baseCalculationValidationRules(): array
    {
        return [
            'work_type' => 'nullable|string',
            'project_address' => 'nullable|string',
            'project_latitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-90,90',
            'project_longitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-180,180',
            'project_place_id' => 'nullable|string|max:255',
            'use_store_filter' => 'nullable|boolean',
            'store_radius_scope' => 'nullable|string|in:within,outside',
            'allow_mixed_store' => 'nullable|boolean',
            'work_floors' => 'nullable|array',
            'work_floors.*' => 'nullable|string|max:120',
            'work_areas' => 'nullable|array',
            'work_areas.*' => 'nullable|string|max:120',
            'work_fields' => 'nullable|array',
            'work_fields.*' => 'nullable|string|max:120',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => ['nullable', Rule::exists('cements', 'id')->where('material_kind', Cement::MATERIAL_KIND)],
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
        ];
    }

    private function updateValidationRules(): array
    {
        return array_merge($this->baseCalculationValidationRules(), [
            'work_type' => 'required|string',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'project_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'plaster_sides' => 'nullable|integer|min:1',
            'skim_sides' => 'nullable|integer|min:1',
        ]);
    }

    private function calculateValidationRules(): array
    {
        return array_merge($this->baseCalculationValidationRules(), [
            'installation_type_id' => 'required|exists:brick_installation_types,id',
        ]);
    }

    private function compareValidationRules(): array
    {
        return $this->baseCalculationValidationRules();
    }

    public function traceCalculation(Request $request)
    {
        $request->validate([
            'formula_code' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:formula_code,brick_rollag|numeric|min:0.01',
            'installation_type_id' => 'nullable|exists:brick_installation_types,id',
            'mortar_thickness' => 'nullable|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'nullable|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => ['nullable', Rule::exists('cements', 'id')->where('material_kind', Cement::MATERIAL_KIND)],
            'sand_id' => 'nullable|exists:sands,id',
            'cat_id' => 'nullable|exists:cats,id',
            'ceramic_id' => 'nullable|exists:ceramics,id',
            'nat_id' => ['nullable', Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND)],
            'grout_thickness' => 'nullable|numeric|min:0.1|max:20',
            'grout_package_weight' => 'nullable|numeric|min:0.1',
            'grout_volume_per_package' => 'nullable|numeric|min:0.0001',
            'grout_price_per_package' => 'nullable|numeric|min:0',
            'custom_cement_ratio' => 'nullable|numeric|min:1',
            'custom_sand_ratio' => 'nullable|numeric|min:1',
            'has_additional_layer' => 'nullable|boolean',
            'layer_count' => 'nullable|integer|min:1',
            'plaster_sides' => 'nullable|integer|min:1',
            'skim_sides' => 'nullable|integer|min:1',
            // Ceramic dimensions for grout_tile formula
            'ceramic_length' => 'nullable|numeric|min:1',
            'ceramic_width' => 'nullable|numeric|min:1',
            'ceramic_thickness' => 'nullable|numeric|min:0.1',
        ]);

        try {
            $formulaCode = $request->input('formula_code');
            $formula = FormulaRegistry::instance($formulaCode);
            if (!$formula) {
                return response()->json(
                    ['success' => false, 'message' => "Formula dengan code '{$formulaCode}' tidak ditemukan"],
                    404,
                );
            }
            if (!$formula->validate($request->all())) {
                return response()->json(
                    ['success' => false, 'message' => 'Parameter tidak valid untuk formula ini'],
                    422,
                );
            }
            $trace = $formula->trace($request->all());

            return response()->json(['success' => true, 'data' => $trace]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

