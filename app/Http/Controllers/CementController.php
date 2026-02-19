<?php

namespace App\Http\Controllers;

use App\Actions\Material\CreateMaterialAction;
use App\Actions\Material\DeleteMaterialAction;
use App\Actions\Material\UpdateMaterialAction;
use App\Http\Requests\Material\CementUpsertRequest;
use App\Models\Cement;
use App\Models\Nat;
use App\Services\Material\MaterialDuplicateService;
use App\Services\Material\MaterialPhotoService;
use App\Support\Material\MaterialIndexQuery;
use App\Support\Material\MaterialIndexSpec;
use App\Support\Material\MaterialKindResolver;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CementController extends Controller
{
    public function index(Request $request)
    {
        $query = Cement::query()->with('packageUnit');
        $search = MaterialIndexQuery::searchValue($request);
        MaterialIndexQuery::applySearch($query, $search, MaterialIndexSpec::searchColumns('cement'));
        [$sortBy, $sortDirection] = MaterialIndexQuery::resolveSort($request, 'cement');

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
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

        $request->validate((new CementUpsertRequest())->rules());

        $data = $request->all();
        $detectedMaterialType = $this->resolveMaterialTypeFromInput($data, 'cement');
        app(MaterialDuplicateService::class)->ensureNoDuplicate($detectedMaterialType, $data);

        DB::beginTransaction();
        try {
            $this->handleCreatePhotoUpload($request, $data);
            $this->ensureCementName($data);

            // Buat cement
            $cement = app(CreateMaterialAction::class)->execute('cement', $data);

            $this->recalculateCementDerivedFields($cement, false);

            $cement->save();

            $this->syncStoreLocationOnSave(
                $request,
                $cement->id,
                $this->resolveMaterialTypeFromKind($cement->material_kind),
            );

            DB::commit();

            $materialType = $this->resolveMaterialTypeFromKind($cement->material_kind);
            $materialLabel = MaterialKindResolver::labelFromKind($materialType);
            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route(MaterialKindResolver::indexRouteNameFromKind($materialType)));
            $newMaterial = ['type' => $materialType, 'id' => $cement->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $materialLabel . ' berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', $materialLabel . ' berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', $materialLabel . ' berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }

            return redirect()
                ->route(MaterialKindResolver::indexRouteNameFromKind($materialType))
                ->with('success', $materialLabel . ' berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create cement: ' . $e->getMessage());

            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Cement $cement)
    {
        $cement->load('packageUnit', 'storeLocations.store'); // UPDATED

        return view('cements.show', compact('cement'));
    }

    public function edit(Cement $cement)
    {
        $cement->load('storeLocations.store'); // NEW
        $units = Cement::getAvailableUnits();

        return view('cements.edit', compact('cement', 'units'));
    }

    public function update(Request $request, Cement $cement)
    {
        // Normalize comma decimal separator (Indonesian format) to dot
        $this->normalizeDecimalFields($request);

        $request->validate((new CementUpsertRequest())->rules());

        $data = $request->all();
        $detectedMaterialType = $this->resolveMaterialTypeFromInput($data, 'cement');
        app(MaterialDuplicateService::class)->ensureNoDuplicate($detectedMaterialType, $data, $cement->id);

        DB::beginTransaction();
        try {
            $this->ensureCementName($data);
            $this->handleUpdatePhotoUpload($request, $cement, $data);

            // Update cement
            app(UpdateMaterialAction::class)->execute($cement, $data);

            $this->recalculateCementDerivedFields($cement, true);

            $cement->save();

            $this->syncStoreLocationOnSave(
                $request,
                $cement->id,
                $this->resolveMaterialTypeFromKind($cement->material_kind),
            );

            DB::commit();

            $materialType = $this->resolveMaterialTypeFromKind($cement->material_kind);
            $materialLabel = MaterialKindResolver::labelFromKind($materialType);
            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route(MaterialKindResolver::indexRouteNameFromKind($materialType)));
            $updatedMaterial = ['type' => $materialType, 'id' => $cement->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $materialLabel . ' berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            // Redirect back to the originating page if requested
            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', $materialLabel . ' berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
            // Backward compatibility for older forms
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', $materialLabel . ' berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route(MaterialKindResolver::indexRouteNameFromKind($materialType))
                ->with('success', $materialLabel . ' berhasil diupdate!')
                ->with('updated_material', $updatedMaterial);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update cement: ' . $e->getMessage());

            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Cement $cement)
    {
        DB::beginTransaction();
        try {
            // Hapus foto
            app(MaterialPhotoService::class)->delete($cement->photo);

            // NEW: Detach store locations
            $cement->storeLocations()->detach();

            app(DeleteMaterialAction::class)->execute($cement);

            DB::commit();

            return redirect()->route('cements.index')->with('success', 'Semen berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete cement: ' . $e->getMessage());

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

    private function handleCreatePhotoUpload(Request $request, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'cements');
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

    private function handleUpdatePhotoUpload(Request $request, Cement $cement, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'cements', $cement->photo);
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

    private function ensureCementName(array &$data): void
    {
        if (!empty($data['cement_name'])) {
            return;
        }

        $parts = array_filter([
            $data['type'] ?? '',
            $data['brand'] ?? '',
            $data['sub_brand'] ?? '',
            $data['code'] ?? '',
            $data['color'] ?? '',
        ]);
        $data['cement_name'] = implode(' ', $parts) ?: 'Semen';
    }

    private function recalculateCementDerivedFields(Cement $cement, bool $resetComparisonPriceIfMissing): void
    {
        if (
            (!$cement->package_weight_net || $cement->package_weight_net <= 0) &&
            $cement->package_weight_gross &&
            $cement->package_unit
        ) {
            $cement->calculateNetWeight();
        }

        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->comparison_price_per_kg = $cement->package_price / $cement->package_weight_net;
            return;
        }

        if ($resetComparisonPriceIfMissing) {
            $cement->comparison_price_per_kg = null;
        }
    }

    private function syncStoreLocationOnCreate(Request $request, Cement $cement): void
    {
        if ($request->filled('store_location_id')) {
            $cement->storeLocations()->attach($request->input('store_location_id'));
        }
    }

    private function syncStoreLocationOnUpdate(Request $request, Cement $cement): void
    {
        if ($request->filled('store_location_id')) {
            $cement->storeLocations()->sync([$request->input('store_location_id')]);
            return;
        }

        $cement->storeLocations()->detach();
    }

    private function syncStoreLocationOnSave(Request $request, int $materialId, string $materialType): void
    {
        $storeLocationId = $request->filled('store_location_id') ? (int) $request->input('store_location_id') : null;

        DB::table('store_material_availabilities')
            ->where('materialable_id', $materialId)
            ->whereIn('materialable_type', [Cement::class, Nat::class])
            ->delete();

        if (!$storeLocationId) {
            return;
        }

        if ($materialType === 'nat') {
            $nat = Nat::query()->find($materialId);
            if ($nat) {
                $nat->storeLocations()->syncWithoutDetaching([$storeLocationId]);
            }

            return;
        }

        $cement = Cement::query()->find($materialId);
        if ($cement) {
            $cement->storeLocations()->syncWithoutDetaching([$storeLocationId]);
        }
    }

    private function resolveMaterialTypeFromInput(array $data, string $fallback = 'cement'): string
    {
        return MaterialKindResolver::inferFromType($data['type'] ?? null, $fallback);
    }

    private function resolveMaterialTypeFromKind(?string $kind): string
    {
        return MaterialKindResolver::normalizeKind($kind);
    }

    // API untuk mendapatkan unique values per field
    public function getFieldValues(string $field, Request $request)
    {
        // Bidang yang diizinkan untuk auto-suggest
        $allowedFields = MaterialLookupSpec::allowedFields('cement');

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $kinds = $this->resolveAutocompleteKinds($request, 'cement');

        // Get filter parameters for cascading autocomplete
        $brand = (string) $request->query('brand', '');
        $packageUnit = (string) $request->query('package_unit', '');
        $store = (string) $request->query('store', '');

        $query = DB::table('cements')
            ->whereIn('material_kind', $kinds)
            ->whereNotNull($field)
            ->where($field, '!=', '');

        // Apply cascading filters based on field
        // Fields that depend on brand selection
        if (in_array($field, ['sub_brand', 'code', 'color', 'package_weight_gross'])) {
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

            // Get from cement/nat based on kinds filter
            $cementStores = DB::table('cements')
                ->whereIn('material_kind', $kinds)
                ->whereNotNull('store')
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
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $materialType = MaterialLookupQuery::queryMaterialType($request, 'all'); // 'cement' atau 'all'
        $kinds = $this->resolveAutocompleteKinds($request, 'cement');

        $stores = collect();

        // Jika tidak ada search term, hanya tampilkan stores dari cement
        // Jika ada search term, tampilkan dari semua material
        if ($materialType === 'cement' || ($search === '' && $materialType === 'all')) {
            // Tampilkan dari material cements table sesuai filter kinds
            $cementStores = DB::table('cements')
                ->whereIn('material_kind', $kinds)
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

            $cementStores = DB::table('cements')
                ->whereIn('material_kind', $kinds)
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
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $kinds = $this->resolveAutocompleteKinds($request, 'cement');

        // Jika tidak ada toko yang dipilih, return empty
        if ($store === '') {
            return response()->json([]);
        }

        $addresses = collect();

        // Ambil address dari cement yang sesuai dengan toko
        $cementAddresses = DB::table('cements')
            ->whereIn('material_kind', $kinds)
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

    /**
     * @return array<int, string>
     */
    private function resolveAutocompleteKinds(Request $request, string $defaultKind): array
    {
        $rawKinds = strtolower((string) $request->query('kinds', ''));
        if (trim($rawKinds) === '') {
            return [$defaultKind];
        }

        $kinds = collect(explode(',', $rawKinds))
            ->map(fn($kind) => trim($kind))
            ->filter(fn($kind) => in_array($kind, ['cement', 'nat'], true))
            ->unique()
            ->values()
            ->all();

        return !empty($kinds) ? $kinds : [$defaultKind];
    }
}
