<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalculationWriteApiController extends CalculationApiController
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate($this->previewAndStoreValidationRules());

            $calculation = $this->orchestrationService->store($validated);
            $calculation->load([
                'installationType',
                'mortarFormula',
                'brick',
                'cement',
                'sand',
                'cat',
                'ceramic',
                'nat',
            ]);

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Calculation saved successfully',
                    'data' => [
                        'id' => $calculation->id,
                        'calculation' => $calculation,
                        'summary' => $calculation->getSummary(),
                    ],
                ],
                201,
            );
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
            Log::error('Store Calculation Error:', [
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

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Find existing calculation
            $existingCalculation = $this->repository->findCalculation($id);

            if (!$existingCalculation) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Calculation not found',
                    ],
                    404,
                );
            }

            $validated = $request->validate([
                ...$this->updateValidationRules(),
            ]);

            // Add work_type from existing if not provided
            if (!isset($validated['work_type'])) {
                $params = $existingCalculation->calculation_params;
                $validated['work_type'] = $params['work_type'] ?? 'brick_half';
            }

            // Perform new calculation
            $newCalculation = $this->orchestrationService->calculateSingle($validated, false);

            // Update existing record
            $existingCalculation->fill($newCalculation->toArray());
            $existingCalculation->save();
            $existingCalculation->load([
                'installationType',
                'mortarFormula',
                'brick',
                'cement',
                'sand',
                'cat',
                'ceramic',
                'nat',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Calculation updated successfully',
                'data' => [
                    'calculation' => $existingCalculation,
                    'summary' => $existingCalculation->getSummary(),
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
            Log::error('Update Calculation Error:', [
                'id' => $id,
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

    public function destroy(int $id): JsonResponse
    {
        try {
            $calculation = $this->repository->findCalculation($id);

            if (!$calculation) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Calculation not found',
                    ],
                    404,
                );
            }

            $calculation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Calculation deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Calculation Error:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to delete calculation: ' . $e->getMessage(),
                ],
                422,
            );
        }
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

    private function updateValidationRules(): array
    {
        return [
            'work_type' => 'nullable|string',
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
            'plaster_sides' => 'nullable|integer|min:1|max:2',
            'skim_sides' => 'nullable|integer|min:1|max:2',
        ];
    }
}
