<?php

use App\Support\Material\MaterialApiIndexQuery;
use Illuminate\Http\Request;

test('material api index query execute uses paginate callback when search is missing', function () {
    $request = Request::create('/api/bricks', 'GET');

    $searchCalled = false;
    $paginatePayload = null;

    $result = MaterialApiIndexQuery::execute(
        $request,
        function () use (&$searchCalled) {
            $searchCalled = true;

            return 'search';
        },
        function ($perPage, $sortBy, $sortDirection) use (&$paginatePayload) {
            $paginatePayload = [$perPage, $sortBy, $sortDirection];

            return 'paginate';
        },
    );

    expect($result)->toBe('paginate')
        ->and($searchCalled)->toBeFalse()
        ->and($paginatePayload)->toBe([15, 'created_at', 'desc']);
});

test('material api index query execute uses search callback when search is truthy', function () {
    $request = Request::create('/api/bricks', 'GET', [
        'search' => 'hebel',
        'per_page' => '25',
        'sort_by' => 'brand',
        'sort_direction' => 'asc',
    ]);

    $searchPayload = null;
    $paginateCalled = false;

    $result = MaterialApiIndexQuery::execute(
        $request,
        function ($search, $perPage, $sortBy, $sortDirection) use (&$searchPayload) {
            $searchPayload = [$search, $perPage, $sortBy, $sortDirection];

            return 'search';
        },
        function () use (&$paginateCalled) {
            $paginateCalled = true;

            return 'paginate';
        },
    );

    expect($result)->toBe('search')
        ->and($paginateCalled)->toBeFalse()
        ->and($searchPayload)->toBe(['hebel', '25', 'brand', 'asc']);
});

test('material api index query execute keeps php truthy behavior for search zero string', function () {
    $request = Request::create('/api/bricks', 'GET', ['search' => '0']);

    $searchCalled = false;
    $paginateCalled = false;

    MaterialApiIndexQuery::execute(
        $request,
        function () use (&$searchCalled) {
            $searchCalled = true;
        },
        function () use (&$paginateCalled) {
            $paginateCalled = true;
        },
    );

    expect($searchCalled)->toBeFalse()
        ->and($paginateCalled)->toBeTrue();
});
