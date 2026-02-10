<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MaterialSetting;

class MaterialSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = [
            [
                'material_type' => 'brick',
                'is_visible' => true,
                'display_order' => 1,
            ],
            [
                'material_type' => 'cat',
                'is_visible' => true,
                'display_order' => 2,
            ],
            [
                'material_type' => 'cement',
                'is_visible' => true,
                'display_order' => 3,
            ],
            [
                'material_type' => 'sand',
                'is_visible' => true,
                'display_order' => 4,
            ],
            [
                'material_type' => 'ceramic',
                'is_visible' => true,
                'display_order' => 5,
            ],
            [
                'material_type' => 'nat',
                'is_visible' => true,
                'display_order' => 6,
            ],
        ];

        foreach ($materials as $material) {
            MaterialSetting::updateOrCreate(['material_type' => $material['material_type']], $material);
        }

        $this->command->info('MaterialSettings seeded successfully!');
    }
}
