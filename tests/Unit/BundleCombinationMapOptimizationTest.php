<?php

use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;

function makeBundleControllerForOptimizationTests(): MaterialCalculationExecutionController
{
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    return new class($repo, $service) extends MaterialCalculationExecutionController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeExtractBestCombinationMapForPayload(array $payload, ?array $allowedLabels = null): array
        {
            return $this->extractBestCombinationMapForPayload($payload, $allowedLabels);
        }

        public function exposeMinimizeBundleItemRequestDataForAggregation(array $requestData): array
        {
            return $this->minimizeBundleItemRequestDataForAggregation($requestData);
        }
    };
}

test('bundle combination map extraction can limit labels and keeps cheapest candidate per label', function () {
    $controller = makeBundleControllerForOptimizationTests();

    $payload = [
        'projects' => [
            [
                'combinations' => [
                    'Ekonomis 1' => [
                        [
                            'result' => ['grand_total' => 150000, 'total_cement_price' => 50000],
                            'cement' => (object) ['id' => 10],
                            'debug_heavy' => ['x' => str_repeat('a', 1000)],
                        ],
                        [
                            'result' => ['grand_total' => 130000, 'total_cement_price' => 45000],
                            'cement' => (object) ['id' => 11],
                            'debug_heavy' => ['x' => str_repeat('b', 1000)],
                        ],
                    ],
                    'Termahal 1' => [
                        [
                            'result' => ['grand_total' => 300000, 'total_cement_price' => 90000],
                            'cement' => (object) ['id' => 20],
                            'debug_heavy' => ['x' => str_repeat('c', 1000)],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $map = $controller->exposeExtractBestCombinationMapForPayload($payload, [
        'Ekonomis 1' => true,
    ]);

    expect(array_keys($map))->toBe(['Ekonomis 1']);
    expect((float) ($map['Ekonomis 1']['result']['grand_total'] ?? 0))->toBe(130000.0);
    expect(($map['Ekonomis 1']['cement']->id ?? null))->toBe(11);
    expect(array_key_exists('debug_heavy', $map['Ekonomis 1']))->toBeFalse();
});

test('bundle combination map extraction maps composite labels to allowed alias labels', function () {
    $controller = makeBundleControllerForOptimizationTests();

    $payload = [
        'projects' => [
            [
                'combinations' => [
                    'Preferensi 1 = Ekonomis 1 = Average 1' => [
                        [
                            'result' => ['grand_total' => 210000],
                            'cement' => (object) ['id' => 30],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $map = $controller->exposeExtractBestCombinationMapForPayload($payload, [
        'Preferensi 1' => true,
        'Ekonomis 1' => true,
        'Average 1' => true,
    ]);

    expect(array_keys($map))->toBe(['Preferensi 1', 'Ekonomis 1', 'Average 1']);
    expect((float) ($map['Preferensi 1']['result']['grand_total'] ?? 0))->toBe(210000.0);
    expect((float) ($map['Ekonomis 1']['result']['grand_total'] ?? 0))->toBe(210000.0);
    expect((float) ($map['Average 1']['result']['grand_total'] ?? 0))->toBe(210000.0);
});

test('bundle item request data minimizer keeps only aggregation relevant keys', function () {
    $controller = makeBundleControllerForOptimizationTests();

    $input = [
        'work_type' => 'brick_half',
        'row_kind' => 'item',
        'work_floor' => 'Lantai 1',
        'work_floors' => ['Lantai 1'],
        'wall_length' => '10',
        'wall_height' => '3',
        'material_type_filters' => ['cement' => ['PCC']],
        'material_customize_filters' => ['cement' => ['brand' => 'X']],
        'csrf_token_like' => 'abc',
        'nested_unsupported' => ['foo' => 'bar'],
    ];

    $minimal = $controller->exposeMinimizeBundleItemRequestDataForAggregation($input);

    expect($minimal)->toHaveKeys(['work_type', 'row_kind', 'work_floor', 'work_floors', 'wall_length', 'wall_height']);
    expect(array_key_exists('material_type_filters', $minimal))->toBeFalse();
    expect(array_key_exists('material_customize_filters', $minimal))->toBeFalse();
    expect(array_key_exists('csrf_token_like', $minimal))->toBeFalse();
    expect(array_key_exists('nested_unsupported', $minimal))->toBeFalse();
});
