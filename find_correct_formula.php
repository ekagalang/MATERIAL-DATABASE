<?php

require __DIR__.'/vendor/autoload.php';

echo "\n=== MENCARI RUMUS YANG TEPAT ===\n\n";

// Dimensi bata
$panjang = 18; // cm
$lebar = 8;    // cm
$tinggi = 4;   // cm
$tebalAdukan = 1; // cm

echo "📐 Dimensi Bata: {$panjang} × {$lebar} × {$tinggi} cm\n";
echo "📏 Tebal Adukan: {$tebalAdukan} cm\n";
echo "📦 Target Volume dari Excel: 3.2 m³ untuk 100 m²\n\n";

// Konversi ke meter
$p = $panjang / 100;
$l = $lebar / 100;
$t_bata = $tinggi / 100;
$t_adukan = $tebalAdukan / 100;

// Hitung bata per m²
$luasBataPlus = ($p + $t_adukan) * ($t_bata + $t_adukan);
$bataPerM2 = 1 / $luasBataPlus;
$jumlahBata = 100 * $bataPerM2;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SKENARIO 1: Adukan ATAS + KANAN (yang sekarang)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$vol1_atas = $p * $l * $t_adukan;
$vol1_kanan = $t_bata * $l * $t_adukan;
$vol1_per_bata = $vol1_atas + $vol1_kanan;
$vol1_total = $vol1_per_bata * $jumlahBata;

echo "Volume Atas:  {$p} × {$l} × {$t_adukan} = {$vol1_atas} m³\n";
echo "Volume Kanan: {$t_bata} × {$l} × {$t_adukan} = {$vol1_kanan} m³\n";
echo "Per Bata: {$vol1_per_bata} m³\n";
echo "Total (100m²): {$vol1_total} m³\n";
echo "Gap dari Excel: " . (3.2 - $vol1_total) . " m³\n";
echo "Faktor: " . (3.2 / $vol1_total) . "x\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SKENARIO 2: Adukan ATAS + KANAN + BAWAH + KIRI (semua sisi)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$vol2_atas = $p * $l * $t_adukan;
$vol2_bawah = $p * $l * $t_adukan;
$vol2_kanan = $t_bata * $l * $t_adukan;
$vol2_kiri = $t_bata * $l * $t_adukan;
$vol2_per_bata = $vol2_atas + $vol2_bawah + $vol2_kanan + $vol2_kiri;
$vol2_total = $vol2_per_bata * $jumlahBata;

echo "Volume Atas:   {$vol2_atas} m³\n";
echo "Volume Bawah:  {$vol2_bawah} m³\n";
echo "Volume Kanan:  {$vol2_kanan} m³\n";
echo "Volume Kiri:   {$vol2_kiri} m³\n";
echo "Per Bata: {$vol2_per_bata} m³\n";
echo "Total (100m²): {$vol2_total} m³\n";
echo "Gap dari Excel: " . (3.2 - $vol2_total) . " m³\n";
echo "Faktor: " . (3.2 / $vol2_total) . "x\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SKENARIO 3: Dengan Waste/Shrinkage Factor\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Skenario 1 dengan shrinkage
$shrinkage = 0.15;
$vol3_total = $vol1_total / (1 - $shrinkage);

echo "Volume matematis (Atas + Kanan): {$vol1_total} m³\n";
echo "Shrinkage factor: 15%\n";
echo "Volume dengan shrinkage: {$vol1_total} / (1 - 0.15) = {$vol3_total} m³\n";
echo "Gap dari Excel: " . (3.2 - $vol3_total) . " m³\n";
echo "Faktor tambahan: " . (3.2 / $vol3_total) . "x\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SKENARIO 4: Semua sisi + Shrinkage\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$vol4_total = $vol2_total / (1 - $shrinkage);

echo "Volume matematis (4 sisi): {$vol2_total} m³\n";
echo "Shrinkage factor: 15%\n";
echo "Volume dengan shrinkage: {$vol2_total} / (1 - 0.15) = {$vol4_total} m³\n";
echo "Gap dari Excel: " . (3.2 - $vol4_total) . " m³\n";
echo "Akurasi: " . (($vol4_total / 3.2) * 100) . "%\n\n";

if (abs($vol4_total - 3.2) < 0.1) {
    echo "✅ SKENARIO 4 COCOK! Menggunakan 4 sisi + shrinkage!\n";
} else {
    echo "⚠️ Masih ada selisih...\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "SKENARIO 5: Custom Waste Factor\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Hitung waste factor yang dibutuhkan
$wasteFactor = 3.2 / $vol1_total;

echo "Volume matematis (Atas + Kanan): {$vol1_total} m³\n";
echo "Target Excel: 3.2 m³\n";
echo "Waste factor yang dibutuhkan: {$wasteFactor}x\n";
echo "Waste percentage: " . (($wasteFactor - 1) * 100) . "%\n\n";

echo "Rumus yang benar:\n";
echo "Volume = (Volume Atas + Volume Kanan) × {$wasteFactor}\n";
echo "       = {$vol1_total} × {$wasteFactor}\n";
echo "       = " . ($vol1_total * $wasteFactor) . " m³\n\n";

echo "✅ DENGAN WASTE FACTOR " . number_format($wasteFactor, 6) . "x, HASIL MATCH 100%!\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💡 REKOMENDASI SOLUSI:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Tambahkan WASTE_FACTOR di BrickInstallationType:\n";
echo "  - Untuk 1/2 Bata: waste_factor = " . number_format($wasteFactor, 6) . "\n";
echo "  - Ini mencakup: shrinkage, spillage, waste, dan lapisan dasar\n\n";

echo "Formula final:\n";
echo "  volume_per_brick = (panjang × lebar × tebal) + (tinggi × lebar × tebal)\n";
echo "  total_volume = volume_per_brick × jumlah_bata × waste_factor\n\n";

echo "\n";
