<?php
// app/Http/Controllers/StoreController.php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    /**
     * Display a listing of stores
     */
    public function index(Request $request)
    {
        $query = Store::with([
            'locations' => function ($q) {
                $q->withCount('materialAvailabilities');
            },
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhereHas('locations', function ($q) use ($search) {
                    $q->where('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('province', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = ['name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $stores = $query->orderBy($sortBy, $sortDirection)->get();

        return view('stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new store
     */
    public function create()
    {
        return view('stores.create');
    }

    /**
     * Store a newly created store
     */
    public function store(Request $request)
    {
        $request->merge([
            'contact_name' => $this->normalizeContactField($request->input('contact_name')),
            'contact_phone' => $this->normalizeContactField($request->input('contact_phone')),
        ]);

        $request->validate(
            [
                'name' => 'required|string|max:255',
                // Location fields
                'address' => 'nullable|string',
                'district' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'place_id' => 'nullable|string|max:255',
                'formatted_address' => 'nullable|string',
                'service_radius_km' => 'nullable|numeric|min:0',
                'contact_name' => 'nullable|array',
                'contact_name.*' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|array',
                'contact_phone.*' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s]*$/'],
            ],
            [
                'contact_phone.*.regex' => 'No telepon hanya boleh berisi angka, spasi, tanda +, dan -.',
            ],
        );

        $contactPairs = $this->normalizeContactPairs($request->input('contact_name', []), $request->input('contact_phone', []));
        $contactNameText = $this->flattenContactColumn($contactPairs, 'name');
        $contactPhoneText = $this->flattenContactColumn($contactPairs, 'phone');

        if ($contactNameText !== null && mb_strlen($contactNameText) > 255) {
            return back()
                ->withErrors(['contact_name' => 'Total gabungan nama kontak maksimal 255 karakter.'])
                ->withInput();
        }

        if ($contactPhoneText !== null && mb_strlen($contactPhoneText) > 255) {
            return back()
                ->withErrors(['contact_phone' => 'Total gabungan nomor kontak maksimal 255 karakter.'])
                ->withInput();
        }

        DB::beginTransaction();
        try {

            $storeData = [
                'name' => $request->name,
            ];

            // Create store
            $store = Store::create($storeData);

            // Create initial location if any location-related data is provided
            $hasInitialLocationData =
                $request->filled('address') ||
                $request->filled('district') ||
                $request->filled('city') ||
                $request->filled('province') ||
                $request->filled('latitude') ||
                $request->filled('longitude') ||
                $request->filled('place_id') ||
                $request->filled('formatted_address') ||
                $request->filled('service_radius_km') ||
                $contactNameText !== null ||
                $contactPhoneText !== null;

            if ($hasInitialLocationData) {
                $store->locations()->create([
                    'address' => $request->address,
                    'district' => $request->district,
                    'city' => $request->city,
                    'province' => $request->province,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'place_id' => $request->place_id,
                    'formatted_address' => $request->formatted_address,
                    'service_radius_km' => $request->service_radius_km,
                    'contact_name' => $contactNameText,
                    'contact_phone' => $contactPhoneText,
                ]);
            }

            DB::commit();

            return redirect()->route('stores.index')->with('success', 'Toko berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create store: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal menyimpan toko: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function normalizeContactField(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }

    private function normalizeContactPairs(array $names, array $phones): array
    {
        $count = max(count($names), count($phones));
        $pairs = [];

        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $phone = trim((string) ($phones[$i] ?? ''));

            if ($name === '' && $phone === '') {
                continue;
            }

            $pairs[] = [
                'name' => $name,
                'phone' => $phone,
            ];
        }

        return $pairs;
    }

    private function flattenContactColumn(array $pairs, string $column): ?string
    {
        if (empty($pairs)) {
            return null;
        }

        $values = array_map(
            fn(array $pair) => $pair[$column] !== '' ? $pair[$column] : '-',
            $pairs,
        );

        $text = implode(' | ', $values);

        return $text !== '' ? $text : null;
    }

    /**
     * Display the specified store
     */
    public function show(Store $store)
    {
        $store->load([
            'locations' => function ($q) {
                $q->withCount('materialAvailabilities');
            },
            'locations.materialAvailabilities.materialable',
        ]);

        return view('stores.show', compact('store'));
    }

    /**
     * Show the form for editing the specified store
     */
    public function edit(Store $store)
    {
        $store->load('locations');
        return view('stores.edit', compact('store'));
    }

    /**
     * Update the specified store
     */
    public function update(Request $request, Store $store)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $storeData = [
                'name' => $request->name,
            ];

            $store->update($storeData);

            DB::commit();

            return redirect()->route('stores.index')->with('success', 'Toko berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update store: ' . $e->getMessage());
            return back()
                ->with('error', 'Gagal update toko: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified store
     */
    public function destroy(Store $store)
    {
        DB::beginTransaction();
        try {
            // Delete store (cascade akan handle locations & availabilities)
            $store->delete();

            DB::commit();

            return redirect()->route('stores.index')->with('success', 'Toko berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete store: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus toko: ' . $e->getMessage());
        }
    }

    /**
     * Show locations for a specific store
     */
    public function locations(Store $store)
    {
        $store->load([
            'locations' => function ($q) {
                $q->withCount('materialAvailabilities');
            },
        ]);

        return view('stores.locations', compact('store'));
    }
}
