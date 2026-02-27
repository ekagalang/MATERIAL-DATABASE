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
use App\Http\Controllers\SandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreSearchRadiusSettingController;
use App\Http\Controllers\WorkItemController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\StoreLocationController;
use App\Http\Controllers\WorkAreaController;
use App\Http\Controllers\WorkFieldController;
use App\Http\Controllers\WorkFloorController;
use App\Helpers\NumberHelper;
use App\Models\BrickCalculation;
// use App\Http\Controllers\Dev\PriceAnalysisController;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
Route::get('/log', [LogViewerController::class, 'index'])->name('logs.index');

// Tambahkan sementara untuk testing:
Route::get('/test-error/{code}', function ($code) {
    abort($code);
});

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
})->name('testing.number-formatting');

Route::resource('units', UnitController::class);
Route::resource('cats', CatController::class);
Route::resource('bricks', BrickController::class);
Route::get('/materials/type-suggestions', [MaterialController::class, 'typeSuggestions'])->name(
    'materials.type-suggestions',
);
Route::get('/materials/tab/{type}', [MaterialController::class, 'fetchTab'])->name('materials.tab');
Route::resource('materials', MaterialController::class);
Route::resource('cements', CementController::class);
Route::resource('nats', NatController::class);
Route::resource('sands', SandController::class);
Route::resource('ceramics', CeramicController::class);

// API untuk mendapatkan unique values per field - cats
Route::get('/api/cats/field-values/{field}', [CatController::class, 'getFieldValues'])->name('cats.field-values');

// API untuk mendapatkan semua stores dari semua material
Route::get('/api/cats/all-stores', [CatController::class, 'getAllStores'])->name('cats.all-stores');

Route::get('/api/sands/all-stores', [SandController::class, 'getAllStores'])->name('sands.all-stores');

Route::get('/api/bricks/all-stores', [BrickController::class, 'getAllStores'])->name('bricks.all-stores');

Route::get('/api/cements/all-stores', [CementController::class, 'getAllStores'])->name('cements.all-stores');
Route::get('/api/nats/all-stores', [NatController::class, 'getAllStores'])->name('nats.all-stores');

// API untuk mendapatkan alamat berdasarkan toko dari semua material
Route::get('/api/cats/addresses-by-store', [CatController::class, 'getAddressesByStore'])->name(
    'cats.addresses-by-store',
);

Route::get('/api/sands/addresses-by-store', [SandController::class, 'getAddressesByStore'])->name(
    'sands.addresses-by-store',
);

Route::get('/api/bricks/addresses-by-store', [BrickController::class, 'getAddressesByStore'])->name(
    'bricks.addresses-by-store',
);

Route::get('/api/cements/addresses-by-store', [CementController::class, 'getAddressesByStore'])->name(
    'cements.addresses-by-store',
);
Route::get('/api/nats/addresses-by-store', [NatController::class, 'getAddressesByStore'])->name(
    'nats.addresses-by-store',
);

// API untuk mendapatkan unique values per field - Bricks
Route::get('/api/bricks/field-values/{field}', [BrickController::class, 'getFieldValues'])->name('bricks.field-values');

// API untuk mendapatkan unique values per field - Cements
Route::get('/api/cements/field-values/{field}', [CementController::class, 'getFieldValues'])->name(
    'cements.field-values',
);
Route::get('/api/nats/field-values/{field}', [NatController::class, 'getFieldValues'])->name('nats.field-values');

// API untuk mendapatkan unique values per field - Sands
Route::get('/api/sands/field-values/{field}', [SandController::class, 'getFieldValues'])->name('sands.field-values');

// --- Routes untuk Keramik (Ceramics) ---
// 1. API Helper Routes (Letakkan SEBELUM resource route agar tidak tertimpa 'show')
Route::get('/api/ceramics/all-stores', [CeramicController::class, 'getAllStores'])->name('ceramics.all-stores');
Route::get('/api/ceramics/addresses-by-store', [CeramicController::class, 'getAddressesByStore'])->name(
    'ceramics.addresses-by-store',
);
Route::get('/api/ceramics/field-values/{field}', [CeramicController::class, 'getFieldValues'])->name(
    'ceramics.field-values',
);

