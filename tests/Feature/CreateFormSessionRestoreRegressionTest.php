<?php

use Illuminate\Support\Facades\File;

test('bundle restore rehydrates main work item taxonomy from payload before restoring additional rows', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('function restoreAdditionalWorkItemsFromBundle(restoredBundleItems = [])')
        ->and($content)
        ->toContain('applyMainWorkItemFromBundleItem(mainItem, {')
        ->and($content)
        ->toContain('preserveExistingTaxonomy: true')
        ->and($content)
        ->toContain('fallbackBundleTaxonomySource')
        ->and($content)
        ->toContain('if (restoredBundleItems.length <= 1) {');
});

test('session restore derives store search mode when legacy payload misses explicit mode key', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('stateHasStoreSearchMode')
        ->and($content)
        ->toContain('stateHasAllowMixedStore')
        ->and($content)
        ->toContain("derivedStoreSearchMode = 'incomplete';")
        ->and($content)
        ->toContain("derivedStoreSearchMode = 'complete_outside';")
        ->and($content)
        ->toContain("modeHiddenInput.value = derivedStoreSearchMode;");
});

test('session restore also derives store search mode when key exists but value is empty or invalid', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('stateStoreSearchModeValue')
        ->and($content)
        ->toContain('storeSearchModeValueValid')
        ->and($content)
        ->toContain('if (!storeSearchModeValueValid')
        ->and($content)
        ->toContain("derivedStoreSearchMode = 'complete_within';");
});

test('floor-order rebuild only swaps main card when candidate and main have valid work item payload', function () {
    $blade = File::get(resource_path('views/material_calculations/create.blade.php'));
    $engine = File::get(public_path('js/vue/material-calculation-create-rebuild-floor-order-engine.js'));

    expect($blade)
        ->toContain('const hasMainWorkType = String(mainDraft?.work_type || \'\').trim() !== \'\';')
        ->and($blade)
        ->toContain('const hasCandidateWorkType = String(candidateData?.work_type || \'\').trim() !== \'\';')
        ->and($blade)
        ->toContain('const hasCandidateFloor = String(candidateData?.work_floor || \'\').trim() !== \'\';')
        ->and($blade)
        ->toContain('if (!hasMainWorkType || !hasCandidateWorkType || !hasCandidateFloor)')
        ->and($engine)
        ->toContain('const hasMainWorkType = String(mainDraft?.work_type || \'\').trim() !== \'\';')
        ->and($engine)
        ->toContain('const hasCandidateWorkType = String(candidateData?.work_type || \'\').trim() !== \'\';')
        ->and($engine)
        ->toContain('const hasCandidateFloor = String(candidateData?.work_floor || \'\').trim() !== \'\';')
        ->and($engine)
        ->toContain('if (!hasMainWorkType || !hasCandidateWorkType || !hasCandidateFloor)');
});

test('create form syncs taxonomy hidden inputs from display values before session save and submit', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('function syncWorkTaxonomyHiddenInputsFromDisplay()')
        ->and($content)
        ->toContain('syncWorkTaxonomyHiddenInputsFromDisplay();')
        ->and($content)
        ->toContain('hiddenEl.value = nextValue;');
});

test('bundle payload collection preserves restore metadata for additional rows', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('const collectedRowData = collectAdditionalWorkItemData(row, i + 1);')
        ->and($content)
        ->toContain('collectAdditionalWorkItemData(itemEl, index = 0)')
        ->and($content)
        ->toContain('restore_scope: restoreMetadata.restore_scope,')
        ->and($content)
        ->toContain('restore_parent_area_key: restoreMetadata.restore_parent_area_key,')
        ->and($content)
        ->toContain('restore_parent_field_key: restoreMetadata.restore_parent_field_key,');
});

test('create form invalidates stale local session schema before restore', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('const calcSessionSchemaVersion = 2;')
        ->and($content)
        ->toContain('schemaVersion: calcSessionSchemaVersion,')
        ->and($content)
        ->toContain('const hasSchemaVersion = Object.prototype.hasOwnProperty.call(parsed || {}, \'schemaVersion\');')
        ->and($content)
        ->toContain('const parsedSchemaVersion = hasSchemaVersion ? Number(parsed?.schemaVersion) : calcSessionSchemaVersion;')
        ->and($content)
        ->toContain('const isLegacyCompatibleSession = !hasSchemaVersion')
        ->and($content)
        ->toContain('if (!isLegacyCompatibleSession && parsedSchemaVersion !== calcSessionSchemaVersion) {');
});

test('preview pages persist calculation session schema version when caching local session', function () {
    $previewCombinations = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));
    $previewBundle = File::get(resource_path('views/material_calculations/preview_bundle_combinations.blade.php'));

    expect($previewCombinations)
        ->toContain('const calcSessionSchemaVersion = 2;')
        ->and($previewCombinations)
        ->toContain('schemaVersion: calcSessionSchemaVersion,')
        ->and($previewBundle)
        ->toContain('const calcSessionSchemaVersion = 2;')
        ->and($previewBundle)
        ->toContain('schemaVersion: calcSessionSchemaVersion,');
});

test('work taxonomy vue engine uses injected floor-sort hook instead of global function lookup', function () {
    $blade = File::get(resource_path('views/material_calculations/create.blade.php'));
    $engine = File::get(public_path('js/vue/material-calculation-create-work-taxonomy-filter-engine.js'));

    expect($blade)
        ->toContain('markFloorSortPending,')
        ->and($engine)
        ->toContain('const markFloorSortPending = typeof deps.markFloorSortPending === \'function\'')
        ->and($engine)
        ->toContain('markFloorSortPending();');
});

test('create form keeps main taxonomy bound to vue work taxonomy engine bridge path', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('if (typeof window.materialCalcCreateWorkTaxonomyFilterEngine === \'function\')')
        ->and($content)
        ->toContain('const bridgeWorkTaxonomyFilterApi = window.materialCalcCreateWorkTaxonomyFilterEngine({');
});
