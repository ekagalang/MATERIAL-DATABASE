<?php

use App\Support\Material\MaterialLookupQuery;
use Illuminate\Http\Request;

test('material lookup query returns raw search and raw limit values for api contract', function () {
    $request = Request::create('/api/materials', 'GET', [
        'search' => '0',
        'limit' => '050',
    ]);

    expect(MaterialLookupQuery::rawSearch($request))->toBe('0')
        ->and(MaterialLookupQuery::rawLimit($request))->toBe('050');
});

test('material lookup query string search normalizes non scalar to default', function () {
    $request = Request::create('/materials', 'GET', ['search' => ['x']]);

    expect(MaterialLookupQuery::stringSearch($request))->toBe('');
});

test('material lookup query normalized limit keeps legacy clamp behavior', function () {
    $invalid = Request::create('/materials', 'GET', ['limit' => '999']);
    $valid = Request::create('/materials', 'GET', ['limit' => '15']);

    expect(MaterialLookupQuery::normalizedLimit($invalid))->toBe(20)
        ->and(MaterialLookupQuery::normalizedLimit($valid))->toBe(15);
});

test('material lookup query query material type keeps raw query shape', function () {
    $request = Request::create('/materials', 'GET', ['material_type' => ['cat']]);

    expect(MaterialLookupQuery::queryMaterialType($request, 'all'))->toBe(['cat']);
});
