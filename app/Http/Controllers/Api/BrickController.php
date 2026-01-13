<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrickResource;
use App\Services\Material\BrickService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Brick Controller
 *
 * Handle HTTP requests untuk Brick API
 * Controller ini HANYA koordinasi - semua business logic di BrickService
 */
class BrickController extends Controller
{
    use ApiResponse;

    /**
     * @var BrickService
     */
    protected $brickService;

    /**
     * BrickController constructor
     *
     * @param BrickService $brickService
     */
    public function __construct(BrickService $brickService)
    {
        $this->brickService = $brickService;
    }

    /**
     * Display a listing of bricks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $bricks = $search
            ? $this->brickService->search($search, $perPage, $sortBy, $sortDirection)
            : $this->brickService->paginateWithSort($perPage, $sortBy, $sortDirection);

        return $this->paginatedResponse(
            BrickResource::collection($bricks)->resource,
            'Bricks retrieved successfully'
        );
    }

    /**
     * Store a newly created brick
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validation (akan dipindah ke FormRequest nanti)
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'price_per_piece' => 'nullable|numeric|min:0',
        ]);

        $brick = $this->brickService->create(
            $validated,
            $request->file('photo')
        );

        return $this->createdResponse(
            new BrickResource($brick),
            'Brick created successfully'
        );
    }

    /**
     * Display the specified brick
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $brick = $this->brickService->find($id);

        if (!$brick) {
            return $this->notFoundResponse('Brick not found');
        }

        return $this->successResponse(new BrickResource($brick));
    }

    /**
     * Update the specified brick
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Validation (akan dipindah ke FormRequest nanti)
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'price_per_piece' => 'nullable|numeric|min:0',
        ]);

        $brick = $this->brickService->update(
            $id,
            $validated,
            $request->file('photo')
        );

        return $this->successResponse(
            new BrickResource($brick),
            'Brick updated successfully'
        );
    }

    /**
     * Remove the specified brick
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->brickService->delete($id);

        return $this->successResponse(
            null,
            'Brick deleted successfully'
        );
    }

    /**
     * Get field values untuk autocomplete
     *
     * @param string $field
     * @param Request $request
     * @return JsonResponse
     */
    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 20);
        $filters = $request->only(['brand', 'store']);

        $values = $this->brickService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    /**
     * Get all stores
     * Supports material_type parameter for cross-material queries
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllStores(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 20);
        $materialType = $request->get('material_type', 'brick'); // 'brick' or 'all'

        $stores = $this->brickService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    /**
     * Get addresses by store
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = $request->get('store');
        $search = $request->get('search');
        $limit = $request->get('limit', 20);

        if (!$store) {
            return response()->json([]);
        }

        $addresses = $this->brickService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
