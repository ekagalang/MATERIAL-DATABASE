<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NatTableSeeder extends Seeder
{
    public function run(): void
    {
        $nats = array_map(function (array $row) {
            $natName = $row['nat_name'] ?? ($row['cement_name'] ?? 'Nat');

            return [
                'cement_name' => $natName,
                'nat_name' => $natName,
                'type' => $row['type'] ?? 'Nat',
                'material_kind' => 'nat',
                'photo' => $row['photo'] ?? null,
                'brand' => $row['brand'] ?? null,
                'sub_brand' => $row['sub_brand'] ?? null,
                'code' => $row['code'] ?? null,
                'color' => $row['color'] ?? null,
                'package_unit' => $row['package_unit'] ?? null,
                'package_weight_gross' => $row['package_weight_gross'] ?? null,
                'package_weight_net' => $row['package_weight_net'] ?? null,
                'package_volume' => $row['package_volume'] ?? null,
                'store' => $row['store'] ?? null,
                'address' => $row['address'] ?? null,
                'store_location_id' => $row['store_location_id'] ?? null,
                'package_price' => $row['package_price'] ?? null,
                'price_unit' => $row['price_unit'] ?? null,
                'comparison_price_per_kg' => $row['comparison_price_per_kg'] ?? null,
            ];
        }, NatSeeder::rows());

        foreach ($nats as $nat) {
            DB::table('cements')->insert(
                array_merge($nat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        $this->command->info('Nat standalone data seeded successfully.');
        $this->command->info(
            'Total nats created: ' . DB::table('cements')->where('material_kind', 'nat')->count(),
        );
    }
}
