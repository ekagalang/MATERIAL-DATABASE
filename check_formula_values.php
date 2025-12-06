<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BrickInstallationType;
use App\Models\MortarFormula;

echo "\n=== CHECK FORMULA VALUES IN DATABASE ===\n\n";

$formula = MortarFormula::where('name', 'Adukan 1:4 (Standar)')->first();

echo "Data MortarFormula dari database:\n";
echo "   - cement_kg_per_m3: {$formula->cement_kg_per_m3}\n";
echo "   - sand_m3_per_m3: {$formula->sand_m3_per_m3}\n";
echo "   - water_liter_per_m3: {$formula->water_liter_per_m3}\n";
echo "   - expansion_factor: {$formula->expansion_factor}\n\n";

echo "Hasil perhitungan untuk 3.2 m³:\n";
$cementKg = $formula->cement_kg_per_m3 * 3.2;
$cementSak40 = $cementKg / 40;
$sandM3 = $formula->sand_m3_per_m3 * 3.2;
$waterLiter = $formula->water_liter_per_m3 * 3.2;

echo "   - Cement: {$cementKg} kg\n";
echo "   - Cement 40kg: {$cementSak40} sak\n";
echo "   - Sand: {$sandM3} m³\n";
echo "   - Water: {$waterLiter} liter\n\n";

// Check installation type
$installationType = BrickInstallationType::where('code', 'half')->first();
echo "Data BrickInstallationType (1/2 Bata):\n";
echo "   - mortar_volume_per_m2: {$installationType->mortar_volume_per_m2}\n";
echo '   - Volume untuk 100m²: '.(100 * $installationType->mortar_volume_per_m2)." m³\n\n";

// Test calculateMaterials method
echo "Test calculateMaterials() method:\n";
$materials = $formula->calculateMaterials(3.2);
echo "   - cement_kg: {$materials['cement_kg']}\n";
echo "   - cement_sak_40kg: {$materials['cement_sak_40kg']}\n";
echo "   - sand_m3: {$materials['sand_m3']}\n";
echo "   - water_liters: {$materials['water_liters']}\n\n";

echo "Expected from Excel:\n";
echo "   - Cement: 1030.30 kg\n";
echo "   - Cement 40kg: 25.76 sak\n";
echo "   - Sand: 2.78 m³\n";
echo "   - Water: 1112.72 liter\n\n";
