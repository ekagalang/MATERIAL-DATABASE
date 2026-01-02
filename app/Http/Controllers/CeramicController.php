<?php

namespace App\Http\Controllers;

use App\Models\Ceramic;
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

        // Jika request AJAX (untuk search/pagination), kembalikan potongan tabel saja
        // Namun jika Anda tidak menggunakan partial, biarkan ini reload halaman biasa (fallback)
        // atau kita return view index dengan layout jika bukan ajax.

        $ceramics = $this->service->search($search, 10);
        $ceramics->appends($request->all());

        return view('ceramics.index', compact('ceramics'));
    }

    public function create(): View
    {
        // Return view TANPA layout (untuk modal)
        return view('ceramics.create');
    }

    public function store(Request $request)
    {
        // Validasi & Simpan (Sama seperti sebelumnya)
        $data = $request->validate([
            'brand' => 'required|string|max:255',
            'sub_brand' => 'nullable|string|max:255',
            'type' => 'nullable|string',
            'code' => 'nullable|string',
            'color' => 'nullable|string',
            'form' => 'nullable|string',
            'dimension_length' => 'required|numeric',
            'dimension_width' => 'required|numeric',
            'dimension_thickness' => 'nullable|numeric',
            'pieces_per_package' => 'required|integer',
            'coverage_per_package' => 'nullable|numeric',
            'price_per_package' => 'required|numeric',
            'store' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        $this->service->create($data, $request->file('photo'));

        // Return redirect ke index agar halaman refresh
        return redirect()->route('ceramics.index')->with('success', 'Data berhasil disimpan');
    }

    public function show(Ceramic $ceramic): View
    {
        // Return view TANPA layout (untuk modal)
        return view('ceramics.show', compact('ceramic'));
    }

    public function edit(Ceramic $ceramic): View
    {
        // Return view TANPA layout (untuk modal)
        return view('ceramics.edit', compact('ceramic'));
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
            'dimension_length' => 'required|numeric',
            'dimension_width' => 'required|numeric',
            'dimension_thickness' => 'nullable|numeric',
            'pieces_per_package' => 'required|integer',
            'coverage_per_package' => 'nullable|numeric',
            'price_per_package' => 'required|numeric',
            'store' => 'nullable|string',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        $this->service->update($ceramic->id, $data, $request->file('photo'));

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

    public function getFieldValues($field)
    {
        $search = request('search');
        $values = $this->service->getFieldValues($field, [], $search);
        return response()->json($values);
    }

    public function getAllStores()
    {
        $search = request('search');
        // 'all' untuk mencari di semua material, 'ceramic' untuk hanya keramik
        $stores = $this->service->getAllStores($search, 20, 'all');
        return response()->json($stores);
    }

    public function getAddressesByStore()
    {
        $store = request('store');
        $search = request('search');
        $addresses = $this->service->getAddressesByStore($store, $search);
        return response()->json($addresses);
    }
}
