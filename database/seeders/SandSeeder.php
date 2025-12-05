<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SandSeeder extends Seeder
{
    public function run(): void
    {
        $sands = [
            // Pasir Beton
            [
                'sand_name' => 'Pasir Beton',
                'type' => 'Plester',
                'brand' => 'Bangka',
                'package_unit' => 'Krg',
                'package_weight_gross' => 40.00,
                'package_weight_net' => 40.00,
                'dimension_length' => 1.00,
                'dimension_width' => 1.00,
                'dimension_height' => 1.50,
                'package_volume' => 1.500000, // sekitar 25 liter per karung
                'store' => 'Pasir Merapi',
                'address' => 'Jl. Raya Merapi No. 45, Bogor',
                'short_address' => 'Bogor',
                'package_price' => 700000,
                'comparison_price_per_m3' => 466666.67, // 700000 / 1.5
            ],

            // Pasir Pasang
            [
                'sand_name' => 'Pasir Pasang',
                'type' => 'Pasir Pasang',
                'brand' => 'Lokal',
                'package_unit' => 'Krg',
                'package_weight_gross' => 40.50,
                'package_weight_net' => 40.00,
                'dimension_length' => null,
                'dimension_width' => null,
                'dimension_height' => null,
                'package_volume' => 0.025, // sekitar 25 liter per karung
                'store' => 'Bangunan Sentosa',
                'address' => 'Jl. Raya Cilebut No. 22, Bogor',
                'short_address' => 'Cilebut, Bogor',
                'package_price' => 28000,
                'comparison_price_per_m3' => 1120000, // 28000 / 0.025
            ],
            [
                'sand_name' => 'Pasir Pasang',
                'type' => 'Pasir Pasang',
                'brand' => 'Lokal',
                'package_unit' => 'M3',
                'package_weight_gross' => null,
                'package_weight_net' => null,
                'dimension_length' => 1.00,
                'dimension_width' => 1.00,
                'dimension_height' => 1.00,
                'package_volume' => 1.000000,
                'store' => 'Bangunan Sentosa',
                'address' => 'Jl. Raya Cilebut No. 22, Bogor',
                'short_address' => 'Cilebut, Bogor',
                'package_price' => 280000,
                'comparison_price_per_m3' => 280000, // 280000 / 1
            ],

            // Pasir Urug
            [
                'sand_name' => 'Pasir Urug',
                'type' => 'Pasir Urug',
                'brand' => 'Lokal',
                'package_unit' => 'M3',
                'package_weight_gross' => null,
                'package_weight_net' => null,
                'dimension_length' => 1.00,
                'dimension_width' => 1.00,
                'dimension_height' => 1.00,
                'package_volume' => 1.000000,
                'store' => 'Pasir Sentosa',
                'address' => 'Jl. Raya Cibinong KM 10, Bogor',
                'short_address' => 'Cibinong, Bogor',
                'package_price' => 150000,
                'comparison_price_per_m3' => 150000, // 150000 / 1
            ],

            // Pasir Halus
            [
                'sand_name' => 'Pasir Halus',
                'type' => 'Pasir Halus',
                'brand' => 'Premium Sand',
                'package_unit' => 'Krg',
                'package_weight_gross' => 40.50,
                'package_weight_net' => 40.00,
                'dimension_length' => null,
                'dimension_width' => null,
                'dimension_height' => null,
                'package_volume' => 0.025,
                'store' => 'Material Prima',
                'address' => 'Jl. Raya Tajur No. 15, Bogor',
                'short_address' => 'Tajur, Bogor',
                'package_price' => 32000,
                'comparison_price_per_m3' => 1280000, // 32000 / 0.025
            ],
        ];

        foreach ($sands as $sand) {
            DB::table('sands')->insert(array_merge($sand, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('✅ Sands seeded successfully!');
        $this->command->info('📊 Total sands created: '.DB::table('sands')->count());
    }
}
