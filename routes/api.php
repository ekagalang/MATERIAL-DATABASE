<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test endpoint untuk verify API setup
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'data' => [
            'version' => 'v1',
            'timestamp' => now()->toISOString(),
        ],
    ]);
});

// Test endpoint dengan error handling
Route::get('/test-error', function () {
    abort(404, 'Test not found error');
});

// Test endpoint dengan validation
Route::post('/test-validation', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Validation passed',
        'data' => $request->only(['name', 'email']),
    ]);
});

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth routes will be added later when implementing authentication

    // ========================================
    // MATERIAL APIs - Clean Architecture Pattern
    // ========================================

    // BRICK
    // Helper routes HARUS sebelum apiResource to avoid route conflict!
    Route::get('/bricks/field-values/{field}', [\App\Http\Controllers\Api\BrickController::class, 'getFieldValues']);
    Route::get('/bricks/all-stores', [\App\Http\Controllers\Api\BrickController::class, 'getAllStores']);
    Route::get('/bricks/addresses-by-store', [\App\Http\Controllers\Api\BrickController::class, 'getAddressesByStore']);
    Route::apiResource('bricks', \App\Http\Controllers\Api\BrickController::class);

    // CEMENT
    Route::get('/cements/field-values/{field}', [\App\Http\Controllers\Api\CementController::class, 'getFieldValues']);
    Route::get('/cements/all-stores', [\App\Http\Controllers\Api\CementController::class, 'getAllStores']);
    Route::get('/cements/addresses-by-store', [\App\Http\Controllers\Api\CementController::class, 'getAddressesByStore']);
    Route::apiResource('cements', \App\Http\Controllers\Api\CementController::class);

    // SAND
    Route::get('/sands/field-values/{field}', [\App\Http\Controllers\Api\SandController::class, 'getFieldValues']);
    Route::get('/sands/all-stores', [\App\Http\Controllers\Api\SandController::class, 'getAllStores']);
    Route::get('/sands/addresses-by-store', [\App\Http\Controllers\Api\SandController::class, 'getAddressesByStore']);
    Route::apiResource('sands', \App\Http\Controllers\Api\SandController::class);

    // CAT
    Route::get('/cats/field-values/{field}', [\App\Http\Controllers\Api\CatController::class, 'getFieldValues']);
    Route::get('/cats/all-stores', [\App\Http\Controllers\Api\CatController::class, 'getAllStores']);
    Route::get('/cats/addresses-by-store', [\App\Http\Controllers\Api\CatController::class, 'getAddressesByStore']);
    Route::apiResource('cats', \App\Http\Controllers\Api\CatController::class);

    // ========================================
    // CALCULATION APIs - Clean Architecture Pattern
    // ========================================

    // Calculation endpoints
    Route::post('/calculations', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'store']);
    Route::post('/calculations/calculate', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'calculate']);
    Route::post('/calculations/preview', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'preview']);
    Route::post('/calculations/compare', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'compare']);
    Route::post('/calculations/compare-installation-types', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'compareInstallationTypes']);
    Route::post('/calculations/trace', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'trace']);
    Route::get('/calculations', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'index']);
    Route::get('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'show']);
    Route::put('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'update']);
    Route::delete('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationApiController::class, 'destroy']);

    // ========================================
    // CONFIGURATION APIs - Phase 5
    // ========================================

    // Installation Types (Config - Read Only)
    Route::get('/installation-types/default', [\App\Http\Controllers\Api\V1\InstallationTypeApiController::class, 'getDefault']);
    Route::get('/installation-types/{id}', [\App\Http\Controllers\Api\V1\InstallationTypeApiController::class, 'show']);
    Route::get('/installation-types', [\App\Http\Controllers\Api\V1\InstallationTypeApiController::class, 'index']);

    // Mortar Formulas (Config - Read Only)
    Route::get('/mortar-formulas/default', [\App\Http\Controllers\Api\V1\MortarFormulaApiController::class, 'getDefault']);
    Route::get('/mortar-formulas/{id}', [\App\Http\Controllers\Api\V1\MortarFormulaApiController::class, 'show']);
    Route::get('/mortar-formulas', [\App\Http\Controllers\Api\V1\MortarFormulaApiController::class, 'index']);

    // ========================================
    // WORK ITEMS APIs (Item Pekerjaan)
    // ========================================

    // Analytics routes MUST come before {id} route to avoid route conflict
    Route::get('/work-items/analytics/{code}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'getAnalyticsByCode']);
    Route::get('/work-items/analytics', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'getAllAnalytics']);

    // CRUD routes
    Route::get('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'show']);
    Route::get('/work-items', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'index']);
    Route::post('/work-items', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'store']);
    Route::put('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'update']);
    Route::delete('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'destroy']);

    // ========================================
    // RECOMMENDATIONS APIs
    // ========================================

    Route::post('/recommendations/bulk-update', [\App\Http\Controllers\Api\V1\RecommendationApiController::class, 'bulkUpdate']);
    Route::get('/recommendations', [\App\Http\Controllers\Api\V1\RecommendationApiController::class, 'index']);

    // ========================================
    // UNITS APIs (Satuan)
    // ========================================

    // Helper routes MUST come before {id} route to avoid route conflict
    Route::get('/units/material-types', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'getMaterialTypes']);
    Route::get('/units/grouped', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'getGrouped']);

    // CRUD routes
    Route::get('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'show']);
    Route::get('/units', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'index']);
    Route::post('/units', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'store']);
    Route::put('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'update']);
    Route::delete('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'destroy']);
});
