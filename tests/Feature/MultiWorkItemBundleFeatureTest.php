<?php

use Illuminate\Support\Facades\File;

test('execution controller supports bundle mode payload parsing and bundle preview generation', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)->toContain('$bundleItems = $this->parseBundleItemsPayload($request->input(\'work_items_payload\'));')
        ->and($content)->toContain('if ($request->boolean(\'enable_bundle_mode\'))')
        ->and($content)->toContain('if (count($bundleItems) < 2)')
        ->and($content)->toContain('Mode paket membutuhkan minimal 2 item pekerjaan.')
        ->and($content)->toContain('return $this->generateBundleCombinations($request, $bundleItems);')
        ->and($content)->toContain('protected function parseBundleItemsPayload(mixed $rawPayload): array')
        ->and($content)->toContain("'material_customize_filters' => \$this->normalizeBundleMaterialCustomizeFilters(")
        ->and($content)->toContain('protected function normalizeBundleMaterialCustomizeFilters(mixed $rawFilters): array')
        ->and($content)->toContain('protected function generateBundleCombinations(Request $request, array $bundleItems)')
        ->and($content)->toContain("\$itemRequestData['material_customize_filters'] = \$itemMaterialCustomizeFilters;")
        ->and($content)->toContain('protected function buildBundleSummaryCombinations(array $bundleItemPayloads, array $priceFilters): array')
        ->and($content)->toContain('$candidateLabelLookup = array_fill_keys($candidateLabels, true);')
        ->and($content)->toContain('$this->extractBestCombinationMapForPayload($bundleItemPayload, $candidateLabelLookup);')
        ->and($content)->toContain('protected function shouldLogBundleSummaryDebug(): bool')
        ->and($content)->toContain('protected function buildBundleProjectsPayload(')
        ->and($content)->toContain('\'projects\' => $bundleProjects')
        ->and($content)->toContain('\'ceramicProjects\' => []');
});

test('page controller always renders shared preview combinations view for bundle and non bundle payloads', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationPageController.php'));

    expect($content)->toContain('return view(\'material_calculations.preview_combinations\', $cachedPayload);');
    expect($content)->not->toContain('if (!empty($cachedPayload[\'is_bundle\']))');
    expect($content)->not->toContain('preview_bundle_combinations');
});

test('create view provides plus button on work type input for multi work item mode', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('id="addWorkItemBtn"')
        ->and($content)->toContain('id="enableBundleMode"')
        ->and($content)->toContain('name="work_items_payload"')
        ->and($content)->toContain('id="additionalWorkItemsSection"')
        ->and($content)->toContain('id="additionalWorkItemsList"')
        ->and($content)->toContain('tombol "+" di ujung dropdown item pekerjaan');
});

test('shared preview combinations view preserves calculation session and returns with resume mode', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('materialCalculationSession')
        ->and($content)->toContain('resume=1')
        ->and($content)->toContain('normalized: true');
});

test('price rank candidates exclude populer labels so ekonomis does not reuse populer source rows', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain("\$labelPrefix = trim((string) preg_replace('/\\s+\\d+.*$/u', '', (string) \$label));")
        ->and($content)->toContain("if (strcasecmp(\$labelPrefix, 'Populer') === 0)")
        ->and($content)->toContain('$popularCombinationSignatures')
        ->and($content)->toContain('$candidateSignature = $buildCombinationSignature($project, $item);')
        ->and($content)->toContain('isset($popularCombinationSignatures[$candidateSignature])')
        ->and($content)->toContain('continue;');
});

test('popular grand total is hidden when required materials are incomplete', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain('$isPopularRekapEntryComplete = function (?array $entry)')
        ->and($content)->toContain('$popularHasCompleteVisibleMaterials = true;')
        ->and($content)->toContain('$canShowGrandTotal = !$isPopulerRow ||')
        ->and($content)->toContain('($popularHasCompleteVisibleMaterials && $isPopularRekapEntryComplete($rekapEntry));')
        ->and($content)->toContain('$commonMaterialComplete = true;')
        ->and($content)->toContain('@if ($canShowGrandTotal && isset($globalRekapData[$key][\'grand_total\']) && $globalRekapData[$key][\'grand_total\'] !== null)')
        ->and($content)->toContain('$commonGrandTotal = array_key_exists(\'grand_total\', $row) ? $row[\'grand_total\'] : null;');
});

test('rekap variants collapse duplicate brand detail rows and skip dash-only placeholders', function () {
    $content = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($content)->toContain("if (\$brand === '-' && \$detail === '-')")
        ->and($content)->toContain("\$dedupeKey = \$normalizeVariantText(\$brand) . '|' . \$normalizeVariantText(\$detail);");
});

test('bundle aggregation exposes detailed material rows so preview can render multi-item material variants', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)->toContain('bundle_material_rows')
        ->and($content)->toContain('buildBundleMaterialRows(')
        ->and($content)->toContain('buildBundleMaterialSignature(')
        ->and($content)->toContain('buildBundleMaterialRowFromCombination(')
        ->and($content)->toContain('minimizeBundleCombinationCandidate(')
        ->and($content)->toContain('minimizeBundleItemRequestDataForAggregation(');
});

test('bundle popular label does not fallback to cheapest candidate when popular data is missing', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)->toContain('$popularPrefix')
        ->and($content)->toContain('Do not fallback Popular rows to cheapest/other categories.')
        ->and($content)->toContain('if (strtolower($targetPrefix) === $popularPrefix)')
        ->and($content)->toContain('if ($allowedPrefixKey === $popularPrefix)')
        ->and($content)->toContain('if ($candidatePrefixKey === $popularPrefix)')
        ->and($content)->toContain('return null;');
});

test('bundle popular label allows partial item coverage without forcing all items complete', function () {
    $content = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($content)->toContain('$isPopularLabel = strtolower($extractLabelPrefix((string) $label)) === $popularPrefix;')
        ->and($content)->toContain('if ($isPopularLabel) {')
        ->and($content)->toContain('if (empty($selectedItems)) {')
        ->and($content)->toContain('} elseif (!$isComplete || empty($selectedItems)) {');
});
