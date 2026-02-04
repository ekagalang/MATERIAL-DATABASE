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
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '27S',
                'color' => 'Beige',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 31000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '106S',
                'color' => 'Bone',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 16000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 16000,
            ],
            [
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '121S',
                'color' => 'Choc Brown',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 31000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '102S',
                'color' => 'Cream',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 31000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '4S',
                'color' => 'Grey',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 31000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 31000,
            ],
            [
                'cement_name' => 'Semen',
                'type' => 'Nat',
                'brand' => 'AM',
                'sub_brand' => '51',
                'code' => '113S',
                'color' => 'Ivory Tusk',
                'package_unit' => 'Bks',
                'package_weight_gross' => 1.0,
                'package_weight_net' => 1.0,
                'store' => 'TB Jaya Gumilang',
                'address' => 'Gading Serpong',
                'package_price' => 31000,
                'price_unit' => 'Bks',
                'comparison_price_per_kg' => 31000,
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
