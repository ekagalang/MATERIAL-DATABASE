<?php

use Illuminate\Support\Facades\File;

test('material calculation controller avoids global brick pool iteration for store-filter mode without explicit brick selection', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('$useStoreFilter = $request->boolean(\'use_store_filter\', true) && $workType !== \'grout_tile\';')
        ->and($content)->toContain('$hasExplicitBrickSelection =')
        ->and($content)->toContain('if (!$isBrickless && $useStoreFilter && !$hasExplicitBrickSelection)')
        ->and($content)->toContain('calculateCombinations($request, [\'brick\' => null]);')
        ->and($content)->toContain('resolveDisplayBrickFromCombinations($combinations)');
});

test('material calculation controller can derive project display brick from generated combinations', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('protected function resolveDisplayBrickFromCombinations(array $combinations): ?Brick')
        ->and($content)->toContain('if (($item[\'brick\'] ?? null) instanceof Brick)')
        ->and($content)->toContain('return $item[\'brick\'];');
});
