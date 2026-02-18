<?php

use App\Models\Brick;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store brick with eceran package keeps purchase price per piece and derives comparison price', function () {
    $this->post(route('bricks.store'), [
        'type' => 'Merah',
        'brand' => 'Brand Eceran',
        'form' => 'Persegi',
        'dimension_length' => 20,
        'dimension_width' => 10,
        'dimension_height' => 5,
        'package_type' => 'eceran',
        'price_per_piece' => 1000,
    ])->assertRedirect(route('bricks.index'));

    $brick = Brick::query()->where('brand', 'Brand Eceran')->firstOrFail();

    expect($brick->package_type)->toBe('eceran')
        ->and((float) $brick->price_per_piece)->toBe(1000.0)
        ->and((float) $brick->comparison_price_per_m3)->toBe(1000000.0);
});

test('store brick with kubik package treats purchase price as price per m3 and derives piece price', function () {
    $this->post(route('bricks.store'), [
        'type' => 'Merah',
        'brand' => 'Brand Kubik',
        'form' => 'Persegi',
        'dimension_length' => 20,
        'dimension_width' => 10,
        'dimension_height' => 5,
        'package_type' => 'kubik',
        'comparison_price_per_m3' => 1200000,
    ])->assertRedirect(route('bricks.index'));

    $brick = Brick::query()->where('brand', 'Brand Kubik')->firstOrFail();

    expect($brick->package_type)->toBe('kubik')
        ->and((float) $brick->comparison_price_per_m3)->toBe(1200000.0)
        ->and((float) $brick->price_per_piece)->toBe(1200.0);
});
