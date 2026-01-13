<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;
use App\Models\UnitMaterialType;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        // Mapping satuan untuk tiap material type
        $unitsByMaterial = [
            'cat' => [
                ['code' => 'Galon', 'name' => 'Galon', 'package_weight' => 0.2],
                ['code' => 'Pail', 'name' => 'Pail', 'package_weight' => 1.0],
                ['code' => 'Klg', 'name' => 'Kaleng', 'package_weight' => 0.1],
                ['code' => 'L', 'name' => 'Liter', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],

            'cement' => [
                ['code' => 'Sak', 'name' => 'Sak', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],

            'sand' => [
                ['code' => 'Krg', 'name' => 'Karung', 'package_weight' => 0],
                ['code' => 'Sak', 'name' => 'Sak', 'package_weight' => 0],
                ['code' => 'Kg', 'name' => 'Kilogram', 'package_weight' => 0],
            ],

            'brick' => [
                ['code' => 'Bh', 'name' => 'Buah', 'package_weight' => 0],
                ['code' => 'Unit', 'name' => 'Unit', 'package_weight' => 0],
                ['code' => 'Pcs', 'name' => 'Piece', 'package_weight' => 0],
            ],

            'ceramic' => [
                ['code' => 'Dus', 'name' => 'Dus', 'package_weight' => 0],
                ['code' => 'Lbr', 'name' => 'Lembar', 'package_weight' => 0],
                ['code' => 'M2', 'name' => 'Meter Persegi', 'package_weight' => 0],
            ],
        ];

        // Insert units untuk setiap material type
        foreach ($unitsByMaterial as $materialType => $units) {
            foreach ($units as $unitData) {
                // 1. Create or Update Unit (based on code)
                $unit = Unit::firstOrCreate(
                    ['code' => $unitData['code']],
                    [
                        'name' => $unitData['name'],
                        'package_weight' => $unitData['package_weight'],
                        'description' => 'Satuan Umum', // Default description
                    ],
                );

                // 2. Attach Material Type if not exists
                UnitMaterialType::firstOrCreate([
                    'unit_id' => $unit->id,
                    'material_type' => $materialType,
                ]);
            }
        }

        $this->command->info('✅ Units seeded successfully with material type relations!');
        $this->command->info('📊 Total units: ' . Unit::count());
        $this->command->info('🔗 Total relations: ' . UnitMaterialType::count());
    }
}
