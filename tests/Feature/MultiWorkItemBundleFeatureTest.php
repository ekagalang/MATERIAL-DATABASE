<?php

use Illuminate\Support\Facades\File;

test('execution controller supports bundle mode payload parsing and bundle preview generation', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)
        ->toContain('$bundleItems = $this->parseBundleItemsPayload($request->input(\'work_items_payload\'));')
        ->and($content)
        ->toContain('if ($request->boolean(\'enable_bundle_mode\'))')
        ->and($content)
        ->toContain('if (count($bundleItems) < 2)')
        ->and($content)
        ->toContain('Mode paket membutuhkan minimal 2 item pekerjaan.')
        ->and($content)
        ->toContain('return $this->generateBundleCombinations($request, $bundleItems);')
        ->and($content)
        ->toContain('protected function parseBundleItemsPayload(mixed $rawPayload): array')
        ->and($content)
        ->toContain("'material_customize_filters' => \$this->normalizeBundleMaterialCustomizeFilters(")
        ->and($content)
        ->toContain('protected function normalizeBundleMaterialCustomizeFilters(mixed $rawFilters): array')
        ->and($content)
        ->toContain('protected function generateBundleCombinations(Request $request, array $bundleItems)')
        ->and($content)
        ->toContain("\$baseMaterialTypeFilters = \$this->normalizeBundleMaterialTypeFilters(")
        ->and($content)
        ->toContain("\$effectiveItemMaterialTypeFilters = !empty(\$itemMaterialTypeFilters)")
        ->and($content)
        ->toContain("\$itemRequestData['material_customize_filters'] = \$effectiveItemMaterialCustomizeFilters;")
        ->and($content)
        ->toContain(
            'protected function buildBundleSummaryCombinations(array $bundleItemPayloads, array $priceFilters): array',
        )
        ->and($content)
        ->toContain('$candidateLabelLookup = array_fill_keys($candidateLabels, true);')
        ->and($content)
        ->toContain('$this->extractBestCombinationMapForPayload($bundleItemPayload, $candidateLabelLookup);')
        ->and($content)
        ->toContain('protected function shouldLogBundleSummaryDebug(): bool')
        ->and($content)
        ->toContain('protected function buildBundleProjectsPayload(')
        ->and($content)
        ->toContain('\'projects\' => $bundleProjects')
        ->and($content)
        ->toContain('\'ceramicProjects\' => []');
});

test('page controller always renders shared preview combinations view for bundle and non bundle payloads', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationPageController.php'));

    expect($content)->toContain('return view(\'material_calculations.preview_combinations\', $cachedPayload);');
    expect($content)->not->toContain('if (!empty($cachedPayload[\'is_bundle\']))');
    expect($content)->not->toContain('preview_bundle_combinations');
});

test('create view provides plus button on work type input for multi work item mode', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('id="addWorkItemBtn"')
        ->and($content)
        ->toContain('id="enableBundleMode"')
        ->and($content)
        ->toContain('name="work_items_payload"')
        ->and($content)
        ->toContain('id="additionalWorkItemsSection"')
        ->and($content)
        ->toContain('id="additionalWorkItemsList"')
        ->and($content)
        ->toContain('tombol "+" di ujung dropdown item pekerjaan');
});

test('create view enables preferensi filter when any bundle work item has recommendation', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain("document.querySelectorAll('#additionalWorkItemsList [data-field=\"work_type\"]')")
        ->and($content)
        ->toContain('if (shouldIncludeBest()) {')
        ->and($content)
        ->toContain('filterBest.checked = shouldIncludeBest();')
        ->and($content)
        ->toContain('availableBestRecommendations.includes(workType)');
});

test('create view validates project address before submit and marks location field error', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('const validateProjectLocationBeforeSubmit = () => {')
        ->and($content)
        ->toContain('location-required-error')
        ->and($content)
        ->toContain('Harap isi alamat proyek terlebih dahulu sebelum menghitung.')
        ->and($content)
        ->toContain('if (!validateProjectLocationBeforeSubmit()) {');
});

test('create view only auto-opens item pekerjaan dropdown for item rows', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain("const shouldAutoFocusWorkType = normalizeBundleRowKind(item.row_kind) === 'item';")
        ->and($content)
        ->toContain('if (shouldAutoFocusWorkType && !hasInitialWorkType) {');
});

test('create view can programmatically open taxonomy dropdown for floor area and field inputs', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)
        ->toContain('floorDisplayInput.__openAdditionalTaxonomyList = () => floorAutocomplete.openList();')
        ->and($content)
        ->toContain('areaDisplayInput.__openAdditionalTaxonomyList = () => areaAutocomplete.openList();')
        ->and($content)
        ->toContain('fieldDisplayInput.__openAdditionalTaxonomyList = () => fieldAutocomplete.openList();')
        ->and($content)
        ->toContain('if (typeof inputEl.__openAdditionalTaxonomyList === \'function\') {');
});

test('shared preview combinations view preserves calculation session and returns with resume mode', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain('materialCalculationSession')
        ->and($content)
        ->toContain('resume=1')
        ->and($content)
        ->toContain('normalized: true');
});

