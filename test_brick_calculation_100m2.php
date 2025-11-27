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

echo "\n=== TEST BRICK CALCULATION: 100m² dengan 1/2 Bata ===\n\n";

// Sesuai dengan example.txt
$params = [
    'project_name' => 'Test 100m² 1/2 Bata',
    'wall_length' => 10,  // 10m x 10m = 100m²
    'wall_height' => 10,
    'installation_type_id' => BrickInstallationType::where('code', 'half')->first()->id,
    'mortar_thickness' => 1.0,
    'mortar_formula_id' => MortarFormula::where('name', 'Adukan 1:4 (Standar)')->first()->id,
    'brick_id' => Brick::first()->id,
    'cement_id' => Cement::where('package_weight_net', 40)->first()->id,
    'sand_id' => Sand::first()->id,
];

echo "📋 Parameter Input:\n";
echo "   - Luas dinding: {$params['wall_length']} m x {$params['wall_height']} m = 100 m²\n";
echo "   - Jenis pemasangan: 1/2 Bata\n";
echo "   - Formula adukan: 1:4 (Standar)\n";
echo "   - Tebal adukan: {$params['mortar_thickness']} cm\n\n";

// Ambil data brick untuk info
$brick = Brick::first();
echo "🧱 Data Bata:\n";
echo "   - Dimensi: {$brick->dimension_length} x {$brick->dimension_width} x {$brick->dimension_height} cm\n";
echo '   - Harga: Rp. '.number_format($brick->price_per_piece, 0, ',', '.')." /buah\n\n";

// Ambil data cement untuk info
$cement = Cement::where('package_weight_net', 40)->first();
echo "🏗️ Data Semen:\n";
echo "   - Berat kemasan: {$cement->package_weight_net} kg\n";
echo '   - Harga: Rp. '.number_format($cement->package_price, 0, ',', '.')." /sak\n\n";

// Ambil data sand untuk info
$sand = Sand::first();
echo "🏖️ Data Pasir:\n";
echo "   - Volume kemasan: {$sand->package_volume} m³\n";
echo '   - Harga: Rp. '.number_format($sand->package_price, 0, ',', '.')." /sak\n\n";

// Get installation type untuk cek mortar_volume_per_m2
$installationType = BrickInstallationType::where('code', 'half')->first();
echo "📐 Volume Adukan per m²:\n";
echo '   - mortar_volume_per_m2: '.($installationType->mortar_volume_per_m2 ?? 'NULL (akan dihitung dari dimensi bata)')." m³/m²\n\n";

try {
    $calculation = BrickCalculation::performCalculation($params);

    echo "✅ HASIL PERHITUNGAN:\n\n";

    echo "📊 Volume Adukan:\n";
    echo '   - Total volume adukan: '.number_format($calculation->mortar_volume, 6)." m³\n";
    echo '   - Volume adukan per bata: '.number_format($calculation->mortar_volume_per_brick, 6)." m³\n\n";

    echo "🧱 Bata:\n";
    echo '   - Jumlah: '.number_format($calculation->brick_quantity, 2)." buah\n\n";

    echo "🏗️ Semen 40kg:\n";
    echo '   - Sak: '.number_format($calculation->cement_quantity_40kg, 2)." sak\n";
    echo '   - Kg: '.number_format($calculation->cement_kg, 2)." kg\n\n";

    echo "🏖️ Pasir:\n";
    echo '   - m³: '.number_format($calculation->sand_m3, 6)." m³\n\n";

    echo "💧 Air:\n";
    echo '   - Liter: '.number_format($calculation->water_liters, 2)." liter\n\n";

    echo "=== PERBANDINGAN DENGAN EXCEL ===\n\n";
    echo "Hasil yang DIHARAPKAN dari Excel:\n";
    echo "   - Volume adukan: 3.2 m³ (100m² × 0.032 m³/m²)\n";
    echo "   - Semen 40kg: 25.76 sak\n";
    echo "   - Semen kg: 1030.30 kg\n";
    echo "   - Pasir m³: 2.78 m³\n";
    echo "   - Air: 1112.72 liter\n\n";

    echo "Hasil dari LARAVEL:\n";
    echo '   - Volume adukan: '.number_format($calculation->mortar_volume, 2)." m³\n";
    echo '   - Semen 40kg: '.number_format($calculation->cement_quantity_40kg, 2)." sak\n";
    echo '   - Semen kg: '.number_format($calculation->cement_kg, 2)." kg\n";
    echo '   - Pasir m³: '.number_format($calculation->sand_m3, 2)." m³\n";
    echo '   - Air: '.number_format($calculation->water_liters, 2)." liter\n\n";

    // Check if values match
    $volumeMatch = abs($calculation->mortar_volume - 3.2) < 0.01;
    $cementSakMatch = abs($calculation->cement_quantity_40kg - 25.76) < 0.1;
    $cementKgMatch = abs($calculation->cement_kg - 1030.30) < 1;
    $sandMatch = abs($calculation->sand_m3 - 2.78) < 0.01;
    $waterMatch = abs($calculation->water_liters - 1112.72) < 1;

    if ($volumeMatch && $cementSakMatch && $cementKgMatch && $sandMatch && $waterMatch) {
        echo "✅ SEMUA NILAI SUDAH SESUAI DENGAN EXCEL!\n";
    } else {
        echo "⚠️ MASIH ADA PERBEDAAN:\n";
        if (! $volumeMatch) {
            echo '   - Volume adukan: '.number_format(abs($calculation->mortar_volume - 3.2), 4)." m³ selisih\n";
        }
        if (! $cementSakMatch) {
            echo '   - Semen 40kg: '.number_format(abs($calculation->cement_quantity_40kg - 25.76), 2)." sak selisih\n";
        }
        if (! $cementKgMatch) {
            echo '   - Semen kg: '.number_format(abs($calculation->cement_kg - 1030.30), 2)." kg selisih\n";
        }
        if (! $sandMatch) {
            echo '   - Pasir m³: '.number_format(abs($calculation->sand_m3 - 2.78), 4)." m³ selisih\n";
        }
        if (! $waterMatch) {
            echo '   - Air: '.number_format(abs($calculation->water_liters - 1112.72), 2)." liter selisih\n";
        }
    }

} catch (Exception $e) {
    echo '❌ ERROR: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\n";
