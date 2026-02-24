<?php

namespace App\Http\Controllers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\MortarFormula;
use App\Models\Nat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            $this->mergeWorkTaxonomyFilters($request);
            $workFloors = $this->normalizeWorkTaxonomyValues($request->input('work_floors', []));
            $workAreas = $this->normalizeWorkTaxonomyValues($request->input('work_areas', []));
            $workFields = $this->normalizeWorkTaxonomyValues($request->input('work_fields', []));
            if ($request->boolean('enable_bundle_mode')) {
                if (count($bundleItems) < 2) {
                    DB::rollBack();

                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Mode paket membutuhkan minimal 2 item pekerjaan.');
                }

                if ($request->boolean('confirm_save')) {
                    $bundleCalculation = $this->buildBundleCalculationFromSelection($request, $bundleItems);
                    if (!$bundleCalculation) {
                        DB::rollBack();

                        return redirect()
                            ->back()
                            ->withInput()
                            ->with(
                                'error',
                                'Data kombinasi paket terpilih tidak ditemukan. Silakan pilih ulang kombinasi dari halaman preview.',
                            );
                    }

                    foreach ($bundleItems as $bundleItem) {
                        $bundleWorkType = trim((string) ($bundleItem['work_type'] ?? ''));
                        if ($bundleWorkType !== '') {
                            $bundleItemFloors = $this->normalizeWorkTaxonomyValues(
                                $bundleItem['work_floors'] ?? ($bundleItem['work_floor'] ?? $workFloors),
                            );
                            $bundleItemAreas = $this->normalizeWorkTaxonomyValues(
                                $bundleItem['work_areas'] ?? ($bundleItem['work_area'] ?? $workAreas),
                            );
                            $bundleItemFields = $this->normalizeWorkTaxonomyValues(
                                $bundleItem['work_fields'] ?? ($bundleItem['work_field'] ?? $workFields),
                            );
                            $this->persistWorkItemTaxonomy(
                                $bundleWorkType,
                                $bundleItemFloors,
                                $bundleItemAreas,
                                $bundleItemFields,
                            );
                        }
                    }
                    $bundleCalculation->save();
                    DB::commit();

                    return redirect()
                        ->route('material-calculations.show', $bundleCalculation)
                        ->with('success', 'Perhitungan paket berhasil disimpan!');
                }

                DB::rollBack();
                foreach ($bundleItems as $bundleItem) {
                    $bundleWorkType = trim((string) ($bundleItem['work_type'] ?? ''));
                    if ($bundleWorkType !== '') {
                        $bundleItemFloors = $this->normalizeWorkTaxonomyValues(
                            $bundleItem['work_floors'] ?? ($bundleItem['work_floor'] ?? $workFloors),
                        );
                        $bundleItemAreas = $this->normalizeWorkTaxonomyValues(
                            $bundleItem['work_areas'] ?? ($bundleItem['work_area'] ?? $workAreas),
                        );
                        $bundleItemFields = $this->normalizeWorkTaxonomyValues(
                            $bundleItem['work_fields'] ?? ($bundleItem['work_field'] ?? $workFields),
                        );
                        $this->persistWorkItemTaxonomy(
                            $bundleWorkType,
                            $bundleItemFloors,
                            $bundleItemAreas,
                            $bundleItemFields,
                        );
                    }
                }

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
                'material_customize_filters_payload' => 'nullable|string',
                'material_customize_filters' => 'nullable|array',
                'work_floors' => 'nullable|array',
                'work_floors.*' => 'nullable|string|max:120',
                'work_areas' => 'nullable|array',
                'work_areas.*' => 'nullable|string|max:120',
                'work_fields' => 'nullable|array',
                'work_fields.*' => 'nullable|string|max:120',
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
                    $rules['nat_id'] = [
                        'required',
                        Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND),
                    ];
                } else {
                    $rules['nat_id'] = [
                        'nullable',
                        Rule::exists('cements', 'id')->where('material_kind', Nat::MATERIAL_KIND),
                    ];
                }
            } else {
                $rules['nat_id'] = 'nullable';
            }

            $this->mergeMaterialTypeFilters($request);
            $this->mergeMaterialCustomizeFilters($request);
            $this->mergeWorkTaxonomyFilters($request);
            $request->validate($rules);
            $workFloors = $this->normalizeWorkTaxonomyValues($request->input('work_floors', []));
            $workAreas = $this->normalizeWorkTaxonomyValues($request->input('work_areas', []));
            $workFields = $this->normalizeWorkTaxonomyValues($request->input('work_fields', []));

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
                $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);

                return $this->generateCombinations($request);
            }

            // 5. SAVE NORMAL
            $calculation = BrickCalculation::performCalculation($request->all());

            if (!$request->boolean('confirm_save')) {
                DB::rollBack();
                $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);
                $calculation->load(['installationType', 'mortarFormula', 'brick', 'cement', 'sand', 'cat']);

                return view('material_calculations.preview', [
                    'calculation' => $calculation,
                    'summary' => $calculation->getSummary(),
                    'formData' => $request->all(),
                ]);
            }

            $this->persistWorkItemTaxonomy((string) $request->work_type, $workFloors, $workAreas, $workFields);
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
            $rowKind = strtolower(trim((string) ($entry['row_kind'] ?? 'area')));
            if (!in_array($rowKind, ['area', 'field', 'item'], true)) {
                $rowKind = 'area';
            }

            $items[] = [
                'title' => trim((string) ($entry['title'] ?? '')),
                'row_kind' => $rowKind,
                'work_floor' => trim((string) ($entry['work_floor'] ?? '')),
                'work_area' => trim((string) ($entry['work_area'] ?? '')),
                'work_field' => trim((string) ($entry['work_field'] ?? '')),
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
                'material_customize_filters' => $this->normalizeBundleMaterialCustomizeFilters(
                    $entry['material_customize_filters'] ?? [],
                ),
            ];
        }

        return array_values($items);
    }

    protected function buildBundleCalculationFromSelection(Request $request, array $bundleItems): ?BrickCalculation
    {
        $rawSelectedResult = $request->input('bundle_selected_result');
        if (!is_string($rawSelectedResult) || trim($rawSelectedResult) === '') {
            return null;
        }

        $selectedResult = json_decode($rawSelectedResult, true);
        if (!is_array($selectedResult)) {
            $decodedHtml = html_entity_decode($rawSelectedResult, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $selectedResult = json_decode($decodedHtml, true);
        }
        if (!is_array($selectedResult)) {
            return null;
        }

        $bundleMaterialRows = [];
        $rawBundleMaterialRows = $request->input('bundle_material_rows');
        if (is_string($rawBundleMaterialRows) && trim($rawBundleMaterialRows) !== '') {
            $decodedBundleRows = json_decode($rawBundleMaterialRows, true);
            if (!is_array($decodedBundleRows)) {
                $decodedBundleRows = json_decode(
                    html_entity_decode($rawBundleMaterialRows, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    true,
                );
            }
            if (is_array($decodedBundleRows)) {
                $bundleMaterialRows = array_values(
                    array_filter($decodedBundleRows, static fn($row) => is_array($row)),
                );
            }
        } elseif (is_array($rawBundleMaterialRows)) {
            $bundleMaterialRows = array_values(
                array_filter($rawBundleMaterialRows, static fn($row) => is_array($row)),
            );
        }

        $n = static fn($value): float => (float) ($value ?? 0);
        $parse = static fn($value): float => (float) (\App\Helpers\NumberHelper::parseNullable($value) ?? 0);

        $defaultInstallationType = BrickInstallationType::where('is_active', true)->orderBy('id')->first();
        $defaultMortarFormula = MortarFormula::where('is_active', true)
            ->where('cement_ratio', 1)
            ->where('sand_ratio', 3)
            ->first();
        if (!$defaultMortarFormula) {
            $defaultMortarFormula = MortarFormula::first();
        }

        $installationTypeId = (int) ($request->input('installation_type_id') ?: ($defaultInstallationType?->id ?? 1));
        $mortarFormulaId = (int) ($request->input('mortar_formula_id') ?: ($defaultMortarFormula?->id ?? 1));
        $mortarThickness = $parse($request->input('mortar_thickness')) > 0 ? $parse($request->input('mortar_thickness')) : 1;

        $bundleTotalArea = 0.0;
        foreach ($bundleItems as $bundleItem) {
            if (!is_array($bundleItem)) {
                continue;
            }
            $itemLength = $parse($bundleItem['wall_length'] ?? null);
            $itemHeight = $parse($bundleItem['wall_height'] ?? null);
            $itemArea = $parse($bundleItem['area'] ?? null);
            $itemWorkType = (string) ($bundleItem['work_type'] ?? '');
            if ($itemArea <= 0 && $itemLength > 0 && $itemHeight > 0 && $itemWorkType !== 'brick_rollag') {
                $itemArea = $itemWorkType === 'plinth_ceramic' ? $itemLength * ($itemHeight / 100) : $itemLength * $itemHeight;
            }
            $bundleTotalArea += max(0, $itemArea);
        }

        $wallLength = $parse($request->input('wall_length'));
        $wallHeight = $parse($request->input('wall_height'));
        if ($wallLength <= 0 || $wallHeight <= 0) {
            if ($bundleTotalArea > 0) {
                $wallLength = $bundleTotalArea;
                $wallHeight = 1;
            } else {
                $wallLength = 1;
                $wallHeight = 1;
            }
        }
        $wallArea = $bundleTotalArea > 0 ? $bundleTotalArea : $wallLength * $wallHeight;

        $cementId = $request->input('cement_id');
        $cement = $cementId ? \App\Models\Cement::find($cementId) : null;
        $cementPackageWeight = $n($cement?->package_weight_net ?? 50);
        if ($cementPackageWeight <= 0) {
            $cementPackageWeight = 50;
        }

        $cementKg = $n($selectedResult['cement_kg'] ?? 0);
        $cementSak = $n($selectedResult['cement_sak'] ?? ($cementKg > 0 ? $cementKg / $cementPackageWeight : 0));
        $sandM3 = $n($selectedResult['sand_m3'] ?? 0);
        $brickQty = $n($selectedResult['total_bricks'] ?? 0);
        $mortarVolume =
            $n($selectedResult['mortar_volume'] ?? ($n($selectedResult['cement_m3'] ?? 0) + $n($selectedResult['sand_m3'] ?? 0)));
        $brickTotal = $n($selectedResult['total_brick_price'] ?? 0);
        $brickPricePerPiece = $n($selectedResult['brick_price_per_piece'] ?? ($brickQty > 0 ? $brickTotal / $brickQty : 0));
        $waterLiters = $n($selectedResult['total_water_liters'] ?? ($selectedResult['water_liters'] ?? 0));

        $calculation = new BrickCalculation();
        $calculation->fill([
            'project_name' => $request->input('project_name'),
            'notes' => $request->input('notes'),
            'project_address' => $request->input('project_address'),
            'project_latitude' => $request->input('project_latitude'),
            'project_longitude' => $request->input('project_longitude'),
            'project_place_id' => $request->input('project_place_id'),
            'wall_length' => $wallLength,
            'wall_height' => $wallHeight,
            'wall_area' => $wallArea,
            'installation_type_id' => $installationTypeId,
            'mortar_thickness' => $mortarThickness,
            'mortar_formula_id' => $mortarFormulaId,
            'use_custom_ratio' => (bool) $request->boolean('use_custom_ratio'),
            'custom_cement_ratio' => $request->input('custom_cement_ratio'),
            'custom_sand_ratio' => $request->input('custom_sand_ratio'),
            'custom_water_ratio' => $request->input('custom_water_ratio'),
            'brick_quantity' => $brickQty,
            'brick_id' => $request->input('brick_id'),
            'brick_price_per_piece' => $brickPricePerPiece,
            'brick_total_cost' => $brickTotal,
            'mortar_volume' => $mortarVolume,
            'mortar_volume_per_brick' => $brickQty > 0 ? $mortarVolume / $brickQty : 0,
            'cement_quantity_40kg' => $cementKg > 0 ? $cementKg / 40 : 0,
            'cement_quantity_50kg' => $cementKg > 0 ? $cementKg / 50 : 0,
            'cement_kg' => $cementKg,
            'cement_package_weight' => $cementPackageWeight,
            'cement_quantity_sak' => $cementSak,
            'cement_id' => $cementId,
            'cement_price_per_sak' => $n($selectedResult['cement_price_per_sak'] ?? 0),
            'cement_total_cost' => $n($selectedResult['total_cement_price'] ?? 0),
            'sand_sak' => $n($selectedResult['sand_sak'] ?? 0),
            'sand_m3' => $sandM3,
            'sand_kg' => $n($selectedResult['sand_kg'] ?? ($sandM3 * 1600)),
            'sand_id' => $request->input('sand_id'),
            'sand_price_per_m3' => $n($selectedResult['sand_price_per_m3'] ?? 0),
            'sand_total_cost' => $n($selectedResult['total_sand_price'] ?? 0),
            'cat_id' => $request->input('cat_id'),
            'cat_quantity' => $n($selectedResult['cat_packages'] ?? 0),
            'cat_kg' => $n($selectedResult['cat_kg'] ?? 0),
            'paint_liters' => $n($selectedResult['cat_liters'] ?? 0),
            'cat_price_per_package' => $n($selectedResult['cat_price_per_package'] ?? 0),
            'cat_total_cost' => $n($selectedResult['total_cat_price'] ?? 0),
            'ceramic_id' => $request->input('ceramic_id'),
            'ceramic_quantity' => $n($selectedResult['total_tiles'] ?? 0),
            'ceramic_packages' => $n($selectedResult['tiles_packages'] ?? 0),
            'ceramic_total_cost' => $n($selectedResult['total_ceramic_price'] ?? 0),
            'nat_id' => $request->input('nat_id'),
            'nat_quantity' => $n($selectedResult['grout_packages'] ?? 0),
            'nat_kg' => $n($selectedResult['grout_kg'] ?? 0),
            'nat_total_cost' => $n($selectedResult['total_grout_price'] ?? 0),
            'water_liters' => $waterLiters,
            'total_material_cost' => $n($selectedResult['grand_total'] ?? 0),
            'calculation_params' => [
                'is_bundle' => true,
                'bundle_name' => $request->input('bundle_name'),
                'bundle_selected_label' => $request->input('bundle_selected_label'),
                'bundle_items' => $bundleItems,
                'bundle_selected_result' => $selectedResult,
                'bundle_material_rows' => $bundleMaterialRows,
                'formula_used' => $request->input('work_type'),
                'work_type' => $request->input('work_type'),
            ],
        ]);

        return $calculation;
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

    protected function normalizeBundleMaterialCustomizeFilters(mixed $rawFilters): array
    {
        return $this->normalizeMaterialCustomizeFilters($rawFilters);
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
            $rowKind = strtolower(trim((string) ($bundleItem['row_kind'] ?? 'item')));
            if (!in_array($rowKind, ['area', 'field', 'item'], true)) {
                $rowKind = 'item';
            }

            $itemRequestData = array_merge($baseRequestData, [
                'work_type' => $bundleItem['work_type'],
                'work_type_select' => $bundleItem['work_type'],
                'row_kind' => $rowKind,
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
            $bundleWorkFloor = trim((string) ($bundleItem['work_floor'] ?? ''));
            $bundleWorkArea = trim((string) ($bundleItem['work_area'] ?? ''));
            $bundleWorkField = trim((string) ($bundleItem['work_field'] ?? ''));
            $itemRequestData['work_floor'] = $bundleWorkFloor;
            $itemRequestData['work_area'] = $bundleWorkArea;
            $itemRequestData['work_field'] = $bundleWorkField;
            if ($bundleWorkFloor !== '') {
                $itemRequestData['work_floors'] = [$bundleWorkFloor];
            }
            if ($bundleWorkArea !== '') {
                $itemRequestData['work_areas'] = [$bundleWorkArea];
            }
            if ($bundleWorkField !== '') {
                $itemRequestData['work_fields'] = [$bundleWorkField];
            }
            $itemMaterialTypeFilters = $this->normalizeBundleMaterialTypeFilters(
                $bundleItem['material_type_filters'] ?? [],
            );
            if (!empty($itemMaterialTypeFilters)) {
                $itemRequestData['material_type_filters'] = $itemMaterialTypeFilters;
                $itemRequestData['material_type_filters_extra'] = [];
            } else {
                unset($itemRequestData['material_type_filters'], $itemRequestData['material_type_filters_extra']);
            }
            $itemMaterialCustomizeFilters = $this->normalizeBundleMaterialCustomizeFilters(
                $bundleItem['material_customize_filters'] ?? [],
            );
            if (!empty($itemMaterialCustomizeFilters)) {
                $itemRequestData['material_customize_filters'] = $itemMaterialCustomizeFilters;
            } else {
                unset($itemRequestData['material_customize_filters'], $itemRequestData['material_customize_filters_payload']);
            }

            $itemRequest = Request::create('/material-calculations', 'POST', $itemRequestData);
            $this->normalizeNatIdentifiers($itemRequest);
            $this->mergeMaterialTypeFilters($itemRequest);
            $this->mergeMaterialCustomizeFilters($itemRequest);

            // Generate + cache preview payload per item using existing engine
            $this->generateCombinations($itemRequest);
            $itemCacheKey = $this->buildCalculationCacheKey($itemRequest);
            $itemPayload = $this->getCalculationCachePayload($itemCacheKey);

            if (!$itemPayload || !is_array($itemPayload)) {
                continue;
            }

            // Keep only fields needed for bundle aggregation to reduce memory pressure
            // when many bundle items are calculated in a single request.
            $bundleItemPayloads[] = [
                'projects' => is_array($itemPayload['projects'] ?? null) ? $itemPayload['projects'] : [],
                'requestData' => is_array($itemPayload['requestData'] ?? null) ? $itemPayload['requestData'] : [],
                'title' => $itemTitle,
                'work_type' => $bundleItem['work_type'],
                'row_kind' => $rowKind,
                'work_floor' => $bundleWorkFloor,
                'work_area' => $bundleWorkArea,
                'work_field' => $bundleWorkField,
            ];

            // Per-item preview cache is only an intermediate artifact in bundle mode.
            // Clearing it prevents cache accumulation and may reduce process memory retained
            // by large payload references in long-running requests.
            if (is_string($itemCacheKey) && $itemCacheKey !== '') {
                \Illuminate\Support\Facades\Cache::forget($itemCacheKey);
            }

            unset($itemPayload);
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
                    'Tidak ada kombinasi lengkap untuk seluruh item paket. Coba longgarkan filter harga/material lalu hitung ulang.',
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
        $itemCombinationMaps = array_values($itemCombinationMaps);

        if (empty($itemCombinationMaps)) {
            return [];
        }
        foreach ($itemCombinationMaps as $itemMap) {
            if (!is_array($itemMap) || empty($itemMap)) {
                return [];
            }
        }

        $extractLabelPrefix = static function (string $label): string {
            return trim((string) preg_replace('/\s+\d+\s*$/u', '', $label));
        };
        $extractLabelRank = static function (string $label): int {
            if (preg_match('/\s+(\d+)\s*$/u', $label, $matches)) {
                return max(1, (int) ($matches[1] ?? 1));
            }

            return 1;
        };
        $allowedPrefixes = array_values(
            array_filter(
                array_map(
                    static fn($filter) => $labelPrefixes[$filter] ?? null,
                    $requestedFilters,
                ),
                static fn($prefix) => is_string($prefix) && trim($prefix) !== '',
            ),
        );
        $popularPrefix = strtolower((string) ($labelPrefixes['common'] ?? 'Populer'));
        $resolveItemCandidate = static function (
            array $itemMap,
            string $targetLabel,
        ) use ($extractLabelPrefix, $extractLabelRank, $allowedPrefixes, $popularPrefix): ?array {
            if (isset($itemMap[$targetLabel]) && is_array($itemMap[$targetLabel])) {
                return $itemMap[$targetLabel];
            }

            $targetPrefix = $extractLabelPrefix($targetLabel);
            $targetRank = $extractLabelRank($targetLabel);
            $prefixCandidates = [];
            foreach ($itemMap as $candidateLabel => $candidateRow) {
                if (!is_array($candidateRow)) {
                    continue;
                }
                if (strcasecmp($extractLabelPrefix((string) $candidateLabel), $targetPrefix) !== 0) {
                    continue;
                }
                $prefixCandidates[] = [
                    'rank' => $extractLabelRank((string) $candidateLabel),
                    'grand_total' => (float) ($candidateRow['result']['grand_total'] ?? PHP_FLOAT_MAX),
                    'candidate' => $candidateRow,
                ];
            }
            if (!empty($prefixCandidates)) {
                usort($prefixCandidates, static function ($a, $b) {
                    $rankCompare = ((int) ($a['rank'] ?? 0)) <=> ((int) ($b['rank'] ?? 0));
                    if ($rankCompare !== 0) {
                        return $rankCompare;
                    }

                    return ((float) ($a['grand_total'] ?? PHP_FLOAT_MAX)) <=>
                        ((float) ($b['grand_total'] ?? PHP_FLOAT_MAX));
                });
                $targetIndex = min(max($targetRank - 1, 0), count($prefixCandidates) - 1);

                return $prefixCandidates[$targetIndex]['candidate'] ?? null;
            }

            // Do not fallback Popular rows to cheapest/other categories.
            if (strtolower($targetPrefix) === $popularPrefix) {
                return null;
            }

            $allowedPrefixLookup = [];
            foreach ($allowedPrefixes as $allowedPrefix) {
                $allowedPrefixKey = strtolower(trim((string) $allowedPrefix));
                if ($allowedPrefixKey === $popularPrefix) {
                    continue;
                }
                $allowedPrefixLookup[$allowedPrefixKey] = true;
            }
            $allowedCandidates = [];
            foreach ($itemMap as $candidateLabel => $candidateRow) {
                if (!is_array($candidateRow)) {
                    continue;
                }
                $candidatePrefixKey = strtolower($extractLabelPrefix((string) $candidateLabel));
                if ($candidatePrefixKey === $popularPrefix) {
                    continue;
                }
                if (!isset($allowedPrefixLookup[$candidatePrefixKey])) {
                    continue;
                }
                $allowedCandidates[] = [
                    'rank' => $extractLabelRank((string) $candidateLabel),
                    'grand_total' => (float) ($candidateRow['result']['grand_total'] ?? PHP_FLOAT_MAX),
                    'candidate' => $candidateRow,
                ];
            }
            if (!empty($allowedCandidates)) {
                usort($allowedCandidates, static function ($a, $b) {
                    $totalCompare = ((float) ($a['grand_total'] ?? PHP_FLOAT_MAX)) <=>
                        ((float) ($b['grand_total'] ?? PHP_FLOAT_MAX));
                    if ($totalCompare !== 0) {
                        return $totalCompare;
                    }

                    return ((int) ($a['rank'] ?? 0)) <=> ((int) ($b['rank'] ?? 0));
                });

                return $allowedCandidates[0]['candidate'] ?? null;
            }

            $anyCandidates = [];
            foreach ($itemMap as $candidateLabel => $candidateRow) {
                if (!is_array($candidateRow)) {
                    continue;
                }
                $candidatePrefixKey = strtolower($extractLabelPrefix((string) $candidateLabel));
                if ($candidatePrefixKey === $popularPrefix) {
                    continue;
                }
                $anyCandidates[] = [
                    'rank' => $extractLabelRank((string) $candidateLabel),
                    'grand_total' => (float) ($candidateRow['result']['grand_total'] ?? PHP_FLOAT_MAX),
                    'candidate' => $candidateRow,
                ];
            }
            if (empty($anyCandidates)) {
                return null;
            }
            usort($anyCandidates, static function ($a, $b) {
                $totalCompare = ((float) ($a['grand_total'] ?? PHP_FLOAT_MAX)) <=>
                    ((float) ($b['grand_total'] ?? PHP_FLOAT_MAX));
                if ($totalCompare !== 0) {
                    return $totalCompare;
                }

                return ((int) ($a['rank'] ?? 0)) <=> ((int) ($b['rank'] ?? 0));
            });

            return $anyCandidates[0]['candidate'] ?? null;
        };

        $bundleCombinations = [];
        foreach ($candidateLabels as $label) {
            $isPopularLabel = strtolower($extractLabelPrefix((string) $label)) === $popularPrefix;
            $selectedItems = [];
            $isComplete = true;

            foreach ($bundleItemPayloads as $index => $bundleItemPayload) {
                $itemMap = $itemCombinationMaps[$index] ?? [];
                $selected = is_array($itemMap) ? $resolveItemCandidate($itemMap, (string) $label) : null;
                if (!$selected) {
                    if ($isPopularLabel) {
                        continue;
                    }
                    $isComplete = false;
                    break;
                }
                $itemRequestData = is_array($bundleItemPayload['requestData'] ?? null)
                    ? $bundleItemPayload['requestData']
                    : [];
                $resolveFirstTaxonomyValue = static function (mixed $value): string {
                    if (is_array($value)) {
                        foreach ($value as $entry) {
                            $text = trim((string) $entry);
                            if ($text !== '') {
                                return $text;
                            }
                        }

                        return '';
                    }

                    return trim((string) $value);
                };
                $itemWorkFloor = trim((string) ($bundleItemPayload['work_floor'] ?? ''));
                if ($itemWorkFloor === '') {
                    $itemWorkFloor = $resolveFirstTaxonomyValue(
                        $itemRequestData['work_floors'] ?? ($itemRequestData['work_floor'] ?? ''),
                    );
                }
                $itemWorkArea = trim((string) ($bundleItemPayload['work_area'] ?? ''));
                if ($itemWorkArea === '') {
                    $itemWorkArea = $resolveFirstTaxonomyValue(
                        $itemRequestData['work_areas'] ?? ($itemRequestData['work_area'] ?? ''),
                    );
                }
                $itemWorkField = trim((string) ($bundleItemPayload['work_field'] ?? ''));
                if ($itemWorkField === '') {
                    $itemWorkField = $resolveFirstTaxonomyValue(
                        $itemRequestData['work_fields'] ?? ($itemRequestData['work_field'] ?? ''),
                    );
                }
                $itemRowKind = strtolower(trim((string) ($bundleItemPayload['row_kind'] ?? ($itemRequestData['row_kind'] ?? 'item'))));
                if (!in_array($itemRowKind, ['area', 'field', 'item'], true)) {
                    $itemRowKind = 'item';
                }

                $selectedItems[] = [
                    'title' => $bundleItemPayload['title'] ?? ('Item ' . ($index + 1)),
                    'work_type' => $bundleItemPayload['work_type'] ?? ($bundleItemPayload['requestData']['work_type'] ?? ''),
                    'row_kind' => $itemRowKind,
                    'work_floor' => $itemWorkFloor,
                    'work_area' => $itemWorkArea,
                    'work_field' => $itemWorkField,
                    'request_data' => $itemRequestData,
                    'combination' => $selected,
                ];
            }

            if ($isPopularLabel) {
                if (empty($selectedItems)) {
                    continue;
                }
            } elseif (!$isComplete || empty($selectedItems)) {
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
        $bundleMaterialRows = $this->buildBundleMaterialRows($combinations);

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
        $bundleItemMaterialBreakdowns = [];
        foreach ($selectedItems as $index => $selectedItem) {
            $combo = $selectedItem['combination'] ?? [];
            $workTypeCode = (string) ($selectedItem['work_type'] ?? '');
            $workTypeMeta = \App\Services\FormulaRegistry::find($workTypeCode);
            $workTypeName = $workTypeMeta['name'] ?? ucwords(str_replace('_', ' ', $workTypeCode));
            $materialRows = is_array($combo) && !empty($combo) ? $this->buildBundleMaterialRows([$combo]) : [];
            $itemRequestData = is_array($selectedItem['request_data'] ?? null) ? $selectedItem['request_data'] : [];
            $resolveFirstTaxonomyValue = static function (mixed $value): string {
                if (is_array($value)) {
                    foreach ($value as $entry) {
                        $text = trim((string) $entry);
                        if ($text !== '') {
                            return $text;
                        }
                    }

                    return '';
                }

                return trim((string) $value);
            };
            $itemWorkFloor = trim((string) ($selectedItem['work_floor'] ?? ''));
            if ($itemWorkFloor === '') {
                $itemWorkFloor = $resolveFirstTaxonomyValue(
                    $itemRequestData['work_floors'] ?? ($itemRequestData['work_floor'] ?? ''),
                );
            }
            $itemWorkArea = trim((string) ($selectedItem['work_area'] ?? ''));
            if ($itemWorkArea === '') {
                $itemWorkArea = $resolveFirstTaxonomyValue(
                    $itemRequestData['work_areas'] ?? ($itemRequestData['work_area'] ?? ''),
                );
            }
            $itemWorkField = trim((string) ($selectedItem['work_field'] ?? ''));
            if ($itemWorkField === '') {
                $itemWorkField = $resolveFirstTaxonomyValue(
                    $itemRequestData['work_fields'] ?? ($itemRequestData['work_field'] ?? ''),
                );
            }
            $itemRowKind = strtolower(trim((string) ($selectedItem['row_kind'] ?? ($itemRequestData['row_kind'] ?? 'item'))));
            if (!in_array($itemRowKind, ['area', 'field', 'item'], true)) {
                $itemRowKind = 'item';
            }
            if ($itemWorkFloor !== '') {
                $itemRequestData['work_floor'] = $itemWorkFloor;
                if (!isset($itemRequestData['work_floors']) || !is_array($itemRequestData['work_floors'])) {
                    $itemRequestData['work_floors'] = [$itemWorkFloor];
                }
            }
            if ($itemWorkArea !== '') {
                $itemRequestData['work_area'] = $itemWorkArea;
                if (!isset($itemRequestData['work_areas']) || !is_array($itemRequestData['work_areas'])) {
                    $itemRequestData['work_areas'] = [$itemWorkArea];
                }
            }
            if ($itemWorkField !== '') {
                $itemRequestData['work_field'] = $itemWorkField;
                if (!isset($itemRequestData['work_fields']) || !is_array($itemRequestData['work_fields'])) {
                    $itemRequestData['work_fields'] = [$itemWorkField];
                }
            }
            $itemRequestData['row_kind'] = $itemRowKind;
            $lengthValue = \App\Helpers\NumberHelper::parseNullable($itemRequestData['wall_length'] ?? null);
            $heightValue = \App\Helpers\NumberHelper::parseNullable($itemRequestData['wall_height'] ?? null);
            $areaValue = \App\Helpers\NumberHelper::parseNullable($itemRequestData['area'] ?? null);
            $isRollag = $workTypeCode === 'brick_rollag';
            $isPlinthCeramic = $workTypeCode === 'plinth_ceramic';
            $computedArea = null;
            if (
                !$isRollag &&
                $lengthValue !== null &&
                $heightValue !== null &&
                $lengthValue > 0 &&
                $heightValue > 0
            ) {
                $computedArea = $isPlinthCeramic ? $lengthValue * ($heightValue / 100) : $lengthValue * $heightValue;
            }
            if ($areaValue === null || $areaValue <= 0) {
                $areaValue = $computedArea;
            }
            $heightLabel = in_array(
                $workTypeCode,
                ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
                true,
            )
                ? 'Lebar'
                : 'Tinggi';
            $heightUnit = $isPlinthCeramic ? 'cm' : 'm';
            $itemRows[] = [
                'title' => $selectedItem['title'] ?? ('Item ' . ($index + 1)),
                'work_type' => $workTypeCode,
                'work_type_name' => $workTypeName,
                'row_kind' => $itemRowKind,
                'work_floor' => $itemWorkFloor,
                'work_area' => $itemWorkArea,
                'work_field' => $itemWorkField,
                'label' => $label,
                'grand_total' => (float) ($combo['result']['grand_total'] ?? 0),
            ];
            $bundleItemMaterialBreakdowns[] = [
                'title' => $selectedItem['title'] ?? ('Item ' . ($index + 1)),
                'work_type' => $workTypeCode,
                'work_type_name' => $workTypeName,
                'row_kind' => $itemRowKind,
                'work_floor' => $itemWorkFloor,
                'work_area' => $itemWorkArea,
                'work_field' => $itemWorkField,
                'grand_total' => (float) ($combo['result']['grand_total'] ?? 0),
                'request_data' => $itemRequestData,
                'field_size' => [
                    'length' => $lengthValue,
                    'length_unit' => 'm',
                    'height' => $heightValue,
                    'height_label' => $heightLabel,
                    'height_unit' => $heightUnit,
                    'area' => $areaValue,
                    'area_unit' => 'm2',
                    'is_rollag' => $isRollag,
                ],
                'materials' => $materialRows,
            ];
        }
        $bundleItemMaterialBreakdowns = $this->assignBundleBreakdownDisplayTotals($bundleItemMaterialBreakdowns);

        return [
            'filter_label' => $label,
            'source_filters' => ['bundle'],
            'bundle_items' => $itemRows,
            'bundle_item_material_breakdowns' => $bundleItemMaterialBreakdowns,
            'bundle_material_rows' => $bundleMaterialRows,
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

    protected function assignBundleBreakdownDisplayTotals(array $breakdowns): array
    {
        if (empty($breakdowns)) {
            return $breakdowns;
        }

        $groupedRows = [];

        foreach ($breakdowns as $breakdownIndex => $breakdown) {
            $materials = is_array($breakdown['materials'] ?? null) ? $breakdown['materials'] : [];
            foreach ($materials as $materialIndex => $materialRow) {
                if (!is_array($materialRow) || (bool) ($materialRow['is_special'] ?? false)) {
                    continue;
                }

                $materialKey = (string) ($materialRow['material_key'] ?? '');
                if ($materialKey === '') {
                    continue;
                }

                $rawTotal = (float) ($materialRow['total_price'] ?? 0);
                if ($rawTotal <= 0) {
                    $pricePerUnit = (float) ($materialRow['price_per_unit'] ?? ($materialRow['package_price'] ?? 0));
                    $priceCalcQty = (float) ($materialRow['price_calc_qty'] ?? ($materialRow['qty'] ?? 0));
                    $rawTotal = $pricePerUnit * $priceCalcQty;
                }

                $signature = $this->buildBundleMaterialSignature($materialKey, $materialRow);
                $groupedRows[$signature][] = [
                    'breakdown_index' => $breakdownIndex,
                    'material_index' => $materialIndex,
                    'raw_total' => $rawTotal,
                ];
            }
        }

        foreach ($groupedRows as $rows) {
            if (empty($rows)) {
                continue;
            }

            $targetRoundedTotal = (int) round(
                array_sum(array_map(static fn($entry) => (float) ($entry['raw_total'] ?? 0), $rows)),
                0,
            );

            $prepared = [];
            $sumFloors = 0;
            foreach ($rows as $entry) {
                $raw = (float) ($entry['raw_total'] ?? 0);
                $floorValue = (int) floor($raw + 1e-9);
                $fraction = $raw - $floorValue;
                $sumFloors += $floorValue;
                $prepared[] = array_merge($entry, [
                    'display_total' => $floorValue,
                    'fraction' => $fraction,
                ]);
            }

            $remaining = $targetRoundedTotal - $sumFloors;
            if ($remaining > 0) {
                usort($prepared, static function ($a, $b) {
                    $fractionCompare = ($b['fraction'] ?? 0) <=> ($a['fraction'] ?? 0);
                    if ($fractionCompare !== 0) {
                        return $fractionCompare;
                    }

                    $rawCompare = ((float) ($b['raw_total'] ?? 0)) <=> ((float) ($a['raw_total'] ?? 0));
                    if ($rawCompare !== 0) {
                        return $rawCompare;
                    }

                    $breakdownCompare = ((int) ($a['breakdown_index'] ?? 0)) <=> ((int) ($b['breakdown_index'] ?? 0));
                    if ($breakdownCompare !== 0) {
                        return $breakdownCompare;
                    }

                    return ((int) ($a['material_index'] ?? 0)) <=> ((int) ($b['material_index'] ?? 0));
                });

                $countPrepared = count($prepared);
                for ($i = 0; $i < $remaining && $countPrepared > 0; $i++) {
                    $targetIndex = $i % $countPrepared;
                    $prepared[$targetIndex]['display_total']++;
                }
            }

            foreach ($prepared as $entry) {
                $breakdownIndex = (int) ($entry['breakdown_index'] ?? -1);
                $materialIndex = (int) ($entry['material_index'] ?? -1);
                if (
                    !isset($breakdowns[$breakdownIndex]) ||
                    !isset($breakdowns[$breakdownIndex]['materials']) ||
                    !is_array($breakdowns[$breakdownIndex]['materials']) ||
                    !isset($breakdowns[$breakdownIndex]['materials'][$materialIndex]) ||
                    !is_array($breakdowns[$breakdownIndex]['materials'][$materialIndex])
                ) {
                    continue;
                }

                $breakdowns[$breakdownIndex]['materials'][$materialIndex]['display_total_price'] = (float) ($entry['display_total'] ?? 0);
            }
        }

        return $breakdowns;
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

    protected function buildBundleMaterialRows(array $combinations): array
    {
        $materialOrder = ['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat', 'water'];
        $mergedRows = [];

        foreach ($combinations as $combination) {
            if (!is_array($combination) || empty($combination)) {
                continue;
            }

            foreach ($materialOrder as $materialKey) {
                $row = $this->buildBundleMaterialRowFromCombination($combination, $materialKey);
                if (!$row) {
                    continue;
                }
                $signature = $this->buildBundleMaterialSignature($materialKey, $row);
                if (!isset($mergedRows[$signature])) {
                    $mergedRows[$signature] = $row;
                    continue;
                }
                $mergedRows[$signature] = $this->mergeBundleMaterialRows($mergedRows[$signature], $row);
            }
        }

        $orderRank = array_flip($materialOrder);
        uasort($mergedRows, static function ($a, $b) use ($orderRank) {
            $keyA = (string) ($a['material_key'] ?? '');
            $keyB = (string) ($b['material_key'] ?? '');
            $rankA = $orderRank[$keyA] ?? PHP_INT_MAX;
            $rankB = $orderRank[$keyB] ?? PHP_INT_MAX;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return array_values($mergedRows);
    }

    protected function buildBundleMaterialRowFromCombination(array $combination, string $materialKey): ?array
    {
        $result = $combination['result'] ?? [];
        if (!is_array($result)) {
            return null;
        }

        if ($materialKey === 'water') {
            $qty = (float) ($result['total_water_liters'] ?? ($result['water_liters'] ?? 0));
            if ($qty <= 0) {
                return null;
            }

            return [
                'material_key' => 'water',
                'name' => 'Air',
                'check_field' => 'total_water_liters',
                'qty' => $qty,
                'qty_debug' => $result['water_liters_debug'] ?? '',
                'unit' => 'L',
                'comparison_unit' => 'L',
                'detail_value' => 1,
                'object' => null,
                'type_field' => null,
                'type_display' => 'Bersih',
                'brand_field' => null,
                'brand_display' => 'PDAM',
                'detail_display' => '',
                'detail_extra' => '',
                'store_field' => null,
                'store_display' => 'Customer',
                'address_field' => null,
                'address_display' => '-',
                'package_price' => 0,
                'package_unit' => '',
                'price_per_unit' => 0,
                'price_unit_label' => '',
                'price_calc_qty' => 0,
                'price_calc_unit' => '',
                'total_price' => 0,
                'unit_price' => 0,
                'unit_price_label' => '',
                'is_special' => true,
            ];
        }

        $model = $combination[$materialKey] ?? null;
        if (!$model) {
            return null;
        }

        $formatNum = static fn($value) => \App\Helpers\NumberHelper::format($value);

        $qtyMap = [
            'brick' => 'total_bricks',
            'cement' => 'cement_sak',
            'sand' => 'sand_m3',
            'cat' => 'cat_packages',
            'ceramic' => 'total_tiles',
            'nat' => 'grout_packages',
        ];
        $totalMap = [
            'brick' => 'total_brick_price',
            'cement' => 'total_cement_price',
            'sand' => 'total_sand_price',
            'cat' => 'total_cat_price',
            'ceramic' => 'total_ceramic_price',
            'nat' => 'total_grout_price',
        ];
        $pricePerUnitMap = [
            'brick' => 'brick_price_per_piece',
            'cement' => 'cement_price_per_sak',
            'sand' => 'sand_price_per_m3',
            'cat' => 'cat_price_per_package',
            'ceramic' => 'ceramic_price_per_package',
            'nat' => 'grout_price_per_package',
        ];
        $qty = (float) ($result[$qtyMap[$materialKey] ?? ''] ?? 0);
        if ($qty <= 0) {
            return null;
        }
        $totalPrice = (float) ($result[$totalMap[$materialKey] ?? ''] ?? 0);
        $pricePerUnit = (float) ($result[$pricePerUnitMap[$materialKey] ?? ''] ?? 0);

        $base = [
            'material_key' => $materialKey,
            'check_field' => $qtyMap[$materialKey] ?? 'qty',
            'qty' => $qty,
            'unit' => '',
            'comparison_unit' => '',
            'detail_value' => 1,
            'object' => $model,
            'type_field' => 'type',
            'brand_field' => 'brand',
            'detail_display' => '-',
            'detail_extra' => '-',
            'store_field' => 'store',
            'address_field' => 'address',
            'store_display' => $model->store ?? '-',
            'address_display' => $model->address ?? '-',
            'package_price' => 0,
            'package_unit' => '',
            'price_per_unit' => $pricePerUnit,
            'price_unit_label' => '',
            'price_calc_qty' => $qty,
            'price_calc_unit' => '',
            'total_price' => $totalPrice,
            'unit_price' => $pricePerUnit,
            'unit_price_label' => '',
        ];

        if ($materialKey === 'brick') {
            $length = (float) ($model->dimension_length ?? 0);
            $width = (float) ($model->dimension_width ?? 0);
            $height = (float) ($model->dimension_height ?? 0);
            $volume = $length > 0 && $width > 0 && $height > 0 ? ($length * $width * $height) / 1000000 : 0;

            return array_merge($base, [
                'name' => 'Bata',
                'unit' => 'Bh',
                'comparison_unit' => 'M3',
                'detail_value' => $volume > 0 ? $volume : 0,
                'detail_display' =>
                    $length > 0 && $width > 0 && $height > 0
                        ? $formatNum($length) . ' x ' . $formatNum($width) . ' x ' . $formatNum($height) . ' cm'
                        : '-',
                'detail_extra' =>
                    $volume > 0 ? \App\Helpers\NumberHelper::formatPlain($volume, 15, ',', '.') . ' M3' : '-',
                'package_price' => (float) ($model->price_per_piece ?? 0),
                'package_unit' => 'bh',
                'price_per_unit' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->price_per_piece ?? 0),
                'price_unit_label' => 'bh',
                'price_calc_unit' => 'bh',
                'unit_price' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->price_per_piece ?? 0),
                'unit_price_label' => 'bh',
            ]);
        }

        if ($materialKey === 'cement') {
            $packageUnit = trim((string) ($model->package_unit ?? 'Sak'));
            if ($packageUnit === '') {
                $packageUnit = 'Sak';
            }
            $weight = (float) ($model->package_weight_net ?? 0);

            return array_merge($base, [
                'name' => 'Semen',
                'unit' => 'Sak',
                'comparison_unit' => 'Kg',
                'detail_value' => $weight > 0 ? $weight : 0,
                'detail_display' => $model->color ?? '-',
                'detail_extra' => $weight > 0 ? $formatNum($weight) . ' Kg' : '-',
                'package_price' => (float) ($model->package_price ?? 0),
                'package_unit' => $packageUnit,
                'price_per_unit' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->package_price ?? 0),
                'price_unit_label' => $packageUnit,
                'price_calc_unit' => 'Sak',
                'unit_price' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->package_price ?? 0),
                'unit_price_label' => $packageUnit,
            ]);
        }

        if ($materialKey === 'sand') {
            $packageUnit = trim((string) ($model->package_unit ?? 'Karung'));
            if ($packageUnit === '') {
                $packageUnit = 'Karung';
            }
            $volume = (float) ($model->package_volume ?? 0);

            return array_merge($base, [
                'name' => 'Pasir',
                'unit' => 'M3',
                'comparison_unit' => 'M3',
                'detail_value' => $volume > 0 ? $volume : 1,
                'detail_display' => $packageUnit,
                'detail_extra' => $volume > 0 ? $formatNum($volume) . ' M3' : '-',
                'package_price' => (float) ($model->package_price ?? 0),
                'package_unit' => $packageUnit,
                'price_per_unit' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->comparison_price_per_m3 ?? 0),
                'price_unit_label' => 'M3',
                'price_calc_unit' => 'M3',
                'unit_price' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->comparison_price_per_m3 ?? 0),
                'unit_price_label' => $packageUnit,
            ]);
        }

        if ($materialKey === 'cat') {
            $packageUnit = trim((string) ($model->package_unit ?? 'Kmsn'));
            if ($packageUnit === '') {
                $packageUnit = 'Kmsn';
            }
            $weight = (float) ($model->package_weight_net ?? 0);
            $subBrand = trim((string) ($model->sub_brand ?? ''));
            $code = trim((string) ($model->color_code ?? ''));
            $colorName = trim((string) ($model->color_name ?? ''));
            $displayParts = array_values(array_filter([$subBrand, $code, $colorName], static fn($v) => $v !== ''));
            $detailDisplay = !empty($displayParts) ? implode(' - ', $displayParts) : '-';
            $detailExtra = $weight > 0 ? $formatNum($weight) . ' Kg' : '-';

            return array_merge($base, [
                'name' => 'Cat',
                'unit' => $packageUnit,
                'comparison_unit' => 'Kg',
                'detail_value' => $weight > 0 ? $weight : 0,
                'detail_display' => $detailDisplay,
                'detail_extra' => $detailExtra,
                'package_price' => (float) ($model->purchase_price ?? 0),
                'package_unit' => $packageUnit,
                'price_per_unit' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->purchase_price ?? 0),
                'price_unit_label' => $packageUnit,
                'price_calc_unit' => $packageUnit,
                'unit_price' => $pricePerUnit > 0 ? $pricePerUnit : (float) ($model->purchase_price ?? 0),
                'unit_price_label' => $packageUnit,
            ]);
        }

        if ($materialKey === 'ceramic') {
            $length = (float) ($model->dimension_length ?? 0);
            $width = (float) ($model->dimension_width ?? 0);
            $area = $length > 0 && $width > 0 ? ($length / 100) * ($width / 100) : 0;
            $tilesPackages = (float) ($result['tiles_packages'] ?? 0);
            $packagePrice = (float) ($model->price_per_package ?? 0);
            $effectivePricePerUnit = $pricePerUnit > 0 ? $pricePerUnit : ($tilesPackages > 0 ? $totalPrice / $tilesPackages : $packagePrice);

            return array_merge($base, [
                'name' => 'Keramik',
                'unit' => 'Bh',
                'comparison_unit' => 'M2',
                'detail_value' => $area > 0 ? $area : 0,
                'detail_display' => $model->color ?? '-',
                'detail_extra' =>
                    $length > 0 && $width > 0 ? $formatNum($length) . 'x' . $formatNum($width) . ' cm' : '-',
                'package_price' => $packagePrice,
                'package_unit' => 'Dus',
                'price_per_unit' => $effectivePricePerUnit,
                'price_unit_label' => 'Dus',
                'price_calc_qty' => $tilesPackages > 0 ? $tilesPackages : 0,
                'price_calc_unit' => 'Dus',
                'unit_price' => $effectivePricePerUnit,
                'unit_price_label' => 'Dus',
            ]);
        }

        if ($materialKey === 'nat') {
            $packageUnit = trim((string) ($model->package_unit ?? 'Bks'));
            if ($packageUnit === '') {
                $packageUnit = 'Bks';
            }
            $weight = (float) ($model->package_weight_net ?? 0);
            $packagePrice = (float) ($model->package_price ?? 0);
            $effectivePricePerUnit = $pricePerUnit > 0 ? $pricePerUnit : $packagePrice;

            return array_merge($base, [
                'name' => 'Nat',
                'unit' => 'Bks',
                'comparison_unit' => 'Kg',
                'detail_value' => $weight > 0 ? $weight : 0,
                'detail_display' => $model->color ?? 'Nat',
                'detail_extra' => $weight > 0 ? $formatNum($weight) . ' Kg' : '-',
                'package_price' => $packagePrice,
                'package_unit' => $packageUnit,
                'price_per_unit' => $effectivePricePerUnit,
                'price_unit_label' => $packageUnit,
                'price_calc_unit' => 'Bks',
                'unit_price' => $effectivePricePerUnit,
                'unit_price_label' => $packageUnit,
            ]);
        }

        return null;
    }

    protected function mergeBundleMaterialRows(array $existing, array $incoming): array
    {
        $existing['qty'] = (float) ($existing['qty'] ?? 0) + (float) ($incoming['qty'] ?? 0);
        $existing['price_calc_qty'] = (float) ($existing['price_calc_qty'] ?? 0) + (float) ($incoming['price_calc_qty'] ?? 0);
        $existing['total_price'] = (float) ($existing['total_price'] ?? 0) + (float) ($incoming['total_price'] ?? 0);

        $priceCalcQty = (float) ($existing['price_calc_qty'] ?? 0);
        if ($priceCalcQty > 0) {
            $perUnit = (float) ($existing['total_price'] ?? 0) / $priceCalcQty;
            $existing['price_per_unit'] = $perUnit;
            $existing['unit_price'] = $perUnit;
        }

        return $existing;
    }

    protected function buildBundleMaterialSignature(string $materialKey, array $row): string
    {
        $normalize = static function ($value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '';
            }
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

            return strtolower($text);
        };

        $model = $row['object'] ?? null;
        $type = $row['type_display'] ?? ($model->type ?? '');
        $brand = $row['brand_display'] ?? ($model->brand ?? '');
        $store = $row['store_display'] ?? ($model->store ?? '');
        $address = $row['address_display'] ?? ($model->address ?? '');

        $signatureData = [
            'material' => $normalize($materialKey),
            'name' => $normalize($row['name'] ?? ''),
            'type' => $normalize($type),
            'brand' => $normalize($brand),
            'detail' => $normalize($row['detail_display'] ?? ''),
            'detail_extra' => $normalize($row['detail_extra'] ?? ''),
            'store' => $normalize($store),
            'address' => $normalize($address),
            'package_unit' => $normalize($row['package_unit'] ?? ''),
            'package_price' => round((float) ($row['package_price'] ?? 0), 4),
            'unit' => $normalize($row['unit'] ?? ''),
        ];

        return hash('sha256', json_encode($signatureData, JSON_UNESCAPED_UNICODE));
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
            'cement_id' => ['nullable', Rule::exists('cements', 'id')->where('material_kind', Cement::MATERIAL_KIND)],
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
