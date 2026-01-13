<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CementSeeder extends Seeder
{
    public function run(): void
    {
        $cements = [
            // Semen Gresik - Sesuai Excel (40kg, 30x20x60 cm = 0.036 M3)
            [
                'cement_name' => 'Semen Gresik (Excel Spec)',
                'type' => 'PCC',
                'brand' => 'Merah Putih',
                'sub_brand' => '(PCC)',
                'code' => 'PCC-40',
                'color' => 'Hitam',
                'package_unit' => 'Sak',
                'package_weight_gross' => 40.0,
                'package_weight_net' => 40.0,
                'package_volume' => 0.036, // M3
                'store' => 'Toko Bangunan Maju',
                'address' => 'Jl. Raya Pasar Minggu No. 67, Jakarta Selatan',
                'package_price' => 42000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1050, 
            ],
            // Semen Gresik
            [
                'cement_name' => 'Semen Gresik',
                'type' => 'Semen Portland',
                'brand' => 'Semen Gresik',
                'sub_brand' => 'Portland Composite Cement (PCC)',
                'code' => 'SG-PCC',
                'color' => 'Abu-abu',
                'package_unit' => 'Sak',
                'package_weight_gross' => 50.5,
                'package_weight_net' => 50.0,
                'package_volume' => 0.0175, // M3
                'store' => 'Toko Bangunan Maju',
                'address' => 'Jl. Raya Pasar Minggu No. 67, Jakarta Selatan',
                'package_price' => 62000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1240, 
            ],
            [
                'cement_name' => 'Semen Gresik Merah',
                'type' => 'Semen Portland',
                'brand' => 'Semen Gresik',
                'sub_brand' => 'Super Masonry Cement',
                'code' => 'SG-SMC',
                'color' => 'Merah',
                'package_unit' => 'Sak',
                'package_weight_gross' => 40.5,
                'package_weight_net' => 40.0,
                'package_volume' => 0.0135, // M3
                'store' => 'Toko Bangunan Maju',
                'address' => 'Jl. Raya Pasar Minggu No. 67, Jakarta Selatan',
                'package_price' => 56000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1400, 
            ],

            // Semen Padang
            [
                'cement_name' => 'Semen Padang',
                'type' => 'Semen Portland',
                'brand' => 'Semen Padang',
                'sub_brand' => 'Portland Composite Cement',
                'code' => 'SP-PCC',
                'color' => 'Abu-abu',
                'package_unit' => 'Sak',
                'package_weight_gross' => 50.5,
                'package_weight_net' => 50.0,
                'package_volume' => 0.0175,
                'store' => 'Bangunan Jaya',
                'address' => 'Jl. Raya Depok No. 123, Depok',
                'package_price' => 64000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1280, 
            ],
            [
                'cement_name' => 'Semen Padang Acian',
                'type' => 'Semen Acian',
                'brand' => 'Semen Padang',
                'sub_brand' => 'Acian Putih',
                'code' => 'SP-AP',
                'color' => 'Putih',
                'package_unit' => 'Sak',
                'package_weight_gross' => 40.5,
                'package_weight_net' => 40.0,
                'package_volume' => 0.0135,
                'store' => 'Bangunan Jaya',
                'address' => 'Jl. Raya Depok No. 123, Depok',
                'package_price' => 72000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1800, 
            ],

            // Semen Holcim
            [
                'cement_name' => 'Holcim Dynamix',
                'type' => 'Semen Portland',
                'brand' => 'Holcim',
                'sub_brand' => 'Dynamix',
                'code' => 'HC-DYN',
                'color' => 'Abu-abu',
                'package_unit' => 'Sak',
                'package_weight_gross' => 50.5,
                'package_weight_net' => 50.0,
                'package_volume' => 0.0175,
                'store' => 'Sumber Makmur',
                'address' => 'Jl. Raya Condet No. 45, Jakarta Timur',
                'package_price' => 65000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1300, 
            ],

            // Semen Tiga Roda
            [
                'cement_name' => 'Semen Tiga Roda',
                'type' => 'Semen Portland',
                'brand' => 'Tiga Roda',
                'sub_brand' => 'Portland Composite Cement',
                'code' => 'TR-PCC',
                'color' => 'Abu-abu',
                'package_unit' => 'Sak',
                'package_weight_gross' => 50.5,
                'package_weight_net' => 50.0,
                'package_volume' => 0.0175,
                'store' => 'Toko Material Sejahtera',
                'address' => 'Jl. Raya Bekasi No. 88, Bekasi',
                'package_price' => 61000,
                'price_unit' => 'Sak',
                'comparison_price_per_kg' => 1220, 
            ],
        ];

        foreach ($cements as $cement) {
            DB::table('cements')->insert(
                array_merge($cement, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $this->command->info('✅ Cements seeded successfully!');
        $this->command->info('📊 Total cements created: ' . DB::table('cements')->count());
    }
}
