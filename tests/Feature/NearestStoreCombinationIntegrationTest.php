<?php

use Illuminate\Support\Facades\File;

test('combination generation service includes nearest radius chain fallback logic', function () {
    $content = File::get(app_path('Services/Calculation/CombinationGenerationService.php'));

    expect($content)->toContain('project_latitude')
        ->and($content)->toContain('project_longitude')
        ->and($content)->toContain('sortReachableLocations')
        ->and($content)->toContain('buildNearestCoveragePlan')
        ->and($content)->toContain('store_coverage_mode')
        ->and($content)->toContain('store_cost_breakdown')
        ->and($content)->toContain('nearest_radius_chain');
});

test('preview combinations view includes store chain allocation section', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('store_plan')
        ->and($content)->toContain('store_coverage_mode')
        ->and($content)->toContain('store_cost_breakdown')
        ->and($content)->toContain('Biaya per Toko')
        ->and($content)->toContain('Sumber Toko');
});

test('store radius mode disables store-name fallback when project coordinates are present', function () {
    $content = File::get(app_path('Services/Calculation/CombinationGenerationService.php'));

    expect($content)->toContain('$allowStoreNameFallback = !$hasProjectCoordinates;')
        ->and($content)->toContain('$allowMixedStore = $request->boolean(\'allow_mixed_store\', false);')
        ->and($content)->toContain('if ($allowMixedStore && empty($allStoreCombinations) && $hasProjectCoordinates)')
        ->and($content)->toContain('storeHasBrick($location, $brick, $allowStoreNameFallback)')
        ->and($content)->toContain('loadStoreMaterialsForLocation(')
        ->and($content)->toContain('bool $allowStoreNameFallback = true')
        ->and($content)->toContain('if ($allowStoreNameFallback && !$hasBrick)')
        ->and($content)->toContain('if ($allowStoreNameFallback && !$hasFixedCeramic)');
});


