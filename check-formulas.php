<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════╗\n";
echo "║         FORMULA vs INSTALLATION TYPE CHECK        ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

// Check FormulaRegistry
$formulas = \App\Services\FormulaRegistry::all();
echo "📋 FORMULAS (from FormulaRegistry):\n";
echo "Total: " . count($formulas) . " formulas\n\n";

foreach ($formulas as $index => $formula) {
    echo ($index + 1) . ". {$formula['name']}\n";
    echo "   Code: {$formula['code']}\n";
    echo "   Description: {$formula['description']}\n";
    echo "\n";
}

// Check Installation Types
echo "\n🏗️ INSTALLATION TYPES (from Database):\n";
$installationTypes = \App\Models\BrickInstallationType::orderBy('display_order')->get();
echo "Total: " . $installationTypes->count() . " types\n\n";

foreach ($installationTypes as $index => $type) {
    echo ($index + 1) . ". {$type->name}\n";
    echo "   Code: {$type->code}\n";
    echo "   Active: " . ($type->is_active ? 'Yes' : 'No') . "\n";
    echo "\n";
}

// Analysis
echo "\n📊 ANALYSIS:\n";
echo "════════════════════════════════════════\n\n";

echo "Brick Installation Formulas:\n";
$brickFormulas = array_filter($formulas, fn($f) => str_starts_with($f['code'], 'brick_'));
foreach ($brickFormulas as $f) {
    echo "  ✓ {$f['name']} ({$f['code']})\n";
}

echo "\nBrickless Formulas (No Installation Type):\n";
$bricklessFormulas = array_filter($formulas, fn($f) => !str_starts_with($f['code'], 'brick_'));
foreach ($bricklessFormulas as $f) {
    echo "  ✓ {$f['name']} ({$f['code']})\n";
}

echo "\n\n💡 EXPLANATION:\n";
echo "════════════════════════════════════════\n";
echo "• Installation Types = Ways to install BRICKS (4 types)\n";
echo "• Formulas = ALL calculation methods (6 total)\n";
echo "  - 4 brick formulas (use installation_type_id)\n";
echo "  - 2 brickless formulas (plaster & skim coating - NO installation_type_id)\n";
echo "\n";
echo "Brickless formulas don't need installation types because\n";
echo "they don't install bricks - they just apply mortar to walls.\n";
echo "\n";
