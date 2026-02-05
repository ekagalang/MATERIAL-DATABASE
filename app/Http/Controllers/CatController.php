<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Unit;
use App\Services\Material\MaterialDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CatController extends Controller
{
    public function index(Request $request)
    {
        $query = Cat::query()->with('packageUnit');
        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cat_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('color_name', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = [
            'cat_name',
            'type',
            'brand',
            'sub_brand',
            'color_name',
            'color_code',
            'form',
            'package_unit',
            'package_weight_gross',
            'package_weight_net',
            'volume',
            'volume_unit',
            'store',
            'address',
            'purchase_price',
            'comparison_price_per_kg',
            'created_at',
        ];

        // Default sorting jika tidak ada atau tidak valid
        if (!$sortBy || !in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
            $sortDirection = 'desc';
        } else {
            // Validasi direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
        }

        $cats = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('cats.index', compact('cats'));
    }

    public function create()
    {
        $units = Cat::getAvailableUnits();
        return view('cats.create', compact('units'));
    }

    public function store(Request $request)
    {
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

        $request->validate([
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
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('cat', $data);
        if ($duplicate) {
            $message = 'Data Cat sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

            // Debug logging
            \Log::info('CatController@store - Request data:', [
                'package_weight_gross' => $request->input('package_weight_gross'),
                'package_weight_net' => $request->input('package_weight_net'),
                'package_unit' => $request->input('package_unit'),
            ]);

            // Upload foto
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('cats', $filename, 'public');
                    if ($path) {
                        $data['photo'] = $path;
                        \Log::info('Photo uploaded successfully: ' . $path);
                    } else {
                        \Log::error('Failed to store photo');
                    }
                } else {
                    \Log::error('Invalid photo file: ' . $photo->getErrorMessage());
                }
            }

            // Auto-generate cat_name jika kosong
            if (empty($data['cat_name'])) {
                $parts = array_filter([
                    $data['type'] ?? '',
                    $data['brand'] ?? '',
                    $data['sub_brand'] ?? '',
                    $data['color_name'] ?? '',
                    ($data['volume'] ?? '') . ($data['volume_unit'] ?? ''),
                ]);
                $data['cat_name'] = implode(' ', $parts) ?: 'Cat';
            }

            // Buat cat
            $cat = Cat::create($data);

            // Jika berat bersih belum diisi, hitung dari (berat kotor - berat kemasan unit)
            if (
                (!$cat->package_weight_net || $cat->package_weight_net <= 0) &&
                $cat->package_weight_gross &&
                $cat->package_unit
            ) {
                $cat->calculateNetWeight();
            }
            // Kalkulasi harga komparasi per kg
            if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
                $cat->comparison_price_per_kg = $cat->purchase_price / $cat->package_weight_net;
            }

            $cat->save();

            // NEW: Attach store location
            if ($request->filled('store_location_id')) {
                $cat->storeLocations()->attach($request->input('store_location_id'));
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('cats.index'));
            $newMaterial = ['type' => 'cat', 'id' => $cat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cat berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Cat berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Cat berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }

            return redirect()->route('cats.index')->with('success', 'cat berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create cat: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Cat $cat)
    {
        $cat->load('packageUnit', 'storeLocations.store'); // UPDATED
        return view('cats.show', compact('cat'));
    }

    public function edit(Cat $cat)
    {
        $cat->load('storeLocations.store'); // NEW
        $units = Cat::getAvailableUnits();
        return view('cats.edit', compact('cat', 'units'));
    }

    public function update(Request $request, Cat $cat)
    {
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

        $request->validate([
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
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('cat', $data, $cat->id);
        if ($duplicate) {
            $message = 'Data Cat sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

            // Debug logging
            \Log::info('CatController@update - Request data:', [
                'id' => $cat->id,
                'package_weight_gross' => $request->input('package_weight_gross'),
                'package_weight_net' => $request->input('package_weight_net'),
                'package_unit' => $request->input('package_unit'),
            ]);

            // Auto-generate cat_name jika kosong
            if (empty($data['cat_name'])) {
                $parts = array_filter([
                    $data['type'] ?? '',
                    $data['brand'] ?? '',
                    $data['sub_brand'] ?? '',
                    $data['color_name'] ?? '',
                    ($data['volume'] ?? '') . ($data['volume_unit'] ?? ''),
                ]);
                $data['cat_name'] = implode(' ', $parts) ?: 'Cat';
            }

            // Upload foto baru
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    // Hapus foto lama
                    if ($cat->photo) {
                        Storage::disk('public')->delete($cat->photo);
                    }

                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('cats', $filename, 'public');
                    if ($path) {
                        $data['photo'] = $path;
                        \Log::info('Photo updated successfully: ' . $path);
                    } else {
                        \Log::error('Failed to update photo');
                    }
                } else {
                    \Log::error('Invalid photo file on update: ' . $photo->getErrorMessage());
                }
            }

            // Update cat
            $cat->update($data);

            // Kalkulasi berat bersih dari berat kotor dan berat kemasan
            // HANYA jika berat bersih belum diisi manual oleh user
            if (
                (!$cat->package_weight_net || $cat->package_weight_net <= 0) &&
                $cat->package_weight_gross &&
                $cat->package_unit
            ) {
                $cat->calculateNetWeight();
            }

            // Kalkulasi harga komparasi per kg
            if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
                $cat->comparison_price_per_kg = $cat->purchase_price / $cat->package_weight_net;
            } else {
                $cat->comparison_price_per_kg = null;
            }

            $cat->save();

            // NEW: Sync store location
            if ($request->filled('store_location_id')) {
                $cat->storeLocations()->sync([$request->input('store_location_id')]);
            } else {
                $cat->storeLocations()->detach();
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('cats.index'));
            $updatedMaterial = ['type' => 'cat', 'id' => $cat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cat berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Cat berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Cat berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route('cats.index')
                ->with('success', 'cat berhasil diupdate!')
                ->with('updated_material', $updatedMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update cat: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Cat $cat)
    {
        DB::beginTransaction();
        try {
            // Hapus foto
            if ($cat->photo) {
                Storage::disk('public')->delete($cat->photo);
            }

            // NEW: Detach store locations
            $cat->storeLocations()->detach();

            $cat->delete();

            DB::commit();

            return redirect()->route('cats.index')->with('success', 'Cat berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete cat: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Normalize comma decimal separator to dot for numeric fields.
     */
    private function normalizeDecimalFields(Request $request): void
    {
        $fields = ['package_weight_gross', 'package_weight_net', 'volume', 'purchase_price', 'comparison_price_per_kg'];
        $normalized = [];

        foreach ($fields as $field) {
            $value = $request->input($field);
            if (is_string($value) && $value !== '') {
                $normalized[$field] = str_replace(',', '.', $value);
            }
        }

        if ($normalized) {
            $request->merge($normalized);
        }
    }

    // API untuk mendapatkan unique values per field dengan cascading filter
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'cat_name',
            'type',
            'brand',
            'sub_brand',
            'color_code',
            'color_name',
            'form',
            'volume',
            'volume_unit',
            'package_weight_gross',
            'package_weight_net',
            'package_unit',
            'store',
            'address',
            'price_unit',
            'purchase_price',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        // Filter parameters untuk cascading
        $brand = $request->query('brand');
        $packageUnit = $request->query('package_unit');
        $store = $request->query('store');
        $colorCode = trim((string) $request->query('color_code', ''));
        $colorName = trim((string) $request->query('color_name', ''));

        $query = Cat::query()->whereNotNull($field)->where($field, '!=', '');

        // Apply cascading filters
        // Fields yang bergantung pada brand
        if (
            in_array($field, [
                'sub_brand',
                'color_name',
                'color_code',
                'volume',
                'package_weight_gross',
                'package_weight_net',
            ]) &&
            $brand
        ) {
            $query->where('brand', $brand);
        }

        // purchase_price bergantung pada package_unit
        if ($field === 'purchase_price' && $packageUnit) {
            $query->where('package_unit', $packageUnit);
        }

        // address bergantung pada store
        if ($field === 'address' && $store) {
            $query->where('store', $store);
        }

        // Pairing warna: kode dan nama warna harus saling terkait
        if ($field === 'color_name' && $colorCode !== '') {
            $query->where('color_code', $colorCode);
        }
        if ($field === 'color_code' && $colorName !== '') {
            $query->where('color_name', $colorName);
        }

        if ($search !== '') {
            $query->where($field, 'like', "%{$search}%");
        }

        // Ambil nilai unik, dibatasi
        $values = $query->select($field)->groupBy($field)->orderBy($field)->limit($limit)->pluck($field);

        return response()->json($values);
    }

    // API khusus untuk mendapatkan semua stores dari semua material (untuk validasi input baru)
    public function getAllStores(Request $request)
    {
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = $request->query('material_type', 'all'); // 'cat' atau 'all'

        $stores = collect();

        // Jika tidak ada search term, hanya tampilkan stores dari cat
        // Jika ada search term, tampilkan dari semua material
        if ($materialType === 'cat' || ($search === '' && $materialType === 'all')) {
            // Tampilkan dari cat saja
            $catStores = Cat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores->merge($catStores)->unique()->sort()->values()->take($limit);
        } else {
            // Tampilkan dari semua material (saat user mengetik)
            $catStores = Cat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $brickStores = \App\Models\Brick::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $cementStores = \App\Models\Cement::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $sandStores = \App\Models\Sand::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            // Gabungkan semua stores dan ambil unique values
            $allStores = $stores
                ->merge($catStores)
                ->merge($brickStores)
                ->merge($cementStores)
                ->merge($sandStores)
                ->unique()
                ->sort()
                ->values()
                ->take($limit);
        }

        return response()->json($allStores);
    }

    /**
     * API untuk mendapatkan alamat berdasarkan toko dari semua material
     */
    public function getAddressesByStore(Request $request)
    {
        $store = (string) $request->query('store', '');
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        // Jika tidak ada toko yang dipilih, return empty
        if ($store === '') {
            return response()->json([]);
        }

        $addresses = collect();

        // Ambil address dari cat yang sesuai dengan toko
        $catAddresses = Cat::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Ambil address dari brick
        $brickAddresses = \App\Models\Brick::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Ambil address dari cement
        $cementAddresses = \App\Models\Cement::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Ambil address dari sand
        $sandAddresses = \App\Models\Sand::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Gabungkan semua addresses dan ambil unique values
        $allAddresses = $addresses
            ->merge($catAddresses)
            ->merge($brickAddresses)
            ->merge($cementAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }
}
