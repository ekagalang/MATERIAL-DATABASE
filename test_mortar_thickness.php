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

echo "\n=== TEST MORTAR THICKNESS SCALING ===\n\n";

$testCases = [
    ['thickness' => 1.0, 'description' => 'Tebal 1 cm (BASE - sesuai Excel)'],
    ['thickness' => 2.0, 'description' => 'Tebal 2 cm (2x lipat)'],
    ['thickness' => 0.5, 'description' => 'Tebal 0.5 cm (setengah)'],
    ['thickness' => 1.5, 'description' => 'Tebal 1.5 cm (1.5x lipat)'],
];

foreach ($testCases as $testCase) {
    $thickness = $testCase['thickness'];
    $description = $testCase['description'];

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📏 TEST: {$description}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $params = [
        'project_name' => "Test {$description}",
        'wall_length' => 10,
        'wall_height' => 10,
        'installation_type_id' => BrickInstallationType::where('code', 'half')->first()->id,
        'mortar_thickness' => $thickness,
        'mortar_formula_id' => MortarFormula::where('name', 'Adukan 1:4 (Standar)')->first()->id,
        'brick_id' => Brick::first()->id,
        'cement_id' => Cement::where('package_weight_net', 40)->first()->id,
        'sand_id' => Sand::first()->id,
    ];

    try {
        $calculation = BrickCalculation::performCalculation($params);

        echo "📊 Parameter Input:\n";
        echo "   - Luas dinding: 100 m²\n";
        echo "   - Tebal adukan: {$thickness} cm\n";
        echo '   - Scale factor: '.($thickness / 1.0)."x\n\n";

        echo "📦 Hasil Perhitungan:\n";
        echo '   - Volume adukan: '.number_format($calculation->mortar_volume, 6)." m³\n";
        echo '   - Semen 40kg: '.number_format($calculation->cement_quantity_40kg, 4)." sak\n";
        echo '   - Semen kg: '.number_format($calculation->cement_kg, 2)." kg\n";
        echo '   - Pasir m³: '.number_format($calculation->sand_m3, 6)." m³\n";
        echo '   - Air: '.number_format($calculation->water_liters, 2)." liter\n\n";

        // Expected values (scaled from base 1cm)
        $baseVolume = 3.2; // m³ untuk tebal 1cm
        $baseCement40 = 25.76; // sak
        $baseCementKg = 1030.30; // kg
        $baseSand = 2.78; // m³
        $baseWater = 1112.72; // liter

        $scaleFactor = $thickness / 1.0;
        $expectedVolume = $baseVolume * $scaleFactor;
        $expectedCement40 = $baseCement40 * $scaleFactor;
        $expectedCementKg = $baseCementKg * $scaleFactor;
        $expectedSand = $baseSand * $scaleFactor;
        $expectedWater = $baseWater * $scaleFactor;

        echo "✅ Expected (scaled from base 1cm):\n";
        echo '   - Volume adukan: '.number_format($expectedVolume, 6)." m³\n";
        echo '   - Semen 40kg: '.number_format($expectedCement40, 4)." sak\n";
        echo '   - Semen kg: '.number_format($expectedCementKg, 2)." kg\n";
        echo '   - Pasir m³: '.number_format($expectedSand, 6)." m³\n";
        echo '   - Air: '.number_format($expectedWater, 2)." liter\n\n";

        // Check if values match
        $volumeMatch = abs($calculation->mortar_volume - $expectedVolume) < 0.01;
        $cementMatch = abs($calculation->cement_quantity_40kg - $expectedCement40) < 0.1;
        $sandMatch = abs($calculation->sand_m3 - $expectedSand) < 0.01;
        $waterMatch = abs($calculation->water_liters - $expectedWater) < 1;

        if ($volumeMatch && $cementMatch && $sandMatch && $waterMatch) {
            echo "✅ SEMUA NILAI SUDAH SESUAI DENGAN SCALING!\n";
        } else {
            echo "⚠️ ADA PERBEDAAN:\n";
            if (! $volumeMatch) {
                echo '   - Volume: selisih '.abs($calculation->mortar_volume - $expectedVolume)." m³\n";
            }
            if (! $cementMatch) {
                echo '   - Semen 40kg: selisih '.abs($calculation->cement_quantity_40kg - $expectedCement40)." sak\n";
            }
            if (! $sandMatch) {
                echo '   - Pasir: selisih '.abs($calculation->sand_m3 - $expectedSand)." m³\n";
            }
            if (! $waterMatch) {
                echo '   - Air: selisih '.abs($calculation->water_liters - $expectedWater)." liter\n";
            }
        }

    } catch (Exception $e) {
        echo '❌ ERROR: '.$e->getMessage()."\n";
    }

    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ TEST COMPLETED!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
