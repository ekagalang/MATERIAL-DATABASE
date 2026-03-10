<?php

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('material autocomplete endpoints normalize numeric values for create and edit forms', function () {
    $permission = Permission::findOrCreate('materials.view', 'web');
    $role = Role::findOrCreate('autocomplete-tester', 'web');
    $role->givePermissionTo($permission);
    $user = User::factory()->create();
    $user->assignRole($role);

    Brick::factory()->create([
        'price_per_piece' => 1200.00,
    ]);

    Cat::query()->create([
        'cat_name' => 'Cat Autocomplete',
        'brand' => 'Brand Cat',
        'package_weight_gross' => 18.50,
    ]);

    Cement::factory()->create([
        'package_weight_gross' => 40.50,
    ]);

    Sand::factory()->create([
        'dimension_length' => 1.25,
    ]);

    Ceramic::factory()->create([
        'dimension_thickness' => 0.80,
    ]);

    Nat::factory()->create([
        'package_weight_gross' => 5.10,
    ]);

    $this->actingAs($user)->getJson(route('bricks.field-values', ['field' => 'price_per_piece']))
        ->assertOk()
        ->assertExactJson([1200]);

    $this->actingAs($user)->getJson(route('cats.field-values', ['field' => 'package_weight_gross']))
        ->assertOk()
        ->assertExactJson([18.5]);

    $this->actingAs($user)->getJson(route('cements.field-values', ['field' => 'package_weight_gross']))
        ->assertOk()
        ->assertExactJson([40.5]);

    $this->actingAs($user)->getJson(route('sands.field-values', ['field' => 'dimension_length']))
        ->assertOk()
        ->assertExactJson([1.25]);

    $this->actingAs($user)->getJson(route('ceramics.field-values', ['field' => 'dimension_thickness']))
        ->assertOk()
        ->assertExactJson([0.8]);

    $this->actingAs($user)->getJson(route('nats.field-values', ['field' => 'package_weight_gross']))
        ->assertOk()
        ->assertExactJson([5.1]);
});
