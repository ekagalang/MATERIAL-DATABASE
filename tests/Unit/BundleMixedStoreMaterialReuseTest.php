<?php

use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;

uses(Tests\TestCase::class);

function makeMixedModeCement(int $id, string $brand): object
{
    return (object) [
        'id' => $id,
        'type' => 'PCC',
        'brand' => $brand,
        'store' => 'Gabungan Toko Terdekat',
        'address' => '-',
        'color' => 'Abu',
        'package_unit' => 'Sak',
        'package_weight_net' => 50,
        'package_price' => 70000,
    ];
}

function makeMixedModeSand(int $id, string $brand): object
{
    return (object) [
        'id' => $id,
        'type' => 'Pasang',
        'brand' => $brand,
        'store' => 'Gabungan Toko Terdekat',
        'address' => '-',
        'package_unit' => 'Karung',
        'package_volume' => 0.02,
        'package_price' => 65000,
        'comparison_price_per_m3' => 3250000,
    ];
}

function makeMixedModeCandidate(int $grandTotal, int $cementId, int $sandId): array
{
    return [
        'store_label' => 'Gabungan Toko Terdekat',
        'store_coverage_mode' => 'nearest_store_chain',
        'store_plan' => [
            [
                'store_location_id' => 2,
                'store_name' => 'Toko A',
                'city' => 'Kota A',
                'distance_km' => 40.0,
                'provided_materials' => ['cement'],
            ],
            [
                'store_location_id' => 3,
                'store_name' => 'Toko B',
                'city' => 'Kota B',
                'distance_km' => 41.0,
                'provided_materials' => ['sand'],
            ],
        ],
        'cement' => makeMixedModeCement($cementId, 'C-' . $cementId),
        'sand' => makeMixedModeSand($sandId, 'S-' . $sandId),
        'result' => [
            'grand_total' => $grandTotal,
            'cement_sak' => 1.0,
            'total_cement_price' => 70000.0,
            'cement_price_per_sak' => 70000.0,
            'sand_m3' => 1.0,
            'total_sand_price' => 65000.0,
            'sand_price_per_m3' => 65000.0,
        ],
        'total_cost' => (float) $grandTotal,
    ];
}

test('mixed-store bundle prefers reusable shared materials when consistent candidates exist', function () {
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    $controller = new class($repo, $service) extends MaterialCalculationExecutionController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeBuildBundleSummaryCombinations(
            array $bundleItemPayloads,
            array $priceFilters,
            array $bundleOptions = [],
        ): array {
            return $this->buildBundleSummaryCombinations($bundleItemPayloads, $priceFilters, $bundleOptions);
        }
    };

    $itemPayloads = [
        [
            'title' => 'Item 1',
            'work_type' => 'wall_plastering',
            'requestData' => ['work_type' => 'wall_plastering'],
            'projects' => [
                [
                    'combinations' => [
                        'Ekonomis 1' => [
                            makeMixedModeCandidate(100, 11, 21),
                            makeMixedModeCandidate(105, 12, 22),
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Item 2',
            'work_type' => 'wall_plastering',
            'requestData' => ['work_type' => 'wall_plastering'],
            'projects' => [
                [
                    'combinations' => [
                        'Ekonomis 1' => [
                            makeMixedModeCandidate(90, 13, 23),
                            makeMixedModeCandidate(106, 12, 22),
                        ],
                    ],
                ],
            ],
        ],
    ];

    $bundleCombinations = $controller->exposeBuildBundleSummaryCombinations(
        $itemPayloads,
        ['cheapest'],
        [
            'use_store_filter' => true,
            'allow_mixed_store' => true,
        ],
    );

    $selectedRow = $bundleCombinations['Ekonomis 1'][0] ?? null;
    expect($selectedRow)->toBeArray();

    $breakdowns = is_array($selectedRow['bundle_item_material_breakdowns'] ?? null)
        ? $selectedRow['bundle_item_material_breakdowns']
        : [];
    expect($breakdowns)->toHaveCount(2);

    $cementIds = [];
    $sandIds = [];
    foreach ($breakdowns as $breakdown) {
        $materials = is_array($breakdown['materials'] ?? null) ? $breakdown['materials'] : [];
        foreach ($materials as $materialRow) {
            if (!is_array($materialRow)) {
                continue;
            }
            $materialKey = (string) ($materialRow['material_key'] ?? '');
            $object = $materialRow['object'] ?? null;
            $id = isset($object->id) ? (int) $object->id : 0;
            if ($id <= 0) {
                continue;
            }
            if ($materialKey === 'cement') {
                $cementIds[] = $id;
            }
            if ($materialKey === 'sand') {
                $sandIds[] = $id;
            }
        }
    }

    expect(array_values(array_unique($cementIds)))->toBe([12])
        ->and(array_values(array_unique($sandIds)))->toBe([22]);
});
