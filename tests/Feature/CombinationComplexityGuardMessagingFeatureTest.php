<?php

use Illuminate\Support\Facades\File;

test('material calculation controller shows specific message when complexity guard blocks all combinations', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('consumeComplexityGuardEvents()')
        ->and($content)->toContain('Preview generation halted by complexity guard')
        ->and($content)->toContain('Perhitungan dihentikan karena kombinasi material terlalu banyak (complexity guard aktif).');
});

