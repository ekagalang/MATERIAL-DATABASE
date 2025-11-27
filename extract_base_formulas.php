<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Extracting BASE Formulas (Row 7-8): {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen (Uk. Bata KUO SHIN');

    if (! $sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: Adukan Semen (Uk. Bata KUO SHIN)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Scan ROW 7 dan 8 (base calculation per 1 m²)
    for ($row = 7; $row <= 8; $row++) {
        echo "\n🔍 ROW {$row}:\n";
        echo "─────────────────────────────────────────────────────────────\n\n";

        for ($col = 'A'; $col <= 'AH'; $col++) {
            $cell = $sheet->getCell($col.$row);

            if ($cell->isFormula()) {
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }

                echo "┌─────────────────────────────────────────────────────────────\n";
                echo "│ Cell: {$col}{$row}\n";
                echo "├─────────────────────────────────────────────────────────────\n";
                echo "│ Formula: {$formula}\n";
                echo "│ Result:  {$value}\n";
                echo "└─────────────────────────────────────────────────────────────\n\n";
            } else {
                $value = $cell->getValue();
                if (! empty($value) && $value !== '') {
                    echo "[{$col}{$row}] = {$value}\n";
                }
            }
        }
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
