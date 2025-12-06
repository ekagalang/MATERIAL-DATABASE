<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelFile = __DIR__.'/rumus.xlsx';

echo "📊 Checking Column X in ITEM PEKERJAAN Sheet\n\n";

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

    // Scan kolom X dari row 1 sampai 30
    echo "🔍 COLUMN X (Rows 1-30):\n\n";

    for ($row = 1; $row <= 30; $row++) {
        $cell = $sheet->getCell('X'.$row);

        // Baca juga kolom D-F untuk label/konteks
        $labelD = $sheet->getCell('D'.$row)->getValue();
        $labelE = $sheet->getCell('E'.$row)->getValue();
        $labelF = $sheet->getCell('F'.$row)->getValue();

        if ($cell->isFormula()) {
            $formula = $cell->getValue();
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Exception $e) {
                $value = 'ERROR: '.$e->getMessage();
            }

            echo "┌─────────────────────────────────────────────────────────────\n";
            echo "│ Row {$row}\n";
            echo "│ Label: [{$labelD}] [{$labelE}] [{$labelF}]\n";
            echo "├─────────────────────────────────────────────────────────────\n";
            echo "│ Cell X{$row}\n";
            echo "│ Formula: {$formula}\n";
            echo "│ Result:  {$value}\n";
            echo "└─────────────────────────────────────────────────────────────\n\n";
        } else {
            $value = $cell->getValue();
            if (! empty($value) && $value !== '') {
                echo "[X{$row}] = {$value}";
                if (! empty($labelD) || ! empty($labelE) || ! empty($labelF)) {
                    echo "  | Label: [{$labelD}] [{$labelE}] [{$labelF}]";
                }
                echo "\n";
            }
        }
    }

    echo "\n✅ Selesai!\n";

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
}
