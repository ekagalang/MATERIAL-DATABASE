<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use App\Models\Nat;
use App\Models\Sand;
use App\Services\FormulaRegistry;
use Illuminate\Http\Request;

class MaterialCalculationTraceController extends MaterialCalculationController
{
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
            'nat_id' => 'nullable|exists:nats,id',
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
