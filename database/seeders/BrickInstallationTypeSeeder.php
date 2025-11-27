<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrickInstallationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => '1/2 Bata',
                'code' => 'half',
                'description' => 'Posisi tidur horizontal, terlihat sisi panjang (alas) × tinggi',
                'visible_side_width' => 'length',
                'visible_side_height' => 'height',
                'orientation' => 'horizontal_lying',
                'bricks_per_sqm' => null, // Akan dihitung dinamis
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '1 Bata',
                'code' => 'one',
                'description' => 'Posisi tidur horizontal, terlihat sisi lebar (alas) × tinggi',
                'visible_side_width' => 'width',
                'visible_side_height' => 'height',
                'orientation' => 'horizontal_lying',
                'bricks_per_sqm' => null,
                'is_active' => true,
                'display_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '1/4 Bata',
                'code' => 'quarter',
                'description' => 'Posisi berdiri horizontal, terlihat sisi panjang (alas) × lebar (tinggi)',
                'visible_side_width' => 'length',
                'visible_side_height' => 'width',
                'orientation' => 'horizontal_standing',
                'bricks_per_sqm' => null,
                'is_active' => true,
                'display_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rollag',
                'code' => 'rollag',
                'description' => 'Posisi berdiri horizontal, terlihat sisi tinggi (alas) × lebar (tinggi)',
                'visible_side_width' => 'height',
                'visible_side_height' => 'width',
                'orientation' => 'horizontal_standing',
                'bricks_per_sqm' => null,
                'is_active' => true,
                'display_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('brick_installation_types')->insert($types);
    }
}