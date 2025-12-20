<?php

namespace App\Http\Controllers;

use App\Models\Sand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SandController extends Controller
{
    public function index(Request $request)
    {
        $query = Sand::query()->with('packageUnit');

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sand_name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('short_address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = [
            'sand_name',
            'type',
            'brand',
            'package_unit',
            'package_weight_gross',
            'package_weight_net',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'package_volume',
            'store',
            'short_address',
            'package_price',
            'comparison_price_per_m3',
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

        $sands = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('sands.index', compact('sands'));
    }

    public function create()
    {
        $units = Sand::getAvailableUnits();
        return view('sands.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
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

        $data = $request->all();

        // Upload foto
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $filename = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('sands', $filename, 'public');
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

        // Auto-generate sand_name jika kosong
        if (empty($data['sand_name'])) {
            $parts = array_filter([$data['type'] ?? '', $data['brand'] ?? '']);
            $data['sand_name'] = implode(' ', $parts) ?: 'Pasir';
        }

        // Buat sand
        $sand = Sand::create($data);

        // Kalkulasi berat bersih dari berat kotor dan berat kemasan
        if ($sand->package_weight_gross && $sand->package_unit) {
            $sand->calculateNetWeight();
        }

        // Kalkulasi volume dari dimensi
        if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
            $sand->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
            $sand->calculateComparisonPrice();
        }

        $sand->save();

        // Check if redirect to materials.index is requested
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index')->with('success', 'Data Pasir berhasil ditambahkan!');
        }

        return redirect()->route('sands.index')->with('success', 'Data Pasir berhasil ditambahkan!');
    }

    public function show(Sand $sand)
    {
        $sand->load('packageUnit');
        return view('sands.show', compact('sand'));
    }

    public function edit(Sand $sand)
    {
        $units = Sand::getAvailableUnits();
        return view('sands.edit', compact('sand', 'units'));
    }

    public function update(Request $request, Sand $sand)
    {
        $request->validate([
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

        $data = $request->all();

        // Auto-generate sand_name jika kosong
        if (empty($data['sand_name'])) {
            $parts = array_filter([$data['type'] ?? '', $data['brand'] ?? '']);
            $data['sand_name'] = implode(' ', $parts) ?: 'Pasir';
        }

        // Upload foto baru
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                // Hapus foto lama
                if ($sand->photo) {
                    Storage::disk('public')->delete($sand->photo);
                }

                $filename = time() . '_' . $photo->getClientOriginalName();
                $path = $photo->storeAs('sands', $filename, 'public');
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

        // Update sand
        $sand->update($data);

        // Kalkulasi berat bersih dari berat kotor dan berat kemasan
        if ($sand->package_weight_gross && $sand->package_unit) {
            $sand->calculateNetWeight();
        }

        // Kalkulasi volume dari dimensi
        if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
            $sand->calculateVolume();
        }

        // Kalkulasi harga komparasi per m³
        if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
            $sand->calculateComparisonPrice();
        } else {
            $sand->comparison_price_per_m3 = null;
        }

        $sand->save();

        // Check if redirect to materials.index is requested
        if ($request->input('_redirect_to_materials')) {
            return redirect()->route('materials.index')->with('success', 'Data Pasir berhasil diupdate!');
        }

        return redirect()->route('sands.index')->with('success', 'Data Pasir berhasil diupdate!');
    }

    public function destroy(Sand $sand)
    {
        // Hapus foto
        if ($sand->photo) {
            Storage::disk('public')->delete($sand->photo);
        }

        $sand->delete();

        return redirect()->route('sands.index')->with('success', 'Data Pasir berhasil dihapus!');
    }

    /**
     * API untuk mendapatkan unique values per field
     */
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = [
            'type',
            'brand',
            'store',
            'short_address',
            'address',
            'package_weight_gross',
            'dimension_length',
            'dimension_width',
            'dimension_height',
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

        $query = Sand::query()->whereNotNull($field)->where($field, '!=', '');

        // Apply cascading filters based on field
        // Fields that depend on brand selection (berat dan dimensi kemasan)
        if (in_array($field, ['package_weight_gross', 'dimension_length', 'dimension_width', 'dimension_height'])) {
            if ($brand !== '') {
                $query->where('brand', $brand);
            }
        }

        // Fields that depend on package_unit selection (harga)
        if ($field === 'package_price') {
            if ($packageUnit !== '') {
                $query->where('package_unit', $packageUnit);
            }
        }

        // Fields that depend on store selection (alamat)
        if ($field === 'address') {
            if ($store !== '') {
                $query->where('store', $store);
            }
        }

        // Special case: store field - show all stores from ALL materials if requested
        if ($field === 'store' && $request->query('all_materials') === 'true') {
            // Get stores from all material types
            $allStores = collect();

            // Get from sands
            $sandStores = Sand::whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search !== '', fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->select('store')
                ->groupBy('store')
                ->pluck('store');

            $allStores = $allStores->merge($sandStores);

            // Get from other material tables if they exist
            // Cement
            if (class_exists(\App\Models\Cement::class)) {
                $cementStores = \App\Models\Cement::whereNotNull('store')
                    ->where('store', '!=', '')
                    ->when($search !== '', fn($q) => $q->where('store', 'like', "%{$search}%"))
                    ->select('store')
                    ->groupBy('store')
                    ->pluck('store');
                $allStores = $allStores->merge($cementStores);
            }

            // Brick
            if (class_exists(\App\Models\Brick::class)) {
                $brickStores = \App\Models\Brick::whereNotNull('store')
                    ->where('store', '!=', '')
                    ->when($search !== '', fn($q) => $q->where('store', 'like', "%{$search}%"))
                    ->select('store')
                    ->groupBy('store')
                    ->pluck('store');
                $allStores = $allStores->merge($brickStores);
            }

            // Cat
            if (class_exists(\App\Models\Cat::class)) {
                $catStores = \App\Models\Cat::whereNotNull('store')
                    ->where('store', '!=', '')
                    ->when($search !== '', fn($q) => $q->where('store', 'like', "%{$search}%"))
                    ->select('store')
                    ->groupBy('store')
                    ->pluck('store');
                $allStores = $allStores->merge($catStores);
            }

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
     * API untuk mendapatkan semua stores dari sand atau semua material
     */
    public function getAllStores(Request $request)
    {
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = $request->query('material_type', 'all'); // 'sand' atau 'all'

        $stores = collect();

        // Jika tidak ada search term, hanya tampilkan stores dari sand
        // Jika ada search term, tampilkan dari semua material
        if ($materialType === 'sand' || ($search === '' && $materialType === 'all')) {
            // Tampilkan dari sand saja
            $sandStores = Sand::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores->merge($sandStores)->unique()->sort()->values()->take($limit);
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

            $cementStores = \App\Models\Cement::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $sandStores = Sand::query()
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

        // Ambil short_address dari sand yang sesuai dengan toko
        $sandAddresses = Sand::query()
            ->where('store', $store)
            ->whereNotNull('short_address')
            ->where('short_address', '!=', '')
            ->when($search, fn($q) => $q->where('short_address', 'like', "%{$search}%"))
            ->pluck('short_address');

        // Ambil short_address dari cat
        $catAddresses = \App\Models\Cat::query()
            ->where('store', $store)
            ->whereNotNull('short_address')
            ->where('short_address', '!=', '')
            ->when($search, fn($q) => $q->where('short_address', 'like', "%{$search}%"))
            ->pluck('short_address');

        // Ambil short_address dari brick
        $brickAddresses = \App\Models\Brick::query()
            ->where('store', $store)
            ->whereNotNull('short_address')
            ->where('short_address', '!=', '')
            ->when($search, fn($q) => $q->where('short_address', 'like', "%{$search}%"))
            ->pluck('short_address');

        // Ambil short_address dari cement
        $cementAddresses = \App\Models\Cement::query()
            ->where('store', $store)
            ->whereNotNull('short_address')
            ->where('short_address', '!=', '')
            ->when($search, fn($q) => $q->where('short_address', 'like', "%{$search}%"))
            ->pluck('short_address');

        // Gabungkan semua addresses dan ambil unique values
        $allAddresses = $addresses
            ->merge($sandAddresses)
            ->merge($catAddresses)
            ->merge($brickAddresses)
            ->merge($cementAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }
}
