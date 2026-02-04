<?php

namespace App\Http\Controllers;

use App\Models\Ceramic;
use App\Models\Unit;
use App\Services\Material\CeramicService;
use App\Services\Material\MaterialDuplicateService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CeramicController extends Controller
{
    protected $service;

    public function __construct(CeramicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        // Validasi sort column untuk security
        $allowedSorts = [
            'material_name',
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'form',
            'dimension_length',
            'dimension_width',
            'dimension_thickness',
            'pieces_per_package',
            'coverage_per_package',
            'store',
            'address',
            'price_per_package',
            'comparison_price_per_m2',
            'created_at',
            'updated_at',
        ];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Get data dengan search & sorting
        $ceramics = $search
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
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'code' => 'nullable|string',
            'color' => 'nullable|string',
            'form' => 'nullable|string',
            'surface' => 'nullable|string|max:255',
            'dimension_length' => 'required|numeric',
            'dimension_width' => 'required|numeric',
            'dimension_thickness' => 'nullable|numeric',
            'pieces_per_package' => 'required|integer',
            'coverage_per_package' => 'nullable|numeric',
            'price_per_package' => 'required|numeric',
            'comparison_price_per_m2' => 'nullable|numeric',
            'packaging' => 'nullable|string|max:255',
            'store' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            // NEW
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        // Extract store_location_id sebelum dikirim ke service
        $storeLocationId = $data['store_location_id'] ?? null;
        unset($data['store_location_id']);

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('ceramic', $data);
        if ($duplicate) {
            $message = 'Data Keramik sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {
            $ceramic = $this->service->create($data, $request->file('photo'));

            // Save store_location_id directly to the model
            if ($storeLocationId) {
                $ceramic->store_location_id = $storeLocationId;
                $ceramic->save();
                // Also attach to many-to-many relationship
                $ceramic->storeLocations()->attach($storeLocationId);
            }

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
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'code' => 'nullable|string',
            'color' => 'nullable|string',
            'form' => 'nullable|string',
            'surface' => 'nullable|string|max:255',
            'dimension_length' => 'required|numeric',
            'dimension_width' => 'required|numeric',
            'dimension_thickness' => 'nullable|numeric',
            'pieces_per_package' => 'required|integer',
            'coverage_per_package' => 'nullable|numeric',
            'price_per_package' => 'required|numeric',
            'comparison_price_per_m2' => 'nullable|numeric',
            'packaging' => 'nullable|string|max:255',
            'store' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            // NEW
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        // Extract store_location_id
        $storeLocationId = $data['store_location_id'] ?? null;
        unset($data['store_location_id']);

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('ceramic', $data, $ceramic->id);
        if ($duplicate) {
            $message = 'Data Keramik sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {
            $this->service->update($ceramic->id, $data, $request->file('photo'));

            // Reload ceramic to get fresh instance
            $ceramic = $ceramic->fresh();

            // Save store_location_id directly to the model
            $ceramic->store_location_id = $storeLocationId;
            $ceramic->save();

            // Also sync to many-to-many relationship
            if ($storeLocationId) {
                $ceramic->storeLocations()->sync([$storeLocationId]);
            } else {
                $ceramic->storeLocations()->detach();
            }

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
        $allowedFields = [
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'form',
            'surface',
            'packaging',
            'pieces_per_package',
            'dimension_length',
            'dimension_width',
            'dimension_thickness',
            'price_per_package',
            'comparison_price_per_m2',
            'store',
            'address',
        ];

        if (!in_array($field, $allowedFields, true)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

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
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = (string) $request->query('material_type', 'all'); // 'ceramic' atau 'all'

        $stores = $this->service->getAllStores($search, $limit, $materialType);

        return response()->json($stores);
    }

    public function getAddressesByStore(Request $request)
    {
        $store = (string) $request->query('store', '');
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = $this->service->getAddressesByStore($store, $search, $limit);

        return response()->json($addresses);
    }
}
