<?php

use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;

uses(Tests\TestCase::class);

function makeBundleControllerForStoreLockTests(): MaterialCalculationExecutionController
{
    $repo = Mockery::mock(CalculationRepository::class);
    $service = Mockery::mock(CombinationGenerationService::class);

    return new class($repo, $service) extends MaterialCalculationExecutionController
    {
        public function __construct(CalculationRepository $repo, CombinationGenerationService $service)
        {
            parent::__construct($repo, $service);
        }

        public function exposeSelectBundleStoreCandidatesWithMaterialReuse(
            array $pendingStoreConstrainedItems,
            string $storeKey,
            array $excludedSelectionSignatures = [],
        ): ?array {
            return $this->selectBundleStoreCandidatesWithMaterialReuse(
                $pendingStoreConstrainedItems,
                $storeKey,
                $excludedSelectionSignatures,
            );
        }

        public function exposeEvaluateBundleMaterialReuseMetrics(array $selectedCandidates): array
        {
            return $this->evaluateBundleMaterialReuseMetrics($selectedCandidates);
        }

        public function exposeBuildBundleSummaryCombinations(
            array $bundleItemPayloads,
            array $priceFilters,
            array $bundleOptions = [],
        ): array {
            return $this->buildBundleSummaryCombinations($bundleItemPayloads, $priceFilters, $bundleOptions);
        }

    };
}

function bundleStoreLockCandidate(
    int $grandTotal,
    ?int $cementId = null,
    ?int $sandId = null,
    int $storeLocationId = 101,
    string $storeName = 'Toko A',
): array
{
    return [
        'result' => ['grand_total' => $grandTotal],
        'store_label' => $storeName,
        'store_plan' => [
            ['store_location_id' => $storeLocationId, 'store_name' => $storeName],
        ],
        'cement' => $cementId ? (object) ['id' => $cementId, 'brand' => 'C-' . $cementId, 'store' => $storeName] : null,
        'sand' => $sandId ? (object) ['id' => $sandId, 'brand' => 'S-' . $sandId, 'store' => $storeName] : null,
    ];
}

test('single-store bundle selection locks shared materials across items when consistent option exists', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $pendingStoreConstrainedItems = [
        [
            'bundle_item_payload' => ['title' => 'Item 1'],
            'index' => 0,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(100, 11, 21),
                    bundleStoreLockCandidate(101, 12, 22),
                ],
            ],
        ],
        [
            'bundle_item_payload' => ['title' => 'Item 2'],
            'index' => 1,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(100, 11, 21),
                    bundleStoreLockCandidate(101, 12, 22),
                ],
            ],
        ],
        [
            'bundle_item_payload' => ['title' => 'Item 3'],
            'index' => 2,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(1, 13, 23),
                    bundleStoreLockCandidate(200, 12, 22),
                ],
            ],
        ],
    ];

    $selection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
    );

    expect($selection)->not->toBeNull();
    $selected = is_array($selection['selected_candidates'] ?? null) ? $selection['selected_candidates'] : [];
    expect($selected)->toHaveCount(3);

    $metrics = $controller->exposeEvaluateBundleMaterialReuseMetrics($selected);
    expect((int) ($metrics['mismatch_count'] ?? -1))->toBe(0)
        ->and((int) ($metrics['variant_excess'] ?? -1))->toBe(0);

    $cementIds = array_map(static fn($row) => (int) (($row['cement']->id ?? 0)), $selected);
    expect(array_values(array_unique($cementIds)))->toBe([12]);
});

test('single-store bundle selection rejects mixed shared materials when no strict lock is possible', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $pendingStoreConstrainedItems = [
        [
            'bundle_item_payload' => ['title' => 'Item 1'],
            'index' => 0,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(100, 11, 21),
                    bundleStoreLockCandidate(120, 12, 22),
                ],
            ],
        ],
        [
            'bundle_item_payload' => ['title' => 'Item 2'],
            'index' => 1,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(90, 13, 23),
                    bundleStoreLockCandidate(95, 14, 24),
                ],
            ],
        ],
    ];

    $selection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
    );

    expect($selection)->toBeNull();
});

test('single-store bundle selection can pick next strict variant when previous signature is excluded', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $pendingStoreConstrainedItems = [
        [
            'bundle_item_payload' => ['title' => 'Item 1'],
            'index' => 0,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(100, 11, 21),
                    bundleStoreLockCandidate(130, 12, 22),
                ],
            ],
        ],
        [
            'bundle_item_payload' => ['title' => 'Item 2'],
            'index' => 1,
            'options_by_store_key' => [
                'store_location:101' => [
                    bundleStoreLockCandidate(110, 11, 21),
                    bundleStoreLockCandidate(140, 12, 22),
                ],
            ],
        ],
    ];

    $firstSelection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
    );
    expect($firstSelection)->not->toBeNull();
    $firstSignature = trim((string) ($firstSelection['selection_signature'] ?? ''));
    expect($firstSignature)->not->toBe('');

    $secondSelection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
        [$firstSignature],
    );
    expect($secondSelection)->not->toBeNull();
    $secondSignature = trim((string) ($secondSelection['selection_signature'] ?? ''));
    expect($secondSignature)->not->toBe('')
        ->and($secondSignature)->not->toBe($firstSignature);
});

