<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NatSeeder extends Seeder
{
    public static function rows(): array
    {
        return [
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '27S',
                'color' => 'Beige',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444, // 1kg / 1440 kg/m3
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 31000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '106S',
                'color' => 'Bone',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 16000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 16000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '121S',
                'color' => 'Choc Brown',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 31000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '102S',
                'color' => 'Cream',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 31000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '4S',
                'color' => 'Grey',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 31000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '113S',
                'color' => 'Ivory Tusk',
                'package_unit' => 'Kg',
                'package_weight_gross' => 1.05,
                'package_weight_net' => 1.0,
                'package_volume' => 0.00069444,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 31000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Epoxy',
                'brand' => 'Sika',
                'sub_brand' => 'EpoxyGrout',
                'code' => 'SKA-EPX-GRY',
                'color' => 'Grey',
                'package_unit' => 'Kg',
                'package_weight_gross' => 5.1,
                'package_weight_net' => 5.0,
                'package_volume' => 0.00347222, // 5kg / 1440
                'store' => 'TB Maju Bersama',
                'address' => 'BSD, Tangerang Selatan',
                'package_price' => 180000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 36000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Epoxy',
                'brand' => 'Sika',
                'sub_brand' => 'EpoxyGrout',
                'code' => 'SKA-EPX-WHT',
                'color' => 'Putih',
                'package_unit' => 'Kg',
                'package_weight_gross' => 5.1,
                'package_weight_net' => 5.0,
                'package_volume' => 0.00347222,
                'store' => 'TB Maju Bersama',
                'address' => 'BSD, Tangerang Selatan',
                'package_price' => 185000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 37000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Sanded',
                'brand' => 'Avian',
                'sub_brand' => 'AviGrout',
                'code' => 'AVG-SND-CRM',
                'color' => 'Cream',
                'package_unit' => 'Kg',
                'package_weight_gross' => 2.05,
                'package_weight_net' => 2.0,
                'package_volume' => 0.00138889, // 2kg / 1440
                'store' => 'TB Bakti Jaya',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 55000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 27500,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Non-Sanded',
                'brand' => 'Avian',
                'sub_brand' => 'AviGrout',
                'code' => 'AVG-NS-GRY',
                'color' => 'Abu-abu',
                'package_unit' => 'Kg',
                'package_weight_gross' => 2.05,
                'package_weight_net' => 2.0,
                'package_volume' => 0.00138889,
                'store' => 'TB Bakti Jaya',
                'address' => 'Gading Serpong, Tangerang',
                'package_price' => 52000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 26000,
            ],
            [
                'cement_name' => 'Nat',
                'type' => 'Regular',
                'brand' => 'Mowilex',
                'sub_brand' => 'Grout Plus',
                'code' => 'MWX-GP-BLK',
                'color' => 'Hitam',
                'package_unit' => 'Kg',
                'package_weight_gross' => 3.05,
                'package_weight_net' => 3.0,
                'package_volume' => 0.00208333, // 3kg / 1440
                'store' => 'TB Harapan Kita',
                'address' => 'BSD, Tangerang Selatan',
                'package_price' => 85000,
                'price_unit' => 'Rp',
                'comparison_price_per_kg' => 28333,
            ],
        ];
    }

    public function run(): void
    {
        $cements = self::rows();

        foreach ($cements as $cement) {
            DB::table('cements')->insert(
                array_merge($cement, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $this->command->info('✅ Nat seeded successfully!');
        $this->command->info('📊 Total cements created: ' . DB::table('cements')->count());
    }
}
