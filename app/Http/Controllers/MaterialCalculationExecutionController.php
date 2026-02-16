<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialCalculationExecutionController extends MaterialCalculationController
{
    public function store(Request $request)
    {
        // Increase execution time for complex calculations
        set_time_limit(300); // 5 minutes

        try {
            DB::beginTransaction();

            // Handle work_type_select from form and convert to work_type
            if ($request->has('work_type_select') && !$request->has('work_type')) {
                $request->merge(['work_type' => $request->work_type_select]);
            }

            // DEBUG: Store request data to session (AFTER conversion)
            session()->put('debug_last_request', [
                'work_type' => $request->work_type,
                'work_type_select' => $request->work_type_select,
                'price_filters' => $request->price_filters,
                'ceramic_types' => $request->ceramic_types,
                'ceramic_sizes' => $request->ceramic_sizes,
                'enable_bundle_mode' => $request->input('enable_bundle_mode'),
                'work_items_payload' => $request->input('work_items_payload'),
                'timestamp' => now()->toDateTimeString(),
            ]);

            $bundleItems = $this->parseBundleItemsPayload($request->input('work_items_payload'));
            if ($request->boolean('enable_bundle_mode')) {
                if (count($bundleItems) < 2) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Mode paket membutuhkan minimal 2 item pekerjaan.');
                }

                DB::rollBack();

                return $this->generateBundleCombinations($request, $bundleItems);
            }

            // CRITICAL: Validate work_type is not null
            if (empty($request->work_type)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with(
                        'error',
                        'Jenis Pekerjaan harus dipilih dari daftar. Mohon klik Item Pekerjaan dan pilih dari dropdown yang muncul (contoh: Pasang Keramik Lantai / Dinding).',
                    );
            }

            if (!$request->has('mortar_formula_type')) {
                $request->merge(['mortar_formula_type' => 'default']);
            }

            $this->normalizeNatIdentifiers($request);

            // 1. VALIDASI
            $rules = [
                'work_type' => 'required',
                'project_address' => 'nullable|string',
                'project_latitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-90,90',
                'project_longitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-180,180',
                'project_place_id' => 'nullable|string|max:255',
                'use_store_filter' => 'nullable|boolean',
                'allow_mixed_store' => 'nullable|boolean',
                'price_filters' => 'required|array|min:1',
                'price_filters.*' => 'in:all,best,common,cheapest,medium,expensive,custom',
                'material_type_filters' => 'nullable|array',
                'material_type_filters.*' => 'nullable',
                'material_type_filters.*.*' => 'nullable|string',
                'material_type_filters_extra' => 'nullable|array',
                'material_type_filters_extra.*' => 'nullable|array',
                'material_type_filters_extra.*.*' => 'nullable|string',
                'wall_length' => 'required|numeric|min:0.01',
                'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
                'mortar_thickness' => 'required|numeric|min:0.01',
                'layer_count' => 'nullable|integer|min:1',
                'plaster_sides' => 'nullable|integer|min:1',
                'skim_sides' => 'nullable|integer|min:1',
            ];

            // Remove default 'best' merge to force user selection
            // if (!$request->has('price_filters') || empty($request->price_filters)) {
            //    $request->merge(['price_filters' => ['best']]);
            // }

            // NEW LOGIC: Dynamic Material Validation based on Work Type
            $workType = $request->work_type;
            if ($workType === 'grout_tile') {
                $request->request->remove('ceramic_id');
            }
            $requiredMaterials = $this->resolveRequiredMaterials($workType);
            $needsBrick = in_array('brick', $requiredMaterials, true);
            $needsSand = in_array('sand', $requiredMaterials, true);
            $needsCement = in_array('cement', $requiredMaterials, true);
            $needsCat = in_array('cat', $requiredMaterials, true);
            $needsCeramic = in_array('ceramic', $requiredMaterials, true);
            $needsNat = in_array('nat', $requiredMaterials, true);

            // Brick Validation
            if (!$needsBrick) {
                $rules['brick_id'] = 'nullable';
                $rules['brick_ids'] = 'nullable|array';
            } else {
                $priceFilters = $request->price_filters ?? [];
                $hasCustom = in_array('custom', $priceFilters);
                $hasOtherFilters = count(array_diff($priceFilters, ['custom'])) > 0;

                if ($hasCustom && !$hasOtherFilters) {
                    if ($request->has('brick_ids')) {
                        $rules['brick_ids'] = 'required|array';
                        $rules['brick_ids.*'] = 'exists:bricks,id';
                    } else {
                        $rules['brick_id'] = 'required|exists:bricks,id';
                    }
                } else {
                    if ($request->has('brick_ids')) {
                        $rules['brick_ids'] = 'nullable|array';
                        $rules['brick_ids.*'] = 'exists:bricks,id';
                    } else {
                        $rules['brick_id'] = 'nullable|exists:bricks,id';
                    }
                }
            }

            if (!$needsSand) {
                $rules['sand_id'] = 'nullable';
            }

            if (!$needsCement) {
                $rules['cement_id'] = 'nullable';
            }

            // Ceramic Validation
            if ($needsCeramic) {
                if ($workType === 'grout_tile') {
                    $rules['ceramic_id'] = 'nullable|exists:ceramics,id';
                } elseif (in_array('custom', $request->price_filters ?? [])) {
                    $rules['ceramic_id'] = 'required|exists:ceramics,id';
                } else {
                    $rules['ceramic_id'] = 'nullable|exists:ceramics,id';
                }
            } else {
                $rules['ceramic_id'] = 'nullable';
            }

            // Nat Validation
            if ($needsNat) {
                if (in_array('custom', $request->price_filters ?? [])) {
                    $rules['nat_id'] = 'required|exists:nats,id';
                } else {
                    $rules['nat_id'] = 'nullable|exists:nats,id';
                }
            } else {
                $rules['nat_id'] = 'nullable';
            }

            $this->mergeMaterialTypeFilters($request);
            $request->validate($rules);

            // 2. SETUP DEFAULT
            $defaultInstallationType = BrickInstallationType::where('is_active', true)->orderBy('id')->first();

            $mortarFormulaType = $request->input('mortar_formula_type');
            if ($mortarFormulaType === 'custom') {
                $request->merge(['use_custom_ratio' => true]);
                $defaultMortarFormula = MortarFormula::where('is_active', true)->orderBy('id')->first();
            } else {
                $defaultMortarFormula = MortarFormula::where('is_active', true)
                    ->where('cement_ratio', 1)
                    ->where('sand_ratio', 3)
                    ->first();
                if (!$defaultMortarFormula) {
                    $defaultMortarFormula = MortarFormula::first();
                }
                $request->merge(['use_custom_ratio' => false]);
            }

            if (!$request->has('installation_type_id')) {
                $request->merge(['installation_type_id' => $defaultInstallationType?->id]);
            }
            if (!$request->has('mortar_formula_id')) {
                $request->merge(['mortar_formula_id' => $defaultMortarFormula?->id]);
            }

            // 3. AUTO SELECT MATERIAL OR GENERATE COMBINATIONS
            $priceFilters = $request->price_filters ?? [];
            $hasCustom = in_array('custom', $priceFilters);
            $hasOtherFilters = count(array_diff($priceFilters, ['custom'])) > 0;

            // Check if we need to generate combinations
            $isMultiBrick = $request->has('brick_ids') && count($request->brick_ids) > 0;
            $isCustomEmpty = false;

            if ($hasCustom) {
                foreach ($requiredMaterials as $material) {
                    if ($material === 'brick') {
                        continue;
                    }
                    $isMissing = false;
                    if ($material === 'nat') {
                        $isMissing = empty($request->nat_id);
                    } else {
                        $key = $material . '_id';
                        $isMissing = empty($request->$key);
                    }
                    if ($isMissing) {
                        $isCustomEmpty = true;
                        break;
                    }
                }
            }

            $needCombinations = $hasOtherFilters || $isMultiBrick || $hasCustom || $isCustomEmpty;

            if ($request->boolean('confirm_save')) {
                $needCombinations = false;
            }

            if ($needCombinations) {
                DB::rollBack();

                return $this->generateCombinations($request);
            }

            // 5. SAVE NORMAL
            $calculation = BrickCalculation::performCalculation($request->all());

            if (!$request->boolean('confirm_save')) {
                DB::rollBack();
                $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);

                return view('material_calculations.preview', [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                    'formData' => $request->all(),
                ]);
            }

            $calculation->save();
            DB::commit();

            return redirect()
                ->route('material-calculations.show', $calculation)
                ->with('success', 'Perhitungan berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    protected function parseBundleItemsPayload(mixed $rawPayload): array
    {
        if (!is_string($rawPayload) || trim($rawPayload) === '') {
            return [];
        }

        $decoded = json_decode($rawPayload, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $workType = trim((string) ($entry['work_type'] ?? ''));
            if ($workType === '') {
                continue;
            }

            $items[] = [
                'title' => trim((string) ($entry['title'] ?? '')),
                'work_type' => $workType,
                'wall_length' => $entry['wall_length'] ?? null,
                'wall_height' => $entry['wall_height'] ?? null,
                'mortar_thickness' => $entry['mortar_thickness'] ?? null,
                'layer_count' => $entry['layer_count'] ?? null,
                'plaster_sides' => $entry['plaster_sides'] ?? null,
                'skim_sides' => $entry['skim_sides'] ?? null,
                'grout_thickness' => $entry['grout_thickness'] ?? null,
                'ceramic_length' => $entry['ceramic_length'] ?? null,
                'ceramic_width' => $entry['ceramic_width'] ?? null,
                'ceramic_thickness' => $entry['ceramic_thickness'] ?? null,
                'material_type_filters' => $this->normalizeBundleMaterialTypeFilters(
                    $entry['material_type_filters'] ?? [],
                ),
            ];
        }

        return array_values($items);
    }

    protected function normalizeBundleMaterialTypeFilters(mixed $rawFilters): array
    {
        if (!is_array($rawFilters)) {
            return [];
        }

        $normalized = [];
        foreach ($rawFilters as $key => $value) {
            $materialKey = trim((string) $key);
            if ($materialKey === '') {
                continue;
            }

            $values = $this->normalizeMaterialTypeFilterValues($value);
            if (empty($values)) {
                continue;
            }

            $normalized[$materialKey] = count($values) === 1 ? $values[0] : array_values($values);
        }

        return $normalized;
    }

    protected function generateBundleCombinations(Request $request, array $bundleItems)
    {
        $bundleName = trim((string) $request->input('bundle_name', 'Paket Pekerjaan'));
        if ($bundleName === '') {
            $bundleName = 'Paket Pekerjaan';
        }

        $baseRequestData = $request->except([
            '_token',
            'confirm_save',
            'work_items_payload',
            'enable_bundle_mode',
            'bundle_name',
            'work_type',
            'work_type_select',
            'wall_length',
            'wall_height',
            'mortar_thickness',
            'layer_count',
            'plaster_sides',
            'skim_sides',
            'grout_thickness',
            'ceramic_length',
            'ceramic_width',
            'ceramic_thickness',
            'brick_id',
            'brick_ids',
            'cement_id',
            'sand_id',
            'cat_id',
            'ceramic_id',
            'nat_id',
        ]);

        $defaultInstallationType = BrickInstallationType::where('is_active', true)->orderBy('id')->first();
        $defaultMortarFormula = MortarFormula::where('is_active', true)
            ->where('cement_ratio', 1)
            ->where('sand_ratio', 3)
            ->first();
        if (!$defaultMortarFormula) {
            $defaultMortarFormula = MortarFormula::first();
        }

        $bundleItemPayloads = [];
        foreach ($bundleItems as $index => $bundleItem) {
            $itemTitle = trim((string) ($bundleItem['title'] ?? ''));
            if ($itemTitle === '') {
                $itemTitle = 'Item ' . ($index + 1);
            }

            $itemRequestData = array_merge($baseRequestData, [
                'work_type' => $bundleItem['work_type'],
                'work_type_select' => $bundleItem['work_type'],
                'wall_length' => $bundleItem['wall_length'],
                'wall_height' => $bundleItem['wall_height'],
                'mortar_thickness' => $bundleItem['mortar_thickness'],
                'layer_count' => $bundleItem['layer_count'] ?? 1,
                'plaster_sides' => $bundleItem['plaster_sides'] ?? 1,
                'skim_sides' => $bundleItem['skim_sides'] ?? 1,
                'grout_thickness' => $bundleItem['grout_thickness'] ?? null,
                'ceramic_length' => $bundleItem['ceramic_length'] ?? null,
                'ceramic_width' => $bundleItem['ceramic_width'] ?? null,
                'ceramic_thickness' => $bundleItem['ceramic_thickness'] ?? null,
                'installation_type_id' => $request->input('installation_type_id') ?? $defaultInstallationType?->id,
                'mortar_formula_id' => $request->input('mortar_formula_id') ?? $defaultMortarFormula?->id,
            ]);
            $itemMaterialTypeFilters = $this->normalizeBundleMaterialTypeFilters(
                $bundleItem['material_type_filters'] ?? [],
            );
            if (!empty($itemMaterialTypeFilters)) {
                $itemRequestData['material_type_filters'] = $itemMaterialTypeFilters;
                $itemRequestData['material_type_filters_extra'] = [];
            } else {
                unset($itemRequestData['material_type_filters'], $itemRequestData['material_type_filters_extra']);
            }

            $itemRequest = Request::create('/material-calculations', 'POST', $itemRequestData);
            $this->normalizeNatIdentifiers($itemRequest);
            $this->mergeMaterialTypeFilters($itemRequest);

            // Generate + cache preview payload per item using existing engine
            $this->generateCombinations($itemRequest);
            $itemCacheKey = $this->buildCalculationCacheKey($itemRequest);
            $itemPayload = $this->getCalculationCachePayload($itemCacheKey);

            if (!$itemPayload || !is_array($itemPayload)) {
                continue;
            }

            $itemPayload['title'] = $itemTitle;
            $itemPayload['work_type'] = $bundleItem['work_type'];
            $bundleItemPayloads[] = $itemPayload;
        }

        if (empty($bundleItemPayloads)) {
            return redirect()
                ->route('material-calculations.create')
                ->withInput()
                ->with(
                    'error',
                    'Tidak dapat menghasilkan kombinasi untuk item pekerjaan paket. Periksa data tiap item.',
                );
        }

        if (count($bundleItemPayloads) !== count($bundleItems)) {
            return redirect()
                ->route('material-calculations.create')
                ->withInput()
                ->with(
                    'error',
                    'Sebagian item paket gagal dihitung. Pastikan setiap item memiliki dimensi dan material yang valid.',
                );
        }

        $bundleCombinations = $this->buildBundleSummaryCombinations(
            $bundleItemPayloads,
            $request->input('price_filters', ['best']),
        );

        if (empty($bundleCombinations)) {
            return redirect()
                ->route('material-calculations.create')
                ->withInput()
                ->with(
                    'error',
                    'Tidak ada kombinasi lengkap untuk seluruh item paket pada jangkauan toko yang tersedia.',
                );
        }

        $bundleProjects = $this->buildBundleProjectsPayload($bundleCombinations);
        if (empty($bundleProjects)) {
            return redirect()
                ->route('material-calculations.create')
                ->withInput()
                ->with(
                    'error',
                    'Kombinasi paket berhasil dihitung tetapi gagal dipetakan ke tampilan preview.',
                );
        }

        $bundleWorkTypes = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn($item) => trim((string) ($item['work_type'] ?? ($item['requestData']['work_type'] ?? ''))),
                        $bundleItemPayloads,
                    ),
                    static fn($workType) => $workType !== '',
                ),
            ),
        );
        $primaryWorkType =
            $bundleWorkTypes[0] ??
            trim((string) ($request->input('work_type') ?? $request->input('work_type_select') ?? 'brick_half'));
        if ($primaryWorkType === '') {
            $primaryWorkType = 'brick_half';
        }

        $bundlePayload = [
            'projects' => $bundleProjects,
            'ceramicProjects' => [],
            'formulaName' => $bundleName,
            'isBrickless' => empty($bundleProjects[0]['brick'] ?? null),
            'is_bundle' => true,
            'requestData' => array_merge(
                $request->except(['_token']),
                [
                    'work_type' => $primaryWorkType,
                    'enable_bundle_mode' => true,
                    'bundle_name' => $bundleName,
                    'work_items_payload' => json_encode($bundleItems),
                    'bundle_work_types' => $bundleWorkTypes,
                ],
            ),
        ];

        $bundleCacheSeed = [
            'bundle_name' => $bundleName,
            'price_filters' => $request->input('price_filters', []),
            'work_items' => $bundleItems,
            'project_address' => $request->input('project_address'),
            'project_latitude' => $request->input('project_latitude'),
            'project_longitude' => $request->input('project_longitude'),
        ];
        $bundleCacheKey =
            self::CALCULATION_CACHE_KEY_PREFIX . 'bundle:' . hash('sha256', json_encode($bundleCacheSeed));

        $this->storeCalculationCachePayload($bundleCacheKey, $bundlePayload);

        return redirect()->route('material-calculations.preview', ['cacheKey' => $bundleCacheKey]);
    }

    protected function buildBundleSummaryCombinations(array $bundleItemPayloads, array $priceFilters): array
    {
        $requestedFilters = is_array($priceFilters) ? $priceFilters : [$priceFilters];
        $requestedFilters = array_map(static fn($filter) => strtolower(trim((string) $filter)), $requestedFilters);
        $requestedFilters = array_values(array_filter($requestedFilters, static fn($filter) => $filter !== ''));
        if (in_array('all', $requestedFilters, true)) {
            $requestedFilters = ['best', 'common', 'cheapest', 'medium', 'expensive'];
        }
        if (empty($requestedFilters)) {
            $requestedFilters = ['best'];
        }

        $labelPrefixes = [
            'best' => 'Preferensi',
            'common' => 'Populer',
            'cheapest' => 'Ekonomis',
            'medium' => 'Average',
            'expensive' => 'Termahal',
            'custom' => 'Custom',
        ];

        $candidateLabels = [];
        foreach ($requestedFilters as $filter) {
            $prefix = $labelPrefixes[$filter] ?? null;
            if (!$prefix) {
                continue;
            }
            $maxRank = $prefix === 'Custom' ? 1 : 3;
            for ($i = 1; $i <= $maxRank; $i++) {
                $candidateLabels[] = $prefix . ' ' . $i;
            }
        }
        $candidateLabels = array_values(array_unique($candidateLabels));

        $itemCombinationMaps = [];
        foreach ($bundleItemPayloads as $bundleItemPayload) {
            $itemCombinationMaps[] = $this->extractBestCombinationMapForPayload($bundleItemPayload);
        }

        $bundleCombinations = [];
        foreach ($candidateLabels as $label) {
            $selectedItems = [];
            $isComplete = true;

            foreach ($bundleItemPayloads as $index => $bundleItemPayload) {
                $itemMap = $itemCombinationMaps[$index] ?? [];
                $selected = $itemMap[$label] ?? null;
                if (!$selected) {
                    $isComplete = false;
                    break;
                }

                $selectedItems[] = [
                    'title' => $bundleItemPayload['title'] ?? ('Item ' . ($index + 1)),
                    'work_type' => $bundleItemPayload['work_type'] ?? ($bundleItemPayload['requestData']['work_type'] ?? ''),
                    'combination' => $selected,
                ];
            }

            if (!$isComplete) {
                continue;
            }

            $bundleCombinations[$label] = [$this->buildBundleAggregatedCombination($label, $selectedItems)];
        }

        uasort($bundleCombinations, function ($a, $b) {
            $totalA = (float) ($a[0]['result']['grand_total'] ?? PHP_FLOAT_MAX);
            $totalB = (float) ($b[0]['result']['grand_total'] ?? PHP_FLOAT_MAX);

            return $totalA <=> $totalB;
        });

        return $bundleCombinations;
    }

    protected function buildBundleProjectsPayload(array $bundleCombinations): array
    {
        if (empty($bundleCombinations)) {
            return [];
        }

        $displayBrick = null;
        foreach ($bundleCombinations as $rows) {
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (!empty($row['brick'])) {
                    $displayBrick = $row['brick'];
                    break 2;
                }
            }
        }

        return [
            [
                'brick' => $displayBrick,
                'combinations' => $bundleCombinations,
            ],
        ];
    }

    protected function buildBundleAggregatedCombination(string $label, array $selectedItems): array
    {
        $combinations = array_values(
            array_filter(
                array_map(static fn($item) => $item['combination'] ?? null, $selectedItems),
                static fn($item) => is_array($item) && !empty($item),
            ),
        );

        $aggregateResult = $this->aggregateBundleResultValues($combinations);

        $brick = $this->pickBundleMaterialModel($combinations, 'brick', 'total_brick_price');
        $cement = $this->pickBundleMaterialModel($combinations, 'cement', 'total_cement_price');
        $sand = $this->pickBundleMaterialModel($combinations, 'sand', 'total_sand_price');
        $cat = $this->pickBundleMaterialModel($combinations, 'cat', 'total_cat_price');
        $ceramic = $this->pickBundleMaterialModel($combinations, 'ceramic', 'total_ceramic_price');
        $nat = $this->pickBundleMaterialModel($combinations, 'nat', 'total_grout_price');

        if ($brick) {
            $aggregateResult['brick_id'] = $brick->id ?? null;
        }
        if ($cement) {
            $aggregateResult['cement_id'] = $cement->id ?? null;
        }
        if ($sand) {
            $aggregateResult['sand_id'] = $sand->id ?? null;
        }
        if ($cat) {
            $aggregateResult['cat_id'] = $cat->id ?? null;
        }
        if ($ceramic) {
            $aggregateResult['ceramic_id'] = $ceramic->id ?? null;
        }
        if ($nat) {
            $aggregateResult['nat_id'] = $nat->id ?? null;
        }

        $itemRows = [];
        foreach ($selectedItems as $index => $selectedItem) {
            $combo = $selectedItem['combination'] ?? [];
            $itemRows[] = [
                'title' => $selectedItem['title'] ?? ('Item ' . ($index + 1)),
                'work_type' => $selectedItem['work_type'] ?? '',
                'label' => $label,
                'grand_total' => (float) ($combo['result']['grand_total'] ?? 0),
            ];
        }

        return [
            'filter_label' => $label,
            'source_filters' => ['bundle'],
            'bundle_items' => $itemRows,
            'brick' => $brick,
            'cement' => $cement,
            'sand' => $sand,
            'cat' => $cat,
            'ceramic' => $ceramic,
            'nat' => $nat,
            'result' => $aggregateResult,
            'total_cost' => (float) ($aggregateResult['grand_total'] ?? 0),
        ];
    }

    protected function aggregateBundleResultValues(array $combinations): array
    {
        $nonAdditiveKeys = [
            'brick_price_per_piece',
            'cement_price_per_sak',
            'sand_price_per_m3',
            'cat_price_per_package',
            'ceramic_price_per_package',
            'grout_price_per_package',
            'tiles_per_package',
        ];

        $aggregate = [];
        foreach ($combinations as $combination) {
            $result = $combination['result'] ?? [];
            if (!is_array($result)) {
                continue;
            }
            foreach ($result as $key => $value) {
                if (!is_numeric($value) || in_array($key, $nonAdditiveKeys, true)) {
                    continue;
                }
                $aggregate[$key] = (float) ($aggregate[$key] ?? 0) + (float) $value;
            }
        }

        $aggregate['total_bricks'] = (float) ($aggregate['total_bricks'] ?? 0);
        $aggregate['total_brick_price'] = (float) ($aggregate['total_brick_price'] ?? 0);
        $aggregate['cement_sak'] = (float) ($aggregate['cement_sak'] ?? 0);
        $aggregate['total_cement_price'] = (float) ($aggregate['total_cement_price'] ?? 0);
        $aggregate['sand_m3'] = (float) ($aggregate['sand_m3'] ?? 0);
        $aggregate['total_sand_price'] = (float) ($aggregate['total_sand_price'] ?? 0);
        $aggregate['cat_packages'] = (float) ($aggregate['cat_packages'] ?? 0);
        $aggregate['total_cat_price'] = (float) ($aggregate['total_cat_price'] ?? 0);
        $aggregate['total_tiles'] = (float) ($aggregate['total_tiles'] ?? 0);
        $aggregate['tiles_packages'] = (float) ($aggregate['tiles_packages'] ?? 0);
        $aggregate['total_ceramic_price'] = (float) ($aggregate['total_ceramic_price'] ?? 0);
        $aggregate['grout_packages'] = (float) ($aggregate['grout_packages'] ?? 0);
        $aggregate['total_grout_price'] = (float) ($aggregate['total_grout_price'] ?? 0);

        $aggregate['brick_price_per_piece'] =
            $aggregate['total_bricks'] > 0 ? $aggregate['total_brick_price'] / $aggregate['total_bricks'] : 0;
        $aggregate['cement_price_per_sak'] =
            $aggregate['cement_sak'] > 0 ? $aggregate['total_cement_price'] / $aggregate['cement_sak'] : 0;
        $aggregate['sand_price_per_m3'] =
            $aggregate['sand_m3'] > 0 ? $aggregate['total_sand_price'] / $aggregate['sand_m3'] : 0;
        $aggregate['cat_price_per_package'] =
            $aggregate['cat_packages'] > 0 ? $aggregate['total_cat_price'] / $aggregate['cat_packages'] : 0;
        $aggregate['ceramic_price_per_package'] =
            $aggregate['tiles_packages'] > 0 ? $aggregate['total_ceramic_price'] / $aggregate['tiles_packages'] : 0;
        $aggregate['grout_price_per_package'] =
            $aggregate['grout_packages'] > 0 ? $aggregate['total_grout_price'] / $aggregate['grout_packages'] : 0;

        $aggregate['grand_total'] = array_sum(
            [
                $aggregate['total_brick_price'],
                $aggregate['total_cement_price'],
                $aggregate['total_sand_price'],
                $aggregate['total_cat_price'],
                $aggregate['total_ceramic_price'],
                $aggregate['total_grout_price'],
            ],
        );
        if ($aggregate['grand_total'] <= 0) {
            $aggregate['grand_total'] = array_sum(
                array_map(static fn($combination) => (float) ($combination['result']['grand_total'] ?? 0), $combinations),
            );
        }

        return $aggregate;
    }

    protected function pickBundleMaterialModel(array $combinations, string $materialKey, string $priceKey): mixed
    {
        $ranked = [];
        foreach ($combinations as $combination) {
            $model = $combination[$materialKey] ?? null;
            if (!$model || !isset($model->id)) {
                continue;
            }

            $id = (int) $model->id;
            if (!isset($ranked[$id])) {
                $ranked[$id] = [
                    'model' => $model,
                    'score' => 0.0,
                ];
            }
            $ranked[$id]['score'] += (float) ($combination['result'][$priceKey] ?? 0);
        }

        if (empty($ranked)) {
            return null;
        }

        uasort($ranked, static fn($a, $b) => $b['score'] <=> $a['score']);
        $top = reset($ranked);

        return $top['model'] ?? null;
    }

    protected function extractBestCombinationMapForPayload(array $payload): array
    {
        $map = [];
        $projects = $payload['projects'] ?? [];
        if (!is_array($projects)) {
            return $map;
        }

        foreach ($projects as $project) {
            $combinations = $project['combinations'] ?? [];
            if (!is_array($combinations)) {
                continue;
            }
            foreach ($combinations as $label => $items) {
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $candidate) {
                    if (!is_array($candidate) || empty($candidate['result']) || !is_array($candidate['result'])) {
                        continue;
                    }
                    $grandTotal = (float) ($candidate['result']['grand_total'] ?? 0);
                    if (!isset($map[$label])) {
                        $map[$label] = $candidate;
                        continue;
                    }
                    $existingTotal = (float) ($map[$label]['result']['grand_total'] ?? PHP_FLOAT_MAX);
                    if ($grandTotal < $existingTotal) {
                        $map[$label] = $candidate;
                    }
                }
            }
        }

        return $map;
    }

    public function update(Request $request, BrickCalculation $materialCalculation)
    {
        $request->validate($this->updateValidationRules());

        try {
            DB::beginTransaction();
            $newCalculation = BrickCalculation::performCalculation($request->all());
            $materialCalculation->fill($newCalculation->toArray());
            $materialCalculation->save();
            DB::commit();

            return redirect()
                ->route('material-calculations.show', $materialCalculation)
                ->with('success', 'Perhitungan berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(BrickCalculation $materialCalculation)
    {
        try {
            $materialCalculation->delete();

            return redirect()->route('material-calculations.log')->with('success', 'Perhitungan berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus perhitungan: ' . $e->getMessage());
        }
    }

    public function calculate(Request $request)
    {
        // Increase execution time for complex calculations
        set_time_limit(300); // 5 minutes

        $request->validate($this->calculateValidationRules());

        try {
            $calculation = BrickCalculation::performCalculation($request->all());
            $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);
            $summary = $calculation->getSummary();

            return response()->json(['success' => true, 'data' => $calculation, 'summary' => $summary]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function compare(Request $request)
    {
        $request->validate($this->compareValidationRules());

        try {
            $installationTypes = \App\Models\BrickInstallationType::getActive();
            $comparisons = [];
            foreach ($installationTypes as $type) {
                $params = array_merge($request->all(), ['installation_type_id' => $type->id]);
                $calculation = BrickCalculation::performCalculation($params);
                $comparisons[] = [
                    'installation_type' => $type->name,
                    'brick_quantity' => $calculation->brick_quantity,
                    'mortar_volume' => $calculation->mortar_volume,
                    'cement_50kg' => $calculation->cement_quantity_50kg,
                    'sand_m3' => $calculation->sand_m3,
                    'water_liters' => $calculation->water_liters,
                    'total_cost' => $calculation->total_material_cost,
                ];
            }

            return response()->json(['success' => true, 'data' => $comparisons]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getBrickDimensions($brickId)
    {
        try {
            $brick = Brick::findOrFail($brickId);

            return response()->json([
                'success' => true,
                'data' => [
                    'length' => $brick->dimension_length,
                    'width' => $brick->dimension_width,
                    'height' => $brick->dimension_height,
                    'price_per_piece' => $brick->price_per_piece,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bata tidak ditemukan'], 404);
        }
    }

    public function getCeramicCombinations(Request $request)
    {
        try {
            // Ensure work_type is present (convert from work_type_select if needed)
            if ($request->has('work_type_select') && !$request->has('work_type')) {
                $request->merge(['work_type' => $request->work_type_select]);
            }

            \Log::info('getCeramicCombinations', [
                'work_type' => $request->work_type,
                'work_type_select' => $request->work_type_select,
                'has_ceramic_id' => $request->has('ceramic_id'),
                'has_type' => $request->has('type'),
                'has_size' => $request->has('size'),
            ]);

            $brick = $this->resolveFallbackBrick(); // Dummy brick for ceramic work

            if ($request->has('type') && $request->has('size')) {
                // GROUP MODE: Compare all brands within this size
                $type = $request->type;
                $size = $request->size; // e.g., "30x30"
                $dims = explode('x', $size);
                $dim1 = isset($dims[0]) ? trim($dims[0]) : 0;
                $dim2 = isset($dims[1]) ? trim($dims[1]) : 0;

                // Find all ceramics matching type and dimensions (flexible LxW or WxL)
                $ceramics = Ceramic::where('type', $type)
                    ->where(function ($q) use ($dim1, $dim2) {
                        $q->where(function ($sq) use ($dim1, $dim2) {
                            $sq->where('dimension_length', $dim1)->where('dimension_width', $dim2);
                        })->orWhere(function ($sq) use ($dim1, $dim2) {
                            $sq->where('dimension_length', $dim2)->where('dimension_width', $dim1);
                        });
                    })
                    ->orderBy('brand')
                    ->get();

                if ($ceramics->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Data keramik tidak ditemukan'], 404);
                }

                $combinations = $this->calculateCombinationsForCeramicGroup($brick, $request, $ceramics);

                \Log::info('Combinations calculated (GROUP)', [
                    'type' => $type,
                    'size' => $size,
                    'ceramics_count' => $ceramics->count(),
                    'combinations_count' => count($combinations),
                    'combinations_keys' => array_keys($combinations),
                ]);

                $contextCeramic = $ceramics->first(); // Context for view
                $isGroupMode = true;
            } else {
                // SINGLE MODE: Specific ceramic ID
                $ceramicId = $request->ceramic_id;
                $ceramic = Ceramic::findOrFail($ceramicId);
                $ceramics = collect([$ceramic]);

                $combinations = $this->combinationGenerationService->calculateCombinationsForBrick(
                    $brick,
                    $request,
                    $ceramic,
                );
                $contextCeramic = $ceramic;
                $isGroupMode = false;
            }

            // Return HTML fragment for the combinations table
            return response()->json([
                'success' => true,
                'html' => view('material_calculations.partials.ceramic_combinations_table', [
                    'ceramic' => $contextCeramic,
                    'combinations' => $combinations,
                    'requestData' => array_merge(
                        $request->except(['ceramic_id', '_token']),
                        ['work_type' => $request->work_type], // Explicitly include work_type
                    ),
                    'isGroupMode' => $isGroupMode,
                ])->render(),
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine(),
                ],
                500,
            );
        }
    }

    private function baseCalculationValidationRules(): array
    {
        return [
            'work_type' => 'nullable|string',
            'project_address' => 'nullable|string',
            'project_latitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-90,90',
            'project_longitude' => 'required_if:use_store_filter,1|nullable|numeric|between:-180,180',
            'project_place_id' => 'nullable|string|max:255',
            'use_store_filter' => 'nullable|boolean',
            'allow_mixed_store' => 'nullable|boolean',
            'wall_length' => 'required|numeric|min:0.01',
            'wall_height' => 'required_unless:work_type,brick_rollag|numeric|min:0.01',
            'mortar_thickness' => 'required|numeric|min:0.01|max:10',
            'mortar_formula_id' => 'required|exists:mortar_formulas,id',
            'brick_id' => 'nullable|exists:bricks,id',
            'cement_id' => 'nullable|exists:cements,id',
            'sand_id' => 'nullable|exists:sands,id',
            'layer_count' => 'nullable|integer|min:1',
        ];
    }

    private function calculateValidationRules(): array
    {
        return array_merge($this->baseCalculationValidationRules(), [
            'installation_type_id' => 'required|exists:brick_installation_types,id',
        ]);
    }

    private function updateValidationRules(): array
    {
        return array_merge($this->baseCalculationValidationRules(), [
            'work_type' => 'required|string',
            'installation_type_id' => 'required|exists:brick_installation_types,id',
            'project_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'plaster_sides' => 'nullable|integer|min:1',
            'skim_sides' => 'nullable|integer|min:1',
        ]);
    }

    private function compareValidationRules(): array
    {
        return $this->baseCalculationValidationRules();
    }
}
