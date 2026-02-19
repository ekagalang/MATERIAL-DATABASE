<?php

use App\Models\Cement;
use App\Models\Nat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cement autocomplete endpoint can return combined cement and nat values via kinds filter', function () {
    Cement::factory()->create([
        'type' => 'SemenTypeAutocomplete',
        'brand' => 'Brand Semen A',
    ]);

    Nat::factory()->create([
        'type' => 'NatTypeAutocomplete',
        'brand' => 'Brand Nat A',
    ]);

    $response = $this->getJson(route('cements.field-values', [
        'field' => 'type',
        'kinds' => 'cement,nat',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['SemenTypeAutocomplete']);
    $response->assertJsonFragment(['NatTypeAutocomplete']);
});

test('nat autocomplete endpoint can return combined nat and cement values via kinds filter', function () {
    Cement::factory()->create([
        'type' => 'SemenTypeAutocomplete2',
        'brand' => 'Brand Semen B',
    ]);

    Nat::factory()->create([
        'type' => 'NatTypeAutocomplete2',
        'brand' => 'Brand Nat B',
    ]);

    $response = $this->getJson(route('nats.field-values', [
        'field' => 'type',
        'kinds' => 'cement,nat',
    ]));

    $response->assertOk();
    $response->assertJsonFragment(['SemenTypeAutocomplete2']);
    $response->assertJsonFragment(['NatTypeAutocomplete2']);
});
