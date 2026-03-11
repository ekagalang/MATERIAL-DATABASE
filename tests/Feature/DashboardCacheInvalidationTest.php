<?php

use App\Models\Ceramic;
use App\Models\Nat;
use App\Services\Cache\CacheService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard cache is invalidated when ceramic materials change', function () {
    $cache = app(CacheService::class);
    $service = app(DashboardService::class);

    $cache->invalidateDashboardCache();

    expect($service->getDashboardData()['materialCount'])->toBe(0);

    Ceramic::query()->create([
        'material_name' => 'Keramik',
        'type' => 'Lantai',
        'brand' => 'Roman',
        'color' => 'Putih',
    ]);

    $data = $service->getDashboardData();

    expect($data['materialCount'])->toBe(1)
        ->and($data['chartData']['labels'])->toContain('Keramik')
        ->and($data['chartData']['data'][5])->toBe(1);
});

test('dashboard cache is invalidated when nat materials change', function () {
    $cache = app(CacheService::class);
    $service = app(DashboardService::class);

    $cache->invalidateDashboardCache();

    expect($service->getDashboardData()['materialCount'])->toBe(0);

    Nat::query()->create([
        'nat_name' => 'Nat Uji',
        'cement_name' => 'Nat Uji',
        'type' => 'Nat',
        'brand' => 'Sika',
        'package_unit' => 'Kg',
        'package_weight_net' => 1,
        'package_volume' => 0.001,
        'store' => 'Toko Uji',
        'address' => 'Alamat Uji',
        'package_price' => 15000,
        'price_unit' => 'Rp',
        'comparison_price_per_kg' => 15000,
    ]);

    $data = $service->getDashboardData();

    expect($data['materialCount'])->toBe(1)
        ->and($data['chartData']['labels'])->toContain('Nat')
        ->and($data['chartData']['data'][3])->toBe(1);
});
