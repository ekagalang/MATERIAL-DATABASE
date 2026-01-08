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
        $ceramicTypes = Ceramic::whereNotNull('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->sort()
            ->values();

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
        $defaultMortarFormula = MortarFormula::getDefault();

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
        try {
            DB::beginTransaction();

            // Handle work_type_select from form and convert to work_type
            if ($request->has('work_type_select') && !$request->has('work_type')) {
                $request->merge(['work_type' => $request->work_type_select]);
            }

            if (!$request->has('mortar_formula_type')) {
                $request->merge(['mortar_formula_type' => 'default']);
            }

            // 1. VALIDASI
            $rules = [
                'work_type' => 'required',
                'price_filters' => 'nullable|array',
                'price_filters.*' => 'in:all,best,common,cheapest,medium,expensive,custom',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1',
                'skim_sides' => 'nullable|integer|min:1',
            ];

            // Default to 'best' if no filters selected
            if (!$request->has('price_filters') || empty($request->price_filters)) {
                $request->merge(['price_filters' => ['best']]);
            }

            // NEW LOGIC: Dynamic Material Validation based on Work Type
            $workType = $request->work_type;
            $needsBrick = !in_array($workType, ['wall_plastering', 'skim_coating', 'painting', 'tile_installation', 'grout_tile']);
            $needsSand = !in_array($workType, ['skim_coating', 'painting', 'grout_tile']);
            $needsCement = !in_array($workType, ['painting', 'grout_tile']);
            $needsCat = in_array($workType, ['painting']);
            $needsCeramic = in_array($workType, ['tile_installation', 'grout_tile']);
            $needsNat = in_array($workType, ['tile_installation', 'grout_tile']);

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
            $isCustomEmpty = $hasCustom && (empty($request->cement_id) || empty($request->sand_id)) && !$needsCat;
            
            if ($needsCat && $hasCustom && empty($request->cat_id)) {
                $isCustomEmpty = true;
            }

            $needCombinations = $hasOtherFilters || $isMultiBrick || $isCustomEmpty;

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

    protected function generateCombinations(Request $request)
    {
        $targetBricks = collect();
        $priceFilters = $request->price_filters ?? [];
        $workType = $request->work_type ?? 'brick_half';
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating', 'painting', 'tile_installation', 'grout_tile']);
        $isCeramicWork = in_array($workType, ['tile_installation', 'grout_tile']);

        // Check if multi-ceramic is selected
        $hasCeramicFilters = $request->has('ceramic_types') || $request->has('ceramic_sizes');
        $isMultiCeramic = $isCeramicWork && $hasCeramicFilters &&
            ((is_array($request->ceramic_types) && count($request->ceramic_types) > 0) ||
             (is_array($request->ceramic_sizes) && count($request->ceramic_sizes) > 0));

        // Handle Multi-Ceramic Selection
        if ($isMultiCeramic) {
            return $this->generateMultiCeramicCombinations($request);
        }

        if ($isBrickless) {
            $targetBricks = Brick::limit(1)->get();
        } else {
            $hasBrickIds = $request->has('brick_ids') && !empty($request->brick_ids);
            $hasBrickId = $request->has('brick_id') && !empty($request->brick_id);

            if (!$hasBrickIds && !$hasBrickId) {
                if (in_array('best', $priceFilters)) {
                    $recommendedBrickIds = RecommendedCombination::where('type', 'best')
                        ->where('work_type', $workType)
                        ->pluck('brick_id')
                        ->unique();
                    if ($recommendedBrickIds->isNotEmpty()) {
                        $targetBricks = Brick::whereIn('id', $recommendedBrickIds)->get();
                    }
                }
                if ($targetBricks->isEmpty()) {
                    $targetBricks = Brick::orderBy('price_per_piece', 'asc')->limit(5)->get();
                }
            } else {
                if ($hasBrickIds) {
                    $targetBricks = Brick::whereIn('id', $request->brick_ids)->get();
                } elseif ($hasBrickId) {
                    $targetBricks = Brick::where('id', $request->brick_id)->get();
                }
            }
        }

        $projects = [];
        foreach ($targetBricks as $brick) {
            $projects[] = [
                'brick' => $brick,
                'combinations' => $this->calculateCombinationsForBrick($brick, $request),
            ];
        }

        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';

        return view('material_calculations.preview_combinations', [
            'projects' => $projects,
            'requestData' => $request->except(['brick_ids', 'brick_id']),
            'formulaName' => $formulaName,
        ]);
    }

    /**
     * Generate combinations for multiple ceramics
     * Group by Type -> Size and display in tabs
     * OPTIMIZED: Limits ceramics and combinations to prevent memory exhaustion
     */
    protected function generateMultiCeramicCombinations(Request $request)
    {
        $workType = $request->work_type ?? 'tile_installation';

        // Build ceramic query based on filters
        $ceramicQuery = Ceramic::query();

        // For grout_tile, filter ceramics with valid dimension_thickness
        if ($workType === 'grout_tile') {
            $ceramicQuery->whereNotNull('dimension_thickness')
                ->where('dimension_thickness', '>', 0);
        }

        // Apply ceramic type and size filters
        $ceramicQuery = $this->applyCeramicFilters($ceramicQuery, $request);

        // Get ALL matching ceramics - no limit needed with lazy loading
        // Combinations are calculated on-demand via AJAX per ceramic
        $targetCeramics = $ceramicQuery->orderBy('type')
            ->orderBy('price_per_package')
            ->orderBy('dimension_length')
            ->orderBy('dimension_width')
            ->get();

        if ($targetCeramics->isEmpty()) {
            return redirect()
                ->back()
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

        return view('material_calculations.preview_combinations', [
            'projects' => [], // Empty for ceramic-only work
            'ceramicProjects' => $ceramicProjects,
            'groupedCeramics' => $groupedByType,
            'isMultiCeramic' => true,
            'isLazyLoad' => true, // Enable lazy loading
            'requestData' => $request->except(['ceramic_types', 'ceramic_sizes']),
            'formulaName' => $formulaName,
            'totalCeramics' => $targetCeramics->count(),
        ]);
    }

    /**
     * AJAX Endpoint: Get combinations for a specific ceramic OR a group of ceramics (Type + Size)
     * Called when user clicks a ceramic tab
     */
    public function getCeramicCombinations(Request $request)
    {
        try {
            $brick = Brick::first(); // Dummy brick for ceramic work

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
                    'requestData' => $request->except(['ceramic_id', '_token']),
                    'isGroupMode' => $isGroupMode,
                ])->render()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine()
            ], 500);
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

        // Pre-fetch related materials to avoid N+1 in loops
        $materialLimit = 10;
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('package_price')->limit($materialLimit)->get();
        $nats = Cement::where('type', 'Nat')->orderBy('package_price')->limit($materialLimit)->get();
        $sands = Sand::orderBy('package_price')->limit($materialLimit)->get();

        foreach ($priceFilters as $filter) {
            if ($filter === 'custom') continue;

            $groupCombos = [];

            // 1. Generate combos for ALL ceramics in this group
            // We use a generator that yields results from all ceramics sequentially
            $groupGenerator = $this->yieldGroupCombinations($brick, $request, $ceramics, $cements, $sands, $nats, $this->getFilterLabel($filter));

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
            
            // Take Top 3 (or unique top 3)
            $topCandidates = array_slice($candidates, 0, 3);

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
        $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id', 'ceramic_id', 'ceramic_ids']);
        $paramsBase['brick_id'] = $brick->id;

        foreach ($ceramics as $ceramic) {
            foreach ($this->yieldTileInstallationCombinations($paramsBase, [$ceramic], $nats, $cements, $sands, $label) as $combo) {
                yield $combo;
            }
        }
    }

    /**
     * Helper to sort combination candidates based on filter type
     */
    protected function sortCandidates(&$candidates, $filter)
    {
        usort($candidates, function ($a, $b) use ($filter) {
            if ($filter === 'expensive') {
                return $b['total_cost'] <=> $a['total_cost']; // Descending
            } elseif ($filter === 'best') {
                 // For Best, prioritize recommendation, then price
                 // Since we don't have recommendation score in yield, fallback to price ASC
                 return $a['total_cost'] <=> $b['total_cost'];
            } elseif ($filter === 'medium') {
                // Hard to do true medium without full set. 
                // Fallback to ASC (Cheapest) or maybe randomize?
                // Let's stick to ASC for consistency or maybe reverse slightly?
                // Standard medium logic requires full dataset.
                return $a['total_cost'] <=> $b['total_cost'];
            } else {
                return $a['total_cost'] <=> $b['total_cost']; // Ascending (Cheapest, Common)
            }
        });
        
        // Special logic for MEDIUM: Pick middle if we have enough
        if ($filter === 'medium' && count($candidates) > 10) {
            $middle = floor(count($candidates) / 2);
            $slice = array_slice($candidates, $middle - 1, count($candidates)); // Take second half
            // $candidates = $slice; // Actually this is risky with partial data. Sticking to ASC is safer for now.
        }
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
            if ($filter === 'custom') continue;

            // Get combinations using the standard filter methods (no limits)
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter, $ceramic);

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
            if ($filter === 'custom') continue;

            $combinations = $this->getCombinationsByFilter($brick, $request, $filter);

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

        $filtersToCalculate = ['best', 'common', 'cheapest', 'medium', 'expensive'];
        if (in_array('custom', $requestedFilters)) {
            $filtersToCalculate[] = 'custom';
        }

        $allCombinations = [];
        foreach ($filtersToCalculate as $filter) {
            $combinations = $this->getCombinationsByFilter($brick, $request, $filter);
            foreach ($combinations as $index => $combo) {
                $number = $index + 1;
                $filterLabel = $this->getFilterLabel($filter);
                if ($filter === 'custom') $filterLabel = 'Custom';

                $allCombinations[] = array_merge($combo, [
                    'filter_label' => "{$filterLabel} {$number}",
                    'filter_type' => $filter,
                    'filter_number' => $number,
                ]);
            }
        }

        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        $priorityLabels = [];
        $userOriginalFilters = $request->price_filters ?? [];
        foreach ($userOriginalFilters as $rf) {
            if ($rf !== 'all') { $priorityLabels[] = $rf === 'custom' ? 'Custom' : $this->getFilterLabel($rf); }
        }

        $finalResults = [];
        foreach ($uniqueCombos as $combo) {
            $sources = $combo['source_filters'] ?? [$combo['filter_type']];
            if (count(array_intersect($sources, $requestedFilters)) > 0) {
                $labels = $combo['all_labels'] ?? [$combo['filter_label']];
                if (!empty($priorityLabels)) {
                    usort($labels, function ($a, $b) use ($priorityLabels) {
                        $aScore = 0; foreach ($priorityLabels as $pl) { if (str_starts_with($a, $pl)) { $aScore = 1; break; } }
                        $bScore = 0; foreach ($priorityLabels as $pl) { if (str_starts_with($b, $pl)) { $bScore = 1; break; } }
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

    protected function getCombinationsByFilter($brick, $request, $filter, $fixedCeramic = null)
    {
        switch ($filter) {
            case 'best': return $this->getBestCombinations($brick, $request, $fixedCeramic);
            case 'common': return $this->getCommonCombinations($brick, $request, $fixedCeramic);
            case 'cheapest': return $this->getCheapestCombinations($brick, $request, $fixedCeramic);
            case 'medium': return $this->getMediumCombinations($brick, $request, $fixedCeramic);
            case 'expensive': return $this->getExpensiveCombinations($brick, $request, $fixedCeramic);
            case 'custom': return $this->getCustomCombinations($brick, $request);
            default: return [];
        }
    }

    protected function getBestCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating', 'painting', 'tile_installation', 'grout_tile']);

        if ($workType === 'painting') {
             $recommendations = collect([]);
        } else {
            $recommendations = RecommendedCombination::where('work_type', $workType)->where('type', 'best')->where('brick_id', $brick->id)->get();
        }

        $allRecommendedResults = [];
        foreach ($recommendations as $rec) {
            $cements = Cement::where('id', $rec->cement_id)->get();
            $sands = Sand::where('id', $rec->sand_id)->get();
            $results = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, [], 'TerBAIK', 1);
            foreach ($results as &$res) {
                $res['source_filter'] = 'best';
                $allRecommendedResults[] = $res;
            }
        }

        if (!empty($allRecommendedResults)) return $allRecommendedResults;

        if ($isBrickless) {
             // For grout_tile, return MEDIUM priced combinations to differentiate from cheapest
             if ($workType === 'grout_tile') {
                 $medium = $this->getMediumCombinations($brick, $request, $fixedCeramic);
                 return array_map(function ($combo) { $combo['source_filter'] = 'best'; return $combo; }, array_slice($medium, 0, 3));
             }
             $cheapest = $this->getCheapestCombinations($brick, $request, $fixedCeramic);
             return array_map(function ($combo) { $combo['source_filter'] = 'best'; return $combo; }, array_slice($cheapest, 0, 3));
        }
        return [];
    }

    protected function getCommonCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        if (in_array($workType, ['wall_plastering', 'skim_coating', 'painting', 'tile_installation', 'grout_tile'])) {
             // For grout_tile, get combinations from mid-low price range to differentiate from cheapest
             if ($workType === 'grout_tile') {
                 $materialLimit = 10;
                 // Skip first 3 cheapest, take next 5 for variety - filter by valid dimension_thickness
                 $ceramics = $this->resolveCeramicsForCalculation(
                     $request,
                     $workType,
                     $fixedCeramic,
                     'price_per_package',
                     'asc',
                     $materialLimit,
                     3
                 );
                 $nats = Cement::where('type', 'Nat')->orderBy('package_price')->skip(2)->limit($materialLimit)->get();

                 // Fallback to cheapest if not enough data
                 if ($ceramics->isEmpty() && !$fixedCeramic && !$request->filled('ceramic_id')) {
                     $ceramics = $this->resolveCeramicsForCalculation(
                         $request,
                         $workType,
                         null,
                         'price_per_package',
                         'asc',
                         $materialLimit
                     );
                 }
                 if ($nats->isEmpty()) {
                     $nats = Cement::where('type', 'Nat')->orderBy('package_price')->limit($materialLimit)->get();
                 }

                 $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->limit(1)->get();
                 $sands = Sand::limit(1)->get();
                 $cats = collect([]);

                 $results = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerUMUM', 3, $ceramics, $nats);
                 return array_map(function ($combo) { $combo['source_filter'] = 'common'; return $combo; }, $results);
             }

             $cheapest = $this->getCheapestCombinations($brick, $request, $fixedCeramic);
             return array_map(function ($combo) { $combo['source_filter'] = 'common'; return $combo; }, array_slice($cheapest, 0, 3));
        }

        $commonCombos = DB::table('brick_calculations')->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))->where('brick_id', $brick->id)->groupBy('cement_id', 'sand_id')->orderByDesc('frequency')->limit(3)->get();
        if ($commonCombos->isEmpty()) {
            $cheapest = $this->getCheapestCombinations($brick, $request, $fixedCeramic);
            return array_map(function ($combo) { $combo['source_filter'] = 'cheapest'; return $combo; }, $cheapest);
        }

        $results = [];
        foreach ($commonCombos as $combo) {
            $cement = Cement::find($combo->cement_id); $sand = Sand::find($combo->sand_id);
            if (!$cement || !$sand) continue;
            $calculated = $this->calculateCombinationsFromMaterials($brick, $request, collect([$cement]), collect([$sand]), [], 'TerUMUM', 1);
            if (!empty($calculated)) $results[] = $calculated[0];
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
                            $subQ->where('dimension_length', $length)
                                ->where('dimension_width', $width);
                        })->orWhere(function ($subQ) use ($length, $width) {
                            $subQ->where('dimension_length', $width)
                                ->where('dimension_width', $length);
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
        $skip = null
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
            $ceramicQuery->whereNotNull('dimension_thickness')
                ->where('dimension_thickness', '>', 0);
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

        // Dengan generator, bisa handle lebih banyak kombinasi tanpa memory issue
        // Limit lebih besar untuk tile_installation untuk mendapat kombinasi terbaik
        $materialLimit = ($workType === 'tile_installation') ? 10 : 5;

        $cements = Cement::where(function($q) {
                $q->where('type', '!=', 'Nat')->orWhereNull('type');
            })
            ->where('package_price', '>', 0)
            ->where('package_weight_net', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $nats = Cement::where('type', 'Nat')
            ->where('package_price', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $sands = Sand::where('package_price', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $cats = Cat::where('purchase_price', '>', 0)
            ->orderBy('purchase_price')
            ->limit($materialLimit)
            ->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'asc',
            $materialLimit
        );

        return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerMURAH', 3, $ceramics, $nats);
    }

    protected function getMediumCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        $materialLimit = ($workType === 'tile_installation') ? 10 : 5;

        $cements = Cement::where(function($q) {
                $q->where('type', '!=', 'Nat')->orWhereNull('type');
            })
            ->where('package_price', '>', 0)
            ->where('package_weight_net', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $nats = Cement::where('type', 'Nat')
            ->where('package_price', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $sands = Sand::where('package_price', '>', 0)
            ->orderBy('package_price')
            ->limit($materialLimit)
            ->get();

        $cats = Cat::where('purchase_price', '>', 0)
            ->orderBy('purchase_price')
            ->limit($materialLimit)
            ->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'asc',
            $materialLimit
        );

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerSEDANG', null, $ceramics, $nats);

        $total = count($allResults);
        if ($total < 3) return $allResults;
        $startIndex = max(0, floor(($total - 3) / 2));
        return array_slice($allResults, $startIndex, 3);
    }

    protected function getExpensiveCombinations($brick, $request, $fixedCeramic = null)
    {
        $workType = $request->work_type ?? 'brick_half';
        $materialLimit = ($workType === 'tile_installation') ? 10 : 5;

        $cements = Cement::where(function($q) {
                $q->where('type', '!=', 'Nat')->orWhereNull('type');
            })
            ->where('package_price', '>', 0)
            ->where('package_weight_net', '>', 0)
            ->orderByDesc('package_price')
            ->limit($materialLimit)
            ->get();

        $nats = Cement::where('type', 'Nat')
            ->where('package_price', '>', 0)
            ->orderByDesc('package_price')
            ->limit($materialLimit)
            ->get();

        $sands = Sand::where('package_price', '>', 0)
            ->orderByDesc('package_price')
            ->limit($materialLimit)
            ->get();

        $cats = Cat::where('purchase_price', '>', 0)
            ->orderByDesc('purchase_price')
            ->limit($materialLimit)
            ->get();

        $ceramics = $this->resolveCeramicsForCalculation(
            $request,
            $workType,
            $fixedCeramic,
            'price_per_package',
            'desc',
            $materialLimit
        );

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerMAHAL', null, $ceramics, $nats);

        return array_slice(array_reverse($allResults), 0, 3);
    }

    protected function getCustomCombinations($brick, $request)
    {
        $workType = $request->work_type ?? 'brick_half';

        if ($workType === 'painting') {
            if ($request->cat_id) {
                $cats = Cat::where('id', $request->cat_id)->get();
                return $this->calculateCombinationsFromMaterials($brick, $request, [], [], $cats, 'Custom', 1);
            }
        } elseif ($workType === 'grout_tile') {
             if ($request->ceramic_id && $request->nat_id) {
                 $ceramics = Ceramic::where('id', $request->ceramic_id)->get();
                 $nats = Cement::where('id', $request->nat_id)->get();
                 return $this->calculateCombinationsFromMaterials($brick, $request, [], [], [], 'Custom', 1, $ceramics, $nats);
             }
        } elseif ($workType === 'tile_installation') {
             if ($request->ceramic_id && $request->nat_id && $request->cement_id && $request->sand_id) {
                 $ceramics = Ceramic::where('id', $request->ceramic_id)->get();
                 $nats = Cement::where('id', $request->nat_id)->get();
                 $cements = Cement::where('id', $request->cement_id)->get();
                 $sands = Sand::where('id', $request->sand_id)->get();
                 return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, [], 'Custom', 1, $ceramics, $nats);
             }
        } else {
             if ($request->cement_id && $request->sand_id) {
                $cements = Cement::where('id', $request->cement_id)->get();
                $sands = Sand::where('id', $request->sand_id)->get();
                return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, [], 'Custom', 1);
            }
        }
        return $this->getCheapestCombinations($brick, $request);
    }

    protected function calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats = [], $groupLabel = 'Kombinasi', $limit = null, $ceramics = [], $nats = [])
    {
        $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
        $paramsBase['brick_id'] = $brick->id;
        $workType = $request->work_type ?? 'brick_half';

        // Gunakan generator untuk tile_installation untuk efisiensi memory
        if ($workType === 'tile_installation') {
            return $this->processGeneratorResults(
                $this->yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel),
                $limit
            );
        }

        // Untuk workType lain, gunakan cara biasa (sudah cukup efisien)
        $results = [];

        if ($workType === 'painting') {
             foreach ($cats as $cat) {
                 if ($cat->purchase_price <= 0) continue;
                 $params = array_merge($paramsBase, ['cat_id' => $cat->id]);
                 try {
                     $formula = FormulaRegistry::instance('painting');
                     $result = $formula->calculate($params);
                     $results[] = ['cat' => $cat, 'result' => $result, 'total_cost' => $result['grand_total'], 'filter_type' => $groupLabel];
                 } catch (\Exception $e) {}
             }
        } elseif ($workType === 'grout_tile') {
             foreach ($ceramics as $ceramic) {
                 foreach ($nats as $nat) {
                     $params = array_merge($paramsBase, ['ceramic_id' => $ceramic->id, 'nat_id' => $nat->id]);
                     try {
                         $formula = FormulaRegistry::instance('grout_tile');
                         $result = $formula->calculate($params);
                         $results[] = ['ceramic' => $ceramic, 'nat' => $nat, 'result' => $result, 'total_cost' => $result['grand_total'], 'filter_type' => $groupLabel];
                     } catch (\Exception $e) {}
                 }
             }
        } else {
            foreach ($cements as $cement) {
                if ($cement->package_weight_net <= 0) continue;
                foreach ($sands as $sand) {
                    $hasPricePerM3 = $sand->comparison_price_per_m3 > 0;
                    $hasPackageData = $sand->package_volume > 0 && $sand->package_price > 0;
                    if (!$hasPricePerM3 && !$hasPackageData) continue;
                    $params = array_merge($paramsBase, ['cement_id' => $cement->id, 'sand_id' => $sand->id]);
                    try {
                        $formula = FormulaRegistry::instance($workType);
                        if (!$formula) continue;
                        $result = $formula->calculate($params);
                        $results[] = ['cement' => $cement, 'sand' => $sand, 'result' => $result, 'total_cost' => $result['grand_total'], 'filter_type' => $groupLabel];
                    } catch (\Exception $e) {}
                }
            }
        }

        usort($results, function ($a, $b) { return $a['total_cost'] <=> $b['total_cost']; });
        if ($limit) $results = array_slice($results, 0, $limit);
        return $results;
    }

    /**
     * Generator function untuk tile installation - streaming results tanpa menyimpan semua di memory
     */
    protected function yieldTileInstallationCombinations($paramsBase, $ceramics, $nats, $cements, $sands, $groupLabel)
    {
        foreach ($ceramics as $ceramic) {
            foreach ($nats as $nat) {
                foreach ($cements as $cement) {
                    if ($cement->package_weight_net <= 0) continue;

                    foreach ($sands as $sand) {
                        $hasPricePerM3 = $sand->comparison_price_per_m3 > 0;
                        $hasPackageData = $sand->package_volume > 0 && $sand->package_price > 0;
                        if (!$hasPricePerM3 && !$hasPackageData) continue;

                        $params = array_merge($paramsBase, [
                            'ceramic_id' => $ceramic->id,
                            'nat_id' => $nat->id,
                            'cement_id' => $cement->id,
                            'sand_id' => $sand->id
                        ]);

                        try {
                            $formula = FormulaRegistry::instance('tile_installation');
                            $result = $formula->calculate($params);

                            // Yield result satu per satu, tidak menyimpan di memory
                            yield [
                                'ceramic' => $ceramic,
                                'nat' => $nat,
                                'cement' => $cement,
                                'sand' => $sand,
                                'result' => $result,
                                'total_cost' => $result['grand_total'],
                                'filter_type' => $groupLabel
                            ];
                        } catch (\Exception $e) {
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
     * Hanya simpan kombinasi terbaik di memory, buang yang lebih mahal
     */
    protected function processGeneratorResults($generator, $limit = null)
    {
        $results = [];
        $batchSize = 100; // Process setiap 100 kombinasi
        $targetSize = $limit ?? 10; // Berapa kombinasi yang mau kita simpan
        $keepSize = max($targetSize * 3, 30); // Simpan 3x lebih banyak untuk sorting
        $processed = 0;

        foreach ($generator as $combination) {
            $results[] = $combination;
            $processed++;

            // Setiap batch, sort dan ambil yang terbaik saja
            if (count($results) >= $batchSize) {
                usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);
                $results = array_slice($results, 0, $keepSize);
            }
        }

        // Final sort
        usort($results, fn($a, $b) => $a['total_cost'] <=> $b['total_cost']);

        // Return sesuai limit
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    protected function getFilterLabel($filter)
    {
        return match ($filter) {
            'best' => 'TerBAIK',
            'common' => 'TerUMUM',
            'cheapest' => 'TerMURAH',
            'medium' => 'TerSEDANG',
            'expensive' => 'TerMAHAL',
            'custom' => 'Custom',
            'all' => 'Semua',
            default => ucfirst($filter),
        };
    }

    protected function detectAndMergeDuplicates($combinations)
    {
        $uniqueCombos = []; $duplicateMap = [];
        foreach ($combinations as $combo) {
            if (isset($combo['cat'])) { 
                $key = 'cat-' . $combo['cat']->id; 
            } elseif (isset($combo['ceramic'])) {
                $key = 'cer-' . ($combo['ceramic']->id ?? 0) . '-nat-' . ($combo['nat']->id ?? 0) . '-cem-' . ($combo['cement']->id ?? 0) . '-snd-' . ($combo['sand']->id ?? 0);
            } else { 
                $key = ($combo['cement']->id ?? 0) . '-' . ($combo['sand']->id ?? 0); 
            }

            if (!isset($combo['source_filters'])) $combo['source_filters'] = [$combo['filter_type']];
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
        $materialCalculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat', 'ceramic', 'nat']);
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
        return view('material_calculations.edit', compact('materialCalculation', 'availableFormulas', 'installationTypes', 'mortarFormulas', 'bricks', 'cements', 'sands'));
    }

    public function update(Request $request, BrickCalculation $materialCalculation)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
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
            return redirect()->route('material-calculations.show', $materialCalculation)->with('success', 'Perhitungan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(BrickCalculation $materialCalculation)
    {
        try {
            $materialCalculation->delete();
            return redirect()->route('material-calculations.log')->with('success', 'Perhitungan berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus perhitungan: ' . $e->getMessage());
        }
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
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
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
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
            return response()->json(['success' => true, 'data' => ['length' => $brick->dimension_length, 'width' => $brick->dimension_width, 'height' => $brick->dimension_height, 'price_per_piece' => $brick->price_per_piece]]);
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
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::where('type', '!=', 'Nat')->orWhereNull('type')->orderBy('cement_name')->get();
        $nats = Cement::where('type', 'Nat')->orderBy('brand')->get();
        $sands = Sand::orderBy('sand_name')->get();
        $cats = Cat::orderBy('brand')->get();
        $ceramics = Ceramic::orderBy('brand')->get();
        return view('material_calculations.trace', compact('availableFormulas', 'installationTypes', 'mortarFormulas', 'bricks', 'cements', 'nats', 'sands', 'cats', 'ceramics'));
    }

    public function traceCalculation(Request $request)
    {
        $request->validate([
            'formula_code' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
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
            if (!$formula) return response()->json(['success' => false, 'message' => "Formula dengan code '{$formulaCode}' tidak ditemukan"], 404);
            if (!$formula->validate($request->all())) return response()->json(['success' => false, 'message' => 'Parameter tidak valid untuk formula ini'], 422);
            $trace = $formula->trace($request->all());
            return response()->json(['success' => true, 'data' => $trace]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
