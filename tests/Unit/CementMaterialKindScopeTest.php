<?php

use App\Models\Cement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('cement model only returns cement material kind', function () {
    Cement::query()->create([
        'cement_name' => 'Semen Portland',
        'brand' => 'Brand Semen',
        'material_kind' => 'cement',
    ]);

    DB::table('cements')->insert([
        'cement_name' => 'Nat Keramik',
        'brand' => 'Brand Nat',
        'material_kind' => 'nat',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $cements = Cement::query()->get();

    expect($cements)->toHaveCount(1)
        ->and($cements->first()->material_kind)->toBe('cement');
});

test('cement record defaults to cement material kind', function () {
    $cement = Cement::query()->create([
        'cement_name' => 'Semen Putih',
        'brand' => 'Brand A',
    ]);

    $fresh = Cement::withoutGlobalScopes()->findOrFail($cement->id);

    expect($fresh->material_kind)->toBe('cement');
});

test('cement model infers nat material kind from jenis keyword', function () {
    $cement = Cement::query()->create([
        'cement_name' => 'Material Otomatis',
        'type' => 'Nat Epoxy',
        'brand' => 'Brand Auto',
    ]);

    $fresh = Cement::withoutGlobalScopes()->findOrFail($cement->id);

    expect($fresh->material_kind)->toBe('nat')
        ->and(Cement::query()->find($cement->id))->toBeNull();
});
