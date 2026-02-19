<?php

use App\Models\Cement;
use App\Models\Nat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('materials index merges nat tab into cement tab summary', function () {
    Cement::factory()->create([
        'type' => 'Semen Instan',
        'brand' => 'Cement Brand',
    ]);

    Nat::factory()->create([
        'type' => 'Nat Epoxy',
        'nat_name' => 'Nat Premium',
        'brand' => 'Nat Brand',
    ]);

    $response = $this->get(route('materials.index'));

    $response->assertOk();

    $materials = collect($response->viewData('materials'));
    $types = $materials->pluck('type')->all();

    expect($types)->toContain('cement')
        ->and($types)->not->toContain('nat');

    $cementTab = $materials->firstWhere('type', 'cement');

    expect($cementTab)->not->toBeNull()
        ->and((int) ($cementTab['db_count'] ?? 0))->toBe(2);
});

test('materials tab endpoint maps nat tab request into cement tab data', function () {
    $cement = Cement::factory()->create([
        'type' => 'Semen Putih',
        'brand' => 'Cement A',
    ]);

    $nat = Nat::factory()->create([
        'type' => 'NatTypeUnique',
        'nat_name' => 'Nat Keramik',
        'brand' => 'NatBrandUnique',
    ]);

    $response = $this->get(route('materials.tab', ['type' => 'nat']));

    $response->assertOk();

    $material = $response->viewData('material');
    $data = $material['data'];
    $html = $response->getContent();

    expect($material['type'])->toBe('cement')
        ->and($data->pluck('id')->all())->toContain($cement->id, $nat->id)
        ->and($data->pluck('row_material_type')->all())->toContain('cement', 'nat');

    $response->assertSee('NatBrandUnique');
    $response->assertSee('NatTypeUnique');
    $response->assertSee("deleteMaterial('nat', {$nat->id})", false);
    expect(substr_count($html, "deleteMaterial('nat',"))->toBeGreaterThan(0);
});

test('materials index no longer exposes nat create entry point in unified modal flow', function () {
    $response = $this->get(route('materials.index'));

    $response->assertOk();
    $response->assertDontSee(route('nats.create'), false);
});
