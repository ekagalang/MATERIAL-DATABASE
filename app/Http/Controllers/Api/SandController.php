<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SandResource;
use App\Services\Material\SandService;
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

    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $sands = $search
            ? $this->sandService->search($search, $perPage, $sortBy, $sortDirection)
            : $this->sandService->paginateWithSort($perPage, $sortBy, $sortDirection);

        return SandResource::collection($sands);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sand_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
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
        ]);

        $sand = $this->sandService->create($validated, $request->file('photo'));
        return $this->createdResponse(new SandResource($sand), 'Sand created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $sand = $this->sandService->find($id);
        if (!$sand) return $this->notFoundResponse('Sand not found');
        return $this->successResponse(new SandResource($sand));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'sand_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
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
        ]);

        $sand = $this->sandService->update($id, $validated, $request->file('photo'));
        return $this->successResponse(new SandResource($sand), 'Sand updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->sandService->delete($id);
        return $this->noContentResponse();
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $values = $this->sandService->getFieldValues($field, $request->only(['brand', 'store']), $request->get('search'), $request->get('limit', 20));
        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $stores = $this->sandService->getAllStores($request->get('search'), $request->get('limit', 20), $request->get('material_type', 'sand'));
        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = $request->get('store');
        if (!$store) return response()->json([]);
        $addresses = $this->sandService->getAddressesByStore($store, $request->get('search'), $request->get('limit', 20));
        return response()->json($addresses);
    }
}
