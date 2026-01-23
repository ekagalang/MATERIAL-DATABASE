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
        $request->validate([
            'name' => 'required|string|max:255',
            // Location fields
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $storeData = [
                'name' => $request->name,
            ];

            // Create store
            $store = Store::create($storeData);

            // Create initial location if any location data is provided
            if ($request->filled(['address', 'city', 'province', 'contact_phone'])) {
                $store->locations()->create([
                    'address' => $request->address,
                    'district' => $request->district,
                    'city' => $request->city,
                    'province' => $request->province,
                    'contact_name' => $request->contact_name,
                    'contact_phone' => $request->contact_phone,
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
