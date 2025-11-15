<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\BrickController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\CementController;
use App\Http\Controllers\SandController;

Route::get('/', function () {
    return redirect()->route('cats.index');
});

Route::resource('units', UnitController::class);
Route::resource('cats', CatController::class);
Route::resource('bricks', BrickController::class);
Route::resource('materials', MaterialController::class);
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