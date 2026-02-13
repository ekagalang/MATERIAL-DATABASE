<?php

use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Services\Formula\PlinthCeramicFormula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('formula has correct metadata', function () {
    expect(PlinthCeramicFormula::getCode())
        ->toBe('plinth_ceramic')
        ->and(PlinthCeramicFormula::getName())
        ->toBe('Pasang Plint Keramik')
        ->and(PlinthCeramicFormula::getDescription())
        ->toContain('plint keramik')
        ->and(PlinthCeramicFormula::getMaterialRequirements())
        ->toBe(['cement', 'sand', 'ceramic', 'nat']);
});

test('formula validates required parameters', function () {
    $formula = new PlinthCeramicFormula();

    // Missing parameters should fail
    expect($formula->validate([]))->toBeFalse();

    // Invalid parameters should fail
    expect(
        $formula->validate([
            'wall_length' => 0,
            'wall_height' => 10,
            'mortar_thickness' => 2,
            'grout_thickness' => 3,
        ]),
    )->toBeFalse();

    // Valid parameters should pass
    expect(
        $formula->validate([
            'wall_length' => 5,
            'wall_height' => 15,
            'mortar_thickness' => 2,
            'grout_thickness' => 3,
        ]),
    )->toBeTrue();
});

test('formula calculates material requirements correctly', function () {
    // Create test materials
    $cement = Cement::factory()->create([
        'package_weight_net' => 50,
        'package_price' => 70000,
    ]);

    $sand = Sand::factory()->create([
        'comparison_price_per_m3' => 200000,
    ]);

    $ceramic = Ceramic::factory()->create([
        'dimension_length' => 60,
        'dimension_width' => 30,
        'dimension_thickness' => 0.8,
        'pieces_per_package' => 10,
        'price_per_package' => 150000,
    ]);

    $nat = Nat::factory()->create([
        'package_weight_net' => 5,
        'package_volume' => 0.00069444,
        'package_price' => 35000,
    ]);

    $formula = new PlinthCeramicFormula();

    $params = [
        'wall_length' => 10, // 10 meters
        'wall_height' => 15, // 15 cm
        'mortar_thickness' => 2, // 2 cm
        'grout_thickness' => 3, // 3 mm
        'cement_id' => $cement->id,
        'sand_id' => $sand->id,
        'ceramic_id' => $ceramic->id,
        'nat_id' => $nat->id,
    ];

    $result = $formula->calculate($params);

    // Verify result structure
    expect($result)->toHaveKeys([
        'total_tiles',
        'tiles_per_package',
        'tiles_packages',
        'cement_sak',
        'sand_m3',
        'grout_packages',
        'total_water_liters',
        'grand_total',
    ]);

    // Verify numeric results are non-negative
    expect($result['total_tiles'])
        ->toBeGreaterThan(0)
        ->and($result['tiles_packages'])
        ->toBeGreaterThan(0)
        ->and($result['cement_sak'])
        ->toBeGreaterThan(0)
        ->and($result['sand_m3'])
        ->toBeGreaterThan(0)
        ->and($result['grout_packages'])
        ->toBeGreaterThan(0)
        ->and($result['grand_total'])
        ->toBeGreaterThan(0);
});

test('formula trace provides detailed calculation steps', function () {
    // Create test materials
    Cement::factory()->create(['package_weight_net' => 50]);
    Sand::factory()->create();
    Ceramic::factory()->create([
        'dimension_length' => 60,
        'dimension_width' => 30,
        'dimension_thickness' => 0.8,
        'pieces_per_package' => 10,
    ]);
    Nat::factory()->create([
        'package_weight_net' => 5,
        'package_volume' => 0.00069444,
    ]);

    $formula = new PlinthCeramicFormula();

    $params = [
        'wall_length' => 5,
        'wall_height' => 10,
        'mortar_thickness' => 2,
        'grout_thickness' => 3,
    ];

    $trace = $formula->trace($params);

    // Verify trace structure
    expect($trace)
        ->toHaveKeys(['mode', 'steps', 'final_result'])
        ->and($trace['mode'])
        ->toBe('Pasang Plint Keramik')
        ->and($trace['steps'])
        ->toBeArray()
        ->and(count($trace['steps']))
        ->toBeGreaterThan(10); // Should have many calculation steps

    // Verify first step is input parameters
    expect($trace['steps'][0])
        ->toHaveKey('title')
        ->and($trace['steps'][0]['title'])
        ->toBe('Input Parameters');
});
