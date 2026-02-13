<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalculationApiSchemaContractTest extends TestCase
{
    use DatabaseTransactions;

    private function requireCalculationTable(): void
    {
        if (!Schema::hasTable('brick_calculations')) {
            $this->markTestSkipped('Table brick_calculations tidak tersedia pada environment testing ini.');
        }
    }

    public function test_index_returns_expected_contract_shape(): void
    {
        $this->requireCalculationTable();

        $response = $this->getJson('/api/v1/calculations');

        $response->assertOk()->assertJsonPath('success', true)->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    }

    public function test_show_returns_not_found_contract_shape(): void
    {
        $this->requireCalculationTable();

        $response = $this->getJson('/api/v1/calculations/999999999');

        $response->assertStatus(404)->assertJsonPath('success', false)->assertJsonStructure(['success', 'message']);
    }

    public function test_update_returns_not_found_contract_shape(): void
    {
        $this->requireCalculationTable();

        $response = $this->putJson('/api/v1/calculations/999999999', []);

        $response->assertStatus(404)->assertJsonPath('success', false)->assertJsonStructure(['success', 'message']);
    }

    public function test_destroy_returns_not_found_contract_shape(): void
    {
        $this->requireCalculationTable();

        $response = $this->deleteJson('/api/v1/calculations/999999999');

        $response->assertStatus(404)->assertJsonPath('success', false)->assertJsonStructure(['success', 'message']);
    }

    public function test_calculate_returns_validation_error_contract_shape(): void
    {
        $response = $this->postJson('/api/v1/calculations/calculate', []);

        $response->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', 'Validation error')
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_preview_returns_validation_error_contract_shape(): void
    {
        $response = $this->postJson('/api/v1/calculations/preview', []);

        $response->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', 'Validation error')
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_compare_returns_validation_error_contract_shape(): void
    {
        $response = $this->postJson('/api/v1/calculations/compare', []);

        $response->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', 'Validation error')
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_trace_returns_validation_error_contract_shape(): void
    {
        $response = $this->postJson('/api/v1/calculations/trace', []);

        $response->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', 'Validation error')
            ->assertJsonStructure(['success', 'message', 'errors']);
    }
}
