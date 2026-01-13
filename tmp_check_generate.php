<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\MaterialCalculationController;
use App\Repositories\CalculationRepository;

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
$method = new ReflectionMethod($controller, 'generateCombinations');
$method->setAccessible(true);
$response = $method->invoke($controller, $request);
// The method returns a View; get data
$data = $response->getData();
print_r(array_keys((array)$data));
print_r($data['projects']);
