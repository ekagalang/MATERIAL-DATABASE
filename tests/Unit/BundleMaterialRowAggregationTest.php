<?php

use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;

function makeBundleCementModel(
    string $type,
    string $brand,
    string $store,
    string $address,
    float $packagePrice = 60000,
): object {
    return (object) [
        'type' => $type,
        'brand' => $brand,
        'store' => $store,
        'address' => $address,
        'color' => 'Abu',
        'package_unit' => 'Sak',
        'package_weight_net' => 50,
        'package_price' => $packagePrice,
    ];
}

test('bundle material rows keep different material variants separated', function () {
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    $controller = new class($repo, $service) extends MaterialCalculationExecutionController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeBuildBundleMaterialRows(array $combinations): array
        {
            return $this->buildBundleMaterialRows($combinations);
        }
    };

    $combinations = [
        [
            'result' => [
                'cement_sak' => 1,
                'total_cement_price' => 60000,
                'cement_price_per_sak' => 60000,
            ],
            'cement' => makeBundleCementModel('PCC', 'A', 'Toko A', 'Alamat A', 60000),
        ],
        [
            'result' => [
                'cement_sak' => 2,
                'total_cement_price' => 130000,
                'cement_price_per_sak' => 65000,
            ],
            'cement' => makeBundleCementModel('PCC', 'B', 'Toko B', 'Alamat B', 65000),
        ],
    ];

    $rows = $controller->exposeBuildBundleMaterialRows($combinations);
    $cementRows = array_values(
        array_filter($rows, static fn($row) => ($row['material_key'] ?? null) === 'cement'),
    );

    expect($cementRows)->toHaveCount(2);
});

test('bundle material rows merge only when material attributes are identical', function () {
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    $controller = new class($repo, $service) extends MaterialCalculationExecutionController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeBuildBundleMaterialRows(array $combinations): array
        {
            return $this->buildBundleMaterialRows($combinations);
        }
    };

    $sameModelA = makeBundleCementModel('PCC', 'A', 'Toko A', 'Alamat A', 60000);
    $sameModelB = makeBundleCementModel('PCC', 'A', 'Toko A', 'Alamat A', 60000);

    $combinations = [
        [
            'result' => [
                'cement_sak' => 1,
                'total_cement_price' => 60000,
                'cement_price_per_sak' => 60000,
            ],
            'cement' => $sameModelA,
        ],
        [
            'result' => [
                'cement_sak' => 2,
                'total_cement_price' => 120000,
                'cement_price_per_sak' => 60000,
            ],
            'cement' => $sameModelB,
        ],
    ];

    $rows = $controller->exposeBuildBundleMaterialRows($combinations);
    $cementRows = array_values(
        array_filter($rows, static fn($row) => ($row['material_key'] ?? null) === 'cement'),
    );

    expect($cementRows)->toHaveCount(1)
        ->and((float) $cementRows[0]['qty'])->toBe(3.0)
        ->and((float) $cementRows[0]['total_price'])->toBe(180000.0);
});
