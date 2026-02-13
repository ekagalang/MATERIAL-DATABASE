<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ApiBrickUpsertRequest;
use App\Http\Resources\BrickResource;
use App\Services\Material\BrickService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
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
     */
    public function __construct(BrickService $brickService)
    {
        $this->brickService = $brickService;
    }

    /**
     * Display a listing of bricks
     */
    public function index(Request $request): JsonResponse
    {
        $bricks = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->brickService->search($search, $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->brickService->paginateWithSort($perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(BrickResource::collection($bricks)->resource, 'Bricks retrieved successfully');
    }

    /**
     * Store a newly created brick
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate((new ApiBrickUpsertRequest())->rules());

        $brick = $this->brickService->create($validated, $request->file('photo'));

        return $this->createdResponse(new BrickResource($brick), 'Brick created successfully');
    }

    /**
     * Display the specified brick
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
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate((new ApiBrickUpsertRequest())->rules());

        $brick = $this->brickService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(new BrickResource($brick), 'Brick updated successfully');
    }

    /**
     * Remove the specified brick
     */
    public function destroy(int $id): JsonResponse
    {
        $this->brickService->delete($id);

        return $this->successResponse(null, 'Brick deleted successfully');
    }

    /**
     * Get field values untuk autocomplete
     */
    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        $filters = MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('brick'));

        $values = $this->brickService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    /**
     * Get all stores
     * Supports material_type parameter for cross-material queries
     */
    public function getAllStores(Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        $materialType = MaterialLookupQuery::rawMaterialType($request, 'brick'); // 'brick' or 'all'

        $stores = $this->brickService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    /**
     * Get addresses by store
     */
    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = MaterialLookupQuery::rawStore($request);
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);

        if (!$store) {
            return response()->json([]);
        }

        $addresses = $this->brickService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
