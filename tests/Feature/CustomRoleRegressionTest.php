<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createCustomRoleUser(string $roleName, array $permissions): User
{
    $role = Role::findOrCreate($roleName, 'web');

    foreach ($permissions as $permissionName) {
        $role->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
    }

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('custom role with only roles permission cannot access users settings', function () {
    $user = createCustomRoleUser('role-auditor', ['roles.manage']);

    $this->actingAs($user)
        ->get(route('settings.roles.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.users.index'))
        ->assertForbidden();
});

test('custom cross module operator can access assigned modules only', function () {
    $user = createCustomRoleUser('store-material-operator', ['stores.manage', 'materials.view']);

    $this->actingAs($user)
        ->get(route('materials.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('stores.create'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('material-calculations.create'))
        ->assertForbidden();
});

test('custom work item manager can access create page without route collision', function () {
    $user = createCustomRoleUser('work-item-manager', ['work-items.manage']);

    $this->actingAs($user)
        ->get(route('work-items.create'))
        ->assertOk();
});

test('custom calculation viewer is redirected to readable calculation log and cannot mutate', function () {
    $user = createCustomRoleUser('calculation-viewer', ['calculations.view']);

    $this->actingAs($user)
        ->get(route('material-calculations.index'))
        ->assertRedirect(route('material-calculations.log', absolute: false));

    $this->actingAs($user)
        ->get(route('material-calculations.log'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('material-calculations.create'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('api.material-calculator.calculate'), [])
        ->assertForbidden();
});
