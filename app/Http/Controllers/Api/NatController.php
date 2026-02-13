<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\NatUpsertRequest;
use App\Http\Resources\NatResource;
use App\Services\Material\NatService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NatController extends Controller
{
    use ApiResponse;

    protected NatService $natService;

    public function __construct(NatService $natService)
    {
        $this->natService = $natService;
    }

    public function index(Request $request): JsonResponse
    {
        $nats = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->natService->search($search, (int) $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->natService->paginateWithSort((int) $perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(NatResource::collection($nats)->resource, 'Nats retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate((new NatUpsertRequest())->rules());

        $nat = $this->natService->create($validated, $request->file('photo'));

        return $this->createdResponse(new NatResource($nat), 'Nat created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $nat = $this->natService->find($id);

        if (!$nat) {
            return $this->notFoundResponse('Nat not found');
        }

        return $this->successResponse(new NatResource($nat));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate((new NatUpsertRequest())->rules());

        $nat = $this->natService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(new NatResource($nat), 'Nat updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->natService->delete($id);

        return $this->successResponse(null, 'Nat deleted successfully');
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::intLimit($request);
        $filters = MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('nat'));

        $values = $this->natService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::intLimit($request);
        $materialType = MaterialLookupQuery::rawMaterialType($request, 'nat');

        $stores = $this->natService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::intLimit($request);

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = $this->natService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
