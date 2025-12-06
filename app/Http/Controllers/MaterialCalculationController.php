<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;
use App\Services\FormulaRegistry;
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

        return view('material_calculations.index', compact(
            'availableFormulas',
            'bricks',
            'cements',
            'sands'
        ));
    }

    /**
     * Log riwayat perhitungan (sebelumnya index)
     */
    public function log(Request $request)
    {
        $query = BrickCalculation::with([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ]);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
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

        $calculations = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $installationTypes = BrickInstallationType::getActive();

        return view('material_calculations.log', compact('calculations', 'installationTypes'));
    }

    /**
     * Show the form for creating a new calculation
     */
    public function create()
    {
        // Get available formulas from Formula Registry
        $availableFormulas = FormulaRegistry::all();

        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        // Get default values
        $defaultInstallationType = BrickInstallationType::getDefault();
        $defaultMortarFormula = MortarFormula::getDefault();

        return view('material_calculations.create', compact(
            'availableFormulas',
            'installationTypes',
            'mortarFormulas',
            'bricks',
            'cements',
            'sands',
            'defaultInstallationType',
            'defaultMortarFormula'
        ));
    }

    /**
     * Store a newly created calculation
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Set default mortar formula type when form tidak mengirim field ini
            if (! $request->has('mortar_formula_type')) {
                $request->merge(['mortar_formula_type' => 'default']);
            }

            $request->validate([
                'work_type' => 'required|string',
                'price_filter' => 'required|in:cheapest,expensive,custom',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.1|max:10',
                'mortar_formula_type' => 'required|in:default,custom',
                'brick_id' => 'required_if:price_filter,custom|nullable|exists:bricks,id',
                'cement_id' => 'required_if:price_filter,custom|nullable|exists:cements,id',
                'sand_id' => 'required_if:price_filter,custom|nullable|exists:sands,id',
                'project_name' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'custom_cement_ratio' => 'required_if:mortar_formula_type,custom|nullable|numeric|min:0.1',
                'custom_sand_ratio' => 'required_if:mortar_formula_type,custom|nullable|numeric|min:0.1',
                'custom_water_ratio' => 'nullable|numeric|min:0',
            ], [
                'work_type.required' => 'Jenis pekerjaan harus dipilih',
                'price_filter.required' => 'Preferensi harga harus dipilih',
                'wall_length.required' => 'Panjang dinding harus diisi',
                'wall_length.min' => 'Panjang dinding minimal 0.01 meter',
                'wall_height.required' => 'Tinggi dinding harus diisi',
                'wall_height.min' => 'Tinggi dinding minimal 0.01 meter',
                'mortar_thickness.required' => 'Tebal adukan harus diisi',
                'mortar_thickness.min' => 'Tebal adukan minimal 0.1 cm',
                'mortar_thickness.max' => 'Tebal adukan maksimal 10 cm',
                'mortar_formula_type.required' => 'Tipe formula adukan harus dipilih',
                'brick_id.required_if' => 'Bata harus dipilih jika menggunakan custom material',
                'cement_id.required_if' => 'Semen harus dipilih jika menggunakan custom material',
                'sand_id.required_if' => 'Pasir harus dipilih jika menggunakan custom material',
                'custom_cement_ratio.required_if' => 'Rasio semen harus diisi jika menggunakan custom ratio',
                'custom_sand_ratio.required_if' => 'Rasio pasir harus diisi jika menggunakan custom ratio',
            ]);

            // Set default installation type and mortar formula based on work_type
            $workType = $request->input('work_type');

            // Default installation type berdasarkan work_type
            // Untuk brick work, gunakan default installation type pertama
            $defaultInstallationType = BrickInstallationType::where('is_active', true)
                ->orderBy('id')
                ->first();

            // Default mortar formula (1:3)
            $mortarFormulaType = $request->input('mortar_formula_type');
            if ($mortarFormulaType === 'custom') {
                // Jika custom, set use_custom_ratio = true
                $request->merge(['use_custom_ratio' => true]);

                // Gunakan mortar formula pertama sebagai base (tapi ratio akan di-override oleh custom)
                $defaultMortarFormula = MortarFormula::where('is_active', true)
                    ->orderBy('id')
                    ->first();
            } else {
                // Jika default (1:3), cari formula dengan ratio 1:3
                $defaultMortarFormula = MortarFormula::where('is_active', true)
                    ->where('cement_ratio', 1)
                    ->where('sand_ratio', 3)
                    ->first();

                // Fallback ke formula pertama jika tidak ada 1:3
                if (! $defaultMortarFormula) {
                    $defaultMortarFormula = MortarFormula::where('is_active', true)
                        ->orderBy('id')
                        ->first();
                }

                $request->merge(['use_custom_ratio' => false]);
            }

            // Merge default values ke request
            $request->merge([
                'installation_type_id' => $defaultInstallationType?->id,
                'mortar_formula_id' => $defaultMortarFormula?->id,
            ]);

            // Auto-select materials based on price filter
            $priceFilter = $request->input('price_filter');
              if ($priceFilter !== 'custom') {
                  $materials = $this->selectMaterialsByPrice($priceFilter);
                  $request->merge([
                      'brick_id' => $materials['brick_id'],
                      'cement_id' => $materials['cement_id'],
                      'sand_id' => $materials['sand_id'],
                  ]);
              }

            // Perform calculation
            $calculation = BrickCalculation::performCalculation($request->all());

            // Load relationships for potential preview / summary
            $calculation->load([
                'installationType',
                'mortarFormula',
                'brick',
                'cement',
                'sand',
            ]);

            // Jika belum ada konfirmasi simpan, tampilkan halaman preview (full page, bukan modal)
            if (! $request->boolean('confirm_save')) {
                DB::rollBack();

                $summary = $calculation->getSummary();

                return view('material_calculations.preview', [
                    'calculation' => $calculation,
                    'summary' => $summary,
                    'formData' => $request->all(),
                ]);
            }

            // Save to database setelah konfirmasi
            $calculation->save();

            DB::commit();

            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Perhitungan berhasil disimpan!',
                    'redirect' => route('material-calculations.show', $calculation),
                ]);
            }

            return redirect()
                ->route('material-calculations.show', $calculation)
                ->with('success', 'Perhitungan berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                    'errors' => ['general' => [$e->getMessage()]],
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Display the specified calculation
     */
    public function show(BrickCalculation $materialCalculation)
    {
        $materialCalculation->load([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ]);

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

        return view('material_calculations.edit', compact(
            'materialCalculation',
            'availableFormulas',
            'installationTypes',
            'mortarFormulas',
            'bricks',
            'cements',
            'sands'
        ));
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
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified calculation
     */
    public function destroy(BrickCalculation $materialCalculation)
    {
        try {
            $materialCalculation->delete();

            return redirect()
                ->route('material-calculations.log')
                ->with('success', 'Perhitungan berhasil dihapus!');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus perhitungan: '.$e->getMessage());
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
            $calculation->load([
                'installationType',
                'mortarFormula',
                'brick',
                'cement',
                'sand',
            ]);

            $summary = $calculation->getSummary();

            return response()->json([
                'success' => true,
                'data' => $calculation,
                'summary' => $summary,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
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
            return response()->json([
                'success' => false,
                'message' => 'Bata tidak ditemukan',
            ], 404);
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

        return view('material_calculations.index', compact(
            'availableFormulas',
            'bricks',
            'cements',
            'sands'
        ));
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

        return view('material_calculations.trace', compact(
            'availableFormulas',
            'installationTypes',
            'mortarFormulas',
            'bricks',
            'cements',
            'sands'
        ));
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

            if (! $formula) {
                return response()->json([
                    'success' => false,
                    'message' => "Formula dengan code '{$formulaCode}' tidak ditemukan",
                ], 404);
            }

            // Validate parameters using formula's validate method
            if (! $formula->validate($request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter tidak valid untuk formula ini',
                ], 422);
            }

            // Execute trace calculation
            $trace = $formula->trace($request->all());

            return response()->json([
                'success' => true,
                'data' => $trace,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
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
        if (! $brick) {
            $brick = Brick::first();
        }
        if (! $cement) {
            $cement = Cement::first();
        }
        if (! $sand) {
            $sand = Sand::first();
        }

        return [
            'brick_id' => $brick?->id,
            'cement_id' => $cement?->id,
            'sand_id' => $sand?->id,
        ];
    }
}
