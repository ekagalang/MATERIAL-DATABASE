<?php

use App\Models\Ceramic;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ajax modal store ceramic returns success payload and persists data', function () {
    $this->actingAsUserWithPermissions(['materials.manage']);

    $response = $this
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->post(route('ceramics.store'), [
            'type' => 'Lantai',
            'brand' => 'Roman Modal',
            'sub_brand' => 'Series A',
            'code' => 'RM-001',
            'color' => 'Ivory',
            'form' => 'Persegi',
            'surface' => 'Glossy',
            'dimension_length' => 60,
            'dimension_width' => 60,
            'dimension_thickness' => 0.8,
            'packaging' => 'Dus',
            'pieces_per_package' => 4,
            'store' => 'Toko Modal',
            'address' => 'Jl. Modal No. 1',
            'price_per_package' => 120000,
            '_redirect_url' => route('materials.index'),
            '_redirect_to_materials' => 1,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect_url', route('materials.index'))
        ->assertJsonPath('new_material.type', 'ceramic');

    $ceramic = Ceramic::query()->where('brand', 'Roman Modal')->first();

    expect($ceramic)->not->toBeNull()
        ->and($ceramic->material_name)->toBe('Keramik')
        ->and((float) $ceramic->coverage_per_package)->toBe(1.44)
        ->and(abs((float) $ceramic->comparison_price_per_m2 - 83333.33))->toBeLessThan(1);
});

test('ajax modal store ceramic can persist with empty optional fields', function () {
    $this->actingAsUserWithPermissions(['materials.manage']);

    $response = $this
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->post(route('ceramics.store'), [
            '_redirect_url' => route('materials.index'),
            '_redirect_to_materials' => 1,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect_url', route('materials.index'))
        ->assertJsonPath('new_material.type', 'ceramic');

    $ceramic = Ceramic::query()->latest('id')->first();

    expect($ceramic)->not->toBeNull()
        ->and($ceramic->material_name)->toBe('Keramik')
        ->and($ceramic->brand)->toBeNull()
        ->and($ceramic->dimension_length)->toBeNull()
        ->and($ceramic->price_per_package)->toBeNull();
});
