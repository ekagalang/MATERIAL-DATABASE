<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Scanning Adukan Semen Sheet: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen (Uk. Bata KUO SHIN');

    if (! $sheet) {
        // Coba tanpa kurung
        $sheet = $spreadsheet->getSheetByName('Adukan Semen');
        if (! $sheet) {
            echo "❌ Sheet not found! Available sheets:\n";
            foreach ($spreadsheet->getAllSheets() as $s) {
                echo '  - '.$s->getTitle()."\n";
            }
            exit(1);
        }
    }

    $sheetName = $sheet->getTitle();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: {$sheetName}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Scan area perhitungan (berdasarkan output sebelumnya, row 10-20 penting)
    echo "🔍 SCANNING ROWS 10-20 FOR FORMULAS:\n\n";

    for ($row = 10; $row <= 20; $row++) {
        $hasFormula = false;
        $rowData = [];

        for ($col = 'A'; $col <= 'P'; $col++) {
            $cell = $sheet->getCell($col.$row);

            if ($cell->isFormula()) {
                $hasFormula = true;
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }
                $rowData[$col] = "[FORMULA] {$formula} → {$value}";
            } else {
                $value = $cell->getValue();
                if (! empty($value) && $value !== '') {
                    $rowData[$col] = "[VALUE] {$value}";
                }
            }
        }

        if ($hasFormula || ! empty($rowData)) {
            echo "─────────────────────────────────────────────────────────────\n";
            echo "ROW {$row}:\n";
            echo "─────────────────────────────────────────────────────────────\n";
            foreach ($rowData as $col => $data) {
                echo "  {$col}{$row}: {$data}\n";
            }
            echo "\n";
        }
    }

    echo "\n\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
