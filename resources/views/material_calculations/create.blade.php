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
    $selectedWorkAreas = old('work_areas', request('work_areas', []));
    $selectedWorkAreas = is_array($selectedWorkAreas) ? $selectedWorkAreas : [$selectedWorkAreas];
    $selectedWorkAreas = array_values(array_filter(array_map(static fn($value) => trim((string) $value), $selectedWorkAreas), static fn($value) => $value !== ''));
    $selectedWorkFields = old('work_fields', request('work_fields', []));
    $selectedWorkFields = is_array($selectedWorkFields) ? $selectedWorkFields : [$selectedWorkFields];
    $selectedWorkFields = array_values(array_filter(array_map(static fn($value) => trim((string) $value), $selectedWorkFields), static fn($value) => $value !== ''));
    $selectedPriceFilters = old('price_filters', ['best']);
    $selectedPriceFilters = is_array($selectedPriceFilters) ? $selectedPriceFilters : [$selectedPriceFilters];
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
    $carryOverBrickId = old('brick_id', request('brick_id'));
    $isSingleCarryOver = !empty($carryOverBrickId);
    $singleBrick = $isSingleCarryOver ? $bricks->find($carryOverBrickId) : null;

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

<h3 class="calc-style"><i class="bi bi-calculator text-primary"></i> Hitung Item Pekerjaan Proyek</h3>

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
                <div class="form-group project-location-group">
                    <label>Lokasi Proyek</label>
                    <div class="input-wrapper project-location-input-wrapper">
                        <input type="text"
                               id="projectLocationSearch"
                               class="autocomplete-input"
                               data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                               placeholder="Cari alamat proyek di Google Maps..."
                               autocomplete="off"
                               value="{{ old('project_address') }}">
                        <input type="hidden" id="projectAddress" name="project_address" value="{{ old('project_address') }}">
                        <input type="hidden" id="projectLatitude" name="project_latitude" value="{{ old('project_latitude') }}">
                        <input type="hidden" id="projectLongitude" name="project_longitude" value="{{ old('project_longitude') }}">
                        <input type="hidden" id="projectPlaceId" name="project_place_id" value="{{ old('project_place_id') }}">
                    </div>
                </div>

                <div class="project-map-group">
                    <div class="project-map-content">
                        <div id="projectLocationMap"
                             data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                             data-store-marker-icon="{{ asset('images/store-marker.svg') }}"
                             class="project-location-map"></div>
                        <small class="text-muted d-block mb-0">Pilih alamat proyek dari Google Maps, lalu sesuaikan pin jika diperlukan. Marker ikon toko menampilkan lokasi toko tersimpan.</small>
                    </div>
                </div>

                <div id="storeSearchModeBox">
                    <label>Mode Pencarian Toko</label>
                    <input type="hidden" name="use_store_filter" value="0">
                    <input type="hidden" name="allow_mixed_store" value="0">
                    <div class="ssm-row">
                        <input type="checkbox" id="storeFilterCheck" name="use_store_filter" value="1"
                            {{ old('use_store_filter', '1') == '1' ? 'checked' : '' }}>
                        <label for="storeFilterCheck" class="ssm-label">Prioritas Radius</label>
                        <small class="ssm-desc">Hitung berdasarkan toko terdekat yang radius layanannya menjangkau lokasi proyek.</small>
                    </div>
                    <div class="ssm-row">
                        <input type="checkbox" id="mixedStoreCheck" name="allow_mixed_store" value="1"
                            {{ old('allow_mixed_store') == '1' ? 'checked' : '' }}>
                        <label for="mixedStoreCheck" class="ssm-label">Lintas Toko</label>
                        <small class="ssm-desc">Jika satu toko tidak lengkap, material diambil berurutan dari toko terdekat berikutnya.</small>
                    </div>
                </div>

                {{-- FILTER CHECKBOX (MULTIPLE SELECTION) --}}
                <div class="filter-section">
                    <label class="filter-section-title">+ Filter by:</label>
                    <div class="filter-tickbox-list">
                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_all" value="all" {{ in_array('all', $selectedPriceFilters, true) ? 'checked' : '' }}>
                        <label for="filter_all">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Semua</b>
                                <span class="text-muted">: Menampilkan semua kombinasi harga</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item position-relative">
                        <input type="checkbox" name="price_filters[]" id="filter_best" value="best" {{ in_array('best', $selectedPriceFilters, true) ? 'checked' : '' }}>
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
                        <input type="checkbox" name="price_filters[]" id="filter_common" value="common" {{ in_array('common', $selectedPriceFilters, true) ? 'checked' : '' }}>
                        <label for="filter_common">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Populer</b>
                                <span class="text-muted">: 3 Kombinasi yang paling sering digunakan Customer</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_cheapest" value="cheapest" {{ in_array('cheapest', $selectedPriceFilters, true) ? 'checked' : '' }}>
                        <label for="filter_cheapest">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Ekonomis</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga paling murah</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_medium" value="medium" {{ in_array('medium', $selectedPriceFilters, true) ? 'checked' : '' }}>
                        <label for="filter_medium">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Average</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga rata-rata</span>
                            </span>
                        </label>
                    </div>

                    <div class="tickbox-item">
                        <input type="checkbox" name="price_filters[]" id="filter_expensive" value="expensive" {{ in_array('expensive', $selectedPriceFilters, true) ? 'checked' : '' }}>
                        <label for="filter_expensive">
                            <span class="tickbox-title d-flex">
                                <b class="tickbox-title-label flex-shrink-0">Termahal</b>
                                <span class="text-muted">: 3 kombinasi dengan total harga paling mahal</span>
                            </span>
                        </label>
                    </div>

                </div>

                {{-- LEGACY CUSTOM FORM (hidden, used for data binding) --}}
                    <div id="customMaterialForm" style="display:none; margin-top:16px;">

                        {{-- 1. BATA SECTION --}}
                        <div class="material-section" data-material="brick">
                            <h4 class="section-header">Bata</h4>

                            @php
                                $selectedBrickId = (string) (old('brick_id') ?? ($singleBrick?->id ?? ''));
                            @endphp

                            @if($isMultiBrick)
                                {{-- TAMPILAN MULTI BATA --}}
                                <div class="alert alert-info border-primary py-2">
                                    <strong><i class="bi bi-collection-fill me-2"></i>{{ $selectedBricks->count() }} Bata Terpilih</strong>
                                    <div class="text-muted small mt-1">Akan dibuat perbandingan untuk semua bata ini. Customize tetap tersedia untuk memfilter daftar bata.</div>
                                    @foreach($selectedBricks as $b)
                                        <input type="hidden" name="brick_ids[]" value="{{ $b->id }}">
                                    @endforeach
                                </div>
                            @endif

                            @if($isSingleCarryOver && $singleBrick)
                                {{-- INFO SINGLE BATA CARRY OVER --}}
                                <div class="form-group">
                                    <label>Carry Over :</label>
                                    <div class="input-wrapper">
                                        <input type="text" value="{{ $singleBrick->brand }} - {{ $singleBrick->type }}" readonly style="background-color:#d1fae5; font-weight:bold;">
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select name="brick_id" id="customBrick" class="form-select select-green">
                                        <option value="">-- Semua Bata (Auto) --</option>
                                        @foreach($bricks as $brick)
                                            <option value="{{ $brick->id }}" {{ $selectedBrickId === (string) $brick->id ? 'selected' : '' }}>
                                                {{ $brick->brand }} - {{ $brick->type }} ({{ $brick->dimension_length }}x{{ $brick->dimension_width }}x{{ $brick->dimension_height }} cm) - @currency($brick->price_per_piece)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="alert alert-info py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan untuk menampilkan kombinasi dari beberapa bata
                            </div>
                        </div>

                        {{-- 2. SEMEN SECTION --}}
                        <div class="material-section" data-material="cement">
                            <h4 class="section-header">Semen</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Semen
                            </div>
                            <div class="form-group">
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select id="customCement" name="cement_id" class="select-orange">
                                        <option value="">-- Semua Semen (Auto) --</option>
                                        @foreach($cements as $cement)
                                            <option value="{{ $cement->id }}" {{ (string) old('cement_id') === (string) $cement->id ? 'selected' : '' }}>
                                                {{ $cement->brand }}{{ $cement->sub_brand ? ' - ' . $cement->sub_brand : '' }}{{ $cement->code ? ' - ' . $cement->code : '' }}{{ $cement->color ? ' - ' . $cement->color : '' }} ({{ $cement->package_unit ?? '-' }}, {{ $cement->package_weight_net ?? 0 }} kg)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- 3. PASIR SECTION --}}
                        <div class="material-section" data-material="sand">
                            <h4 class="section-header">Pasir</h4>
                            <div class="alert alert-warning py-1 px-2 mb-2" style="font-size:12px;">
                                <i class="bi bi-info-circle"></i> Kosongkan pilihan untuk melihat semua kombinasi Pasir
                            </div>
                            <div class="form-group">
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select id="customSand" name="sand_id" class="select-gray">
                                        <option value="">-- Semua Pasir (Auto) --</option>
                                        @foreach($sands as $sand)
                                            <option value="{{ $sand->id }}" {{ (string) old('sand_id') === (string) $sand->id ? 'selected' : '' }}>
                                                {{ $sand->brand }} ({{ $sand->package_unit ?? '-' }}, {{ $sand->package_volume ?? 0 }} M3)
                                            </option>
                                        @endforeach
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
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select id="customCat" name="cat_id" class="select-gray">
                                        <option value="">-- Semua Cat (Auto) --</option>
                                        @if(isset($cats))
                                            @foreach($cats as $cat)
                                                <option value="{{ $cat->id }}" {{ (string) old('cat_id') === (string) $cat->id ? 'selected' : '' }}>
                                                    {{ $cat->brand }}{{ $cat->sub_brand ? ' - ' . $cat->sub_brand : '' }}{{ $cat->color_code ? ' - ' . $cat->color_code : '' }}{{ $cat->color_name ? ' - ' . $cat->color_name : '' }} ({{ $cat->package_unit ?? '-' }}, {{ $cat->volume ?? 0 }} {{ $cat->volume_unit ?? '' }}, {{ $cat->package_weight_net ?? 0 }} kg)
                                                </option>
                                            @endforeach
                                        @endif
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
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select id="customCeramic" name="ceramic_id" class="select-gray">
                                        <option value="">-- Semua Keramik (Auto) --</option>
                                        @if(isset($ceramics))
                                            @foreach($ceramics as $ceramic)
                                                <option value="{{ $ceramic->id }}" {{ (string) old('ceramic_id') === (string) $ceramic->id ? 'selected' : '' }}>
                                                    {{ $ceramic->brand }}{{ $ceramic->sub_brand ? ' - ' . $ceramic->sub_brand : '' }}{{ $ceramic->surface ? ' - ' . $ceramic->surface : '' }}{{ $ceramic->code ? ' - ' . $ceramic->code : '' }}{{ $ceramic->color ? ' - ' . $ceramic->color : '' }} ({{ $ceramic->dimension_length }}x{{ $ceramic->dimension_width }} cm)
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
                                <label>Material :</label>
                                <div class="input-wrapper">
                                    <select id="customNat" name="nat_id" class="select-gray">
                                        <option value="">-- Semua Nat (Auto) --</option>
                                        @if(isset($nats))
                                            @foreach($nats as $nat)
                                                <option value="{{ $nat->id }}" {{ (string) old('nat_id') === (string) $nat->id ? 'selected' : '' }}>
                                                    {{ $nat->brand }}{{ $nat->sub_brand ? ' - ' . $nat->sub_brand : '' }}{{ $nat->code ? ' - ' . $nat->code : '' }}{{ $nat->color ? ' - ' . $nat->color : '' }} ({{ $nat->package_unit ?? '-' }}, {{ $nat->package_weight_net ?? 0 }} kg)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="button" id="btnResetForm" class="btn-cancel" style="padding: 5px 20px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                </button>
            </div>
            </div> {{-- /.left-column --}}


            {{-- RIGHT COLUMN: FILTERS --}}
            <div class="right-column">
                <div class="taxonomy-tree-main taxonomy-group-card">
                    <div class="taxonomy-node taxonomy-node-area">
                        <div class="form-group work-area-group taxonomy-card taxonomy-card-area taxonomy-inline-group">
                            <label>Area</label>
                            <div class="material-type-filter-body">
                                <div class="material-type-rows" id="workAreaRows" data-taxonomy-kind="area" data-initial-values='@json($selectedWorkAreas)'>
                                    <div class="material-type-row material-type-row-base" data-taxonomy-kind="area">
                                        <div class="input-wrapper">
                                            <div class="work-type-autocomplete">
                                                <div class="work-type-input">
                                                    <input type="text"
                                                           id="workAreaDisplay"
                                                           class="autocomplete-input"
                                                           placeholder="Pilih atau ketik area..."
                                                           autocomplete="off"
                                                           value="{{ $selectedWorkAreas[0] ?? '' }}"
                                                           data-taxonomy-display="1">
                                                </div>
                                                <div class="autocomplete-list" id="workArea-list"></div>
                                            </div>
                                            <input type="hidden"
                                                   id="workAreaValue"
                                                   name="work_areas[]"
                                                   value="{{ $selectedWorkAreas[0] ?? '' }}"
                                                   data-taxonomy-hidden="1">
                                        </div>
                                        <div class="material-type-row-actions">
                                            <button type="button" class="material-type-row-btn material-type-row-btn-delete" data-taxonomy-action="remove" data-taxonomy-kind="area" title="Hapus baris">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <button type="button" class="material-type-row-btn material-type-row-btn-add" data-taxonomy-action="add" data-taxonomy-kind="area" title="Tambah baris">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="taxonomy-level-actions">
                                <button type="button" id="addFieldFromMainBtn" class="taxonomy-level-btn" title="Bidang">
                                    + Bidang
                                </button>
                            </div>
                        </div>

                        <div class="taxonomy-node-children">
                            <div class="taxonomy-node taxonomy-node-field">
                                <div class="form-group work-field-group taxonomy-card taxonomy-card-field taxonomy-inline-group">
                                    <label>Bidang</label>
                                    <div class="material-type-filter-body">
                                        <div class="material-type-rows" id="workFieldRows" data-taxonomy-kind="field" data-initial-values='@json($selectedWorkFields)'>
                                            <div class="material-type-row material-type-row-base" data-taxonomy-kind="field">
                                                <div class="input-wrapper">
                                                    <div class="work-type-autocomplete">
                                                        <div class="work-type-input">
                                                            <input type="text"
                                                                   id="workFieldDisplay"
                                                                   class="autocomplete-input"
                                                                   placeholder="Pilih atau ketik bidang..."
                                                                   autocomplete="off"
                                                                   value="{{ $selectedWorkFields[0] ?? '' }}"
                                                                   data-taxonomy-display="1">
                                                        </div>
                                                        <div class="autocomplete-list" id="workField-list"></div>
                                                    </div>
                                                    <input type="hidden"
                                                           id="workFieldValue"
                                                           name="work_fields[]"
                                                           value="{{ $selectedWorkFields[0] ?? '' }}"
                                                           data-taxonomy-hidden="1">
                                                </div>
                                                <div class="material-type-row-actions">
                                                    <button type="button" class="material-type-row-btn material-type-row-btn-delete" data-taxonomy-action="remove" data-taxonomy-kind="field" title="Hapus baris">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <button type="button" class="material-type-row-btn material-type-row-btn-add" data-taxonomy-action="add" data-taxonomy-kind="field" title="Tambah baris">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="taxonomy-level-actions">
                                        <button type="button" id="addItemFromMainBtn" class="taxonomy-level-btn" title="Tambah Item Pekerjaan di Bidang ini">
                                            + Item Pekerjaan
                                        </button>
                                    </div>
                                </div>

                                <div class="taxonomy-node-children">
                                    <div class="taxonomy-node taxonomy-node-item">
                                        {{-- WORK TYPE --}}
                                        <div class="form-group work-type-group taxonomy-card taxonomy-card-item taxonomy-inline-item">
                                            <label id="mainWorkTypeLabel">Item Pekerjaan</label>
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
                                                <input type="hidden" id="enableBundleMode" name="enable_bundle_mode"
                                                    value="{{ old('enable_bundle_mode', 0) }}">
                                                <input type="hidden" id="workItemsPayload" name="work_items_payload"
                                                    value="{{ old('work_items_payload') }}">
                                                <input type="hidden" id="materialCustomizeFiltersPayload" name="material_customize_filters_payload"
                                                    value="{{ old('material_customize_filters_payload') }}">
                                            </div>
                                        </div>

                                        <div id="inputFormContainer">
                    <div id="brickForm" class="work-type-form">

                        {{-- DIMENSI - VERTICAL LAYOUT --}}
                        <div class="dimensions-container-vertical">
                            <div class="dimension-item">
                                <label>Panjang</label>
                                <div class="input-with-unit">
                                    <input type="text" inputmode="decimal" name="wall_length" id="wallLength" step="0.01" min="0.01"
                                        value="{{ old('wall_length', request('wall_length')) }}" required>
                                    <span class="unit">M</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="wallHeightGroup">
                                <label id="wallHeightLabel">Tinggi</label>
                                <div class="input-with-unit">
                                    <input type="text" inputmode="decimal" name="wall_height" id="wallHeight" step="0.01" min="0.01"
                                        value="{{ old('wall_height', request('wall_height')) }}" required>
                                    <span class="unit">M</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="mortarThicknessGroup">
                                <label id="mortarThicknessLabel">Tebal Adukan</label>
                                <div class="input-with-unit">
                                    <input type="text" inputmode="decimal" name="mortar_thickness" id="mortarThickness" step="0.1" min="0.1" data-unit="cm"
                                        value="{{ old('mortar_thickness', request('mortar_thickness', 2)) }}">
                                    <span class="unit" id="mortarThicknessUnit">cm</span>
                                </div>
                            </div>

                            {{-- INPUT TINGKAT UNTUK ROLLAG / LAPIS UNTUK PENGECATAN --}}
                            <div class="dimension-item" id="layerCountGroup" style="display: none;">
                                <label id="layerCountLabel">Tingkat</label>
                                <div class="input-with-unit" id="layerCountInputWrapper" style="background-color: #fffbeb; border-color: #fcd34d;">
                                    <input type="text" inputmode="decimal" name="layer_count" id="layerCount" step="1" min="1" value="{{ old('layer_count', request('layer_count') ?? 1) }}">
                                    <span class="unit" id="layerCountUnit" style="background-color: #fef3c7;">Lapis</span>
                                </div>
                            </div>

                            {{-- INPUT SISI PLESTERAN UNTUK WALL PLASTERING --}}
                            <div class="dimension-item" id="plasterSidesGroup" style="display: none;">
                                <label>Sisi Plesteran</label>
                                <div class="input-with-unit" style="background-color: #e0f2fe; border-color: #7dd3fc;">
                                    <input type="text" inputmode="decimal" name="plaster_sides" id="plasterSides" step="1" min="1" value="{{ old('plaster_sides', request('plaster_sides') ?? 1) }}">
                                    <span class="unit" style="background-color: #bae6fd;">Sisi</span>
                                </div>
                            </div>

                            {{-- INPUT SISI ACI UNTUK SKIM COATING --}}
                            <div class="dimension-item" id="skimSidesGroup" style="display: none;">
                                <label>Sisi Acian</label>
                                <div class="input-with-unit" style="background-color: #e0e7ff; border-color: #a5b4fc;">
                                    <input type="text" inputmode="decimal" name="skim_sides" id="skimSides" step="1" min="1" value="{{ old('skim_sides', request('skim_sides') ?? 1) }}">
                                    <span class="unit" style="background-color: #c7d2fe;">Sisi</span>
                                </div>
                            </div>

                            {{-- INPUT TEBAL NAT UNTUK TILE INSTALLATION & GROUT ONLY --}}
                            <div class="dimension-item" id="groutThicknessGroup" style="display: none;">
                                <label>Tebal Nat</label>
                                <div class="input-with-unit" style="background-color: #f1f5f9; border-color: #cbd5e1;">
                                    <input type="text" inputmode="decimal" name="grout_thickness" id="groutThickness" step="0.1" min="0.1" value="{{ old('grout_thickness', request('grout_thickness', 2)) }}">
                                    <span class="unit" style="background-color: #e2e8f0;">mm</span>
                                </div>
                            </div>

                            {{-- INPUT UKURAN KERAMIK UNTUK GROUT TILE --}}
                            <div class="dimension-item" id="ceramicLengthGroup" style="display: none;">
                                <label>Panjang Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="text" inputmode="decimal" name="ceramic_length" id="ceramicLength" step="0.1" min="1" value="{{ old('ceramic_length', request('ceramic_length', 30)) }}">
                                    <span class="unit" style="background-color: #fef08a;">cm</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="ceramicWidthGroup" style="display: none;">
                                <label>Lebar Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="text" inputmode="decimal" name="ceramic_width" id="ceramicWidth" step="0.1" min="1" value="{{ old('ceramic_width', request('ceramic_width', 30)) }}">
                                    <span class="unit" style="background-color: #fef08a;">cm</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="ceramicThicknessGroup" style="display: none;">
                                <label>Tebal Keramik</label>
                                <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                    <input type="text" inputmode="decimal" name="ceramic_thickness" id="ceramicThickness" step="0.1" min="0.1" value="{{ old('ceramic_thickness', request('ceramic_thickness', 8)) }}">
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
                                    <div class="material-type-filter-body">
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
                                            @if(in_array($materialKey, ['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'], true))
                                                <button type="button"
                                                    class="material-type-row-btn material-type-row-btn-customize"
                                                    data-customize-toggle="{{ $materialKey }}"
                                                    data-customize-panel-id="customizePanel-{{ $materialKey }}"
                                                    title="Custom {{ $materialLabel }}">
                                                    Custom
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    @if($materialKey === 'brick')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-brick" data-customize-panel="brick" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeBrickBrand" data-customize-filter="brick" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Dimensi :</label>
                                                    <div class="input-wrapper"><select id="customizeBrickDimension" data-customize-filter="brick" data-filter-key="dimension" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($materialKey === 'cement')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-cement" data-customize-panel="cement" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCementBrand" data-customize-filter="cement" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sub Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCementSubBrand" data-customize-filter="cement" data-filter-key="sub_brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode :</label>
                                                    <div class="input-wrapper"><select id="customizeCementCode" data-customize-filter="cement" data-filter-key="code" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Warna :</label>
                                                    <div class="input-wrapper"><select id="customizeCementColor" data-customize-filter="cement" data-filter-key="color" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kemasan :</label>
                                                    <div class="input-wrapper"><select id="customizeCementPackage" data-customize-filter="cement" data-filter-key="package_unit" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Berat Bersih :</label>
                                                    <div class="input-wrapper"><select id="customizeCementWeight" data-customize-filter="cement" data-filter-key="package_weight_net" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($materialKey === 'sand')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-sand" data-customize-panel="sand" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeSandBrand" data-customize-filter="sand" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($materialKey === 'cat')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-cat" data-customize-panel="cat" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCatBrand" data-customize-filter="cat" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sub Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCatSubBrand" data-customize-filter="cat" data-filter-key="sub_brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode :</label>
                                                    <div class="input-wrapper"><select id="customizeCatCode" data-customize-filter="cat" data-filter-key="color_code" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Warna :</label>
                                                    <div class="input-wrapper"><select id="customizeCatColor" data-customize-filter="cat" data-filter-key="color_name" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kemasan :</label>
                                                    <div class="input-wrapper"><select id="customizeCatPackage" data-customize-filter="cat" data-filter-key="package_unit" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Volume :</label>
                                                    <div class="input-wrapper"><select id="customizeCatVolume" data-customize-filter="cat" data-filter-key="volume_display" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Berat Bersih :</label>
                                                    <div class="input-wrapper"><select id="customizeCatWeight" data-customize-filter="cat" data-filter-key="package_weight_net" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($materialKey === 'ceramic')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-ceramic" data-customize-panel="ceramic" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicBrand" data-customize-filter="ceramic" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Dimensi :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicDimension" data-customize-filter="ceramic" data-filter-key="dimension" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sub Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicSubBrand" data-customize-filter="ceramic" data-filter-key="sub_brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Permukaan :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicSurface" data-customize-filter="ceramic" data-filter-key="surface" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode Pembakaran :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicCode" data-customize-filter="ceramic" data-filter-key="code" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Corak :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicPattern" data-customize-filter="ceramic" data-filter-key="color" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif($materialKey === 'nat')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-nat" data-customize-panel="nat" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeNatBrand" data-customize-filter="nat" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sub Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeNatSubBrand" data-customize-filter="nat" data-filter-key="sub_brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode :</label>
                                                    <div class="input-wrapper"><select id="customizeNatCode" data-customize-filter="nat" data-filter-key="code" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Warna :</label>
                                                    <div class="input-wrapper"><select id="customizeNatColor" data-customize-filter="nat" data-filter-key="color" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kemasan :</label>
                                                    <div class="input-wrapper"><select id="customizeNatPackage" data-customize-filter="nat" data-filter-key="package_unit" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Berat Bersih :</label>
                                                    <div class="input-wrapper"><select id="customizeNatWeight" data-customize-filter="nat" data-filter-key="package_weight_net" class="select-gray"></select></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="material-type-extra-rows" data-material-type="{{ $materialKey }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                </div>

                <div id="additionalWorkItemsSection" class="additional-work-items-section" style="display: none;">
                    <div id="additionalWorkItemsList" class="additional-work-items-list"></div>
                </div>

                <div class="form-group work-area-group work-area-extra-group" id="workAreaExtraSection" hidden>
                    <label>Area</label>
                    <div class="material-type-filter-body">
                        <div class="material-type-extra-rows" id="workAreaExtraRows"></div>
                    </div>
                </div>

                <div class="form-group work-field-group work-field-extra-group" id="workFieldExtraSection" hidden>
                    <label>Bidang</label>
                    <div class="material-type-filter-body">
                        <div class="material-type-extra-rows" id="workFieldExtraRows"></div>
                    </div>
                </div>

                <div class="work-item-bottom-bar work-item-bottom-bar-outside">
                    <div class="work-item-stepper" aria-label="Kontrol item pekerjaan">
                        <button type="button" id="addWorkItemBtn" class="work-item-stepper-btn" title="Tambah Area Baru" aria-label="Tambah Area Baru">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <span class="work-item-stepper-label">Area Baru</span>
                    </div>
                    <div class="button-actions">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-search"></i> Hitung
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
@endsection

