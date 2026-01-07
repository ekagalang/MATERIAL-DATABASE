<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$formulas = \App\Services\FormulaRegistry::all();
echo "Found " . count($formulas) . " formulas:\n";
foreach ($formulas as $f) {
    echo "- " . $f['code'] . ": " . $f['name'] . "\n";
}

if (\App\Services\FormulaRegistry::has('grout_tile')) {
    echo "\nSUCCESS: grout_tile found.\n";
} else {
    echo "\nERROR: grout_tile NOT found.\n";
}

if (\App\Services\FormulaRegistry::has('tile_installation')) {
    echo "SUCCESS: tile_installation found.\n";
} else {
    echo "ERROR: tile_installation NOT found.\n";
}

