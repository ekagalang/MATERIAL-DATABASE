<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PriceAnalysisController extends Controller
{
    public function index()
    {
        $formulas = FormulaRegistry::all();
        
        $inputs = [
            'wall_length' => 1,
            'wall_height' => 1,
            'mortar_thickness' => 2.0,
            'formula_code' => null,
        ];

        return view('dev.price_analysis.index', compact('formulas', 'inputs'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'formula_code' => 'required',
            'mortar_thickness' => 'required|numeric|min:0.1',
            'wall_length' => 'required|numeric|min:0.1',
            'wall_height' => 'required|numeric|min:0.1',
        ]);

        // 1. SETUP PARAMETER
        $wallLength = $request->wall_length;
        $wallHeight = $request->wall_height;
        $wallArea = $wallLength * $wallHeight;
        $mortarThickness = $request->mortar_thickness;

        // 2. LOAD DEPENDENSI
        $selectedFormula = FormulaRegistry::instance($request->formula_code);
        
        // Auto-Matching Installation Type
        $formulaName = $selectedFormula::getName();
        $installationType = BrickInstallationType::where('name', 'LIKE', "%{$formulaName}%")->first();

        if (!$installationType) {
            if (str_contains(strtolower($formulaName), '1/2') || str_contains(strtolower($formulaName), 'half')) {
                $installationType = BrickInstallationType::where('name', 'like', '%1/2%')->orWhere('name', 'like', '%half%')->first();
            } else {
                $installationType = BrickInstallationType::where('name', 'like', '%1%')->where('name', 'not like', '%1/2%')->first();
            }
        }
        if (!$installationType) {
            $installationType = BrickInstallationType::first();
        }

        // Default Mortar (1:3)
        $defaultMortar = MortarFormula::where('cement_ratio', 1)->where('sand_ratio', 3)->first() 
                         ?? MortarFormula::first();

        // 3. PARAMETER BASE
        $paramsBase = [
            'wall_length' => $wallLength,
            'wall_height' => $wallHeight,
            'mortar_thickness' => $mortarThickness,
            'mortar_formula_id' => $defaultMortar->id,
            'installation_type_id' => $installationType->id,
        ];

        // LOAD MATERIAL
        $bricks = Brick::orderBy('price_per_piece', 'asc')->get();
        $cements = Cement::orderBy('package_price', 'asc')->get();
        $sands = Sand::all();

        $refBrick = $bricks->first();
        $refCement = $cements->first();
        $refSand = $sands->first();

        // --- 1. ANALISA BATA ---
        $brickAnalysis = [];
        foreach ($bricks as $brick) {
            $params = array_merge($paramsBase, [
                'brick_id' => $brick->id,
                'cement_id' => $refCement->id,
                'sand_id' => $refSand->id
            ]);

            $traceResult = $this->runFormulaTraceFull($selectedFormula, $params);
            
            if ($traceResult) {
                $result = $traceResult['final_result'];
                $tebal_cm = $mortarThickness;
                $luas_pasangan_cm2 = ($brick->dimension_length + $tebal_cm) * ($brick->dimension_height + $tebal_cm);

                $brickAnalysis[] = [
                    'material_name' => $brick->material_name,
                    'type' => $brick->type ?? '-',
                    'brand' => $brick->brand,
                    'dimensions' => "P:{$brick->dimension_length} L:{$brick->dimension_width} T:{$brick->dimension_height}",
                    'store' => Str::limit($brick->store ?? 'Tidak tersedia', 15),
                    'address' => Str::limit($brick->address ?? '-', 15),
                    'price_per_piece' => $brick->price_per_piece,
                    'mortar_thickness' => $mortarThickness,
                    'area_per_brick' => number_format($luas_pasangan_cm2, 0) . ' cm²',
                    'total_qty_job' => $result['total_bricks'], 
                    'total_price_job' => $result['total_brick_price'],
                ];
            }
        }
        $brickAnalysis = collect($brickAnalysis)->sortBy('total_price_job')->values();


        // --- 2. ANALISA SEMEN ---
        $cementAnalysis = [];
        foreach ($cements as $cement) {
            $params = array_merge($paramsBase, [
                'brick_id' => $refBrick->id,
                'cement_id' => $cement->id,
                'sand_id' => $refSand->id
            ]);

            $traceResult = $this->runFormulaTraceFull($selectedFormula, $params);
            
            if ($traceResult) {
                $result = $traceResult['final_result'];
                $totalMortarVol = $this->extractTotalMortarVolume($traceResult['steps']);
                $totalSacks = $result['cement_sak'];
                $yieldMortarPerSack = $totalSacks > 0 ? ($totalMortarVol / $totalSacks) : 0;

                $cementAnalysis[] = [
                    'material_name' => $cement->cement_name,
                    'type' => $cement->type ?? '-',
                    'brand' => $cement->brand,
                    'packaging' => $cement->package_unit ?? '-', // Tambahan
                    'dimensions' => "{$cement->package_weight_net} Kg",
                    'store' => Str::limit($cement->store ?? 'Tidak tersedia', 15),
                    'address' => Str::limit($cement->address ?? '-', 15),
                    'yield_mortar_per_unit' => $yieldMortarPerSack, 
                ];
            }
        }
        $cementAnalysis = collect($cementAnalysis)->sortByDesc('yield_mortar_per_unit')->values();


        // --- 3. ANALISA PASIR ---
        $sandAnalysis = [];
        foreach ($sands as $sand) {
            $params = array_merge($paramsBase, [
                'brick_id' => $refBrick->id,
                'cement_id' => $refCement->id,
                'sand_id' => $sand->id
            ]);

            $traceResult = $this->runFormulaTraceFull($selectedFormula, $params);
            
            if ($traceResult) {
                $result = $traceResult['final_result'];
                $totalMortarVol = $this->extractTotalMortarVolume($traceResult['steps']);
                $totalSandM3 = $result['sand_m3'];
                $yieldMortarPerUnit = $totalSandM3 > 0 ? ($totalMortarVol / $totalSandM3) : 0;

                // Tentukan Label Dimensi
                $dimensiPasir = '-';
                if ($sand->package_weight_net > 0) {
                    $dimensiPasir = "{$sand->package_weight_net} Kg";
                } elseif ($sand->package_volume > 0) {
                    $dimensiPasir = "{$sand->package_volume} m³";
                } elseif ($sand->dimension_length > 0) {
                    $dimensiPasir = "P:{$sand->dimension_length} L:{$sand->dimension_width} T:{$sand->dimension_height}";
                }

                $sandAnalysis[] = [
                    'material_name' => $sand->sand_name,
                    'type' => $sand->type ?? '-',
                    'brand' => $sand->brand ?? 'No Brand',
                    'packaging' => $sand->package_unit ?? '-', // Tambahan
                    'dimensions' => $dimensiPasir,
                    'store' => Str::limit($sand->store ?? 'Tidak tersedia', 15),
                    'address' => Str::limit($sand->address ?? '-', 15),
                    'yield_mortar_per_unit' => $yieldMortarPerUnit, 
                ];
            }
        }
        $sandAnalysis = collect($sandAnalysis)->sortByDesc('yield_mortar_per_unit')->values();


        // --- 4. ANALISA AIR ---
        $waterAnalysis = [];
        foreach ($bricks as $brick) {
            $params = array_merge($paramsBase, [
                'brick_id' => $brick->id,
                'cement_id' => $refCement->id,
                'sand_id' => $refSand->id
            ]);
            $traceResult = $this->runFormulaTraceFull($selectedFormula, $params);
            if ($traceResult) {
                $result = $traceResult['final_result'];
                $waterAnalysis[] = [
                    'material_ref' => "Air (Ref: {$brick->brand})",
                    'mortar_thickness' => $mortarThickness,
                    'qty_per_m2' => $result['water_liters'] / $wallArea,
                    'total_qty_job' => $result['water_liters'],
                ];
            }
        }

        $formulas = FormulaRegistry::all();
        $inputs = $request->all();
        $inputs['wall_area'] = $wallArea;
        $inputs['mortar_thickness'] = $mortarThickness;
        $inputs['formula_name'] = $selectedFormula::getName();
        $inputs['mortar_name'] = "1 Semen : {$defaultMortar->sand_ratio} Pasir";

        return view('dev.price_analysis.index', compact(
            'brickAnalysis', 
            'cementAnalysis', 
            'sandAnalysis',
            'waterAnalysis',
            'formulas',
            'inputs'
        ));
    }

    private function runFormulaTraceFull($formulaInstance, $params)
    {
        try {
            return $formulaInstance->trace($params);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractTotalMortarVolume($steps)
    {
        foreach ($steps as $step) {
            if (isset($step['title']) && $step['title'] === 'Total Volume Mortar' && isset($step['step']) && $step['step'] === '7c') {
                if (isset($step['calculations']['Total Volume Mortar'])) {
                    $val = $step['calculations']['Total Volume Mortar'];
                    return (float) filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }
            }
        }
        return 0;
    }
}