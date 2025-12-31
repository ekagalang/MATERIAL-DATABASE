<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;
use App\Repositories\RecommendationRepository;
use App\Services\FormulaRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recommendation API Controller
 *
 * Handles recommended material combinations for different work types
 * Extracted from RecommendedCombinationController (2 methods)
 */
class RecommendationApiController extends Controller
{
    public function __construct(
        private RecommendationRepository $repository
    ) {
    }

    /**
     * Get all recommendations grouped by work_type
     *
     * GET /api/v1/recommendations
     *
     * Query params:
     * - include_materials: bool (default: false) - Include available materials for dropdowns
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $groupedRecommendations = $this->repository->getRecommendationsGroupedByWorkType();

            $response = [
                'success' => true,
                'data' => $groupedRecommendations,
            ];

            // Include available materials if requested (for dropdowns in UI)
            if ($request->boolean('include_materials')) {
                $response['materials'] = [
                    'bricks' => Brick::orderBy('brand')->get(),
                    'cements' => Cement::orderBy('brand')->get(),
                    'sands' => Sand::orderBy('brand')->get(),
                ];
                $response['formulas'] = FormulaRegistry::all();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Get Recommendations Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update recommendations
     * Deletes all existing recommendations and inserts new ones
     *
     * POST /api/v1/recommendations/bulk-update
     *
     * Request body:
     * {
     *   "recommendations": [
     *     {
     *       "work_type": "brick_half",
     *       "brick_id": 1,
     *       "cement_id": 2,
     *       "sand_id": 3
     *     },
     *     ...
     *   ]
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recommendations' => 'nullable|array',
                'recommendations.*.work_type' => 'required|string',
                'recommendations.*.brick_id' => 'nullable|exists:bricks,id',
                'recommendations.*.cement_id' => 'nullable|exists:cements,id',
                'recommendations.*.sand_id' => 'nullable|exists:sands,id',
            ]);

            $recommendations = $validated['recommendations'] ?? [];

            $this->repository->bulkUpdateRecommendations($recommendations);

            // Return updated recommendations
            $groupedRecommendations = $this->repository->getRecommendationsGroupedByWorkType();

            return response()->json([
                'success' => true,
                'message' => 'Recommendations updated successfully',
                'data' => $groupedRecommendations,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Bulk Update Recommendations Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update recommendations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
