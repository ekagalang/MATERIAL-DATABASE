<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
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

            if (!$request->has('mortar_formula_type')) {
                $request->merge(['mortar_formula_type' => 'default']);
            }

            // 1. VALIDASI
            $rules = [
                'work_type' => 'required',
                'price_filter' => 'required|in:cheapest,expensive,custom',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.1',
            ];

            // Validasi Brick: Bisa single 'brick_id' atau array 'brick_ids'
            if ($request->has('brick_ids')) {
                $rules['brick_ids'] = 'required|array';
                $rules['brick_ids.*'] = 'exists:bricks,id';
            } else {
                $rules['brick_id'] = 'required|exists:bricks,id';
            }

            // Validasi Semen/Pasir hanya wajib jika BUKAN custom
            if ($request->price_filter !== 'custom') {
                $rules['cement_id'] = 'required|exists:cements,id';
                $rules['sand_id'] = 'required|exists:sands,id';
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

            // 3. AUTO SELECT MATERIAL (Cheapest/Expensive)
            if ($request->price_filter !== 'custom') {
                $materials = $this->selectMaterialsByPrice($request->price_filter);
                $request->merge([
                    'cement_id' => $materials['cement_id'],
                    'sand_id' => $materials['sand_id'],
                ]);
            }

            // 4. LOGIC PREVIEW KOMBINASI
            // Masuk sini jika:
            // a. User memilih banyak bata (Multi Brick)
            // b. User memilih Custom TAPI mengosongkan Semen/Pasir
            $isMultiBrick = $request->has('brick_ids') && count($request->brick_ids) > 0;
            $isCustomEmpty =
                $request->price_filter === 'custom' && (empty($request->cement_id) || empty($request->sand_id));

            if ($isMultiBrick || $isCustomEmpty) {
                DB::rollBack(); // Tidak jadi simpan
                return $this->generateCombinations($request);
            }

            // 5. SAVE NORMAL (Single Brick & Material Lengkap)
            $calculation = BrickCalculation::performCalculation($request->all());

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

        if ($request->has('brick_ids')) {
            $targetBricks = Brick::whereIn('id', $request->brick_ids)->get();
        } elseif ($request->has('brick_id')) {
            $targetBricks = Brick::where('id', $request->brick_id)->get();
        }

        // Struktur Project untuk View (agar support Multi-Tab)
        $projects = [];

        foreach ($targetBricks as $brick) {
            $projects[] = [
                'brick' => $brick,
                'combinations' => $this->calculateCombinationsForBrick($brick, $request),
            ];
        }

        return view('material_calculations.preview_combinations', [
            'projects' => $projects,
            'requestData' => $request->except(['brick_ids', 'brick_id']), // Clean request data
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
        // Tentukan Kandidat Semen & Pasir
        // Jika filter Cheapest/Expensive, hanya ambil 1 kandidat (pemenang)
        // Jika Custom Empty, ambil semua

        $cements = collect();
        $sands = collect();

        if ($request->price_filter !== 'custom') {
            // Auto Select 1 Pemenang
            $materials = $this->selectMaterialsByPrice($request->price_filter);
            $cements = Cement::where('id', $materials['cement_id'])->get();
            $sands = Sand::where('id', $materials['sand_id'])->get();
        } else {
            // Custom Logic
            $cements = $request->cement_id
                ? Cement::where('id', $request->cement_id)->get()
                : Cement::orderBy('package_price')->get();
            $sands = $request->sand_id
                ? Sand::where('id', $request->sand_id)->get()
                : Sand::orderBy('package_price')->get();
        }

        // Setup Parameter
        $paramsBase = [
            'wall_length' => $request->wall_length,
            'wall_height' => $request->wall_height,
            'mortar_thickness' => $request->mortar_thickness,
            'installation_type_id' => $request->installation_type_id,
            'mortar_formula_id' => $request->mortar_formula_id,
            'brick_id' => $brick->id,
        ];

        $combinations = [];

        foreach ($cements as $cement) {
            foreach ($sands as $sand) {
                $params = array_merge($paramsBase, ['cement_id' => $cement->id, 'sand_id' => $sand->id]);

                try {
                    $trace = BrickCalculationTracer::traceProfessionalMode($params);
                    $result = $trace['final_result'];

                    // Grouping Logic
                    $groupBy = 'Umum';
                    if ($request->price_filter !== 'custom') {
                        $groupBy = $request->price_filter == 'cheapest' ? 'Termurah' : 'Termahal';
                    } else {
                        if (!$request->cement_id && $request->sand_id) {
                            $groupBy = $cement->brand ?? 'Semen';
                        } elseif ($request->cement_id && !$request->sand_id) {
                            $groupBy = $sand->brand ?? 'Pasir';
                        } else {
                            $groupBy = $cement->brand ?? 'Umum';
                        }
                    }

                    $combinations[$groupBy][] = [
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

        // Sorting Logic
        foreach ($combinations as &$items) {
            usort($items, function ($a, $b) {
                return $a['total_cost'] <=> $b['total_cost'];
            });
        }
        uasort($combinations, function ($groupA, $groupB) {
            return ($groupA[0]['total_cost'] ?? 0) <=> ($groupB[0]['total_cost'] ?? 0);
        });

        return $combinations;
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
