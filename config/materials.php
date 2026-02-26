<?php

return [
    'topk_buffer_enabled' => (bool) env('MATERIALS_TOPK_BUFFER_ENABLED', false),
    'topk_capacity_per_label' => (int) env('MATERIALS_TOPK_CAPACITY_PER_LABEL', 3),
    'topk_buffer_filters' => array_values(array_filter(array_map(
        static fn(string $value) => trim($value),
        explode(',', (string) env('MATERIALS_TOPK_BUFFER_FILTERS', 'cheapest,expensive,medium')),
    ), static fn(string $value) => $value !== '')),
    'topk_buffer_log_debug' => (bool) env('MATERIALS_TOPK_BUFFER_LOG_DEBUG', false),
    'performance_log_debug' => (bool) env('MATERIALS_PERFORMANCE_LOG_DEBUG', false),
    'combination_complexity_log_debug' => (bool) env('MATERIALS_COMBINATION_COMPLEXITY_LOG_DEBUG', false),
    'combination_complexity_max_estimated' => (int) env('MATERIALS_COMBINATION_COMPLEXITY_MAX_ESTIMATED', 0),
    'combination_complexity_fast_mode_enabled' => (bool) env('MATERIALS_COMPLEXITY_FAST_MODE_ENABLED', false),
    'combination_complexity_fast_mode_cap_per_material' => (int) env('MATERIALS_COMPLEXITY_FAST_MODE_CAP_PER_MATERIAL', 2),
];
