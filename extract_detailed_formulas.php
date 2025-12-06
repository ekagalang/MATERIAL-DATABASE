<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Extracting Detailed Formulas from Adukan Semen Sheet\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen (Uk. Bata KUO SHIN');

    if (! $sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 ROW 8 - BASIS PERHITUNGAN PER M³\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Kolom penting
    $columns = [
        'Q' => 'Volume Adukan',
        'S' => 'Semen (Sak 40kg)',
        'V' => 'Semen (Kg)',
        'AB' => 'Pasir (Sak)',
        'AE' => 'Pasir (m³)',
        'AH' => 'Air (Liter)',
    ];

    foreach ($columns as $col => $label) {
        $cell = $sheet->getCell($col.'8');

        echo "┌─────────────────────────────────────────────────────────────\n";
        echo "│ {$label}\n";
        echo "├─────────────────────────────────────────────────────────────\n";
        echo "│ Cell: {$col}8\n";

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR';
            }
            echo "│ Formula: {$formula}\n";
            echo "│ Result: {$value}\n";
        } else {
            $value = $cell->getValue();
            echo "│ Value: {$value}\n";
        }
        echo "└─────────────────────────────────────────────────────────────\n\n";
    }

    // Cek juga sel-sel referensi penting
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "📄 REFERENSI PENTING (Rows 1-20, Various Columns)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Scan D column untuk label
    for ($row = 1; $row <= 20; $row++) {
        $dCell = $sheet->getCell('D'.$row);
        $dValue = $dCell->getValue();

        if (stripos($dValue, 'rasio') !== false ||
            stripos($dValue, 'ratio') !== false ||
            stripos($dValue, 'perbandingan') !== false ||
            stripos($dValue, 'kubik') !== false ||
            stripos($dValue, 'volume') !== false ||
            stripos($dValue, 'dimensi') !== false ||
            stripos($dValue, 'kemasan') !== false) {

            echo "Row {$row}: {$dValue}\n";

            // Cek kolom E, F, G untuk nilai terkait
            for ($c = 'E'; $c <= 'H'; $c++) {
                $cell = $sheet->getCell($c.$row);
                if ($cell->isFormula()) {
                    $formula = $cell->getValue();
                    try {
                        $val = $cell->getCalculatedValue();
                    } catch (\Exception $e) {
                        $val = 'ERROR';
                    }
                    echo "  [{$c}{$row}] FORMULA: {$formula} → {$val}\n";
                } else {
                    $val = $cell->getValue();
                    if (! empty($val) && $val !== '') {
                        echo "  [{$c}{$row}] = {$val}\n";
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
