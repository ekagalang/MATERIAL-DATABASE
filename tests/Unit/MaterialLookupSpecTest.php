<?php

use App\Support\Material\MaterialLookupSpec;

test('material lookup spec exposes expected web allowed fields', function () {
    expect(MaterialLookupSpec::allowedFields('brick'))
        ->toContain('type', 'brand', 'price_per_piece')
        ->and(MaterialLookupSpec::allowedFields('cat'))
        ->toContain('cat_name', 'color_code', 'purchase_price')
        ->and(MaterialLookupSpec::allowedFields('ceramic'))
        ->toContain('packaging', 'comparison_price_per_m2');
});

test('material lookup spec exposes expected nat field map', function () {
    $map = MaterialLookupSpec::fieldMap('nat');

    expect($map['nat_name'])->toBe('nat_name')
        ->and($map['package_price'])->toBe('package_price')
        ->and($map)->toHaveKey('address');
});

test('material lookup spec exposes expected api filter keys', function () {
    expect(MaterialLookupSpec::apiFilterKeys('brick'))->toBe(['brand', 'store'])
        ->and(MaterialLookupSpec::apiFilterKeys('cement'))->toBe(['brand', 'store', 'package_unit'])
        ->and(MaterialLookupSpec::apiFilterKeys('ceramic'))->toBe(['brand', 'store', 'type', 'packaging']);
});
