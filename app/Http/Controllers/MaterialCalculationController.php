<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use App\Services\BrickCalculationTracer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialCalculationController extends Controller
{
    /**
     * Display a listing of calculations
     */
    public function index(Request $request)
    {
        $availableFormulas = FormulaRegistry::all();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        return view('material_calculations.index', compact('availableFormulas', 'bricks', 'cements', 'sands'));
    }

    /**
     * Log riwayat perhitungan (sebelumnya index)
     */
    public function log(Request $request)
    {
        $query = BrickCalculation::with(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->has('installation_type') && $request->installation_type != '') {
            $query->where('installation_type_id', $request->installation_type);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $calculations = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        $installationTypes = BrickInstallationType::getActive();

        return view('material_calculations.log', compact('calculations', 'installationTypes'));
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
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        $defaultInstallationType = BrickInstallationType::getDefault();
        $defaultMortarFormula = MortarFormula::getDefault();

        // LOGIC BARU: Handle Multi-Select Bricks dari Price Analysis
        // Kita kirim variable $selectedBricks ke View
        $selectedBricks = collect();
        if ($request->has('brick_ids')) {
            $selectedBricks = Brick::whereIn('id', $request->brick_ids)->get();
        }

        return view(
            'material_calculations.create',
            compact(
                'availableFormulas',
                'installationTypes',
                'mortarFormulas',
                'bricks',
                'cements',
                'sands',
                'defaultInstallationType',
                'defaultMortarFormula',
                'selectedBricks', // Pastikan ini dikirim!
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
                'mortar_thickness' => 'required|numeric|min:0.1',
            ];

            // Default to 'best' if no filters selected
            if (!$request->has('price_filters') || empty($request->price_filters)) {
                $request->merge(['price_filters' => ['best']]);
            }

            // Validasi Brick: Bisa single 'brick_id' atau array 'brick_ids'
            if ($request->has('brick_ids')) {
                $rules['brick_ids'] = 'required|array';
                $rules['brick_ids.*'] = 'exists:bricks,id';
            } else {
                $rules['brick_id'] = 'required|exists:bricks,id';
            }

            // Validasi Semen/Pasir hanya wajib jika 'custom' ada di price_filters
            $priceFilters = $request->price_filters ?? [];
            if (in_array('custom', $priceFilters)) {
                // Custom filter selected, cement and sand are optional (can be selected by user)
            } else {
                // No custom filter, cement and sand not required (will be auto-selected)
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
            $isCustomEmpty = $hasCustom && (empty($request->cement_id) || empty($request->sand_id));
            $needCombinations = $hasOtherFilters || $isMultiBrick || $isCustomEmpty;

            if ($needCombinations) {
                // Generate combinations for selected filters
                DB::rollBack(); // Tidak jadi simpan
                return $this->generateCombinations($request);
            }

            // If only custom is selected and materials are provided, auto-select them
            if ($hasCustom && !empty($request->cement_id) && !empty($request->sand_id)) {
                // Keep custom selected materials
            }

            // 5. SAVE NORMAL (Single Brick & Material Lengkap)
            // Debug logging
            \Log::info('Store Calculation - Request Data:', [
                'work_type' => $request->work_type,
                'brick_id' => $request->brick_id,
                'cement_id' => $request->cement_id,
                'sand_id' => $request->sand_id,
                'wall_length' => $request->wall_length,
                'wall_height' => $request->wall_height,
                'mortar_thickness' => $request->mortar_thickness,
                'installation_type_id' => $request->installation_type_id,
                'mortar_formula_id' => $request->mortar_formula_id,
                'layer_count' => $request->layer_count ?? 1, // For Rollag formula
            ]);

            $calculation = BrickCalculation::performCalculation($request->all());

            \Log::info('Store Calculation - Result:', [
                'total_cost' => $calculation->total_material_cost,
                'brick_quantity' => $calculation->brick_quantity,
                'cement_quantity' => $calculation->cement_quantity_sak,
                'sand_quantity' => $calculation->sand_m3,
            ]);

            if (!$request->boolean('confirm_save')) {
                DB::rollBack();
                $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);
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
     * Logic baru untuk generate kombinasi jika user mengosongkan Semen/Pasir di mode Custom
     */
    protected function generateCombinations(Request $request)
    {
        // Tentukan Bata mana saja yang akan dihitung
        $targetBricks = collect();

        // CHECK: If 'best' filter is selected, we want to include ALL recommended bricks?
        // Logic: "filter TerBAIK ini... mandiri seperti di setting rekomendasi"
        // This implies: If 'best' is chosen, show ALL configured recommendations.
        $priceFilters = $request->price_filters ?? [];
        if (in_array('best', $priceFilters) && count($priceFilters) === 1) {
            // Fetch ALL bricks that have a recommendation
            $recommendedBrickIds = RecommendedCombination::where('type', 'best')->pluck('brick_id')->unique();
            
            if ($recommendedBrickIds->isNotEmpty()) {
                $targetBricks = Brick::whereIn('id', $recommendedBrickIds)->get();
            } else {
                // Fallback to normal selection if no recommendations exist
                if ($request->has('brick_ids')) {
                    $targetBricks = Brick::whereIn('id', $request->brick_ids)->get();
                } elseif ($request->has('brick_id')) {
                    $targetBricks = Brick::where('id', $request->brick_id)->get();
                }
            }
        } else {
            // Normal behavior for other filters
            if ($request->has('brick_ids')) {
                $targetBricks = Brick::whereIn('id', $request->brick_ids)->get();
            } elseif ($request->has('brick_id')) {
                $targetBricks = Brick::where('id', $request->brick_id)->get();
            }
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
        $workType = $request->work_type ?? 'brick_half';
        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';

        return view('material_calculations.preview_combinations', [
            'projects' => $projects,
            'requestData' => $request->except(['brick_ids', 'brick_id']), // Clean request data
            'formulaName' => $formulaName,
        ]);
    }

    public function compareBricks(Request $request)
    {
        $request->validate([
            'brick_ids' => 'required|array|min:1',
            'wall_length' => 'required|numeric',
            'wall_height' => 'required|numeric',
            'mortar_thickness' => 'required|numeric',
            'installation_type_id' => 'required',
            'layer_count' => 'nullable|integer|min:1', // For Rollag formula
        ]);

        $wallArea = $request->wall_length * $request->wall_height;
        $bricks = Brick::whereIn('id', $request->brick_ids)->get();

        // Auto-select cheapest mortar materials for fair comparison
        $priceFilter = 'cheapest';
        $materials = $this->selectMaterialsByPrice($priceFilter);

        // Use default mortar formula (1:3 or 1:4)
        // Disini kita cari formula 1:3 atau yang tersedia
        $defaultMortar =
            MortarFormula::where('cement_ratio', 1)->where('sand_ratio', 3)->first() ?? MortarFormula::first();

        $comparisons = [];

        foreach ($bricks as $brick) {
            $params = [
                'wall_length' => $request->wall_length,
                'wall_height' => $request->wall_height,
                'mortar_thickness' => $request->mortar_thickness,
                'installation_type_id' => $request->installation_type_id,
                'mortar_formula_id' => $defaultMortar->id,
                'brick_id' => $brick->id,
                'cement_id' => $materials['cement_id'],
                'sand_id' => $materials['sand_id'],
                'layer_count' => $request->layer_count ?? 1, // For Rollag formula
            ];

            try {
                $trace = BrickCalculationTracer::traceProfessionalMode($params);
                $result = $trace['final_result'];

                $comparisons[] = [
                    'brick' => $brick,
                    'result' => $result,
                    'total_cost' => $result['grand_total'],
                    'cost_per_m2' => $result['grand_total'] / $wallArea,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        // Sort by total cost ascending (Cheapest first)
        usort($comparisons, function ($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        // Load cement & sand objects for display
        $cement = Cement::find($materials['cement_id']);
        $sand = Sand::find($materials['sand_id']);

        return view('material_calculations.compare_bricks', [
            'comparisons' => $comparisons,
            'wallArea' => $wallArea,
            'wallLength' => $request->wall_length,
            'wallHeight' => $request->wall_height,
            'mortarThickness' => $request->mortar_thickness,
            'refCement' => $cement,
            'refSand' => $sand,
            'requestData' => $request->all(),
        ]);
    }

    /**
     * Helper: Hitung Kombinasi untuk 1 Bata
     */
    protected function calculateCombinationsForBrick($brick, $request)
    {
        $requestedFilters = $request->price_filters ?? ['best'];
        
        // Special, independent handling for 'best' filter when it's the only one selected
        if (count($requestedFilters) === 1 && $requestedFilters[0] === 'best') {
            $bestCombinations = $this->getBestCombinations($brick, $request);
            $finalResults = [];
            foreach ($bestCombinations as $index => $combo) {
                $label = "TerBAIK " . ($index + 1);
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
                $filterLabel = $this->getFilterLabel($filter);
                
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
        $uniqueCombos = $this->detectAndMergeDuplicates($allCombinations);

        // Pre-calculate mapped labels for requested filters to fast check priority
        // We use the original $request->price_filters to prioritize the USER's direct selection
        // (even if 'all' was selected, we prioritize standard ordering, but if specific was selected, priority matters)
        $priorityLabels = [];
        $userOriginalFilters = $request->price_filters ?? [];
        foreach ($userOriginalFilters as $rf) {
            if ($rf !== 'all') {
                $priorityLabels[] = ($rf === 'custom') ? 'Custom' : $this->getFilterLabel($rf);
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
                    usort($labels, function($a, $b) use ($priorityLabels) {
                        // Check priority score
                        $aScore = 0;
                        foreach ($priorityLabels as $pl) {
                            if (str_starts_with($a, $pl)) {
                                $aScore = 1; break; 
                            }
                        }
                        
                        $bScore = 0;
                        foreach ($priorityLabels as $pl) {
                             if (str_starts_with($b, $pl)) {
                                $bScore = 1; break; 
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
     * Get all combinations (no limit) - for filter "Semua"
     */
    protected function getAllCombinations($brick, $request)
    {
        // Get all valid cements and sands (exclude null/invalid data)
        $cements = Cement::whereNotNull('brand')
            ->whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('package_price')
            ->get();

        $sands = Sand::whereNotNull('brand')
            ->whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('package_price')
            ->get();

        $results = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'Semua');

        // Group as "Semua" (not by individual labels)
        return ['Semua' => $results];
    }

    /**
     * Get 3 combinations based on filter type
     */
    protected function getCombinationsByFilter($brick, $request, $filter)
    {
        switch ($filter) {
            case 'best':
                return $this->getBestCombinations($brick, $request);
            case 'common':
                return $this->getCommonCombinations($brick, $request);
            case 'cheapest':
                return $this->getCheapestCombinations($brick, $request);
            case 'medium':
                return $this->getMediumCombinations($brick, $request);
            case 'expensive':
                return $this->getExpensiveCombinations($brick, $request);
            case 'custom':
                return $this->getCustomCombinations($brick, $request);
            default:
                return [];
        }
    }

    /**
     * Get filter label in Indonesian
     */
    protected function getFilterLabel($filter)
    {
        return match($filter) {
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

    /**
     * Detect and merge duplicate combinations
     */
    protected function detectAndMergeDuplicates($combinations)
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

                \Log::info('Duplicate Detected:', [
                    'key' => $key,
                    'merged_label' => $uniqueCombos[$existingIndex]['filter_label'],
                ]);
            } else {
                // New combination
                $duplicateMap[$key] = count($uniqueCombos);
                
                // Initialize all_labels
                $combo['all_labels'] = [$currentLabel];
                
                $uniqueCombos[] = $combo;

                \Log::info('New Combination:', [
                    'key' => $key,
                    'label' => $combo['filter_label'],
                ]);
            }
        }

        \Log::info('Duplicate Detection Summary:', [
            'total_input' => count($combinations),
            'total_unique' => count($uniqueCombos),
            'duplicates_merged' => count($combinations) - count($uniqueCombos),
        ]);

        return $uniqueCombos;
    }

    /**
     * Calculate combinations from given materials
     */
    protected function calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $groupLabel = 'Kombinasi', $limit = null)
    {
        $paramsBase = [
            'wall_length' => $request->wall_length,
            'wall_height' => $request->wall_height,
            'mortar_thickness' => $request->mortar_thickness,
            'installation_type_id' => $request->installation_type_id,
            'mortar_formula_id' => $request->mortar_formula_id,
            'work_type' => $request->work_type ?? 'brick_half',
            'brick_id' => $brick->id,
            'layer_count' => $request->layer_count ?? 1, // For Rollag formula
        ];

        $results = [];

        foreach ($cements as $cement) {
            foreach ($sands as $sand) {
                $params = array_merge($paramsBase, [
                    'cement_id' => $cement->id,
                    'sand_id' => $sand->id
                ]);

                try {
                    // Use the same calculation method as save for consistency
                    $formulaCode = $params['work_type'] ?? 'brick_half';
                    $formula = \App\Services\FormulaRegistry::instance($formulaCode);

                    if (!$formula) {
                        throw new \Exception("Formula '{$formulaCode}' tidak ditemukan");
                    }

                    $result = $formula->calculate($params);

                    // Debug logging untuk preview
                    \Log::info('Preview Calculation:', [
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
                    \Log::error('Preview Calculation Error:', [
                        'cement_id' => $cement->id,
                        'sand_id' => $sand->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }
        }

        // Sort by total cost
        usort($results, function($a, $b) {
            return $a['total_cost'] <=> $b['total_cost'];
        });

        // Apply limit if specified
        if ($limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Get 3 best (recommended) combinations
     */
    protected function getBestCombinations($brick, $request)
    {
        // 1. Check for Admin Recommendations
        $recommendations = RecommendedCombination::where('brick_id', $brick->id)
            ->where('type', 'best')
            ->get();

        \Log::info("GetBestCombinations: Found " . $recommendations->count() . " recommendations for brick ID: " . $brick->id);

        $allRecommendedResults = [];

        foreach ($recommendations as $rec) {
            $cements = Cement::where('id', $rec->cement_id)->get();
            $sands = Sand::where('id', $rec->sand_id)->get();

            \Log::info("Processing recommendation: Cement ID {$rec->cement_id}, Sand ID {$rec->sand_id}");

            // Calculate for this specific pair
            $results = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerBAIK', 1);
            
            \Log::info("Calculation result count: " . count($results));

            // Mark as 'best' source and add to collection
            foreach ($results as &$res) {
                $res['source_filter'] = 'best';
                $allRecommendedResults[] = $res;
            }
        }

        // If there are admin recommendations, return them
        if (!empty($allRecommendedResults)) {
            \Log::info("Returning " . count($allRecommendedResults) . " admin-defined 'best' combinations.");
            return $allRecommendedResults;
        }

        // 2. No recommendations found
        \Log::info("No admin recommendations found.");
        return [];
    }

    /**
     * Get 3 most commonly used combinations
     */
    protected function getCommonCombinations($brick, $request)
    {
        // Query most frequent combinations from material_calculations table
        $commonCombos = \DB::table('brick_calculations')
            ->select('cement_id', 'sand_id', \DB::raw('count(*) as frequency'))
            ->where('brick_id', $brick->id)
            ->groupBy('cement_id', 'sand_id')
            ->orderByDesc('frequency')
            ->limit(3)
            ->get();

        if ($commonCombos->isEmpty()) {
            // Fallback to cheapest if no history
            // Mark these combinations as coming from 'cheapest' filter
            $cheapest = $this->getCheapestCombinations($brick, $request);
            return array_map(function($combo) {
                $combo['source_filter'] = 'cheapest';
                return $combo;
            }, $cheapest);
        }

        $results = [];
        foreach ($commonCombos as $combo) {
            $cement = Cement::find($combo->cement_id);
            $sand = Sand::find($combo->sand_id);

            if (!$cement || !$sand) continue;

            $cements = collect([$cement]);
            $sands = collect([$sand]);
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
     */
    protected function getCheapestCombinations($brick, $request)
    {
        $cements = Cement::orderBy('package_price')->get();
        $sands = Sand::orderBy('package_price')->get();

        return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerMURAH', 3);
    }

    /**
     * Get 3 medium-priced combinations
     */
    protected function getMediumCombinations($brick, $request)
    {
        $cements = Cement::orderBy('package_price')->get();
        $sands = Sand::orderBy('package_price')->get();

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerSEDANG');

        // Get middle 3 combinations
        $total = count($allResults);
        if ($total < 3) return $allResults;

        $startIndex = max(0, floor(($total - 3) / 2));
        return array_slice($allResults, $startIndex, 3);
    }

    /**
     * Get 3 most expensive combinations
     */
    protected function getExpensiveCombinations($brick, $request)
    {
        $cements = Cement::orderByDesc('package_price')->get();
        $sands = Sand::orderByDesc('package_price')->get();

        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'TerMAHAL');

        // Get top 3 most expensive
        return array_slice(array_reverse($allResults), 0, 3);
    }

    /**
     * Get custom combinations
     */
    protected function getCustomCombinations($brick, $request)
    {
        if ($request->cement_id && $request->sand_id) {
            // Specific materials selected
            $cements = Cement::where('id', $request->cement_id)->get();
            $sands = Sand::where('id', $request->sand_id)->get();
            return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, 'Custom', 1);
        } else {
            // Show all combinations
            return $this->getAllCombinations($brick, $request);
        }
    }

    /**
     * Display the specified calculation
     */
    public function show(BrickCalculation $materialCalculation)
    {
        $materialCalculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);

        $summary = $materialCalculation->getSummary();

        return view('material_calculations.show_log', compact('materialCalculation', 'summary'));
    }

    /**
     * Show the form for editing
     */
    public function edit(BrickCalculation $materialCalculation)
    {
        // Get available formulas from Formula Registry
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

    /**
     * Update the specified calculation
     */
    public function update(Request $request, BrickCalculation $materialCalculation)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.1|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'project_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'layer_count' => 'nullable|integer|min:1', // For Rollag formula
        ]);

        try {
            DB::beginTransaction();

            // Perform new calculation with updated params
            $newCalculation = BrickCalculation::performCalculation($request->all());

            // Update existing record
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

    /**
     * Remove the specified calculation
     */
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

    /**
     * API: Real-time calculation (tanpa save ke database)
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.1|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1', // For Rollag formula
        ]);

        try {
            // Perform calculation without saving
            $calculation = BrickCalculation::performCalculation($request->all());

            // Load relationships for response
            $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand']);

            $summary = $calculation->getSummary();

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    /**
     * API: Compare multiple installation types
     */
    public function compare(Request $request)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.1|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1', // For Rollag formula
        ]);

        try {
            $installationTypes = BrickInstallationType::getActive();
            $comparisons = [];

            foreach ($installationTypes as $type) {
                $params = array_merge($request->all(), [
                    'installation_type_id' => $type->id,
                ]);

                $calculation = BrickCalculation::performCalculation($params);
                $calculation->load(['installationType', 'mortarFormula']);

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

            return response()->json([
                'success' => true,
                'data' => $comparisons,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    /**
     * API: Get brick dimensions by ID
     */
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
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Bata tidak ditemukan',
                ],
                404,
            );
        }
    }

    /**
     * Export calculation to PDF (placeholder - implement di fase berikutnya)
     */
    public function exportPdf(BrickCalculation $materialCalculation)
    {
        // TODO: Implement PDF export in Phase 6
        return redirect()->back()->with('info', 'Fitur export PDF akan ditambahkan di fase berikutnya');
    }

    /**
     * Dashboard/Statistics
     */
    public function dashboard()
    {
        $availableFormulas = FormulaRegistry::all();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        return view('material_calculations.index', compact('availableFormulas', 'bricks', 'cements', 'sands'));
    }

    /**
     * View: Trace page - step by step calculation
     */
    public function traceView()
    {
        // Get available formulas from Formula Registry
        $availableFormulas = FormulaRegistry::all();

        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('cement_name')->get();
        $sands = Sand::orderBy('sand_name')->get();

        return view(
            'material_calculations.trace',
            compact('availableFormulas', 'installationTypes', 'mortarFormulas', 'bricks', 'cements', 'sands'),
        );
    }

    /**
     * API: Trace calculation - return step by step
     * Mode 1 Professional only (most accurate and complete)
     */
    public function traceCalculation(Request $request)
    {
        $request->validate([
            'formula_code' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.1|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'custom_cement_ratio' => 'nullable|numeric|min:1',
            'custom_sand_ratio' => 'nullable|numeric|min:1',
            'has_additional_layer' => 'nullable|boolean',
            'layer_count' => 'nullable|integer|min:1', // For Rollag formula
        ]);

        try {
            // Get formula instance from registry
            $formulaCode = $request->input('formula_code');
            $formula = FormulaRegistry::instance($formulaCode);

            if (!$formula) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => "Formula dengan code '{$formulaCode}' tidak ditemukan",
                    ],
                    404,
                );
            }

            // Validate parameters using formula's validate method
            if (!$formula->validate($request->all())) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Parameter tidak valid untuk formula ini',
                    ],
                    422,
                );
            }

            // Execute trace calculation
            $trace = $formula->trace($request->all());

            return response()->json([
                'success' => true,
                'data' => $trace,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    /**
     * Helper: Select materials based on price filter
     *
     * @param  string  $filter  'cheapest' or 'expensive'
     * @return array ['brick_id', 'cement_id', 'sand_id']
     */
    protected function selectMaterialsByPrice(string $filter): array
    {
        $orderDirection = $filter === 'cheapest' ? 'asc' : 'desc';

        // Get brick based on price_per_piece
        $brick = Brick::whereNotNull('price_per_piece')
            ->where('price_per_piece', '>', 0)
            ->orderBy('price_per_piece', $orderDirection)
            ->first();

        // Get cement based on package_price
        $cement = Cement::whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('package_price', $orderDirection)
            ->first();

        // Get sand based on comparison_price_per_m3 or calculate from package_price/volume
        $sand = Sand::whereNotNull('comparison_price_per_m3')
            ->where('comparison_price_per_m3', '>', 0)
            ->orderBy('comparison_price_per_m3', $orderDirection)
            ->first();

        // Fallback to first available if no price data
        if (!$brick) {
            $brick = Brick::first();
        }
        if (!$cement) {
            $cement = Cement::first();
        }
        if (!$sand) {
            $sand = Sand::first();
        }

        return [
            'brick_id' => $brick?->id,
            'cement_id' => $cement?->id,
            'sand_id' => $sand?->id,
        ];
    }
}
