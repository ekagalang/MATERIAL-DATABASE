<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ApiCatStoreRequest;
use App\Http\Requests\Material\ApiCatUpdateRequest;
use App\Http\Resources\CatResource;
use App\Services\Material\CatService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatController extends Controller
{
    use ApiResponse;

    protected $catService;

    public function __construct(CatService $catService)
    {
        $this->catService = $catService;
    }

    public function index(Request $request): JsonResponse
    {
        $cats = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->catService->search($search, $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->catService->paginateWithSort($perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(CatResource::collection($cats)->resource, 'Cats retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate((new ApiCatStoreRequest())->rules());

        $cat = $this->catService->create($validated, $request->file('photo'));

        return $this->createdResponse(new CatResource($cat), 'Cat created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $cat = $this->catService->find($id);
        if (!$cat) {
            return $this->notFoundResponse('Cat not found');
        }

        return $this->successResponse(new CatResource($cat));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate((new ApiCatUpdateRequest())->rules());

        $cat = $this->catService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(new CatResource($cat), 'Cat updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->catService->delete($id);

        return $this->successResponse(null, 'Cat deleted successfully');
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $values = $this->catService->getFieldValues(
            $field,
            MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('cat')),
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
        );

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $stores = $this->catService->getAllStores(
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
            MaterialLookupQuery::rawMaterialType($request, 'cat'),
        );

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = MaterialLookupQuery::rawStore($request);
        if (!$store) {
            return response()->json([]);
        }
        $addresses = $this->catService->getAddressesByStore(
            $store,
            MaterialLookupQuery::rawSearch($request),
            MaterialLookupQuery::rawLimit($request),
        );

        return response()->json($addresses);
    }
}
