<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MortarFormulaSeeder extends Seeder
{
    public function run(): void
    {
        $formulas = [
            [
                'name' => 'Adukan 1:4 (Standar)',
                'description' => 'Campuran 1 semen : 4 pasir - untuk dinding struktural',
                'cement_ratio' => 1,
                'sand_ratio' => 4,
                'water_ratio' => 0.5,
                'cement_kg_per_m3' => 325, // Estimasi untuk adukan 1:4
                'sand_m3_per_m3' => 0.87, // ~87% dari volume total
                'water_liter_per_m3' => 400, // ~400 liter per m³
                'expansion_factor' => 1.2, // Volume mengembang 20%
                'cement_bag_type' => 'both',
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adukan 1:5',
                'description' => 'Campuran 1 semen : 5 pasir - untuk dinding non-struktural',
                'cement_ratio' => 1,
                'sand_ratio' => 5,
                'water_ratio' => 0.5,
                'cement_kg_per_m3' => 275,
                'sand_m3_per_m3' => 0.89,
                'water_liter_per_m3' => 400,
                'expansion_factor' => 1.2,
                'cement_bag_type' => 'both',
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adukan 1:6',
                'description' => 'Campuran 1 semen : 6 pasir - untuk plesteran',
                'cement_ratio' => 1,
                'sand_ratio' => 6,
                'water_ratio' => 0.5,
                'cement_kg_per_m3' => 235,
                'sand_m3_per_m3' => 0.91,
                'water_liter_per_m3' => 400,
                'expansion_factor' => 1.2,
                'cement_bag_type' => 'both',
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('mortar_formulas')->insert($formulas);
    }
}