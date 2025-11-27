<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus 2 .xlsx';

echo "📊 Reading Adukan Semen Sheet Detail\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen');

    if (!$sheet) {
        echo "❌ Sheet 'Adukan Semen' not found!\n";
        exit(1);
    }

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "SHEET: Adukan Semen\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Read specific ranges that contain key data
    echo "SECTION 1: Pasangan 1/2 Bata (Rows 12-22)\n";
    echo "─────────────────────────────────────────────────────────────\n\n";

    for ($row = 12; $row <= 22; $row++) {
        $label = $sheet->getCell('A' . $row)->getValue();
        $valueB = $sheet->getCell('B' . $row)->getValue();
        $valueC = $sheet->getCell('C' . $row)->getValue();
        $valueD = $sheet->getCell('D' . $row)->getValue();

        if (!empty($label)) {
            echo "Row {$row}:\n";
            echo "  [A] Label: {$label}\n";

            // Check if it's a formula
            $cellB = $sheet->getCell('B' . $row);
            if ($cellB->isFormula()) {
                echo "  [B] Formula: " . $cellB->getValue() . " → ";
                try {
                    echo $cellB->getCalculatedValue() . "\n";
                } catch (\Exception $e) {
                    echo "ERROR\n";
                }
            } else if (!empty($valueB)) {
                echo "  [B] Value: {$valueB}\n";
            }

            $cellC = $sheet->getCell('C' . $row);
            if ($cellC->isFormula()) {
                echo "  [C] Formula: " . $cellC->getValue() . " → ";
                try {
                    echo $cellC->getCalculatedValue() . "\n";
                } catch (\Exception $e) {
                    echo "ERROR\n";
                }
            } else if (!empty($valueC)) {
                echo "  [C] Value: {$valueC}\n";
            }

            $cellD = $sheet->getCell('D' . $row);
            if ($cellD->isFormula()) {
                echo "  [D] Formula: " . $cellD->getValue() . " → ";
                try {
                    echo $cellD->getCalculatedValue() . "\n";
                } catch (\Exception $e) {
                    echo "ERROR\n";
                }
            } else if (!empty($valueD)) {
                echo "  [D] Value: {$valueD}\n";
            }

            echo "\n";
        }
    }

    // Try to find input cells
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "LOOKING FOR INPUT PARAMETERS (Rows 1-10)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    for ($row = 1; $row <= 10; $row++) {
        $hasData = false;
        $rowData = [];

        for ($col = 'A'; $col <= 'H'; $col++) {
            $cell = $sheet->getCell($col . $row);
            $value = $cell->getValue();

            if (!empty($value)) {
                $hasData = true;
                if ($cell->isFormula()) {
                    try {
                        $calculated = $cell->getCalculatedValue();
                        $rowData[] = "[{$col}] {$value} → {$calculated}";
                    } catch (\Exception $e) {
                        $rowData[] = "[{$col}] {$value} → ERROR";
                    }
                } else {
                    $rowData[] = "[{$col}] {$value}";
                }
            }
        }

        if ($hasData) {
            echo "Row {$row}: " . implode(" | ", $rowData) . "\n";
        }
    }

    // Check for other sections
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "SECTION 2: Pasangan 1 Bata (Rows 24-34)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    for ($row = 24; $row <= 34; $row++) {
        $label = $sheet->getCell('A' . $row)->getValue();
        $valueB = $sheet->getCell('B' . $row)->getValue();
        $valueC = $sheet->getCell('C' . $row)->getValue();

        if (!empty($label)) {
            echo "Row {$row}: {$label}";
            if (!empty($valueB)) echo " | B: {$valueB}";
            if (!empty($valueC)) echo " | C: {$valueC}";
            echo "\n";
        }
    }

    echo "\n✅ Done!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
