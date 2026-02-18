<?php

// app/Http/Controllers/StoreLocationController.php

namespace App\Http\Controllers;

use App\Http\Requests\Material\StoreLocationUpsertRequest;
use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\StoreMaterialAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $request->merge([
            'contact_name' => $this->normalizeContactField($request->input('contact_name')),
            'contact_phone' => $this->normalizeContactField($request->input('contact_phone')),
        ]);

        $request->validate((new StoreLocationUpsertRequest())->rules());
        $contactData = $this->buildNormalizedContactData($request->input('contact_name', []), $request->input('contact_phone', []));

        if ($contactData['error']) {
            return back()
                ->withErrors($contactData['error'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $locationData = $this->extractLocationData($request, $contactData['name'], $contactData['phone']);

            $store->locations()->create($locationData);

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
        $request->merge([
            'contact_name' => $this->normalizeContactField($request->input('contact_name')),
            'contact_phone' => $this->normalizeContactField($request->input('contact_phone')),
        ]);

        $request->validate((new StoreLocationUpsertRequest())->rules());
        $contactData = $this->buildNormalizedContactData($request->input('contact_name', []), $request->input('contact_phone', []));

        if ($contactData['error']) {
            return back()
                ->withErrors($contactData['error'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $locationData = $this->extractLocationData($request, $contactData['name'], $contactData['phone']);

            $location->update($locationData);
            $this->syncMaterialLocationSnapshot($store, $location);

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
        // Note: packageUnit relationship exists on Sand, Cat, Cement, and Nat models
        $query = $location->materialAvailabilities()->with('materialable');

        $availabilities = $query->get();

        // Group by material type
        $bricks = collect();
        $cements = collect();
        $sands = collect();
        $cats = collect();
        $ceramics = collect();
        $nats = collect();

        foreach ($availabilities as $availability) {
            $material = $availability->materialable;
            if (!$material) {
                continue;
            }

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
                    $valueStr = strtolower((string) $value);
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
                    'Nat' => ['nat_name'],
                    'Ceramic' => ['material_name'],
                    default => [],
                };

                if (!$found) {
                    foreach ($computedFields as $field) {
                        try {
                            $value = $material->{$field} ?? null;
                            if ($value && stripos(strtolower((string) $value), $searchLower) !== false) {
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
                if (!$found && in_array($type, ['Sand', 'Cat', 'Cement', 'Nat'])) {
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

                if (!$found) {
                    continue;
                }
            }

            switch ($type) {
                case 'Brick':
                    $bricks->push($material);
                    break;
                case 'Cement':
                    $cements->push($material);
                    break;
                case 'Sand':
                    $sands->push($material);
                    break;
                case 'Cat':
                    $cats->push($material);
                    break;
                case 'Ceramic':
                    $ceramics->push($material);
                    break;
                case 'Nat':
                    $nats->push($material);
                    break;
            }
        }

        // Sorting Logic
        $sortBy = request('sort_by');
        $sortDirection = request('sort_direction', 'asc');

        $bricks = $this->sortMaterialsCollection($bricks, 'brick', $sortBy, $sortDirection);
        $cements = $this->sortMaterialsCollection($cements, 'cement', $sortBy, $sortDirection);
        $sands = $this->sortMaterialsCollection($sands, 'sand', $sortBy, $sortDirection);
        $cats = $this->sortMaterialsCollection($cats, 'cat', $sortBy, $sortDirection);
        $ceramics = $this->sortMaterialsCollection($ceramics, 'ceramic', $sortBy, $sortDirection);
        $nats = $this->sortMaterialsCollection($nats, 'nat', $sortBy, $sortDirection);

        // Prepare data structure for the view (similar to MaterialController@index)
        // IMPORTANT: Always include all material types even if count is 0
        // This prevents JavaScript errors when the view expects all types
        $materials = [];

        // Always include brick
        $materials[] = [
            'type' => 'brick',
            'label' => 'Bata',
            'count' => $bricks->count(),
            'db_count' => $bricks->count(),
            'data' => $bricks,
            'active_letters' => $this->getActiveLetters($bricks),
        ];

        // Always include cement
        $materials[] = [
            'type' => 'cement',
            'label' => 'Semen',
            'count' => $cements->count(),
            'db_count' => $cements->count(),
            'data' => $cements,
            'active_letters' => $this->getActiveLetters($cements),
        ];

        // Always include nat
        $materials[] = [
            'type' => 'nat',
            'label' => 'Nat',
            'count' => $nats->count(),
            'db_count' => $nats->count(),
            'data' => $nats,
            'active_letters' => $this->getActiveLetters($nats),
        ];

        // Always include sand
        $materials[] = [
            'type' => 'sand',
            'label' => 'Pasir',
            'count' => $sands->count(),
            'db_count' => $sands->count(),
            'data' => $sands,
            'active_letters' => $this->getActiveLetters($sands),
        ];

        // Always include cat
        $materials[] = [
            'type' => 'cat',
            'label' => 'Cat',
            'count' => $cats->count(),
            'db_count' => $cats->count(),
            'data' => $cats,
            'active_letters' => $this->getActiveLetters($cats),
        ];

        // Always include ceramic
        $materials[] = [
            'type' => 'ceramic',
            'label' => 'Keramik',
            'count' => $ceramics->count(),
            'db_count' => $ceramics->count(),
            'data' => $ceramics,
            'active_letters' => $this->getActiveLetters($ceramics),
        ];

        // Get all settings for filter dropdown (required by the view structure)
        $allSettings = \App\Models\MaterialSetting::where('is_visible', true)->orderBy('display_order')->get();

        return view('store-locations.materials', compact('store', 'location', 'materials', 'allSettings'));
    }

    /**
     * AJAX Endpoint for lazy loading tabs in Store Location view
     */
    public function fetchTab(Request $request, Store $store, StoreLocation $location, $type)
    {
        // Validate type
        if (!in_array($type, ['brick', 'cat', 'cement', 'sand', 'ceramic', 'nat'])) {
            abort(404);
        }

        $search = $request->get('search');

        // Base query for this location
        $query = $location->materialAvailabilities()->with('materialable');

        // Get all availabilities first (filtering happens in memory due to polymorphic relation)
        // Optimization: In a real large-scale app, we might want to filter by type in DB query if possible
        // But since materialAvailabilities is polymorphic, we fetch and filter.
        $availabilities = $query->get();

        $materialsCollection = collect();

        $targetClass = match ($type) {
            'brick' => 'Brick',
            'cat' => 'Cat',
            'cement' => 'Cement',
            'sand' => 'Sand',
            'ceramic' => 'Ceramic',
            'nat' => 'Nat',
        };

        foreach ($availabilities as $availability) {
            $material = $availability->materialable;
            if (!$material) {
                continue;
            }

            // Filter by type
            if (class_basename($material) !== $targetClass) {
                continue;
            }

            // Search Filter
            if ($search) {
                $searchLower = strtolower($search);
                $found = false;

                // 1. Attribute Search
                $attributes = $material->getAttributes();
                foreach ($attributes as $key => $value) {
                    if (in_array($key, ['id', 'created_at', 'updated_at']) || $value === null) {
                        continue;
                    }
                    if (stripos(strtolower((string) $value), $searchLower) !== false) {
                        $found = true;
                        break;
                    }
                }

                // 2. Computed Properties Search
                if (!$found) {
                    $computedFields = match ($type) {
                        'sand' => ['sand_name'],
                        'cat' => ['cat_name'],
                        'cement' => ['cement_name'],
                        'nat' => ['nat_name'],
                        'ceramic' => ['material_name'],
                        default => [],
                    };
                    foreach ($computedFields as $field) {
                        try {
                            $value = $material->{$field} ?? null;
                            if ($value && stripos(strtolower((string) $value), $searchLower) !== false) {
                                $found = true;
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                // 3. Relationship Search (Package Unit)
                if (!$found && in_array($type, ['sand', 'cat', 'cement', 'nat'])) {
                    try {
                        if (method_exists($material, 'packageUnit') && $material->packageUnit) {
                            $packageUnitName = $material->packageUnit->name ?? null;
                            if ($packageUnitName && stripos(strtolower($packageUnitName), $searchLower) !== false) {
                                $found = true;
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }

                // 4. Unit Labels Search
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

                if (!$found) {
                    continue;
                }
            }

            $materialsCollection->push($material);
        }

        // Sorting
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction', 'asc');
        $materialsCollection = $this->sortMaterialsCollection($materialsCollection, $type, $sortBy, $sortDirection);

        // Calculate Grand Total (Count of all materials in this location)
        // This is needed for the partial view footer usually, or just consistency
        $grandTotal = $location->materialAvailabilities()->count();

        // Prepare View Data
        $material = [
            'type' => $type,
            'label' => \App\Models\MaterialSetting::getMaterialLabel($type),
            'data' => $materialsCollection,
            'count' => $materialsCollection->count(),
            'db_count' => $materialsCollection->count(), // For store view, db_count is effectively the filtered list count in this context
            'active_letters' => $this->getActiveLetters($materialsCollection),
            'is_loaded' => true,
        ];

        return view('materials.partials.table', compact('material', 'grandTotal'));
    }

    /**
     * Helper to get active letters for grouping
     */
    private function sortMaterialsCollection($collection, string $materialType, ?string $sortBy, string $sortDirection)
    {
        $priorityColumns = $this->getMaterialSortPriorityColumns($materialType);
        if (empty($priorityColumns)) {
            return $collection->values();
        }

        $normalizedDirection = strtolower((string) $sortDirection) === 'desc' ? 'desc' : 'asc';
        $primaryColumns = [];

        if ($sortBy && in_array($sortBy, $priorityColumns, true)) {
            if ($materialType === 'ceramic' && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width', 'dimension_thickness'];
            } elseif (in_array($materialType, ['brick', 'sand'], true) && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width', 'dimension_height'];
            } else {
                $primaryColumns = [$sortBy];
            }
        }

        $sortPlan = [];
        if (empty($primaryColumns)) {
            foreach ($priorityColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
            }
        } else {
            foreach ($primaryColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => $normalizedDirection];
            }
            foreach ($priorityColumns as $column) {
                if (!in_array($column, $primaryColumns, true)) {
                    $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
                }
            }
        }

        return $collection
            ->sort(function ($left, $right) use ($sortPlan, $materialType) {
                foreach ($sortPlan as $rule) {
                    $leftValue = $this->readMaterialSortValue($left, $rule['column'], $materialType);
                    $rightValue = $this->readMaterialSortValue($right, $rule['column'], $materialType);
                    $comparison = $this->compareMaterialSortValues($leftValue, $rightValue);

                    if ($comparison !== 0) {
                        return $rule['direction'] === 'desc' ? -$comparison : $comparison;
                    }
                }

                return ($left->id ?? 0) <=> ($right->id ?? 0);
            })
            ->values();
    }

    private function getMaterialSortPriorityColumns(string $materialType): array
    {
        return match ($materialType) {
            'brick' => [
                'type',
                'brand',
                'form',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'package_type',
                'store',
                'address',
                'price_per_piece',
                'comparison_price_per_m3',
            ],
            'sand' => [
                'type',
                'brand',
                'package_unit',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'package_price',
                'comparison_price_per_m3',
            ],
            'cat' => [
                'type',
                'brand',
                'sub_brand',
                'color_code',
                'color_name',
                'package_unit',
                'package_weight_gross',
                'volume',
                'package_weight_net',
                'store',
                'address',
                'purchase_price',
                'comparison_price_per_kg',
            ],
            'cement' => [
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'package_unit',
                'package_weight_net',
                'store',
                'address',
                'package_price',
                'comparison_price_per_kg',
            ],
            'ceramic' => [
                'type',
                'brand',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'sub_brand',
                'surface',
                'code',
                'color',
                'form',
                'packaging',
                'pieces_per_package',
                'coverage_per_package',
                'store',
                'address',
                'price_per_package',
                'comparison_price_per_m2',
            ],
            'nat' => [
                'type',
                'nat_name',
                'brand',
                'sub_brand',
                'code',
                'color',
                'package_unit',
                'package_weight_net',
                'store',
                'address',
                'package_price',
                'comparison_price_per_kg',
            ],
            default => [],
        };
    }

    private function readMaterialSortValue($item, string $column, string $materialType)
    {
        $resolvedColumn = $column;
        if ($materialType === 'nat' && $column === 'type') {
            $resolvedColumn = 'nat_name';
        }

        $value = $item->{$resolvedColumn} ?? null;
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    private function compareMaterialSortValues($left, $right): int
    {
        $leftIsEmpty = $left === null || $left === '';
        $rightIsEmpty = $right === null || $right === '';

        if ($leftIsEmpty && $rightIsEmpty) {
            return 0;
        }
        if ($leftIsEmpty) {
            return 1;
        }
        if ($rightIsEmpty) {
            return -1;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strnatcasecmp((string) $left, (string) $right);
    }

    private function getActiveLetters($collection)
    {
        return $collection
            ->map(function ($item) {
                return strtoupper(substr($item->brand ?? '#', 0, 1));
            })
            ->unique()
            ->sort()
            ->values();
    }

    private function syncMaterialLocationSnapshot(Store $store, StoreLocation $location): void
    {
        $snapshot = [
            'store' => $store->name,
            'address' => $location->address,
        ];

        $materialClasses = [Brick::class, Cat::class, Cement::class, Sand::class, Ceramic::class, Nat::class];

        foreach ($materialClasses as $materialClass) {
            $table = (new $materialClass())->getTable();
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Sync direct relation records first.
            $materialClass::query()->where('store_location_id', $location->id)->update($snapshot);

            // Sync legacy records that are linked only through polymorphic availability.
            $materialIds = StoreMaterialAvailability::query()
                ->where('store_location_id', $location->id)
                ->where('materialable_type', $materialClass)
                ->pluck('materialable_id');

            if ($materialIds->isEmpty()) {
                continue;
            }

            $materialClass
                ::query()
                ->whereIn('id', $materialIds)
                ->where(function ($query) use ($location) {
                    $query->whereNull('store_location_id')->orWhere('store_location_id', $location->id);
                })
                ->update($snapshot);

            $materialClass::query()
                ->whereIn('id', $materialIds)
                ->whereNull('store_location_id')
                ->update(['store_location_id' => $location->id]);
        }
    }

    private function extractLocationData(Request $request, ?string $contactName, ?string $contactPhone): array
    {
        $locationData = $request->only([
            'address',
            'district',
            'city',
            'province',
            'latitude',
            'longitude',
            'place_id',
            'formatted_address',
            'service_radius_km',
        ]);

        $locationData['contact_name'] = $contactName;
        $locationData['contact_phone'] = $contactPhone;

        return $locationData;
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

    private function buildNormalizedContactData(array $names, array $phones): array
    {
        $pairs = $this->normalizeContactPairs($names, $phones);
        $contactName = $this->flattenContactColumn($pairs, 'name');
        $contactPhone = $this->flattenContactColumn($pairs, 'phone');

        if ($contactName !== null && mb_strlen($contactName) > 255) {
            return [
                'name' => null,
                'phone' => null,
                'error' => ['contact_name' => 'Total gabungan nama kontak maksimal 255 karakter.'],
            ];
        }

        if ($contactPhone !== null && mb_strlen($contactPhone) > 255) {
            return [
                'name' => null,
                'phone' => null,
                'error' => ['contact_phone' => 'Total gabungan nomor kontak maksimal 255 karakter.'],
            ];
        }

        return [
            'name' => $contactName,
            'phone' => $contactPhone,
            'error' => null,
        ];
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
}
