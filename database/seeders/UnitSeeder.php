<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ['code' => 'L', 'name' => 'Liter', 'package_weight' => 0],
            ['code' => 'Galon', 'name' => 'Galon', 'package_weight' => 0.20],
            ['code' => 'Pail', 'name' => 'Pail', 'package_weight' => 1.00],
            ['code' => 'Sak', 'name' => 'Sak', 'package_weight' => 0],
            ['code' => 'Bh', 'name' => 'Buah', 'package_weight' => 0],
            ['code' => 'LS', 'name' => 'Lumpsum', 'package_weight' => 0],
            ['code' => 'Unit', 'name' => 'Unit', 'package_weight' => 0],
            ['code' => 'Btg', 'name' => 'Batang', 'package_weight' => 0],
            ['code' => 'Lbr', 'name' => 'Lembar', 'package_weight' => 0],
            ['code' => 'M', 'name' => 'Meter', 'package_weight' => 0],
            ['code' => 'M2', 'name' => 'Meter Kuadrat', 'package_weight' => 0],
            ['code' => 'M3', 'name' => 'Meter Kubik', 'package_weight' => 0],
            ['code' => 'cm', 'name' => 'Centi Meter', 'package_weight' => 0],
            ['code' => 'mm', 'name' => 'Mili Meter', 'package_weight' => 0],
            ['code' => 'Urat', 'name' => 'Urat', 'package_weight' => 0],
            ['code' => '"', 'name' => 'Inch', 'package_weight' => 0],
            ['code' => 'Krg', 'name' => 'Karung', 'package_weight' => 0],
            ['code' => 'Klg', 'name' => 'Kaleng', 'package_weight' => 0.10],
        ];

        foreach ($units as $unit) {
            DB::table('units')->insert([
                'code' => $unit['code'],
                'name' => $unit['name'],
                'package_weight' => $unit['package_weight'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}