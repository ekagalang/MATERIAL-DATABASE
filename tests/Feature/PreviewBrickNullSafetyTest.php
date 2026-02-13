<?php

use Illuminate\Support\Facades\File;

test('preview combinations view uses null-safe brick access in detail calculations', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('$brickDimensionLength = (float) ($brick?->dimension_length ?? 0);')
        ->and($content)->toContain('$brickDimensionWidth = (float) ($brick?->dimension_width ?? 0);')
        ->and($content)->toContain('$brickDimensionHeight = (float) ($brick?->dimension_height ?? 0);')
        ->and($content)->toContain('$brickPricePerPiece = $res[\'brick_price_per_piece\'] ?? ($brick?->price_per_piece ?? 0);')
        ->and($content)->toContain('$isBricklessWork ? 0 : ($brick?->id ?? 0),');
});

test('preview combinations view only submits brick_id when brick exists', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('@if (!($isBrickless ?? false) && !empty($brick))')
        ->and($content)->toContain('$traceParams[\'brick_id\'] = $brick->id;');
});
