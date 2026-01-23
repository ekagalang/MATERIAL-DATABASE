<?php
// app/Http/Controllers/StoreLocationController.php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MaterialController;

class StoreLocationController extends Controller
{
    /**
     * Show form to create new location for a store
     */
    public function create(Store $store)
    {
        return view('store-locations.create', compact('store'));
    }

    /**
     * Store a new location
     */
    public function store(Request $request, Store $store)
    {
        $request->validate([
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $store->locations()->create($request->all());

            DB::commit();

            return redirect()->route('stores.show', $store)->with('success', 'Lokasi berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal menambah lokasi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form to edit location
     */
    public function edit(Store $store, StoreLocation $location)
    {
        return view('store-locations.edit', compact('store', 'location'));
    }

    /**
     * Update location
     */
    public function update(Request $request, Store $store, StoreLocation $location)
    {
        $request->validate([
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $location->update($request->all());

            DB::commit();

            return redirect()->route('stores.show', $store)->with('success', 'Lokasi berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal update lokasi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete location
     */
    public function destroy(Store $store, StoreLocation $location)
    {
        DB::beginTransaction();
        try {
            $location->delete();

            DB::commit();

            return redirect()->route('stores.show', $store)->with('success', 'Lokasi berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus lokasi: ' . $e->getMessage());
        }
    }

    /**
     * Display materials available at this specific location
     */
    public function materials(Store $store, StoreLocation $location)
    {
        $search = request('search');
        
        // Eager load materialAvailabilities with their polymorphic relations
        // This effectively gets all materials linked to this location
        $query = $location->materialAvailabilities()->with('materialable');
        
        $availabilities = $query->get();
        
        // Group by material type
        $bricks = collect();
        $cements = collect();
        $sands = collect();
        $cats = collect();
        $ceramics = collect();
        
        foreach ($availabilities as $availability) {
            $material = $availability->materialable;
            if (!$material) continue;
            
            // Search filter logic
            if ($search) {
                $searchableText = strtolower(implode(' ', array_filter([
                    $material->brand ?? '', 
                    $material->sub_brand ?? '', 
                    $material->type ?? '',
                    $material->color ?? '',
                    $material->code ?? ''
                ])));
                
                if (!str_contains($searchableText, strtolower($search))) {
                    continue;
                }
            }
            
            // Categorize based on class type
            $type = class_basename($material);
            
            switch($type) {
                case 'Brick': $bricks->push($material); break;
                case 'Cement': $cements->push($material); break;
                case 'Sand': $sands->push($material); break;
                case 'Cat': $cats->push($material); break;
                case 'Ceramic': $ceramics->push($material); break;
            }
        }

        // Prepare data structure for the view (similar to MaterialController@index)
        $materials = [];

        if ($bricks->count() > 0) {
            $materials[] = [
                'type' => 'brick',
                'label' => 'Bata',
                'count' => $bricks->count(),
                'data' => $bricks,
                'active_letters' => $this->getActiveLetters($bricks)
            ];
        }

        if ($cements->count() > 0) {
            $materials[] = [
                'type' => 'cement',
                'label' => 'Semen',
                'count' => $cements->count(),
                'data' => $cements,
                'active_letters' => $this->getActiveLetters($cements)
            ];
        }

        if ($sands->count() > 0) {
            $materials[] = [
                'type' => 'sand',
                'label' => 'Pasir',
                'count' => $sands->count(),
                'data' => $sands,
                'active_letters' => $this->getActiveLetters($sands)
            ];
        }

        if ($cats->count() > 0) {
            $materials[] = [
                'type' => 'cat',
                'label' => 'Cat',
                'count' => $cats->count(),
                'data' => $cats,
                'active_letters' => $this->getActiveLetters($cats)
            ];
        }

        if ($ceramics->count() > 0) {
            $materials[] = [
                'type' => 'ceramic',
                'label' => 'Keramik',
                'count' => $ceramics->count(),
                'data' => $ceramics,
                'active_letters' => $this->getActiveLetters($ceramics)
            ];
        }
        
        // Get all settings for filter dropdown (required by the view structure)
        $allSettings = \App\Models\MaterialSetting::where('is_visible', true)
            ->orderBy('display_order')
            ->get();

        return view('store-locations.materials', compact('store', 'location', 'materials', 'allSettings'));
    }

    /**
     * Helper to get active letters for grouping
     */
    private function getActiveLetters($collection)
    {
        return $collection->map(function ($item) {
            return strtoupper(substr($item->brand ?? '#', 0, 1));
        })->unique()->sort()->values();
    }
}
