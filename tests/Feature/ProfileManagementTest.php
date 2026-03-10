<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('guest is redirected when accessing profile page', function () {
    $this->get('/profile')
        ->assertRedirect('/login');
});

test('authenticated user can view profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Profile Akun')
        ->assertSee($user->name)
        ->assertSee($user->email);
});

test('authenticated user can update profile details', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.test',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'New Name',
        ])
        ->assertRedirect(route('profile.show'));

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->email)->toBe('old@example.test');
});

test('profile update ignores attempted email changes', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.test',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.test',
        ])
        ->assertRedirect(route('profile.show'));

    expect($user->fresh()->name)->toBe('New Name')
        ->and($user->fresh()->email)->toBe('old@example.test');
});

test('authenticated user can change password from profile page', function () {
    $user = User::factory()->create([
        'password' => 'old-secret-123',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => $user->name,
            'current_password' => 'old-secret-123',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])
        ->assertRedirect(route('profile.show'));

    expect(Hash::check('new-secret-123', $user->fresh()->password))->toBeTrue();
});

test('profile password update requires correct current password', function () {
    $user = User::factory()->create([
        'password' => 'old-secret-123',
    ]);

    $this->actingAs($user)
        ->from(route('profile.show'))
        ->put(route('profile.update'), [
            'name' => $user->name,
            'current_password' => 'wrong-password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old-secret-123', $user->fresh()->password))->toBeTrue();
});
