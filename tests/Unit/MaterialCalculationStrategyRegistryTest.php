<?php

use App\Services\Material\Calculations\BaseMaterialCalculationStrategy;
use App\Services\Material\Calculations\MaterialCalculationStrategyRegistry;
use Tests\TestCase;

uses(TestCase::class);

test('strategy registry falls back to base strategy when material type is not registered', function () {
    config()->set('material_calculation_strategies', []);

    $strategy = MaterialCalculationStrategyRegistry::resolve('unknown_material');

    expect($strategy)->toBeInstanceOf(BaseMaterialCalculationStrategy::class);
});

test('strategy registry applyFor returns unchanged payload by default', function () {
    config()->set('material_calculation_strategies', []);

    $payload = ['price_per_package' => 100000];
    $result = MaterialCalculationStrategyRegistry::applyFor('unknown_material', $payload);

    expect($result)->toBe($payload);
});
