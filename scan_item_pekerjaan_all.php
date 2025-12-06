<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Scanning ITEM PEKERJAAN Sheet for Material Calculations\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    $sheet = null;
    foreach ($spreadsheet->getAllSheets() as $s) {
        if (stripos($s->getTitle(), 'ITEM PEKERJAAN') !== false) {
            $sheet = $s;
            break;
        }
    }

    if (! $sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    $sheetName = $sheet->getTitle();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: {$sheetName}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Scan untuk kata kunci: Semen, Pasir, Air
    echo "🔍 SCANNING FOR MATERIAL KEYWORDS (Semen, Pasir, Air):\n\n";

    $highestRow = $sheet->getHighestRow();
    $materialRows = [];

    for ($row = 1; $row <= $highestRow; $row++) {
        for ($col = 'A'; $col <= 'Z'; $col++) {
            $cell = $sheet->getCell($col.$row);
            $value = $cell->getValue();

            if (stripos($value, 'semen') !== false ||
                stripos($value, 'pasir') !== false ||
                stripos($value, 'air') !== false ||
                stripos($value, 'cement') !== false ||
                stripos($value, 'sand') !== false) {

                if (! isset($materialRows[$row])) {
                    $materialRows[$row] = [];
                }
                $materialRows[$row][$col] = $value;
            }
        }
    }

    foreach ($materialRows as $row => $cols) {
        echo "─────────────────────────────────────────────────────────────\n";
        echo "ROW {$row}:\n";
        foreach ($cols as $col => $value) {
            echo "  [{$col}{$row}] = {$value}\n";
        }

        // Scan sampai kolom X untuk row ini
        echo "\n  Full Row {$row} Data (A-X):\n";
        for ($c = 'A'; $c <= 'X'; $c++) {
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

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
