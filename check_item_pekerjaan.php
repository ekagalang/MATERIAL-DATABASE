<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Checking ITEM PEKERJAAN Sheet: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    // Coba cari sheet dengan nama yang mengandung "ITEM PEKERJAAN"
    $sheet = null;
    foreach ($spreadsheet->getAllSheets() as $s) {
        if (stripos($s->getTitle(), 'ITEM PEKERJAAN') !== false) {
            $sheet = $s;
            break;
        }
    }

    if (! $sheet) {
        echo "❌ Sheet 'ITEM PEKERJAAN' not found!\n";
        echo "Available sheets:\n";
        foreach ($spreadsheet->getAllSheets() as $s) {
            echo '  - '.$s->getTitle()."\n";
        }
        exit(1);
    }

    $sheetName = $sheet->getTitle();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: {$sheetName}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Baca X5, X6, X7
    $cells = ['X5', 'X6', 'X7'];

    echo "🔍 CHECKING CELLS X5, X6, X7:\n\n";

    foreach ($cells as $cellAddr) {
        $cell = $sheet->getCell($cellAddr);

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR: '.$e->getMessage();
            }

            echo "┌─────────────────────────────────────────────────────────────\n";
            echo "│ Cell: {$cellAddr}\n";
            echo "├─────────────────────────────────────────────────────────────\n";
            echo "│ Formula: {$formula}\n";
            echo "│ Result:  {$value}\n";
            echo "└─────────────────────────────────────────────────────────────\n\n";
        } else {
            $value = $cell->getValue();
            echo "[{$cellAddr}] = {$value}\n\n";
        }
    }

    // Baca juga header/label di kolom sebelumnya untuk konteks
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "📄 CONTEXT - Row 5, 6, 7 (Columns A-X):\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    for ($row = 5; $row <= 7; $row++) {
        echo "─────────────────────────────────────────────────────────────\n";
        echo "ROW {$row}:\n";
        echo "─────────────────────────────────────────────────────────────\n";

        $rowData = [];
        for ($col = 'A'; $col <= 'X'; $col++) {
            $cell = $sheet->getCell($col.$row);

            if ($cell->isFormula()) {
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }
                $rowData[] = "[{$col}{$row}] FORMULA: {$formula} → {$value}";
            } else {
                $value = $cell->getValue();
                if (! empty($value) && $value !== '') {
                    $rowData[] = "[{$col}{$row}] = {$value}";
                }
            }
        }

        foreach ($rowData as $data) {
            echo "  {$data}\n";
        }
        echo "\n";
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
