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
                <button type="button" id="btnResetSession"
                    class="btn-cancel"
                    style="border: 1px solid #891313; background-color: transparent; color: #891313;
                    padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                    display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-arrow-left"></i> Kembali
                </button>
            </div>

            <!-- TENGAH -->
            <div class="position-absolute start-50 translate-middle-x text-center">
                <h2 class="fw-bold mb-0"
                    style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                    Pilih Kombinasi Material
                </h2>
            </div>

            <!-- KANAN -->
            <div>
                @if(!empty($projects))
                    <button type="button"
                        style="border: 1px solid #891313; background-color: transparent; color: #891313;
                        padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                        display: inline-flex; align-items: center; gap: 8px;"
                        data-bs-toggle="modal"
                        data-bs-target="#allPriceModal">
                        <i class="bi bi-list-ul"></i> Daftar Kombinasi Harga
                    </button>
                @endif
            </div>

        </div>
    </div>

    @if(empty($projects) && empty($ceramicProjects ?? []))
        <div class="container">
            <div class="alert" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; padding: 16px 20px; color: #856404;">
                <i class="bi bi-exclamation-triangle me-2"></i> Tidak ditemukan data material yang cocok dengan filter Anda.
            </div>
        </div>
    @elseif(isset($isMultiCeramic) && $isMultiCeramic && isset($groupedCeramics))
        @php
            $workType = $requestData['work_type'] ?? '';
            $isRollag = $workType === 'brick_rollag';
            $isGroutTile = $workType === 'grout_tile';
            $heightLabel = in_array($workType, ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'], true) ? 'LEBAR' : 'TINGGI';
            $lengthValue = $requestData['wall_length'] ?? null;
            $heightValue = $isRollag ? null : ($requestData['wall_height'] ?? null);
            $groutValue = $requestData['grout_thickness'] ?? null;
            $areaValue = $isRollag ? null : ($requestData['area'] ?? null);
            if (!$isRollag && !$areaValue && $lengthValue !== null && $heightValue !== null) {
                $areaValue = $lengthValue * $heightValue;
            }
            $formulaDisplay = $formulaName ?? ($requestData['formula_name'] ?? null);
            $mortarValue = $requestData['mortar_thickness'] ?? null;
            $isPainting = (isset($requestData['work_type']) && ($requestData['work_type'] === 'painting' || $requestData['work_type'] === 'wall_painting'));
            $paramLabel = $isPainting ? 'LAPIS' : 'TEBAL ADUKAN';
            $paramUnit = $isPainting ? 'Lapis' : 'cm';
            // Don't show mortar thickness for grout_tile
            $paramValue = $isGroutTile ? null : ($isPainting ? ($requestData['layer_count'] ?? $requestData['painting_layers'] ?? 2) : $mortarValue);
        @endphp

        @if($formulaDisplay || $paramValue || $lengthValue || $heightValue || $groutValue || $areaValue)
        <div class="container mb-3">
            <div class="card p-3 shadow-sm border-0 ceramic-info-card preview-params-sticky" style="background-color: #fdfdfd; border-radius: 12px;">
                <div class="d-flex flex-wrap align-items-end gap-3 justify-content-start preview-param-row">
                    @if($formulaDisplay)
                    <div style="flex: 1; min-width: 250px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">ITEM PEKERJAAN</span>
                        </label>
                        <div class="form-control fw-bold text-dark" style="background-color: #e9ecef;">
                            {{ $formulaDisplay }}
                        </div>
                    </div>
                    @endif

                    @if($paramValue)
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">{{ $paramLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">@format($paramValue)</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                        </div>
                    </div>
                    @endif

                    @if($lengthValue)
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">PANJANG</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">@format($lengthValue)</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>
                    @endif

                    @if($heightValue)
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">{{ $heightLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">@format($heightValue)</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>
                    @endif

                    @if($groutValue)
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-info text-white border">TEBAL NAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e0f2fe; border-color: #38bdf8;">@format($groutValue)</div>
                            <span class="input-group-text bg-info text-white small px-1" style="font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Panjang Keramik (untuk Pasang Nat saja) --}}
                    @if($isGroutTile && isset($requestData['ceramic_length']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">P. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_length'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Lebar Keramik (untuk Pasang Nat saja) --}}
                    @if($isGroutTile && isset($requestData['ceramic_width']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">L. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_width'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Tebal Keramik (untuk Pasang Nat saja) --}}
                    @if($isGroutTile && isset($requestData['ceramic_thickness']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">T. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_thickness'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                    @endif

                    @if($areaValue)
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center bg-white text-danger px-1" style="border-color: #dc3545;">@format($areaValue)</div>
                            <span class="input-group-text bg-danger text-white small px-1" style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- MULTI-CERAMIC TABS SECTION (COMPACT TWO ROWS) --}}
        <div class="container mb-3">
            <div class="card shadow-sm ceramic-tabs-card" style="border: 1px solid #e2e8f0; background: #ffffff; border-radius: 8px; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); padding: 0;">
                <div class="card-body ceramic-tabs-card-body" style="padding: 6px 8px;">
                    
                    {{-- Row 1: JENIS --}}
                    <div class="ceramic-group-row ceramic-group-row--types">
                        <div class="ceramic-group-label">JENIS:</div>
                        <ul class="nav nav-pills hide-scrollbar ceramic-group-tabs" id="ceramicTypeTabs" role="tablist">
                            @foreach($groupedCeramics as $type => $ceramicsOfType)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link btn-sm {{ $loop->first ? 'active' : '' }}"
                                            style="white-space: nowrap; font-size: 12px; padding: 4px 12px; border-radius: 6px; border: 1px solid #f1f5f9;"
                                            id="type-{{ Str::slug($type) }}-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#type-{{ Str::slug($type) }}"
                                            type="button"
                                            role="tab">
                                        {{ $type }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Row 2: UKURAN (Dynamic Content based on Type) --}}
                    <div class="tab-content" id="ceramicTypeTabContent">
                        @foreach($groupedCeramics as $type => $ceramicsOfType)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="type-{{ Str::slug($type) }}"
                                 role="tabpanel">
                                <div class="ceramic-group-row ceramic-group-row--sizes">
                                    <div class="ceramic-group-label">UKURAN:</div>
                                    <ul class="nav nav-pills hide-scrollbar ceramic-group-tabs" id="size-{{ Str::slug($type) }}-tabs" role="tablist">
                                        @foreach($ceramicsOfType->groupBy('size') as $size => $ceramicsOfSize)
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link btn-sm {{ $loop->first ? 'active' : '' }}"
                                                        style="white-space: nowrap; font-size: 12px; padding: 3px 10px; border-radius: 6px;"
                                                        id="size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}-tab"
                                                        data-bs-toggle="pill"
                                                        data-bs-target="#size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}"
                                                        type="button"
                                                        role="tab">
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
                @foreach($groupedCeramics as $type => $ceramicsOfType)
                    @foreach($ceramicsOfType->groupBy('size') as $size => $ceramicsOfSize)
                        <div class="tab-pane fade {{ $loop->parent->first && $loop->first ? 'show active' : '' }}"
                             id="size-{{ Str::slug($type) }}-{{ str_replace('x', '_', $size) }}"
                             role="tabpanel">
                            
                            <div class="ceramic-project" data-type="{{ $type }}" data-size="{{ $size }}" data-loaded="false">
                                {{-- Compact Loading --}}
                                <div class="loading-placeholder text-center py-4 bg-white rounded-3 border">
                                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                                    <div class="small fw-bold text-dark">Menghitung {{ $type }} {{ $size }}...</div>
                                    <div class="progress mx-auto mt-2" style="height: 4px; width: 120px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
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
            .hide-scrollbar::-webkit-scrollbar { display: none; }
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

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
            #ceramicTypeTabs .nav-link { color: #64748b; background: #f8fafc; font-weight: 600; }
            #ceramicTypeTabs .nav-link.active { 
                background: #891313 !important; 
                color: white !important; 
                border-color: #891313 !important;
                box-shadow: 0 2px 4px rgba(137, 19, 19, 0.2);
            }

            /* SIZE PILLS */
            [id^="size-"][id$="-tabs"] .nav-link { color: #64748b; background: transparent; border: 1px solid #e2e8f0; }
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
                background-color: rgba(255,255,255,0.2) !important;
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
                'best' => 'Rekomendasi',
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
            $rekapCategories = ['Rekomendasi', 'Populer', 'Ekonomis', 'Average', 'Termahal'];
            if (in_array('custom', $filterSet, true)) {
                $filterCategories[] = 'Custom';
                $rekapCategories[] = 'Custom';
            }
            $globalRekapData = [];
            $hasBrick = false;
            $hasCement = false;
            $hasSand = false;
            $hasCat = false;
            $hasCeramic = false;
            $hasNat = false;

            // Build historical material usage map for Populer (same work_type only).
            $workType = $requestData['work_type'] ?? $requestData['work_type_select'] ?? null;
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
                'nat' => \App\Models\Cement::class,
            ];

            $historicalFrequencyQuery = DB::table('brick_calculations')
                ->select(
                    'brick_id',
                    'cement_id',
                    'sand_id',
                    'cat_id',
                    'ceramic_id',
                    'nat_id',
                    DB::raw('count(*) as frequency')
                );
            if ($workType) {
                $historicalFrequencyQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(calculation_params, '$.work_type')) = ?", [$workType]);
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
                    $materialUsage['brick'][$row->brick_id] = ($materialUsage['brick'][$row->brick_id] ?? 0) + $frequency;
                }
                if (!empty($row->cement_id)) {
                    $materialUsage['cement'][$row->cement_id] = ($materialUsage['cement'][$row->cement_id] ?? 0) + $frequency;
                }
                if (!empty($row->sand_id)) {
                    $materialUsage['sand'][$row->sand_id] = ($materialUsage['sand'][$row->sand_id] ?? 0) + $frequency;
                }
                if (!empty($row->cat_id)) {
                    $materialUsage['cat'][$row->cat_id] = ($materialUsage['cat'][$row->cat_id] ?? 0) + $frequency;
                }
                if (!empty($row->ceramic_id)) {
                    $materialUsage['ceramic'][$row->ceramic_id] = ($materialUsage['ceramic'][$row->ceramic_id] ?? 0) + $frequency;
                }
                if (!empty($row->nat_id)) {
                    $materialUsage['nat'][$row->nat_id] = ($materialUsage['nat'][$row->nat_id] ?? 0) + $frequency;
                }
            }
            foreach ($materialTypeFilters as $type => $value) {
                $value = is_string($value) ? trim($value) : $value;
                if (empty($value) || empty($materialUsage[$type]) || empty($materialModelMap[$type])) {
                    if (!empty($value) && empty($materialModelMap[$type])) {
                        $materialUsage[$type] = [];
                    }
                    continue;
                }
                $ids = array_keys($materialUsage[$type]);
                if ($type === 'ceramic') {
                    $targetSize = $normalizeCeramicSize((string) $value);
                    if ($targetSize === '') {
                        $materialUsage[$type] = [];
                        continue;
                    }
                    $matchedIds = $materialModelMap[$type]::whereIn('id', $ids)
                        ->get(['id', 'dimension_length', 'dimension_width'])
                        ->filter(function ($model) use ($formatCeramicSizeValue, $targetSize) {
                            return $formatCeramicSizeValue($model->dimension_length, $model->dimension_width) === $targetSize;
                        })
                        ->pluck('id')
                        ->all();
                } else {
                    $matchedIds = $materialModelMap[$type]::whereIn('id', $ids)
                        ->where('type', $value)
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
            // Keep Populer rows visible even if no historical usage; show "-" placeholders instead.

            // Definisi warna label untuk kolom Rekap (sama dengan yang di tabel utama)
            $rekapLabelColors = [
                'Rekomendasi' => [
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
            $buildRekapEntry = function ($project, $item, $key) use (&$hasBrick, &$hasCement, &$hasSand, &$hasCat, &$hasCeramic, &$hasNat) {
                $res = $item['result'];

                if (($res['total_bricks'] ?? 0) > 0) $hasBrick = true;
                if (($res['cement_sak'] ?? 0) > 0) $hasCement = true;
                if (($res['sand_m3'] ?? 0) > 0) $hasSand = true;
                if (($res['cat_packages'] ?? 0) > 0) $hasCat = true;
                if (($res['total_tiles'] ?? 0) > 0) $hasCeramic = true;
                if (($res['grout_packages'] ?? 0) > 0) $hasNat = true;

                $rekapEntry = [
                    'grand_total' => $item['result']['grand_total'] ?? null,
                    'filter_label' => $key,
                ];

                // Only add brick data if brick exists in project
                if (isset($project['brick'])) {
                    $rekapEntry['brick_id'] = $project['brick']->id;
                    $rekapEntry['brick_brand'] = $project['brick']->brand;
                    $rekapEntry['brick_detail'] = ($project['brick']->type ?? '-') . ' - ' .
                                    ($project['brick']->dimension_length + 0) . ' x ' .
                                    ($project['brick']->dimension_width + 0) . ' x ' .
                                    ($project['brick']->dimension_height + 0) . ' cm';
                }

                if (isset($item['cement'])) {
                    $rekapEntry['cement_id'] = $item['cement']->id;
                    $rekapEntry['cement_brand'] = $item['cement']->brand;
                    $rekapEntry['cement_detail'] = ($item['cement']->color ?? '-') . ' - ' . ($item['cement']->package_weight_net + 0) . ' Kg';
                }

                if (isset($item['sand'])) {
                    $rekapEntry['sand_id'] = $item['sand']->id;
                    $rekapEntry['sand_brand'] = $item['sand']->brand;
                    $rekapEntry['sand_detail'] = ($item['sand']->package_unit ?? '-') . ' - ' . (($item['sand']->package_volume ?? 0) > 0 ? (($item['sand']->package_volume + 0) . ' M3') : '-');
                }

                if (isset($item['cat'])) {
                    $cat = $item['cat'];
                    $rekapEntry['cat_id'] = $cat->id;
                    $rekapEntry['cat_brand'] = $cat->brand;
                    $catDetailParts = [];
                    $catDetailParts[] = ($cat->sub_brand ?? '-');
                    if (!empty($cat->color_code)) $catDetailParts[] = $cat->color_code;
                    if (!empty($cat->color_name)) $catDetailParts[] = $cat->color_name;
                    $catPackageUnitDisplay = trim((string)($cat->package_unit ?? ''));
                    if ($catPackageUnitDisplay === '') $catPackageUnitDisplay = '-';
                    $catGrossWeight = $cat->package_weight_gross ?? null;
                    $catGrossDisplay = ($catGrossWeight !== null && $catGrossWeight > 0)
                        ? \App\Helpers\NumberHelper::format($catGrossWeight)
                        : '-';
                    $catDetailParts[] = $catPackageUnitDisplay . ' ( ' . $catGrossDisplay . ' Kg )';
                    $catVolumeUnit = trim((string)($cat->volume_unit ?? 'L'));
                    if ($catVolumeUnit === '') $catVolumeUnit = 'L';
                    if (!empty($cat->volume)) {
                        $catDetailParts[] = '( ' . \App\Helpers\NumberHelper::format($cat->volume) . ' ' . $catVolumeUnit . ' )';
                    } else {
                        $catDetailParts[] = '( - ' . $catVolumeUnit . ' )';
                    }
                    $catDetailParts[] = 'BB: ' . \App\Helpers\NumberHelper::format($cat->package_weight_net + 0) . ' kg';
                    $rekapEntry['cat_detail'] = implode(' - ', $catDetailParts);
                }

                if (isset($item['ceramic'])) {
                    $rekapEntry['ceramic_id'] = $item['ceramic']->id;
                    $rekapEntry['ceramic_brand'] = $item['ceramic']->brand;
                    $rekapEntry['ceramic_detail'] = ($item['ceramic']->color ?? '-') . ' (' . ($item['ceramic']->dimension_length + 0) . 'x' . ($item['ceramic']->dimension_width + 0) . ')';
                }

                if (isset($item['nat'])) {
                    $rekapEntry['nat_id'] = $item['nat']->id;
                    $rekapEntry['nat_brand'] = $item['nat']->brand;
                    $rekapEntry['nat_detail'] = ($item['nat']->color ?? 'Nat') . ' (' . ($item['nat']->package_weight_net + 0) . ' kg)';
                }

                return $rekapEntry;
            };
            $buildPartialRekapEntry = function ($key, array $models) use (&$hasBrick, &$hasCement, &$hasSand, &$hasCat, &$hasCeramic, &$hasNat) {
                $entry = [
                    'grand_total' => null,
                    'filter_label' => $key,
                ];

                if (!empty($models['brick'])) {
                    $brick = $models['brick'];
                    $hasBrick = true;
                    $entry['brick_id'] = $brick->id;
                    $entry['brick_brand'] = $brick->brand;
                    $entry['brick_detail'] = ($brick->type ?? '-') . ' - ' .
                        ($brick->dimension_length + 0) . ' x ' .
                        ($brick->dimension_width + 0) . ' x ' .
                        ($brick->dimension_height + 0) . ' cm';
                }

                if (!empty($models['cement'])) {
                    $cement = $models['cement'];
                    $hasCement = true;
                    $entry['cement_id'] = $cement->id;
                    $entry['cement_brand'] = $cement->brand;
                    $entry['cement_detail'] = ($cement->color ?? '-') . ' - ' . ($cement->package_weight_net + 0) . ' Kg';
                }

                if (!empty($models['sand'])) {
                    $sand = $models['sand'];
                    $hasSand = true;
                    $entry['sand_id'] = $sand->id;
                    $entry['sand_brand'] = $sand->brand;
                    $entry['sand_detail'] = ($sand->package_unit ?? '-') . ' - ' . (($sand->package_volume ?? 0) > 0 ? (($sand->package_volume + 0) . ' M3') : '-');
                }

                if (!empty($models['cat'])) {
                    $cat = $models['cat'];
                    $hasCat = true;
                    $entry['cat_id'] = $cat->id;
                    $entry['cat_brand'] = $cat->brand;
                    $catDetailParts = [];
                    $catDetailParts[] = ($cat->sub_brand ?? '-');
                    if (!empty($cat->color_code)) $catDetailParts[] = $cat->color_code;
                    if (!empty($cat->color_name)) $catDetailParts[] = $cat->color_name;
                    $catPackageUnitDisplay = trim((string)($cat->package_unit ?? ''));
                    if ($catPackageUnitDisplay === '') $catPackageUnitDisplay = '-';
                    $catGrossWeight = $cat->package_weight_gross ?? null;
                    $catGrossDisplay = ($catGrossWeight !== null && $catGrossWeight > 0)
                        ? \App\Helpers\NumberHelper::format($catGrossWeight)
                        : '-';
                    $catDetailParts[] = $catPackageUnitDisplay . ' (' . $catGrossDisplay . ' Kg)';
                    $catVolumeUnit = trim((string)($cat->volume_unit ?? 'L'));
                    if ($catVolumeUnit === '') $catVolumeUnit = 'L';
                    if (!empty($cat->volume)) {
                        $catDetailParts[] = '(' . \App\Helpers\NumberHelper::format($cat->volume) . ' ' . $catVolumeUnit . ')';
                    } else {
                        $catDetailParts[] = '(- ' . $catVolumeUnit . ')';
                    }
                    $catDetailParts[] = 'BB: ' . \App\Helpers\NumberHelper::format($cat->package_weight_net + 0) . ' kg';
                    $entry['cat_detail'] = implode(' - ', $catDetailParts);
                }

                if (!empty($models['ceramic'])) {
                    $ceramic = $models['ceramic'];
                    $hasCeramic = true;
                    $entry['ceramic_id'] = $ceramic->id;
                    $entry['ceramic_brand'] = $ceramic->brand;
                    $entry['ceramic_detail'] = ($ceramic->color ?? '-') . ' (' . ($ceramic->dimension_length + 0) . 'x' . ($ceramic->dimension_width + 0) . ')';
                }

                if (!empty($models['nat'])) {
                    $nat = $models['nat'];
                    $hasNat = true;
                    $entry['nat_id'] = $nat->id;
                    $entry['nat_brand'] = $nat->brand;
                    $entry['nat_detail'] = ($nat->color ?? 'Nat') . ' (' . ($nat->package_weight_net + 0) . ' kg)';
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
                        if (!empty($item['cement'])) $fallbackMaterialIds['cement'][$item['cement']->id] = true;
                        if (!empty($item['sand'])) $fallbackMaterialIds['sand'][$item['sand']->id] = true;
                        if (!empty($item['cat'])) $fallbackMaterialIds['cat'][$item['cat']->id] = true;
                        if (!empty($item['ceramic'])) $fallbackMaterialIds['ceramic'][$item['ceramic']->id] = true;
                        if (!empty($item['nat'])) $fallbackMaterialIds['nat'][$item['nat']->id] = true;
                    }
                }
            }

            // Usage percentage helpers for rekap table (based on historical usage within current filtered materials)
            // Percentages are calculated per-brand to align with Populer ranking (unique brand per material type).
            $materialBrandUsage = [
                'brick' => [],
                'cement' => [],
                'sand' => [],
                'cat' => [],
                'ceramic' => [],
                'nat' => [],
            ];
            $materialIdToBrand = [
                'brick' => [],
                'cement' => [],
                'sand' => [],
                'cat' => [],
                'ceramic' => [],
                'nat' => [],
            ];

            foreach ($materialUsage as $type => $usageMap) {
                $ids = array_keys($usageMap);
                if (empty($ids) || empty($materialModelMap[$type])) {
                    continue;
                }
                $models = $materialModelMap[$type]::whereIn('id', $ids)->get(['id', 'brand'])->keyBy('id');
                foreach ($usageMap as $id => $count) {
                    $brand = $models->get($id)->brand ?? null;
                    if (!$brand) {
                        continue;
                    }
                    $materialIdToBrand[$type][$id] = $brand;
                    $materialBrandUsage[$type][$brand] = ($materialBrandUsage[$type][$brand] ?? 0) + (int) $count;
                }
            }

            $allowedBrandMap = [
                'brick' => [],
                'cement' => [],
                'sand' => [],
                'cat' => [],
                'ceramic' => [],
                'nat' => [],
            ];
            foreach ($fallbackMaterialIds as $type => $allowedIdsMap) {
                $allowedIds = array_keys($allowedIdsMap ?? []);
                if (empty($allowedIds) || empty($materialModelMap[$type])) {
                    continue;
                }
                $models = $materialModelMap[$type]::whereIn('id', $allowedIds)->get(['id', 'brand']);
                foreach ($models as $model) {
                    if (!empty($model->brand)) {
                        $allowedBrandMap[$type][$model->brand] = true;
                    }
                }
            }

            $usageTotals = [];
            foreach ($materialBrandUsage as $type => $brandUsage) {
                $allowedBrands = $allowedBrandMap[$type] ?? [];
                if (!empty($allowedBrands)) {
                    $total = 0;
                    foreach ($allowedBrands as $brand => $flag) {
                        $total += (int) ($brandUsage[$brand] ?? 0);
                    }
                    $usageTotals[$type] = $total;
                } else {
                    $usageTotals[$type] = array_sum($brandUsage);
                }
            }

            $formatUsagePercent = function (string $type, $id) use ($materialBrandUsage, $materialIdToBrand, $usageTotals, $allowedBrandMap) {
                if (empty($usageTotals[$type]) || empty($id)) {
                    return null;
                }
                $brand = $materialIdToBrand[$type][$id] ?? null;
                if (!$brand) {
                    return null;
                }
                $allowedBrands = $allowedBrandMap[$type] ?? [];
                if (!empty($allowedBrands) && !isset($allowedBrands[$brand])) {
                    return null;
                }
                $count = (int) ($materialBrandUsage[$type][$brand] ?? 0);
                if ($count <= 0) {
                    return null;
                }
                $percent = ($count / $usageTotals[$type]) * 100;
                return number_format($percent, 1);
            };

            $resolveRankedUniqueIds = function (string $materialType, array $usageMap, array $fallbackMap, string $modelClass, int $limit = 3) use ($workType): array {
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

            if (in_array('Populer', $filterCategories, true) && $hasHistoricalUsage) {
                $comboService = app(\App\Services\Calculation\CombinationGenerationService::class);
                $isBricklessWork = $isBrickless ?? false;
                $emptyEloquent = new \Illuminate\Database\Eloquent\Collection();

                $rankedIds = [
                    'brick' => in_array('brick', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('brick', $materialUsage['brick'], $fallbackMaterialIds['brick'], \App\Models\Brick::class)
                        : [],
                    'cement' => in_array('cement', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('cement', $materialUsage['cement'], $fallbackMaterialIds['cement'], \App\Models\Cement::class)
                        : [],
                    'sand' => in_array('sand', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('sand', $materialUsage['sand'], $fallbackMaterialIds['sand'], \App\Models\Sand::class)
                        : [],
                    'cat' => in_array('cat', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('cat', $materialUsage['cat'], $fallbackMaterialIds['cat'], \App\Models\Cat::class)
                        : [],
                    'ceramic' => in_array('ceramic', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('ceramic', $materialUsage['ceramic'], $fallbackMaterialIds['ceramic'], \App\Models\Ceramic::class)
                        : [],
                    'nat' => in_array('nat', $requiredMaterials, true)
                        ? $resolveRankedUniqueIds('nat', $materialUsage['nat'], $fallbackMaterialIds['nat'], \App\Models\Cement::class)
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
                            $models['nat'] = \App\Models\Cement::find($natId);
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
                        $cements = (in_array('cement', $requiredMaterials, true) && $cementId)
                            ? \App\Models\Cement::where('id', $cementId)->get()
                            : $emptyEloquent;
                        $sands = (in_array('sand', $requiredMaterials, true) && $sandId)
                            ? \App\Models\Sand::where('id', $sandId)->get()
                            : $emptyEloquent;
                        $cats = (in_array('cat', $requiredMaterials, true) && $catId)
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
                        $nats = (in_array('nat', $requiredMaterials, true) && $natId)
                            ? \App\Models\Cement::where('id', $natId)->get()
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
                                $isBricklessWork ? 0 : ($brick->id ?? 0),
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
                        $isBricklessWork ? 0 : ($partialEntry['brick_id'] ?? 0),
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
                        if ($matType === 'ceramic' && (($workType ?? '') === 'grout_tile') && !empty($fallbackCeramic)) {
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
                        $cements = !empty($models['cement']) ? new \Illuminate\Database\Eloquent\Collection([$models['cement']]) : $emptyEloquent;
                        $sands = !empty($models['sand']) ? new \Illuminate\Database\Eloquent\Collection([$models['sand']]) : $emptyEloquent;
                        $cats = !empty($models['cat']) ? new \Illuminate\Database\Eloquent\Collection([$models['cat']]) : $emptyEloquent;
                        $ceramics = !empty($models['ceramic']) ? new \Illuminate\Database\Eloquent\Collection([$models['ceramic']]) :
                                   ((($workType ?? '') === 'grout_tile' && $fallbackCeramic) ? new \Illuminate\Database\Eloquent\Collection([$fallbackCeramic]) : $emptyEloquent);
                        $nats = !empty($models['nat']) ? new \Illuminate\Database\Eloquent\Collection([$models['nat']]) : $emptyEloquent;

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
                    }
                }
            }

            if (!empty($populerRankedEntries)) {
                $rank = 0;
                foreach ($populerRankedEntries as $entry) {
                    $rank++;
                    $newKey = 'Populer ' . $rank;
                    if (!empty($entry['partial_entry'])) {
                        // Check if item has valid grand_total
                        $grandTotal = $entry['item']['result']['grand_total'] ?? null;
                        $rekapEntry = array_merge($entry['partial_entry'], ['filter_label' => $newKey]);

                        // If grand_total is still null, try to find it from existing combinations with same material signature
                        if ($grandTotal === null) {
                            // For grout_tile, ignore ceramic_id in signature (only dimensions matter, not ceramic selection)
                            $isGroutTile = ($workType ?? '') === 'grout_tile';
                            $searchSignature = implode('-', [
                                $isBricklessWork ? 0 : ($rekapEntry['brick_id'] ?? 0),
                                $rekapEntry['cement_id'] ?? 0,
                                $rekapEntry['sand_id'] ?? 0,
                                $rekapEntry['cat_id'] ?? 0,
                                $isGroutTile ? 0 : ($rekapEntry['ceramic_id'] ?? 0),
                                $rekapEntry['nat_id'] ?? 0,
                            ]);

                            // Search in all projects combinations for matching material signature
                            foreach ($projects as $searchProject) {
                                foreach ($searchProject['combinations'] as $searchLabel => $searchItems) {
                                    foreach ($searchItems as $searchItem) {
                                        $itemSignature = implode('-', [
                                            $isBricklessWork ? 0 : ($searchProject['brick']->id ?? 0),
                                            $searchItem['cement']->id ?? 0,
                                            $searchItem['sand']->id ?? 0,
                                            $searchItem['cat']->id ?? 0,
                                            $isGroutTile ? 0 : ($searchItem['ceramic']->id ?? 0),
                                            $searchItem['nat']->id ?? 0,
                                        ]);

                                        if ($itemSignature === $searchSignature && isset($searchItem['result']['grand_total'])) {
                                            $grandTotal = $searchItem['result']['grand_total'];
                                            break 3; // Break out of all three loops
                                        }
                                    }
                                }
                            }
                        }

                        if ($grandTotal !== null) {
                            $rekapEntry['grand_total'] = $grandTotal;
                        }
                        $globalRekapData[$newKey] = $rekapEntry;
                    } else {
                        $project = $entry['project'];
                        $item = $entry['item'];
                        $globalRekapData[$newKey] = $buildRekapEntry($project, $item, $newKey);
                    }
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
                                'grand_total' => (float)($item['result']['grand_total'] ?? 0),
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
                        }
                    }

                    if (in_array('Termahal', $filterCategories, true)) {
                        for ($i = 0; $i < $TermahalCount; $i++) {
                            $rank = $i + 1;
                            $candidateIndex = $totalCandidates - 1 - $i;
                            $key = 'Termahal ' . $rank;
                            $combo = $allPriceCandidates[$candidateIndex];
                            $globalRekapData[$key] = $buildRekapEntry($combo['project'], $combo['item'], $key);
                        }
                    }

                    if (in_array('Average', $filterCategories, true)) {
                        $sumTotal = array_sum(array_map(fn ($row) => $row['grand_total'], $allPriceCandidates));
                        $averageTotal = $totalCandidates > 0 ? ($sumTotal / $totalCandidates) : 0;
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
                            $lastPrice = $combo['grand_total'];
                            $averageRank = 2;

                            for ($i = $closestIndex + 1; $i < $totalCandidates && $averageRank <= 3; $i++) {
                                $candidatePrice = $allPriceCandidates[$i]['grand_total'];
                                if ($candidatePrice <= $lastPrice) {
                                    continue;
                                }
                                $key = 'Average ' . $averageRank;
                                $globalRekapData[$key] = $buildRekapEntry($allPriceCandidates[$i]['project'], $allPriceCandidates[$i]['item'], $key);
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
            $availableColors = array_merge($brickColors, $cementColors, $sandColors, $catColors, $ceramicColors, $natColors);

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
                // Extract filter type from key (e.g., "Rekomendasi 1" -> "Rekomendasi")
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
                                    if (isset($globalRekapData[$key]) &&
                                        !empty($globalRekapData[$key]['brick_id']) &&
                                        isset($p['brick']) &&
                                        $p['brick'] &&
                                        $p['brick']->id === $globalRekapData[$key]['brick_id']) {
                                        $project = $p;
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($project) {
                            // Create signature based on complete brick data (WITHOUT filterType)
                            $brick = $project['brick'];
                            $dataSignature = $brick->brand . '-' .
                                           $brick->type . '-' .
                                           $brick->dimension_length . '-' .
                                           $brick->dimension_width . '-' .
                                           $brick->dimension_height . '-' .
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
                                    if (isset($globalRekapData[$key]) && 
                                        isset($globalRekapData[$key]['cement_id']) &&
                                        isset($item['cement']) &&
                                        $item['cement']->id === $globalRekapData[$key]['cement_id']) {
                                        $cement = $item['cement'];
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($cement) {
                            // Create signature based on complete cement data (WITHOUT filterType)
                            $dataSignature = $cement->brand . '-' .
                                           ($cement->color ?? '-') . '-' .
                                           $cement->package_weight_net . '-' .
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
                                    if (isset($globalRekapData[$key]) && 
                                        isset($globalRekapData[$key]['sand_id']) &&
                                        isset($item['sand']) &&
                                        $item['sand']->id === $globalRekapData[$key]['sand_id']) {
                                        $sand = $item['sand'];
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($sand) {
                            // Create signature based on complete sand data (WITHOUT filterType)
                            $dataSignature = $sand->brand . '-' .
                                           ($sand->package_unit ?? '-') . '-' .
                                           ($sand->package_volume ?? '0') . '-' .
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

        @if(count($rekapCategories) > 0)
        <div class="container mb-4">
            @php
                $isRollag = isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag';
                if (!isset($area)) {
                    $area = $isRollag ? 0 : (($requestData['wall_length'] ?? 0) * ($requestData['wall_height'] ?? 0));
                }
            @endphp
            @php
                $heightLabel = in_array($requestData['work_type'] ?? '', ['tile_installation', 'grout_tile', 'floor_screed', 'coating_floor'], true) ? 'LEBAR' : 'TINGGI';
            @endphp
            <div class="card p-3 shadow-sm border-0 preview-params-sticky" style="background-color: #fdfdfd; border-radius: 12px;">
                <div class="d-flex flex-wrap align-items-end gap-3 justify-content-start preview-param-row">
                    {{-- ===== GRUP UTAMA: Item Pekerjaan + Dimensi ===== --}}

                    {{-- Jenis Item Pekerjaan --}}
                    <div style="flex: 1; min-width: 250px;">
                        <label class="fw-bold mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="bi bi-briefcase me-1"></i>Jenis Item Pekerjaan
                        </label>
                        <div class="form-control fw-bold border-secondary text-dark" style="background-color: #e9ecef; opacity: 1;">
                            {{ $formulaName }}
                        </div>
                    </div>

                    {{-- Panjang --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">PANJANG</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">@format($requestData['wall_length'])</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>

                    @if(!$isRollag)
                    {{-- Tinggi/Lebar --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">{{ $heightLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">@format($requestData['wall_height'])</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>

                    {{-- Luas --}}
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center bg-white text-danger px-1" style="border-color: #dc3545;">@format($area)</div>
                            <span class="input-group-text bg-danger text-white small px-1" style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                        </div>
                    </div>
                    @endif

                    {{-- ===== SEPARATOR / GAP ===== --}}
                    <div style="flex: 0 0 auto; width: 10px;"></div>

                    {{-- ===== GRUP TAMBAHAN: Parameter Lainnya ===== --}}

                    {{-- Tebal Spesi (tidak untuk Pasang Nat atau Pengecatan) --}}
                    @if(!isset($requestData['work_type']) || (!in_array($requestData['work_type'], ['grout_tile', 'painting', 'wall_painting'])))
                    <div style="flex: 0 0 auto; width: 100px;">
                        @php
                            // Logic simplified: this block is now only for mortar thickness
                            $paramLabel = 'TEBAL ADUKAN';
                            $paramUnit = 'cm';
                            $paramValue = $requestData['mortar_thickness'] ?? 2.0;
                            $badgeClass = 'bg-light';
                        @endphp
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge {{ $badgeClass }} border">{{ $paramLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ $paramValue }}</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Tingkat (hanya untuk Rollag) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-warning border">TINGKAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fffbeb; border-color: #fcd34d;">{{ $requestData['layer_count'] ?? 1 }}</div>
                            <span class="input-group-text bg-warning small px-1" style="font-size: 0.7rem;">Lapis</span>
                        </div>
                    </div>
                    @endif

                    {{-- Sisi Aci (hanya untuk Aci Dinding) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'skim_coating')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-info text-white border">SISI ACI</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e0f2fe; border-color: #38bdf8;">{{ $requestData['skim_sides'] ?? 1 }}</div>
                            <span class="input-group-text bg-info text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                        </div>
                    </div>
                    @endif

                    {{-- Sisi Plester (hanya untuk Plester Dinding) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'wall_plastering')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-success text-white border">SISI PLESTER</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #d1fae5; border-color: #34d399;">{{ $requestData['plaster_sides'] ?? 1 }}</div>
                            <span class="input-group-text bg-success text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                        </div>
                    </div>
                    @endif

                    {{-- Lapis Pengecatan --}}
                    @if(isset($requestData['work_type']) && ($requestData['work_type'] === 'wall_painting' || $requestData['work_type'] === 'painting'))
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-primary text-white border border-primary">LAPIS CAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #dbeafe; border-color: #3b82f6;">{{ $requestData['layer_count'] ?? $requestData['paint_layers'] ?? $requestData['painting_layers'] ?? 1 }}</div>
                            <span class="input-group-text bg-primary text-white small px-1" style="font-size: 0.7rem;">Lapisan</span>
                        </div>
                    </div>
                    @endif

                    {{-- Tebal Nat (untuk Pasang Keramik dan Pasang Nat) --}}
                    @if(isset($requestData['work_type']) && ($requestData['work_type'] === 'tile_installation' || $requestData['work_type'] === 'grout_tile'))
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-info text-white border">TEBAL NAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e0f2fe; border-color: #38bdf8;">{{ $requestData['grout_thickness'] ?? 3 }}</div>
                            <span class="input-group-text bg-info text-white small px-1" style="font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Panjang Keramik (untuk Pasang Nat saja) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'grout_tile' && isset($requestData['ceramic_length']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">P. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_length'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Lebar Keramik (untuk Pasang Nat saja) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'grout_tile' && isset($requestData['ceramic_width']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">L. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_width'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">cm</span>
                        </div>
                    </div>
                    @endif

                    {{-- Tebal Keramik (untuk Pasang Nat saja) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'grout_tile' && isset($requestData['ceramic_thickness']))
                    <div style="flex: 0 0 auto; width: 110px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge text-white border" style="background-color: #f59e0b;">T. KERAMIK</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fef3c7; border-color: #fde047;">{{ $requestData['ceramic_thickness'] }}</div>
                            <span class="input-group-text text-white small px-1" style="background-color: #f59e0b; font-size: 0.7rem;">mm</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
            <div class="card rekap-card" style="background: #ffffff; padding: 0; border-radius: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: visible;">
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
                    <table class="table-preview table-rekap-global" data-rekap-table="true" style="margin: 0;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 0; z-index: 3; width: 80px; min-width: 80px;">Rekap</th>
                                <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 80px; z-index: 3; width: 120px; min-width: 120px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.3);">Grand Total</th>
                                @if($hasBrick)
                                <th colspan="2" style="background: #891313; color: white;">Bata</th>
                                @endif
                                @if($hasCement)
                                <th colspan="2" style="background: #891313; color: white;">Semen</th>
                                @endif
                                @if($hasSand)
                                <th colspan="2" style="background: #891313; color: white;">Pasir</th>
                                @endif
                                @if($hasCat)
                                <th colspan="2" style="background: #891313; color: white;">Cat</th>
                                @endif
                                @if($hasCeramic)
                                <th colspan="2" style="background: #891313; color: white;">Keramik</th>
                                @endif
                                @if($hasNat)
                                <th colspan="2" style="background: #891313; color: white;">Nat</th>
                                @endif
                            </tr>
                            <tr>
                                @if($hasBrick)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasCement)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasSand)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasCat)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasCeramic)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasNat)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rekapCategories as $filterType)
                                @foreach($getDisplayKeys($filterType) as $displayIndex => $key)
                                    @php
                                        $rank = $displayIndex + 1;
                                        $bgColor = $globalColorMap[$key] ?? '#ffffff';
                                        $grandTotalBg = ($bgColor && strtolower($bgColor) !== '#ffffff') ? $bgColor : null;
                                        $brickBgColor = $brickColorMap[$key] ?? '#ffffff';
                                        $cementBgColor = $cementColorMap[$key] ?? '#ffffff';
                                        $sandBgColor = $sandColorMap[$key] ?? '#ffffff';
                                        $catBgColor = $catColorMap[$key] ?? '#ffffff';
                                        $natBgColor = $natColorMap[$key] ?? '#ffffff';
                                        $ceramicBgColor = $ceramicColorMap[$key] ?? '#ffffff';
                                        $isPopulerRow = $filterType === 'Populer';
                                        $brickPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('brick', $globalRekapData[$key]['brick_id'] ?? null) : null;
                                        $cementPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('cement', $globalRekapData[$key]['cement_id'] ?? null) : null;
                                        $sandPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('sand', $globalRekapData[$key]['sand_id'] ?? null) : null;
                                        $catPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('cat', $globalRekapData[$key]['cat_id'] ?? null) : null;
                                        $ceramicPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('ceramic', $globalRekapData[$key]['ceramic_id'] ?? null) : null;
                                        $natPercent = isset($globalRekapData[$key]) ? $formatUsagePercent('nat', $globalRekapData[$key]['nat_id'] ?? null) : null;

                                        // Get label color untuk kolom Rekap
                                        $labelColor = $rekapLabelColors[$filterType][$rank] ?? ['bg' => '#ffffff', 'text' => '#000000'];
                                    @endphp
                                    <tr>
                                        {{-- Column 1: Filter Label --}}
                                        <td style="font-weight: 700; position: sticky; left: 0; z-index: 2; background: {{ $labelColor['bg'] }}; color: {{ $labelColor['text'] }}; padding: 4px 8px; vertical-align: middle; width: 80px; min-width: 80px;">
                                            <a href="#detail-{{ strtolower(str_replace(' ', '-', $key)) }}" style="color: inherit; text-decoration: none; display: block; cursor: pointer;">
                                                {{ $key }}
                                            </a>
                                        </td>

                                        {{-- Column 2: Grand Total --}}
                                        <td class="text-end fw-bold" style="position: sticky; left: 80px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1); {{ $grandTotalBg ? 'background: ' . $grandTotalBg . ';' : '' }} padding: 4px 8px; vertical-align: middle; width: 120px; min-width: 120px;">
                                        @if(isset($globalRekapData[$key]))
                                            <div class="d-flex justify-content-between w-100">
                                                @if(isset($globalRekapData[$key]['grand_total']) && $globalRekapData[$key]['grand_total'] !== null)
                                                    <span>Rp</span>
                                                    <span>@price($globalRekapData[$key]['grand_total'])</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        </td>

                                        {{-- Column 3: Merek Bata --}}
                                        @if($hasBrick)
                                        <td style="background: {{ $brickBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                <div title="Grand Total: @currency($globalRekapData[$key]['grand_total'])">
                                                    {{ $globalRekapData[$key]['brick_brand'] ?? '-' }}
                                                @if($isPopulerRow && $brickPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $brickPercent }}%</span>
                                                @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 4: Detail Bata --}}
                                        <td class="text-muted small" style="background: {{ $brickBgColor }}; vertical-align: middle; border-right: 2px solid #891313;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['brick_detail'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 5: Merek Semen --}}
                                        @if($hasCement)
                                        <td style="background: {{ $cementBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['cement_brand'] ?? '-' }}
                                                @if($isPopulerRow && $cementPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $cementPercent }}%</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 6: Detail Semen --}}
                                        <td class="text-muted small" style="background: {{ $cementBgColor }}; vertical-align: middle; border-right: 2px solid #891313;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['cement_detail'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 7: Merek Pasir --}}
                                        @if($hasSand)
                                        <td style="background: {{ $sandBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['sand_brand'] ?? '-' }}
                                                @if($isPopulerRow && $sandPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $sandPercent }}%</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 8: Detail Pasir --}}
                                        <td class="text-muted small" style="background: {{ $sandBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['sand_brand']))
                                                {{ $globalRekapData[$key]['sand_detail'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 9: Merek Cat --}}
                                        @if($hasCat)
                                        <td style="background: {{ $catBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_brand']))
                                                {{ $globalRekapData[$key]['cat_brand'] ?? '-' }}
                                                @if($isPopulerRow && $catPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $catPercent }}%</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 10: Detail Cat --}}
                                        <td class="text-muted small" style="background: {{ $catBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_detail']))
                                                {{ $globalRekapData[$key]['cat_detail'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                        
                                        {{-- Column 11: Merek Keramik --}}
                                        @if($hasCeramic)
                                        <td style="background: {{ $ceramicBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['ceramic_brand']))
                                                {{ $globalRekapData[$key]['ceramic_brand'] ?? '-' }}
                                                @if($isPopulerRow && $ceramicPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $ceramicPercent }}%</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        
                                        {{-- Column 12: Detail Keramik --}}
                                        <td class="text-muted small" style="background: {{ $ceramicBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['ceramic_detail']))
                                                {{ $globalRekapData[$key]['ceramic_detail'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                        
                                        {{-- Column 13: Merek Nat --}}
                                        @if($hasNat)
                                        <td style="background: {{ $natBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['nat_brand']))
                                                {{ $globalRekapData[$key]['nat_brand'] ?? '-' }}
                                                @if($isPopulerRow && $natPercent)
                                                    <span class="badge rounded-pill text-bg-primary" style="font-size: 0.7rem;">Populer {{ $natPercent }}%</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 14: Detail Nat --}}
                                        <td class="text-muted small" style="background: {{ $natBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['nat_detail']))
                                                {{ $globalRekapData[$key]['nat_detail'] ?? '-' }}
                                            @else
                                                -
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
        </div>
        @endif

        {{-- SINGLE TABLE FOR ALL COMBINATIONS --}}
        <div class="container">
            <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden; position: relative; z-index: 1;">
                <div class="table-responsive detail-table-wrap">
                                <style>
                                    /* Global Text Styling */
                                    .table-preview th,
                                    .table-preview td,
                                    .table-preview span,
                                    .table-preview div,
                                    .table-preview a,
                                    .table-preview label,
                                    .table-preview button {
                                        font-family: 'Nunito', sans-serif !important;
                                        color: #000000 !important;
                                        font-weight: 700 !important;
                                    }

                                    /* Table Styling */
                                    .table-preview {
                                        width: 100%;
                                        border-collapse: separate;
                                        border-spacing: 0;
                                        font-size: 13px;
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
                                    .table-preview td.preview-scroll-td {
                                        position: relative;
                                        overflow: hidden;
                                        white-space: nowrap;
                                        text-align: left;
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
                                        min-width: 90px;
                                        max-width: 105px;
                                        width: 90px;
                                    }
                                    .sticky-col-2 {
                                        position: sticky;
                                        left: 105px;
                                        background-color: white;
                                        z-index: 2;
                                        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                                        min-width: 80px;
                                    }
                                    .sticky-col-3 {
                                        position: sticky;
                                        left: 200px;
                                        background-color: white;
                                        z-index: 2;
                                        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                                        min-width: 100px;
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
                                </style>

                                <table class="table-preview">
                                    <thead class="align-top">
                                        <tr>
                                            <th class="sticky-col-1">Qty<br>/ Pekerjaan</th>
                                            <th class="sticky-col-2">Satuan</th>
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

                                            foreach ($filterCategories as $filterType) {
                                                foreach ($getDisplayKeys($filterType) as $key) {

                                                    // Check if this filter exists in global recap
                                                    if (isset($globalRekapData[$key])) {
                                                        $rekapData = $globalRekapData[$key];

                                                        // Search through ALL projects to find the matching combination
                                                        foreach ($projects as $project) {
                                                            // Check if this project uses the brick from recap
                                                            if (!empty($rekapData['brick_id']) &&
                                                                isset($project['brick']) &&
                                                                $project['brick'] &&
                                                                $rekapData['brick_id'] === $project['brick']->id) {
                                                                // Find the matching combination in this project
                                                                foreach ($project['combinations'] as $label => $items) {
                                                                    foreach ($items as $item) {
                                                                        $match = false;
                                                                        if (isset($rekapData['cat_id']) && isset($item['cat'])) {
                                                                            // Match by Cat ID (for painting)
                                                                            if ($item['cat']->id === $rekapData['cat_id']) {
                                                                                $match = true;
                                                                            }
                                                                        } elseif (isset($rekapData['ceramic_id']) && isset($rekapData['nat_id']) && isset($item['ceramic']) && isset($item['nat'])) {
                                                                            // Match by Ceramic & Nat ID (for tile_installation and grout_tile)
                                                                            if ($item['ceramic']->id === $rekapData['ceramic_id'] && $item['nat']->id === $rekapData['nat_id']) {
                                                                                $match = true;
                                                                            }
                                                                        } elseif (isset($rekapData['cement_id']) && isset($item['cement'])) {
                                                                            // Match by Cement (and Sand if applicable)
                                                                            $rekapSandId = $rekapData['sand_id'] ?? null;
                                                                            $itemSandId = isset($item['sand']) ? $item['sand']->id : null;

                                                                            if ($item['cement']->id === $rekapData['cement_id'] && $rekapSandId === $itemSandId) {
                                                                                $match = true;
                                                                            }
                                                                        }

                                                                        if ($match) {
                                                                            $allFilteredCombinations[] = [
                                                                                'label' => $key, // Use recap label
                                                                                'item' => $item,
                                                                                'brick' => $project['brick']
                                                                            ];
                                                                            break 3; // Found it, move to next filter
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @foreach($allFilteredCombinations as $combo)
                                            @php
                                                $globalIndex++;
                                                $label = $combo['label'];
                                                $item = $combo['item'];
                                                $brick = $combo['brick'];
                                                $res = $item['result'];
                                                $isFirstOption = ($globalIndex === 1);
                                                $areaForCost = $area;
                                                if ($isRollag) {
                                                    $wallLength = (float)($requestData['wall_length'] ?? 0);
                                                    $brickLength = (float)($brick->dimension_length ?? 0);
                                                    if ($brickLength <= 0) {
                                                        $brickLength = 19.2;
                                                    }
                                                    $areaForCost = ($wallLength > 0 && $brickLength > 0)
                                                        ? $wallLength * ($brickLength / 100)
                                                        : 0;
                                                }
                                                // Normalize areaForCost karena non-rupiah (M2), normalize hasil pembagian
                                                $normalizedArea = \App\Helpers\NumberHelper::normalize($areaForCost);
                                                $costPerM2 = $normalizedArea > 0
                                                    ? \App\Helpers\NumberHelper::normalize($res['grand_total'] / $normalizedArea)
                                                    : 0;
                                                $brickVolume = 0;
                                                if ($brick && $brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
                                                    $brickVolume = ($brick->dimension_length * $brick->dimension_width * $brick->dimension_height) / 1000000;
                                                }
                                                if ($brickVolume <= 0) {
                                                    $brickVolume = $brick->package_volume ?? 0;
                                                }
                                                $brickVolumeDisplay = $brickVolume > 0 ? $brickVolume : null;
                                                if ($brickVolume <= 0) {
                                                    $brickVolume = 1;
                                                }
                                                $cementWeight = isset($item['cement']) ? ($item['cement']->package_weight_net ?? 0) : 0;
                                                if ($cementWeight <= 0) {
                                                    $cementWeight = 1;
                                                }
                                                $catWeight = isset($item['cat']) ? ($item['cat']->package_weight_net ?? 0) : 0;
                                                if ($catWeight <= 0) {
                                                    $catWeight = 1;
                                                }
                                                $ceramicArea = 0;
                                                if (isset($item['ceramic']) && $item['ceramic']->dimension_length && $item['ceramic']->dimension_width) {
                                                    $ceramicArea = ($item['ceramic']->dimension_length / 100) * ($item['ceramic']->dimension_width / 100);
                                                }
                                                if ($ceramicArea <= 0) {
                                                    $ceramicArea = 1;
                                                }
                                                $natWeight = isset($item['nat']) ? ($item['nat']->package_weight_net ?? 0) : 0;
                                                if ($natWeight <= 0) {
                                                    $natWeight = 1;
                                                }

                                                $brickPricePerPiece = $res['brick_price_per_piece'] ?? ($brick->price_per_piece ?? 0);
                                                $cementPricePerSak = $res['cement_price_per_sak'] ?? (isset($item['cement']) ? ($item['cement']->package_price ?? 0) : 0);
                                                $catPricePerPackage = $res['cat_price_per_package'] ?? (isset($item['cat']) ? ($item['cat']->purchase_price ?? 0) : 0);
                                                $ceramicPricePerPackage = $res['ceramic_price_per_package'] ?? (isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0);
                                                $groutPricePerPackage = $res['grout_price_per_package'] ?? (isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0);

                                                $sandPricePerM3 = $res['sand_price_per_m3'] ?? 0;
                                                if ($sandPricePerM3 <= 0 && isset($item['sand'])) {
                                                    $sandPricePerM3 = $item['sand']->comparison_price_per_m3 ?? 0;
                                                    if ($sandPricePerM3 <= 0 && ($item['sand']->package_price ?? 0) > 0 && ($item['sand']->package_volume ?? 0) > 0) {
                                                        $sandPricePerM3 = $item['sand']->package_price / $item['sand']->package_volume;
                                                    }
                                                }

                                                $tilesPerPackage = $res['tiles_per_package'] ?? (isset($item['ceramic']) ? ($item['ceramic']->pieces_per_package ?? 0) : 0);
                                                $tilesPackages = $res['tiles_packages'] ?? (($tilesPerPackage > 0) ? ceil(($res['total_tiles'] ?? 0) / $tilesPerPackage) : 0);

                                                // Helper function: format number without trailing zeros
                                                $formatNum = function($num, $decimals = null) {
                                                    return \App\Helpers\NumberHelper::format($num);
                                                };
                                                $formatMoney = function($num) {
                                                    return \App\Helpers\NumberHelper::format($num, 0);
                                                };
                                                $formatRaw = function($num, $decimals = 6) {
                                                    return \App\Helpers\NumberHelper::format($num, $decimals);
                                                };
                                                $catDetailDisplayParts = [];
                                                $catDetailExtraParts = [];
                                                $catSubBrand = isset($item['cat']) ? trim((string)($item['cat']->sub_brand ?? '')) : '';
                                                $catCode = isset($item['cat']) ? trim((string)($item['cat']->color_code ?? '')) : '';
                                                $catColor = isset($item['cat']) ? trim((string)($item['cat']->color_name ?? '')) : '';
                                                if ($catSubBrand !== '') $catDetailDisplayParts[] = $catSubBrand;
                                                if ($catCode !== '') $catDetailDisplayParts[] = $catCode;
                                                if ($catColor !== '') $catDetailDisplayParts[] = $catColor;
                                                $catDetailDisplay = !empty($catDetailDisplayParts) ? implode(' - ', $catDetailDisplayParts) : '-';

                                                $catPackageUnit = isset($item['cat']) ? trim((string)($item['cat']->package_unit ?? '')) : '';
                                                $catVolume = isset($item['cat']) ? ($item['cat']->volume ?? null) : null;
                                                $catVolumeUnit = isset($item['cat']) ? trim((string)($item['cat']->volume_unit ?? 'L')) : 'L';
                                                if ($catVolumeUnit === '') $catVolumeUnit = 'L';
                                                $catPackageUnitDisplay = $catPackageUnit !== '' ? $catPackageUnit : '-';
                                                $catGrossWeight = isset($item['cat']) ? ($item['cat']->package_weight_gross ?? null) : null;
                                                $catGrossDisplay = ($catGrossWeight !== null && $catGrossWeight > 0)
                                                    ? $formatNum($catGrossWeight)
                                                    : '-';
                                                $catDetailExtraParts[] = $catPackageUnitDisplay . ' ( ' . $catGrossDisplay . ' Kg )';
                                                if (!empty($catVolume) && $catVolume > 0) {
                                                    $catDetailExtraParts[] = '( ' . $formatNum($catVolume) . ' ' . $catVolumeUnit . ' )';
                                                } else {
                                                    $catDetailExtraParts[] = '( - ' . $catVolumeUnit . ' )';
                                                }
                                                if (isset($item['cat']) && ($item['cat']->package_weight_net ?? null) !== null) {
                                                    $catDetailExtraParts[] = 'BB ' . $formatNum($item['cat']->package_weight_net) . ' Kg';
                                                }
                                                $catDetailExtra = !empty($catDetailExtraParts) ? implode(' - ', $catDetailExtraParts) : '-';

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
                                                        'detail_value_debug' => 'Rumus: (' . $formatNum($brick->dimension_length) . ' x ' . $formatNum($brick->dimension_width) . ' x ' . $formatNum($brick->dimension_height) . ') / 1.000.000 = ' . $formatNum($brickVolume) . ' M3',
                                                        'object' => $brick,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => $formatNum($brick->dimension_length) . ' x ' . $formatNum($brick->dimension_width) . ' x ' . $formatNum($brick->dimension_height) . ' cm',
                                                        'detail_extra' => $brickVolumeDisplay ? ($formatNum($brickVolumeDisplay) . ' M3') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => $brick->price_per_piece ?? 0,
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
                                                        'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($cementWeight) . ' Kg',
                                                        'object' => $item['cement'] ?? null,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => isset($item['cement']) ? ($item['cement']->color ?? '-') : '-',
                                                        'detail_extra' => isset($item['cement']) ? ($formatNum($item['cement']->package_weight_net) . ' Kg') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => isset($item['cement']) ? ($item['cement']->package_price ?? 0) : 0,
                                                        'package_unit' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                                        'price_per_unit' => $cementPricePerSak,
                                                        'price_unit_label' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                                        'price_calc_qty' => $res['cement_sak'] ?? 0,
                                                        'price_calc_unit' => 'Sak',
                                                        'total_price' => $res['total_cement_price'] ?? 0,
                                                        'unit_price' => $cementPricePerSak,
                                                        'unit_price_label' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                                    ],
                                                    'sand' => [
                                                        'name' => 'Pasir',
                                                        'check_field' => 'sand_m3',
                                                        'qty' => $res['sand_m3'] ?? 0,
                                                        'qty_debug' => 'Kebutuhan pasir untuk area ' . $formatNum($areaForCost) . ' M2',
                                                        'unit' => 'M3',
                                                        'comparison_unit' => 'M3',
                                                        'detail_value' => isset($item['sand']) && $item['sand']->package_volume > 0 ? $item['sand']->package_volume : 1,
                                                        'detail_value_debug' => isset($item['sand']) ? ('Volume per kemasan: ' . $formatNum($item['sand']->package_volume ?? 0) . ' M3') : '-',
                                                        'object' => $item['sand'] ?? null,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => isset($item['sand']) ? ($item['sand']->package_unit ?? '-') : '-',
                                                        'detail_extra' => isset($item['sand']) ? ($item['sand']->package_volume ? ($formatNum($item['sand']->package_volume) . ' M3') : '-') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => isset($item['sand']) ? ($item['sand']->package_price ?? 0) : 0,
                                                        'package_unit' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                                        'price_per_unit' => $sandPricePerM3,
                                                        'price_unit_label' => 'M3',
                                                        'price_calc_qty' => $res['sand_m3'] ?? 0,
                                                        'price_calc_unit' => 'M3',
                                                        'total_price' => $res['total_sand_price'] ?? 0,
                                                        'unit_price' => $sandPricePerM3,
                                                        'unit_price_label' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                                    ],
                                                    'cat' => [
                                                        'name' => 'Cat',
                                                        'check_field' => 'cat_packages',
                                                        'qty' => $res['cat_packages'] ?? 0,
                                                        'qty_debug' => 'Kebutuhan cat untuk area ' . $formatNum($areaForCost) . ' M2',
                                                        'unit' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Kmsn') : 'Kmsn',
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
                                                        'package_price' => isset($item['cat']) ? ($item['cat']->purchase_price ?? 0) : 0,
                                                        'package_unit' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                        'price_per_unit' => $catPricePerPackage,
                                                        'price_unit_label' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                        'price_calc_qty' => $res['cat_packages'] ?? 0,
                                                        'price_calc_unit' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                        'total_price' => $res['total_cat_price'] ?? 0,
                                                        'unit_price' => $catPricePerPackage,
                                                        'unit_price_label' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                    ],
                                                    'ceramic' => [
                                                        'name' => 'Keramik',
                                                        'check_field' => 'total_tiles',
                                                        'qty' => $res['total_tiles'] ?? 0,
                                                        'qty_debug' => 'Kebutuhan keramik untuk area ' . $formatNum($areaForCost) . ' M2',
                                                        'unit' => 'Bh',
                                                        'comparison_unit' => 'M2',
                                                        'detail_value' => $ceramicArea,
                                                        'detail_value_debug' => isset($item['ceramic']) ? ('Rumus: (' . $formatNum($item['ceramic']->dimension_length) . '/100) x (' . $formatNum($item['ceramic']->dimension_width) . '/100) = ' . $formatNum($ceramicArea) . ' M2') : '-',
                                                        'object' => $item['ceramic'] ?? null,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => isset($item['ceramic']) ? ($item['ceramic']->color ?? '-') : '-',
                                                        'detail_extra' => isset($item['ceramic']) ? ($formatNum($item['ceramic']->dimension_length) . 'x' . $formatNum($item['ceramic']->dimension_width) . ' cm') : '-',
                                                        'detail_extra_debug' => isset($item['ceramic']) ? ('Luas: ' . $formatNum($ceramicArea) . ' M2 per keping') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0,
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
                                                        'detail_display' => isset($item['nat']) ? ($item['nat']->color ?? 'Nat') : 'Nat',
                                                        'detail_extra' => isset($item['nat']) ? ($formatNum($item['nat']->package_weight_net) . ' Kg') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0,
                                                        'package_unit' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                                        'price_per_unit' => $groutPricePerPackage,
                                                        'price_unit_label' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                                        'price_calc_qty' => $res['grout_packages'] ?? 0,
                                                        'price_calc_unit' => 'Bks',
                                                        'total_price' => $res['total_grout_price'] ?? 0,
                                                        'unit_price' => $groutPricePerPackage,
                                                        'unit_price_label' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                                    ],
                                                    'water' => [
                                                        'name' => 'Air',
                                                        'check_field' => 'total_water_liters',
                                                        'qty' => $res['total_water_liters'] ?? ($res['water_liters'] ?? 0),
                                                        'qty_debug' => ($res['water_liters_debug'] ?? '') ?: 'Kebutuhan air untuk area ' . $formatNum($areaForCost) . ' M2',
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

                                                // Filter materials: only show if qty > 0
                                                $visibleMaterials = array_filter($materialConfig, function($mat) {
                                                    return isset($mat['qty']) && $mat['qty'] > 0;
                                                });

                                                // Calculate rowspan based on visible materials
                                                $rowCount = count($visibleMaterials);
                                            @endphp

                                                {{-- ROW 0: GROUP NAME / LABEL --}}
                                                <tr class="{{ $isFirstOption ? '' : 'group-divider' }}" id="detail-{{ strtolower(str_replace(' ', '-', $label)) }}">
                                                    <td colspan="3" class="text-start align-middle sticky-label-row sticky-col-label" style="background: #f8fafc; padding: 10px 16px; font-weight: 600;">
                                                        @php
                                                            // Definisi warna dengan 3 level gradasi (1=gelap, 2=sedang, 3=cerah)
                                                            $labelColors = [
                                                                'Semua' => [
                                                                    1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                                                                    2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                                                                    3 => ['bg' => '#ffffff', 'border' => '#e2e8f0', 'text' => '#64748b'],
                                                                ],
                                                                'Rekomendasi' => [
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
                                                        <div style="display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                                            <span style="color: #891313; font-weight: 700; font-size: 11px;">
                                                                #{{ $globalIndex }}
                                                            </span>
                                                            @foreach($labelParts as $index => $singleLabel)
                                                                @php
                                                                    // Extract prefix dari label (sebelum angka)
                                                                    $labelPrefix = preg_replace('/\s+\d+.*$/', '', $singleLabel);
                                                                    $labelPrefix = trim($labelPrefix);

                                                                    // Extract nomor dari label (contoh: "Rekomendasi 1" -> 1)
                                                                    preg_match('/\s+(\d+)/', $singleLabel, $matches);
                                                                    $number = isset($matches[1]) ? (int)$matches[1] : 1;

                                                                    // Batasi number ke range 1-3
                                                                    $number = max(1, min(3, $number));

                                                                    // Ambil warna berdasarkan prefix dan number
                                                                    $colorSet = $labelColors[$labelPrefix] ?? [
                                                                        1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                        2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                        3 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                    ];
                                                                    $color = $colorSet[$number];
                                                                @endphp
                                                                <a href="#preview-top" class="filter-back-top">
                                                                    <span class="badge" style="background: {{ $color['bg'] }}; border: 1.5px solid {{ $color['border'] }}; color: {{ $color['text'] }}; padding: 3px 8px; border-radius: 5px; font-weight: 600; font-size: 10px; white-space: nowrap;">
                                                                        {{ $singleLabel }}
                                                                    </span>
                                                                </a>
                                                                @if($index < count($labelParts) - 1)
                                                                    <span style="color: #94a3b8; font-size: 10px; font-weight: 600;">=</span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td colspan="18" style="background: #f8fafc;"></td>
                                                </tr>

                                                {{-- DYNAMIC MATERIAL ROWS --}}
                                                @php $matIndex = 0; @endphp
                                                @foreach($visibleMaterials as $matKey => $mat)
                                                    @php
                                                        $matIndex++;
                                                        $isFirstMaterial = $matIndex === 1;
                                                        $isLastMaterial = $matIndex === count($visibleMaterials);
                                                        $pricePerUnit = $mat['price_per_unit'] ?? ($mat['package_price'] ?? 0);
                                                        $priceUnitLabel = $mat['price_unit_label'] ?? ($mat['package_unit'] ?? '');
                                                        $priceCalcQty = $mat['price_calc_qty'] ?? ($mat['qty'] ?? 0);
                                                        $priceCalcUnit = $mat['price_calc_unit'] ?? ($mat['unit'] ?? '');
                                                                // Rumus baru: (Harga beli / ukuran per kemasan) * Qty per pekerjaan
                                                                $conversionFactor = 1;
                                                                if ($matKey === 'sand') {
                                                                     $conversionFactor = $mat['detail_value'] ?? 1;
                                                                } elseif ($matKey === 'ceramic') {
                                                                     $conversionFactor = $mat['object']->pieces_per_package ?? 1;
                                                                }
                                                                
                                                                $normalizedPrice = \App\Helpers\NumberHelper::normalize($mat['package_price'] ?? 0);
                                                                $normalizedSize = \App\Helpers\NumberHelper::normalize($conversionFactor);
                                                                $normalizedQty = \App\Helpers\NumberHelper::normalize($mat['qty'] ?? 0);

                                                                $unitPrice = ($normalizedSize > 0) ? ($normalizedPrice / $normalizedSize) : 0;

                                                                $hargaKomparasi = \App\Helpers\NumberHelper::normalize($unitPrice * $normalizedQty);                                                        $comparisonUnit = $mat['comparison_unit'] ?? ($mat['unit'] ?? '');
                                                        $detailValue = $mat['detail_value'] ?? 1;

                                                        $qtyTitleParts = [];
                                                        if (!empty($mat['qty_debug'])) {
                                                            $qtyTitleParts[] = $mat['qty_debug'];
                                                        }
                                                        $qtyTitleParts[] = 'Nilai tampil: ' . $formatNum($mat['qty']) . ' ' . ($mat['unit'] ?? '');
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
                                                        $packagePriceTitleParts[] = 'Nilai tampil: Rp ' . $formatMoney($mat['package_price']) . ' / ' . $mat['package_unit'];
                                                        if ($priceUnitLabel !== $mat['package_unit'] || abs($pricePerUnit - $mat['package_price']) > 0.00001) {
                                                            $packagePriceTitleParts[] = 'Harga unit formula: Rp ' . $formatMoney($pricePerUnit) . ' / ' . $priceUnitLabel;
                                                        }
                                                        if ($matKey === 'sand' && $detailValue > 0) {
                                                            $convertedSand = $mat['package_price'] / $detailValue;
                                                            $packagePriceTitleParts[] = 'Konversi: Rp ' . $formatMoney($mat['package_price']) . ' / ' . $formatNum($detailValue) . ' ' . $comparisonUnit . ' = Rp ' . $formatMoney($convertedSand) . ' / ' . $comparisonUnit;
                                                        }
                                                        $packagePriceTitle = implode(' | ', $packagePriceTitleParts);
                                                    @endphp
                                                    <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                                                        {{-- Column 1-3: Qty, Unit, Material Name --}}
                                                        <td class="text-end fw-bold sticky-col-1" title="{{ $qtyTitle }}">@format($mat['qty'])</td>
                                                        <td class="text-center sticky-col-2">{{ $mat['unit'] }}</td>
                                                        <td class="fw-bold sticky-col-3">{{ $mat['name'] }}</td>

                                                        {{-- Column 4-9: Material Details --}}
                                                        <td class="text-muted">{{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}</td>
                                                        <td class="fw-bold">{{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}</td>
                                                        <td class="{{ $matKey === 'brick' ? 'text-center text-nowrap' : '' }}">{{ $mat['detail_display'] }}</td>
                                                        <td class="{{ $matKey === 'cement' || $matKey === 'sand' || $matKey === 'brick' ? 'text-start text-nowrap fw-bold' : '' }}" title="{{ $detailTitle }}">{{ $mat['detail_extra'] ?? '' }}</td>
                                                        <td class="preview-scroll-td preview-store-cell">
                                                            <div class="preview-scroll-cell">{{ $mat['store_display'] ?? ($mat['object']->{$mat['store_field']} ?? '-') }}</div>
                                                        </td>
                                                        <td class="preview-scroll-td preview-address-cell small text-muted">
                                                            <div class="preview-scroll-cell">{{ $mat['address_display'] ?? ($mat['object']->{$mat['address_field']} ?? '-') }}</div>
                                                        </td>

                                                        {{-- Column 10-11: Package Price --}}
                                                        @if(isset($mat['is_special']) && $mat['is_special'])
                                                            <td class="text-center text-muted">-</td>
                                                            <td></td>
                                                        @else
                                                            <td class="text-nowrap fw-bold" title="{{ $packagePriceTitle }}">
                                                                <div class="d-flex justify-content-between w-100">
                                                                    <span>Rp</span>
                                                                    <span>{{ $formatMoney($mat['package_price']) }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-muted text-nowrap ps-1">/ {{ $mat['package_unit'] }}</td>
                                                        @endif

                                                        {{-- Column 12: Total Price (Harga Komparasi) --}}
                                                        @if(isset($mat['is_special']) && $mat['is_special'])
                                                            <td class="text-center text-muted">-</td>
                                                        @else
                                                            @php
                                                                // Hitung harga komparasi: (harga / ukuran) * qty
                                                                $hargaKomparasiDebugParts = [];
                                                                $hargaKomparasiDebugParts[] = "Rumus: (Rp " . $formatMoney($normalizedPrice) . " / " . $formatNum($normalizedSize) . ") x " . $formatNum($normalizedQty) . " = Rp " . $formatMoney($hargaKomparasi);
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
                                                        @if($isFirstMaterial)
                                                            @php
                                                                // Build debug breakdown for grand_total (harga per kemasan × qty)
                                                                $grandTotalParts = [];
                                                                $calculatedGrandTotal = 0;
                                                                foreach($visibleMaterials as $debugMatKey => $debugMat) {
                                                                    if (!isset($debugMat['is_special']) || !$debugMat['is_special']) {
                                                                        // Rumus baru: (Harga beli / ukuran per kemasan) * Qty per pekerjaan
                                                                        $debugConversionFactor = 1;
                                                                        if ($debugMatKey === 'sand') {
                                                                             $debugConversionFactor = $debugMat['detail_value'] ?? 1;
                                                                        } elseif ($debugMatKey === 'ceramic') {
                                                                             $debugConversionFactor = $debugMat['object']->pieces_per_package ?? 1;
                                                                        }
                                                                        
                                                                        $debugNormalizedPrice = \App\Helpers\NumberHelper::normalize($debugMat['package_price'] ?? 0);
                                                                        $debugNormalizedSize = \App\Helpers\NumberHelper::normalize($debugConversionFactor);
                                                                        $debugNormalizedQty = \App\Helpers\NumberHelper::normalize($debugMat['qty'] ?? 0);
                                                                        
                                                                        $debugUnitPrice = ($debugNormalizedSize > 0) ? ($debugNormalizedPrice / $debugNormalizedSize) : 0;
                                                                        $debugUnitPrice = \App\Helpers\NumberHelper::normalize($debugUnitPrice);
                                                                        
                                                                        $calcPrice = \App\Helpers\NumberHelper::normalize($debugUnitPrice * $debugNormalizedQty);
                                                                        $calculatedGrandTotal += $calcPrice;
                                                                        
                                                                        $grandTotalParts[] = $debugMat['name'] . " ((Rp " . $formatMoney($debugNormalizedPrice) . " / " . $formatNum($debugNormalizedSize) . ") x " . $formatNum($debugNormalizedQty) . "): Rp " . $formatMoney($calcPrice);
                                                                    }
                                                                }
                                                                $grandTotalValue = \App\Helpers\NumberHelper::normalize($calculatedGrandTotal);
                                                                $grandTotalDebug = "Rumus: " . implode(' + ', $grandTotalParts);
                                                                $grandTotalDebug .= " | Total: Rp " . $formatMoney($grandTotalValue);

                                                                // Build debug for costPerM2 (normalize areaForCost karena non-rupiah, normalize hasil pembagian)
                                                                $normalizedAreaForCost = \App\Helpers\NumberHelper::normalize($areaForCost);
                                                                $calculatedCostPerM2 = $normalizedAreaForCost > 0
                                                                    ? \App\Helpers\NumberHelper::normalize($grandTotalValue / $normalizedAreaForCost)
                                                                    : 0;
                                                                $costPerM2Debug = "Rumus: Rp " . $formatMoney($grandTotalValue) . " / " . $formatNum($normalizedAreaForCost) . " M2";
                                                                $costPerM2Debug .= " | Nilai tampil: Rp " . $formatMoney($calculatedCostPerM2) . " / M2";
                                                            @endphp
                                                            <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell" title="{{ $grandTotalDebug }}">
                                                                <div class="d-flex justify-content-between w-100">
                                                                    <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                                                    <span class="text-success-dark" style="font-size: 15px;">{{ $formatMoney($grandTotalValue) }}</span>
                                                                </div>
                                                            </td>
                                                            <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell" title="{{ $costPerM2Debug }}">
                                                                <div class="d-flex justify-content-between w-100">
                                                                    <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                                                    <span class="text-primary-dark" style="font-size: 14px;">{{ $formatMoney($calculatedCostPerM2) }}</span>
                                                                </div>
                                                            </td>
                                                            <td rowspan="{{ $rowCount }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                                                        @endif

                                                        {{-- Column 16-17: Harga Beli Aktual / Satuan Komparasi --}}
                                                        @if(isset($mat['is_special']) && $mat['is_special'])
                                                            <td class="text-center text-muted">-</td>
                                                            <td></td>
                                                        @else
                                                            @php
                                                                // Normalize qty untuk konsistensi
                                                                $normalizedQtyValue = \App\Helpers\NumberHelper::normalize($mat['qty'] ?? 0);
                                                                        // Gunakan harga komparasi yang sudah dihitung (sesuai formula)
                                                                        // Normalize ke 0 decimal agar perhitungan backward (total / qty) sesuai dengan angka yang ditampilkan (formatMoney truncates)
                                                                        $totalPriceValue = \App\Helpers\NumberHelper::normalize($hargaKomparasi, 0);
                                                                // Normalisasi nilai agar sesuai dengan yang ditampilkan (mengikuti aturan NumberHelper)
                                                                // Ini memastikan perhitungan menggunakan nilai yang sama dengan yang user lihat
                                                                $normalizedDetailValue = \App\Helpers\NumberHelper::normalize($detailValue);

                                                                // Untuk sand, hanya hitung total_price / qty (tanpa pembagian detail_value)
                                                                if ($matKey === 'sand') {
                                                                    $actualBuyPrice = ($normalizedQtyValue > 0)
                                                                        ? \App\Helpers\NumberHelper::normalize($totalPriceValue / $normalizedQtyValue)
                                                                        : 0;
                                                                    $hargaBeliAktualDebug = "Rumus: Rp " . $formatMoney($totalPriceValue) . " / " . $formatNum($normalizedQtyValue) . " " . $mat['unit'] . " = Rp " . $formatMoney($actualBuyPrice);
                                                                } else {
                                                                    $actualBuyPrice = ($normalizedQtyValue > 0 && $normalizedDetailValue > 0)
                                                                        ? \App\Helpers\NumberHelper::normalize($totalPriceValue / $normalizedQtyValue / $normalizedDetailValue)
                                                                        : 0;
                                                                    $hargaBeliAktualDebug = "Rumus: Rp " . $formatMoney($totalPriceValue) . " / " . $formatNum($normalizedQtyValue) . " " . $mat['unit'] . " / " . $formatNum($normalizedDetailValue) . " " . $comparisonUnit . " = Rp " . $formatMoney($actualBuyPrice);
                                                                }
                                                            @endphp
                                                            <td class="text-nowrap" title="{{ $hargaBeliAktualDebug }}">
                                                                <div class="d-flex justify-content-between w-100">
                                                                    <span>Rp</span>
                                                                    <span>{{ $formatMoney($actualBuyPrice) }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-muted text-nowrap ps-1">/ {{ $comparisonUnit }}</td>
                                                        @endif

                                                        {{-- Column 18: Action (Rowspan) --}}
                                                        @if($isFirstMaterial)
                                                            <td rowspan="{{ $rowCount }}" class="text-center align-top rowspan-cell">
                                                                @php
                                                                    $traceFormulaCode = $requestData['formula_code']
                                                                        ?? $requestData['work_type']
                                                                        ?? null;
                                                                    $traceParams = [
                                                                        'formula_code' => $traceFormulaCode,
                                                                        'work_type' => $requestData['work_type'] ?? null,
                                                                        'wall_length' => $requestData['wall_length'] ?? null,
                                                                        'wall_height' => $requestData['wall_height'] ?? null,
                                                                        'area' => $requestData['area'] ?? null,
                                                                        'mortar_thickness' => $requestData['mortar_thickness'] ?? null,
                                                                        'grout_thickness' => $requestData['grout_thickness'] ?? null,
                                                                        'painting_layers' => $requestData['painting_layers'] ?? null,
                                                                        'layer_count' => $requestData['layer_count'] ?? null,
                                                                        'auto_trace' => 1,
                                                                    ];
                                                                    $traceParams['brick_id'] = $brick->id;
                                                                    if (isset($item['cement'])) {
                                                                        $traceParams['cement_id'] = $item['cement']->id;
                                                                    }
                                                                    if (isset($item['sand'])) {
                                                                        $traceParams['sand_id'] = $item['sand']->id;
                                                                    }
                                                                    if (isset($item['cat'])) {
                                                                        $traceParams['cat_id'] = $item['cat']->id;
                                                                    }
                                                                    if (isset($item['ceramic'])) {
                                                                        $traceParams['ceramic_id'] = $item['ceramic']->id;
                                                                    }
                                                                    if (isset($item['nat'])) {
                                                                        $traceParams['nat_id'] = $item['nat']->id;
                                                                    }
                                                                    $traceUrl = route('material-calculator.trace') . '?' . http_build_query(array_filter($traceParams, function ($value) {
                                                                        return $value !== null && $value !== '';
                                                                    }));
                                                                @endphp
                                                                <div class="d-flex flex-column gap-2 align-items-center">
                                                                    <a href="{{ $traceUrl }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
                                                                        <i class="bi bi-diagram-3 me-1"></i> Trace
                                                                    </a>
                                                                    <form action="{{ route('material-calculations.store') }}" method="POST" style="margin: 0;">
                                                                    @csrf
                                                                    @foreach($requestData as $key => $value)
                                                                        @if($key != '_token' && $key != 'cement_id' && $key != 'sand_id' && $key != 'brick_ids' && $key != 'brick_id' && $key != 'price_filters' && $key != 'work_type')
                                                                            @if(is_array($value))
                                                                                @foreach($value as $v)
                                                                                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                                                                @endforeach
                                                                            @else
                                                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                                            @endif
                                                                        @endif
                                                                    @endforeach
                                                                    
                                                                    {{-- Explicitly pass work_type --}}
                                                                    <input type="hidden" name="work_type" value="{{ $requestData['work_type'] ?? '' }}">

                                                                    {{-- Only pass brick_id if NOT brickless --}}
                                                                    @if(!($isBrickless ?? false))
                                                                        <input type="hidden" name="brick_id" value="{{ $brick->id }}">
                                                                    @endif

                                                                    @if(isset($item['cement']))
                                                                        <input type="hidden" name="cement_id" value="{{ $item['cement']->id }}">
                                                                    @endif
                                                                    @if(isset($item['sand']))
                                                                        <input type="hidden" name="sand_id" value="{{ $item['sand']->id }}">
                                                                    @endif
                                                                    @if(isset($item['cat']))
                                                                        <input type="hidden" name="cat_id" value="{{ $item['cat']->id }}">
                                                                    @endif
                                                                    @if(isset($item['ceramic']))
                                                                        <input type="hidden" name="ceramic_id" value="{{ $item['ceramic']->id }}">
                                                                    @endif
                                                                    @if(isset($item['nat']))
                                                                        <input type="hidden" name="nat_id" value="{{ $item['nat']->id }}">
                                                                    @endif
                                                                    <input type="hidden" name="price_filters[]" value="custom">
                                                                    <input type="hidden" name="confirm_save" value="1">
                                                                        <button type="submit" class="btn-select">
                                                                            <i class="bi bi-check-circle me-1"></i> Pilih
                                                                        </button>
                                                                    </form>
                                                                </div>
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
                                <i class="bi bi-info-circle me-1"></i> Gunakan tombol <span class="text-muted">Pilih</span> pada kolom Aksi untuk menyimpan perhitungan ini ke proyek Anda.
                            </p>
                        </div>
            </div>
        </div>
    @endif
</div>

<style>
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
    .preview-combinations-page .preview-params-sticky > * {
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
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
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
    .rekap-sticky-header {
        position: fixed;
        left: 0;
        z-index: 120;
        pointer-events: none;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-6px);
        visibility: hidden;
        transition: opacity 0.2s ease, transform 0.2s ease;
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
        position: relative;
        overflow: hidden;
        white-space: nowrap;
        text-align: left;
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
        min-width: 90px;
        max-width: 105px;
        width: 90px;
    }
    .sticky-col-2 {
        position: sticky;
        left: 105px;
        background-color: white;
        z-index: 2;
        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
        min-width: 80px;
    }
    .sticky-col-3 {
        position: sticky;
        left: 202px;
        background-color: white;
        z-index: 2;
        box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
        min-width: 100px;
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
        0%, 100% {
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
        0%, 100% {
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
@if(!empty($projects))
    @php
        $allPriceRows = [];
        $bestRows = [];
        $hasAllPriceBrick = false;
        foreach ($projects as $project) {
            $brick = $project['brick'] ?? null;
            $brickLabel = '';
            if ($brick) {
                $brickLabel = trim(($brick->brand ?? '') . ' ' . ($brick->type ?? ''));
                if ($brickLabel === '') {
                    $brickLabel = $brick->material_name ?? '';
                }
                if ($brickLabel !== '') {
                    $hasAllPriceBrick = true;
                }
            }
            foreach ($project['combinations'] as $label => $items) {
                foreach ($items as $item) {
                    $labelParts = array_map('trim', explode('=', $label));
                    $grandTotal = (float)($item['result']['grand_total'] ?? 0);
                    $rowBase = [
                        'label' => $label,
                        'brick' => $brickLabel,
                        'grand_total' => $grandTotal,
                    ];

                    $bestLabel = null;
                    foreach ($labelParts as $part) {
                        if ($bestLabel === null && str_starts_with($part, 'Rekomendasi')) {
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
                        'grand_total' => (float)($row['grand_total'] ?? 0),
                    ];
                }
            }
        }
        $sortByLabelNumber = function ($a, $b) {
            $getNumber = function ($label) {
                if (preg_match('/\s+(\d+)/', $label, $matches)) {
                    return (int)$matches[1];
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
            $sumTotals = array_sum(array_map(fn ($row) => $row['grand_total'], $allPriceRows));
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

    <div class="modal fade modal-high" id="allPriceModal" tabindex="-1" aria-labelledby="allPriceModalLabel" aria-hidden="true">
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
                    @if(count($allPriceRows) > 0)
                        @if(count($bestRows) > 0)
                            <div class="fw-bold mb-1">Rekomendasi</div>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Label</th>
                                            @if($hasAllPriceBrick)
                                                <th>Bata</th>
                                            @endif
                                            <th class="text-end" style="width: 160px;">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bestRows as $index => $row)
                                            <tr>
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td>{{ $row['display_label'] }}</td>
                                                @if($hasAllPriceBrick)
                                                    <td>{{ $row['brick'] ?: '-' }}</td>
                                                @endif
                                                <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if(count($commonRows) > 0)
                            <div class="fw-bold mb-1">Populer</div>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Label</th>
                                            @if($hasAllPriceBrick)
                                                <th>Bata</th>
                                            @endif
                                            <th class="text-end" style="width: 160px;">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($commonRows as $index => $row)
                                            <tr>
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td>{{ $row['display_label'] }}</td>
                                                @if($hasAllPriceBrick)
                                                    <td>{{ $row['brick'] ?: '-' }}</td>
                                                @endif
                                                <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
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
                                        <th style="width: 60px;">#</th>
                                        <th>Label</th>
                                        @if($hasAllPriceBrick)
                                            <th>Bata</th>
                                        @endif
                                        <th class="text-end" style="width: 160px;">Grand Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allPriceRows as $row)
                                        <tr>
                                            <td class="text-muted">{{ $row['index'] }}</td>
                                            <td>{{ $row['display_label'] }}</td>
                                            @if($hasAllPriceBrick)
                                                <td>{{ $row['brick'] ?: '-' }}</td>
                                            @endif
                                            <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
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
    #allPriceModal.modal-high {
        z-index: 20050 !important;
    }
    .modal-backdrop.modal-high-backdrop {
        z-index: 20040 !important;
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

    // Robust "Skip" Logic:
    // When submitting the form (moving forward to Result), replace the CURRENT history entry (Preview)
    // with the URL of the Create page. This ensures that hitting "Back" from Result goes straight to Create.
    document.addEventListener('DOMContentLoaded', () => {
        const baseCreateUrl = "{{ $requestData['referrer'] ?? route('material-calculations.create') }}";
        const createPageUrl = baseCreateUrl.includes('?')
            ? `${baseCreateUrl}&resume=1`
            : `${baseCreateUrl}?resume=1`;

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
                document.body.appendChild(stickyContainer);

                const syncWidths = () => {
                    const sourceCells = rekapHead.querySelectorAll('th');
                    const stickyCells = stickyHead.querySelectorAll('th');
                    const rect = rekapTable.getBoundingClientRect();
                    stickyTable.style.width = `${rect.width}px`;
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
                    stickyContainer.style.top = `${stickyTop}px`;
                    stickyContainer.style.left = `${wrapRect.left}px`;
                    stickyContainer.style.width = `${wrapRect.width}px`;
                    stickyTable.style.transform = `translateX(${-scrollLeft}px)`;
                    const isActive = rect.top <= stickyTop && rect.bottom > stickyTop + headHeight;
                    stickyContainer.classList.toggle('is-active', isActive);
                    rekapTable.classList.toggle('rekap-sticky-active', isActive);
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
                window.addEventListener('scroll', scheduleSync, { passive: true });
                window.addEventListener('resize', scheduleSync);
                if (rekapWrap) {
                    rekapWrap.addEventListener('scroll', scheduleSync, { passive: true });
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

                window.addEventListener('scroll', handleScrollState, { passive: true });
                if (rekapWrap) {
                    rekapWrap.addEventListener('scroll', handleScrollState, { passive: true });
                }
            }
        }
    });
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
            scroller.addEventListener('scroll', updatePreviewScrollIndicators, { passive: true });
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
        window.scrollTo({ top: Math.max(top, 0), behavior: behavior || 'auto' });
        requestAnimationFrame(function() {
            const overlayAfter = getOverlayHeight();
            const rect = target.getBoundingClientRect();
            if (rect.top < overlayAfter + 2) {
                const adjust = overlayAfter + 2 - rect.top;
                window.scrollTo({ top: Math.max(window.pageYOffset - adjust, 0), behavior: 'auto' });
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
                const styleTop = window.getComputedStyle(card).getPropertyValue('--sticky-top') || '60px';
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
            window.addEventListener('scroll', updateSticky, { passive: true });
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

@if(isset($isMultiCeramic) && $isMultiCeramic && isset($isLazyLoad) && $isLazyLoad)
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
            url: '{{ route("api.material-calculator.ceramic-combinations") }}',
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
                        $content.html(response.html).show();
                        ensureCeramicModals($content);
                        $ceramicProject.data('loaded', 'true');
                        if (typeof window.updatePreviewScrollIndicators === 'function') {
                            window.updatePreviewScrollIndicators();
                        }
                    } else {
                        showError($ceramicProject, response.message || 'Gagal memuat kombinasi');
                        $ceramicProject.data('loaded', 'false');
                    }
                }, 300);
            },
            error: function(xhr) {
                // Clear Interval
                clearInterval($ceramicProject.data('loading-interval'));
                
                const errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan saat memuat kombinasi';
                showError($ceramicProject, errorMsg + ' (Check console for details)');
                $ceramicProject.data('loaded', 'false');
            }
        });
    }

    // Show error message
    function showError($ceramicProject, message) {
        $ceramicProject.find('.loading-placeholder').hide();
        $ceramicProject.find('.combinations-content')
            .html(`<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ${message}</div>`)
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
            $button.closest('.nav').find('.nav-link').not($button).removeClass('active').attr('aria-selected', 'false');
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
    $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function() {
        loadVisibleCeramics();
    });

    $('#ceramicTypeTabs button[data-bs-toggle="pill"], #ceramicTypeTabs button[data-bs-toggle="tab"]').on('shown.bs.tab shown.bs.pill', function() {
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
document.addEventListener('shown.bs.modal', function(event) {
    if (!event.target) return;
    if (event.target.id !== 'allPriceModal' && !event.target.id.startsWith('ceramicAllPriceModal-')) return;
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.classList.add('modal-high-backdrop');
    }
});

document.addEventListener('hidden.bs.modal', function(event) {
    if (!event.target) return;
    if (event.target.id !== 'allPriceModal' && !event.target.id.startsWith('ceramicAllPriceModal-')) return;
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.classList.remove('modal-high-backdrop');
    }
});
</script>
@endpush
