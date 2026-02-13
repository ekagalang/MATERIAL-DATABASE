<?php

use App\Http\Controllers\Api\V1\CalculationExecutionApiController;
use App\Http\Controllers\Api\V1\CalculationReadApiController;
use App\Http\Controllers\Api\V1\CalculationWriteApiController;
use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Http\Controllers\MaterialCalculationPageController;
use App\Http\Controllers\MaterialCalculationTraceController;

function expectMethodsDeclaredOn(string $class, array $methods): void
{
    foreach ($methods as $method) {
        $declaringClass = (new \ReflectionMethod($class, $method))->getDeclaringClass()->getName();
        expect($declaringClass)->toBe($class);
    }
}

test('material calculation split controllers own their routed methods', function () {
    expectMethodsDeclaredOn(MaterialCalculationPageController::class, [
        'indexRedirect',
        'log',
        'create',
        'showPreview',
        'show',
        'edit',
        'exportPdf',
    ]);

    expectMethodsDeclaredOn(MaterialCalculationExecutionController::class, [
        'store',
        'update',
        'destroy',
        'calculate',
        'compare',
        'getBrickDimensions',
        'getCeramicCombinations',
    ]);

    expectMethodsDeclaredOn(MaterialCalculationTraceController::class, [
        'traceCalculation',
        'traceView',
    ]);
});

test('calculation api split controllers own their routed methods', function () {
    expectMethodsDeclaredOn(CalculationReadApiController::class, [
        'index',
        'show',
    ]);

    expectMethodsDeclaredOn(CalculationWriteApiController::class, [
        'store',
        'update',
        'destroy',
    ]);

    expectMethodsDeclaredOn(CalculationExecutionApiController::class, [
        'calculate',
        'preview',
        'compare',
        'compareInstallationTypes',
        'trace',
    ]);
});
