<?php

namespace App\Http\Controllers;

use App\Models\Ceramic;
use App\Models\Unit;
use App\Services\Material\CeramicService;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        ]);

        $this->service->create($data, $request->file('photo'));

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()->to($request->input('_redirect_url'))->with('success', 'Data berhasil disimpan');
        }

        return redirect()->route('ceramics.index')->with('success', 'Data berhasil disimpan');
    }

    public function show(Ceramic $ceramic): View
    {
        // Return view TANPA layout (untuk modal)
        return view('ceramics.show', compact('ceramic'));
    }

    public function edit(Ceramic $ceramic): View
    {
        $units = Unit::forMaterial('ceramic')->get();
        // Return view TANPA layout (untuk modal)
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
        ]);

        $this->service->update($ceramic->id, $data, $request->file('photo'));

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()->to($request->input('_redirect_url'))->with('success', 'Data berhasil diperbarui');
        }

        return redirect()->route('ceramics.index')->with('success', 'Data berhasil diperbarui');
    }

    public function destroy(Ceramic $ceramic)
    {
        $this->service->delete($ceramic->id);
        return redirect()->route('ceramics.index')->with('success', 'Data berhasil dihapus');
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
        $packagingFilteredFields = [
            'price_per_package',
            'comparison_price_per_m2',
        ];
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