// 2. Resource Routes (Index, Create, Store, Edit, Update, Destroy)
Route::resource('ceramics', CeramicController::class);

// Material Calculator Routes
Route::prefix('material-calculations')
    ->name('material-calculations.')
    ->group(function () {
        Route::get('/', [MaterialCalculationPageController::class, 'indexRedirect'])->name('index');
        Route::get('/log', [MaterialCalculationPageController::class, 'log'])->name('log');
        Route::get('/create', [MaterialCalculationPageController::class, 'create'])->name('create');
        Route::post('/', [MaterialCalculationExecutionController::class, 'store'])->name('store');
        Route::get('/preview/{cacheKey}', [MaterialCalculationPageController::class, 'showPreview'])->name('preview');
        Route::get('/{materialCalculation}', [MaterialCalculationPageController::class, 'show'])->name('show');
        Route::get('/{materialCalculation}/edit', [MaterialCalculationPageController::class, 'edit'])->name('edit');
        Route::put('/{materialCalculation}', [MaterialCalculationExecutionController::class, 'update'])->name('update');
        Route::delete('/{materialCalculation}', [MaterialCalculationExecutionController::class, 'destroy'])->name(
            'destroy',
        );

        // Export
        Route::get('/{materialCalculation}/export-pdf', [MaterialCalculationPageController::class, 'exportPdf'])->name(
            'export-pdf',
        );
    });

// API Routes untuk real-time calculation
Route::prefix('api/material-calculator')
    ->name('api.material-calculator.')
    ->group(function () {
        Route::post('/calculate', [MaterialCalculationExecutionController::class, 'calculate'])->name('calculate');
        Route::post('/compare', [MaterialCalculationExecutionController::class, 'compare'])->name('compare');
        Route::post('/trace', [MaterialCalculationTraceController::class, 'traceCalculation'])->name('trace');
        Route::get('/brick-dimensions/{brickId}', [MaterialCalculationExecutionController::class, 'getBrickDimensions'])->name(
            'brick-dimensions',
        );
        Route::post('/ceramic-combinations', [MaterialCalculationExecutionController::class, 'getCeramicCombinations'])->name(
            'ceramic-combinations',
        );
    });

// Trace View - step by step
Route::get('/material-calculator/trace', [MaterialCalculationTraceController::class, 'traceView'])->name(
    'material-calculator.trace',
);

Route::resource('stores', StoreController::class);
Route::get('stores/{store}/locations', [StoreController::class, 'locations'])->name('stores.locations');

Route::get('/work-items/analytics/{code}', [WorkItemController::class, 'analytics'])->name('work-items.analytics');
Route::resource('work-items', WorkItemController::class);
Route::get('/workers', [WorkerController::class, 'index'])->name('workers.index');
Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');

// Setting Rekomendasi Material (Rekomendasi)
Route::prefix('settings/recommendations')
    ->name('settings.recommendations.')
    ->group(function () {
        Route::get('/', [App\Http\Controllers\RecommendedCombinationController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\RecommendedCombinationController::class, 'store'])->name('store');
    });

Route::prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('/store-search-radius', [StoreSearchRadiusSettingController::class, 'index'])->name(
            'store-search-radius.index',
        );
        Route::post('/store-search-radius', [StoreSearchRadiusSettingController::class, 'store'])->name(
            'store-search-radius.store',
        );

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

// Store Location Routes
Route::prefix('stores/{store}/locations')
    ->name('store-locations.')
    ->group(function () {
        Route::get('/create', [StoreLocationController::class, 'create'])->name('create');
        Route::post('/', [StoreLocationController::class, 'store'])->name('store');
        Route::get('/{location}/edit', [StoreLocationController::class, 'edit'])->name('edit');
        Route::put('/{location}', [StoreLocationController::class, 'update'])->name('update');
        Route::delete('/{location}', [StoreLocationController::class, 'destroy'])->name('destroy');
        Route::get('/{location}/materials', [StoreLocationController::class, 'materials'])->name('materials');
        Route::get('/{location}/materials/tab/{type}', [StoreLocationController::class, 'fetchTab'])->name(
            'materials.tab',
        );
    });
