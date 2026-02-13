<?php

use App\Services\Calculation\StoreProximityService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

test('haversine distance returns expected kilometers', function () {
    $service = new StoreProximityService();

    $samePoint = $service->haversineKm(-6.2, 106.8, -6.2, 106.8);
    $oneDegreeLat = $service->haversineKm(0.0, 0.0, 1.0, 0.0);

    expect($samePoint)->toBeFloat()->toBe(0.0)
        ->and($oneDegreeLat)->toBeGreaterThan(110.0)->toBeLessThan(112.0);
});

test('sort reachable locations keeps only stores inside service radius and sorts by distance', function () {
    $service = new StoreProximityService();

    $locations = collect([
        (object) ['id' => 1, 'latitude' => 0.02, 'longitude' => 0.0, 'service_radius_km' => 5], // ~2.2 km
        (object) ['id' => 2, 'latitude' => 0.08, 'longitude' => 0.0, 'service_radius_km' => 5], // ~8.9 km (out)
        (object) ['id' => 3, 'latitude' => 0.03, 'longitude' => 0.0, 'service_radius_km' => 10], // ~3.3 km
    ]);

    $ranked = $service->sortReachableLocations($locations, 0.0, 0.0);

    expect($ranked)->toBeInstanceOf(Collection::class)
        ->and($ranked->pluck('location.id')->all())->toBe([1, 3])
        ->and($ranked->pluck('distance_km')->all()[0])->toBeLessThan($ranked->pluck('distance_km')->all()[1]);
});

test('build nearest coverage plan allocates missing materials to next nearest store', function () {
    $service = new StoreProximityService();

    $preparedLocations = collect([
        [
            'location' => (object) ['id' => 101, 'service_radius_km' => 8, 'store' => (object) ['name' => 'A']],
            'distance_km' => 2.1,
            'has_brick' => false,
            'brick' => null,
            'materials' => [
                'cement' => collect([(object) ['id' => 11]]),
                'sand' => collect(),
                'cat' => collect(),
                'ceramic' => collect(),
                'nat' => collect(),
            ],
        ],
        [
            'location' => (object) ['id' => 102, 'service_radius_km' => 10, 'store' => (object) ['name' => 'B']],
            'distance_km' => 4.4,
            'has_brick' => true,
            'brick' => (object) ['id' => 77],
            'materials' => [
                'cement' => collect(),
                'sand' => collect([(object) ['id' => 21]]),
                'cat' => collect(),
                'ceramic' => collect(),
                'nat' => collect(),
            ],
        ],
    ]);

    $coverage = $service->buildNearestCoveragePlan($preparedLocations, ['brick', 'cement', 'sand'], true);

    expect($coverage['is_complete'])->toBeTrue()
        ->and($coverage['selected_materials']['cement']->pluck('id')->all())->toBe([11])
        ->and($coverage['selected_materials']['sand']->pluck('id')->all())->toBe([21])
        ->and(($coverage['selected_brick']->id ?? null))->toBe(77)
        ->and($coverage['store_plan'])->toHaveCount(2)
        ->and($coverage['store_plan'][0]['provided_materials'])->toContain('cement')
        ->and($coverage['store_plan'][1]['provided_materials'])->toContain('sand')
        ->and($coverage['store_plan'][1]['provided_materials'])->toContain('brick');
});

test('build nearest coverage plan marks incomplete when required material is missing', function () {
    $service = new StoreProximityService();

    $preparedLocations = collect([
        [
            'location' => (object) ['id' => 201, 'service_radius_km' => 10, 'store' => (object) ['name' => 'A']],
            'distance_km' => 1.2,
            'has_brick' => true,
            'brick' => (object) ['id' => 88],
            'materials' => [
                'cement' => collect([(object) ['id' => 31]]),
                'sand' => collect(),
                'cat' => collect(),
                'ceramic' => collect(),
                'nat' => collect(),
            ],
        ],
    ]);

    $coverage = $service->buildNearestCoveragePlan($preparedLocations, ['brick', 'cement', 'sand'], true);

    expect($coverage['is_complete'])->toBeFalse()
        ->and($coverage['missing_materials'])->toContain('sand');
});
