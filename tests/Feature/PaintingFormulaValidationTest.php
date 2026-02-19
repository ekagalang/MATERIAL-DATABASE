<?php

use App\Models\Cat;
use App\Services\Formula\PaintingFormula;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('painting formula throws clear error when cat net weight is zero', function () {
    $cat = Cat::create([
        'cat_name' => 'Cat Uji',
        'brand' => 'Brand Uji',
        'package_weight_net' => 0,
        'purchase_price' => 50000,
    ]);

    $formula = new PaintingFormula();

    expect(fn() => $formula->calculate([
        'wall_length' => 3,
        'wall_height' => 2.5,
        'layer_count' => 2,
        'cat_id' => $cat->id,
    ]))->toThrow(\RuntimeException::class);
});