test('price rank candidates exclude populer labels so ekonomis does not reuse populer source rows', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain("\$labelPrefix = trim((string) preg_replace('/\\s+\\d+.*$/u', '', (string) \$label));")
        ->and($content)
        ->toContain("if (strcasecmp(\$labelPrefix, 'Populer') === 0)")
        ->and($content)
        ->toContain('$popularCombinationSignatures')
        ->and($content)
        ->toContain('$candidateSignature = $buildCombinationSignature($project, $item);')
        ->and($content)
        ->toContain('isset($popularCombinationSignatures[$candidateSignature])')
        ->and($content)
        ->toContain('continue;');
});

test('popular grand total is hidden when required materials are incomplete', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain('$isPopularRekapEntryComplete = function (?array $entry)')
        ->and($content)
        ->toContain('$popularHasCompleteVisibleMaterials = true;')
        ->and($content)
        ->toContain('$canShowGrandTotal = !$isPopulerRow ||')
        ->and($content)
        ->toContain('($popularHasCompleteVisibleMaterials && $isPopularRekapEntryComplete($rekapEntry));')
        ->and($content)
        ->toContain('$commonMaterialComplete = true;')
        ->and($content)
        ->toContain(
            '@if ($canShowGrandTotal && isset($globalRekapData[$key][\'grand_total\']) && $globalRekapData[$key][\'grand_total\'] !== null)',
        )
        ->and($content)
        ->toContain('$commonGrandTotal = array_key_exists(\'grand_total\', $row) ? $row[\'grand_total\'] : null;');
});

test('detail table skips preferensi and populer rows when active rekap materials are incomplete', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain('$hasCompleteVisibleRekapMaterialsForDetailLabel = function (?array $entry)')
        ->and($content)
        ->toContain('$detailLabelHasCompleteMaterials = !$requiresCompleteDetailMaterials ||')
        ->and($content)
        ->toContain('if (!$detailLabelHasCompleteMaterials) {')
        ->and($content)
        ->toContain('continue;');
});

test('rekap variants collapse duplicate brand detail rows and skip dash-only placeholders', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain("if (\$brand === '-' && \$detail === '-')")
        ->and($content)
        ->toContain("\$dedupeKey = \$normalizeVariantText(\$brand) . '|' . \$normalizeVariantText(\$detail);");
});

test(
    'bundle aggregation exposes detailed material rows so preview can render multi-item material variants',
    function () {
        $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

        expect($content)
            ->toContain('bundle_material_rows')
            ->and($content)
            ->toContain('buildBundleMaterialRows(')
            ->and($content)
            ->toContain('buildBundleMaterialSignature(')
            ->and($content)
            ->toContain('buildBundleMaterialRowFromCombination(')
            ->and($content)
            ->toContain('minimizeBundleCombinationCandidate(')
            ->and($content)
            ->toContain('minimizeBundleItemRequestDataForAggregation(');
    },
);

test('bundle popular label does not fallback to cheapest candidate when popular data is missing', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)
        ->toContain('$popularPrefix')
        ->and($content)
        ->toContain('$preferensiPrefix')
        ->and($content)
        ->toContain('Do not fallback Popular/Preferensi rows to other categories.')
        ->and($content)
        ->toContain('if (in_array(strtolower($targetPrefix), [$popularPrefix, $preferensiPrefix], true))')
        ->and($content)
        ->toContain('if ($allowedPrefixKey === $popularPrefix)')
        ->and($content)
        ->toContain('if ($candidatePrefixKey === $popularPrefix)')
        ->and($content)
        ->toContain('return null;');
});

test('bundle popular label allows partial item coverage without forcing all items complete', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)
        ->toContain('$isPopularLabel = strtolower($extractLabelPrefix((string) $label)) === $popularPrefix;')
        ->and($content)
        ->toContain('$isPreferenceLabel = strtolower($extractLabelPrefix((string) $label)) === $preferensiPrefix;')
        ->and($content)
        ->toContain('if ($isPopularLabel || $isPreferenceLabel) {')
        ->and($content)
        ->toContain('if (empty($selectedItems)) {')
        ->and($content)
        ->toContain('} elseif (!$isComplete || empty($selectedItems)) {');
});

test('bundle preview keeps preferensi and populer rank labels without view-side renumbering', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)
        ->toContain('$isBundlePreviewMode = !empty($is_bundle ?? false) || !empty($requestData[\'enable_bundle_mode\'] ?? false);')
        ->and($content)
        ->toContain('if ($isBundlePreviewMode) {')
        ->and($content)
        ->toContain('$newKey = $key;')
        ->and($content)
        ->toContain('if ($filterType === \'Populer\') {')
        ->and($content)
        ->toContain('$populerDetailMap[$newKey] = $selectedCombination;');
});

test('bundle generation respects mixed store mode from request instead of forcing one-stop', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)
        ->toContain('$bundleAllowMixedStore = $bundleUseStoreFilter')
        ->and($content)
        ->toContain("\$itemRequestData['allow_mixed_store'] = \$bundleAllowMixedStore ? 1 : 0;")
        ->and($content)
        ->toContain("'allow_mixed_store' => \$bundleAllowMixedStore ? 1 : 0,");
});
