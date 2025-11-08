<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\MaterialController;

Route::get('/', function () {
    return redirect()->route('materials.index');
});

Route::resource('units', UnitController::class);
Route::resource('materials', MaterialController::class);

// API untuk mendapatkan unique values per field
Route::get('/api/materials/field-values/{field}', [MaterialController::class, 'getFieldValues'])->name('materials.field-values');