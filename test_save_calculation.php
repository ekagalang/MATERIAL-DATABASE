<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

echo "\n=== TEST SAVE CALCULATION (Simulasi dari Form) ===\n\n";

$params = [
    'project_name' => 'Test 100m² 1/2 Bata via Form',
    'wall_length' => 10,
    'wall_height' => 10,
    'installation_type_id' => BrickInstallationType::where('code', 'half')->first()->id,
    'mortar_thickness' => 1.0,
    'mortar_formula_id' => MortarFormula::where('name', 'Adukan 1:4 (Standar)')->first()->id,
    'brick_id' => Brick::first()->id,
    'cement_id' => Cement::where('package_weight_net', 40)->first()->id,
    'sand_id' => Sand::first()->id,
    'use_custom_ratio' => false,
];

try {
    // Perform calculation
    $calculation = BrickCalculation::performCalculation($params);

    echo "✅ Calculation performed successfully!\n\n";

    echo "📊 Hasil Perhitungan SEBELUM disimpan:\n";
    echo "   - cement_quantity_40kg: {$calculation->cement_quantity_40kg}\n";
    echo "   - cement_kg: {$calculation->cement_kg}\n";
    echo "   - sand_m3: {$calculation->sand_m3}\n";
    echo "   - water_liters: {$calculation->water_liters}\n";
    echo "   - mortar_volume: {$calculation->mortar_volume}\n\n";

    // Save to database
    $calculation->save();

    echo "💾 Data saved to database with ID: {$calculation->id}\n\n";

    // Retrieve from database
    $retrieved = BrickCalculation::find($calculation->id);

    echo "📥 Hasil Perhitungan SETELAH diambil dari database:\n";
    echo "   - cement_quantity_40kg: {$retrieved->cement_quantity_40kg}\n";
    echo "   - cement_kg: {$retrieved->cement_kg}\n";
    echo "   - sand_m3: {$retrieved->sand_m3}\n";
    echo "   - water_liters: {$retrieved->water_liters}\n";
    echo "   - mortar_volume: {$retrieved->mortar_volume}\n\n";

    // Get summary
    $summary = $retrieved->getSummary();

    echo "📋 Summary yang ditampilkan di View:\n";
    echo "   - Semen 40kg: {$summary['materials']['cement']['40kg']}\n";
    echo "   - Semen kg: {$summary['materials']['cement']['kg']}\n";
    echo "   - Pasir m³: {$summary['materials']['sand']['m3']}\n";
    echo "   - Air: {$summary['materials']['water']['liters']}\n\n";

    echo "Expected from Excel:\n";
    echo "   - Semen 40kg: 25.76 sak\n";
    echo "   - Semen kg: 1,030.30 kg\n";
    echo "   - Pasir m³: 2.780000 m³\n";
    echo "   - Air: 1,112.74 liter\n\n";

    $match40kg = abs($retrieved->cement_quantity_40kg - 25.76) < 0.01;
    $matchKg = abs($retrieved->cement_kg - 1030.30) < 0.5;
    $matchSand = abs($retrieved->sand_m3 - 2.78) < 0.01;
    $matchWater = abs($retrieved->water_liters - 1112.72) < 1;

    if ($match40kg && $matchKg && $matchSand && $matchWater) {
        echo "✅ SEMUA NILAI SUDAH SESUAI DENGAN EXCEL!\n";
    } else {
        echo "⚠️ MASIH ADA PERBEDAAN:\n";
        if (! $match40kg) {
            echo '   - Semen 40kg: '.abs($retrieved->cement_quantity_40kg - 25.76)." sak selisih\n";
        }
        if (! $matchKg) {
            echo '   - Semen kg: '.abs($retrieved->cement_kg - 1030.30)." kg selisih\n";
        }
        if (! $matchSand) {
            echo '   - Pasir m³: '.abs($retrieved->sand_m3 - 2.78)." m³ selisih\n";
        }
        if (! $matchWater) {
            echo '   - Air: '.abs($retrieved->water_liters - 1112.72)." liter selisih\n";
        }
    }

    // Cleanup
    $retrieved->delete();
    echo "\n🗑️ Test data cleaned up (deleted ID: {$calculation->id})\n";

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\n";
