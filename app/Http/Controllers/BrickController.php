<?php

namespace App\Http\Controllers;

use App\Actions\Material\CreateMaterialAction;
use App\Actions\Material\DeleteMaterialAction;
use App\Actions\Material\UpdateMaterialAction;
use App\Http\Requests\Material\BrickUpsertRequest;
use App\Models\Brick;
use App\Services\Material\MaterialDuplicateService;
use App\Services\Material\MaterialPhotoService;
use App\Support\Material\MaterialIndexQuery;
use App\Support\Material\MaterialIndexSpec;
use App\Support\Material\MaterialLookupQuery;
use App\Support\Material\MaterialLookupSpec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrickController extends Controller
{
    public function index(Request $request)
    {
        $query = Brick::query();
        $search = MaterialIndexQuery::searchValue($request);
        MaterialIndexQuery::applySearch($query, $search, MaterialIndexSpec::searchColumns('brick'));
        [$sortBy, $sortDirection] = MaterialIndexQuery::resolveSort($request, 'brick');

        $bricks = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('bricks.index', compact('bricks'));
    }

    public function create()
    {
        return view('bricks.create');
    }

    public function store(Request $request)
    {
        $request->validate((new BrickUpsertRequest())->rules());

        $data = $this->prepareBrickData($request);
        app(MaterialDuplicateService::class)->ensureNoDuplicate('brick', $data);

        DB::beginTransaction();
        try {
            $this->handleCreatePhotoUpload($request, $data);

            // Buat brick
            $brick = app(CreateMaterialAction::class)->execute('brick', $data);

            $this->recalculateBrickDerivedFields($brick, false);

            $brick->save();

            $this->syncStoreLocationOnCreate($request, $brick);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('bricks.index'));
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
        $request->validate((new BrickUpsertRequest())->rules());

        $data = $this->prepareBrickData($request);
        app(MaterialDuplicateService::class)->ensureNoDuplicate('brick', $data, $brick->id);

        DB::beginTransaction();
        try {
            $this->handleUpdatePhotoUpload($request, $brick, $data);

            // Update brick
            app(UpdateMaterialAction::class)->execute($brick, $data);

            $this->recalculateBrickDerivedFields($brick, true);

            $brick->save();

            $this->syncStoreLocationOnUpdate($request, $brick);

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('bricks.index'));
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
            app(MaterialPhotoService::class)->delete($brick->photo);

            // NEW: Detach store locations
            $brick->storeLocations()->detach();

            app(DeleteMaterialAction::class)->execute($brick);

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
        $allowedFields = MaterialLookupSpec::allowedFields('brick');

        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

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
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);
        $materialType = MaterialLookupQuery::queryMaterialType($request, 'all'); // 'brick' atau 'all'

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
        $store = MaterialLookupQuery::stringStore($request);
        $search = MaterialLookupQuery::stringSearch($request);
        $limit = MaterialLookupQuery::normalizedLimit($request);

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

    private function prepareBrickData(Request $request): array
    {
        $data = $request->all();
        $data['material_name'] = 'Bata';

        return $data;
    }

    private function handleCreatePhotoUpload(Request $request, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'bricks');
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

    private function handleUpdatePhotoUpload(Request $request, Brick $brick, array &$data): void
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            if ($photo->isValid()) {
                $path = app(MaterialPhotoService::class)->upload($photo, 'bricks', $brick->photo);
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

    private function recalculateBrickDerivedFields(Brick $brick, bool $resetComparisonPriceIfMissing): void
    {
        if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
            $brick->calculateVolume();
        }

        if ($brick->price_per_piece && $brick->package_volume && $brick->package_volume > 0) {
            $brick->calculateComparisonPrice();
            return;
        }

        if ($resetComparisonPriceIfMissing) {
            $brick->comparison_price_per_m3 = null;
        }
    }

    private function syncStoreLocationOnCreate(Request $request, Brick $brick): void
    {
        if ($request->filled('store_location_id')) {
            $brick->storeLocations()->attach($request->input('store_location_id'));
        }
    }

    private function syncStoreLocationOnUpdate(Request $request, Brick $brick): void
    {
        if ($request->filled('store_location_id')) {
            $brick->storeLocations()->sync([$request->input('store_location_id')]);
            return;
        }

        $brick->storeLocations()->detach();
    }
}
