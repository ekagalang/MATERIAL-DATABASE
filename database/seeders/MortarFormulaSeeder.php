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
                'name' => 'Adukan 1:3',
                'description' => 'Campuran 1 semen : 3 pasir - untuk dinding struktural kuat',
                'cement_ratio' => 1,
                'sand_ratio' => 3,
                'water_ratio' => 0.3, // 30% sesuai Excel
                'cement_kg_per_m3' => 325,
                'sand_m3_per_m3' => 0.87,
                'water_liter_per_m3' => 400,
                'expansion_factor' => 0.15, // Shrinkage 15% sesuai Excel
                'cement_bag_type' => 'both',
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Adukan 1:4 (Standar)',
                'description' => 'Campuran 1 semen : 4 pasir - untuk dinding struktural',
                'cement_ratio' => 1,
                'sand_ratio' => 4,
                'water_ratio' => 0.3, // 30% sesuai Excel
                'cement_kg_per_m3' => 321.96875, // Dari Excel: 1030.30 kg / 3.2 M3 = 321.96875 kg/M3
                'sand_m3_per_m3' => 0.86875, // Dari Excel: 2.78 M3 / 3.2 M3 = 0.86875 M3/M3
                'water_liter_per_m3' => 347.725, // Dari Excel: 1112.72 liter / 3.2 M3 = 347.725 liter/M3
                'expansion_factor' => 0.15, // Shrinkage 15% sesuai Excel
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
                'water_ratio' => 0.3,
                'cement_kg_per_m3' => 275,
                'sand_m3_per_m3' => 0.89,
                'water_liter_per_m3' => 400,
                'expansion_factor' => 0.15,
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
                'water_ratio' => 0.3,
                'cement_kg_per_m3' => 235,
                'sand_m3_per_m3' => 0.91,
                'water_liter_per_m3' => 400,
                'expansion_factor' => 0.15,
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
