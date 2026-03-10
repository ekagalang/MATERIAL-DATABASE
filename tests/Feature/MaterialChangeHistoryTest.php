<?php

use App\Models\Brick;
use App\Models\MaterialChangeLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('material update stores change history with editor and before after values', function () {
    $user = $this->actingAsUserWithPermissions(['materials.manage']);

    $brick = Brick::factory()->create([
        'brand' => 'Merek Lama',
        'store' => 'Toko Lama',
        'price_per_piece' => 1200,
    ]);

    $payload = [
        'type' => $brick->type,
        'brand' => 'Merek Baru',
        'form' => $brick->form,
        'dimension_length' => $brick->dimension_length,
        'dimension_width' => $brick->dimension_width,
        'dimension_height' => $brick->dimension_height,
        'package_type' => $brick->package_type,
        'store' => 'Toko Baru',
        'address' => $brick->address,
        'price_per_piece' => 1500,
        'comparison_price_per_m3' => $brick->comparison_price_per_m3,
        '_redirect_to_materials' => 1,
    ];

    $this->put(route('bricks.update', $brick), $payload)
        ->assertRedirect(route('materials.index', absolute: false));

    $log = MaterialChangeLog::query()
        ->where('material_table', $brick->getTable())
        ->where('material_id', $brick->id)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->action)->toBe('updated')
        ->and($log->changes['brand']['from'])->toBe('Merek Lama')
        ->and($log->changes['brand']['to'])->toBe('Merek Baru')
        ->and($log->changes['store']['from'])->toBe('Toko Lama')
        ->and($log->changes['store']['to'])->toBe('Toko Baru')
        ->and($log->changes['price_per_piece']['from'])->toEqual(1200)
        ->and($log->changes['price_per_piece']['to'])->toEqual(1500);
});

test('material update only logs fields explicitly edited by the user', function () {
    $this->actingAsUserWithPermissions(['materials.manage']);

    $brick = Brick::factory()->create([
        'brand' => 'Merek Lama',
        'price_per_piece' => 1200,
        'comparison_price_per_m3' => 800000,
    ]);

    $this->put(route('bricks.update', $brick), [
        'type' => $brick->type,
        'brand' => 'Merek Baru',
        'form' => $brick->form,
        'dimension_length' => $brick->dimension_length,
        'dimension_width' => $brick->dimension_width,
        'dimension_height' => $brick->dimension_height,
        'package_type' => $brick->package_type,
        'store' => $brick->store,
        'address' => $brick->address,
        'price_per_piece' => $brick->price_per_piece,
        'comparison_price_per_m3' => $brick->comparison_price_per_m3,
        '_redirect_to_materials' => 1,
    ])->assertRedirect(route('materials.index', absolute: false));

    $log = MaterialChangeLog::query()
        ->where('material_table', $brick->getTable())
        ->where('material_id', $brick->id)
        ->where('action', 'updated')
        ->latest('id')
        ->firstOrFail();

    expect(array_keys($log->changes))->toBe(['brand'])
        ->and($log->changes['brand']['from'])->toBe('Merek Lama')
        ->and($log->changes['brand']['to'])->toBe('Merek Baru');
});

test('material create stores change history with creator and inserted values', function () {
    $user = $this->actingAsUserWithPermissions(['materials.manage']);

    $payload = [
        'type' => 'Press',
        'brand' => 'Merek Baru',
        'form' => 'Solid',
        'dimension_length' => 24,
        'dimension_width' => 11,
        'dimension_height' => 6,
        'package_type' => 'eceran',
        'store' => 'Toko Utama',
        'address' => 'Jalan Material No. 1',
        'price_per_piece' => 1300,
        '_redirect_to_materials' => 1,
    ];

    $this->post(route('bricks.store'), $payload)
        ->assertRedirect(route('materials.index', absolute: false));

    $brick = Brick::query()->latest('id')->firstOrFail();

    $log = MaterialChangeLog::query()
        ->where('material_table', $brick->getTable())
        ->where('material_id', $brick->id)
        ->where('action', 'created')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->changes['brand']['from'])->toBeNull()
        ->and($log->changes['brand']['to'])->toBe('Merek Baru')
        ->and($log->changes['store']['from'])->toBeNull()
        ->and($log->changes['store']['to'])->toBe('Toko Utama');
});

test('material delete stores change history with deleted values', function () {
    $user = $this->actingAsUserWithPermissions(['materials.manage']);

    $brick = Brick::factory()->create([
        'brand' => 'Merek Hapus',
        'store' => 'Toko Hapus',
    ]);

    $this->delete(route('bricks.destroy', $brick))
        ->assertRedirect(route('bricks.index', absolute: false));

    $log = MaterialChangeLog::query()
        ->where('material_table', $brick->getTable())
        ->where('material_id', $brick->id)
        ->where('action', 'deleted')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->changes['brand']['from'])->toBe('Merek Hapus')
        ->and($log->changes['brand']['to'])->toBeNull()
        ->and($log->changes['store']['from'])->toBe('Toko Hapus')
        ->and($log->changes['store']['to'])->toBeNull();
});

test('material detail modal shows change history entries', function () {
    $user = $this->actingAsUserWithPermissions(['materials.manage']);

    $brick = Brick::factory()->create([
        'brand' => 'Merek Lama',
        'price_per_piece' => 1200,
    ]);

    MaterialChangeLog::query()->create([
        'material_table' => $brick->getTable(),
        'material_id' => $brick->id,
        'material_kind' => 'brick',
        'user_id' => $user->id,
        'request_batch' => 'test-batch-1',
        'action' => 'updated',
        'changes' => [
            'brand' => ['from' => 'Merek Lama', 'to' => 'Merek Baru'],
            'price_per_piece' => ['from' => 1200.0, 'to' => 1500.0],
        ],
        'before_values' => [
            'brand' => 'Merek Lama',
            'price_per_piece' => 1200.0,
        ],
        'after_values' => [
            'brand' => 'Merek Baru',
            'price_per_piece' => 1500.0,
        ],
        'edited_at' => now(),
    ]);

    $this->get(route('bricks.show', $brick))
        ->assertOk()
        ->assertSee('Riwayat Perubahan')
        ->assertSee($user->name)
        ->assertSee('Merek Lama')
        ->assertSee('Merek Baru');
});