test('single-store bundle selection can pick next variant with same shared materials', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $baseItem1 = bundleStoreLockCandidate(100, 11, 21);
    $baseItem2 = bundleStoreLockCandidate(80, 11, 21);

    $item1VariantA = array_merge($baseItem1, ['cat' => (object) ['id' => 31, 'brand' => 'Cat-31', 'store' => 'Toko A']]);
    $item1VariantB = array_merge($baseItem1, ['result' => ['grand_total' => 110], 'cat' => (object) ['id' => 32, 'brand' => 'Cat-32', 'store' => 'Toko A']]);

    $pendingStoreConstrainedItems = [
        [
            'bundle_item_payload' => ['title' => 'Item 1'],
            'index' => 0,
            'options_by_store_key' => [
                'store_location:101' => [
                    $item1VariantA,
                    $item1VariantB,
                ],
            ],
        ],
        [
            'bundle_item_payload' => ['title' => 'Item 2'],
            'index' => 1,
            'options_by_store_key' => [
                'store_location:101' => [
                    $baseItem2,
                ],
            ],
        ],
    ];

    $firstSelection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
    );
    expect($firstSelection)->not->toBeNull();
    $firstSignature = trim((string) ($firstSelection['selection_signature'] ?? ''));
    expect($firstSignature)->not->toBe('');

    $firstSelected = is_array($firstSelection['selected_candidates'] ?? null) ? $firstSelection['selected_candidates'] : [];
    expect((int) (($firstSelected[0]['cat']->id ?? 0)))->toBe(31);

    $secondSelection = $controller->exposeSelectBundleStoreCandidatesWithMaterialReuse(
        $pendingStoreConstrainedItems,
        'store_location:101',
        [$firstSignature],
    );
    expect($secondSelection)->not->toBeNull();

    $secondSelected = is_array($secondSelection['selected_candidates'] ?? null) ? $secondSelection['selected_candidates'] : [];
    expect((int) (($secondSelected[0]['cat']->id ?? 0)))->toBe(32);
});

test('single-store bundle can use different one-stop store across price ranks in same prefix', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $itemPayloads = [
        [
            'title' => 'Item 1',
            'work_type' => 'wall_plastering',
            'requestData' => ['work_type' => 'wall_plastering'],
            'projects' => [
                [
                    'combinations' => [
                        'Ekonomis 1' => [
                            bundleStoreLockCandidate(100, 11, 21, 101, 'Toko A'),
                            bundleStoreLockCandidate(120, 31, 41, 202, 'Toko B'),
                        ],
                        'Ekonomis 2' => [
                            bundleStoreLockCandidate(130, 12, 22, 101, 'Toko A'),
                            bundleStoreLockCandidate(160, 32, 42, 202, 'Toko B'),
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
                            bundleStoreLockCandidate(101, 11, 21, 101, 'Toko A'),
                            bundleStoreLockCandidate(121, 31, 41, 202, 'Toko B'),
                        ],
                        'Ekonomis 2' => [
                            bundleStoreLockCandidate(131, 12, 22, 101, 'Toko A'),
                            bundleStoreLockCandidate(161, 32, 42, 202, 'Toko B'),
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
            'allow_mixed_store' => false,
        ],
    );

    $ekonomis1 = $bundleCombinations['Ekonomis 1'][0] ?? null;
    $ekonomis2 = $bundleCombinations['Ekonomis 2'][0] ?? null;

    expect($ekonomis1)->toBeArray()
        ->and($ekonomis2)->toBeArray()
        ->and((string) ($ekonomis1['store_label'] ?? ''))->toBe('Toko A')
        ->and((string) ($ekonomis2['store_label'] ?? ''))->toBe('Toko B');
});

test('single-store bundle rank fallback can pick alternate store when strict lock unavailable on higher rank', function () {
    $controller = makeBundleControllerForStoreLockTests();

    $itemPayloads = [
        [
            'title' => 'Item 1',
            'work_type' => 'wall_plastering',
            'requestData' => ['work_type' => 'wall_plastering'],
            'projects' => [
                [
                    'combinations' => [
                        'Ekonomis 1' => [
                            bundleStoreLockCandidate(100, 11, 21, 101, 'Toko A'),
                            bundleStoreLockCandidate(120, 31, 41, 202, 'Toko B'),
                        ],
                        'Ekonomis 2' => [
                            bundleStoreLockCandidate(130, 12, 22, 101, 'Toko A'),
                            bundleStoreLockCandidate(150, 31, 41, 202, 'Toko B'),
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
                            bundleStoreLockCandidate(101, 11, 21, 101, 'Toko A'),
                            bundleStoreLockCandidate(121, 32, 42, 202, 'Toko B'),
                        ],
                        'Ekonomis 2' => [
                            bundleStoreLockCandidate(131, 12, 22, 101, 'Toko A'),
                            bundleStoreLockCandidate(151, 33, 43, 202, 'Toko B'),
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
            'allow_mixed_store' => false,
        ],
    );

    $ekonomis1 = $bundleCombinations['Ekonomis 1'][0] ?? null;
    $ekonomis2 = $bundleCombinations['Ekonomis 2'][0] ?? null;

    expect($ekonomis1)->toBeArray()
        ->and($ekonomis2)->toBeArray()
        ->and((string) ($ekonomis1['store_label'] ?? ''))->toBe('Toko A')
        ->and((string) ($ekonomis2['store_label'] ?? ''))->toBe('Toko B');
});
