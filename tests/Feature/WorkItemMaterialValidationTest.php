<?php

use App\Models\Brick;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('item pekerjaan calculation skips nats with incomplete pricing data', function () {
    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();
    $service = new CombinationGenerationService($repository, $materialSelection, $storeProximity);

    $brick = Brick::factory()->create();

    $validNatId = DB::table('cements')->insertGetId([
        'cement_name' => 'Nat Valid',
        'type' => 'Nat',
        'brand' => 'Brand A',
        'material_kind' => 'nat',
        'package_unit' => 'Kg',
        'package_weight_net' => 1,
        'package_volume' => 1 / 1440,
        'package_price' => 25000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $invalidNatId = DB::table('cements')->insertGetId([
        'cement_name' => 'Nat Invalid',
        'type' => 'Nat',
        'brand' => 'Brand B',
        'material_kind' => 'nat',
        'package_unit' => 'Kg',
        'package_weight_net' => 1,
        'package_volume' => 1 / 1440,
        'package_price' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = [
        'work_type' => 'grout_tile',
        'wall_length' => 3,
        'wall_height' => 3,
        'mortar_thickness' => 1,
        'installation_type_id' => 1,
        'mortar_formula_id' => 1,
        'grout_thickness' => 3,
        'ceramic_length' => 30,
        'ceramic_width' => 30,
        'ceramic_thickness' => 8,
        'ceramic_price_per_package' => 90000,
        'ceramic_pieces_per_package' => 8,
    ];

    $results = $service->calculateCombinationsFromMaterials(
        $brick,
        $request,
        collect(),
        collect(),
        collect(),
        collect(),
        collect([
            (object) ['id' => $validNatId, 'package_weight_net' => 1, 'package_price' => 25000],
            (object) ['id' => $invalidNatId, 'package_weight_net' => 1, 'package_price' => 0],
        ]),
        'Kombinasi',
    );

    expect($results)->toHaveCount(1)
        ->and((int) ($results[0]['nat']->id ?? 0))->toBe($validNatId);
});
