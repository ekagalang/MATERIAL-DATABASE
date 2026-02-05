@extends('layouts.app')

@php
    $formulaDescriptions = [];
    $formulas = $availableFormulas ?? $formulas ?? [];
    $formulaNames = [];
    $formulaOptions = [];
    foreach ($formulas as $formula) {
        $formulaDescriptions[$formula['code']] = $formula['description'] ?? '';
        $formulaNames[$formula['code']] = $formula['name'] ?? $formula['code'];
        $formulaOptions[] = [
            'code' => $formula['code'] ?? '',
            'name' => $formula['name'] ?? '',
            'materials' => $formula['materials'] ?? [],
        ];
    }

    $selectedWorkType = old('work_type') ?? old('work_type_select') ?? request('formula_code');
    $selectedWorkTypeLabel = $selectedWorkType
        ? ($formulaNames[$selectedWorkType] ?? $selectedWorkType)
        : '';

    $selectedCeramicTypes = old('ceramic_types', request('ceramic_types', []));
    $selectedCeramicTypes = is_array($selectedCeramicTypes) ? $selectedCeramicTypes : [$selectedCeramicTypes];
    $materialTypeLabels = [
        'brick' => 'Bata',
        'cement' => 'Semen',
        'sand' => 'Pasir',
        'cat' => 'Cat',
        'ceramic_type' => 'Keramik',
        'ceramic' => 'Keramik',
        'nat' => 'Nat',
    ];
    $selectedMaterialTypeFilters = old('material_type_filters') ?? (request('material_type_filters') ?? []);
    if (empty($selectedMaterialTypeFilters['ceramic_type']) && !empty($selectedCeramicTypes)) {
        $selectedMaterialTypeFilters['ceramic_type'] = $selectedCeramicTypes;
    }
    
    // Cek Single Brick (Carry Over)
    $isSingleCarryOver = request()->has('brick_id');
    $singleBrick = $isSingleCarryOver ? $bricks->find(request('brick_id')) : null;

    // FIX: Definisikan variable $isMultiBrick dengan benar
    $isMultiBrick = isset($selectedBricks) && $selectedBricks->count() > 0;
@endphp

@section('content')
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.98); z-index: 9999; justify-content: center; align-items: center; flex-direction: column; backdrop-filter: blur(8px);">
    <div style="width: 80%; max-width: 500px; text-align: center;">
        <div class="mb-4 animate-bounce">
             <i class="bi bi-calculator text-primary" style="font-size: 3.5rem;"></i>
        </div>
        <h4 id="loadingTitle" class="mb-2 text-primary fw-bold" style="font-size: 1.5rem; transition: opacity 0.3s ease;">Memulai Perhitungan...</h4>
        <p id="loadingSubtitle" class="text-muted mb-4" style="transition: opacity 0.3s ease;">Mohon tunggu, kami sedang menyiapkan data Anda.</p>
        
        <div class="progress" style="height: 12px; border-radius: 6px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); background-color: #e9ecef;">
            <div id="loadingProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-gradient-primary" role="progressbar" style="width: 0%; transition: width 0.3s ease;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between mt-2 text-muted small fw-medium">
            <span id="loadingPercent">0%</span>
            <span id="loadingTime"><i class="bi bi-clock me-1"></i>Est. 3-8 detik</span>
        </div>

        <button type="button" id="cancelCalculation" class="btn btn-sm btn-primary-glossy mt-4 px-4 rounded-pill">
            <i class="bi bi-x-circle me-1"></i> Batalkan
        </button>
    </div>
</div>

