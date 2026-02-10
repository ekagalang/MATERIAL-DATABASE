<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MortarFormula;
use Illuminate\Http\JsonResponse;

/**
 * Mortar Formula API Controller
 *
 * Provides configuration data for mortar formulas (cement:sand ratios)
 * Used by frontend for dropdowns and calculations
 */
class MortarFormulaApiController extends Controller
{
    /**
     * Get all active mortar formulas
     *
     * GET /api/v1/mortar-formulas
     */
    public function index(): JsonResponse
    {
        try {
            $formulas = MortarFormula::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $formulas,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve mortar formulas',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get single mortar formula by ID
     *
     * GET /api/v1/mortar-formulas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $formula = MortarFormula::find($id);

            if (! $formula) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Mortar formula not found',
                    ],
                    404,
                );
            }

            return response()->json([
                'success' => true,
                'data' => $formula,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve mortar formula',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get default mortar formula
     *
     * GET /api/v1/mortar-formulas/default
     */
    public function getDefault(): JsonResponse
    {
        try {
            $defaultFormula = MortarFormula::getDefault();

            if (! $defaultFormula) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No default mortar formula found',
                    ],
                    404,
                );
            }

            return response()->json([
                'success' => true,
                'data' => $defaultFormula,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve default mortar formula',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
