<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Finding Material Calculation Results in Excel\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    echo "Available Sheets:\n";
    foreach ($spreadsheet->getAllSheets() as $idx => $s) {
        echo '  '.($idx + 1).'. '.$s->getTitle()."\n";
    }
    echo "\n";

    // Cek sheet "Adukan Semen" yang kita tahu punya rumus
    $adukanSheet = $spreadsheet->getSheetByName('Adukan Semen (Uk. Bata KUO SHIN');

    if ($adukanSheet) {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "📄 SHEET: Adukan Semen (Uk. Bata KUO SHIN)\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Row 10 (1/2 Bata) - Hasil Perhitungan:\n";
        echo "─────────────────────────────────────────────────────────────\n\n";

        // Kolom yang kemungkinan berisi hasil
        $columns = [
            'S' => 'Kebutuhan Semen (Sak 40kg)',
            'V' => 'Kebutuhan Semen (Kg)',
            'AB' => 'Kebutuhan Pasir (Sak)',
            'AE' => 'Kebutuhan Pasir (m³)',
            'AH' => 'Kebutuhan Air (Liter)',
        ];

        foreach ($columns as $col => $label) {
            $cell = $adukanSheet->getCell($col.'10');

            if ($cell->isFormula()) {
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }
                echo "{$label}:\n";
                echo "  Cell: {$col}10\n";
                echo "  Formula: {$formula}\n";
                echo "  Result: {$value}\n\n";
            } else {
                $value = $cell->getValue();
                if (! empty($value)) {
                    echo "{$label}:\n";
                    echo "  Cell: {$col}10\n";
                    echo "  Value: {$value}\n\n";
                }
            }
        }

        // Cek juga volume adukan di kolom Q
        echo "Volume Adukan:\n";
        $qCell = $adukanSheet->getCell('Q10');
        if ($qCell->isFormula()) {
            $formula = $qCell->getValue();
            try {
                $value = $qCell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR';
            }
            echo "  Cell: Q10\n";
            echo "  Formula: {$formula}\n";
            echo "  Result: {$value} m³\n\n";
        } else {
            $value = $qCell->getValue();
            echo "  Cell: Q10\n";
            echo "  Value: {$value} m³\n\n";
        }
    }

    // Cek juga sheet "Pasangan Bata Merah KUO SHIN"
    $brickSheet = $spreadsheet->getSheetByName('Pasangan Bata Merah KUO SHIN');

    if ($brickSheet) {
        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "📄 SHEET: Pasangan Bata Merah KUO SHIN\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "Row 10 (1/2 Bata) - Kolom X, Y, Z (jika ada):\n";
        echo "─────────────────────────────────────────────────────────────\n\n";

        for ($col = 'X'; $col <= 'Z'; $col++) {
            $cell = $brickSheet->getCell($col.'10');

            if ($cell->isFormula()) {
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }
                echo "Cell {$col}10:\n";
                echo "  Formula: {$formula}\n";
                echo "  Result: {$value}\n\n";
            } else {
                $value = $cell->getValue();
                if (! empty($value) && $value !== '') {
                    echo "Cell {$col}10: {$value}\n\n";
                }
            }
        }
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