<h3 class="calc-style"><i class="bi bi-calculator text-primary"></i> Perhitungan Material Baru</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Perhatian:</strong>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('material-calculations.store') }}" method="POST" id="calculationForm">
        @csrf
        
        {{-- Capture Referrer for History Skip Logic --}}
        <input type="hidden" name="referrer" value="{{ request()->url() }}">

        {{-- Hidden fields for default values --}}
        <input type="hidden" name="installation_type_id" value="{{ $defaultInstallationType->id ?? '' }}">
        <input type="hidden" name="mortar_formula_id" value="{{ $defaultMortarFormula->id ?? '' }}">

        {{-- TWO COLUMN LAYOUT - ALWAYS VISIBLE --}}
        <div class="two-column-layout">
            {{-- LEFT COLUMN: FORM INPUTS --}}
            <div class="left-column">
                {{-- WORK TYPE --}}
                <div class="form-group work-type-group">
                    <label>Item Pekerjaan</label>
                    <div class="input-wrapper">
                        <div class="work-type-autocomplete">
                            <div class="work-type-input">
                                <input type="text"
                                       id="workTypeDisplay"
                                       class="autocomplete-input"
                                       placeholder="Pilih atau ketik item pekerjaan..."
                                       autocomplete="off"
                                       value="{{ $selectedWorkTypeLabel }}"
                                       {{ request('formula_code') ? 'readonly' : '' }}
                                       required>
                            </div>
                            <div class="autocomplete-list" id="workType-list"></div>
                        </div>
                        <input type="hidden" id="workTypeSelector" name="work_type_select" value="{{ $selectedWorkType }}">
                    </div>
                </div>

                <div id="inputFormContainer">
                    <div id="brickForm" class="work-type-form">

                        {{-- DIMENSI - VERTICAL LAYOUT --}}
                        <div class="dimensions-container-vertical">
                            <div class="dimension-item">
                                <label>Panjang</label>
                                <div class="input-with-unit">
                                    <input type="number" name="wall_length" id="wallLength" step="0.01" min="0.01"
                                        value="{{ request('wall_length') }}" required>
                                    <span class="unit">M</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="wallHeightGroup">
                                <label id="wallHeightLabel">Tinggi</label>
                                <div class="input-with-unit">
                                    <input type="number" name="wall_height" id="wallHeight" step="0.01" min="0.01"
                                        value="{{ request('wall_height') }}" required>
                                    <span class="unit">M</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="mortarThicknessGroup">
                                <label id="mortarThicknessLabel">Tebal Adukan</label>
                                <div class="input-with-unit">
                                    <input type="number" name="mortar_thickness" id="mortarThickness" step="0.1" min="0.1" data-unit="cm"
                                        value="{{ request('mortar_thickness', 2) }}">
                                    <span class="unit" id="mortarThicknessUnit">cm</span>
                                </div>
                            </div>

                            {{-- INPUT TINGKAT UNTUK ROLLAG / LAPIS UNTUK PENGECATAN --}}
                            <div class="dimension-item" id="layerCountGroup" style="display: none;">
                                <label id="layerCountLabel">Tingkat</label>
                                <div class="input-with-unit" id="layerCountInputWrapper" style="background-color: #fffbeb; border-color: #fcd34d;">
                                    <input type="number" name="layer_count" id="layerCount" step="1" min="1" value="{{ request('layer_count') ?? 1 }}">
                                    <span class="unit" id="layerCountUnit" style="background-color: #fef3c7;">Lapis</span>
                                </div>
                            </div>

                            {{-- INPUT SISI PLESTERAN UNTUK WALL PLASTERING --}}
                            <div class="dimension-item" id="plasterSidesGroup" style="display: none;">
                                <label>Sisi Plesteran</label>
                                <div class="input-with-unit" style="background-color: #e0f2fe; border-color: #7dd3fc;">
                                    <input type="number" name="plaster_sides" id="plasterSides" step="1" min="1" value="{{ request('plaster_sides') ?? 1 }}">
                                    <span class="unit" style="background-color: #bae6fd;">Sisi</span>
                                </div>
                            </div>

                            {{-- INPUT SISI ACI UNTUK SKIM COATING --}}
                            <div class="dimension-item" id="skimSidesGroup" style="display: none;">
                                <label>Sisi Acian</label>
                                <div class="input-with-unit" style="background-color: #e0e7ff; border-color: #a5b4fc;">
                                    <input type="number" name="skim_sides" id="skimSides" step="1" min="1" value="{{ request('skim_sides') ?? 1 }}">
                                    <span class="unit" style="background-color: #c7d2fe;">Sisi</span>
                                </div>
                            </div>

                            {{-- INPUT TEBAL NAT UNTUK TILE INSTALLATION & GROUT ONLY --}}
                            <div class="dimension-item" id="groutThicknessGroup" style="display: none;">
                                <label>Tebal Nat</label>
                                <div class="input-with-unit" style="background-color: #f1f5f9; border-color: #cbd5e1;">
                                    <input type="number" name="grout_thickness" id="groutThickness" step="0.1" min="0.1" value="{{ request('grout_thickness', 2) }}">
                                    <span class="unit" style="background-color: #e2e8f0;">mm</span>
                                </div>
                            </div>

                            {{-- INPUT UKURAN KERAMIK UNTUK GROUT TILE --}}
                            <div class="dimension-item" id="ceramicLengthGroup" style="display: none;">
                                <label>Panjang Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="number" name="ceramic_length" id="ceramicLength" step="0.1" min="1" value="{{ request('ceramic_length', 30) }}">
                                    <span class="unit" style="background-color: #fef08a;">cm</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="ceramicWidthGroup" style="display: none;">
                                <label>Lebar Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="number" name="ceramic_width" id="ceramicWidth" step="0.1" min="1" value="{{ request('ceramic_width', 30) }}">
                                    <span class="unit" style="background-color: #fef08a;">cm</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="ceramicThicknessGroup" style="display: none;">
                                <label>Tebal Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="number" name="ceramic_thickness" id="ceramicThickness" step="0.1" min="0.1" value="{{ request('ceramic_thickness', 8) }}">
                                    <span class="unit" style="background-color: #fef08a;">mm</span>
                                </div>
                            </div>
                        </div>

                        <div class="material-type-filter-group" id="materialTypeFilterGroup" style="display: none;">
                            @foreach($materialTypeLabels as $materialKey => $materialLabel)
                                @php
                                    $selectedTypeValue = $selectedMaterialTypeFilters[$materialKey] ?? '';
                                @endphp
                        <div class="form-group material-type-filter-item" data-material-type="{{ $materialKey }}" style="display: none;">
                                    @php
                                        $labelText = $materialKey === 'ceramic'
                                            ? 'Ukuran Keramik'
                                            : ($materialKey === 'ceramic_type' ? 'Jenis Keramik' : ('Jenis ' . $materialLabel));
                                        $placeholderText = $materialKey === 'ceramic'
                                            ? 'Pilih atau ketik ukuran keramik...'
                                            : ($materialKey === 'ceramic_type'
                                                ? 'Pilih atau ketik jenis keramik...'
                                                : 'Pilih atau ketik jenis ' . strtolower($materialLabel) . '...');
                                    @endphp
                                    <label>{{ $labelText }}</label>
                                    <div class="material-type-rows" data-material-type="{{ $materialKey }}">
                                        <div class="material-type-row material-type-row-base" data-material-type="{{ $materialKey }}">
                                            <div class="input-wrapper">
                                                <div class="work-type-autocomplete">
                                                    <div class="work-type-input">
                                                        <input type="text"
                                                               id="materialTypeDisplay-{{ $materialKey }}"
                                                               class="autocomplete-input"
                                                               placeholder="{{ $placeholderText }}"
                                                               autocomplete="off"
                                                               value="{{ is_array($selectedTypeValue) ? ($selectedTypeValue[0] ?? '') : $selectedTypeValue }}">
                                                    </div>
                                                    <div class="autocomplete-list" id="materialType-list-{{ $materialKey }}"></div>
                                                </div>
                                                <input type="hidden"
                                                       id="materialTypeSelector-{{ $materialKey }}"
                                                       name="material_type_filters[{{ $materialKey }}]"
                                                       value="{{ is_array($selectedTypeValue) ? ($selectedTypeValue[0] ?? '') : $selectedTypeValue }}">
                                            </div>
                                            <div class="material-type-row-actions">
                                                <button type="button" class="material-type-row-btn material-type-row-btn-delete"
                                                    data-material-type-action="remove" data-material-type="{{ $materialKey }}"
                                                    title="Hapus baris">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <button type="button" class="material-type-row-btn material-type-row-btn-add"
                                                    data-material-type-action="add" data-material-type="{{ $materialKey }}"
                                                    title="Tambah baris">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="material-type-extra-rows" data-material-type="{{ $materialKey }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" id="btnResetForm" class="btn-cancel" style="padding: 5px 20px;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            {{-- RIGHT COLUMN: FILTERS --}}
            <div class="right-column">
                {{-- FILTER CHECKBOX (MULTIPLE SELECTION) --}}
                <div class="filter-section">
                    <label class="filter-section-title">+ Filter by:</label>
                    <div class="filter-tickbox-list">
    
                    <div class="filter-tickbox-list">
                    
                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_all" value="all">
                        <label for="filter_all">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Semua</b>
                                <span class="text-muted">: Menampilkan semua kombinasi harga</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item position-relative">
                        <input type="checkbox" name="price_filters[]" id="filter_best" value="best" checked>
                        <label for="filter_best" class="w-100">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Preferensi</b>
                                <span class="text-muted">: 3 Kombinasi pilihan Kanggo</span>
                            </span>
                        </label>
                        <a href="{{ route('settings.recommendations.index') }}" 
                        class="position-absolute top-0 end-0 mt-2 me-2 text-decoration-none" 
                        style="z-index: 50; color: #64748b; font-size: 1.1rem; padding: 4px;" 
                        title="Setting Rekomendasi"
                        onclick="event.stopPropagation(); event.preventDefault(); if(typeof openGlobalMaterialModal === 'function') { openGlobalMaterialModal(this.href, document.getElementById('workTypeSelector')?.value); }">
                            <i class="bi bi-gear-fill"></i>
                        </a>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_common" value="common">
                        <label for="filter_common">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Populer</b>
                                <span class="text-muted">: 3 Kombinasi yang paling sering digunakan Customer</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_cheapest" value="cheapest">
                        <label for="filter_cheapest">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Ekonomis</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga paling murah</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_medium" value="medium">
                        <label for="filter_medium">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Average</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga rata-rata</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_expensive" value="expensive">
                        <label for="filter_expensive">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Termahal</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga paling mahal</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_custom" value="custom">
                        <label for="filter_custom">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Custom</b>
                                <span class="text-muted">: Pilih kombinasi sendiri secara manual</span>
                            </span>
                        </label>
                    </div>
                </div>

                    {{-- CUSTOM FORM - MOVED TO RIGHT COLUMN --}}
                    <div id="customMaterialForm" style="display:none; margin-top:16px;">

                        {{-- 1. BATA SECTION --}}
                        <div class="material-section" data-material="brick">
                            <h4 class="section-header">Bata</h4>

                            @if($isMultiBrick)
                                {{-- TAMPILAN MULTI BATA --}}
                                <div class="alert alert-info border-primary py-2">
                                    <strong><i class="bi bi-collection-fill me-2"></i>{{ $selectedBricks->count() }} Bata Terpilih</strong>
                                    <div class="text-muted small mt-1">Akan dibuat perbandingan untuk semua bata ini.</div>
                                    @foreach($selectedBricks as $b)
                                        <input type="hidden" name="brick_ids[]" value="{{ $b->id }}">
                                    @endforeach
                                </div>
                            @elseif($isSingleCarryOver && $singleBrick)
                                {{-- TAMPILAN SINGLE BATA (READONLY) --}}
                                <div class="form-group">
                                    <label>Bata :</label>
                                    <div class="input-wrapper">
                                        <input type="text" value="{{ $singleBrick->brand }} - {{ $singleBrick->type }}" readonly style="background-color:#d1fae5; font-weight:bold;">
                                        <input type="hidden" name="brick_id" value="{{ $singleBrick->id }}">
                                    </div>
                                </div>
                            @else
                                {{-- TAMPILAN NORMAL (DROPDOWN) - OPTIONAL --}}
                                <div class="form-group">
                                    <label>Bata :</label>
                                    <div class="input-wrapper">
                                        <select name="brick_id" id="customBrick" class="form-select select-green">
                                            <option value="">-- Semua Bata (Auto) --</option>
                                            @foreach($bricks as $brick)
                                                <option value="{{ $brick->id }}">
                                                    {{ $brick->brand }} - {{ $brick->type }} ({{ $brick->dimension_length }}x{{ $brick->dimension_width }}x{{ $brick->dimension_height }} cm) - @currency($brick->price_per_piece)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="alert alert-info py-1 px-2 mb-2" style="font-size:12px;">
                                    <i class="bi bi-info-circle"></i> Kosongkan untuk menampilkan kombinasi dari beberapa bata
                                </div>
                            @endif
                        </div>

                        {{-- 2. SEMEN SECTION (RESTORED DROPDOWNS) --}}
                        <div class="material-section" data-material="cement">
                            <h4 class="section-header">Semen</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Semen
                            </div>

                            <div class="form-group">
                                <label>Jenis :</label>
                                <div class="input-wrapper">
                                    <select id="customCementType" name="custom_cement_type" class="select-pink">
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach($cements->groupBy('cement_name')->keys() as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Merek :</label>
                                <div class="input-wrapper">
                                    <select id="customCementBrand" name="cement_id" class="select-orange">
                                        <option value="">-- Pilih Merk (Opsional) --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- 3. PASIR SECTION (RESTORED DROPDOWNS) --}}
                        <div class="material-section" data-material="sand">
                            <h4 class="section-header">Pasir</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Pasir
                            </div>

                            <div class="form-group">
                                <label>Jenis :</label>
                                <div class="input-wrapper">
                                    <select id="customSandType" name="custom_sand_type" class="select-gray">
                                        <option value="">-- Pilih Jenis --</option>
                                        @foreach($sands->groupBy('sand_name')->keys() as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Merek :</label>
                                <div class="input-wrapper">
                                    <select id="customSandBrand" name="custom_sand_brand" class="select-gray">
                                        <option value="">-- Pilih Merk --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Kemasan :</label>
                                <div class="input-wrapper">
                                    <select id="customSandPackage" name="sand_id" class="select-gray-light">
                                        <option value="">-- Pilih Kemasan (Opsional) --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- 4. CAT SECTION --}}
                        <div class="material-section" id="catSection" data-material="cat" style="display: none;">
                            <h4 class="section-header">Cat</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Cat
                            </div>

                            <div class="form-group">
                                <label>Jenis :</label>
                                <div class="input-wrapper">
                                    <select id="customCatType" name="custom_cat_type" class="select-gray">
                                        <option value="">-- Pilih Jenis --</option>
                                        @if(isset($cats))
                                            @foreach($cats->groupBy('cat_name')->keys() as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Merek :</label>
                                <div class="input-wrapper">
                                    <select id="customCatBrand" name="custom_cat_brand" class="select-gray">
                                        <option value="">-- Pilih Merk --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Kemasan :</label>
                                <div class="input-wrapper">
                                    <select id="customCatPackage" name="cat_id" class="select-gray-light">
                                        <option value="">-- Pilih Kemasan (Opsional) --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- 5. KERAMIK SECTION --}}
                        <div class="material-section" id="ceramicSection" data-material="ceramic" style="display: none;">
                            <h4 class="section-header">Keramik</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Keramik
                            </div>
                            <div class="form-group">
                                <label>Pilih :</label>
                                <div class="input-wrapper">
                                    <select name="ceramic_id" class="select-gray">
                                        <option value="">-- Semua Keramik (Auto) --</option>
                                        @if(isset($ceramics))
                                            @foreach($ceramics as $ceramic)
                                                <option value="{{ $ceramic->id }}">
                                                    {{ $ceramic->brand }} - {{ $ceramic->color }} ({{ $ceramic->dimension_length }}x{{ $ceramic->dimension_width }} cm)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- 6. NAT SECTION --}}
                        <div class="material-section" id="natSection" data-material="nat" style="display: none;">
                            <h4 class="section-header">Nat</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Nat
                            </div>
                            <div class="form-group">
                                <label>Pilih :</label>
                                <div class="input-wrapper">
                                    <select name="nat_id" class="select-gray">
                                        <option value="">-- Semua Nat (Auto) --</option>
                                        @if(isset($nats))
                                            @foreach($nats as $nat)
                                                <option value="{{ $nat->id }}">
                                                    {{ $nat->nat_name ?? $nat->brand }} ({{ $nat->package_weight_net }} kg)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="button-actions">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-search"></i> Hitung
                        </button>
                    </div>
                </div>

        </div>
    </form>
