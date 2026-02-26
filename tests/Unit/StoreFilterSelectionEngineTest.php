<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Tests\TestCase;

uses(TestCase::class);

function makeStoreCandidate(
    float $totalCost,
    int $brickId,
    int $cementId,
    int $sandId,
    string $storeLabel,
    array $extra = [],
): array {
    return $extra + [
        'total_cost' => $totalCost,
        'brick' => (object) ['id' => $brickId],
        'cement' => (object) ['id' => $cementId],
        'sand' => (object) ['id' => $sandId],
        'cat' => null,
        'ceramic' => null,
        'nat' => null,
        'store_label' => $storeLabel,
        'store_coverage_mode' => 'single',
        'store_plan' => [
            ['store_id' => crc32($storeLabel) % 10000, 'materials' => ['brick', 'cement', 'sand']],
        ],
    ];
}

test('store filter engine maps selected price filters to matching labels only', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeBuildStoreFilteredResults(array $candidates, array $requestData, array $requiredMaterials): array
        {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }
    };

    $candidates = [
        ['total_cost' => 100000],
        ['total_cost' => 150000],
        ['total_cost' => 220000],
    ];

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest', 'medium', 'expensive'],
    ], ['brick', 'cement', 'sand']);

    expect(array_keys($result))
        ->toContain('Ekonomis 1')
        ->toContain('Average 1')
        ->toContain('Termahal 1')
        ->not->toContain('Preferensi 1')
        ->not->toContain('Populer 1');
});

test('store filter engine can emit preferensi and populer labels when those filters are requested', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeBuildStoreFilteredResults(array $candidates, array $requestData, array $requiredMaterials): array
        {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }

        protected function selectStorePreferensiCandidates(
            array $candidates,
            array $requestData,
            array $requiredMaterials,
            int $limit = 3,
        ): array {
            return array_slice($candidates, 0, min($limit, count($candidates)));
        }

        protected function selectStorePopularCandidates(
            array $candidates,
            array $requestData,
            array $requiredMaterials,
            int $limit = 3,
        ): array {
            return array_slice(array_reverse($candidates), 0, min($limit, count($candidates)));
        }
    };

    $candidates = [
        ['total_cost' => 120000],
        ['total_cost' => 180000],
    ];

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['best', 'common'],
    ], ['brick', 'cement', 'sand']);

    expect(array_keys($result))
        ->toContain('Preferensi 1')
        ->toContain('Populer 1')
        ->not->toContain('Ekonomis 1');
});

test('store filter engine topk mode keeps cost-based labels parity with legacy selectors', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeBuildStoreFilteredResults(array $candidates, array $requestData, array $requiredMaterials): array
        {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }
    };

    $candidates = [
        makeStoreCandidate(210000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(190000, 2, 10, 100, 'Store B'),
        makeStoreCandidate(250000, 3, 11, 101, 'Store C'),
        makeStoreCandidate(170000, 4, 12, 102, 'Store D'),
        makeStoreCandidate(230000, 5, 13, 103, 'Store E'),
        makeStoreCandidate(260000, 6, 14, 104, 'Store F'),
    ];

    config()->set('materials.topk_buffer_enabled', false);
    $legacy = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest', 'medium', 'expensive'],
    ], ['brick', 'cement', 'sand']);

    config()->set('materials.topk_buffer_enabled', true);
    config()->set('materials.topk_buffer_filters', ['cheapest', 'medium', 'expensive']);
    config()->set('materials.topk_capacity_per_label', 3);
    $topk = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest', 'medium', 'expensive'],
    ], ['brick', 'cement', 'sand']);

    $labels = [
        'Ekonomis 1', 'Ekonomis 2', 'Ekonomis 3',
        'Average 1', 'Average 2', 'Average 3',
        'Termahal 1', 'Termahal 2', 'Termahal 3',
    ];

    foreach ($labels as $label) {
        expect(isset($legacy[$label]))->toBeTrue("Legacy missing label {$label}");
        expect(isset($topk[$label]))->toBeTrue("TopK missing label {$label}");

        $legacyCost = (float) (($legacy[$label][0]['total_cost'] ?? 0));
        $topkCost = (float) (($topk[$label][0]['total_cost'] ?? 0));
        expect($topkCost)->toBe($legacyCost, "Cost mismatch for {$label}");
    }

    config()->set('materials.topk_buffer_enabled', false);
});

test('store filter engine topk mode dedupes duplicate store-material signatures', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeBuildStoreFilteredResults(array $candidates, array $requestData, array $requiredMaterials): array
        {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }
    };

    $candidates = [
        makeStoreCandidate(170000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(180000, 2, 11, 101, 'Store B'),
        makeStoreCandidate(190000, 3, 12, 102, 'Store C'),
        // same composition + same store signature as Store B, but worse cost
        makeStoreCandidate(185000, 2, 11, 101, 'Store B'),
        makeStoreCandidate(220000, 4, 13, 103, 'Store D'),
    ];

    config()->set('materials.topk_buffer_enabled', true);
    config()->set('materials.topk_buffer_filters', ['cheapest']);
    config()->set('materials.topk_capacity_per_label', 3);

    $topk = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest'],
    ], ['brick', 'cement', 'sand']);

    $economicalCosts = array_map(
        static fn(array $group) => (float) ($group[0]['total_cost'] ?? 0),
        array_values(array_filter($topk, static fn($v, $k) => str_starts_with($k, 'Ekonomis '), ARRAY_FILTER_USE_BOTH)),
    );

    expect($economicalCosts)->toBe([170000.0, 180000.0, 190000.0]);

    config()->set('materials.topk_buffer_enabled', false);
});

test('store filter engine all filter expands and remains compatible with topk mode', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeBuildStoreFilteredResults(array $candidates, array $requestData, array $requiredMaterials): array
        {
            return $this->buildStoreFilteredResults($candidates, $requestData, $requiredMaterials);
        }

        protected function selectStorePreferensiCandidates(
            array $candidates,
            array $requestData,
            array $requiredMaterials,
            int $limit = 3,
        ): array {
            return array_slice($candidates, 0, min($limit, count($candidates)));
        }

        protected function selectStorePopularCandidates(
            array $candidates,
            array $requestData,
            array $requiredMaterials,
            int $limit = 3,
        ): array {
            return array_slice(array_reverse($candidates), 0, min($limit, count($candidates)));
        }
    };

    $candidates = [
        makeStoreCandidate(110000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(140000, 2, 11, 101, 'Store B'),
        makeStoreCandidate(170000, 3, 12, 102, 'Store C'),
        makeStoreCandidate(200000, 4, 13, 103, 'Store D'),
        makeStoreCandidate(230000, 5, 14, 104, 'Store E'),
    ];

    config()->set('materials.topk_buffer_enabled', true);
    config()->set('materials.topk_buffer_filters', ['cheapest', 'medium', 'expensive']);

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['all'],
    ], ['brick', 'cement', 'sand']);

    expect(array_keys($result))
        ->toContain('Preferensi 1')
        ->toContain('Populer 1')
        ->toContain('Ekonomis 1')
        ->toContain('Average 1')
        ->toContain('Termahal 1');

    config()->set('materials.topk_buffer_enabled', false);
});
