<?php

namespace App\Http\Controllers;

use App\Http\Requests\Material\CeramicUpsertRequest;
use App\Models\Ceramic;
use App\Models\Unit;
use App\Services\Material\CeramicService;
use App\Services\Material\MaterialDuplicateService;
use App\Support\Material\MaterialIndexQuery;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class CeramicController extends Controller
{
    protected $service;

    public function __construct(CeramicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $search = MaterialIndexQuery::searchValue($request);
        [$sortBy, $sortDirection] = MaterialIndexQuery::resolveSort($request, 'ceramic');
        $perPage = $request->input('per_page', 15);

        // Get data dengan search & sorting
        $ceramics = $search !== ''
            ? $this->service->search($search, $perPage, $sortBy, $sortDirection)
            : $this->service->paginateWithSort($perPage, $sortBy, $sortDirection);

        $ceramics->appends($request->all());

        return view('ceramics.index', compact('ceramics'));
    }

    public function create(): View
    {
        // Ambil data unit untuk dropdown kemasan
        $units = Unit::forMaterial('ceramic')->get();
        // Return view TANPA layout (untuk modal)
        return view('ceramics.create', compact('units'));
    }

    public function store(Request $request)
    {
        // Validasi & Simpan
        $data = $request->validate((new CeramicUpsertRequest())->rules());

        // Extract store_location_id sebelum dikirim ke service
        $storeLocationId = $this->extractStoreLocationId($data);

        app(MaterialDuplicateService::class)->ensureNoDuplicate('ceramic', $data);

        DB::beginTransaction();
        try {
            $ceramic = $this->service->create($data, $request->file('photo'));

            $this->applyStoreLocationOnStore($ceramic, $storeLocationId);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : route('ceramics.index');
            $newMaterial = ['type' => 'ceramic', 'id' => $ceramic->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil disimpan',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data berhasil disimpan')
                    ->with('new_material', $newMaterial);
            }

            return redirect()->route('ceramics.index')->with('success', 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create ceramic: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Ceramic $ceramic): View
    {
        $ceramic->load('storeLocations.store'); // NEW
        return view('ceramics.show', compact('ceramic'));
    }

    public function edit(Ceramic $ceramic): View
    {
        $ceramic->load('storeLocations.store'); // NEW
        $units = Unit::forMaterial('ceramic')->get();
        return view('ceramics.edit', compact('ceramic', 'units'));
    }

    public function update(Request $request, Ceramic $ceramic)
    {
        $data = $request->validate((new CeramicUpsertRequest())->rules());

        // Extract store_location_id
        $storeLocationId = $this->extractStoreLocationId($data);

        app(MaterialDuplicateService::class)->ensureNoDuplicate('ceramic', $data, $ceramic->id);

        DB::beginTransaction();
        try {
            $this->service->update($ceramic->id, $data, $request->file('photo'));

            // Reload ceramic to get fresh instance
            $ceramic = $ceramic->fresh();

            $this->applyStoreLocationOnUpdate($ceramic, $storeLocationId);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : route('ceramics.index');
            $updatedMaterial = ['type' => 'ceramic', 'id' => $ceramic->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil diperbarui',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data berhasil diperbarui')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route('ceramics.index')
                ->with('success', 'Data berhasil diperbarui')
                ->with('updated_material', $updatedMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update ceramic: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Ceramic $ceramic)
    {
        DB::beginTransaction();
        try {
            // NEW: Detach store locations
            $ceramic->storeLocations()->detach();

            $this->service->delete($ceramic->id);

            DB::commit();

            return redirect()->route('ceramics.index')->with('success', 'Data berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete ceramic: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /* |--------------------------------------------------------------------------
    | API / JSON Methods (Untuk Autocomplete Frontend)
    |--------------------------------------------------------------------------
    */

    public function getFieldValues(string $field, Request $request)
    {
        $allowedFields = MaterialLookupSpec::allowedFields('ceramic');

        if (!in_array($field, $allowedFields, true)) {
            return response()->json([]);
        }

        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

        $filters = [];

        // Rule: filter brand by selected type
        if ($field === 'brand' && $request->filled('type')) {
            $filters['type'] = (string) $request->query('type');
        }

        // Rule: filter by selected brand
        $brandFilteredFields = [
            'sub_brand',
            'color',
            'code',
            'form',
            'surface',
            'pieces_per_package',
            'dimension_length',
            'dimension_width',
            'dimension_thickness',
        ];
        if (in_array($field, $brandFilteredFields, true) && $request->filled('brand')) {
            $filters['brand'] = (string) $request->query('brand');
        }

        // Rule: filter price by selected packaging
        $packagingFilteredFields = ['price_per_package', 'comparison_price_per_m2'];
        if (in_array($field, $packagingFilteredFields, true) && $request->filled('packaging')) {
            $filters['packaging'] = (string) $request->query('packaging');
        }

        // Optional: address by selected store
        if ($field === 'address' && $request->filled('store')) {
            $filters['store'] = (string) $request->query('store');
        }

        $values = $this->service->getFieldValues($field, $filters, $search, $limit);

        return response()->json($values);
    }

    public function getAllStores(Request $request)
    {
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $materialType = MaterialLookupQuery::stringMaterialType($request, 'all'); // 'ceramic' atau 'all'

        $stores = $this->service->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request)
    {
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = $this->service->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }

    private function extractStoreLocationId(array &$data): ?int
    {
        $storeLocationId = $data['store_location_id'] ?? null;
        unset($data['store_location_id']);

        return $storeLocationId ? (int) $storeLocationId : null;
    }

    private function applyStoreLocationOnStore(Ceramic $ceramic, ?int $storeLocationId): void
    {
        if ($storeLocationId) {
            $ceramic->store_location_id = $storeLocationId;
            $ceramic->save();
            $ceramic->storeLocations()->attach($storeLocationId);
        }
    }

    private function applyStoreLocationOnUpdate(Ceramic $ceramic, ?int $storeLocationId): void
    {
        $ceramic->store_location_id = $storeLocationId;
        $ceramic->save();

        if ($storeLocationId) {
            $ceramic->storeLocations()->sync([$storeLocationId]);
            return;
        }

        $ceramic->storeLocations()->detach();
    }
}
