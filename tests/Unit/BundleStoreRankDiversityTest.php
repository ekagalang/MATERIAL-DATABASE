<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;

uses(Tests\TestCase::class);

function makeBundleStoreRankDiversityService(): CombinationGenerationService
{
    $repository = Mockery::mock(CalculationRepository::class);
    $materialSelection = Mockery::mock(MaterialSelectionService::class);
    $storeProximityService = Mockery::mock(StoreProximityService::class);

    return new class($repository, $materialSelection, $storeProximityService) extends CombinationGenerationService
    {
        public function __construct(
            CalculationRepository $repository,
            MaterialSelectionService $materialSelection,
            StoreProximityService $storeProximityService,
        ) {
            parent::__construct($repository, $materialSelection, $storeProximityService);
        }

        public function exposeBuildStoreFilteredResults(
            array $candidates,
            array $requestData,
            array $requiredMaterials,
        ): array {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }
    };
}

function makeStoreRankCandidate(
    int $totalCost,
    int $cementId,
    int $sandId,
    int $storeLocationId,
    string $storeName,
): array {
    return [
        'total_cost' => (float) $totalCost,
        'result' => ['grand_total' => (float) $totalCost],
        'store_label' => $storeName . ' [Varian]',
        'store_plan' => [
            [
                'store_location_id' => $storeLocationId,
                'store_name' => $storeName,
                'city' => 'Kota',
                'distance_km' => 10.0,
            ],
        ],
        'store_coverage_mode' => 'single_store',
        'cement' => (object) ['id' => $cementId, 'brand' => 'C-' . $cementId, 'store' => $storeName],
        'sand' => (object) ['id' => $sandId, 'brand' => 'S-' . $sandId, 'store' => $storeName],
    ];
}

test('bundle one-stop cheapest rank pool keeps alternate complete stores when available', function () {
    $service = makeBundleStoreRankDiversityService();

    $candidates = [
        makeStoreRankCandidate(100, 11, 21, 101, 'Toko A'),
        makeStoreRankCandidate(101, 12, 22, 101, 'Toko A'),
        makeStoreRankCandidate(102, 13, 23, 101, 'Toko A'),
        makeStoreRankCandidate(103, 14, 24, 101, 'Toko A'),
        makeStoreRankCandidate(104, 15, 25, 101, 'Toko A'),
        makeStoreRankCandidate(105, 16, 26, 101, 'Toko A'),
        makeStoreRankCandidate(106, 31, 41, 202, 'Toko B'),
        makeStoreRankCandidate(107, 32, 42, 202, 'Toko B'),
        makeStoreRankCandidate(108, 33, 43, 202, 'Toko B'),
    ];

    $results = $service->exposeBuildStoreFilteredResults(
        $candidates,
        [
            'allow_mixed_store' => false,
            'bundle_variant_mode' => true,
            'bundle_store_variant_limit' => 3,
            'price_filters' => ['cheapest'],
        ],
        ['cement', 'sand'],
    );

    $ekonomis2Rows = is_array($results['Ekonomis 2'] ?? null) ? $results['Ekonomis 2'] : [];
    $labels = array_map(
        static fn($row) => trim((string) ($row['store_label'] ?? '')),
        array_filter($ekonomis2Rows, static fn($row) => is_array($row)),
    );

    expect($ekonomis2Rows)->not->toBeEmpty()
        ->and(collect($labels)->contains(fn($label) => str_contains($label, 'Toko B')))->toBeTrue();
});
