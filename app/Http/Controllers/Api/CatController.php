<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CatResource;
use App\Services\Material\CatService;
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
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $cats = $search
            ? $this->catService->search($search, $perPage, $sortBy, $sortDirection)
            : $this->catService->paginateWithSort($perPage, $sortBy, $sortDirection);

        return $this->paginatedResponse(
            CatResource::collection($cats)->resource,
            'Cats retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:100',
            'color_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'volume_unit' => 'nullable|string|max:20',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $cat = $this->catService->create($validated, $request->file('photo'));
        return $this->createdResponse(new CatResource($cat), 'Cat created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $cat = $this->catService->find($id);
        if (!$cat) return $this->notFoundResponse('Cat not found');
        return $this->successResponse(new CatResource($cat));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'cat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:100',
            'color_name' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'volume_unit' => 'nullable|string|max:20',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $cat = $this->catService->update($id, $validated, $request->file('photo'));
        return $this->successResponse(new CatResource($cat), 'Cat updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->catService->delete($id);
        return $this->successResponse(
            null,
            'Cat deleted successfully'
        );
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $values = $this->catService->getFieldValues($field, $request->only(['brand', 'store', 'package_unit']), $request->get('search'), $request->get('limit', 20));
        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $stores = $this->catService->getAllStores($request->get('search'), $request->get('limit', 20), $request->get('material_type', 'cat'));
        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = $request->get('store');
        if (!$store) return response()->json([]);
        $addresses = $this->catService->getAddressesByStore($store, $request->get('search'), $request->get('limit', 20));
        return response()->json($addresses);
    }
}
