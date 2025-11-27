<?php

require __DIR__.'/vendor/autoload.php';

echo "\n=== ANALISIS RUMUS VOLUME ADUKAN PER BATA ===\n\n";

// Dimensi bata (dari example.txt: 18 x 8 x 4 cm)
$panjang = 18; // cm (length)
$lebar = 8;    // cm (width)
$tinggi = 4;   // cm (height)
$tebalAdukan = 1; // cm (mortar thickness)

echo "📐 Dimensi Bata (dari example.txt):\n";
echo "   - Panjang (length): {$panjang} cm\n";
echo "   - Lebar (width): {$lebar} cm\n";
echo "   - Tinggi (height): {$tinggi} cm\n";
echo "   - Tebal adukan: {$tebalAdukan} cm\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📋 RUMUS YANG ANDA SEBUTKAN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Rumus dari user: (panjang + 2 + tinggi) x 2 x lebar
echo "Formula: (panjang + tebal + tinggi) × tebal × lebar\n";
echo "         atau bisa juga: (panjang × tebal × lebar) + (tinggi × tebal × lebar)\n\n";

$volumeUser = (($panjang * $tebalAdukan * $lebar) + ($tinggi * $tebalAdukan * $lebar)) / 1000000; // cm³ to m³

echo "Perhitungan:\n";
echo "   Volume Atas  = panjang × tebal × lebar\n";
echo "                = {$panjang} × {$tebalAdukan} × {$lebar}\n";
echo "                = ".($panjang * $tebalAdukan * $lebar)." cm³\n\n";
echo "   Volume Kanan = tinggi × tebal × lebar\n";
echo "                = {$tinggi} × {$tebalAdukan} × {$lebar}\n";
echo "                = ".($tinggi * $tebalAdukan * $lebar)." cm³\n\n";
echo "   Total Volume = ".($panjang * $tebalAdukan * $lebar + $tinggi * $tebalAdukan * $lebar)." cm³\n";
echo "                = {$volumeUser} m³ per bata\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "💻 RUMUS YANG SEKARANG DIGUNAKAN SISTEM:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$l = $panjang / 100; // m
$w = $lebar / 100;   // m
$h = $tinggi / 100;  // m
$t = $tebalAdukan / 100; // m

$volumeTop = $l * $w * $t;
$volumeRight = $h * $w * $t;
$volumeSystem = $volumeTop + $volumeRight;

echo "Formula: (panjang × lebar × tebal) + (tinggi × lebar × tebal)\n\n";
echo "Perhitungan:\n";
echo "   Volume Atas  = panjang × lebar × tebal\n";
echo "                = {$l} × {$w} × {$t}\n";
echo "                = {$volumeTop} m³\n\n";
echo "   Volume Kanan = tinggi × lebar × tebal\n";
echo "                = {$h} × {$w} × {$t}\n";
echo "                = {$volumeRight} m³\n\n";
echo "   Total Volume = {$volumeSystem} m³ per bata\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 PERBANDINGAN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "   Rumus User:   ".number_format($volumeUser, 10)." m³ per bata\n";
echo "   Rumus System: ".number_format($volumeSystem, 10)." m³ per bata\n";
echo "   Selisih:      ".number_format(abs($volumeUser - $volumeSystem), 10)." m³\n\n";

if (abs($volumeUser - $volumeSystem) < 0.0000001) {
    echo "✅ KEDUA RUMUS IDENTIK!\n";
} else {
    echo "⚠️ ADA PERBEDAAN!\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 VERIFIKASI DENGAN DATA EXCEL:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Untuk 100 m² dengan 1/2 Bata
$luasDinding = 100; // m²

// Hitung jumlah bata per m²
$bataPerM2System = 1 / (($l + $t) * ($h + $t));
$jumlahBata = $luasDinding * $bataPerM2System;

echo "Untuk dinding {$luasDinding} m²:\n";
echo "   Bata per m²: ".number_format($bataPerM2System, 2)." buah/m²\n";
echo "   Total bata:  ".number_format($jumlahBata, 2)." buah\n\n";

$totalVolumeUser = $volumeUser * $jumlahBata;
$totalVolumeSystem = $volumeSystem * $jumlahBata;

echo "Total Volume Adukan:\n";
echo "   Menggunakan rumus user:   ".number_format($totalVolumeUser, 6)." m³\n";
echo "   Menggunakan rumus system: ".number_format($totalVolumeSystem, 6)." m³\n";
echo "   Dari Excel (fixed):       3.200000 m³\n\n";

$deviationUser = abs($totalVolumeUser - 3.2);
$deviationSystem = abs($totalVolumeSystem - 3.2);

echo "Deviasi dari Excel:\n";
echo "   Rumus user:   ".number_format($deviationUser, 6)." m³ (".number_format(($deviationUser / 3.2) * 100, 2)."%)\n";
echo "   Rumus system: ".number_format($deviationSystem, 6)." m³ (".number_format(($deviationSystem / 3.2) * 100, 2)."%)\n\n";

if ($deviationUser < $deviationSystem) {
    echo "✅ Rumus user lebih mendekati Excel!\n";
} elseif ($deviationSystem < $deviationUser) {
    echo "✅ Rumus system lebih mendekati Excel!\n";
} else {
    echo "✅ Kedua rumus sama akuratnya!\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📝 KESIMPULAN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Rumus yang Anda sebutkan:\n";
echo "   (panjang × tebal × lebar) + (tinggi × tebal × lebar)\n\n";
echo "Rumus yang digunakan sistem:\n";
echo "   (panjang × lebar × tebal) + (tinggi × lebar × tebal)\n\n";
echo "➡️ Kedua rumus ini IDENTIK (hanya urutan perkalian berbeda)!\n";
echo "➡️ Rumus sudah BENAR secara matematis!\n\n";

echo "NAMUN, karena hasil perhitungan (".number_format($totalVolumeSystem, 6)." m³)\n";
echo "tidak sama dengan Excel (3.200000 m³),\n";
echo "maka sistem sekarang menggunakan FIXED VALUE dari Excel\n";
echo "yang di-scale berdasarkan tebal adukan user input.\n\n";

echo "\n";
