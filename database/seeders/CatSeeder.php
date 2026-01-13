<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            // Nippon Paint
            [
                'cat_name' => 'Nippon Paint Vinilex',
                'type' => 'Cat Tembok',
                'brand' => 'Nippon Paint',
                'sub_brand' => 'Vinilex',
                'color_code' => 'NP-001',
                'color_name' => 'Putih',
                'form' => 'Cair',
                'package_unit' => 'Galon',
                'package_weight_gross' => 5.2,
                'package_weight_net' => 5.0,
                'volume' => 5.0,
                'volume_unit' => 'L',
                'store' => 'Toko Cat Sejahtera',
                'address' => 'Jl. Raya Cilandak No. 123, Jakarta Selatan',
                'purchase_price' => 185000,
                'price_unit' => 'Galon',
                'comparison_price_per_kg' => 37000,
            ],
            [
                'cat_name' => 'Nippon Paint Odour-less',
                'type' => 'Cat Tembok',
                'brand' => 'Nippon Paint',
                'sub_brand' => 'Odour-less',
                'color_code' => 'NP-002',
                'color_name' => 'Broken White',
                'form' => 'Cair',
                'package_unit' => 'Pail',
                'package_weight_gross' => 26.0,
                'package_weight_net' => 25.0,
                'volume' => 25.0,
                'volume_unit' => 'L',
                'store' => 'Toko Cat Sejahtera',
                'address' => 'Jl. Raya Cilandak No. 123, Jakarta Selatan',
                'purchase_price' => 850000,
                'price_unit' => 'Pail',
                'comparison_price_per_kg' => 34000,
            ],

            // Avian
            [
                'cat_name' => 'Avian Avitex',
                'type' => 'Cat Tembok',
                'brand' => 'Avian',
                'sub_brand' => 'Avitex',
                'color_code' => 'AV-100',
                'color_name' => 'Putih',
                'form' => 'Cair',
                'package_unit' => 'Galon',
                'package_weight_gross' => 5.2,
                'package_weight_net' => 5.0,
                'volume' => 5.0,
                'volume_unit' => 'L',
                'store' => 'Mandiri Bangunan',
                'address' => 'Jl. Fatmawati Raya No. 45, Jakarta Selatan',
                'purchase_price' => 165000,
                'price_unit' => 'Galon',
                'comparison_price_per_kg' => 33000,
            ],
            [
                'cat_name' => 'Avian Weathershield',
                'type' => 'Cat Eksterior',
                'brand' => 'Avian',
                'sub_brand' => 'Weathershield',
                'color_code' => 'AV-201',
                'color_name' => 'Cream',
                'form' => 'Cair',
                'package_unit' => 'Pail',
                'package_weight_gross' => 21.0,
                'package_weight_net' => 20.0,
                'volume' => 20.0,
                'volume_unit' => 'L',
                'store' => 'Mandiri Bangunan',
                'address' => 'Jl. Fatmawati Raya No. 45, Jakarta Selatan',
                'purchase_price' => 750000,
                'price_unit' => 'Pail',
                'comparison_price_per_kg' => 37500,
            ],

            // Dulux
            [
                'cat_name' => 'Dulux Catylac',
                'type' => 'Cat Tembok',
                'brand' => 'Dulux',
                'sub_brand' => 'Catylac',
                'color_code' => 'DLX-050',
                'color_name' => 'Putih',
                'form' => 'Cair',
                'package_unit' => 'Galon',
                'package_weight_gross' => 5.3,
                'package_weight_net' => 5.0,
                'volume' => 5.0,
                'volume_unit' => 'L',
                'store' => 'Indo Bangunan',
                'address' => 'Jl. TB Simatupang No. 88, Jakarta Selatan',
                'purchase_price' => 195000,
                'price_unit' => 'Galon',
                'comparison_price_per_kg' => 39000,
            ],
            [
                'cat_name' => 'Dulux Pentalite',
                'type' => 'Cat Tembok',
                'brand' => 'Dulux',
                'sub_brand' => 'Pentalite',
                'color_code' => 'DLX-100',
                'color_name' => 'Cloud White',
                'form' => 'Cair',
                'package_unit' => 'Klg',
                'package_weight_gross' => 1.1,
                'package_weight_net' => 1.0,
                'volume' => 1.0,
                'volume_unit' => 'L',
                'store' => 'Indo Bangunan',
                'address' => 'Jl. TB Simatupang No. 88, Jakarta Selatan',
                'purchase_price' => 42000,
                'price_unit' => 'Klg',
                'comparison_price_per_kg' => 42000,
            ],
        ];

        foreach ($cats as $cat) {
            DB::table('cats')->insert(
                array_merge($cat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $this->command->info('✅ Cats seeded successfully!');
        $this->command->info('📊 Total cats created: ' . DB::table('cats')->count());
    }
}
