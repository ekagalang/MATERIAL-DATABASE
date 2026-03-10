<?php

use App\Models\Nat;
use App\Models\Cement;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api bulk update accepts grout_tile recommendation without ceramic id', function () {
    $this->actingAsUserWithPermissions(['recommendations.manage']);
    $nat = Nat::factory()->create();

    $response = $this->postJson('/api/v1/recommendations/bulk-update', [
        'recommendations' => [
            [
                'work_type' => 'grout_tile',
                'nat_id' => $nat->id,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $saved = RecommendedCombination::query()
        ->where('type', 'best')
        ->where('work_type', 'grout_tile')
        ->first();

    expect($saved)->not->toBeNull()
        ->and((int) ($saved->nat_id ?? 0))->toBe((int) $nat->id)
        ->and($saved->ceramic_id)->toBeNull();
});

test('material calculation create only exposes active best recommendations', function () {
    $this->actingAsUserWithPermissions(['calculations.manage']);
    RecommendedCombination::query()->create([
        'work_type' => 'brick_half',
        'type' => 'best',
        'is_active' => false,
        'sort_order' => 0,
    ]);

    RecommendedCombination::query()->create([
        'work_type' => 'tile_installation',
        'type' => 'best',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $response = $this->get(route('material-calculations.create'));

    $response->assertOk()
        ->assertViewHas('bestRecommendations', function ($bestRecommendations) {
            if (!is_array($bestRecommendations)) {
                return false;
            }

            return in_array('tile_installation', $bestRecommendations, true) &&
                !in_array('brick_half', $bestRecommendations, true);
        });
});

test('api bulk update stores maximum three recommendations per work type', function () {
    $this->actingAsUserWithPermissions(['recommendations.manage']);
    $payload = [
        'recommendations' => [],
    ];

    for ($i = 0; $i < 4; $i++) {
        $cement = Cement::factory()->create();
        $sand = Sand::factory()->create();

        $payload['recommendations'][] = [
            'work_type' => 'brick_half',
            'cement_id' => $cement->id,
            'sand_id' => $sand->id,
        ];
    }

    $response = $this->postJson('/api/v1/recommendations/bulk-update', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $count = RecommendedCombination::query()
        ->where('type', 'best')
        ->where('work_type', 'brick_half')
        ->count();

    expect($count)->toBe(3);
});
