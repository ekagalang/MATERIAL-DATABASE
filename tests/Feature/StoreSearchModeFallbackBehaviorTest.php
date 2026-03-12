<?php

use App\Models\Brick;
use App\Models\Ceramic;
use App\Models\Cement;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('non mixed store mode falls back to next reachable single store when nearest store is incomplete', function () {
    $storeA = Store::create(['name' => 'Toko A']);
    $storeB = Store::create(['name' => 'Toko B']);

    $locationA = StoreLocation::create([
        'store_id' => $storeA->id,
        'city' => 'A',
        'latitude' => -6.2000,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $locationB = StoreLocation::create([
        'store_id' => $storeB->id,
        'city' => 'B',
        'latitude' => -6.2045,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $selectedBrick = Brick::factory()->create([
        'store' => $storeA->name,
        'store_location_id' => $locationA->id,
    ]);

    // Alternative brick only exists in store B.
    Brick::factory()->create([
        'store' => $storeB->name,
        'store_location_id' => $locationB->id,
    ]);

    Cement::factory()->create([
        'store' => $storeA->name,
        'store_location_id' => $locationA->id,
    ]);

    Cement::factory()->create([
        'store' => $storeB->name,
        'store_location_id' => $locationB->id,
    ]);

    Sand::factory()->create([
        'store' => $storeB->name,
        'store_location_id' => $locationB->id,
    ]);

    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService
    {
        public function calculateCombinationsFromMaterials(
            Brick $brick,
            array $request,
            iterable $cements,
            iterable $sands,
            ?iterable $cats = null,
            ?iterable $ceramics = null,
            ?iterable $nats = null,
            string $groupLabel = 'Kombinasi',
            ?int $limit = null,
        ): array {
            $cementCollection = collect($cements);
            $sandCollection = collect($sands);

            if ($cementCollection->isEmpty() || $sandCollection->isEmpty()) {
                return [];
            }

            return [[
                'cement' => $cementCollection->first(),
                'sand' => $sandCollection->first(),
                'result' => [
                    'grand_total' => 125000,
                    'total_brick_cost' => 45000,
                    'total_cement_price' => 50000,
                    'total_sand_price' => 30000,
                ],
                'total_cost' => 125000,
                'filter_type' => $groupLabel,
            ]];
        }
    };

    $request = new Request([
        'work_type' => 'brick_half',
        'use_store_filter' => 1,
        'allow_mixed_store' => 0,
        'project_latitude' => -6.1980,
        'project_longitude' => 106.8000,
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
    ]);

    $result = $service->calculateCombinations($request, ['brick' => $selectedBrick]);

    expect($result)->not->toBeEmpty();
    expect($result['Ekonomis 1'][0]['store_coverage_mode'] ?? null)->toBe('single_store');
    expect($result['Ekonomis 1'][0]['store_plan'][0]['store_location_id'] ?? null)->toBe($locationB->id);
    expect($result['Ekonomis 1'][0]['store_plan'][0]['store_name'] ?? null)->toBe('Toko B');
    expect($result['Ekonomis 1'][0]['brick']->store_location_id ?? null)->toBe($locationB->id);
});

test('mixed store mode uses brick from covered in-radius store instead of original out-of-radius brick', function () {
    $storeA = Store::create(['name' => 'Toko A']);
    $storeB = Store::create(['name' => 'Toko B']);
    $storeFar = Store::create(['name' => 'Toko Far']);

    $locationA = StoreLocation::create([
        'store_id' => $storeA->id,
        'city' => 'A',
        'latitude' => -6.2000,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $locationB = StoreLocation::create([
        'store_id' => $storeB->id,
        'city' => 'B',
        'latitude' => -6.2045,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $locationFar = StoreLocation::create([
        'store_id' => $storeFar->id,
        'city' => 'Far',
        'latitude' => -6.5000,
        'longitude' => 107.2000,
        'service_radius_km' => 5,
    ]);

    $farBrick = Brick::factory()->create([
        'store' => $storeFar->name,
        'store_location_id' => $locationFar->id,
    ]);

    $localBrick = Brick::factory()->create([
        'store' => $storeA->name,
        'store_location_id' => $locationA->id,
    ]);

    Cement::factory()->create([
        'store' => $storeA->name,
        'store_location_id' => $locationA->id,
    ]);

    Sand::factory()->create([
        'store' => $storeB->name,
        'store_location_id' => $locationB->id,
    ]);

    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService
    {
        public function calculateCombinationsFromMaterials(
            Brick $brick,
            array $request,
            iterable $cements,
            iterable $sands,
            ?iterable $cats = null,
            ?iterable $ceramics = null,
            ?iterable $nats = null,
            string $groupLabel = 'Kombinasi',
            ?int $limit = null,
        ): array {
            if (collect($cements)->isEmpty() || collect($sands)->isEmpty()) {
                return [];
            }

            return [[
                'result' => [
                    'grand_total' => 111000,
                    'total_brick_cost' => 41000,
                    'total_cement_price' => 50000,
                    'total_sand_price' => 20000,
                ],
                'total_cost' => 111000,
                'filter_type' => $groupLabel,
            ]];
        }
    };

    $request = new Request([
        'work_type' => 'brick_half',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'project_latitude' => -6.1980,
        'project_longitude' => 106.8000,
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
    ]);

    $result = $service->calculateCombinations($request, ['brick' => $farBrick]);

    expect($result)->not->toBeEmpty();
    expect($result['Ekonomis 1'][0]['store_coverage_mode'] ?? null)->toBe('nearest_radius_chain');
    expect($result['Ekonomis 1'][0]['brick']->id ?? null)->toBe($localBrick->id);
});

test('mixed store mode keeps searching nearest stores outside radius until missing materials are covered', function () {
    $storeNear = Store::create(['name' => 'Toko Near']);
    $storeFar = Store::create(['name' => 'Toko Far']);

    $nearLocation = StoreLocation::create([
        'store_id' => $storeNear->id,
        'city' => 'Near',
        'latitude' => -6.2000,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $farLocation = StoreLocation::create([
        'store_id' => $storeFar->id,
        'city' => 'Far',
        'latitude' => -6.2300,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    Cement::factory()->create([
        'store' => $storeNear->name,
        'store_location_id' => $nearLocation->id,
    ]);

    Sand::factory()->create([
        'store' => $storeFar->name,
        'store_location_id' => $farLocation->id,
    ]);

    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService
    {
        public function calculateCombinationsFromMaterials(
            Brick $brick,
            array $request,
            iterable $cements,
            iterable $sands,
            ?iterable $cats = null,
            ?iterable $ceramics = null,
            ?iterable $nats = null,
            string $groupLabel = 'Kombinasi',
            ?int $limit = null,
        ): array {
            if (collect($cements)->isEmpty() || collect($sands)->isEmpty()) {
                return [];
            }

            return [[
                'result' => [
                    'grand_total' => 90000,
                    'total_cement_price' => 50000,
                    'total_sand_price' => 40000,
                ],
                'total_cost' => 90000,
                'filter_type' => $groupLabel,
            ]];
        }
    };

    $request = new Request([
        'work_type' => 'wall_plastering',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'store_radius_scope' => 'within',
        // Radius sengaja kecil agar toko jauh berada di luar radius.
        'project_store_radius_km' => 0.5,
        'project_store_radius_final_km' => 0.5,
        'project_latitude' => -6.1980,
        'project_longitude' => 106.8000,
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
    ]);

    $result = $service->calculateCombinations($request, []);

    expect($result)->not->toBeEmpty();
    expect($result['Ekonomis 1'][0]['store_coverage_mode'] ?? null)->toBe('nearest_radius_chain');
    $storePlan = $result['Ekonomis 1'][0]['store_plan'] ?? [];
    expect(is_array($storePlan))->toBeTrue();
    expect(count($storePlan))->toBe(2);
    expect(($storePlan[0]['store_location_id'] ?? null))->toBe($nearLocation->id);
    expect(($storePlan[1]['store_location_id'] ?? null))->toBe($farLocation->id);
});

test('mixed store mode can fallback to store-name engine when distance-scoped stores have no complete coverage', function () {
    $store = Store::create(['name' => 'Toko Geo']);

    StoreLocation::create([
        'store_id' => $store->id,
        'city' => 'Geo',
        'latitude' => -6.2000,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService
    {
        public bool $fallbackCalled = false;

        public function calculateCombinationsFromMaterials(
            Brick $brick,
            array $request,
            iterable $cements,
            iterable $sands,
            ?iterable $cats = null,
            ?iterable $ceramics = null,
            ?iterable $nats = null,
            string $groupLabel = 'Kombinasi',
            ?int $limit = null,
        ): array {
            return [];
        }

        protected function getStoreBasedCombinationsByStoreName(Request $request, array $constraints = []): array
        {
            $this->fallbackCalled = true;

            return [
                'Ekonomis 1' => [[
                    'result' => [
                        'grand_total' => 99000,
                    ],
                    'total_cost' => 99000,
                    'filter_label' => 'Ekonomis 1',
                    'filter_type' => 'cheapest',
                ]],
            ];
        }
    };

    $request = new Request([
        'work_type' => 'tile_installation',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'project_latitude' => -6.1980,
        'project_longitude' => 106.8000,
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'grout_thickness' => 3,
        'price_filters' => ['cheapest'],
    ]);

    $result = $service->calculateCombinations($request, []);

    expect($service->fallbackCalled)->toBeTrue();
    expect($result['Ekonomis 1'][0]['result']['grand_total'] ?? null)->toBe(99000);
});

test('mixed store mode can complete tile installation coverage using non-geocoded fallback stores', function () {
    $storeNear = Store::create(['name' => 'Toko Near']);
    $storeNat = Store::create(['name' => 'Toko Nat']);

    $nearLocation = StoreLocation::create([
        'store_id' => $storeNear->id,
        'city' => 'Near',
        'latitude' => -6.2000,
        'longitude' => 106.8000,
        'service_radius_km' => 10,
    ]);

    $natOnlyLocation = StoreLocation::create([
        'store_id' => $storeNat->id,
        'city' => 'Nat',
        'latitude' => null,
        'longitude' => null,
        'service_radius_km' => 10,
    ]);

    Cement::factory()->create([
        'store' => $storeNear->name,
        'store_location_id' => $nearLocation->id,
    ]);

    Sand::factory()->create([
        'store' => $storeNear->name,
        'store_location_id' => $nearLocation->id,
    ]);

    Ceramic::factory()->create([
        'store' => $storeNear->name,
        'store_location_id' => $nearLocation->id,
    ]);

    Nat::factory()->create([
        'store' => $storeNat->name,
        'store_location_id' => $natOnlyLocation->id,
    ]);

    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService
    {
        public function calculateCombinationsFromMaterials(
            Brick $brick,
            array $request,
            iterable $cements,
            iterable $sands,
            ?iterable $cats = null,
            ?iterable $ceramics = null,
            ?iterable $nats = null,
            string $groupLabel = 'Kombinasi',
            ?int $limit = null,
        ): array {
            if (
                collect($cements)->isEmpty() ||
                collect($sands)->isEmpty() ||
                collect($ceramics ?? [])->isEmpty() ||
                collect($nats ?? [])->isEmpty()
            ) {
                return [];
            }

            return [[
                'result' => [
                    'grand_total' => 250000,
                    'total_cement_price' => 60000,
                    'total_sand_price' => 50000,
                    'total_ceramic_price' => 110000,
                    'total_grout_price' => 30000,
                ],
                'total_cost' => 250000,
                'filter_type' => $groupLabel,
            ]];
        }
    };

    $request = new Request([
        'work_type' => 'tile_installation',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'project_latitude' => -6.1980,
        'project_longitude' => 106.8000,
        'store_radius_scope' => 'within',
        'project_store_radius_km' => 5,
        'project_store_radius_final_km' => 5,
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'grout_thickness' => 3,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
    ]);

    $result = $service->calculateCombinations($request, []);

    expect($result)->not->toBeEmpty();
    expect($result['Ekonomis 1'][0]['store_coverage_mode'] ?? null)->toBe('nearest_radius_chain');
    $storePlan = $result['Ekonomis 1'][0]['store_plan'] ?? [];
    expect(is_array($storePlan))->toBeTrue();
    expect(count($storePlan))->toBe(2);
    expect(($storePlan[0]['store_location_id'] ?? null))->toBe($nearLocation->id);
    expect(($storePlan[1]['store_location_id'] ?? null))->toBe($natOnlyLocation->id);
});
