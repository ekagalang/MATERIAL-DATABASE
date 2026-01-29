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
        // Note: packageUnit relationship only exists on Sand, Cat, and Cement models
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
            
            // Categorize based on class type
            $type = class_basename($material);

            // Search filter logic - Search in ALL attributes
            if ($search) {
                // Convert search to lowercase for case-insensitive matching
                $searchLower = strtolower($search);

                // Get all attributes of the material as an array
                $attributes = $material->getAttributes();

                $found = false;
                foreach ($attributes as $key => $value) {
                    // Skip id, timestamps, and null values
                    if (in_array($key, ['id', 'created_at', 'updated_at']) || $value === null) {
                        continue;
                    }

                    // Convert value to string and search
                    $valueStr = strtolower((string)$value);
                    if (stripos($valueStr, $searchLower) !== false) {
                        $found = true;
                        break;
                    }
                }

                // Also search in computed/accessor properties that might be displayed
                $computedFields = match ($type) {
                    'Sand' => ['sand_name'],
                    'Cat' => ['cat_name'],
                    'Cement' => ['cement_name'],
                    'Ceramic' => ['material_name'],
                    default => []
                };

                if (!$found) {
                    foreach ($computedFields as $field) {
                        try {
                            $value = $material->{$field} ?? null;
                            if ($value && stripos(strtolower((string)$value), $searchLower) !== false) {
                                $found = true;
                                break;
                            }
                        } catch (\Exception $e) {
                            // Skip if accessor doesn't exist
                            continue;
                        }
                    }
                }

                // Search in packageUnit relationship (for "karung", "sak", etc.)
                // Only Sand, Cat, and Cement have packageUnit relationship
                if (!$found && in_array($type, ['Sand', 'Cat', 'Cement'])) {
                    try {
                        if (method_exists($material, 'packageUnit') && $material->packageUnit) {
                            $packageUnitName = $material->packageUnit->name ?? null;
                            if ($packageUnitName && stripos(strtolower($packageUnitName), $searchLower) !== false) {
                                $found = true;
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip if relation doesn't exist or error accessing it
                    }
                }

                // Search in unit labels that appear in display (M3, Kg, M2)
                if (!$found) {
                    $unitLabels = [];
                    if (isset($material->comparison_price_per_m3) && $material->comparison_price_per_m3) {
                        $unitLabels[] = 'm3';
                    }
                    if (isset($material->comparison_price_per_kg) && $material->comparison_price_per_kg) {
                        $unitLabels[] = 'kg';
                        $unitLabels[] = 'kilogram';
                    }
                    if (isset($material->comparison_price_per_m2) && $material->comparison_price_per_m2) {
                        $unitLabels[] = 'm2';
                    }

                    foreach ($unitLabels as $label) {
                        if (stripos($label, $searchLower) !== false) {
                            $found = true;
                            break;
                        }
                    }
                }

                if (!$found) continue;
            }
            
            switch($type) {
                case 'Brick': $bricks->push($material); break;
                case 'Cement': $cements->push($material); break;
                case 'Sand': $sands->push($material); break;
                case 'Cat': $cats->push($material); break;
                case 'Ceramic': $ceramics->push($material); break;
            }
        }

        // Sorting Logic
        $sortBy = request('sort_by');
        $sortDirection = request('sort_direction', 'asc');

        $sortCollection = function($collection) use ($sortBy, $sortDirection) {
            if (!$sortBy) {
                return $collection->sortBy('brand', SORT_NATURAL|SORT_FLAG_CASE);
            }
            return $collection->sortBy(function($item) use ($sortBy) {
                return $item->{$sortBy} ?? null;
            }, SORT_REGULAR, $sortDirection === 'desc');
        };

        $bricks = $sortCollection($bricks);
        $cements = $sortCollection($cements);
        $sands = $sortCollection($sands);
        $cats = $sortCollection($cats);
        $ceramics = $sortCollection($ceramics);

        // Prepare data structure for the view (similar to MaterialController@index)
        // IMPORTANT: Always include all material types even if count is 0
        // This prevents JavaScript errors when the view expects all types
        $materials = [];

        // Always include brick
        $materials[] = [
            'type' => 'brick',
            'label' => 'Bata',
            'count' => $bricks->count(),
            'data' => $bricks,
            'active_letters' => $this->getActiveLetters($bricks)
        ];

        // Always include cement
        $materials[] = [
            'type' => 'cement',
            'label' => 'Semen',
            'count' => $cements->count(),
            'data' => $cements,
            'active_letters' => $this->getActiveLetters($cements)
        ];

        // Always include sand
        $materials[] = [
            'type' => 'sand',
            'label' => 'Pasir',
            'count' => $sands->count(),
            'data' => $sands,
            'active_letters' => $this->getActiveLetters($sands)
        ];

        // Always include cat
        $materials[] = [
            'type' => 'cat',
            'label' => 'Cat',
            'count' => $cats->count(),
            'data' => $cats,
            'active_letters' => $this->getActiveLetters($cats)
        ];

        // Always include ceramic
        $materials[] = [
            'type' => 'ceramic',
            'label' => 'Keramik',
            'count' => $ceramics->count(),
            'data' => $ceramics,
            'active_letters' => $this->getActiveLetters($ceramics)
        ];
        
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
