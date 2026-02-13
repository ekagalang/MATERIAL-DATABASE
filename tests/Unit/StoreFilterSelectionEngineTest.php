<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;

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

