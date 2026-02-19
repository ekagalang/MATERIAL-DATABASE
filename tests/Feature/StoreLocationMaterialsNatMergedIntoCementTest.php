<?php

use App\Models\Cement;
use App\Models\Nat;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\StoreMaterialAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store location materials merges nat rows into cement tab', function () {
    $store = Store::query()->create([
        'name' => 'Toko Uji',
    ]);

    $location = StoreLocation::query()->create([
        'store_id' => $store->id,
        'address' => 'Jl. Uji No. 1',
        'city' => 'Bandung',
        'province' => 'Jawa Barat',
    ]);

    $cement = Cement::factory()->create([
        'type' => 'Semen OPC',
        'brand' => 'CementBrandX',
    ]);

    $nat = Nat::factory()->create([
        'type' => 'NatTypeStore',
        'nat_name' => 'Nat Keramik Store',
        'brand' => 'NatBrandStore',
    ]);

    StoreMaterialAvailability::query()->create([
        'store_location_id' => $location->id,
        'materialable_id' => $cement->id,
        'materialable_type' => Cement::class,
    ]);

    StoreMaterialAvailability::query()->create([
        'store_location_id' => $location->id,
        'materialable_id' => $nat->id,
        'materialable_type' => Nat::class,
    ]);

    $response = $this->get(route('store-locations.materials', ['store' => $store, 'location' => $location]));

    $response->assertOk();

    $materials = collect($response->viewData('materials'));
    $types = $materials->pluck('type')->all();

    expect($types)->toContain('cement')
        ->and($types)->not->toContain('nat');

    $cementTab = $materials->firstWhere('type', 'cement');
    $cementData = collect($cementTab['data'] ?? collect());

    expect((int) ($cementTab['count'] ?? 0))->toBe(2)
        ->and((int) ($cementTab['db_count'] ?? 0))->toBe(2)
        ->and($cementData->pluck('row_material_type')->all())->toContain('cement', 'nat');
});
