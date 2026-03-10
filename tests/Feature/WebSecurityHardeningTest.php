<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createWebDiagnosticUser(): User
{
    $permission = Permission::findOrCreate('logs.view', 'web');
    $role = Role::findOrCreate('web-diagnostics', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('web diagnostic pages are hidden when disabled', function () {
    config(['app.web_diagnostics_enabled' => false]);

    $user = createWebDiagnosticUser();

    $this->actingAs($user)
        ->get('/testing/number-formatting')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/test-error/418')
        ->assertNotFound();
});

test('web diagnostic pages require logs permission when enabled', function () {
    config(['app.web_diagnostics_enabled' => true]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/testing/number-formatting')
        ->assertForbidden();
});

test('web diagnostic pages are available to users with logs permission when enabled', function () {
    config(['app.web_diagnostics_enabled' => true]);

    $user = createWebDiagnosticUser();

    $this->actingAs($user)
        ->get('/testing/number-formatting')
        ->assertOk();

    $this->actingAs($user)
        ->get('/test-error/418')
        ->assertStatus(418);
});
