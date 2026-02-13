<?php

namespace App\Http\Controllers;

use App\Actions\Material\CreateMaterialAction;
use App\Actions\Material\DeleteMaterialAction;
use App\Actions\Material\UpdateMaterialAction;
use App\Http\Requests\Material\CatStoreRequest;
use App\Http\Requests\Material\CatUpdateRequest;
use App\Models\Cat;
use App\Models\Unit;
use App\Services\Material\MaterialDuplicateService;
use App\Services\Material\MaterialPhotoService;
use App\Support\Material\MaterialIndexQuery;
use App\Support\Material\MaterialIndexSpec;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatController extends Controller
{
    public function index(Request $request)
    {
        $query = Cat::query()->with('packageUnit');
        $search = MaterialIndexQuery::searchValue($request);
        MaterialIndexQuery::applySearch($query, $search, MaterialIndexSpec::searchColumns('cat'));
        [$sortBy, $sortDirection] = MaterialIndexQuery::resolveSort($request, 'cat');

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

        $request->validate((new CatStoreRequest())->rules());

        $data = $request->all();
        app(MaterialDuplicateService::class)->ensureNoDuplicate('cat', $data);

        DB::beginTransaction();
        try {
            $this->logStoreRequest($request);
            $this->handleCreatePhotoUpload($request, $data);
            $this->ensureCatName($data);

            // Buat cat
            $cat = app(CreateMaterialAction::class)->execute('cat', $data);

            $this->recalculateCatDerivedFields($cat, false);

            $cat->save();

            $this->syncStoreLocationOnCreate($request, $cat);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('cats.index'));
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

        $request->validate((new CatUpdateRequest())->rules());

        $data = $request->all();
        app(MaterialDuplicateService::class)->ensureNoDuplicate('cat', $data, $cat->id);

        DB::beginTransaction();
        try {
            $this->logUpdateRequest($request, $cat);
            $this->ensureCatName($data);
            $this->handleUpdatePhotoUpload($request, $cat, $data);

            // Update cat
            app(UpdateMaterialAction::class)->execute($cat, $data);

            $this->recalculateCatDerivedFields($cat, true);

            $cat->save();

            $this->syncStoreLocationOnUpdate($request, $cat);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('cats.index'));
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
            app(MaterialPhotoService::class)->delete($cat->photo);

            // NEW: Detach store locations
            $cat->storeLocations()->detach();

            app(DeleteMaterialAction::class)->execute($cat);

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

    private function logStoreRequest(Request $request): void
    {
        \Log::info('CatController@store - Request data:', [
            'package_weight_gross' => $request->input('package_weight_gross'),
            'package_weight_net' => $request->input('package_weight_net'),
            'package_unit' => $request->input('package_unit'),
        ]);
    }

    private function logUpdateRequest(Request $request, Cat $cat): void
    {
        \Log::info('CatController@update - Request data:', [
            'id' => $cat->id,
            'package_weight_gross' => $request->input('package_weight_gross'),
            'package_weight_net' => $request->input('package_weight_net'),
            'package_unit' => $request->input('package_unit'),
        ]);
    }

    private function handleCreatePhotoUpload(Request $request, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'cats');
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
    }

    private function handleUpdatePhotoUpload(Request $request, Cat $cat, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'cats', $cat->photo);
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
    }

    private function ensureCatName(array &$data): void
    {
        if (!empty($data['cat_name'])) {
            return;
        }

        $parts = array_filter([
            $data['type'] ?? '',
            $data['brand'] ?? '',
            $data['sub_brand'] ?? '',
            $data['color_name'] ?? '',
            ($data['volume'] ?? '') . ($data['volume_unit'] ?? ''),
        ]);
        $data['cat_name'] = implode(' ', $parts) ?: 'Cat';
    }

    private function recalculateCatDerivedFields(Cat $cat, bool $resetComparisonPriceIfMissing): void
    {
        if (
            (!$cat->package_weight_net || $cat->package_weight_net <= 0) &&
            $cat->package_weight_gross &&
            $cat->package_unit
        ) {
            $cat->calculateNetWeight();
        }

        if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
            $cat->comparison_price_per_kg = $cat->purchase_price / $cat->package_weight_net;
            return;
        }

        if ($resetComparisonPriceIfMissing) {
            $cat->comparison_price_per_kg = null;
        }
    }

    private function syncStoreLocationOnCreate(Request $request, Cat $cat): void
    {
        if ($request->filled('store_location_id')) {
            $cat->storeLocations()->attach($request->input('store_location_id'));
        }
    }

    private function syncStoreLocationOnUpdate(Request $request, Cat $cat): void
    {
        if ($request->filled('store_location_id')) {
            $cat->storeLocations()->sync([$request->input('store_location_id')]);
            return;
        }

        $cat->storeLocations()->detach();
    }

    // API untuk mendapatkan unique values per field dengan cascading filter
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = MaterialLookupSpec::allowedFields('cat');

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

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
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $materialType = MaterialLookupQuery::queryMaterialType($request, 'all'); // 'cat' atau 'all'

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
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

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
