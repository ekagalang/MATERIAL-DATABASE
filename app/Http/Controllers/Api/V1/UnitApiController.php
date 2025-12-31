<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\UnitRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Unit API Controller
 *
 * Handles units (satuan) CRUD operations
 * Extracted from UnitController (6 methods)
 */
class UnitApiController extends Controller
{
    public function __construct(
        private UnitRepository $repository
    ) {
    }

    /**
     * Get paginated units with optional filters
     *
     * GET /api/v1/units
     *
     * Query params:
     * - material_type: string (brick, cement, sand, cat)
     * - sort_by: string (code, name, package_weight, created_at)
     * - sort_direction: asc|desc
     * - per_page: int (default: 20)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'material_type' => $request->input('material_type'),
                'sort_by' => $request->input('sort_by'),
                'sort_direction' => $request->input('sort_direction'),
            ];

            $perPage = (int) $request->input('per_page', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            $units = $this->repository->getUnits($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $units->items(),
                'pagination' => [
                    'current_page' => $units->currentPage(),
                    'per_page' => $units->perPage(),
                    'total' => $units->total(),
                    'last_page' => $units->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Units Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve units',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available material types with labels
     *
     * GET /api/v1/units/material-types
     *
     * @return JsonResponse
     */
    public function getMaterialTypes(): JsonResponse
    {
        try {
            $materialTypes = $this->repository->getMaterialTypesWithLabels();

            return response()->json([
                'success' => true,
                'data' => $materialTypes,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Material Types Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve material types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get units grouped by material type
     *
     * GET /api/v1/units/grouped
     *
     * @return JsonResponse
     */
    public function getGrouped(): JsonResponse
    {
        try {
            $groupedUnits = $this->repository->getUnitsGroupedByMaterialType();

            return response()->json([
                'success' => true,
                'data' => $groupedUnits,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Grouped Units Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve grouped units',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new unit
     *
     * POST /api/v1/units
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:units,code',
                'material_types' => 'required|array',
                'material_types.*' => 'string',
                'name' => 'required|string|max:100',
                'package_weight' => 'required|numeric|min:0',
                'description' => 'nullable|string',
            ]);

            $unit = $this->repository->createUnit($validated);

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully',
                'data' => $unit,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Create Unit Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single unit by ID
     *
     * GET /api/v1/units/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $unit = $this->repository->findUnit($id);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $unit,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Unit Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update unit
     *
     * PUT /api/v1/units/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $unit = $this->repository->findUnit($id);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found',
                ], 404);
            }

            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:units,code,' . $id,
                'material_types' => 'required|array',
                'material_types.*' => 'string',
                'name' => 'required|string|max:100',
                'package_weight' => 'required|numeric|min:0',
                'description' => 'nullable|string',
            ]);

            $unit = $this->repository->updateUnit($unit, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully',
                'data' => $unit,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Unit Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete unit
     *
     * DELETE /api/v1/units/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $unit = $this->repository->findUnit($id);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found',
                ], 404);
            }

            $this->repository->deleteUnit($unit);

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Unit Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
