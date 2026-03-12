<?php

use App\Http\Controllers\MaterialCalculationController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use Illuminate\Http\Request;

uses(Tests\TestCase::class);

function makeStoreSearchModeNormalizationController(): MaterialCalculationController
{
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    return new class($repo, $service) extends MaterialCalculationController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeNormalizeStoreSearchMode(Request $request): void
        {
            $this->normalizeStoreSearchModeRequest($request);
        }
    };
}

test('store search mode normalization maps incomplete toggle to mixed store mode', function () {
    $controller = makeStoreSearchModeNormalizationController();
    $request = new Request([
        'use_store_filter' => 0,
        'allow_mixed_store' => 0,
        'store_mode_incomplete' => 1,
    ]);

    $controller->exposeNormalizeStoreSearchMode($request);

    expect($request->input('use_store_filter'))->toBe(1)
        ->and($request->input('allow_mixed_store'))->toBe(1)
        ->and($request->input('store_radius_scope'))->toBe('outside')
        ->and($request->input('store_search_mode'))->toBe('incomplete');
});

test('store search mode normalization maps complete outside toggle to strict one-store outside mode', function () {
    $controller = makeStoreSearchModeNormalizationController();
    $request = new Request([
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'store_mode_complete_outside' => 1,
    ]);

    $controller->exposeNormalizeStoreSearchMode($request);

    expect($request->input('use_store_filter'))->toBe(1)
        ->and($request->input('allow_mixed_store'))->toBe(0)
        ->and($request->input('store_radius_scope'))->toBe('outside')
        ->and($request->input('store_search_mode'))->toBe('complete_outside');
});

test('store search mode normalization can resolve from hidden mode value when toggles are absent', function () {
    $controller = makeStoreSearchModeNormalizationController();
    $request = new Request([
        'store_search_mode' => 'complete_within',
        'use_store_filter' => 0,
        'allow_mixed_store' => 1,
    ]);

    $controller->exposeNormalizeStoreSearchMode($request);

    expect($request->input('use_store_filter'))->toBe(1)
        ->and($request->input('allow_mixed_store'))->toBe(0)
        ->and($request->input('store_radius_scope'))->toBe('within')
        ->and($request->input('store_search_mode'))->toBe('complete_within');
});

test('store search mode normalization keeps mixed-store safety net when mode fields are missing', function () {
    $controller = makeStoreSearchModeNormalizationController();
    $request = new Request([
        'use_store_filter' => 0,
        'allow_mixed_store' => 1,
    ]);

    $controller->exposeNormalizeStoreSearchMode($request);

    expect($request->input('use_store_filter'))->toBe(1)
        ->and($request->input('allow_mixed_store'))->toBe(1);
});

test('store search mode normalization prioritizes hidden mode over stale toggle flags', function () {
    $controller = makeStoreSearchModeNormalizationController();
    $request = new Request([
        'store_search_mode' => 'incomplete',
        // Stale toggle from prior sticky state should not override hidden mode.
        'store_mode_complete_within' => 1,
        'use_store_filter' => 0,
        'allow_mixed_store' => 0,
    ]);

    $controller->exposeNormalizeStoreSearchMode($request);

    expect($request->input('use_store_filter'))->toBe(1)
        ->and($request->input('allow_mixed_store'))->toBe(1)
        ->and($request->input('store_radius_scope'))->toBe('outside')
        ->and($request->input('store_search_mode'))->toBe('incomplete');
});
