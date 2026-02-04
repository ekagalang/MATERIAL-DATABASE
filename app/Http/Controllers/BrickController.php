<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Services\Material\MaterialDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BrickController extends Controller
{
    public function index(Request $request)
    {
        $query = Brick::query();

        // Pencarian
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('form', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        // Validasi kolom yang boleh di-sort
        $allowedSorts = [
            'material_name',
            'type',
            'brand',
            'form',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'package_volume',
            'store',
            'address',
            'price_per_piece',
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

        $bricks = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('bricks.index', compact('bricks'));
    }

    public function create()
    {
        return view('bricks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'address' => 'nullable|string',
            'price_per_piece' => 'nullable|numeric|min:0',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();
        $data['material_name'] = 'Bata';

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('brick', $data);
        if ($duplicate) {
            $message = 'Data Bata sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

            // Upload foto
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('bricks', $filename, 'public');
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

            // Buat brick
            $brick = Brick::create($data);

            // Kalkulasi volume dari dimensi
            if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
                $brick->calculateVolume();
            }

            // Kalkulasi harga komparasi per M3
            if ($brick->price_per_piece && $brick->package_volume && $brick->package_volume > 0) {
                $brick->calculateComparisonPrice();
            }

            $brick->save();

            // NEW: Attach store location
            if ($request->filled('store_location_id')) {
                $brick->storeLocations()->attach($request->input('store_location_id'));
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('bricks.index'));
            $newMaterial = ['type' => 'brick', 'id' => $brick->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data Bata berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data Bata berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Data Bata berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }

            return redirect()->route('bricks.index')->with('success', 'Data Bata berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create brick: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Brick $brick)
    {
        $brick->load('storeLocations.store'); // NEW
        return view('bricks.show', compact('brick'));
    }

    public function edit(Brick $brick)
    {
        $brick->load('storeLocations.store'); // NEW
        return view('bricks.edit', compact('brick'));
    }

    public function update(Request $request, Brick $brick)
    {
        $request->validate([
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:255',
            'form' => 'nullable|string|max:255',
            'dimension_length' => 'nullable|numeric|min:0',
            'dimension_width' => 'nullable|numeric|min:0',
            'dimension_height' => 'nullable|numeric|min:0',
            'store' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'address' => 'nullable|string',
            'price_per_piece' => 'nullable|numeric|min:0',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();
        $data['material_name'] = 'Bata';

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('brick', $data, $brick->id);
        if ($duplicate) {
            $message = 'Data Bata sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {

            // Upload foto baru
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    if ($brick->photo) {
                        Storage::disk('public')->delete($brick->photo);
                    }

                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('bricks', $filename, 'public');
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

            // Update brick
            $brick->update($data);

            // Kalkulasi volume dari dimensi
            if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
                $brick->calculateVolume();
            }

            // Kalkulasi harga komparasi per M3
            if ($brick->price_per_piece && $brick->package_volume && $brick->package_volume > 0) {
                $brick->calculateComparisonPrice();
            } else {
                $brick->comparison_price_per_m3 = null;
            }

            $brick->save();

            // NEW: Sync store location
            if ($request->filled('store_location_id')) {
                $brick->storeLocations()->sync([$request->input('store_location_id')]);
            } else {
                $brick->storeLocations()->detach();
            }

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials') ? route('materials.index') : route('bricks.index'));
            $updatedMaterial = ['type' => 'brick', 'id' => $brick->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data Bata berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Data Bata berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Data Bata berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route('bricks.index')
                ->with('success', 'Data Bata berhasil diupdate!')
                ->with('updated_material', $updatedMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update brick: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Brick $brick)
    {
        DB::beginTransaction();
        try {
            // Hapus foto
            if ($brick->photo) {
                Storage::disk('public')->delete($brick->photo);
            }

            // NEW: Detach store locations
            $brick->storeLocations()->detach();

            $brick->delete();

            DB::commit();

            return redirect()->route('bricks.index')->with('success', 'Data Bata berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete brick: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
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
            'form',
            'store',
            'address',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'price_per_piece',
        ];

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = Brick::query()->whereNotNull($field)->where($field, '!=', '');

        // Filter berdasarkan parent selections
        // Bentuk: filter by merek
        if ($field === 'form' && $request->has('brand') && $request->brand !== '') {
            $query->where('brand', $request->brand);
        }

        // Dimensi: filter by merek
        if (
            in_array($field, ['dimension_length', 'dimension_width', 'dimension_height']) &&
            $request->has('brand') &&
            $request->brand !== ''
        ) {
            $query->where('brand', $request->brand);
        }

        // Alamat: filter by toko
        if ($field === 'address' && $request->has('store') && $request->store !== '') {
            $query->where('store', $request->store);
        }

        // Harga: filter by dimensi (semua 3 dimensi harus cocok)
        if ($field === 'price_per_piece') {
            if ($request->has('dimension_length') && $request->dimension_length !== '') {
                $query->where('dimension_length', $request->dimension_length);
            }
            if ($request->has('dimension_width') && $request->dimension_width !== '') {
                $query->where('dimension_width', $request->dimension_width);
            }
            if ($request->has('dimension_height') && $request->dimension_height !== '') {
                $query->where('dimension_height', $request->dimension_height);
            }
        }

        if ($search !== '') {
            $query->where($field, 'like', "%{$search}%");
        }

        // Ambil nilai unik, dibatasi
        $values = $query->select($field)->groupBy($field)->orderBy($field)->limit($limit)->pluck($field);

        return response()->json($values);
    }

    /**
     * API untuk mendapatkan semua stores dari brick atau semua material
     */
    public function getAllStores(Request $request)
    {
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = $request->query('material_type', 'all'); // 'brick' atau 'all'

        $stores = collect();

        // Jika tidak ada search term, hanya tampilkan stores dari brick
        // Jika ada search term, tampilkan dari semua material
        if ($materialType === 'brick' || ($search === '' && $materialType === 'all')) {
            // Tampilkan dari brick saja
            $brickStores = Brick::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores->merge($brickStores)->unique()->sort()->values()->take($limit);
        } else {
            // Tampilkan dari semua material (saat user mengetik)
            $catStores = \App\Models\Cat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $brickStores = Brick::query()
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

        // Ambil address dari brick yang sesuai dengan toko
        $brickAddresses = Brick::query()
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
            ->merge($brickAddresses)
            ->merge($catAddresses)
            ->merge($cementAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }
}
