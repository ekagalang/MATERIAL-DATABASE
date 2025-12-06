<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Membaca file: {$excelFile}\n\n";

try {
    $spreadsheet = IOFactory::load($excelFile);

    // Loop semua sheet
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "📄 SHEET: {$sheetName}\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Loop semua cell
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellCoordinate = $col.$row;
                $cell = $sheet->getCell($cellCoordinate);

                try {
                    $cellValue = $cell->getCalculatedValue();
                } catch (\Exception $e) {
                    $cellValue = $cell->getValue();
                }

                // Cek apakah cell memiliki formula
                if ($cell->isFormula()) {
                    $formula = $cell->getValue();

                    echo "┌─────────────────────────────────────────────────────────────\n";
                    echo "│ Cell: {$cellCoordinate}\n";
                    echo "├─────────────────────────────────────────────────────────────\n";
                    echo "│ Formula: {$formula}\n";
                    echo "│ Result:  {$cellValue}\n";
                    echo "└─────────────────────────────────────────────────────────────\n\n";
                } elseif (! empty($cellValue) && $cellValue !== '') {
                    // Tampilkan nilai yang bukan formula (untuk konteks)
                    echo "[{$cellCoordinate}] = {$cellValue}\n";
                }
            }
        }

        echo "\n";
    }

    echo "\n✅ Selesai membaca file Excel!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
