<?php

use Illuminate\Support\Facades\File;

test('materials config exposes performance debug toggle', function () {
    $content = File::get(config_path('materials.php'));

    expect($content)->toContain("'performance_log_debug' => (bool) env('MATERIALS_PERFORMANCE_LOG_DEBUG', false),");
    expect($content)->toContain("'combination_complexity_fast_mode_enabled' => (bool) env('MATERIALS_COMPLEXITY_FAST_MODE_ENABLED', false),");
    expect($content)->toContain(
        "'combination_complexity_fast_mode_cap_per_material' => (int) env('MATERIALS_COMPLEXITY_FAST_MODE_CAP_PER_MATERIAL', 2),",
    );
});

test('material calculation controller includes shared performance instrumentation helper', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));

    expect($content)->toContain('protected function shouldLogMaterialPerformanceDebug(): bool')
        ->and($content)->toContain('protected function materialPerformanceSnapshot(float $startedAt): array')
        ->and($content)->toContain("Material calculation performance")
        ->and($content)->toContain("consumeComplexityFastModeEvents()")
        ->and($content)->toContain("'calculationDiagnostics' => [")
        ->and($content)->toContain("'complexity_fast_mode_events' => array_values(\$complexityFastModeEvents),")
        ->and($content)->toContain('generate_combinations.target_bricks_ready')
        ->and($content)->toContain('generate_combinations.projects_built')
        ->and($content)->toContain('generate_combinations.payload_cached');
});

test('bundle generation logs performance stages for item loop summary and cache', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)->toContain('bundle_combinations.item_payloads_built')
        ->and($content)->toContain('bundle_combinations.summary_built')
        ->and($content)->toContain('bundle_combinations.projects_payload_built')
        ->and($content)->toContain('bundle_combinations.payload_cached')
        ->and($content)->toContain("'bundle_item_complexity_fast_mode_events'")
        ->and($content)->toContain("'calculationDiagnostics' => [");
});
