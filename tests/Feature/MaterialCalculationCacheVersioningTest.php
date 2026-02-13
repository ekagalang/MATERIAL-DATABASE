<?php

use Illuminate\Support\Facades\File;

test('material calculation controller uses versioned cache key prefix for preview payloads', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('protected const CALCULATION_CACHE_KEY_PREFIX = \'material_calc:v3:\';')
        ->and($content)->toContain('$payload[\'_engine_version\'] = self::CALCULATION_CACHE_KEY_PREFIX;')
        ->and($content)->toContain('return self::CALCULATION_CACHE_KEY_PREFIX . hash(\'sha256\', json_encode($normalized));');
});

test('material calculation index redirect ignores stale unversioned session cache keys', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('if (!str_starts_with((string) $cacheKey, self::CALCULATION_CACHE_KEY_PREFIX))')
        ->and($content)->toContain('session()->forget(\'material_calc_last_key\');');
});

test('material calculation preview rejects stale cache keys from old engine versions', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('if (!str_starts_with($cacheKey, self::CALCULATION_CACHE_KEY_PREFIX))')
        ->and($content)->toContain('Rejected stale preview cache key prefix')
        ->and($content)->toContain('Hasil preview lama terdeteksi. Silakan hitung ulang untuk data terbaru.');
});