@endsection

<style>
    .calc-style {
        color: var(--text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        font-size: 32px;
    }

    .material-type-filter-group {
        margin-top: 16px;
        display: block;
    }

    .material-type-filter-item {
        align-items: flex-start;
        margin-bottom: 4px !important;
    }

    .material-type-filter-item > label {
        align-self: flex-start;
        padding-top: 0 !important;
    }

    .material-type-filter-item.has-extra-rows {
        margin-bottom: 8px !important;
    }

    .material-type-filter-item:last-child {
        margin-bottom: 0 !important;
    }

    .material-type-filter-item .material-type-rows {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    .material-type-rows {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .material-type-extra-rows {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .material-type-row {
        display: flex;
        align-items: stretch;
        width: 100%;
        gap: 0;
        position: relative;
    }

    .material-type-row::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 4px;
        pointer-events: none;
        box-shadow: 0 0 0 0 rgba(137, 19, 19, 0);
        transition: box-shadow 0.15s ease;
    }

    .material-type-row .input-wrapper {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
        margin-bottom: 0;
    }

    .material-type-row .work-type-input {
        border-radius: 4px 0 0 4px;
        border-right: 0 !important;
    }

    .material-type-row .work-type-autocomplete,
    .material-type-row .work-type-input,
    .material-type-row .autocomplete-input {
        width: 100%;
    }

    .material-type-row-actions {
        display: flex;
        align-items: stretch;
        gap: 0;
        margin-left: -1px;
        border: 1px solid #cbd5e1;
        border-left: none;
        border-radius: 0 4px 4px 0;
        background: #ffffff;
        overflow: hidden;
        flex: 0 0 auto;
    }

    .material-type-row-btn {
        border: 0;
        border-left: 1px solid #e2e8f0;
        background: transparent;
        color: #334155;
        width: 38px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }

    .material-type-row-actions .material-type-row-btn:first-child {
        border-left: 0;
    }

    .material-type-row-actions .material-type-row-btn-delete:not(.is-visible) + .material-type-row-btn-add {
        border-left: 0;
    }

    .material-type-row-btn:hover {
        background: #f1f5f9;
    }

    .material-type-row-btn-delete {
        color: #b91c1c;
        display: none;
    }

    .material-type-row-btn-add {
        color: #166534;
    }

    .material-type-row:focus-within .work-type-input:focus-within {
        box-shadow: none !important;
        transform: none !important;
    }

    .material-type-row:focus-within .work-type-input {
        border-color: #891313 !important;
        border-width: 1px !important;
        border-right-width: 0 !important;
        background-color: #fffbfb !important;
    }

    .material-type-row .work-type-input .autocomplete-input:focus {
        border: 0 !important;
        border-width: 0 !important;
        box-shadow: none !important;
        transform: none !important;
        background-color: transparent !important;
    }

    .material-type-row:focus-within .material-type-row-actions {
        border-color: #891313;
        box-shadow: none;
    }

    .material-type-row:focus-within::after {
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.12);
    }

    .material-type-row.has-multiple .material-type-row-btn-delete,
    .material-type-row-btn-delete.is-visible {
        display: inline-flex;
    }

    .tickbox-title-label {
        min-width: 100px; /* Sesuaikan lebar ini jika perlu */
        display: inline-block;
        color: #000; /* Pastikan judul tetap hitam */
    }
    
    /* CSS untuk warna abu-abu (jika tidak pakai Bootstrap, class text-muted bisa dihapus dan pakai ini) */
    .desc-text {
        color: #6c757d; /* Kode warna abu-abu */
    }
</style>

@push('scripts')
{{-- Load JS Asli --}}
<script type="application/json" id="materialCalculationFormData">
{!! json_encode([
    'formulaDescriptions' => $formulaDescriptions,
    'formulas' => $formulaOptions,
    'bricks' => $bricks,
    'cements' => $cements,
    'nats' => $nats ?? [],
    'sands' => $sands,
    'cats' => $cats ?? [],
    'ceramics' => $ceramics ?? [],
]) !!}
</script>
<script>
    const availableBestRecommendations = @json($bestRecommendations ?? []);
</script>
<script src="{{ asset('js/material-calculation-form.js') }}?v={{ @filemtime(public_path('js/material-calculation-form.js')) }}"></script>
<script>
    (function() {
        const dataScript = document.getElementById('materialCalculationFormData');
        const payload = dataScript ? JSON.parse(dataScript.textContent) : null;
        const formulaMaterials = {};
        if (payload && Array.isArray(payload.formulas)) {
            payload.formulas.forEach(formula => {
                if (formula && formula.code) {
                    formulaMaterials[formula.code] = Array.isArray(formula.materials) ? formula.materials : [];
                }
            });
        }
        if (typeof initMaterialCalculationForm === 'function') {
            initMaterialCalculationForm(document, payload);
        }

        function normalizeFilterToken(value) {
            return String(value ?? '').trim();
        }

        function uniqueFilterTokens(values) {
            const seen = new Set();
            const result = [];
            (values || []).forEach(value => {
                const rawToken = normalizeFilterToken(value);
                if (!rawToken) return;
                rawToken.split('|').forEach(part => {
                    const token = normalizeFilterToken(part);
                    if (!token || seen.has(token)) return;
                    seen.add(token);
                    result.push(token);
                });
            });
            return result;
        }

        function formatMaterialTypeSize(length, width) {
            const len = Number(length);
            const wid = Number(width);
            if (!Number.isFinite(len) || !Number.isFinite(wid) || len <= 0 || wid <= 0) return '';
            const minVal = Math.min(len, wid);
            const maxVal = Math.max(len, wid);
            return `${minVal.toString().replace('.', ',')} x ${maxVal.toString().replace('.', ',')}`;
        }

        function sortAlphabetic(values) {
            const list = Array.isArray(values) ? [...values] : [];
            try {
                const collator = new Intl.Collator('id-ID', { numeric: true, sensitivity: 'base' });
                return list.sort((a, b) => collator.compare(String(a ?? ''), String(b ?? '')));
            } catch (error) {
                return list.sort((a, b) => String(a ?? '').localeCompare(String(b ?? ''), 'id-ID'));
            }
        }

        function buildMaterialTypeOptionMap(formPayload) {
            const sourceMap = {
                brick: formPayload?.bricks || [],
                cement: formPayload?.cements || [],
                sand: formPayload?.sands || [],
                cat: formPayload?.cats || [],
                ceramic_type: formPayload?.ceramics || [],
                ceramic: formPayload?.ceramics || [],
                nat: formPayload?.nats || [],
            };

            const valueResolver = {
                brick: item => item?.type || '',
                cement: item => item?.type || '',
                sand: item => item?.type || '',
                cat: item => item?.type || '',
                ceramic_type: item => item?.type || '',
                ceramic: item => formatMaterialTypeSize(item?.dimension_length, item?.dimension_width),
                nat: item => item?.type || '',
            };

            const options = {};
            Object.keys(sourceMap).forEach(type => {
                const values = (sourceMap[type] || [])
                    .map(item => (valueResolver[type] ? valueResolver[type](item) : ''))
                    .filter(Boolean);
                options[type] = sortAlphabetic(uniqueFilterTokens(values));
            });
            return options;
        }

        function initMultiMaterialTypeFilters(formPayload) {
            const optionsByType = buildMaterialTypeOptionMap(formPayload);
            const itemElements = document.querySelectorAll('.material-type-filter-item[data-material-type]');
            const api = {
                setValues(type, values) {},
                clearHiddenRows() {},
                clearAll() {},
            };
            const typeControllers = {};
            let extraRowSequence = 0;

            function createActionButton(type, action) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `material-type-row-btn ${action === 'add' ? 'material-type-row-btn-add' : 'material-type-row-btn-delete'}`;
                btn.dataset.materialTypeAction = action;
                btn.dataset.materialType = type;
                btn.title = action === 'add' ? 'Tambah baris' : 'Hapus baris';
                btn.innerHTML = action === 'add'
                    ? '<i class="bi bi-plus-lg"></i>'
                    : '<i class="bi bi-trash"></i>';
                return btn;
            }

            function normalizeOption(value) {
                return String(value ?? '').trim().toLowerCase();
            }

            itemElements.forEach(itemEl => {
                const type = itemEl.dataset.materialType;
                const baseRow = itemEl.querySelector('.material-type-row-base');
                const baseDisplay = itemEl.querySelector(`#materialTypeDisplay-${type}`);
                const baseHidden = itemEl.querySelector(`#materialTypeSelector-${type}`);
                const baseList = itemEl.querySelector(`#materialType-list-${type}`);
                const extraRowsContainer = itemEl.querySelector('.material-type-extra-rows');
                const baseDeleteBtn = baseRow?.querySelector('[data-material-type-action="remove"]');
                const baseAddBtn = baseRow?.querySelector('[data-material-type-action="add"]');
                const basePlaceholder = baseDisplay?.getAttribute('placeholder') || 'Pilih atau ketik...';
                const options = optionsByType[type] || [];
                let isSyncing = false;

                if (
                    !type ||
                    !baseRow ||
                    !baseDisplay ||
                    !baseHidden ||
                    !baseList ||
                    !extraRowsContainer ||
                    !baseAddBtn ||
                    !baseDeleteBtn
                ) {
                    return;
                }

                baseHidden.dataset.materialTypeHidden = '1';

                const updateRowButtons = () => {
                    const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
                    const hasExtra = extraRows.length > 0;
                    baseRow.classList.toggle('has-multiple', hasExtra);
                    itemEl.classList.toggle('has-extra-rows', hasExtra);
                    baseDeleteBtn.classList.toggle('is-visible', hasExtra);
                    extraRows.forEach(row => {
                        const deleteBtn = row.querySelector('[data-material-type-action="remove"]');
                        if (deleteBtn) {
                            deleteBtn.classList.toggle('is-visible', true);
                        }
                    });
                };

                const getRowStates = () => {
                    const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
                    return rows.map(row => row.__materialTypeRowState).filter(Boolean);
                };

                const getHiddenInputs = () => getRowStates().map(row => row.hiddenEl).filter(Boolean);

                const getAvailableOptions = (term = '', currentHiddenEl = null, includeCurrentSelection = false) => {
                    const query = normalizeOption(term);
                    const selectedSet = new Set();
                    getHiddenInputs().forEach(hiddenEl => {
                        if (!hiddenEl) return;
                        if (includeCurrentSelection && hiddenEl === currentHiddenEl) return;
                        const normalized = normalizeOption(hiddenEl.value);
                        if (normalized) {
                            selectedSet.add(normalized);
                        }
                    });
                    const available = options.filter(option => {
                        const normalized = normalizeOption(option);
                        if (!normalized || selectedSet.has(normalized)) return false;
                        if (!query) return true;
                        return normalized.includes(query);
                    });
                    return sortAlphabetic(available);
                };

                const refreshOpenLists = () => {
                    getRowStates().forEach(rowState => {
                        if (rowState.listEl && rowState.listEl.style.display === 'block') {
                            rowState.renderList(rowState.displayEl.value || '');
                        }
                    });
                };

                const enforceUniqueSelection = () => {
                    if (isSyncing) return;
                    isSyncing = true;
                    try {
                        const seen = new Set();
                        getRowStates().forEach(rowState => {
                            const currentValue = String(rowState.hiddenEl.value || '').trim();
                            const normalized = normalizeOption(currentValue);
                            if (!normalized) return;

                            if (seen.has(normalized)) {
                                rowState.displayEl.value = '';
                                rowState.hiddenEl.value = '';
                                rowState.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                                return;
                            }
                            seen.add(normalized);
                        });
                    } finally {
                        isSyncing = false;
                    }
                };

                const syncRows = () => {
                    enforceUniqueSelection();
                    refreshOpenLists();
                };

                const setupAutocomplete = rowState => {
                    const { rowEl, displayEl, hiddenEl, listEl } = rowState;

                    const closeList = () => {
                        listEl.style.display = 'none';
                    };

                    const applySelection = optionValue => {
                        const finalValue = String(optionValue || '').trim();
                        displayEl.value = finalValue;
                        if (hiddenEl.value !== finalValue) {
                            hiddenEl.value = finalValue;
                            hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            syncRows();
                        }
                        closeList();
                    };

                    const renderList = (term = '') => {
                        listEl.innerHTML = '';

                        const emptyItem = document.createElement('div');
                        emptyItem.className = 'autocomplete-item';
                        emptyItem.textContent = '- Tidak Pilih -';
                        emptyItem.addEventListener('click', function() {
                            applySelection('');
                        });
                        listEl.appendChild(emptyItem);

                        getAvailableOptions(term, hiddenEl).forEach(option => {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            item.textContent = option;
                            item.addEventListener('click', function() {
                                applySelection(option);
                            });
                            listEl.appendChild(item);
                        });

                        listEl.style.display = 'block';
                    };

                    const findExactAvailableOption = term => {
                        const query = normalizeOption(term);
                        if (!query) return null;
                        // Allow the current row's selected value to remain valid while typing.
                        const available = getAvailableOptions(term, hiddenEl, true);
                        return available.find(option => normalizeOption(option) === query) || null;
                    };

                    rowState.closeList = closeList;
                    rowState.renderList = renderList;
                    rowEl.__materialTypeRowState = rowState;

                    displayEl.addEventListener('focus', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        // On focus, show full available options (not filtered by current selected value).
                        renderList('');
                    });

                    displayEl.addEventListener('input', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        const term = this.value || '';
                        renderList(term);

                        if (!term.trim()) {
                            if (hiddenEl.value) {
                                hiddenEl.value = '';
                                hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                            } else {
                                syncRows();
                            }
                            return;
                        }

                        const exactMatch = findExactAvailableOption(term);
                        if (exactMatch) {
                            if (hiddenEl.value !== exactMatch) {
                                hiddenEl.value = exactMatch;
                                hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                            } else {
                                syncRows();
                            }
                        } else if (hiddenEl.value) {
                            hiddenEl.value = '';
                            hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            syncRows();
                        }
                    });

                    displayEl.addEventListener('keydown', function(event) {
                        if (event.key !== 'Enter') return;
                        const exactMatch = findExactAvailableOption(displayEl.value || '');
                        if (exactMatch) {
                            applySelection(exactMatch);
                            event.preventDefault();
                        }
                    });

                    displayEl.addEventListener('blur', function() {
                        setTimeout(closeList, 150);
                    });

                    document.addEventListener('click', function(event) {
                        if (event.target === displayEl || listEl.contains(event.target)) return;
                        closeList();
                    });

                    hiddenEl.addEventListener('change', function() {
                        if (displayEl.value !== hiddenEl.value) {
                            displayEl.value = hiddenEl.value;
                        }
                        if (!isSyncing) {
                            syncRows();
                        }
                    });

                    if (options.length === 0) {
                        displayEl.disabled = true;
                        displayEl.placeholder = `Tidak ada data untuk ${type}`;
                    }
                };

                const buildExtraRow = (value = '') => {
                    const rowEl = document.createElement('div');
                    rowEl.className = 'material-type-row material-type-row-extra';
                    rowEl.dataset.materialType = type;

                    const inputWrapperEl = document.createElement('div');
                    inputWrapperEl.className = 'input-wrapper';
                    const autocompleteEl = document.createElement('div');
                    autocompleteEl.className = 'work-type-autocomplete';
                    const inputShellEl = document.createElement('div');
                    inputShellEl.className = 'work-type-input';

                    const displayEl = document.createElement('input');
                    displayEl.type = 'text';
                    displayEl.className = 'autocomplete-input';
                    displayEl.placeholder = basePlaceholder;
                    displayEl.autocomplete = 'off';
                    displayEl.value = String(value || '');

                    const listEl = document.createElement('div');
                    listEl.className = 'autocomplete-list';
                    listEl.id = `materialType-list-${type}-extra-${++extraRowSequence}`;

                    const hiddenEl = document.createElement('input');
                    hiddenEl.type = 'hidden';
                    hiddenEl.name = `material_type_filters_extra[${type}][]`;
                    hiddenEl.value = String(value || '');
                    hiddenEl.dataset.materialTypeHidden = '1';
                    hiddenEl.dataset.materialTypeExtra = '1';

                    inputShellEl.appendChild(displayEl);
                    autocompleteEl.appendChild(inputShellEl);
                    autocompleteEl.appendChild(listEl);
                    inputWrapperEl.appendChild(autocompleteEl);
                    inputWrapperEl.appendChild(hiddenEl);

                    const actionEl = document.createElement('div');
                    actionEl.className = 'material-type-row-actions';
                    const deleteBtn = createActionButton(type, 'remove');
                    const addBtn = createActionButton(type, 'add');
                    actionEl.appendChild(deleteBtn);
                    actionEl.appendChild(addBtn);

                    rowEl.appendChild(inputWrapperEl);
                    rowEl.appendChild(actionEl);
                    extraRowsContainer.appendChild(rowEl);
                    updateRowButtons();

                    setupAutocomplete({
                        rowEl,
                        displayEl,
                        hiddenEl,
                        listEl,
                        renderList() {},
                        closeList() {},
                    });
                    syncRows();

                    deleteBtn.addEventListener('click', function() {
                        rowEl.remove();
                        updateRowButtons();
                        syncRows();
                    });
                    addBtn.addEventListener('click', function() {
                        buildExtraRow('');
                    });

                    return rowEl;
                };

                const removeBaseRow = () => {
                    const extraRows = Array.from(extraRowsContainer.querySelectorAll('.material-type-row-extra'));

                    // If there are extra rows, promote the first extra row value to base
                    // so deleting on base behaves like deleting the clicked (first) row.
                    if (extraRows.length > 0) {
                        const firstExtraRow = extraRows[0];
                        const firstState = firstExtraRow.__materialTypeRowState;
                        const promotedValue = String(
                            firstState?.hiddenEl?.value ??
                            firstState?.displayEl?.value ??
                            '',
                        ).trim();

                        baseDisplay.value = promotedValue;
                        if (baseHidden.value !== promotedValue) {
                            baseHidden.value = promotedValue;
                            baseHidden.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        firstExtraRow.remove();
                        updateRowButtons();
                        syncRows();
                        return;
                    }

                    // If this is the only row, clear its value.
                    if (baseDisplay.value || baseHidden.value) {
                        baseDisplay.value = '';
                        baseHidden.value = '';
                        baseHidden.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        syncRows();
                    }
                };

                baseAddBtn.addEventListener('click', function() {
                    buildExtraRow('');
                });

                baseDeleteBtn.addEventListener('click', function() {
                    removeBaseRow();
                });

                setupAutocomplete({
                    rowEl: baseRow,
                    displayEl: baseDisplay,
                    hiddenEl: baseHidden,
                    listEl: baseList,
                    renderList() {},
                    closeList() {},
                });

                itemEl.__setMaterialTypeValues = function(values) {
                    const tokens = uniqueFilterTokens(Array.isArray(values) ? values : [values]);
                    while (extraRowsContainer.firstChild) {
                        extraRowsContainer.removeChild(extraRowsContainer.firstChild);
                    }

                    const first = tokens[0] || '';
                    baseDisplay.value = first;
                    baseHidden.value = first;
                    baseHidden.dispatchEvent(new Event('change', { bubbles: true }));

                    tokens.slice(1).forEach(token => buildExtraRow(token));
                    updateRowButtons();
                    syncRows();
                };

                itemEl.__clearExtraRows = function() {
                    while (extraRowsContainer.firstChild) {
                        extraRowsContainer.removeChild(extraRowsContainer.firstChild);
                    }
                    updateRowButtons();
                    syncRows();
                };

                typeControllers[type] = {
                    setValues: itemEl.__setMaterialTypeValues,
                    clearExtraRows: itemEl.__clearExtraRows,
                };

                updateRowButtons();
                syncRows();
            });

            api.setValues = function(type, values) {
                const controller = typeControllers[type];
                if (!controller || typeof controller.setValues !== 'function') return;
                controller.setValues(values);
            };

            api.clearHiddenRows = function() {
                itemElements.forEach(itemEl => {
                    if (itemEl.style.display !== 'none') return;
                    const type = itemEl.dataset.materialType;
                    const controller = typeControllers[type];
                    if (controller && typeof controller.clearExtraRows === 'function') {
                        controller.clearExtraRows();
                    }
                });
            };

            api.clearAll = function() {
                itemElements.forEach(itemEl => {
                    if (typeof itemEl.__setMaterialTypeValues === 'function') {
                        itemEl.__setMaterialTypeValues([]);
                    }
                });
            };

            return api;
        }

        const materialTypeFilterMultiApi = initMultiMaterialTypeFilters(payload);
        const initialMaterialTypeFilters = @json($selectedMaterialTypeFilters);
        if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.setValues === 'function' && initialMaterialTypeFilters) {
            Object.entries(initialMaterialTypeFilters).forEach(([type, value]) => {
                const values = Array.isArray(value) ? value : [value];
                materialTypeFilterMultiApi.setValues(type, values);
            });
        }

        function initCeramicTypeFilterAutocomplete() {
            const displayEl = document.getElementById('ceramicTypeDisplay');
            const hiddenEl = document.getElementById('ceramicTypeSelector');
            const listEl = document.getElementById('ceramicType-list');
            const options = sortAlphabetic(uniqueFilterTokens(@json(isset($ceramicTypes) ? $ceramicTypes->values()->all() : [])));

            const emptyApi = {
                clear() {},
            };

            if (!displayEl || !hiddenEl || !listEl) {
                return emptyApi;
            }

            const normalizeOption = value => String(value ?? '').trim().toLowerCase();

            const closeList = () => {
                listEl.style.display = 'none';
            };

            const applySelection = optionValue => {
                const finalValue = String(optionValue || '').trim();
                displayEl.value = finalValue;
                if (hiddenEl.value !== finalValue) {
                    hiddenEl.value = finalValue;
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                closeList();
            };

            const getAvailableOptions = term => {
                const query = normalizeOption(term);
                const selected = normalizeOption(hiddenEl.value);
                const available = options.filter(option => {
                    const normalized = normalizeOption(option);
                    if (!normalized) return false;
                    if (selected && normalized === selected) return false;
                    if (!query) return true;
                    return normalized.includes(query);
                });
                return sortAlphabetic(available);
            };

            const renderList = (term = '') => {
                listEl.innerHTML = '';

                const emptyItem = document.createElement('div');
                emptyItem.className = 'autocomplete-item';
                emptyItem.textContent = '- Tidak Pilih -';
                emptyItem.addEventListener('click', function() {
                    applySelection('');
                });
                listEl.appendChild(emptyItem);

                getAvailableOptions(term).forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = option;
                    item.addEventListener('click', function() {
                        applySelection(option);
                    });
                    listEl.appendChild(item);
                });

                listEl.style.display = 'block';
            };

            const findExactOption = term => {
                const query = normalizeOption(term);
                if (!query) return null;
                return options.find(option => normalizeOption(option) === query) || null;
            };

            displayEl.addEventListener('focus', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                renderList('');
            });

            displayEl.addEventListener('input', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                const term = this.value || '';
                renderList(term);

                if (!term.trim()) {
                    if (hiddenEl.value) {
                        hiddenEl.value = '';
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                const exactMatch = findExactOption(term);
                if (exactMatch) {
                    if (hiddenEl.value !== exactMatch) {
                        hiddenEl.value = exactMatch;
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else if (hiddenEl.value) {
                    hiddenEl.value = '';
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            displayEl.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                const exactMatch = findExactOption(displayEl.value || '');
                if (exactMatch) {
                    applySelection(exactMatch);
                    event.preventDefault();
                }
            });

            displayEl.addEventListener('blur', function() {
                setTimeout(closeList, 150);
            });

            document.addEventListener('click', function(event) {
                if (event.target === displayEl || listEl.contains(event.target)) return;
                closeList();
            });

            hiddenEl.addEventListener('change', function() {
                if (displayEl.value !== hiddenEl.value) {
                    displayEl.value = hiddenEl.value;
                }
            });

            if (options.length === 0) {
                displayEl.disabled = true;
                displayEl.placeholder = 'Tidak ada data jenis keramik';
            }

            return {
                clear() {
                    applySelection('');
                },
            };
        }

        const ceramicTypeFilterApi = initCeramicTypeFilterAutocomplete();

        // Handle filter checkboxes (multiple selection)
        const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
        const customForm = document.getElementById('customMaterialForm');
        const filterAll = document.getElementById('filter_all');
        const filterCustom = document.getElementById('filter_custom');

        // Function to toggle custom form visibility
        function toggleCustomForm() {
            if (filterCustom && filterCustom.checked) {
                customForm.style.display = 'block';
            } else {
                customForm.style.display = 'none';
            }
        }

        // Function to handle "Semua" checkbox
        function shouldIncludeBest() {
            const selectedWorkType = workTypeSelector ? workTypeSelector.value : null;
            return selectedWorkType && Array.isArray(availableBestRecommendations)
                ? availableBestRecommendations.includes(selectedWorkType)
                : false;
        }

        function handleAllCheckbox() {
            if (filterAll) {
                if (filterAll.checked) {
                    const includeBest = shouldIncludeBest();
                    // Check all other checkboxes except custom, best only if available
                    filterCheckboxes.forEach(checkbox => {
                        if (checkbox === filterAll) return;

                        if (checkbox.value === 'custom') {
                            checkbox.checked = false;
                            return;
                        }

                        if (checkbox.value === 'best') {
                            checkbox.checked = includeBest;
                            return;
                        }

                        checkbox.checked = true;
                    });
                } else {
                    // Uncheck ALL checkboxes
                    filterCheckboxes.forEach(checkbox => {
                        if (checkbox === filterAll) return;
                        checkbox.checked = false;
                    });
                }
            }
        }

        // Function to uncheck "Semua" if any other checkbox is unchecked
        function handleOtherCheckboxes() {
            const includeBest = shouldIncludeBest();
            const allOthersChecked = Array.from(filterCheckboxes).every(checkbox => {
                if (checkbox === filterAll) return true;
                if (checkbox === filterCustom) return true;
                if (checkbox.value === 'best' && !includeBest) return true;
                return checkbox.checked;
            });

            if (filterAll && !allOthersChecked) {
                filterAll.checked = false;
            }
        }

        // Initialize form visibility on page load
        toggleCustomForm();

        // Handle Work Type Change for Layer Inputs (Rollag), Plaster Sides, and Skim Sides
        const workTypeSelector = document.getElementById('workTypeSelector');
        const layerCountGroup = document.getElementById('layerCountGroup');
        const plasterSidesGroup = document.getElementById('plasterSidesGroup');
        const skimSidesGroup = document.getElementById('skimSidesGroup');
        const groutThicknessGroup = document.getElementById('groutThicknessGroup');
        const ceramicLengthGroup = document.getElementById('ceramicLengthGroup');
        const ceramicWidthGroup = document.getElementById('ceramicWidthGroup');
        const ceramicThicknessGroup = document.getElementById('ceramicThicknessGroup');
        const wallHeightLabel = document.getElementById('wallHeightLabel');
        const wallHeightGroup = document.getElementById('wallHeightGroup');
        const wallHeightInput = document.getElementById('wallHeight');
        const ceramicFilterSection = document.getElementById('ceramicFilterSection');
        const mortarThicknessInput = document.getElementById('mortarThickness');
        const mortarThicknessUnit = document.getElementById('mortarThicknessUnit');
        const mortarThicknessLabel = document.getElementById('mortarThicknessLabel');
        const wallHeightDefaultDisplay = wallHeightGroup ? getComputedStyle(wallHeightGroup).display : 'flex';

        function formatFixedPlain(value, decimals = 2) {
            const num = Number(value);
            if (!isFinite(num)) return '';
            if (num === 0) return '0';

            const absValue = Math.abs(num);
            const epsilon = Math.min(absValue * 1e-12, 1e-6);
            const adjusted = num + (num >= 0 ? epsilon : -epsilon);
            const sign = adjusted < 0 ? '-' : '';
            const abs = Math.abs(adjusted);
            const intPart = Math.trunc(abs);

            if (intPart > 0) {
                const scaled = Math.trunc(abs * 100);
                const intDisplay = Math.trunc(scaled / 100).toString();
                let decPart = String(scaled % 100).padStart(2, '0');
                decPart = decPart.replace(/0+$/, '');
                return decPart ? `${sign}${intDisplay}.${decPart}` : `${sign}${intDisplay}`;
            }

            let fraction = abs;
            let digits = '';
            let firstNonZeroIndex = null;
            const maxDigits = 30;

            for (let i = 0; i < maxDigits; i++) {
                fraction *= 10;
                const digit = Math.floor(fraction + 1e-12);
                fraction -= digit;
                digits += String(digit);

                if (digit !== 0 && firstNonZeroIndex === null) {
                    firstNonZeroIndex = i;
                }

                if (firstNonZeroIndex !== null && i >= firstNonZeroIndex + 1) {
                    break;
                }
            }

            digits = digits.replace(/0+$/, '');
            if (!digits) return '0';
            return `${sign}0.${digits}`;
        }

        function formatThicknessValue(value) {
            return formatFixedPlain(value, 2);
        }

        function setMortarThicknessUnit(unit) {
            if (!mortarThicknessInput || !mortarThicknessUnit) return;

            const currentUnit = mortarThicknessInput.dataset.unit || 'cm';
            if (unit !== currentUnit) {
                const currentValue = parseFloat(mortarThicknessInput.value);
                if (!isNaN(currentValue)) {
                    const converted = unit === 'mm' ? currentValue * 10 : currentValue / 10;
                    mortarThicknessInput.value = formatThicknessValue(converted);
                }
            }

            mortarThicknessInput.dataset.unit = unit;
            mortarThicknessUnit.textContent = unit;
            if (unit === 'mm') {
                mortarThicknessInput.step = '1';
                mortarThicknessInput.min = '1';
            } else {
                mortarThicknessInput.step = '0.1';
                mortarThicknessInput.min = '0.1';
            }
        }

        function setWallHeightVisibility(isVisible) {
            if (!wallHeightGroup) return;
            wallHeightGroup.style.display = isVisible ? wallHeightDefaultDisplay : 'none';
            if (wallHeightInput) {
                wallHeightInput.required = isVisible;
                wallHeightInput.disabled = !isVisible;
            }
        }

        function toggleLayerInputs() {
            const mortarThicknessGroup = document.getElementById('mortarThicknessGroup');
            const layerCountLabel = document.getElementById('layerCountLabel');
            const layerCountUnit = document.getElementById('layerCountUnit');
            const layerCountInputWrapper = document.getElementById('layerCountInputWrapper');
            const isRollag = workTypeSelector && workTypeSelector.value === 'brick_rollag';
            const isAciWall = workTypeSelector && workTypeSelector.value === 'skim_coating';
            const isAciFloor = workTypeSelector && workTypeSelector.value === 'coating_floor';
            const isAci = isAciWall || isAciFloor;
            const nextMortarMode = isAci ? 'acian' : 'adukan';
            const prevMortarMode = mortarThicknessInput ? (mortarThicknessInput.dataset.mode || 'adukan') : 'adukan';
            const mortarModeChanged = prevMortarMode !== nextMortarMode;

            setWallHeightVisibility(!isRollag);

            if (wallHeightLabel) {
                wallHeightLabel.textContent = 'Tinggi';
            }

            if (mortarThicknessLabel) {
                mortarThicknessLabel.textContent = isAci ? 'Tebal Acian' : 'Tebal Adukan';
            }
            if (mortarThicknessInput) {
                mortarThicknessInput.dataset.mode = nextMortarMode;
            }

            if (workTypeSelector && layerCountGroup && plasterSidesGroup && skimSidesGroup) {
                if (workTypeSelector.value === 'brick_rollag') {
                    layerCountGroup.style.display = 'flex';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';

                    // Update label and unit for Rollag
                    if (layerCountLabel) layerCountLabel.textContent = 'Tingkat';
                    if (layerCountUnit) {
                        layerCountUnit.textContent = 'Tingkat';
                        layerCountUnit.style.backgroundColor = '#fef3c7';
                    }
                    if (layerCountInputWrapper) {
                        layerCountInputWrapper.style.backgroundColor = '#fffbeb';
                        layerCountInputWrapper.style.borderColor = '#fcd34d';
                    }
                } else if (workTypeSelector.value === 'wall_plastering') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'flex';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    // Restore label to "Tinggi" for Plastering
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'skim_coating') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'flex';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('mm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(3);
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    // Restore label to "Tinggi" for Skim Coating
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'painting') {
                    layerCountGroup.style.display = 'flex';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'none';
                    if (mortarModeChanged && mortarThicknessInput) {
                        setMortarThicknessUnit('cm');
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';

                    // Update label and unit for Painting
                    if (layerCountLabel) layerCountLabel.textContent = 'Lapis';
                    if (layerCountUnit) {
                        layerCountUnit.textContent = 'Lapis';
                        layerCountUnit.style.backgroundColor = '#dbeafe';
                    }
                    if (layerCountInputWrapper) {
                        layerCountInputWrapper.style.backgroundColor = '#f0f9ff';
                        layerCountInputWrapper.style.borderColor = '#38bdf8';
                    }

                    // Restore label to "Tinggi" for Painting
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'floor_screed' || workTypeSelector.value === 'coating_floor') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    if (workTypeSelector.value === 'coating_floor') {
                        setMortarThicknessUnit('mm');
                        if (mortarModeChanged && mortarThicknessInput) {
                            mortarThicknessInput.value = formatThicknessValue(3);
                        }
                    } else {
                        setMortarThicknessUnit('cm');
                        if (mortarModeChanged && mortarThicknessInput) {
                            mortarThicknessInput.value = formatThicknessValue(2);
                        }
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';

                    // Change label to "Lebar" for Floor types
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else if (workTypeSelector.value === 'tile_installation') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'flex';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'block';
                    // Change label to "Lebar" for tile installation
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else if (workTypeSelector.value === 'grout_tile') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'flex';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'none';
                    if (mortarModeChanged && mortarThicknessInput) {
                        setMortarThicknessUnit('cm');
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }

                    // Show ceramic dimension inputs for grout_tile
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'flex';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'flex';
                    if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'flex';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';

                    // Change label to "Lebar" for grout tile
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    // Restore label to "Tinggi" for other formulas
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                }
            }

            if (ceramicFilterSection && workTypeSelector) {
                const showCeramicFilters = workTypeSelector.value === 'tile_installation';
                ceramicFilterSection.style.display = showCeramicFilters ? 'block' : 'none';
                if (!showCeramicFilters) {
                    if (ceramicTypeFilterApi && typeof ceramicTypeFilterApi.clear === 'function') {
                        ceramicTypeFilterApi.clear();
                    }
                }
            }
        }

        // Handle work type changes - simplified version
        let handleWorkTypeChange;

        if (workTypeSelector) {
            handleWorkTypeChange = function() {
                const selectedWorkType = workTypeSelector.value;

                // Update "Preferensi" filter state based on availability
                const filterBest = document.getElementById('filter_best');
                if (filterBest) {
                    if (availableBestRecommendations.includes(selectedWorkType)) {
                        filterBest.checked = true;
                    } else {
                        filterBest.checked = false;
                    }
                }

                // Toggle special inputs (layer count, plaster sides, skim sides)
                toggleLayerInputs();

                // Show/hide material sections based on formula materials in custom form
                setTimeout(() => {
                    const sections = document.querySelectorAll('#customMaterialForm .material-section');
                    const requiredMaterials = Array.isArray(formulaMaterials[selectedWorkType]) && formulaMaterials[selectedWorkType].length > 0
                        ? formulaMaterials[selectedWorkType]
                        : ['brick', 'cement', 'sand'];

                    sections.forEach(section => {
                        const materialKey = section.dataset.material;
                        if (!materialKey) return;
                        section.style.display = requiredMaterials.includes(materialKey) ? 'block' : 'none';
                    });
                    if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.clearHiddenRows === 'function') {
                        materialTypeFilterMultiApi.clearHiddenRows();
                    }
                }, 100);
            };

            workTypeSelector.addEventListener('change', handleWorkTypeChange);

            // Run on init
            handleWorkTypeChange();
        }

        // Add event listeners
        if (filterAll) {
            filterAll.addEventListener('change', function() {
                handleAllCheckbox();
                toggleCustomForm();
            });
        }

        filterCheckboxes.forEach(checkbox => {
            if (checkbox !== filterAll) {
                checkbox.addEventListener('change', function() {
                    handleOtherCheckboxes();
                    toggleCustomForm();
                });
            }
        });

        // Initialize on page load if work type is selected
        if (workTypeSelector && workTypeSelector.value) {
            handleWorkTypeChange();
        }

        // Loading State Handler with Real Progress Simulation
        const form = document.getElementById('calculationForm');
        let loadingInterval = null;
        const calcSessionKey = 'materialCalculationSession';
        let saveSessionTimer = null;
        const resetButton = document.getElementById('btnResetForm');

        if (resetButton) {
            resetButton.addEventListener('click', function() {
                if (!form) return;

                form.reset();
                localStorage.removeItem(calcSessionKey);

                const workTypeDisplay = document.getElementById('workTypeDisplay');
                const workTypeHidden = document.getElementById('workTypeSelector');
                if (workTypeDisplay) {
                    workTypeDisplay.value = '';
                    workTypeDisplay.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (workTypeHidden) {
                    workTypeHidden.value = '';
                    workTypeHidden.dispatchEvent(new Event('change', { bubbles: true }));
                }
                const materialTypeInputs = document.querySelectorAll('[name^="material_type_filters["]');
                materialTypeInputs.forEach(input => {
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.clearAll === 'function') {
                    materialTypeFilterMultiApi.clearAll();
                }
                if (ceramicTypeFilterApi && typeof ceramicTypeFilterApi.clear === 'function') {
                    ceramicTypeFilterApi.clear();
                }

                toggleCustomForm();
                if (typeof handleWorkTypeChange === 'function') {
                    handleWorkTypeChange();
                }
            });
        }

        function serializeCalculationSession(formEl) {
            if (!formEl) return null;
            const data = {};
            const formData = new FormData(formEl);
            formData.forEach((value, key) => {
                const normalizedKey = key.endsWith('[]') ? key.slice(0, -2) : key;
                if (normalizedKey === '_token' || normalizedKey === 'confirm_save') {
                    return;
                }
                if (data[normalizedKey] === undefined) {
                    data[normalizedKey] = value;
                } else if (Array.isArray(data[normalizedKey])) {
                    data[normalizedKey].push(value);
                } else {
                    data[normalizedKey] = [data[normalizedKey], value];
                }
            });
            return data;
        }

        function saveCalculationSession(payload) {
            if (!form) return;
            const sessionPayload = payload || serializeCalculationSession(form);
            if (!sessionPayload) return;
            try {
                localStorage.setItem(calcSessionKey, JSON.stringify({
                    updatedAt: Date.now(),
                    data: sessionPayload,
                    autoSubmit: false,
                }));
            } catch (error) {
                console.warn('Failed to save calculation session', error);
            }
        }

        function normalizeSessionPayload(value) {
            if (Array.isArray(value)) {
                const normalizedList = value.map(normalizeSessionPayload);
                normalizedList.sort();
                return normalizedList;
            }
            if (value && typeof value === 'object') {
                const normalized = {};
                Object.keys(value).sort().forEach(key => {
                    normalized[key] = normalizeSessionPayload(value[key]);
                });
                return normalized;
            }
            return value;
        }

        function buildSessionFingerprint(payload) {
            return JSON.stringify(normalizeSessionPayload(payload));
        }

        function isSameAsLastSession(currentPayload) {
            const raw = localStorage.getItem(calcSessionKey);
            if (!raw) return false;
            try {
                const parsed = JSON.parse(raw);
                if (!parsed || typeof parsed !== 'object' || !parsed.data) return false;
                return buildSessionFingerprint(parsed.data) === buildSessionFingerprint(currentPayload);
            } catch (error) {
                return false;
            }
        }

        function shouldRestoreCalculationSession() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('resume') !== '1') return false;
            params.delete('resume');
            params.delete('auto_submit');
            return !params.toString();
        }

        function applyCalculationSession(state) {
            if (!form || !state || typeof state !== 'object') return;

            const workTypeInput = form.querySelector('#workTypeSelector');
            const workTypeValue = state.work_type_select || state.work_type || '';
            const expectsMm = ['skim_coating', 'coating_floor'].includes(workTypeValue);
            let pendingMortarThickness = null;
            if (workTypeInput && workTypeValue) {
                workTypeInput.value = workTypeValue;
                workTypeInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(field => {
                field.checked = false;
            });

            Object.entries(state).forEach(([key, value]) => {
                if (key === 'work_type_select' || key === 'work_type') return;
                
                // Handle nested material_type_filters object (from JSON state)
                if (key === 'material_type_filters' && typeof value === 'object' && value !== null) {
                    Object.entries(value).forEach(([subKey, subValue]) => {
                        const values = Array.isArray(subValue) ? subValue : [subValue];
                        if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.setValues === 'function') {
                            materialTypeFilterMultiApi.setValues(subKey, values);
                            return;
                        }
                        const fieldName = `material_type_filters[${subKey}]`;
                        const fields = form.querySelectorAll(`[name="${fieldName}"]`);
                        if (!fields.length) return;
                        const typeValue = values[0] ?? '';
                        fields.forEach(field => {
                            field.value = typeValue;
                            field.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    });
                    return;
                }

                if (key.startsWith('material_type_filters[')) {
                    const fields = form.querySelectorAll(`[name="${key}"]`);
                    if (!fields.length) return;
                    const typeValue = Array.isArray(value) ? value[0] : value;
                    fields.forEach(field => {
                        field.value = typeValue;
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    return;
                }
                if (key.startsWith('material_type_filters_extra[')) {
                    if (!materialTypeFilterMultiApi || typeof materialTypeFilterMultiApi.setValues !== 'function') {
                        return;
                    }
                    const match = key.match(/^material_type_filters_extra\[(.+?)\]$/);
                    if (!match || !match[1]) {
                        return;
                    }
                    const materialKey = match[1];
                    const existingMain = form.querySelector(`[name="material_type_filters[${materialKey}]"]`);
                    const mergedValues = [];
                    if (existingMain && existingMain.value) {
                        mergedValues.push(existingMain.value);
                    }
                    const extraValues = Array.isArray(value) ? value : [value];
                    mergedValues.push(...extraValues);
                    materialTypeFilterMultiApi.setValues(materialKey, mergedValues);
                    return;
                }
                if (key === 'mortar_thickness' && expectsMm) {
                    pendingMortarThickness = value;
                    return;
                }
                const selector = `[name="${key}"], [name="${key}[]"]`;
                const fields = form.querySelectorAll(selector);
                if (!fields.length) return;

                const values = Array.isArray(value) ? value.map(String) : [String(value)];
                fields.forEach(field => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = values.includes(String(field.value));
                    } else if (field.multiple && field.options) {
                        Array.from(field.options).forEach(option => {
                            option.selected = values.includes(String(option.value));
                        });
                    } else {
                        field.value = values[0];
                    }
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    if (field.type !== 'checkbox' && field.type !== 'radio') {
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            });

            if (expectsMm && pendingMortarThickness !== null && mortarThicknessInput) {
                const cmValue = Array.isArray(pendingMortarThickness)
                    ? parseFloat(pendingMortarThickness[0])
                    : parseFloat(pendingMortarThickness);
                if (!isNaN(cmValue)) {
                    mortarThicknessInput.value = formatThicknessValue(cmValue * 10);
                    mortarThicknessInput.dispatchEvent(new Event('change', { bubbles: true }));
                    mortarThicknessInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }

        function restoreCalculationSession() {
            if (!form || !shouldRestoreCalculationSession()) return;
            const params = new URLSearchParams(window.location.search);
            const autoSubmitRequested = params.get('auto_submit') === '1';
            const raw = localStorage.getItem(calcSessionKey);
            if (!raw) return;

            let parsed = null;
            try {
                parsed = JSON.parse(raw);
            } catch (error) {
                localStorage.removeItem(calcSessionKey);
                return;
            }

            const isNormalized = parsed && typeof parsed === 'object' && parsed.normalized === true;
            const state = parsed && typeof parsed === 'object' && parsed.data ? parsed.data : parsed;
            applyCalculationSession(state);

            if (isNormalized && mortarThicknessInput) {
                const currentUnit = mortarThicknessInput.dataset.unit || 'cm';
                if (currentUnit === 'mm' && state && state.mortar_thickness !== undefined && state.mortar_thickness !== null && state.mortar_thickness !== '') {
                    const cmValue = parseFloat(state.mortar_thickness);
                    if (!isNaN(cmValue)) {
                        mortarThicknessInput.value = formatThicknessValue(cmValue * 10);
                        mortarThicknessInput.dispatchEvent(new Event('input', { bubbles: true }));
                        mortarThicknessInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }

            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('resume');
            cleanUrl.searchParams.delete('auto_submit');
            window.history.replaceState({}, '', cleanUrl.pathname + cleanUrl.search);

            if (autoSubmitRequested) {
                setTimeout(() => {
                    if (!form.checkValidity()) return;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }, 50);
            }
        }

        // Function to Reset UI
        function resetLoadingState() {
            // Hide overlay
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'none';
            
            // Stop Interval
            if (loadingInterval) {
                clearInterval(loadingInterval);
                loadingInterval = null;
            }

            // Reset Button
            const btn = form ? form.querySelector('button[type="submit"]') : null;
            if (btn) {
                btn.disabled = false;
                const originalText = btn.getAttribute('data-original-text');
                if (originalText) {
                    btn.innerHTML = originalText;
                } else {
                    btn.innerHTML = '<i class="bi bi-search"></i> Hitung';
                }
            }
            
            // Reset Progress Bar Elements
            const bar = document.getElementById('loadingProgressBar');
            const percent = document.getElementById('loadingPercent');
            const title = document.getElementById('loadingTitle');
            const subtitle = document.getElementById('loadingSubtitle');
            
            if (bar) bar.style.width = '0%';
            if (percent) percent.textContent = '0%';
            if (title) title.textContent = 'Memulai Perhitungan...';
            if (subtitle) subtitle.textContent = 'Mohon tunggu, kami sedang menyiapkan data Anda.';
        }

        // Handle Back/Forward Navigation
        window.addEventListener('pageshow', function(event) {
            resetLoadingState();
        });

        // Handle Cancel Button
        const cancelBtn = document.getElementById('cancelCalculation');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                // Stop browser navigation/request
                if (window.stop) {
                    window.stop();
                } else if (document.execCommand) {
                    document.execCommand('Stop'); // Fallback for older IE
                }
                
                resetLoadingState();
            });
        }

        if (form) {
            form.addEventListener('input', function() {
                if (saveSessionTimer) clearTimeout(saveSessionTimer);
                saveSessionTimer = setTimeout(saveCalculationSession, 250);
            });

            form.addEventListener('change', function() {
                if (saveSessionTimer) clearTimeout(saveSessionTimer);
                saveSessionTimer = setTimeout(saveCalculationSession, 250);
            });

            form.addEventListener('submit', function(e) {
                // Client-side validation for work type selection
                const workTypeHidden = document.getElementById('workTypeSelector');
                if (!workTypeHidden || !workTypeHidden.value) {
                    e.preventDefault();
                    if (typeof window.showToast === 'function') {
                        window.showToast('Harap pilih Item Pekerjaan dari daftar yang tersedia. Klik atau ketik untuk melihat pilihan.', 'error');
                    } else {
                        alert('Harap pilih Item Pekerjaan dari daftar yang tersedia.');
                    }
                    // Scroll to work type field
                    const workTypeGroup = document.querySelector('.work-type-group');
                    if (workTypeGroup) {
                        workTypeGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    // Focus on the work type input
                    const workTypeDisplay = document.getElementById('workTypeDisplay');
                    if (workTypeDisplay) {
                        workTypeDisplay.focus();
                    }
                    return;
                }

                // Client-side validation for price filters
                const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
                const isAnyFilterChecked = Array.from(filterCheckboxes).some(cb => cb.checked);

                if (!isAnyFilterChecked) {
                    e.preventDefault();
                    if (typeof window.showToast === 'function') {
                        window.showToast('Harap pilih minimal satu filter harga (contoh: Preferensi, Ekonomis).', 'error');
                    } else {
                        alert('Harap pilih minimal satu filter harga.');
                    }
                    // Scroll to filter section
                    const filterSection = document.querySelector('.filter-section');
                    if (filterSection) {
                        filterSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }

                if (this.checkValidity()) {
                    const currentSession = serializeCalculationSession(form);
                    const isFastCachePath = currentSession ? isSameAsLastSession(currentSession) : false;
                    saveCalculationSession(currentSession);
                    if (mortarThicknessInput && mortarThicknessInput.dataset.unit === 'mm') {
                        const mmValue = parseFloat(mortarThicknessInput.value);
                        if (!isNaN(mmValue)) {
                            mortarThicknessInput.value = formatThicknessValue(mmValue / 10);
                        }
                        mortarThicknessInput.dataset.unit = 'cm';
                        if (mortarThicknessUnit) {
                            mortarThicknessUnit.textContent = 'cm';
                        }
                        mortarThicknessInput.step = '0.1';
                        mortarThicknessInput.min = '0.1';
                    }

                    // Save original button text if not saved
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && !btn.getAttribute('data-original-text')) {
                         btn.setAttribute('data-original-text', btn.innerHTML);
                    }

                    // Show Overlay
                    document.getElementById('loadingOverlay').style.display = 'flex';
                    
                    // Update Button State
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
                    }

                    // Progress Simulation Data
                    const bar = document.getElementById('loadingProgressBar');
                    const title = document.getElementById('loadingTitle');
                    const subtitle = document.getElementById('loadingSubtitle');
                    const percent = document.getElementById('loadingPercent');
                    
                    let progress = 0;
                    const messages = isFastCachePath ? [
                        { p: 10, t: 'Memuat hasil tersimpan...', s: 'Mengambil data perhitungan sebelumnya.' },
                        { p: 55, t: 'Menyiapkan tampilan...', s: 'Merapikan tabel dan ringkasan hasil.' },
                        { p: 90, t: 'Finalisasi...', s: 'Sedang mengalihkan ke halaman hasil...' },
                    ] : [
                        { p: 5, t: 'Menganalisis Permintaan...', s: 'Memvalidasi input dan preferensi filter.' },
                        { p: 20, t: 'Mengambil Data Material...', s: 'Memuat database harga bata, semen, dan pasir terbaru.' },
                        { p: 40, t: 'Menjalankan Algoritma...', s: 'Menghitung volume dan kebutuhan material presisi.' },
                        { p: 60, t: 'Komparasi Harga...', s: 'Membandingkan efisiensi biaya antar merek material.' },
                        { p: 80, t: 'Menyusun Laporan...', s: 'Membuat ringkasan rekomendasi terbaik untuk Anda.' },
                        { p: 95, t: 'Finalisasi...', s: 'Sedang mengalihkan ke halaman hasil...' }
                    ];

                    // Clear previous interval if any
                    if (loadingInterval) clearInterval(loadingInterval);

                    // Start Animation Loop
                    const intervalMs = isFastCachePath ? 35 : 50;
                    loadingInterval = setInterval(() => {
                        // REVISED LOGIC: Aggressive start for "Realtime" feel
                        let increment = 0;
                        
                        // Phase 1: Rapid Acceleration (0-60% in ~0.8s)
                        if (progress < 60) {
                            increment = isFastCachePath
                                ? Math.random() * 6 + 4
                                : Math.random() * 4 + 1;
                        } 
                        // Phase 2: Moderate Pace (60-85% in ~1s)
                        else if (progress < 85) {
                            increment = isFastCachePath
                                ? Math.random() * 2.5 + 0.5
                                : Math.random() * 1.5 + 0.2;
                        } 
                        // Phase 3: Zeno's Paradox (85-98%) - crawl to wait for server
                        else if (progress < 98) {
                            increment = isFastCachePath ? 0.12 : 0.05;
                        }
                        
                        progress = Math.min(progress + increment, 98); // Cap at 98, jump to 100 on unload
                        
                        const percentText = (() => {
                            const scaled = Math.floor(progress * 100);
                            const intPart = Math.floor(scaled / 100);
                            const decPart = (scaled % 100).toString().padStart(2, '0');
                            return `${intPart}.${decPart}%`;
                        })();

                        // Update UI
                        bar.style.width = `${progress}%`;
                        percent.textContent = percentText;

                        // Update Text
                        let currentMsg = null;
                        for (let i = messages.length - 1; i >= 0; i--) {
                            if (progress >= messages[i].p) {
                                currentMsg = messages[i];
                                break;
                            }
                        }

                        if (currentMsg && title.textContent !== currentMsg.t) {
                            title.textContent = currentMsg.t;
                            subtitle.textContent = currentMsg.s;
                        }

                    }, intervalMs);
                }
            });
        }
        
        // VISUAL TRICK: Force 100% when browser starts navigation (server responded)
        window.addEventListener('beforeunload', function() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay && overlay.style.display !== 'none') {
                // If overlay is visible, it means we are in a calculation that just finished
                const bar = document.getElementById('loadingProgressBar');
                const percent = document.getElementById('loadingPercent');
                const title = document.getElementById('loadingTitle');
                const subtitle = document.getElementById('loadingSubtitle');
                
                if (bar) {
                    bar.style.width = '100%';
                    bar.classList.remove('progress-bar-animated'); // Stop stripe animation
                }
                if (percent) percent.textContent = '100.00%';
                if (title) title.textContent = 'Selesai!';
                if (subtitle) subtitle.textContent = 'Memuat hasil perhitungan...';
            }
        });

        restoreCalculationSession();
    })();
</script>
@endpush
