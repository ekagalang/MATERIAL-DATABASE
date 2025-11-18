<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\MaterialTypeDetector;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping satuan untuk tiap material type
        $unitsByMaterial = [
            'cat' => [
                ['code' => 'Galon', 'name' => 'Galon', 'package_weight' => 0.20],
                ['code' => 'Pail', 'name' => 'Pail', 'package_weight' => 1.00],
                ['code' => 'Klg', 'name' => 'Kaleng', 'package_weight' => 0.10],
                ['code' => 'L', 'name' => 'Liter', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],
            
            'cement' => [
                ['code' => 'Sak', 'name' => 'Sak', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],
            
            'sand' => [
                ['code' => 'Krg', 'name' => 'Karung', 'package_weight' => 0],
                ['code' => 'M3', 'name' => 'Meter Kubik', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],
            
            'brick' => [
                ['code' => 'Buah', 'name' => 'Buah', 'package_weight' => 0],
                ['code' => 'Unit', 'name' => 'Unit', 'package_weight' => 0],
                ['code' => 'M3', 'name' => 'Meter Kubik', 'package_weight' => 0],
                ['code' => 'M2', 'name' => 'Meter Kuadrat', 'package_weight' => 0],
            ],
        ];

        // Insert units untuk setiap material type
        foreach ($unitsByMaterial as $materialType => $units) {
            foreach ($units as $unit) {
                DB::table('units')->insert([
                    'code' => $unit['code'],
                    'material_type' => $materialType,
                    'name' => $unit['name'],
                    'package_weight' => $unit['package_weight'],
                    'description' => "Satuan untuk " . ucfirst($materialType),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Units seeded successfully with material type grouping!');
        $this->command->info('📊 Total units created: ' . DB::table('units')->count());
    }
}