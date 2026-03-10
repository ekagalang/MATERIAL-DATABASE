<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_internal_store_search_api(): void
    {
        $this->getJson('/api/stores/all-stores')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated');
    }

    public function test_store_viewer_can_access_internal_store_search_api(): void
    {
        $this->actingAsUserWithPermissions(['stores.view']);

        $this->getJson('/api/stores/all-stores')
            ->assertOk();
    }

    public function test_store_viewer_cannot_quick_create_store_via_api(): void
    {
        $this->actingAsUserWithPermissions(['stores.view']);

        $this->postJson('/api/stores/quick-create', [
            'store' => 'Toko API',
            'address' => 'Jalan API',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }
}
