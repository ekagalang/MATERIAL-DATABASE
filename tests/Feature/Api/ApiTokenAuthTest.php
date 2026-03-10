<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_api_token_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'api-user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/tokens', [
            'email' => 'api-user@example.com',
            'password' => 'password',
            'device_name' => 'integration-script',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token created')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'token_type', 'user' => ['id', 'name', 'email']],
            ]);
    }

    public function test_user_cannot_create_api_token_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'api-user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/tokens', [
            'email' => 'api-user@example.com',
            'password' => 'wrong-password',
            'device_name' => 'integration-script',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_bearer_token_can_access_permitted_api_endpoint(): void
    {
        $user = $this->createUserWithPermissions(['stores.view']);
        $token = $user->createToken('integration-script')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/stores/all-stores')
            ->assertOk();
    }

    public function test_bearer_token_respects_permission_checks(): void
    {
        $user = $this->createUserWithPermissions(['stores.view']);
        $token = $user->createToken('integration-script')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/stores/quick-create', [
                'store' => 'Toko Token',
                'address' => 'Jalan Token',
            ])->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_user_can_revoke_current_api_token(): void
    {
        $user = $this->createUserWithPermissions(['stores.view']);
        $token = $user->createToken('integration-script')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/auth/tokens/current')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Token revoked');

        $this->assertNull(PersonalAccessToken::findToken($token));

        auth()->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/stores/all-stores')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }
}
