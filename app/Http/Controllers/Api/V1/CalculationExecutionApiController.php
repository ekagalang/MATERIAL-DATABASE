<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FormulaRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalculationExecutionApiController extends CalculationApiController
{
    public function calculate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->calculateValidationRules());

            $result = $this->orchestrationService->generateCombinations($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Calculate Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function preview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->previewAndStoreValidationRules());

            $calculation = $this->orchestrationService->preview($validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Preview Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function compare(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->compareValidationRules());

            $result = $this->orchestrationService->compareBricks($validated);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Compare Bricks Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function compareInstallationTypes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->compareInstallationTypesValidationRules());

            $installationTypes = $this->repository->getInstallationTypesAndFormulas()['installationTypes'];
            $comparisons = [];

            foreach ($installationTypes as $type) {
                $params = array_merge($validated, [
                    'installation_type_id' => $type->id,
                    'work_type' => 'brick_half', // Default, will be overridden by formula
                ]);

                try {
                    $calculation = $this->orchestrationService->calculateSingle($params, false);
                    $calculation->load(['installationType', 'mortarFormula']);

                    $comparisons[] = [
                        'installation_type' => $type->name,
                        'installation_type_code' => $type->code,
                        'brick_quantity' => $calculation->brick_quantity,
                        'mortar_volume' => $calculation->mortar_volume,
                        'cement_40kg' => $calculation->cement_quantity_40kg,
                        'cement_50kg' => $calculation->cement_quantity_50kg,
                        'sand_m3' => $calculation->sand_m3,
                        'water_liters' => $calculation->water_liters,
                        'total_cost' => $calculation->total_material_cost,
                    ];
                } catch (\Exception $e) {
                    Log::error('Compare Installation Type Error:', [
                        'type_id' => $type->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Skip this type if calculation fails
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $comparisons,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Compare Installation Types Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    public function trace(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->traceValidationRules());

            // Get formula instance from registry
            $formulaCode = $validated['formula_code'];
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
            if (!$formula->validate($validated)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Parameter tidak valid untuk formula ini',
                    ],
                    422,
                );
            }

            // Execute trace calculation
            $trace = $formula->trace($validated);

            return response()->json([
                'success' => true,
                'data' => $trace,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors(),
                ],
                422,
            );
        } catch (\Exception $e) {
            Log::error('Trace Calculation Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ],
                422,
            );
        }
    }

    private function calculateValidationRules(): array
    {
        return [
            'work_type' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_ids' => 'nullable|array',
            'brick_ids.*' => 'exists:bricks,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'price_filters' => 'nullable|array',
            'price_filters.*' => 'in:best,common,cheapest,medium,expensive,custom,all',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
            'plaster_sides' => 'nullable|integer|min:1|max:2',
            'skim_sides' => 'nullable|integer|min:1|max:2',
        ];
    }

    private function previewAndStoreValidationRules(): array
    {
        return [
            'work_type' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'required|exists:bricks,id',
            'cement_id' => 'required|exists:cements,id',
            'sand_id' => 'required|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
            'plaster_sides' => 'nullable|integer|min:1|max:2',
            'skim_sides' => 'nullable|integer|min:1|max:2',
            'project_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    private function compareValidationRules(): array
    {
        return [
            'brick_ids' => 'required|array|min:2',
            'brick_ids.*' => 'exists:bricks,id',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'work_type' => 'nullable|string',
            'layer_count' => 'nullable|integer|min:1',
        ];
    }

    private function traceValidationRules(): array
    {
        return [
            'formula_code' => 'required|string',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:formula_code,brick_rollag|numeric|min:0.01',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'custom_cement_ratio' => 'nullable|numeric|min:1',
            'custom_sand_ratio' => 'nullable|numeric|min:1',
            'has_additional_layer' => 'nullable|boolean',
            'layer_count' => 'nullable|integer|min:1',
        ];
    }

    private function compareInstallationTypesValidationRules(): array
    {
        return [
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
        ];
    }
}
