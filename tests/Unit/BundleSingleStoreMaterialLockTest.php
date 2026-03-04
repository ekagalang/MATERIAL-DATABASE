<?php

use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Repositories\CalculationRepository;
use App\Services\Calculation\CombinationGenerationService;

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

    };
}

function bundleStoreLockCandidate(int $grandTotal, ?int $cementId = null, ?int $sandId = null): array
{
    return [
        'result' => ['grand_total' => $grandTotal],
        'store_label' => 'Toko A',
        'store_plan' => [
            ['store_location_id' => 101, 'store_name' => 'Toko A'],
        ],
        'cement' => $cementId ? (object) ['id' => $cementId, 'brand' => 'C-' . $cementId, 'store' => 'Toko A'] : null,
        'sand' => $sandId ? (object) ['id' => $sandId, 'brand' => 'S-' . $sandId, 'store' => 'Toko A'] : null,
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
