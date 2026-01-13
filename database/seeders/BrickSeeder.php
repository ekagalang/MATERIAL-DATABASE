<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrickSeeder extends Seeder
{
    public function run(): void
    {
        $bricks = [
            // Bata Merah
            [
                'material_name' => 'Bata',
                'type' => 'Merah',
                'brand' => 'Kuo Shin',
                'form' => 'Persegi',
                'dimension_length' => 18.0,
                'dimension_width' => 8.0,
                'dimension_height' => 4.0,
                'package_volume' => 0.000576, // 22 x 11 x 5 cm = 0.001210 m3
                'store' => 'Toko Bangunan Jaya',
                'address' => 'Jl. Raya Bogor KM 15, Cibinong, Bogor',
                'price_per_piece' => 550,
                'comparison_price_per_m3' => 954861, // 850 / 0.001210
            ],
            [
                'material_name' => 'Bata',
                'type' => 'Bata Merah',
                'brand' => 'Lokal',
                'form' => 'Berlubang',
                'dimension_length' => 23.0,
                'dimension_width' => 11.0,
                'dimension_height' => 5.5,
                'package_volume' => 0.001392, // 23 x 11 x 5.5 cm
                'store' => 'Toko Bangunan Jaya',
                'address' => 'Jl. Raya Bogor KM 15, Cibinong, Bogor',
                'price_per_piece' => 900,
                'comparison_price_per_m3' => 646551.72, // 900 / 0.001392
            ],

            // Bata Press
            [
                'material_name' => 'Bata',
                'type' => 'Bata Press',
                'brand' => 'Citicon',
                'form' => 'Persegi',
                'dimension_length' => 21.0,
                'dimension_width' => 10.0,
                'dimension_height' => 5.0,
                'package_volume' => 0.00105, // 21 x 10 x 5 cm
                'store' => 'Sumber Bangunan',
                'address' => 'Jl. Raya Bekasi KM 20, Bekasi Timur',
                'price_per_piece' => 1100,
                'comparison_price_per_m3' => 1047619.05, // 1100 / 0.001050
            ],
            [
                'material_name' => 'Bata',
                'type' => 'Bata Press',
                'brand' => 'Citicon',
                'form' => 'Berlubang',
                'dimension_length' => 22.0,
                'dimension_width' => 10.0,
                'dimension_height' => 6.0,
                'package_volume' => 0.00132, // 22 x 10 x 6 cm
                'store' => 'Sumber Bangunan',
                'address' => 'Jl. Raya Bekasi KM 20, Bekasi Timur',
                'price_per_piece' => 1200,
                'comparison_price_per_m3' => 909090.91, // 1200 / 0.001320
            ],

            // Bata Ringan
            [
                'material_name' => 'Bata',
                'type' => 'Bata Ringan',
                'brand' => 'Hebel',
                'form' => 'Persegi',
                'dimension_length' => 60.0,
                'dimension_width' => 20.0,
                'dimension_height' => 10.0,
                'package_volume' => 0.012, // 60 x 20 x 10 cm
                'store' => 'Mega Bangunan',
                'address' => 'Jl. Raya Serpong, Tangerang Selatan',
                'price_per_piece' => 10500,
                'comparison_price_per_m3' => 875000.0, // 10500 / 0.012000
            ],
            [
                'material_name' => 'Bata',
                'type' => 'Bata Ringan',
                'brand' => 'Bricon',
                'form' => 'Persegi',
                'dimension_length' => 60.0,
                'dimension_width' => 20.0,
                'dimension_height' => 7.5,
                'package_volume' => 0.009, // 60 x 20 x 7.5 cm
                'store' => 'Mega Bangunan',
                'address' => 'Jl. Raya Serpong, Tangerang Selatan',
                'price_per_piece' => 8500,
                'comparison_price_per_m3' => 944444.44, // 8500 / 0.009000
            ],
        ];

        foreach ($bricks as $brick) {
            DB::table('bricks')->insert(
                array_merge($brick, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $this->command->info('✅ Bricks seeded successfully!');
        $this->command->info('📊 Total bricks created: ' . DB::table('bricks')->count());
    }
}
