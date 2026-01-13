<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\MaterialCalculationController;
use App\Repositories\CalculationRepository;
use App\Models\Brick;

$request = Request::create('/', 'POST', [
    'work_type' => 'brick_half',
    'wall_length' => 10,
    'wall_height' => 3,
    'mortar_thickness' => 1.5,
    'installation_type_id' => 1,
    'mortar_formula_id' => 1,
    'price_filters' => ['best'],
]);

$controller = new MaterialCalculationController(new CalculationRepository());
$brick = Brick::find(1);
$method = new ReflectionMethod($controller, 'getBestCombinations');
$method->setAccessible(true);
$result = $method->invoke($controller, $brick, $request, null);
print_r($result);
