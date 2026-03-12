<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use Tests\TestCase;

uses(TestCase::class);

function makeCombinationServiceForMaterialCustomizeIterableTests(): CombinationGenerationService
{
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    return new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public function exposeFilterIterableByMaterialCustomize(
            iterable $items,
            string $materialKey,
            array $materialCustomizeFilters,
        ): iterable {
            return $this->filterIterableByMaterialCustomize($items, $materialKey, $materialCustomizeFilters);
        }
    };
}

test('material customize filter returns rewindable iterable for repeated traversal', function () {
    $service = makeCombinationServiceForMaterialCustomizeIterableTests();
    $source = (function () {
        foreach (['A', 'B', 'A'] as $brand) {
            yield (object) ['brand' => $brand];
        }
    })();

    $filtered = $service->exposeFilterIterableByMaterialCustomize(
        $source,
        'cement',
        ['cement' => ['brand' => 'A']],
    );

    $toList = static function (iterable $items): array {
        return is_array($items) ? array_values($items) : iterator_to_array($items, false);
    };

    $firstPass = $toList($filtered);
    $secondPass = $toList($filtered);

    expect($filtered)->toBeArray();
    expect($firstPass)->toHaveCount(2);
    expect($secondPass)->toHaveCount(2);
});

