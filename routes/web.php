<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\BrickController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\CementController;
use App\Http\Controllers\SandController;
use App\Http\Controllers\BrickCalculationController;

Route::get('/', function () {
    return redirect()->route('materials.index');
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

// Brick Calculator Routes
Route::prefix('brick-calculations')->name('brick-calculations.')->group(function () {
    Route::get('/', [BrickCalculationController::class, 'index'])->name('index');
    Route::get('/create', [BrickCalculationController::class, 'create'])->name('create');
    Route::post('/', [BrickCalculationController::class, 'store'])->name('store');
    Route::get('/{brickCalculation}', [BrickCalculationController::class, 'show'])->name('show');
    Route::get('/{brickCalculation}/edit', [BrickCalculationController::class, 'edit'])->name('edit');
    Route::put('/{brickCalculation}', [BrickCalculationController::class, 'update'])->name('update');
    Route::delete('/{brickCalculation}', [BrickCalculationController::class, 'destroy'])->name('destroy');
    
    // Export
    Route::get('/{brickCalculation}/export-pdf', [BrickCalculationController::class, 'exportPdf'])->name('export-pdf');
});

// Dashboard kalkulator
Route::get('/brick-calculator/dashboard', [BrickCalculationController::class, 'dashboard'])->name('brick-calculator.dashboard');

// API Routes untuk real-time calculation
Route::prefix('api/brick-calculator')->name('api.brick-calculator.')->group(function () {
    Route::post('/calculate', [BrickCalculationController::class, 'calculate'])->name('calculate');
    Route::post('/compare', [BrickCalculationController::class, 'compare'])->name('compare');
    Route::get('/brick-dimensions/{brickId}', [BrickCalculationController::class, 'getBrickDimensions'])->name('brick-dimensions');
});