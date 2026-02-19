<?php

namespace App\Http\Controllers;

use App\Actions\Material\CreateMaterialAction;
use App\Actions\Material\DeleteMaterialAction;
use App\Actions\Material\UpdateMaterialAction;
use App\Http\Requests\Material\NatUpsertRequest;
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

class NatController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('materials.index', ['tab' => 'cement']);
    }

    public function create()
    {
        return redirect()->route('cements.create');
    }

    public function store(Request $request)
    {
        $request->validate((new NatUpsertRequest())->rules());

        $data = $request->all();
        $detectedMaterialType = $this->resolveMaterialTypeFromInput($data, 'nat');
        app(MaterialDuplicateService::class)->ensureNoDuplicate($detectedMaterialType, $data);

        DB::beginTransaction();
        try {
            $this->handleCreatePhotoUpload($request, $data);
            $this->ensureNatName($data);

            $nat = app(CreateMaterialAction::class)->execute('nat', $data);

            $this->syncStoreLocationOnSave(
                $request,
                $nat->id,
                $this->resolveMaterialTypeFromKind($nat->material_kind),
            );
            $this->recalculateNatDerivedFields($nat);

            $nat->save();

            DB::commit();

            $materialType = $this->resolveMaterialTypeFromKind($nat->material_kind);
            $materialLabel = MaterialKindResolver::labelFromKind($materialType);
            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route(MaterialKindResolver::indexRouteNameFromKind($materialType)));
            $newMaterial = ['type' => $materialType, 'id' => $nat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $materialLabel . ' berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', $materialLabel . ' berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
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

            return back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Nat $nat)
    {
        $nat->load('packageUnit', 'storeLocation');

        return view('nats.show', compact('nat'));
    }

    public function edit(Nat $nat)
    {
        $nat->load('storeLocation');
        $units = Nat::getAvailableUnits();

        return view('nats.edit', compact('nat', 'units'));
    }

    public function update(Request $request, Nat $nat)
    {
        $request->validate((new NatUpsertRequest())->rules());

        $data = $request->all();
        $detectedMaterialType = $this->resolveMaterialTypeFromInput($data, 'nat');
        app(MaterialDuplicateService::class)->ensureNoDuplicate($detectedMaterialType, $data, $nat->id);

        DB::beginTransaction();
        try {
            $this->ensureNatName($data);
            $this->handleUpdatePhotoUpload($request, $nat, $data);

            app(UpdateMaterialAction::class)->execute($nat, $data);

            $this->syncStoreLocationOnSave(
                $request,
                $nat->id,
                $this->resolveMaterialTypeFromKind($nat->material_kind),
            );
            $this->recalculateNatDerivedFields($nat);

            $nat->save();

            DB::commit();

            $materialType = $this->resolveMaterialTypeFromKind($nat->material_kind);
            $materialLabel = MaterialKindResolver::labelFromKind($materialType);
            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route(MaterialKindResolver::indexRouteNameFromKind($materialType)));
            $updatedMaterial = ['type' => $materialType, 'id' => $nat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => $materialLabel . ' berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', $materialLabel . ' berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
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

            return back()
                ->with('error', 'Gagal update data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Nat $nat)
    {
        DB::beginTransaction();
        try {
            app(MaterialPhotoService::class)->delete($nat->photo);

            app(DeleteMaterialAction::class)->execute($nat);

            DB::commit();

            return redirect()->route('nats.index')->with('success', 'Nat berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function getFieldValues(string $field, Request $request)
    {
        $fieldMap = MaterialLookupSpec::fieldMap('nat');

        if (!isset($fieldMap[$field])) {
            return response()->json([]);
        }

        $column = $fieldMap[$field];
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $kinds = $this->resolveAutocompleteKinds($request, 'nat');

        $brand = (string) $request->query('brand', '');
        $packageUnit = (string) $request->query('package_unit', '');
        $store = (string) $request->query('store', '');

        $query = DB::table('cements')
            ->whereIn('material_kind', $kinds)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        if (in_array($field, ['sub_brand', 'code', 'color', 'package_weight_gross'], true)) {
            if ($brand !== '') {
                $query->where('brand', $brand);
            }
        }

        if ($field === 'package_price') {
            if ($packageUnit !== '') {
                $query->where('package_unit', $packageUnit);
            }
        }

        if ($field === 'address') {
            if ($store !== '') {
                $query->where('store', $store);
            }
        }

        if ($search !== '') {
            $query->where($column, 'like', "%{$search}%");
        }

        $values = $query->select($column)->groupBy($column)->orderBy($column)->limit($limit)->pluck($column);

        return response()->json($values);
    }

    public function getAllStores(Request $request)
    {
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $materialType = MaterialLookupQuery::queryMaterialType($request, 'all');
        $kinds = $this->resolveAutocompleteKinds($request, 'nat');

        $stores = collect();

        if ($materialType === 'nat' || ($search === '' && $materialType === 'all')) {
            $natStores = DB::table('cements')
                ->whereIn('material_kind', $kinds)
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores->merge($natStores)->unique()->sort()->values()->take($limit);
        } else {
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

    public function getAddressesByStore(Request $request)
    {
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $kinds = $this->resolveAutocompleteKinds($request, 'nat');

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = collect();

        $natAddresses = DB::table('cements')
            ->whereIn('material_kind', $kinds)
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $catAddresses = \App\Models\Cat::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $brickAddresses = \App\Models\Brick::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $sandAddresses = \App\Models\Sand::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $allAddresses = $addresses
            ->merge($natAddresses)
            ->merge($catAddresses)
            ->merge($brickAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }

    private function handleCreatePhotoUpload(Request $request, array &$data): void
    {
        $path = app(MaterialPhotoService::class)->upload($request->file('photo'), 'nats');
        if ($path) {
            $data['photo'] = $path;
        }
    }

    private function handleUpdatePhotoUpload(Request $request, Nat $nat, array &$data): void
    {
        $path = app(MaterialPhotoService::class)->upload($request->file('photo'), 'nats', $nat->photo);
        if ($path) {
            $data['photo'] = $path;
        }
    }

    private function ensureNatName(array &$data): void
    {
        if (!empty($data['nat_name'])) {
            return;
        }

        $parts = array_filter([
            $data['type'] ?? '',
            $data['brand'] ?? '',
            $data['sub_brand'] ?? '',
            $data['code'] ?? '',
            $data['color'] ?? '',
        ]);
        $data['nat_name'] = implode(' ', $parts) ?: 'Nat';
    }

    private function syncStoreLocationOnCreate(Request $request, Nat $nat): void
    {
        if ($request->filled('store_location_id')) {
            $nat->storeLocations()->attach($request->input('store_location_id'));
        }
    }

    private function syncStoreLocationOnUpdate(Request $request, Nat $nat): void
    {
        if ($request->filled('store_location_id')) {
            $nat->storeLocations()->sync([$request->input('store_location_id')]);
        }
    }

    private function recalculateNatDerivedFields(Nat $nat): void
    {
        if (
            (!$nat->package_weight_net || $nat->package_weight_net <= 0) &&
            $nat->package_weight_gross &&
            $nat->package_unit
        ) {
            $nat->calculateNetWeight();
        }

        if ($nat->package_price && $nat->package_weight_net && $nat->package_weight_net > 0) {
            $nat->calculateComparisonPrice();
            return;
        }

        $nat->comparison_price_per_kg = null;
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

    private function resolveMaterialTypeFromInput(array $data, string $fallback = 'nat'): string
    {
        return MaterialKindResolver::inferFromType($data['type'] ?? null, $fallback);
    }

    private function resolveMaterialTypeFromKind(?string $kind): string
    {
        return MaterialKindResolver::normalizeKind($kind);
    }
}
