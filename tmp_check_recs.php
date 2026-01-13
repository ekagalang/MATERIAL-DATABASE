<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$recs = App\Models\RecommendedCombination::where('type', 'best')->get()->toArray();
print_r($recs);
