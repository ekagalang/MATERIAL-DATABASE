<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CementResource;
use App\Services\Material\CementService;
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
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $cements = $search
            ? $this->cementService->search($search, $perPage, $sortBy, $sortDirection)
            : $this->cementService->paginateWithSort($perPage, $sortBy, $sortDirection);

        return $this->paginatedResponse(
            CementResource::collection($cements)->resource,
            'Cements retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cement_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $cement = $this->cementService->create($validated, $request->file('photo'));

        return $this->createdResponse(
            new CementResource($cement),
            'Cement created successfully'
        );
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
        $validated = $request->validate([
            'cement_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'short_address' => 'nullable|string|max:255',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $cement = $this->cementService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(
            new CementResource($cement),
            'Cement updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->cementService->delete($id);
        return $this->successResponse(
            null,
            'Cement deleted successfully'
        );
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 20);
        $filters = $request->only(['brand', 'store', 'package_unit']);

        $values = $this->cementService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = $request->get('limit', 20);
        $materialType = $request->get('material_type', 'cement');

        $stores = $this->cementService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = $request->get('store');
        $search = $request->get('search');
        $limit = $request->get('limit', 20);

        if (!$store) {
            return response()->json([]);
        }

        $addresses = $this->cementService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
