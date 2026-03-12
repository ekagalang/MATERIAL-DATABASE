<?php

use App\Models\Cement;
use App\Models\Nat;
use Database\Seeders\MassDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('mass data seeder creates nat rows with nat material kind', function () {
    $this->seed(MassDataSeeder::class);

    expect(Nat::query()->count())->toBeGreaterThan(0);

    $sampleNat = Nat::query()->first();

    expect($sampleNat)->not->toBeNull()
        ->and($sampleNat->material_kind)->toBe(Nat::MATERIAL_KIND)
        ->and($sampleNat->nat_name)->not->toBeEmpty();

    expect(Cement::query()->where('brand', $sampleNat->brand)->count())
        ->toBeLessThan(DB::table('cements')->where('brand', $sampleNat->brand)->count());
});
