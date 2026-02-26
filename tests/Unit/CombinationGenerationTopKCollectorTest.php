<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;

function makeCombinationServiceForTopKCollectorTests(): CombinationGenerationService
{
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    return new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        protected function topkBufferCapacity(): int
        {
            return 3;
        }

        public function exposeCollectTopKGeneratedCombinations(iterable $generator, int $limit, bool $sortDesc): array
        {
            return $this->collectTopKGeneratedCombinations($generator, $limit, $sortDesc);
        }
    };
}

test('generator topk collector keeps cheapest items in ascending order', function () {
    $service = makeCombinationServiceForTopKCollectorTests();

    $generator = (function () {
        yield ['total_cost' => 500];
        yield ['total_cost' => 100];
        yield ['total_cost' => 400];
        yield ['total_cost' => 200];
        yield ['total_cost' => 300];
    })();

    $result = $service->exposeCollectTopKGeneratedCombinations($generator, 3, false);

    expect(array_column($result['items'], 'total_cost'))->toBe([100, 200, 300]);
    expect($result['stats']['evaluated'])->toBe(5);
    expect($result['stats']['selected'])->toBe(3);
});

test('generator topk collector keeps most expensive items in descending order', function () {
    $service = makeCombinationServiceForTopKCollectorTests();

    $generator = (function () {
        yield ['total_cost' => 500];
        yield ['total_cost' => 100];
        yield ['total_cost' => 400];
        yield ['total_cost' => 200];
        yield ['total_cost' => 300];
    })();

    $result = $service->exposeCollectTopKGeneratedCombinations($generator, 3, true);

    expect(array_column($result['items'], 'total_cost'))->toBe([500, 400, 300]);
    expect($result['stats']['evaluated'])->toBe(5);
    expect($result['stats']['selected'])->toBe(3);
});

