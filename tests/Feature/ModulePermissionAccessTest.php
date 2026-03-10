<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createUserForModulePermission(string $permissionName): User
{
    $permission = Permission::findOrCreate($permissionName, 'web');
    $role = Role::findOrCreate("module-{$permissionName}", 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create([
        'password' => 'secret-pass-123',
    ]);
    $user->assignRole($role);

    return $user;
}

test('materials viewer can access materials module and is denied stores module', function () {
    $user = createUserForModulePermission('materials.view');

    $this->actingAs($user)
        ->get(route('materials.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('stores.index'))
        ->assertForbidden();
});

test('materials viewer can access material helper endpoint', function () {
    $user = createUserForModulePermission('materials.view');

    $this->actingAs($user)
        ->get(route('materials.type-suggestions', ['q' => '']))
        ->assertOk();
});

test('login redirects user to first permitted module when dashboard permission is absent', function () {
    $user = createUserForModulePermission('materials.view');

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-pass-123',
    ])->assertRedirect(route('materials.index', absolute: false));
});

test('work item viewer can access work items and is denied calculation create page', function () {
    $user = createUserForModulePermission('work-items.view');

    $this->actingAs($user)
        ->get(route('work-items.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('material-calculations.create'))
        ->assertForbidden();
});

test('calculation manager can access calculation create page and is denied work taxonomy settings', function () {
    $user = createUserForModulePermission('calculations.manage');

    $this->actingAs($user)
        ->get(route('material-calculations.create'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.work-areas.index'))
        ->assertForbidden();
});

test('recommendation manager can access recommendation settings and is denied work taxonomy settings', function () {
    $user = createUserForModulePermission('recommendations.manage');

    $this->actingAs($user)
        ->get(route('settings.recommendations.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.work-areas.index'))
        ->assertForbidden();
});

test('work taxonomy manager can access work taxonomy settings and is denied store radius settings', function () {
    $user = createUserForModulePermission('work-taxonomy.manage');

    $this->actingAs($user)
        ->get(route('settings.work-areas.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('settings.store-search-radius.index'))
        ->assertForbidden();
});
