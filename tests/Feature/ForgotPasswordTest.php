<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('guest can view forgot password page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Lupa Password');
});

test('guest can request password reset link', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset-me@example.test',
    ]);

    $this->post(route('password.email'), [
        'email' => 'reset-me@example.test',
    ])->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('guest can view reset password page with valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Atur Password Baru');
});

test('guest can reset password with valid token', function () {
    Event::fake([PasswordReset::class]);

    $user = User::factory()->create([
        'email' => 'change-me@example.test',
        'password' => 'old-password-123',
    ]);

    $token = Password::createToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status');

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();

    Event::assertDispatched(PasswordReset::class);
});
