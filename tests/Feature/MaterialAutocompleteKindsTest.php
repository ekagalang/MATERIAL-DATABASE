<?php

use App\Models\Cement;
use App\Models\Nat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAutocompleteKindsUser(): User
{
    $permission = Permission::findOrCreate('materials.view', 'web');
    $role = Role::findOrCreate('autocomplete-kinds-tester', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('cement autocomplete endpoint can return combined cement and nat values via kinds filter', function () {
    $user = createAutocompleteKindsUser();

    Cement::factory()->create([
        'type' => 'SemenTypeAutocomplete',
        'brand' => 'Brand Semen A',
    ]);

    Nat::factory()->create([
        'type' => 'NatTypeAutocomplete',
        'brand' => 'Brand Nat A',
    ]);

    $response = $this->actingAs($user)->getJson(route('cements.field-values', [
        'field' => 'type',
        'kinds' => 'cement,nat',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['SemenTypeAutocomplete']);
    $response->assertJsonFragment(['NatTypeAutocomplete']);
});

test('nat autocomplete endpoint can return combined nat and cement values via kinds filter', function () {
    $user = createAutocompleteKindsUser();

    Cement::factory()->create([
        'type' => 'SemenTypeAutocomplete2',
        'brand' => 'Brand Semen B',
    ]);

    Nat::factory()->create([
        'type' => 'NatTypeAutocomplete2',
        'brand' => 'Brand Nat B',
    ]);

    $response = $this->actingAs($user)->getJson(route('nats.field-values', [
        'field' => 'type',
        'kinds' => 'cement,nat',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['SemenTypeAutocomplete2']);
    $response->assertJsonFragment(['NatTypeAutocomplete2']);
});
