<?php

use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createUserWithPermission(string $permissionName): User
{
    $permission = Permission::findOrCreate($permissionName, 'web');
    $role = Role::findOrCreate("role-for-{$permissionName}", 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('user without roles permission cannot access roles settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.roles.index'))
        ->assertForbidden();
});

test('authorized admin can create role with permissions', function () {
    Permission::findOrCreate('settings.manage', 'web');

    $user = createUserWithPermission('roles.manage');

    $this->actingAs($user)
        ->post(route('settings.roles.store'), [
            'name' => 'project-manager',
            'permissions' => ['settings.manage'],
        ])
        ->assertRedirect(route('settings.roles.index'));

    $role = Role::findByName('project-manager', 'web');

    expect($role->hasPermissionTo('settings.manage'))->toBeTrue();
});

test('user without users permission cannot access users settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.users.index'))
        ->assertForbidden();
});

test('authorized admin can create user and assign role', function () {
    $user = createUserWithPermission('users.manage');
    $role = Role::findOrCreate('staff', 'web');

    $this->actingAs($user)
        ->post(route('settings.users.store'), [
            'name' => 'Staff User',
            'email' => 'staff@example.test',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'roles' => ['staff'],
        ])
        ->assertRedirect(route('settings.users.index'));

    $createdUser = User::where('email', 'staff@example.test')->first();

    expect($createdUser)->not->toBeNull()
        ->and($createdUser->hasRole('staff'))->toBeTrue();
});

test('authorized admin can update user roles', function () {
    $admin = createUserWithPermission('users.manage');
    $targetUser = User::factory()->create();
    Role::findOrCreate('staff', 'web');
    Role::findOrCreate('supervisor', 'web');

    $this->actingAs($admin)
        ->put(route('settings.users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'roles' => ['staff', 'supervisor'],
        ])
        ->assertRedirect(route('settings.users.index'));

    expect($targetUser->fresh()->hasRole('staff'))->toBeTrue()
        ->and($targetUser->fresh()->hasRole('supervisor'))->toBeTrue();
});

test('authorized admin can enable public registration from users settings', function () {
    $admin = createUserWithPermission('users.manage');

    $this->actingAs($admin)
        ->post(route('settings.users.registration.update'), [
            'registration_enabled' => '1',
        ])
        ->assertRedirect(route('settings.users.index'));

    expect(AppSetting::getValue('auth.registration_enabled'))->toBe('1');
});

test('authorized admin can filter users by role', function () {
    $admin = createUserWithPermission('users.manage');
    $staffRole = Role::findOrCreate('staff', 'web');
    $supervisorRole = Role::findOrCreate('supervisor', 'web');

    $staffUser = User::factory()->create(['name' => 'Staff Target']);
    $staffUser->assignRole($staffRole);

    $supervisorUser = User::factory()->create(['name' => 'Supervisor Target']);
    $supervisorUser->assignRole($supervisorRole);

    $this->actingAs($admin)
        ->get(route('settings.users.index', ['role' => 'staff']))
        ->assertOk()
        ->assertSee('Staff Target')
        ->assertDontSee('Supervisor Target');
});

test('authorized admin can filter roles by permission', function () {
    $admin = createUserWithPermission('roles.manage');

    $userPermission = Permission::findOrCreate('users.manage', 'web');
    $roleWithPermission = Role::findOrCreate('user-admin', 'web');
    $roleWithPermission->givePermissionTo($userPermission);

    $roleWithoutPermission = Role::findOrCreate('viewer-only', 'web');

    $this->actingAs($admin)
        ->get(route('settings.roles.index', ['permission' => 'users.manage']))
        ->assertOk()
        ->assertSee('user-admin')
        ->assertDontSee('viewer-only');
});
