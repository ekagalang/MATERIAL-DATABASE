<?php

use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Sand;
use Database\Seeders\MassDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mass data seeder creates requested material type variants', function () {
    $this->seed(MassDataSeeder::class);

    expect(Cement::query()->distinct()->pluck('type')->filter()->values()->all())
        ->toEqualCanonicalizing(['PCC', 'OPC']);

    expect(Sand::query()->distinct()->pluck('type')->filter()->values()->all())
        ->toEqualCanonicalizing(['Pasang', 'Urug', 'Bangka']);

    expect(Ceramic::query()->distinct()->pluck('type')->filter()->values()->all())
        ->toEqualCanonicalizing(['HT', 'Biasa']);

    expect(Cat::query()->distinct()->pluck('type')->filter()->values()->all())
        ->toEqualCanonicalizing(['Exterior', 'Interior', 'Dasar Exterior', 'Dasar Interior']);
});
