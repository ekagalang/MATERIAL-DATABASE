<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ApiTokenController;
use App\Http\Controllers\Api\StoreSearchController;
use App\Http\Middleware\EnsureApiDiagnosticsEnabled;

$materialReadPermission = 'permission:materials.view|materials.manage';
$materialManagePermission = 'permission:materials.manage';
$storeReadPermission = 'permission:stores.view|stores.manage';
$storeManagePermission = 'permission:stores.manage';
$workItemReadPermission = 'permission:work-items.view|work-items.manage|projects.view|projects.manage';
$workItemManagePermission = 'permission:work-items.manage|projects.manage';
$calculationReadPermission = 'permission:calculations.view|calculations.manage|projects.view|projects.manage';
$calculationManagePermission = 'permission:calculations.manage|projects.manage';
$unitReadPermission = 'permission:units.view|units.manage';
$unitManagePermission = 'permission:units.manage';
$recommendationManagePermission = 'permission:recommendations.manage|settings.manage';
$internalApiMiddleware = ['auth:sanctum'];
$apiDiagnosticMiddleware = ['throttle:20,1', EnsureApiDiagnosticsEnabled::class];

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

Route::middleware($apiDiagnosticMiddleware)->group(function () {
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
});

Route::post('/auth/tokens', [ApiTokenController::class, 'store'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('auth')->group(function () {
    Route::get('/me', [ApiTokenController::class, 'me']);
    Route::delete('/tokens/current', [ApiTokenController::class, 'destroyCurrent']);
});

Route::middleware($internalApiMiddleware)->group(function () use ($storeReadPermission, $storeManagePermission) {
    Route::get('/stores/search', [StoreSearchController::class, 'search'])->middleware($storeReadPermission);
    Route::get('/stores/all-stores', [StoreSearchController::class, 'allStores'])->middleware($storeReadPermission);
    Route::get('/stores/addresses-by-store', [StoreSearchController::class, 'addressesByStore'])->middleware(
        $storeReadPermission,
    );
    Route::post('/stores/quick-create', [StoreSearchController::class, 'quickCreate'])->middleware(
        $storeManagePermission,
    );
});

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware($internalApiMiddleware)->group(function () use (
    $materialReadPermission,
    $materialManagePermission,
    $workItemReadPermission,
    $workItemManagePermission,
    $calculationReadPermission,
    $calculationManagePermission,
    $recommendationManagePermission,
    $unitReadPermission,
    $unitManagePermission,
) {

    // ========================================
    // MATERIAL APIs - Clean Architecture Pattern
    // ========================================

    // BRICK
    // Helper routes HARUS sebelum apiResource to avoid route conflict!
    Route::get('/bricks/field-values/{field}', [\App\Http\Controllers\Api\BrickController::class, 'getFieldValues'])
        ->middleware($materialReadPermission);
    Route::get('/bricks/all-stores', [\App\Http\Controllers\Api\BrickController::class, 'getAllStores'])->middleware(
        $materialReadPermission,
    );
    Route::get('/bricks/addresses-by-store', [\App\Http\Controllers\Api\BrickController::class, 'getAddressesByStore'])
        ->middleware($materialReadPermission);
    Route::apiResource('bricks', \App\Http\Controllers\Api\BrickController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.bricks');
    Route::apiResource('bricks', \App\Http\Controllers\Api\BrickController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.bricks');

    // CEMENT
    Route::get('/cements/field-values/{field}', [\App\Http\Controllers\Api\CementController::class, 'getFieldValues'])
        ->middleware($materialReadPermission);
    Route::get('/cements/all-stores', [\App\Http\Controllers\Api\CementController::class, 'getAllStores'])
        ->middleware($materialReadPermission);
    Route::get('/cements/addresses-by-store', [
        \App\Http\Controllers\Api\CementController::class,
        'getAddressesByStore',
    ])->middleware($materialReadPermission);
    Route::apiResource('cements', \App\Http\Controllers\Api\CementController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.cements');
    Route::apiResource('cements', \App\Http\Controllers\Api\CementController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.cements');

    // NAT
    Route::get('/nats/field-values/{field}', [\App\Http\Controllers\Api\NatController::class, 'getFieldValues'])
        ->middleware($materialReadPermission);
    Route::get('/nats/all-stores', [\App\Http\Controllers\Api\NatController::class, 'getAllStores'])->middleware(
        $materialReadPermission,
    );
    Route::get('/nats/addresses-by-store', [\App\Http\Controllers\Api\NatController::class, 'getAddressesByStore'])
        ->middleware($materialReadPermission);
    Route::apiResource('nats', \App\Http\Controllers\Api\NatController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.nats');
    Route::apiResource('nats', \App\Http\Controllers\Api\NatController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.nats');

    // SAND
    Route::get('/sands/field-values/{field}', [\App\Http\Controllers\Api\SandController::class, 'getFieldValues'])
        ->middleware($materialReadPermission);
    Route::get('/sands/all-stores', [\App\Http\Controllers\Api\SandController::class, 'getAllStores'])->middleware(
        $materialReadPermission,
    );
    Route::get('/sands/addresses-by-store', [\App\Http\Controllers\Api\SandController::class, 'getAddressesByStore'])
        ->middleware($materialReadPermission);
    Route::apiResource('sands', \App\Http\Controllers\Api\SandController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.sands');
    Route::apiResource('sands', \App\Http\Controllers\Api\SandController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.sands');

    // CAT
    Route::get('/cats/field-values/{field}', [\App\Http\Controllers\Api\CatController::class, 'getFieldValues'])
        ->middleware($materialReadPermission);
    Route::get('/cats/all-stores', [\App\Http\Controllers\Api\CatController::class, 'getAllStores'])->middleware(
        $materialReadPermission,
    );
    Route::get('/cats/addresses-by-store', [\App\Http\Controllers\Api\CatController::class, 'getAddressesByStore'])
        ->middleware($materialReadPermission);
    Route::apiResource('cats', \App\Http\Controllers\Api\CatController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.cats');
    Route::apiResource('cats', \App\Http\Controllers\Api\CatController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.cats');

    // CERAMIC
    Route::get('/ceramics/field-values/{field}', [
        \App\Http\Controllers\Api\CeramicController::class,
        'getFieldValues',
    ])->middleware($materialReadPermission);
    Route::get('/ceramics/all-stores', [\App\Http\Controllers\Api\CeramicController::class, 'getAllStores'])
        ->middleware($materialReadPermission);
    Route::get('/ceramics/addresses-by-store', [
        \App\Http\Controllers\Api\CeramicController::class,
        'getAddressesByStore',
    ])->middleware($materialReadPermission);
    Route::apiResource('ceramics', \App\Http\Controllers\Api\CeramicController::class)
        ->only(['index', 'show'])
        ->middleware($materialReadPermission)
        ->names('api.ceramics');
    Route::apiResource('ceramics', \App\Http\Controllers\Api\CeramicController::class)
        ->except(['index', 'show'])
        ->middleware($materialManagePermission)
        ->names('api.ceramics');

    // ========================================
    // CALCULATION APIs - Clean Architecture Pattern
    // ========================================

    // Calculation endpoints
    Route::post('/calculations', [\App\Http\Controllers\Api\V1\CalculationWriteApiController::class, 'store'])
        ->middleware($calculationManagePermission);
    Route::post('/calculations/calculate', [
        \App\Http\Controllers\Api\V1\CalculationExecutionApiController::class,
        'calculate',
    ])->middleware($calculationManagePermission);
    Route::post('/calculations/preview', [
        \App\Http\Controllers\Api\V1\CalculationExecutionApiController::class,
        'preview',
    ])->middleware($calculationManagePermission);
    Route::post('/calculations/compare', [
        \App\Http\Controllers\Api\V1\CalculationExecutionApiController::class,
        'compare',
    ])->middleware($calculationManagePermission);
    Route::post('/calculations/compare-installation-types', [
        \App\Http\Controllers\Api\V1\CalculationExecutionApiController::class,
        'compareInstallationTypes',
    ])->middleware($calculationManagePermission);
    Route::post('/calculations/trace', [\App\Http\Controllers\Api\V1\CalculationExecutionApiController::class, 'trace'])
        ->middleware($calculationManagePermission);
    Route::get('/calculations', [\App\Http\Controllers\Api\V1\CalculationReadApiController::class, 'index'])
        ->middleware($calculationReadPermission);
    Route::get('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationReadApiController::class, 'show'])
        ->middleware($calculationReadPermission);
    Route::put('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationWriteApiController::class, 'update'])
        ->middleware($calculationManagePermission);
    Route::delete('/calculations/{id}', [\App\Http\Controllers\Api\V1\CalculationWriteApiController::class, 'destroy'])
        ->middleware($calculationManagePermission);

    // ========================================
    // CONFIGURATION APIs - Phase 5
    // ========================================

    // Installation Types (Config - Read Only)
    Route::get('/installation-types/default', [
        \App\Http\Controllers\Api\V1\InstallationTypeApiController::class,
        'getDefault',
    ])->middleware($calculationReadPermission);
    Route::get('/installation-types/{id}', [\App\Http\Controllers\Api\V1\InstallationTypeApiController::class, 'show'])
        ->middleware($calculationReadPermission);
    Route::get('/installation-types', [\App\Http\Controllers\Api\V1\InstallationTypeApiController::class, 'index'])
        ->middleware($calculationReadPermission);

    // Mortar Formulas (Config - Read Only)
    Route::get('/mortar-formulas/default', [
        \App\Http\Controllers\Api\V1\MortarFormulaApiController::class,
        'getDefault',
    ])->middleware($calculationReadPermission);
    Route::get('/mortar-formulas/{id}', [\App\Http\Controllers\Api\V1\MortarFormulaApiController::class, 'show'])
        ->middleware($calculationReadPermission);
    Route::get('/mortar-formulas', [\App\Http\Controllers\Api\V1\MortarFormulaApiController::class, 'index'])
        ->middleware($calculationReadPermission);

    // ========================================
    // WORK ITEMS APIs (Item Pekerjaan)
    // ========================================

    // Analytics routes MUST come before {id} route to avoid route conflict
    Route::get('/work-items/analytics/{code}', [
        \App\Http\Controllers\Api\V1\WorkItemApiController::class,
        'getAnalyticsByCode',
    ])->middleware($workItemReadPermission);
    Route::get('/work-items/analytics', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'getAllAnalytics'])
        ->middleware($workItemReadPermission);

    // CRUD routes
    Route::get('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'show'])
        ->middleware($workItemReadPermission);
    Route::get('/work-items', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'index'])
        ->middleware($workItemReadPermission);
    Route::post('/work-items', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'store'])
        ->middleware($workItemManagePermission);
    Route::put('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'update'])
        ->middleware($workItemManagePermission);
    Route::delete('/work-items/{id}', [\App\Http\Controllers\Api\V1\WorkItemApiController::class, 'destroy'])
        ->middleware($workItemManagePermission);

    // ========================================
    // RECOMMENDATIONS APIs
    // ========================================

    Route::post('/recommendations/bulk-update', [
        \App\Http\Controllers\Api\V1\RecommendationApiController::class,
        'bulkUpdate',
    ])->middleware($recommendationManagePermission);
    Route::get('/recommendations', [\App\Http\Controllers\Api\V1\RecommendationApiController::class, 'index'])
        ->middleware($recommendationManagePermission);

    // ========================================
    // UNITS APIs (Satuan)
    // ========================================

    // Helper routes MUST come before {id} route to avoid route conflict
    Route::get('/units/material-types', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'getMaterialTypes'])
        ->middleware($unitReadPermission);
    Route::get('/units/grouped', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'getGrouped'])
        ->middleware($unitReadPermission);

    // CRUD routes
    Route::get('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'show'])
        ->middleware($unitReadPermission);
    Route::get('/units', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'index'])
        ->middleware($unitReadPermission);
    Route::post('/units', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'store'])
        ->middleware($unitManagePermission);
    Route::put('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'update'])
        ->middleware($unitManagePermission);
    Route::delete('/units/{id}', [\App\Http\Controllers\Api\V1\UnitApiController::class, 'destroy'])
        ->middleware($unitManagePermission);

    // ========================================
    // NUMBER FORMATTING APIs
    // ========================================

    Route::post('/number-helper/format', [\App\Http\Controllers\Api\V1\NumberHelperApiController::class, 'format'])
        ->middleware('auth:sanctum');
});
