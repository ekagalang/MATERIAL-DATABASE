@php
    $bundleIgnoredKeys = [
        'title',
        'work_type',
        'active_fields',
        'material_type_filters',
        'material_type_filters_extra',
    ];
    $bundleSupportedParamKeys = [
        'wall_length',
        'wall_height',
        'mortar_thickness',
        'layer_count',
        'grout_thickness',
        'ceramic_length',
        'ceramic_width',
        'ceramic_thickness',
        'area',
    ];
    $bundleParamLabelMap = [
        'wall_length' => 'Panjang',
        'wall_height' => 'Tinggi',
        'mortar_thickness' => 'Tebal Adukan',
        'layer_count' => 'Tingkat',
        'grout_thickness' => 'Tebal Nat',
        'ceramic_length' => 'P. Keramik',
        'ceramic_width' => 'L. Keramik',
        'ceramic_thickness' => 'T. Keramik',
        'area' => 'Luas',
    ];
    $bundleParamUnitMap = [
        'wall_length' => 'M',
        'wall_height' => 'M',
        'mortar_thickness' => 'cm',
        'layer_count' => 'Lapis',
        'grout_thickness' => 'mm',
        'ceramic_length' => 'cm',
        'ceramic_width' => 'cm',
        'ceramic_thickness' => 'mm',
        'area' => 'M2',
    ];
    $bundleParamOrderMap = [
        'wall_length' => 10,
        'wall_height' => 20,
        'area' => 30,
        '__computed_area' => 31,
        'mortar_thickness' => 40,
        'layer_count' => 50,
        'grout_thickness' => 80,
        'ceramic_length' => 90,
        'ceramic_width' => 100,
        'ceramic_thickness' => 110,
    ];

    $formatBundleParamLabel = static function (string $key, string $workType) use ($bundleParamLabelMap): string {
        if ($key === 'wall_height') {
            return in_array(
                $workType,
                ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
                true,
            )
                ? 'Lebar'
                : 'Tinggi';
        }
        if ($key === 'mortar_thickness' && in_array($workType, ['skim_coating', 'coating_floor'], true)) {
            return 'Tebal Acian';
        }
        if ($key === 'layer_count' && in_array($workType, ['painting', 'wall_painting'], true)) {
            return 'Lapis Cat';
        }
        if (isset($bundleParamLabelMap[$key])) {
            return $bundleParamLabelMap[$key];
        }

        return ucwords(str_replace('_', ' ', $key));
    };

    $resolveFallbackActiveFields = static function (string $workType): array {
        $fields = ['wall_length'];
        if ($workType !== 'brick_rollag') {
            $fields[] = 'wall_height';
        }
        if (!in_array($workType, ['painting', 'wall_painting', 'grout_tile'], true)) {
            $fields[] = 'mortar_thickness';
        }
        if (in_array($workType, ['brick_rollag', 'painting', 'wall_painting'], true)) {
            $fields[] = 'layer_count';
        }
        if (
            in_array(
                $workType,
                ['tile_installation', 'grout_tile', 'plinth_ceramic', 'adhesive_mix', 'plinth_adhesive_mix'],
                true,
            )
        ) {
            $fields[] = 'grout_thickness';
        }
        if ($workType === 'grout_tile') {
            $fields[] = 'ceramic_length';
            $fields[] = 'ceramic_width';
            $fields[] = 'ceramic_thickness';
        }

        return array_values(array_unique($fields));
    };

    $resolveBundleParamUnit = static function (string $key, string $workType) use ($bundleParamUnitMap): string {
        if ($key === 'wall_height' && $workType === 'plinth_ceramic') {
            return 'cm';
        }

        return $bundleParamUnitMap[$key] ?? '';
    };

    $normalizeBundleParamValue = static function ($value): ?string {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        $parsedNumeric = \App\Helpers\NumberHelper::parseNullable($value);
        if ($parsedNumeric !== null) {
            return \App\Helpers\NumberHelper::format($parsedNumeric);
        }

        return $text;
    };

    $parseBundleNumeric = static function ($value): ?float {
        return \App\Helpers\NumberHelper::parseNullable($value);
    };

    $buildBundleItemVariables = function (array $item, string $workType) use (
        $bundleIgnoredKeys,
        $bundleSupportedParamKeys,
        $bundleParamOrderMap,
        $formatBundleParamLabel,
        $resolveFallbackActiveFields,
        $resolveBundleParamUnit,
        $normalizeBundleParamValue,
        $parseBundleNumeric,
    ): array {
        $variables = [];
        $activeFields = array_values(
            array_filter(
                array_map(static fn($field) => trim((string) $field), $item['active_fields'] ?? []),
                static fn($field) => $field !== '',
            ),
        );
        if (empty($activeFields)) {
            $activeFields = $resolveFallbackActiveFields($workType);
        }
        $activeFieldsLookup = array_fill_keys($activeFields, true);

        foreach ($item as $rawKey => $rawValue) {
            $paramKey = trim((string) $rawKey);
            if ($paramKey === '' || in_array($paramKey, $bundleIgnoredKeys, true)) {
                continue;
            }
            if (!in_array($paramKey, $bundleSupportedParamKeys, true)) {
                continue;
            }
            if (!isset($activeFieldsLookup[$paramKey]) && $paramKey !== 'area') {
                continue;
            }
            if (str_starts_with($paramKey, 'material_type_filter') || str_ends_with($paramKey, '_id')) {
                continue;
            }

            $normalizedValue = $normalizeBundleParamValue($rawValue);
            if ($normalizedValue === null) {
                continue;
            }

            $variables[$paramKey] = [
                'key' => $paramKey,
                'label' => $formatBundleParamLabel($paramKey, $workType),
                'value' => $normalizedValue,
                'unit' => $resolveBundleParamUnit($paramKey, $workType),
            ];
        }

        $length = $parseBundleNumeric($item['wall_length'] ?? null) ?? 0;
        $height = $parseBundleNumeric($item['wall_height'] ?? null) ?? 0;
        $isRollag = $workType === 'brick_rollag';
        if (
            !$isRollag &&
            $length > 0 &&
            $height > 0 &&
            !isset($variables['area'])
        ) {
            $computedArea = $workType === 'plinth_ceramic' ? $length * ($height / 100) : $length * $height;
            $variables['__computed_area'] = [
                'key' => '__computed_area',
                'label' => 'Luas',
                'value' => \App\Helpers\NumberHelper::format($computedArea),
                'unit' => 'M2',
            ];
        }

        $orderedVariables = array_values($variables);
        usort($orderedVariables, static function (array $a, array $b) use ($bundleParamOrderMap) {
            $orderA = $bundleParamOrderMap[$a['key']] ?? 999;
            $orderB = $bundleParamOrderMap[$b['key']] ?? 999;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $orderedVariables;
    };

    $bundleItemsSource = [];
    if (isset($rawBundleItems) && is_array($rawBundleItems) && !empty($rawBundleItems)) {
        $bundleItemsSource = $rawBundleItems;
    } elseif (isset($bundleDisplayItems) && is_array($bundleDisplayItems) && !empty($bundleDisplayItems)) {
        foreach ($bundleDisplayItems as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $bundleItemsSource[] = [
                'title' => $entry['title'] ?? '',
                'work_type' => $entry['work_type'] ?? '',
                'wall_length' => $entry['length'] ?? null,
                'wall_height' => $entry['height'] ?? null,
                'area' => $entry['area'] ?? null,
                'mortar_thickness' => $entry['mortar_thickness'] ?? null,
                'layer_count' => $entry['layer_count'] ?? null,
                'grout_thickness' => $entry['grout_thickness'] ?? null,
                'ceramic_length' => $entry['ceramic_length'] ?? null,
                'ceramic_width' => $entry['ceramic_width'] ?? null,
                'ceramic_thickness' => $entry['ceramic_thickness'] ?? null,
            ];
        }
    }

    $bundleWorkItemsForParams = [];
    foreach ($bundleItemsSource as $idx => $decodedItem) {
        if (!is_array($decodedItem)) {
            continue;
        }
        $workTypeCode = trim((string) ($decodedItem['work_type'] ?? ''));
        if ($workTypeCode === '') {
            continue;
        }
        $formulaMeta = \App\Services\FormulaRegistry::find($workTypeCode);
        $workTypeName = $formulaMeta['name'] ?? ucwords(str_replace('_', ' ', $workTypeCode));
        $itemTitle = trim((string) ($decodedItem['title'] ?? ''));
        if ($itemTitle === '') {
            $itemTitle = 'Item Pekerjaan ' . ($idx + 1);
        }

        $variables = $buildBundleItemVariables($decodedItem, $workTypeCode);

        $bundleWorkItemsForParams[] = [
            'title' => $itemTitle,
            'work_type_code' => $workTypeCode,
            'work_type_name' => $workTypeName,
            'raw' => $decodedItem,
            'variables' => $variables,
        ];
    }
    $hasBundleWorkItemDropdown = count($bundleWorkItemsForParams) > 0;
@endphp

<div class="container mb-3">
    <div
        class="card p-3 shadow-sm border-0 preview-params-sticky {{ $hasBundleWorkItemDropdown ? 'preview-params-sticky--bundle' : '' }}"
        style="background-color: #fdfdfd; border-radius: 12px;">
        <div
            class="d-flex flex-wrap align-items-end gap-3 justify-content-start preview-param-row {{ $hasBundleWorkItemDropdown ? 'preview-param-row-with-dropdown' : '' }}">
            @if ($hasBundleWorkItemDropdown)
                <div style="flex: 1; min-width: 320px;">
                    <div class="dropdown w-100 preview-param-items-dropdown preview-param-items-dropdown-inline"
                        data-bs-auto-close="outside">
                        <div class="mb-2">
                            <button class="dropdown-toggle fw-bold text-uppercase preview-param-label-toggle" type="button"
                                data-param-dropdown-toggle="true" aria-expanded="false">
                                <i class="bi bi-briefcase me-1"></i>Item Pekerjaan
                            </button>
                        </div>
                        <div class="dropdown-menu p-2 shadow-sm bundle-param-dropdown-menu">
                            @foreach ($bundleWorkItemsForParams as $bundleIndex => $bundleItemParam)
                                @php
                                    $itemWorkType = $bundleItemParam['work_type_code'] ?? '';
                                    $isItemRollag = $itemWorkType === 'brick_rollag';
                                    $itemHeightLabel = in_array(
                                        $itemWorkType,
                                        ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
                                        true,
                                    )
                                        ? 'LEBAR'
                                        : 'TINGGI';
                                    $itemVarMap = [];
                                    foreach ($bundleItemParam['variables'] as $itemVariable) {
                                        $varKey = trim((string) ($itemVariable['key'] ?? ''));
                                        if ($varKey === '') {
                                            continue;
                                        }
                                        $itemVarMap[$varKey] = $itemVariable;
                                    }
                                    $itemHas = static fn($key) => isset($itemVarMap[$key]);
                                    $itemVal = static fn($key, $default = '-') => $itemVarMap[$key]['value'] ?? $default;
                                    $itemUnit = static fn($key, $default = '') => $itemVarMap[$key]['unit'] ?? $default;
                                    $itemAreaKey = $itemHas('area') ? 'area' : ($itemHas('__computed_area') ? '__computed_area' : null);
                                    $showLength = $itemHas('wall_length');
                                    $showHeight = !$isItemRollag && $itemHas('wall_height');
                                    $showArea = !empty($itemAreaKey);
                                    $showMortar = $itemHas('mortar_thickness');
                                    $showLayerRollag = $itemHas('layer_count') && $itemWorkType === 'brick_rollag';
                                    $showLayerPaint = $itemHas('layer_count') && in_array($itemWorkType, ['painting', 'wall_painting'], true);
                                    $showPlaster = false;
                                    $showSkim = false;
                                    $showGrout = $itemHas('grout_thickness');
                                    $showCerLen = $itemHas('ceramic_length');
                                    $showCerWid = $itemHas('ceramic_width');
                                    $showCerThk = $itemHas('ceramic_thickness');
                                    $sizeFieldCount = ($showLength ? 1 : 0) + ($showHeight ? 1 : 0) + ($showArea ? 1 : 0);
                                    $supportFieldCount =
                                        ($showMortar ? 1 : 0) +
                                        ($showLayerRollag ? 1 : 0) +
                                        ($showPlaster ? 1 : 0) +
                                        ($showSkim ? 1 : 0) +
                                        ($showLayerPaint ? 1 : 0) +
                                        ($showGrout ? 1 : 0) +
                                        ($showCerLen ? 1 : 0) +
                                        ($showCerWid ? 1 : 0) +
                                        ($showCerThk ? 1 : 0);
                                @endphp
                                <div class="px-2 py-2 bundle-param-item-card {{ $bundleIndex > 0 ? 'border-top mt-1' : '' }}">
                                    <div class="bundle-param-item-layout">
                                        <div class="bundle-param-section bundle-param-section--worktype">
                                            <div class="form-control fw-bold border-secondary text-dark bundle-param-worktype-value"
                                                style="background-color: #e9ecef; opacity: 1;">
                                                {{ $bundleItemParam['work_type_name'] }}
                                            </div>
                                        </div>

                                        <div class="bundle-param-section bundle-param-section--size">
                                            <div class="bundle-param-section-fields">
                                                @if ($showLength)
                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-light border">PANJANG</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #e9ecef;">
                                                                {{ $itemVal('wall_length') }}
                                                            </div>
                                                            <span class="input-group-text bg-light small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('wall_length', 'M') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showHeight)
                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-light border">{{ $itemHeightLabel }}</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #e9ecef;">
                                                                {{ $itemVal('wall_height') }}
                                                            </div>
                                                            <span class="input-group-text bg-light small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('wall_height', 'M') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showArea)
                                                    <div class="bundle-param-field bundle-param-field--md">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center bg-white text-danger px-1"
                                                                style="border-color: #dc3545;">
                                                                {{ $itemVal($itemAreaKey) }}
                                                            </div>
                                                            <span class="input-group-text bg-danger text-white small px-1"
                                                                style="font-size: 0.7rem; border-color: #dc3545;">{{ $itemUnit($itemAreaKey, 'M2') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($sizeFieldCount === 0)
                                                    <div class="bundle-param-empty">-</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="bundle-param-section bundle-param-section--support">
                                            <div class="bundle-param-section-fields">
                                                @if ($showMortar)
                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-light border">TEBAL ADUKAN</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #e9ecef;">
                                                                {{ $itemVal('mortar_thickness') }}
                                                            </div>
                                                            <span class="input-group-text bg-light small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('mortar_thickness', 'cm') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showLayerRollag)
                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-warning border">TINGKAT</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #fffbeb; border-color: #fcd34d;">
                                                                {{ $itemVal('layer_count') }}
                                                            </div>
                                                            <span class="input-group-text bg-warning small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('layer_count', 'Lapis') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showLayerPaint)
                                                    <div class="bundle-param-field bundle-param-field--md">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-primary text-white border border-primary">LAPIS CAT</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #dbeafe; border-color: #3b82f6;">
                                                                {{ $itemVal('layer_count') }}
                                                            </div>
                                                            <span class="input-group-text bg-primary text-white small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('layer_count', 'Lapisan') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showGrout)
                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge bg-info text-white border">TEBAL NAT</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #e0f2fe; border-color: #38bdf8;">
                                                                {{ $itemVal('grout_thickness') }}
                                                            </div>
                                                            <span class="input-group-text bg-info text-white small px-1"
                                                                style="font-size: 0.7rem;">{{ $itemUnit('grout_thickness', 'mm') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showCerLen)
                                                    <div class="bundle-param-field bundle-param-field--ceramic">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge text-white border"
                                                                style="background-color: #f59e0b;">P. KERAMIK</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #fef3c7; border-color: #fde047;">
                                                                {{ $itemVal('ceramic_length') }}
                                                            </div>
                                                            <span class="input-group-text text-white small px-1"
                                                                style="background-color: #f59e0b; font-size: 0.7rem;">{{ $itemUnit('ceramic_length', 'cm') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showCerWid)
                                                    <div class="bundle-param-field bundle-param-field--ceramic">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge text-white border"
                                                                style="background-color: #f59e0b;">L. KERAMIK</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #fef3c7; border-color: #fde047;">
                                                                {{ $itemVal('ceramic_width') }}
                                                            </div>
                                                            <span class="input-group-text text-white small px-1"
                                                                style="background-color: #f59e0b; font-size: 0.7rem;">{{ $itemUnit('ceramic_width', 'cm') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($showCerThk)
                                                    <div class="bundle-param-field bundle-param-field--ceramic">
                                                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                            style="font-size: 0.75rem;">
                                                            <span class="badge text-white border"
                                                                style="background-color: #f59e0b;">T. KERAMIK</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <div class="form-control fw-bold text-center px-1"
                                                                style="background-color: #fef3c7; border-color: #fde047;">
                                                                {{ $itemVal('ceramic_thickness') }}
                                                            </div>
                                                            <span class="input-group-text text-white small px-1"
                                                                style="background-color: #f59e0b; font-size: 0.7rem;">{{ $itemUnit('ceramic_thickness', 'mm') }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($supportFieldCount === 0)
                                                    <div class="bundle-param-empty">-</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
