<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_diagnostic_endpoints_are_available_when_enabled(): void
    {
        config(['app.api_diagnostics_enabled' => true]);

        $this->getJson('/api/test')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_diagnostic_endpoints_are_hidden_when_disabled(): void
    {
        config(['app.api_diagnostics_enabled' => false]);

        $this->getJson('/api/test')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found');

        $this->postJson('/api/test-validation', [
            'name' => 'Test',
            'email' => 'test@example.com',
        ])->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found');
    }

    public function test_api_token_issuance_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'rate-limit@example.com',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
                ->postJson('/api/auth/tokens', [
                    'email' => 'rate-limit@example.com',
                    'password' => 'wrong-password',
                    'device_name' => 'rate-limit-check',
                ])->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->postJson('/api/auth/tokens', [
                'email' => 'rate-limit@example.com',
                'password' => 'wrong-password',
                'device_name' => 'rate-limit-check',
            ])->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Too many requests');
    }
}
