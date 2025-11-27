<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Mengekstrak Rumus Perhitungan Bata dari: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    // Fokus pada sheet "Pasangan Bata Merah KUO SHIN"
    $sheet = $spreadsheet->getSheetByName('Pasangan Bata Merah KUO SHIN');

    if (! $sheet) {
        echo "❌ Sheet 'Pasangan Bata Merah KUO SHIN' tidak ditemukan!\n";
        exit(1);
    }

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: Pasangan Bata Merah KUO SHIN\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Area yang penting untuk perhitungan adukan (kolom Q sampai AH)
    // Kita akan fokus pada baris 10 (1/2 Bata) sebagai contoh
    $row = 10;

    echo "🔍 MENGEKSTRAK RUMUS UNTUK 1/2 BATA (Row {$row}):\n\n";

    // Kolom penting:
    // Q = Volume Adukan
    // S = Kebutuhan Semen (Sak)
    // V = Kebutuhan Semen (Kg)
    // AB = Kebutuhan Pasir (Sak)
    // AE = Kebutuhan Pasir (m³)
    // AH = Kebutuhan Air (Liter)

    $importantColumns = [
        'D' => 'Kebutuhan Bata',
        'Q' => 'Volume Adukan (m³)',
        'S' => 'Semen (Sak 50kg)',
        'V' => 'Semen (Kg)',
        'AB' => 'Pasir (Sak)',
        'AE' => 'Pasir (m³)',
        'AH' => 'Air (Liter)',
    ];

    foreach ($importantColumns as $col => $label) {
        $cell = $sheet->getCell($col.$row);

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            $value = $cell->getCalculatedValue();

            echo "┌─────────────────────────────────────────────────────────────\n";
            echo "│ {$label}\n";
            echo "├─────────────────────────────────────────────────────────────\n";
            echo "│ Cell:    {$col}{$row}\n";
            echo "│ Formula: {$formula}\n";
            echo "│ Result:  {$value}\n";
            echo "└─────────────────────────────────────────────────────────────\n\n";
        } else {
            $value = $cell->getValue();
            if (! empty($value)) {
                echo "[{$col}{$row}] {$label} = {$value}\n\n";
            }
        }
    }

    echo "\n\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 MENCARI DETAIL PERHITUNGAN DI KOLOM SEBELUMNYA\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Coba scan area perhitungan detail (kolom A-P, row 1-20)
    for ($r = 1; $r <= 20; $r++) {
        $hasFormula = false;
        for ($c = 'A'; $c <= 'P'; $c++) {
            $cell = $sheet->getCell($c.$r);
            if ($cell->isFormula()) {
                $hasFormula = true;
                break;
            }
        }

        if ($hasFormula) {
            echo "Row {$r}:\n";
            for ($c = 'A'; $c <= 'P'; $c++) {
                $cell = $sheet->getCell($c.$r);
                if ($cell->isFormula()) {
                    $formula = $cell->getValue();
                    $value = $cell->getCalculatedValue();
                    echo "  [{$c}{$r}] = {$formula} → {$value}\n";
                } else {
                    $value = $cell->getValue();
                    if (! empty($value) && $value !== '') {
                        echo "  [{$c}{$r}] = {$value}\n";
                    }
                }
            }
            echo "\n";
        }
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
