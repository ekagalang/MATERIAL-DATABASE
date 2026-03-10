<?php

use App\Http\Controllers\BrickController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\CementController;
use App\Http\Controllers\CeramicController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\MaterialCalculationExecutionController;
use App\Http\Controllers\MaterialCalculationPageController;
use App\Http\Controllers\MaterialCalculationTraceController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\NatController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSearchRadiusSettingController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StoreLocationController;
use App\Http\Controllers\WorkAreaController;
use App\Http\Controllers\WorkFieldController;
use App\Http\Controllers\WorkFloorController;
use App\Http\Middleware\EnsureWebDiagnosticsEnabled;
use App\Helpers\NumberHelper;
use App\Models\BrickCalculation;
// use App\Http\Controllers\Dev\PriceAnalysisController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

$dashboardViewPermission = 'permission:dashboard.view';
$materialReadPermission = 'permission:materials.view|materials.manage';
$materialManagePermission = 'permission:materials.manage';
$storeReadPermission = 'permission:stores.view|stores.manage';
$storeManagePermission = 'permission:stores.manage';
$workItemReadPermission = 'permission:work-items.view|work-items.manage|projects.view|projects.manage';
$workItemManagePermission = 'permission:work-items.manage|projects.manage';
$calculationReadPermission = 'permission:calculations.view|calculations.manage|projects.view|projects.manage';
$calculationManagePermission = 'permission:calculations.manage|projects.manage';
$unitReadPermission = 'permission:units.view|units.manage';
$unitManagePermission = 'permission:units.manage';
$workerViewPermission = 'permission:workers.view';
$skillViewPermission = 'permission:skills.view';
$recommendationManagePermission = 'permission:recommendations.manage|settings.manage';
$workTaxonomyManagePermission = 'permission:work-taxonomy.manage|settings.manage';
$storeSearchRadiusManagePermission = 'permission:store-search-radius.manage|settings.manage';
$webDiagnosticsMiddleware = [EnsureWebDiagnosticsEnabled::class, 'permission:logs.view'];

