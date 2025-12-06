<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            CatSeeder::class,
            BrickSeeder::class,
            CementSeeder::class,
            SandSeeder::class,
            BrickInstallationTypeSeeder::class,
            MortarFormulaSeeder::class,

            // Uncomment untuk create sample calculation (opsional)
            // $this->call([
            //     BrickCalculationDataSeeder::class,
            // ]);
        ]);
    }
}