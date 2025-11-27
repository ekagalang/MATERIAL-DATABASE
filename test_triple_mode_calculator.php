<?php

/**
 * Test script untuk Triple Mode Calculator
 * Verifikasi hasil dari ketiga metode perhitungan
 */

require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST TRIPLE MODE CALCULATOR\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

use App\Models\BrickInstallationType;
use App\Models\MortarFormula;
use App\Models\Brick;
use App\Services\BrickCalculationModes;

// Test parameters
$params = [
    'wall_length' => 6.2,
    'wall_height' => 3.0,
    'installation_type_id' => BrickInstallationType::where('code', 'half')->first()->id,
    'mortar_formula_id' => MortarFormula::where('is_default', true)->first()->id,
    'mortar_thickness' => 1.0,
    'brick_id' => Brick::first()->id,
    'custom_cement_ratio' => 1,
    'custom_sand_ratio' => 4,
];

echo "INPUT PARAMETERS:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "Dinding: {$params['wall_length']}m × {$params['wall_height']}m = " . ($params['wall_length'] * $params['wall_height']) . " m²\n";
echo "Tebal Adukan: {$params['mortar_thickness']} cm\n";
echo "Custom Ratio: {$params['custom_cement_ratio']}:{$params['custom_sand_ratio']}\n\n";

// Calculate all modes
echo "CALCULATING WITH ALL 3 MODES...\n\n";

try {
    $results = BrickCalculationModes::calculateAllModes($params);

    // Display Mode 1
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "MODE 1: PROFESSIONAL (Volume Mortar)\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    displayMode($results['mode_1_professional']);

    // Display Mode 2
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "MODE 2: FIELD (Package Engineering)\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    displayMode($results['mode_2_field']);

    // Display Mode 3
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "MODE 3: SIMPLE (Package Basic)\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    displayMode($results['mode_3_simple']);

    // Comparison Table
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "COMPARISON TABLE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $mode1 = $results['mode_1_professional'];
    $mode2 = $results['mode_2_field'];
    $mode3 = $results['mode_3_simple'];

    printf("%-25s %-20s %-20s %-20s\n", "Material", "Mode 1", "Mode 2", "Mode 3");
    echo str_repeat("─", 85) . "\n";
    printf("%-25s %-20s %-20s %-20s\n",
        "Total Bata (buah)",
        number_format($mode1['total_bricks'], 2),
        number_format($mode2['total_bricks'], 2),
        number_format($mode3['total_bricks'], 2)
    );
    printf("%-25s %-20s %-20s %-20s\n",
        "Semen (kg)",
        number_format($mode1['cement_kg'], 2),
        number_format($mode2['cement_kg'], 2),
        number_format($mode3['cement_kg'], 2)
    );
    printf("%-25s %-20s %-20s %-20s\n",
        "Semen (sak)",
        number_format($mode1['cement_sak'], 2) . ' @ ' . number_format($mode1['cement_weight_per_sak'], 0) . 'kg',
        number_format($mode2['cement_sak'], 2) . ' @ ' . number_format($mode2['cement_weight_per_sak'], 0) . 'kg',
        number_format($mode3['cement_sak'], 2) . ' @ ' . number_format($mode3['cement_weight_per_sak'], 0) . 'kg'
    );
    printf("%-25s %-20s %-20s %-20s\n",
        "Pasir (m³)",
        number_format($mode1['sand_m3'], 6),
        number_format($mode2['sand_m3'], 6),
        number_format($mode3['sand_m3'], 6)
    );
    printf("%-25s %-20s %-20s %-20s\n",
        "Air (liter)",
        number_format($mode1['water_liters'], 2),
        number_format($mode2['water_liters'], 2),
        number_format($mode3['water_liters'], 2)
    );

    // Analysis
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "ANALYSIS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $minCement = min($mode1['cement_kg'], $mode2['cement_kg'], $mode3['cement_kg']);
    $maxCement = max($mode1['cement_kg'], $mode2['cement_kg'], $mode3['cement_kg']);
    $diffPercent = (($maxCement - $minCement) / $minCement) * 100;

    echo "Semen:\n";
    echo "  Min: " . number_format($minCement, 2) . " kg\n";
    echo "  Max: " . number_format($maxCement, 2) . " kg\n";
    echo "  Selisih: " . number_format($maxCement - $minCement, 2) . " kg (" . number_format($diffPercent, 1) . "%)\n\n";

    $minSand = min($mode1['sand_m3'], $mode2['sand_m3'], $mode3['sand_m3']);
    $maxSand = max($mode1['sand_m3'], $mode2['sand_m3'], $mode3['sand_m3']);
    $diffSandPercent = (($maxSand - $minSand) / $minSand) * 100;

    echo "Pasir:\n";
    echo "  Min: " . number_format($minSand, 6) . " m³\n";
    echo "  Max: " . number_format($maxSand, 6) . " m³\n";
    echo "  Selisih: " . number_format($maxSand - $minSand, 6) . " m³ (" . number_format($diffSandPercent, 1) . "%)\n\n";

    echo "✅ Test selesai!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

function displayMode($mode) {
    echo "Method: {$mode['method']}\n";
    echo "─────────────────────────────────────────────────────────────\n\n";

    echo "Dinding & Bata:\n";
    echo "  • Luas dinding: " . number_format($mode['wall_area'], 2) . " m²\n";
    echo "  • Bata per m²: " . number_format($mode['bricks_per_sqm'], 2) . " buah\n";
    echo "  • Total bata: " . number_format($mode['total_bricks'], 2) . " buah\n\n";

    if (isset($mode['total_mortar_volume'])) {
        echo "Volume Mortar:\n";
        if (isset($mode['mortar_volume_per_brick'])) {
            echo "  • Per bata: " . number_format($mode['mortar_volume_per_brick'], 6) . " m³\n";
        }
        echo "  • Total: " . number_format($mode['total_mortar_volume'], 6) . " m³\n\n";
    }

    echo "Material:\n";
    echo "  • Semen: " . number_format($mode['cement_kg'], 2) . " kg\n";
    if (isset($mode['cement_sak']) && isset($mode['cement_weight_per_sak'])) {
        echo "    → " . number_format($mode['cement_sak'], 2) . " sak (" . number_format($mode['cement_weight_per_sak'], 2) . " kg/sak)\n";
    }
    echo "  • Pasir: " . number_format($mode['sand_m3'], 6) . " m³\n";
    echo "    → " . number_format($mode['sand_kg'], 2) . " kg\n";
    if (isset($mode['sand_sak'])) {
        echo "    → " . number_format($mode['sand_sak'], 2) . " sak\n";
    }
    echo "  • Air: " . number_format($mode['water_liters'], 2) . " liter\n\n";

    echo "Ratio: {$mode['ratio_used']}\n";

    if (isset($mode['engineering_factors'])) {
        echo "\nEngineering Factors:\n";
        echo "  • Volume sak: " . $mode['engineering_factors']['sak_volume'] . " m³\n";
        echo "  • Shrinkage: " . ($mode['engineering_factors']['shrinkage'] * 100) . "%\n";
        echo "  • Water %: " . ($mode['engineering_factors']['water_percentage'] * 100) . "%\n";
    }

    if (isset($mode['assumptions'])) {
        echo "\nAssumptions:\n";
        echo "  • Cement sak/m²: " . $mode['assumptions']['cement_sak_per_m2'] . "\n";
        echo "  • Sak volume: " . $mode['assumptions']['sak_volume'] . " m³\n";
    }
}
