<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus 2 .xlsx';

echo "📊 Extracting Formulas from: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    // List all sheets
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "AVAILABLE SHEETS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $sheetNames = $spreadsheet->getSheetNames();
    foreach ($sheetNames as $index => $name) {
        echo ($index + 1) . ". {$name}\n";
    }
    echo "\n";

    // Try to find "Adukan" sheet
    $targetSheet = null;
    foreach ($sheetNames as $name) {
        if (stripos($name, 'adukan') !== false || stripos($name, 'semen') !== false) {
            $targetSheet = $spreadsheet->getSheetByName($name);
            break;
        }
    }

    // If not found, use first sheet
    if (!$targetSheet) {
        $targetSheet = $spreadsheet->getSheet(0);
    }

    $sheetName = $targetSheet->getTitle();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 ANALYZING SHEET: {$sheetName}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Read all data from the sheet
    $highestRow = $targetSheet->getHighestRow();
    $highestColumn = $targetSheet->getHighestColumn();

    echo "Sheet size: {$highestColumn}{$highestRow}\n\n";

    // Scan for formulas and important cells
    echo "SCANNING FOR FORMULAS AND DATA...\n";
    echo "─────────────────────────────────────────────────────────────\n\n";

    $foundFormulas = [];
    $foundData = [];

    // Scan first 30 rows and columns A to AZ
    for ($row = 1; $row <= min(30, $highestRow); $row++) {
        for ($col = 'A'; $col <= 'AZ' && $col <= $highestColumn; $col++) {
            $cell = $targetSheet->getCell($col . $row);

            if ($cell->isFormula()) {
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }

                $foundFormulas[] = [
                    'cell' => $col . $row,
                    'formula' => $formula,
                    'value' => $value,
                    'row' => $row
                ];
            } else {
                $value = $cell->getValue();
                if (!empty($value) && $value !== '') {
                    // Store interesting data (labels, values)
                    $foundData[] = [
                        'cell' => $col . $row,
                        'value' => $value,
                        'row' => $row
                    ];
                }
            }
        }
    }

    // Print found data (headers and labels)
    echo "FOUND DATA (Labels & Values):\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $currentRow = 0;
    foreach ($foundData as $data) {
        if ($data['row'] != $currentRow) {
            if ($currentRow > 0) echo "\n";
            echo "Row {$data['row']}:\n";
            $currentRow = $data['row'];
        }
        $cellValue = is_numeric($data['value']) ? number_format($data['value'], 6) : $data['value'];
        echo "  [{$data['cell']}] = {$cellValue}\n";
    }

    echo "\n";

    // Print found formulas
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "FOUND FORMULAS:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $currentRow = 0;
    foreach ($foundFormulas as $formula) {
        if ($formula['row'] != $currentRow) {
            if ($currentRow > 0) echo "\n";
            echo "Row {$formula['row']}:\n";
            $currentRow = $formula['row'];
        }

        $valueDisplay = is_numeric($formula['value']) ? number_format($formula['value'], 6) : $formula['value'];
        echo "┌─────────────────────────────────────────────────────────────\n";
        echo "│ Cell: {$formula['cell']}\n";
        echo "│ Formula: {$formula['formula']}\n";
        echo "│ Result: {$valueDisplay}\n";
        echo "└─────────────────────────────────────────────────────────────\n";
    }

    // Try to identify key calculations
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "IDENTIFYING KEY CALCULATIONS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Look for keywords in data
    $keywords = ['semen', 'pasir', 'air', 'water', 'cement', 'sand', 'bata', 'brick', 'luas', 'area', 'volume', 'sak', 'kg', 'm3', 'm²', 'ratio'];

    echo "Cells containing key terms:\n";
    echo "─────────────────────────────────────────────────────────────\n";

    foreach ($foundData as $data) {
        $valueStr = strtolower((string)$data['value']);
        foreach ($keywords as $keyword) {
            if (stripos($valueStr, $keyword) !== false) {
                echo "[{$data['cell']}] {$data['value']}\n";
                break;
            }
        }
    }

    echo "\n✅ Extraction complete!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
