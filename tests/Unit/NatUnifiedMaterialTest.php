<?php

use App\Models\Nat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('nat model persists data in cements table with nat material kind', function () {
    $nat = Nat::create([
        'nat_name' => 'Nat Putih',
        'type' => 'Regular',
        'brand' => 'Alpha',
        'package_price' => 25000,
    ]);

    expect($nat->getTable())->toBe('cements');

    $this->assertDatabaseHas('cements', [
        'id' => $nat->id,
        'material_kind' => 'nat',
        'nat_name' => 'Nat Putih',
        'brand' => 'Alpha',
    ]);
});

test('nat model only returns rows with nat material kind', function () {
    DB::table('cements')->insert([
        [
            'cement_name' => 'Semen A',
            'nat_name' => null,
            'material_kind' => 'cement',
            'type' => null,
            'brand' => 'Cement Brand',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'cement_name' => 'Nat Existing',
            'nat_name' => 'Nat Existing',
            'material_kind' => 'nat',
            'type' => 'Epoxy',
            'brand' => 'Nat Brand',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $nats = Nat::query()->get();

    expect($nats)->toHaveCount(1)
        ->and($nats->first()->material_kind)->toBe('nat')
        ->and($nats->first()->nat_name)->toBe('Nat Existing');
});

test('nat model infers cement material kind for non nat jenis', function () {
    $material = Nat::create([
        'nat_name' => 'Material Uji',
        'type' => 'Semen Portland',
        'brand' => 'Brand Uji',
    ]);

    $fresh = Nat::withoutGlobalScopes()->findOrFail($material->id);

    expect($fresh->material_kind)->toBe('cement')
        ->and(Nat::query()->find($material->id))->toBeNull();
});
