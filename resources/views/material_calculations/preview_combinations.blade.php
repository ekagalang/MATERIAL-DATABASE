@extends('layouts.app')

@section('title', 'Pilih Kombinasi Material')

@section('content')

    {{-- DEBUG PANEL REMOVED --}}

    <div id="preview-top"></div>
    <div class="container-fluid py-4 preview-combinations-page">
        <div class="container mb-4">
            <div class="d-flex align-items-center justify-content-between position-relative">

                <!-- KIRI -->
                <div>
                    <button type="button" id="btnResetSession" class="btn-cancel"
                        style="border: 1px solid #891313; background-color: transparent; color: #891313;
                    padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                    display: inline-flex; align-items: center; gap: 8px;">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </button>
                </div>

                <!-- TENGAH -->
                <div class="position-absolute start-50 translate-middle-x text-center">
                    <h2 class="fw-bold mb-0" style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                        Pilih Kombinasi Material
                    </h2>
                </div>

                <!-- KANAN -->
                <div>
                    @if (!empty($projects))
                        <button type="button"
                            style="border: 1px solid #891313; background-color: transparent; color: #891313;
                        padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                        display: inline-flex; align-items: center; gap: 8px;"
                            data-bs-toggle="modal" data-bs-target="#allPriceModal">
                            <i class="bi bi-list-ul"></i> Daftar Kombinasi Harga
                        </button>
                    @endif
                </div>

            </div>
        </div>

        @php
            $hasProjectCombinations = false;
            if (!empty($projects)) {
                foreach ($projects as $project) {
                    if (!empty($project['combinations']) && count($project['combinations']) > 0) {
                        $hasProjectCombinations = true;
                        break;
                    }
                }
            }
        @endphp

        @if (!$hasProjectCombinations && empty($ceramicProjects ?? []))
            @php
                $workType = $requestData['work_type'] ?? ($requestData['work_type_select'] ?? '');
                $requiredMaterials = \App\Services\FormulaRegistry::materialsFor($workType);
                if (empty($requiredMaterials)) {
                    $requiredMaterials = ['brick', 'cement', 'sand'];
                }
                $materialTypeFilters = $requestData['material_type_filters'] ?? [];
                if (!is_array($materialTypeFilters)) {
                    $materialTypeFilters = [];
                }
                $priceFilters = $requestData['price_filters'] ?? [];
                if (!is_array($priceFilters)) {
                    $priceFilters = [$priceFilters];
                }
                $hasCustomFilter = in_array('custom', $priceFilters, true);
                $labels = [
                    'brick' => 'Bata',
                    'cement' => 'Semen',
                    'sand' => 'Pasir',
                    'cat' => 'Cat',
                    'ceramic' => 'Keramik',
                    'nat' => 'Nat',
                ];
                $normalizeFilterValues = function ($value) use (&$normalizeFilterValues) {
                    if (is_array($value)) {
                        $flattened = [];
                        foreach ($value as $item) {
                            $flattened = array_merge($flattened, $normalizeFilterValues($item));
                        }
                        return array_values(array_unique($flattened));
                    }
                    if ($value === null) {
                        return [];
                    }
                    $text = trim((string) $value);
                    if ($text === '') {
                        return [];
                    }
                    $parts = preg_split('/\s*\|\s*/', $text) ?: [];
                    $tokens = array_values(
                        array_filter(
                            array_map(static fn($part) => trim((string) $part), $parts),
                            static fn($part) => $part !== '',
                        ),
                    );
                    return array_values(array_unique($tokens));
                };
                $applyTypeFilter = function ($query, $filterValue) use ($normalizeFilterValues) {
                    $values = $normalizeFilterValues($filterValue);
                    if (!empty($values)) {
                        $query->whereIn('type', $values);
                    }
                };
                $applyCeramicSizeFilter = function ($query, $filterValue) use ($normalizeFilterValues) {
                    $values = $normalizeFilterValues($filterValue);
                    if (empty($values)) {
                        return;
                    }
                    $dimensionsList = [];
                    foreach ($values as $sizeFilter) {
                        $normalized = str_replace(',', '.', (string) $sizeFilter);
                        $normalized = str_replace(['×', 'Ã—', 'Ãƒâ€”'], 'x', $normalized);
                        $dimensions = array_map('trim', explode('x', strtolower($normalized)));
                        if (count($dimensions) !== 2) {
                            continue;
                        }
                        $dim1 = (float) $dimensions[0];
                        $dim2 = (float) $dimensions[1];
                        if ($dim1 > 0 && $dim2 > 0) {
                            $dimensionsList[] = [$dim1, $dim2];
                        }
                    }
                    if (empty($dimensionsList)) {
                        return;
                    }
                    $query->where(function ($q) use ($dimensionsList) {
                        foreach ($dimensionsList as [$dim1, $dim2]) {
                            $q->orWhere(function ($sq) use ($dim1, $dim2) {
                                $sq->where('dimension_length', $dim1)->where('dimension_width', $dim2);
                            })->orWhere(function ($sq) use ($dim1, $dim2) {
                                $sq->where('dimension_length', $dim2)->where('dimension_width', $dim1);
                            });
                        }
                    });
                };

                $missingMaterials = [];
                $missingDetails = [];
                $missingCustom = [];
                $materialCounts = [];
                $lowThresholdExempt = ['Keramik (dimensi)', 'Nat (tebal)'];

                $addMissing = function (string $label) use (&$missingMaterials) {
                    if (!in_array($label, $missingMaterials, true)) {
                        $missingMaterials[] = $label;
                    }
                };

                $markMissing = function (string $type, int $count) use (&$missingMaterials, &$missingDetails, $labels) {
                    if ($count <= 0) {
                        $label = $labels[$type] ?? $type;
                        $missingMaterials[] = $label;
                        $missingDetails[] = $label . ' (0 data cocok)';
                    }
                };

                if ($hasCustomFilter) {
                    if (in_array('brick', $requiredMaterials, true)) {
                        $hasBrickSelection = !empty($requestData['brick_id']) || !empty($requestData['brick_ids']);
                        if (!$hasBrickSelection) {
                            $addMissing($labels['brick']);
                            $missingCustom[] = $labels['brick'];
                        }
                    }
                    if (in_array('cement', $requiredMaterials, true) && empty($requestData['cement_id'])) {
                        $addMissing($labels['cement']);
                        $missingCustom[] = $labels['cement'];
                    }
                    if (in_array('sand', $requiredMaterials, true) && empty($requestData['sand_id'])) {
                        $addMissing($labels['sand']);
                        $missingCustom[] = $labels['sand'];
                    }
                    if (in_array('cat', $requiredMaterials, true) && empty($requestData['cat_id'])) {
                        $addMissing($labels['cat']);
                        $missingCustom[] = $labels['cat'];
                    }
                    if (in_array('ceramic', $requiredMaterials, true) && $workType !== 'grout_tile') {
                        if (empty($requestData['ceramic_id'])) {
                            $addMissing($labels['ceramic']);
                            $missingCustom[] = $labels['ceramic'];
                        }
                    }
                    if (in_array('nat', $requiredMaterials, true) && empty($requestData['nat_id'])) {
                        $addMissing($labels['nat']);
                        $missingCustom[] = $labels['nat'];
                    }
                }

                if (in_array('brick', $requiredMaterials, true)) {
                    $brickQuery = \App\Models\Brick::query();
                    $applyTypeFilter($brickQuery, $materialTypeFilters['brick'] ?? null);
                    $brickCount = $brickQuery->count();
                    $materialCounts[$labels['brick']] = $brickCount;
                    $markMissing('brick', $brickCount);
                }

                if (in_array('cement', $requiredMaterials, true)) {
                    $cementQuery = \App\Models\Cement::query()
                        ->where('package_price', '>', 0)
                        ->where('package_weight_net', '>', 0);
                    $applyTypeFilter($cementQuery, $materialTypeFilters['cement'] ?? null);
                    $cementCount = $cementQuery->count();
                    $materialCounts[$labels['cement']] = $cementCount;
                    $markMissing('cement', $cementCount);
                }

                if (in_array('sand', $requiredMaterials, true)) {
                    $sandQuery = \App\Models\Sand::query();
                    $applyTypeFilter($sandQuery, $materialTypeFilters['sand'] ?? null);
                    $sandQuery->where(function ($q) {
                        $q->where('comparison_price_per_m3', '>', 0)
                            ->orWhere(function ($sq) {
                                $sq->where('package_volume', '>', 0)->where('package_price', '>', 0);
                            });
                    });
                    $sandCount = $sandQuery->count();
                    $materialCounts[$labels['sand']] = $sandCount;
                    $markMissing('sand', $sandCount);
                }

                if (in_array('cat', $requiredMaterials, true)) {
                    $catQuery = \App\Models\Cat::query()->where('purchase_price', '>', 0);
                    $applyTypeFilter($catQuery, $materialTypeFilters['cat'] ?? null);
                    $catCount = $catQuery->count();
                    $materialCounts[$labels['cat']] = $catCount;
                    $markMissing('cat', $catCount);
                }

                if (in_array('ceramic', $requiredMaterials, true)) {
                    if ($workType === 'grout_tile') {
                        $ceramicLength = (float) ($requestData['ceramic_length'] ?? 0);
                        $ceramicWidth = (float) ($requestData['ceramic_width'] ?? 0);
                        $ceramicThickness = (float) ($requestData['ceramic_thickness'] ?? 0);
                        if ($ceramicLength <= 0 || $ceramicWidth <= 0 || $ceramicThickness <= 0) {
                            $addMissing('Keramik (dimensi)');
                        }
                        $materialCounts['Keramik (dimensi)'] =
                            $ceramicLength > 0 && $ceramicWidth > 0 && $ceramicThickness > 0 ? 1 : 0;
                    } else {
                        $ceramicQuery = \App\Models\Ceramic::query()->whereNotNull('price_per_package');
                        $applyCeramicSizeFilter($ceramicQuery, $materialTypeFilters['ceramic'] ?? null);
                        $ceramicCount = $ceramicQuery->count();
                        $materialCounts[$labels['ceramic']] = $ceramicCount;
                        $markMissing('ceramic', $ceramicCount);
                    }
                }

                if (in_array('nat', $requiredMaterials, true)) {
                    if ($workType === 'grout_tile') {
                        $groutThickness = (float) ($requestData['grout_thickness'] ?? 0);
                        if ($groutThickness <= 0) {
                            $addMissing('Nat (tebal)');
                        }
                        $materialCounts['Nat (tebal)'] = $groutThickness > 0 ? 1 : 0;
                    }
                    $natQuery = \App\Models\Nat::query();
                    if ($workType !== 'grout_tile') {
                        $natQuery->where('package_price', '>', 0);
                    }
                    $applyTypeFilter($natQuery, $materialTypeFilters['nat'] ?? null);
                    $natCount = $natQuery->count();
                    $materialCounts[$labels['nat']] = $natCount;
                    $markMissing('nat', $natCount);
                }

                $useStoreFilter = (bool) data_get($requestData ?? [], 'use_store_filter', true);
                $allowMixedStore = (bool) data_get($requestData ?? [], 'allow_mixed_store', false);
                $showStoreNote = $useStoreFilter && !$allowMixedStore && $workType !== 'grout_tile';

                $missingList = [];
                $minRequiredCount = 2;
                foreach ($materialCounts as $label => $count) {
                    if ($count <= 0) {
                        $missingList[] = $label . ' (0)';
                        continue;
                    }
                    if (in_array($label, $lowThresholdExempt, true)) {
                        continue;
                    }
                    if ($count < $minRequiredCount) {
                        $missingList[] = $label . ' (' . $count . ')';
                    }
                }

            @endphp
            <div class="container">
                <div class="alert shadow-sm"
                    style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; padding: 16px 20px; color: #856404;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle fs-5"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">Tidak ditemukan data material yang cocok dengan filter Anda.</div>
                            <div class="mt-2 small text-muted">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-light text-dark border">Material tidak tersedia</span>
                                    <span>{{ !empty($missingList) ? implode(', ', $missingList) : '-' }}</span>
                                </div>
                                @if (!empty($missingCustom))
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="badge bg-light text-dark border">Belum dipilih (Custom)</span>
                                        <span>{{ implode(', ', array_unique($missingCustom)) }}</span>
                                    </div>
                                @endif
                                @if (!empty($materialCounts))
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="badge bg-light text-dark border">Ketersediaan</span>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($materialCounts as $label => $count)
                                                <span class="badge bg-white text-dark border">{{ $label }}: {{ $count }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(isset($isMultiCeramic) && $isMultiCeramic && isset($groupedCeramics))
            @php
                $workType = $requestData['work_type'] ?? '';
                $isRollag = $workType === 'brick_rollag';
                $isGroutTile = $workType === 'grout_tile';
                $heightLabel = in_array(
                    $workType,
                    ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor', 'adhesive_mix'],
                    true,
                )
                    ? 'LEBAR'
                    : 'TINGGI';
                $lengthValue = $requestData['wall_length'] ?? null;
                $heightValue = $isRollag ? null : $requestData['wall_height'] ?? null;
                $groutValue = $requestData['grout_thickness'] ?? null;
                $areaValue = $isRollag ? null : $requestData['area'] ?? null;
                $isPlinthArea = isset($requestData['work_type']) && $requestData['work_type'] === 'plinth_ceramic';
                if (!$isRollag && !$areaValue && $lengthValue !== null && $heightValue !== null) {
                    // For plinth ceramic, height is in cm, convert to meters for area calculation
                    $areaValue = $isPlinthArea ? ($lengthValue * ($heightValue / 100)) : ($lengthValue * $heightValue);
                }
                $formulaDisplay = $formulaName ?? ($requestData['formula_name'] ?? null);
                $mortarValue = $requestData['mortar_thickness'] ?? null;
                $isPainting =
                    isset($requestData['work_type']) &&
                    ($requestData['work_type'] === 'painting' || $requestData['work_type'] === 'wall_painting');
                $paramLabel = $isPainting ? 'LAPIS' : 'TEBAL ADUKAN';
                $paramUnit = $isPainting ? 'Lapis' : 'cm';
                // Don't show mortar thickness for grout_tile
$paramValue = $isGroutTile
    ? null
    : ($isPainting
        ? $requestData['layer_count'] ?? ($requestData['painting_layers'] ?? 2)
                        : $mortarValue);
            @endphp

            @if ($formulaDisplay || $paramValue || $lengthValue || $heightValue || $groutValue || $areaValue)
                <div class="container mb-3">
                    <div class="card p-3 shadow-sm border-0 ceramic-info-card preview-params-sticky"
                        style="background-color: #fdfdfd; border-radius: 12px;">
                        <div class="d-flex flex-wrap align-items-end gap-3 justify-content-start preview-param-row">
                            @if ($formulaDisplay)
                                <div style="flex: 1; min-width: 250px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-light border">ITEM PEKERJAAN</span>
                                    </label>
                                    <div class="form-control fw-bold text-dark" style="background-color: #e9ecef;">
                                        {{ $formulaDisplay }}
                                    </div>
                                </div>
                            @endif

                            @if ($paramValue)
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-light border">{{ $paramLabel }}</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e9ecef;">@format($paramValue)</div>
                                        <span class="input-group-text bg-light small px-1"
                                            style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                                    </div>
                                </div>
                            @endif

                            @if ($lengthValue)
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-light border">PANJANG</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e9ecef;">@format($lengthValue)</div>
                                        <span class="input-group-text bg-light small px-1"
                                            style="font-size: 0.7rem;">M</span>
                                    </div>
                                </div>
                            @endif

                            @if ($heightValue)
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-light border">{{ $heightLabel }}</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e9ecef;">@format($heightValue)</div>
                                        <span class="input-group-text bg-light small px-1"
                                            style="font-size: 0.7rem;">M</span>
                                    </div>
                                </div>
                            @endif

                            @if ($groutValue)
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-info text-white border">TEBAL NAT</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e0f2fe; border-color: #38bdf8;">@format($groutValue)
                                        </div>
                                        <span class="input-group-text bg-info text-white small px-1"
                                            style="font-size: 0.7rem;">mm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Panjang Keramik (untuk Pasang Nat saja) --}}
                            @if ($isGroutTile && isset($requestData['ceramic_length']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">P.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_length'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Lebar Keramik (untuk Pasang Nat saja) --}}
                            @if ($isGroutTile && isset($requestData['ceramic_width']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">L.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_width'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Tebal Keramik (untuk Pasang Nat saja) --}}
                            @if ($isGroutTile && isset($requestData['ceramic_thickness']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">T.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_thickness'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">mm</span>
                                    </div>
                                </div>
                            @endif

                            @if ($areaValue)
                                <div style="flex: 0 0 auto; width: 120px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-danger text-white border border-danger">LUAS</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center bg-white text-danger px-1"
                                            style="border-color: #dc3545;">@format($areaValue)</div>
                                        <span class="input-group-text bg-danger text-white small px-1"
                                            style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- MULTI-CERAMIC TABS SECTION (COMPACT TWO ROWS) --}}
            <div class="container mb-3">
                <div class="card shadow-sm ceramic-tabs-card"
                    style="border: 1px solid #e2e8f0; background: #ffffff; border-radius: 8px; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); padding: 0;">
                    <div class="card-body ceramic-tabs-card-body" style="padding: 6px 8px;">

                        {{-- Row 1: JENIS --}}
                        <div class="ceramic-group-row ceramic-group-row--types">
                            <div class="ceramic-group-label">JENIS:</div>
                            <ul class="nav nav-pills hide-scrollbar ceramic-group-tabs" id="ceramicTypeTabs"
                                role="tablist">
                                @foreach ($groupedCeramics as $type => $ceramicsOfType)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-sm {{ $loop->first ? 'active' : '' }}"
                                            style="white-space: nowrap; font-size: 12px; padding: 4px 12px; border-radius: 6px; border: 1px solid #f1f5f9;"
                                            id="type-{{ Str::slug($type) }}-tab" data-bs-toggle="pill"
                                            data-bs-target="#type-{{ Str::slug($type) }}" type="button" role="tab">
                                            {{ $type }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Row 2: UKURAN (Dynamic Content based on Type) --}}
                        <div class="tab-content" id="ceramicTypeTabContent">
                            @foreach ($groupedCeramics as $type => $ceramicsOfType)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                    id="type-{{ Str::slug($type) }}" role="tabpanel">
                                    <div class="ceramic-group-row ceramic-group-row--sizes">
                                        <div class="ceramic-group-label">UKURAN:</div>
                                        <ul class="nav nav-pills hide-scrollbar ceramic-group-tabs"
                                            id="size-{{ Str::slug($type) }}-tabs" role="tablist">
                                            @foreach ($ceramicsOfType->groupBy('size') as $size => $ceramicsOfSize)
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link btn-sm {{ $loop->first ? 'active' : '' }}"
                                                        style="white-space: nowrap; font-size: 12px; padding: 3px 10px; border-radius: 6px;"
                                                        id="size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}-tab"
                                                        data-bs-toggle="pill"
                                                        data-bs-target="#size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}"
                                                        type="button" role="tab">
                                                        {{ $size }}
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Result Area for the selected combinations --}}
                <div class="tab-content mt-2" id="ceramicSizeTabContent">
                    @foreach ($groupedCeramics as $type => $ceramicsOfType)
                        @foreach ($ceramicsOfType->groupBy('size') as $size => $ceramicsOfSize)
                            <div class="tab-pane fade {{ $loop->parent->first && $loop->first ? 'show active' : '' }}"
                                id="size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}" role="tabpanel">

                                <div class="ceramic-project" data-type="{{ $type }}"
                                    data-size="{{ $size }}" data-loaded="false">
                                    {{-- Compact Loading --}}
                                    <div class="loading-placeholder text-center py-4 bg-white rounded-3 border">
                                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status">
                                        </div>
                                        <div class="small fw-bold text-dark">Menghitung {{ $type }}
                                            {{ $size }}...</div>
                                        <div class="progress mx-auto mt-2" style="height: 4px; width: 120px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="combinations-content" style="display: none;"></div>
                                </div>

                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <style>
                .hide-scrollbar::-webkit-scrollbar {
                    display: none;
                }

                .hide-scrollbar {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }

                .ceramic-tabs-card {
                    border-radius: 8px !important;
                    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08) !important;
                    padding: 0 !important;
                }

                .ceramic-tabs-card-body {
                    padding: 6px 8px !important;
                }

                .ceramic-group-row {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-wrap: nowrap;
                    width: 100%;
                }

                .ceramic-group-row--types {
                    margin-bottom: 8px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #f1f5f9;
                }

                .ceramic-group-label {
                    font-size: 10px;
                    font-weight: 800;
                    letter-spacing: 0.5px;
                    color: #64748b;
                    min-width: 75px;
                    padding: 0 8px 0 4px;
                    flex: 0 0 50px;
                    text-align: right;
                    border-right: 1px solid #e2e8f0;
                }

                .ceramic-group-tabs {
                    display: flex !important;
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    gap: 4px;
                    overflow-x: auto;
                    overflow-y: hidden;
                    padding: 0 !important;
                    margin: 0 !important;
                    flex: 1 1 auto;
                    min-width: 0;
                    white-space: nowrap;
                    background: transparent !important;
                    height: auto !important;
                    border: none !important;
                }

                .ceramic-group-tabs .nav-item {
                    flex: 0 0 auto;
                    display: inline-flex;
                }

                .ceramic-group-tabs.nav {
                    padding: 0 !important;
                    margin: 0 !important;
                    gap: 4px !important;
                }

                .ceramic-info-item {
                    display: flex;
                    flex-direction: column;
                    align-items: stretch;
                    min-width: 100px;
                }

                .ceramic-info-item--work {
                    min-width: 260px;
                    flex: 1 1 260px;
                }

                /* TYPE PILLS */
                #ceramicTypeTabs .nav-link {
                    color: #64748b;
                    background: #f8fafc;
                    font-weight: 600;
                }

                #ceramicTypeTabs .nav-link.active {
                    background: #891313 !important;
                    color: white !important;
                    border-color: #891313 !important;
                    box-shadow: 0 2px 4px rgba(137, 19, 19, 0.2);
                }

                /* SIZE PILLS */
                [id^="size-"][id$="-tabs"] .nav-link {
                    color: #64748b;
                    background: transparent;
                    border: 1px solid #e2e8f0;
                }

                [id^="size-"][id$="-tabs"] .nav-link.active {
                    background: #f1f5f9 !important;
                    color: #891313 !important;
                    border: 1px solid #891313 !important;
                    font-weight: 800;
                }
            </style>



            {{-- Custom Styles for Chips/Pills --}}
            <style>
                .hide-scrollbar::-webkit-scrollbar {
                    display: none;
                }

                .hide-scrollbar {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }

                /* TYPE TABS STYLING */
                #ceramicTypeTabs .nav-link {
                    color: #64748b;
                    background-color: #fff;
                    transition: all 0.2s ease;
                }

                #ceramicTypeTabs .nav-link:hover {
                    background-color: #f8fafc;
                    border-color: #cbd5e1 !important;
                    color: #475569;
                }

                #ceramicTypeTabs .nav-link.active {
                    background-color: #891313 !important;
                    color: white !important;
                    border-color: #891313 !important;
                    box-shadow: 0 4px 6px -1px rgba(137, 19, 19, 0.2), 0 2px 4px -1px rgba(137, 19, 19, 0.1);
                }

                #ceramicTypeTabs .nav-link.active .badge {
                    background-color: rgba(255, 255, 255, 0.2) !important;
                    color: white !important;
                    border: none !important;
                }

                /* SIZE TABS STYLING */
                [id^="size-"][id$="-tabs"] .nav-link {
                    color: #64748b;
                    background: transparent;
                    border: 1px solid transparent;
                }

                [id^="size-"][id$="-tabs"] .nav-link:hover {
                    background-color: #e2e8f0;
                    color: #1e293b;
                }

                [id^="size-"][id$="-tabs"] .nav-link.active {
                    background-color: #fff !important;
                    color: #891313 !important;
                    border: 1px solid #891313 !important;
                    font-weight: 700;
                    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                }
            </style>
        @else
            {{-- TABEL REKAP GLOBAL (untuk semua bata) --}}
            @php
                // Prepare rekap data global untuk semua bata
                $requestedFilters = $requestData['price_filters'] ?? [];
                if (!is_array($requestedFilters)) {
                    $requestedFilters = [$requestedFilters];
                }
                if (empty($requestedFilters)) {
                    $requestedFilters = ['best'];
                }
                $filterMap = [
                    'best' => 'Preferensi',
                    'common' => 'Populer',
                    'cheapest' => 'Ekonomis',
                    'medium' => 'Average',
                    'expensive' => 'Termahal',
                ];
                $orderedFilters = array_keys($filterMap);
                $filterSet = in_array('all', $requestedFilters, true)
                    ? array_unique(array_merge($requestedFilters, $orderedFilters))
                    : array_unique($requestedFilters);
                $filterCategories = [];
                foreach ($orderedFilters as $filterKey) {
                    if (in_array($filterKey, $filterSet, true)) {
                        $filterCategories[] = $filterMap[$filterKey];
                    }
                }
                $rekapCategories = ['Preferensi', 'Populer', 'Ekonomis', 'Average', 'Termahal'];
                if (in_array('custom', $filterSet, true)) {
                    $filterCategories[] = 'Custom';
                    $rekapCategories[] = 'Custom';
                }
                $availableFilterTypes = [];
                if (!empty($projects)) {
                    foreach ($projects as $project) {
                        foreach (($project['combinations'] ?? []) as $label => $items) {
                            $labelParts = array_map('trim', explode('=', (string) $label));
                            foreach ($labelParts as $singleLabel) {
                                if (preg_match('/^(.+?)\s+\d+/', $singleLabel, $matches)) {
                                    $type = trim($matches[1]);
                                    if ($type !== '') {
                                        $availableFilterTypes[$type] = true;
                                    }
                                }
                            }
                        }
                    }
                }
                $availableFilterTypes = array_keys($availableFilterTypes);
                if (!empty($availableFilterTypes)) {
                    $hasOverlap = count(array_intersect($filterCategories, $availableFilterTypes)) > 0;
                    if (empty($filterCategories) || !$hasOverlap) {
                        $filterCategories = $availableFilterTypes;
                    }
                    foreach ($availableFilterTypes as $type) {
                        if (!in_array($type, $rekapCategories, true)) {
                            $rekapCategories[] = $type;
                        }
                    }
                }

                // BUGFIX: Ensure both tables use the same filter categories
                // Use whichever array has more items (typically $rekapCategories after above processing)
                if (count($rekapCategories) > count($filterCategories)) {
                    $filterCategories = $rekapCategories;
                } else {
                    $rekapCategories = $filterCategories;
                }
                $globalRekapData = [];
                $detailCombinationMap = [];
                $hasBrick = false;
                $hasCement = false;
                $hasSand = false;
                $hasCat = false;
                $hasCeramic = false;
                $hasNat = false;

                // Build historical material usage map for Populer (same work_type only).
                $workType = $requestData['work_type'] ?? ($requestData['work_type_select'] ?? null);
                $isStoreScopedView = (bool) ($requestData['use_store_filter'] ?? true);
                if (($workType ?? '') === 'grout_tile') {
                    $isStoreScopedView = false;
                }
                $materialUsage = [
                    'brick' => [],
                    'cement' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'nat' => [],
                ];
                $materialTypeFilters = $requestData['material_type_filters'] ?? [];
                if (!is_array($materialTypeFilters)) {
                    $materialTypeFilters = [];
                }
                $materialTypeFilters = array_map(function ($value) {
                    if (is_array($value)) {
                        return array_values(
                            array_filter(
                                array_map(function ($item) {
                                    return is_string($item) ? trim($item) : $item;
                                }, $value),
                                function ($item) {
                                    return $item !== null && $item !== '';
                                },
                            ),
                        );
                    }
                    return is_string($value) ? trim($value) : $value;
                }, $materialTypeFilters);
                $normalizeCeramicSize = function ($value) {
                    $clean = is_string($value) ? trim($value) : '';
                    if ($clean === '') {
                        return '';
                    }
                    $clean = strtolower(str_replace(' ', '', $clean));
                    return $clean;
                };
                $formatCeramicSizeValue = function ($length, $width) use ($normalizeCeramicSize) {
                    $lengthValue = is_numeric($length) ? (float) $length : null;
                    $widthValue = is_numeric($width) ? (float) $width : null;
                    if (empty($lengthValue) || empty($widthValue)) {
                        return '';
                    }
                    $min = min($lengthValue, $widthValue);
                    $max = max($lengthValue, $widthValue);
                    if ($min <= 0 || $max <= 0) {
                        return '';
                    }
                    $minText = \App\Helpers\NumberHelper::format($min);
                    $maxText = \App\Helpers\NumberHelper::format($max);
                    if ($minText === '' || $maxText === '') {
                        return '';
                    }
                    return $normalizeCeramicSize($minText . ' x ' . $maxText);
                };
                $materialModelMap = [
                    'brick' => \App\Models\Brick::class,
                    'cement' => \App\Models\Cement::class,
                    'sand' => \App\Models\Sand::class,
                    'cat' => \App\Models\Cat::class,
                    'ceramic' => \App\Models\Ceramic::class,
                    'nat' => \App\Models\Nat::class,
                ];

                $historicalFrequencyQuery = DB::table('brick_calculations')->select(
                    'brick_id',
                    'cement_id',
                    'sand_id',
                    'cat_id',
                    'ceramic_id',
                    'nat_id',
                    DB::raw('count(*) as frequency'),
                );
                if ($workType) {
                    $historicalFrequencyQuery->whereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?",
                        [$workType],
                    );
                }
                $historicalRows = collect();
                if ($workType) {
                    $historicalRows = $historicalFrequencyQuery
                        ->groupBy('brick_id', 'cement_id', 'sand_id', 'cat_id', 'ceramic_id', 'nat_id')
                        ->get();
                }

                foreach ($historicalRows as $row) {
                    $frequency = (int) $row->frequency;
                    if (!empty($row->brick_id)) {
                        $materialUsage['brick'][$row->brick_id] =
                            ($materialUsage['brick'][$row->brick_id] ?? 0) + $frequency;
                    }
                    if (!empty($row->cement_id)) {
                        $materialUsage['cement'][$row->cement_id] =
                            ($materialUsage['cement'][$row->cement_id] ?? 0) + $frequency;
                    }
                    if (!empty($row->sand_id)) {
                        $materialUsage['sand'][$row->sand_id] =
                            ($materialUsage['sand'][$row->sand_id] ?? 0) + $frequency;
                    }
                    if (!empty($row->cat_id)) {
                        $materialUsage['cat'][$row->cat_id] = ($materialUsage['cat'][$row->cat_id] ?? 0) + $frequency;
                    }
                    if (!empty($row->ceramic_id)) {
                        $materialUsage['ceramic'][$row->ceramic_id] =
                            ($materialUsage['ceramic'][$row->ceramic_id] ?? 0) + $frequency;
                    }
                    if (!empty($row->nat_id)) {
                        $materialUsage['nat'][$row->nat_id] = ($materialUsage['nat'][$row->nat_id] ?? 0) + $frequency;
                    }
                }
                foreach ($materialTypeFilters as $type => $value) {
                    $filterValues = is_array($value) ? $value : preg_split('/\s*\|\s*/', (string) $value);
                    $filterValues = array_values(
                        array_filter(
                            array_map(function ($item) {
                                return is_string($item) ? trim($item) : $item;
                            }, $filterValues),
                            function ($item) {
                                return $item !== null && $item !== '';
                            },
                        ),
                    );
                    if (empty($filterValues) || empty($materialUsage[$type]) || empty($materialModelMap[$type])) {
                        if (!empty($filterValues) && empty($materialModelMap[$type])) {
                            $materialUsage[$type] = [];
                        }
                        continue;
                    }
                    $ids = array_keys($materialUsage[$type]);
                    if ($type === 'ceramic') {
                        $targetSizes = array_values(
                            array_filter(
                                array_map(function ($item) use ($normalizeCeramicSize) {
                                    return $normalizeCeramicSize((string) $item);
                                }, $filterValues),
                                function ($item) {
                                    return $item !== '';
                                },
                            ),
                        );
                        if (empty($targetSizes)) {
                            $materialUsage[$type] = [];
                            continue;
                        }
                        $matchedIds = $materialModelMap[$type]
                            ::whereIn('id', $ids)
                            ->get(['id', 'dimension_length', 'dimension_width'])
                            ->filter(function ($model) use ($formatCeramicSizeValue, $targetSizes) {
                                return in_array(
                                    $formatCeramicSizeValue($model->dimension_length, $model->dimension_width),
                                    $targetSizes,
                                    true,
                                );
                            })
                            ->pluck('id')
                            ->all();
                    } else {
                        $matchedIds = $materialModelMap[$type]
                            ::whereIn('id', $ids)
                            ->whereIn('type', $filterValues)
                            ->pluck('id')
                            ->all();
                    }
                    if (empty($matchedIds)) {
                        $materialUsage[$type] = [];
                        continue;
                    }
                    $allowed = array_flip($matchedIds);
                    $materialUsage[$type] = array_intersect_key($materialUsage[$type], $allowed);
                }
                $hasHistoricalUsage =
                    array_sum($materialUsage['brick']) > 0 ||
                    array_sum($materialUsage['cement']) > 0 ||
                    array_sum($materialUsage['sand']) > 0 ||
                    array_sum($materialUsage['cat']) > 0 ||
                    array_sum($materialUsage['ceramic']) > 0 ||
                    array_sum($materialUsage['nat']) > 0;
                $selectedTypeFilters = array_filter($materialTypeFilters, function ($value) {
                    if (is_array($value)) {
                        return !empty($value);
                    }
                    return is_string($value) ? trim($value) !== '' : !empty($value);
                });
                if (!empty($selectedTypeFilters)) {
                    foreach ($selectedTypeFilters as $type => $value) {
                        if (empty($materialUsage[$type])) {
                            $hasHistoricalUsage = false;
                            break;
                        }
                    }
                }
                // Note: Populer column is kept in $rekapCategories so the header always appears,
                // but data rows are only populated when $hasHistoricalUsage is true.

                // Definisi warna label untuk kolom Rekap (sama dengan yang di tabel utama)
                $rekapLabelColors = [
                    'Preferensi' => [
                        1 => ['bg' => '#fca5a5', 'text' => '#991b1b'],
                        2 => ['bg' => '#fecaca', 'text' => '#dc2626'],
                        3 => ['bg' => '#fee2e2', 'text' => '#ef4444'],
                    ],
                    'Populer' => [
                        1 => ['bg' => '#93c5fd', 'text' => '#1e40af'],
                        2 => ['bg' => '#bfdbfe', 'text' => '#2563eb'],
                        3 => ['bg' => '#dbeafe', 'text' => '#3b82f6'],
                    ],
                    'Ekonomis' => [
                        1 => ['bg' => '#6ee7b7', 'text' => '#065f46'],
                        2 => ['bg' => '#a7f3d0', 'text' => '#16a34a'],
                        3 => ['bg' => '#d1fae5', 'text' => '#22c55e'],
                    ],
                    'Average' => [
                        1 => ['bg' => '#fcd34d', 'text' => '#92400e'],
                        2 => ['bg' => '#fde68a', 'text' => '#b45309'],
                        3 => ['bg' => '#fef3c7', 'text' => '#d97706'],
                    ],
                    'Termahal' => [
                        1 => ['bg' => '#d8b4fe', 'text' => '#6b21a8'],
                        2 => ['bg' => '#e9d5ff', 'text' => '#7c3aed'],
                        3 => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
                    ],
                    'Custom' => [
                        1 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                        2 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                        3 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                    ],
                ];
                $resolveBrickModelForRekap = function ($project, $item) {
                    $comboBrick = $item['brick'] ?? ($project['brick'] ?? null);
                    if (!empty($comboBrick)) {
                        return $comboBrick;
                    }

                    $brickId = $item['brick_id'] ?? ($item['result']['brick_id'] ?? null);
                    if (empty($brickId) && !empty($project['brick']->id ?? null)) {
                        $brickId = $project['brick']->id;
                    }
                    if (empty($brickId)) {
                        return null;
                    }

                    return \App\Models\Brick::find($brickId);
                };
                $formatBrickBrandForRekap = function ($brick) {
                    if (empty($brick)) {
                        return '-';
                    }

                    $brand = trim((string) ($brick->brand ?? ''));
                    if ($brand !== '') {
                        return $brand;
                    }

                    $materialName = trim((string) ($brick->material_name ?? ''));
                    if ($materialName !== '') {
                        return $materialName;
                    }

                    $type = trim((string) ($brick->type ?? ''));
                    if ($type !== '') {
                        return $type;
                    }

                    return 'Bata #' . ($brick->id ?? '-');
                };
                $formatBrickDetailForRekap = function ($brick) {
                    if (empty($brick)) {
                        return '-';
                    }

                    $length = (float) ($brick->dimension_length ?? 0);
                    $width = (float) ($brick->dimension_width ?? 0);
                    $height = (float) ($brick->dimension_height ?? 0);
                    $type = trim((string) ($brick->type ?? '-'));

                    if ($length > 0 && $width > 0 && $height > 0) {
                        return $type . ' - ' . ($length + 0) . ' x ' . ($width + 0) . ' x ' . ($height + 0) . ' cm';
                    }

                    if ($type !== '' && $type !== '-') {
                        return $type;
                    }

                    return '-';
                };
                $rekapLegacyFieldMap = [
                    'brick' => ['id' => 'brick_id', 'brand' => 'brick_brand', 'detail' => 'brick_detail'],
                    'cement' => ['id' => 'cement_id', 'brand' => 'cement_brand', 'detail' => 'cement_detail'],
                    'sand' => ['id' => 'sand_id', 'brand' => 'sand_brand', 'detail' => 'sand_detail'],
                    'cat' => ['id' => 'cat_id', 'brand' => 'cat_brand', 'detail' => 'cat_detail'],
                    'ceramic' => ['id' => 'ceramic_id', 'brand' => 'ceramic_brand', 'detail' => 'ceramic_detail'],
                    'nat' => ['id' => 'nat_id', 'brand' => 'nat_brand', 'detail' => 'nat_detail'],
                ];
                $emptyRekapMaterialVariants = static function () use ($rekapLegacyFieldMap): array {
                    $data = [];
                    foreach (array_keys($rekapLegacyFieldMap) as $matKey) {
                        $data[$matKey] = [];
                    }

                    return $data;
                };
                $appendRekapMaterialVariant = function (
                    array &$entry,
                    string $materialKey,
                    $id,
                    $brand,
                    $detail,
                ) use ($rekapLegacyFieldMap): void {
                    if (!isset($rekapLegacyFieldMap[$materialKey])) {
                        return;
                    }

                    if (!isset($entry['material_variants']) || !is_array($entry['material_variants'])) {
                        $entry['material_variants'] = [];
                    }
                    if (!isset($entry['material_variants'][$materialKey]) || !is_array($entry['material_variants'][$materialKey])) {
                        $entry['material_variants'][$materialKey] = [];
                    }

                    $brandText = trim((string) ($brand ?? ''));
                    $detailText = trim((string) ($detail ?? ''));
                    if ($brandText === '' && $detailText === '') {
                        return;
                    }
                    if ($brandText === '') {
                        $brandText = '-';
                    }
                    if ($detailText === '') {
                        $detailText = '-';
                    }
                    $materialId = is_numeric($id) ? (int) $id : null;
                    $signature = strtolower(
                        ($materialId ?? 'null') . '|' . preg_replace('/\s+/u', ' ', $brandText) . '|' . preg_replace('/\s+/u', ' ', $detailText),
                    );
                    foreach ($entry['material_variants'][$materialKey] as $existing) {
                        $existingId = is_array($existing) && is_numeric($existing['id'] ?? null) ? (int) $existing['id'] : null;
                        if ($materialId !== null && $existingId !== null && $materialId === $existingId) {
                            return;
                        }
                        if (($existing['signature'] ?? '') === $signature) {
                            return;
                        }
                    }

                    $entry['material_variants'][$materialKey][] = [
                        'id' => $materialId,
                        'brand' => $brandText,
                        'detail' => $detailText,
                        'signature' => $signature,
                    ];
                };
                $extractBundleMaterialVariantsForRekap = function ($item) use ($rekapLegacyFieldMap): array {
                    $variants = [];
                    foreach (array_keys($rekapLegacyFieldMap) as $matKey) {
                        $variants[$matKey] = [];
                    }

                    $bundleRows = $item['bundle_material_rows'] ?? [];
                    if (!is_array($bundleRows)) {
                        return $variants;
                    }

                    foreach ($bundleRows as $bundleRow) {
                        if (!is_array($bundleRow)) {
                            continue;
                        }
                        $materialKey = trim((string) ($bundleRow['material_key'] ?? ''));
                        if (!isset($variants[$materialKey])) {
                            continue;
                        }

                        $model = $bundleRow['object'] ?? null;
                        $brand = trim((string) ($bundleRow['brand_display'] ?? ($model->brand ?? '')));
                        $detail = trim((string) ($bundleRow['detail_display'] ?? ''));
                        $detailExtra = trim((string) ($bundleRow['detail_extra'] ?? ''));
                        if ($detailExtra !== '' && $detailExtra !== '-') {
                            $detail = $detail === '' || $detail === '-' ? $detailExtra : $detail . ' - ' . $detailExtra;
                        }
                        $materialId = is_object($model) && isset($model->id) ? (int) $model->id : null;

                        if ($brand === '' && $detail === '') {
                            continue;
                        }
                        if ($brand === '') {
                            $brand = '-';
                        }
                        if ($detail === '') {
                            $detail = '-';
                        }

                        $signature = strtolower(
                            ($materialId ?? 'null') .
                                '|' .
                                (preg_replace('/\s+/u', ' ', $brand) ?? $brand) .
                                '|' .
                                (preg_replace('/\s+/u', ' ', $detail) ?? $detail),
                        );
                        if (isset($variants[$materialKey][$signature])) {
                            continue;
                        }

                        $variants[$materialKey][$signature] = [
                            'id' => $materialId,
                            'brand' => $brand,
                            'detail' => $detail,
                            'signature' => $signature,
                        ];
                    }

                    foreach ($variants as $matKey => $grouped) {
                        $variants[$matKey] = array_values($grouped);
                    }

                    return $variants;
                };
                $buildRekapEntry = function ($project, $item, $key) use (
                    &$hasBrick,
                    &$hasCement,
                    &$hasSand,
                    &$hasCat,
                    &$hasCeramic,
                    &$hasNat,
                    $resolveBrickModelForRekap,
                    $formatBrickBrandForRekap,
                    $formatBrickDetailForRekap,
                    $rekapLegacyFieldMap,
                    $emptyRekapMaterialVariants,
                    $appendRekapMaterialVariant,
                    $extractBundleMaterialVariantsForRekap,
                ) {
                    $res = $item['result'];
                    $comboBrick = $resolveBrickModelForRekap($project, $item);

                    if (($res['total_bricks'] ?? 0) > 0) {
                        $hasBrick = true;
                    }
                    if (($res['cement_sak'] ?? 0) > 0) {
                        $hasCement = true;
                    }
                    if (($res['sand_m3'] ?? 0) > 0) {
                        $hasSand = true;
                    }
                    if (($res['cat_packages'] ?? 0) > 0) {
                        $hasCat = true;
                    }
                    if (($res['total_tiles'] ?? 0) > 0) {
                        $hasCeramic = true;
                    }
                    if (($res['grout_packages'] ?? 0) > 0) {
                        $hasNat = true;
                    }

                    $rekapEntry = [
                        'grand_total' => $item['result']['grand_total'] ?? null,
                        'filter_label' => $key,
                        'material_variants' => $emptyRekapMaterialVariants(),
                    ];
                    if (isset($item['frequency'])) {
                        $rekapEntry['frequency'] = $item['frequency'];
                    }

                    // Prefer brick from combination item to keep store-scoped consistency.
                    if ($comboBrick) {
                        $rekapEntry['brick_id'] = $comboBrick->id;
                        $rekapEntry['brick_brand'] = $formatBrickBrandForRekap($comboBrick);
                        $rekapEntry['brick_detail'] = $formatBrickDetailForRekap($comboBrick);
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'brick',
                            $comboBrick->id ?? null,
                            $rekapEntry['brick_brand'],
                            $rekapEntry['brick_detail'],
                        );
                    }

                    if (isset($item['cement'])) {
                        $rekapEntry['cement_id'] = $item['cement']->id;
                        $rekapEntry['cement_brand'] = $item['cement']->brand;
                        $rekapEntry['cement_detail'] =
                            ($item['cement']->color ?? '-') . ' - ' . ($item['cement']->package_weight_net + 0) . ' Kg';
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'cement',
                            $item['cement']->id ?? null,
                            $rekapEntry['cement_brand'],
                            $rekapEntry['cement_detail'],
                        );
                    }

                    if (isset($item['sand'])) {
                        $rekapEntry['sand_id'] = $item['sand']->id;
                        $rekapEntry['sand_brand'] = $item['sand']->brand;
                        $rekapEntry['sand_detail'] =
                            ($item['sand']->package_unit ?? '-') .
                            ' - ' .
                            (($item['sand']->package_volume ?? 0) > 0
                                ? $item['sand']->package_volume + 0 . ' M3'
                                : '-');
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'sand',
                            $item['sand']->id ?? null,
                            $rekapEntry['sand_brand'],
                            $rekapEntry['sand_detail'],
                        );
                    }

                    if (isset($item['cat'])) {
                        $cat = $item['cat'];
                        $rekapEntry['cat_id'] = $cat->id;
                        $rekapEntry['cat_brand'] = $cat->brand;
                        $catDetailParts = [];
                        $catDetailParts[] = $cat->sub_brand ?? '-';
                        if (!empty($cat->color_code)) {
                            $catDetailParts[] = $cat->color_code;
                        }
                        if (!empty($cat->color_name)) {
                            $catDetailParts[] = $cat->color_name;
                        }
                        $catPackageUnitDisplay = trim((string) ($cat->package_unit ?? ''));
                        if ($catPackageUnitDisplay === '') {
                            $catPackageUnitDisplay = '-';
                        }
                        $catGrossWeight = $cat->package_weight_gross ?? null;
                        $catGrossDisplay =
                            $catGrossWeight !== null && $catGrossWeight > 0
                                ? \App\Helpers\NumberHelper::format($catGrossWeight)
                                : '-';
                        $catDetailParts[] = $catPackageUnitDisplay . ' ( ' . $catGrossDisplay . ' Kg )';
                        $catVolumeUnit = trim((string) ($cat->volume_unit ?? 'L'));
                        if ($catVolumeUnit === '') {
                            $catVolumeUnit = 'L';
                        }
                        if (!empty($cat->volume)) {
                            $catDetailParts[] =
                                '( ' . \App\Helpers\NumberHelper::format($cat->volume) . ' ' . $catVolumeUnit . ' )';
                        } else {
                            $catDetailParts[] = '( - ' . $catVolumeUnit . ' )';
                        }
                        $catDetailParts[] =
                            'BB: ' . \App\Helpers\NumberHelper::format($cat->package_weight_net + 0) . ' kg';
                        $rekapEntry['cat_detail'] = implode(' - ', $catDetailParts);
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'cat',
                            $cat->id ?? null,
                            $rekapEntry['cat_brand'],
                            $rekapEntry['cat_detail'],
                        );
                    }

                    if (isset($item['ceramic'])) {
                        $rekapEntry['ceramic_id'] = $item['ceramic']->id;
                        $rekapEntry['ceramic_brand'] = $item['ceramic']->brand;
                        $rekapEntry['ceramic_detail'] =
                            ($item['ceramic']->color ?? '-') .
                            ' (' .
                            ($item['ceramic']->dimension_length + 0) .
                            'x' .
                            ($item['ceramic']->dimension_width + 0) .
                            ')';
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'ceramic',
                            $item['ceramic']->id ?? null,
                            $rekapEntry['ceramic_brand'],
                            $rekapEntry['ceramic_detail'],
                        );
                    }

                    if (isset($item['nat'])) {
                        $rekapEntry['nat_id'] = $item['nat']->id;
                        $rekapEntry['nat_brand'] = $item['nat']->brand;
                        $rekapEntry['nat_detail'] =
                            ($item['nat']->color ?? 'Nat') . ' (' . ($item['nat']->package_weight_net + 0) . ' kg)';
                        $appendRekapMaterialVariant(
                            $rekapEntry,
                            'nat',
                            $item['nat']->id ?? null,
                            $rekapEntry['nat_brand'],
                            $rekapEntry['nat_detail'],
                        );
                    }

                    // Bundle mode: keep all material variants per material type.
                    $bundleVariants = $extractBundleMaterialVariantsForRekap($item);
                    foreach ($bundleVariants as $materialKey => $variantRows) {
                        if (!is_array($variantRows) || empty($variantRows)) {
                            continue;
                        }
                        foreach ($variantRows as $variantRow) {
                            $appendRekapMaterialVariant(
                                $rekapEntry,
                                $materialKey,
                                $variantRow['id'] ?? null,
                                $variantRow['brand'] ?? '-',
                                $variantRow['detail'] ?? '-',
                            );
                        }

                        if ($materialKey === 'brick') {
                            $hasBrick = true;
                        } elseif ($materialKey === 'cement') {
                            $hasCement = true;
                        } elseif ($materialKey === 'sand') {
                            $hasSand = true;
                        } elseif ($materialKey === 'cat') {
                            $hasCat = true;
                        } elseif ($materialKey === 'ceramic') {
                            $hasCeramic = true;
                        } elseif ($materialKey === 'nat') {
                            $hasNat = true;
                        }
                    }

                    // Backward compatibility: keep legacy single fields from first variant.
                    foreach ($rekapLegacyFieldMap as $materialKey => $fieldMap) {
                        $firstVariant = $rekapEntry['material_variants'][$materialKey][0] ?? null;
                        if (!is_array($firstVariant)) {
                            continue;
                        }
                        if (empty($rekapEntry[$fieldMap['id']] ?? null) && !empty($firstVariant['id'])) {
                            $rekapEntry[$fieldMap['id']] = $firstVariant['id'];
                        }
                        if (empty(trim((string) ($rekapEntry[$fieldMap['brand']] ?? '')))) {
                            $rekapEntry[$fieldMap['brand']] = $firstVariant['brand'] ?? '-';
                        }
                        if (empty(trim((string) ($rekapEntry[$fieldMap['detail']] ?? '')))) {
                            $rekapEntry[$fieldMap['detail']] = $firstVariant['detail'] ?? '-';
                        }
                    }

                    return $rekapEntry;
                };
                $buildPartialRekapEntry = function ($key, array $models) use (
                    &$hasBrick,
                    &$hasCement,
                    &$hasSand,
                    &$hasCat,
                    &$hasCeramic,
                    &$hasNat,
                    $formatBrickBrandForRekap,
                    $formatBrickDetailForRekap,
                    $emptyRekapMaterialVariants,
                    $appendRekapMaterialVariant,
                ) {
                    $entry = [
                        'grand_total' => null,
                        'filter_label' => $key,
                        'material_variants' => $emptyRekapMaterialVariants(),
                    ];

                    if (!empty($models['brick'])) {
                        $brick = $models['brick'];
                        $hasBrick = true;
                        $entry['brick_id'] = $brick->id;
                        $entry['brick_brand'] = $formatBrickBrandForRekap($brick);
                        $entry['brick_detail'] = $formatBrickDetailForRekap($brick);
                        $appendRekapMaterialVariant(
                            $entry,
                            'brick',
                            $brick->id ?? null,
                            $entry['brick_brand'],
                            $entry['brick_detail'],
                        );
                    }

                    if (!empty($models['cement'])) {
                        $cement = $models['cement'];
                        $hasCement = true;
                        $entry['cement_id'] = $cement->id;
                        $entry['cement_brand'] = $cement->brand;
                        $entry['cement_detail'] =
                            ($cement->color ?? '-') . ' - ' . ($cement->package_weight_net + 0) . ' Kg';
                        $appendRekapMaterialVariant(
                            $entry,
                            'cement',
                            $cement->id ?? null,
                            $entry['cement_brand'],
                            $entry['cement_detail'],
                        );
                    }

                    if (!empty($models['sand'])) {
                        $sand = $models['sand'];
                        $hasSand = true;
                        $entry['sand_id'] = $sand->id;
                        $entry['sand_brand'] = $sand->brand;
                        $entry['sand_detail'] =
                            ($sand->package_unit ?? '-') .
                            ' - ' .
                            (($sand->package_volume ?? 0) > 0 ? $sand->package_volume + 0 . ' M3' : '-');
                        $appendRekapMaterialVariant(
                            $entry,
                            'sand',
                            $sand->id ?? null,
                            $entry['sand_brand'],
                            $entry['sand_detail'],
                        );
                    }

                    if (!empty($models['cat'])) {
                        $cat = $models['cat'];
                        $hasCat = true;
                        $entry['cat_id'] = $cat->id;
                        $entry['cat_brand'] = $cat->brand;
                        $catDetailParts = [];
                        $catDetailParts[] = $cat->sub_brand ?? '-';
                        if (!empty($cat->color_code)) {
                            $catDetailParts[] = $cat->color_code;
                        }
                        if (!empty($cat->color_name)) {
                            $catDetailParts[] = $cat->color_name;
                        }
                        $catPackageUnitDisplay = trim((string) ($cat->package_unit ?? ''));
                        if ($catPackageUnitDisplay === '') {
                            $catPackageUnitDisplay = '-';
                        }
                        $catGrossWeight = $cat->package_weight_gross ?? null;
                        $catGrossDisplay =
                            $catGrossWeight !== null && $catGrossWeight > 0
                                ? \App\Helpers\NumberHelper::format($catGrossWeight)
                                : '-';
                        $catDetailParts[] = $catPackageUnitDisplay . ' (' . $catGrossDisplay . ' Kg)';
                        $catVolumeUnit = trim((string) ($cat->volume_unit ?? 'L'));
                        if ($catVolumeUnit === '') {
                            $catVolumeUnit = 'L';
                        }
                        if (!empty($cat->volume)) {
                            $catDetailParts[] =
                                '(' . \App\Helpers\NumberHelper::format($cat->volume) . ' ' . $catVolumeUnit . ')';
                        } else {
                            $catDetailParts[] = '(- ' . $catVolumeUnit . ')';
                        }
                        $catDetailParts[] =
                            'BB: ' . \App\Helpers\NumberHelper::format($cat->package_weight_net + 0) . ' kg';
                        $entry['cat_detail'] = implode(' - ', $catDetailParts);
                        $appendRekapMaterialVariant(
                            $entry,
                            'cat',
                            $cat->id ?? null,
                            $entry['cat_brand'],
                            $entry['cat_detail'],
                        );
                    }

                    if (!empty($models['ceramic'])) {
                        $ceramic = $models['ceramic'];
                        $hasCeramic = true;
                        $entry['ceramic_id'] = $ceramic->id;
                        $entry['ceramic_brand'] = $ceramic->brand;
                        $entry['ceramic_detail'] =
                            ($ceramic->color ?? '-') .
                            ' (' .
                            ($ceramic->dimension_length + 0) .
                            'x' .
                            ($ceramic->dimension_width + 0) .
                            ')';
                        $appendRekapMaterialVariant(
                            $entry,
                            'ceramic',
                            $ceramic->id ?? null,
                            $entry['ceramic_brand'],
                            $entry['ceramic_detail'],
                        );
                    }

                    if (!empty($models['nat'])) {
                        $nat = $models['nat'];
                        $hasNat = true;
                        $entry['nat_id'] = $nat->id;
                        $entry['nat_brand'] = $nat->brand;
                        $entry['nat_detail'] = ($nat->color ?? 'Nat') . ' (' . ($nat->package_weight_net + 0) . ' kg)';
                        $appendRekapMaterialVariant(
                            $entry,
                            'nat',
                            $nat->id ?? null,
                            $entry['nat_brand'],
                            $entry['nat_detail'],
                        );
                    }

                    return $entry;
                };

                // Collect all combinations from all bricks
                $allCombinations = [];

                // DEBUG: Log all combination labels
                $allLabels = [];
                foreach ($projects as $project) {
                    foreach ($project['combinations'] as $label => $items) {
                        $allLabels[] = $label;
                    }
                }
                \Log::info('All combination labels in view', ['labels' => $allLabels]);

                foreach ($projects as $project) {
                    foreach ($project['combinations'] as $label => $items) {
                        foreach ($items as $item) {
                            $labelParts = array_map('trim', explode('=', $label));

                            foreach ($labelParts as $singleLabel) {
                                foreach ($filterCategories as $filterType) {
                                    if (str_starts_with($singleLabel, $filterType)) {
                                        preg_match('/' . $filterType . '\s+(\d+)/', $singleLabel, $matches);
                                        if (isset($matches[1])) {
                                            $number = $matches[1];
                                            $key = $filterType . ' ' . $number;

                                            // Store all combinations for later processing
                                            if (!isset($allCombinations[$key])) {
                                                $allCombinations[$key] = [];
                                            }

                                            $allCombinations[$key][] = [
                                                'project' => $project,
                                                'item' => $item,
                                            ];
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                // DEBUG: Show what combinations were found
                \Log::info('All combinations collected for rekap', [
                    'keys' => array_keys($allCombinations),
                    'counts' => array_map('count', $allCombinations),
                ]);

                // Collect Populer candidates based on per-material rank (can create new combinations)
                $populerRankedEntries = [];
                $populerDetailMap = [];
                $requiredMaterials = \App\Services\FormulaRegistry::materialsFor($workType);
                if (empty($requiredMaterials)) {
                    $requiredMaterials = ['brick', 'cement', 'sand'];
                }

                $fallbackMaterialIds = [
                    'brick' => [],
                    'cement' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'nat' => [],
                ];

                foreach ($projects as $project) {
                    if (!empty($project['brick'])) {
                        $fallbackMaterialIds['brick'][$project['brick']->id] = true;
                    }
                    foreach ($project['combinations'] as $label => $items) {
                        foreach ($items as $item) {
                            if (!empty($item['cement'])) {
                                $fallbackMaterialIds['cement'][$item['cement']->id] = true;
                            }
                            if (!empty($item['sand'])) {
                                $fallbackMaterialIds['sand'][$item['sand']->id] = true;
                            }
                            if (!empty($item['cat'])) {
                                $fallbackMaterialIds['cat'][$item['cat']->id] = true;
                            }
                            if (!empty($item['ceramic'])) {
                                $fallbackMaterialIds['ceramic'][$item['ceramic']->id] = true;
                            }
                            if (!empty($item['nat'])) {
                                $fallbackMaterialIds['nat'][$item['nat']->id] = true;
                            }
                        }
                    }
                }

                // Usage percentage helpers: GROUP BY BRAND (Normalized)
                // This ensures that "Semen Gresik" from Store A (ID 1) matches "Semen Gresik" from Store B (ID 2)
                $materialBrandUsage = [
                    'brick' => [],
                    'cement' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'nat' => [],
                ];

                // Usage Totals per Type
                $usageTotals = [
                    'brick' => 0,
                    'cement' => 0,
                    'sand' => 0,
                    'cat' => 0,
                    'ceramic' => 0,
                    'nat' => 0,
                ];

                // 1. Load ALL historical IDs involved to get their brands
                foreach ($materialUsage as $type => $usageMap) {
                    $ids = array_keys($usageMap);
                    if (empty($ids) || empty($materialModelMap[$type])) {
                        continue;
                    }

                    // Fetch brands for these IDs
                    $models = $materialModelMap[$type]::whereIn('id', $ids)->get(['id', 'brand']);

                    foreach ($models as $model) {
                        $id = $model->id;
                        $count = $usageMap[$id] ?? 0;
                        $brand = strtoupper(trim($model->brand ?? ''));

                        if ($brand !== '') {
                            if (!isset($materialBrandUsage[$type][$brand])) {
                                $materialBrandUsage[$type][$brand] = 0;
                            }
                            $materialBrandUsage[$type][$brand] += $count;
                        }
                    }

                    // Calculate Total Usage for this Material Type
                    $usageTotals[$type] = array_sum($materialBrandUsage[$type]);
                }

                // 2. Helper to Format Percent based on BRAND name
                $formatUsagePercent = function (string $type, $modelOrBrand) use ($materialBrandUsage, $usageTotals) {
                    if (empty($usageTotals[$type]) || empty($modelOrBrand)) {
                        return null;
                    }

                    $brand = '';
                    if (is_string($modelOrBrand)) {
                        $brand = strtoupper(trim($modelOrBrand));
                    } elseif (is_object($modelOrBrand)) {
                        $brand = strtoupper(trim($modelOrBrand->brand ?? ''));
                    }

                    if ($brand === '') {
                        return null;
                    }

                    $count = $materialBrandUsage[$type][$brand] ?? 0;

                    if ($count <= 0) {
                        return null;
                    }

                    // Threshold: Only show if > 5% popularity to reduce noise
                    $percent = ($count / $usageTotals[$type]) * 100;
                    if ($percent < 5) {
                        return null;
                    }

                    return number_format($percent, 0);
                };

                $resolveRankedUniqueIds = function (
                    string $materialType,
                    array $usageMap,
                    array $fallbackMap,
                    string $modelClass,
                    int $limit = 3,
                ) use ($workType): array {
                    $ids = !empty($usageMap) ? array_keys($usageMap) : array_keys($fallbackMap);
                    if (empty($ids)) {
                        return [];
                    }

                    $models = $modelClass::whereIn('id', $ids)->get()->keyBy('id');
                    $cacheKey = 'populer_rank:' . ($workType ?? 'all') . ':' . $materialType;
                    $previousOrder = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
                    $previousIndex = is_array($previousOrder) ? array_flip($previousOrder) : [];

                    if (!empty($usageMap)) {
                        usort($ids, function ($a, $b) use ($usageMap, $previousIndex) {
                            $freqCompare = ($usageMap[$b] ?? 0) <=> ($usageMap[$a] ?? 0);
                            if ($freqCompare !== 0) {
                                return $freqCompare;
                            }
                            $aIndex = $previousIndex[$a] ?? PHP_INT_MAX;
                            $bIndex = $previousIndex[$b] ?? PHP_INT_MAX;
                            if ($aIndex !== $bIndex) {
                                return $aIndex <=> $bIndex;
                            }
                            return $a <=> $b;
                        });
                    } elseif (!empty($previousIndex)) {
                        usort($ids, function ($a, $b) use ($previousIndex) {
                            $aIndex = $previousIndex[$a] ?? PHP_INT_MAX;
                            $bIndex = $previousIndex[$b] ?? PHP_INT_MAX;
                            if ($aIndex !== $bIndex) {
                                return $aIndex <=> $bIndex;
                            }
                            return $a <=> $b;
                        });
                    }

                    $unique = [];
                    $seenBrands = [];
                    foreach ($ids as $id) {
                        $model = $models->get($id);
                        if (!$model) {
                            continue;
                        }
                        $brand = $model->brand ?? null;
                        if (!$brand) {
                            continue;
                        }
                        if (isset($seenBrands[$brand])) {
                            continue;
                        }
                        $seenBrands[$brand] = true;
                        $unique[] = $id;
                        if (count($unique) >= $limit) {
                            break;
                        }
                    }
                    if (!empty($usageMap)) {
                        \Illuminate\Support\Facades\Cache::forever($cacheKey, $unique);
                    }
                    return $unique;
                };
                $getRankedId = function (array $list, int $index) {
                    return $list[$index] ?? null;
                };
                $useStoreValidatedPopularRows = true;

                if (!$useStoreValidatedPopularRows && in_array('Populer', $filterCategories, true) && $hasHistoricalUsage) {
                    $comboService = app(\App\Services\Calculation\CombinationGenerationService::class);
                    $isBricklessWork = $isBrickless ?? false;
                    $emptyEloquent = new \Illuminate\Database\Eloquent\Collection();

                    $rankedIds = [
                        'brick' => in_array('brick', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'brick',
                                $materialUsage['brick'],
                                $fallbackMaterialIds['brick'],
                                \App\Models\Brick::class,
                            )
                            : [],
                        'cement' => in_array('cement', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'cement',
                                $materialUsage['cement'],
                                $fallbackMaterialIds['cement'],
                                \App\Models\Cement::class,
                            )
                            : [],
                        'sand' => in_array('sand', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'sand',
                                $materialUsage['sand'],
                                $fallbackMaterialIds['sand'],
                                \App\Models\Sand::class,
                            )
                            : [],
                        'cat' => in_array('cat', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'cat',
                                $materialUsage['cat'],
                                $fallbackMaterialIds['cat'],
                                \App\Models\Cat::class,
                            )
                            : [],
                        'ceramic' => in_array('ceramic', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'ceramic',
                                $materialUsage['ceramic'],
                                $fallbackMaterialIds['ceramic'],
                                \App\Models\Ceramic::class,
                            )
                            : [],
                        'nat' => in_array('nat', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'nat',
                                $materialUsage['nat'],
                                $fallbackMaterialIds['nat'],
                                \App\Models\Nat::class,
                            )
                            : [],
                    ];
                    if (($workType ?? '') === 'grout_tile') {
                        $rankedIds['ceramic'] = [];
                    }

                    $defaultBrick = $projects[0]['brick'] ?? \App\Models\Brick::first();
                    $usedSignatures = [];
                    $fallbackCeramic = null;
                    if (($workType ?? '') === 'grout_tile') {
                        if (!empty($requestData['ceramic_id'])) {
                            $fallbackCeramic = \App\Models\Ceramic::find($requestData['ceramic_id']);
                        }
                        if (!$fallbackCeramic) {
                            $fallbackCeramic = \App\Models\Ceramic::whereNotNull('dimension_thickness')
                                ->where('dimension_thickness', '>', 0)
                                ->orderBy('id')
                                ->first();
                        }
                    }

                    for ($rankIndex = 0; $rankIndex < 3; $rankIndex++) {
                        $brickId = in_array('brick', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['brick'], $rankIndex)
                            : null;
                        $cementId = in_array('cement', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['cement'], $rankIndex)
                            : null;
                        $sandId = in_array('sand', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['sand'], $rankIndex)
                            : null;
                        $catId = in_array('cat', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['cat'], $rankIndex)
                            : null;
                        $ceramicId = in_array('ceramic', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['ceramic'], $rankIndex)
                            : null;
                        $natId = in_array('nat', $requiredMaterials, true)
                            ? $getRankedId($rankedIds['nat'], $rankIndex)
                            : null;

                        $models = [
                            'brick' => null,
                            'cement' => null,
                            'sand' => null,
                            'cat' => null,
                            'ceramic' => null,
                            'nat' => null,
                        ];
                        $hasAnyMaterial = false;
                        $isComplete = true;

                        if (in_array('brick', $requiredMaterials, true)) {
                            if ($brickId) {
                                $models['brick'] = \App\Models\Brick::find($brickId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['brick']);
                            } else {
                                $isComplete = false;
                            }
                        } else {
                            $models['brick'] = $defaultBrick;
                        }

                        if (in_array('cement', $requiredMaterials, true)) {
                            if ($cementId) {
                                $models['cement'] = \App\Models\Cement::find($cementId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['cement']);
                            } else {
                                $isComplete = false;
                            }
                        }

                        if (in_array('sand', $requiredMaterials, true)) {
                            if ($sandId) {
                                $models['sand'] = \App\Models\Sand::find($sandId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['sand']);
                            } else {
                                $isComplete = false;
                            }
                        }

                        if (in_array('cat', $requiredMaterials, true)) {
                            if ($catId) {
                                $models['cat'] = \App\Models\Cat::find($catId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['cat']);
                            } else {
                                $isComplete = false;
                            }
                        }

                        if (in_array('ceramic', $requiredMaterials, true)) {
                            if ($ceramicId) {
                                $models['ceramic'] = \App\Models\Ceramic::find($ceramicId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['ceramic']);
                            } elseif (($workType ?? '') === 'grout_tile' && $fallbackCeramic) {
                                $models['ceramic'] = $fallbackCeramic;
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['ceramic']);
                            } else {
                                $isComplete = false;
                            }
                        }

                        if (in_array('nat', $requiredMaterials, true)) {
                            if ($natId) {
                                $models['nat'] = \App\Models\Nat::find($natId);
                                $hasAnyMaterial = $hasAnyMaterial || !empty($models['nat']);
                            } else {
                                $isComplete = false;
                            }
                        }

                        if (!$hasAnyMaterial) {
                            continue;
                        }

                        $brick = $defaultBrick;
                        if (in_array('brick', $requiredMaterials, true)) {
                            $brick = $models['brick'] ?? $defaultBrick;
                        }
                        if (!$brick) {
                            if ($isComplete) {
                                continue;
                            }
                        }

                        if ($isComplete) {
                            $cements =
                                in_array('cement', $requiredMaterials, true) && $cementId
                                    ? \App\Models\Cement::where('id', $cementId)->get()
                                    : $emptyEloquent;
                            $sands =
                                in_array('sand', $requiredMaterials, true) && $sandId
                                    ? \App\Models\Sand::where('id', $sandId)->get()
                                    : $emptyEloquent;
                            $cats =
                                in_array('cat', $requiredMaterials, true) && $catId
                                    ? \App\Models\Cat::where('id', $catId)->get()
                                    : $emptyEloquent;
                            $ceramics = $emptyEloquent;
                            if (in_array('ceramic', $requiredMaterials, true)) {
                                if ($ceramicId) {
                                    $ceramics = \App\Models\Ceramic::where('id', $ceramicId)->get();
                                } elseif (($workType ?? '') === 'grout_tile' && $fallbackCeramic) {
                                    $ceramics = new \Illuminate\Database\Eloquent\Collection([$fallbackCeramic]);
                                }
                            }
                            $nats =
                                in_array('nat', $requiredMaterials, true) && $natId
                                    ? \App\Models\Nat::where('id', $natId)->get()
                                    : $emptyEloquent;

                            $combos = $comboService->calculateCombinationsFromMaterials(
                                $brick,
                                $requestData,
                                $cements,
                                $sands,
                                $cats,
                                $ceramics,
                                $nats,
                                'Populer',
                                1,
                            );

                            if (!empty($combos)) {
                                $combo = $combos[0];
                                $comboSignature = implode('-', [
                                    $isBricklessWork ? 0 : ($brick?->id ?? 0),
                                    $combo['cement']->id ?? 0,
                                    $combo['sand']->id ?? 0,
                                    $combo['cat']->id ?? 0,
                                    $combo['ceramic']->id ?? 0,
                                    $combo['nat']->id ?? 0,
                                ]);

                                if (!isset($usedSignatures[$comboSignature])) {
                                    $usedSignatures[$comboSignature] = true;
                                    $projectData = [];
                                    if ($brick && !$isBricklessWork) {
                                        $projectData['brick'] = $brick;
                                    }
                                    $populerRankedEntries[] = [
                                        'project' => $projectData,
                                        'item' => $combo,
                                    ];
                                    continue;
                                }
                            }
                        }

                        $partialEntry = $buildPartialRekapEntry('Populer', $models);
                        $signatureParts = [
                            $isBricklessWork ? 0 : $partialEntry['brick_id'] ?? 0,
                            $partialEntry['cement_id'] ?? 0,
                            $partialEntry['sand_id'] ?? 0,
                            $partialEntry['cat_id'] ?? 0,
                            $partialEntry['ceramic_id'] ?? 0,
                            $partialEntry['nat_id'] ?? 0,
                        ];
                        $partialSignature = implode('-', $signatureParts);
                        if (isset($usedSignatures[$partialSignature])) {
                            continue;
                        }
                        $usedSignatures[$partialSignature] = true;
                        $projectDataPartial = [];
                        if ($brick && !$isBricklessWork) {
                            $projectDataPartial['brick'] = $brick;
                        }

                        // Try to calculate grand total if we have complete materials
                        // For brickless works (e.g., grout_tile), check if all required materials are available
                        $canCalculate = true;
                        foreach ($requiredMaterials as $matType) {
                            if (
                                $matType === 'ceramic' &&
                                ($workType ?? '') === 'grout_tile' &&
                                !empty($fallbackCeramic)
                            ) {
                                // For grout_tile, fallback ceramic is acceptable
                                continue;
                            }
                            if (empty($models[$matType])) {
                                $canCalculate = false;
                                break;
                            }
                        }

                        $itemData = ['result' => ['grand_total' => null]] + $partialEntry;

                        if ($canCalculate) {
                            // Try to calculate the combination
                            $cements = !empty($models['cement'])
                                ? new \Illuminate\Database\Eloquent\Collection([$models['cement']])
                                : $emptyEloquent;
                            $sands = !empty($models['sand'])
                                ? new \Illuminate\Database\Eloquent\Collection([$models['sand']])
                                : $emptyEloquent;
                            $cats = !empty($models['cat'])
                                ? new \Illuminate\Database\Eloquent\Collection([$models['cat']])
                                : $emptyEloquent;
                            $ceramics = !empty($models['ceramic'])
                                ? new \Illuminate\Database\Eloquent\Collection([$models['ceramic']])
                                : (($workType ?? '') === 'grout_tile' && $fallbackCeramic
                                    ? new \Illuminate\Database\Eloquent\Collection([$fallbackCeramic])
                                    : $emptyEloquent);
                            $nats = !empty($models['nat'])
                                ? new \Illuminate\Database\Eloquent\Collection([$models['nat']])
                                : $emptyEloquent;

                            try {
                                $calculatedCombos = $comboService->calculateCombinationsFromMaterials(
                                    $brick,
                                    $requestData,
                                    $cements,
                                    $sands,
                                    $cats,
                                    $ceramics,
                                    $nats,
                                    'Populer',
                                    1,
                                );

                                if (!empty($calculatedCombos)) {
                                    $itemData = $calculatedCombos[0];
                                }
                            } catch (\Exception $e) {
                                // If calculation fails, keep grand_total as null
                            }
                        }

                        $populerRankedEntries[] = [
                            'project' => $projectDataPartial,
                            'item' => $itemData,
                            'partial_entry' => $partialEntry,
                        ];
                    }
                }

                // Second pass: Select and renumber combinations for each filter type
                // Track sequential numbering per filter type
                $filterTypeNumbers = [];

                foreach ($allCombinations as $key => $combinations) {
                    $filterType = preg_replace('/\s+\d+.*$/', '', $key);

                    // Initialize counter for this filter type if not exists
                    if (!isset($filterTypeNumbers[$filterType])) {
                        $filterTypeNumbers[$filterType] = 0;
                    }

                    if ($filterType === 'Populer') {
                        $selectedCombination = null;
                        foreach ($combinations as $combo) {
                            $currentTotal = $combo['item']['result']['grand_total'] ?? null;
                            if ($currentTotal === null) {
                                continue;
                            }
                            if (
                                !$selectedCombination ||
                                $currentTotal < ($selectedCombination['item']['result']['grand_total'] ?? PHP_INT_MAX)
                            ) {
                                $selectedCombination = $combo;
                            }
                        }

                        if ($selectedCombination) {
                            $filterTypeNumbers[$filterType]++;
                            $newKey = $filterType . ' ' . $filterTypeNumbers[$filterType];
                            $project = $selectedCombination['project'];
                            $item = $selectedCombination['item'];
                            $populerDetailMap[$newKey] = $selectedCombination;
                            $globalRekapData[$newKey] = $buildRekapEntry($project, $item, $newKey);
                            $detailCombinationMap[$newKey] = [
                                'project' => $project,
                                'item' => $item,
                            ];
                        }

                        continue;
                    } else {
                        // For other filter types: use price-based selection
                        $selectedCombination = null;

                        foreach ($combinations as $combo) {
                            if (!$selectedCombination) {
                                $selectedCombination = $combo;
                                continue;
                            }

                            $currentTotal = $combo['item']['result']['grand_total'];
                            $selectedTotal = $selectedCombination['item']['result']['grand_total'];

                            if ($filterType === 'Termahal') {
                                // Pick the HIGHEST price
                                if ($currentTotal > $selectedTotal) {
                                    $selectedCombination = $combo;
                                }
                            } else {
                                // For Ekonomis, Average: pick the LOWEST price
                                if ($currentTotal < $selectedTotal) {
                                    $selectedCombination = $combo;
                                }
                            }
                        }

                        // Store the selected combination with renumbered key
                        if ($selectedCombination) {
                            $filterTypeNumbers[$filterType]++;
                            $newKey = $filterType . ' ' . $filterTypeNumbers[$filterType];

                            $project = $selectedCombination['project'];
                            $item = $selectedCombination['item'];
                            $globalRekapData[$newKey] = $buildRekapEntry($project, $item, $newKey);
                            $detailCombinationMap[$newKey] = [
                                'project' => $project,
                                'item' => $item,
                            ];
                        }
                    }
                }

                if (!$useStoreValidatedPopularRows && !empty($populerRankedEntries)) {
                    $rank = 0;
                    $populerHasCompleteMaterials = function ($item, $project) use ($requiredMaterials, $isBricklessWork, $workType) {
                        if (empty($item) || empty($item['result']) || !isset($item['result']['grand_total'])) {
                            return false;
                        }
                        if ($item['result']['grand_total'] === null) {
                            return false;
                        }
                        foreach ($requiredMaterials as $matType) {
                            if ($matType === 'brick') {
                                if ($isBricklessWork) {
                                    continue;
                                }
                                if (empty($project['brick'])) {
                                    return false;
                                }
                                continue;
                            }
                            if ($matType === 'ceramic' && ($workType ?? '') === 'grout_tile') {
                                if (empty($item['ceramic'])) {
                                    return false;
                                }
                                continue;
                            }
                            if (empty($item[$matType])) {
                                return false;
                            }
                        }
                        return true;
                    };
                    foreach ($populerRankedEntries as $entry) {
                        $project = $entry['project'] ?? [];
                        $item = $entry['item'] ?? null;
                        if (!$populerHasCompleteMaterials($item, $project)) {
                            continue;
                        }
                        $rank++;
                        $newKey = 'Populer ' . $rank;
                        $populerDetailMap[$newKey] = $entry;
                        $globalRekapData[$newKey] = $buildRekapEntry($project, $item, $newKey);
                        $detailCombinationMap[$newKey] = [
                            'project' => $project,
                            'item' => $item,
                        ];
                    }
                }

                // Fallback visual rank untuk Populer di Rekap Global:
                // Tampilkan identitas material populer HANYA dari histori, bukan fallback.
                if (in_array('Populer', $filterCategories, true) && $hasHistoricalUsage && !$isStoreScopedView) {
                    $populerRankedIdsForDisplay = [
                        'brick' => in_array('brick', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'brick',
                                $materialUsage['brick'],
                                $fallbackMaterialIds['brick'],
                                \App\Models\Brick::class,
                            )
                            : [],
                        'cement' => in_array('cement', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'cement',
                                $materialUsage['cement'],
                                $fallbackMaterialIds['cement'],
                                \App\Models\Cement::class,
                            )
                            : [],
                        'sand' => in_array('sand', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'sand',
                                $materialUsage['sand'],
                                $fallbackMaterialIds['sand'],
                                \App\Models\Sand::class,
                            )
                            : [],
                        'cat' => in_array('cat', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'cat',
                                $materialUsage['cat'],
                                $fallbackMaterialIds['cat'],
                                \App\Models\Cat::class,
                            )
                            : [],
                        'ceramic' => in_array('ceramic', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'ceramic',
                                $materialUsage['ceramic'],
                                $fallbackMaterialIds['ceramic'],
                                \App\Models\Ceramic::class,
                            )
                            : [],
                        'nat' => in_array('nat', $requiredMaterials, true)
                            ? $resolveRankedUniqueIds(
                                'nat',
                                $materialUsage['nat'],
                                $fallbackMaterialIds['nat'],
                                \App\Models\Nat::class,
                            )
                            : [],
                    ];

                    for ($rankIndex = 0; $rankIndex < 3; $rankIndex++) {
                        $newKey = 'Populer ' . ($rankIndex + 1);
                        if (isset($globalRekapData[$newKey])) {
                            continue;
                        }

                        $models = [
                            'brick' => null,
                            'cement' => null,
                            'sand' => null,
                            'cat' => null,
                            'ceramic' => null,
                            'nat' => null,
                        ];

                        foreach (['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'] as $matType) {
                            if (!in_array($matType, $requiredMaterials, true)) {
                                continue;
                            }
                            $materialId = $getRankedId($populerRankedIdsForDisplay[$matType] ?? [], $rankIndex);
                            $modelClass = $materialModelMap[$matType] ?? null;
                            if ($materialId && $modelClass) {
                                $models[$matType] = $modelClass::find($materialId);
                            }
                        }

                        $hasAnyIdentity = false;
                        foreach ($models as $model) {
                            if (!empty($model)) {
                                $hasAnyIdentity = true;
                                break;
                            }
                        }
                        if (!$hasAnyIdentity) {
                            continue;
                        }

                        $globalRekapData[$newKey] = $buildPartialRekapEntry($newKey, $models);
                    }
                }

                $priceRankFilters = ['Ekonomis', 'Average', 'Termahal'];
                $needsPriceRanks = count(array_intersect($filterCategories, $priceRankFilters)) > 0;
                if ($needsPriceRanks) {
                    $allPriceCandidates = [];
                    foreach ($projects as $project) {
                        foreach ($project['combinations'] as $label => $items) {
                            foreach ($items as $item) {
                                $allPriceCandidates[] = [
                                    'project' => $project,
                                    'item' => $item,
                                    'label' => $label,
                                    'grand_total' => (float) ($item['result']['grand_total'] ?? 0),
                                ];
                            }
                        }
                    }

                    usort($allPriceCandidates, function ($a, $b) {
                        if ($a['grand_total'] === $b['grand_total']) {
                            return strcmp($a['label'], $b['label']);
                        }
                        return $a['grand_total'] <=> $b['grand_total'];
                    });

                    $totalCandidates = count($allPriceCandidates);
                    if ($totalCandidates > 0) {
                        $EkonomisLimit = min(3, $totalCandidates);
                        $TermahalCount = min(3, $totalCandidates);

                        if (in_array('Ekonomis', $filterCategories, true)) {
                            for ($i = 0; $i < $EkonomisLimit; $i++) {
                                $key = 'Ekonomis ' . ($i + 1);
                                $combo = $allPriceCandidates[$i];
                                $globalRekapData[$key] = $buildRekapEntry($combo['project'], $combo['item'], $key);
                                $detailCombinationMap[$key] = [
                                    'project' => $combo['project'],
                                    'item' => $combo['item'],
                                ];
                            }
                        }

                        if (in_array('Termahal', $filterCategories, true)) {
                            for ($i = 0; $i < $TermahalCount; $i++) {
                                $rank = $i + 1;
                                $candidateIndex = $totalCandidates - 1 - $i;
                                $key = 'Termahal ' . $rank;
                                $combo = $allPriceCandidates[$candidateIndex];
                                $globalRekapData[$key] = $buildRekapEntry($combo['project'], $combo['item'], $key);
                                $detailCombinationMap[$key] = [
                                    'project' => $combo['project'],
                                    'item' => $combo['item'],
                                ];
                            }
                        }

                        if (in_array('Average', $filterCategories, true)) {
                            $sumTotal = array_sum(array_map(fn($row) => $row['grand_total'], $allPriceCandidates));
                            $averageTotal = $totalCandidates > 0 ? $sumTotal / $totalCandidates : 0;
                            $closestIndex = 0;
                            $closestDiff = null;

                            foreach ($allPriceCandidates as $idx => $combo) {
                                $diff = abs($combo['grand_total'] - $averageTotal);
                                if ($closestDiff === null || $diff < $closestDiff) {
                                    $closestDiff = $diff;
                                    $closestIndex = $idx;
                                }
                            }

                            $averageRank = 1;
                            if (isset($allPriceCandidates[$closestIndex])) {
                                $combo = $allPriceCandidates[$closestIndex];
                                $key = 'Average 1';
                                $globalRekapData[$key] = $buildRekapEntry($combo['project'], $combo['item'], $key);
                                $detailCombinationMap[$key] = [
                                    'project' => $combo['project'],
                                    'item' => $combo['item'],
                                ];
                                $lastPrice = $combo['grand_total'];
                                $averageRank = 2;

                                for ($i = $closestIndex + 1; $i < $totalCandidates && $averageRank <= 3; $i++) {
                                    $candidatePrice = $allPriceCandidates[$i]['grand_total'];
                                    if ($candidatePrice <= $lastPrice) {
                                        continue;
                                    }
                                    $key = 'Average ' . $averageRank;
                                    $globalRekapData[$key] = $buildRekapEntry(
                                        $allPriceCandidates[$i]['project'],
                                        $allPriceCandidates[$i]['item'],
                                        $key,
                                    );
                                    $detailCombinationMap[$key] = [
                                        'project' => $allPriceCandidates[$i]['project'],
                                        'item' => $allPriceCandidates[$i]['item'],
                                    ];
                                    $lastPrice = $candidatePrice;
                                    $averageRank++;
                                }
                            }
                        }
                    }
                }

                $getDisplayKeys = function ($filterType) {
                    $maxCount = $filterType === 'Custom' ? 1 : 3;
                    $fallback = [];
                    for ($i = 1; $i <= $maxCount; $i++) {
                        $fallback[] = $filterType . ' ' . $i;
                    }
                    return $fallback;
                };

                $workTypeForGlobalRekap = $requestData['work_type'] ?? '';
                $forceShowEmptyPopularRows = in_array(
                    $workTypeForGlobalRekap,
                    ['tile_installation', 'plinth_ceramic'],
                    true,
                );
                if ($forceShowEmptyPopularRows) {
                    $requiredMaterialsForForcedColumns = \App\Services\FormulaRegistry::materialsFor(
                        $workTypeForGlobalRekap,
                    );
                    if (empty($requiredMaterialsForForcedColumns)) {
                        $requiredMaterialsForForcedColumns = ['brick', 'cement', 'sand'];
                    }
                    $hasBrick = $hasBrick || in_array('brick', $requiredMaterialsForForcedColumns, true);
                    $hasCement = $hasCement || in_array('cement', $requiredMaterialsForForcedColumns, true);
                    $hasSand = $hasSand || in_array('sand', $requiredMaterialsForForcedColumns, true);
                    $hasCat = $hasCat || in_array('cat', $requiredMaterialsForForcedColumns, true);
                    $hasCeramic = $hasCeramic || in_array('ceramic', $requiredMaterialsForForcedColumns, true);
                    $hasNat = $hasNat || in_array('nat', $requiredMaterialsForForcedColumns, true);
                }

                if (($workType ?? '') === 'grout_tile') {
                    $hasBrick = false;
                    $hasCeramic = false;
                }

                // Generate color mapping for combinations
                $globalColorMap = [];
                $brickColorMap = [];
                $cementColorMap = [];
                $sandColorMap = [];

                // BATA: Pastel Hangat (Nuansa Tanah Liat & Kemerahan)
                // Variasi: Dari Pink Salem ke Coklat Susu
                $brickColors = [
                    '#FFAB91', // Deep Orange lighten-3 (Salem Oranye) -> Beda dari pink
                    '#F48FB1', // Pink lighten-3 (Pink Permen) -> Pink standar
                    '#BCAAA4', // Brown lighten-3 (Coklat Mocca) -> Netral hangat
                    '#EF9A9A', // Red lighten-3 (Merah Pudar) -> Merah lembut
                    '#E1BEE7', // Purple lighten-4 (Ungu Anggrek Pudar) -> Sentuhan beda
                    '#FFCCBC', // Deep Orange lighten-4 (Peach Pucat)
                    '#D7CCC8', // Brown lighten-4 (Coklat Abu/Taupe)
                    '#FF8A80', // Red Accent-1 (Coral Lembut)
                ];

                // SEMEN: Pastel Dingin (Nuansa Beton & Langit)
                // Variasi: Dari Abu, Ungu, ke Hijau Mint
                $cementColors = [
                    '#B0BEC5', // Blue Grey lighten-3 (Abu Semen Standar)
                    '#90CAF9', // Blue lighten-3 (Biru Langit) -> Sangat biru
                    '#CE93D8', // Purple lighten-3 (Ungu Lavender) -> Pembeda utama
                    '#80CBC4', // Teal lighten-3 (Hijau Tosca Pudar) -> Nuansa kehijauan
                    '#CFD8DC', // Blue Grey lighten-4 (Abu Perak)
                    '#9FA8DA', // Indigo lighten-3 (Biru Ungu/Periwinkle)
                    '#B3E5FC', // Light Blue lighten-3 (Biru Es)
                    '#81D4FA', // Light Blue lighten-2 (Biru Awan)
                ];

                // PASIR: Pastel Alam (Nuansa Gurun & Tumbuhan)
                // Variasi: Dari Kuning Mentega ke Hijau Pistachio
                $sandColors = [
                    '#FFF59D', // Yellow lighten-3 (Kuning Kenari) -> Kuning jelas
                    '#AED581', // Light Green lighten-2 (Hijau Pistachio) -> Hijau jelas
                    '#FFE0B2', // Orange lighten-4 (Krem Biscuits) -> Oranye pudar
                    '#DCE775', // Lime lighten-2 (Hijau Pupus) -> Hijau kekuningan
                    '#FFF176', // Yellow lighten-2 (Kuning Jagung Muda)
                    '#C5E1A5', // Light Green lighten-3 (Hijau Melon)
                    '#FFE082', // Amber lighten-3 (Kuning Telur) -> Lebih gelap
                    '#F0F4C3', // Lime lighten-4 (Putih Tulang Kehijauan)
                ];

                // CAT: Pastel Cerah (Nuansa Dekoratif)
                $catColors = [
                    '#F8BBD0', // Pink lighten-4
                    '#E1BEE7', // Purple lighten-4
                    '#D1C4E9', // Deep Purple lighten-4
                    '#C5CAE9', // Indigo lighten-4
                    '#BBDEFB', // Blue lighten-4
                    '#B2EBF2', // Cyan lighten-4
                    '#B2DFDB', // Teal lighten-4
                    '#C8E6C9', // Green lighten-4
                ];

                // KERAMIK: Pastel Dingin/Netral
                $ceramicColors = [
                    '#E0F7FA', // Cyan lighten-5
                    '#E1F5FE', // Light Blue lighten-5
                    '#F3E5F5', // Purple lighten-5
                    '#FBE9E7', // Deep Orange lighten-5
                    '#ECEFF1', // Blue Grey lighten-5
                    '#FAFAFA', // Grey lighten-5
                    '#FFF3E0', // Orange lighten-5
                    '#E8EAF6', // Indigo lighten-5
                ];

                // NAT: Pastel Gelap/Kontras
                $natColors = [
                    '#CFD8DC', // Blue Grey lighten-4
                    '#B0BEC5', // Blue Grey lighten-3
                    '#90A4AE', // Blue Grey lighten-2
                    '#78909C', // Blue Grey lighten-1
                    '#D7CCC8', // Brown lighten-4
                    '#BCAAA4', // Brown lighten-3
                    '#A1887F', // Brown lighten-2
                    '#8D6E63', // Brown lighten-1
                ];

                // Grand Total: Use combined palette
                $availableColors = array_merge(
                    $brickColors,
                    $cementColors,
                    $sandColors,
                    $catColors,
                    $ceramicColors,
                    $natColors,
                );

                $signatureWorkType = $requestData['work_type'] ?? 'unknown';
                $buildCombinationSignature = function ($row) use ($signatureWorkType) {
                    // For grout_tile, ceramic_id is not relevant for matching (only used for dimensions)
                    // Only nat_id matters for identifying the same combination
                    if ($signatureWorkType === 'grout_tile') {
                        return implode('-', [
                            $signatureWorkType,
                            0, // brick_id (not used)
                            0, // cement_id (not used)
                            0, // sand_id (not used)
                            0, // cat_id (not used)
                            0, // ceramic_id (ignore - only for dimensions)
                            $row['nat_id'] ?? 0, // nat_id (the only material that matters)
                        ]);
                    }

                    return implode('-', [
                        $signatureWorkType,
                        $row['brick_id'] ?? 0,
                        $row['cement_id'] ?? 0,
                        $row['sand_id'] ?? 0,
                        $row['cat_id'] ?? 0,
                        $row['ceramic_id'] ?? 0,
                        $row['nat_id'] ?? 0,
                    ]);
                };

                // Color map for Grand Total - only color if combination appears in multiple filter types
                $colorIndex = 0;
                $combinationColorMap = []; // Track colors by combination signature
                $signatureFilterTypes = []; // Track which filter types have each signature

                $displayedRekapKeys = [];
                foreach ($rekapCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key])) {
                            $displayedRekapKeys[] = $key;
                        }
                    }
                }

                // First pass: track which filter types have each signature (only displayed rows)
                foreach ($displayedRekapKeys as $key1) {
                    $data1 = $globalRekapData[$key1] ?? null;
                    if (!$data1) {
                        continue;
                    }
                    // Extract filter type from key (e.g., "Preferensi 1" -> "Preferensi")
                    $filterType = preg_replace('/\s+\d+$/', '', $key1);

                    $signature = $buildCombinationSignature($data1);

                    if (!isset($signatureFilterTypes[$signature])) {
                        $signatureFilterTypes[$signature] = [];
                    }
                    // Track unique filter types for this signature
                    if (!in_array($filterType, $signatureFilterTypes[$signature])) {
                        $signatureFilterTypes[$signature][] = $filterType;
                    }
                }

                // Second pass: assign colors only if combination appears in multiple filter types
                foreach ($displayedRekapKeys as $key1) {
                    $data1 = $globalRekapData[$key1] ?? null;
                    if (!$data1) {
                        continue;
                    }
                    if (!isset($globalColorMap[$key1])) {
                        // Create unique signature for this combination (all materials + work type)
                        $signature = $buildCombinationSignature($data1);

                        // Only assign color if this combination appears in more than one filter type
                        if (count($signatureFilterTypes[$signature]) > 1) {
                            if (isset($combinationColorMap[$signature])) {
                                // Use existing color for this combination
                                $globalColorMap[$key1] = $combinationColorMap[$signature];
                            } else {
                                // Assign new color for this recurring combination across filter types
                                $color = $availableColors[$colorIndex % count($availableColors)];
                                $globalColorMap[$key1] = $color;
                                $combinationColorMap[$signature] = $color;
                                $colorIndex++;
                            }
                        } else {
                            // Combination only appears in one filter type - white background (must be opaque for sticky columns)
                            $globalColorMap[$key1] = '#ffffff';
                        }
                    }
                }

                // Color map for Brick - based on complete data (brand, size, price, type)
                // Use BRICK COLOR PALETTE (warm colors)
                $colorIndex = 0;
                $brickDataColorMap = []; // Track colors by complete brick data

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key])) {
                            $project = null;
                            // Find the project data for this key
                            foreach ($projects as $p) {
                                foreach ($p['combinations'] as $label => $items) {
                                    foreach ($items as $item) {
                                        if (
                                            isset($globalRekapData[$key]) &&
                                            !empty($globalRekapData[$key]['brick_id']) &&
                                            isset($p['brick']) &&
                                            $p['brick'] &&
                                            $p['brick']->id === $globalRekapData[$key]['brick_id']
                                        ) {
                                            $project = $p;
                                            break 3;
                                        }
                                    }
                                }
                            }

                            if ($project) {
                                // Create signature based on complete brick data (WITHOUT filterType)
                                $brick = $project['brick'];
                                $dataSignature =
                                    ($brick->brand ?? '') .
                                    '-' .
                                    ($brick->type ?? '') .
                                    '-' .
                                    ($brick->dimension_length ?? '') .
                                    '-' .
                                    ($brick->dimension_width ?? '') .
                                    '-' .
                                    ($brick->dimension_height ?? '') .
                                    '-' .
                                    ($brick->price ?? '0');

                                if (isset($brickDataColorMap[$dataSignature])) {
                                    $brickColorMap[$key] = $brickDataColorMap[$dataSignature];
                                } else {
                                    $color = $brickColors[$colorIndex % count($brickColors)];
                                    $brickColorMap[$key] = $color;
                                    $brickDataColorMap[$dataSignature] = $color;
                                    $colorIndex++;
                                }
                            }
                        }
                    }
                }

                // Color map for Cement - based on complete data (brand, color, weight, price)
                // Use CEMENT COLOR PALETTE (cool colors)
                $colorIndex = 0;
                $cementDataColorMap = []; // Track colors by complete cement data

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key])) {
                            $cement = null;
                            // Find the cement data for this key
                            foreach ($projects as $p) {
                                foreach ($p['combinations'] as $label => $items) {
                                    foreach ($items as $item) {
                                        if (
                                            isset($globalRekapData[$key]) &&
                                            isset($globalRekapData[$key]['cement_id']) &&
                                            isset($item['cement']) &&
                                            $item['cement']->id === $globalRekapData[$key]['cement_id']
                                        ) {
                                            $cement = $item['cement'];
                                            break 3;
                                        }
                                    }
                                }
                            }

                            if ($cement) {
                                // Create signature based on complete cement data (WITHOUT filterType)
                                $dataSignature =
                                    $cement->brand .
                                    '-' .
                                    ($cement->color ?? '-') .
                                    '-' .
                                    $cement->package_weight_net .
                                    '-' .
                                    ($cement->price ?? '0');

                                if (isset($cementDataColorMap[$dataSignature])) {
                                    $cementColorMap[$key] = $cementDataColorMap[$dataSignature];
                                } else {
                                    $color = $cementColors[$colorIndex % count($cementColors)];
                                    $cementColorMap[$key] = $color;
                                    $cementDataColorMap[$dataSignature] = $color;
                                    $colorIndex++;
                                }
                            }
                        }
                    }
                }

                // Color map for Sand - based on complete data (brand, unit, volume, price)
                // Use SAND COLOR PALETTE (earth/yellow tones)
                $colorIndex = 0;
                $sandDataColorMap = []; // Track colors by complete sand data

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key])) {
                            $sand = null;
                            // Find the sand data for this key
                            foreach ($projects as $p) {
                                foreach ($p['combinations'] as $label => $items) {
                                    foreach ($items as $item) {
                                        if (
                                            isset($globalRekapData[$key]) &&
                                            isset($globalRekapData[$key]['sand_id']) &&
                                            isset($item['sand']) &&
                                            $item['sand']->id === $globalRekapData[$key]['sand_id']
                                        ) {
                                            $sand = $item['sand'];
                                            break 3;
                                        }
                                    }
                                }
                            }

                            if ($sand) {
                                // Create signature based on complete sand data (WITHOUT filterType)
                                $dataSignature =
                                    $sand->brand .
                                    '-' .
                                    ($sand->package_unit ?? '-') .
                                    '-' .
                                    ($sand->package_volume ?? '0') .
                                    '-' .
                                    ($sand->price ?? '0');

                                if (isset($sandDataColorMap[$dataSignature])) {
                                    $sandColorMap[$key] = $sandDataColorMap[$dataSignature];
                                } else {
                                    $color = $sandColors[$colorIndex % count($sandColors)];
                                    $sandColorMap[$key] = $color;
                                    $sandDataColorMap[$dataSignature] = $color;
                                    $colorIndex++;
                                }
                            }
                        }
                    }
                }

                // Color map for Cat
                $colorIndex = 0;
                $catDataColorMap = [];
                $catColorMap = [];

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_id'])) {
                            // Create signature
                            $catId = $globalRekapData[$key]['cat_id'];
                            $catBrand = $globalRekapData[$key]['cat_brand'];
                            $dataSignature = $catId . '-' . $catBrand;

                            if (isset($catDataColorMap[$dataSignature])) {
                                $catColorMap[$key] = $catDataColorMap[$dataSignature];
                            } else {
                                $color = $catColors[$colorIndex % count($catColors)];
                                $catColorMap[$key] = $color;
                                $catDataColorMap[$dataSignature] = $color;
                                $colorIndex++;
                            }
                        }
                    }
                }

                // Color map for Ceramic
                $colorIndex = 0;
                $ceramicDataColorMap = [];
                $ceramicColorMap = [];

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key]) && isset($globalRekapData[$key]['ceramic_id'])) {
                            $ceramicId = $globalRekapData[$key]['ceramic_id'];
                            $ceramicBrand = $globalRekapData[$key]['ceramic_brand'];
                            $dataSignature = $ceramicId . '-' . $ceramicBrand;

                            if (isset($ceramicDataColorMap[$dataSignature])) {
                                $ceramicColorMap[$key] = $ceramicDataColorMap[$dataSignature];
                            } else {
                                $color = $ceramicColors[$colorIndex % count($ceramicColors)];
                                $ceramicColorMap[$key] = $color;
                                $ceramicDataColorMap[$dataSignature] = $color;
                                $colorIndex++;
                            }
                        }
                    }
                }

                // Color map for Nat
                $colorIndex = 0;
                $natDataColorMap = [];
                $natColorMap = [];

                foreach ($filterCategories as $filterType) {
                    foreach ($getDisplayKeys($filterType) as $key) {
                        if (isset($globalRekapData[$key]) && isset($globalRekapData[$key]['nat_id'])) {
                            $natId = $globalRekapData[$key]['nat_id'];
                            $natBrand = $globalRekapData[$key]['nat_brand'];
                            $dataSignature = $natId . '-' . $natBrand;

                            if (isset($natDataColorMap[$dataSignature])) {
                                $natColorMap[$key] = $natDataColorMap[$dataSignature];
                            } else {
                                $color = $natColors[$colorIndex % count($natColors)];
                                $natColorMap[$key] = $color;
                                $natDataColorMap[$dataSignature] = $color;
                                $colorIndex++;
                            }
                        }
                    }
                }
            @endphp

            @if (count($rekapCategories) > 0)
                <div class="container mb-4">
                    @php
                        $isRollag = isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag';
                        $isPlinthCeramic = isset($requestData['work_type']) && $requestData['work_type'] === 'plinth_ceramic';
                        if (!isset($area)) {
                            if ($isRollag) {
                                $area = 0;
                            } elseif ($isPlinthCeramic) {
                                // For plinth ceramic, height is in cm, convert to meters
                                $area = ($requestData['wall_length'] ?? 0) * (($requestData['wall_height'] ?? 0) / 100);
                            } else {
                                $area = ($requestData['wall_length'] ?? 0) * ($requestData['wall_height'] ?? 0);
                            }
                        }
                    @endphp
                    @php
                        $heightLabel = in_array(
                            $requestData['work_type'] ?? '',
                            ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'],
                            true,
                        )
                            ? 'LEBAR'
                            : 'TINGGI';
                        // Plinth ceramic uses TINGGI (already in else block, so no change needed)
                        $bundleWorkItemsForParams = [];
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
                            'plaster_sides',
                            'skim_sides',
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
                            'plaster_sides' => 'Sisi Plester',
                            'skim_sides' => 'Sisi Aci',
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
                            'plaster_sides' => 'Sisi',
                            'skim_sides' => 'Sisi',
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
                            'plaster_sides' => 60,
                            'skim_sides' => 70,
                            'grout_thickness' => 80,
                            'ceramic_length' => 90,
                            'ceramic_width' => 100,
                            'ceramic_thickness' => 110,
                        ];
                        $formatBundleParamLabel = static function (
                            string $key,
                            string $workType,
                        ) use ($bundleParamLabelMap): string {
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
                            if ($workType === 'wall_plastering') {
                                $fields[] = 'plaster_sides';
                            }
                            if ($workType === 'skim_coating') {
                                $fields[] = 'skim_sides';
                            }
                            if (in_array(
                                $workType,
                                ['tile_installation', 'grout_tile', 'plinth_ceramic', 'adhesive_mix', 'plinth_adhesive_mix'],
                                true,
                            )) {
                                $fields[] = 'grout_thickness';
                            }
                            if ($workType === 'grout_tile') {
                                $fields[] = 'ceramic_length';
                                $fields[] = 'ceramic_width';
                                $fields[] = 'ceramic_thickness';
                            }

                            return array_values(array_unique($fields));
                        };
                        $resolveBundleParamUnit = static function (string $key, string $workType) use (
                            $bundleParamUnitMap,
                        ): string {
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
                                $computedArea =
                                    $workType === 'plinth_ceramic' ? $length * ($height / 100) : $length * $height;
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
                        $rawWorkItemsPayload = $requestData['work_items_payload'] ?? null;
                        if (is_string($rawWorkItemsPayload) && trim($rawWorkItemsPayload) !== '') {
                            $decodedWorkItems = json_decode($rawWorkItemsPayload, true);
                            if (is_array($decodedWorkItems)) {
                                foreach ($decodedWorkItems as $idx => $decodedItem) {
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
                            }
                        }
                        $hasBundleWorkItemDropdown = count($bundleWorkItemsForParams) > 1;
                    @endphp
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
                                            <button
                                                class="dropdown-toggle fw-bold text-uppercase preview-param-label-toggle"
                                                type="button" data-bs-toggle="dropdown" data-param-dropdown-toggle="true"
                                                aria-expanded="false">
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
                                                    $showPlaster = $itemHas('plaster_sides');
                                                    $showSkim = $itemHas('skim_sides');
                                                    $showLayerPaint = $itemHas('layer_count') && in_array($itemWorkType, ['painting', 'wall_painting'], true);
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
                                                <div
                                                    class="px-2 py-2 bundle-param-item-card {{ $bundleIndex > 0 ? 'border-top mt-1' : '' }}">
                                                    <div class="bundle-param-item-layout">
                                                        <div class="bundle-param-section bundle-param-section--worktype">
                                                            <div
                                                                class="form-control fw-bold border-secondary text-dark bundle-param-worktype-value"
                                                                style="background-color: #e9ecef; opacity: 1;">
                                                                {{ $bundleItemParam['work_type_name'] }}
                                                            </div>
                                                        </div>

                                                        <div class="bundle-param-section bundle-param-section--size">
                                                            <div class="bundle-param-section-fields">
                                                                @if ($showLength)
                                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                                            style="font-size: 0.75rem;">
                                                                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                                                                        </label>
                                                                        <div class="input-group">
                                                                            <div class="form-control fw-bold text-center bg-white text-danger px-1"
                                                                                style="border-color: #dc3545;">
                                                                                {{ $itemVal($itemAreaKey) }}
                                                                            </div>
                                                                            <span
                                                                                class="input-group-text bg-danger text-white small px-1"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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

                                                                @if ($showPlaster)
                                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                                            style="font-size: 0.75rem;">
                                                                            <span class="badge bg-success text-white border">SISI PLESTER</span>
                                                                        </label>
                                                                        <div class="input-group">
                                                                            <div class="form-control fw-bold text-center px-1"
                                                                                style="background-color: #d1fae5; border-color: #34d399;">
                                                                                {{ $itemVal('plaster_sides') }}
                                                                            </div>
                                                                            <span class="input-group-text bg-success text-white small px-1"
                                                                                style="font-size: 0.7rem;">{{ $itemUnit('plaster_sides', 'Sisi') }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if ($showSkim)
                                                                    <div class="bundle-param-field bundle-param-field--sm">
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                                                            style="font-size: 0.75rem;">
                                                                            <span class="badge bg-info text-white border">SISI ACI</span>
                                                                        </label>
                                                                        <div class="input-group">
                                                                            <div class="form-control fw-bold text-center px-1"
                                                                                style="background-color: #e0f2fe; border-color: #38bdf8;">
                                                                                {{ $itemVal('skim_sides') }}
                                                                            </div>
                                                                            <span class="input-group-text bg-info text-white small px-1"
                                                                                style="font-size: 0.7rem;">{{ $itemUnit('skim_sides', 'Sisi') }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endif

                                                                @if ($showLayerPaint)
                                                                    <div class="bundle-param-field bundle-param-field--md">
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                                                                        <label
                                                                            class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
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
                            @else
                            {{-- ===== GRUP UTAMA: Item Pekerjaan + Dimensi ===== --}}

                            {{-- Jenis Item Pekerjaan --}}
                            <div style="flex: 1; min-width: 250px;">
                                <label class="fw-bold mb-2 text-uppercase"
                                    style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    <i class="bi bi-briefcase me-1"></i>Jenis Item Pekerjaan
                                </label>
                                <div class="form-control fw-bold border-secondary text-dark"
                                    style="background-color: #e9ecef; opacity: 1;">
                                    {{ $formulaName }}
                                </div>
                            </div>

                            {{-- Panjang --}}
                            <div style="flex: 0 0 auto; width: 100px;">
                                <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                    style="font-size: 0.75rem;">
                                    <span class="badge bg-light border">PANJANG</span>
                                </label>
                                <div class="input-group">
                                    <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">
                                        @format($requestData['wall_length'])</div>
                                    <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                                </div>
                            </div>

                            @if (!$isRollag)
                                {{-- Tinggi/Lebar --}}
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-light border">{{ $heightLabel }}</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e9ecef;">@format($requestData['wall_height'])</div>
                                        <span class="input-group-text bg-light small px-1"
                                            style="font-size: 0.7rem;">{{ isset($requestData['work_type']) && $requestData['work_type'] === 'plinth_ceramic' ? 'cm' : 'M' }}</span>
                                    </div>
                                </div>

                                {{-- Luas --}}
                                <div style="flex: 0 0 auto; width: 120px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-danger text-white border border-danger">LUAS</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center bg-white text-danger px-1"
                                            style="border-color: #dc3545;">@format($area)</div>
                                        <span class="input-group-text bg-danger text-white small px-1"
                                            style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                                    </div>
                                </div>
                            @endif

                            {{-- ===== SEPARATOR / GAP ===== --}}
                            <div style="flex: 0 0 auto; width: 10px;"></div>

                            {{-- ===== GRUP TAMBAHAN: Parameter Lainnya ===== --}}

                            {{-- Tebal Spesi (tidak untuk Pasang Nat atau Pengecatan) --}}
                            @if (
                                !isset($requestData['work_type']) ||
                                    !in_array($requestData['work_type'], ['grout_tile', 'painting', 'wall_painting']))
                                <div style="flex: 0 0 auto; width: 100px;">
                                    @php
                                        // Logic simplified: this block is now only for mortar thickness
                                        $paramLabel = 'TEBAL ADUKAN';
                                        $paramUnit = 'cm';
                                        $paramValue = $requestData['mortar_thickness'] ?? 2.0;
                                        $badgeClass = 'bg-light';
                                    @endphp
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge {{ $badgeClass }} border">{{ $paramLabel }}</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e9ecef;">{{ $paramValue }}</div>
                                        <span class="input-group-text bg-light small px-1"
                                            style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Tingkat (hanya untuk Rollag) --}}
                            @if (isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag')
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-warning border">TINGKAT</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fffbeb; border-color: #fcd34d;">
                                            {{ $requestData['layer_count'] ?? 1 }}</div>
                                        <span class="input-group-text bg-warning small px-1"
                                            style="font-size: 0.7rem;">Lapis</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Sisi Aci (hanya untuk Aci Dinding) --}}
                            @if (isset($requestData['work_type']) && $requestData['work_type'] === 'skim_coating')
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-info text-white border">SISI ACI</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e0f2fe; border-color: #38bdf8;">
                                            {{ $requestData['skim_sides'] ?? 1 }}</div>
                                        <span class="input-group-text bg-info text-white small px-1"
                                            style="font-size: 0.7rem;">Sisi</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Sisi Plester (hanya untuk Plester Dinding) --}}
                            @if (isset($requestData['work_type']) && $requestData['work_type'] === 'wall_plastering')
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-success text-white border">SISI PLESTER</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #d1fae5; border-color: #34d399;">
                                            {{ $requestData['plaster_sides'] ?? 1 }}</div>
                                        <span class="input-group-text bg-success text-white small px-1"
                                            style="font-size: 0.7rem;">Sisi</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Lapis Pengecatan --}}
                            @if (isset($requestData['work_type']) &&
                                    ($requestData['work_type'] === 'wall_painting' || $requestData['work_type'] === 'painting'))
                                <div style="flex: 0 0 auto; width: 120px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-primary text-white border border-primary">LAPIS CAT</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #dbeafe; border-color: #3b82f6;">
                                            {{ $requestData['layer_count'] ?? ($requestData['paint_layers'] ?? ($requestData['painting_layers'] ?? 1)) }}
                                        </div>
                                        <span class="input-group-text bg-primary text-white small px-1"
                                            style="font-size: 0.7rem;">Lapisan</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Tebal Nat (untuk Pasang Keramik, Pasang Nat, Plint Keramik, Pasang Keramik Saja, dan Pasang Plint Keramik Saja) --}}
                            @if (isset($requestData['work_type']) &&
                                    ($requestData['work_type'] === 'tile_installation' || $requestData['work_type'] === 'grout_tile' || $requestData['work_type'] === 'plinth_ceramic' || $requestData['work_type'] === 'adhesive_mix' || $requestData['work_type'] === 'plinth_adhesive_mix'))
                                <div style="flex: 0 0 auto; width: 100px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge bg-info text-white border">TEBAL NAT</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #e0f2fe; border-color: #38bdf8;">
                                            {{ $requestData['grout_thickness'] ?? 3 }}</div>
                                        <span class="input-group-text bg-info text-white small px-1"
                                            style="font-size: 0.7rem;">mm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Panjang Keramik (untuk Pasang Nat saja) --}}
                            @if (isset($requestData['work_type']) &&
                                    $requestData['work_type'] === 'grout_tile' &&
                                    isset($requestData['ceramic_length']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">P.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_length'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Lebar Keramik (untuk Pasang Nat saja) --}}
                            @if (isset($requestData['work_type']) &&
                                    $requestData['work_type'] === 'grout_tile' &&
                                    isset($requestData['ceramic_width']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">L.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_width'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Tebal Keramik (untuk Pasang Nat saja) --}}
                            @if (isset($requestData['work_type']) &&
                                    $requestData['work_type'] === 'grout_tile' &&
                                    isset($requestData['ceramic_thickness']))
                                <div style="flex: 0 0 auto; width: 110px;">
                                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start"
                                        style="font-size: 0.75rem;">
                                        <span class="badge text-white border" style="background-color: #f59e0b;">T.
                                            KERAMIK</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="form-control fw-bold text-center px-1"
                                            style="background-color: #fef3c7; border-color: #fde047;">
                                            {{ $requestData['ceramic_thickness'] }}</div>
                                        <span class="input-group-text text-white small px-1"
                                            style="background-color: #f59e0b; font-size: 0.7rem;">mm</span>
                                    </div>
                                </div>
                            @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card rekap-card"
                    style="background: #ffffff; padding: 0; border-radius: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: visible;">
                    <div class="table-responsive">
                        <style>
                            .table-rekap-global th {
                                padding: 8px 10px !important;
                                font-size: 13px !important;
                            }

                            .table-rekap-global td {
                                padding: 8px 10px !important;
                                border: 1px solid #f1f5f9;
                            }
                        </style>
                        @php
                            $rekapMaterialColumns = [
                                'brick' => [
                                    'enabled' => $hasBrick,
                                    'label' => 'Bata',
                                    'brand_field' => 'brick_brand',
                                    'detail_field' => 'brick_detail',
                                ],
                                'cement' => [
                                    'enabled' => $hasCement,
                                    'label' => 'Semen',
                                    'brand_field' => 'cement_brand',
                                    'detail_field' => 'cement_detail',
                                ],
                                'sand' => [
                                    'enabled' => $hasSand,
                                    'label' => 'Pasir',
                                    'brand_field' => 'sand_brand',
                                    'detail_field' => 'sand_detail',
                                ],
                                'cat' => [
                                    'enabled' => $hasCat,
                                    'label' => 'Cat',
                                    'brand_field' => 'cat_brand',
                                    'detail_field' => 'cat_detail',
                                ],
                                'ceramic' => [
                                    'enabled' => $hasCeramic,
                                    'label' => 'Keramik',
                                    'brand_field' => 'ceramic_brand',
                                    'detail_field' => 'ceramic_detail',
                                ],
                                'nat' => [
                                    'enabled' => $hasNat,
                                    'label' => 'Nat',
                                    'brand_field' => 'nat_brand',
                                    'detail_field' => 'nat_detail',
                                ],
                            ];
                            $activeRekapMaterialKeys = array_values(
                                array_filter(array_keys($rekapMaterialColumns), fn($matKey) => $rekapMaterialColumns[$matKey]['enabled']),
                            );
                            $getRekapVariantsForDisplay = function (?array $entry, string $materialKey) use (
                                $rekapMaterialColumns,
                            ): array {
                                if (!is_array($entry) || !isset($rekapMaterialColumns[$materialKey])) {
                                    return [];
                                }

                                $variants = $entry['material_variants'][$materialKey] ?? [];
                                if (!is_array($variants)) {
                                    $variants = [];
                                }

                                $normalized = [];
                                $seenVariantKeys = [];
                                foreach ($variants as $variant) {
                                    if (!is_array($variant)) {
                                        continue;
                                    }
                                    $variantId = is_numeric($variant['id'] ?? null) ? (int) $variant['id'] : null;
                                    $brand = trim((string) ($variant['brand'] ?? ''));
                                    $detail = trim((string) ($variant['detail'] ?? ''));
                                    if ($brand === '' && $detail === '') {
                                        continue;
                                    }
                                    $dedupeKey = $variantId !== null ? 'id:' . $variantId : strtolower($brand . '|' . $detail);
                                    if (isset($seenVariantKeys[$dedupeKey])) {
                                        continue;
                                    }
                                    $seenVariantKeys[$dedupeKey] = true;
                                    $normalized[] = [
                                        'brand' => $brand !== '' ? $brand : '-',
                                        'detail' => $detail !== '' ? $detail : '-',
                                    ];
                                }

                                if (!empty($normalized)) {
                                    return $normalized;
                                }

                                $brandField = $rekapMaterialColumns[$materialKey]['brand_field'];
                                $detailField = $rekapMaterialColumns[$materialKey]['detail_field'];
                                $legacyBrand = trim((string) ($entry[$brandField] ?? ''));
                                $legacyDetail = trim((string) ($entry[$detailField] ?? ''));
                                if ($legacyBrand === '' && $legacyDetail === '') {
                                    return [];
                                }

                                return [
                                    [
                                        'brand' => $legacyBrand !== '' ? $legacyBrand : '-',
                                        'detail' => $legacyDetail !== '' ? $legacyDetail : '-',
                                    ],
                                ];
                            };
                            $rekapVariantCounts = [];
                            foreach ($activeRekapMaterialKeys as $materialKey) {
                                $maxVariants = 1;
                                foreach ($rekapCategories as $filterType) {
                                    foreach ($getDisplayKeys($filterType) as $displayKey) {
                                        $entry = $globalRekapData[$displayKey] ?? null;
                                        $variantCount = count($getRekapVariantsForDisplay($entry, $materialKey));
                                        if ($variantCount > $maxVariants) {
                                            $maxVariants = $variantCount;
                                        }
                                    }
                                }
                                $rekapVariantCounts[$materialKey] = $maxVariants;
                            }
                        @endphp
                        <table class="table-preview table-rekap-global" data-rekap-table="true" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th rowspan="2"
                                        class="rekap-sticky-col-label"
                                        style="background: #891313; color: white; position: sticky; left: 0; z-index: 3; width: 80px; min-width: 80px;">
                                        Rekap</th>
                                    <th rowspan="2"
                                        class="rekap-sticky-col-total"
                                        style="background: #891313; color: white; position: sticky; left: var(--rekap-left-2, 80px); z-index: 3; width: 120px; min-width: 120px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.3);">
                                        Grand Total</th>
                                    @foreach ($activeRekapMaterialKeys as $materialKey)
                                        <th colspan="{{ ($rekapVariantCounts[$materialKey] ?? 1) * 2 }}"
                                            style="background: #891313; color: white;">
                                            {{ $rekapMaterialColumns[$materialKey]['label'] }}
                                        </th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($activeRekapMaterialKeys as $materialKey)
                                        @for ($variantIndex = 0; $variantIndex < ($rekapVariantCounts[$materialKey] ?? 1); $variantIndex++)
                                            <th style="background: #891313; color: white;">Merek</th>
                                            <th style="background: #891313; color: white;">Detail</th>
                                        @endfor
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rekapCategories as $filterType)
                                    @foreach ($getDisplayKeys($filterType) as $displayIndex => $key)
                                        @php
                                            $rekapEntry = $globalRekapData[$key] ?? null;
                                            $shouldSkipRow = false;
                                            $rank = $displayIndex + 1;
                                            $bgColor = $globalColorMap[$key] ?? '#ffffff';
                                            $grandTotalBg =
                                                $bgColor && strtolower($bgColor) !== '#ffffff' ? $bgColor : null;
                                            $brickBgColor = $brickColorMap[$key] ?? '#ffffff';
                                            $cementBgColor = $cementColorMap[$key] ?? '#ffffff';
                                            $sandBgColor = $sandColorMap[$key] ?? '#ffffff';
                                            $catBgColor = $catColorMap[$key] ?? '#ffffff';
                                            $natBgColor = $natColorMap[$key] ?? '#ffffff';
                                            $ceramicBgColor = $ceramicColorMap[$key] ?? '#ffffff';
                                            $isPopulerRow = str_contains($filterType, 'Populer');
                                            $materialBgByKey = [
                                                'brick' => $brickBgColor,
                                                'cement' => $cementBgColor,
                                                'sand' => $sandBgColor,
                                                'cat' => $catBgColor,
                                                'ceramic' => $ceramicBgColor,
                                                'nat' => $natBgColor,
                                            ];
                                            $rekapVariantsByMaterial = [];
                                            $usagePercentByMaterial = [];
                                            foreach ($activeRekapMaterialKeys as $materialKey) {
                                                $rekapVariantsByMaterial[$materialKey] = $getRekapVariantsForDisplay(
                                                    $rekapEntry,
                                                    $materialKey,
                                                );
                                                $firstBrand =
                                                    $rekapVariantsByMaterial[$materialKey][0]['brand'] ??
                                                    ($rekapEntry[$rekapMaterialColumns[$materialKey]['brand_field']] ??
                                                        null);
                                                $usagePercentByMaterial[$materialKey] = isset($globalRekapData[$key])
                                                    ? $formatUsagePercent($materialKey, $firstBrand)
                                                    : null;
                                            }

                                            // Get label color untuk kolom Rekap
                                            $labelColor = $rekapLabelColors[$filterType][$rank] ?? [
                                                'bg' => '#ffffff',
                                                'text' => '#000000',
                                            ];
                                        @endphp
                                        @continue($shouldSkipRow)
                                        <tr>
                                            {{-- Column 1: Filter Label --}}
                                            <td
                                                class="rekap-sticky-col-label"
                                                style="font-weight: 700; position: sticky; left: 0; z-index: 2; background: {{ $labelColor['bg'] }}; color: {{ $labelColor['text'] }}; padding: 4px 8px; vertical-align: middle; width: 80px; min-width: 80px;">
                                                <a href="#detail-{{ strtolower(str_replace(' ', '-', $key)) }}"
                                                    style="color: inherit; text-decoration: none; display: block; cursor: pointer;">
                                                    {{ $key }}
                                                </a>
                                            </td>

                                            {{-- Column 2: Grand Total --}}
                                            <td class="text-end fw-bold rekap-sticky-col-total"
                                                style="position: sticky; left: var(--rekap-left-2, 80px); box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1); background: {{ $grandTotalBg ?: '#ffffff' }}; padding: 4px 8px; vertical-align: middle; width: 140px; min-width: 140px;">
                                                @if (isset($globalRekapData[$key]))
                                                    <div class="d-flex flex-column align-items-end w-100">
                                                        {{-- Usage Frequency Badge (Populer) --}}
                                                        @if (isset($globalRekapData[$key]['frequency']))
                                                            <span
                                                                class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1"
                                                                style="font-size: 0.65rem;">
                                                                Dipakai {{ $globalRekapData[$key]['frequency'] }}x
                                                            </span>
                                                        @endif

                                                        @if (isset($globalRekapData[$key]['grand_total']) && $globalRekapData[$key]['grand_total'] !== null)
                                                            <div class="d-flex justify-content-between w-100">
                                                                <span>Rp</span>
                                                                <span>@price($globalRekapData[$key]['grand_total'])</span>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            @foreach ($activeRekapMaterialKeys as $materialKey)
                                                @php
                                                    $variantLimit = $rekapVariantCounts[$materialKey] ?? 1;
                                                    $materialVariants = $rekapVariantsByMaterial[$materialKey] ?? [];
                                                    $materialBg = $materialBgByKey[$materialKey] ?? '#ffffff';
                                                    $materialPercent = $usagePercentByMaterial[$materialKey] ?? null;
                                                @endphp
                                                @for ($variantIndex = 0; $variantIndex < $variantLimit; $variantIndex++)
                                                    @php
                                                        $variant = $materialVariants[$variantIndex] ?? null;
                                                        $brandText = trim((string) ($variant['brand'] ?? '-'));
                                                        if ($brandText === '') {
                                                            $brandText = '-';
                                                        }
                                                        $detailText = trim((string) ($variant['detail'] ?? '-'));
                                                        if ($detailText === '') {
                                                            $detailText = '-';
                                                        }
                                                        $isLastVariant = $variantIndex === $variantLimit - 1;
                                                        $detailCellStyle = 'background: ' . $materialBg . '; vertical-align: middle;';
                                                        if ($isLastVariant) {
                                                            $detailCellStyle .= ' border-right: 2px solid #891313;';
                                                        }
                                                    @endphp
                                                    <td style="background: {{ $materialBg }}; vertical-align: middle;">
                                                        @if (isset($globalRekapData[$key]))
                                                            {{ $brandText }}
                                                            @if ($variantIndex === 0 && $isPopulerRow && $materialPercent)
                                                                <span class="badge rounded-pill bg-primary text-white"
                                                                    style="font-size: 0.7rem; color: white !important;">{{ $materialPercent }}%</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted small" style="{{ $detailCellStyle }}">
                                                        @if (isset($globalRekapData[$key]))
                                                            {{ $detailText }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                @endfor
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
    </div>
    @endif

    {{-- SINGLE TABLE FOR ALL COMBINATIONS --}}
    <div class="container">
        <div class="card"
            style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden; position: relative; z-index: 1;">
            <div class="table-responsive detail-table-wrap">
                <table class="table-preview">
                    <thead class="align-top">
                        <tr>
                            <th class="sticky-col-1">Qty<br>/ Pekerjaan</th>
                            <th class="sticky-col-2">Sat.</th>
                            <th class="sticky-col-3">Material</th>
                            <th colspan="4">Detail</th>
                            <th class="preview-store-cell">Toko</th>
                            <th class="preview-address-cell">Alamat</th>
                            <th colspan="2">Harga Beli</th>
                            <th>Biaya<br>/ Material</th>
                            <th>Total Biaya</br> Material / Pekerjaan</th>
                            <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                            <th colspan="2">Harga Komparasi<br>/ Materal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $globalIndex = 0;
                            // Collect ALL filtered combinations from ALL projects
                            // Display them in the order of the recap table
                            $allFilteredCombinations = [];

                            $workTypeForDetail = $requestData['work_type'] ?? null;
                            $isBricklessDetailWork = in_array(
                                $workTypeForDetail,
                                ['grout_tile', 'painting', 'wall_painting'],
                                true,
                            );
                            $isGroutTileDetailWork = $workTypeForDetail === 'grout_tile';
                            $requiredMaterialsDetail = \App\Services\FormulaRegistry::materialsFor($workTypeForDetail ?? '');
                            if (empty($requiredMaterialsDetail)) {
                                $requiredMaterialsDetail = ['brick', 'cement', 'sand'];
                            }
                            $defaultProjectBrick = $projects[0]['brick'] ?? null;
                            $hasCompleteMaterialsForCombo = function ($item, $brick) use ($requiredMaterialsDetail, $isBricklessDetailWork, $isGroutTileDetailWork) {
                                foreach ($requiredMaterialsDetail as $matType) {
                                    if ($matType === 'brick') {
                                        if ($isBricklessDetailWork) {
                                            continue;
                                        }
                                        if (empty($brick)) {
                                            return false;
                                        }
                                        continue;
                                    }
                                    if ($matType === 'ceramic' && $isGroutTileDetailWork) {
                                        if (empty($item['ceramic'])) {
                                            return false;
                                        }
                                        continue;
                                    }
                                    if (empty($item[$matType])) {
                                        return false;
                                    }
                                }
                                if (empty($item['result']) || !array_key_exists('grand_total', $item['result']) || $item['result']['grand_total'] === null) {
                                    return false;
                                }
                                return true;
                            };

                            foreach ($filterCategories as $filterType) {
                                foreach ($getDisplayKeys($filterType) as $key) {
                                    // Check if this filter exists in global recap
                                    if (isset($globalRekapData[$key])) {
                                        $resolvedDetailEntry = null;
                                        if (
                                            isset($detailCombinationMap[$key]) &&
                                            !empty($detailCombinationMap[$key]['item'])
                                        ) {
                                            $resolvedDetailEntry = $detailCombinationMap[$key];
                                        } elseif (
                                            isset($populerDetailMap[$key]) &&
                                            !empty($populerDetailMap[$key]['item'])
                                        ) {
                                            $resolvedDetailEntry = $populerDetailMap[$key];
                                        }

                                        if ($resolvedDetailEntry) {
                                            $resolvedItem = $resolvedDetailEntry['item'];
                                            $resolvedBrick =
                                                $resolvedItem['brick'] ??
                                                $resolvedDetailEntry['project']['brick'] ??
                                                $resolvedDetailEntry['brick'] ??
                                                $defaultProjectBrick;

                                            if (
                                                !empty($resolvedItem) &&
                                                !empty($resolvedItem['result']) &&
                                                array_key_exists('grand_total', $resolvedItem['result'])
                                            ) {
                                                $allFilteredCombinations[] = [
                                                    'label' => $key,
                                                    'item' => $resolvedItem,
                                                    'brick' => $resolvedBrick,
                                                ];
                                                continue;
                                            }
                                        }

                                        $rekapData = $globalRekapData[$key];
                                        if (str_starts_with($key, 'Populer')) {
                                            if (
                                                isset($populerDetailMap[$key]) &&
                                                !empty($populerDetailMap[$key]['item'])
                                            ) {
                                                $fallbackEntry = $populerDetailMap[$key];
                                                $fallbackBrick =
                                                    $fallbackEntry['item']['brick'] ??
                                                    $fallbackEntry['project']['brick'] ??
                                                    $fallbackEntry['brick'] ??
                                                    $defaultProjectBrick;

                                                if ($hasCompleteMaterialsForCombo($fallbackEntry['item'], $fallbackBrick)) {
                                                    $allFilteredCombinations[] = [
                                                        'label' => $key,
                                                        'item' => $fallbackEntry['item'],
                                                        'brick' => $fallbackBrick,
                                                    ];
                                                }
                                            }
                                            continue;
                                        }
                                        $foundCombination = false;

                                        // Search through ALL projects to find the matching combination
                                        foreach ($projects as $project) {
                                            // Find the matching combination in this project
                                            foreach ($project['combinations'] as $label => $items) {
                                                foreach ($items as $item) {
                                                    $itemBrickId =
                                                        isset($item['brick']) && $item['brick']
                                                            ? $item['brick']->id
                                                            : (isset($project['brick']) && $project['brick']
                                                                ? $project['brick']->id
                                                                : null);
                                                    $rekapBrickId = $rekapData['brick_id'] ?? null;

                                                    // For brick-required work, if recap already has a brick,
                                                    // require matching brick on the actual combination item.
                                                    if (
                                                        !$isBricklessDetailWork &&
                                                        !empty($rekapBrickId) &&
                                                        (empty($itemBrickId) || $rekapBrickId !== $itemBrickId)
                                                    ) {
                                                        continue;
                                                    }

                                                    $match = false;

                                                    if (isset($rekapData['cat_id']) && isset($item['cat'])) {
                                                        // Match by Cat ID (for painting)
                                                        if ($item['cat']->id === $rekapData['cat_id']) {
                                                            $match = true;
                                                        }
                                                    } elseif (isset($rekapData['nat_id']) && isset($item['nat'])) {
                                                        if ($isGroutTileDetailWork) {
                                                            // For grout_tile, nat is the key matcher in recap/detail mapping.
                                                            if ($item['nat']->id === $rekapData['nat_id']) {
                                                                $match = true;
                                                            }
                                                        } elseif (
                                                            isset($rekapData['ceramic_id']) &&
                                                            isset($item['ceramic']) &&
                                                            $item['ceramic']->id === $rekapData['ceramic_id'] &&
                                                            $item['nat']->id === $rekapData['nat_id']
                                                        ) {
                                                            // Match by Ceramic & Nat ID (for tile_installation).
                                                            $match = true;
                                                        }
                                                    } elseif (isset($rekapData['cement_id']) && isset($item['cement'])) {
                                                        // Match by Cement (and Sand if applicable)
                                                        $rekapSandId = $rekapData['sand_id'] ?? null;
                                                        $itemSandId = isset($item['sand']) ? $item['sand']->id : null;

                                                        if (
                                                            $item['cement']->id === $rekapData['cement_id'] &&
                                                            $rekapSandId === $itemSandId
                                                        ) {
                                                            $match = true;
                                                        }
                                                    }

                                                    if ($match) {
                                                        $comboBrick = $item['brick'] ?? ($project['brick'] ?? $defaultProjectBrick);
                                                        if (
                                                            !empty($item) &&
                                                            !empty($item['result']) &&
                                                            array_key_exists('grand_total', $item['result'])
                                                        ) {
                                                            $allFilteredCombinations[] = [
                                                                'label' => $key, // Use recap label
                                                                'item' => $item,
                                                                'brick' => $comboBrick,
                                                            ];
                                                        }
                                                        $foundCombination = true;
                                                        break 3; // Found it, move to next filter
                                                    }
                                                }
                                            }
                                        }

                                        if (
                                            !$foundCombination &&
                                            isset($populerDetailMap[$key]) &&
                                            !empty($populerDetailMap[$key]['item'])
                                        ) {
                                            $fallbackEntry = $populerDetailMap[$key];
                                            $fallbackBrick =
                                                $fallbackEntry['item']['brick'] ??
                                                $fallbackEntry['project']['brick'] ??
                                                $fallbackEntry['brick'] ??
                                                $defaultProjectBrick;

                                            if (
                                                !empty($fallbackEntry['item']) &&
                                                !empty($fallbackEntry['item']['result']) &&
                                                array_key_exists('grand_total', $fallbackEntry['item']['result'])
                                            ) {
                                                $allFilteredCombinations[] = [
                                                    'label' => $key,
                                                    'item' => $fallbackEntry['item'],
                                                    'brick' => $fallbackBrick,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp

                        @foreach ($allFilteredCombinations as $combo)
                            @php
                                $globalIndex++;
                                $label = $combo['label'];
                                $item = $combo['item'];
                                $brick = $combo['brick'];
                                $brickDimensionLength = (float) ($brick?->dimension_length ?? 0);
                                $brickDimensionWidth = (float) ($brick?->dimension_width ?? 0);
                                $brickDimensionHeight = (float) ($brick?->dimension_height ?? 0);
                                $res = $item['result'];
                                $isFirstOption = $globalIndex === 1;
                                $areaForCost = $area;
                                if ($isRollag) {
                                    $wallLength = (float) ($requestData['wall_length'] ?? 0);
                                    $brickLength = $brickDimensionLength;
                                    if ($brickLength <= 0) {
                                        $brickLength = 19.2;
                                    }
                                    $areaForCost =
                                        $wallLength > 0 && $brickLength > 0 ? $wallLength * ($brickLength / 100) : 0;
                                }
                                $normalizedArea = (float) $areaForCost;
                                $costPerM2 = $normalizedArea > 0 ? $res['grand_total'] / $normalizedArea : 0;
                                $brickVolume = 0;
                                if (
                                    $brick &&
                                    $brickDimensionLength > 0 &&
                                    $brickDimensionWidth > 0 &&
                                    $brickDimensionHeight > 0
                                ) {
                                    $brickVolume =
                                        ($brickDimensionLength *
                                            $brickDimensionWidth *
                                            $brickDimensionHeight) /
                                        1000000;
                                }
                                if ($brickVolume <= 0) {
                                    $brickVolume = $brick?->package_volume ?? 0;
                                }
                                $brickVolumeDisplay = $brickVolume > 0 ? $brickVolume : null;
                                if ($brickVolume <= 0) {
                                    $brickVolume = 1;
                                }
                                $cementWeight = isset($item['cement']) ? $item['cement']->package_weight_net ?? 0 : 0;
                                if ($cementWeight <= 0) {
                                    $cementWeight = 1;
                                }
                                $catWeight = isset($item['cat']) ? $item['cat']->package_weight_net ?? 0 : 0;
                                if ($catWeight <= 0) {
                                    $catWeight = 1;
                                }
                                $ceramicArea = 0;
                                if (
                                    isset($item['ceramic']) &&
                                    $item['ceramic']->dimension_length &&
                                    $item['ceramic']->dimension_width
                                ) {
                                    $ceramicArea =
                                        ($item['ceramic']->dimension_length / 100) *
                                        ($item['ceramic']->dimension_width / 100);
                                }
                                if ($ceramicArea <= 0) {
                                    $ceramicArea = 1;
                                }
                                $natWeight = isset($item['nat']) ? $item['nat']->package_weight_net ?? 0 : 0;
                                if ($natWeight <= 0) {
                                    $natWeight = 1;
                                }

                                $brickPricePerPiece = $res['brick_price_per_piece'] ?? ($brick?->price_per_piece ?? 0);
                                $cementPricePerSak =
                                    $res['cement_price_per_sak'] ??
                                    (isset($item['cement']) ? $item['cement']->package_price ?? 0 : 0);
                                $catPricePerPackage =
                                    $res['cat_price_per_package'] ??
                                    (isset($item['cat']) ? $item['cat']->purchase_price ?? 0 : 0);
                                $ceramicPricePerPackage =
                                    $res['ceramic_price_per_package'] ??
                                    (isset($item['ceramic']) ? $item['ceramic']->price_per_package ?? 0 : 0);
                                $groutPricePerPackage =
                                    $res['grout_price_per_package'] ??
                                    (isset($item['nat']) ? $item['nat']->package_price ?? 0 : 0);

                                $sandPricePerM3 = $res['sand_price_per_m3'] ?? 0;
                                if ($sandPricePerM3 <= 0 && isset($item['sand'])) {
                                    $sandPricePerM3 = $item['sand']->comparison_price_per_m3 ?? 0;
                                    if (
                                        $sandPricePerM3 <= 0 &&
                                        ($item['sand']->package_price ?? 0) > 0 &&
                                        ($item['sand']->package_volume ?? 0) > 0
                                    ) {
                                        $sandPricePerM3 = $item['sand']->package_price / $item['sand']->package_volume;
                                    }
                                }

                                $tilesPerPackage =
                                    $res['tiles_per_package'] ??
                                    (isset($item['ceramic']) ? $item['ceramic']->pieces_per_package ?? 0 : 0);
                                $tilesPackages =
                                    $res['tiles_packages'] ??
                                    ($tilesPerPackage > 0 ? ceil(($res['total_tiles'] ?? 0) / $tilesPerPackage) : 0);

                                // Helper function: format number without trailing zeros
                                $formatNum = function ($num, $decimals = null) {
                                    return \App\Helpers\NumberHelper::format($num);
                                };
                                $formatPlain = function ($num, $maxDecimals = 15) {
                                    return \App\Helpers\NumberHelper::formatPlain($num, $maxDecimals, ',', '.');
                                };
                                $formatMoney = function ($num) {
                                    return \App\Helpers\NumberHelper::formatFixed($num, 0);
                                };
                                $formatRaw = function ($num, $decimals = 6) {
                                    return \App\Helpers\NumberHelper::format($num, $decimals);
                                };
                                $catDetailDisplayParts = [];
                                $catDetailExtraParts = [];
                                $catSubBrand = isset($item['cat'])
                                    ? trim((string) ($item['cat']->sub_brand ?? ''))
                                    : '';
                                $catCode = isset($item['cat']) ? trim((string) ($item['cat']->color_code ?? '')) : '';
                                $catColor = isset($item['cat']) ? trim((string) ($item['cat']->color_name ?? '')) : '';
                                if ($catSubBrand !== '') {
                                    $catDetailDisplayParts[] = $catSubBrand;
                                }
                                if ($catCode !== '') {
                                    $catDetailDisplayParts[] = $catCode;
                                }
                                if ($catColor !== '') {
                                    $catDetailDisplayParts[] = $catColor;
                                }
                                $catDetailDisplay = !empty($catDetailDisplayParts)
                                    ? implode(' - ', $catDetailDisplayParts)
                                    : '-';

                                $catPackageUnit = isset($item['cat'])
                                    ? trim((string) ($item['cat']->package_unit ?? ''))
                                    : '';
                                $catVolume = isset($item['cat']) ? $item['cat']->volume ?? null : null;
                                $catVolumeUnit = isset($item['cat'])
                                    ? trim((string) ($item['cat']->volume_unit ?? 'L'))
                                    : 'L';
                                if ($catVolumeUnit === '') {
                                    $catVolumeUnit = 'L';
                                }
                                $catPackageUnitDisplay = $catPackageUnit !== '' ? $catPackageUnit : '-';
                                $catGrossWeight = isset($item['cat'])
                                    ? $item['cat']->package_weight_gross ?? null
                                    : null;
                                $catGrossDisplay =
                                    $catGrossWeight !== null && $catGrossWeight > 0 ? $formatNum($catGrossWeight) : '-';
                                $catDetailExtraParts[] = $catPackageUnitDisplay . ' ( ' . $catGrossDisplay . ' Kg )';
                                if (!empty($catVolume) && $catVolume > 0) {
                                    $catDetailExtraParts[] =
                                        '( ' . $formatNum($catVolume) . ' ' . $catVolumeUnit . ' )';
                                } else {
                                    $catDetailExtraParts[] = '( - ' . $catVolumeUnit . ' )';
                                }
                                if (isset($item['cat']) && ($item['cat']->package_weight_net ?? null) !== null) {
                                    $catDetailExtraParts[] =
                                        'BB ' . $formatNum($item['cat']->package_weight_net) . ' Kg';
                                }
                                $catDetailExtra = !empty($catDetailExtraParts)
                                    ? implode(' - ', $catDetailExtraParts)
                                    : '-';

                                // ========================================
                                // DYNAMIC MATERIAL CONFIGURATION
                                // To add new material, just add to this array!
                                // ========================================
                                $materialConfig = [
                                    'brick' => [
                                        'name' => 'Bata',
                                        'check_field' => 'total_bricks',
                                        'qty' => $res['total_bricks'] ?? 0,
                                        'qty_debug' => 'Kebutuhan bata untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => 'Bh',
                                        'comparison_unit' => 'M3',
                                        'detail_value' => $brickVolume,
                                        'detail_value_debug' =>
                                            'Rumus: (' .
                                            $formatNum($brickDimensionLength) .
                                            ' x ' .
                                            $formatNum($brickDimensionWidth) .
                                            ' x ' .
                                            $formatNum($brickDimensionHeight) .
                                            ') / 1.000.000 = ' .
                                            $formatPlain($brickVolume) .
                                            ' M3',
                                        'object' => $brick,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' =>
                                            $formatNum($brickDimensionLength) .
                                            ' x ' .
                                            $formatNum($brickDimensionWidth) .
                                            ' x ' .
                                            $formatNum($brickDimensionHeight) .
                                            ' cm',
                                        'detail_extra' => $brickVolumeDisplay
                                            ? $formatPlain($brickVolumeDisplay) . ' M3'
                                            : '-',
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => $brick?->price_per_piece ?? 0,
                                        'package_unit' => 'bh',
                                        'price_per_unit' => $brickPricePerPiece,
                                        'price_unit_label' => 'bh',
                                        'price_calc_qty' => $res['total_bricks'] ?? 0,
                                        'price_calc_unit' => 'bh',
                                        'total_price' => $res['total_brick_price'] ?? 0,
                                        'unit_price' => $brickPricePerPiece,
                                        'unit_price_label' => 'bh',
                                    ],
                                    'cement' => [
                                        'name' => 'Semen',
                                        'check_field' => 'cement_sak',
                                        'qty' => $res['cement_sak'] ?? 0,
                                        'qty_debug' => 'Kebutuhan semen untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => 'Sak',
                                        'comparison_unit' => 'Kg',
                                        'detail_value' => $cementWeight,
                                        'detail_value_debug' =>
                                            'Berat per kemasan: ' . $formatNum($cementWeight) . ' Kg',
                                        'object' => $item['cement'] ?? null,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' => isset($item['cement'])
                                            ? $item['cement']->color ?? '-'
                                            : '-',
                                        'detail_extra' => isset($item['cement'])
                                            ? $formatNum($item['cement']->package_weight_net) . ' Kg'
                                            : '-',
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => isset($item['cement'])
                                            ? $item['cement']->package_price ?? 0
                                            : 0,
                                        'package_unit' => isset($item['cement'])
                                            ? $item['cement']->package_unit ?? 'Sak'
                                            : 'Sak',
                                        'price_per_unit' => $cementPricePerSak,
                                        'price_unit_label' => isset($item['cement'])
                                            ? $item['cement']->package_unit ?? 'Sak'
                                            : 'Sak',
                                        'price_calc_qty' => $res['cement_sak'] ?? 0,
                                        'price_calc_unit' => 'Sak',
                                        'total_price' => $res['total_cement_price'] ?? 0,
                                        'unit_price' => $cementPricePerSak,
                                        'unit_price_label' => isset($item['cement'])
                                            ? $item['cement']->package_unit ?? 'Sak'
                                            : 'Sak',
                                    ],
                                    'sand' => [
                                        'name' => 'Pasir',
                                        'check_field' => 'sand_m3',
                                        'qty' => $res['sand_m3'] ?? 0,
                                        'qty_debug' => 'Kebutuhan pasir untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => 'M3',
                                        'comparison_unit' => 'M3',
                                        'detail_value' =>
                                            isset($item['sand']) && $item['sand']->package_volume > 0
                                                ? $item['sand']->package_volume
                                                : 1,
                                        'detail_value_debug' => isset($item['sand'])
                                            ? 'Volume per kemasan: ' .
                                                $formatNum($item['sand']->package_volume ?? 0) .
                                                ' M3'
                                            : '-',
                                        'object' => $item['sand'] ?? null,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' => isset($item['sand'])
                                            ? $item['sand']->package_unit ?? '-'
                                            : '-',
                                        'detail_extra' => isset($item['sand'])
                                            ? ($item['sand']->package_volume
                                                ? $formatNum($item['sand']->package_volume) . ' M3'
                                                : '-')
                                            : '-',
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => isset($item['sand']) ? $item['sand']->package_price ?? 0 : 0,
                                        'package_unit' => isset($item['sand'])
                                            ? $item['sand']->package_unit ?? 'Karung'
                                            : 'Karung',
                                        'price_per_unit' => $sandPricePerM3,
                                        'price_unit_label' => 'M3',
                                        'price_calc_qty' => $res['sand_m3'] ?? 0,
                                        'price_calc_unit' => 'M3',
                                        'total_price' => $res['total_sand_price'] ?? 0,
                                        'unit_price' => $sandPricePerM3,
                                        'unit_price_label' => isset($item['sand'])
                                            ? $item['sand']->package_unit ?? 'Karung'
                                            : 'Karung',
                                    ],
                                    'cat' => [
                                        'name' => 'Cat',
                                        'check_field' => 'cat_packages',
                                        'qty' => $res['cat_packages'] ?? 0,
                                        'qty_debug' => 'Kebutuhan cat untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => isset($item['cat']) ? $item['cat']->package_unit ?? 'Kmsn' : 'Kmsn',
                                        'comparison_unit' => 'Kg',
                                        'detail_value' => $catWeight,
                                        'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($catWeight) . ' Kg',
                                        'object' => $item['cat'] ?? null,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' => $catDetailDisplay,
                                        'detail_extra' => $catDetailExtra,
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => isset($item['cat']) ? $item['cat']->purchase_price ?? 0 : 0,
                                        'package_unit' => isset($item['cat'])
                                            ? $item['cat']->package_unit ?? 'Galon'
                                            : 'Galon',
                                        'price_per_unit' => $catPricePerPackage,
                                        'price_unit_label' => isset($item['cat'])
                                            ? $item['cat']->package_unit ?? 'Galon'
                                            : 'Galon',
                                        'price_calc_qty' => $res['cat_packages'] ?? 0,
                                        'price_calc_unit' => isset($item['cat'])
                                            ? $item['cat']->package_unit ?? 'Galon'
                                            : 'Galon',
                                        'total_price' => $res['total_cat_price'] ?? 0,
                                        'unit_price' => $catPricePerPackage,
                                        'unit_price_label' => isset($item['cat'])
                                            ? $item['cat']->package_unit ?? 'Galon'
                                            : 'Galon',
                                    ],
                                    'ceramic' => [
                                        'name' => 'Keramik',
                                        'check_field' => 'total_tiles',
                                        'qty' => $res['total_tiles'] ?? 0,
                                        'qty_debug' =>
                                            'Kebutuhan keramik untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => 'Bh',
                                        'comparison_unit' => 'M2',
                                        'detail_value' => $ceramicArea,
                                        'detail_value_debug' => isset($item['ceramic'])
                                            ? 'Rumus: (' .
                                                $formatNum($item['ceramic']->dimension_length) .
                                                '/100) x (' .
                                                $formatNum($item['ceramic']->dimension_width) .
                                                '/100) = ' .
                                                $formatNum($ceramicArea) .
                                                ' M2'
                                            : '-',
                                        'object' => $item['ceramic'] ?? null,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' => isset($item['ceramic'])
                                            ? $item['ceramic']->color ?? '-'
                                            : '-',
                                        'detail_extra' => isset($item['ceramic'])
                                            ? $formatNum($item['ceramic']->dimension_length) .
                                                'x' .
                                                $formatNum($item['ceramic']->dimension_width) .
                                                ' cm'
                                            : '-',
                                        'detail_extra_debug' => isset($item['ceramic'])
                                            ? 'Luas: ' . $formatNum($ceramicArea) . ' M2 per keping'
                                            : '-',
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => isset($item['ceramic'])
                                            ? $item['ceramic']->price_per_package ?? 0
                                            : 0,
                                        'package_unit' => 'Dus',
                                        'price_per_unit' => $ceramicPricePerPackage,
                                        'price_unit_label' => 'Dus',
                                        'price_calc_qty' => $tilesPackages,
                                        'price_calc_unit' => 'Dus',
                                        'total_price' => $res['total_ceramic_price'] ?? 0,
                                        'unit_price' => $ceramicPricePerPackage,
                                        'unit_price_label' => 'Dus',
                                    ],
                                    'nat' => [
                                        'name' => 'Nat',
                                        'check_field' => 'grout_packages',
                                        'qty' => $res['grout_packages'] ?? 0,
                                        'qty_debug' => 'Kebutuhan nat untuk area ' . $formatNum($areaForCost) . ' M2',
                                        'unit' => 'Bks',
                                        'comparison_unit' => 'Kg',
                                        'detail_value' => $natWeight,
                                        'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($natWeight) . ' Kg',
                                        'object' => $item['nat'] ?? null,
                                        'type_field' => 'type',
                                        'brand_field' => 'brand',
                                        'detail_display' => isset($item['nat']) ? $item['nat']->color ?? 'Nat' : 'Nat',
                                        'detail_extra' => isset($item['nat'])
                                            ? $formatNum($item['nat']->package_weight_net) . ' Kg'
                                            : '-',
                                        'store_field' => 'store',
                                        'address_field' => 'address',
                                        'package_price' => isset($item['nat']) ? $item['nat']->package_price ?? 0 : 0,
                                        'package_unit' => isset($item['nat'])
                                            ? $item['nat']->package_unit ?? 'Bks'
                                            : 'Bks',
                                        'price_per_unit' => $groutPricePerPackage,
                                        'price_unit_label' => isset($item['nat'])
                                            ? $item['nat']->package_unit ?? 'Bks'
                                            : 'Bks',
                                        'price_calc_qty' => $res['grout_packages'] ?? 0,
                                        'price_calc_unit' => 'Bks',
                                        'total_price' => $res['total_grout_price'] ?? 0,
                                        'unit_price' => $groutPricePerPackage,
                                        'unit_price_label' => isset($item['nat'])
                                            ? $item['nat']->package_unit ?? 'Bks'
                                            : 'Bks',
                                    ],
                                    'water' => [
                                        'name' => 'Air',
                                        'check_field' => 'total_water_liters',
                                        'qty' => $res['total_water_liters'] ?? ($res['water_liters'] ?? 0),
                                        'qty_debug' =>
                                            $res['water_liters_debug'] ?? '' ?:
                                            'Kebutuhan air untuk area ' . $formatNum($areaForCost) . ' M2',
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
                                        'total_price' => 0,
                                        'unit_price' => 0,
                                        'unit_price_label' => '',
                                        'is_special' => true, // Special handling for water
                                    ],
                                ];

                                $bundleMaterialRows = $item['bundle_material_rows'] ?? [];
                                if (is_array($bundleMaterialRows) && !empty($bundleMaterialRows)) {
                                    $materialConfig = [];
                                    foreach (array_values($bundleMaterialRows) as $bundleIndex => $bundleRow) {
                                        if (!is_array($bundleRow)) {
                                            continue;
                                        }
                                        $materialTypeKey = trim((string) ($bundleRow['material_key'] ?? ''));
                                        if ($materialTypeKey === '') {
                                            $materialTypeKey = 'material';
                                        }
                                        $bundleRow['material_key'] = $materialTypeKey;
                                        if (!isset($bundleRow['check_field'])) {
                                            $bundleRow['check_field'] = 'qty';
                                        }
                                        $materialConfig[$materialTypeKey . '_' . $bundleIndex] = $bundleRow;
                                    }
                                }

                                // Filter materials: only show if qty > 0
                                $visibleMaterials = array_filter($materialConfig, function ($mat) {
                                    return isset($mat['qty']) && $mat['qty'] > 0;
                                });

                                // Calculate rowspan based on visible materials
                                $rowCount = count($visibleMaterials);
                                $storePlan = is_array($item['store_plan'] ?? null) ? $item['store_plan'] : [];
                                $storeCostBreakdown = is_array($item['store_cost_breakdown'] ?? null)
                                    ? $item['store_cost_breakdown']
                                    : [];
                                $storeCoverageMode = $item['store_coverage_mode'] ?? null;
                                $showStorePlan = !empty($storePlan);
                            @endphp

                            {{-- ROW 0: GROUP NAME / LABEL --}}
                            <tr class="{{ $isFirstOption ? '' : 'group-divider' }}"
                                id="detail-{{ strtolower(str_replace(' ', '-', $label)) }}">
                                <td colspan="3" class="text-start align-middle sticky-label-row sticky-col-label"
                                    style="background: #f8fafc; padding: 10px 16px; font-weight: 600;">
                                    @php
                                        // Definisi warna dengan 3 level gradasi (1=gelap, 2=sedang, 3=cerah)
                                        $labelColors = [
                                            'Semua' => [
                                                1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                                                2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                                                3 => ['bg' => '#ffffff', 'border' => '#e2e8f0', 'text' => '#64748b'],
                                            ],
                                            'Preferensi' => [
                                                1 => ['bg' => '#fca5a5', 'border' => '#f87171', 'text' => '#991b1b'],
                                                2 => ['bg' => '#fecaca', 'border' => '#fca5a5', 'text' => '#dc2626'],
                                                3 => ['bg' => '#fee2e2', 'border' => '#fecaca', 'text' => '#ef4444'],
                                            ],
                                            'Populer' => [
                                                1 => ['bg' => '#93c5fd', 'border' => '#60a5fa', 'text' => '#1e40af'],
                                                2 => ['bg' => '#bfdbfe', 'border' => '#93c5fd', 'text' => '#2563eb'],
                                                3 => ['bg' => '#dbeafe', 'border' => '#bfdbfe', 'text' => '#3b82f6'],
                                            ],
                                            'Ekonomis' => [
                                                1 => ['bg' => '#6ee7b7', 'border' => '#34d399', 'text' => '#065f46'],
                                                2 => ['bg' => '#a7f3d0', 'border' => '#6ee7b7', 'text' => '#16a34a'],
                                                3 => ['bg' => '#d1fae5', 'border' => '#a7f3d0', 'text' => '#22c55e'],
                                            ],
                                            'Average' => [
                                                1 => ['bg' => '#fcd34d', 'border' => '#fbbf24', 'text' => '#92400e'],
                                                2 => ['bg' => '#fde68a', 'border' => '#fcd34d', 'text' => '#b45309'],
                                                3 => ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#d97706'],
                                            ],
                                            'Termahal' => [
                                                1 => ['bg' => '#d8b4fe', 'border' => '#c084fc', 'text' => '#6b21a8'],
                                                2 => ['bg' => '#e9d5ff', 'border' => '#d8b4fe', 'text' => '#7c3aed'],
                                                3 => ['bg' => '#f3e8ff', 'border' => '#e9d5ff', 'text' => '#9333ea'],
                                            ],
                                            'Custom' => [
                                                1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                3 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                            ],
                                        ];

                                        // Split label berdasarkan " = " untuk handle multiple labels
                                        $labelParts = array_map('trim', explode('=', $label));
                                    @endphp
                                    <div
                                        style="display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                        <span style="color: #891313; font-weight: 700; font-size: 11px;">
                                            #{{ $globalIndex }}
                                        </span>
                                        @foreach ($labelParts as $index => $singleLabel)
                                            @php
                                                // Extract prefix dari label (sebelum angka)
                                                $labelPrefix = preg_replace('/\s+\d+.*$/', '', $singleLabel);
                                                $labelPrefix = trim($labelPrefix);

                                                // Extract nomor dari label (contoh: "Preferensi 1" -> 1)
                                                preg_match('/\s+(\d+)/', $singleLabel, $matches);
                                                $number = isset($matches[1]) ? (int) $matches[1] : 1;

                                                // Batasi number ke range 1-3
                                                $number = max(1, min(3, $number));

                                                // Ambil warna berdasarkan prefix dan number
                                                $colorSet = $labelColors[$labelPrefix] ?? [
                                                    1 => [
                                                        'bg' => '#f8fafc',
                                                        'border' => '#cbd5e1',
                                                        'text' => '#64748b',
                                                    ],
                                                    2 => [
                                                        'bg' => '#f8fafc',
                                                        'border' => '#cbd5e1',
                                                        'text' => '#64748b',
                                                    ],
                                                    3 => [
                                                        'bg' => '#f8fafc',
                                                        'border' => '#cbd5e1',
                                                        'text' => '#64748b',
                                                    ],
                                                ];
                                                $color = $colorSet[$number];
                                            @endphp
                                            <a href="#preview-top" class="filter-back-top">
                                                <span class="badge"
                                                    style="background: {{ $color['bg'] }}; border: 1.5px solid {{ $color['border'] }}; color: #000000 !important; padding: 3px 8px; border-radius: 5px; font-weight: 600; font-size: 10px; white-space: nowrap;">
                                                    {{ $singleLabel }}
                                                </span>
                                            </a>
                                            @if ($index < count($labelParts) - 1)
                                                <span style="color: #94a3b8; font-size: 10px; font-weight: 600;">=</span>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td colspan="18" style="background: #f8fafc;"></td>
                            </tr>

                            @if ($showStorePlan)
                                <tr class="store-plan-row" data-store-coverage-mode="{{ $storeCoverageMode }}">
                                    <td colspan="21" style="background: #f8fafc; padding: 8px 12px 10px 12px; border-top: 0;">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge rounded-pill text-bg-light border">Sumber Toko</span>
                                            @if ($storeCoverageMode === 'nearest_radius_chain')
                                                <span class="badge rounded-pill text-bg-success">Nearest Radius Chain</span>
                                            @elseif ($storeCoverageMode)
                                                <span class="badge rounded-pill text-bg-secondary">{{ $storeCoverageMode }}</span>
                                            @endif
                                            @foreach ($storePlan as $storePlanEntry)
                                                @php
                                                    $providedMaterials = $storePlanEntry['provided_materials'] ?? [];
                                                    $materialsText = !empty($providedMaterials)
                                                        ? implode(', ', array_map('ucfirst', $providedMaterials))
                                                        : '-';
                                                @endphp
                                                <span class="badge rounded-pill border text-dark bg-white">
                                                    {{ $storePlanEntry['store_name'] ?? 'Toko' }}
                                                    @if (!empty($storePlanEntry['distance_km']))
                                                        ({{ $storePlanEntry['distance_km'] }} km)
                                                    @endif
                                                    : {{ $materialsText }}
                                                </span>
                                            @endforeach
                                        </div>
                                        @if (!empty($storeCostBreakdown))
                                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                                <span class="badge rounded-pill text-bg-light border">Biaya per Toko</span>
                                                @foreach ($storeCostBreakdown as $costEntry)
                                                    <span class="badge rounded-pill border text-dark bg-white">
                                                        {{ $costEntry['store_name'] ?? 'Toko' }}:
                                                        Rp {{ \App\Helpers\NumberHelper::formatFixed((float) ($costEntry['estimated_cost'] ?? 0), 0) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            {{-- DYNAMIC MATERIAL ROWS --}}
                            @php $matIndex = 0; @endphp
                            @foreach ($visibleMaterials as $matKey => $mat)
                                @php
                                    $matIndex++;
                                    $isFirstMaterial = $matIndex === 1;
                                    $isLastMaterial = $matIndex === count($visibleMaterials);
                                    $materialTypeKey = $mat['material_key'] ?? $matKey;
                                    $pricePerUnit = $mat['price_per_unit'] ?? ($mat['package_price'] ?? 0);
                                    $priceUnitLabel = $mat['price_unit_label'] ?? ($mat['package_unit'] ?? '');
                                    $priceCalcQty = $mat['price_calc_qty'] ?? ($mat['qty'] ?? 0);
                                    $priceCalcUnit = $mat['price_calc_unit'] ?? ($mat['unit'] ?? '');
                                    // Gunakan total hasil formula agar konsisten dengan Trace.
                                    $hargaKomparasi = round((float) ($mat['total_price'] ?? 0), 0);
                                    if (
                                        $hargaKomparasi <= 0 &&
                                        !(isset($mat['is_special']) && $mat['is_special'])
                                    ) {
                                        $hargaKomparasi = round(
                                            (float) (($pricePerUnit ?? 0) * ($priceCalcQty ?? 0)),
                                            0,
                                        );
                                    }
                                    $comparisonUnit = $mat['comparison_unit'] ?? ($mat['unit'] ?? '');
                                    $detailValue = $mat['detail_value'] ?? 1;

                                    $qtyTitleParts = [];
                                    if (!empty($mat['qty_debug'])) {
                                        $qtyTitleParts[] = $mat['qty_debug'];
                                    }
                                    $qtyTitleParts[] =
                                        'Nilai tampil: ' . $formatNum($mat['qty']) . ' ' . ($mat['unit'] ?? '');
                                    $qtyTitle = implode(' | ', $qtyTitleParts);

                                    $detailTitleParts = [];
                                    if (!empty($mat['detail_value_debug'])) {
                                        $detailTitleParts[] = $mat['detail_value_debug'];
                                    }
                                    if (!empty($mat['detail_extra_debug'])) {
                                        $detailTitleParts[] = $mat['detail_extra_debug'];
                                    }
                                    if (!empty($mat['detail_extra'])) {
                                        $detailTitleParts[] = 'Nilai tampil: ' . $mat['detail_extra'];
                                    }
                                    $detailTitle = implode(' | ', $detailTitleParts);

                                    $packagePriceTitleParts = [];
                                    $packagePriceTitleParts[] =
                                        'Nilai tampil: Rp ' .
                                        $formatMoney($mat['package_price']) .
                                        ' / ' .
                                        $mat['package_unit'];
                                    if (
                                        $priceUnitLabel !== $mat['package_unit'] ||
                                        abs($pricePerUnit - $mat['package_price']) > 0.00001
                                    ) {
                                        $packagePriceTitleParts[] =
                                            'Harga unit formula: Rp ' .
                                            $formatMoney($pricePerUnit) .
                                            ' / ' .
                                            $priceUnitLabel;
                                    }
                                    if ($materialTypeKey === 'sand' && $detailValue > 0) {
                                        $convertedSand = $mat['package_price'] / $detailValue;
                                        $packagePriceTitleParts[] =
                                            'Konversi: Rp ' .
                                            $formatMoney($mat['package_price']) .
                                            ' / ' .
                                            $formatNum($detailValue) .
                                            ' ' .
                                            $comparisonUnit .
                                            ' = Rp ' .
                                            $formatMoney($convertedSand) .
                                            ' / ' .
                                            $comparisonUnit;
                                    }
                                    $packagePriceTitle = implode(' | ', $packagePriceTitleParts);
                                @endphp
                                <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                                    {{-- Column 1-3: Qty, Unit, Material Name --}}
                                    <td class="text-end fw-bold sticky-col-1 preview-scroll-td" style="border-right: none;"
                                        title="{{ $qtyTitle }}">
                                        <div class="preview-scroll-cell">
                                            @formatResult($mat['qty'])
                                        </div>
                                    </td>
                                    <td class="text-start sticky-col-2" style="border-left: none; border-right: none;">
                                        {{ $mat['unit'] }}
                                    </td>
                                    <td class="fw-bold sticky-col-3" style="border-left: none;">{{ $mat['name'] }}</td>

                                    {{-- Column 4-9: Material Details --}}
                                    <td class="text-muted" style="border-right: none;">
                                        {{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}</td>
                                    <td class="fw-bold" style="border-left: none; border-right: none;">
                                        {{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}</td>
                                    <td class="{{ $materialTypeKey === 'brick' ? 'text-start text-nowrap' : '' }}"
                                        style="border-left: none; border-right: none;">
                                        {{ $mat['detail_display'] }}</td>
                                    <td class="{{ $materialTypeKey === 'cement' || $materialTypeKey === 'sand' || $materialTypeKey === 'brick' ? 'text-start text-nowrap fw-bold' : '' }} {{ $materialTypeKey === 'brick' ? 'preview-scroll-td' : '' }}"
                                        title="{{ $detailTitle }}" style="border-left: none;">
                                        @if ($materialTypeKey === 'brick')
                                            <div class="preview-scroll-cell">{{ $mat['detail_extra'] ?? '' }}</div>
                                        @else
                                            {{ $mat['detail_extra'] ?? '' }}
                                        @endif
                                    </td>
                                    <td class="preview-scroll-td preview-store-cell">
                                        <div class="preview-scroll-cell">
                                            {{ $mat['store_display'] ?? ($mat['object']->{$mat['store_field']} ?? '-') }}
                                        </div>
                                    </td>
                                    <td class="preview-scroll-td preview-address-cell small text-muted">
                                        <div class="preview-scroll-cell">
                                            {{ $mat['address_display'] ?? ($mat['object']->{$mat['address_field']} ?? '-') }}
                                        </div>
                                    </td>

                                    {{-- Column 10-11: Package Price --}}
                                    @if (isset($mat['is_special']) && $mat['is_special'])
                                        <td class="text-center text-muted" style="border-right: none;">-</td>
                                        <td style="border-left: none;"></td>
                                    @else
                                        <td class="text-nowrap fw-bold" title="{{ $packagePriceTitle }}"
                                            style="border-right: none;">
                                            <div class="d-flex justify-content-between" style="width: 100px;">
                                                <span>Rp</span>
                                                <span>{{ $formatMoney($mat['package_price']) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted text-nowrap ps-1" style="border-left: none;">/
                                            {{ $mat['package_unit'] }}</td>
                                    @endif

                                    {{-- Column 12: Total Price (Harga Komparasi) --}}
                                    @if (isset($mat['is_special']) && $mat['is_special'])
                                        <td class="text-center text-muted">-</td>
                                    @else
                                        @php
                                            // Harga komparasi mengikuti hasil formula (unit price x qty formula).
                                            $normalizedPrice = (float) ($pricePerUnit ?? 0);
                                            $normalizedQty = (float) ($priceCalcQty ?? 0);
                                            $normalizedTotal = (float) ($hargaKomparasi ?? 0);
                                            $hargaKomparasiDebugParts = [];
                                            $hargaKomparasiDebugParts[] =
                                                'Rumus: (Rp ' .
                                                $formatNum($normalizedPrice) .
                                                ' x ' .
                                                $formatNum($normalizedQty) .
                                                ' = Rp ' .
                                                $formatNum($normalizedTotal);
                                            $hargaKomparasiDebug = implode(' | ', $hargaKomparasiDebugParts);
                                        @endphp
                                        <td class="text-nowrap" title="{{ $hargaKomparasiDebug }}">
                                            <div class="d-flex justify-content-between w-100">
                                                <span>Rp</span>
                                                <span>{{ $formatMoney($hargaKomparasi) }}</span>
                                            </div>
                                        </td>
                                    @endif

                                    {{-- Column 13-15: Rowspan columns (Grand Total, Cost per M2, Action) --}}
                                    @if ($isFirstMaterial)
                                        @php
                                            // Build debug breakdown for grand_total (harga per kemasan Ã— qty)
                                            $grandTotalParts = [];
                                            $calculatedGrandTotal = 0;
                                            foreach ($visibleMaterials as $debugMatKey => $debugMat) {
                                                if (isset($debugMat['is_special']) && $debugMat['is_special']) {
                                                    continue;
                                                }

                                                $debugPricePerUnit = (float) ($debugMat['price_per_unit'] ?? ($debugMat['package_price'] ?? 0));
                                                $debugPriceCalcQty = (float) ($debugMat['price_calc_qty'] ?? ($debugMat['qty'] ?? 0));
                                                $debugTotal = round((float) ($debugMat['total_price'] ?? 0), 0);
                                                if ($debugTotal <= 0) {
                                                    $debugTotal = round((float) ($debugPricePerUnit * $debugPriceCalcQty), 0);
                                                }

                                                $calculatedGrandTotal += $debugTotal;

                                                $grandTotalParts[] =
                                                    $debugMat['name'] .
                                                    ' (Rp ' .
                                                    $formatMoney($debugPricePerUnit) .
                                                    ' x ' .
                                                    $formatNum($debugPriceCalcQty) .
                                                    ' = Rp ' .
                                                    $formatMoney($debugTotal) .
                                                    ')';
                                            }
                                            $grandTotalValue = (float) $calculatedGrandTotal;
                                            $grandTotalDebug = 'Rumus: ' . implode(' + ', $grandTotalParts);
                                            $grandTotalDebug .= ' | Total: Rp ' . $formatMoney($grandTotalValue);

                                            // Build debug for costPerM2 (non-rupiah, gunakan float murni)
                                            $normalizedAreaForCost = (float) $areaForCost;
                                            $calculatedCostPerM2 =
                                                $normalizedAreaForCost > 0
                                                    ? $grandTotalValue / $normalizedAreaForCost
                                                    : 0;
                                            $costPerM2Debug =
                                                'Rumus: Rp ' .
                                                $formatMoney($grandTotalValue) .
                                                ' / ' .
                                                $formatNum($normalizedAreaForCost) .
                                                ' M2';
                                            $costPerM2Debug .=
                                                ' | Nilai tampil: Rp ' . $formatMoney($calculatedCostPerM2) . ' / M2';
                                        @endphp
                                        <td rowspan="{{ $rowCount }}"
                                            class="text-end bg-highlight align-top rowspan-cell"
                                            title="{{ $grandTotalDebug }}">
                                            <div class="d-flex justify-content-between w-100">
                                                <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                                <span class="text-success-dark"
                                                    style="font-size: 15px;">{{ $formatMoney($grandTotalValue) }}</span>
                                            </div>
                                        </td>
                                        <td rowspan="{{ $rowCount }}"
                                            class="text-end bg-highlight align-top rowspan-cell"
                                            title="{{ $costPerM2Debug }}" style="border-right: none;">
                                            <div class="d-flex justify-content-between w-100">
                                                <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                                <span class="text-primary-dark"
                                                    style="font-size: 14px;">{{ $formatMoney($calculatedCostPerM2) }}</span>
                                            </div>
                                        </td>
                                        <td rowspan="{{ $rowCount }}"
                                            class="bg-highlight-reverse align-top text-muted fw-bold text-start ps-1 rowspan-cell"
                                            style="max-width: 30px; border-left: none;">/ M2</td>
                                    @endif

                                    {{-- Column 16-17: Harga Beli Aktual / Satuan Komparasi --}}
                                    @if (isset($mat['is_special']) && $mat['is_special'])
                                        <td class="text-center text-muted" style="border-right: none;">-</td>
                                        <td style="border-left: none;"></td>
                                    @else
                                        @php
                                            $normalizedQtyValue = (float) ($mat['qty'] ?? 0);
                                            // Gunakan harga komparasi yang sudah dihitung (sesuai formula)
                                            $totalPriceValue = round((float) $hargaKomparasi, 0);
                                            $normalizedDetailValue = (float) $detailValue;

                                            // Untuk sand, hanya hitung total_price / qty (tanpa pembagian detail_value)
                                            if ($materialTypeKey === 'sand') {
                                                $actualBuyPrice =
                                                    $normalizedQtyValue > 0 ? $totalPriceValue / $normalizedQtyValue : 0;
                                                $hargaBeliAktualDebug =
                                                    'Rumus: Rp ' .
                                                    $formatMoney($totalPriceValue) .
                                                    ' / ' .
                                                    $formatNum($normalizedQtyValue) .
                                                    ' ' .
                                                    $mat['unit'] .
                                                    ' = Rp ' .
                                                    $formatMoney($actualBuyPrice);
                                            } else {
                                                $actualBuyPrice =
                                                    $normalizedQtyValue > 0 && $normalizedDetailValue > 0
                                                        ? $totalPriceValue /
                                                            $normalizedQtyValue /
                                                            $normalizedDetailValue
                                                        : 0;
                                                $hargaBeliAktualDebug =
                                                    'Rumus: Rp ' .
                                                    $formatMoney($totalPriceValue) .
                                                    ' / ' .
                                                    $formatNum($normalizedQtyValue) .
                                                    ' ' .
                                                    $mat['unit'] .
                                                    ' / ' .
                                                    $formatNum($normalizedDetailValue) .
                                                    ' ' .
                                                    $comparisonUnit .
                                                    ' = Rp ' .
                                                    $formatMoney($actualBuyPrice);
                                            }
                                        @endphp
                                        <td class="text-nowrap" style="border-right: none;"
                                            title="{{ $hargaBeliAktualDebug }}">
                                            <div class="d-flex justify-content-between w-100">
                                                <span>Rp</span>
                                                <span>{{ $formatMoney($actualBuyPrice) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted text-nowrap ps-1" style="border-left: none;">/
                                            {{ $comparisonUnit }}</td>
                                    @endif

                                    {{-- Column 18: Action (Rowspan) --}}
                                    @if ($isFirstMaterial)
                                        <td rowspan="{{ $rowCount }}" class="text-center align-top rowspan-cell">
                                            @php
                                                $detailModalId =
                                                    'materialDetailModal_' .
                                                    $globalIndex .
                                                    '_' .
                                                    trim((string) preg_replace('/[^a-z0-9]+/i', '_', strtolower((string) $label)), '_');
                                                $detailMaterialBreakdowns = [];
                                                $isBundleDetail = !empty($item['bundle_items'] ?? null);
                                                $rawBreakdowns = $item['bundle_item_material_breakdowns'] ?? null;
                                                if (is_array($rawBreakdowns)) {
                                                    foreach ($rawBreakdowns as $breakdownIndex => $breakdownRow) {
                                                        if (!is_array($breakdownRow)) {
                                                            continue;
                                                        }
                                                        $materials = $breakdownRow['materials'] ?? [];
                                                        if (!is_array($materials)) {
                                                            $materials = [];
                                                        }
                                                        $materials = array_values(
                                                            array_filter($materials, function ($matRow) {
                                                                return is_array($matRow) && ((float) ($matRow['qty'] ?? 0)) > 0;
                                                            }),
                                                        );
                                                        if (empty($materials)) {
                                                            continue;
                                                        }
                                                        $detailMaterialBreakdowns[] = [
                                                            'title' => $breakdownRow['title'] ?? ('Item ' . ($breakdownIndex + 1)),
                                                            'work_type' => $breakdownRow['work_type'] ?? '',
                                                            'work_type_name' =>
                                                                $breakdownRow['work_type_name'] ??
                                                                ucwords(str_replace('_', ' ', (string) ($breakdownRow['work_type'] ?? ''))),
                                                            'grand_total' => (float) ($breakdownRow['grand_total'] ?? 0),
                                                            'request_data' => is_array($breakdownRow['request_data'] ?? null)
                                                                ? $breakdownRow['request_data']
                                                                : [],
                                                            'materials' => $materials,
                                                        ];
                                                    }
                                                }
                                                if (!empty($detailMaterialBreakdowns)) {
                                                    $buildDetailMaterialSignature = static function (
                                                        string $materialKey,
                                                        array $row,
                                                    ): string {
                                                        $normalize = static function ($value): string {
                                                            $text = trim((string) $value);
                                                            if ($text === '') {
                                                                return '';
                                                            }
                                                            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

                                                            return strtolower($text);
                                                        };

                                                        $model = $row['object'] ?? null;
                                                        $typeField = (string) ($row['type_field'] ?? '');
                                                        $brandField = (string) ($row['brand_field'] ?? '');
                                                        $storeField = (string) ($row['store_field'] ?? '');
                                                        $addressField = (string) ($row['address_field'] ?? '');

                                                        $type = $row['type_display'] ??
                                                            (is_object($model) && $typeField !== '' ? ($model->{$typeField} ?? '') : '');
                                                        $brand = $row['brand_display'] ??
                                                            (is_object($model) && $brandField !== '' ? ($model->{$brandField} ?? '') : '');
                                                        $store = $row['store_display'] ??
                                                            (is_object($model) && $storeField !== '' ? ($model->{$storeField} ?? '') : '');
                                                        $address = $row['address_display'] ??
                                                            (is_object($model) && $addressField !== ''
                                                                ? ($model->{$addressField} ?? '')
                                                                : '');

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
                                                    };

                                                    $groupedDisplayRows = [];
                                                    foreach ($detailMaterialBreakdowns as $breakdownIndex => $breakdownRow) {
                                                        $materials = is_array($breakdownRow['materials'] ?? null)
                                                            ? $breakdownRow['materials']
                                                            : [];
                                                        foreach ($materials as $materialIndex => $materialRow) {
                                                            if (
                                                                !is_array($materialRow) ||
                                                                (bool) ($materialRow['is_special'] ?? false)
                                                            ) {
                                                                continue;
                                                            }

                                                            $materialKey = trim((string) ($materialRow['material_key'] ?? ''));
                                                            if ($materialKey === '') {
                                                                continue;
                                                            }

                                                            $rawTotal = (float) ($materialRow['total_price'] ?? 0);
                                                            if ($rawTotal <= 0) {
                                                                $pricePerUnit = (float) ($materialRow['price_per_unit'] ?? ($materialRow['package_price'] ?? 0));
                                                                $priceCalcQty = (float) ($materialRow['price_calc_qty'] ?? ($materialRow['qty'] ?? 0));
                                                                $rawTotal = $pricePerUnit * $priceCalcQty;
                                                            }

                                                            $signature = $buildDetailMaterialSignature($materialKey, $materialRow);
                                                            $groupedDisplayRows[$signature][] = [
                                                                'breakdown_index' => $breakdownIndex,
                                                                'material_index' => $materialIndex,
                                                                'raw_total' => $rawTotal,
                                                            ];
                                                        }
                                                    }

                                                    foreach ($groupedDisplayRows as $signatureRows) {
                                                        if (empty($signatureRows)) {
                                                            continue;
                                                        }

                                                        $targetRoundedTotal = (int) round(
                                                            array_sum(
                                                                array_map(
                                                                    static fn($entry) => (float) ($entry['raw_total'] ?? 0),
                                                                    $signatureRows,
                                                                ),
                                                            ),
                                                            0,
                                                        );

                                                        $prepared = [];
                                                        $sumFloors = 0;
                                                        foreach ($signatureRows as $entry) {
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

                                                                $rawCompare = ((float) ($b['raw_total'] ?? 0)) <=>
                                                                    ((float) ($a['raw_total'] ?? 0));
                                                                if ($rawCompare !== 0) {
                                                                    return $rawCompare;
                                                                }

                                                                $breakdownCompare = ((int) ($a['breakdown_index'] ?? 0)) <=>
                                                                    ((int) ($b['breakdown_index'] ?? 0));
                                                                if ($breakdownCompare !== 0) {
                                                                    return $breakdownCompare;
                                                                }

                                                                return ((int) ($a['material_index'] ?? 0)) <=>
                                                                    ((int) ($b['material_index'] ?? 0));
                                                            });

                                                            $preparedCount = count($prepared);
                                                            for ($inc = 0; $inc < $remaining && $preparedCount > 0; $inc++) {
                                                                $targetIndex = $inc % $preparedCount;
                                                                $prepared[$targetIndex]['display_total']++;
                                                            }
                                                        }

                                                        foreach ($prepared as $entry) {
                                                            $bi = (int) ($entry['breakdown_index'] ?? -1);
                                                            $mi = (int) ($entry['material_index'] ?? -1);
                                                            if (
                                                                !isset($detailMaterialBreakdowns[$bi]) ||
                                                                !isset($detailMaterialBreakdowns[$bi]['materials']) ||
                                                                !is_array($detailMaterialBreakdowns[$bi]['materials']) ||
                                                                !isset($detailMaterialBreakdowns[$bi]['materials'][$mi]) ||
                                                                !is_array($detailMaterialBreakdowns[$bi]['materials'][$mi])
                                                            ) {
                                                                continue;
                                                            }

                                                            $detailMaterialBreakdowns[$bi]['materials'][$mi]['display_total_price'] = (float) ($entry['display_total'] ?? 0);
                                                        }
                                                    }
                                                }
                                                if (empty($detailMaterialBreakdowns) && !$isBundleDetail) {
                                                    $fallbackMaterials = array_values(
                                                        array_filter($visibleMaterials ?? [], function ($matRow) {
                                                            return is_array($matRow) && ((float) ($matRow['qty'] ?? 0)) > 0;
                                                        }),
                                                    );
                                                    if (!empty($fallbackMaterials)) {
                                                        $detailMaterialBreakdowns[] = [
                                                            'title' => 'Item Pekerjaan',
                                                            'work_type_name' => $formulaName ?? '-',
                                                            'grand_total' => (float) ($item['result']['grand_total'] ?? 0),
                                                            'materials' => $fallbackMaterials,
                                                        ];
                                                    }
                                                }
                                                $bundleDetailFallbackItems = [];
                                                if ($isBundleDetail && empty($detailMaterialBreakdowns)) {
                                                    $rawBundleItems = $item['bundle_items'] ?? [];
                                                    if (is_array($rawBundleItems)) {
                                                        foreach ($rawBundleItems as $bundleItemIndex => $bundleItemRow) {
                                                            if (!is_array($bundleItemRow)) {
                                                                continue;
                                                            }
                                                            $bundleWorkType = trim((string) ($bundleItemRow['work_type'] ?? ''));
                                                            $bundleWorkTypeMeta = $bundleWorkType !== ''
                                                                ? \App\Services\FormulaRegistry::find($bundleWorkType)
                                                                : null;
                                                            $bundleDetailFallbackItems[] = [
                                                                'title' =>
                                                                    $bundleItemRow['title'] ??
                                                                    ('Item ' . ($bundleItemIndex + 1)),
                                                                'work_type_name' =>
                                                                    $bundleWorkTypeMeta['name'] ??
                                                                    ucwords(str_replace('_', ' ', $bundleWorkType)),
                                                                'grand_total' => (float) ($bundleItemRow['grand_total'] ?? 0),
                                                            ];
                                                        }
                                                    }
                                                }
                                                $bundleTracePayloadEncoded = null;
                                                if ($isBundleDetail && is_array($rawBreakdowns)) {
                                                    $bundleTraceItems = [];
                                                    $workItemsPayloadData = [];
                                                    $rawWorkItemsPayload = $requestData['work_items_payload'] ?? null;
                                                    if (is_string($rawWorkItemsPayload) && trim($rawWorkItemsPayload) !== '') {
                                                        $decodedWorkItemsPayload = json_decode($rawWorkItemsPayload, true);
                                                        if (is_array($decodedWorkItemsPayload)) {
                                                            $workItemsPayloadData = array_values($decodedWorkItemsPayload);
                                                        }
                                                    }

                                                    foreach ($rawBreakdowns as $traceItemIndex => $traceRow) {
                                                        if (!is_array($traceRow)) {
                                                            continue;
                                                        }

                                                        $traceWorkType = trim((string) ($traceRow['work_type'] ?? ''));
                                                        if ($traceWorkType === '') {
                                                            continue;
                                                        }

                                                        $traceRequestData = is_array($traceRow['request_data'] ?? null)
                                                            ? $traceRow['request_data']
                                                            : [];
                                                        if (
                                                            empty($traceRequestData) &&
                                                            isset($workItemsPayloadData[$traceItemIndex]) &&
                                                            is_array($workItemsPayloadData[$traceItemIndex])
                                                        ) {
                                                            $traceRequestData = $workItemsPayloadData[$traceItemIndex];
                                                        }

                                                        $traceMaterials = is_array($traceRow['materials'] ?? null) ? $traceRow['materials'] : [];
                                                        $traceMaterialIds = [
                                                            'brick_id' => null,
                                                            'cement_id' => null,
                                                            'sand_id' => null,
                                                            'cat_id' => null,
                                                            'ceramic_id' => null,
                                                            'nat_id' => null,
                                                        ];
                                                        foreach ($traceMaterials as $traceMatRow) {
                                                            if (!is_array($traceMatRow)) {
                                                                continue;
                                                            }
                                                            $traceMatQty = \App\Helpers\NumberHelper::parseNullable(
                                                                $traceMatRow['qty'] ?? null,
                                                            );
                                                            if ($traceMatQty === null || $traceMatQty <= 0) {
                                                                continue;
                                                            }
                                                            $traceMaterialKey = trim((string) ($traceMatRow['material_key'] ?? ''));
                                                            if ($traceMaterialKey === '') {
                                                                continue;
                                                            }
                                                            $traceMatModel = $traceMatRow['object'] ?? null;
                                                            $traceMatId = null;
                                                            if (
                                                                is_object($traceMatModel) &&
                                                                isset($traceMatModel->id) &&
                                                                is_numeric($traceMatModel->id)
                                                            ) {
                                                                $traceMatId = (int) $traceMatModel->id;
                                                            } elseif (
                                                                is_array($traceMatModel) &&
                                                                is_numeric($traceMatModel['id'] ?? null)
                                                            ) {
                                                                $traceMatId = (int) ($traceMatModel['id'] ?? 0);
                                                            }
                                                            if ($traceMatId === null || $traceMatId <= 0) {
                                                                continue;
                                                            }

                                                            if ($traceMaterialKey === 'brick' && $traceMaterialIds['brick_id'] === null) {
                                                                $traceMaterialIds['brick_id'] = $traceMatId;
                                                            } elseif (
                                                                $traceMaterialKey === 'cement' &&
                                                                $traceMaterialIds['cement_id'] === null
                                                            ) {
                                                                $traceMaterialIds['cement_id'] = $traceMatId;
                                                            } elseif ($traceMaterialKey === 'sand' && $traceMaterialIds['sand_id'] === null) {
                                                                $traceMaterialIds['sand_id'] = $traceMatId;
                                                            } elseif ($traceMaterialKey === 'cat' && $traceMaterialIds['cat_id'] === null) {
                                                                $traceMaterialIds['cat_id'] = $traceMatId;
                                                            } elseif (
                                                                $traceMaterialKey === 'ceramic' &&
                                                                $traceMaterialIds['ceramic_id'] === null
                                                            ) {
                                                                $traceMaterialIds['ceramic_id'] = $traceMatId;
                                                            } elseif ($traceMaterialKey === 'nat' && $traceMaterialIds['nat_id'] === null) {
                                                                $traceMaterialIds['nat_id'] = $traceMatId;
                                                            }
                                                        }

                                                        $traceItemParams = [
                                                            'title' => $traceRow['title'] ?? ('Item ' . ($traceItemIndex + 1)),
                                                            'formula_code' => $traceWorkType,
                                                            'work_type' => $traceWorkType,
                                                            'wall_length' =>
                                                                $traceRequestData['wall_length'] ?? ($requestData['wall_length'] ?? null),
                                                            'wall_height' =>
                                                                $traceRequestData['wall_height'] ?? ($requestData['wall_height'] ?? null),
                                                            'area' => $traceRequestData['area'] ?? ($requestData['area'] ?? null),
                                                            'mortar_thickness' =>
                                                                $traceRequestData['mortar_thickness'] ??
                                                                ($requestData['mortar_thickness'] ?? null),
                                                            'grout_thickness' =>
                                                                $traceRequestData['grout_thickness'] ??
                                                                ($requestData['grout_thickness'] ?? null),
                                                            'ceramic_length' =>
                                                                $traceRequestData['ceramic_length'] ??
                                                                ($requestData['ceramic_length'] ?? null),
                                                            'ceramic_width' =>
                                                                $traceRequestData['ceramic_width'] ??
                                                                ($requestData['ceramic_width'] ?? null),
                                                            'ceramic_thickness' =>
                                                                $traceRequestData['ceramic_thickness'] ??
                                                                ($requestData['ceramic_thickness'] ?? null),
                                                            'painting_layers' =>
                                                                $traceRequestData['painting_layers'] ??
                                                                ($requestData['painting_layers'] ?? null),
                                                            'layer_count' =>
                                                                $traceRequestData['layer_count'] ??
                                                                ($requestData['layer_count'] ?? null),
                                                            'plaster_sides' =>
                                                                $traceRequestData['plaster_sides'] ??
                                                                ($requestData['plaster_sides'] ?? null),
                                                            'skim_sides' =>
                                                                $traceRequestData['skim_sides'] ??
                                                                ($requestData['skim_sides'] ?? null),
                                                            'installation_type_id' =>
                                                                $traceRequestData['installation_type_id'] ??
                                                                ($requestData['installation_type_id'] ?? null),
                                                            'mortar_formula_id' =>
                                                                $traceRequestData['mortar_formula_id'] ??
                                                                ($requestData['mortar_formula_id'] ?? null),
                                                        ];

                                                        foreach ($traceMaterialIds as $traceMatKey => $traceMatIdValue) {
                                                            if ($traceMatIdValue !== null && $traceMatIdValue > 0) {
                                                                $traceItemParams[$traceMatKey] = $traceMatIdValue;
                                                            }
                                                        }

                                                        $traceItemParams = array_filter($traceItemParams, function ($value) {
                                                            return $value !== null && $value !== '';
                                                        });

                                                        if (!empty($traceItemParams['formula_code'])) {
                                                            $bundleTraceItems[] = $traceItemParams;
                                                        }
                                                    }

                                                    if (!empty($bundleTraceItems)) {
                                                        $bundleTracePayloadJson = json_encode(
                                                            [
                                                                'items' => $bundleTraceItems,
                                                                'auto_trace' => 1,
                                                            ],
                                                            JSON_UNESCAPED_UNICODE,
                                                        );
                                                        if (is_string($bundleTracePayloadJson) && $bundleTracePayloadJson !== '') {
                                                            $bundleTracePayloadEncoded = rtrim(
                                                                strtr(base64_encode($bundleTracePayloadJson), '+/', '-_'),
                                                                '=',
                                                            );
                                                        }
                                                    }
                                                }

                                                if ($bundleTracePayloadEncoded !== null) {
                                                    $traceUrl =
                                                        route('material-calculator.trace') .
                                                        '?' .
                                                        http_build_query([
                                                            'bundle_trace_payload' => $bundleTracePayloadEncoded,
                                                            'auto_trace' => 1,
                                                        ]);
                                                } else {
                                                    $traceFormulaCode =
                                                        $requestData['formula_code'] ?? ($requestData['work_type'] ?? null);
                                                    $traceCeramic = $item['ceramic'] ?? null;
                                                    $traceCeramicLength =
                                                        $requestData['ceramic_length'] ??
                                                        data_get($requestData, 'ceramic_dimensions.length') ??
                                                        data_get($requestData, 'params.ceramic_length') ??
                                                        data_get($requestData, 'params.ceramic_dimensions.length');
                                                    $traceCeramicWidth =
                                                        $requestData['ceramic_width'] ??
                                                        data_get($requestData, 'ceramic_dimensions.width') ??
                                                        data_get($requestData, 'params.ceramic_width') ??
                                                        data_get($requestData, 'params.ceramic_dimensions.width');
                                                    $traceCeramicThickness =
                                                        $requestData['ceramic_thickness'] ??
                                                        data_get($requestData, 'ceramic_dimensions.thickness') ??
                                                        data_get($requestData, 'params.ceramic_thickness') ??
                                                        data_get($requestData, 'params.ceramic_dimensions.thickness');
                                                    if (
                                                        (!is_numeric($traceCeramicLength) || (float) $traceCeramicLength <= 0) &&
                                                        $traceCeramic
                                                    ) {
                                                        $traceCeramicLength = $traceCeramic->dimension_length ?? null;
                                                    }
                                                    if (
                                                        (!is_numeric($traceCeramicWidth) || (float) $traceCeramicWidth <= 0) &&
                                                        $traceCeramic
                                                    ) {
                                                        $traceCeramicWidth = $traceCeramic->dimension_width ?? null;
                                                    }
                                                    if (
                                                        (!is_numeric($traceCeramicThickness) ||
                                                            (float) $traceCeramicThickness <= 0) &&
                                                        $traceCeramic
                                                    ) {
                                                        $traceCeramicThickness = $traceCeramic->dimension_thickness ?? null;
                                                    }
                                                    $traceParams = [
                                                        'formula_code' => $traceFormulaCode,
                                                        'work_type' => $requestData['work_type'] ?? null,
                                                        'wall_length' => $requestData['wall_length'] ?? null,
                                                        'wall_height' => $requestData['wall_height'] ?? null,
                                                        'area' => $requestData['area'] ?? null,
                                                        'mortar_thickness' => $requestData['mortar_thickness'] ?? null,
                                                        'grout_thickness' => $requestData['grout_thickness'] ?? null,
                                                        'ceramic_length' => $traceCeramicLength,
                                                        'ceramic_width' => $traceCeramicWidth,
                                                        'ceramic_thickness' => $traceCeramicThickness,
                                                        'painting_layers' => $requestData['painting_layers'] ?? null,
                                                        'layer_count' => $requestData['layer_count'] ?? null,
                                                        'auto_trace' => 1,
                                                    ];
                                                    if ($brick) {
                                                        $traceParams['brick_id'] = $brick->id;
                                                    }
                                                    if (isset($item['cement'])) {
                                                        $traceParams['cement_id'] = $item['cement']->id;
                                                    }
                                                    if (isset($item['sand'])) {
                                                        $traceParams['sand_id'] = $item['sand']->id;
                                                    }
                                                    if (isset($item['cat'])) {
                                                        $traceParams['cat_id'] = $item['cat']->id;
                                                    }
                                                    if (
                                                        isset($item['ceramic']) &&
                                                        ($requestData['work_type'] ?? '') !== 'grout_tile'
                                                    ) {
                                                        $traceParams['ceramic_id'] = $item['ceramic']->id;
                                                    }
                                                    if (isset($item['nat'])) {
                                                        $traceParams['nat_id'] = $item['nat']->id;
                                                    }
                                                    $traceUrl =
                                                        route('material-calculator.trace') .
                                                        '?' .
                                                        http_build_query(
                                                            array_filter($traceParams, function ($value) {
                                                                return $value !== null && $value !== '';
                                                            }),
                                                        );
                                                }
                                            @endphp
                                            <div class="d-flex flex-column gap-2 align-items-center">
                                                @if (!empty($detailMaterialBreakdowns) || $isBundleDetail)
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        style="--bs-btn-color: #000000; --bs-btn-hover-color: #000000; --bs-btn-active-color: #000000; color: #000000 !important;"
                                                        data-bs-toggle="modal" data-bs-target="#{{ $detailModalId }}">
                                                        <i class="bi bi-card-list me-1"></i> Detail
                                                    </button>
                                                @endif
                                                <a href="{{ $traceUrl }}" class="btn btn-outline-secondary btn-sm"
                                                    style="--bs-btn-color: #000000; --bs-btn-hover-color: #000000; --bs-btn-active-color: #000000; color: #000000 !important;"
                                                    target="_blank" rel="noopener">
                                                    <i class="bi bi-diagram-3 me-1"></i> Trace
                                                </a>
                                                <form action="{{ route('material-calculations.store') }}" method="POST"
                                                    style="margin: 0;">
                                                    @csrf
                                                    @php
                                                        $flattenHiddenInputs = function (
                                                            string $name,
                                                            mixed $value,
                                                        ) use (&$flattenHiddenInputs, &$hiddenInputs) {
                                                            if (is_array($value)) {
                                                                $isAssoc = array_keys($value) !==
                                                                    range(0, count($value) - 1);
                                                                foreach ($value as $childKey => $childValue) {
                                                                    $childName = $isAssoc
                                                                        ? $name . '[' . $childKey . ']'
                                                                        : $name . '[]';
                                                                    $flattenHiddenInputs($childName, $childValue);
                                                                }
                                                                return;
                                                            }

                                                            $hiddenInputs[] = [
                                                                'name' => $name,
                                                                'value' => is_scalar($value) || $value === null
                                                                    ? (string) $value
                                                                    : json_encode($value),
                                                            ];
                                                        };
                                                        $hiddenInputs = [];
                                                    @endphp
                                                    @foreach ($requestData as $key => $value)
                                                        @if (
                                                            $key != '_token' &&
                                                                $key != 'cement_id' &&
                                                                $key != 'sand_id' &&
                                                                $key != 'brick_ids' &&
                                                                $key != 'brick_id' &&
                                                                $key != 'price_filters' &&
                                                                $key != 'work_type')
                                                            @php
                                                                $flattenHiddenInputs($key, $value);
                                                            @endphp
                                                        @endif
                                                    @endforeach
                                                    @foreach ($hiddenInputs as $hiddenInput)
                                                        <input type="hidden" name="{{ $hiddenInput['name'] }}"
                                                            value="{{ $hiddenInput['value'] }}">
                                                    @endforeach

                                                    {{-- Explicitly pass work_type --}}
                                                    <input type="hidden" name="work_type"
                                                        value="{{ $requestData['work_type'] ?? '' }}">

                                                    {{-- Only pass brick_id if NOT brickless --}}
                                                    @if (!($isBrickless ?? false) && !empty($brick))
                                                        <input type="hidden" name="brick_id"
                                                            value="{{ $brick->id }}">
                                                    @endif

                                                    @if (isset($item['cement']))
                                                        <input type="hidden" name="cement_id"
                                                            value="{{ $item['cement']->id }}">
                                                    @endif
                                                    @if (isset($item['sand']))
                                                        <input type="hidden" name="sand_id"
                                                            value="{{ $item['sand']->id }}">
                                                    @endif
                                                    @if (isset($item['cat']))
                                                        <input type="hidden" name="cat_id"
                                                            value="{{ $item['cat']->id }}">
                                                    @endif
                                                    @if (isset($item['ceramic']) && ($requestData['work_type'] ?? '') !== 'grout_tile')
                                                        <input type="hidden" name="ceramic_id"
                                                            value="{{ $item['ceramic']->id }}">
                                                    @endif
                                                    @if (isset($item['nat']))
                                                        <input type="hidden" name="nat_id"
                                                            value="{{ $item['nat']->id }}">
                                                    @endif
                                                    @if ($isBundleDetail)
                                                        @php
                                                            $bundleMaterialRowsPayload = [];
                                                            $rawBundleMaterialRows = $item['bundle_material_rows'] ?? [];
                                                            if (is_array($rawBundleMaterialRows)) {
                                                                foreach ($rawBundleMaterialRows as $bundleRow) {
                                                                    if (!is_array($bundleRow)) {
                                                                        continue;
                                                                    }
                                                                    $bundleObj = $bundleRow['object'] ?? null;
                                                                    $bundleTypeField = is_string($bundleRow['type_field'] ?? null)
                                                                        ? $bundleRow['type_field']
                                                                        : '';
                                                                    $bundleBrandField = is_string($bundleRow['brand_field'] ?? null)
                                                                        ? $bundleRow['brand_field']
                                                                        : '';
                                                                    $bundleStoreField = is_string($bundleRow['store_field'] ?? null)
                                                                        ? $bundleRow['store_field']
                                                                        : '';
                                                                    $bundleAddressField = is_string($bundleRow['address_field'] ?? null)
                                                                        ? $bundleRow['address_field']
                                                                        : '';

                                                                    $bundleTypeDisplay = trim((string) ($bundleRow['type_display'] ?? ''));
                                                                    if (
                                                                        $bundleTypeDisplay === '' &&
                                                                        is_object($bundleObj) &&
                                                                        $bundleTypeField !== ''
                                                                    ) {
                                                                        $bundleTypeDisplay = trim((string) ($bundleObj->{$bundleTypeField} ?? ''));
                                                                    }

                                                                    $bundleBrandDisplay = trim((string) ($bundleRow['brand_display'] ?? ''));
                                                                    if (
                                                                        $bundleBrandDisplay === '' &&
                                                                        is_object($bundleObj) &&
                                                                        $bundleBrandField !== ''
                                                                    ) {
                                                                        $bundleBrandDisplay = trim((string) ($bundleObj->{$bundleBrandField} ?? ''));
                                                                    }

                                                                    $bundleStoreDisplay = trim((string) ($bundleRow['store_display'] ?? ''));
                                                                    if (
                                                                        $bundleStoreDisplay === '' &&
                                                                        is_object($bundleObj) &&
                                                                        $bundleStoreField !== ''
                                                                    ) {
                                                                        $bundleStoreDisplay = trim((string) ($bundleObj->{$bundleStoreField} ?? ''));
                                                                    }

                                                                    $bundleAddressDisplay = trim((string) ($bundleRow['address_display'] ?? ''));
                                                                    if (
                                                                        $bundleAddressDisplay === '' &&
                                                                        is_object($bundleObj) &&
                                                                        $bundleAddressField !== ''
                                                                    ) {
                                                                        $bundleAddressDisplay = trim((string) ($bundleObj->{$bundleAddressField} ?? ''));
                                                                    }

                                                                    $bundleMaterialRowsPayload[] = [
                                                                        'material_key' => $bundleRow['material_key'] ?? null,
                                                                        'name' => $bundleRow['name'] ?? null,
                                                                        'check_field' => $bundleRow['check_field'] ?? null,
                                                                        'qty' => $bundleRow['qty'] ?? null,
                                                                        'qty_debug' => $bundleRow['qty_debug'] ?? null,
                                                                        'unit' => $bundleRow['unit'] ?? null,
                                                                        'comparison_unit' => $bundleRow['comparison_unit'] ?? null,
                                                                        'detail_value' => $bundleRow['detail_value'] ?? null,
                                                                        'detail_value_debug' => $bundleRow['detail_value_debug'] ?? null,
                                                                        'detail_display' => $bundleRow['detail_display'] ?? null,
                                                                        'detail_extra' => $bundleRow['detail_extra'] ?? null,
                                                                        'store_display' => $bundleStoreDisplay,
                                                                        'address_display' => $bundleAddressDisplay,
                                                                        'type_display' => $bundleTypeDisplay,
                                                                        'brand_display' => $bundleBrandDisplay,
                                                                        'package_price' => $bundleRow['package_price'] ?? null,
                                                                        'package_unit' => $bundleRow['package_unit'] ?? null,
                                                                        'price_per_unit' => $bundleRow['price_per_unit'] ?? null,
                                                                        'price_unit_label' => $bundleRow['price_unit_label'] ?? null,
                                                                        'price_calc_qty' => $bundleRow['price_calc_qty'] ?? null,
                                                                        'price_calc_unit' => $bundleRow['price_calc_unit'] ?? null,
                                                                        'total_price' => $bundleRow['total_price'] ?? null,
                                                                        'unit_price' => $bundleRow['unit_price'] ?? null,
                                                                        'unit_price_label' => $bundleRow['unit_price_label'] ?? null,
                                                                        'is_special' => (bool) ($bundleRow['is_special'] ?? false),
                                                                    ];
                                                                }
                                                            }
                                                        @endphp
                                                        <input type="hidden" name="bundle_selected_label"
                                                            value="{{ $label }}">
                                                        <input type="hidden" name="bundle_selected_result"
                                                            value="{{ json_encode($item['result'] ?? [], JSON_UNESCAPED_UNICODE) }}">
                                                        <input type="hidden" name="bundle_material_rows"
                                                            value="{{ json_encode($bundleMaterialRowsPayload, JSON_UNESCAPED_UNICODE) }}">
                                                    @endif
                                                    <input type="hidden" name="price_filters[]" value="custom">
                                                    <input type="hidden" name="confirm_save" value="1">
                                                    <button type="submit" class="btn-select">
                                                        <i class="bi bi-check-circle me-1"></i> Pilih
                                                    </button>
                                                </form>
                                            </div>
                                            @if (!empty($detailMaterialBreakdowns) || $isBundleDetail)
                                                <div class="modal fade modal-high" id="{{ $detailModalId }}" tabindex="-1"
                                                    aria-labelledby="{{ $detailModalId }}Label" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                        <div class="modal-content">
                                                            <div class="modal-header py-2">
                                                                <h5 class="modal-title fs-6"
                                                                    id="{{ $detailModalId }}Label">
                                                                    Rincian Material per Item Pekerjaan
                                                                </h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body p-2">
                                                                @foreach ($detailMaterialBreakdowns as $detailGroup)
                                                                    @php
                                                                        $groupTitle = trim((string) ($detailGroup['title'] ?? 'Item Pekerjaan'));
                                                                        if ($groupTitle === '') {
                                                                            $groupTitle = 'Item Pekerjaan';
                                                                        }
                                                                        $groupWorkType = trim((string) ($detailGroup['work_type_name'] ?? '-'));
                                                                        if ($groupWorkType === '') {
                                                                            $groupWorkType = '-';
                                                                        }
                                                                        $displayGroupTitle = $groupTitle;
                                                                        if (preg_match('/^item\s+\d+$/i', $displayGroupTitle) && $groupWorkType !== '-') {
                                                                            $displayGroupTitle = $groupWorkType;
                                                                        }
                                                                        $groupGrandTotal = (float) ($detailGroup['grand_total'] ?? 0);
                                                                        $fieldSize = is_array($detailGroup['field_size'] ?? null)
                                                                            ? $detailGroup['field_size']
                                                                            : [];
                                                                        $fieldSizeParts = [];
                                                                        $fieldLength = \App\Helpers\NumberHelper::parseNullable($fieldSize['length'] ?? null);
                                                                        $fieldHeight = \App\Helpers\NumberHelper::parseNullable($fieldSize['height'] ?? null);
                                                                        $fieldArea = \App\Helpers\NumberHelper::parseNullable($fieldSize['area'] ?? null);
                                                                        $fieldIsRollag = (bool) ($fieldSize['is_rollag'] ?? false);
                                                                        $fieldHeightLabel = trim((string) ($fieldSize['height_label'] ?? 'Tinggi'));
                                                                        if ($fieldHeightLabel === '') {
                                                                            $fieldHeightLabel = 'Tinggi';
                                                                        }
                                                                        $fieldLengthUnit = trim((string) ($fieldSize['length_unit'] ?? 'm'));
                                                                        if ($fieldLengthUnit === '') {
                                                                            $fieldLengthUnit = 'm';
                                                                        }
                                                                        $fieldHeightUnit = trim((string) ($fieldSize['height_unit'] ?? 'm'));
                                                                        if ($fieldHeightUnit === '') {
                                                                            $fieldHeightUnit = 'm';
                                                                        }
                                                                        if ($fieldLength !== null && $fieldLength > 0) {
                                                                            $fieldSizeParts[] =
                                                                                'Panjang: ' .
                                                                                \App\Helpers\NumberHelper::format($fieldLength) .
                                                                                ' ' .
                                                                                strtoupper($fieldLengthUnit);
                                                                        }
                                                                        if (!$fieldIsRollag && $fieldHeight !== null && $fieldHeight > 0) {
                                                                            $fieldSizeParts[] =
                                                                                $fieldHeightLabel .
                                                                                ': ' .
                                                                                \App\Helpers\NumberHelper::format($fieldHeight) .
                                                                                ' ' .
                                                                                strtoupper($fieldHeightUnit);
                                                                        }
                                                                        if ($fieldArea !== null && $fieldArea > 0) {
                                                                            $fieldSizeParts[] =
                                                                                'Luas: ' .
                                                                                \App\Helpers\NumberHelper::format($fieldArea) .
                                                                                ' M2';
                                                                        }
                                                                        $groupMaterials = is_array($detailGroup['materials'] ?? null)
                                                                            ? $detailGroup['materials']
                                                                            : [];
                                                                        $groupComputedGrandTotal = 0.0;
                                                                        foreach ($groupMaterials as $sumMat) {
                                                                            if (!is_array($sumMat)) {
                                                                                continue;
                                                                            }
                                                                            if ((bool) ($sumMat['is_special'] ?? false)) {
                                                                                continue;
                                                                            }
                                                                            $sumPricePerUnit = (float) ($sumMat['price_per_unit'] ?? ($sumMat['package_price'] ?? 0));
                                                                            $sumCalcQty = (float) ($sumMat['price_calc_qty'] ?? ($sumMat['qty'] ?? 0));
                                                                            $sumDisplayTotal = \App\Helpers\NumberHelper::parseNullable(
                                                                                $sumMat['display_total_price'] ?? null,
                                                                            );
                                                                            $sumTotal =
                                                                                $sumDisplayTotal !== null
                                                                                    ? (float) round($sumDisplayTotal, 0)
                                                                                    : round((float) ($sumMat['total_price'] ?? 0), 0);
                                                                            if ($sumDisplayTotal === null && $sumTotal <= 0) {
                                                                                $sumTotal = round((float) ($sumPricePerUnit * $sumCalcQty), 0);
                                                                            }
                                                                            $groupComputedGrandTotal += $sumTotal;
                                                                        }
                                                                        $groupDisplayGrandTotal =
                                                                            $groupComputedGrandTotal > 0
                                                                                ? $groupComputedGrandTotal
                                                                                : round($groupGrandTotal, 0);
                                                                        $materialQtyParts = [];
                                                                        foreach ($groupMaterials as $summaryMat) {
                                                                            if (!is_array($summaryMat)) {
                                                                                continue;
                                                                            }
                                                                            $summaryQty = \App\Helpers\NumberHelper::parseNullable($summaryMat['qty'] ?? null);
                                                                            if ($summaryQty === null || $summaryQty <= 0) {
                                                                                continue;
                                                                            }
                                                                            $summaryName = trim((string) ($summaryMat['name'] ?? 'Material'));
                                                                            if ($summaryName === '') {
                                                                                $summaryName = 'Material';
                                                                            }
                                                                            $summaryUnit = trim((string) ($summaryMat['unit'] ?? ''));
                                                                            $summaryText =
                                                                                $summaryName .
                                                                                ': ' .
                                                                                \App\Helpers\NumberHelper::format($summaryQty);
                                                                            if ($summaryUnit !== '') {
                                                                                $summaryText .= ' ' . $summaryUnit;
                                                                            }
                                                                            $materialQtyParts[] = $summaryText;
                                                                        }
                                                                    @endphp
                                                                    <div class="card border mb-2">
                                                                        <div class="card-body p-2">
                                                                            <div
                                                                                class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                                                                <div class="text-start">
                                                                                    <div class="fw-bold">
                                                                                        {{ $displayGroupTitle }}
                                                                                    </div>
                                                                                    @if ($groupWorkType !== '-' && strcasecmp($displayGroupTitle, $groupWorkType) !== 0)
                                                                                        <div class="small text-muted">
                                                                                            {{ $groupWorkType }}
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                <span
                                                                                    class="badge bg-light border text-dark">
                                                                                    Harga: Rp {{ $formatMoney($groupDisplayGrandTotal) }}
                                                                                </span>
                                                                            </div>
                                                                            @if (!empty($fieldSizeParts))
                                                                                <div class="small text-muted mb-1">
                                                                                    <strong>Ukuran Bidang:</strong>
                                                                                    {{ implode(' | ', $fieldSizeParts) }}
                                                                                </div>
                                                                            @endif
                                                                            @if (!empty($materialQtyParts))
                                                                                <div class="small text-muted mb-2">
                                                                                    <strong>Jumlah Material:</strong>
                                                                                    {{ implode(' | ', $materialQtyParts) }}
                                                                                </div>
                                                                            @endif
                                                                            <div class="table-responsive">
                                                                                <table
                                                                                    class="table table-sm align-middle mb-0 detail-breakdown-table">
                                                                                    <thead class="detail-breakdown-thead">
                                                                                        <tr>
                                                                                            <th class="text-end">Qty
                                                                                            </th>
                                                                                            <th>Sat.</th>
                                                                                            <th>Material</th>
                                                                                            <th>Tipe</th>
                                                                                            <th>Merek</th>
                                                                                            <th>Detail</th>
                                                                                            <th>Toko</th>
                                                                                            <th>Alamat</th>
                                                                                            <th class="text-end">Harga
                                                                                                Beli</th>
                                                                                            <th class="text-end">Biaya
                                                                                                Material</th>
                                                                                            <th class="text-end">Harga
                                                                                                Satuan</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        @foreach ($groupMaterials as $detailMat)
                                                                                            @php
                                                                                                $detailModel = $detailMat['object'] ?? null;
                                                                                                $detailTypeField = $detailMat['type_field'] ?? null;
                                                                                                $detailBrandField = $detailMat['brand_field'] ?? null;
                                                                                                $detailStoreField = $detailMat['store_field'] ?? null;
                                                                                                $detailAddressField = $detailMat['address_field'] ?? null;
                                                                                                $detailType = trim(
                                                                                                    (string) ($detailMat['type_display'] ??
                                                                                                        (is_object($detailModel) && is_string($detailTypeField) && $detailTypeField !== ''
                                                                                                            ? ($detailModel->{$detailTypeField} ?? '')
                                                                                                            : '')),
                                                                                                );
                                                                                                if ($detailType === '') {
                                                                                                    $detailType = '-';
                                                                                                }
                                                                                                $detailBrand = trim(
                                                                                                    (string) ($detailMat['brand_display'] ??
                                                                                                        (is_object($detailModel) && is_string($detailBrandField) && $detailBrandField !== ''
                                                                                                            ? ($detailModel->{$detailBrandField} ?? '')
                                                                                                            : '')),
                                                                                                );
                                                                                                if ($detailBrand === '') {
                                                                                                    $detailBrand = '-';
                                                                                                }
                                                                                                $detailDisplay = trim((string) ($detailMat['detail_display'] ?? '-'));
                                                                                                $detailExtra = trim((string) ($detailMat['detail_extra'] ?? ''));
                                                                                                $detailText = $detailDisplay !== '' ? $detailDisplay : '-';
                                                                                                if ($detailExtra !== '' && $detailExtra !== '-') {
                                                                                                    $detailText .= ' - ' . $detailExtra;
                                                                                                }
                                                                                                $detailStore = trim(
                                                                                                    (string) ($detailMat['store_display'] ??
                                                                                                        (is_object($detailModel) && is_string($detailStoreField) && $detailStoreField !== ''
                                                                                                            ? ($detailModel->{$detailStoreField} ?? '')
                                                                                                            : '')),
                                                                                                );
                                                                                                if ($detailStore === '') {
                                                                                                    $detailStore = '-';
                                                                                                }
                                                                                                $detailAddress = trim(
                                                                                                    (string) ($detailMat['address_display'] ??
                                                                                                        (is_object($detailModel) && is_string($detailAddressField) && $detailAddressField !== ''
                                                                                                            ? ($detailModel->{$detailAddressField} ?? '')
                                                                                                            : '')),
                                                                                                );
                                                                                                if ($detailAddress === '') {
                                                                                                    $detailAddress = '-';
                                                                                                }
                                                                                                $detailQty = \App\Helpers\NumberHelper::parseNullable($detailMat['qty'] ?? null) ?? 0.0;
                                                                                                $detailValue = \App\Helpers\NumberHelper::parseNullable($detailMat['detail_value'] ?? null);
                                                                                                if ($detailValue === null || $detailValue <= 0) {
                                                                                                    $detailValue = 1.0;
                                                                                                }
                                                                                                $detailPricePerUnit = (float) ($detailMat['price_per_unit'] ?? ($detailMat['package_price'] ?? 0));
                                                                                                $detailPriceCalcQty = (float) ($detailMat['price_calc_qty'] ?? ($detailMat['qty'] ?? 0));
                                                                                                $detailDisplayTotal = \App\Helpers\NumberHelper::parseNullable(
                                                                                                    $detailMat['display_total_price'] ?? null,
                                                                                                );
                                                                                                $detailTotalPrice =
                                                                                                    $detailDisplayTotal !== null
                                                                                                        ? (float) round($detailDisplayTotal, 0)
                                                                                                        : round((float) ($detailMat['total_price'] ?? 0), 0);
                                                                                                $detailPackagePrice = (float) ($detailMat['package_price'] ?? 0);
                                                                                                $detailPackageUnit = trim((string) ($detailMat['package_unit'] ?? ''));
                                                                                                if ($detailPackageUnit === '') {
                                                                                                    $detailPackageUnit = '-';
                                                                                                }
                                                                                                $detailComparisonUnit = trim(
                                                                                                    (string) ($detailMat['comparison_unit'] ?? ($detailMat['unit'] ?? '')),
                                                                                                );
                                                                                                if ($detailComparisonUnit === '') {
                                                                                                    $detailComparisonUnit = '-';
                                                                                                }
                                                                                                $detailMaterialKey = trim((string) ($detailMat['material_key'] ?? ''));
                                                                                                $detailIsSpecial = (bool) ($detailMat['is_special'] ?? false);
                                                                                                if (!$detailIsSpecial && $detailDisplayTotal === null && $detailTotalPrice <= 0) {
                                                                                                    $detailTotalPrice = round((float) ($detailPricePerUnit * $detailPriceCalcQty), 0);
                                                                                                }
                                                                                                $detailActualBuyPrice = 0.0;
                                                                                                if (!$detailIsSpecial && $detailQty > 0) {
                                                                                                    if ($detailMaterialKey === 'sand') {
                                                                                                        $detailActualBuyPrice = $detailTotalPrice / $detailQty;
                                                                                                    } else {
                                                                                                        $detailActualBuyPrice = $detailTotalPrice / ($detailQty * $detailValue);
                                                                                                    }
                                                                                                }
                                                                                            @endphp
                                                                                            <tr>
                                                                                                <td class="text-end">
                                                                                                    @formatResult($detailMat['qty'] ?? 0)
                                                                                                </td>
                                                                                                <td>{{ $detailMat['unit'] ?? '-' }}
                                                                                                </td>
                                                                                                <td>{{ $detailMat['name'] ?? '-' }}
                                                                                                </td>
                                                                                                <td>{{ $detailType }}</td>
                                                                                                <td>{{ $detailBrand }}</td>
                                                                                                <td>{{ $detailText }}</td>
                                                                                                <td>{{ $detailStore }}</td>
                                                                                                <td class="small text-muted">
                                                                                                    {{ $detailAddress }}
                                                                                                </td>
                                                                                                <td class="text-end">
                                                                                                    @if ($detailIsSpecial)
                                                                                                        -
                                                                                                    @else
                                                                                                        Rp {{ $formatMoney($detailPackagePrice) }} /
                                                                                                        {{ $detailPackageUnit }}
                                                                                                    @endif
                                                                                                </td>
                                                                                                <td class="text-end">
                                                                                                    @if ($detailIsSpecial)
                                                                                                        -
                                                                                                    @else
                                                                                                        Rp {{ $formatMoney($detailTotalPrice) }}
                                                                                                    @endif
                                                                                                </td>
                                                                                                <td class="text-end">
                                                                                                    @if ($detailIsSpecial)
                                                                                                        -
                                                                                                    @else
                                                                                                        Rp {{ $formatMoney($detailActualBuyPrice) }} /
                                                                                                        {{ $detailComparisonUnit }}
                                                                                                    @endif
                                                                                                </td>
                                                                                            </tr>
                                                                                        @endforeach
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                @if (empty($detailMaterialBreakdowns) && !empty($bundleDetailFallbackItems))
                                                                    <div class="alert alert-warning py-2 px-3 mb-2">
                                                                        Rincian material per item belum tersedia dari cache lama.
                                                                        Jalankan ulang hitung kombinasi untuk memunculkan detail material tiap item.
                                                                    </div>
                                                                    <div class="table-responsive">
                                                                        <table
                                                                            class="table table-sm align-middle mb-0 detail-breakdown-table detail-breakdown-table--fallback">
                                                                            <thead class="detail-breakdown-thead">
                                                                                <tr>
                                                                                    <th>Item Pekerjaan</th>
                                                                                    <th>Jenis</th>
                                                                                    <th class="text-end">Harga</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($bundleDetailFallbackItems as $fallbackItem)
                                                                                    @php
                                                                                        $fallbackTitle = trim((string) ($fallbackItem['title'] ?? 'Item Pekerjaan'));
                                                                                        if ($fallbackTitle === '') {
                                                                                            $fallbackTitle = 'Item Pekerjaan';
                                                                                        }
                                                                                        $fallbackWorkType = trim((string) ($fallbackItem['work_type_name'] ?? '-'));
                                                                                        if ($fallbackWorkType === '') {
                                                                                            $fallbackWorkType = '-';
                                                                                        }
                                                                                        if (preg_match('/^item\s+\d+$/i', $fallbackTitle) && $fallbackWorkType !== '-') {
                                                                                            $fallbackTitle = $fallbackWorkType;
                                                                                        }
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>{{ $fallbackTitle }}</td>
                                                                                        <td>{{ $fallbackWorkType }}</td>
                                                                                        <td class="text-end">
                                                                                            Rp {{ $formatMoney((float) ($fallbackItem['grand_total'] ?? 0)) }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @elseif(empty($detailMaterialBreakdowns) && $isBundleDetail)
                                                                    <div class="text-muted small">
                                                                        Data detail item pekerjaan belum tersedia.
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4 text-center container">
            <p class="" style="font-size: 13px;">
                <i class="bi bi-info-circle me-1"></i> Gunakan tombol <span class="text-muted">Pilih</span> pada kolom
                Aksi untuk menyimpan perhitungan ini ke proyek Anda.
            </p>
        </div>
    </div>
    </div>
    @endif
    </div>

    <style>
        /* Anti-Flicker: Prevent content flash during page load */
        .preview-combinations-page {
            opacity: 0;
            transition: opacity 0.2s ease-in;
        }

        .preview-combinations-page.page-loaded {
            opacity: 1;
        }

        /* Prevent table layout shift */
        .table-responsive {
            min-height: 200px;
            position: relative;
        }

        .table-preview,
        .table-rekap-global {
            table-layout: auto;
            visibility: hidden;
        }

        .table-preview.table-ready,
        .table-rekap-global.table-ready {
            visibility: visible;
        }

        /* Global Text Styling for All Elements */
        .preview-combinations-page h1,
        .preview-combinations-page h2,
        .preview-combinations-page h3,
        .preview-combinations-page h4,
        .preview-combinations-page h5,
        .preview-combinations-page h6,
        .preview-combinations-page p,
        .preview-combinations-page span,
        .preview-combinations-page div,
        .preview-combinations-page a,
        .preview-combinations-page label,
        .preview-combinations-page input,
        .preview-combinations-page select,
        .preview-combinations-page textarea,
        .preview-combinations-page button,
        .preview-combinations-page th,
        .preview-combinations-page td,
        .preview-combinations-page i,
        .preview-combinations-page strong {
            font-family: 'Nunito', sans-serif !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }

        /* Smooth scroll untuk seluruh halaman */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: var(--preview-scroll-offset, 60px);
        }

        .preview-combinations-page [id^="detail-"] {
            scroll-margin-top: var(--preview-scroll-offset, 60px);
        }

        .detail-breakdown-table {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            table-layout: fixed;
        }

        .detail-breakdown-table th,
        .detail-breakdown-table td {
            white-space: nowrap !important;
            vertical-align: top;
            padding: 0.28rem 0.42rem !important;
            font-size: 0.62rem;
            line-height: 1.2;
            width: auto !important;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .detail-breakdown-table thead {
            display: table-header-group !important;
        }

        .detail-breakdown-table thead th {
            font-size: 0.63rem;
            background: #891313 !important;
            color: #ffffff !important;
            border-bottom: 1px solid #6b0f0f;
            text-transform: uppercase;
            letter-spacing: 0.15px;
        }

        .detail-breakdown-table th:nth-child(1),
        .detail-breakdown-table td:nth-child(1) {
            width: 6%;
        }

        .detail-breakdown-table th:nth-child(2),
        .detail-breakdown-table td:nth-child(2) {
            width: 5%;
        }

        .detail-breakdown-table th:nth-child(3),
        .detail-breakdown-table td:nth-child(3) {
            width: 9%;
        }

        .detail-breakdown-table th:nth-child(4),
        .detail-breakdown-table td:nth-child(4) {
            width: 7%;
        }

        .detail-breakdown-table th:nth-child(5),
        .detail-breakdown-table td:nth-child(5) {
            width: 8%;
        }

        .detail-breakdown-table th:nth-child(6),
        .detail-breakdown-table td:nth-child(6) {
            width: 12%;
        }

        .detail-breakdown-table th:nth-child(7),
        .detail-breakdown-table td:nth-child(7) {
            width: 8%;
        }

        .detail-breakdown-table th:nth-child(8),
        .detail-breakdown-table td:nth-child(8) {
            width: 14%;
        }

        /* Allow wrap only for Toko and Alamat columns */
        .detail-breakdown-table th:nth-child(7),
        .detail-breakdown-table th:nth-child(8),
        .detail-breakdown-table td:nth-child(7),
        .detail-breakdown-table td:nth-child(8) {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            overflow: visible;
            text-overflow: unset;
        }

        .detail-breakdown-table th:nth-child(9),
        .detail-breakdown-table td:nth-child(9) {
            width: 11%;
        }

        .detail-breakdown-table th:nth-child(10),
        .detail-breakdown-table td:nth-child(10) {
            width: 10%;
        }

        .detail-breakdown-table th:nth-child(11),
        .detail-breakdown-table td:nth-child(11) {
            width: 10%;
        }

        .detail-breakdown-table--fallback th:nth-child(1),
        .detail-breakdown-table--fallback td:nth-child(1) {
            width: 50% !important;
            min-width: 0 !important;
        }

        .detail-breakdown-table--fallback th:nth-child(2),
        .detail-breakdown-table--fallback td:nth-child(2) {
            width: 30% !important;
            min-width: 0 !important;
        }

        .detail-breakdown-table--fallback th:nth-child(3),
        .detail-breakdown-table--fallback td:nth-child(3) {
            width: 20% !important;
            min-width: 0 !important;
        }

        /* Hover effect untuk button cancel */
        .preview-combinations-page .btn-cancel:hover {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%) !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
        }

        /* Hover effect untuk link rekap - tambahkan underline saat hover */
        .preview-combinations-page .table-preview tbody td a:hover {
            text-decoration: underline !important;
            opacity: 0.8;
        }

        .preview-combinations-page .filter-back-top {
            text-decoration: none;
            color: inherit;
            display: inline-block;
        }

        .preview-combinations-page .preview-param-row {
            justify-content: flex-start;
            min-width: 0;
        }

        .preview-combinations-page .preview-param-row.preview-param-row-with-dropdown {
            overflow: visible;
        }

        .preview-combinations-page .preview-param-items-dropdown {
            position: relative;
            z-index: 95;
        }

        .preview-combinations-page .preview-param-items-dropdown .dropdown-menu {
            z-index: 1305;
            overflow-x: hidden;
            --bundle-col-work: 340px;
            --bundle-col-size: 260px;
            --bundle-col-support: 320px;
        }

        .preview-combinations-page .preview-param-items-dropdown .bundle-param-dropdown-menu {
            width: 100%;
            min-width: 100%;
            max-width: min(100%, calc(100vw - 24px));
        }

        /* Multi-item dropdown should open inline (non-floating) */
        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline {
            z-index: auto;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .dropdown-menu {
            position: static !important;
            inset: auto !important;
            transform: none !important;
            float: none !important;
            margin-top: 0.5rem !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            max-height: none !important;
            overflow-y: visible !important;
            overflow-x: hidden;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-dropdown-menu {
            padding: 0 !important;
            box-shadow: none !important;
            border: 0 !important;
            background: transparent !important;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-card {
            margin-bottom: 0.35rem;
            padding: 0.22rem 0.35rem !important;
            border-radius: 7px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-card:last-child {
            margin-bottom: 0;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-item-layout {
            gap: 0.4rem 0.55rem;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section label {
            font-size: 0.6rem !important;
            margin-bottom: 0.2rem !important;
            letter-spacing: 0.25px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section-fields {
            gap: 0.25rem 0.3rem;
            min-height: 28px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--sm {
            width: 74px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--md {
            width: 88px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-field--ceramic {
            width: 84px;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-section-fields .badge {
            font-size: 0.5rem !important;
            padding: 0.08rem 0.24rem !important;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .bundle-param-worktype-value {
            margin-top: 15px;
            font-size: 0.7rem;
            min-height: 26px;
            padding: 0.16rem 0.34rem;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .form-control {
            font-size: 0.7rem;
            min-height: 26px;
            padding: 0.12rem 0.28rem !important;
            line-height: 1.2;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .input-group-text {
            font-size: 0.56rem !important;
            padding: 0.1rem 0.24rem !important;
            min-height: 26px;
            line-height: 1.1;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .dropdown-toggle {
            font-size: 0.72rem;
            padding: 0.3rem 0.56rem;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle {
            color: #4b5563;
            text-decoration: none !important;
            border: 0 !important;
            border-bottom: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 0.68rem;
            letter-spacing: 0.35px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            line-height: 1.1;
            pointer-events: auto !important;
            outline: none !important;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle::after {
            margin-left: 0.35rem;
            vertical-align: middle;
            border-top-color: #6b7280;
        }

        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle:hover,
        .preview-combinations-page .preview-param-items-dropdown.preview-param-items-dropdown-inline .preview-param-label-toggle:focus {
            color: #111827;
            text-decoration: none;
        }

        .preview-combinations-page .bundle-param-item-card {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .preview-combinations-page .bundle-param-item-layout {
            display: grid;
            grid-template-columns: var(--bundle-col-work) var(--bundle-col-size) var(--bundle-col-support);
            gap: 0.85rem 1rem;
            align-items: start;
            width: 100%;
            min-width: 0;
        }

        .preview-combinations-page .bundle-param-section {
            min-width: 0;
            overflow: hidden;
        }

        .preview-combinations-page .bundle-param-section-fields {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.5rem 0.6rem;
            align-items: flex-end;
            min-height: 46px;
            overflow: hidden;
        }

        .preview-combinations-page .bundle-param-field {
            flex: 0 0 auto;
        }

        .preview-combinations-page .bundle-param-field--sm {
            width: 100px;
        }

        .preview-combinations-page .bundle-param-field--md {
            width: 120px;
        }

        .preview-combinations-page .bundle-param-field--ceramic {
            width: 110px;
        }

        .preview-combinations-page .bundle-param-section-fields .badge {
            font-size: 0.62rem;
            padding: 0.2rem 0.45rem;
            letter-spacing: 0.2px;
        }

        .preview-combinations-page .bundle-param-empty {
            min-width: 96px;
            min-height: 36px;
            padding: 0 0.75rem;
            border: 1px dashed #d1d5db;
            border-radius: 0.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: #9ca3af;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .preview-combinations-page .bundle-param-worktype-value {
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 26px;
        }

        /* Compact and vertically centered variable fields in sticky parameter row */
        .preview-combinations-page .preview-params-sticky .preview-param-row label {
            font-size: 0.6rem !important;
            margin-bottom: 0.2rem !important;
            line-height: 1.1;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row .badge {
            font-size: 0.5rem !important;
            padding: 0.08rem 0.24rem !important;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row .form-control {
            min-height: 26px;
            font-size: 0.7rem;
            padding: 0.12rem 0.28rem !important;
            line-height: 1.1;
            display: flex;
            align-items: center;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row .form-control.text-center {
            justify-content: center;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row .input-group-text {
            min-height: 26px;
            font-size: 0.56rem !important;
            padding: 0.1rem 0.24rem !important;
            line-height: 1.1;
            display: inline-flex;
            align-items: center;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row>div[style*="width: 100px"] {
            width: 74px !important;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row>div[style*="width: 110px"] {
            width: 84px !important;
        }

        .preview-combinations-page .preview-params-sticky .preview-param-row>div[style*="width: 120px"] {
            width: 88px !important;
        }

        @media (max-width: 1199.98px) {
            .preview-combinations-page .bundle-param-item-layout {
                min-width: 100%;
            }
        }

        .preview-combinations-page .preview-params-sticky {
            position: sticky;
            top: 60px;
            z-index: 60;
            background-color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, padding 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease, left 0.2s ease, right 0.2s ease, width 0.2s ease;
            transform-origin: top center;
            will-change: transform, opacity;
        }

        .preview-combinations-page .preview-params-sticky.preview-params-sticky--bundle {
            padding: 0.5rem 0.85rem !important;
        }

        .preview-combinations-page .preview-params-sticky.preview-params-sticky--bundle .preview-param-row {
            gap: 0.45rem !important;
        }

        .preview-combinations-page .preview-params-sticky.preview-params-sticky--bundle .preview-param-items-dropdown-inline>.mb-2 {
            margin-bottom: 0 !important;
        }

        .preview-combinations-page .preview-params-sticky.preview-params-sticky--bundle.preview-params-sticky--bundle-open {
            padding: 0.72rem 0.9rem !important;
        }

        .preview-combinations-page .preview-params-sticky.preview-params-sticky--bundle.preview-params-sticky--bundle-open .preview-param-items-dropdown-inline>.mb-2 {
            margin-bottom: 0.2rem !important;
        }

        .preview-combinations-page .preview-params-sticky>* {
            position: relative;
            z-index: 1;
        }

        .preview-combinations-page .preview-params-sticky.is-sticky-enter,
        .preview-combinations-page .preview-params-sticky.is-sticky-leave {
            opacity: 0.98;
            transform: translateY(-4px);
        }

        .preview-combinations-page .preview-params-fixed {
            position: fixed;
            top: 60px !important;
            z-index: 80;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            padding: 0.45rem 0.9rem !important;
            font-size: 0.9rem;
            border-radius: 0 !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            box-sizing: border-box;
        }

        .preview-combinations-page .preview-params-fixed .preview-param-row {
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 6px !important;
            overflow-x: auto;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0 12px;
            -webkit-overflow-scrolling: touch;
        }

        .preview-combinations-page .preview-params-fixed .preview-param-row.preview-param-row-with-dropdown {
            flex-wrap: wrap !important;
            overflow: visible !important;
            max-width: 100% !important;
        }

        @media (min-width: 576px) {
            .preview-combinations-page .preview-params-fixed .preview-param-row {
                max-width: 540px;
            }
        }

        @media (min-width: 768px) {
            .preview-combinations-page .preview-params-fixed .preview-param-row {
                max-width: 720px;
            }
        }

        @media (min-width: 992px) {
            .preview-combinations-page .preview-params-fixed .preview-param-row {
                max-width: 960px;
            }
        }

        @media (min-width: 1200px) {
            .preview-combinations-page .preview-params-fixed .preview-param-row {
                max-width: 1140px;
            }
        }

        @media (min-width: 1400px) {
            .preview-combinations-page .preview-params-fixed .preview-param-row {
                max-width: 1440px;
            }
        }

        .preview-combinations-page .preview-params-fixed label {
            font-size: 0.6rem !important;
            margin-bottom: 2px !important;
        }

        .preview-combinations-page .preview-params-fixed .badge {
            font-size: 0.58rem !important;
            padding: 2px 5px !important;
        }

        .preview-combinations-page .preview-params-fixed .form-control {
            font-size: 0.74rem !important;
            padding: 2px 6px !important;
            min-height: 0 !important;
            height: auto !important;
        }

        .preview-combinations-page .preview-params-fixed .input-group-text {
            font-size: 0.68rem !important;
            padding: 2px 6px !important;
        }

        .table-rekap-global th {
            padding: 8px 10px !important;
            font-size: 14px !important;
        }

        .table-rekap-global td {
            padding: 8px 10px !important;
            border: 1px solid #f1f5f9;
        }

        .table-rekap-global {
            --sticky-top: 0px;
            --rekap-left-2: 80px;
        }

        .rekap-card {
            overflow: visible;
        }

        .table-rekap-global thead th {
            position: static;
            top: auto;
            z-index: auto;
        }

        .table-rekap-global thead tr:first-child th:first-child {
            border-top-left-radius: 12px;
            background-clip: padding-box;
        }

        .table-rekap-global thead tr:first-child th:last-child {
            border-top-right-radius: 12px;
            background-clip: padding-box;
        }

        .table-rekap-global thead tr:nth-child(2) th {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .table-rekap-global.rekap-sticky-active thead {
            visibility: hidden;
        }

        .table-rekap-global .rekap-sticky-col-label,
        .table-rekap-global .rekap-sticky-col-total {
            background-clip: padding-box;
        }

        .rekap-sticky-header {
            position: fixed;
            left: 0;
            z-index: 120;
            pointer-events: none;
            overflow-x: auto;
            overflow-y: hidden;
            opacity: 0;
            transform: translateY(-6px);
            visibility: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .rekap-sticky-header::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
        }

        .rekap-sticky-header:not(.is-active) {
            pointer-events: none;
        }

        .rekap-sticky-header.is-active {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }

        .rekap-sticky-header.is-scrolling {
            opacity: 0.985;
        }

        .rekap-sticky-header .table-rekap-global {
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }

        .rekap-sticky-header.is-active .table-rekap-global {
            border-radius: 0;
        }

        .rekap-sticky-header.is-active thead th {
            border-radius: 0 !important;
        }

        .rekap-sticky-header .table-rekap-global {
            margin: 0;
        }

        /* Table Styling (shared for normal + multi-ceramic) */
        .table-preview th,
        .table-preview label,
        .table-preview button {
            font-family: 'Nunito', sans-serif !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        .table-preview {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            margin: 0;
        }

        .table-preview th {
            background: #891313;
            color: #ffffff;
            text-align: center;
            font-weight: 900;
            padding: 14px 16px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .table-preview td {
            padding: 14px 16px;
            border: 1px solid #f1f5f9;
            vertical-align: top;
            white-space: nowrap;
        }

        .table-preview:not(.table-rekap-global) tbody tr {
            height: 40px;
        }

        .table-preview:not(.table-rekap-global) tbody td {
            height: 40px;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .table-preview td.preview-scroll-td {
            overflow: hidden;
            white-space: nowrap;
            text-align: left;
        }

        .table-preview td.preview-scroll-td:not(.sticky-col-1):not(.sticky-col-2):not(.sticky-col-3) {
            position: relative;
        }

        .table-preview td.preview-scroll-td.sticky-col-1,
        .table-preview td.preview-scroll-td.sticky-col-2,
        .table-preview td.preview-scroll-td.sticky-col-3 {
            position: sticky;
        }

        .table-preview td.preview-store-cell {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
        }

        .table-preview td.preview-address-cell {
            width: 200px;
            min-width: 200px;
            max-width: 200px;
        }

        .table-preview th.preview-store-cell {
            width: 150px;
            min-width: 150px;
            max-width: 150px;
        }

        .table-preview th.preview-address-cell {
            width: 200px;
            min-width: 200px;
            max-width: 200px;
        }

        .table-preview td.preview-scroll-td.is-scrollable::after {
            content: '...';
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: 600;
            color: rgba(15, 23, 42, 0.85);
            background: linear-gradient(90deg, rgba(248, 250, 252, 0) 0%, rgba(248, 250, 252, 0.95) 40%, rgba(248, 250, 252, 1) 100%);
            padding-left: 8px;
            pointer-events: none;
        }

        .table-preview td.preview-scroll-td.is-scrolled-end::after {
            opacity: 0;
        }

        .table-preview .preview-scroll-cell {
            display: block;
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            scrollbar-color: transparent transparent;
            white-space: nowrap;
        }

        .table-preview .preview-scroll-cell::-webkit-scrollbar {
            height: 0;
        }

        .table-preview tbody tr:last-child td {
            border-bottom: none;
        }

        .table-preview tbody tr:hover td {
            background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
        }

        .bg-highlight {
            background: linear-gradient(to right, #f8fafc 0%, #f1f5f9 100%) !important;
        }

        .bg-highlight-reverse {
            background: linear-gradient(to left, #f8fafc 0%, #f1f5f9 100%) !important;
        }

        .text-primary-dark {
            color: #891313;
            font-weight: 700;
        }

        .text-success-dark {
            color: #059669;
            font-weight: 700;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 1;
        }

        .sticky-col-1 {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 2;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            min-width: 105px;
            max-width: 105px;
            width: 105px;
            backface-visibility: hidden;
            transform: translateZ(0);
        }

        .sticky-col-2 {
            position: sticky;
            left: 117px;
            background-color: white;
            z-index: 2;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            min-width: 60px;
            max-width: 95px;
            width: 60px;
            backface-visibility: hidden;
            transform: translateZ(0);
        }

        .sticky-col-3 {
            position: sticky;
            left: 185px;
            background-color: white;
            z-index: 2;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            min-width: 120px;
            max-width: 120px;
            width: 120px;
            backface-visibility: hidden;
            transform: translateZ(0);
        }

        .table-preview thead th.sticky-col-1,
        .table-preview thead th.sticky-col-2,
        .table-preview thead th.sticky-col-3 {
            background-color: #891313;
            z-index: 3;
        }

        .table-preview tbody tr:hover td.sticky-col-1,
        .table-preview tbody tr:hover td.sticky-col-2,
        .table-preview tbody tr:hover td.sticky-col-3 {
            background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
        }

        .btn-select {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            color: #ffffff;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(137, 19, 19, 0.2);
        }

        .btn-select:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
        }

        .group-divider {
            border-top: 2px solid #891313 !important;
        }

        .group-end {
            border-bottom: 3px solid #891313 !important;
        }

        .group-end td {
            border-bottom: 3px solid #891313 !important;
        }

        .rowspan-cell {
            border-bottom: 3px solid #891313 !important;
        }

        .sticky-col-label {
            position: sticky;
            left: 0;
            z-index: 2;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
            min-width: 320px;
        }

        /* Highlight effect dengan blinking border untuk target row */
        /* Exclude sticky columns to preserve sticky behavior */
        tr:target td:not(.sticky-col-1):not(.sticky-col-2):not(.sticky-col-3):not(.sticky-col-label),
        tr.is-skip-target td:not(.sticky-col-1):not(.sticky-col-2):not(.sticky-col-3):not(.sticky-col-label) {
            animation: border-blink 1.5s ease-in-out 3;
        }

        /* Apply animation to sticky columns without changing position */
        tr:target td.sticky-col-1,
        tr:target td.sticky-col-2,
        tr:target td.sticky-col-3,
        tr:target td.sticky-col-label,
        tr.is-skip-target td.sticky-col-1,
        tr.is-skip-target td.sticky-col-2,
        tr.is-skip-target td.sticky-col-3,
        tr.is-skip-target td.sticky-col-label {
            animation: border-blink-sticky 1.5s ease-in-out 3;
        }

        @keyframes border-blink {

            0%,
            100% {
                box-shadow: inset 0 0 0 0px transparent;
                background-color: transparent;
            }

            25% {
                box-shadow: inset 0 0 0 3px #891313;
                background-color: rgba(137, 19, 19, 0.05);
            }

            50% {
                box-shadow: inset 0 0 0 3px transparent;
                background-color: transparent;
            }

            75% {
                box-shadow: inset 0 0 0 3px #891313;
                background-color: rgba(137, 19, 19, 0.05);
            }
        }

        /* Animation khusus untuk sticky columns - tanpa mengubah position */
        @keyframes border-blink-sticky {

            0%,
            100% {
                box-shadow: inset 0 0 0 0px transparent;
                background-color: transparent;
            }

            25% {
                box-shadow: inset 0 0 0 3px #891313;
                background-color: rgba(137, 19, 19, 0.05);
            }

            50% {
                box-shadow: inset 0 0 0 3px transparent;
                background-color: transparent;
            }

            75% {
                box-shadow: inset 0 0 0 3px #891313;
                background-color: rgba(137, 19, 19, 0.05);
            }
        }
    </style>
@endsection

@section('modals')
    @parent
    @if (!empty($projects))
        @php
            $allPriceRows = [];
            $bestRows = [];
            $hasAllPriceBrick = false;
            foreach ($projects as $project) {
                foreach ($project['combinations'] as $label => $items) {
                    foreach ($items as $item) {
                        $rowBrick = $item['brick'] ?? ($project['brick'] ?? null);
                        $brickLabel = '';
                        if ($rowBrick) {
                            $brickLabel = trim(($rowBrick->brand ?? '') . ' ' . ($rowBrick->type ?? ''));
                            if ($brickLabel === '') {
                                $brickLabel = trim((string) ($rowBrick->material_name ?? ''));
                            }
                            if ($brickLabel === '') {
                                $brickLabel = trim((string) ($rowBrick->type ?? ''));
                            }
                            if ($brickLabel !== '') {
                                $hasAllPriceBrick = true;
                            }
                        }

                        $labelParts = array_map('trim', explode('=', $label));
                        $grandTotal = (float) ($item['result']['grand_total'] ?? 0);

                        // Extract Details
                        $storeInfo = $item['store_label'] ?? null;
                        $cementInfo = isset($item['cement']) ? $item['cement']->brand ?? '' : null;
                        $sandInfo = isset($item['sand']) ? $item['sand']->brand ?? '' : null;
                        $catInfo = isset($item['cat']) ? $item['cat']->brand ?? '' : null;
                        $ceramicInfo = isset($item['ceramic']) ? $item['ceramic']->brand ?? '' : null;
                        $natInfo = isset($item['nat']) ? $item['nat']->brand ?? '' : null;

                        $rowBase = [
                            'label' => $label,
                            'brick' => $brickLabel,
                            'grand_total' => $grandTotal,
                            'store' => $storeInfo,
                            'cement' => $cementInfo,
                            'sand' => $sandInfo,
                            'cat' => $catInfo,
                            'ceramic' => $ceramicInfo,
                            'nat' => $natInfo,
                        ];

                        $bestLabel = null;
                        foreach ($labelParts as $part) {
                            if ($bestLabel === null && str_starts_with($part, 'Preferensi')) {
                                $bestLabel = $part;
                            }
                        }
                        if ($bestLabel) {
                            $bestRows[] = $rowBase + ['display_label' => $bestLabel];
                        }
                        $allPriceRows[] = $rowBase;
                    }
                }
            }
            $commonRows = [];
            if (isset($globalRekapData)) {
                foreach ($globalRekapData as $label => $row) {
                    if (str_starts_with($label, 'Populer')) {
                        $commonRows[] = [
                            'display_label' => $label,
                            'brick' => $row['brick_brand'] ?? '',
                            'grand_total' => (float) ($row['grand_total'] ?? 0),
                        ];
                    }
                }
            }
            $sortByLabelNumber = function ($a, $b) {
                $getNumber = function ($label) {
                    if (preg_match('/\s+(\d+)/', $label, $matches)) {
                        return (int) $matches[1];
                    }
                    return PHP_INT_MAX;
                };
                $numA = $getNumber($a['display_label'] ?? '');
                $numB = $getNumber($b['display_label'] ?? '');
                return $numA <=> $numB;
            };
            usort($bestRows, $sortByLabelNumber);
            usort($commonRows, $sortByLabelNumber);

            usort($allPriceRows, function ($a, $b) {
                if ($a['grand_total'] === $b['grand_total']) {
                    return strcmp($a['label'], $b['label']);
                }
                return $a['grand_total'] <=> $b['grand_total'];
            });
            $sortedCount = count($allPriceRows);
            $EkonomisLimit = min(3, $sortedCount);
            $TermahalStart = $sortedCount > 0 ? max(1, $sortedCount - 2) : 1;
            $averageIndexMap = [];
            if ($sortedCount > 0) {
                $sumTotals = array_sum(array_map(fn($row) => $row['grand_total'], $allPriceRows));
                $averageTotal = $sumTotals / $sortedCount;
                $closestIndex = 0;
                $closestDiff = null;

                foreach ($allPriceRows as $idx => $row) {
                    $diff = abs($row['grand_total'] - $averageTotal);
                    if ($closestDiff === null || $diff < $closestDiff) {
                        $closestDiff = $diff;
                        $closestIndex = $idx;
                    }
                }

                $averageIndices = [$closestIndex];
                $lastPrice = $allPriceRows[$closestIndex]['grand_total'];
                for ($i = $closestIndex + 1; $i < $sortedCount && count($averageIndices) < 3; $i++) {
                    $candidatePrice = $allPriceRows[$i]['grand_total'];
                    if ($candidatePrice <= $lastPrice) {
                        continue;
                    }
                    $averageIndices[] = $i;
                    $lastPrice = $candidatePrice;
                }

                foreach ($averageIndices as $rank => $idx) {
                    $averageIndexMap[$idx] = $rank + 1;
                }
            }

            $sortedIndex = 0;
            foreach ($allPriceRows as &$row) {
                $sortedIndex++;
                $row['index'] = $sortedIndex;
                $displayLabel = 'Harga ' . $sortedIndex;
                if (isset($averageIndexMap[$sortedIndex - 1])) {
                    $displayLabel = 'Average ' . $averageIndexMap[$sortedIndex - 1];
                } elseif ($sortedIndex <= $EkonomisLimit) {
                    $displayLabel = 'Ekonomis ' . $sortedIndex;
                } elseif ($sortedIndex >= $TermahalStart) {
                    $rank = $sortedCount - $sortedIndex + 1;
                    $displayLabel = 'Termahal ' . $rank;
                }
                $row['display_label'] = $displayLabel;
            }
            unset($row);
        @endphp

        <div class="modal fade modal-high" id="allPriceModal" tabindex="-1" aria-labelledby="allPriceModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold" id="allPriceModalLabel">Daftar Semua Grand Total</h5>
                            <div class="small text-muted">Ringkas: hanya label dan grand total.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if (count($allPriceRows) > 0)
                            @if (count($bestRows) > 0)
                                <div class="fw-bold mb-1">Preferensi</div>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">#</th>
                                                <th>Label</th>
                                                @if ($hasAllPriceBrick)
                                                    <th>Bata</th>
                                                @endif
                                                <th class="text-end" style="width: 160px;">Grand Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($bestRows as $index => $row)
                                                <tr>
                                                    <td class="text-muted">{{ $index + 1 }}</td>
                                                    <td>{{ $row['display_label'] }}</td>
                                                    @if ($hasAllPriceBrick)
                                                        <td>{{ $row['brick'] ?: '-' }}</td>
                                                    @endif
                                                    <td class="text-end">Rp
                                                        {{ \App\Helpers\NumberHelper::formatFixed($row['grand_total'], 0) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if (count($commonRows) > 0)
                                <div class="fw-bold mb-1">Populer</div>
                                <div class="table-responsive mb-2">
                                    <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 60px;">#</th>
                                                <th>Label</th>
                                                @if ($hasAllPriceBrick)
                                                    <th>Bata</th>
                                                @endif
                                                <th class="text-end" style="width: 160px;">Grand Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($commonRows as $index => $row)
                                                <tr>
                                                    <td class="text-muted">{{ $index + 1 }}</td>
                                                    <td>{{ $row['display_label'] }}</td>
                                                    @if ($hasAllPriceBrick)
                                                        <td>{{ $row['brick'] ?: '-' }}</td>
                                                    @endif
                                                    <td class="text-end">Rp
                                                        {{ \App\Helpers\NumberHelper::formatFixed($row['grand_total'], 0) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="fw-bold mb-1">Semua Harga (Ekonomis &rarr; Termahal)</div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Label</th>
                                            @if ($hasAllPriceBrick)
                                                <th>Bata</th>
                                            @endif
                                            <th>Toko & Material</th>
                                            <th class="text-end" style="width: 130px;">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($allPriceRows as $row)
                                            <tr>
                                                <td class="text-muted">{{ $row['index'] }}</td>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $row['display_label'] }}</div>
                                                    <div class="text-muted" style="font-size: 0.65rem;">
                                                        {{ $row['label'] }}</div>
                                                </td>
                                                @if ($hasAllPriceBrick)
                                                    <td>{{ $row['brick'] ?: '-' }}</td>
                                                @endif
                                                <td>
                                                    @if (!empty($row['store']))
                                                        <div class="mb-1 text-primary fw-bold"
                                                            style="font-size: 0.75rem;">
                                                            <i
                                                                class="bi bi-shop me-1"></i>{{ Str::before($row['store'], ' (') }}
                                                        </div>
                                                    @endif
                                                    <div class="text-secondary lh-sm" style="font-size: 0.7rem;">
                                                        @if (!empty($row['cement']))
                                                            <span class="d-block">Sem: {{ $row['cement'] }}</span>
                                                        @endif
                                                        @if (!empty($row['sand']))
                                                            <span class="d-block">Pas: {{ $row['sand'] }}</span>
                                                        @endif
                                                        @if (!empty($row['ceramic']))
                                                            <span class="d-block">Ker: {{ $row['ceramic'] }}</span>
                                                        @endif
                                                        @if (!empty($row['nat']))
                                                            <span class="d-block">Nat: {{ $row['nat'] }}</span>
                                                        @endif
                                                        @if (!empty($row['cat']))
                                                            <span class="d-block">Cat: {{ $row['cat'] }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold">Rp
                                                    {{ \App\Helpers\NumberHelper::formatFixed($row['grand_total'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-muted">Tidak ada data kombinasi untuk ditampilkan.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        .modal.modal-high {
            z-index: 20050 !important;
            overscroll-behavior: contain;
        }

        .modal-backdrop.modal-high-backdrop {
            z-index: 20040 !important;
        }

        html.modal-open-force,
        body.modal-open-force {
            overflow: hidden !important;
        }

        .modal.modal-high .modal-body,
        #allPriceModal .modal-body,
        .modal[id^="ceramicAllPriceModal-"] .modal-body {
            overscroll-behavior: contain;
        }

        #allPriceModal .modal-dialog {
            max-width: 520px !important;
            width: 92vw;
        }

        #allPriceModal .modal-body {
            padding: 12px 16px;
        }

        #allPriceModal .all-price-table th,
        #allPriceModal .all-price-table td {
            padding: 4px 6px;
            font-size: 12px;
            line-height: 1.2;
        }

        #allPriceModal .all-price-table th {
            font-weight: 700;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Mark this page as a "Skip Page" for history navigation
        document.body.classList.add('skip-history');

        // Anti-Flicker: Mark page and tables as ready
        function initPageVisibility() {
            // Mark all tables as ready
            document.querySelectorAll('.table-preview, .table-rekap-global').forEach(table => {
                table.classList.add('table-ready');
            });

            // Mark page as loaded
            const pageContainer = document.querySelector('.preview-combinations-page');
            if (pageContainer) {
                requestAnimationFrame(() => {
                    pageContainer.classList.add('page-loaded');
                });
            }
        }

        // Run immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPageVisibility);
        } else {
            initPageVisibility();
        }

        // Robust "Skip" Logic:
        // When submitting the form (moving forward to Result), replace the CURRENT history entry (Preview)
        // with the URL of the Create page. This ensures that hitting "Back" from Result goes straight to Create.
        document.addEventListener('DOMContentLoaded', () => {
            const baseCreateUrl = "{{ $requestData['referrer'] ?? route('material-calculations.create') }}";
            const createPageUrl = baseCreateUrl.includes('?') ?
                `${baseCreateUrl}&resume=1` :
                `${baseCreateUrl}?resume=1`;

            const sessionPayload = @json($requestData ?? []);
            if (sessionPayload && Object.keys(sessionPayload).length) {
                try {
                    localStorage.setItem('materialCalculationSession', JSON.stringify({
                        updatedAt: Date.now(),
                        data: sessionPayload,
                        autoSubmit: false,
                        normalized: true,
                    }));
                } catch (error) {
                    console.warn('Failed to cache calculation session', error);
                }
            }

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    history.replaceState(null, '', createPageUrl);
                });
            });

            // Handle New Calculation Button
            const btnReset = document.getElementById('btnResetSession');
            if (btnReset) {
                btnReset.addEventListener('click', function() {
                    window.location.href = createPageUrl;
                });
            }

            const rekapTable = document.querySelector('[data-rekap-table="true"]');
            const rekapCard = document.querySelector('.rekap-card');
            if (rekapTable) {
                const rekapWrap = rekapTable.closest('.table-responsive');
                const rekapHead = rekapTable.querySelector('thead');
                if (rekapHead) {
                    const stickyContainer = document.createElement('div');
                    stickyContainer.className = 'rekap-sticky-header';

                    const stickyTable = rekapTable.cloneNode(false);
                    const stickyHead = rekapHead.cloneNode(true);
                    stickyTable.appendChild(stickyHead);
                    stickyContainer.appendChild(stickyTable);
                    stickyContainer.style.display = 'none';
                    document.body.appendChild(stickyContainer);

                    const syncWidths = () => {
                        const sourceCells = rekapHead.querySelectorAll('th');
                        const stickyCells = stickyHead.querySelectorAll('th');
                        const rect = rekapTable.getBoundingClientRect();
                        stickyTable.style.width = `${rect.width}px`;
                        const stickyLabelCell = rekapTable.querySelector('.rekap-sticky-col-label');
                        if (stickyLabelCell) {
                            const stickyLeft2 = stickyLabelCell.getBoundingClientRect().width;
                            rekapTable.style.setProperty('--rekap-left-2', `${stickyLeft2}px`);
                            stickyTable.style.setProperty('--rekap-left-2', `${stickyLeft2}px`);
                        }
                        sourceCells.forEach((cell, idx) => {
                            const stickyCell = stickyCells[idx];
                            if (!stickyCell) return;
                            stickyCell.style.width = `${cell.getBoundingClientRect().width}px`;
                        });
                    };

                    const getStickyTop = () => {
                        const topbar = document.querySelector('.global-topbar');
                        let top = topbar ? topbar.getBoundingClientRect().height : 0;
                        const fixedCard = document.querySelector('.preview-params-fixed');
                        if (fixedCard) {
                            top += fixedCard.getBoundingClientRect().height;
                            return top;
                        }
                        const cards = document.querySelectorAll('.preview-params-sticky');
                        let activeCard = null;
                        cards.forEach((card) => {
                            if (!activeCard && card.offsetParent !== null) {
                                activeCard = card;
                            }
                        });
                        if (activeCard) {
                            const rect = activeCard.getBoundingClientRect();
                            if (rect.top <= top + 1) {
                                top += rect.height;
                            }
                        }
                        return top;
                    };

                    const updateSticky = () => {
                        const rect = rekapTable.getBoundingClientRect();
                        const wrapRect = rekapWrap ? rekapWrap.getBoundingClientRect() : rect;
                        const stickyTop = getStickyTop();
                        const headHeight = rekapHead.offsetHeight || 0;
                        const scrollLeft = rekapWrap ? rekapWrap.scrollLeft : 0;
                        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                        stickyContainer.style.top = `${stickyTop}px`;
                        stickyContainer.style.left = `${wrapRect.left}px`;
                        stickyContainer.style.width = `${wrapRect.width}px`;
                        stickyContainer.scrollLeft = scrollLeft;
                        stickyTable.style.transform = '';
                        const detailWrap = document.querySelector('.detail-table-wrap');
                        const detailRect = detailWrap ? detailWrap.getBoundingClientRect() : null;
                        const detailAtTop = detailRect ? detailRect.top <= stickyTop + headHeight : false;
                        const inViewport = rect.bottom > 0 && rect.top < viewportHeight;
                        const isActive =
                            inViewport &&
                            rect.top <= stickyTop &&
                            rect.bottom > stickyTop + headHeight &&
                            !detailAtTop;
                        stickyContainer.classList.toggle('is-active', isActive);
                        rekapTable.classList.toggle('rekap-sticky-active', isActive);
                        stickyContainer.style.display = isActive ? 'block' : 'none';
                    };

                    let rafId = null;
                    let scrollTick = null;
                    let isScrolling = false;
                    const scheduleScrollEnd = () => {
                        if (scrollTick) {
                            clearTimeout(scrollTick);
                        }
                        scrollTick = window.setTimeout(() => {
                            isScrolling = false;
                            stickyContainer.classList.remove('is-scrolling');
                        }, 120);
                    };
                    const scheduleSync = () => {
                        if (rafId) return;
                        rafId = window.requestAnimationFrame(() => {
                            rafId = null;
                            syncWidths();
                            updateSticky();
                        });
                    };

                    scheduleSync();
                    window.addEventListener('scroll', scheduleSync, {
                        passive: true
                    });
                    window.addEventListener('resize', scheduleSync);
                    if (rekapWrap) {
                        rekapWrap.addEventListener('scroll', scheduleSync, {
                            passive: true
                        });
                    }
                    if (document.fonts && document.fonts.ready) {
                        document.fonts.ready.then(scheduleSync).catch(() => {});
                    }

                    const handleScrollState = () => {
                        if (!isScrolling) {
                            isScrolling = true;
                            stickyContainer.classList.add('is-scrolling');
                        }
                        scheduleSync();
                        scheduleScrollEnd();
                    };

                    window.addEventListener('scroll', handleScrollState, {
                        passive: true
                    });
                    if (rekapWrap) {
                        rekapWrap.addEventListener('scroll', handleScrollState, {
                            passive: true
                        });
                    }
                }
            }
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initBundleParamDropdown() {
                const defaultBundleCols = {
                    work: 340,
                    size: 260,
                    support: 320,
                };
                const minBundleCols = {
                    work: 180,
                    size: 200,
                    support: 220,
                };
                const resetBundleMenuColumns = (menu) => {
                    if (!menu) {
                        return;
                    }
                    menu.style.setProperty('--bundle-col-work', `${defaultBundleCols.work}px`);
                    menu.style.setProperty('--bundle-col-size', `${defaultBundleCols.size}px`);
                    menu.style.setProperty('--bundle-col-support', `${defaultBundleCols.support}px`);
                };
                const syncBundleCardOpenState = (wrapper, isOpen) => {
                    const stickyCard = wrapper?.closest('.preview-params-sticky--bundle');
                    if (!stickyCard) {
                        return;
                    }
                    stickyCard.classList.toggle('preview-params-sticky--bundle-open', Boolean(isOpen));
                };
                const measureIntrinsicWidth = (element, host) => {
                    if (!element || !host) {
                        return 0;
                    }
                    const clone = element.cloneNode(true);
                    clone.style.position = 'absolute';
                    clone.style.visibility = 'hidden';
                    clone.style.pointerEvents = 'none';
                    clone.style.left = '-100000px';
                    clone.style.top = '0';
                    clone.style.width = 'max-content';
                    clone.style.minWidth = '0';
                    clone.style.maxWidth = 'none';
                    clone.style.overflow = 'visible';
                    host.appendChild(clone);
                    const width = Math.ceil(clone.getBoundingClientRect().width);
                    clone.remove();
                    return width;
                };
                const clampBundleMenuInViewport = (menu) => {
                    if (!menu) {
                        return;
                    }
                    const wrapper = menu.closest('.preview-param-items-dropdown');
                    if (wrapper && wrapper.classList.contains('preview-param-items-dropdown-inline')) {
                        menu.style.marginLeft = '0px';
                        return;
                    }
                    menu.style.marginLeft = '0px';
                    const margin = 12;
                    const rect = menu.getBoundingClientRect();
                    let shift = 0;
                    if (rect.right > window.innerWidth - margin) {
                        shift = rect.right - (window.innerWidth - margin);
                    }
                    if (rect.left - shift < margin) {
                        shift = rect.left - margin;
                    }
                    if (Math.abs(shift) > 0.5) {
                        menu.style.marginLeft = `${-shift}px`;
                    }
                };

                const syncBundleParamColumns = (wrapper) => {
                    const menu = wrapper?.querySelector('.dropdown-menu');
                    if (!menu) {
                        return;
                    }
                    resetBundleMenuColumns(menu);
                    const isVisible = menu.classList.contains('show') || window.getComputedStyle(menu).display !== 'none';
                    if (!isVisible) {
                        return;
                    }
                    const layouts = menu.querySelectorAll('.bundle-param-item-layout');
                    if (!layouts.length) {
                        return;
                    }

                    let maxWork = 340;
                    let maxSize = 260;
                    let maxSupport = 320;

                    layouts.forEach((layout) => {
                        const workValue = layout.querySelector(
                            '.bundle-param-section--worktype .bundle-param-worktype-value',
                        );
                        const sizeFields = layout.querySelector('.bundle-param-section--size .bundle-param-section-fields');
                        const supportFields = layout.querySelector(
                            '.bundle-param-section--support .bundle-param-section-fields',
                        );

                        maxWork = Math.max(maxWork, measureIntrinsicWidth(workValue, menu) + 10);
                        maxSize = Math.max(maxSize, measureIntrinsicWidth(sizeFields, menu) + 8);
                        maxSupport = Math.max(maxSupport, measureIntrinsicWidth(supportFields, menu) + 8);
                    });

                    const firstLayout = layouts[0];
                    const layoutStyles = firstLayout ? window.getComputedStyle(firstLayout) : null;
                    const columnGap = layoutStyles
                        ? parseFloat(layoutStyles.columnGap || layoutStyles.gap || '16') || 16
                        : 16;
                    const totalGap = columnGap * 2;
                    const menuStyles = window.getComputedStyle(menu);
                    const paddingLeft = parseFloat(menuStyles.paddingLeft || '0') || 0;
                    const paddingRight = parseFloat(menuStyles.paddingRight || '0') || 0;
                    const horizontalPadding = paddingLeft + paddingRight;

                    const triggerWidth = Math.ceil(wrapper.getBoundingClientRect().width);
                    const availableContentWidth = Math.max(0, triggerWidth - horizontalPadding);

                    let sizeCol = maxSize;
                    let supportCol = maxSupport;
                    let workCol = maxWork;

                    // Keep middle/right as close as possible to content, but force total columns to fit dropdown width.
                    const desiredTotal = workCol + sizeCol + supportCol + totalGap;
                    if (desiredTotal > availableContentWidth) {
                        const targetWork = Math.max(minBundleCols.work, Math.min(workCol, Math.round(availableContentWidth * 0.36)));
                        let remainingForRight = Math.max(0, availableContentWidth - totalGap - targetWork);

                        const minRightTotal = minBundleCols.size + minBundleCols.support;
                        if (remainingForRight < minRightTotal) {
                            const fallbackWork = Math.max(120, availableContentWidth - totalGap - minRightTotal);
                            remainingForRight = Math.max(0, availableContentWidth - totalGap - fallbackWork);
                            workCol = fallbackWork;
                        } else {
                            workCol = targetWork;
                        }

                        const desiredRightTotal = sizeCol + supportCol;
                        if (desiredRightTotal > remainingForRight) {
                            const shrinkableSize = Math.max(0, sizeCol - minBundleCols.size);
                            const shrinkableSupport = Math.max(0, supportCol - minBundleCols.support);
                            const totalShrinkable = shrinkableSize + shrinkableSupport;
                            const needShrink = desiredRightTotal - remainingForRight;

                            if (totalShrinkable > 0 && needShrink > 0) {
                                const sizeShrink = Math.min(
                                    shrinkableSize,
                                    Math.round((needShrink * shrinkableSize) / totalShrinkable),
                                );
                                sizeCol -= sizeShrink;
                                supportCol -= Math.min(shrinkableSupport, needShrink - sizeShrink);
                            }

                            sizeCol = Math.max(minBundleCols.size, sizeCol);
                            supportCol = Math.max(minBundleCols.support, supportCol);

                            const overflowAfterMin = sizeCol + supportCol - remainingForRight;
                            if (overflowAfterMin > 0) {
                                const giveFromWork = Math.min(workCol - 120, overflowAfterMin);
                                if (giveFromWork > 0) {
                                    workCol -= giveFromWork;
                                }
                            }
                        }
                    }

                    // Fill remaining space to left column so the row always spans full dropdown width.
                    const occupied = workCol + sizeCol + supportCol + totalGap;
                    if (availableContentWidth > occupied) {
                        workCol += availableContentWidth - occupied;
                    }

                    menu.style.setProperty('--bundle-col-work', `${Math.max(120, Math.round(workCol))}px`);
                    menu.style.setProperty('--bundle-col-size', `${Math.max(140, Math.round(sizeCol))}px`);
                    menu.style.setProperty('--bundle-col-support', `${Math.max(160, Math.round(supportCol))}px`);
                    const isInlineDropdown = wrapper.classList.contains('preview-param-items-dropdown-inline');
                    if (!isInlineDropdown) {
                        clampBundleMenuInViewport(menu);
                    }
                };

                const dropdownWrappers = document.querySelectorAll('.preview-param-items-dropdown');
                dropdownWrappers.forEach((wrapper) => {
                    const button = wrapper.querySelector('[data-param-dropdown-toggle="true"]');
                    const menu = wrapper.querySelector('.dropdown-menu');
                    const isInlineDropdown = wrapper.classList.contains('preview-param-items-dropdown-inline');
                    if (!button || !menu || button.__bundleDropdownBound) {
                        return;
                    }
                    button.__bundleDropdownBound = true;
                    button.style.pointerEvents = 'auto';
                    syncBundleCardOpenState(wrapper, menu.classList.contains('show'));

                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();

                        if (isInlineDropdown) {
                            const willShow = !menu.classList.contains('show');
                            wrapper.classList.toggle('show', willShow);
                            menu.classList.toggle('show', willShow);
                            button.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                            syncBundleCardOpenState(wrapper, willShow);
                            if (willShow) {
                                requestAnimationFrame(() => syncBundleParamColumns(wrapper));
                            } else {
                                resetBundleMenuColumns(menu);
                            }
                            return;
                        }

                        if (window.bootstrap && bootstrap.Dropdown) {
                            const instance = bootstrap.Dropdown.getOrCreateInstance(button, {
                                autoClose: 'outside',
                            });
                            instance.toggle();
                            return;
                        }

                        const willShow = !menu.classList.contains('show');
                        wrapper.classList.toggle('show', willShow);
                        menu.classList.toggle('show', willShow);
                        button.setAttribute('aria-expanded', willShow ? 'true' : 'false');
                        syncBundleCardOpenState(wrapper, willShow);
                        if (willShow) {
                            requestAnimationFrame(() => syncBundleParamColumns(wrapper));
                        } else {
                            if (!isInlineDropdown) {
                                menu.style.marginLeft = '0px';
                            }
                            resetBundleMenuColumns(menu);
                        }
                    });

                    if (!wrapper.__bundleDropdownShownBound && window.bootstrap && bootstrap.Dropdown) {
                        wrapper.__bundleDropdownShownBound = true;
                        wrapper.addEventListener('shown.bs.dropdown', function() {
                            syncBundleCardOpenState(wrapper, true);
                            syncBundleParamColumns(wrapper);
                        });
                        wrapper.addEventListener('hidden.bs.dropdown', function() {
                            syncBundleCardOpenState(wrapper, false);
                            if (!isInlineDropdown) {
                                menu.style.marginLeft = '0px';
                            }
                            resetBundleMenuColumns(menu);
                        });
                    }
                });

                if (!document.__bundleDropdownOutsideBound) {
                    document.__bundleDropdownOutsideBound = true;
                    document.addEventListener('click', function(event) {
                        document.querySelectorAll('.preview-param-items-dropdown').forEach((wrapper) => {
                            if (wrapper.contains(event.target)) {
                                return;
                            }
                            const isInlineDropdown = wrapper.classList.contains('preview-param-items-dropdown-inline');
                            if (window.bootstrap && !isInlineDropdown) {
                                return;
                            }
                            wrapper.classList.remove('show');
                            const menu = wrapper.querySelector('.dropdown-menu');
                            if (menu) {
                                menu.classList.remove('show');
                                if (!isInlineDropdown) {
                                    menu.style.marginLeft = '0px';
                                }
                                resetBundleMenuColumns(menu);
                            }
                            syncBundleCardOpenState(wrapper, false);
                            const button = wrapper.querySelector('[data-param-dropdown-toggle="true"]');
                            if (button) {
                                button.setAttribute('aria-expanded', 'false');
                            }
                        });
                    });
                }

                if (!window.__bundleDropdownResizeBound) {
                    window.__bundleDropdownResizeBound = true;
                    window.addEventListener('resize', function() {
                        document.querySelectorAll('.preview-param-items-dropdown').forEach((wrapper) => {
                            const menu = wrapper.querySelector('.dropdown-menu');
                            if (!menu || !menu.classList.contains('show')) {
                                return;
                            }
                            syncBundleParamColumns(wrapper);
                        });
                    });
                }
            }

            const sessionData = @json($requestData ?? request()->except(['_token', 'confirm_save']));
            try {
                localStorage.setItem('materialCalculationSession', JSON.stringify({
                    updatedAt: Date.now(),
                    data: sessionData,
                    autoSubmit: true,
                }));
                localStorage.setItem('materialCalculationPreview', JSON.stringify({
                    updatedAt: Date.now(),
                    url: window.location.href,
                }));
            } catch (error) {
                console.warn('Failed to store calculation session', error);
            }

            initBundleParamDropdown();
        });
    </script>
    <script>
        (function() {
            function getTopbarHeight() {
                const topbar = document.querySelector('.global-topbar');
                return topbar ? topbar.getBoundingClientRect().height : 0;
            }

            function getVisibleParamCard() {
                const cards = document.querySelectorAll('.preview-params-sticky');
                for (let i = 0; i < cards.length; i += 1) {
                    if (cards[i].offsetParent !== null) {
                        return cards[i];
                    }
                }
                return cards.length ? cards[0] : null;
            }

            function getOverlayHeight() {
                const topbarHeight = getTopbarHeight();
                let overlayHeight = topbarHeight;
                const card = getVisibleParamCard();
                if (!card) {
                    return overlayHeight;
                }
                const rect = card.getBoundingClientRect();
                const isSticky = card.classList.contains('preview-params-fixed') || rect.top <= topbarHeight + 1;
                if (isSticky) {
                    overlayHeight += rect.height;
                }
                return overlayHeight;
            }

            function updatePreviewScrollIndicators() {
                const cells = document.querySelectorAll('.table-preview .preview-scroll-td');
                cells.forEach(function(cell) {
                    const scroller = cell.querySelector('.preview-scroll-cell');
                    if (!scroller) return;
                    const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
                    cell.classList.toggle('is-scrollable', isScrollable);
                    const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
                    cell.classList.toggle('is-scrolled-end', isScrollable && atEnd);
                });
            }

            function bindPreviewScrollHandlers() {
                const cells = document.querySelectorAll('.table-preview .preview-scroll-td');
                cells.forEach(function(cell) {
                    const scroller = cell.querySelector('.preview-scroll-cell');
                    if (!scroller || scroller.__previewScrollBound) return;
                    scroller.__previewScrollBound = true;
                    scroller.addEventListener('scroll', updatePreviewScrollIndicators, {
                        passive: true
                    });
                });
            }

            window.updatePreviewScrollIndicators = function() {
                updatePreviewScrollIndicators();
                bindPreviewScrollHandlers();
                requestAnimationFrame(updatePreviewScrollIndicators);
                setTimeout(updatePreviewScrollIndicators, 60);
            };

            function triggerSkipHighlight(target) {
                if (!target) return;
                target.classList.remove('is-skip-target');
                void target.offsetWidth;
                target.classList.add('is-skip-target');
                if (target.__skipHighlightTimer) {
                    clearTimeout(target.__skipHighlightTimer);
                }
                target.__skipHighlightTimer = window.setTimeout(function() {
                    target.classList.remove('is-skip-target');
                    target.__skipHighlightTimer = null;
                }, 4800);
            }

            function scrollToTargetWithOffset(target, behavior) {
                if (!target) return;
                const overlay = getOverlayHeight();
                const top = target.getBoundingClientRect().top + window.pageYOffset - overlay;
                window.scrollTo({
                    top: Math.max(top, 0),
                    behavior: behavior || 'auto'
                });
                requestAnimationFrame(function() {
                    const overlayAfter = getOverlayHeight();
                    const rect = target.getBoundingClientRect();
                    if (rect.top < overlayAfter + 2) {
                        const adjust = overlayAfter + 2 - rect.top;
                        window.scrollTo({
                            top: Math.max(window.pageYOffset - adjust, 0),
                            behavior: 'auto'
                        });
                    }
                    triggerSkipHighlight(target);
                });
            }

            function updatePreviewScrollOffset() {
                const root = document.documentElement;
                const topbarHeight = getTopbarHeight();
                const offset = Math.ceil(topbarHeight);
                root.style.setProperty('--preview-scroll-offset', `${offset}px`);
            }

            function initPreviewStickyCard() {
                const cards = document.querySelectorAll('.preview-params-sticky');
                cards.forEach(function(card) {
                    if (card.__stickyInited) return;
                    card.__stickyInited = true;

                    const placeholder = document.createElement('div');
                    placeholder.style.display = 'block';
                    placeholder.style.width = '100%';
                    placeholder.style.height = '0px';
                    card.parentNode.insertBefore(placeholder, card);

                    function updateSticky() {
                        const styleTop = window.getComputedStyle(card).getPropertyValue('--sticky-top') ||
                            '60px';
                        const topValue = parseInt(styleTop, 10) || 60;
                        const placeholderRect = placeholder.getBoundingClientRect();
                        const shouldFix = placeholderRect.top <= topValue;

                        if (shouldFix) {
                            if (card.__stickyLeaveTimer) {
                                clearTimeout(card.__stickyLeaveTimer);
                                card.__stickyLeaveTimer = null;
                            }
                            card.__stickyLeaving = false;
                            card.classList.remove('is-sticky-leave');
                            if (!card.classList.contains('preview-params-fixed')) {
                                card.classList.add('preview-params-fixed');
                                card.classList.add('is-sticky-enter');
                                requestAnimationFrame(function() {
                                    card.classList.remove('is-sticky-enter');
                                });
                            }
                            card.style.left = '0px';
                            card.style.right = '0px';
                            card.style.width = '100%';
                            const fixedHeight = card.getBoundingClientRect().height;
                            placeholder.style.height = fixedHeight + 'px';
                        } else {
                            if (card.classList.contains('preview-params-fixed') && !card.__stickyLeaving) {
                                card.__stickyLeaving = true;
                                card.classList.add('is-sticky-leave');
                                card.__stickyLeaveTimer = window.setTimeout(function() {
                                    card.classList.remove('preview-params-fixed');
                                    card.classList.remove('is-sticky-leave');
                                    card.style.left = '';
                                    card.style.right = '';
                                    card.style.width = '';
                                    placeholder.style.height = '0px';
                                    card.__stickyLeaving = false;
                                    card.__stickyLeaveTimer = null;
                                }, 180);
                            } else if (!card.classList.contains('preview-params-fixed')) {
                                card.style.left = '';
                                card.style.right = '';
                                card.style.width = '';
                                placeholder.style.height = '0px';
                            }
                        }
                        updatePreviewScrollOffset();
                    }

                    updateSticky();
                    window.addEventListener('scroll', updateSticky, {
                        passive: true
                    });
                    window.addEventListener('resize', updateSticky);
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                initPreviewStickyCard();
                updatePreviewScrollOffset();
                window.updatePreviewScrollIndicators();
                window.addEventListener('resize', updatePreviewScrollOffset);
                window.addEventListener('resize', window.updatePreviewScrollIndicators);
                if (window.location.hash) {
                    requestAnimationFrame(function() {
                        updatePreviewScrollOffset();
                        const target = document.querySelector(window.location.hash);
                        scrollToTargetWithOffset(target, 'auto');
                    });
                }
            });

            document.addEventListener('click', function(event) {
                const link = event.target.closest('a[href^="#detail-"]');
                if (!link) return;
                const href = link.getAttribute('href');
                const target = href ? document.querySelector(href) : null;
                if (!target) return;
                event.preventDefault();
                updatePreviewScrollOffset();
                scrollToTargetWithOffset(target, 'smooth');
                history.replaceState(null, '', href);
            });
        })();
    </script>

    @if (isset($isMultiCeramic) && $isMultiCeramic && isset($isLazyLoad) && $isLazyLoad)
        <script>
            $(document).ready(function() {
                // Request data untuk AJAX
                const requestData = @json($requestData ?? []);
                const maxConcurrent = 2;
                let activeRequests = 0;
                const queue = [];

                // Function to load combinations for a ceramic
                function loadCeramicCombinations($ceramicProject) {
                    // Check for Group Mode (Type + Size) OR Single Mode (Ceramic ID)
                    const type = $ceramicProject.data('type');
                    const size = $ceramicProject.data('size');
                    const ceramicId = $ceramicProject.data('ceramic-id');

                    const isLoaded = $ceramicProject.data('loaded');

                    // Skip if already loaded
                    if (isLoaded === 'true' || isLoaded === true) {
                        return $.Deferred().resolve().promise();
                    }

                    // Show loading and Reset Progress
                    $ceramicProject.find('.loading-placeholder').show();
                    $ceramicProject.find('.combinations-content').hide();

                    const $progressBar = $ceramicProject.find('.progress-bar');
                    const $progressText = $ceramicProject.find('.progress-text');

                    const formatProgress = (value) => {
                        const scaled = Math.floor(value * 100);
                        const intPart = Math.floor(scaled / 100);
                        const decPart = (scaled % 100).toString().padStart(2, '0');
                        return `${intPart}.${decPart}%`;
                    };

                    $progressBar.css('width', '0%').attr('aria-valuenow', 0);
                    $progressText.text(formatProgress(0));

                    // Start Progress Simulation
                    let progress = 0;
                    const interval = setInterval(function() {
                        // Aggressive start
                        let increment = 0;
                        if (progress < 40) increment = Math.random() * 5 + 2;
                        else if (progress < 70) increment = Math.random() * 2 + 1;
                        else if (progress < 95) increment = 0.5;

                        progress = Math.min(progress + increment, 98);

                        $progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
                        $progressText.text(formatProgress(progress));
                    }, 100);

                    // Store interval to clear it later
                    $ceramicProject.data('loading-interval', interval);

                    // Prepare data payload
                    const payload = {
                        ...requestData,
                        _token: '{{ csrf_token() }}'
                    };

                    if (type && size) {
                        payload.type = type;
                        payload.size = size;
                    } else {
                        payload.ceramic_id = ceramicId;
                    }

                    // AJAX request
                    return $.ajax({
                        url: '{{ route('api.material-calculator.ceramic-combinations') }}',
                        method: 'POST',
                        data: payload,
                        success: function(response) {
                            // Clear Interval
                            clearInterval($ceramicProject.data('loading-interval'));

                            // Force 100%
                            $progressBar.css('width', '100%').attr('aria-valuenow', 100);
                            $progressText.text(formatProgress(100));

                            // Short delay to show 100% before showing content
                            setTimeout(function() {
                                if (response.success) {
                                    // Hide loading, show content
                                    $ceramicProject.find('.loading-placeholder').hide();
                                    const $content = $ceramicProject.find('.combinations-content');
                                    const parsedNodes = $.parseHTML(response.html || '', document, false) || [];
                                    const $fragment = $('<div>').append(parsedNodes);
                                    // Prevent repeated global style injection from lazy-loaded partials.
                                    $fragment.find('style, link[rel="stylesheet"]').remove();
                                    $content.empty().append($fragment.contents()).show();
                                    ensureCeramicModals($content);
                                    $ceramicProject.data('loaded', 'true');
                                    if (typeof window.updatePreviewScrollIndicators ===
                                        'function') {
                                        requestAnimationFrame(function() {
                                            window.updatePreviewScrollIndicators();
                                        });
                                    }
                                } else {
                                    showError($ceramicProject, response.message ||
                                        'Gagal memuat kombinasi');
                                    $ceramicProject.data('loaded', 'false');
                                }
                            }, 300);
                        },
                        error: function(xhr) {
                            // Clear Interval
                            clearInterval($ceramicProject.data('loading-interval'));

                            const errorMsg = xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memuat kombinasi';
                            showError($ceramicProject, errorMsg + ' (Check console for details)');
                            $ceramicProject.data('loaded', 'false');
                        }
                    });
                }

                // Show error message
                function showError($ceramicProject, message) {
                    $ceramicProject.find('.loading-placeholder').hide();
                    $ceramicProject.find('.combinations-content')
                        .html(
                            `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${message}</div>`
                        )
                        .show();
                }

                function ensureCeramicModalInBody(modalEl) {
                    if (!modalEl || !modalEl.id) {
                        return;
                    }
                    const existing = document.querySelectorAll('body > #' + modalEl.id);
                    existing.forEach(function(node) {
                        if (node !== modalEl) {
                            node.remove();
                        }
                    });
                    if (modalEl.parentElement !== document.body) {
                        document.body.appendChild(modalEl);
                    }
                }

                function ensureCeramicModals($container) {
                    if (!$container || !$container.length) {
                        return;
                    }
                    $container.find('.modal[id^="ceramicAllPriceModal"]').each(function() {
                        ensureCeramicModalInBody(this);
                    });
                }

                function enqueueCeramic($ceramicProject) {
                    const isLoaded = $ceramicProject.data('loaded');
                    if (isLoaded === 'true' || isLoaded === true || isLoaded === 'loading') {
                        return;
                    }
                    $ceramicProject.data('loaded', 'loading');
                    queue.push($ceramicProject);
                    processQueue();
                }

                function processQueue() {
                    while (activeRequests < maxConcurrent && queue.length > 0) {
                        const $next = queue.shift();
                        activeRequests++;
                        loadCeramicCombinations($next).always(function() {
                            activeRequests = Math.max(0, activeRequests - 1);
                            processQueue();
                        });
                    }
                }

                function showSizePaneByTarget(targetSelector) {
                    if (!targetSelector) {
                        return;
                    }

                    const $targetPane = $(targetSelector);
                    if (!$targetPane.length) {
                        return;
                    }

                    let $sizeContent = $('#ceramicSizeTabContent');
                    if (!$sizeContent.length) {
                        $sizeContent = $('.preview-combinations-page .tab-content.mt-2').first();
                    }

                    $sizeContent.find('.tab-pane').removeClass('show active');
                    $targetPane.addClass('show active');

                    loadVisibleCeramics();
                }

                function activateSizeButton($button) {
                    if (!$button || !$button.length) {
                        return;
                    }

                    const target = $button.attr('data-bs-target');

                    if (window.bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance($button[0]).show();
                    } else {
                        $button.addClass('active').attr('aria-selected', 'true');
                        $button.closest('.nav').find('.nav-link').not($button).removeClass('active').attr(
                            'aria-selected', 'false');
                        showSizePaneByTarget(target);
                    }

                    showSizePaneByTarget(target);
                }

                function syncActiveSizeForType($typePane) {
                    if (!$typePane || !$typePane.length) {
                        return;
                    }

                    let $sizeButton = $typePane.find('[id^="size-"][id$="-tabs"] .nav-link.active').first();
                    if (!$sizeButton.length) {
                        $sizeButton = $typePane.find('[id^="size-"][id$="-tabs"] .nav-link').first();
                    }

                    activateSizeButton($sizeButton);
                }

                // Load combinations for visible ceramics when tab is shown
                function loadVisibleCeramics() {
                    // Find active leaf panes (deepest visible tabs)
                    const $activeLeafPanes = $('.tab-pane.active').filter(function() {
                        return $(this).find('.tab-pane.active').length === 0;
                    });

                    // Load all ceramics in active leaf panes
                    $activeLeafPanes.each(function() {
                        $(this).find('.ceramic-project[data-loaded="false"]').each(function() {
                            enqueueCeramic($(this));
                        });
                    });
                }

                // On page load: sync size tab with active type, then load ceramics
                const $initialTypePane = $('#ceramicTypeTabContent .tab-pane.active').first();
                syncActiveSizeForType($initialTypePane);
                setTimeout(loadVisibleCeramics, 100);

                // On tab change: Load ceramics in newly shown tab
                $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill',
                    function() {
                        loadVisibleCeramics();
                    });

                $('#ceramicTypeTabs button[data-bs-toggle="pill"], #ceramicTypeTabs button[data-bs-toggle="tab"]').on(
                    'shown.bs.tab shown.bs.pill',
                    function() {
                        const target = $(this).attr('data-bs-target');
                        syncActiveSizeForType($(target));
                    });

                $(document).on('shown.bs.tab shown.bs.pill', '[id^="size-"][id$="-tabs"] .nav-link', function() {
                    showSizePaneByTarget($(this).attr('data-bs-target'));
                });

                $(document).on('click', '[data-ceramic-modal-target]', function(event) {
                    const targetId = $(this).data('ceramic-modal-target');
                    if (!targetId) {
                        return;
                    }
                    const modalEl = document.getElementById(targetId);
                    if (!modalEl) {
                        return;
                    }
                    event.preventDefault();
                    ensureCeramicModalInBody(modalEl);
                    if (window.bootstrap && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                });

                console.log('Lazy loading initialized for', $('.ceramic-project').length, 'ceramics');
            });
        </script>
    @endif
    <script>
        const updateModalScrollLockState = () => {
            const hasVisiblePriorityModal = Boolean(
                document.querySelector(
                    '.modal.show.modal-high, #allPriceModal.show, .modal[id^="ceramicAllPriceModal-"].show',
                ),
            );
            document.documentElement.classList.toggle('modal-open-force', hasVisiblePriorityModal);
            document.body.classList.toggle('modal-open-force', hasVisiblePriorityModal);
        };

        document.addEventListener(
            'click',
            function(event) {
                const trigger = event.target.closest('[data-bs-target^="#materialDetailModal_"]');
                if (!trigger) return;
                const targetSelector = trigger.getAttribute('data-bs-target');
                if (!targetSelector) return;
                const modalEl = document.querySelector(targetSelector);
                if (!modalEl) return;
                modalEl.classList.add('modal-high');
                if (modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }
            },
            true,
        );

        document.addEventListener('shown.bs.modal', function(event) {
            if (!event.target) return;
            const isHighModal =
                event.target.classList.contains('modal-high') ||
                event.target.id === 'allPriceModal' ||
                event.target.id.startsWith('ceramicAllPriceModal-');
            if (!isHighModal) return;
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.classList.add('modal-high-backdrop');
            }
            updateModalScrollLockState();
        });

        document.addEventListener('hidden.bs.modal', function(event) {
            if (!event.target) return;
            const isHighModal =
                event.target.classList.contains('modal-high') ||
                event.target.id === 'allPriceModal' ||
                event.target.id.startsWith('ceramicAllPriceModal-');
            if (!isHighModal) return;
            if (document.querySelector('.modal.show.modal-high')) return;
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.classList.remove('modal-high-backdrop');
            }
            updateModalScrollLockState();
        });

        // Handle Back/Forward Navigation (BFCache) for Preview Page
        window.addEventListener('pageshow', function(event) {
            // If page was restored from BFCache, re-initialize visibility
            if (event.persisted) {
                const pageContainer = document.querySelector('.preview-combinations-page');
                if (pageContainer && !pageContainer.classList.contains('page-loaded')) {
                    initPageVisibility();
                }
            }
        });
    </script>
@endpush
