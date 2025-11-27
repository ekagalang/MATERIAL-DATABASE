<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\MortarFormula;
use App\Models\Brick;
use App\Models\Cement;
use App\Models\Sand;

class BrickCalculationDataSeeder extends Seeder
{
    /**
     * Seed sample calculation data for testing
     */
    public function run(): void
    {
        // Pastikan ada data material
        $brick = Brick::first();
        $cement = Cement::first();
        $sand = Sand::first();

        if (!$brick || !$cement || !$sand) {
            $this->command->warn('⚠️  Tidak ada data Brick/Cement/Sand. Silakan tambahkan data material terlebih dahulu.');
            return;
        }

        $installationType = BrickInstallationType::where('code', 'half')->first();
        $mortarFormula = MortarFormula::getDefault();

        // Create sample calculation
        $calculation = BrickCalculation::performCalculation([
            'project_name' => 'Contoh Perhitungan - Dinding Rumah',
            'notes' => 'Perhitungan untuk dinding ruang tamu',
            'wall_length' => 6.2,
            'wall_height' => 3.0,
            'installation_type_id' => $installationType->id,
            'mortar_thickness' => 1.0,
            'mortar_formula_id' => $mortarFormula->id,
            'brick_id' => $brick->id,
            'cement_id' => $cement->id,
            'sand_id' => $sand->id,
        ]);

        $calculation->save();

        $this->command->info('✅ Sample calculation created successfully!');
    }
}