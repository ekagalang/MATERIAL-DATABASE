<?php

use App\Http\Controllers\BrickController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\CementController;
use App\Http\Controllers\MaterialCalculationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\PriceAnalysisController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\SkillController;
// use App\Http\Controllers\Dev\PriceAnalysisController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return app(\App\Http\Controllers\MaterialCalculationController::class)->index(request());
});

Route::resource('units', UnitController::class);
Route::resource('cats', CatController::class);
Route::resource('bricks', BrickController::class);
Route::resource('materials', MaterialController::class);
Route::get('/materials-settings', [MaterialController::class, 'settings'])->name('materials.settings');
Route::post('/materials-settings', [MaterialController::class, 'updateSettings'])->name('materials.settings.update');
Route::resource('cements', CementController::class);
Route::resource('sands', SandController::class);

// API untuk mendapatkan unique values per field - cats
Route::get('/api/cats/field-values/{field}', [CatController::class, 'getFieldValues'])
    ->name('cats.field-values');

// API untuk mendapatkan unique values per field - Bricks
Route::get('/api/bricks/field-values/{field}', [BrickController::class, 'getFieldValues'])
    ->name('bricks.field-values');

// API untuk mendapatkan unique values per field - Cements
Route::get('/api/cements/field-values/{field}', [CementController::class, 'getFieldValues'])
    ->name('cements.field-values');

// API untuk mendapatkan unique values per field - Sands
Route::get('/api/sands/field-values/{field}', [SandController::class, 'getFieldValues'])
    ->name('sands.field-values');

// Material Calculator Routes
Route::prefix('material-calculations')->name('material-calculations.')->group(function () {
    Route::get('/', [MaterialCalculationController::class, 'index'])->name('index');
    Route::get('/log', [MaterialCalculationController::class, 'log'])->name('log');
    Route::get('/create', [MaterialCalculationController::class, 'create'])->name('create');
    Route::post('/', [MaterialCalculationController::class, 'store'])->name('store');
    Route::get('/{materialCalculation}', [MaterialCalculationController::class, 'show'])->name('show');
    Route::get('/{materialCalculation}/edit', [MaterialCalculationController::class, 'edit'])->name('edit');
    Route::put('/{materialCalculation}', [MaterialCalculationController::class, 'update'])->name('update');
    Route::delete('/{materialCalculation}', [MaterialCalculationController::class, 'destroy'])->name('destroy');

    // Export
    Route::get('/{materialCalculation}/export-pdf', [MaterialCalculationController::class, 'exportPdf'])->name('export-pdf');
});

// Dashboard kalkulator
Route::get('/material-calculator/dashboard', [MaterialCalculationController::class, 'dashboard'])->name('material-calculator.dashboard');

// API Routes untuk real-time calculation
Route::prefix('api/material-calculator')->name('api.material-calculator.')->group(function () {
    Route::post('/calculate', [MaterialCalculationController::class, 'calculate'])->name('calculate');
    Route::post('/compare', [MaterialCalculationController::class, 'compare'])->name('compare');
    Route::post('/trace', [MaterialCalculationController::class, 'traceCalculation'])->name('trace');
    Route::get('/brick-dimensions/{brickId}', [MaterialCalculationController::class, 'getBrickDimensions'])->name('brick-dimensions');
});

// Trace View - step by step
Route::get('/material-calculator/trace', [MaterialCalculationController::class, 'traceView'])->name('material-calculator.trace');

/* Group route khusus untuk development tools
Route::prefix('dev')->name('dev.')->group(function () {
    Route::get('/price-analysis', [PriceAnalysisController::class, 'index'])
        ->name('price-analysis.index');
    Route::post('/price-analysis', [PriceAnalysisController::class, 'calculate'])
        ->name('price-analysis.calculate');
});
*/

Route::get('/price-analysis', [PriceAnalysisController::class, 'index'])->name('price-analysis.index');
Route::post('/price-analysis', [PriceAnalysisController::class, 'calculate'])->name('price-analysis.calculate');
Route::post('/material-calculations/compare-bricks', [App\Http\Controllers\MaterialCalculationController::class, 'compareBricks'])->name('material-calculations.compare-bricks');

Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('/work-items', [WorkItemController::class, 'index'])->name('work-items.index');
Route::get('/workers', [WorkerController::class, 'index'])->name('workers.index');
Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
