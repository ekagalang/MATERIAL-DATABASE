<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Extracting EXACT Formulas: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Adukan Semen (Uk. Bata KUO SHIN');

    if (! $sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    $sheetName = $sheet->getTitle();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SHEET: {$sheetName}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $row = 10;

    // Kolom penting untuk perhitungan
    $columns = [
        'Q' => 'Volume Adukan (m³)',
        'R' => 'Satuan Volume Adukan',
        'S' => 'Kebutuhan Semen (Sak 40kg)',
        'T' => 'Satuan Semen Sak',
        'V' => 'Kebutuhan Semen (Kg)',
        'W' => 'Satuan Semen Kg',
        'AB' => 'Kebutuhan Pasir (Sak)',
        'AC' => 'Satuan Pasir Sak',
        'AE' => 'Kebutuhan Pasir (m³)',
        'AF' => 'Satuan Pasir m³',
        'AH' => 'Kebutuhan Air (Liter)',
        'AI' => 'Satuan Air',
    ];

    echo "🔍 ROW {$row} - RUMUS PERHITUNGAN UTAMA:\n\n";

    foreach ($columns as $col => $label) {
        $cell = $sheet->getCell($col.$row);

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR: '.$e->getMessage();
            }

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

    // Sekarang cari detail di header (row 5-9)
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "📄 HEADER & SETTING (Row 1-9)\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    for ($r = 1; $r <= 9; $r++) {
        $hasData = false;
        $rowData = [];

        for ($col = 'A'; $col <= 'AH'; $col++) {
            $cell = $sheet->getCell($col.$r);

            if ($cell->isFormula()) {
                $hasData = true;
                $formula = $cell->getValue();
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $value = 'ERROR';
                }
                $rowData[] = "[{$col}{$r}] = {$formula} → {$value}";
            } else {
                $value = $cell->getValue();
                if (! empty($value) && $value !== '') {
                    $hasData = true;
                    $rowData[] = "[{$col}{$r}] = {$value}";
                }
            }
        }

        if ($hasData) {
            echo "Row {$r}:\n";
            foreach ($rowData as $data) {
                echo "  {$data}\n";
            }
            echo "\n";
        }
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