<style>
    @media (min-width: 769px) {
        #calculationForm .two-column-layout {
            grid-template-columns: minmax(0, 55fr) minmax(0, 45fr) !important;
        }
    }

    .calc-style {
        color: var(--text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        font-size: 32px;
    }

    #calculationForm .right-column {
        --work-taxonomy-indent-step: 18px;
        --work-item-parameter-indent: 125px;
        --work-item-parameter-indent-mobile: 10px;
    }

    #calculationForm .taxonomy-tree-main {
        display: block;
        margin-bottom: 8px;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card {
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        padding: 10px;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card > .taxonomy-node > .taxonomy-card {
        margin-top: 0;
    }

    #calculationForm .taxonomy-node {
        display: block;
    }

    #calculationForm .taxonomy-node-children {
        margin-left: var(--work-taxonomy-indent-step);
        padding-left: 12px;
        border-left: 1px dashed #cbd5e1;
    }

    /* Level 1: Bidang menjorong 15px dari Area */
    #calculationForm .taxonomy-node-area > .taxonomy-node-children {
        margin-left: 15px;
    }

    /* Level 2: Item Pekerjaan menjorong ke kanan, start sejajar akhir teks "Bidang" */
    #calculationForm .taxonomy-node-field > .taxonomy-node-children {
        margin-left: 44px;
        padding-left: 10px;
        border-left: 1px dashed #cbd5e1;
    }

    #calculationForm .taxonomy-card {
        display: block;
        background: #ffffff;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        padding: 10px 10px 8px 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        margin-bottom: 10px;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card {
        background: transparent;
        border: 0;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
    }

    #calculationForm .taxonomy-card > label {
        display: block;
        width: auto !important;
        margin-bottom: 6px;
        font-weight: 700;
        color: #334155;
    }

    #calculationForm .taxonomy-card .input-wrapper,
    #calculationForm .taxonomy-card .material-type-filter-body,
    #calculationForm .taxonomy-card .work-type-autocomplete {
        width: 100%;
    }

    #calculationForm .taxonomy-card-area {
        border-left: 4px solid #334155;
    }

    #calculationForm .taxonomy-card-field {
        border-left: 4px solid #f59e0b;
    }

    #calculationForm .taxonomy-card-item {
        border-left: 4px solid #10b981;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-item {
        border-left: 0;
    }

    #calculationForm .taxonomy-inline-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #calculationForm .taxonomy-inline-group > label {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        flex: 0 0 72px;
        width: 72px !important;
        margin-bottom: 0;
        padding-top: 0 !important;
        white-space: nowrap;
    }

    #calculationForm .taxonomy-inline-group .material-type-filter-body {
        flex: 1 1 auto;
        min-width: 0;
        width: auto;
    }

    #calculationForm .taxonomy-inline-group .taxonomy-level-actions {
        margin-top: 0;
        margin-left: 2px;
        flex: 0 0 auto;
    }

    #calculationForm .taxonomy-inline-group .taxonomy-level-btn {
        min-height: 38px;
        white-space: nowrap;
    }

    #calculationForm .taxonomy-inline-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #calculationForm .taxonomy-inline-item > label {
        display: inline-flex;
        align-items: center;
        flex: 0 0 110px;
        width: 110px !important;
        margin-bottom: 0;
        padding-top: 0 !important;
        white-space: nowrap;
    }

    #calculationForm .taxonomy-inline-item > .input-wrapper {
        flex: 1 1 auto;
        min-width: 0;
        width: auto;
        margin-bottom: 0;
    }

    #calculationForm .work-area-group,
    #calculationForm .work-field-group {
        align-items: flex-start;
    }

    #calculationForm .work-area-group .material-type-filter-body,
    #calculationForm .work-field-group .material-type-filter-body {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    #calculationForm .work-area-group .material-type-rows,
    #calculationForm .work-field-group .material-type-rows,
    #calculationForm .work-area-group .material-type-extra-rows,
    #calculationForm .work-field-group .material-type-extra-rows {
        width: 100%;
    }

    #workAreaRows .material-type-row-actions,
    #workFieldRows .material-type-row-actions {
        display: none;
    }

    #workAreaRows .material-type-row .work-type-input,
    #workFieldRows .material-type-row .work-type-input {
        border-right: 1px solid #cbd5e1 !important;
        border-radius: 4px;
    }

    .work-area-extra-group,
    .work-field-extra-group {
        display: none !important;
    }

    .work-type-input-with-action {
        display: flex;
        align-items: stretch;
    }

    .work-type-input-with-action .autocomplete-input {
        flex: 1 1 auto;
        min-width: 0;
        border-radius: 8px 0 0 8px;
        border-right: 0;
    }

    #workType-list {
        overscroll-behavior: contain;
    }

    #calculationForm .work-type-group,
    #calculationForm .dimension-item {
        align-items: flex-start;
    }

    #calculationForm .work-type-group > label,
    #calculationForm .dimension-item > label {
        align-self: flex-start;
        padding-top: 0 !important;
    }

    .work-type-add-btn {
        width: 40px;
        border: 1px solid #cbd5e1;
        border-left: 0;
        border-radius: 0 8px 8px 0;
        background: #ffffff;
        color: #166534;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.15s ease;
    }

    .work-type-add-btn:hover {
        background: #f1f5f9;
    }

    .work-item-bottom-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 12px;
    }

    .work-item-bottom-bar-outside {
        margin-top: 14px;
    }

    .work-item-bottom-bar .button-actions {
        margin-top: 0;
    }

    .work-item-stepper {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .work-item-stepper-label {
        height: 32px;
        display: inline-flex;
        align-items: center;
        padding: 0 12px;
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        background: #ffffff;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        white-space: nowrap;
    }

    .work-item-stepper-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #0f172a;
        transition: all 0.15s ease;
    }

    .work-item-stepper-btn:hover:not(:disabled) {
        background: #eef2ff;
        border-color: #94a3b8;
    }

    #removeWorkItemBtn {
        background: #fee2e2;
        border-color: #ef4444;
        color: #b91c1c;
    }

    #removeWorkItemBtn:hover:not(:disabled) {
        background: #fecaca;
        border-color: #dc2626;
        color: #991b1b;
    }

    #addWorkItemBtn {
        background: #dcfce7;
        border-color: #22c55e;
        color: #166534;
    }

    #addWorkItemBtn:hover:not(:disabled) {
        background: #bbf7d0;
        border-color: #16a34a;
        color: #14532d;
    }

    .work-item-stepper-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .taxonomy-level-actions {
        margin-top: 6px;
    }

    .taxonomy-level-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.15s ease;
    }

    .taxonomy-level-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
    }

    #calculationForm #inputFormContainer,
    #calculationForm #additionalWorkItemsSection {
        margin-left: 0;
        padding-left: 0;
    }

    #calculationForm #inputFormContainer {
        margin-top: 0;
        padding-top: 0;
        padding-left: var(--work-item-parameter-indent);
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    #calculationForm #inputFormContainer .dimensions-container-vertical,
    #calculationForm #inputFormContainer .material-type-filter-group {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .additional-work-items-section {
        margin-top: 12px;
        padding: 0;
    }

    #calculationForm .right-column > #additionalWorkItemsSection {
        margin-top: 10px;
        padding-top: 0;
        border-top: 0;
    }

    .additional-work-items-title {
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
    }

    .additional-work-items-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .additional-work-item {
        border: 0;
        border-radius: 0;
        background: transparent;
        padding: 0;
    }

    #calculationForm .additional-work-item[data-row-kind="area"] {
        margin-top: 0;
    }

    #calculationForm .additional-work-item.taxonomy-tree-main.taxonomy-group-card > .additional-work-item-grid > .taxonomy-node > .taxonomy-card {
        margin-top: 0;
    }

    .additional-work-item.field-break {
        margin-top: 0;
        padding-top: 4px;
        border-top: 1px dashed #cbd5e1;
    }

    .additional-work-item-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }

    .additional-work-item-badge {
        color: #891313;
        font-size: 12px;
        font-weight: 700;
    }

    .additional-work-item-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .additional-work-item-grid {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .additional-area-children {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 6px;
    }

    .additional-area-children:empty {
        display: none;
        margin-top: 0;
    }

    .additional-area-children > .additional-work-item {
        margin-top: 0 !important;
    }

    /* Tambah jeda khusus antara item utama dan item ke-2 di Area utama */
    .main-area-children > .additional-work-item:first-child {
        margin-top: 8px !important;
    }

    .additional-material-inline {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 0;
    }

    .additional-material-inline .material-type-filter-item {
        margin-bottom: 6px !important;
    }

    .additional-material-inline .material-type-filter-item.has-extra-rows {
        
    }

    .additional-material-inline .material-type-filter-item:last-child {
        margin-bottom: 0 !important;
    }

    .additional-material-filter-item {
        align-items: flex-start;
    }

    .additional-material-filter-item .material-type-rows {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    .additional-material-filter-item .input-wrapper {
        margin-bottom: 0;
    }

    .additional-worktype-group {
        margin-bottom: 12px;
        margin-left: 0;
        width: 100%;
    }

    .additional-dimensions-container {
        margin-top: 0;
        padding-top: 0;
        margin-left: 0;
        padding-left: var(--work-item-parameter-indent);
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .additional-work-area-group,
    .additional-work-field-group {
        margin-bottom: 8px;
        margin-left: 0;
        width: 100%;
    }

    .additional-work-item[data-row-kind="field"] .additional-work-area-group,
    .additional-work-item[data-row-kind="item"] .additional-work-area-group {
        display: none !important;
    }

    .additional-work-item[data-row-kind="item"] .additional-work-field-group {
        display: none !important;
    }

    .additional-work-item .taxonomy-node-children {
        margin-top: 2px;
    }

    .additional-work-item[data-row-kind="field"] .additional-node-area > .taxonomy-node-children,
    .additional-work-item[data-row-kind="item"] .additional-node-area > .taxonomy-node-children {
        margin-top: 0;
        margin-left: 0;
        padding-left: 0;
        border-left: 0;
    }

    .additional-work-item[data-row-kind="item"] .additional-node-field > .taxonomy-node-children {
        margin-top: 0;
        margin-left: 0;
        padding-left: 10px;
        border-left: 1px dashed #cbd5e1;
    }

    .additional-work-item .additional-worktype-group {
        margin-bottom: 2px;
    }

    .additional-work-item .dimensions-container-vertical {
        margin-bottom: 0;
    }

    .additional-work-item .material-type-filter-group {
        margin-top: 2px;
    }

    .material-type-row.no-actions .work-type-input {
        border-right: 1px solid #cbd5e1 !important;
        border-radius: 4px;
    }

    .additional-worktype-input {
        overflow: hidden;
    }

    .additional-worktype-input .autocomplete-input {
        flex: 1 1 auto;
        min-width: 0;
        width: auto !important;
    }

    .additional-worktype-suffix-btn {
        flex: 0 0 38px;
        width: 38px;
        height: 38px;
        border: 0;
        border-left: 1px solid #e2e8f0;
        background: #fee2e2;
        color: #b91c1c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .additional-worktype-suffix-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .additional-worktype-btn {
        width: 34px;
        height: 34px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #166534;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }

    .additional-worktype-btn:hover {
        background: #f1f5f9;
    }

    .additional-worktype-btn.remove {
        color: #b91c1c;
        background: #fef2f2;
    }

    .material-type-filter-group {
        margin-top: 5px;
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

    .material-type-filter-item .material-type-filter-body {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    .material-type-filter-item .material-type-rows {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    .customize-tools {
        display: flex;
        justify-content: flex-end;
        margin-top: -4px;
        margin-bottom: 8px;
    }

    .customize-toggle-btn {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #1e293b;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        line-height: 1.3;
    }

    .customize-toggle-btn.is-active,
    .customize-toggle-btn:hover {
        border-color: #60a5fa;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .customize-panel {
        border: 0;
        border-radius: 0;
        background: transparent;
        padding: 0;
        margin-bottom: 10px;
    }

    .customize-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 10px;
    }

    .customize-panel .form-group {
        margin-bottom: 0;
    }

    .customize-panel .form-group > label {
        font-size: 12px;
        margin-bottom: 3px;
    }

    .material-type-customize-panel .input-wrapper {
        margin-bottom: 0;
    }

    .material-type-customize-panel select[data-customize-filter] {
        width: 100% !important;
        min-height: 38px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        background: #ffffff !important;
        background-image: none !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        padding: 8px 12px !important;
        padding-right: 12px !important;
        box-shadow: none !important;
        transform: none !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-type-customize-panel select[data-customize-filter]:focus {
        outline: none !important;
        border-color: #891313 !important;
        border-width: 2px !important;
        box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.15), 0 4px 12px rgba(137, 19, 19, 0.1) !important;
        background-color: #fffbfb !important;
        transform: translateY(-1px);
        z-index: 5;
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

    .material-type-extra-rows[data-material-type] {
        gap: 5px;
    }

    .material-type-extra-rows[data-material-type]:not(:empty) {
        margin-top: 5px;
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

    .material-type-row-btn-customize {
        margin-left: 8px;
        width: auto;
        min-height: 38px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px;
        padding: 0 10px;
        font-size: 12px;
        font-weight: 600;
        color: #1d4ed8;
        background: #eff6ff;
        white-space: nowrap;
    }

    .material-type-row-btn-customize:hover,
    .material-type-row-btn-customize.is-active {
        color: #1e40af;
        background: #dbeafe;
    }

    .material-type-customize-panel {
        margin-top: 8px;
    }

    .material-type-customize-panel .customize-grid {
        grid-template-columns: 1fr;
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

    .project-map-group {
        display: block;
        width: 100%;
        margin-bottom: 12px;
    }

    .project-map-content {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .project-location-group {
        gap: 8px;
    }

    .project-location-group .project-location-input-wrapper {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    #storeSearchModeBox {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 12px;
    }

    #storeSearchModeBox > label {
        font-weight: 600;
        font-size: 13px;
        color: #1e293b;
        margin: 0 0 2px 0;
    }

    .ssm-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 6px;
    }

    .ssm-row input[type="checkbox"] {
        flex: 0 0 auto;
        margin: 0;
        cursor: pointer;
    }

    .ssm-label {
        flex: 0 0 auto;
        min-width: 120px;
        margin: 0;
        font-weight: 600;
        font-size: 13px;
        color: #334155;
        cursor: pointer;
    }

    .ssm-desc {
        flex: 1 1 auto;
        font-size: 12px;
        color: #64748b;
        line-height: 1.3;
    }

    .project-location-map {
        width: 100%;
        height: 260px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
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

    @media (max-width: 768px) {
        #calculationForm .taxonomy-node-children {
            margin-left: 8px;
            padding-left: 8px;
        }

        #calculationForm .taxonomy-node-area > .taxonomy-node-children {
            margin-left: 15px;
        }

        #calculationForm .taxonomy-node-field > .taxonomy-node-children {
            margin-left: 32px;
            padding-left: 8px;
            border-left: 1px dashed #cbd5e1;
        }

        #calculationForm .taxonomy-inline-group {
            gap: 6px;
        }

        #calculationForm .taxonomy-inline-group > label {
            flex-basis: 54px;
            width: 54px !important;
            font-size: 12px;
        }

        #calculationForm .taxonomy-inline-group .taxonomy-level-btn {
            min-height: 34px;
            padding: 0 8px;
            font-size: 11px;
        }

        #calculationForm .taxonomy-inline-item {
            gap: 6px;
        }

        #calculationForm .taxonomy-inline-item > label {
            flex-basis: 96px;
            width: 96px !important;
            font-size: 12px;
        }

        .work-item-bottom-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .work-item-stepper {
            justify-content: flex-start;
        }

        .work-item-bottom-bar .button-actions {
            width: 100%;
        }

        #calculationForm #inputFormContainer,
        #calculationForm #additionalWorkItemsSection {
            margin-left: 0;
            padding-left: 0;
        }

        #calculationForm #inputFormContainer {
            padding-left: var(--work-item-parameter-indent-mobile);
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .additional-dimensions-container {
            margin-left: 0;
            padding-left: var(--work-item-parameter-indent-mobile);
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .additional-work-item-grid {
            gap: 6px;
        }

        .additional-work-item-header {
            align-items: flex-start;
        }

        .additional-worktype-group {
            margin-left: 0;
            width: 100%;
        }

        .customize-grid {
            grid-template-columns: 1fr;
        }

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
    'workAreas' => $workAreas ?? [],
    'workFields' => $workFields ?? [],
    'workItemGroupings' => $workItemGroupings ?? [],
    'storeLocations' => $storeLocationsForMap ?? [],
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
        const materialTypeLabels = @json($materialTypeLabels);
        if (typeof initMaterialCalculationForm === 'function') {
            try {
                initMaterialCalculationForm(document, payload);
            } catch (error) {
                console.error('initMaterialCalculationForm failed:', error);
            }
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

        function initWorkTaxonomyFilters(formPayload) {
            const workAreaRows = document.getElementById('workAreaRows');
            const workFieldRows = document.getElementById('workFieldRows');
            const workAreaExtraRows = document.getElementById('workAreaExtraRows');
            const workFieldExtraRows = document.getElementById('workFieldExtraRows');
            const workAreaExtraSection = document.getElementById('workAreaExtraSection');
            const workFieldExtraSection = document.getElementById('workFieldExtraSection');
            const rightColumn = document.querySelector('#calculationForm .right-column');
            const emptyApi = {
                setValues() {},
                getValues() { return []; },
                subscribe() { return function() {}; },
                refresh() {},
            };

            if (!workAreaRows || !workFieldRows || !workAreaExtraRows || !workFieldExtraRows) {
                return emptyApi;
            }

            // Keep taxonomy-extra rows as a dedicated grouping section at the bottom.
            if (
                rightColumn instanceof HTMLElement &&
                workAreaExtraSection instanceof HTMLElement &&
                workFieldExtraSection instanceof HTMLElement
            ) {
                rightColumn.appendChild(workAreaExtraSection);
                rightColumn.appendChild(workFieldExtraSection);
            }

            const normalizeOption = value => String(value ?? '').trim().toLowerCase();
            const baseAreaOptions = sortAlphabetic(
                uniqueFilterTokens((formPayload?.workAreas || []).map(item => item?.name || '')),
            );
            const baseFieldOptions = sortAlphabetic(
                uniqueFilterTokens((formPayload?.workFields || []).map(item => item?.name || '')),
            );
            const normalizedGroupings = Array.isArray(formPayload?.workItemGroupings)
                ? formPayload.workItemGroupings
                    .map(item => ({
                        work_area: String(item?.work_area || '').trim(),
                        work_area_norm: normalizeOption(item?.work_area || ''),
                        work_field: String(item?.work_field || '').trim(),
                        work_field_norm: normalizeOption(item?.work_field || ''),
                        formula_code: String(item?.formula_code || '').trim(),
                    }))
                    .filter(item => item.formula_code !== '')
                : [];
            const listeners = new Set();
            let taxonomyRowListSequence = 0;
            let fieldController = null;

            const parseInitialValues = rowsContainer => {
                if (!rowsContainer) return [];
                try {
                    const raw = rowsContainer.dataset.initialValues || '[]';
                    const parsed = JSON.parse(raw);
                    return uniqueFilterTokens(Array.isArray(parsed) ? parsed : [parsed]);
                } catch (error) {
                    return [];
                }
            };

            const notifyChanged = () => {
                listeners.forEach(callback => {
                    try {
                        callback();
                    } catch (error) {
                        console.warn('work taxonomy callback failed', error);
                    }
                });
            };

            const initKind = ({ kind, rowsContainer, extraRowsContainer, inputName, placeholder, initialOptions, onRowsChanged }) => {
                const baseRow = rowsContainer.querySelector('.material-type-row-base');
                const baseDisplay = baseRow?.querySelector('input[data-taxonomy-display="1"]');
                const baseHidden = baseRow?.querySelector('input[data-taxonomy-hidden="1"]');
                const baseList = baseRow?.querySelector('.autocomplete-list');
                const baseDeleteBtn = baseRow?.querySelector('[data-taxonomy-action="remove"]');
                const baseAddBtn = baseRow?.querySelector('[data-taxonomy-action="add"]');
                const extraSectionEl = document.getElementById(kind === 'area' ? 'workAreaExtraSection' : 'workFieldExtraSection');

                if (
                    !baseRow ||
                    !baseDisplay ||
                    !baseHidden ||
                    !baseList ||
                    !baseDeleteBtn ||
                    !baseAddBtn ||
                    !extraRowsContainer
                ) {
                    return null;
                }

                baseHidden.name = inputName;
                let currentOptions = sortAlphabetic(uniqueFilterTokens(initialOptions));
                let isSyncing = false;

                const getRowStates = () => {
                    const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
                    return rows.map(row => row.__taxonomyRowState).filter(Boolean);
                };

                const getHiddenInputs = () => getRowStates().map(state => state.hiddenEl).filter(Boolean);

                const updateRowButtons = () => {
                    const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
                    const hasExtra = extraRows.length > 0;
                    baseRow.classList.toggle('has-multiple', hasExtra);
                    baseDeleteBtn.classList.toggle('is-visible', hasExtra);
                    if (extraSectionEl) {
                        extraSectionEl.hidden = !hasExtra;
                    }
                    extraRows.forEach(row => {
                        const btn = row.querySelector('[data-taxonomy-action="remove"]');
                        if (btn) {
                            btn.classList.add('is-visible');
                        }
                    });
                };

                const getAvailableOptions = (term = '', currentHiddenEl = null, includeCurrentSelection = false) => {
                    const query = normalizeOption(term);
                    const selectedSet = new Set();
                    getHiddenInputs().forEach(hiddenEl => {
                        if (!hiddenEl) return;
                        if (includeCurrentSelection && hiddenEl === currentHiddenEl) return;
                        const normalizedValue = normalizeOption(hiddenEl.value);
                        if (normalizedValue) {
                            selectedSet.add(normalizedValue);
                        }
                    });

                    const options = uniqueFilterTokens(currentOptions);
                    const filtered = options.filter(option => {
                        const normalized = normalizeOption(option);
                        if (!normalized) return false;
                        if (selectedSet.has(normalized)) return false;
                        if (!query) return true;
                        return normalized.includes(query);
                    });

                    return sortAlphabetic(filtered);
                };

                const refreshOpenLists = () => {
                    getRowStates().forEach(state => {
                        if (state.listEl && state.listEl.style.display === 'block') {
                            state.renderList(state.displayEl.value || '');
                        }
                    });
                };

                const enforceUniqueSelections = () => {
                    if (isSyncing) return;
                    isSyncing = true;
                    try {
                        const seen = new Set();
                        getRowStates().forEach(state => {
                            const currentValue = String(state.hiddenEl.value || '').trim();
                            if (!currentValue) {
                                return;
                            }
                            const normalized = normalizeOption(currentValue);
                            if (!normalized) {
                                return;
                            }

                            if (seen.has(normalized)) {
                                state.hiddenEl.value = '';
                                state.displayEl.value = '';
                                state.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                                return;
                            }

                            seen.add(normalized);
                        });
                    } finally {
                        isSyncing = false;
                    }
                };

                const syncRows = () => {
                    enforceUniqueSelections();
                    refreshOpenLists();
                    if (typeof onRowsChanged === 'function') {
                        onRowsChanged();
                    }
                };

                const createRowState = (rowEl, displayEl, hiddenEl, listEl) => {
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
                        syncRows();
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

                    const findExactOption = term => {
                        const query = normalizeOption(term);
                        if (!query) return null;
                        const available = getAvailableOptions(term, hiddenEl, true);
                        return available.find(option => normalizeOption(option) === query) || null;
                    };

                    const rowState = {
                        rowEl,
                        displayEl,
                        hiddenEl,
                        listEl,
                        closeList,
                        renderList,
                    };
                    rowEl.__taxonomyRowState = rowState;

                    displayEl.addEventListener('focus', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        renderList(displayEl.value || '');
                    });

                    displayEl.addEventListener('input', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        const typed = String(this.value || '').trim();
                        hiddenEl.value = typed;
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                        renderList(this.value || '');
                        syncRows();
                    });

                    displayEl.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            const exactMatch = findExactOption(displayEl.value || '');
                            if (exactMatch) {
                                applySelection(exactMatch);
                            } else {
                                applySelection(displayEl.value || '');
                            }
                            event.preventDefault();
                            return;
                        }
                        if (event.key === 'Escape') {
                            closeList();
                        }
                    });

                    displayEl.addEventListener('blur', function() {
                        setTimeout(() => {
                            displayEl.value = String(hiddenEl.value || '').trim();
                            closeList();
                        }, 150);
                    });

                    document.addEventListener('click', function(event) {
                        if (event.target === displayEl || listEl.contains(event.target)) return;
                        closeList();
                    });

                    hiddenEl.addEventListener('change', function() {
                        const value = String(hiddenEl.value || '').trim();
                        if (displayEl.value !== value) {
                            displayEl.value = value;
                        }
                    });

                    return rowState;
                };

                const createExtraRow = (value = '') => {
                    const rowEl = document.createElement('div');
                    rowEl.className = 'material-type-row material-type-row-extra';
                    rowEl.dataset.taxonomyKind = kind;

                    const inputWrapper = document.createElement('div');
                    inputWrapper.className = 'input-wrapper';

                    const autocompleteWrap = document.createElement('div');
                    autocompleteWrap.className = 'work-type-autocomplete';

                    const inputShell = document.createElement('div');
                    inputShell.className = 'work-type-input';

                    const displayEl = document.createElement('input');
                    displayEl.type = 'text';
                    displayEl.className = 'autocomplete-input';
                    displayEl.placeholder = placeholder;
                    displayEl.autocomplete = 'off';
                    displayEl.dataset.taxonomyDisplay = '1';
                    displayEl.value = String(value || '');

                    const listEl = document.createElement('div');
                    listEl.className = 'autocomplete-list';
                    listEl.id = `workTaxonomy-${kind}-list-${++taxonomyRowListSequence}`;

                    const hiddenEl = document.createElement('input');
                    hiddenEl.type = 'hidden';
                    hiddenEl.name = inputName;
                    hiddenEl.value = String(value || '').trim();
                    hiddenEl.dataset.taxonomyHidden = '1';

                    inputShell.appendChild(displayEl);
                    autocompleteWrap.appendChild(inputShell);
                    autocompleteWrap.appendChild(listEl);
                    inputWrapper.appendChild(autocompleteWrap);
                    inputWrapper.appendChild(hiddenEl);

                    const actions = document.createElement('div');
                    actions.className = 'material-type-row-actions';
                    actions.innerHTML = `
                        <button type="button"
                            class="material-type-row-btn material-type-row-btn-delete is-visible"
                            data-taxonomy-action="remove"
                            data-taxonomy-kind="${kind}"
                            title="Hapus baris">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button"
                            class="material-type-row-btn material-type-row-btn-add"
                            data-taxonomy-action="add"
                            data-taxonomy-kind="${kind}"
                            title="Tambah baris">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    `;

                    rowEl.appendChild(inputWrapper);
                    rowEl.appendChild(actions);
                    extraRowsContainer.appendChild(rowEl);
                    createRowState(rowEl, displayEl, hiddenEl, listEl);
                    updateRowButtons();
                    return rowEl;
                };

                const setValues = values => {
                    const tokens = uniqueFilterTokens(Array.isArray(values) ? values : [values]);
                    while (extraRowsContainer.firstChild) {
                        extraRowsContainer.removeChild(extraRowsContainer.firstChild);
                    }

                    baseDisplay.value = '';
                    baseHidden.value = '';

                    const firstValue = tokens[0] || '';
                    baseDisplay.value = firstValue;
                    baseHidden.value = firstValue;

                    tokens.slice(1).forEach(token => {
                        createExtraRow(token);
                    });

                    updateRowButtons();
                    syncRows();
                };

                const removeBaseRow = () => {
                    const extraRows = Array.from(extraRowsContainer.querySelectorAll('.material-type-row-extra'));
                    if (extraRows.length > 0) {
                        const firstExtra = extraRows[0];
                        const state = firstExtra.__taxonomyRowState;
                        const promoted = String(state?.hiddenEl?.value ?? '').trim();
                        baseDisplay.value = promoted;
                        baseHidden.value = promoted;
                        firstExtra.remove();
                        updateRowButtons();
                        syncRows();
                        return;
                    }

                    baseDisplay.value = '';
                    baseHidden.value = '';
                    syncRows();
                };

                const handleRowActionClick = function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) return;
                    const actionBtn = target.closest('[data-taxonomy-action]');
                    if (!actionBtn) return;

                    const action = String(actionBtn.dataset.taxonomyAction || '').trim();
                    if (!action) return;

                    if (action === 'add') {
                        event.preventDefault();
                        createExtraRow('');
                        syncRows();
                        return;
                    }

                    if (action === 'remove') {
                        event.preventDefault();
                        const row = actionBtn.closest('.material-type-row');
                        if (!row) return;
                        if (row.classList.contains('material-type-row-base')) {
                            removeBaseRow();
                            return;
                        }
                        row.remove();
                        updateRowButtons();
                        syncRows();
                    }
                };

                rowsContainer.addEventListener('click', handleRowActionClick);
                extraRowsContainer.addEventListener('click', handleRowActionClick);

                createRowState(baseRow, baseDisplay, baseHidden, baseList);
                baseHidden.value = String(baseHidden.value || '').trim();
                baseDisplay.value = baseHidden.value;
                updateRowButtons();

                return {
                    setValues,
                    setOptions(nextOptions) {
                        currentOptions = sortAlphabetic(uniqueFilterTokens(nextOptions || []));
                        refreshOpenLists();
                    },
                    getValues() {
                        return uniqueFilterTokens(getHiddenInputs().map(input => input.value));
                    },
                };
            };

            const computeFieldOptions = areaController => {
                let scopedOptions = [...baseFieldOptions];
                if (!areaController) {
                    return sortAlphabetic(uniqueFilterTokens(scopedOptions));
                }

                const selectedAreas = areaController.getValues();
                if (selectedAreas.length > 0 && normalizedGroupings.length > 0) {
                    const selectedAreaSet = new Set(selectedAreas.map(value => normalizeOption(value)));
                    const matchedFields = normalizedGroupings
                        .filter(item => item.work_area_norm && selectedAreaSet.has(item.work_area_norm) && item.work_field)
                        .map(item => item.work_field);

                    if (matchedFields.length > 0) {
                        scopedOptions = sortAlphabetic(uniqueFilterTokens(matchedFields));
                    }
                }

                if (fieldController) {
                    scopedOptions = uniqueFilterTokens([...scopedOptions, ...fieldController.getValues()]);
                }

                return sortAlphabetic(scopedOptions);
            };

            const areaController = initKind({
                kind: 'area',
                rowsContainer: workAreaRows,
                extraRowsContainer: workAreaExtraRows,
                inputName: 'work_areas[]',
                placeholder: 'Pilih atau ketik area...',
                initialOptions: baseAreaOptions,
                onRowsChanged() {
                    if (fieldController) {
                        fieldController.setOptions(computeFieldOptions(areaController));
                    }
                    notifyChanged();
                },
            });

            fieldController = initKind({
                kind: 'field',
                rowsContainer: workFieldRows,
                extraRowsContainer: workFieldExtraRows,
                inputName: 'work_fields[]',
                placeholder: 'Pilih atau ketik bidang...',
                initialOptions: baseFieldOptions,
                onRowsChanged() {
                    notifyChanged();
                },
            });

            if (!areaController || !fieldController) {
                return emptyApi;
            }

            areaController.setValues(parseInitialValues(workAreaRows));
            fieldController.setValues(parseInitialValues(workFieldRows));
            fieldController.setOptions(computeFieldOptions(areaController));

            return {
                setValues(kind, values) {
                    const type = String(kind || '').trim();
                    if (type === 'area') {
                        areaController.setValues(values);
                        return;
                    }
                    if (type === 'field') {
                        fieldController.setValues(values);
                    }
                },
                getValues(kind) {
                    const type = String(kind || '').trim();
                    if (type === 'area') {
                        return areaController.getValues();
                    }
                    if (type === 'field') {
                        return fieldController.getValues();
                    }
                    return [];
                },
                subscribe(callback) {
                    if (typeof callback !== 'function') {
                        return function() {};
                    }
                    listeners.add(callback);
                    return function unsubscribe() {
                        listeners.delete(callback);
                    };
                },
                refresh() {
                    fieldController.setOptions(computeFieldOptions(areaController));
                    notifyChanged();
                },
            };
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
            let customizePanelSequence = 0;

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
                    const supportsCustomize = ['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'].includes(type);

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
                    let rowCustomizePanelEl = null;
                    if (supportsCustomize) {
                        const customizeBtn = document.createElement('button');
                        customizeBtn.type = 'button';
                        customizeBtn.className = 'material-type-row-btn material-type-row-btn-customize';
                        customizeBtn.dataset.customizeToggle = type;
                        customizeBtn.title = `Custom ${materialTypeLabels[type] || type}`;
                        customizeBtn.textContent = 'Custom';

                        const templatePanel = itemEl.querySelector(`[data-customize-panel="${type}"]`) ||
                            document.getElementById(`customizePanel-${type}`);
                        if (templatePanel) {
                            const panelId = `customizePanel-${type}-extra-${++customizePanelSequence}`;
                            rowCustomizePanelEl = templatePanel.cloneNode(true);
                            rowCustomizePanelEl.hidden = true;
                            rowCustomizePanelEl.id = panelId;
                            rowCustomizePanelEl.dataset.customizePanel = type;
                            rowCustomizePanelEl.querySelectorAll('.customize-filter-autocomplete').forEach(el => el.remove());
                            rowCustomizePanelEl.querySelectorAll('select[data-customize-filter]').forEach((selectEl, index) => {
                                selectEl.value = '';
                                selectEl.style.display = '';
                                selectEl.tabIndex = 0;
                                delete selectEl.dataset.customizeAutocompleteBound;
                                if (selectEl.id) {
                                    selectEl.id = `${selectEl.id}-extra-${customizePanelSequence}-${index}`;
                                }
                            });
                            customizeBtn.dataset.customizePanelId = panelId;
                        }

                        rowEl.appendChild(customizeBtn);
                    }
                    extraRowsContainer.appendChild(rowEl);
                    if (rowCustomizePanelEl) {
                        extraRowsContainer.appendChild(rowCustomizePanelEl);
                        rowEl.__customizePanelEl = rowCustomizePanelEl;
                    }
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
                        if (rowEl.__customizePanelEl) {
                            rowEl.__customizePanelEl.remove();
                        }
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
                        if (firstExtraRow.__customizePanelEl) {
                            firstExtraRow.__customizePanelEl.remove();
                        }
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

        const workTaxonomyFilterApi = initWorkTaxonomyFilters(payload);
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

        // Legacy custom form stays hidden (custom UI lives in material type section)
        function ensureCustomFormVisible() {
            if (customForm) {
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
                    // Check all other checkboxes, best only if available
                    filterCheckboxes.forEach(checkbox => {
                        if (checkbox === filterAll) return;

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
                if (checkbox.value === 'best' && !includeBest) return true;
                return checkbox.checked;
            });

            if (filterAll && !allOthersChecked) {
                filterAll.checked = false;
            }
        }

        // Initialize custom form visibility on page load
        ensureCustomFormVisible();

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
                    // Change label to "Lebar" for tile installation
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                    // Restore wall height to meters
                    if (wallHeightInput) {
                        wallHeightInput.step = '0.01';
                        wallHeightInput.min = '0.01';
                        wallHeightInput.placeholder = '';
                    }
                    const wallHeightUnitTile = document.querySelector('#wallHeightGroup .unit');
                    if (wallHeightUnitTile) {
                        wallHeightUnitTile.textContent = 'M';
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

                    // Change label to "Lebar" for grout tile
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else if (workTypeSelector.value === 'plinth_ceramic') {
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

                    // Change label to "Tinggi" and unit to "cm" for plinth ceramic
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                    // Change wall height input unit to cm for plinth
                    if (wallHeightInput) {
                        wallHeightInput.step = '1';
                        wallHeightInput.min = '1';
                        wallHeightInput.placeholder = 'Tinggi plint (10-20)';
                    }
                    // Update unit display to cm
                    const wallHeightUnit = document.querySelector('#wallHeightGroup .unit');
                    if (wallHeightUnit) {
                        wallHeightUnit.textContent = 'cm';
                    }
                } else if (workTypeSelector.value === 'adhesive_mix') {
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

                    // Change label to "Lebar" for adhesive_mix (Pasang Keramik Saja)
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                    // Keep wall height in meters
                    if (wallHeightInput) {
                        wallHeightInput.step = '0.01';
                        wallHeightInput.min = '0.01';
                        wallHeightInput.placeholder = '';
                    }
                    const wallHeightUnitAdhesive = document.querySelector('#wallHeightGroup .unit');
                    if (wallHeightUnitAdhesive) {
                        wallHeightUnitAdhesive.textContent = 'M';
                    }
                } else if (workTypeSelector.value === 'plinth_adhesive_mix') {
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

                    // Change label to "Tinggi" and unit to "cm" for plinth_adhesive_mix (Pasang Plint Keramik Saja)
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                    // Change wall height input unit to cm for plinth
                    if (wallHeightInput) {
                        wallHeightInput.step = '1';
                        wallHeightInput.min = '1';
                        wallHeightInput.placeholder = 'Tinggi plint (10-20)';
                    }
                    const wallHeightUnitPlinthAdhesive = document.querySelector('#wallHeightGroup .unit');
                    if (wallHeightUnitPlinthAdhesive) {
                        wallHeightUnitPlinthAdhesive.textContent = 'cm';
                    }
                } else {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    if (mortarModeChanged && mortarThicknessInput) {
                        mortarThicknessInput.value = formatThicknessValue(2);
                    }
                    // Restore label to "Tinggi" for other formulas
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                    // Restore wall height unit to "M"
                    if (wallHeightInput) {
                        wallHeightInput.step = '0.01';
                        wallHeightInput.min = '0.01';
                        wallHeightInput.placeholder = '';
                    }
                    const wallHeightUnit = document.querySelector('#wallHeightGroup .unit');
                    if (wallHeightUnit) {
                        wallHeightUnit.textContent = 'M';
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
            });
        }

        filterCheckboxes.forEach(checkbox => {
            if (checkbox !== filterAll) {
                checkbox.addEventListener('change', function() {
                    handleOtherCheckboxes();
                });
            }
        });

        // Initialize on page load if work type is selected
        if (workTypeSelector && workTypeSelector.value) {
            handleWorkTypeChange();
        }

        // Multi item pekerjaan via tombol "+" di ujung dropdown item pekerjaan
        const addWorkItemBtn = document.getElementById('addWorkItemBtn');
        const removeWorkItemBtn = document.getElementById('removeWorkItemBtn');
        const enableBundleModeInput = document.getElementById('enableBundleMode');
        const workItemsPayloadInput = document.getElementById('workItemsPayload');
        const materialCustomizeFiltersPayloadInput = document.getElementById('materialCustomizeFiltersPayload');
        const initialMaterialCustomizeFiltersPayloadRaw = materialCustomizeFiltersPayloadInput
            ? String(materialCustomizeFiltersPayloadInput.value || '').trim()
            : '';
        const additionalWorkItemsSection = document.getElementById('additionalWorkItemsSection');
        const additionalWorkItemsList = document.getElementById('additionalWorkItemsList');
        const mainWorkTypeLabel = document.getElementById('mainWorkTypeLabel');
        const mainWorkTypeDisplayInput = document.getElementById('workTypeDisplay');
        const mainWorkTypeHiddenInput = document.getElementById('workTypeSelector');
        const mainWallLengthInput = document.getElementById('wallLength');
        const mainWallHeightInput = document.getElementById('wallHeight');
        const bundleFormulaOptions = Array.isArray(payload?.formulas)
            ? payload.formulas
                .filter(item => item && item.code)
                .map(item => ({
                    code: String(item.code),
                    name: String(item.name || item.code),
                }))
            : [];
        const enableWorkTypeTaxonomyScoping = false;
        const workItemGroupingIndex = Array.isArray(payload?.workItemGroupings)
            ? payload.workItemGroupings
                .map(item => ({
                    formula_code: String(item?.formula_code || '').trim(),
                    work_area_norm: String(item?.work_area || '').trim().toLowerCase(),
                    work_field_norm: String(item?.work_field || '').trim().toLowerCase(),
                    work_field: String(item?.work_field || '').trim(),
                }))
                .filter(item => item.formula_code !== '')
            : [];
        const workAreaOptionValues = sortAlphabetic(
            uniqueFilterTokens((payload?.workAreas || []).map(item => item?.name || '')),
        );
        const workFieldOptionValues = sortAlphabetic(
            uniqueFilterTokens((payload?.workFields || []).map(item => item?.name || '')),
        );
        const mainTaxonomyGroupCard = document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card');

        function getMainAreaChildrenHost() {
            if (!(mainTaxonomyGroupCard instanceof HTMLElement)) {
                return null;
            }

            let host = mainTaxonomyGroupCard.querySelector('[data-main-area-children]');
            if (host instanceof HTMLElement) {
                return host;
            }

            host = document.createElement('div');
            host.className = 'additional-area-children main-area-children';
            host.setAttribute('data-main-area-children', '1');
            mainTaxonomyGroupCard.appendChild(host);
            return host;
        }

        function resolveScopedWorkTypeOptionsByTaxonomy(selectedAreasInput = [], selectedFieldsInput = []) {
            if (!enableWorkTypeTaxonomyScoping) {
                return bundleFormulaOptions;
            }

            const areaSet = new Set(
                uniqueFilterTokens(Array.isArray(selectedAreasInput) ? selectedAreasInput : [selectedAreasInput])
                    .map(value => String(value || '').trim().toLowerCase())
                    .filter(Boolean),
            );
            const fieldSet = new Set(
                uniqueFilterTokens(Array.isArray(selectedFieldsInput) ? selectedFieldsInput : [selectedFieldsInput])
                    .map(value => String(value || '').trim().toLowerCase())
                    .filter(Boolean),
            );

            if (areaSet.size === 0 && fieldSet.size === 0) {
                return bundleFormulaOptions;
            }

            if (workItemGroupingIndex.length === 0) {
                return bundleFormulaOptions;
            }

            const matchedCodes = new Set();
            workItemGroupingIndex.forEach(item => {
                if (areaSet.size > 0 && (!item.work_area_norm || !areaSet.has(item.work_area_norm))) {
                    return;
                }
                if (fieldSet.size > 0 && (!item.work_field_norm || !fieldSet.has(item.work_field_norm))) {
                    return;
                }
                if (item.formula_code) {
                    matchedCodes.add(item.formula_code);
                }
            });

            if (matchedCodes.size === 0) {
                return bundleFormulaOptions;
            }

            return bundleFormulaOptions.filter(option => matchedCodes.has(String(option.code || '').trim()));
        }

        function resolveScopedWorkFieldOptionsByArea(selectedAreasInput = [], includeFieldsInput = []) {
            const areaSet = new Set(
                uniqueFilterTokens(Array.isArray(selectedAreasInput) ? selectedAreasInput : [selectedAreasInput])
                    .map(value => String(value || '').trim().toLowerCase())
                    .filter(Boolean),
            );
            const includeFields = uniqueFilterTokens(
                Array.isArray(includeFieldsInput) ? includeFieldsInput : [includeFieldsInput],
            );

            if (areaSet.size === 0 || workItemGroupingIndex.length === 0) {
                return sortAlphabetic(uniqueFilterTokens([...workFieldOptionValues, ...includeFields]));
            }

            const scoped = sortAlphabetic(
                uniqueFilterTokens(
                    workItemGroupingIndex
                        .filter(item => item.work_area_norm && areaSet.has(item.work_area_norm))
                        .map(item => item.work_field)
                        .filter(Boolean),
                ),
            );

            if (!scoped.length) {
                return sortAlphabetic(uniqueFilterTokens([...workFieldOptionValues, ...includeFields]));
            }

            return sortAlphabetic(uniqueFilterTokens([...scoped, ...includeFields]));
        }

        function resolveScopedWorkTypeOptions() {
            const selectedAreas = workTaxonomyFilterApi && typeof workTaxonomyFilterApi.getValues === 'function'
                ? workTaxonomyFilterApi.getValues('area')
                : [];
            const selectedFields = workTaxonomyFilterApi && typeof workTaxonomyFilterApi.getValues === 'function'
                ? workTaxonomyFilterApi.getValues('field')
                : [];
            return resolveScopedWorkTypeOptionsByTaxonomy(selectedAreas, selectedFields);
        }

        function refreshWorkTypeOptionConsumers() {
            document.dispatchEvent(new Event('material-calculation:refresh-work-type-options'));
            if (!additionalWorkItemsList) {
                return;
            }
            additionalWorkItemsList
                .querySelectorAll('[data-additional-work-item="true"]')
                .forEach(itemEl => {
                    if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                        itemEl.__refreshWorkTypeOptions();
                    }
                });
        }

        window.MaterialCalculationWorkTypeOptionsProvider = function() {
            return resolveScopedWorkTypeOptions();
        };

        if (workTaxonomyFilterApi && typeof workTaxonomyFilterApi.subscribe === 'function') {
            workTaxonomyFilterApi.subscribe(function() {
                refreshWorkTypeOptionConsumers();
            });
        }
        refreshWorkTypeOptionConsumers();

        const bundleRequiredTargets = [
            { el: mainWorkTypeDisplayInput, defaultRequired: !!mainWorkTypeDisplayInput?.required },
            { el: mainWallLengthInput, defaultRequired: !!mainWallLengthInput?.required },
            { el: mainWallHeightInput, defaultRequired: !!mainWallHeightInput?.required },
        ];
        const bundleMaterialTypeOrder = ['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'ceramic', 'nat'];
        const bundleCustomizeSupportedTypes = new Set(['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat']);
        const bundleMaterialTypeLabels = @json($materialTypeLabels);
        const bundleMaterialTypeOptions = buildMaterialTypeOptionMap(payload);
        let bundleAdditionalAutocompleteSeq = 0;
        let bundleCustomizePanelSeq = 0;

        function escapeHtml(raw) {
            return String(raw ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function normalizeBundleMaterialTypeFilters(rawFilters) {
            const source = rawFilters && typeof rawFilters === 'object' ? rawFilters : {};
            const normalized = {};
            Object.entries(source).forEach(([key, value]) => {
                const materialKey = String(key || '').trim();
                if (!materialKey) {
                    return;
                }
                const tokens = uniqueFilterTokens(Array.isArray(value) ? value : [value]);
                if (!tokens.length) {
                    return;
                }
                normalized[materialKey] = tokens.length === 1 ? tokens[0] : tokens;
            });
            return normalized;
        }

        function getBundleMaterialTypeFieldLabel(type) {
            if (type === 'ceramic') {
                return 'Ukuran Keramik';
            }
            if (type === 'ceramic_type') {
                return 'Jenis Keramik';
            }
            const label = bundleMaterialTypeLabels[type] || type;
            return `Jenis ${label}`;
        }

        function getBundleMaterialTypePlaceholder(type) {
            if (type === 'ceramic') {
                return '-- Semua ukuran keramik --';
            }
            if (type === 'ceramic_type') {
                return '-- Semua jenis keramik --';
            }
            const label = String(bundleMaterialTypeLabels[type] || type).toLowerCase();
            return `-- Semua jenis ${label} --`;
        }

        function getBundleMaterialTypeValues(filters, type) {
            if (!filters || typeof filters !== 'object') {
                return [];
            }
            const values = uniqueFilterTokens(Array.isArray(filters[type]) ? filters[type] : [filters[type]]);
            return values;
        }

        function buildBundleCustomizePanelHtml(type, panelId = '') {
            const renderField = (label, key) => `
                <div class="form-group">
                    <label>${escapeHtml(label)} :</label>
                    <div class="input-wrapper">
                        <select data-customize-filter="${type}" data-filter-key="${key}" class="select-gray"></select>
                    </div>
                </div>
            `;

            if (type === 'brick') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                            ${renderField('Dimensi', 'dimension')}
                        </div>
                    </div>
                `;
            }

            if (type === 'cement') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                            ${renderField('Sub Merek', 'sub_brand')}
                            ${renderField('Kode', 'code')}
                            ${renderField('Warna', 'color')}
                            ${renderField('Kemasan', 'package_unit')}
                            ${renderField('Berat Bersih', 'package_weight_net')}
                        </div>
                    </div>
                `;
            }

            if (type === 'sand') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                        </div>
                    </div>
                `;
            }

            if (type === 'cat') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                            ${renderField('Sub Merek', 'sub_brand')}
                            ${renderField('Kode', 'color_code')}
                            ${renderField('Warna', 'color_name')}
                            ${renderField('Kemasan', 'package_unit')}
                            ${renderField('Volume', 'volume_display')}
                            ${renderField('Berat Bersih', 'package_weight_net')}
                        </div>
                    </div>
                `;
            }

            if (type === 'ceramic') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                            ${renderField('Dimensi', 'dimension')}
                            ${renderField('Sub Merek', 'sub_brand')}
                            ${renderField('Permukaan', 'surface')}
                            ${renderField('Kode Pembakaran', 'code')}
                            ${renderField('Corak', 'color')}
                        </div>
                    </div>
                `;
            }

            if (type === 'nat') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Merek', 'brand')}
                            ${renderField('Sub Merek', 'sub_brand')}
                            ${renderField('Kode', 'code')}
                            ${renderField('Warna', 'color')}
                            ${renderField('Kemasan', 'package_unit')}
                            ${renderField('Berat Bersih', 'package_weight_net')}
                        </div>
                    </div>
                `;
            }

            return '';
        }

        function buildBundleMaterialFilterSectionHtml(item) {
            const rows = bundleMaterialTypeOrder
                .map(type => {
                    const typeLabel = bundleMaterialTypeLabels[type] || type;
                    const supportsCustomize = bundleCustomizeSupportedTypes.has(String(type || '').trim());
                    const basePanelId = supportsCustomize ? `bundleCustomizePanel-${type}-${++bundleCustomizePanelSeq}` : '';
                    return `
                        <div class="form-group material-type-filter-item additional-material-filter-item" data-material-wrap="${type}">
                            <label>${escapeHtml(getBundleMaterialTypeFieldLabel(type))}</label>
                            <div class="material-type-rows additional-material-type-rows" data-material-type="${type}">
                                <div class="material-type-row material-type-row-base" data-material-type="${type}">
                                    <div class="input-wrapper">
                                        <div class="work-type-autocomplete">
                                            <div class="work-type-input">
                                                <input type="text"
                                                       class="autocomplete-input"
                                                       data-material-display="1"
                                                       placeholder="${escapeHtml(getBundleMaterialTypePlaceholder(type))}"
                                                       autocomplete="off">
                                            </div>
                                            <div class="autocomplete-list" id="bundleMaterial-list-${type}-${++bundleAdditionalAutocompleteSeq}"></div>
                                        </div>
                                        <input type="hidden" data-field="material_type_${type}" data-material-type-hidden="1" value="">
                                    </div>
                                    <div class="material-type-row-actions">
                                        <button type="button"
                                            class="material-type-row-btn material-type-row-btn-delete"
                                            data-material-type-action="remove"
                                            title="Hapus baris">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button type="button"
                                            class="material-type-row-btn material-type-row-btn-add"
                                            data-material-type-action="add"
                                            title="Tambah baris">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                    ${supportsCustomize ? `
                                        <button type="button"
                                            class="material-type-row-btn material-type-row-btn-customize"
                                            data-customize-toggle="${type}"
                                            data-customize-panel-id="${basePanelId}"
                                            title="Custom ${escapeHtml(typeLabel)}">
                                            Custom
                                        </button>
                                    ` : ''}
                                </div>
                                ${buildBundleCustomizePanelHtml(type, basePanelId)}
                                <div class="material-type-extra-rows" data-material-type="${type}"></div>
                            </div>
                        </div>
                    `;
                })
                .join('');

            return `
                <div class="additional-material-inline" data-wrap="material_filters">
                    ${rows}
                </div>
            `;
        }

        function collectMainMaterialTypeFilters() {
            const inputs = document.querySelectorAll(
                'input[name^="material_type_filters["], input[name^="material_type_filters_extra["]',
            );
            const grouped = {};

            inputs.forEach(input => {
                if (!input || input.disabled) {
                    return;
                }
                const name = String(input.name || '');
                const match = name.match(/^material_type_filters(?:_extra)?\[(.+?)\]/);
                if (!match) {
                    return;
                }
                const materialKey = String(match[1] || '').trim();
                if (!materialKey) {
                    return;
                }
                const tokens = uniqueFilterTokens([input.value]);
                if (!tokens.length) {
                    return;
                }
                if (!grouped[materialKey]) {
                    grouped[materialKey] = [];
                }
                grouped[materialKey].push(...tokens);
            });

            const normalized = {};
            Object.entries(grouped).forEach(([key, values]) => {
                const uniqueValues = uniqueFilterTokens(values);
                if (!uniqueValues.length) {
                    return;
                }
                normalized[key] = uniqueValues.length === 1 ? uniqueValues[0] : uniqueValues;
            });

            return normalized;
        }

        function normalizeBundleMaterialCustomizeFilters(rawFilters) {
            if (!rawFilters || typeof rawFilters !== 'object') {
                return {};
            }

            const allowedFieldsByMaterial = {
                brick: ['brand', 'dimension'],
                cement: ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
                sand: ['brand'],
                cat: ['brand', 'sub_brand', 'color_code', 'color_name', 'package_unit', 'volume_display', 'package_weight_net'],
                ceramic: ['brand', 'dimension', 'sub_brand', 'surface', 'code', 'color'],
                nat: ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
            };

            const normalized = {};
            Object.entries(rawFilters).forEach(([materialKey, fieldMap]) => {
                const material = String(materialKey || '').trim();
                if (!material || !allowedFieldsByMaterial[material] || !fieldMap || typeof fieldMap !== 'object') {
                    return;
                }

                const normalizedFieldMap = {};
                allowedFieldsByMaterial[material].forEach(fieldKey => {
                    const rawValue = fieldMap[fieldKey];
                    const values = uniqueFilterTokens(Array.isArray(rawValue) ? rawValue : [rawValue]);
                    if (!values.length) {
                        return;
                    }
                    normalizedFieldMap[fieldKey] = values.length === 1 ? values[0] : values;
                });

                if (Object.keys(normalizedFieldMap).length > 0) {
                    normalized[material] = normalizedFieldMap;
                }
            });

            return normalized;
        }

        function collectCustomizeFiltersFromRoot(rootEl, options = {}) {
            if (!rootEl) {
                return {};
            }

            const excludeAdditional = !!options.excludeAdditional;
            const grouped = {};
            const panelEls = Array.from(rootEl.querySelectorAll('.customize-panel[data-customize-panel]'));

            panelEls.forEach(panelEl => {
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }
                if (excludeAdditional && panelEl.closest('[data-additional-work-item="true"]')) {
                    return;
                }

                const materialKey = String(panelEl.dataset.customizePanel || '').trim();
                if (!materialKey) {
                    return;
                }

                const selectEls = panelEl.querySelectorAll(`select[data-customize-filter="${materialKey}"][data-filter-key]`);
                selectEls.forEach(selectEl => {
                    const filterKey = String(selectEl.dataset.filterKey || '').trim();
                    if (!filterKey) {
                        return;
                    }
                    const value = String(selectEl.value || '').trim();
                    if (!value) {
                        return;
                    }

                    if (!grouped[materialKey]) {
                        grouped[materialKey] = {};
                    }
                    if (!grouped[materialKey][filterKey]) {
                        grouped[materialKey][filterKey] = [];
                    }
                    grouped[materialKey][filterKey].push(value);
                });
            });

            return normalizeBundleMaterialCustomizeFilters(grouped);
        }

        function collectMainMaterialCustomizeFilters() {
            const root = document.getElementById('inputFormContainer') || document;
            return collectCustomizeFiltersFromRoot(root, { excludeAdditional: true });
        }

        function parseObjectPayload(raw) {
            const text = String(raw || '').trim();
            if (!text) {
                return {};
            }
            try {
                const parsed = JSON.parse(text);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                try {
                    const helper = document.createElement('textarea');
                    helper.innerHTML = text;
                    const decoded = JSON.parse(helper.value);
                    return decoded && typeof decoded === 'object' ? decoded : {};
                } catch (decodeError) {
                    return {};
                }
            }
        }

        function getFirstFilterToken(rawValue) {
            const tokens = uniqueFilterTokens(Array.isArray(rawValue) ? rawValue : [rawValue]);
            return tokens[0] || '';
        }

        function panelHasActiveCustomizeValues(panelEl) {
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            const materialKey = String(panelEl.dataset.customizePanel || '').trim();
            if (!materialKey) {
                return false;
            }
            const selectEls = panelEl.querySelectorAll(`select[data-customize-filter="${materialKey}"][data-filter-key]`);
            return Array.from(selectEls).some(selectEl => String(selectEl.value || '').trim() !== '');
        }

        function collapseEmptyCustomizePanels(rootEl = document) {
            if (!rootEl) {
                return;
            }

            const panelEls = Array.from(rootEl.querySelectorAll('.customize-panel[data-customize-panel]'));
            panelEls.forEach(panelEl => {
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }
                if (panelHasActiveCustomizeValues(panelEl)) {
                    return;
                }
                panelEl.hidden = true;

                const panelId = String(panelEl.id || '').trim();
                if (!panelId) {
                    return;
                }
                document.querySelectorAll(`[data-customize-panel-id="${panelId}"]`).forEach(btn => {
                    btn.classList.remove('is-active');
                });
            });
        }

        function bindAutoHideEmptyCustomizePanels() {
            if (document.__autoHideEmptyCustomizeBound) {
                return;
            }
            document.__autoHideEmptyCustomizeBound = true;

            const shouldSkipAutoHide = target => {
                if (!(target instanceof Element)) {
                    return false;
                }
                if (target.closest('[data-customize-toggle]')) {
                    return true;
                }
                if (target.closest('.customize-panel[data-customize-panel]')) {
                    return true;
                }
                return false;
            };

            document.addEventListener('click', function(event) {
                const target = event?.target;
                if (shouldSkipAutoHide(target)) {
                    return;
                }
                collapseEmptyCustomizePanels(document);
            });

            document.addEventListener('focusin', function(event) {
                const target = event?.target;
                if (shouldSkipAutoHide(target)) {
                    return;
                }
                collapseEmptyCustomizePanels(document);
            });
        }

        function applyMaterialCustomizeFiltersToPanels(rootEl, rawFilters) {
            if (!rootEl) {
                return;
            }

            const normalizedFilters = normalizeBundleMaterialCustomizeFilters(rawFilters);
            if (Object.keys(normalizedFilters).length === 0) {
                return;
            }

            Object.entries(normalizedFilters).forEach(([materialKey, fieldMap]) => {
                const panels = Array.from(
                    rootEl.querySelectorAll(`.customize-panel[data-customize-panel="${materialKey}"]`),
                );
                if (!panels.length || !fieldMap || typeof fieldMap !== 'object') {
                    return;
                }

                const selectedValues = {};
                Object.entries(fieldMap).forEach(([fieldKey, rawValue]) => {
                    const firstValue = getFirstFilterToken(rawValue);
                    if (!firstValue) {
                        return;
                    }
                    selectedValues[String(fieldKey || '').trim()] = firstValue;
                });
                if (Object.keys(selectedValues).length === 0) {
                    return;
                }

                const panelEl = panels[0];
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }

                const wasHidden = !!panelEl.hidden;
                const panelId = String(panelEl.id || '').trim();
                const openBtn = panelId
                    ? (rootEl.querySelector(`[data-customize-panel-id="${panelId}"]`) ||
                        document.querySelector(`[data-customize-panel-id="${panelId}"]`))
                    : null;

                if (wasHidden) {
                    if (openBtn instanceof HTMLElement) {
                        openBtn.click();
                    } else {
                        panelEl.hidden = false;
                    }
                }

                const selectEls = Array.from(
                    panelEl.querySelectorAll(`select[data-customize-filter="${materialKey}"][data-filter-key]`),
                );
                selectEls.forEach(selectEl => {
                    const filterKey = String(selectEl.dataset.filterKey || '').trim();
                    if (!filterKey || !Object.prototype.hasOwnProperty.call(selectedValues, filterKey)) {
                        return;
                    }
                    const nextValue = String(selectedValues[filterKey] || '').trim();
                    if (!nextValue) {
                        return;
                    }
                    selectEl.value = nextValue;
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                });

                panelEl.hidden = false;
            });
        }

        function syncMaterialCustomizeFiltersPayload() {
            if (!materialCustomizeFiltersPayloadInput) {
                return;
            }
            const filters = collectMainMaterialCustomizeFilters();
            materialCustomizeFiltersPayloadInput.value = Object.keys(filters).length > 0
                ? JSON.stringify(filters)
                : '';
        }

        const bundleParameterFields = [
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
        ];

        function normalizeActiveFieldList(rawFields) {
            if (!Array.isArray(rawFields)) {
                return [];
            }
            const seen = new Set();
            const normalized = [];
            rawFields.forEach(field => {
                const key = String(field || '').trim();
                if (!key || seen.has(key) || !bundleParameterFields.includes(key)) {
                    return;
                }
                seen.add(key);
                normalized.push(key);
            });
            return normalized;
        }

        function isFieldWrapVisible(wrapEl) {
            if (!wrapEl) {
                return false;
            }
            if (wrapEl.offsetParent === null) {
                return false;
            }
            const style = window.getComputedStyle(wrapEl);
            return style.display !== 'none' && style.visibility !== 'hidden';
        }

        function getMainActiveParameterFields() {
            const active = [];
            const mainFieldWrapMap = {
                wall_length: document.getElementById('wallLength')?.closest('.dimension-item') || null,
                wall_height: document.getElementById('wallHeightGroup'),
                mortar_thickness: document.getElementById('mortarThicknessGroup'),
                layer_count: document.getElementById('layerCountGroup'),
                plaster_sides: document.getElementById('plasterSidesGroup'),
                skim_sides: document.getElementById('skimSidesGroup'),
                grout_thickness: document.getElementById('groutThicknessGroup'),
                ceramic_length: document.getElementById('ceramicLengthGroup'),
                ceramic_width: document.getElementById('ceramicWidthGroup'),
                ceramic_thickness: document.getElementById('ceramicThicknessGroup'),
            };

            bundleParameterFields.forEach(field => {
                const wrapEl = mainFieldWrapMap[field] || null;
                if (isFieldWrapVisible(wrapEl)) {
                    active.push(field);
                }
            });

            return active;
        }

        function getAdditionalActiveParameterFields(itemEl) {
            if (!itemEl) {
                return [];
            }
            const active = [];
            bundleParameterFields.forEach(field => {
                const wrapEl = itemEl.querySelector(`[data-wrap="${field}"]`);
                if (isFieldWrapVisible(wrapEl)) {
                    active.push(field);
                }
            });
            return active;
        }

        function normalizeBundleRowKind(value) {
            const kind = String(value || '').trim().toLowerCase();
            if (kind === 'field' || kind === 'item') {
                return kind;
            }
            return 'area';
        }

        function normalizeBundleItem(item, index) {
            const entry = item && typeof item === 'object' ? item : {};
            const title = String(entry.title || '').trim();
            const workType = String(entry.work_type || '').trim();
            return {
                title: title || `Item ${index + 1}`,
                row_kind: normalizeBundleRowKind(entry.row_kind),
                work_area: String(entry.work_area || '').trim(),
                work_field: String(entry.work_field || '').trim(),
                work_type: workType,
                wall_length: String(entry.wall_length || '').trim(),
                wall_height: String(entry.wall_height || '').trim(),
                mortar_thickness: String(entry.mortar_thickness || '').trim(),
                layer_count: String(entry.layer_count || '').trim(),
                plaster_sides: String(entry.plaster_sides || '').trim(),
                skim_sides: String(entry.skim_sides || '').trim(),
                grout_thickness: String(entry.grout_thickness || '').trim(),
                ceramic_length: String(entry.ceramic_length || '').trim(),
                ceramic_width: String(entry.ceramic_width || '').trim(),
                ceramic_thickness: String(entry.ceramic_thickness || '').trim(),
                active_fields: normalizeActiveFieldList(entry.active_fields),
                material_type_filters: normalizeBundleMaterialTypeFilters(entry.material_type_filters || {}),
                material_customize_filters: normalizeBundleMaterialCustomizeFilters(entry.material_customize_filters || {}),
            };
        }

        function setMainFormRequired(enabled) {
            bundleRequiredTargets.forEach(target => {
                if (!target.el || !target.defaultRequired) {
                    return;
                }
                if (enabled) {
                    target.el.setAttribute('required', 'required');
                } else {
                    target.el.removeAttribute('required');
                }
            });
        }

        function buildWorkTypeOptionHtml(selectedValue) {
            const selected = String(selectedValue || '').trim();
            const options = ['<option value="">Pilih item pekerjaan...</option>'];
            bundleFormulaOptions.forEach(item => {
                const code = escapeHtml(item.code);
                const name = escapeHtml(item.name);
                const selectedAttr = selected === item.code ? 'selected' : '';
                options.push(`<option value="${code}" ${selectedAttr}>${name}</option>`);
            });
            return options.join('');
        }

        function getMainFormValue(id) {
            const el = document.getElementById(id);
            return el ? String(el.value || '').trim() : '';
        }

        function getMainTaxonomyValue(kind) {
            const normalized = kind === 'field' ? 'field' : 'area';
            const hiddenId = normalized === 'field' ? 'workFieldValue' : 'workAreaValue';
            const displayId = normalized === 'field' ? 'workFieldDisplay' : 'workAreaDisplay';
            return getMainFormValue(hiddenId) || getMainFormValue(displayId);
        }

        function collectMainWorkItem() {
            const workType = mainWorkTypeHiddenInput ? String(mainWorkTypeHiddenInput.value || '').trim() : '';
            if (!workType) {
                return null;
            }
            return normalizeBundleItem(
                {
                    title: mainWorkTypeDisplayInput ? String(mainWorkTypeDisplayInput.value || '').trim() : '',
                    work_area: getMainTaxonomyValue('area'),
                    work_field: getMainTaxonomyValue('field'),
                    work_type: workType,
                    wall_length: getMainFormValue('wallLength'),
                    wall_height: getMainFormValue('wallHeight'),
                    mortar_thickness: getMainFormValue('mortarThickness'),
                    layer_count: getMainFormValue('layerCount'),
                    plaster_sides: getMainFormValue('plasterSides'),
                    skim_sides: getMainFormValue('skimSides'),
                    grout_thickness: getMainFormValue('groutThickness'),
                    ceramic_length: getMainFormValue('ceramicLength'),
                    ceramic_width: getMainFormValue('ceramicWidth'),
                    ceramic_thickness: getMainFormValue('ceramicThickness'),
                    active_fields: getMainActiveParameterFields(),
                    material_type_filters: collectMainMaterialTypeFilters(),
                    material_customize_filters: collectMainMaterialCustomizeFilters(),
                },
                0,
            );
        }

        function bindAutocompleteScrollLock(listEl) {
            if (!listEl || listEl.__scrollLockBound) {
                return;
            }
            listEl.__scrollLockBound = true;
            listEl.addEventListener(
                'wheel',
                function(event) {
                    const deltaY = event.deltaY || 0;
                    if (!deltaY) return;

                    const canScroll = listEl.scrollHeight > listEl.clientHeight + 1;
                    if (!canScroll) {
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }

                    listEl.scrollTop += deltaY;
                    event.preventDefault();
                    event.stopPropagation();
                },
                { passive: false },
            );
        }

        function initAdditionalWorkTaxonomyAutocomplete(itemEl, initial = {}) {
            if (!itemEl) {
                return;
            }

            const areaDisplayInput = itemEl.querySelector('[data-field-display="work_area"]');
            const areaHiddenInput = itemEl.querySelector('[data-field="work_area"]');
            const areaListEl = itemEl.querySelector('[data-field-list="work_area"]');
            const fieldDisplayInput = itemEl.querySelector('[data-field-display="work_field"]');
            const fieldHiddenInput = itemEl.querySelector('[data-field="work_field"]');
            const fieldListEl = itemEl.querySelector('[data-field-list="work_field"]');

            if (
                !areaDisplayInput ||
                !areaHiddenInput ||
                !areaListEl ||
                !fieldDisplayInput ||
                !fieldHiddenInput ||
                !fieldListEl
            ) {
                return;
            }

            bindAutocompleteScrollLock(areaListEl);
            bindAutocompleteScrollLock(fieldListEl);

            const normalize = text =>
                String(text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/gi, '')
                    .trim();

            const setupAutocomplete = ({ displayInput, hiddenInput, listEl, getOptions, onChanged }) => {
                const closeList = () => {
                    listEl.style.display = 'none';
                };

                const getFilteredOptions = term => {
                    const options = uniqueFilterTokens(getOptions() || []);
                    const query = normalize(term);
                    if (!query) return options;
                    return options.filter(option => normalize(option).includes(query));
                };

                const applyRawValue = value => {
                    const finalValue = String(value || '').trim();
                    displayInput.value = finalValue;
                    if (hiddenInput.value !== finalValue) {
                        hiddenInput.value = finalValue;
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    } else if (typeof onChanged === 'function') {
                        onChanged();
                    }
                };

                const renderList = term => {
                    listEl.innerHTML = '';

                    const emptyItem = document.createElement('div');
                    emptyItem.className = 'autocomplete-item';
                    emptyItem.textContent = '- Tidak Pilih -';
                    emptyItem.addEventListener('click', function() {
                        applyRawValue('');
                        closeList();
                    });
                    listEl.appendChild(emptyItem);

                    getFilteredOptions(term).forEach(option => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = option;
                        item.addEventListener('click', function() {
                            applyRawValue(option);
                            closeList();
                        });
                        listEl.appendChild(item);
                    });

                    listEl.style.display = 'block';
                };

                const findExactMatch = term => {
                    const query = normalize(term);
                    if (!query) return null;
                    return getFilteredOptions('').find(option => normalize(option) === query) || null;
                };

                displayInput.addEventListener('focus', function() {
                    renderList(displayInput.value || '');
                });

                displayInput.addEventListener('input', function() {
                    const term = String(displayInput.value || '');
                    applyRawValue(term);
                    renderList(term);
                });

                displayInput.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        const exact = findExactMatch(displayInput.value || '');
                        if (exact) {
                            applyRawValue(exact);
                        } else {
                            applyRawValue(displayInput.value || '');
                        }
                        closeList();
                        event.preventDefault();
                    } else if (event.key === 'Escape') {
                        closeList();
                    }
                });

                displayInput.addEventListener('blur', function() {
                    setTimeout(() => {
                        displayInput.value = String(hiddenInput.value || '').trim();
                        closeList();
                    }, 150);
                });

                document.addEventListener('click', function(event) {
                    if (event.target === displayInput || listEl.contains(event.target)) return;
                    closeList();
                });

                hiddenInput.addEventListener('change', function() {
                    const value = String(hiddenInput.value || '').trim();
                    if (displayInput.value !== value) {
                        displayInput.value = value;
                    }
                    if (typeof onChanged === 'function') {
                        onChanged();
                    }
                });

                return {
                    setValue(value) {
                        applyRawValue(value);
                    },
                };
            };

            const areaAutocomplete = setupAutocomplete({
                displayInput: areaDisplayInput,
                hiddenInput: areaHiddenInput,
                listEl: areaListEl,
                getOptions: () => sortAlphabetic(uniqueFilterTokens([...workAreaOptionValues, areaHiddenInput.value])),
                onChanged: () => {
                    if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                        itemEl.__refreshWorkTypeOptions();
                    }
                },
            });

            const fieldAutocomplete = setupAutocomplete({
                displayInput: fieldDisplayInput,
                hiddenInput: fieldHiddenInput,
                listEl: fieldListEl,
                getOptions: () => resolveScopedWorkFieldOptionsByArea(areaHiddenInput.value, fieldHiddenInput.value),
                onChanged: () => {
                    if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                        itemEl.__refreshWorkTypeOptions();
                    }
                },
            });

            const initialArea = String(initial.work_area || '').trim();
            const initialField = String(initial.work_field || '').trim();
            if (initialArea) {
                areaAutocomplete.setValue(initialArea);
            }
            if (initialField) {
                fieldAutocomplete.setValue(initialField);
            }
        }

        function initAdditionalWorkTypeAutocomplete(itemEl, initial = {}) {
            if (!itemEl) {
                return;
            }

            const displayInput = itemEl.querySelector('[data-field-display="work_type"]');
            const hiddenInput = itemEl.querySelector('[data-field="work_type"]');
            const listEl = itemEl.querySelector('[data-field-list="work_type"]');

            if (!displayInput || !hiddenInput || !listEl || bundleFormulaOptions.length === 0) {
                return;
            }

            bindAutocompleteScrollLock(listEl);

            const baseOptions = bundleFormulaOptions
                .filter(option => option && option.code && option.name)
                .map(option => ({
                    code: String(option.code),
                    name: String(option.name),
                }));

            const getScopedOptions = () => {
                const rowKind = normalizeBundleRowKind(
                    getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                );
                if (rowKind === 'item') {
                    return baseOptions;
                }

                const selectedArea = getAdditionalFieldValue(itemEl, 'work_area');
                const selectedField = getAdditionalFieldValue(itemEl, 'work_field');
                const scoped = resolveScopedWorkTypeOptionsByTaxonomy(selectedArea, selectedField);
                if (!Array.isArray(scoped) || scoped.length === 0) {
                    return baseOptions;
                }
                return scoped
                    .filter(option => option && option.code && option.name)
                    .map(option => ({
                        code: String(option.code),
                        name: String(option.name),
                    }));
            };

            const normalize = text =>
                String(text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/gi, '')
                    .trim();

            const filterOptions = term => {
                const options = getScopedOptions();
                const query = normalize(term);
                if (!query) return options;
                return options.filter(option => {
                    const name = normalize(option.name);
                    const code = normalize(option.code);
                    return name.includes(query) || code.includes(query);
                });
            };

            const findExactMatch = term => {
                const options = getScopedOptions();
                const query = normalize(term);
                if (!query) return null;
                return options.find(option => normalize(option.name) === query || normalize(option.code) === query) || null;
            };

            const closeList = () => {
                listEl.style.display = 'none';
            };

            const openList = () => {
                renderList(filterOptions(''));
            };

            const applySelection = option => {
                if (!option) return;
                displayInput.value = option.name;
                if (hiddenInput.value !== option.code) {
                    hiddenInput.value = option.code;
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                const titleInput = itemEl.querySelector('[data-field="title"]');
                if (titleInput && !String(titleInput.value || '').trim()) {
                    titleInput.value = option.name;
                }
                closeList();
            };

            const renderList = items => {
                listEl.innerHTML = '';
                items.forEach(option => {
                    const row = document.createElement('div');
                    row.className = 'autocomplete-item';
                    row.textContent = option.name;
                    row.addEventListener('click', function() {
                        applySelection(option);
                    });
                    listEl.appendChild(row);
                });
                listEl.style.display = items.length > 0 ? 'block' : 'none';
            };

            displayInput.addEventListener('focus', function() {
                if (displayInput.readOnly || displayInput.disabled) return;
                openList();
            });

            displayInput.addEventListener('input', function() {
                if (displayInput.readOnly || displayInput.disabled) return;
                const term = this.value || '';
                renderList(filterOptions(term));

                if (!term.trim()) {
                    if (hiddenInput.value) {
                        hiddenInput.value = '';
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                const exactMatch = findExactMatch(term);
                if (exactMatch && hiddenInput.value !== exactMatch.code) {
                    hiddenInput.value = exactMatch.code;
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            displayInput.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                const exactMatch = findExactMatch(displayInput.value);
                if (exactMatch) {
                    applySelection(exactMatch);
                    event.preventDefault();
                }
            });

            displayInput.addEventListener('blur', function() {
                setTimeout(closeList, 150);
            });

            document.addEventListener('click', function(event) {
                if (event.target === displayInput || listEl.contains(event.target)) return;
                closeList();
            });

            hiddenInput.addEventListener('change', function() {
                const options = getScopedOptions();
                const selected = options.find(option => option.code === hiddenInput.value);
                if (selected) {
                    if (displayInput.value !== selected.name) {
                        displayInput.value = selected.name;
                    }
                    return;
                }
                if (!hiddenInput.value) {
                    displayInput.value = '';
                }
            });

            const refreshOptions = () => {
                const options = getScopedOptions();
                const selected = options.find(option => option.code === hiddenInput.value);
                if (selected) {
                    if (displayInput.value !== selected.name) {
                        displayInput.value = selected.name;
                    }
                } else if (listEl.style.display === 'block') {
                    renderList(filterOptions(displayInput.value || ''));
                }
            };

            const initialWorkType = String(initial.work_type || '').trim();
            if (initialWorkType) {
                hiddenInput.value = initialWorkType;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                displayInput.value = '';
                hiddenInput.value = '';
            }

            // Expose helper so newly added rows can reliably auto-open the dropdown.
            displayInput.__openAdditionalWorkTypeList = openList;
            itemEl.__refreshWorkTypeOptions = refreshOptions;
        }

        function initAdditionalMaterialTypeFilters(itemEl, initialFilters = {}) {
            if (!itemEl) {
                return;
            }

            const normalizeOption = value => String(value ?? '').trim().toLowerCase();
            let additionalCustomizePanelSequence = 0;

            bundleMaterialTypeOrder.forEach(type => {
                const wrap = itemEl.querySelector(`[data-material-wrap="${type}"]`);
                const baseRow = wrap?.querySelector('.material-type-row-base');
                const baseDisplay = baseRow?.querySelector('.autocomplete-input[data-material-display="1"]');
                const baseHidden = baseRow?.querySelector('input[data-material-type-hidden="1"]');
                const baseList = baseRow?.querySelector('.autocomplete-list');
                const extraRowsContainer = wrap?.querySelector('.material-type-extra-rows');
                const baseDeleteBtn = baseRow?.querySelector('[data-material-type-action="remove"]');
                const baseAddBtn = baseRow?.querySelector('[data-material-type-action="add"]');
                const options = sortAlphabetic(uniqueFilterTokens(bundleMaterialTypeOptions[type] || []));
                let isSyncing = false;

                if (
                    !wrap ||
                    !baseRow ||
                    !baseDisplay ||
                    !baseHidden ||
                    !baseList ||
                    !extraRowsContainer ||
                    !baseDeleteBtn ||
                    !baseAddBtn
                ) {
                    return;
                }

                const updateRowButtons = () => {
                    const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
                    const hasExtra = extraRows.length > 0;
                    baseRow.classList.toggle('has-multiple', hasExtra);
                    wrap.classList.toggle('has-extra-rows', hasExtra);
                    baseDeleteBtn.classList.toggle('is-visible', hasExtra);
                    extraRows.forEach(row => {
                        const deleteBtn = row.querySelector('[data-material-type-action="remove"]');
                        if (deleteBtn) {
                            deleteBtn.classList.add('is-visible');
                        }
                    });
                };

                const getRowStates = () => {
                    const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
                    return rows.map(row => row.__bundleMaterialRowState).filter(Boolean);
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
                    syncBundleFromForms();
                };

                const setupAutocomplete = rowState => {
                    const { rowEl, displayEl, hiddenEl, listEl } = rowState;

                    const closeList = () => {
                        listEl.style.display = 'none';
                    };

                    const applySelection = optionValue => {
                        const finalValue = String(optionValue || '').trim();
                        displayEl.value = finalValue;
                        hiddenEl.value = finalValue;
                        closeList();
                        syncRows();
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
                        const available = getAvailableOptions(term, hiddenEl, true);
                        return available.find(option => normalizeOption(option) === query) || null;
                    };

                    rowState.closeList = closeList;
                    rowState.renderList = renderList;
                    rowEl.__bundleMaterialRowState = rowState;

                    displayEl.addEventListener('focus', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        renderList('');
                    });

                    displayEl.addEventListener('input', function() {
                        if (displayEl.readOnly || displayEl.disabled) return;
                        const term = this.value || '';
                        renderList(term);

                        if (!term.trim()) {
                            hiddenEl.value = '';
                            syncRows();
                            return;
                        }

                        const exactMatch = findExactAvailableOption(term);
                        if (exactMatch) {
                            hiddenEl.value = exactMatch;
                        } else {
                            hiddenEl.value = '';
                        }
                        syncRows();
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
                };

                const createExtraRow = (value = '') => {
                    const rowEl = document.createElement('div');
                    rowEl.className = 'material-type-row material-type-row-extra';
                    rowEl.dataset.materialType = type;
                    const supportsCustomize = bundleCustomizeSupportedTypes.has(String(type || '').trim());

                    const inputWrapperEl = document.createElement('div');
                    inputWrapperEl.className = 'input-wrapper';

                    const autocompleteEl = document.createElement('div');
                    autocompleteEl.className = 'work-type-autocomplete';

                    const inputShellEl = document.createElement('div');
                    inputShellEl.className = 'work-type-input';

                    const displayEl = document.createElement('input');
                    displayEl.type = 'text';
                    displayEl.className = 'autocomplete-input';
                    displayEl.dataset.materialDisplay = '1';
                    displayEl.placeholder = getBundleMaterialTypePlaceholder(type);
                    displayEl.autocomplete = 'off';
                    displayEl.value = String(value || '');

                    const listEl = document.createElement('div');
                    listEl.className = 'autocomplete-list';
                    listEl.id = `bundleMaterial-list-${type}-${++bundleAdditionalAutocompleteSeq}`;

                    const hiddenEl = document.createElement('input');
                    hiddenEl.type = 'hidden';
                    hiddenEl.dataset.materialTypeHidden = '1';
                    hiddenEl.dataset.field = `material_type_${type}`;
                    hiddenEl.setAttribute('data-field', `material_type_${type}`);
                    hiddenEl.value = String(value || '');

                    inputShellEl.appendChild(displayEl);
                    autocompleteEl.appendChild(inputShellEl);
                    autocompleteEl.appendChild(listEl);
                    inputWrapperEl.appendChild(autocompleteEl);
                    inputWrapperEl.appendChild(hiddenEl);

                    const actionEl = document.createElement('div');
                    actionEl.className = 'material-type-row-actions';
                    actionEl.innerHTML = `
                        <button type="button" class="material-type-row-btn material-type-row-btn-delete is-visible"
                            data-material-type-action="remove" title="Hapus baris">
                            <i class="bi bi-trash"></i>
                        </button>
                        <button type="button" class="material-type-row-btn material-type-row-btn-add"
                            data-material-type-action="add" title="Tambah baris">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    `;

                    rowEl.appendChild(inputWrapperEl);
                    rowEl.appendChild(actionEl);
                    let rowCustomizePanelEl = null;
                    if (supportsCustomize) {
                        const customizeBtn = document.createElement('button');
                        customizeBtn.type = 'button';
                        customizeBtn.className = 'material-type-row-btn material-type-row-btn-customize';
                        customizeBtn.dataset.customizeToggle = type;
                        customizeBtn.title = `Custom ${bundleMaterialTypeLabels[type] || type}`;
                        customizeBtn.textContent = 'Custom';

                        const templatePanel = wrap.querySelector(`[data-customize-panel="${type}"]`);
                        if (templatePanel) {
                            const panelId = `bundleCustomizePanel-${type}-extra-${++additionalCustomizePanelSequence}`;
                            rowCustomizePanelEl = templatePanel.cloneNode(true);
                            rowCustomizePanelEl.hidden = true;
                            rowCustomizePanelEl.id = panelId;
                            rowCustomizePanelEl.dataset.customizePanel = type;
                            rowCustomizePanelEl.querySelectorAll('.customize-filter-autocomplete').forEach(el => el.remove());
                            rowCustomizePanelEl.querySelectorAll('select[data-customize-filter]').forEach((selectEl, index) => {
                                selectEl.value = '';
                                selectEl.style.display = '';
                                selectEl.tabIndex = 0;
                                delete selectEl.dataset.customizeAutocompleteBound;
                                if (selectEl.id) {
                                    selectEl.id = `${selectEl.id}-extra-${additionalCustomizePanelSequence}-${index}`;
                                }
                            });
                            customizeBtn.dataset.customizePanelId = panelId;
                        }

                        rowEl.appendChild(customizeBtn);
                    }
                    extraRowsContainer.appendChild(rowEl);
                    if (rowCustomizePanelEl) {
                        extraRowsContainer.appendChild(rowCustomizePanelEl);
                        rowEl.__customizePanelEl = rowCustomizePanelEl;
                    }

                    setupAutocomplete({
                        rowEl,
                        displayEl,
                        hiddenEl,
                        listEl,
                        renderList() {},
                        closeList() {},
                    });

                    updateRowButtons();
                    return rowEl;
                };

                const setValues = values => {
                    const tokens = uniqueFilterTokens(Array.isArray(values) ? values : [values]);
                    while (extraRowsContainer.firstChild) {
                        extraRowsContainer.removeChild(extraRowsContainer.firstChild);
                    }
                    baseDisplay.value = '';
                    baseHidden.value = '';

                    const firstValue = tokens[0] || '';
                    baseDisplay.value = firstValue;
                    baseHidden.value = firstValue;

                    tokens.slice(1).forEach(token => {
                        createExtraRow(token);
                    });
                    updateRowButtons();
                    syncRows();
                };

                const removeBaseRow = () => {
                    const extraRows = Array.from(extraRowsContainer.querySelectorAll('.material-type-row-extra'));
                    if (extraRows.length > 0) {
                        const firstExtra = extraRows[0];
                        const state = firstExtra.__bundleMaterialRowState;
                        const promoted = String(state?.hiddenEl?.value ?? state?.displayEl?.value ?? '').trim();
                        baseDisplay.value = promoted;
                        baseHidden.value = promoted;
                        if (firstExtra.__customizePanelEl) {
                            firstExtra.__customizePanelEl.remove();
                        }
                        firstExtra.remove();
                        updateRowButtons();
                        syncRows();
                        return;
                    }

                    baseDisplay.value = '';
                    baseHidden.value = '';
                    syncRows();
                };

                wrap.addEventListener('click', function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) return;
                    const actionBtn = target.closest('[data-material-type-action]');
                    if (!actionBtn || !wrap.contains(actionBtn)) return;

                    const action = String(actionBtn.dataset.materialTypeAction || '').trim();
                    if (!action) return;

                    if (action === 'add') {
                        event.preventDefault();
                        createExtraRow('');
                        updateRowButtons();
                        syncRows();
                        return;
                    }

                    if (action === 'remove') {
                        event.preventDefault();
                        const row = actionBtn.closest('.material-type-row');
                        if (!row) return;

                        if (row.classList.contains('material-type-row-base')) {
                            removeBaseRow();
                            return;
                        }

                        if (row.__customizePanelEl) {
                            row.__customizePanelEl.remove();
                        }
                        row.remove();
                        updateRowButtons();
                        syncRows();
                    }
                });

                setupAutocomplete({
                    rowEl: baseRow,
                    displayEl: baseDisplay,
                    hiddenEl: baseHidden,
                    listEl: baseList,
                    renderList() {},
                    closeList() {},
                });

                baseHidden.dataset.materialTypeHidden = '1';
                baseHidden.setAttribute('data-field', `material_type_${type}`);

                const initialValues = getBundleMaterialTypeValues(initialFilters, type);
                setValues(initialValues);

                wrap.__setBundleMaterialTypeValues = setValues;
                wrap.__clearBundleMaterialTypeValues = function() {
                    setValues([]);
                };
            });
        }

        function createAdditionalWorkItemForm(initial = {}, afterElement = null, options = {}) {
            if (!additionalWorkItemsList) {
                return null;
            }

            const requestedRowKind = normalizeBundleRowKind(options.rowKind || initial.row_kind);
            const item = normalizeBundleItem(
                {
                    ...initial,
                    row_kind: requestedRowKind,
                },
                getAllAdditionalWorkRows().length + 1,
            );
            const wrapper = document.createElement('div');
            wrapper.className = 'additional-work-item';
            wrapper.setAttribute('data-additional-work-item', 'true');
            wrapper.setAttribute('data-row-kind', item.row_kind);
            wrapper.innerHTML = `
                <div class="additional-work-item-grid">
                    <input type="hidden" data-field="title" value="${escapeHtml(item.title)}">
                    <input type="hidden" data-field="row_kind" value="${escapeHtml(item.row_kind)}">
                    <div class="taxonomy-node taxonomy-node-area additional-node-area">
                    <div class="form-group work-area-group additional-work-area-group taxonomy-card taxonomy-card-area taxonomy-inline-group">
                        <label>Area</label>
                        <div class="material-type-filter-body">
                            <div class="material-type-rows">
                                <div class="material-type-row material-type-row-base no-actions">
                                    <div class="input-wrapper">
                                        <div class="work-type-autocomplete">
                                            <div class="work-type-input">
                                                <input type="text"
                                                       class="autocomplete-input"
                                                       data-field-display="work_area"
                                                       placeholder="Pilih atau ketik area..."
                                                       autocomplete="off"
                                                       value="">
                                            </div>
                                            <div class="autocomplete-list" data-field-list="work_area" id="additionalWorkArea-list-${++bundleAdditionalAutocompleteSeq}"></div>
                                        </div>
                                        <input type="hidden" data-field="work_area" value="${escapeHtml(item.work_area)}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="taxonomy-level-actions">
                            <button type="button" class="taxonomy-level-btn" data-action="add-field" title="Bidang">
                                + Bidang
                            </button>
                        </div>
                    </div>
                    <div class="taxonomy-node-children">
                        <div class="taxonomy-node taxonomy-node-field additional-node-field">
                    <div class="form-group work-field-group additional-work-field-group taxonomy-card taxonomy-card-field taxonomy-inline-group">
                        <label>Bidang</label>
                        <div class="material-type-filter-body">
                            <div class="material-type-rows">
                                <div class="material-type-row material-type-row-base no-actions">
                                    <div class="input-wrapper">
                                        <div class="work-type-autocomplete">
                                            <div class="work-type-input">
                                                <input type="text"
                                                       class="autocomplete-input"
                                                       data-field-display="work_field"
                                                       placeholder="Pilih atau ketik bidang..."
                                                       autocomplete="off"
                                                       value="">
                                            </div>
                                            <div class="autocomplete-list" data-field-list="work_field" id="additionalWorkField-list-${++bundleAdditionalAutocompleteSeq}"></div>
                                        </div>
                                        <input type="hidden" data-field="work_field" value="${escapeHtml(item.work_field)}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="taxonomy-level-actions">
                            <button type="button" class="taxonomy-level-btn" data-action="add-item" title="Tambah Item Pekerjaan di Bidang ini">
                                + Item Pekerjaan
                            </button>
                        </div>
                    </div>
                    <div class="taxonomy-node-children">
                        <div class="taxonomy-node taxonomy-node-item additional-node-item">
                    <div class="form-group work-type-group additional-worktype-group taxonomy-card taxonomy-card-item taxonomy-inline-item">
                        <label data-additional-worktype-label>Item Pekerjaan</label>
                        <div class="input-wrapper">
                            <div class="work-type-autocomplete">
                                <div class="work-type-input additional-worktype-input">
                                    <input type="text"
                                           class="autocomplete-input"
                                           data-field-display="work_type"
                                           placeholder="Pilih atau ketik item pekerjaan..."
                                           autocomplete="off"
                                           value="">
                                    <button type="button"
                                            class="additional-worktype-suffix-btn"
                                            data-action="remove"
                                            title="Hapus item pekerjaan ini">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="autocomplete-list" data-field-list="work_type" id="additionalWorkType-list-${++bundleAdditionalAutocompleteSeq}"></div>
                            </div>
                            <input type="hidden" data-field="work_type" value="${escapeHtml(item.work_type)}">
                        </div>
                    </div>
                    <div class="dimensions-container-vertical additional-dimensions-container">
                        <div class="dimension-item" data-wrap="wall_length">
                            <label>Panjang</label>
                            <div class="input-with-unit">
                                <input type="text" inputmode="decimal" step="0.01" min="0.01" data-field="wall_length" value="${escapeHtml(item.wall_length)}">
                                <span class="unit">M</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="wall_height">
                            <label data-wall-height-label>Tinggi</label>
                            <div class="input-with-unit">
                                <input type="text" inputmode="decimal" step="0.01" min="0.01" data-field="wall_height" value="${escapeHtml(item.wall_height)}">
                                <span class="unit">M</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="mortar_thickness">
                            <label>Tebal Adukan</label>
                            <div class="input-with-unit">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="mortar_thickness" value="${escapeHtml(item.mortar_thickness || '2')}">
                                <span class="unit">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="layer_count">
                            <label>Lapis / Tingkat</label>
                            <div class="input-with-unit" style="background-color: #fffbeb; border-color: #fcd34d;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="layer_count" value="${escapeHtml(item.layer_count || '1')}">
                                <span class="unit" style="background-color: #fef3c7;">Lapis</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="plaster_sides">
                            <label>Sisi Plesteran</label>
                            <div class="input-with-unit" style="background-color: #e0f2fe; border-color: #7dd3fc;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="plaster_sides" value="${escapeHtml(item.plaster_sides || '1')}">
                                <span class="unit" style="background-color: #bae6fd;">Sisi</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="skim_sides">
                            <label>Sisi Acian</label>
                            <div class="input-with-unit" style="background-color: #e0e7ff; border-color: #a5b4fc;">
                                <input type="text" inputmode="decimal" step="1" min="0" data-field="skim_sides" value="${escapeHtml(item.skim_sides || '1')}">
                                <span class="unit" style="background-color: #c7d2fe;">Sisi</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="grout_thickness">
                            <label>Tebal Nat</label>
                            <div class="input-with-unit" style="background-color: #f1f5f9; border-color: #cbd5e1;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="grout_thickness" value="${escapeHtml(item.grout_thickness || '2')}">
                                <span class="unit" style="background-color: #e2e8f0;">mm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_length">
                            <label>Panjang Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_length" value="${escapeHtml(item.ceramic_length || '30')}">
                                <span class="unit" style="background-color: #fef08a;">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_width">
                            <label>Lebar Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_width" value="${escapeHtml(item.ceramic_width || '30')}">
                                <span class="unit" style="background-color: #fef08a;">cm</span>
                            </div>
                        </div>
                        <div class="dimension-item" data-wrap="ceramic_thickness">
                            <label>Tebal Keramik</label>
                            <div class="input-with-unit" style="background-color: #fef3c7; border-color: #fde047;">
                                <input type="text" inputmode="decimal" step="0.01" min="0" data-field="ceramic_thickness" value="${escapeHtml(item.ceramic_thickness || '8')}">
                                <span class="unit" style="background-color: #fef08a;">mm</span>
                            </div>
                        </div>
                        ${buildBundleMaterialFilterSectionHtml(item)}
                    </div>
                        </div>
                    </div>
                        </div>
                    </div>
                    </div>
                    <div class="additional-area-children" data-area-children></div>
                </div>
            `;

            const initialModeWorkType = String(item.work_type || '').trim();
            const initialMortarInput = wrapper.querySelector('[data-field="mortar_thickness"]');
            const hasInitialMortarValue = String(item.mortar_thickness || '').trim() !== '';
            const isInitialAci = ['skim_coating', 'coating_floor'].includes(initialModeWorkType);
            if (initialMortarInput && initialModeWorkType) {
                // Preserve the original unit context on restored rows so unit conversion
                // does not run with a wrong assumption (which could append an extra zero).
                initialMortarInput.dataset.unit = isInitialAci ? 'mm' : 'cm';
            }
            if (initialMortarInput && initialModeWorkType && hasInitialMortarValue) {
                initialMortarInput.dataset.mode = isInitialAci ? 'acian' : 'adukan';
            }

            const target = resolveAdditionalInsertionTarget(item, afterElement, options);
            if (target.referenceNode && target.referenceNode.parentNode === target.parent) {
                target.parent.insertBefore(wrapper, target.referenceNode);
            } else {
                target.parent.appendChild(wrapper);
            }

            setAdditionalWorkItemRowKind(wrapper, item.row_kind);
            initAdditionalWorkTaxonomyAutocomplete(wrapper, item);
            initAdditionalWorkTypeAutocomplete(wrapper, item);
            if (typeof wrapper.__refreshWorkTypeOptions === 'function') {
                wrapper.__refreshWorkTypeOptions();
            }
            initAdditionalMaterialTypeFilters(wrapper, item.material_type_filters || {});
            applyMaterialCustomizeFiltersToPanels(wrapper, item.material_customize_filters || {});
            attachAdditionalWorkItemEvents(wrapper);
            applyAdditionalWorkItemVisibility(wrapper);
            refreshAdditionalWorkItemHeader();
            syncBundleFromForms();

            const hasInitialWorkType = String(item.work_type || '').trim() !== '';
            if (!hasInitialWorkType) {
                const workTypeDisplay = wrapper.querySelector('[data-field-display="work_type"]');
                if (workTypeDisplay) {
                    setTimeout(() => {
                        workTypeDisplay.focus();
                        if (typeof workTypeDisplay.__openAdditionalWorkTypeList === 'function') {
                            workTypeDisplay.__openAdditionalWorkTypeList();
                        }
                    }, 0);
                }
            }

            return wrapper;
        }

        function getMainTaxonomyContext() {
            return {
                work_area: getMainTaxonomyValue('area'),
                work_field: getMainTaxonomyValue('field'),
            };
        }

        function setAdditionalWorkItemRowKind(itemEl, rowKind = 'area') {
            if (!itemEl) {
                return;
            }

            const normalizedKind = normalizeBundleRowKind(rowKind);
            itemEl.setAttribute('data-row-kind', normalizedKind);
            const isAreaRow = normalizedKind === 'area';
            itemEl.classList.toggle('taxonomy-tree-main', isAreaRow);
            itemEl.classList.toggle('taxonomy-group-card', isAreaRow);

            const rowKindInput = itemEl.querySelector('[data-field="row_kind"]');
            if (rowKindInput) {
                rowKindInput.value = normalizedKind;
            }

            const areaCard = itemEl.querySelector('.additional-work-area-group');
            const fieldCard = itemEl.querySelector('.additional-work-field-group');
            if (areaCard) {
                areaCard.style.display = normalizedKind === 'area' ? '' : 'none';
            }
            if (fieldCard) {
                fieldCard.style.display = normalizedKind === 'item' ? 'none' : '';
            }
        }

        function createAndFocusAdditionalWorkItem(initial = {}, afterElement = null, focusField = 'work_type', options = {}) {
            const newForm = createAdditionalWorkItemForm(initial, afterElement, options);
            if (!newForm) {
                return null;
            }

            const selectorMap = {
                work_area: '[data-field-display="work_area"]',
                work_field: '[data-field-display="work_field"]',
                work_type: '[data-field-display="work_type"]',
            };
            const focusSelector = selectorMap[focusField] || selectorMap.work_type;
            const focusInput = newForm.querySelector(focusSelector);
            if (focusInput) {
                focusInput.focus();
                if (typeof focusInput.__openAdditionalWorkTypeList === 'function') {
                    focusInput.__openAdditionalWorkTypeList();
                }
            }
            return newForm;
        }

        function showTaxonomyActionError(message, focusEl = null) {
            if (typeof window.showToast === 'function') {
                window.showToast(message, 'error');
            } else {
                alert(message);
            }
            if (focusEl && typeof focusEl.focus === 'function') {
                focusEl.focus();
                if (typeof focusEl.__openAdditionalWorkTypeList === 'function') {
                    focusEl.__openAdditionalWorkTypeList();
                }
            }
        }

        function refreshAdditionalWorkItemHeader() {
            if (!additionalWorkItemsList) {
                return;
            }
            const items = getAllAdditionalWorkRows();
            const hasAdditionalItems = items.length > 0;

            if (mainWorkTypeLabel) {
                mainWorkTypeLabel.textContent = hasAdditionalItems ? 'Item Pekerjaan 1' : 'Item Pekerjaan';
            }

            const setAdditionalItemLabel = (itemEl, numberInArea) => {
                if (!(itemEl instanceof HTMLElement)) {
                    return;
                }
                const label = itemEl.querySelector('[data-additional-worktype-label]');
                if (label) {
                    label.textContent = `Item Pekerjaan ${numberInArea}`;
                }

                const rowKind = normalizeBundleRowKind(
                    getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                );
                const shouldShowFieldBreak = numberInArea > 1 && rowKind === 'field';
                itemEl.classList.toggle('field-break', shouldShowFieldBreak);
            };

            const mainAreaChildren = getMainAreaChildrenHost();
            let mainAreaCounter = 2;
            if (mainAreaChildren instanceof HTMLElement) {
                Array.from(mainAreaChildren.children)
                    .filter(row => row instanceof HTMLElement && row.matches('[data-additional-work-item="true"]'))
                    .forEach(row => {
                        setAdditionalItemLabel(row, mainAreaCounter);
                        mainAreaCounter += 1;
                    });
            }

            getTopLevelAdditionalRows().forEach(areaRow => {
                const rowKind = normalizeBundleRowKind(
                    getAdditionalFieldValue(areaRow, 'row_kind') || areaRow.dataset.rowKind || 'area',
                );

                if (rowKind !== 'area') {
                    setAdditionalItemLabel(areaRow, 1);
                    return;
                }

                setAdditionalItemLabel(areaRow, 1);
                const areaChildren = areaRow.querySelector('[data-area-children]');
                let areaCounter = 2;
                if (!(areaChildren instanceof HTMLElement)) {
                    return;
                }

                Array.from(areaChildren.children)
                    .filter(row => row instanceof HTMLElement && row.matches('[data-additional-work-item="true"]'))
                    .forEach(row => {
                        setAdditionalItemLabel(row, areaCounter);
                        areaCounter += 1;
                    });
            });
        }

        function getAdditionalFieldValue(itemEl, key) {
            const el = itemEl.querySelector(`[data-field="${key}"]`);
            const hiddenValue = el ? String(el.value || '').trim() : '';
            if (hiddenValue) {
                return hiddenValue;
            }

            if (key === 'work_area' || key === 'work_field' || key === 'work_type') {
                const displayEl = itemEl.querySelector(`[data-field-display="${key}"]`);
                return displayEl ? String(displayEl.value || '').trim() : '';
            }

            return '';
        }

        function normalizeTaxonomyValue(value) {
            return String(value || '').trim().toLowerCase();
        }

        function findLastAdditionalRowByTaxonomy(workArea = '', workField = '', matchField = true) {
            if (!additionalWorkItemsList) {
                return null;
            }

            const targetArea = normalizeTaxonomyValue(workArea);
            const targetField = normalizeTaxonomyValue(workField);
            if (!targetArea) {
                return null;
            }

            const rows = getAllAdditionalWorkRows();
            let matchedRow = null;
            rows.forEach(row => {
                const rowArea = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_area'));
                if (rowArea !== targetArea) {
                    return;
                }

                if (matchField) {
                    const rowField = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_field'));
                    if (rowField !== targetField) {
                        return;
                    }
                }

                matchedRow = row;
            });
            return matchedRow;
        }

        function getTopLevelAdditionalRows() {
            if (!additionalWorkItemsList) {
                return [];
            }
            return Array.from(additionalWorkItemsList.children).filter(el =>
                el instanceof HTMLElement && el.matches('[data-additional-work-item="true"]'),
            );
        }

        function getAllAdditionalWorkRows() {
            const mainHost = getMainAreaChildrenHost();
            const mainRows = mainHost
                ? Array.from(mainHost.querySelectorAll('[data-additional-work-item="true"]'))
                : [];
            const extraRows = additionalWorkItemsList
                ? Array.from(additionalWorkItemsList.querySelectorAll('[data-additional-work-item="true"]'))
                : [];
            return [...mainRows, ...extraRows];
        }

        function findLastAdditionalAreaCardByWorkArea(workArea = '') {
            const targetArea = normalizeTaxonomyValue(workArea);
            if (!targetArea) {
                return null;
            }

            let matched = null;
            getTopLevelAdditionalRows().forEach(row => {
                if (row.getAttribute('data-row-kind') !== 'area') {
                    return;
                }
                const rowArea = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_area'));
                if (rowArea === targetArea) {
                    matched = row;
                }
            });

            return matched;
        }

        function resolveAdditionalInsertionTarget(item, afterElement = null, options = {}) {
            const beforeElement = options.beforeElement || null;
            const forceMainAreaHost = options.targetMainArea === true;
            let parent = additionalWorkItemsList;
            let referenceNode = null;
            const mainHost = getMainAreaChildrenHost();

            if (!additionalWorkItemsList) {
                return { parent, referenceNode };
            }

            if (item.row_kind !== 'area') {
                if (forceMainAreaHost && mainHost instanceof HTMLElement) {
                    parent = mainHost;
                    if (beforeElement && beforeElement.parentNode === parent) {
                        referenceNode = beforeElement;
                    } else if (afterElement && afterElement.parentNode === parent) {
                        referenceNode = afterElement.nextSibling;
                    }
                    return { parent, referenceNode };
                }

                if (afterElement instanceof HTMLElement && afterElement.parentNode instanceof HTMLElement) {
                    const afterParent = afterElement.parentNode;
                    if (
                        afterParent.matches('[data-area-children]') ||
                        afterParent.matches('[data-main-area-children]')
                    ) {
                        return { parent: afterParent, referenceNode: afterElement.nextSibling };
                    }
                }
                if (beforeElement instanceof HTMLElement && beforeElement.parentNode instanceof HTMLElement) {
                    const beforeParent = beforeElement.parentNode;
                    if (
                        beforeParent.matches('[data-area-children]') ||
                        beforeParent.matches('[data-main-area-children]')
                    ) {
                        return { parent: beforeParent, referenceNode: beforeElement };
                    }
                }

                let hostAreaRow = null;

                if (afterElement instanceof HTMLElement) {
                    hostAreaRow = afterElement.closest('.additional-work-item[data-row-kind="area"]');
                }
                if (!hostAreaRow && beforeElement instanceof HTMLElement) {
                    hostAreaRow = beforeElement.closest('.additional-work-item[data-row-kind="area"]');
                }
                if (!hostAreaRow && item.work_area) {
                    hostAreaRow = findLastAdditionalAreaCardByWorkArea(item.work_area);
                }

                if (hostAreaRow instanceof HTMLElement) {
                    const areaChildren = hostAreaRow.querySelector('[data-area-children]');
                    if (areaChildren instanceof HTMLElement) {
                        parent = areaChildren;
                        if (beforeElement && beforeElement.parentNode === parent) {
                            referenceNode = beforeElement;
                        } else if (afterElement && afterElement.parentNode === parent) {
                            referenceNode = afterElement.nextSibling;
                        }
                        return { parent, referenceNode };
                    }
                }

                const mainAreaNormalized = normalizeTaxonomyValue(getMainFormValue('workAreaValue'));
                const itemAreaNormalized = normalizeTaxonomyValue(item.work_area);
                if (
                    mainHost instanceof HTMLElement &&
                    itemAreaNormalized &&
                    mainAreaNormalized &&
                    itemAreaNormalized === mainAreaNormalized
                ) {
                    parent = mainHost;
                    if (beforeElement && beforeElement.parentNode === parent) {
                        referenceNode = beforeElement;
                    } else if (afterElement && afterElement.parentNode === parent) {
                        referenceNode = afterElement.nextSibling;
                    }
                    return { parent, referenceNode };
                }
            }

            if (beforeElement && beforeElement.parentNode === additionalWorkItemsList) {
                referenceNode = beforeElement;
            } else if (afterElement && afterElement.parentNode === additionalWorkItemsList) {
                referenceNode = afterElement.nextSibling;
            }

            return { parent, referenceNode };
        }

        function normalizeComparableValue(value) {
            const trimmed = String(value ?? '').trim();
            if (trimmed === '') {
                return '';
            }
            const numericCandidate = trimmed.replace(',', '.');
            if (/^-?\d+(\.\d+)?$/.test(numericCandidate)) {
                const parsed = Number(numericCandidate);
                if (Number.isFinite(parsed)) {
                    return String(parsed);
                }
            }
            return trimmed.toLowerCase();
        }

        function getAdditionalWorkItemDefaults(workType) {
            const currentWorkType = String(workType || '').trim();
            const isAciType = currentWorkType === 'skim_coating' || currentWorkType === 'coating_floor';
            return {
                wall_length: '',
                wall_height: '',
                mortar_thickness: isAciType ? '3' : '2',
                layer_count: '1',
                plaster_sides: '1',
                skim_sides: '1',
                grout_thickness: '2',
                ceramic_length: '30',
                ceramic_width: '30',
                ceramic_thickness: '8',
            };
        }

        function hasAdditionalFieldChangedFromDefault(itemEl, key, expectedValue) {
            const currentValue = normalizeComparableValue(getAdditionalFieldValue(itemEl, key));
            const defaultValue = normalizeComparableValue(expectedValue);
            return currentValue !== defaultValue;
        }

        async function confirmAdditionalWorkItemRemoval(message) {
            if (typeof window.showConfirm === 'function') {
                return window.showConfirm({
                    title: 'Konfirmasi Hapus',
                    message,
                    confirmText: 'Hapus',
                    cancelText: 'Batal',
                    type: 'danger',
                });
            }
            return window.confirm(message);
        }

        function isAdditionalWorkItemFilled(itemEl) {
            if (!itemEl) {
                return false;
            }
            const materialInputs = itemEl.querySelectorAll('input[data-material-type-hidden="1"]');
            if (Array.from(materialInputs).some(input => String(input.value || '').trim() !== '')) {
                return true;
            }

            if (getAdditionalFieldValue(itemEl, 'work_area') || getAdditionalFieldValue(itemEl, 'work_field')) {
                return true;
            }

            const workType = getAdditionalFieldValue(itemEl, 'work_type');
            const fieldDefaults = getAdditionalWorkItemDefaults(workType);
            return Object.entries(fieldDefaults).some(([key, defaultValue]) =>
                hasAdditionalFieldChangedFromDefault(itemEl, key, defaultValue),
            );
        }

        function collectAdditionalMaterialTypeFilters(itemEl) {
            const filters = {};
            bundleMaterialTypeOrder.forEach(type => {
                const inputs = itemEl.querySelectorAll(`[data-material-wrap="${type}"] input[data-material-type-hidden="1"]`);
                const values = uniqueFilterTokens(
                    Array.from(inputs).map(input => String(input?.value || '').trim()),
                );
                if (!values.length) {
                    return;
                }
                filters[type] = values.length === 1 ? values[0] : values;
            });
            return filters;
        }

        function collectAdditionalMaterialCustomizeFilters(itemEl) {
            return collectCustomizeFiltersFromRoot(itemEl);
        }

        function collectAdditionalWorkItems(strict = false) {
            if (!additionalWorkItemsList) {
                return { items: [], error: null };
            }

            const rows = getAllAdditionalWorkRows();
            const items = [];
            for (let i = 0; i < rows.length; i += 1) {
                const row = rows[i];
                const rowKind = normalizeBundleRowKind(getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area');
                const workArea = getAdditionalFieldValue(row, 'work_area');
                const workField = getAdditionalFieldValue(row, 'work_field');
                const workType = getAdditionalFieldValue(row, 'work_type');
                const wallLength = getAdditionalFieldValue(row, 'wall_length');
                const wallHeight = getAdditionalFieldValue(row, 'wall_height');
                if (strict && !workArea) {
                    return {
                        items: [],
                        error: {
                            message: `Item tambahan ${i + 2} belum mengisi Area.`,
                            focusEl: row.querySelector('[data-field-display="work_area"]'),
                        },
                    };
                }
                if (strict && rowKind !== 'item' && !workField) {
                    return {
                        items: [],
                        error: {
                            message: `Item tambahan ${i + 2} belum mengisi Bidang.`,
                            focusEl: row.querySelector('[data-field-display="work_field"]'),
                        },
                    };
                }
                if (strict && !workType) {
                    return {
                        items: [],
                        error: {
                            message: `Item tambahan ${i + 2} belum memilih Item Pekerjaan.`,
                            focusEl: row.querySelector('[data-field-display="work_type"]'),
                        },
                    };
                }
                if (!workType) {
                    continue;
                }
                if (strict && !wallLength) {
                    return { items: [], error: { message: `Item tambahan ${i + 2} wajib isi Panjang.`, focusEl: row.querySelector('[data-field="wall_length"]') } };
                }
                if (strict && workType !== 'brick_rollag' && !wallHeight) {
                    return {
                        items: [],
                        error: {
                            message: `Item tambahan ${i + 2} wajib isi Tinggi/Lebar.`,
                            focusEl: row.querySelector('[data-field="wall_height"]'),
                        },
                    };
                }

                items.push(
                    normalizeBundleItem(
                        {
                            title: getAdditionalFieldValue(row, 'title'),
                            row_kind: rowKind,
                            work_area: workArea,
                            work_field: workField,
                            work_type: workType,
                            wall_length: wallLength,
                            wall_height: wallHeight,
                            mortar_thickness: getAdditionalFieldValue(row, 'mortar_thickness'),
                            layer_count: getAdditionalFieldValue(row, 'layer_count'),
                            plaster_sides: getAdditionalFieldValue(row, 'plaster_sides'),
                            skim_sides: getAdditionalFieldValue(row, 'skim_sides'),
                            grout_thickness: getAdditionalFieldValue(row, 'grout_thickness'),
                            ceramic_length: getAdditionalFieldValue(row, 'ceramic_length'),
                            ceramic_width: getAdditionalFieldValue(row, 'ceramic_width'),
                            ceramic_thickness: getAdditionalFieldValue(row, 'ceramic_thickness'),
                            active_fields: getAdditionalActiveParameterFields(row),
                            material_type_filters: collectAdditionalMaterialTypeFilters(row),
                            material_customize_filters: collectAdditionalMaterialCustomizeFilters(row),
                        },
                        i + 1,
                    ),
                );
            }

            return { items, error: null };
        }

        function buildBundleItems(strict = false) {
            const { items: additionalItems, error } = collectAdditionalWorkItems(strict);
            if (error) {
                return { items: [], error };
            }
            if (additionalItems.length === 0) {
                return { items: [], error: null };
            }

            const mainItem = collectMainWorkItem();
            if (!mainItem) {
                return {
                    items: [],
                    error: strict
                        ? {
                              message: 'Item pekerjaan utama wajib diisi sebelum menambahkan item berikutnya.',
                              focusEl: mainWorkTypeDisplayInput,
                          }
                        : null,
                };
            }

            if (strict && !mainItem.wall_length) {
                return {
                    items: [],
                    error: {
                        message: 'Item pekerjaan utama wajib isi Panjang.',
                        focusEl: document.getElementById('wallLength'),
                    },
                };
            }
            if (strict && additionalItems.length > 0 && !String(mainItem.work_area || '').trim()) {
                return {
                    items: [],
                    error: {
                        message: 'Area pada item pekerjaan utama wajib diisi.',
                        focusEl: document.getElementById('workAreaDisplay'),
                    },
                };
            }
            const requiresMainWorkField = additionalItems.some(
                item => normalizeBundleRowKind(item?.row_kind || 'area') !== 'item',
            );
            if (strict && requiresMainWorkField && !String(mainItem.work_field || '').trim()) {
                return {
                    items: [],
                    error: {
                        message: 'Bidang pada item pekerjaan utama wajib diisi.',
                        focusEl: document.getElementById('workFieldDisplay'),
                    },
                };
            }
            if (strict && mainItem.work_type !== 'brick_rollag' && !mainItem.wall_height) {
                return {
                    items: [],
                    error: {
                        message: 'Item pekerjaan utama wajib isi Tinggi/Lebar.',
                        focusEl: document.getElementById('wallHeight'),
                    },
                };
            }

            return { items: [mainItem, ...additionalItems], error: null };
        }

        function syncBundleFromForms() {
            const result = buildBundleItems(false);
            const items = result.items || [];
            syncMaterialCustomizeFiltersPayload();
            if (workItemsPayloadInput) {
                workItemsPayloadInput.value = items.length >= 2 ? JSON.stringify(items) : '';
            }
            if (enableBundleModeInput) {
                enableBundleModeInput.value = items.length >= 2 ? '1' : '0';
            }
            setMainFormRequired(!(items.length >= 2));

            if (additionalWorkItemsSection) {
                additionalWorkItemsSection.style.display =
                    additionalWorkItemsList && additionalWorkItemsList.children.length > 0 ? 'block' : 'none';
            }

            refreshAdditionalWorkItemHeader();

            if (addWorkItemBtn) {
                addWorkItemBtn.disabled = false;
            }

            if (removeWorkItemBtn) {
                const hasAdditionalRows = getAllAdditionalWorkRows().length > 0;
                removeWorkItemBtn.disabled = !hasAdditionalRows;
            }
        }

        function applyAdditionalWorkItemVisibility(itemEl) {
            const workType = getAdditionalFieldValue(itemEl, 'work_type');
            const hasWorkType = workType !== '';
            const isRollag = workType === 'brick_rollag';
            const isFloorLike = ['floor_screed', 'coating_floor', 'tile_installation', 'grout_tile', 'adhesive_mix']
                .includes(workType);
            const showLayer = workType === 'brick_rollag' || workType === 'painting';
            const showPlaster = workType === 'wall_plastering';
            const showSkim = workType === 'skim_coating';
            const showGrout = ['tile_installation', 'grout_tile', 'plinth_ceramic', 'adhesive_mix', 'plinth_adhesive_mix']
                .includes(workType);
            const showCeramicDim = workType === 'grout_tile';
            const showMortar = !['painting', 'grout_tile'].includes(workType);
            const isAci = workType === 'skim_coating' || workType === 'coating_floor';
            const requiredMaterials = Array.isArray(formulaMaterials[workType]) ? formulaMaterials[workType] : [];
            const showMaterialFilters = !!workType && requiredMaterials.length > 0;
            const wallHeightInput = itemEl.querySelector('[data-field="wall_height"]');
            const wallHeightUnit = itemEl.querySelector('[data-wrap="wall_height"] .unit');
            const wallHeightLabel = itemEl.querySelector('[data-wall-height-label]');
            const mortarInput = itemEl.querySelector('[data-field="mortar_thickness"]');
            const mortarUnit = itemEl.querySelector('[data-wrap="mortar_thickness"] .unit');
            const mortarLabel = itemEl.querySelector('[data-wrap="mortar_thickness"] > label');
            const layerLabel = itemEl.querySelector('[data-wrap="layer_count"] > label');
            const layerUnit = itemEl.querySelector('[data-wrap="layer_count"] .unit');
            const dimensionsContainer = itemEl.querySelector('.additional-dimensions-container');
            const nextMortarMode = isAci ? 'acian' : 'adukan';
            const prevMortarMode = mortarInput ? (mortarInput.dataset.mode || 'adukan') : 'adukan';
            const mortarModeChanged = prevMortarMode !== nextMortarMode;

            const toggleWrap = (name, visible, displayMode = 'flex') => {
                const wrap = itemEl.querySelector(`[data-wrap="${name}"]`);
                if (!wrap) return;
                wrap.style.display = visible ? displayMode : 'none';
            };

            const setMortarUnit = unit => {
                if (!mortarInput || !mortarUnit) return;
                const currentUnit = mortarInput.dataset.unit || 'cm';
                if (unit !== currentUnit) {
                    const currentValue = parseFloat(String(mortarInput.value || '').replace(',', '.'));
                    if (!isNaN(currentValue)) {
                        const converted = unit === 'mm' ? currentValue * 10 : currentValue / 10;
                        mortarInput.value = formatThicknessValue(converted);
                    }
                }
                mortarInput.dataset.unit = unit;
                mortarUnit.textContent = unit;
                if (unit === 'mm') {
                    mortarInput.step = '1';
                    mortarInput.min = '1';
                } else {
                    mortarInput.step = '0.1';
                    mortarInput.min = '0.1';
                }
            };

            const toggleMaterialWrap = (materialType, visible) => {
                const wrap = itemEl.querySelector(`[data-material-wrap="${materialType}"]`);
                if (!wrap) return;
                wrap.style.display = visible ? 'flex' : 'none';
                if (!visible) {
                    if (typeof wrap.__clearBundleMaterialTypeValues === 'function') {
                        wrap.__clearBundleMaterialTypeValues();
                        return;
                    }
                    wrap.querySelectorAll('input[data-material-type-hidden="1"]').forEach(input => {
                        input.value = '';
                    });
                    wrap.querySelectorAll('.autocomplete-input[data-material-display="1"]').forEach(input => {
                        input.value = '';
                    });
                }
            };

            if (dimensionsContainer) {
                dimensionsContainer.style.display = hasWorkType ? '' : 'none';
            }

            if (!hasWorkType) {
                toggleWrap('wall_length', false);
                toggleWrap('wall_height', false);
                toggleWrap('mortar_thickness', false);
                toggleWrap('layer_count', false);
                toggleWrap('plaster_sides', false);
                toggleWrap('skim_sides', false);
                toggleWrap('grout_thickness', false);
                toggleWrap('ceramic_length', false);
                toggleWrap('ceramic_width', false);
                toggleWrap('ceramic_thickness', false);
                toggleWrap('material_filters', false, 'block');
                bundleMaterialTypeOrder.forEach(type => toggleMaterialWrap(type, false));
                if (mortarInput) {
                    mortarInput.dataset.mode = 'adukan';
                }
                return;
            }

            toggleWrap('wall_length', true);
            toggleWrap('wall_height', !isRollag);
            toggleWrap('mortar_thickness', showMortar);
            toggleWrap('layer_count', showLayer);
            toggleWrap('plaster_sides', showPlaster);
            toggleWrap('skim_sides', showSkim);
            toggleWrap('grout_thickness', showGrout);
            toggleWrap('ceramic_length', showCeramicDim);
            toggleWrap('ceramic_width', showCeramicDim);
            toggleWrap('ceramic_thickness', showCeramicDim);
            toggleWrap('material_filters', showMaterialFilters, 'block');

            bundleMaterialTypeOrder.forEach(type => {
                let visible = showMaterialFilters && requiredMaterials.includes(type);
                if (type === 'ceramic_type') {
                    visible = showMaterialFilters && ['tile_installation', 'plinth_ceramic', 'adhesive_mix', 'plinth_adhesive_mix']
                        .includes(workType);
                }
                if (workType === 'grout_tile' && type === 'ceramic') {
                    visible = false;
                }
                toggleMaterialWrap(type, visible);
            });

            if (wallHeightLabel) {
                wallHeightLabel.textContent = isFloorLike ? 'Lebar' : 'Tinggi';
            }
            if (wallHeightUnit) {
                wallHeightUnit.textContent = 'M';
            }
            if (wallHeightInput) {
                wallHeightInput.step = '0.01';
                wallHeightInput.min = '0.01';
                wallHeightInput.placeholder = '';
            }
            if (mortarLabel) {
                mortarLabel.textContent = 'Tebal Adukan';
            }
            if (mortarInput) {
                mortarInput.dataset.mode = nextMortarMode;
            }
            setMortarUnit('cm');

            if (isAci) {
                if (mortarLabel) {
                    mortarLabel.textContent = 'Tebal Acian';
                }
                setMortarUnit('mm');
            }
            if (mortarModeChanged && mortarInput) {
                mortarInput.value = formatThicknessValue(isAci ? 3 : 2);
            }

            if (layerLabel && layerUnit) {
                if (workType === 'brick_rollag') {
                    layerLabel.textContent = 'Tingkat';
                    layerUnit.textContent = 'Tingkat';
                } else {
                    layerLabel.textContent = 'Lapis / Tingkat';
                    layerUnit.textContent = 'Lapis';
                }
            }

            if (workType === 'plinth_ceramic' || workType === 'plinth_adhesive_mix') {
                if (wallHeightLabel) {
                    wallHeightLabel.textContent = 'Tinggi';
                }
                if (wallHeightUnit) {
                    wallHeightUnit.textContent = 'cm';
                }
                if (wallHeightInput) {
                    wallHeightInput.step = '1';
                    wallHeightInput.min = '1';
                    wallHeightInput.placeholder = 'Tinggi plint (10-20)';
                }
            } else if (workType === 'adhesive_mix') {
                if (wallHeightLabel) {
                    wallHeightLabel.textContent = 'Lebar';
                }
            }
        }

        function attachAdditionalWorkItemEvents(itemEl) {
            const addBtn = itemEl.querySelector('[data-action="add"]');
            const removeBtn = itemEl.querySelector('[data-action="remove"]');
            const addFieldBtn = itemEl.querySelector('[data-action="add-field"]');
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            const workTypeSelect = itemEl.querySelector('[data-field="work_type"]');

            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    const newForm = createAdditionalWorkItemForm({}, itemEl);
                    const workTypeDisplay = newForm ? newForm.querySelector('[data-field-display="work_type"]') : null;
                    if (workTypeDisplay) {
                        workTypeDisplay.focus();
                        if (typeof workTypeDisplay.__openAdditionalWorkTypeList === 'function') {
                            workTypeDisplay.__openAdditionalWorkTypeList();
                        }
                    }
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', async function() {
                    if (isAdditionalWorkItemFilled(itemEl)) {
                        const confirmed = await confirmAdditionalWorkItemRemoval(
                            'Form item pekerjaan ini sudah terisi. Yakin ingin menghapus?',
                        );
                        if (!confirmed) {
                            return;
                        }
                    }
                    itemEl.remove();
                    refreshAdditionalWorkItemHeader();
                    syncBundleFromForms();
                });
            }

            if (addFieldBtn) {
                addFieldBtn.addEventListener('click', function() {
                    const areaValue = getAdditionalFieldValue(itemEl, 'work_area');
                    if (!areaValue) {
                        showTaxonomyActionError(
                            'Isi Area terlebih dahulu sebelum menambah Bidang baru.',
                            itemEl.querySelector('[data-field-display="work_area"]'),
                        );
                        return;
                    }
                    const afterTarget = findLastAdditionalRowByTaxonomy(areaValue, '', false) || itemEl;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_area: areaValue,
                            work_field: '',
                            work_type: '',
                            row_kind: 'field',
                        },
                        afterTarget,
                        'work_field',
                        { rowKind: 'field' },
                    );
                });
            }

            if (addItemBtn) {
                addItemBtn.addEventListener('click', function() {
                    const areaValue = getAdditionalFieldValue(itemEl, 'work_area');
                    const fieldValue = getAdditionalFieldValue(itemEl, 'work_field');
                    if (!areaValue) {
                        showTaxonomyActionError(
                            'Isi Area terlebih dahulu sebelum menambah Item Pekerjaan.',
                            itemEl.querySelector('[data-field-display="work_area"]'),
                        );
                        return;
                    }
                    const afterTarget = findLastAdditionalRowByTaxonomy(areaValue, fieldValue, true) || itemEl;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_area: areaValue,
                            work_field: fieldValue,
                            work_type: '',
                            row_kind: 'item',
                        },
                        afterTarget,
                        'work_type',
                        { rowKind: 'item' },
                    );
                });
            }

            if (workTypeSelect) {
                workTypeSelect.addEventListener('change', function() {
                    applyAdditionalWorkItemVisibility(itemEl);
                    syncBundleFromForms();
                });
            }

            itemEl.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', syncBundleFromForms);
                el.addEventListener('change', syncBundleFromForms);
            });
        }

        function parseBundleItemsFromHidden() {
            if (!workItemsPayloadInput || !workItemsPayloadInput.value) {
                return [];
            }
            try {
                const parsed = JSON.parse(workItemsPayloadInput.value);
                if (!Array.isArray(parsed)) {
                    return [];
                }
                return parsed.map((item, index) => normalizeBundleItem(item, index));
            } catch (e) {
                return [];
            }
        }

        const addFieldFromMainBtn = document.getElementById('addFieldFromMainBtn');
        const addItemFromMainBtn = document.getElementById('addItemFromMainBtn');

        if (addFieldFromMainBtn) {
            addFieldFromMainBtn.addEventListener('click', function() {
                const context = getMainTaxonomyContext();
                if (!context.work_area) {
                    showTaxonomyActionError(
                        'Isi Area utama terlebih dahulu sebelum menambah Bidang.',
                        document.getElementById('workAreaDisplay'),
                    );
                    return;
                }
                const afterTarget = findLastAdditionalRowByTaxonomy(context.work_area, '', false);
                const firstAdditionalRow = additionalWorkItemsList
                    ? additionalWorkItemsList.querySelector('[data-additional-work-item="true"]')
                    : null;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_area: context.work_area,
                            work_field: '',
                            work_type: '',
                            row_kind: 'field',
                        },
                        afterTarget,
                        'work_field',
                        { rowKind: 'field', beforeElement: afterTarget ? null : firstAdditionalRow, targetMainArea: true },
                    );
                });
            }

        if (addItemFromMainBtn) {
            addItemFromMainBtn.addEventListener('click', function() {
                const context = getMainTaxonomyContext();
                if (!context.work_area) {
                    showTaxonomyActionError(
                        'Isi Area utama terlebih dahulu sebelum menambah Item Pekerjaan.',
                        document.getElementById('workAreaDisplay'),
                    );
                    return;
                }
                const afterTarget = findLastAdditionalRowByTaxonomy(context.work_area, context.work_field, true);
                const firstAdditionalRow = additionalWorkItemsList
                    ? additionalWorkItemsList.querySelector('[data-additional-work-item="true"]')
                    : null;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_area: context.work_area,
                            work_field: context.work_field,
                            work_type: '',
                            row_kind: 'item',
                        },
                        afterTarget,
                        'work_type',
                        { rowKind: 'item', beforeElement: afterTarget ? null : firstAdditionalRow, targetMainArea: true },
                    );
                });
            }

        if (addWorkItemBtn) {
            addWorkItemBtn.addEventListener('click', function() {
                const mainItem = collectMainWorkItem();
                if (!mainItem) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Isi item pekerjaan utama dulu, lalu klik + untuk tambah area berikutnya.', 'error');
                    } else {
                        alert('Isi item pekerjaan utama dulu, lalu klik + untuk tambah area berikutnya.');
                    }
                    if (mainWorkTypeDisplayInput) {
                        mainWorkTypeDisplayInput.focus();
                    }
                    return;
                }

                createAndFocusAdditionalWorkItem(
                    { work_area: '', work_field: '', work_type: '', row_kind: 'area' },
                    null,
                    'work_area',
                    { rowKind: 'area' },
                );
            });
        }

        if (removeWorkItemBtn) {
            removeWorkItemBtn.addEventListener('click', async function() {
                const rows = getAllAdditionalWorkRows();
                if (rows.length === 0) {
                    return;
                }
                const lastRow = rows.length > 0 ? rows[rows.length - 1] : null;
                if (!lastRow) {
                    return;
                }
                if (isAdditionalWorkItemFilled(lastRow)) {
                    const confirmed = await confirmAdditionalWorkItemRemoval(
                        'Form item pekerjaan terakhir sudah terisi. Yakin ingin menghapus?',
                    );
                    if (!confirmed) {
                        return;
                    }
                }
                lastRow.remove();
                refreshAdditionalWorkItemHeader();
                syncBundleFromForms();
            });
        }

        const restoredBundleItems = parseBundleItemsFromHidden();
        if (restoredBundleItems.length > 1) {
            for (let i = 1; i < restoredBundleItems.length; i += 1) {
                createAdditionalWorkItemForm(restoredBundleItems[i]);
            }
        }
        syncBundleFromForms();
        if (initialMaterialCustomizeFiltersPayloadRaw) {
            const initialMainCustomizeFilters = parseObjectPayload(initialMaterialCustomizeFiltersPayloadRaw);
            const mainRoot = document.getElementById('inputFormContainer') || document;
            applyMaterialCustomizeFiltersToPanels(mainRoot, initialMainCustomizeFilters);
            syncBundleFromForms();
        }
        bindAutoHideEmptyCustomizePanels();
        collapseEmptyCustomizePanels(document);

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

                if (additionalWorkItemsList) {
                    additionalWorkItemsList.innerHTML = '';
                }
                const mainAreaChildrenHost = getMainAreaChildrenHost();
                if (mainAreaChildrenHost) {
                    mainAreaChildrenHost.innerHTML = '';
                }
                if (additionalWorkItemsSection) {
                    additionalWorkItemsSection.style.display = 'none';
                }
                if (workItemsPayloadInput) {
                    workItemsPayloadInput.value = '';
                }
                if (enableBundleModeInput) {
                    enableBundleModeInput.value = '0';
                }
                setMainFormRequired(true);
                syncBundleFromForms();

                ensureCustomFormVisible();
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

            const customizePanelState = {};
            document.querySelectorAll('.customize-panel[data-customize-panel]').forEach(panelEl => {
                const panelId = String(panelEl.id || '').trim();
                const materialKey = String(panelEl.dataset.customizePanel || '').trim();
                if (!panelId || !materialKey) {
                    return;
                }

                const fieldValues = {};
                panelEl.querySelectorAll(`select[data-customize-filter="${materialKey}"][data-filter-key]`).forEach(selectEl => {
                    const filterKey = String(selectEl.dataset.filterKey || '').trim();
                    const value = String(selectEl.value || '').trim();
                    if (!filterKey || !value) {
                        return;
                    }
                    fieldValues[filterKey] = value;
                });

                const hasValues = Object.keys(fieldValues).length > 0;
                const isOpen = !panelEl.hidden;
                if (!hasValues) {
                    return;
                }

                customizePanelState[panelId] = {
                    material_key: materialKey,
                    values: fieldValues,
                    open: isOpen,
                };
            });

            if (Object.keys(customizePanelState).length > 0) {
                data.customize_panel_state = customizePanelState;
            }
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

        function isReloadNavigation() {
            if (typeof performance === 'undefined') {
                return false;
            }
            try {
                const entries = performance.getEntriesByType('navigation');
                if (Array.isArray(entries) && entries.length > 0) {
                    return entries[0].type === 'reload';
                }
            } catch (error) {
                // noop
            }
            if (performance && performance.navigation) {
                return performance.navigation.type === 1;
            }
            return false;
        }

        function isBackForwardNavigation() {
            if (typeof performance === 'undefined') {
                return false;
            }
            try {
                const entries = performance.getEntriesByType('navigation');
                if (Array.isArray(entries) && entries.length > 0) {
                    return entries[0].type === 'back_forward';
                }
            } catch (error) {
                // noop
            }
            return false;
        }

        function shouldRestoreCalculationSession() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('resume') === '1') {
                params.delete('resume');
                params.delete('auto_submit');
                return !params.toString();
            }
            // Restore session on refresh and browser back/forward navigation.
            return isReloadNavigation() || isBackForwardNavigation();
        }

        function applyCalculationSession(state) {
            if (!form || !state || typeof state !== 'object') return;

            const workTypeInput = form.querySelector('#workTypeSelector');
            const workTypeValue = state.work_type_select || state.work_type || '';
            const expectsMm = ['skim_coating', 'coating_floor'].includes(workTypeValue);
            let pendingMortarThickness = null;
            let pendingCustomizePanelState = null;
            let pendingMaterialCustomizeFilters = null;
            if (workTypeInput && workTypeValue) {
                workTypeInput.value = workTypeValue;
                workTypeInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(field => {
                field.checked = false;
            });

            Object.entries(state).forEach(([key, value]) => {
                if (key === 'work_type_select' || key === 'work_type') return;
                if (key === 'customize_panel_state') {
                    pendingCustomizePanelState = value;
                    return;
                }
                if (key === 'material_customize_filters' && value && typeof value === 'object') {
                    pendingMaterialCustomizeFilters = value;
                    return;
                }
                if (key === 'material_customize_filters_payload') {
                    const parsedCustomizePayload = parseObjectPayload(value);
                    if (parsedCustomizePayload && Object.keys(parsedCustomizePayload).length > 0) {
                        pendingMaterialCustomizeFilters = parsedCustomizePayload;
                    }
                    const hiddenCustomizeInput = form.querySelector('[name="material_customize_filters_payload"]');
                    if (hiddenCustomizeInput) {
                        hiddenCustomizeInput.value = String(value || '');
                    }
                    return;
                }

                if (key === 'work_areas' || key === 'work_fields') {
                    if (workTaxonomyFilterApi && typeof workTaxonomyFilterApi.setValues === 'function') {
                        const normalizedValues = uniqueFilterTokens(Array.isArray(value) ? value : [value]);
                        workTaxonomyFilterApi.setValues(key === 'work_areas' ? 'area' : 'field', normalizedValues);
                    }
                    return;
                }
                 
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
                const fieldList = Array.from(fields);
                const checkableFields = fieldList.filter(field => field.type === 'checkbox' || field.type === 'radio');
                const nonCheckableFields = fieldList.filter(field => field.type !== 'checkbox' && field.type !== 'radio');

                if (checkableFields.length > 0) {
                    checkableFields.forEach(field => {
                        field.checked = values.includes(String(field.value));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    // Keep hidden fallback inputs (value="0") untouched when same name also has checkboxes.
                    return;
                }

                nonCheckableFields.forEach(field => {
                    if (field.multiple && field.options) {
                        Array.from(field.options).forEach(option => {
                            option.selected = values.includes(String(option.value));
                        });
                    } else {
                        field.value = values[0];
                    }
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                    field.dispatchEvent(new Event('input', { bubbles: true }));
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

            if (additionalWorkItemsList) {
                additionalWorkItemsList.innerHTML = '';
                const mainAreaChildrenHost = getMainAreaChildrenHost();
                if (mainAreaChildrenHost) {
                    mainAreaChildrenHost.innerHTML = '';
                }
                const restoredBundleItems = parseBundleItemsFromHidden();
                if (restoredBundleItems.length > 1) {
                    for (let i = 1; i < restoredBundleItems.length; i += 1) {
                        createAdditionalWorkItemForm(restoredBundleItems[i]);
                    }
                }
            }
            syncBundleFromForms();

            if (pendingMaterialCustomizeFilters && typeof pendingMaterialCustomizeFilters === 'object') {
                const mainRoot = document.getElementById('inputFormContainer') || document;
                applyMaterialCustomizeFiltersToPanels(mainRoot, pendingMaterialCustomizeFilters);
                syncBundleFromForms();
            }

            if (pendingCustomizePanelState && typeof pendingCustomizePanelState === 'object') {
                Object.entries(pendingCustomizePanelState).forEach(([panelId, payload]) => {
                    const panelEl = document.getElementById(panelId);
                    if (!panelEl) {
                        return;
                    }

                    const materialKey = String(payload?.material_key || panelEl.dataset.customizePanel || '').trim();
                    if (!materialKey) {
                        return;
                    }

                    const values = payload && typeof payload === 'object' && payload.values && typeof payload.values === 'object'
                        ? payload.values
                        : {};
                    const hasRestoredValues = Object.keys(values).length > 0;

                    if (!hasRestoredValues) {
                        panelEl.hidden = true;
                        return;
                    }

                    if (panelEl.hidden) {
                        const openBtn = document.querySelector(`[data-customize-panel-id="${panelId}"]`);
                        if (openBtn instanceof HTMLElement) {
                            openBtn.click();
                        } else {
                            panelEl.hidden = false;
                        }
                    }

                    const selectEls = Array.from(
                        panelEl.querySelectorAll(`select[data-customize-filter="${materialKey}"][data-filter-key]`),
                    );

                    selectEls.forEach(selectEl => {
                        const filterKey = String(selectEl.dataset.filterKey || '').trim();
                        if (!filterKey || !Object.prototype.hasOwnProperty.call(values, filterKey)) {
                            return;
                        }
                        const nextValue = String(values[filterKey] || '').trim();
                        selectEl.value = nextValue;
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    const shouldOpen = hasRestoredValues;
                    panelEl.hidden = !shouldOpen;
                });
            }

            collapseEmptyCustomizePanels(document);
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

        // Handle Back/Forward Navigation (BFCache)
        window.addEventListener('pageshow', function(event) {
            resetLoadingState();

            // If page was restored from BFCache and has resume parameter
            if (event.persisted && shouldRestoreCalculationSession()) {
                const formContainer = document.querySelector('.two-column-layout');
                if (formContainer) {
                    formContainer.style.opacity = '0';
                }

                setTimeout(() => {
                    restoreCalculationSession();
                    if (formContainer) {
                        formContainer.style.opacity = '1';
                    }
                }, 50);
            }
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
                syncMaterialCustomizeFiltersPayload();
                const bundleBuild = buildBundleItems(true);
                if (bundleBuild.error) {
                    e.preventDefault();
                    if (typeof window.showToast === 'function') {
                        window.showToast(bundleBuild.error.message, 'error');
                    } else {
                        alert(bundleBuild.error.message);
                    }
                    if (bundleBuild.error.focusEl && typeof bundleBuild.error.focusEl.focus === 'function') {
                        bundleBuild.error.focusEl.focus();
                    }
                    return;
                }
                const bundleItems = bundleBuild.items || [];
                const isBundleModeEnabled = bundleItems.length >= 2;

                if (!isBundleModeEnabled) {
                    // Client-side validation for work type selection (single mode)
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

                if (isBundleModeEnabled) {
                    setMainFormRequired(false);
                    if (workItemsPayloadInput) {
                        workItemsPayloadInput.value = JSON.stringify(bundleItems);
                    }
                    if (enableBundleModeInput) {
                        enableBundleModeInput.value = '1';
                    }
                } else if (workItemsPayloadInput) {
                    workItemsPayloadInput.value = '';
                    if (enableBundleModeInput) {
                        enableBundleModeInput.value = '0';
                    }
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
            // Persist latest values so browser refresh keeps dynamic filters as well.
            saveCalculationSession();

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

        // Restore session BEFORE showing page to prevent flicker
        if (shouldRestoreCalculationSession()) {
            // Hide content during restoration
            const formContainer = document.querySelector('.two-column-layout');
            if (formContainer) {
                formContainer.style.opacity = '0';
                formContainer.style.transition = 'opacity 0.2s ease-in';
            }

            // Restore session
            restoreCalculationSession();

            // Show content after restoration
            requestAnimationFrame(() => {
                if (formContainer) {
                    formContainer.style.opacity = '1';
                }
            });
        } else {
            restoreCalculationSession();
        }
    })();
</script>
@endpush
