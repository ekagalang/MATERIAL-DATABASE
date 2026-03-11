<?php

use App\Models\Nat;
use App\Models\Store;
use App\Models\Unit;
use App\Services\Cache\CacheService;
use App\Services\Dashboard\DashboardService;
use App\Services\FormulaRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('dashboard data uses actual database counts for cards', function () {
    DB::table('bricks')->insert([
        [
            'material_name' => 'Bata',
            'type' => 'Merah',
            'brand' => 'Uji Bata',
            'form' => 'Solid',
            'dimension_length' => 20,
            'dimension_width' => 10,
            'dimension_height' => 5,
            'package_volume' => 0.001,
            'package_type' => 'eceran',
            'store' => 'Toko Uji',
            'address' => 'Alamat Uji',
            'price_per_piece' => 1000,
            'comparison_price_per_m3' => 1000000,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Nat::query()->create([
        'nat_name' => 'Nat Uji 1',
        'cement_name' => 'Nat Uji 1',
        'type' => 'Nat',
        'brand' => 'Brand Nat',
        'package_unit' => 'Kg',
        'package_weight_net' => 1,
        'package_volume' => 0.001,
        'store' => 'Toko Uji',
        'address' => 'Alamat Uji',
        'package_price' => 15000,
        'price_unit' => 'Rp',
        'comparison_price_per_kg' => 15000,
    ]);

    Nat::query()->create([
        'nat_name' => 'Nat Uji 2',
        'cement_name' => 'Nat Uji 2',
        'type' => 'Nat',
        'brand' => 'Brand Nat',
        'package_unit' => 'Kg',
        'package_weight_net' => 1,
        'package_volume' => 0.001,
        'store' => 'Toko Uji',
        'address' => 'Alamat Uji',
        'package_price' => 20000,
        'price_unit' => 'Rp',
        'comparison_price_per_kg' => 20000,
    ]);

    Unit::query()->create([
        'code' => 'M2',
        'name' => 'Meter Persegi',
        'package_weight' => 1,
        'description' => 'Unit test',
    ]);

    Store::query()->create([
        'name' => 'Toko Uji',
    ]);

    app(CacheService::class)->invalidateDashboardCache();

    $data = app(DashboardService::class)->getDashboardData();
    $expectedFormulaCount = count(FormulaRegistry::all());

    expect($data['materialCount'])->toBe(3)
        ->and($data['unitCount'])->toBe(1)
        ->and($data['storeCount'])->toBe(1)
        ->and($data['workItemCount'])->toBe($expectedFormulaCount)
        ->and($data['chartData']['labels'])->toContain('Nat')
        ->and($data['chartData']['data'])->toContain(2);
});
