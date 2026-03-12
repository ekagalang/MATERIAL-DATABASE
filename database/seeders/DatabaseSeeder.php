<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessControlSeeder::class,
            UnitSeeder::class,
            MaterialSettingSeeder::class,
            BrickInstallationTypeSeeder::class,
            MortarFormulaSeeder::class,
            WorkTaxonomySeeder::class,
        ]);

        // Seeder dummy / sample dijalankan manual jika dibutuhkan:
        // $this->call(MassDataSeeder::class);
        // $this->call(BrickCalculationDataSeeder::class);
    }
}
