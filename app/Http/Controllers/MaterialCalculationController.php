<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cat;
use App\Models\Cement;
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
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();
        $cats = Cat::orderBy('brand')->get();

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
                'cats',
                'defaultInstallationType',
                'defaultMortarFormula',
                'selectedBricks', 
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
            $needsBrick = !in_array($workType, ['wall_plastering', 'skim_coating', 'painting']);
            $needsSand = !in_array($workType, ['skim_coating', 'painting']);
            $needsCement = !in_array($workType, ['painting']);
            $needsCat = in_array($workType, ['painting']);

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
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating', 'painting']);

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

    protected function getCombinationsByFilter($brick, $request, $filter)
    {
        switch ($filter) {
            case 'best': return $this->getBestCombinations($brick, $request);
            case 'common': return $this->getCommonCombinations($brick, $request);
            case 'cheapest': return $this->getCheapestCombinations($brick, $request);
            case 'medium': return $this->getMediumCombinations($brick, $request);
            case 'expensive': return $this->getExpensiveCombinations($brick, $request);
            case 'custom': return $this->getCustomCombinations($brick, $request);
            default: return [];
        }
    }

    protected function getBestCombinations($brick, $request)
    {
        $workType = $request->work_type ?? 'brick_half';
        $isBrickless = in_array($workType, ['wall_plastering', 'skim_coating', 'painting']);

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
             $cheapest = $this->getCheapestCombinations($brick, $request);
             return array_map(function ($combo) { $combo['source_filter'] = 'best'; return $combo; }, array_slice($cheapest, 0, 3));
        }
        return [];
    }

    protected function getCommonCombinations($brick, $request)
    {
        $workType = $request->work_type ?? 'brick_half';
        if (in_array($workType, ['wall_plastering', 'skim_coating', 'painting'])) {
             $cheapest = $this->getCheapestCombinations($brick, $request);
             return array_map(function ($combo) { $combo['source_filter'] = 'common'; return $combo; }, array_slice($cheapest, 0, 3));
        }

        $commonCombos = DB::table('brick_calculations')->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))->where('brick_id', $brick->id)->groupBy('cement_id', 'sand_id')->orderByDesc('frequency')->limit(3)->get();
        if ($commonCombos->isEmpty()) {
            $cheapest = $this->getCheapestCombinations($brick, $request);
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

    protected function getCheapestCombinations($brick, $request)
    {
        $cements = Cement::orderBy('package_price')->get();
        $sands = Sand::orderBy('package_price')->get();
        $cats = Cat::orderBy('purchase_price')->get();
        return $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerMURAH', 3);
    }

    protected function getMediumCombinations($brick, $request)
    {
        $cements = Cement::orderBy('package_price')->get();
        $sands = Sand::orderBy('package_price')->get();
        $cats = Cat::orderBy('purchase_price')->get();
        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerSEDANG');
        $total = count($allResults);
        if ($total < 3) return $allResults;
        $startIndex = max(0, floor(($total - 3) / 2));
        return array_slice($allResults, $startIndex, 3);
    }

    protected function getExpensiveCombinations($brick, $request)
    {
        $cements = Cement::orderByDesc('package_price')->get();
        $sands = Sand::orderByDesc('package_price')->get();
        $cats = Cat::orderByDesc('purchase_price')->get();
        $allResults = $this->calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats, 'TerMAHAL');
        return array_slice(array_reverse($allResults), 0, 3);
    }

    protected function getCustomCombinations($brick, $request)
    {
        if ($request->work_type === 'painting') {
            if ($request->cat_id) {
                $cats = Cat::where('id', $request->cat_id)->get();
                return $this->calculateCombinationsFromMaterials($brick, $request, [], [], $cats, 'Custom', 1);
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

    protected function calculateCombinationsFromMaterials($brick, $request, $cements, $sands, $cats = [], $groupLabel = 'Kombinasi', $limit = null)
    {
        $paramsBase = $request->except(['_token', 'price_filters', 'brick_ids', 'brick_id']);
        $paramsBase['brick_id'] = $brick->id;
        $workType = $request->work_type ?? 'brick_half';
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
            if (isset($combo['cat'])) { $key = 'cat-' . $combo['cat']->id; }
            else { $key = ($combo['cement']->id ?? 0) . '-' . ($combo['sand']->id ?? 0); }

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
        $materialCalculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);
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
        $cements = Cement::orderBy('cement_name')->get();
        $sands = Sand::orderBy('sand_name')->get();
        $cats = Cat::orderBy('brand')->get();
        return view('material_calculations.trace', compact('availableFormulas', 'installationTypes', 'mortarFormulas', 'bricks', 'cements', 'sands', 'cats'));
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
