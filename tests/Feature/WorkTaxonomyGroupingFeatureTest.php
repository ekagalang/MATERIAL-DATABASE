<?php

use Illuminate\Support\Facades\File;

test('material calculation create view wires area bidang taxonomy filters and scoped work type provider', function () {
    $content = File::get(resource_path('views/material_calculations/create.blade.php'));

    expect($content)->toContain('id="workFloorRows"')
        ->and($content)->toContain('id="workAreaRows"')
        ->and($content)->toContain('id="workFieldRows"')
        ->and($content)->toContain('name="work_floors[]"')
        ->and($content)->toContain('name="work_areas[]"')
        ->and($content)->toContain('name="work_fields[]"')
        ->and($content)->toContain('function initWorkTaxonomyFilters(formPayload)')
        ->and($content)->toContain('data-field-display="work_floor"')
        ->and($content)->toContain('window.MaterialCalculationWorkTypeOptionsProvider = function()')
        ->and($content)->toContain('data-field-display="work_area"')
        ->and($content)->toContain('data-field-display="work_field"')
        ->and($content)->toContain('function initAdditionalWorkTaxonomyAutocomplete(itemEl, initial = {})')
        ->and($content)->toContain('workTaxonomyFilterApi.subscribe(function()')
        ->and($content)->toContain("if (key === 'work_floors' || key === 'work_areas' || key === 'work_fields')")
        ->and($content)->toContain('const taxonomyKindMap = { work_floors: \'floor\', work_areas: \'area\', work_fields: \'field\' };');
});

test('settings menu and routes expose lantai area and bidang management', function () {
    $routes = File::get(base_path('routes/web.php'));
    $layout = File::get(resource_path('views/layouts/app.blade.php'));
    $floorView = File::get(resource_path('views/settings/work_floors/index.blade.php'));
    $areaView = File::get(resource_path('views/settings/work_areas/index.blade.php'));
    $fieldView = File::get(resource_path('views/settings/work_fields/index.blade.php'));

    expect($routes)->toContain("Route::get('/work-floors', [WorkFloorController::class, 'index'])->name('work-floors.index');")
        ->and($routes)->toContain("Route::get('/work-areas', [WorkAreaController::class, 'index'])->name('work-areas.index');")
        ->and($routes)->toContain("Route::get('/work-fields', [WorkFieldController::class, 'index'])->name('work-fields.index');")
        ->and($layout)->toContain("route('settings.work-floors.index')")
        ->and($layout)->toContain("route('settings.work-areas.index')")
        ->and($layout)->toContain("route('settings.work-fields.index')")
        ->and($layout)->toContain('Manajemen Lantai')
        ->and($layout)->toContain('Manajemen Area')
        ->and($layout)->toContain('Manajemen Bidang')
        ->and($floorView)->toContain("route('settings.work-floors.store')")
        ->and($areaView)->toContain("route('settings.work-areas.store')")
        ->and($fieldView)->toContain("route('settings.work-fields.store')");
});

test('material calculation controller persists lantai area and bidang taxonomy values', function () {
    $controller = File::get(app_path('Http/Controllers/MaterialCalculationController.php'));
    $executionController = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));

    expect($controller)->toContain('protected function mergeWorkTaxonomyFilters(Request $request): void')
        ->and($controller)->toContain('protected function persistWorkItemTaxonomy(string $workType, array $floors = [], array $areas = [], array $fields = []): void')
        ->and($controller)->toContain("WorkFloor::firstOrCreate(['name' => \$name]);")
        ->and($controller)->toContain("WorkArea::firstOrCreate(['name' => \$name]);")
        ->and($controller)->toContain("WorkField::firstOrCreate(['name' => \$name]);")
        ->and($controller)->toContain('WorkItemGrouping::firstOrCreate([')
        ->and($executionController)->toContain("'work_floor' => trim((string) (\$entry['work_floor'] ?? ''))")
        ->and($executionController)->toContain("'work_area' => trim((string) (\$entry['work_area'] ?? ''))")
        ->and($executionController)->toContain("'work_field' => trim((string) (\$entry['work_field'] ?? ''))")
        ->and($executionController)->toContain("\$bundleItem['work_floors'] ?? (\$bundleItem['work_floor'] ?? \$workFloors)")
        ->and($executionController)->toContain("\$bundleItem['work_areas'] ?? (\$bundleItem['work_area'] ?? \$workAreas)")
        ->and($executionController)->toContain("\$bundleItem['work_fields'] ?? (\$bundleItem['work_field'] ?? \$workFields)");
});

test('bundle detail modal renders lantai area and bidang grouping metadata from bundle payload', function () {
    $executionController = File::get(app_path('Http/Controllers/MaterialCalculationExecutionController.php'));
    $previewView = File::get(resource_path('views/material_calculations/preview_combinations.blade.php'));

    expect($executionController)->toContain("'work_floor' => \$itemWorkFloor")
        ->and($executionController)->toContain("'work_area' => \$itemWorkArea")
        ->and($executionController)->toContain("'work_field' => \$itemWorkField")
        ->and($executionController)->toContain("'row_kind' => \$itemRowKind")
        ->and($previewView)->toContain("\$groupTaxonomyParts[] = 'Lantai: ' . \$groupWorkFloor;")
        ->and($previewView)->toContain("\$groupTaxonomyParts[] = 'Area: ' . \$groupWorkArea;")
        ->and($previewView)->toContain("\$groupTaxonomyParts[] = 'Bidang: ' . \$groupWorkField;")
        ->and($previewView)->toContain("\$fallbackTaxonomyParts[] = 'Lantai: ' . \$fallbackWorkFloor;")
        ->and($previewView)->toContain("\$fallbackTaxonomyParts[] = 'Area: ' . \$fallbackWorkArea;")
        ->and($previewView)->toContain("\$fallbackTaxonomyParts[] = 'Bidang: ' . \$fallbackWorkField;");
});
