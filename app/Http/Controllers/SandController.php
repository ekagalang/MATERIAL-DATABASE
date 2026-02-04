<?php

namespace App\Http\Controllers;

use App\Models\Sand;
use App\Services\Material\MaterialDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
                    ->orWhere('address', 'like', "%{$search}%");
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
            'address',
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
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

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
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('sand', $data);
        if ($duplicate) {
            $message = 'Data Pasir sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

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

            // Kalkulasi harga komparasi per M3
            if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
                $sand->calculateComparisonPrice();
            }

            $sand->save();

            // NEW: Attach store location
            if ($request->filled('store_location_id')) {
                $sand->storeLocations()->attach($request->input('store_location_id'));
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('sands.index'));
            $newMaterial = ['type' => 'sand', 'id' => $sand->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data Pasir berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data Pasir berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Data Pasir berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }

            return redirect()->route('sands.index')->with('success', 'Data Pasir berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create sand: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Sand $sand)
    {
        $sand->load('packageUnit', 'storeLocations.store'); // UPDATED
        return view('sands.show', compact('sand'));
    }

    public function edit(Sand $sand)
    {
        $sand->load('storeLocations.store'); // NEW
        $units = Sand::getAvailableUnits();
        return view('sands.edit', compact('sand', 'units'));
    }

    public function update(Request $request, Sand $sand)
    {
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

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
            'address' => 'nullable|string',
            'package_price' => 'nullable|numeric|min:0',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('sand', $data, $sand->id);
        if ($duplicate) {
            $message = 'Data Pasir sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

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
            // HANYA jika berat bersih belum diisi manual oleh user
            if (
                (!$sand->package_weight_net || $sand->package_weight_net <= 0) &&
                $sand->package_weight_gross &&
                $sand->package_unit
            ) {
                $sand->calculateNetWeight();
            }

            // Kalkulasi volume dari dimensi
            if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
                $sand->calculateVolume();
            }

            // Kalkulasi harga komparasi per M3
            if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
                $sand->calculateComparisonPrice();
            } else {
                $sand->comparison_price_per_m3 = null;
            }

            $sand->save();

            // NEW: Sync store location
            if ($request->filled('store_location_id')) {
                $sand->storeLocations()->sync([$request->input('store_location_id')]);
            } else {
                $sand->storeLocations()->detach();
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('sands.index'));
            $updatedMaterial = ['type' => 'sand', 'id' => $sand->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data Pasir berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data Pasir berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Data Pasir berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route('sands.index')
                ->with('success', 'Data Pasir berhasil diupdate!')
                ->with('updated_material', $updatedMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update sand: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Sand $sand)
    {
        DB::beginTransaction();
        try {
            // Hapus foto
            if ($sand->photo) {
                Storage::disk('public')->delete($sand->photo);
            }

            // NEW: Detach store locations
            $sand->storeLocations()->detach();

            $sand->delete();

            DB::commit();

            return redirect()->route('sands.index')->with('success', 'Data Pasir berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete sand: ' . $e->getMessage());
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

        // Ambil address dari sand yang sesuai dengan toko
        $sandAddresses = Sand::query()
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

        // Ambil address dari cement
        $cementAddresses = \App\Models\Cement::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

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
