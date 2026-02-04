<?php

namespace Tests\Feature\Api;

use App\Models\Nat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NatApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('nats')) {
            $this->markTestSkipped('Table nats tidak tersedia pada environment testing ini.');
        }

        Nat::query()->delete();
    }

    public function test_can_list_nats(): void
    {
        Nat::create([
            'nat_name' => 'Nat A',
            'brand' => 'Brand A',
            'package_price' => 10000,
        ]);

        Nat::create([
            'nat_name' => 'Nat B',
            'brand' => 'Brand B',
            'package_price' => 20000,
        ]);

        $response = $this->getJson('/api/v1/nats?per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_nat(): void
    {
        $response = $this->postJson('/api/v1/nats', [
            'type' => 'Nat Tile',
            'brand' => 'Alpha',
            'sub_brand' => 'Series 01',
            'code' => 'A01',
            'color' => 'Beige',
            'package_weight_gross' => 1.2,
            'package_weight_net' => 1.0,
            'package_price' => 25000,
            'price_unit' => 'Bks',
            'store' => 'TB Maju',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.brand', 'Alpha');

        $this->assertDatabaseHas('nats', [
            'brand' => 'Alpha',
            'code' => 'A01',
            'price_unit' => 'Bks',
        ]);
    }

    public function test_can_update_nat(): void
    {
        $nat = Nat::create([
            'nat_name' => 'Nat Lama',
            'brand' => 'Brand Lama',
            'package_price' => 15000,
        ]);

        $response = $this->putJson('/api/v1/nats/' . $nat->id, [
            'type' => 'Nat Baru',
            'brand' => 'Brand Baru',
            'package_weight_net' => 1.5,
            'package_price' => 30000,
            'price_unit' => 'Bks',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.brand', 'Brand Baru');

        $this->assertDatabaseHas('nats', [
            'id' => $nat->id,
            'brand' => 'Brand Baru',
            'price_unit' => 'Bks',
        ]);
    }

    public function test_can_delete_nat(): void
    {
        $nat = Nat::create([
            'nat_name' => 'Nat Hapus',
            'brand' => 'To Delete',
        ]);

        $response = $this->deleteJson('/api/v1/nats/' . $nat->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('nats', [
            'id' => $nat->id,
        ]);
    }
}
