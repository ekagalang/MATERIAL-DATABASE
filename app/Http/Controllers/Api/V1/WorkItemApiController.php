<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\WorkItemRepository;
use App\Services\FormulaRegistry;
use App\Services\WorkItem\WorkItemAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WorkItem API Controller
 *
 * Handles Item Pekerjaan (Work Items) CRUD and analytics
 * Extracted from WorkItemController (8 methods → 7 API endpoints)
 */
class WorkItemApiController extends Controller
{
    public function __construct(
        private WorkItemRepository $repository,
        private WorkItemAnalyticsService $analyticsService
    ) {
    }

    /**
     * Get paginated work items with optional analytics
     *
     * GET /api/v1/work-items
     *
     * Query params:
     * - search: string
     * - sort_by: string (default: created_at)
     * - sort_direction: asc|desc (default: desc)
     * - per_page: int (default: 20)
     * - include_analytics: bool (default: false)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_direction' => $request->input('sort_direction', 'desc'),
            ];

            $perPage = (int) $request->input('per_page', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            $workItems = $this->repository->getWorkItems($filters, $perPage);

            $response = [
                'success' => true,
                'data' => $workItems->items(),
                'pagination' => [
                    'current_page' => $workItems->currentPage(),
                    'per_page' => $workItems->perPage(),
                    'total' => $workItems->total(),
                    'last_page' => $workItems->lastPage(),
                ],
            ];

            // Include analytics if requested
            if ($request->boolean('include_analytics')) {
                $response['analytics'] = $this->analyticsService->generateAnalyticsForAllWorkTypes();
                $response['formulas'] = FormulaRegistry::all();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Get Work Items Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve work items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get analytics summary for all work types
     *
     * GET /api/v1/work-items/analytics
     *
     * @return JsonResponse
     */
    public function getAllAnalytics(): JsonResponse
    {
        try {
            $analytics = $this->analyticsService->generateAnalyticsForAllWorkTypes();
            $formulas = FormulaRegistry::all();

            return response()->json([
                'success' => true,
                'data' => [
                    'analytics' => $analytics,
                    'formulas' => $formulas,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get All Analytics Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed analytics for specific work type
     *
     * GET /api/v1/work-items/analytics/{code}
     *
     * @param string $code Work type code (brick_half, wall_plastering, etc.)
     * @return JsonResponse
     */
    public function getAnalyticsByCode(string $code): JsonResponse
    {
        try {
            // Validate formula exists
            $formulas = FormulaRegistry::all();
            $formula = collect($formulas)->firstWhere('code', $code);

            if (!$formula) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work type not found',
                ], 404);
            }

            $analytics = $this->analyticsService->generateDetailedAnalytics($code);
            $calculations = $this->repository->getCalculationsByWorkType($code);

            return response()->json([
                'success' => true,
                'data' => [
                    'formula' => $formula,
                    'analytics' => $analytics,
                    'calculations' => $calculations,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Analytics By Code Error:', [
                'code' => $code,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new work item
     *
     * POST /api/v1/work-items
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required|numeric|min:0',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string',
            ]);

            $workItem = $this->repository->createWorkItem($validated);

            return response()->json([
                'success' => true,
                'message' => 'Work item created successfully',
                'data' => $workItem,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Create Work Item Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create work item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single work item by ID
     *
     * GET /api/v1/work-items/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $workItem = $this->repository->findWorkItem($id);

            if (!$workItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work item not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $workItem,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Work Item Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve work item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update work item
     *
     * PUT /api/v1/work-items/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $workItem = $this->repository->findWorkItem($id);

            if (!$workItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work item not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'price' => 'required|numeric|min:0',
                'category' => 'nullable|string|max:100',
                'description' => 'nullable|string',
            ]);

            $this->repository->updateWorkItem($workItem, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Work item updated successfully',
                'data' => $workItem->fresh(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Work Item Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update work item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete work item
     *
     * DELETE /api/v1/work-items/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $workItem = $this->repository->findWorkItem($id);

            if (!$workItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work item not found',
                ], 404);
            }

            $this->repository->deleteWorkItem($workItem);

            return response()->json([
                'success' => true,
                'message' => 'Work item deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Work Item Error:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete work item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
