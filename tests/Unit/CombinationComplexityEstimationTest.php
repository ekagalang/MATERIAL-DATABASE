<?php

use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;
use App\Services\Calculation\MaterialSelectionService;
use App\Services\Calculation\StoreProximityService;
use App\Models\Brick;
use Tests\TestCase;

uses(TestCase::class);

function makeCombinationServiceForComplexityTests(): CombinationGenerationService
{
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    return new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        protected function combinationComplexityMaxEstimated(): int
        {
            return 0;
        }

        public function exposeEstimateCombinationComplexity(
            string $workType,
            array $requiredMaterials,
            iterable $cements,
            iterable $sands,
            iterable $cats,
            iterable $ceramics,
            iterable $nats,
        ): array {
            return $this->estimateCombinationComplexity(
                $workType,
                $requiredMaterials,
                $cements,
                $sands,
                $cats,
                $ceramics,
                $nats,
            );
        }

        public function exposeIterableCardinalityHint(iterable $items): ?int
        {
            return $this->iterableCardinalityHint($items);
        }

        public function exposeConsumeComplexityGuardEvents(): array
        {
            return $this->consumeComplexityGuardEvents();
        }

        public function exposeConsumeComplexityFastModeEvents(): array
        {
            return $this->consumeComplexityFastModeEvents();
        }
    };
}

test('combination complexity estimate computes cartesian size for cement-sand work', function () {
    $service = makeCombinationServiceForComplexityTests();

    $estimate = $service->exposeEstimateCombinationComplexity(
        'brick_half',
        ['brick', 'cement', 'sand'],
        [1, 2, 3],
        [1, 2],
        [],
        [],
        [],
    );

    expect($estimate['mode'])->toBe('cement_sand');
    expect($estimate['estimated_combinations'])->toBe(6);
    expect($estimate['countable'])->toBeTrue();
});

test('combination complexity estimate returns unknown cardinality for non-countable iterables', function () {
    $service = makeCombinationServiceForComplexityTests();

    $cements = (function () {
        yield 1;
        yield 2;
    })();

    $estimate = $service->exposeEstimateCombinationComplexity(
        'brick_half',
        ['brick', 'cement', 'sand'],
        $cements,
        [1, 2],
        [],
        [],
        [],
    );

    expect($estimate['counts']['cement'])->toBeNull();
    expect($estimate['estimated_combinations'])->toBeNull();
    expect($estimate['countable'])->toBeFalse();
});

test('iterable cardinality hint supports arrays and countable collections only', function () {
    $service = makeCombinationServiceForComplexityTests();

    $generator = (function () {
        yield 1;
    })();

    expect($service->exposeIterableCardinalityHint([1, 2, 3]))->toBe(3);
    expect($service->exposeIterableCardinalityHint(collect([1, 2])))->toBe(2);
    expect($service->exposeIterableCardinalityHint($generator))->toBeNull();
});

test('complexity guard events are recorded and can be consumed once', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        protected function combinationComplexityMaxEstimated(): int
        {
            return 2;
        }

        protected function shouldLogCombinationComplexityDebug(): bool
        {
            return false;
        }

        public function exposeConsumeComplexityGuardEvents(): array
        {
            return $this->consumeComplexityGuardEvents();
        }

        public function exposeConsumeComplexityFastModeEvents(): array
        {
            return $this->consumeComplexityFastModeEvents();
        }

        protected function resolveRequiredMaterials(string $workType): array
        {
            return ['brick', 'cement', 'sand'];
        }

        protected function yieldCombinations(
            array $paramsBase,
            Brick $brick,
            string $workType,
            iterable $cements,
            iterable $sands,
            iterable $cats,
            iterable $ceramics,
            iterable $nats,
            string $groupLabel,
        ) {
            throw new RuntimeException('Guard should return before generator is invoked.');
        }
    };

    $brick = new Brick();
    $brick->id = 1;

    $results = $service->calculateCombinationsFromMaterials(
        $brick,
        [
            'work_type' => 'brick_half',
            'wall_length' => 1,
            'wall_height' => 1,
            'mortar_thickness' => 1,
            'installation_type_id' => 1,
            'mortar_formula_id' => 1,
        ],
        [1, 2, 3], // cement
        [1, 2], // sand => estimated 6 > max 2, should guard before formula execution
        [],
        [],
        [],
        'Ekonomis',
        3,
    );

    expect($results)->toBe([]);

    $events = $service->exposeConsumeComplexityGuardEvents();
    expect($events)->toHaveCount(1);
    expect(data_get($events, '0.estimate.estimated_combinations'))->toBe(6);

    // consumed -> empty on next read
    expect($service->exposeConsumeComplexityGuardEvents())->toBe([]);
});

