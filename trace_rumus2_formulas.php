<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus 2 .xlsx';

echo "═══════════════════════════════════════════════════════════════\n";
echo "TRACE FORMULAS - RUMUS 2.XLSX\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen');

    if (!$sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    echo "BAGIAN 1/2 BATA - TRACING FORMULAS\n";
    echo "─────────────────────────────────────────────────────────────\n\n";

    // Row 7 adalah base calculation
    echo "ROW 7 (Base Calculation per 1 m²):\n";
    echo "──────────────────────────────────\n";

    // Luas Pasangan
    $cellB7 = $sheet->getCell('B7');
    echo "[B7] Luas Pasangan Dinding: " . $cellB7->getValue() . "\n";
    echo "     Result: " . $cellB7->getCalculatedValue() . " " . $sheet->getCell('C7')->getValue() . "\n\n";

    // Jumlah Bata
    $cellD7 = $sheet->getCell('D7');
    echo "[D7] Jumlah Pasangan Bata: " . $cellD7->getValue() . "\n";
    echo "     Result: " . $cellD7->getCalculatedValue() . " " . $sheet->getCell('E7')->getValue() . "\n\n";

    // Dimensi Panjang
    $cellF7 = $sheet->getCell('F7');
    echo "[F7] Dimensi Panjang: " . $cellF7->getValue() . "\n";
    echo "     Result: " . $cellF7->getCalculatedValue() . " cm\n\n";

    // ROW 8 - Normalisasi ke 1 m²
    echo "ROW 8 (Normalisasi ke 1 m²):\n";
    echo "──────────────────────────────────\n";

    $cellB8 = $sheet->getCell('B8');
    echo "[B8] Luas per 1 m²: " . $cellB8->getValue() . "\n";
    echo "     Result: " . $cellB8->getCalculatedValue() . " m²\n\n";

    $cellD8 = $sheet->getCell('D8');
    echo "[D8] Bata per 1 m²: " . $cellD8->getValue() . "\n";
    echo "     Result: " . $cellD8->getCalculatedValue() . " buah\n\n";

    // ROW 10 - Input dari ITEM PEKERJAAN
    echo "ROW 10 (Input dari Item Pekerjaan):\n";
    echo "──────────────────────────────────\n";

    $cellB10 = $sheet->getCell('B10');
    echo "[B10] Luas Bidang Dinding (Input): " . $cellB10->getValue() . "\n";
    echo "      Result: " . $cellB10->getCalculatedValue() . " m²\n\n";

    $cellD10 = $sheet->getCell('D10');
    echo "[D10] Total Kebutuhan Bata: " . $cellD10->getValue() . "\n";
    echo "      Result: " . $cellD10->getCalculatedValue() . " buah\n\n";

    // Volume Adukan
    echo "VOLUME ADUKAN:\n";
    echo "──────────────────────────────────\n";

    $cellO7 = $sheet->getCell('O7');
    if ($cellO7->getValue()) {
        echo "[O7] " . ($cellO7->isFormula() ? "Formula: " . $cellO7->getValue() : "Value: " . $cellO7->getValue()) . "\n";
        echo "     Result: " . $cellO7->getCalculatedValue() . "\n\n";
    }

    $cellQ7 = $sheet->getCell('Q7');
    echo "[Q7] Volume Adukan per luas: " . $cellQ7->getValue() . "\n";
    echo "     Result: " . $cellQ7->getCalculatedValue() . " m³\n\n";

    $cellQ10 = $sheet->getCell('Q10');
    echo "[Q10] Total Volume Adukan: " . $cellQ10->getValue() . "\n";
    echo "      Result: " . $cellQ10->getCalculatedValue() . " m³\n\n";

    // Semen
    echo "KEBUTUHAN SEMEN:\n";
    echo "──────────────────────────────────\n";

    $cellS10 = $sheet->getCell('S10');
    echo "[S10] Kebutuhan Semen (Sak 40kg): " . $cellS10->getValue() . "\n";
    echo "      Result: " . $cellS10->getCalculatedValue() . " sak\n\n";

    $cellD13 = $sheet->getCell('D13');
    echo "[D13] Semen per m² pasangan: " . $cellD13->getValue() . " sak\n\n";

    // Pasir
    echo "KEBUTUHAN PASIR:\n";
    echo "──────────────────────────────────\n";

    $cellY10 = $sheet->getCell('Y10');
    echo "[Y10] Kebutuhan Pasir (Sak): " . $cellY10->getValue() . "\n";
    echo "      Result: " . $cellY10->getCalculatedValue() . " sak\n\n";

    $cellD18 = $sheet->getCell('D18');
    echo "[D18] Pasir per m² pasangan: " . $cellD18->getValue() . " sak\n\n";

    // Air
    echo "KEBUTUHAN AIR:\n";
    echo "──────────────────────────────────\n";

    $cellAE10 = $sheet->getCell('AE10');
    echo "[AE10] Kebutuhan Air (Liter): " . $cellAE10->getValue() . "\n";
    echo "       Result: " . $cellAE10->getCalculatedValue() . " liter\n\n";

    $cellD20 = $sheet->getCell('D20');
    echo "[D20] Air per m² pasangan: " . $cellD20->getValue() . " liter\n\n";

    // Cek cell D13, D14, D15 untuk ratio per m²
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "ANGKA KUNCI (PER M² PASANGAN BATA):\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    echo "Semen per m²: " . $sheet->getCell('D13')->getValue() . " sak (40kg)\n";
    echo "Bata per m²: " . $sheet->getCell('D14')->getValue() . " buah\n";
    echo "Volume adukan per m²: " . $sheet->getCell('D15')->getValue() . " m³\n";
    echo "Semen per m²: " . $sheet->getCell('D16')->getValue() . " sak (40kg)\n";
    echo "Pasir per m²: " . $sheet->getCell('D18')->getValue() . " sak\n";
    echo "Air per m²: " . $sheet->getCell('D20')->getValue() . " liter\n\n";

    // Check formula details for keysells
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "DETAIL FORMULA CELLS:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $keyCells = ['O7', 'Q7', 'S7', 'Y7', 'AE7', 'S10', 'Y10', 'AE10'];

    foreach ($keyCells as $cellRef) {
        $cell = $sheet->getCell($cellRef);
        if ($cell->isFormula()) {
            echo "[{$cellRef}] Formula: " . $cell->getValue() . "\n";
            try {
                echo "         Result: " . $cell->getCalculatedValue() . "\n";
            } catch (\Exception $e) {
                echo "         Result: ERROR\n";
            }
        } else {
            $val = $cell->getValue();
            if (!empty($val)) {
                echo "[{$cellRef}] Value: {$val}\n";
            }
        }
        echo "\n";
    }

    echo "✅ Tracing complete!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
