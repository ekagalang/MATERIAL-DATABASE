<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Scanning ALL Formulas: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);
    $sheet = $spreadsheet->getSheetByName('Pasangan Bata Merah KUO SHIN');

    if (! $sheet) {
        echo "❌ Sheet not found!\n";
        exit(1);
    }

    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📄 SCANNING ROW 10 (1/2 Bata) - ALL COLUMNS\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    $row = 10;
    $highestColumn = $sheet->getHighestColumn();

    for ($col = 'A'; $col <= $highestColumn; $col++) {
        $cell = $sheet->getCell($col.$row);

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR: '.$e->getMessage();
            }

            echo "┌─────────────────────────────────────────────────────────────\n";
            echo "│ Cell: {$col}{$row}\n";
            echo "├─────────────────────────────────────────────────────────────\n";
            echo "│ Formula: {$formula}\n";
            echo "│ Result:  {$value}\n";
            echo "└─────────────────────────────────────────────────────────────\n\n";
        }
    }

    echo "\n\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
