<?php

use App\Models\Brick;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

test('store filter engine does not emit preferensi labels when admin best recommendation is missing', function () {
    $repository = Mockery::mock(CalculationRepository::class);
    $repository->shouldReceive('getRecommendedCombinations')
        ->once()
        ->with('tile_installation')
        ->andReturn(new EloquentCollection());

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
        makeStoreCandidate(120000, 1, 10, 100, 'Store A', [
            'ceramic' => (object) ['id' => 200],
            'nat' => (object) ['id' => 300],
        ]),
    ];

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'tile_installation',
        'price_filters' => ['best'],
    ], ['cement', 'sand', 'ceramic', 'nat']);

    expect($result)->toBe([]);
});

test('getBestCombinations returns empty for brickless work when admin best recommendation is missing', function () {
    $repository = Mockery::mock(CalculationRepository::class);
    $repository->shouldReceive('getRecommendedCombinations')
        ->once()
        ->with('tile_installation')
        ->andReturn(new EloquentCollection());

    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeGetBestCombinations(Brick $brick, array $request): array
        {
            return $this->getBestCombinations($brick, $request);
        }
    };

    $brick = new Brick();
    $brick->id = 1;

    $result = $service->exposeGetBestCombinations($brick, [
        'work_type' => 'tile_installation',
    ]);

    expect($result)->toBe([]);
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

test('store filter engine keeps preferensi via closest recommendation match when strict id match is unavailable', function () {
    $repository = Mockery::mock(CalculationRepository::class);
    $repository->shouldReceive('getRecommendedCombinations')
        ->once()
        ->with('brick_half')
        ->andReturn(new EloquentCollection([
            (object) [
                'type' => 'best',
                'brick_id' => 99,
                'cement_id' => 10,
                'sand_id' => 100,
            ],
        ]));

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
        makeStoreCandidate(120000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(121000, 2, 11, 101, 'Store B'),
    ];

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['best'],
    ], ['brick', 'cement', 'sand']);

    expect(array_keys($result))->toContain('Preferensi 1')
        ->and((int) ($result['Preferensi 1'][0]['cement']->id ?? 0))->toBe(10)
        ->and((int) ($result['Preferensi 1'][0]['sand']->id ?? 0))->toBe(100);
});

test('store filter engine keeps preferensi via cheapest fallback when recommendation exists but no ids overlap', function () {
    $repository = Mockery::mock(CalculationRepository::class);
    $repository->shouldReceive('getRecommendedCombinations')
        ->once()
        ->with('brick_half')
        ->andReturn(new EloquentCollection([
            (object) [
                'type' => 'best',
                'brick_id' => 999,
                'cement_id' => 999,
                'sand_id' => 999,
            ],
        ]));

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
        makeStoreCandidate(121000, 2, 11, 101, 'Store B'),
        makeStoreCandidate(120000, 1, 10, 100, 'Store A'),
    ];

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['best'],
    ], ['brick', 'cement', 'sand']);

    expect(array_keys($result))->toContain('Preferensi 1')
        ->and((float) ($result['Preferensi 1'][0]['total_cost'] ?? 0))->toBe(120000.0)
        ->and((int) ($result['Preferensi 1'][0]['cement']->id ?? 0))->toBe(10)
        ->and((int) ($result['Preferensi 1'][0]['sand']->id ?? 0))->toBe(100);
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

test('store filter engine bundle variant mode keeps extra candidate pool per rank label', function () {
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
        makeStoreCandidate(100000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(110000, 2, 11, 101, 'Store A'),
        makeStoreCandidate(120000, 3, 12, 102, 'Store A'),
        makeStoreCandidate(130000, 4, 13, 103, 'Store A'),
        makeStoreCandidate(140000, 5, 14, 104, 'Store A'),
        makeStoreCandidate(150000, 6, 15, 105, 'Store A'),
    ];

    config()->set('materials.topk_buffer_enabled', false);
    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest'],
        'bundle_variant_mode' => true,
        'bundle_store_variant_limit' => 6,
    ], ['brick', 'cement', 'sand']);

    expect(isset($result['Ekonomis 1']))->toBeTrue();
    expect(count($result['Ekonomis 1']))->toBeGreaterThan(1);
    expect(isset($result['Ekonomis 2']))->toBeTrue();
    expect(count($result['Ekonomis 2']))->toBeGreaterThan(1);
});

test('store filter engine bundle variant mode is not capped by topk capacity 3', function () {
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
        makeStoreCandidate(100000, 1, 10, 100, 'Store A'),
        makeStoreCandidate(110000, 2, 11, 101, 'Store B'),
        makeStoreCandidate(120000, 3, 12, 102, 'Store C'),
        makeStoreCandidate(130000, 4, 13, 103, 'Store D'),
        makeStoreCandidate(140000, 5, 14, 104, 'Store E'),
        makeStoreCandidate(150000, 6, 15, 105, 'Store F'),
    ];

    config()->set('materials.topk_buffer_enabled', true);
    config()->set('materials.topk_buffer_filters', ['cheapest', 'medium', 'expensive']);
    config()->set('materials.topk_capacity_per_label', 3);

    $result = $service->exposeBuildStoreFilteredResults($candidates, [
        'work_type' => 'brick_half',
        'price_filters' => ['cheapest'],
        'bundle_variant_mode' => true,
        'bundle_store_variant_limit' => 6,
    ], ['brick', 'cement', 'sand']);

    expect(isset($result['Ekonomis 1']))->toBeTrue();
    expect(count($result['Ekonomis 1']))->toBeGreaterThan(3);

    config()->set('materials.topk_buffer_enabled', false);
});
