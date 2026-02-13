<?php

use App\Models\Brick;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

test('store filter requires project coordinates in calculate combinations', function () {
    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService {
        public function getStoreBasedCombinations(Request $request, array $constraints = []): array
        {
            return [
                'Ekonomis 1' => [
                    [
                        'total_cost' => 100000,
                        'filter_label' => 'Ekonomis 1',
                    ],
                ],
            ];
        }
    };

    $request = new Request([
        'work_type' => 'brick_half',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        // project_latitude & project_longitude intentionally omitted
    ]);

    $result = $service->calculateCombinations($request, [
        'brick' => new Brick(),
    ]);

    expect($result)->toBe([]);
});

test('store filter does not fallback to global combinations when mixed store is enabled', function () {
    $repository = new CalculationRepository();
    $materialSelection = new MaterialSelectionService($repository);
    $storeProximity = new StoreProximityService();

    $service = new class($repository, $materialSelection, $storeProximity) extends CombinationGenerationService {
        public function getStoreBasedCombinations(Request $request, array $constraints = []): array
        {
            return [];
        }

        public function getBestCombinations(Brick $brick, array $request): array
        {
            return [
                [
                    'total_cost' => 123456,
                    'filter_label' => 'Preferensi 1',
                ],
            ];
        }
    };

    $request = new Request([
        'work_type' => 'brick_half',
        'use_store_filter' => 1,
        'allow_mixed_store' => 1,
        'project_latitude' => -6.2,
        'project_longitude' => 106.8,
    ]);

    $result = $service->calculateCombinations($request, [
        'brick' => new Brick(),
    ]);

    expect($result)->toBe([]);
});
