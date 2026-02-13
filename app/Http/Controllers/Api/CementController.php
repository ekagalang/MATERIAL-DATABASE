<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ApiCementUpsertRequest;
use App\Http\Resources\CementResource;
use App\Services\Material\CementService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CementController extends Controller
{
    use ApiResponse;

    protected $cementService;

    public function __construct(CementService $cementService)
    {
        $this->cementService = $cementService;
    }

    public function index(Request $request): JsonResponse
    {
        $cements = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->cementService->search($search, $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->cementService->paginateWithSort($perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(
            CementResource::collection($cements)->resource,
            'Cements retrieved successfully',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate((new ApiCementUpsertRequest())->rules());

        $cement = $this->cementService->create($validated, $request->file('photo'));

        return $this->createdResponse(new CementResource($cement), 'Cement created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $cement = $this->cementService->find($id);

        if (!$cement) {
            return $this->notFoundResponse('Cement not found');
        }

        return $this->successResponse(new CementResource($cement));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate((new ApiCementUpsertRequest())->rules());

        $cement = $this->cementService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(new CementResource($cement), 'Cement updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->cementService->delete($id);

        return $this->successResponse(null, 'Cement deleted successfully');
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        $filters = MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('cement'));

        $values = $this->cementService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        $materialType = MaterialLookupQuery::rawMaterialType($request, 'cement');

        $stores = $this->cementService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = MaterialLookupQuery::rawStore($request);
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);

        if (!$store) {
            return response()->json([]);
        }

        $addresses = $this->cementService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
