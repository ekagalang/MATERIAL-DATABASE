<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;
use App\Services\BrickCalculationModes;
use App\Services\BrickCalculationTracer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrickCalculationController extends Controller
{
    /**
     * Display a listing of calculations
     */
    public function index(Request $request)
    {
        $query = BrickCalculation::with([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ]);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filter by installation type
        if ($request->has('installation_type') && $request->installation_type != '') {
            $query->where('installation_type_id', $request->installation_type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $calculations = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        // Get filter options
        $installationTypes = BrickInstallationType::getActive();

        return view('brick_calculations.index', compact('calculations', 'installationTypes'));
    }

    /**
     * Show the form for creating a new calculation
     */
    public function create()
    {
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        // Get default values
        $defaultInstallationType = BrickInstallationType::getDefault();
        $defaultMortarFormula = MortarFormula::getDefault();

        return view('brick_calculations.create', compact(
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
            'use_custom_ratio' => 'nullable|boolean',
            'custom_cement_ratio' => 'required_if:use_custom_ratio,1|nullable|numeric|min:0.1',
            'custom_sand_ratio' => 'required_if:use_custom_ratio,1|nullable|numeric|min:0.1',
            'custom_water_ratio' => 'nullable|numeric|min:0',
        ], [
            'wall_length.required' => 'Panjang dinding harus diisi',
            'wall_length.min' => 'Panjang dinding minimal 0.01 meter',
            'wall_height.required' => 'Tinggi dinding harus diisi',
            'wall_height.min' => 'Tinggi dinding minimal 0.01 meter',
            'installation_type_id.required' => 'Jenis pemasangan harus dipilih',
            'mortar_thickness.required' => 'Tebal adukan harus diisi',
            'mortar_thickness.min' => 'Tebal adukan minimal 0.1 cm',
            'mortar_thickness.max' => 'Tebal adukan maksimal 10 cm',
            'mortar_formula_id.required' => 'Formula adukan harus dipilih',
            'custom_cement_ratio.required_if' => 'Rasio semen harus diisi jika menggunakan custom ratio',
            'custom_sand_ratio.required_if' => 'Rasio pasir harus diisi jika menggunakan custom ratio',
        ]);

        try {
            DB::beginTransaction();

            // Perform calculation
            $calculation = BrickCalculation::performCalculation($request->all());

            // Save to database
            $calculation->save();

            DB::commit();

            return redirect()
                ->route('brick-calculations.show', $calculation)
                ->with('success', 'Perhitungan berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Display the specified calculation
     */
    public function show(BrickCalculation $brickCalculation)
    {
        $brickCalculation->load([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ]);

        $summary = $brickCalculation->getSummary();

        return view('brick_calculations.show', compact('brickCalculation', 'summary'));
    }

    /**
     * Show the form for editing
     */
    public function edit(BrickCalculation $brickCalculation)
    {
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('brand')->get();
        $sands = Sand::orderBy('brand')->get();

        return view('brick_calculations.edit', compact(
            'brickCalculation',
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
    public function update(Request $request, BrickCalculation $brickCalculation)
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
            $brickCalculation->fill($newCalculation->toArray());
            $brickCalculation->save();

            DB::commit();

            return redirect()
                ->route('brick-calculations.show', $brickCalculation)
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
    public function destroy(BrickCalculation $brickCalculation)
    {
        try {
            $brickCalculation->delete();

            return redirect()
                ->route('brick-calculations.index')
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
    public function exportPdf(BrickCalculation $brickCalculation)
    {
        // TODO: Implement PDF export in Phase 6
        return redirect()->back()->with('info', 'Fitur export PDF akan ditambahkan di fase berikutnya');
    }

    /**
     * Dashboard/Statistics
     */
    public function dashboard()
    {
        $totalCalculations = BrickCalculation::count();
        $totalCost = BrickCalculation::sum('total_material_cost');

        $recentCalculations = BrickCalculation::with(['installationType', 'mortarFormula'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $calculationsByType = BrickCalculation::select(
            'installation_type_id',
            DB::raw('count(*) as count'),
            DB::raw('sum(total_material_cost) as total_cost')
        )
            ->groupBy('installation_type_id')
            ->with('installationType')
            ->get();

        return view('brick_calculations.dashboard', compact(
            'totalCalculations',
            'totalCost',
            'recentCalculations',
            'calculationsByType'
        ));
    }

    /**
     * API: Compare 3 calculation modes
     * Mode 1: Professional (Volume Mortar)
     * Mode 2: Field (Package Engineering from rumus 2.xlsx)
     * Mode 3: Simple (Package Basic - Corrected)
     */
    public function compareThreeModes(Request $request)
    {
        $request->validate([
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.1|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'custom_cement_ratio' => 'nullable|numeric|min:1',
            'custom_sand_ratio' => 'nullable|numeric|min:1',
        ]);

        try {
            $comparison = BrickCalculationModes::calculateAllModes($request->all());

            return response()->json([
                'success' => true,
                'data' => $comparison,
                'explanation' => [
                    'mode_1' => 'Professional: Berbasis volume mortar dengan data empiris verified (sistem saat ini)',
                    'mode_2' => 'Field: Berbasis kemasan dengan engineering factors dari rumus 2.xlsx (shrinkage 15%, water 30%)',
                    'mode_3' => 'Simple: Berbasis kemasan sederhana dengan volume sak terkoreksi',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * View: Comparison page for 3 modes
     */
    public function comparisonView()
    {
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();

        return view('brick_calculations.comparison', compact(
            'installationTypes',
            'mortarFormulas',
            'bricks'
        ));
    }

    /**
     * View: Trace page - step by step calculation
     */
    public function traceView()
    {
        $installationTypes = BrickInstallationType::getActive();
        $mortarFormulas = MortarFormula::getActive();
        $bricks = Brick::orderBy('brand')->get();
        $cements = Cement::orderBy('cement_name')->get();
        $sands = Sand::orderBy('sand_name')->get();

        return view('brick_calculations.trace', compact(
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
        ]);

        try {
            $trace = BrickCalculationTracer::traceProfessionalMode($request->all());

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
}