test('complexity guard can downgrade to fast mode and continue with capped materials', function () {
    $repository = new CalculationRepository();
    $selection = new MaterialSelectionService($repository);
    $proximity = new StoreProximityService();

    $service = new class($repository, $selection, $proximity) extends CombinationGenerationService
    {
        public array $captured = [];

        protected function combinationComplexityMaxEstimated(): int
        {
            return 2;
        }

        protected function complexityFastModeEnabled(): bool
        {
            return true;
        }

        protected function complexityFastModeMaterialCap(): int
        {
            return 1;
        }

        protected function shouldLogCombinationComplexityDebug(): bool
        {
            return false;
        }

        protected function topkBufferEnabled(): bool
        {
            return false;
        }

        public function exposeConsumeComplexityGuardEvents(): array
        {
            return $this->consumeComplexityGuardEvents();
        }

        public function exposeConsumeComplexityFastModeEvents(): array
        {
            return $this->consumeComplexityFastModeEvents();
        }

        protected function resolveRequiredMaterials(string $workType): array
        {
            return ['brick', 'cement', 'sand'];
        }

        protected function yieldCombinations(
            array $paramsBase,
            Brick $brick,
            string $workType,
            iterable $cements,
            iterable $sands,
            iterable $cats,
            iterable $ceramics,
            iterable $nats,
            string $groupLabel,
        ) {
            $cementItems = is_array($cements) ? $cements : iterator_to_array($cements, false);
            $sandItems = is_array($sands) ? $sands : iterator_to_array($sands, false);
            $this->captured = [
                'cements_count' => count($cementItems),
                'sands_count' => count($sandItems),
                'cements' => array_values($cementItems),
                'sands' => array_values($sandItems),
            ];

            yield [
                'result' => ['grand_total' => 12345],
                'total_cost' => 12345,
            ];
        }
    };

    $brick = new Brick();
    $brick->id = 1;

    $results = $service->calculateCombinationsFromMaterials(
        $brick,
        [
            'work_type' => 'brick_half',
            'wall_length' => 1,
            'wall_height' => 1,
            'mortar_thickness' => 1,
            'installation_type_id' => 1,
            'mortar_formula_id' => 1,
        ],
        [10, 11, 12],
        [20, 21],
        [],
        [],
        [],
        'Ekonomis',
        3,
    );

    expect($results)->toHaveCount(1);
    expect((float) ($results[0]['total_cost'] ?? 0))->toBe(12345.0);
    expect($service->captured['cements_count'])->toBe(1);
    expect($service->captured['sands_count'])->toBe(1);
    expect($service->captured['cements'])->toBe([10]);
    expect($service->captured['sands'])->toBe([20]);

    // Fast mode should not emit terminal guard event because calculation still proceeds.
    expect($service->exposeConsumeComplexityGuardEvents())->toBe([]);
    $fastModeEvents = $service->exposeConsumeComplexityFastModeEvents();
    expect($fastModeEvents)->toHaveCount(1);
    expect(data_get($fastModeEvents, '0.fast_mode.cap_per_material'))->toBe(1);
});
