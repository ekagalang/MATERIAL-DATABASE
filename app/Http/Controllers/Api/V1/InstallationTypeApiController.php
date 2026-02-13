<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BrickInstallationType;
use Illuminate\Http\JsonResponse;

/**
 * Installation Type API Controller
 *
 * Provides configuration data for brick installation types
 * Used by frontend for dropdowns and calculations
 */
class InstallationTypeApiController extends Controller
{
    /**
     * Get all active installation types
     *
     * GET /api/v1/installation-types
     */
    public function index(): JsonResponse
    {
        try {
            $installationTypes = BrickInstallationType::where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $installationTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve installation types',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get single installation type by ID
     *
     * GET /api/v1/installation-types/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $installationType = BrickInstallationType::find($id);

            if (!$installationType) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Installation type not found',
                    ],
                    404,
                );
            }

            return response()->json([
                'success' => true,
                'data' => $installationType,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve installation type',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get default installation type
     *
     * GET /api/v1/installation-types/default
     */
    public function getDefault(): JsonResponse
    {
        try {
            $defaultType = BrickInstallationType::getDefault();

            if (!$defaultType) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'No default installation type found',
                    ],
                    404,
                );
            }

            return response()->json([
                'success' => true,
                'data' => $defaultType,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to retrieve default installation type',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
