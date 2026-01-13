<?php

namespace App\Http\Controllers;

use App\Models\Cement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CementController extends Controller
{
    public function index(Request $request)
    {
        $query = Cement::query()->with('packageUnit');

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cement_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('sub_brand', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = [
            'cement_name',
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'package_unit',
            'package_weight_gross',
            'package_weight_net',
            'store',
            'address',
            'package_price',
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

        $cements = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('cements.index', compact('cements'));
    }

    public function create()
    {
        $units = Cement::getAvailableUnits();

        return view('cements.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('cements', $filename, 'public');
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

        // Auto-generate cement_name jika kosong
        if (empty($data['cement_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['cement_name'] = implode(' ', $parts) ?: 'Semen';
        }

        // Buat cement
        $cement = Cement::create($data);

        // Kalkulasi berat bersih jika belum diisi
        if (
            (!$cement->package_weight_net || $cement->package_weight_net <= 0) &&
            $cement->package_weight_gross &&
            $cement->package_unit
        ) {
            $cement->calculateNetWeight();
        }

        // Kalkulasi harga komparasi per kg
        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->comparison_price_per_kg = $cement->package_price / $cement->package_weight_net;
        }

        $cement->save();

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()->to($request->input('_redirect_url'))->with('success', 'Semen berhasil ditambahkan!');
        }
        // Backward compatibility for older forms
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index')->with('success', 'Semen berhasil ditambahkan!');
        }

        return redirect()->route('cements.index')->with('success', 'Semen berhasil ditambahkan!');
    }

    public function show(Cement $cement)
    {
        $cement->load('packageUnit');

        return view('cements.show', compact('cement'));
    }

    public function edit(Cement $cement)
    {
        $units = Cement::getAvailableUnits();

        return view('cements.edit', compact('cement', 'units'));
    }

    public function update(Request $request, Cement $cement)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        // Auto-generate cement_name jika kosong
        if (empty($data['cement_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['cement_name'] = implode(' ', $parts) ?: 'Semen';
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($cement->photo) {
                    Storage::disk('public')->delete($cement->photo);
                }

                $filename = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('cements', $filename, 'public');
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

        // Update cement
        $cement->update($data);

        // Kalkulasi berat bersih jika belum diisi
        if (
            (!$cement->package_weight_net || $cement->package_weight_net <= 0) &&
            $cement->package_weight_gross &&
            $cement->package_unit
        ) {
            $cement->calculateNetWeight();
        }

        // Kalkulasi harga komparasi per kg
        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->comparison_price_per_kg = $cement->package_price / $cement->package_weight_net;
        } else {
            $cement->comparison_price_per_kg = null;
        }

        $cement->save();

        // Redirect back to the originating page if requested
        if ($request->filled('_redirect_url')) {
            return redirect()->to($request->input('_redirect_url'))->with('success', 'Semen berhasil diupdate!');
        }
        // Backward compatibility for older forms
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index')->with('success', 'Semen berhasil diupdate!');
        }

        return redirect()->route('cements.index')->with('success', 'Semen berhasil diupdate!');
    }

    public function destroy(Cement $cement)
    {
        // Hapus foto
        if ($cement->photo) {
            Storage::disk('public')->delete($cement->photo);
        }

        $cement->delete();

        return redirect()->route('cements.index')->with('success', 'Semen berhasil dihapus!');
    }

    // API untuk mendapatkan unique values per field
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'cement_name',
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'store',
            'address',
            'price_unit',
            'package_weight_gross',
            'package_price',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        // Get filter parameters for cascading autocomplete
        $brand = (string) $request->query('brand', '');
        $packageUnit = (string) $request->query('package_unit', '');
        $store = (string) $request->query('store', '');

        $query = Cement::query()->whereNotNull($field)->where($field, '!=', '');

        // Apply cascading filters based on field
        // Fields that depend on brand selection
        if (
            in_array($field, [
                'sub_brand',
                'code',
                'color',
                'package_weight_gross',
            ])
        ) {
            if ($brand !== '') {
                $query->where('brand', $brand);
            }
        }

        // Fields that depend on package_unit selection
        if ($field === 'package_price') {
            if ($packageUnit !== '') {
                $query->where('package_unit', $packageUnit);
            }
        }

        // Fields that depend on store selection
        if ($field === 'address') {
            if ($store !== '') {
                $query->where('store', $store);
            }
        }

        // Special case: store field - show all stores from ALL materials if requested
        if ($field === 'store' && $request->query('all_materials') === 'true') {
            // Get stores from all material types
            $allStores = collect();

            // Get from cements
            $cementStores = Cement::whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search !== '', fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->select('store')
                ->groupBy('store')
                ->pluck('store');

            $allStores = $allStores->merge($cementStores);

            // Get from other material tables if they exist
            // Add more material types here as needed
            // Example: $brickStores = Brick::...

            return response()->json($allStores->unique()->sort()->values()->take($limit));
        }

        if ($search !== '') {
            $query->where($field, 'like', "%{$search}%");
        }

        // Ambil nilai unik, dibatasi
        $values = $query->select($field)->groupBy($field)->orderBy($field)->limit($limit)->pluck($field);

        return response()->json($values);
    }

    /**
     * API untuk mendapatkan semua stores dari cement atau semua material
     */
    public function getAllStores(Request $request)
    {
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = $request->query('material_type', 'all'); // 'cement' atau 'all'

        $stores = collect();

        // Jika tidak ada search term, hanya tampilkan stores dari cement
        // Jika ada search term, tampilkan dari semua material
        if ($materialType === 'cement' || ($search === '' && $materialType === 'all')) {
            // Tampilkan dari cement saja
            $cementStores = Cement::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores->merge($cementStores)->unique()->sort()->values()->take($limit);
        } else {
            // Tampilkan dari semua material (saat user mengetik)
            $catStores = \App\Models\Cat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $brickStores = \App\Models\Brick::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $cementStores = Cement::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $sandStores = \App\Models\Sand::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

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

        // Ambil address dari cement yang sesuai dengan toko
        $cementAddresses = Cement::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Ambil address dari cat
        $catAddresses = \App\Models\Cat::query()
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

        // Ambil address dari sand
        $sandAddresses = \App\Models\Sand::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        // Gabungkan semua addresses dan ambil unique values
        $allAddresses = $addresses
            ->merge($cementAddresses)
            ->merge($catAddresses)
            ->merge($brickAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }
}
