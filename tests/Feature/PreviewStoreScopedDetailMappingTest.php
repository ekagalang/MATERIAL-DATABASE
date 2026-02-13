<?php

use Illuminate\Support\Facades\File;

test('preview combinations view disables historical populer fallback when store filter mode is active', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('$isStoreScopedView = (bool) ($requestData[\'use_store_filter\'] ?? true);')
        ->and($content)->toContain('if (in_array(\'Populer\', $filterCategories, true) && $hasHistoricalUsage && !$isStoreScopedView)');
});

test('preview combinations view matches detail rows using brick from combination item', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('$resolveBrickModelForRekap = function ($project, $item) {')
        ->and($content)->toContain('$comboBrick = $resolveBrickModelForRekap($project, $item);')
        ->and($content)->toContain('$comboBrick = $item[\'brick\'] ?? ($project[\'brick\'] ?? $defaultProjectBrick);')
        ->and($content)->toContain('$itemBrickId =')
        ->and($content)->toContain('$rekapBrickId = $rekapData[\'brick_id\'] ?? null;')
        ->and($content)->toContain('$detailCombinationMap = [];')
        ->and($content)->toContain('$resolvedDetailEntry = null;')
        ->and($content)->toContain('if ($resolvedDetailEntry)')
        ->and($content)->toContain('$resolvedItem = $resolvedDetailEntry[\'item\'];')
        ->and($content)->toContain('array_key_exists(\'grand_total\', $resolvedItem[\'result\'])')
        ->and($content)->toContain('array_key_exists(\'grand_total\', $item[\'result\'])')
        ->and($content)->toContain('array_key_exists(\'grand_total\', $fallbackEntry[\'item\'][\'result\'])');
});
