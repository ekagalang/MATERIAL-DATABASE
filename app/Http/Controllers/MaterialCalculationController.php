<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Repositories\CalculationRepository;
use App\Services\FormulaRegistry;
use App\Services\BrickCalculationTracer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaterialCalculationController extends Controller
{
    protected CalculationRepository $calculationRepository;

    public function __construct(CalculationRepository $calculationRepository)
    {
        $this->calculationRepository = $calculationRepository;
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
     * Show the form for creating a new calculation
     */
    public function create(Request $request)
    {
        $availableFormulas = FormulaRegistry::all();
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('brand')->get();
        $nats = Cement::where('type', 'Nat')->orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();

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
        if ($request->has('brick_ids')) {
            $selectedBricks = Brick::whereIn('id', $request->brick_ids)->get();
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

            // 1. VALIDASI
            $rules = [
                'work_type' => 'required',
                'price_filters' => 'required|array|min:1',
                'price_filters.*' => 'in:all,best,common,cheapest,medium,expensive,custom',
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
                if (in_array('custom', $request->price_filters ?? [])) {
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
                    $rules['nat_id'] = 'required|exists:cements,id';
                } else {
                    $rules['nat_id'] = 'nullable|exists:cements,id';
                }
            } else {
                $rules['nat_id'] = 'nullable';
            }

            $request->validate($rules);

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
                    $key = $material . '_id';
                    if (empty($request->$key)) {
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
                return $this->generateCombinations($request);
            }

            // 5. SAVE NORMAL
            $calculation = BrickCalculation::performCalculation($request->all());

            if (!$request->boolean('confirm_save')) {
                DB::rollBack();
                $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);
                return view('material_calculations.preview', [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                    'formData' => $request->all(),
                ]);
            }

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
        $cacheKey = $this->buildCalculationCacheKey($request);
        $cachedPayload = $this->getCalculationCachePayload($cacheKey);
        if ($cachedPayload) {
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
        ]);

        $targetBricks = collect();
        $priceFilters = $request->price_filters ?? [];
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);
        $isCeramicWork = in_array('ceramic', $requiredMaterials, true);

        // Check if multi-ceramic is selected
        $hasCeramicFilters = $request->has('ceramic_types') || $request->has('ceramic_sizes');
        $isMultiCeramic =
            $isCeramicWork &&
            $hasCeramicFilters &&
            ((is_array($request->ceramic_types) && count($request->ceramic_types) > 0) ||
                (is_array($request->ceramic_sizes) && count($request->ceramic_sizes) > 0));

        \Log::info('Multi-Ceramic check', [
            'isCeramicWork' => $isCeramicWork,
            'hasCeramicFilters' => $hasCeramicFilters,
            'isMultiCeramic' => $isMultiCeramic,
            'requiredMaterials' => $requiredMaterials,
        ]);

        // Handle Multi-Ceramic Selection
        if ($isMultiCeramic) {
            \Log::info('Calling generateMultiCeramicCombinations');
            return $this->generateMultiCeramicCombinations($request);
        }

        if ($isBrickless) {
            $targetBricks = collect([$this->resolveFallbackBrick()]);
        } else {
            $targetBricks = collect();
            $hasBrickIds = $request->has('brick_ids') && !empty($request->brick_ids);
            $hasBrickId = $request->has('brick_id') && !empty($request->brick_id);

            if ($hasBrickIds) {
                $targetBricks = Brick::whereIn('id', $request->brick_ids)->get();
            } elseif ($hasBrickId) {
                $targetBricks = Brick::where('id', $request->brick_id)->get();
            } else {
                // 1. Filter Rekomendasi (Best)
                if (in_array('best', $priceFilters, true)) {
                    $recommendedBrickIds = RecommendedCombination::where('type', 'best')
                        ->where('work_type', $workType)
                        ->pluck('brick_id')
                        ->unique()
                        ->filter();

                    if ($recommendedBrickIds->isNotEmpty()) {
                        $recBricks = Brick::whereIn('id', $recommendedBrickIds)->get();
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
                        $commonBricks = Brick::whereIn('id', $commonBrickIds)->get();
                        $targetBricks = $targetBricks->merge($commonBricks);
                    }
                }

                // 3. Filter Premium (Expensive) - Butuh bata mahal
                if (in_array('expensive', $priceFilters, true)) {
                    $expensiveBricks = Brick::orderBy('price_per_piece', 'desc')->limit(5)->get();
                    $targetBricks = $targetBricks->merge($expensiveBricks);
                }

                // 4. Filter Lainnya (Cheapest, Medium, atau Default jika kosong)
                // Kita ambil bata Ekonomis sebagai base comparison
                $otherFilters = array_diff($priceFilters, ['best', 'common', 'expensive', 'custom']);
                $needsDefaultPool = !empty($otherFilters) || $targetBricks->isEmpty();

                if ($needsDefaultPool) {
                    $defaultBricks = Brick::orderBy('price_per_piece', 'asc')->limit(5)->get();
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
                        DB::raw('count(*) as frequency')
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
                    $commonBricks = Brick::whereIn('id', $commonBrickIds)->get();
                    $targetBricks = $targetBricks->merge($commonBricks);
                }
            }

            // Ensure unique bricks
            $targetBricks = $targetBricks->unique('id')->values();
        }

        $projects = [];
        foreach ($targetBricks as $brick) {
            $combinations = $this->calculateCombinationsForBrick($brick, $request);

            \Log::info('Project combinations for brick', [
                'brick_id' => $brick->id,
                'brick_brand' => $brick->brand,
                'combination_labels' => array_keys($combinations),
                'total_combinations' => count($combinations),
            ]);

            $projects[] = [
                'brick' => $brick,
                'combinations' => $combinations,
            ];
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
        ];

        $this->storeCalculationCachePayload($cacheKey, $payload);

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
            return redirect()->back()->with('error', 'Tidak ada keramik yang sesuai dengan filter yang dipilih.');
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

        \Log::info('Cache stored, redirecting to preview', ['cacheKey' => $cacheKey]);

        // Redirect to GET route untuk support pagination dan refresh
        return redirect()->route('material-calculations.preview', ['cacheKey' => $cacheKey]);
    }

    protected function buildCalculationCacheKey(Request $request): string
    {
        $payload = $request->except(['_token', 'confirm_save']);
        $normalized = $this->normalizeCalculationPayload($payload);
        return 'material_calc:' . hash('sha256', json_encode($normalized));
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

    protected function getCalculationCachePayload(string $cacheKey): ?array
    {
        if (app()->environment('local') && config('app.debug')) {
            return null;
        }
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

                $combinations = $this->calculateCombinationsForCeramicLite($brick, $request, $ceramic);
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

        // Pre-fetch related materials to avoid N+1 in loops
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('package_price')->get();
        $nats = Cement::where('type', 'Nat')->orderBy('package_price')->get();
        $sands = Sand::orderBy('package_price')->get();

        \Log::info('calculateCombinationsForCeramicGroup START', [
            'work_type' => $request->work_type,
            'price_filters' => $priceFilters,
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
                    $recNats = $rec->nat_id ? Cement::where('id', $rec->nat_id)->get() : $nats;
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
                    $recResults = $this->calculateCombinationsFromMaterials(
                        $brick,
                        $request,
                        $recCements,
                        $recSands,
                        [],
                        'Rekomendasi',
                        1,
                        $targetCeramics,
                        $recNats,
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
                        $filterLabel = $this->getFilterLabel($filter);
                        $allCombinations[] = array_merge($combo, [
                            'filter_label' => "{$filterLabel} {$number}",
                            'filter_type' => $filter,
                            'filter_number' => $number,
                        ]);
                    }
                    continue; // Skip default generation for 'best'
                }

                // If no recommendations found, fall through to default generation (cheapest)?
                // Or show nothing? Previously it fell through.
                // Let's allow fall through if no recommendation exists,
                // or we can strictly return nothing if that's preferred.
                // For now, allowing fall through to show *something* is safer,
                // but usually 'Best' implies specific choice.
                // If the user strictly wants recommendation, we should probably not continue.
                // But to avoid empty column, let's keep falling through or just break?
                // The prompt implies we want to USE recommendation. If none, maybe Cheapest is acceptable fallback.
            }

            // For 'common' filter, use historical frequency data
            if ($filter === 'common') {
                $commonCombos = $this->getCommonCombinations($brick, $request);
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
                    $filterLabel = $this->getFilterLabel($filter);
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
                            $customNats = Cement::where('id', $request->nat_id)->get();
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

                $customCombos = $this->calculateCombinationsFromMaterials(
                    $brick,
                    $request,
                    $customCements,
                    $customSands,
                    [],
                    'Custom',
                    1,
                    $customCeramics,
                    $customNats,
                );

                foreach ($customCombos as $index => $combo) {
                    $number = $index + 1;
                    $filterLabel = $this->getFilterLabel($filter);

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
            $groupGenerator = $this->yieldGroupCombinations(
                $brick,
                $request,
                $ceramics,
                $cements,
                $sands,
                $nats,
                $this->getFilterLabel($filter),
            );

            // 2. Collect ALL results (we need to sort globally)
            // Warning: memory usage. We use a larger limit but aggressive pruning in processGeneratorResults
            // But processGeneratorResults sorts by cost ASC.
            // We need custom sorting based on filter.

            // Let's implement specific logic per filter type
            $candidates = [];
            foreach ($groupGenerator as $combo) {
                $candidates[] = $combo;
                // Keep memory check - keep top 50 per iteration?
                // No, just collect decent amount then sort.
                if (count($candidates) > 200) {
                    // Intermediate sort and prune to keep memory low
                    $this->sortCandidates($candidates, $filter);
                    $candidates = array_slice($candidates, 0, 50);
                }
            }

            // Final Sort
            $this->sortCandidates($candidates, $filter);

            $limit = $filter === 'best' ? 1 : null;
            $topCandidates = $limit ? array_slice($candidates, 0, $limit) : $candidates;

            // Add to results
            foreach ($topCandidates as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->getFilterLabel($filter);

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        // Convert back to label-keyed array for display
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

    /**
     * FULL CALCULATION: Calculate all combinations for a specific ceramic
     * No limits - full calculation with all materials and filters
     */
    protected function calculateCombinationsForCeramicLite($brick, $request, $ceramic)
    {
        $workType = $request->work_type ?? 'tile_installation';

        // FULL CALCULATION: Use all requested price filters
        $priceFilters = $request->price_filters ?? ['best'];

        // Use the same logic as calculateCombinationsForBrick but with fixed ceramic
        $allCombinations = [];

        foreach ($priceFilters as $filter) {
            // Get combinations using the standard filter methods (no limits)
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter, $ceramic);
            if ($filter === 'best' || $filter === 'custom') {
                $combinations = array_slice($combinations, 0, 1);
            }

            foreach ($combinations as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->getFilterLabel($filter);

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        // Convert back to label-keyed array for display
        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $label = $combo['filter_label'];
            $finalResults[$label] = [$combo];
        }

        return $finalResults;
    }

    /**
     * Calculate combinations for a specific ceramic
     */
    protected function calculateCombinationsForCeramic($brick, $request, $ceramic)
    {
        $priceFilters = $request->price_filters ?? ['best'];

        // Use the same logic as calculateCombinationsForBrick but with fixed ceramic
        $allCombinations = [];

        foreach ($priceFilters as $filter) {
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter);
            if ($filter === 'best' || $filter === 'custom') {
                $combinations = array_slice($combinations, 0, 1);
            }

            foreach ($combinations as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->getFilterLabel($filter);

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        // Convert back to label-keyed array for display
        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $label = $combo['filter_label'];
            $finalResults[$label] = [$combo];
        }

        return $finalResults;
    }

    protected function calculateCombinationsForBrick($brick, $request)
    {
        $requestedFilters = $request->price_filters ?? ['best'];

        if (count($requestedFilters) === 1 && $requestedFilters[0] === 'best') {
            $bestCombinations = $this->getBestCombinations($brick, $request);
            $finalResults = [];
            foreach ($bestCombinations as $index => $combo) {
                $label = 'Rekomendasi ' . ($index + 1);
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
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter);

            \Log::info("Filter '{$filter}' returned combinations", [
                'filter' => $filter,
                'brick_id' => $brick->id,
                'count' => count($combinations),
            ]);

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

        \Log::info('All combinations before deduplication', [
            'brick_id' => $brick->id,
            'total_count' => count($allCombinations),
            'filter_types' => array_count_values(array_column($allCombinations, 'filter_type')),
        ]);

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        $priorityLabels = [];
        $userOriginalFilters = $request->price_filters ?? [];
        foreach ($userOriginalFilters as $rf) {
            if ($rf !== 'all') {
                $priorityLabels[] = $rf === 'custom' ? 'Custom' : $this->getFilterLabel($rf);
            }
        }

        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $sources = $combo['source_filters'] ?? [$combo['filter_type']];
            $intersect = array_intersect($sources, $requestedFilters);

            \Log::info('Checking combo for final results', [
                'filter_label' => $combo['filter_label'] ?? 'unknown',
                'sources' => $sources,
                'requested_filters' => $requestedFilters,
                'intersect' => $intersect,
                'has_match' => count($intersect) > 0,
            ]);

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

        \Log::info('Final results after filtering', [
            'brick_id' => $brick->id,
            'requested_filters' => $requestedFilters,
            'unique_combos_count' => count($uniqueCombos),
            'final_results_count' => count($finalResults),
            'final_labels' => array_keys($finalResults),
        ]);

        return $finalResults;
    }

    protected function getCombinationsByFilter($brick, $request, $filter, $fixedCeramic = null)
    {
        // Remove the brick selection check - if brick is passed to this method, it means it should be calculated
        // The brick selection logic is already handled in generateCombinations() where bricks are chosen
        // based on filters (best, expensive, etc.) or user selection

        switch ($filter) {
            case 'best':
                return $this->getBestCombinations($brick, $request, $fixedCeramic);
            case 'common':
                return $this->getCommonCombinations($brick, $request, $fixedCeramic);
            case 'cheapest':
                return $this->getCheapestCombinations($brick, $request, $fixedCeramic);
            case 'medium':
                return $this->getMediumCombinations($brick, $request, $fixedCeramic);
            case 'expensive':
                return $this->getExpensiveCombinations($brick, $request, $fixedCeramic);
            case 'custom':
                return $this->getCustomCombinations($brick, $request);
            default:
                return [];
        }
    }

    protected function getBestCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);

        $recommendationQuery = RecommendedCombination::where('work_type', $workType)->where('type', 'best');

        if (in_array('brick', $requiredMaterials, true)) {
            $recommendationQuery->where(function ($query) use ($brick) {
                $query->where('brick_id', $brick->id)->orWhereNull('brick_id');
            });
        }

        $recommendations = $recommendationQuery->get();

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
                    $cements = Cement::where('id', $rec->cement_id)->get();
                }
            }

            if (in_array('sand', $requiredMaterials, true)) {
                if (empty($rec->sand_id)) {
                    $missingRequired = true;
                } else {
                    $sands = Sand::where('id', $rec->sand_id)->get();
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
                'Rekomendasi',
                1,
                $ceramics,
                $nats,
            );
            foreach ($results as &$res) {
                $res['source_filter'] = 'best';
                $allRecommendedResults[] = $res;
            }
        }

        if (!empty($allRecommendedResults)) {
            return $allRecommendedResults;
        }

        // No recommendations found - return empty array
        // Don't fallback to cheapest/medium, as this would show Rekomendasi filter without actual recommendations
        return [];
    }

    protected function getCommonCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $isBrickless = !in_array('brick', $requiredMaterials, true);
        $isCeramicWork = in_array('ceramic', $requiredMaterials, true);

        \Log::info('getCommonCombinations called', [
            'work_type' => $workType,
            'brick_id' => $brick->id ?? null,
            'is_brickless' => $isBrickless,
            'is_ceramic_work' => $isCeramicWork,
        ]);

        if ($isCeramicWork) {
            // For ceramic work types, get most frequently used combinations from history
            $query = DB::table('brick_calculations')
                ->select('ceramic_id', 'nat_id', 'cement_id', 'sand_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('ceramic_id')
                ->whereNotNull('nat_id');

            // Only filter by cement/sand if required by the work type
            if (in_array('cement', $requiredMaterials, true)) {
                $query->whereNotNull('cement_id');
            }
            if (in_array('sand', $requiredMaterials, true)) {
                $query->whereNotNull('sand_id');
            }

            // If fixedCeramic is provided (single ceramic mode), filter by that ceramic
            if ($fixedCeramic) {
                $query->where('ceramic_id', $fixedCeramic->id);
            }

            $frequencyCounts = $query
                ->groupBy('ceramic_id', 'nat_id', 'cement_id', 'sand_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            \Log::info('Ceramic common combinations query result', [
                'work_type' => $workType,
                'fixed_ceramic_id' => $fixedCeramic->id ?? null,
                'found_count' => $frequencyCounts->count(),
            ]);

            if ($frequencyCounts->isEmpty()) {
                \Log::warning('No common combinations found for ceramic work type', [
                    'work_type' => $workType,
                    'fixed_ceramic' => $fixedCeramic ? $fixedCeramic->id : 'none',
                ]);
                return [];
            }

            $results = [];
            $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
            $paramsBase['brick_id'] = $brick->id;

            foreach ($frequencyCounts as $combo) {
                $ceramic = Ceramic::find($combo->ceramic_id);
                $nat = Cement::find($combo->nat_id);
                $cement = $combo->cement_id ? Cement::find($combo->cement_id) : null;
                $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

                if (!$ceramic || !$nat) {
                    continue;
                }

                // If cement is required but missing/invalid
                if (in_array('cement', $requiredMaterials) && !$cement) {
                    continue;
                }
                if ($combo->cement_id && !$cement) {
                    continue;
                }

                // If sand is required but missing/invalid
                if (in_array('sand', $requiredMaterials) && !$sand) {
                    continue;
                }
                if ($combo->sand_id && !$sand) {
                    continue;
                }

                // Calculate with the exact materials from history
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

            \Log::info('Ceramic common combinations result', [
                'work_type' => $workType,
                'found_count' => count($results),
            ]);

            return $results;
        }

        // For brick-based work types, check historical combinations
        // Filter by both brick_id AND work_type to ensure correct data per work item
        // Handle specialized work types (e.g. Painting)
        if (in_array('cat', $requiredMaterials)) {
            $commonCombos = DB::table('brick_calculations')
                ->select('cat_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('cat_id')
                ->groupBy('cat_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            \Log::info('Cat common combinations query result', [
                'work_type' => $workType,
                'found_count' => $commonCombos->count(),
            ]);

            if ($commonCombos->isEmpty()) {
                \Log::warning('No common combinations found for cat work type', [
                    'work_type' => $workType,
                ]);
                return [];
            }

            $results = [];
            $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
            $paramsBase['brick_id'] = $brick->id;

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
            // For other brickless work types (non-ceramic), check historical combinations
            // Use JSON extraction to filter by work_type
            $commonCombos = DB::table('brick_calculations')
                ->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType])
                ->whereNotNull('cement_id')
                ->groupBy('cement_id', 'sand_id')
                ->orderByDesc('frequency')
                ->limit(3)
                ->get();

            \Log::info('Brickless common combinations query result', [
                'work_type' => $workType,
                'found_count' => $commonCombos->count(),
            ]);

            if ($commonCombos->isEmpty()) {
                \Log::warning('No common combinations found for brickless work type', [
                    'work_type' => $workType,
                ]);
                return [];
            }

            $results = [];
            $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
            $paramsBase['brick_id'] = $brick->id;

            foreach ($commonCombos as $combo) {
                $cement = Cement::find($combo->cement_id);
                $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

                if (!$cement) {
                    continue;
                }

                // If sand is required by formula but missing in DB/result
                if (in_array('sand', $requiredMaterials) && !$sand) {
                    continue;
                }

                // If sand ID exists in record but model not found (deleted)
                if ($combo->sand_id && !$sand) {
                    continue;
                }

                // Calculate directly without going through processGeneratorResults
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

        \Log::info('Common combinations query result (brick-based)', [
            'work_type' => $workType,
            'brick_id' => $brick->id,
            'found_count' => $commonCombos->count(),
            'combinations' => $commonCombos->toArray(),
        ]);

        if ($commonCombos->isEmpty()) {
            // No historical data for this brick/work_type - return empty
            \Log::info('No common combos for specific brick', [
                'work_type' => $workType,
                'brick_id' => $brick->id,
            ]);
            return [];
        }

        $results = [];
        $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
        $paramsBase['brick_id'] = $brick->id;

        foreach ($commonCombos as $combo) {
            $cement = Cement::find($combo->cement_id);
            $sand = $combo->sand_id ? Sand::find($combo->sand_id) : null;

            if (!$cement) {
                continue;
            }

            // If sand is required by formula but missing in DB/result
            if (in_array('sand', $requiredMaterials) && !$sand) {
                continue;
            }

            // If sand ID exists in record but model not found (deleted)
            if ($combo->sand_id && !$sand) {
                continue;
            }

            // Calculate directly with exact materials from history
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

    protected function getCheapestCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';

        // Limit dihapus agar menampilkan semua kombinasi

        $cementQuery = Cement::where(function ($q) {
            $q->where('type', '!=', 'Nat')->orWhereNull('type');
        })->where('package_price', '>', 0);
        if ($workType !== 'tile_installation') {
            $cementQuery->where('package_weight_net', '>', 0);
        }
        $cements = $cementQuery->orderBy('package_price')->get();

        $nats = Cement::where('type', 'Nat')->where('package_price', '>', 0)->orderBy('package_price')->get();

        $sands = Sand::where('package_price', '>', 0)->orderBy('package_price')->get();

        $cats = Cat::where('purchase_price', '>', 0)->orderBy('purchase_price')->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'asc',
            null, // Unlimited
        );

        // Limit passed as null to return ALL combinations
        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            'Ekonomis',
            null,
            $ceramics,
            $nats,
        );
    }

    protected function getMediumCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';

        $cementQuery = Cement::where(function ($q) {
            $q->where('type', '!=', 'Nat')->orWhereNull('type');
        })->where('package_price', '>', 0);
        if ($workType !== 'tile_installation') {
            $cementQuery->where('package_weight_net', '>', 0);
        }
        $cements = $cementQuery->orderBy('package_price')->get();

        $nats = Cement::where('type', 'Nat')->where('package_price', '>', 0)->orderBy('package_price')->get();

        $sands = Sand::where('package_price', '>', 0)->orderBy('package_price')->get();

        $cats = Cat::where('purchase_price', '>', 0)->orderBy('purchase_price')->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'asc',
            null, // Unlimited
        );

        // Get ALL results first (sorted by price ASC via calculateCombinationsFromMaterials)
        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            'Moderat',
            null,
            $ceramics,
            $nats,
        );

        // Return full ascending list; median selection handled in view for display/rekap
        return $allResults;
    }

    protected function getExpensiveCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';

        $cementQuery = Cement::where(function ($q) {
            $q->where('type', '!=', 'Nat')->orWhereNull('type');
        })->where('package_price', '>', 0);
        if ($workType !== 'tile_installation') {
            $cementQuery->where('package_weight_net', '>', 0);
        }
        $cements = $cementQuery->orderByDesc('package_price')->get();

        $nats = Cement::where('type', 'Nat')->where('package_price', '>', 0)->orderByDesc('package_price')->get();

        $sands = Sand::where('package_price', '>', 0)->orderByDesc('package_price')->get();

        $cats = Cat::where('purchase_price', '>', 0)->orderByDesc('purchase_price')->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'desc',
            null, // Unlimited
        );

        $allResults = $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            'Premium',
            null,
            $ceramics,
            $nats,
        );

        return array_reverse($allResults);
    }

    protected function getCustomCombinations($brick, $request)
    {
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);
        $missingRequired = false;

        $cements = collect();
        $sands = collect();
        $cats = collect();
        $ceramics = collect();
        $nats = collect();

        foreach ($requiredMaterials as $material) {
            if ($material === 'brick') {
                continue;
            }

            $key = $material . '_id';
            if (empty($request->$key)) {
                $missingRequired = true;
                break;
            }

            switch ($material) {
                case 'cement':
                    $cements = Cement::where('id', $request->cement_id)->get();
                    break;
                case 'sand':
                    $sands = Sand::where('id', $request->sand_id)->get();
                    break;
                case 'cat':
                    $cats = Cat::where('id', $request->cat_id)->get();
                    break;
                case 'ceramic':
                    $ceramics = Ceramic::where('id', $request->ceramic_id)->get();
                    break;
                case 'nat':
                    $nats = Cement::where('id', $request->nat_id)->get();
                    break;
            }
        }

        if ($missingRequired) {
            return $this->getCheapestCombinations($brick, $request);
        }

        return $this->calculateCombinationsFromMaterials(
            $brick,
            $request,
            $cements,
            $sands,
            $cats,
            'Custom',
            1,
            $ceramics,
            $nats,
        );
    }

    protected function calculateCombinationsFromMaterials(
        $brick,
        $request,
        $cements,
        $sands,
        $cats = [],
        $groupLabel = 'Kombinasi',
        $limit = null,
        $ceramics = [],
        $nats = [],
    ) {
        $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
        $paramsBase['brick_id'] = $brick->id;
        $workType = $request->work_type ?? 'brick_half';
        $requiredMaterials = $this->resolveRequiredMaterials($workType);

        // Gunakan generator untuk tile_installation untuk efisiensi memory
        if ($workType === 'tile_installation') {
            return $this->processGeneratorResults(
                $this->yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel),
                $limit,
            );
        }

        // Untuk workType lain, gunakan cara biasa (sudah cukup efisien)
        $results = [];

        if (in_array('cat', $requiredMaterials, true)) {
            foreach ($cats as $cat) {
                if ($cat->purchase_price <= 0) {
                    continue;
                }
                $params = array_merge($paramsBase, ['cat_id' => $cat->id]);
                try {
                    $formula = FormulaRegistry::instance('painting');
                    $trace = $formula->trace($params);
                    $result = $trace['final_result'] ?? $formula->calculate($params);
                    $results[] = [
                        'cat' => $cat,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                        'filter_type' => $groupLabel,
                    ];
                } catch (\Exception $e) {
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
                    $params = array_merge($paramsBase, ['ceramic_id' => $ceramic->id, 'nat_id' => $nat->id]);
                    try {
                        $formula = FormulaRegistry::instance($workType);
                        $trace = $formula->trace($params);
                        $result = $trace['final_result'] ?? $formula->calculate($params);
                        $results[] = [
                            'ceramic' => $ceramic,
                            'nat' => $nat,
                            'result' => $result,
                            'total_cost' => $result['grand_total'],
                            'filter_type' => $groupLabel,
                        ];
                    } catch (\Exception $e) {
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
                    $trace = $formula->trace($params);
                    $result = $trace['final_result'] ?? $formula->calculate($params);
                    $results[] = [
                        'cement' => $cement,
                        'result' => $result,
                        'total_cost' => $result['grand_total'],
                        'filter_type' => $groupLabel,
                    ];
                } catch (\Exception $e) {
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
                    $params = array_merge($paramsBase, ['cement_id' => $cement->id, 'sand_id' => $sand->id]);
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
                            'filter_type' => $groupLabel,
                        ];
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        usort($results, function ($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }
        return $results;
    }

    protected function isBrickSelectedForRequest($brick, $request): bool
    {
        if ($request->has('brick_ids') && is_array($request->brick_ids) && !empty($request->brick_ids)) {
            return in_array($brick->id, $request->brick_ids);
        }

        if ($request->filled('brick_id')) {
            return (int) $brick->id === (int) $request->brick_id;
        }

        return true;
    }

    /**
     * Generator function untuk tile installation - streaming results tanpa menyimpan semua di memory
     */
    protected function yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel)
    {
        foreach ($ceramics as $ceramic) {
            foreach ($nats as $nat) {
                foreach ($cements as $cement) {
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
                            $trace = $formula->trace($params);
                            $result = $trace['final_result'] ?? $formula->calculate($params);

                            // Yield result satu per satu, tidak menyimpan di memory
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
                            // Log error untuk debugging
                            \Log::warning('yieldTileInstallationCombinations error', [
                                'ceramic_id' => $ceramic->id,
                                'nat_id' => $nat->id,
                                'cement_id' => $cement->id,
                                'sand_id' => $sand->id,
                                'error' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                            ]);
                            // Skip kombinasi yang error
                            continue;
                        }
                    }
                }
            }
        }
    }

    /**
     * Process generator results dengan smart batching
     * Hanya simpan kombinasi Rekomendasi di memory, buang yang lebih mahal
     */
    protected function processGeneratorResults($generator, $limit = null)
    {
        $results = [];
        $batchSize = 100; // Process setiap 100 kombinasi
        // If limit is null, we keep ALL results (no pruning)
        // If limit is set, we keep 3x limit for sorting buffer
        $keepSize = $limit ? max($limit * 3, 30) : null;

        foreach ($generator as $combination) {
            $results[] = $combination;

            // Setiap batch, sort dan ambil yang Rekomendasi saja (HANYA JIKA ADA LIMIT)
            if ($limit && count($results) >= $batchSize) {
                usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
                $results = array_slice($results, 0, $keepSize);
            }
        }

        // Final sort
        usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);

        // Return sesuai limit (jika ada)
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    protected function getFilterLabel($filter)
    {
        return match ($filter) {
            'best' => 'Rekomendasi',
            'common' => 'Populer',
            'cheapest' => 'Ekonomis',
            'medium' => 'Moderat',
            'expensive' => 'Premium',
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

    protected function detectAndMergeDuplicates($combinations)
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

            if (!isset($combo['source_filters'])) {
                $combo['source_filters'] = [$combo['filter_type']];
            }
            $currentLabel = $combo['filter_label'];

            if (isset($duplicateMap[$key])) {
                $existingIndex = $duplicateMap[$key];
                $uniqueCombos[$existingIndex]['all_labels'][] = $currentLabel;
                $uniqueCombos[$existingIndex]['filter_label'] .= ' = ' . $currentLabel;
                if (!in_array($combo['filter_type'], $uniqueCombos[$existingIndex]['source_filters'])) {
                    $uniqueCombos[$existingIndex]['source_filters'][] = $combo['filter_type'];
                }
            } else {
                $duplicateMap[$key] = count($uniqueCombos);
                $combo['all_labels'] = [$currentLabel];
                $uniqueCombos[] = $combo;
            }
        }
        return $uniqueCombos;
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
        $request->validate([
            'work_type' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'project_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'layer_count' => 'nullable|integer|min:1',
            'plaster_sides' => 'nullable|integer|min:1',
            'skim_sides' => 'nullable|integer|min:1',
        ]);

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

        $request->validate([
            'work_type' => 'nullable|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
        ]);

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
        $request->validate([
            'work_type' => 'nullable|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
        ]);

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
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('cement_name')->get();
        $nats = Cement::where('type', 'Nat')->orderBy('brand')->get();
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
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'cat_id' => 'nullable|exists:cats,id',
            'ceramic_id' => 'nullable|exists:ceramics,id',
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