Route::middleware('auth')->group(function () use (
    $dashboardViewPermission,
    $materialReadPermission,
    $materialManagePermission,
    $storeReadPermission,
    $storeManagePermission,
    $workItemReadPermission,
    $workItemManagePermission,
    $calculationReadPermission,
    $calculationManagePermission,
    $unitReadPermission,
    $unitManagePermission,
    $workerViewPermission,
    $skillViewPermission,
    $recommendationManagePermission,
    $workTaxonomyManagePermission,
    $storeSearchRadiusManagePermission,
    $webDiagnosticsMiddleware,
) {
Route::middleware($dashboardViewPermission)->get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
Route::middleware('permission:logs.view')->get('/log', [LogViewerController::class, 'index'])->name('logs.index');

// Tambahkan sementara untuk testing:
Route::get('/test-error/{code}', function ($code) {
    abort($code);
})->middleware($webDiagnosticsMiddleware);

// Halaman testing format angka (khusus internal)
Route::get('/testing/number-formatting', function () {
    $samples = [
        ['label' => 'integer', 'value' => '10'],
        ['label' => 'one decimal', 'value' => '10.5'],
        ['label' => 'two decimals', 'value' => '10.25'],
        ['label' => 'three decimals', 'value' => '10.259'],
        ['label' => 'three decimals (round up)', 'value' => '10.299'],
        ['label' => 'small decimal', 'value' => '0.0049'],
        ['label' => 'small decimal just over', 'value' => '0.0051'],
        ['label' => 'very small decimal', 'value' => '0.000021'],
        ['label' => 'large number', 'value' => '1234567.8912'],
        ['label' => 'negative', 'value' => '-12.3456'],
        ['label' => 'zero', 'value' => '0'],
    ];
    $factor = '1.333';

    $normalizeInput = static function (string $value): string {
        $value = trim($value);
        $value = str_replace(',', '.', $value);
        if ($value === '' || !is_numeric($value)) {
            return '0';
        }
        return $value;
    };

    $dynamicPlain = static function (string $value) use ($normalizeInput): string {
        $value = $normalizeInput($value);
        $sign = '';
        if (str_starts_with($value, '-')) {
            $sign = '-';
            $value = substr($value, 1);
        }

        $parts = explode('.', $value, 2);
        $intPart = $parts[0] === '' ? '0' : ltrim($parts[0], '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        $decPart = $parts[1] ?? '';
        $decPart = rtrim($decPart, '0');

        if ($intPart !== '0') {
            $decPart = substr($decPart, 0, 2);
            $decPart = rtrim($decPart, '0');
            if ($decPart === '') {
                return $sign . $intPart;
            }
            return $sign . $intPart . '.' . $decPart;
        }

        if ($decPart === '') {
            return $sign . '0';
        }

        $leadingZeros = strspn($decPart, '0');
        if ($leadingZeros >= strlen($decPart)) {
            return $sign . '0';
        }

        $cutLength = min(strlen($decPart), $leadingZeros + 2);
        $decPart = substr($decPart, 0, $cutLength);
        $decPart = rtrim($decPart, '0');

        if ($decPart === '') {
            return $sign . '0';
        }

        return $sign . '0.' . $decPart;
    };

    $formatIdDynamic = static function (string $value) use ($dynamicPlain): string {
        $plain = $dynamicPlain($value);
        $sign = '';
        if (str_starts_with($plain, '-')) {
            $sign = '-';
            $plain = substr($plain, 1);
        }

        $parts = explode('.', $plain, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';
        $intPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $intPart);

        if ($decPart === '') {
            return $sign . $intPart;
        }

        return $sign . $intPart . ',' . $decPart;
    };

    $buildRow = static function (string $label, string $value) use (
        $normalizeInput,
        $dynamicPlain,
        $formatIdDynamic,
        $factor,
    ): array {
        $raw = $normalizeInput($value);
        $rawFloat = (float) $raw;
        $currentDisplay = NumberHelper::format($rawFloat);
        $currentCalcPlain = NumberHelper::format(NumberHelper::normalize($rawFloat), null, '.', '');

        $targetPlain = $dynamicPlain($raw);
        $targetDisplay = $formatIdDynamic($raw);

        $calcRaw = (float) $targetPlain * (float) $factor;
        $calcPlain = NumberHelper::format(NumberHelper::normalize($calcRaw), null, '.', '');
        $calcDisplay = $formatIdDynamic($calcPlain);

        return [
            'label' => $label,
            'raw' => $raw,
            'current_display' => $currentDisplay,
            'current_calc' => $currentCalcPlain,
            'target_display' => $targetDisplay,
            'target_calc' => $targetPlain,
            'calc_display' => $calcDisplay,
            'display_ok' => $currentDisplay === $targetDisplay,
            'calc_ok' => $currentCalcPlain === $targetPlain,
        ];
    };

    $rows = [];
    foreach ($samples as $sample) {
        $rows[] = $buildRow($sample['label'], $sample['value']);
    }

    $fields = [
        'wall_length' => 'Wall length',
        'wall_height' => 'Wall height',
        'wall_area' => 'Wall area',
        'mortar_thickness' => 'Mortar thickness',
        'brick_quantity' => 'Brick quantity',
        'mortar_volume' => 'Mortar volume',
        'cement_quantity_sak' => 'Cement quantity (sak)',
        'sand_m3' => 'Sand volume (M3)',
        'water_liters' => 'Water liters',
        'total_material_cost' => 'Total material cost',
    ];
    $realRows = [];
    $recentCalculations = BrickCalculation::orderByDesc('created_at')->limit(5)->get();
    foreach ($recentCalculations as $calculation) {
        foreach ($fields as $field => $label) {
            $rawValue = $calculation->getRawOriginal($field);
            if ($rawValue === null || $rawValue === '') {
                continue;
            }
            $row = $buildRow($label, (string) $rawValue);
            $row['calc_id'] = $calculation->id;
            $row['created_at'] = $calculation->created_at?->format('Y-m-d H:i');
            $row['field'] = $field;
            $realRows[] = $row;
        }
    }

    return view('testing.number-formatting', [
        'rows' => $rows,
        'factor' => $factor,
        'realRows' => $realRows,
    ]);
})->middleware($webDiagnosticsMiddleware)->name('testing.number-formatting');

Route::resource('units', UnitController::class)
    ->except(['index', 'show'])
    ->middleware($unitManagePermission);
Route::resource('units', UnitController::class)
    ->only(['index', 'show'])
    ->middleware($unitReadPermission);

Route::resource('cats', CatController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('cats', CatController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

Route::resource('bricks', BrickController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('bricks', BrickController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

Route::get('/materials/type-suggestions', [MaterialController::class, 'typeSuggestions'])
    ->middleware($materialReadPermission)
    ->name('materials.type-suggestions');
Route::get('/materials/tab/{type}', [MaterialController::class, 'fetchTab'])
    ->middleware($materialReadPermission)
    ->name('materials.tab');
Route::resource('materials', MaterialController::class)
    ->only(['index'])
    ->middleware($materialReadPermission);

Route::resource('cements', CementController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('cements', CementController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

Route::resource('nats', NatController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('nats', NatController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

Route::resource('sands', SandController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('sands', SandController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

Route::resource('ceramics', CeramicController::class)
    ->except(['index', 'show'])
    ->middleware($materialManagePermission);
Route::resource('ceramics', CeramicController::class)
    ->only(['index', 'show'])
    ->middleware($materialReadPermission);

// API untuk mendapatkan unique values per field - cats
Route::get('/api/cats/field-values/{field}', [CatController::class, 'getFieldValues'])
    ->middleware($materialReadPermission)
    ->name('cats.field-values');

// API untuk mendapatkan semua stores dari semua material
Route::get('/api/cats/all-stores', [CatController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('cats.all-stores');

Route::get('/api/sands/all-stores', [SandController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('sands.all-stores');

Route::get('/api/bricks/all-stores', [BrickController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('bricks.all-stores');

Route::get('/api/cements/all-stores', [CementController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('cements.all-stores');
Route::get('/api/nats/all-stores', [NatController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('nats.all-stores');

// API untuk mendapatkan alamat berdasarkan toko dari semua material
Route::get('/api/cats/addresses-by-store', [CatController::class, 'getAddressesByStore'])->name(
    'cats.addresses-by-store',
)->middleware($materialReadPermission);

Route::get('/api/sands/addresses-by-store', [SandController::class, 'getAddressesByStore'])->name(
    'sands.addresses-by-store',
)->middleware($materialReadPermission);

Route::get('/api/bricks/addresses-by-store', [BrickController::class, 'getAddressesByStore'])->name(
    'bricks.addresses-by-store',
)->middleware($materialReadPermission);

Route::get('/api/cements/addresses-by-store', [CementController::class, 'getAddressesByStore'])->name(
    'cements.addresses-by-store',
)->middleware($materialReadPermission);
Route::get('/api/nats/addresses-by-store', [NatController::class, 'getAddressesByStore'])->name(
    'nats.addresses-by-store',
)->middleware($materialReadPermission);

// API untuk mendapatkan unique values per field - Bricks
Route::get('/api/bricks/field-values/{field}', [BrickController::class, 'getFieldValues'])
    ->middleware($materialReadPermission)
    ->name('bricks.field-values');

// API untuk mendapatkan unique values per field - Cements
Route::get('/api/cements/field-values/{field}', [CementController::class, 'getFieldValues'])->name(
    'cements.field-values',
)->middleware($materialReadPermission);
Route::get('/api/nats/field-values/{field}', [NatController::class, 'getFieldValues'])
    ->middleware($materialReadPermission)
    ->name('nats.field-values');

// API untuk mendapatkan unique values per field - Sands
Route::get('/api/sands/field-values/{field}', [SandController::class, 'getFieldValues'])
    ->middleware($materialReadPermission)
    ->name('sands.field-values');

// --- Routes untuk Keramik (Ceramics) ---
// 1. API Helper Routes (Letakkan SEBELUM resource route agar tidak tertimpa 'show')
Route::get('/api/ceramics/all-stores', [CeramicController::class, 'getAllStores'])
    ->middleware($materialReadPermission)
    ->name('ceramics.all-stores');
Route::get('/api/ceramics/addresses-by-store', [CeramicController::class, 'getAddressesByStore'])->name(
    'ceramics.addresses-by-store',
)->middleware($materialReadPermission);
Route::get('/api/ceramics/field-values/{field}', [CeramicController::class, 'getFieldValues'])->name(
    'ceramics.field-values',
)->middleware($materialReadPermission);

// Material Calculator Routes
Route::prefix('material-calculations')
    ->name('material-calculations.')
    ->group(function () use ($calculationReadPermission, $calculationManagePermission) {
        Route::get('/', [MaterialCalculationPageController::class, 'indexRedirect'])
            ->middleware($calculationReadPermission)
            ->name('index');
        Route::get('/log', [MaterialCalculationPageController::class, 'log'])
            ->middleware($calculationReadPermission)
            ->name('log');
        Route::get('/create', [MaterialCalculationPageController::class, 'create'])
            ->middleware($calculationManagePermission)
            ->name('create');
        Route::post('/', [MaterialCalculationExecutionController::class, 'store'])
            ->middleware($calculationManagePermission)
            ->name('store');
        Route::get('/preview/{cacheKey}', [MaterialCalculationPageController::class, 'showPreview'])
            ->middleware($calculationReadPermission)
            ->name('preview');
        Route::get('/{materialCalculation}', [MaterialCalculationPageController::class, 'show'])
            ->middleware($calculationReadPermission)
            ->name('show');
        Route::get('/{materialCalculation}/edit', [MaterialCalculationPageController::class, 'edit'])
            ->middleware($calculationManagePermission)
            ->name('edit');
        Route::put('/{materialCalculation}', [MaterialCalculationExecutionController::class, 'update'])
            ->middleware($calculationManagePermission)
            ->name('update');
        Route::delete('/{materialCalculation}', [MaterialCalculationExecutionController::class, 'destroy'])->name(
            'destroy',
        )->middleware($calculationManagePermission);

        // Export
        Route::get('/{materialCalculation}/export-pdf', [MaterialCalculationPageController::class, 'exportPdf'])->name(
            'export-pdf',
        )->middleware($calculationReadPermission);
    });

// API Routes untuk real-time calculation
Route::prefix('api/material-calculator')
    ->name('api.material-calculator.')
    ->group(function () use ($calculationManagePermission) {
        Route::post('/calculate', [MaterialCalculationExecutionController::class, 'calculate'])
            ->middleware($calculationManagePermission)
            ->name('calculate');
        Route::post('/compare', [MaterialCalculationExecutionController::class, 'compare'])
            ->middleware($calculationManagePermission)
            ->name('compare');
        Route::post('/trace', [MaterialCalculationTraceController::class, 'traceCalculation'])
            ->middleware($calculationManagePermission)
            ->name('trace');
        Route::get('/brick-dimensions/{brickId}', [MaterialCalculationExecutionController::class, 'getBrickDimensions'])->name(
            'brick-dimensions',
        )->middleware($calculationManagePermission);
        Route::post('/ceramic-combinations', [MaterialCalculationExecutionController::class, 'getCeramicCombinations'])->name(
            'ceramic-combinations',
        )->middleware($calculationManagePermission);
    });

// Trace View - step by step
Route::get('/material-calculator/trace', [MaterialCalculationTraceController::class, 'traceView'])->name(
    'material-calculator.trace',
)->middleware($calculationReadPermission);

Route::prefix('settings/roles')
    ->middleware('permission:roles.manage')
    ->name('settings.roles.')
    ->group(function () {
        Route::get('/', [RoleManagementController::class, 'index'])->name('index');
        Route::post('/', [RoleManagementController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleManagementController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'destroy'])->name('destroy');
    });

Route::prefix('settings/users')
    ->middleware('permission:users.manage')
    ->name('settings.users.')
    ->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/registration', [UserManagementController::class, 'updateRegistration'])->name('registration.update');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

Route::resource('stores', StoreController::class)
    ->except(['index', 'show'])
    ->middleware($storeManagePermission);
Route::resource('stores', StoreController::class)
    ->only(['index', 'show'])
    ->middleware($storeReadPermission);
Route::get('stores/{store}/locations', [StoreController::class, 'locations'])
    ->middleware($storeReadPermission)
    ->name('stores.locations');

Route::get('/work-items/analytics/{code}', [WorkItemController::class, 'analytics'])
    ->middleware($workItemReadPermission)
    ->name('work-items.analytics');
Route::resource('work-items', WorkItemController::class)
    ->except(['index', 'show'])
    ->middleware($workItemManagePermission);
Route::resource('work-items', WorkItemController::class)
    ->only(['index', 'show'])
    ->middleware($workItemReadPermission);
Route::get('/workers', [WorkerController::class, 'index'])->middleware($workerViewPermission)->name('workers.index');
Route::get('/skills', [SkillController::class, 'index'])->middleware($skillViewPermission)->name('skills.index');

// Setting Rekomendasi Material (Rekomendasi)
Route::prefix('settings/recommendations')
    ->middleware($recommendationManagePermission)
    ->name('settings.recommendations.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\RecommendedCombinationController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\RecommendedCombinationController::class, 'store'])->name('store');
    });

Route::prefix('settings')
    ->name('settings.')
    ->group(function () use ($storeSearchRadiusManagePermission, $workTaxonomyManagePermission) {
        Route::middleware($storeSearchRadiusManagePermission)->group(function () {
            Route::get('/store-search-radius', [StoreSearchRadiusSettingController::class, 'index'])->name(
                'store-search-radius.index',
            );
            Route::post('/store-search-radius', [StoreSearchRadiusSettingController::class, 'store'])->name(
                'store-search-radius.store',
            );
        });

        Route::middleware($workTaxonomyManagePermission)->group(function () {
            Route::get('/work-floors', [WorkFloorController::class, 'index'])->name('work-floors.index');
            Route::post('/work-floors', [WorkFloorController::class, 'store'])->name('work-floors.store');
            Route::put('/work-floors/{workFloor}', [WorkFloorController::class, 'update'])->name('work-floors.update');
            Route::delete('/work-floors/{workFloor}', [WorkFloorController::class, 'destroy'])->name('work-floors.destroy');

            Route::get('/work-areas', [WorkAreaController::class, 'index'])->name('work-areas.index');
            Route::post('/work-areas', [WorkAreaController::class, 'store'])->name('work-areas.store');
            Route::put('/work-areas/{workArea}', [WorkAreaController::class, 'update'])->name('work-areas.update');
            Route::delete('/work-areas/{workArea}', [WorkAreaController::class, 'destroy'])->name('work-areas.destroy');

            Route::get('/work-fields', [WorkFieldController::class, 'index'])->name('work-fields.index');
            Route::post('/work-fields', [WorkFieldController::class, 'store'])->name('work-fields.store');
            Route::put('/work-fields/{workField}', [WorkFieldController::class, 'update'])->name('work-fields.update');
            Route::delete('/work-fields/{workField}', [WorkFieldController::class, 'destroy'])->name('work-fields.destroy');
        });
    });

// Store Location Routes
Route::prefix('stores/{store}/locations')
    ->name('store-locations.')
    ->group(function () use ($storeManagePermission, $storeReadPermission) {
        Route::get('/create', [StoreLocationController::class, 'create'])->middleware($storeManagePermission)->name('create');
        Route::post('/', [StoreLocationController::class, 'store'])->middleware($storeManagePermission)->name('store');
        Route::get('/{location}/edit', [StoreLocationController::class, 'edit'])->middleware($storeManagePermission)->name('edit');
        Route::put('/{location}', [StoreLocationController::class, 'update'])->middleware($storeManagePermission)->name('update');
        Route::delete('/{location}', [StoreLocationController::class, 'destroy'])->middleware($storeManagePermission)->name('destroy');
        Route::get('/{location}/materials', [StoreLocationController::class, 'materials'])->middleware($storeReadPermission)->name('materials');
        Route::get('/{location}/materials/tab/{type}', [StoreLocationController::class, 'fetchTab'])->name(
            'materials.tab',
        )->middleware($storeReadPermission);
    });
});
