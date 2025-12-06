<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cement;

$cements = Cement::take(5)->get();

echo "CEMENT DATA IN DATABASE:\n";
echo "═══════════════════════════════════════════\n\n";

foreach ($cements as $cement) {
    echo "Cement: " . $cement->cement_name . "\n";
    echo "Brand: " . $cement->brand . "\n";
    echo "Package Weight Net: " . $cement->package_weight_net . " kg\n";
    echo "Package Volume: " . $cement->package_volume . " m³\n";
    echo "─────────────────────────────────────────\n\n";
}
