<?php

use App\Support\Material\MaterialKindResolver;

test('material kind resolver detects nat keywords from jenis', function () {
    expect(MaterialKindResolver::inferFromType('Nat Keramik'))->toBe('nat')
        ->and(MaterialKindResolver::inferFromType('Tile Grout'))->toBe('nat')
        ->and(MaterialKindResolver::inferFromType('Regular'))->toBe('nat')
        ->and(MaterialKindResolver::inferFromType('Non-Sanded'))->toBe('nat');
});

test('material kind resolver defaults to cement for non nat jenis', function () {
    expect(MaterialKindResolver::inferFromType('Semen Portland'))->toBe('cement')
        ->and(MaterialKindResolver::inferFromType('Mortar Instan'))->toBe('cement');
});

test('material kind resolver uses fallback when jenis empty', function () {
    expect(MaterialKindResolver::inferFromType('', 'nat'))->toBe('nat')
        ->and(MaterialKindResolver::inferFromType(null, 'cement'))->toBe('cement');
});

test('material kind resolver uses unified index route for nat kind', function () {
    expect(MaterialKindResolver::indexRouteNameFromKind('nat'))->toBe('materials.index')
        ->and(MaterialKindResolver::indexRouteNameFromKind('cement'))->toBe('cements.index');
});
