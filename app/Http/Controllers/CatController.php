<?php

namespace App\Http\Controllers;

use App\Models\Cat;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        ]);

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time() . '_' . $photo->getClientOriginalName();
                // Simpan ke disk 'public' agar dapat diakses via /storage
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

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()
                ->to($request->input('_redirect_url'))
                ->with('success', 'Cat berhasil ditambahkan!')
                ->with('new_material', ['type' => 'cat', 'id' => $cat->id]);
        }
        // Backward compatibility for older forms
        if ($request->input('_redirect_to_materials')) {
            return redirect()
                ->route('materials.index')
                ->with('success', 'Cat berhasil ditambahkan!')
                ->with('new_material', ['type' => 'cat', 'id' => $cat->id]);
        }

        return redirect()->route('cats.index')->with('success', 'cat berhasil ditambahkan!');
    }

    public function show(Cat $cat)
    {
        $cat->load('packageUnit');
        return view('cats.show', compact('cat'));
    }

    public function edit(Cat $cat)
    {
        $units = Cat::getAvailableUnits();
        return view('cats.edit', compact('cat', 'units'));
    }

    public function update(Request $request, Cat $cat)
    {
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
        ]);

        $data = $request->all();

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
                // Simpan ke disk 'public' agar dapat diakses via /storage
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
        } else {
            $cat->comparison_price_per_kg = null;
        }

        $cat->save();

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()->to($request->input('_redirect_url'))->with('success', 'Cat berhasil diupdate!');
        }
        // Backward compatibility for older forms
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index')->with('success', 'Cat berhasil diupdate!');
        }

        return redirect()->route('cats.index')->with('success', 'cat berhasil diupdate!');
    }

    public function destroy(Cat $cat)
    {
        // Hapus foto
        if ($cat->photo) {
            Storage::disk('public')->delete($cat->photo);
        }

        $cat->delete();

        return redirect()->route('cats.index')->with('success', 'Cat berhasil dihapus!');
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
