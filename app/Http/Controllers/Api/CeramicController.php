<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Material\ApiCeramicStoreRequest;
use App\Http\Resources\CeramicResource;
use App\Services\Material\CeramicService;
use App\Support\Material\MaterialApiIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API Ceramic Controller
 *
 * Handle HTTP requests untuk Ceramic API
 * Business logic didelegasikan ke CeramicService
 */
class CeramicController extends Controller
{
    use ApiResponse;

    /**
     * @var CeramicService
     */
    protected $ceramicService;

    /**
     * CeramicController constructor
     *
     * @param CeramicService $ceramicService
     */
    public function __construct(CeramicService $ceramicService)
    {
        $this->ceramicService = $ceramicService;
    }

    /**
     * Display a listing of ceramics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $ceramics = MaterialApiIndexQuery::execute(
            $request,
            fn($search, $perPage, $sortBy, $sortDirection) =>
            $this->ceramicService->search($search, $perPage, $sortBy, $sortDirection),
            fn($perPage, $sortBy, $sortDirection) =>
            $this->ceramicService->paginateWithSort($perPage, $sortBy, $sortDirection),
        );

        return $this->paginatedResponse(
            CeramicResource::collection($ceramics)->resource,
            'Data Keramik berhasil diambil',
        );
    }

    /**
     * Store a newly created ceramic
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate((new ApiCeramicStoreRequest())->rules());

        try {
            // Gabungkan semua input, termasuk file photo
            $allData = $request->all();
            $photo = $request->file('photo');

            $ceramic = $this->ceramicService->create($allData, $photo);

            return $this->successResponse(
                new CeramicResource($ceramic),
                'Data Keramik berhasil ditambahkan',
                Response::HTTP_CREATED,
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified ceramic
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Gunakan findOrFail dari repository via service (jika ada method getById)
            // Atau akses repository langsung jika service mengeksposnya
            // Di sini kita pakai cara manual find di model atau tambahkan method find di Service
            $ceramic = \App\Models\Ceramic::findOrFail($id);

            return $this->successResponse(new CeramicResource($ceramic), 'Detail Keramik berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Data Keramik tidak ditemukan', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified ceramic
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $allData = $request->all();
            $photo = $request->file('photo');

            $ceramic = $this->ceramicService->update($id, $allData, $photo);

            return $this->successResponse(new CeramicResource($ceramic), 'Data Keramik berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified ceramic
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->ceramicService->delete($id);
            return $this->successResponse(null, 'Data Keramik berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // =========================================================================
    // HELPER METHODS (Autocomplete & Filters) - Sesuai BrickController
    // =========================================================================

    /**
     * Get unique values for a specific field (Autocomplete)
     *
     * @param string $field
     * @param Request $request
     * @return JsonResponse
     */
    public function getFieldValues(string $field, Request $request): JsonResponse
    {
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        // Filter spesifik yang mungkin dikirim frontend
        $filters = MaterialLookupQuery::onlyFilters($request, MaterialLookupSpec::apiFilterKeys('ceramic'));

        $values = $this->ceramicService->getFieldValues($field, $filters, $search, $limit);

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
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);
        $materialType = MaterialLookupQuery::rawMaterialType($request, 'ceramic'); // Default 'ceramic' or 'all'

        $stores = $this->ceramicService->getAllStores($search, $limit, $materialType);

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
        $store = MaterialLookupQuery::rawStore($request);
        $search = MaterialLookupQuery::rawSearch($request);
        $limit = MaterialLookupQuery::rawLimit($request);

        if (!$store) {
            return response()->json([]);
        }

        $addresses = $this->ceramicService->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
