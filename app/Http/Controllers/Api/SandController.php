<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ApiSandUpsertRequest;
use App\Http\Resources\SandResource;
use App\Services\Material\SandService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SandController extends Controller
{
    use ApiResponse;

    protected $sandService;

    public function __construct(SandService $sandService)
    {
        $this->sandService = $sandService;
    }

    public function index(Request $request): JsonResponse
    {
        $sands = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->sandService->search($search, $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->sandService->paginateWithSort($perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(SandResource::collection($sands)->resource, 'Sands retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate((new ApiSandUpsertRequest())->rules());

        $sand = $this->sandService->create($validated, $request->file('photo'));

        return $this->createdResponse(new SandResource($sand), 'Sand created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $sand = $this->sandService->find($id);
        if (!$sand) {
            return $this->notFoundResponse('Sand not found');
        }

        return $this->successResponse(new SandResource($sand));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate((new ApiSandUpsertRequest())->rules());

        $sand = $this->sandService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(new SandResource($sand), 'Sand updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->sandService->delete($id);

        return $this->successResponse(null, 'Sand deleted successfully');
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $values = $this->sandService->getFieldValues(
            $field,
            MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('sand')),
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
        );

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $stores = $this->sandService->getAllStores(
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
            MaterialLookupQuery::rawMaterialType($request, 'sand'),
        );

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = MaterialLookupQuery::rawStore($request);
        if (!$store) {
            return response()->json([]);
        }
        $addresses = $this->sandService->getAddressesByStore(
            $store,
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
        );

        return response()->json($addresses);
    }
}
