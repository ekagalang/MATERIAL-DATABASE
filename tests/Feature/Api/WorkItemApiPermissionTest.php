<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkItemApiPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_item_viewer_can_access_work_items_api(): void
    {
        $this->actingAsUserWithPermissions(['work-items.view']);

        $this->getJson('/api/v1/work-items')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_work_item_viewer_cannot_create_work_item_via_api(): void
    {
        $this->actingAsUserWithPermissions(['work-items.view']);

        $this->postJson('/api/v1/work-items', [])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Forbidden');
    }
}
