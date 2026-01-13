<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cement = App\Models\Cement::find(1);
$sand = App\Models\Sand::find(1);
$brick = App\Models\Brick::find(1);
print_r([
  'cement' => $cement ? $cement->toArray() : null,
  'sand' => $sand ? $sand->toArray() : null,
  'brick' => $brick ? $brick->toArray() : null,
]);
