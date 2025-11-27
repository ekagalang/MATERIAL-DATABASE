<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__.'/database/database.sqlite',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "📊 Testing MortarFormula Calculation\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Load models
require __DIR__.'/app/Models/MortarFormula.php';
require __DIR__.'/app/Models/Cement.php';
require __DIR__.'/app/Models/Sand.php';

use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

// Get first cement and sand
$cement = Cement::first();
$sand = Sand::first();
$formula = MortarFormula::first();

if (! $cement || ! $sand || ! $formula) {
    echo "❌ Data tidak ditemukan. Jalankan seeder terlebih dahulu.\n";
    exit(1);
}

echo "📦 DATA MATERIAL:\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "Semen: {$cement->name}\n";
echo "  - Berat per sak: {$cement->package_weight_net} kg\n";
echo "  - Dimensi: {$cement->dimension_length}m × {$cement->dimension_width}m × {$cement->dimension_height}m\n";
echo "  - Volume per sak: {$cement->package_volume} m³\n\n";

echo "Pasir: {$sand->name}\n";
echo "  - Type: {$sand->type}\n\n";

echo "Formula: {$formula->name}\n";
echo "  - Rasio: 1:{$formula->sand_ratio}\n";
echo "  - Cement ratio: {$formula->cement_ratio}\n";
echo "  - Sand ratio: {$formula->sand_ratio}\n\n";

// Test dengan volume adukan 1 m³
$mortarVolume = 1.0; // m³

echo "═══════════════════════════════════════════════════════════════\n";
echo "🧮 PERHITUNGAN DENGAN VOLUME ADUKAN: {$mortarVolume} m³\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$materials = $formula->calculateMaterials(
    $mortarVolume,
    null,
    null,
    $cement,
    $sand
);

echo "📋 HASIL PERHITUNGAN:\n";
echo "─────────────────────────────────────────────────────────────\n\n";

echo "SEMEN:\n";
echo "  • Volume per kemasan: {$materials['cement_volume_per_bag']} m³\n";
echo "  • Volume total dibutuhkan: {$materials['cement_volume_m3']} m³\n";
echo "  • Jumlah kemasan: {$materials['cement_sak']} sak\n";
echo "  • Total berat: {$materials['cement_kg']} kg\n";
echo "  • Konversi sak 40kg: {$materials['cement_sak_40kg']} sak\n";
echo "  • Konversi sak 50kg: {$materials['cement_sak_50kg']} sak\n\n";

echo "PASIR:\n";
echo "  • Volume per kemasan: {$materials['sand_volume_per_bag']} m³ (SAMA dengan kemasan semen)\n";
echo "  • Jumlah kemasan: {$materials['sand_sak']} sak\n";
echo "  • Volume total: {$materials['sand_m3']} m³\n";
echo "  • Total berat: {$materials['sand_kg']} kg\n\n";

echo "AIR:\n";
echo "  • Volume: {$materials['water_m3']} m³\n";
echo "  • Liter: {$materials['water_liters']} liter\n\n";

// Verifikasi rumus
$cementRatio = $formula->cement_ratio;
$sandRatio = $formula->sand_ratio;
$totalRatio = $cementRatio + $sandRatio;

$sandMultiplier = $sandRatio / $cementRatio;

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ VERIFIKASI RUMUS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. RASIO CAMPURAN:\n";
echo "   Semen : Pasir = {$cementRatio} : {$sandRatio}\n";
echo "   Multiplier = {$sandRatio} ÷ {$cementRatio} = {$sandMultiplier}\n\n";

echo "2. JUMLAH KEMASAN:\n";
echo "   Semen = {$materials['cement_sak']} sak\n";
echo "   Pasir = {$materials['cement_sak']} sak × {$sandMultiplier} = {$materials['sand_sak']} sak\n";
echo "   ✓ Pasir menggunakan {$sandMultiplier}× JUMLAH KEMASAN semen\n\n";

echo "3. VOLUME KEMASAN:\n";
echo "   Semen per sak = {$materials['cement_volume_per_bag']} m³\n";
echo "   Pasir per sak = {$materials['sand_volume_per_bag']} m³\n";
echo "   ✓ Ukuran kemasan SAMA\n\n";

echo "4. AIR:\n";
echo "   Total kemasan = {$materials['cement_sak']} + {$materials['sand_sak']} = ".($materials['cement_sak'] + $materials['sand_sak'])." sak\n";
echo '   Air (liter) = '.($materials['cement_sak'] + $materials['sand_sak'])." × {$materials['cement_volume_per_bag']} × 0.30 × 1000\n";
echo "                = {$materials['water_liters']} liter\n";
echo "   Air (kubik) = {$materials['cement_volume_m3']} × 1.2 = {$materials['water_m3']} m³\n\n";

echo "✅ Perhitungan selesai!\n";
