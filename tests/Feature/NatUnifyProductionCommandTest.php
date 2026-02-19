<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nat unify production command dry run succeeds and prints planned steps', function () {
    $this->artisan('nat:unify-production --dry-run --drop-legacy')
        ->expectsOutputToContain('NAT UNIFICATION PRODUCTION ROLLOUT')
        ->expectsOutputToContain('Planned steps:')
        ->expectsOutputToContain('2026_02_19_120000_add_material_kind_to_cements_table.php')
        ->expectsOutputToContain('2026_02_19_130000_merge_nats_into_cements_table.php')
        ->expectsOutputToContain('2026_02_19_140000_drop_legacy_nats_table.php')
        ->assertSuccessful();
});

