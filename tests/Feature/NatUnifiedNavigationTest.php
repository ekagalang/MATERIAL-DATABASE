<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nat index route redirects to unified materials cement tab', function () {
    $response = $this->get(route('nats.index'));

    $response->assertRedirect(route('materials.index', ['tab' => 'cement']));
});

test('nat create route redirects to cement create form', function () {
    $response = $this->get(route('nats.create'));

    $response->assertRedirect(route('cements.create'));
});

