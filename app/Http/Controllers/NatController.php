<?php

namespace App\Http\Controllers;

use App\Models\Nat;
use App\Services\Material\MaterialDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class NatController extends Controller
{
    public function index(Request $request)
    {
        $query = Nat::query()->with('packageUnit');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('nat_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('sub_brand', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('store', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction');

        $sortMap = [
            'type' => 'type',
            'nat_name' => 'nat_name',
            'brand' => 'brand',
            'sub_brand' => 'sub_brand',
            'code' => 'code',
            'color' => 'color',
            'package_weight' => 'package_weight_net',
            'package_weight_net' => 'package_weight_net',
            'store' => 'store',
            'address' => 'address',
            'price_per_bag' => 'package_price',
            'package_price' => 'package_price',
            'comparison_price_per_kg' => 'comparison_price_per_kg',
            'created_at' => 'created_at',
        ];

        if (!$sortBy || !isset($sortMap[$sortBy])) {
            $sortBy = 'created_at';
            $sortDirection = 'desc';
        } else {
            if (!in_array($sortDirection, ['asc', 'desc'], true)) {
                $sortDirection = 'asc';
            }
            $sortBy = $sortMap[$sortBy];
        }

        $nats = $query->orderBy($sortBy, $sortDirection)->paginate(15)->appends($request->query());

        return view('nats.index', compact('nats'));
    }

    public function create()
    {
        $units = Nat::getAvailableUnits();

        return view('nats.create', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('nat', $data);
        if ($duplicate) {
            $message = 'Data Nat sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('nats', $filename, 'public');
                    if ($path) {
                        $data['photo'] = $path;
                    }
                }
            }

            if (empty($data['nat_name'])) {
                $parts = array_filter([
                    $data['type'] ?? '',
                    $data['brand'] ?? '',
                    $data['sub_brand'] ?? '',
                    $data['code'] ?? '',
                    $data['color'] ?? '',
                ]);
                $data['nat_name'] = implode(' ', $parts) ?: 'Nat';
            }

            $nat = Nat::create($data);

            if ($request->filled('store_location_id')) {
                $nat->storeLocations()->attach($request->input('store_location_id'));
            }

            if (
                (!$nat->package_weight_net || $nat->package_weight_net <= 0) &&
                $nat->package_weight_gross &&
                $nat->package_unit
            ) {
                $nat->calculateNetWeight();
            }

            if ($nat->package_price && $nat->package_weight_net && $nat->package_weight_net > 0) {
                $nat->calculateComparisonPrice();
            } else {
                $nat->comparison_price_per_kg = null;
            }

            $nat->save();

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('nats.index'));
            $newMaterial = ['type' => 'nat', 'id' => $nat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nat berhasil ditambahkan!',
                    'redirect_url' => $redirectUrl,
                    'new_material' => $newMaterial,
                ]);
            }

            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Nat berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Nat berhasil ditambahkan!')
                    ->with('new_material', $newMaterial);
            }

            return redirect()->route('nats.index')->with('success', 'Nat berhasil ditambahkan!');
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
        $request->validate([
            'nat_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'package_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:20',
            'store_location_id' => 'nullable|exists:store_locations,id',
        ]);

        $data = $request->all();

        $duplicate = app(MaterialDuplicateService::class)->findDuplicate('nat', $data, $nat->id);
        if ($duplicate) {
            $message = 'Data Nat sudah ada. Tidak bisa menyimpan data duplikat.';
            throw ValidationException::withMessages(['duplicate' => $message]);
        }

        DB::beginTransaction();
        try {
            if (empty($data['nat_name'])) {
                $parts = array_filter([
                    $data['type'] ?? '',
                    $data['brand'] ?? '',
                    $data['sub_brand'] ?? '',
                    $data['code'] ?? '',
                    $data['color'] ?? '',
                ]);
                $data['nat_name'] = implode(' ', $parts) ?: 'Nat';
            }

            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                if ($photo->isValid()) {
                    if ($nat->photo) {
                        Storage::disk('public')->delete($nat->photo);
                    }

                    $filename = time() . '_' . $photo->getClientOriginalName();
                    $path = $photo->storeAs('nats', $filename, 'public');
                    if ($path) {
                        $data['photo'] = $path;
                    }
                }
            }

            $nat->update($data);

            if ($request->filled('store_location_id')) {
                $nat->storeLocations()->sync([$request->input('store_location_id')]);
            }

            if (
                (!$nat->package_weight_net || $nat->package_weight_net <= 0) &&
                $nat->package_weight_gross &&
                $nat->package_unit
            ) {
                $nat->calculateNetWeight();
            }

            if ($nat->package_price && $nat->package_weight_net && $nat->package_weight_net > 0) {
                $nat->calculateComparisonPrice();
            } else {
                $nat->comparison_price_per_kg = null;
            }

            $nat->save();

            DB::commit();

            $redirectUrl = $request->filled('_redirect_url')
                ? $request->input('_redirect_url')
                : ($request->input('_redirect_to_materials')
                    ? route('materials.index')
                    : route('nats.index'));
            $updatedMaterial = ['type' => 'nat', 'id' => $nat->id];
            $isAjaxRequest = $request->expectsJson() || $request->ajax();

            if ($isAjaxRequest) {
                return response()->json([
                    'success' => true,
                    'message' => 'Nat berhasil diupdate!',
                    'redirect_url' => $redirectUrl,
                    'updated_material' => $updatedMaterial,
                ]);
            }

            if ($request->filled('_redirect_url')) {
                return redirect()
                    ->to($request->input('_redirect_url'))
                    ->with('success', 'Nat berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }
            if ($request->input('_redirect_to_materials')) {
                return redirect()
                    ->route('materials.index')
                    ->with('success', 'Nat berhasil diupdate!')
                    ->with('updated_material', $updatedMaterial);
            }

            return redirect()
                ->route('nats.index')
                ->with('success', 'Nat berhasil diupdate!')
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
            if ($nat->photo) {
                Storage::disk('public')->delete($nat->photo);
            }

            $nat->delete();

            DB::commit();

            return redirect()->route('nats.index')->with('success', 'Nat berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function getFieldValues(string $field, Request $request)
    {
        $fieldMap = [
            'type' => 'type',
            'nat_name' => 'nat_name',
            'brand' => 'brand',
            'sub_brand' => 'sub_brand',
            'code' => 'code',
            'color' => 'color',
            'store' => 'store',
            'address' => 'address',
            'price_unit' => 'price_unit',
            'package_weight_gross' => 'package_weight_gross',
            'package_price' => 'package_price',
        ];

        if (!isset($fieldMap[$field])) {
            return response()->json([]);
        }

        $column = $fieldMap[$field];
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $brand = (string) $request->query('brand', '');
        $packageUnit = (string) $request->query('package_unit', '');
        $store = (string) $request->query('store', '');

        $query = Nat::query()->whereNotNull($column)->where($column, '!=', '');

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
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $materialType = $request->query('material_type', 'all');

        $stores = collect();

        if ($materialType === 'nat' || ($search === '' && $materialType === 'all')) {
            $natStores = Nat::query()
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

            $natStores = Nat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $allStores = $stores
                ->merge($catStores)
                ->merge($brickStores)
                ->merge($cementStores)
                ->merge($sandStores)
                ->merge($natStores)
                ->unique()
                ->sort()
                ->values()
                ->take($limit);
        }

        return response()->json($allStores);
    }

    public function getAddressesByStore(Request $request)
    {
        $store = (string) $request->query('store', '');
        $search = (string) $request->query('search', '');
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        if ($store === '') {
            return response()->json([]);
        }

        $addresses = collect();

        $natAddresses = Nat::query()
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

        $cementAddresses = \App\Models\Cement::query()
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
            ->merge($cementAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);

        return response()->json($allAddresses);
    }
}
