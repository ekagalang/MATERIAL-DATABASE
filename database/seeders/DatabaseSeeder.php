<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            MaterialSettingSeeder::class, // HARUS DI AWAL sebelum material seeders
            CatSeeder::class,
            BrickSeeder::class,
            CementSeeder::class,
            SandSeeder::class,
            CeramicSeeder::class,
            BrickInstallationTypeSeeder::class,
            MortarFormulaSeeder::class,
            NatTableSeeder::class,

            // Uncomment untuk create sample calculation (opsional)
            // $this->call([
            //     BrickCalculationDataSeeder::class,
            // ]);
        ]);
    }
}
