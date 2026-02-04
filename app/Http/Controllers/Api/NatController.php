<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NatResource;
use App\Services\Material\NatService;
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
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $nats = $search
            ? $this->natService->search($search, $perPage, $sortBy, $sortDirection)
            : $this->natService->paginateWithSort($perPage, $sortBy, $sortDirection);

        return $this->paginatedResponse(
            NatResource::collection($nats)->resource,
            'Nats retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'package_volume' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'store_location_id' => 'nullable|exists:store_locations,id',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $nat = $this->natService->create($validated, $request->file('photo'));

        return $this->createdResponse(
            new NatResource($nat),
            'Nat created successfully'
        );
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
        $validated = $request->validate([
            'nat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'brand' => 'nullable|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'package_unit' => 'nullable|string|max:20',
            'package_weight_gross' => 'nullable|numeric|min:0',
            'package_weight_net' => 'nullable|numeric|min:0',
            'package_volume' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'store_location_id' => 'nullable|exists:store_locations,id',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $nat = $this->natService->update($id, $validated, $request->file('photo'));

        return $this->successResponse(
            new NatResource($nat),
            'Nat updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->natService->delete($id);

        return $this->successResponse(
            null,
            'Nat deleted successfully'
        );
    }

    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = (int) $request->get('limit', 20);
        $filters = $request->only(['brand', 'store', 'package_unit']);

        $values = $this->natService->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    public function getAllStores(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $limit = (int) $request->get('limit', 20);
        $materialType = $request->get('material_type', 'nat');

        $stores = $this->natService->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request): JsonResponse
    {
        $store = (string) $request->get('store');
        $search = $request->get('search');
        $limit = (int) $request->get('limit', 20);

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = $this->natService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
