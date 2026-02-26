<?php

use Illuminate\Support\Facades\File;

test('material calculation create view exposes customize filters per material type', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('class="material-type-row-btn material-type-row-btn-customize"')
        ->and($content)->toContain('const materialTypeLabels = @json($materialTypeLabels);')
        ->and($content)->toContain('data-customize-toggle="{{ $materialKey }}"')
        ->and($content)->toContain('id="customizePanel-brick"')
        ->and($content)->toContain('data-filter-key="brand"')
        ->and($content)->toContain('data-filter-key="dimension"')
        ->and($content)->toContain('id="customizePanel-cement"')
        ->and($content)->toContain('id="customizePanel-sand"')
        ->and($content)->toContain('id="customizePanel-cat"')
        ->and($content)->toContain('id="customizeCatPackage"')
        ->and($content)->toContain('id="customizeCatVolume"')
        ->and($content)->toContain('id="customizeCatWeight"')
        ->and($content)->toContain('id="customizePanel-ceramic_type"')
        ->and($content)->not->toContain('id="customizePanel-ceramic"')
        ->and($content)->toContain('id="customizePanel-nat"')
        ->and($content)->toContain('material-type-customize-panel');
});

test('material calculation form script initializes cascading customize filters', function () {
    $content = File::get(public_path('js/material-calculation-form.js'));

    expect($content)->toContain('function setupCustomMaterialAdvancedFilters()')
        ->and($content)->toContain('data-customize-toggle')
        ->and($content)->toContain('refreshFromFieldIndex')
        ->and($content)->toContain('setupCustomMaterialAdvancedFilters();');
});

test('price filter list no longer includes custom option and custom material form is always available', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->not->toContain('id="filter_custom"')
        ->and($content)->toContain('id="customMaterialForm"')
        ->and($content)->toContain('id="customMaterialForm" style="display:none');
});

test('bundle material filter rows include customize button for supported material types', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain("const bundleCustomizeSupportedTypes = new Set(['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'nat']);")
        ->and($content)->toContain('const supportsCustomize = bundleCustomizeSupportedTypes.has(String(type || \'\').trim());')
        ->and($content)->toContain('${supportsCustomize ? `')
        ->and($content)->toContain('data-customize-toggle="${type}"')
        ->and($content)->toContain("const actionBtn = target.closest('[data-material-type-action]');")
        ->and($content)->toContain("if (action === 'add') {");
});

test('calculation session stores and restores customize panel state', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('data.customize_panel_state = customizePanelState;')
        ->and($content)->toContain("if (key === 'customize_panel_state')")
        ->and($content)->toContain('const openBtn = document.querySelector(`[data-customize-panel-id="${panelId}"]`);')
        ->and($content)->toContain("panelEl.hidden = !shouldOpen;");
});

test('create view serializes customize filters into request payloads for calculation', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('name="material_customize_filters_payload"')
        ->and($content)->toContain('function collectMainMaterialCustomizeFilters()')
        ->and($content)->toContain('function collectAdditionalMaterialCustomizeFilters(itemEl)')
        ->and($content)->toContain('material_customize_filters: collectMainMaterialCustomizeFilters(),')
        ->and($content)->toContain('material_customize_filters: collectAdditionalMaterialCustomizeFilters(row),');
});
