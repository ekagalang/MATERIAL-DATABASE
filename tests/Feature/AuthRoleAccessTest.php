<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest can view login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee(route('register'));
});

test('login page shows register link when registration is enabled', function () {
    \App\Models\AppSetting::putValue('auth.registration_enabled', true);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('register'));
});

test('guest cannot view register page when registration is disabled', function () {
    $this->get('/register')
        ->assertNotFound();
});

test('guest is redirected to login when accessing dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect('/login');
});

test('user can authenticate through login form', function () {
    Permission::findOrCreate('dashboard.view', 'web');
    $role = Role::findOrCreate('dashboard-user', 'web');
    $role->givePermissionTo('dashboard.view');

    $password = 'secret-pass-123';
    $user = User::factory()->create([
        'password' => $password,
    ]);
    $user->assignRole($role);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => $password,
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('user without module permissions is redirected to access pending after login', function () {
    $password = 'secret-pass-123';
    $user = User::factory()->create([
        'password' => $password,
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => $password,
    ])->assertRedirect(route('access.pending', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('guest can register through registration form when registration is enabled', function () {
    \App\Models\AppSetting::putValue('auth.registration_enabled', true);

    $this->post(route('register'), [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'secret-pass-123',
        'password_confirmation' => 'secret-pass-123',
    ])->assertRedirect(route('access.pending', absolute: false));

    $user = User::query()->where('email', 'new-user@example.com')->first();

    expect($user)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('guest cannot register when registration is disabled', function () {
    $this->post('/register', [
        'name' => 'Blocked User',
        'email' => 'blocked-user@example.com',
        'password' => 'secret-pass-123',
        'password_confirmation' => 'secret-pass-123',
    ])->assertNotFound();

    $this->assertGuest();
});

test('authenticated user without settings permission cannot access settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.work-areas.index'))
        ->assertForbidden();
});

test('admin user with settings permission can access settings page', function () {
    $permission = Permission::findOrCreate('settings.manage', 'web');
    $role = Role::findOrCreate('admin', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('settings.work-areas.index'))
        ->assertOk();
});

test('authenticated user without permissions can access pending access page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('access.pending'))
        ->assertOk();
});
