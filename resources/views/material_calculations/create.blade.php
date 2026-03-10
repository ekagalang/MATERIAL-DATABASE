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
    $selectedWorkFloors = old('work_floors', request('work_floors', []));
    $selectedWorkFloors = is_array($selectedWorkFloors) ? $selectedWorkFloors : [$selectedWorkFloors];
    $selectedWorkFloors = array_values(array_filter(array_map(static fn($value) => trim((string) $value), $selectedWorkFloors), static fn($value) => $value !== ''));
    $selectedWorkAreas = old('work_areas', request('work_areas', []));
    $selectedWorkAreas = is_array($selectedWorkAreas) ? $selectedWorkAreas : [$selectedWorkAreas];
    $selectedWorkAreas = array_values(array_filter(array_map(static fn($value) => trim((string) $value), $selectedWorkAreas), static fn($value) => $value !== ''));
    $selectedWorkFields = old('work_fields', request('work_fields', []));
    $selectedWorkFields = is_array($selectedWorkFields) ? $selectedWorkFields : [$selectedWorkFields];
    $selectedWorkFields = array_values(array_filter(array_map(static fn($value) => trim((string) $value), $selectedWorkFields), static fn($value) => $value !== ''));
    $selectedPriceFilters = old('price_filters', ['best']);
    $selectedPriceFilters = is_array($selectedPriceFilters) ? $selectedPriceFilters : [$selectedPriceFilters];
    $initialUseStoreFilter = old('use_store_filter', '1') == '1';
    $initialAllowMixedStore = old('allow_mixed_store') == '1';
    $initialStoreRadiusScope = old('store_radius_scope');
    if (!in_array($initialStoreRadiusScope, ['within', 'outside'], true)) {
        $initialStoreRadiusScope = $initialUseStoreFilter ? 'within' : '';
    }
    $projectStoreRadiusDefaultKm = (float) ($projectStoreRadiusDefaultKm ?? 10);
    $projectStoreRadiusFinalKm = (float) ($projectStoreRadiusFinalKm ?? max(15, $projectStoreRadiusDefaultKm));
    $initialStoreSearchMode = 'complete_within';
    if ($initialAllowMixedStore) {
        $initialStoreSearchMode = 'incomplete';
    } elseif ($initialUseStoreFilter && $initialStoreRadiusScope === 'outside') {
        $initialStoreSearchMode = 'complete_outside';
    } elseif ($initialUseStoreFilter && $initialStoreRadiusScope === 'within') {
        $initialStoreSearchMode = 'complete_within';
    }
    $selectedProjectStoreRadiusKm = old('project_store_radius_km', request('project_store_radius_km', $projectStoreRadiusDefaultKm));
    $selectedProjectStoreRadiusFinalKm = old(
        'project_store_radius_final_km',
        request('project_store_radius_final_km', $projectStoreRadiusFinalKm),
    );
    $materialTypeLabels = [
        'brick' => 'Bata',
        'cement' => 'Semen',
        'sand' => 'Pasir',
        'cat' => 'Cat',
        'ceramic_type' => 'Keramik',
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

<div id="calcCreateSearchScope">
    <div
        id="materialCalculationVueBridge"
        class="d-none"
        aria-hidden="true"
        data-vue-bridge="material-calculation-create"
        data-form-selector="#calculationForm"></div>
<div class="calc-header-row">
    <h3 class="calc-style mb-0"><i class="bi bi-calculator text-primary"></i> Hitung Item Pekerjaan Proyek</h3>
</div>

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
                    <input type="hidden"
                           id="projectStoreRadiusKm"
                           name="project_store_radius_km"
                           value="{{ $selectedProjectStoreRadiusKm }}">
                    <input type="hidden"
                           id="projectStoreRadiusFinalKm"
                           name="project_store_radius_final_km"
                           value="{{ $selectedProjectStoreRadiusFinalKm }}">
                    <small class="ssm-radius-inline-note d-block mb-2">
                        Radius pencarian toko mengikuti setting global. Ubah di menu <b>Pengaturan &gt; Radius Pencarian Toko</b>.
                    </small>
                    <input type="hidden" name="use_store_filter" value="0">
                    <input type="hidden" name="allow_mixed_store" value="0">
                    <input type="hidden" name="store_radius_scope" id="storeRadiusScopeValue" value="{{ $initialStoreRadiusScope }}">
                    <input type="hidden" id="storeSearchModeValue" value="{{ $initialStoreSearchMode }}">
                    <div class="ssm-complete-layout">
                        <div class="ssm-row ssm-row-group">
                            <input type="checkbox" id="storeModeCompleteGroupCheck"
                                {{ in_array($initialStoreSearchMode, ['complete_within', 'complete_outside'], true) ? 'checked' : '' }}>
                            <label for="storeModeCompleteGroupCheck" class="ssm-label">Lengkap</label>
                        </div>
                        <div class="ssm-complete-children">
                            <div class="ssm-row ssm-row-sub">
                                <input type="checkbox" id="storeModeCompleteWithinCheck"
                                    {{ $initialStoreSearchMode === 'complete_within' ? 'checked' : '' }}>
                                <label for="storeModeCompleteWithinCheck" class="ssm-label">Dalam Radius 1</label>
                                <small class="ssm-desc" id="storeModeCompleteWithinDesc">
                                    : Mencari Toko Dengan Material Lengkap Dalam Radius {{ $selectedProjectStoreRadiusKm }} dari Proyek.
                                </small>
                            </div>
                            <div class="ssm-row ssm-row-sub">
                                <input type="checkbox" id="storeModeCompleteOutsideCheck"
                                    {{ $initialStoreSearchMode === 'complete_outside' ? 'checked' : '' }}>
                                <label for="storeModeCompleteOutsideCheck" class="ssm-label">Sampai Radius 2</label>
                                <small class="ssm-desc" id="storeModeCompleteOutsideDesc">
                                    : Mencari Toko Dengan Material Lengkap Sampai Radius {{ $selectedProjectStoreRadiusFinalKm }} dari Proyek.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="ssm-row">
                        <input type="checkbox" id="storeModeIncompleteCheck"
                            {{ $initialStoreSearchMode === 'incomplete' ? 'checked' : '' }}>
                        <label for="storeModeIncompleteCheck" class="ssm-label">Tidak Lengkap</label>
                        <small class="ssm-desc" id="storeModeIncompleteDesc">
                            : Mencari Material Sedapatnya Secara Bertahap Mulai Dari Toko Terdekat Sampai Lengkap
                        </small>
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
            </div> {{-- /.left-column --}}

            {{-- RIGHT GRID SLOT: FILTER BY --}}
            <div class="filter-right-column">
                <div id="filterByRightColumn"></div>
                <div class="calc-header-row">
                    <div class="calc-inline-search" id="calcInlineSearch" role="search" aria-label="Cari teks pada halaman perhitungan">
                        <div class="calc-inline-search-inputwrap">
                            <i class="bi bi-search calc-inline-search-icon" aria-hidden="true"></i>
                            <input type="text"
                                   id="calcPageSearchInput"
                                   class="calc-inline-search-input"
                                   placeholder="Cari teks, label, atau isi input..."
                                   autocomplete="off">
                            <div class="calc-inline-search-suffix">
                                <span class="calc-inline-search-count" id="calcPageSearchCount" aria-live="polite">0 / 0</span>
                                <button type="button" class="calc-inline-search-nav" id="calcPageSearchPrev" aria-label="Hasil sebelumnya" title="Sebelumnya">
                                    <i class="bi bi-arrow-up" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="calc-inline-search-nav" id="calcPageSearchNext" aria-label="Hasil berikutnya" title="Berikutnya">
                                    <i class="bi bi-arrow-down" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="calc-inline-search-clear" id="calcPageSearchClear" aria-label="Hapus pencarian" title="Hapus">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: FILTERS --}}
            <div class="right-column">
                <div class="calc-taxonomy-scroll-tracker" id="calcTaxonomyScrollTracker" hidden aria-live="polite">
                    <div class="calc-taxonomy-scroll-card is-floor">
                        <span class="calc-taxonomy-scroll-label">Lantai</span>
                        <span class="calc-taxonomy-scroll-value" data-scroll-active-floor>-</span>
                    </div>
                    <div class="calc-taxonomy-scroll-card is-area">
                        <span class="calc-taxonomy-scroll-label">Area</span>
                        <span class="calc-taxonomy-scroll-value" data-scroll-active-area>-</span>
                        <button type="button"
                                class="taxonomy-level-btn calc-taxonomy-scroll-action is-area-action"
                                data-scroll-action="add-area"
                                title="Tambah Area">
                            + Area
                        </button>
                    </div>
                    <div class="calc-taxonomy-scroll-card is-field">
                        <span class="calc-taxonomy-scroll-label">Bidang</span>
                        <span class="calc-taxonomy-scroll-value" data-scroll-active-field>-</span>
                        <button type="button"
                                class="taxonomy-level-btn calc-taxonomy-scroll-action is-field-action"
                                data-scroll-action="add-field"
                                title="Tambah Bidang">
                            + Bidang
                        </button>
                    </div>
                </div>
                <div class="taxonomy-tree-main taxonomy-group-card taxonomy-main-horizontal">
                    <div class="taxonomy-node taxonomy-node-floor">
                        <div class="form-group work-floor-group taxonomy-card taxonomy-card-floor taxonomy-inline-group">
                            <label>Lantai</label>
                            <div class="material-type-filter-body">
                                <div class="material-type-rows" id="workFloorRows" data-taxonomy-kind="floor" data-initial-values='@json($selectedWorkFloors)'>
                                    <div class="material-type-row material-type-row-base" data-taxonomy-kind="floor">
                                        <div class="input-wrapper">
                                            <div class="work-type-autocomplete">
                                                <div class="work-type-input">
                                                    <input type="text"
                                                           id="workFloorDisplay"
                                                           class="autocomplete-input"
                                                           placeholder="Pilih atau ketik lantai..."
                                                           autocomplete="off"
                                                           value="{{ $selectedWorkFloors[0] ?? '' }}"
                                                           data-taxonomy-display="1">
                                                </div>
                                                <div class="autocomplete-list" id="workFloor-list"></div>
                                            </div>
                                            <input type="hidden"
                                                   id="workFloorValue"
                                                   name="work_floors[]"
                                                   value="{{ $selectedWorkFloors[0] ?? '' }}"
                                                   data-taxonomy-hidden="1">
                                        </div>
                                        <div class="material-type-row-actions">
                                            <button type="button" class="material-type-row-btn material-type-row-btn-delete" data-taxonomy-action="remove" data-taxonomy-kind="floor" title="Hapus baris">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <button type="button" class="material-type-row-btn material-type-row-btn-add" data-taxonomy-action="add" data-taxonomy-kind="floor" title="Tambah baris">
                                                <i class="bi bi-plus-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="taxonomy-level-actions">
                                <button type="button" id="addAreaFromMainBtn" class="taxonomy-level-btn" title="Area">
                                    + Area
                                </button>
                            </div>
                        </div>

                        <div class="taxonomy-node-children">
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
                                                <button type="button" id="toggleMainFieldItemVisibilityBtn" class="taxonomy-level-btn taxonomy-toggle-item-btn" title="Sembunyikan Item Pekerjaan pada bidang ini" aria-label="Sembunyikan Item Pekerjaan pada bidang ini" aria-pressed="false">
                                                    <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                                </button>
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
                                                    <div class="work-type-input work-type-input-with-action">
                                                        <input type="text"
                                                               id="workTypeDisplay"
                                                               class="autocomplete-input"
                                                               placeholder="Pilih atau ketik item pekerjaan..."
                                                               autocomplete="off"
                                                               value="{{ $selectedWorkTypeLabel }}"
                                                               {{ request('formula_code') ? 'readonly' : '' }}
                                                               required>
                                                        <button type="button"
                                                                id="removeMainItemBtn"
                                                                class="additional-worktype-suffix-btn"
                                                                title="Hapus item pekerjaan utama dan promosikan item berikutnya"
                                                                aria-label="Hapus item pekerjaan utama dan promosikan item berikutnya"
                                                                hidden
                                                                disabled>
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
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
                                                <div class="dimension-area-summary worktype-inline-summary" id="mainDimensionAreaSummary">
                                                    <div class="dimension-area-summary-value" id="mainDimensionAreaValue">-</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="inputFormContainer" style="margin-bottom: 15px;">
                    <div id="brickForm" class="work-type-form">

                        {{-- DIMENSI - VERTICAL LAYOUT --}}
                        <div class="dimensions-container-vertical">
                            <div class="dimension-area-layout" id="mainDimensionAreaLayout">
                                <div class="dimension-area-inputs">
                                    <div class="dimension-item">
                                        <label>Panjang</label>
                                        <div class="input-with-unit">
                                            <input type="text" inputmode="text" name="wall_length" id="wallLength" data-allow-expression="1" step="0.01" min="0.01"
                                                value="{{ old('wall_length', request('wall_length')) }}" required>
                                            <span class="unit">M</span>
                                            <span class="dimension-expression-hint" data-expression-hint hidden></span>
                                        </div>
                                    </div>

                                    <div class="dimension-item" id="wallHeightGroup">
                                        <label id="wallHeightLabel">Tinggi</label>
                                        <div class="input-with-unit">
                                            <input type="text" inputmode="text" name="wall_height" id="wallHeight" data-allow-expression="1" step="0.01" min="0.01"
                                                value="{{ old('wall_height', request('wall_height')) }}" required>
                                            <span class="unit">M</span>
                                            <span class="dimension-expression-hint" data-expression-hint hidden></span>
                                        </div>
                                    </div>
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

                        <div class="work-type-right-col">
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
                                            @if(in_array($materialKey, ['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'nat'], true))
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
                                    @elseif($materialKey === 'ceramic_type')
                                        <div class="customize-panel material-type-customize-panel" id="customizePanel-ceramic_type" data-customize-panel="ceramic_type" hidden>
                                            <div class="customize-grid">
                                                <div class="form-group">
                                                    <label>Dimensi :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypeDimension" data-customize-filter="ceramic_type" data-filter-key="dimension" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypeBrand" data-customize-filter="ceramic_type" data-filter-key="brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Sub Merek :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypeSubBrand" data-customize-filter="ceramic_type" data-filter-key="sub_brand" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Permukaan :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypeSurface" data-customize-filter="ceramic_type" data-filter-key="surface" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode Pembakaran :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypeCode" data-customize-filter="ceramic_type" data-filter-key="code" class="select-gray"></select></div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Corak :</label>
                                                    <div class="input-wrapper"><select id="customizeCeramicTypePattern" data-customize-filter="ceramic_type" data-filter-key="color" class="select-gray"></select></div>
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
                </div>

            </div>

        </div>
        <div id="additionalWorkItemsSection" class="additional-work-items-section" style="display: none;">
            <div id="additionalWorkItemsList" class="additional-work-items-list"></div>
        </div>

        <div class="form-group work-floor-group work-floor-extra-group" id="workFloorExtraSection" hidden>
            <label>Lantai</label>
            <div class="material-type-filter-body">
                <div class="material-type-extra-rows" id="workFloorExtraRows"></div>
            </div>
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
                <button type="button" id="addWorkItemBtn" class="work-item-stepper-btn work-item-stepper-card" title="Tambah Lantai" aria-label="Tambah Lantai">
                    <span class="work-item-stepper-icon" aria-hidden="true">
                        <i class="bi bi-plus-lg"></i>
                    </span>
                    <span class="work-item-stepper-label">Lantai</span>
                </button>
            </div>
            <div class="button-actions">
                <button type="button" id="btnResetForm" class="btn-cancel" style="padding: 5px 20px;">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                </button>
                <button type="submit" class="btn-save" id="btnCalculate" style="padding: 5px 20px;">
                    <i class="bi bi-search"></i> Hitung
                </button>
            </div>
        </div>
    </form>
</div>

    <div class="calc-scroll-fab" id="calcScrollFabWrap" aria-live="polite">
        <div class="calc-scroll-fab-dropdown" id="calcScrollFabDropdown" role="dialog" aria-label="Ringkasan Lantai Area Bidang">
            <div class="calc-scroll-fab-dropdown-title">Navigasi Input</div>
            <div class="calc-scroll-fab-menu-root" data-scroll-summary-tree></div>
        </div>
        <button type="button" class="calc-scroll-fab-btn" id="calcScrollFabBtn" aria-label="Scroll ke bawah" title="Scroll ke bawah">
            <i class="bi bi-arrow-down" id="calcScrollFabIcon" aria-hidden="true"></i>
        </button>
    </div>
@endsection

<style>
    @media (min-width: 769px) {
        #calculationForm .two-column-layout {
            grid-template-columns: minmax(0, 60fr) minmax(0, 40fr) !important;
        }

        #calculationForm .two-column-layout > .left-column {
            grid-column: 1;
            grid-row: 1;
            min-width: 0;
        }

        #calculationForm .two-column-layout > .filter-right-column {
            grid-column: 2;
            grid-row: 1;
            min-width: 0;
            align-self: stretch;
            display: flex;
            flex-direction: column;
        }

        #calculationForm .two-column-layout > .filter-right-column > .calc-header-row {
            margin-top: auto;
            margin-bottom: 0;
        }

        #calculationForm .two-column-layout > .right-column {
            grid-column: 1 / -1;
            grid-row: 2;
            min-width: 0;
        }
    }

    .calc-style {
        color: var(--text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        font-size: 32px;
    }

    .calc-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .calc-left-search-row {
        justify-content: flex-start;
    }

    .calc-left-search-row .calc-inline-search {
        justify-content: flex-start;
        margin-left: 0;
        margin-right: auto;
    }

    .calc-inline-search {
        display: inline-flex;
        align-items: center;
        min-width: 0;
        max-width: min(100%, 620px);
        flex: 1 1 auto;
        justify-content: flex-end;
        margin-left: auto;
        margin-bottom: 15px;
        align-self: flex-start;
        padding: 0;
        border-radius: 0;
        background: transparent;
        border: 0;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        box-shadow: none;
    }

    .calc-inline-search.is-sticky-fixed {
        position: fixed;
        top: var(--calc-search-sticky-top, 72px);
        left: var(--calc-inline-search-fixed-left, auto);
        width: var(--calc-inline-search-fixed-width, auto);
        max-width: min(100vw - 16px, 620px);
        margin-left: 0;
        z-index: 1045;
    }

    .calc-inline-search.is-sticky-fixed .calc-inline-search-inputwrap {
        gap: 4px;
        padding: 0 3px 0 6px;
    }

    .calc-inline-search.is-sticky-fixed .calc-inline-search-input {
        font-size: 10px;
        line-height: 1.05;
    }

    .calc-inline-search.is-sticky-fixed .calc-inline-search-icon {
        font-size: 10px;
    }

    .calc-inline-search.is-sticky-fixed .calc-inline-search-nav,
    .calc-inline-search.is-sticky-fixed .calc-inline-search-clear {
        width: 18px;
        height: 18px;
    }

    .calc-inline-search-inputwrap {
        display: flex;
        align-items: center;
        gap: 5px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        border-radius: 999px;
        padding: 1px 4px 1px 7px;
        min-width: 0;
        width: min(100%, 500px);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .calc-inline-search-inputwrap:focus-within {
        border-color: #93c5fd;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.12);
    }

    .calc-inline-search-icon {
        color: #64748b;
        font-size: 11px;
        flex: 0 0 auto;
    }

    .calc-inline-search-input {
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        min-width: 0;
        flex: 1 1 auto;
        font-size: 11px;
        color: #0f172a;
        background: transparent;
        line-height: 1.15;
    }

    .calc-inline-search-input:focus {
        border: 0 !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .calc-inline-search-input::placeholder {
        color: #94a3b8;
    }

    .calc-inline-search-suffix {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        flex: 0 0 auto;
        padding-left: 5px;
        border-left: 1px solid #e2e8f0;
        margin-left: 1px;
    }

    .calc-inline-search-count {
        min-width: 36px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        color: #334155;
        padding: 0 2px;
        line-height: 1;
    }

    .calc-inline-search-nav {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        border: 0;
        background: transparent;
        color: #1d4ed8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .calc-inline-search-nav:hover:not(:disabled) {
        background: #eff6ff;
    }

    .calc-inline-search-nav:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .calc-inline-search-clear {
        border: 0;
        background: transparent;
        color: #64748b;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .calc-inline-search-clear:hover {
        background: #f1f5f9;
        color: #334155;
    }

    .calc-search-mark {
        background: rgba(191, 219, 254, 0.55);
        color: inherit;
        border-radius: 4px;
        padding: 0 1px;
        box-decoration-break: clone;
        -webkit-box-decoration-break: clone;
    }

    .calc-search-mark.is-active {
        background: rgba(254, 240, 138, 0.9);
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.38);
    }

    .calc-search-hit-field.is-active {
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.38), 0 0 0 5px rgba(254, 240, 138, 0.24);
        background-color: rgba(254, 249, 195, 0.18);
        border-radius: 8px;
        transition: box-shadow 0.18s ease;
    }

    /* Safe space for fixed FAB so bottom action buttons are not covered */
    #calculationForm {
        padding-bottom: 96px;
    }

    .calc-taxonomy-scroll-tracker {
        position: sticky;
        top: calc(var(--calc-search-sticky-top, 72px) + var(--calc-search-sticky-height, 0px) + 4px);
        z-index: 1044;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px 10px;
        margin: 0 0 10px 0;
        padding: 8px;
        border: 1px solid #dbe3ee;
        border-top: 0;
        border-radius: 0 0 12px 12px;
        background: #ffffff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        isolation: isolate;
        overflow: visible;
    }

    .calc-taxonomy-scroll-tracker::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: calc(-1 * (var(--calc-search-sticky-top, 72px) + var(--calc-search-sticky-height, 0px) + 4px));
        height: calc(var(--calc-search-sticky-top, 72px) + var(--calc-search-sticky-height, 0px) + 4px);
        background: #ffffff;
        pointer-events: none;
        z-index: 0;
    }

    .calc-taxonomy-scroll-tracker > * {
        position: relative;
        z-index: 1;
    }

    .calc-taxonomy-scroll-card {
        min-width: 0;
        position: relative;
        border-radius: 12px;
        padding: 10px;
        border: 1px solid var(--taxonomy-accent-border);
        background:
            radial-gradient(120% 140% at 0% 0%, var(--taxonomy-accent-soft) 0%, rgba(255, 255, 255, 0) 62%),
            linear-gradient(180deg, var(--taxonomy-accent-soft) 0%, #ffffff 72%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: box-shadow 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
    }

    .calc-taxonomy-scroll-card.is-floor {
        --taxonomy-accent-soft: #fef2f2;
        --taxonomy-accent-border: #fecaca;
        --taxonomy-accent-label: #991b1b;
    }

    .calc-taxonomy-scroll-card.is-area {
        --taxonomy-accent-soft: #fefce8;
        --taxonomy-accent-border: #fde68a;
        --taxonomy-accent-label: #92400e;
    }

    .calc-taxonomy-scroll-card.is-field {
        --taxonomy-accent-soft: #f0fdf4;
        --taxonomy-accent-border: #bbf7d0;
        --taxonomy-accent-label: #166534;
    }

    .calc-taxonomy-scroll-label {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        flex: 0 0 72px;
        width: 72px;
        margin-bottom: 0;
        padding-top: 0;
        white-space: nowrap;
        font-size: inherit;
        font-weight: 700;
        color: var(--taxonomy-accent-label);
    }

    .calc-taxonomy-scroll-value {
        min-width: 0;
        flex: 1 1 auto;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #ffffff;
        min-height: 38px;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.2;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .calc-taxonomy-scroll-action {
        flex: 0 0 auto;
        min-height: 38px;
        padding: 0 12px;
        font-size: 12px;
        white-space: nowrap;
    }

    .calc-taxonomy-scroll-action.is-area-action {
        background: linear-gradient(180deg, #fefce8 0%, #fef3c7 100%);
        border-color: #fcd34d;
        color: #92400e;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(234, 179, 8, 0.08) inset;
    }

    .calc-taxonomy-scroll-action.is-area-action:hover:not(:disabled) {
        background: linear-gradient(180deg, #fef3c7 0%, #fde68a 100%);
        border-color: #eab308;
        color: #78350f;
        box-shadow: 0 3px 10px rgba(234, 179, 8, 0.14);
    }

    .calc-taxonomy-scroll-action.is-field-action {
        background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #86efac;
        color: #166534;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(22, 163, 74, 0.08) inset;
    }

    .calc-taxonomy-scroll-action.is-field-action:hover:not(:disabled) {
        background: linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%);
        border-color: #22c55e;
        color: #14532d;
        box-shadow: 0 3px 10px rgba(22, 163, 74, 0.14);
    }

    .calc-taxonomy-scroll-action:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }

    .calc-scroll-fab {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1050;
        width: 45px;
        height: 45px;
    }

    .calc-scroll-fab-btn {
        width: 45px;
        height: 45px;
        border-radius: 999px;
        border: 1px solid #bfdbfe;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        box-shadow: 0 10px 24px rgba(30, 64, 175, 0.18), 0 2px 6px rgba(15, 23, 42, 0.08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
    }

    .calc-scroll-fab-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(30, 64, 175, 0.22), 0 4px 10px rgba(15, 23, 42, 0.1);
    }

    .calc-scroll-fab-btn i {
        font-size: 1rem;
        font-weight: 700;
    }

    .calc-scroll-fab-dropdown {
        position: absolute;
        right: 0;
        bottom: calc(100% + 6px);
        width: max-content;
        min-width: 220px;
        max-width: min(360px, calc(100vw - 36px));
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid #dbeafe;
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        padding: 10px;
        backdrop-filter: blur(10px);
        opacity: 0;
        visibility: hidden;
        transform: translateY(6px);
        transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
        pointer-events: none;
    }

    /* Hover bridge between FAB and dropdown so hover doesn't break on the gap */
    .calc-scroll-fab-dropdown::after {
        content: '';
        position: absolute;
        right: 0;
        bottom: -10px;
        width: 120px;
        height: 12px;
        background: transparent;
    }

    .calc-scroll-fab:hover .calc-scroll-fab-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
        pointer-events: auto;
    }

    .calc-scroll-fab-dropdown-title {
        font-size: 12px;
        font-weight: 700;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 8px;
    }

    .calc-scroll-fab-menu-root {
        min-width: 0;
        width: max-content;
        max-width: 100%;
    }

    .calc-scroll-fab-menu,
    .calc-scroll-fab-submenu {
        margin: 0;
        padding: 4px;
        list-style: none;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        width: max-content;
        min-width: 180px;
        max-width: min(320px, calc(100vw - 36px));
    }

    .calc-scroll-fab-submenu {
        position: absolute;
        top: auto;
        bottom: -1px;
        left: auto;
        right: calc(100% + 8px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        opacity: 0;
        visibility: hidden;
        transform: translateX(-4px);
        transition: opacity 0.14s ease, transform 0.14s ease, visibility 0.14s ease;
        z-index: 2;
    }

    /* Hover bridge between parent item and left-opening submenu */
    .calc-scroll-fab-submenu::after {
        content: '';
        position: absolute;
        top: 0;
        right: -10px;
        width: 12px;
        height: 100%;
        background: transparent;
    }

    .calc-scroll-fab-menu-item {
        position: relative;
    }

    .calc-scroll-fab-menu-item + .calc-scroll-fab-menu-item {
        margin-top: 4px;
    }

    .calc-scroll-fab-menu-item:hover > .calc-scroll-fab-submenu {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    .calc-scroll-fab-menu-item-label {
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        border: 1px solid #dbeafe;
        background: #ffffff;
        color: #1e293b;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
    }

    .calc-scroll-fab-menu-item-label.is-clickable {
        cursor: pointer;
    }

    .calc-scroll-fab-menu-item-label.is-clickable:hover,
    .calc-scroll-fab-menu-item-label.is-clickable:focus {
        outline: none;
        background: #eff6ff;
        border-color: #93c5fd;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.14);
    }

    .calc-scroll-fab-menu-item.has-children > .calc-scroll-fab-menu-item-label::before {
        content: '<';
        color: #64748b;
        font-weight: 700;
        flex: 0 0 auto;
    }

    .calc-scroll-fab-submenu-title {
        display: block;
        padding: 4px 8px 6px 8px;
        margin: 0 0 4px 0;
        font-size: 10px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border-bottom: 1px dashed #cbd5e1;
    }

    .calc-scroll-fab-submenu-title.is-area {
        color: #a16207;
        background: linear-gradient(180deg, #fefce8 0%, #fef9c3 100%);
        border: 1px solid #fde68a;
        border-radius: 8px;
        margin-bottom: 6px;
        border-bottom-style: solid;
    }

    .calc-scroll-fab-submenu-title.is-field {
        color: #166534;
        background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        margin-bottom: 6px;
        border-bottom-style: solid;
    }

    .calc-scroll-fab-menu-item-label .calc-scroll-fab-menu-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .calc-scroll-fab-chip.is-empty,
    .calc-scroll-fab-menu-empty {
        display: block;
        width: 100%;
        border: 1px dashed #cbd5e1;
        background: #ffffff;
        color: #64748b;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        line-height: 1.25;
        font-style: italic;
    }

    @media (max-width: 768px) {
        .calc-header-row {
            align-items: stretch;
            flex-direction: column;
            gap: 8px;
        }

        .calc-inline-search {
            width: 100%;
            max-width: 100%;
            justify-content: stretch;
            padding: 0;
        }

        .calc-inline-search.is-sticky-fixed {
            top: var(--calc-search-sticky-top, 64px);
            max-width: calc(100vw - 12px);
        }

        .calc-inline-search-inputwrap {
            width: 100%;
        }

        .calc-inline-search-suffix {
            padding-left: 4px;
            gap: 2px;
        }

        .calc-inline-search-count {
            min-width: 34px;
            font-size: 9px;
        }

        #calculationForm {
            padding-bottom: 84px;
        }

        .calc-taxonomy-scroll-tracker {
            top: calc(var(--calc-search-sticky-top, 64px) + var(--calc-search-sticky-height, 0px) + 4px);
            grid-template-columns: 1fr;
            gap: 6px;
            padding: 7px;
            margin-bottom: 8px;
        }

        .calc-taxonomy-scroll-card {
            padding: 7px 8px;
        }

        .calc-taxonomy-scroll-action {
            min-height: 34px;
            padding: 0 10px;
            font-size: 11px;
        }

        .calc-scroll-fab {
            right: 12px;
            bottom: 12px;
            width: 45px;
            height: 45px;
        }

        .calc-scroll-fab-btn {
            width: 45px;
            height: 45px;
        }

        .calc-scroll-fab-dropdown {
            width: min(94vw, 320px);
            max-width: 94vw;
            min-width: 0;
            padding: 8px;
        }

        .calc-scroll-fab-menu-root {
            min-width: 0;
        }

        .calc-scroll-fab-submenu {
            position: static;
            width: 100%;
            opacity: 1;
            visibility: visible;
            transform: none;
            box-shadow: none;
            margin-top: 6px;
            margin-left: 10px;
            min-width: 0;
            max-width: 100%;
        }

        .calc-scroll-fab-menu-item + .calc-scroll-fab-menu-item {
            margin-top: 6px;
        }
    }

    #calculationForm .filter-right-column {
        min-width: 0;
    }

    #calculationForm .filter-right-column .filter-section {
        margin-bottom: 12px;
    }

    #calculationForm .right-column {
        --work-taxonomy-indent-step: 18px;
        /* Samakan start parameter dengan start input "Item Pekerjaan" setelah label bernomor dibuat lebih lebar */
        --work-item-parameter-indent: 149px;
        --work-item-parameter-indent-mobile: 10px;
        --work-item-inline-indent: 0px;
        --work-item-inline-indent-mobile: 8px;
        --work-parameter-gap: 8px;
        --work-parameter-split-column-gap: 125px;
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

    #calculationForm .taxonomy-node-floor > .taxonomy-node-children {
        margin-left: 15px;
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

    #calculationForm .taxonomy-card-floor {
        --taxonomy-accent: #dc2626;
        --taxonomy-accent-soft: #fef2f2;
        --taxonomy-accent-border: #fecaca;
        --taxonomy-accent-glow: rgba(220, 38, 38, 0.18);
        --taxonomy-accent-label: #991b1b;
        border-left: 4px solid var(--taxonomy-accent);
    }

    #calculationForm .taxonomy-card-area {
        --taxonomy-accent: #eab308;
        --taxonomy-accent-soft: #fefce8;
        --taxonomy-accent-border: #fde68a;
        --taxonomy-accent-glow: rgba(234, 179, 8, 0.2);
        --taxonomy-accent-label: #92400e;
        border-left: 4px solid var(--taxonomy-accent);
    }

    #calculationForm .taxonomy-card-field {
        --taxonomy-accent: #16a34a;
        --taxonomy-accent-soft: #f0fdf4;
        --taxonomy-accent-border: #bbf7d0;
        --taxonomy-accent-glow: rgba(22, 163, 74, 0.18);
        --taxonomy-accent-label: #166534;
        border-left: 4px solid var(--taxonomy-accent);
    }

    .additional-work-item .additional-work-floor-group {
        --taxonomy-accent: #dc2626;
        --taxonomy-accent-soft: #fef2f2;
        --taxonomy-accent-border: #fecaca;
        --taxonomy-accent-glow: rgba(220, 38, 38, 0.18);
        --taxonomy-accent-label: #991b1b;
    }

    .additional-work-item .additional-work-area-group {
        --taxonomy-accent: #eab308;
        --taxonomy-accent-soft: #fefce8;
        --taxonomy-accent-border: #fde68a;
        --taxonomy-accent-glow: rgba(234, 179, 8, 0.2);
        --taxonomy-accent-label: #92400e;
    }

    .additional-work-item .additional-work-field-group {
        --taxonomy-accent: #16a34a;
        --taxonomy-accent-soft: #f0fdf4;
        --taxonomy-accent-border: #bbf7d0;
        --taxonomy-accent-glow: rgba(22, 163, 74, 0.18);
        --taxonomy-accent-label: #166534;
    }

    .additional-work-item .additional-taxonomy-cell[data-taxonomy-cell="floor"] {
        --taxonomy-accent: #dc2626;
        --taxonomy-accent-soft: #fef2f2;
        --taxonomy-accent-border: #fecaca;
        --taxonomy-accent-glow: rgba(220, 38, 38, 0.18);
        --taxonomy-accent-label: #991b1b;
    }

    .additional-work-item .additional-taxonomy-cell[data-taxonomy-cell="area"] {
        --taxonomy-accent: #eab308;
        --taxonomy-accent-soft: #fefce8;
        --taxonomy-accent-border: #fde68a;
        --taxonomy-accent-glow: rgba(234, 179, 8, 0.2);
        --taxonomy-accent-label: #92400e;
    }

    .additional-work-item .additional-taxonomy-cell[data-taxonomy-cell="field"] {
        --taxonomy-accent: #16a34a;
        --taxonomy-accent-soft: #f0fdf4;
        --taxonomy-accent-border: #bbf7d0;
        --taxonomy-accent-glow: rgba(22, 163, 74, 0.18);
        --taxonomy-accent-label: #166534;
    }

    #calculationForm .taxonomy-card-item,
    .additional-work-item .additional-worktype-group {
        --taxonomy-accent: #2563eb;
        --taxonomy-accent-soft: #eff6ff;
        --taxonomy-accent-border: #bfdbfe;
        --taxonomy-accent-glow: rgba(37, 99, 235, 0.18);
        --taxonomy-accent-label: #1d4ed8;
        border-left: 4px solid var(--taxonomy-accent);
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-item {
        border-left: 0;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-item,
    .additional-work-item .additional-work-floor-group,
    .additional-work-item .additional-work-area-group,
    .additional-work-item .additional-work-field-group,
    .additional-work-item .additional-worktype-group {
        position: relative;
        background:
            radial-gradient(120% 140% at 0% 0%, var(--taxonomy-accent-soft) 0%, rgba(255, 255, 255, 0) 62%),
            linear-gradient(180deg, var(--taxonomy-accent-soft) 0%, #ffffff 72%);
        border: 1px solid var(--taxonomy-accent-border);
        border-radius: 12px;
        padding: 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: box-shadow 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor:focus-within,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area:focus-within,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field:focus-within,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-item:focus-within,
    .additional-work-item .additional-work-floor-group:focus-within,
    .additional-work-item .additional-work-area-group:focus-within,
    .additional-work-item .additional-work-field-group:focus-within,
    .additional-work-item .additional-worktype-group:focus-within,
    .additional-work-item .additional-taxonomy-cell:focus-within {
        border-color: var(--taxonomy-accent);
        box-shadow: 0 0 0 3px var(--taxonomy-accent-glow), 0 6px 18px rgba(15, 23, 42, 0.06);
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor > label,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area > label,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field > label,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-item > label,
    .additional-work-item .additional-work-floor-group > label,
    .additional-work-item .additional-work-area-group > label,
    .additional-work-item .additional-work-field-group > label,
    .additional-work-item .additional-worktype-group > label {
        color: var(--taxonomy-accent-label);
        margin-bottom: 8px;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor .material-type-filter-body,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area .material-type-filter-body,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field .material-type-filter-body,
    .additional-work-item .additional-work-floor-group .material-type-filter-body,
    .additional-work-item .additional-work-area-group .material-type-filter-body,
    .additional-work-item .additional-work-field-group .material-type-filter-body {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-floor .taxonomy-level-actions,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-area .taxonomy-level-actions,
    #calculationForm .taxonomy-tree-main.taxonomy-group-card .taxonomy-card-field .taxonomy-level-actions,
    .additional-work-item .additional-work-floor-group .taxonomy-level-actions,
    .additional-work-item .additional-work-area-group .taxonomy-level-actions,
    .additional-work-item .additional-work-field-group .taxonomy-level-actions {
        margin-top: 8px;
    }

    .additional-work-item .additional-taxonomy-cell:not(.is-inherited) {
        background:
            radial-gradient(120% 140% at 0% 0%, var(--taxonomy-accent-soft) 0%, rgba(255, 255, 255, 0) 62%),
            linear-gradient(180deg, var(--taxonomy-accent-soft) 0%, #ffffff 72%);
        border: 1px solid var(--taxonomy-accent-border);
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .additional-work-item .additional-taxonomy-cell:not(.is-inherited) .additional-taxonomy-cell-label {
        color: var(--taxonomy-accent-label);
    }

    .additional-work-item .additional-taxonomy-cell:not(.is-inherited) .additional-taxonomy-cell-body {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
    }

    #calculationForm .taxonomy-node-field.is-item-content-collapsed > .taxonomy-node-children {
        display: none;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card.is-main-item-content-collapsed .main-taxonomy-actions-row {
        display: none !important;
    }

    .additional-work-item.is-item-content-collapsed[data-row-kind="item"] {
        display: none !important;
    }

    .additional-work-item.is-item-content-collapsed > .additional-work-item-grid > .additional-worktype-group,
    .additional-work-item.is-item-content-collapsed > .additional-work-item-grid > .additional-dimensions-container,
    .additional-work-item.is-item-content-collapsed .additional-row-item-content .additional-worktype-group,
    .additional-work-item.is-item-content-collapsed .additional-row-item-content .additional-dimensions-container,
    .additional-work-item.is-item-content-collapsed .additional-row-item-content .material-type-filter-group {
        display: none !important;
    }

    .additional-work-item.is-hidden-by-parent-toggle {
        display: none !important;
    }

    .additional-work-item.is-item-content-collapsed > .additional-work-item-grid > .additional-taxonomy-actions-row,
    .additional-work-item.is-item-content-collapsed > .additional-work-item-grid > [data-area-children] > .additional-taxonomy-actions-row,
    .additional-work-item.is-item-content-collapsed > .additional-work-item-grid > [data-floor-children] > .additional-taxonomy-actions-row {
        display: none !important;
    }

    #calculationForm .taxonomy-main-horizontal {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: start;
        gap: 8px 10px;
    }

    #calculationForm .taxonomy-main-horizontal > .taxonomy-node-floor,
    #calculationForm .taxonomy-main-horizontal > .taxonomy-node-floor > .taxonomy-node-children,
    #calculationForm .taxonomy-main-horizontal > .taxonomy-node-floor > .taxonomy-node-children > .taxonomy-node-area,
    #calculationForm .taxonomy-main-horizontal > .taxonomy-node-floor > .taxonomy-node-children > .taxonomy-node-area > .taxonomy-node-children,
    #calculationForm .taxonomy-main-horizontal > .taxonomy-node-floor > .taxonomy-node-children > .taxonomy-node-area > .taxonomy-node-children > .taxonomy-node-field {
        display: contents;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-node-floor > .taxonomy-node-children,
    #calculationForm .taxonomy-main-horizontal .taxonomy-node-area > .taxonomy-node-children,
    #calculationForm .taxonomy-main-horizontal .taxonomy-node-field > .taxonomy-node-children {
        margin-left: 0;
        padding-left: 0;
        border-left: 0;
    }

    #calculationForm .taxonomy-main-horizontal .work-floor-group,
    #calculationForm .taxonomy-main-horizontal .work-area-group,
    #calculationForm .taxonomy-main-horizontal .work-field-group {
        width: 100%;
        min-width: 0;
        margin-bottom: 0;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 6px 8px;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group > label {
        grid-column: 1;
        width: auto !important;
        flex: initial;
        margin-bottom: 0;
        white-space: nowrap;
        align-self: center;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group .material-type-filter-body {
        grid-column: 2;
        width: 100%;
        min-width: 0;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group .taxonomy-level-actions {
        grid-column: 3;
        margin: 0 !important;
        align-self: center;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group .taxonomy-level-btn {
        padding-left: 8px;
        padding-right: 8px;
        font-size: 12px;
    }

    #calculationForm .taxonomy-main-horizontal .work-field-group .taxonomy-level-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    #calculationForm .taxonomy-main-horizontal .taxonomy-node-field > .taxonomy-node-children,
    #calculationForm .taxonomy-main-horizontal .taxonomy-node-item,
    #calculationForm .taxonomy-main-horizontal #inputFormContainer,
    #calculationForm .taxonomy-main-horizontal .main-area-children {
        grid-column: 1 / -1;
        width: 100%;
        min-width: 0;
    }

    #calculationForm .taxonomy-tree-main.taxonomy-group-card.taxonomy-main-horizontal .work-type-group.taxonomy-card-item {
        margin-top: 2px;
        margin-bottom: var(--work-parameter-gap);
        padding-left: 10px;
        box-sizing: border-box;
    }

    @media (max-width: 768px) {
        .additional-taxonomy-header {
            grid-template-columns: 1fr;
        }

        .additional-taxonomy-cell.is-inherited {
            display: none;
            visibility: visible;
            pointer-events: auto;
        }

        .main-taxonomy-actions-row,
        .additional-taxonomy-actions-row {
            display: grid;
            grid-template-columns: 1fr;
        }

        .main-taxonomy-actions-row > #addAreaFromMainBtn,
        .main-taxonomy-actions-row > #addFieldFromMainBtn,
        .main-taxonomy-actions-row > #addItemFromMainBtn,
        .additional-taxonomy-actions-row > [data-action="add-area"],
        .additional-taxonomy-actions-row > [data-action="add-field"],
        .additional-taxonomy-actions-row > [data-action="add-item"] {
            grid-column: auto;
            margin-left: 0;
            transform: none;
        }

        .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body {
            grid-template-columns: 1fr;
        }

        .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body .taxonomy-toggle-item-btn {
            grid-column: auto;
            width: 100%;
            justify-content: center;
        }

        #calculationForm .taxonomy-main-horizontal {
            grid-template-columns: 1fr;
        }

        #calculationForm .taxonomy-tree-main.taxonomy-group-card.taxonomy-main-horizontal .work-type-group.taxonomy-card-item {
            padding-left: 10px;
        }

        #calculationForm .taxonomy-main-horizontal .work-floor-group,
        #calculationForm .taxonomy-main-horizontal .work-area-group,
        #calculationForm .taxonomy-main-horizontal .work-field-group {
            width: 100%;
            min-width: 0;
        }

        #calculationForm .taxonomy-main-horizontal .taxonomy-inline-group .taxonomy-level-btn {
            padding-left: 10px;
            padding-right: 10px;
            font-size: inherit;
        }
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

    /* Beri ruang ekstra saat label berubah menjadi "Item Pekerjaan X" */
    #calculationForm .work-type-group.taxonomy-inline-item {
        gap: 12px;
    }

    #calculationForm .work-type-group.taxonomy-inline-item > label {
        flex-basis: 136px;
        width: 136px !important;
    }

    #calculationForm .work-type-group.taxonomy-inline-item > .input-wrapper,
    .additional-worktype-group.taxonomy-inline-item .input-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    #calculationForm .work-type-group.taxonomy-inline-item > .input-wrapper > .work-type-autocomplete,
    .additional-worktype-group.taxonomy-inline-item .input-wrapper > .work-type-autocomplete {
        flex: 0 1 50%;
        max-width: 45%;
        min-width: 0;
    }

    .worktype-inline-summary {
        display: inline-flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: fit-content;
        min-width: 0;
        max-width: 100%;
        padding: 4px 10px;
        gap: 0;
        align-self: center;
    }

    .worktype-inline-summary .dimension-area-summary-title {
        font-size: 11px;
    }

    .worktype-inline-summary .dimension-area-summary-formula {
        font-size: 11px;
    }

    .worktype-inline-summary .dimension-area-summary-value {
        font-size: 14px;
        line-height: 1;
        white-space: nowrap;
    }

    #calculationForm .work-floor-group,
    #calculationForm .work-area-group,
    #calculationForm .work-field-group {
        align-items: flex-start;
    }

    #calculationForm .work-floor-group .material-type-filter-body,
    #calculationForm .work-area-group .material-type-filter-body,
    #calculationForm .work-field-group .material-type-filter-body {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
    }

    #calculationForm .work-floor-group .material-type-rows,
    #calculationForm .work-floor-group .material-type-extra-rows,
    #calculationForm .work-area-group .material-type-rows,
    #calculationForm .work-field-group .material-type-rows,
    #calculationForm .work-area-group .material-type-extra-rows,
    #calculationForm .work-field-group .material-type-extra-rows {
        width: 100%;
    }

    #workFloorRows .material-type-row-actions,
    #workAreaRows .material-type-row-actions,
    #workFieldRows .material-type-row-actions {
        display: none;
    }

    #workFloorRows .material-type-row .work-type-input,
    #workAreaRows .material-type-row .work-type-input,
    #workFieldRows .material-type-row .work-type-input {
        border-right: 1px solid #cbd5e1 !important;
        border-radius: 4px;
    }

    .work-floor-extra-group,
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
        align-items: center;
    }

    #calculationForm .work-type-group > label,
    #calculationForm .dimension-item > label {
        padding-top: 0 !important;
    }

    .dimension-expression-hint {
        margin-left: 8px;
        margin-right: 8px;
        font-size: 11px;
        line-height: 1.2;
        color: #64748b;
        white-space: nowrap;
        font-style: italic;
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
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 0;
    }

    .work-item-stepper {
        display: inline-flex;
        align-items: center;
        gap: 0;
    }

    .work-item-stepper-label {
        height: 100%;
        display: inline-flex;
        align-items: center;
        padding: 0 12px 0 6px;
        border: 0;
        border-radius: 0;
        background: transparent;
        font-size: 13px;
        font-weight: 700;
        color: #991b1b;
        white-space: nowrap;
        user-select: none;
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

    .work-item-stepper-card {
        width: auto;
        min-height: 36px;
        height: auto;
        gap: 6px;
        padding: 2px;
        border: 1px solid #fca5a5;
        border-radius: 10px;
        background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 0 0 2px rgba(220, 38, 38, 0.06) inset;
    }

    .work-item-stepper-icon {
        width: auto;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 0 0 10px;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #991b1b;
        flex: 0 0 auto;
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
        background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
        border-color: #fca5a5;
        color: #991b1b;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 0 0 2px rgba(220, 38, 38, 0.06) inset;
    }

    #addWorkItemBtn:hover:not(:disabled) {
        background: linear-gradient(180deg, #fee2e2 0%, #fecaca 100%);
        border-color: #ef4444;
        color: #7f1d1d;
        box-shadow: 0 3px 10px rgba(220, 38, 38, 0.12), 0 0 0 2px rgba(220, 38, 38, 0.08) inset;
    }

    #addWorkItemBtn:disabled .work-item-stepper-icon,
    #addWorkItemBtn:disabled .work-item-stepper-label {
        opacity: 0.65;
    }

    .work-item-stepper-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .taxonomy-level-actions {
        margin-top: 6px;
    }

    .main-taxonomy-actions-row {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px 10px;
        margin-top: 10px;
        margin-bottom: 2px;
        width: 100%;
    }

    #calculationForm .taxonomy-main-horizontal > .main-taxonomy-actions-row {
        grid-column: 1 / -1;
        width: 100%;
        min-width: 0;
    }

    .main-area-children > .main-taxonomy-actions-row {
        width: 100%;
        min-width: 0;
        margin-top: 8px;
        margin-bottom: 8px;
    }

    .main-taxonomy-actions-row > #addItemFromMainBtn {
        order: 1;
        justify-self: auto;
        margin-right: auto;
        transform: translateX(var(--work-item-inline-indent));
    }

    .main-taxonomy-actions-row > #addAreaFromMainBtn {
        order: 2;
        justify-self: auto;
        margin-left: 0;
    }

    .main-taxonomy-actions-row > #addFieldFromMainBtn {
        order: 3;
        justify-self: auto;
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
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .taxonomy-level-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.08);
    }

    .taxonomy-toggle-item-btn {
        gap: 0;
        justify-content: center;
        min-width: 38px;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        border-color: #cbd5e1;
        color: #334155;
    }

    .taxonomy-toggle-item-btn i {
        font-size: 14px;
        line-height: 1;
    }

    .taxonomy-toggle-item-btn:hover {
        background: linear-gradient(180deg, #eef2f7 0%, #e2e8f0 100%);
        border-color: #94a3b8;
        color: #0f172a;
    }

    .taxonomy-toggle-item-btn.is-collapsed {
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        border-color: #93c5fd;
        color: #1d4ed8;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(59, 130, 246, 0.07) inset;
    }

    .taxonomy-toggle-item-btn.is-collapsed:hover {
        background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: #60a5fa;
        color: #1e40af;
    }

    .taxonomy-main-item-remove-btn {
        flex: 0 0 auto;
        width: 36px;
        min-width: 36px;
        min-height: 36px;
        padding: 0;
        margin-left: 4px;
        background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
        border-color: #fecaca;
        color: #b91c1c;
        justify-content: center;
    }

    .taxonomy-main-item-remove-btn:hover:not(:disabled) {
        background: linear-gradient(180deg, #fee2e2 0%, #fecaca 100%);
        border-color: #fca5a5;
        color: #991b1b;
        box-shadow: 0 3px 10px rgba(239, 68, 68, 0.12);
    }

    .taxonomy-main-item-remove-btn i {
        font-size: 13px;
        line-height: 1;
    }

    #addAreaFromMainBtn,
    .additional-taxonomy-actions-row > [data-action="add-area"],
    .additional-taxonomy-inline-actions > [data-action="add-area"] {
        background: linear-gradient(180deg, #fefce8 0%, #fef3c7 100%);
        border-color: #fcd34d;
        color: #92400e;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(234, 179, 8, 0.08) inset;
    }

    #addAreaFromMainBtn:hover,
    .additional-taxonomy-actions-row > [data-action="add-area"]:hover,
    .additional-taxonomy-inline-actions > [data-action="add-area"]:hover {
        background: linear-gradient(180deg, #fef3c7 0%, #fde68a 100%);
        border-color: #eab308;
        color: #78350f;
        box-shadow: 0 3px 10px rgba(234, 179, 8, 0.14);
    }

    #addFieldFromMainBtn,
    .additional-taxonomy-actions-row > [data-action="add-field"],
    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body > [data-action="add-field"] {
        background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);
        border-color: #86efac;
        color: #166534;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(22, 163, 74, 0.08) inset;
    }

    #addFieldFromMainBtn:hover,
    .additional-taxonomy-actions-row > [data-action="add-field"]:hover,
    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body > [data-action="add-field"]:hover {
        background: linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%);
        border-color: #22c55e;
        color: #14532d;
        box-shadow: 0 3px 10px rgba(22, 163, 74, 0.14);
    }

    #addItemFromMainBtn,
    .additional-taxonomy-actions-row > [data-action="add-item"] {
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        border-color: #93c5fd;
        color: #1d4ed8;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 0 0 2px rgba(59, 130, 246, 0.08) inset;
    }

    #addItemFromMainBtn:hover,
    .additional-taxonomy-actions-row > [data-action="add-item"]:hover {
        background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: #60a5fa;
        color: #1e40af;
        box-shadow: 0 3px 10px rgba(59, 130, 246, 0.14);
    }

    #calculationForm #inputFormContainer,
    #calculationForm #additionalWorkItemsSection {
        margin-left: 0;
        padding-left: 0;
    }

    #calculationForm #inputFormContainer {
        margin-top: 0;
        padding-top: 0;
        padding-left: calc(var(--work-item-parameter-indent) + var(--work-item-inline-indent));
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    #calculationForm #inputFormContainer .dimensions-container-vertical,
    #calculationForm #inputFormContainer .material-type-filter-group,
    #calculationForm #inputFormContainer .work-type-right-col {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    /* Split parameter area: left = ukuran, right = jenis material (main item) */
    #calculationForm #inputFormContainer .work-type-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        gap: var(--work-parameter-gap) var(--work-parameter-split-column-gap);
    }

    #calculationForm #inputFormContainer .work-type-form > .dimensions-container-vertical {
        grid-column: 1;
        margin-bottom: 0;
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
    }

    #calculationForm #inputFormContainer .work-type-form > .work-type-right-col {
        grid-column: 2;
        margin-top: 0;
        align-self: start;
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
    }

    #calculationForm #inputFormContainer .work-type-form > .work-type-right-col > .material-type-filter-group {
        margin-top: 0;
        align-self: start;
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
    }

    #calculationForm #inputFormContainer .work-type-form > .dimensions-container-vertical > .dimension-item,
    #calculationForm #inputFormContainer .work-type-form > .work-type-right-col > .material-type-filter-group > .material-type-filter-item {
        margin: 0 !important;
    }

    .dimension-area-layout {
        display: block;
    }

    .dimension-area-inputs {
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
        min-width: 0;
    }

    .dimension-area-inputs > .dimension-item {
        margin: 0 !important;
    }

    .dimension-area-summary {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        padding: 10px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        min-width: 0;
    }

    .dimension-area-summary-title {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        line-height: 1.2;
    }

    .dimension-area-summary-formula {
        font-size: 12px;
        color: #475569;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dimension-area-summary-value {
        font-size: 18px;
        font-weight: 800;
        color: #1e3a8a;
        line-height: 1.2;
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

    /* Base: flat ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â no individual card per item */
    .additional-work-item {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
        margin-left: 0 !important;
    }

    /* Top-level floor group card ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â same visual language as the main taxonomy card */
    .additional-work-item.is-floor-group {
        background: #ffffff;
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        padding: 10px;
    }

    /* Separator between nested items inside a floor card */
    .additional-floor-children > .additional-work-item,
    .additional-area-children > .additional-work-item,
    .main-area-children > .additional-work-item {
        padding-top: 8px;
        border-top: 1px solid #e8eef5;
    }

    /* Hilangkan garis pembatas antar item pekerjaan, tetap pertahankan spacing */
    .additional-floor-children > .additional-work-item[data-row-kind="item"],
    .additional-area-children > .additional-work-item[data-row-kind="item"],
    .main-area-children > .additional-work-item[data-row-kind="item"] {
        border-top: 0;
    }

    .additional-work-item.field-break {
        padding-top: 8px;
        border-top: 1px dashed #cbd5e1;
    }

    /* Pembatas antar section Area/Bidang tambahan (bukan antar item pekerjaan) */
    .main-area-children > .additional-work-item + .additional-work-item[data-row-kind="area"],
    .main-area-children > .additional-work-item + .additional-work-item[data-row-kind="field"],
    .additional-floor-children > .additional-work-item + .additional-work-item[data-row-kind="area"],
    .additional-floor-children > .additional-work-item + .additional-work-item[data-row-kind="field"],
    .additional-area-children > .additional-work-item + .additional-work-item[data-row-kind="area"],
    .additional-area-children > .additional-work-item + .additional-work-item[data-row-kind="field"] {
        padding-top: 10px;
        border-top: 2px dashed #94a3b8;
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
        width: 100%;
    }

    .additional-work-item-grid > input[type="hidden"] {
        display: none !important;
    }

    /* Taxonomy header - flex layout so hidden cells don't leave gaps */
    .additional-taxonomy-header {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: start;
        gap: 8px 10px;
        margin-bottom: 8px;
    }

    .additional-taxonomy-cell {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 6px 8px;
        width: 100%;
        min-width: 0;
    }

    .additional-taxonomy-cell-body {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: center;
        gap: 6px 8px;
        width: 100%;
        min-width: 0;
        grid-column: 2;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body {
        grid-template-columns: minmax(0, 1fr) auto;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body.has-inline-add-field {
        grid-template-columns: minmax(0, 1fr) auto auto;
    }

    .additional-taxonomy-cell.has-inline-action {
        grid-template-columns: auto minmax(0, 1fr) auto;
    }

    .additional-taxonomy-cell-label {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.03em;
        margin-bottom: 0;
        white-space: nowrap;
        align-self: center;
    }

    .additional-taxonomy-cell .work-type-autocomplete,
    .additional-taxonomy-cell .work-type-input {
        width: 100%;
    }

    .additional-taxonomy-cell-body .work-type-autocomplete {
        min-width: 0;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body .work-type-autocomplete {
        grid-column: 1;
    }

    .additional-taxonomy-cell.has-inline-action .additional-taxonomy-cell-body {
        grid-column: 2;
    }

    .additional-taxonomy-cell-body .taxonomy-level-btn {
        margin: 0;
        align-self: end;
        white-space: nowrap;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body > [data-action="add-field"] {
        min-height: 36px;
        margin: 0;
        align-self: center;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body.has-inline-add-field > [data-action="add-field"] {
        grid-column: 2;
    }

    .additional-taxonomy-inline-actions {
        grid-column: 3;
        margin: 0;
        align-self: center;
        display: inline-flex;
        align-items: center;
    }

    .additional-taxonomy-inline-actions .taxonomy-level-btn {
        margin: 0;
        align-self: center;
        white-space: nowrap;
    }

    .additional-taxonomy-cell.has-inline-action .additional-taxonomy-inline-actions .taxonomy-level-btn {
        align-self: center;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body .taxonomy-toggle-item-btn {
        grid-column: 2;
        min-height: 36px;
        padding-left: 9px;
        padding-right: 9px;
    }

    .additional-taxonomy-cell[data-taxonomy-cell="field"] .additional-taxonomy-cell-body.has-inline-add-field .taxonomy-toggle-item-btn {
        grid-column: 3;
    }

    /* Inherited cells are hidden entirely ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â value kept in hidden input */
    .additional-taxonomy-cell.is-inherited {
        visibility: hidden;
        pointer-events: none;
    }

    .additional-taxonomy-actions-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: center;
        gap: 6px 10px;
        margin-top: 8px;
        margin-bottom: 8px;
    }

    .main-taxonomy-actions-row > .taxonomy-level-btn,
    .additional-taxonomy-actions-row > .taxonomy-level-btn {
        white-space: nowrap;
        align-self: center;
        margin-top: 0;
    }

    .additional-taxonomy-actions-row > [data-action="add-item"] {
        grid-column: 1;
        grid-row: 1;
        justify-self: start;
        transform: translateX(var(--work-item-inline-indent));
    }

    .additional-taxonomy-actions-row > [data-action="add-area"] {
        grid-column: 2;
        grid-row: 1;
        justify-self: start;
    }

    .additional-area-children > .additional-taxonomy-actions-row,
    .additional-floor-children > .additional-taxonomy-actions-row {
        margin-top: 0;
        margin-bottom: 4px;
    }

    .additional-taxonomy-actions-row > [data-action="add-field"] {
        grid-column: 3;
        grid-row: 1;
        justify-self: start;
    }

    .additional-worktype-group.taxonomy-inline-item {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .additional-worktype-group.taxonomy-inline-item .input-wrapper {
        flex: 1 1 auto;
        min-width: 0;
    }

    .additional-worktype-group.taxonomy-inline-item .autocomplete-input {
        width: 100%;
    }

    .additional-area-children {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 6px;
    }

    .additional-floor-children {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    /* Main section additional host uses row separators (padding+border), not flex gap,
       so spacing between item 1-2 and 2-3 stays identical. */
    .main-area-children {
        gap: 0 !important;
        margin-top: 0 !important;
    }

    .additional-floor-children:empty,
    .additional-area-children:empty {
        display: none;
        margin-top: 0;
    }

    .additional-area-children > .additional-work-item {
        margin-top: 0 !important;
    }

    .additional-area-children > .additional-taxonomy-actions-row + .additional-work-item,
    .additional-floor-children > .additional-taxonomy-actions-row + .additional-work-item {
        padding-top: 0;
        border-top: 0;
    }

    /* Samakan jarak item tambahan pertama di area utama dengan row lainnya */
    .main-area-children > .additional-work-item:first-child {
        margin-top: 0 !important;
    }

    /* Transisi dari item utama -> item tambahan pertama tidak perlu separator row ekstra.
       Kalau tidak, jarak 1-2 terlihat lebih besar daripada 2-3. */
    .main-area-children > .additional-work-item:first-child,
    .main-area-children > .main-taxonomy-actions-row + .additional-work-item {
        padding-top: 0;
        border-top: 0;
    }

    /* Restore divider for Area/Bidang sections even when preceded by action footers */
    .main-area-children > .main-taxonomy-actions-row + .additional-work-item[data-row-kind="area"],
    .main-area-children > .main-taxonomy-actions-row + .additional-work-item[data-row-kind="field"],
    .main-area-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="area"],
    .main-area-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="field"],
    .additional-floor-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="area"],
    .additional-floor-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="field"],
    .additional-area-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="area"],
    .additional-area-children > .additional-taxonomy-actions-row + .additional-work-item[data-row-kind="field"] {
        padding-top: 10px;
        border-top: 2px dashed #94a3b8;
    }

    .additional-material-inline {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 0;
    }

    .additional-material-inline .material-type-filter-item {
        margin-bottom: 0 !important;
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
        margin-bottom: var(--work-parameter-gap);
        margin-left: 0;
        padding-left: var(--work-item-inline-indent);
        width: 100%;
        box-sizing: border-box;
    }

    .additional-dimensions-container {
        margin-top: 0;
        padding-top: 0;
        margin-left: 0;
        padding-left: calc(var(--work-item-parameter-indent) + var(--work-item-inline-indent)) !important;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .additional-parameter-split {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        align-items: start;
        gap: var(--work-parameter-gap) var(--work-parameter-split-column-gap);
        width: 100%;
        min-width: 0;
    }

    .additional-parameter-size-col,
    .additional-parameter-material-col {
        min-width: 0;
    }

    .additional-parameter-size-col {
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
    }

    .additional-parameter-size-col > .dimension-item {
        margin: 0 !important;
    }

    .additional-dimensions-container .dimension-area-layout {
        margin-bottom: 0;
    }

    .additional-parameter-material-col {
        display: flex;
        flex-direction: column;
        gap: var(--work-parameter-gap);
    }

    .additional-parameter-material-col .additional-material-inline {
        margin-top: 0;
        gap: 0;
    }

    .additional-parameter-material-col .additional-material-inline > .material-type-filter-item {
        margin: 0 !important;
    }

    .additional-parameter-material-col .additional-material-inline > .material-type-filter-item + .material-type-filter-item {
    }

    .additional-dimensions-container .additional-material-filter-item {
        margin: 0 !important;
    }

    .additional-dimensions-container .additional-material-filter-item > label {
        padding-top: 0 !important;
        align-items: center;
    }

    .additional-dimensions-container .additional-material-filter-item > .material-type-rows {
        margin: 0;
    }

    .additional-work-item .dimensions-container-vertical {
        margin-bottom: 0;
    }

    .additional-work-item .material-type-filter-group {
        margin-top: 0;
    }

    /* Normalisasi gap parameter item utama + tambahan agar konsisten */
    #calculationForm #inputFormContainer .work-type-form > .dimensions-container-vertical > .dimension-item,
    #calculationForm #inputFormContainer .work-type-form > .work-type-right-col > .material-type-filter-group > .material-type-filter-item,
    .additional-dimensions-container .additional-parameter-size-col > .dimension-item,
    .additional-dimensions-container .additional-parameter-material-col .material-type-filter-item {
        margin: 0 !important;
    }

    #calculationForm #inputFormContainer .work-type-form > .dimensions-container-vertical,
    #calculationForm #inputFormContainer .work-type-form > .work-type-right-col,
    .additional-dimensions-container .additional-parameter-size-col,
    .additional-dimensions-container .additional-parameter-material-col .additional-material-inline {
        gap: var(--work-parameter-gap) !important;
    }

    /* Samakan ritme vertikal form parameter Ukuran vs Jenis Material (hindari gap dobel). */
    #calculationForm #inputFormContainer .material-type-filter-item > .material-type-filter-body,
    .additional-dimensions-container .material-type-filter-item > .material-type-filter-body {
        gap: 0;
    }

    #calculationForm #inputFormContainer .material-type-filter-item .customize-panel,
    .additional-dimensions-container .material-type-filter-item .customize-panel {
        margin-bottom: 0;
    }

    #calculationForm #inputFormContainer .material-type-extra-rows[data-material-type],
    .additional-dimensions-container .material-type-extra-rows[data-material-type] {
        gap: var(--work-parameter-gap);
    }

    #calculationForm #inputFormContainer .material-type-extra-rows[data-material-type]:not(:empty),
    .additional-dimensions-container .material-type-extra-rows[data-material-type]:not(:empty) {
        margin-top: var(--work-parameter-gap);
    }

    /* Additional item: samakan ritme vertikal kolom material dengan kolom ukuran secara ketat. */
    .additional-dimensions-container .additional-parameter-material-col .additional-material-inline {
        gap: 0 !important;
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
        margin-top: 0;
        display: block;
    }

    .material-type-filter-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: start;
        gap: 0 8px;
        margin-bottom: 4px !important;
    }

    .material-type-filter-item > label {
        grid-column: 1;
        grid-row: 1;
        display: flex;
        align-items: flex-start;
        align-self: start;
        padding-top: 10px !important;
        margin-bottom: 0;
    }

    .material-type-filter-item > .material-type-filter-body {
        grid-column: 2;
        grid-row: 1;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .material-type-filter-item > .material-type-rows {
        grid-column: 2;
        grid-row: 1;
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

    .material-type-customize-panel .form-group {
        align-items: flex-start;
    }

    .customize-panel .form-group > label {
        font-size: 12px;
        margin-bottom: 0;
    }

    .material-type-customize-panel .form-group > label {
        margin-top: 5px;
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
    }

    .material-type-customize-panel .customize-grid {
        grid-template-columns: 1fr;
        margin-top: 5px;
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

    .project-location-group.location-required-error .project-location-input-wrapper .autocomplete-input {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12) !important;
        background-color: #fff7f7 !important;
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

    .ssm-radius-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 10px;
        margin-bottom: 4px;
    }

    .ssm-radius-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .ssm-radius-label {
        margin: 0;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }

    .ssm-radius-input {
        width: 100%;
        min-width: 0;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 13px;
        line-height: 1.2;
        background: #fff;
        color: #0f172a;
    }

    .ssm-radius-input:focus {
        outline: none;
        border-color: #891313;
        box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1);
    }

    .ssm-radius-help {
        font-size: 11px;
        line-height: 1.25;
        color: #64748b;
        margin: 0;
    }

    .ssm-row {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 6px;
    }

    .ssm-complete-layout {
        display: grid;
        grid-template-columns: minmax(100px, max-content) minmax(0, 1fr);
        gap: 4px 14px;
        align-items: start;
    }

    .ssm-complete-children {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .ssm-radius-inline-note {
        font-size: 11px;
        line-height: 1.35;
        color: #64748b;
        padding-left: 2px;
    }

    .ssm-group-title {
        margin-top: 2px;
        padding-left: 2px;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        letter-spacing: 0.01em;
    }

    .ssm-row-sub {
        padding-left: 18px;
    }

    .ssm-complete-children .ssm-row-sub {
        padding-left: 0;
    }

    .ssm-row input[type="checkbox"] {
        flex: 0 0 auto;
        margin: 0;
        cursor: pointer;
    }

    .ssm-label {
        flex: 0 0 auto;
        min-width: 80px;
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

    .ssm-row-sub .ssm-label {
        min-width: 120px;
    }

    .ssm-row-sub input[type="checkbox"]:disabled + .ssm-label,
    .ssm-row-sub input[type="checkbox"]:disabled + .ssm-label + .ssm-desc {
        opacity: 0.55;
    }

    @media (max-width: 768px) {
        .ssm-radius-grid {
            grid-template-columns: 1fr;
        }

        .ssm-complete-layout {
            grid-template-columns: 1fr;
            gap: 6px;
        }

        .ssm-complete-children .ssm-row-sub {
            padding-left: 18px;
        }
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

    /* Samakan warna teks form: label abu-abu, nilai input hitam */
    #calculationForm label {
        color: #6b7280 !important;
    }

    #calculationForm input,
    #calculationForm select,
    #calculationForm textarea {
        color: #111827 !important;
        -webkit-text-fill-color: #111827 !important;
    }

    #calculationForm select option {
        color: #111827;
    }

    @media (max-width: 768px) {
        #calculationForm .taxonomy-node-children {
            margin-left: 8px;
            padding-left: 8px;
        }

        #calculationForm .taxonomy-node-floor > .taxonomy-node-children {
            margin-left: 12px;
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

        #calculationForm .work-type-group.taxonomy-inline-item {
            gap: 8px;
        }

        #calculationForm .work-type-group.taxonomy-inline-item > label {
            flex-basis: 112px;
            width: 112px !important;
        }

        #calculationForm .work-type-group.taxonomy-inline-item > .input-wrapper,
        .additional-worktype-group.taxonomy-inline-item .input-wrapper {
            flex-wrap: wrap;
        }

        #calculationForm .work-type-group.taxonomy-inline-item > .input-wrapper > .work-type-autocomplete,
        .additional-worktype-group.taxonomy-inline-item .input-wrapper > .work-type-autocomplete {
            flex: 1 1 100%;
            max-width: 100%;
            min-width: 0;
        }

        .worktype-inline-summary {
            flex: 0 0 auto;
            width: fit-content;
            max-width: 100%;
            min-width: 0;
        }

        #calculationForm .right-column {
            --work-item-parameter-indent-mobile: 28px;
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
            padding-left: calc(var(--work-item-parameter-indent-mobile) + var(--work-item-inline-indent-mobile));
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        #calculationForm #inputFormContainer .work-type-form {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        #calculationForm #inputFormContainer .work-type-form > .dimensions-container-vertical,
        #calculationForm #inputFormContainer .work-type-form > .work-type-right-col {
            grid-column: auto;
        }

        .additional-dimensions-container {
            margin-left: 0;
            padding-left: calc(var(--work-item-parameter-indent-mobile) + var(--work-item-inline-indent-mobile));
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        .additional-parameter-split {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .additional-work-item-grid {
            gap: 6px;
        }

        .additional-work-item-header {
            align-items: flex-start;
        }

        .additional-worktype-group {
            margin-left: 0;
            padding-left: var(--work-item-inline-indent-mobile);
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
    'workFloors' => $workFloors ?? [],
    'workAreas' => $workAreas ?? [],
    'workFields' => $workFields ?? [],
    'workItemGroupings' => $workItemGroupings ?? [],
    'storeLocations' => $storeLocationsForMap ?? [],
]) !!}
</script>
<script>
    const availableBestRecommendations = @json($bestRecommendations ?? []);
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js" defer></script>
<script src="{{ asset('js/vue/material-calculation-create-bridge.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-bridge.js')) }}" defer></script>
<script src="{{ asset('js/vue/material-calculation-create-search-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-search-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-taxonomy-tracker-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-taxonomy-tracker-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-scroll-fab-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-scroll-fab-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-store-mode-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-store-mode-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-ceramic-type-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-ceramic-type-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-filter-selection-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-filter-selection-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-work-taxonomy-filter-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-work-taxonomy-filter-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-multi-material-type-filter-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-multi-material-type-filter-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-dimension-expression-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-dimension-expression-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-taxonomy-autocomplete-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-taxonomy-autocomplete-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-worktype-autocomplete-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-worktype-autocomplete-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-material-type-filter-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-material-type-filter-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-work-item-form-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-work-item-form-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-item-focus-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-item-focus-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-item-header-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-item-header-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-bundle-floor-sort-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-bundle-floor-sort-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-floor-sort-queue-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-floor-sort-queue-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-main-floor-card-sort-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-main-floor-card-sort-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-rebuild-floor-order-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-rebuild-floor-order-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-sort-additional-items-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-sort-additional-items-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-main-taxonomy-footer-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-main-taxonomy-footer-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-taxonomy-footer-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-taxonomy-footer-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-inline-layout-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-inline-layout-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-row-kind-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-row-kind-engine.js')) }}"></script>
<script src="{{ asset('js/vue/material-calculation-create-additional-field-engine.js') }}?v={{ @filemtime(public_path('js/vue/material-calculation-create-additional-field-engine.js')) }}"></script>
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
        window.__materialCalcDelegateBundleSync = true;
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

        function sortFloors(values) {
            const list = Array.isArray(values) ? [...values] : [];
            const getFloorKey = name => {
                const s = String(name ?? '').trim();
                if (!s) return null;

                // Accept both legacy and simplified labels:
                // "Lantai Dasar" / "Dasar", "Lantai 2" / "2", and "Basement 1".
                if (/^(?:lantai\s+)?dasar$/i.test(s)) return 0;

                const lantaiMatch = /^(?:lantai\s+)?(\d+)$/i.exec(s);
                if (lantaiMatch) return parseInt(lantaiMatch[1], 10);

                const basementMatch = /^(?:basement|b)\s*(\d+)$/i.exec(s);
                if (basementMatch) return -parseInt(basementMatch[1], 10);
                return null;
            };
            return list.sort((a, b) => {
                const keyA = getFloorKey(a);
                const keyB = getFloorKey(b);
                if (keyA === null && keyB === null) {
                    return String(a ?? '').localeCompare(String(b ?? ''), 'id-ID');
                }
                if (keyA === null) return 1;
                if (keyB === null) return -1;
                return keyB - keyA;
            });
        }

        function initWorkTaxonomyFilters(formPayload) {
            if (typeof window.materialCalcCreateWorkTaxonomyFilterEngine === 'function') {
                try {
                    const bridgeWorkTaxonomyFilterApi = window.materialCalcCreateWorkTaxonomyFilterEngine({
                        deps: {
                            sortFloors,
                            uniqueFilterTokens,
                            sortAlphabetic,
                        },
                        formPayload,
                    });
                    if (
                        bridgeWorkTaxonomyFilterApi &&
                        typeof bridgeWorkTaxonomyFilterApi.setValues === 'function' &&
                        typeof bridgeWorkTaxonomyFilterApi.getValues === 'function' &&
                        typeof bridgeWorkTaxonomyFilterApi.subscribe === 'function' &&
                        typeof bridgeWorkTaxonomyFilterApi.refresh === 'function'
                    ) {
                        return bridgeWorkTaxonomyFilterApi;
                    }
                } catch (error) {
                    console.error('work taxonomy filter engine failed:', error);
                }
            }

            return {
                setValues() {},
                getValues() { return []; },
                subscribe() { return function() {}; },
                refresh() {},
            };
        }

        function relocateFilterSectionToRightGrid() {
            const filterSection = document.querySelector('#calculationForm .left-column .filter-section');
            const filterRightSlot = document.getElementById('filterByRightColumn');
            if (!(filterSection instanceof HTMLElement) || !(filterRightSlot instanceof HTMLElement)) {
                return;
            }
            if (filterSection.parentElement === filterRightSlot) {
                return;
            }
            filterRightSlot.appendChild(filterSection);
        }

        function buildMaterialTypeOptionMap(formPayload) {
            const sourceMap = {
                brick: formPayload?.bricks || [],
                cement: formPayload?.cements || [],
                sand: formPayload?.sands || [],
                cat: formPayload?.cats || [],
                ceramic_type: formPayload?.ceramics || [],
                nat: formPayload?.nats || [],
            };

            const valueResolver = {
                brick: item => item?.type || '',
                cement: item => item?.type || '',
                sand: item => item?.type || '',
                cat: item => item?.type || '',
                ceramic_type: item => item?.type || '',
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

        let bundleRowPanelSyncSeq = 0;

        function initMultiMaterialTypeFilters(formPayload) {
            if (typeof window.materialCalcCreateMultiMaterialTypeFilterEngine === 'function') {
                try {
                    const bridgeMultiMaterialTypeApi = window.materialCalcCreateMultiMaterialTypeFilterEngine({
                        deps: {
                            sortAlphabetic,
                            uniqueFilterTokens,
                            syncSharedBundleMaterialTypeAcrossItems,
                            linkBundleRowWithCustomizePanel,
                            materialTypeLabels,
                        },
                        formPayload,
                        optionsByType: buildMaterialTypeOptionMap(formPayload),
                    });
                    if (
                        bridgeMultiMaterialTypeApi &&
                        typeof bridgeMultiMaterialTypeApi.setValues === 'function' &&
                        typeof bridgeMultiMaterialTypeApi.clearHiddenRows === 'function' &&
                        typeof bridgeMultiMaterialTypeApi.clearAll === 'function'
                    ) {
                        return bridgeMultiMaterialTypeApi;
                    }
                } catch (error) {
                    console.error('multi material type filter engine failed:', error);
                }
            }

            return {
                setValues(type, values) {},
                clearHiddenRows() {},
                clearAll() {},
            };
        }

        let isRebuildingFloorCardOrder = false;
        let hasPendingFloorSort = false;
        let lastPointerDownTarget = null;
        let lastPointerDownAt = 0;
        let calcTaxonomyScrollTrackerApi = null;
        let calcScrollFabApi = null;
        let calcPageSearchApi = null;

        const workTaxonomyFilterApi = initWorkTaxonomyFilters(payload);
        const materialTypeFilterMultiApi = initMultiMaterialTypeFilters(payload);

        document.addEventListener(
            'pointerdown',
            function(event) {
                lastPointerDownTarget = event?.target instanceof HTMLElement ? event.target : null;
                lastPointerDownAt = Date.now();
            },
            true,
        );
        const initialMaterialTypeFilters = @json($selectedMaterialTypeFilters);
        if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.setValues === 'function' && initialMaterialTypeFilters) {
            Object.entries(initialMaterialTypeFilters).forEach(([type, value]) => {
                const values = Array.isArray(value) ? value : [value];
                materialTypeFilterMultiApi.setValues(type, values);
            });
        }

        function initCeramicTypeFilterAutocomplete() {
            if (typeof window.materialCalcCreateCeramicTypeEngine === 'function') {
                const bridgeCeramicTypeApi = window.materialCalcCreateCeramicTypeEngine({
                    deps: {
                        sortAlphabetic,
                        uniqueFilterTokens,
                    },
                    ceramicTypeOptions: @json(isset($ceramicTypes) ? $ceramicTypes->values()->all() : []),
                });
                if (bridgeCeramicTypeApi && typeof bridgeCeramicTypeApi.clear === 'function') {
                    return bridgeCeramicTypeApi;
                }
            }

            return {
                clear() {},
            };
        }

        const ceramicTypeFilterApi = initCeramicTypeFilterAutocomplete();

        // Handle filter checkboxes (multiple selection)
        const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
        const customForm = document.getElementById('customMaterialForm');
        const filterAll = document.getElementById('filter_all');
        const filterSelectionEngineApi = typeof window.materialCalcCreateFilterSelectionEngine === 'function'
            ? window.materialCalcCreateFilterSelectionEngine({
                availableBestRecommendations,
            })
            : null;

        // Legacy custom form stays hidden (custom UI lives in material type section)
        function ensureCustomFormVisible() {
            if (filterSelectionEngineApi && typeof filterSelectionEngineApi.ensureCustomFormVisible === 'function') {
                filterSelectionEngineApi.ensureCustomFormVisible();
                return;
            }
            if (customForm) {
                customForm.style.display = 'none';
            }
        }

        // Function to handle "Semua" checkbox
        function shouldIncludeBest() {
            if (filterSelectionEngineApi && typeof filterSelectionEngineApi.shouldIncludeBest === 'function') {
                return Boolean(filterSelectionEngineApi.shouldIncludeBest());
            }

            if (!Array.isArray(availableBestRecommendations) || availableBestRecommendations.length === 0) {
                return false;
            }

            const selectedTypes = new Set();
            const mainWorkType = String(document.getElementById('workTypeSelector')?.value || '').trim();
            if (mainWorkType !== '') {
                selectedTypes.add(mainWorkType);
            }

            document.querySelectorAll('#additionalWorkItemsList [data-field="work_type"]').forEach(input => {
                const value = String(input?.value || '').trim();
                if (value !== '') {
                    selectedTypes.add(value);
                }
            });

            for (const workType of selectedTypes) {
                if (availableBestRecommendations.includes(workType)) {
                    return true;
                }
            }

            return false;
        }

        function handleAllCheckbox() {
            if (filterSelectionEngineApi && typeof filterSelectionEngineApi.handleAllCheckbox === 'function') {
                filterSelectionEngineApi.handleAllCheckbox();
                return;
            }
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
            if (filterSelectionEngineApi && typeof filterSelectionEngineApi.handleOtherCheckboxes === 'function') {
                filterSelectionEngineApi.handleOtherCheckboxes();
                return;
            }
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

        const dimensionExpressionEngineApi = (() => {
            if (typeof window.materialCalcCreateDimensionExpressionEngine === 'function') {
                try {
                    return window.materialCalcCreateDimensionExpressionEngine({
                        deps: {
                            getAllAdditionalWorkRows: () => (typeof getAllAdditionalWorkRows === 'function' ? getAllAdditionalWorkRows() : []),
                            getMainWallLengthInput: () => mainWallLengthInput,
                            getMainWallHeightInput: () => mainWallHeightInput,
                            getCalcExpressionStateKey: () => calcExpressionStateKey,
                        },
                    });
                } catch (error) {
                    console.error('dimension expression engine failed:', error);
                }
            }
            return null;
        })();

        function callDimensionExpressionEngine(methodName, args, fallback) {
            if (dimensionExpressionEngineApi && typeof dimensionExpressionEngineApi[methodName] === 'function') {
                return dimensionExpressionEngineApi[methodName](...(Array.isArray(args) ? args : []));
            }
            return typeof fallback === 'function' ? fallback() : undefined;
        }

        function formatFixedPlain(value, decimals = 2) {
            return callDimensionExpressionEngine('formatFixedPlain', [value, decimals], () => "");
        }

        function formatThicknessValue(value) {
            return callDimensionExpressionEngine('formatThicknessValue', [value], () => formatFixedPlain(value, 2));
        }

        function getDimensionExpressionFieldKey(inputEl) {
            return callDimensionExpressionEngine('getDimensionExpressionFieldKey', [inputEl], () => "");
        }

        function isDimensionExpressionInput(inputEl) {
            return callDimensionExpressionEngine('isDimensionExpressionInput', [inputEl], () => Boolean(getDimensionExpressionFieldKey(inputEl)));
        }

        function hasArithmeticOperator(rawValue) {
            return callDimensionExpressionEngine('hasArithmeticOperator', [rawValue], () => /[+\-*/xX]/.test(String(rawValue || "")));
        }

        function sanitizeDimensionExpression(rawValue) {
            return callDimensionExpressionEngine('sanitizeDimensionExpression', [rawValue], () => String(rawValue || "").trim().replace(/\s+/g, "").replace(/,/g, "."));
        }

        function formatDimensionExpressionPreview(rawValue) {
            return callDimensionExpressionEngine('formatDimensionExpressionPreview', [rawValue], () => String(rawValue || "").trim());
        }

        function tokenizeDimensionExpression(expression) {
            return callDimensionExpressionEngine('tokenizeDimensionExpression', [expression], () => []);
        }

        function parseDimensionExpression(rawValue) {
            return callDimensionExpressionEngine('parseDimensionExpression', [rawValue], () => ({ ok: false, error: "engine_unavailable" }));
        }

        function formatDimensionNumericValue(value, decimals = 6) {
            return callDimensionExpressionEngine('formatDimensionNumericValue', [value, decimals], () => "");
        }

        function getDimensionExpressionHint(inputEl) {
            return callDimensionExpressionEngine('getDimensionExpressionHint', [inputEl], () => null);
        }

        function showDimensionExpressionHint(inputEl, expressionText) {
            return callDimensionExpressionEngine('showDimensionExpressionHint', [inputEl, expressionText], () => undefined);
        }

        function hideDimensionExpressionHint(inputEl) {
            return callDimensionExpressionEngine('hideDimensionExpressionHint', [inputEl], () => undefined);
        }

        function evaluateDimensionInputRawValue(rawValue) {
            return callDimensionExpressionEngine('evaluateDimensionInputRawValue', [rawValue], () => ({ ok: false, error: "engine_unavailable" }));
        }

        function parseDimensionNumericFromInput(inputEl) {
            return callDimensionExpressionEngine('parseDimensionNumericFromInput', [inputEl], () => ({ ok: false }));
        }

        function isDimensionInputCurrentlyVisible(inputEl) {
            return callDimensionExpressionEngine('isDimensionInputCurrentlyVisible', [inputEl], () => false);
        }

        function resolveDimensionAreaSummaryContainer(layoutEl) {
            return callDimensionExpressionEngine('resolveDimensionAreaSummaryContainer', [layoutEl], () => null);
        }

        function setDimensionAreaSummary(summaryEl, formulaText = '-', valueText = '-') {
            return callDimensionExpressionEngine('setDimensionAreaSummary', [summaryEl, formulaText, valueText], () => undefined);
        }

        function updateDimensionAreaSummary(layoutEl) {
            return callDimensionExpressionEngine('updateDimensionAreaSummary', [layoutEl], () => undefined);
        }

        function refreshDimensionAreaSummaries() {
            return callDimensionExpressionEngine('refreshDimensionAreaSummaries', [], () => undefined);
        }

        function evaluateDimensionExpressionInput(inputEl, options = {}) {
            return callDimensionExpressionEngine('evaluateDimensionExpressionInput', [inputEl, options], () => ({ ok: false, error: "engine_unavailable", inputEl }));
        }

        function bindDimensionExpressionInput(inputEl) {
            return callDimensionExpressionEngine('bindDimensionExpressionInput', [inputEl], () => undefined);
        }

        function bindDimensionExpressionInputs(scope = document) {
            return callDimensionExpressionEngine('bindDimensionExpressionInputs', [scope], () => undefined);
        }

        function normalizeDimensionExpressionInputsForSubmit(scope = document, options = {}) {
            return callDimensionExpressionEngine('normalizeDimensionExpressionInputsForSubmit', [scope, options], () => ({ ok: true }));
        }

        function collectDimensionExpressionState() {
            return callDimensionExpressionEngine('collectDimensionExpressionState', [], () => null);
        }

        function applyDimensionExpressionState(state) {
            return callDimensionExpressionEngine('applyDimensionExpressionState', [state], () => undefined);
        }

        function getStoredDimensionExpressionState() {
            return callDimensionExpressionEngine('getStoredDimensionExpressionState', [], () => null);
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
                    // Restore label to "Tinggi" for Plastering
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'skim_coating') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
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

            refreshDimensionAreaSummaries();
        }

        // Handle work type changes - simplified version
        let handleWorkTypeChange;

        if (workTypeSelector) {
            handleWorkTypeChange = function() {
                const selectedWorkType = workTypeSelector.value;

                // Update "Preferensi" filter state based on availability
                const filterBest = document.getElementById('filter_best');
                if (filterBest) {
                    if (shouldIncludeBest()) {
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
        if (additionalWorkItemsList) {
            additionalWorkItemsList.addEventListener('change', function(event) {
                const target = event.target;
                if (!(target instanceof HTMLElement) || !target.matches('[data-field="work_floor"]')) {
                    return;
                }
                markFloorSortPending();
            });
        }
        const mainWorkTypeLabel = document.getElementById('mainWorkTypeLabel');
        const mainWorkTypeDisplayInput = document.getElementById('workTypeDisplay');
        const mainWorkTypeHiddenInput = document.getElementById('workTypeSelector');
        const mainWorkFloorHiddenInput = document.getElementById('workFloorValue');
        const mainWorkFloorDisplayInput = document.getElementById('workFloorDisplay');
        const removeMainItemBtn = document.getElementById('removeMainItemBtn');
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
                    work_floor_norm: String(item?.work_floor || '').trim().toLowerCase(),
                    work_area_norm: String(item?.work_area || '').trim().toLowerCase(),
                    work_field_norm: String(item?.work_field || '').trim().toLowerCase(),
                    work_area: String(item?.work_area || '').trim(),
                    work_field: String(item?.work_field || '').trim(),
                }))
                .filter(item => item.formula_code !== '')
            : [];
        const workFloorOptionValues = sortFloors(
            uniqueFilterTokens((payload?.workFloors || []).map(item => item?.name || '')),
        );
        const workAreaOptionValues = sortAlphabetic(
            uniqueFilterTokens((payload?.workAreas || []).map(item => item?.name || '')),
        );
        const workFieldOptionValues = sortAlphabetic(
            uniqueFilterTokens((payload?.workFields || []).map(item => item?.name || '')),
        );
        const mainTaxonomyGroupCard = document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card');

        if (mainWorkFloorHiddenInput) {
            mainWorkFloorHiddenInput.addEventListener('change', function() {
                markFloorSortPending();
            });
        }
        if (mainWorkFloorDisplayInput) {
            mainWorkFloorDisplayInput.addEventListener('blur', function() {
                markFloorSortPending();
            });
        }
        if (mainTaxonomyGroupCard instanceof HTMLElement) {
            mainTaxonomyGroupCard.addEventListener(
                'focusout',
                function() {
                    flushFloorSortWhenFocusLeaves(mainTaxonomyGroupCard);
                },
                true,
            );
        }
        if (additionalWorkItemsList) {
            additionalWorkItemsList.addEventListener(
                'focusout',
                function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    const cardEl = target.closest('.additional-work-item[data-additional-work-item="true"]');
                    if (!(cardEl instanceof HTMLElement) || !additionalWorkItemsList.contains(cardEl)) {
                        return;
                    }
                    flushFloorSortWhenFocusLeaves(cardEl);
                },
                true,
            );
        }

        relocateFilterSectionToRightGrid();
        relocateMainTaxonomyActionButtonsToFooter();

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
            const mainActionsFooter = getDirectChildMatching(mainTaxonomyGroupCard, '.main-taxonomy-actions-row');
            if (mainActionsFooter instanceof HTMLElement) {
                if (mainActionsFooter.nextSibling) {
                    mainTaxonomyGroupCard.insertBefore(host, mainActionsFooter.nextSibling);
                } else {
                    mainTaxonomyGroupCard.appendChild(host);
                }
            } else {
                mainTaxonomyGroupCard.appendChild(host);
            }
            return host;
        }

        function setToggleItemVisibilityButtonState(buttonEl, collapsed) {
            if (!(buttonEl instanceof HTMLElement)) {
                return;
            }
            const isCollapsed = !!collapsed;
            buttonEl.classList.toggle('is-collapsed', !!collapsed);
            buttonEl.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
            const buttonLabel = isCollapsed
                ? 'Tampilkan kembali Item Pekerjaan pada bidang ini'
                : 'Sembunyikan Item Pekerjaan pada bidang ini';
            buttonEl.setAttribute('title', buttonLabel);
            buttonEl.setAttribute('aria-label', buttonLabel);
            const iconEl = buttonEl.querySelector('i');
            if (iconEl instanceof HTMLElement) {
                iconEl.classList.remove('bi-chevron-up', 'bi-chevron-down');
                iconEl.classList.add(isCollapsed ? 'bi-chevron-down' : 'bi-chevron-up');
            }
        }

        function getMainFieldNode() {
            const fieldNode = document.querySelector('#calculationForm .taxonomy-main-horizontal .taxonomy-node-field');
            return fieldNode instanceof HTMLElement ? fieldNode : null;
        }

        function setMainFieldItemContentCollapsed(collapsed) {
            const fieldNode = getMainFieldNode();
            if (!(fieldNode instanceof HTMLElement)) {
                return;
            }
            fieldNode.classList.toggle('is-item-content-collapsed', !!collapsed);
            if (mainTaxonomyGroupCard instanceof HTMLElement) {
                mainTaxonomyGroupCard.classList.toggle('is-main-item-content-collapsed', !!collapsed);
            }

            const mainAreaHost = getMainAreaChildrenHost();
            if (mainAreaHost instanceof HTMLElement) {
                getDirectAdditionalChildRows(mainAreaHost).forEach(row => {
                    const rowKind = normalizeBundleRowKind(
                        row.getAttribute('data-row-kind') || getAdditionalFieldValue(row, 'row_kind') || 'area',
                    );
                    if (rowKind === 'item') {
                        setAdditionalItemContentCollapsed(row, !!collapsed);
                    }
                });
            }

            const toggleBtn = document.getElementById('toggleMainFieldItemVisibilityBtn');
            if (toggleBtn instanceof HTMLElement) {
                setToggleItemVisibilityButtonState(toggleBtn, !!collapsed);
            }
        }

        function toggleMainFieldItemContentCollapsed() {
            const fieldNode = getMainFieldNode();
            if (!(fieldNode instanceof HTMLElement)) {
                return;
            }
            const nextCollapsed = !fieldNode.classList.contains('is-item-content-collapsed');
            setMainFieldItemContentCollapsed(nextCollapsed);
        }

        function resolveScopedWorkTypeOptionsByTaxonomy(
            selectedFloorsInput = [],
            selectedAreasInput = [],
            selectedFieldsInput = [],
        ) {
            if (!enableWorkTypeTaxonomyScoping) {
                return bundleFormulaOptions;
            }

            const floorSet = new Set(
                uniqueFilterTokens(Array.isArray(selectedFloorsInput) ? selectedFloorsInput : [selectedFloorsInput])
                    .map(value => String(value || '').trim().toLowerCase())
                    .filter(Boolean),
            );
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

            if (floorSet.size === 0 && areaSet.size === 0 && fieldSet.size === 0) {
                return bundleFormulaOptions;
            }

            if (workItemGroupingIndex.length === 0) {
                return bundleFormulaOptions;
            }

            const matchedCodes = new Set();
            workItemGroupingIndex.forEach(item => {
                if (floorSet.size > 0 && (!item.work_floor_norm || !floorSet.has(item.work_floor_norm))) {
                    return;
                }
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

        function resolveScopedWorkAreaOptionsByFloor(selectedFloorsInput = [], includeAreasInput = []) {
            const includeAreas = uniqueFilterTokens(
                Array.isArray(includeAreasInput) ? includeAreasInput : [includeAreasInput],
            );

            return sortAlphabetic(uniqueFilterTokens([...workAreaOptionValues, ...includeAreas]));
        }

        function resolveScopedWorkFieldOptionsByArea(
            selectedFloorsInput = [],
            selectedAreasInput = [],
            includeFieldsInput = [],
        ) {
            const includeFields = uniqueFilterTokens(
                Array.isArray(includeFieldsInput) ? includeFieldsInput : [includeFieldsInput],
            );

            return sortAlphabetic(uniqueFilterTokens([...workFieldOptionValues, ...includeFields]));
        }

        function resolveScopedWorkTypeOptions() {
            const selectedFloors = workTaxonomyFilterApi && typeof workTaxonomyFilterApi.getValues === 'function'
                ? workTaxonomyFilterApi.getValues('floor')
                : [];
            const selectedAreas = workTaxonomyFilterApi && typeof workTaxonomyFilterApi.getValues === 'function'
                ? workTaxonomyFilterApi.getValues('area')
                : [];
            const selectedFields = workTaxonomyFilterApi && typeof workTaxonomyFilterApi.getValues === 'function'
                ? workTaxonomyFilterApi.getValues('field')
                : [];
            return resolveScopedWorkTypeOptionsByTaxonomy(selectedFloors, selectedAreas, selectedFields);
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
        const bundleMaterialTypeOrder = ['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'nat'];
        const bundleCustomizeSupportedTypes = new Set(['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'nat']);
        const bundleMaterialTypeLabels = @json($materialTypeLabels);
        const bundleMaterialTypeOptions = buildMaterialTypeOptionMap(payload);
        let bundleAdditionalAutocompleteSeq = 0;
        let bundleCustomizePanelSeq = 0;
        const bundleDuplicateMaterialNoticeCache = new Set();
        let bundleSharedMaterialTypeSyncLock = false;
        let bundleSharedMaterialCustomizeSyncLock = false;
        let bundleForceRealtimeSyncLock = false;
        let bundleSharedCustomizeReconcileLock = false;
        let bundleSharedCustomizeSyncRafId = null;
        let bundleSharedCustomizeSyncTimerId = null;
        let bundlePayloadSyncQueued = false;
        const bundleSharedCustomizeRealtimeState = new Map();

        function normalizeBundleMaterialNoticeToken(value) {
            return String(value ?? '').trim().toLowerCase();
        }

        function resolveBundleMaterialWrapByPanel(panelEl, explicitType = '') {
            if (!(panelEl instanceof HTMLElement)) {
                return null;
            }
            const type = String(explicitType || panelEl.dataset.customizePanel || '').trim();
            const directWrap = panelEl.closest(`[data-material-wrap="${type}"]`);
            if (directWrap instanceof HTMLElement) {
                return directWrap;
            }
            const typedWrap = panelEl.closest(`.material-type-filter-item[data-material-type="${type}"]`);
            if (typedWrap instanceof HTMLElement) {
                return typedWrap;
            }
            return panelEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
        }

        function collectBundleMaterialTokensFromWrap(wrapEl, materialType) {
            if (!(wrapEl instanceof HTMLElement)) {
                return [];
            }
            const type = String(materialType || '').trim();
            if (!type) {
                return [];
            }
            const tokens = new Set();
            wrapEl.querySelectorAll(`.material-type-row[data-material-type="${type}"]`).forEach(rowEl => {
                if (!(rowEl instanceof HTMLElement)) {
                    return;
                }
                const hiddenEl = rowEl.querySelector('input[data-material-type-hidden="1"]');
                const displayEl = rowEl.querySelector('.autocomplete-input[data-material-display="1"], .autocomplete-input');
                [hiddenEl?.value || '', displayEl?.value || ''].forEach(rawValue => {
                    String(rawValue || '')
                        .split('|')
                        .map(token => normalizeBundleMaterialNoticeToken(token))
                        .filter(Boolean)
                        .forEach(token => tokens.add(token));
                });
            });
            return Array.from(tokens);
        }

        function queueBundlePayloadSync() {
            if (bundlePayloadSyncQueued) {
                return;
            }
            bundlePayloadSyncQueued = true;
            Promise.resolve().then(() => {
                bundlePayloadSyncQueued = false;
                if (typeof syncBundleFromForms === 'function') {
                    syncBundleFromForms();
                }
            });
        }

        function rebuildBundleSharedCustomizeRealtimeState() {
            bundleSharedCustomizeRealtimeState.clear();
            Array.from(bundleCustomizeSupportedTypes || []).forEach(rawType => {
                const type = String(rawType || '').trim();
                if (!type) {
                    return;
                }
                const wraps = collectBundleMaterialWrapsByType(type);
                wraps.forEach(wrapEl => {
                    if (!(wrapEl instanceof HTMLElement)) {
                        return;
                    }
                    const panelEl = wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
                    if (!(panelEl instanceof HTMLElement) || isBundleCustomizePanelDetached(panelEl)) {
                        return;
                    }
                    const panelValues = collectBundleCustomizePanelValues(panelEl, type);
                    const normalizedValues = {};
                    Object.entries(panelValues).forEach(([fieldKey, rawValue]) => {
                        const key = String(fieldKey || '').trim();
                        const value = String(rawValue || '').trim();
                        if (!key || !value) {
                            return;
                        }
                        normalizedValues[key] = value;
                    });
                    if (Object.keys(normalizedValues).length === 0) {
                        return;
                    }
                    const tokens = collectBundleMaterialTokensFromWrap(wrapEl, type);
                    if (!tokens.length) {
                        return;
                    }
                    tokens.forEach(rawToken => {
                        const tokenState = getBundleSharedCustomizeRealtimeTokenState(type, rawToken, true);
                        if (!tokenState || typeof tokenState !== 'object') {
                            return;
                        }
                        Object.entries(normalizedValues).forEach(([fieldKey, value]) => {
                            if (!Object.prototype.hasOwnProperty.call(tokenState, fieldKey)) {
                                tokenState[fieldKey] = value;
                            }
                        });
                    });
                });
            });
        }

        function collectBundleMaterialWrapsByType(materialType) {
            const type = String(materialType || '').trim();
            if (!type) {
                return [];
            }
            const wrapSet = new Set();
            document.querySelectorAll(`[data-material-wrap="${type}"]`).forEach(wrapEl => {
                if (wrapEl instanceof HTMLElement) {
                    wrapSet.add(wrapEl);
                }
            });
            document.querySelectorAll(`.material-type-filter-item[data-material-type="${type}"]`).forEach(wrapEl => {
                if (wrapEl instanceof HTMLElement) {
                    wrapSet.add(wrapEl);
                }
            });
            return Array.from(wrapSet);
        }

        function getBundleSharedCustomizeRealtimeTokenState(materialType, token, create = false) {
            const type = String(materialType || '').trim();
            const normalizedToken = normalizeBundleMaterialNoticeToken(token);
            if (!type || !normalizedToken) {
                return null;
            }
            const stateKey = `${type}::${normalizedToken}`;
            if (!bundleSharedCustomizeRealtimeState.has(stateKey)) {
                if (!create) {
                    return null;
                }
                bundleSharedCustomizeRealtimeState.set(stateKey, {});
            }
            return bundleSharedCustomizeRealtimeState.get(stateKey) || null;
        }

        function mergeBundleSharedCustomizeRealtimeValues(materialType, tokens) {
            const merged = {};
            const type = String(materialType || '').trim();
            if (!type) {
                return merged;
            }
            uniqueFilterTokens(Array.isArray(tokens) ? tokens : [tokens]).forEach(rawToken => {
                const tokenState = getBundleSharedCustomizeRealtimeTokenState(type, rawToken, false);
                if (!tokenState || typeof tokenState !== 'object') {
                    return;
                }
                Object.entries(tokenState).forEach(([fieldKey, rawValue]) => {
                    const normalizedFieldKey = String(fieldKey || '').trim();
                    const normalizedValue = String(rawValue || '').trim();
                    if (!normalizedFieldKey || !normalizedValue) {
                        return;
                    }
                    if (!Object.prototype.hasOwnProperty.call(merged, normalizedFieldKey)) {
                        merged[normalizedFieldKey] = normalizedValue;
                    }
                });
            });
            return merged;
        }

        function syncBundleSharedCustomizeRealtimeToWrap(materialType, wrapEl) {
            const type = String(materialType || '').trim();
            if (!type || !(wrapEl instanceof HTMLElement)) {
                return false;
            }
            const panelEl = wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }
            const tokens = collectBundleMaterialTokensFromWrap(wrapEl, type);
            if (!tokens.length) {
                return false;
            }
            const valuesToApply = mergeBundleSharedCustomizeRealtimeValues(type, tokens);
            if (Object.keys(valuesToApply).length === 0) {
                return false;
            }
            return applyBundleCustomizePanelValues(panelEl, type, valuesToApply, false);
        }

        function forceSyncBundleCustomizeFromSelect(targetSelect) {
            if (bundleForceRealtimeSyncLock) {
                return false;
            }
            if (!(targetSelect instanceof HTMLSelectElement)) {
                return false;
            }
            if (!targetSelect.matches('select[data-customize-filter][data-filter-key]')) {
                return false;
            }

            const panelEl = targetSelect.closest('.customize-panel[data-customize-panel]');
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }
            const type = String(panelEl.dataset.customizePanel || '').trim();
            const fieldKey = String(targetSelect.dataset.filterKey || '').trim();
            const nextValue = String(targetSelect.value || '').trim();
            if (!type || !fieldKey || !nextValue) {
                return false;
            }

            let sourceWrap = resolveBundleMaterialWrapByPanel(panelEl, type);
            if (!(sourceWrap instanceof HTMLElement)) {
                const sourceRowEl = resolveBundleSourceRowFromPanel(panelEl, type);
                if (sourceRowEl instanceof HTMLElement) {
                    sourceWrap =
                        sourceRowEl.closest(`[data-material-wrap="${type}"]`) ||
                        sourceRowEl.closest(`.material-type-filter-item[data-material-type="${type}"]`) ||
                        sourceRowEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
                }
            }
            if (!(sourceWrap instanceof HTMLElement)) {
                return false;
            }
            const sourceTokenSet = new Set(
                collectBundleMaterialTokensFromWrap(sourceWrap, type)
                    .map(token => normalizeBundleMaterialNoticeToken(token))
                    .filter(Boolean),
            );
            if (!sourceTokenSet.size) {
                return false;
            }

            const hasCommonToken = targetTokens => {
                if (!Array.isArray(targetTokens) || targetTokens.length === 0) {
                    return false;
                }
                return targetTokens.some(token => sourceTokenSet.has(normalizeBundleMaterialNoticeToken(token)));
            };

            const setSelectValue = selectEl => {
                if (!(selectEl instanceof HTMLSelectElement)) {
                    return false;
                }
                const hasOption = Array.from(selectEl.options).some(option => {
                    return String(option.value || '').trim() === nextValue;
                });
                if (!hasOption) {
                    const fallbackOption = document.createElement('option');
                    fallbackOption.value = nextValue;
                    fallbackOption.textContent = nextValue;
                    selectEl.appendChild(fallbackOption);
                }
                if (String(selectEl.value || '').trim() === nextValue) {
                    syncBundleCustomizeSelectDisplay(selectEl);
                    return false;
                }
                selectEl.value = nextValue;
                selectEl.dataset.bundleSynced = '1';
                syncBundleCustomizeSelectDisplay(selectEl);
                selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            };

            let hasAnyChanged = false;
            bundleForceRealtimeSyncLock = true;
            try {
                document
                    .querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`)
                    .forEach(candidatePanel => {
                        if (!(candidatePanel instanceof HTMLElement) || candidatePanel === panelEl) {
                            return;
                        }
                        if (isBundleCustomizePanelDetached(candidatePanel)) {
                            return;
                        }
                        const targetWrap = resolveBundleMaterialWrapByPanel(candidatePanel, type);
                        if (!(targetWrap instanceof HTMLElement)) {
                            return;
                        }
                        const targetTokens = collectBundleMaterialTokensFromWrap(targetWrap, type);
                        if (!hasCommonToken(targetTokens)) {
                            return;
                        }
                        const targetField = candidatePanel.querySelector(
                            `select[data-customize-filter="${type}"][data-filter-key="${fieldKey}"]`,
                        );
                        const changed = setSelectValue(targetField);
                        if (changed) {
                            hasAnyChanged = true;
                            syncBundleCustomizePanelToggleState(candidatePanel);
                            if (candidatePanel.hidden) {
                                candidatePanel.hidden = false;
                            }
                        }
                    });
            } finally {
                bundleForceRealtimeSyncLock = false;
            }

            syncAllBundleCustomizePanelToggleStates(type);

            return hasAnyChanged;
        }

        function enforceBundleSharedCustomizeRealtime(materialType = '') {
            let hasAnyChanged = false;
            const normalizedType = String(materialType || '').trim();
            const types = normalizedType
                ? [normalizedType]
                : Array.from(bundleCustomizeSupportedTypes || []);

            types.forEach(type => {
                const key = String(type || '').trim();
                if (!key) {
                    return;
                }
                const wraps = collectBundleMaterialWrapsByType(key);
                wraps.forEach(wrapEl => {
                    const changed = syncBundleSharedCustomizeRealtimeToWrap(key, wrapEl);
                    if (changed) {
                        hasAnyChanged = true;
                    }
                });
            });

            return hasAnyChanged;
        }

        function syncBundleSharedCustomizeRealtimeAcrossWraps(panelEl, specificFieldKey, specificFieldValue = '') {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }
            const type = String(panelEl.dataset.customizePanel || '').trim();
            const fieldKey = String(specificFieldKey || '').trim();
            const nextValue = String(specificFieldValue || '').trim();
            if (!type || !fieldKey || !nextValue) {
                return false;
            }

            const sourceWrap = resolveBundleMaterialWrapByPanel(panelEl, type);
            if (!(sourceWrap instanceof HTMLElement)) {
                return false;
            }
            const sourceTokens = collectBundleMaterialTokensFromWrap(sourceWrap, type);
            if (!sourceTokens.length) {
                return false;
            }

            sourceTokens.forEach(token => {
                const tokenState = getBundleSharedCustomizeRealtimeTokenState(type, token, true);
                if (tokenState && typeof tokenState === 'object') {
                    tokenState[fieldKey] = nextValue;
                }
            });

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                const sourceTokenSet = new Set(sourceTokens.map(token => normalizeBundleMaterialNoticeToken(token)));
                collectBundleMaterialWrapsByType(type).forEach(targetWrap => {
                    if (!(targetWrap instanceof HTMLElement)) {
                        return;
                    }
                    const targetTokens = collectBundleMaterialTokensFromWrap(targetWrap, type);
                    if (!targetTokens.length) {
                        return;
                    }
                    const hasIntersection = targetTokens.some(token => {
                        return sourceTokenSet.has(normalizeBundleMaterialNoticeToken(token));
                    });
                    if (!hasIntersection) {
                        return;
                    }
                    const targetPanel = targetWrap.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
                    if (!(targetPanel instanceof HTMLElement) || targetPanel === panelEl) {
                        return;
                    }
                    if (isBundleCustomizePanelDetached(targetPanel)) {
                        return;
                    }
                    const changed = applyBundleCustomizePanelValues(
                        targetPanel,
                        type,
                        { [fieldKey]: nextValue },
                        false,
                    );
                    if (changed) {
                        hasAnyChanged = true;
                    }
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }
            return hasAnyChanged;
        }

        function propagateBundleSharedCustomizeFromCurrentPanels() {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                bundleCustomizeSupportedTypes.forEach(type => {
                    const normalizedType = String(type || '').trim();
                    if (!normalizedType) {
                        return;
                    }
                    const wraps = collectBundleMaterialWrapsByType(normalizedType);
                    if (!wraps.length) {
                        return;
                    }

                    const tokenValueMap = new Map();
                    wraps.forEach(wrapEl => {
                        if (!(wrapEl instanceof HTMLElement)) {
                            return;
                        }
                        const panelEl = wrapEl.querySelector(
                            `.customize-panel[data-customize-panel="${normalizedType}"]`,
                        );
                        if (!(panelEl instanceof HTMLElement)) {
                            return;
                        }
                        if (isBundleCustomizePanelDetached(panelEl)) {
                            return;
                        }
                        const tokens = collectBundleMaterialTokensFromWrap(wrapEl, normalizedType);
                        if (!tokens.length) {
                            return;
                        }
                        const panelValues = collectBundleCustomizePanelValues(panelEl, normalizedType);
                        const normalizedValues = {};
                        Object.entries(panelValues).forEach(([fieldKey, rawValue]) => {
                            const key = String(fieldKey || '').trim();
                            const value = String(rawValue || '').trim();
                            if (!key || !value) {
                                return;
                            }
                            normalizedValues[key] = value;
                        });
                        if (Object.keys(normalizedValues).length === 0) {
                            return;
                        }

                        tokens.forEach(rawToken => {
                            const token = normalizeBundleMaterialNoticeToken(rawToken);
                            if (!token) {
                                return;
                            }
                            if (!tokenValueMap.has(token)) {
                                tokenValueMap.set(token, {});
                            }
                            const bucket = tokenValueMap.get(token) || {};
                            Object.entries(normalizedValues).forEach(([fieldKey, value]) => {
                                if (!Object.prototype.hasOwnProperty.call(bucket, fieldKey)) {
                                    bucket[fieldKey] = value;
                                }
                            });
                            tokenValueMap.set(token, bucket);
                        });
                    });

                    if (!tokenValueMap.size) {
                        return;
                    }

                    wraps.forEach(wrapEl => {
                        if (!(wrapEl instanceof HTMLElement)) {
                            return;
                        }
                        const panelEl = wrapEl.querySelector(
                            `.customize-panel[data-customize-panel="${normalizedType}"]`,
                        );
                        if (!(panelEl instanceof HTMLElement)) {
                            return;
                        }
                        if (isBundleCustomizePanelDetached(panelEl)) {
                            return;
                        }
                        const tokens = collectBundleMaterialTokensFromWrap(wrapEl, normalizedType)
                            .map(token => normalizeBundleMaterialNoticeToken(token))
                            .filter(Boolean);
                        if (!tokens.length) {
                            return;
                        }

                        const valuesToApply = {};
                        tokens.forEach(token => {
                            const tokenValues = tokenValueMap.get(token);
                            if (!tokenValues || typeof tokenValues !== 'object') {
                                return;
                            }
                            Object.entries(tokenValues).forEach(([fieldKey, rawValue]) => {
                                const key = String(fieldKey || '').trim();
                                const value = String(rawValue || '').trim();
                                if (!key || !value) {
                                    return;
                                }
                                if (!Object.prototype.hasOwnProperty.call(valuesToApply, key)) {
                                    valuesToApply[key] = value;
                                }
                            });
                        });
                        if (Object.keys(valuesToApply).length === 0) {
                            return;
                        }

                        const changed = applyBundleCustomizePanelValues(
                            panelEl,
                            normalizedType,
                            valuesToApply,
                            false,
                        );
                        if (changed) {
                            hasAnyChanged = true;
                        }
                    });
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }

            return hasAnyChanged;
        }

        function linkBundleRowWithCustomizePanel(rowEl, explicitType = '') {
            if (!(rowEl instanceof HTMLElement)) {
                return;
            }
            const type = String(explicitType || rowEl.dataset.materialType || '').trim();
            if (!type) {
                return;
            }

            const toggleBtn = rowEl.querySelector(`[data-customize-toggle="${type}"]`);
            if (!(toggleBtn instanceof HTMLElement)) {
                return;
            }

            let rowSyncId = String(rowEl.dataset.bundleRowSyncId || '').trim();
            if (!rowSyncId) {
                rowSyncId = `bundle-row-sync-${++bundleRowPanelSyncSeq}`;
                rowEl.dataset.bundleRowSyncId = rowSyncId;
            }

            const panelId = String(toggleBtn.dataset.customizePanelId || '').trim();
            if (!panelId) {
                return;
            }

            const owner =
                rowEl.closest('[data-additional-work-item="true"]') ||
                rowEl.closest('#inputFormContainer') ||
                rowEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]') ||
                document;
            const panelCandidates = Array.from(
                owner.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`),
            ).filter(panelEl => String(panelEl?.id || '').trim() === panelId);

            if (panelCandidates.length === 1 && panelCandidates[0] instanceof HTMLElement) {
                panelCandidates[0].dataset.bundleRowSyncId = rowSyncId;
                return;
            }

            const globalCandidates = Array.from(
                document.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`),
            ).filter(panelEl => String(panelEl?.id || '').trim() === panelId);
            if (globalCandidates.length === 1 && globalCandidates[0] instanceof HTMLElement) {
                globalCandidates[0].dataset.bundleRowSyncId = rowSyncId;
            }
        }

        function ensureBundleRowPanelSyncLinks(rootEl = document) {
            const scope = rootEl === document || rootEl instanceof HTMLElement ? rootEl : document;
            scope.querySelectorAll('[data-material-wrap], .material-type-filter-item[data-material-type]').forEach(wrap => {
                if (!(wrap instanceof HTMLElement)) {
                    return;
                }

                wrap.querySelectorAll('.material-type-row[data-material-type]').forEach(rowEl => {
                    if (!(rowEl instanceof HTMLElement)) {
                        return;
                    }
                    const type = String(rowEl.dataset.materialType || '').trim();
                    if (!type) {
                        return;
                    }
                    linkBundleRowWithCustomizePanel(rowEl, type);
                });
            });
        }

        function getBundleMaterialNoticeOwnerKey(rowEl) {
            if (!(rowEl instanceof HTMLElement)) {
                return '';
            }
            const additionalItemEl = rowEl.closest('[data-additional-work-item="true"]');
            if (additionalItemEl instanceof HTMLElement) {
                const index = getAllAdditionalWorkRows().indexOf(additionalItemEl);
                return index >= 0 ? `additional-${index + 1}` : 'additional';
            }
            const mainRoot = document.getElementById('inputFormContainer');
            if (mainRoot && mainRoot.contains(rowEl)) {
                return 'main';
            }
            return '';
        }

        function collectBundleRowsByMaterialTypeValue(materialType, normalizedValue) {
            const type = String(materialType || '').trim();
            const valueNorm = normalizeBundleMaterialNoticeToken(normalizedValue);
            if (!type || !valueNorm) {
                return [];
            }

            return collectBundleMaterialRowStatesByType(type)
                .filter(state => normalizeBundleMaterialNoticeToken(state?.hiddenEl?.value || '') === valueNorm)
                .map(state => state.rowEl)
                .filter(rowEl => rowEl instanceof HTMLElement);
        }

        function notifySharedBundleMaterialType(materialType, rawValue) {
            const type = String(materialType || '').trim();
            const value = String(rawValue || '').trim();
            const normalizedValue = normalizeBundleMaterialNoticeToken(value);
            if (!type || !normalizedValue) {
                return;
            }

            const matchingRows = collectBundleRowsByMaterialTypeValue(type, normalizedValue);
            if (!matchingRows.length) {
                return;
            }

            const owners = Array.from(
                new Set(
                    matchingRows
                        .map(getBundleMaterialNoticeOwnerKey)
                        .filter(Boolean),
                ),
            ).sort();
            if (owners.length < 2) {
                return;
            }

            const signature = `${type}|${normalizedValue}|${owners.join(',')}`;
            if (bundleDuplicateMaterialNoticeCache.has(signature)) {
                return;
            }
            bundleDuplicateMaterialNoticeCache.add(signature);

            const typeLabel = String(bundleMaterialTypeLabels?.[type] || type).trim();
            const message =
                `Jenis ${typeLabel} "${value}" dipakai di ${owners.length} item pekerjaan. ` +
                'Sistem bundle akan pakai material yang sama, dan custom per item tetap bisa diubah.';

            if (typeof window.showToast === 'function') {
                window.showToast(message, 'warning');
                return;
            }
            window.alert(message);
        }

        function getBundleMaterialRowState(rowEl) {
            if (!(rowEl instanceof HTMLElement)) {
                return null;
            }
            const state = rowEl.__materialTypeRowState || rowEl.__bundleMaterialRowState || {};
            if (state.hiddenEl instanceof HTMLInputElement && state.displayEl instanceof HTMLInputElement) {
                return state;
            }

            const hiddenEl = rowEl.querySelector('input[data-material-type-hidden="1"]');
            const displayEl = rowEl.querySelector('.autocomplete-input[data-material-display="1"], .autocomplete-input');
            if (!(hiddenEl instanceof HTMLInputElement) || !(displayEl instanceof HTMLInputElement)) {
                return null;
            }
            return {
                rowEl,
                hiddenEl,
                displayEl,
                listEl: null,
            };
        }

        function collectBundleMaterialRowStatesByType(materialType) {
            const type = String(materialType || '').trim();
            if (!type) {
                return [];
            }

            const states = [];
            const seenRows = new Set();
            const formRoot = document.getElementById('calculationForm') || document;
            formRoot.querySelectorAll(`.material-type-row[data-material-type="${type}"]`).forEach(rowEl => {
                if (!(rowEl instanceof HTMLElement) || seenRows.has(rowEl)) {
                    return;
                }
                seenRows.add(rowEl);

                const state = getBundleMaterialRowState(rowEl);
                if (!state) {
                    return;
                }
                states.push(state);
            });

            return states;
        }

        function setBundleMaterialRowStateValue(rowState, value) {
            if (!rowState || !(rowState.hiddenEl instanceof HTMLInputElement) || !(rowState.displayEl instanceof HTMLInputElement)) {
                return;
            }
            const nextValue = String(value || '').trim();
            if (String(rowState.hiddenEl.value || '').trim() === nextValue &&
                String(rowState.displayEl.value || '').trim() === nextValue) {
                rowState.__lastSharedTypeValue = nextValue;
                return;
            }
            rowState.hiddenEl.value = nextValue;
            rowState.displayEl.value = nextValue;
            rowState.__lastSharedTypeValue = nextValue;
            rowState.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function syncSharedBundleMaterialTypeAcrossItems(materialType, sourceRowState, previousRawValue) {
            if (bundleSharedMaterialTypeSyncLock) {
                return;
            }
            if (!sourceRowState || !(sourceRowState.rowEl instanceof HTMLElement) || !(sourceRowState.hiddenEl instanceof HTMLInputElement)) {
                return;
            }

            const type = String(materialType || '').trim();
            const previousValueNorm = normalizeBundleMaterialNoticeToken(previousRawValue);
            const currentValue = String(sourceRowState.hiddenEl.value || '').trim();
            const currentValueNorm = normalizeBundleMaterialNoticeToken(currentValue);

            sourceRowState.__lastSharedTypeValue = currentValue;
            if (!type || !previousValueNorm || previousValueNorm === currentValueNorm) {
                if (currentValue) {
                    syncSharedBundleMaterialCustomizeAcrossItems(type, sourceRowState.rowEl);
                    notifySharedBundleMaterialType(type, currentValue);
                }
                return;
            }

            const sourceOwner = getBundleMaterialNoticeOwnerKey(sourceRowState.rowEl);
            const targetStates = collectBundleMaterialRowStatesByType(type).filter(state => {
                if (!state || state === sourceRowState || !(state.hiddenEl instanceof HTMLInputElement)) {
                    return false;
                }
                const targetValueNorm = normalizeBundleMaterialNoticeToken(state.hiddenEl.value);
                return targetValueNorm === previousValueNorm;
            });

            if (!targetStates.length) {
                if (currentValue) {
                    notifySharedBundleMaterialType(type, currentValue);
                }
                return;
            }

            bundleSharedMaterialTypeSyncLock = true;
            try {
                targetStates.forEach(state => {
                    setBundleMaterialRowStateValue(state, currentValue);
                });
            } finally {
                bundleSharedMaterialTypeSyncLock = false;
            }

            if (typeof syncBundleFromForms === 'function') {
                syncBundleFromForms();
            }
            if (currentValue) {
                syncSharedBundleMaterialCustomizeAcrossItems(type, sourceRowState.rowEl);
            }
            if (currentValue) {
                notifySharedBundleMaterialType(type, currentValue);
            }
        }

        function resolveBundleCustomizePanelByRow(materialType, rowEl) {
            if (!(rowEl instanceof HTMLElement)) {
                return null;
            }
            const type = String(materialType || '').trim();
            if (!type) {
                return null;
            }

            ensureBundleRowPanelSyncLinks(
                rowEl.closest('[data-additional-work-item="true"]') ||
                    rowEl.closest('#inputFormContainer') ||
                    document,
            );

            const rowSyncId = String(rowEl.dataset.bundleRowSyncId || '').trim();
            if (rowSyncId) {
                const panelBySyncId = Array.from(
                    document.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`),
                ).find(panelEl => String(panelEl?.dataset?.bundleRowSyncId || '').trim() === rowSyncId);
                if (panelBySyncId instanceof HTMLElement) {
                    return panelBySyncId;
                }
            }

            const toggleBtn = rowEl.querySelector(`[data-customize-toggle="${type}"]`);
            if (toggleBtn instanceof HTMLElement) {
                const panelId = String(toggleBtn.dataset.customizePanelId || '').trim();
                if (panelId) {
                    const rowOwner =
                        rowEl.closest('[data-additional-work-item="true"]') ||
                        rowEl.closest('#inputFormContainer');
                    if (rowOwner instanceof HTMLElement) {
                        const panelInOwner = rowOwner.querySelector(
                            `.customize-panel[data-customize-panel="${type}"][id="${panelId}"]`,
                        );
                        if (panelInOwner instanceof HTMLElement) {
                            return panelInOwner;
                        }
                    }
                    const panelByIdCandidates = Array.from(
                        document.querySelectorAll(`.customize-panel[data-customize-panel="${type}"][id="${panelId}"]`),
                    );
                    if (panelByIdCandidates.length === 1 && panelByIdCandidates[0] instanceof HTMLElement) {
                        return panelByIdCandidates[0];
                    }
                    const panelById = document.getElementById(panelId);
                    if (panelById instanceof HTMLElement) {
                        return panelById;
                    }
                }
            }

            const wrap = rowEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
            if (wrap instanceof HTMLElement) {
                const panel = wrap.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
                if (panel instanceof HTMLElement) {
                    return panel;
                }
            }

            return null;
        }

        function collectBundleCustomizePanelValues(panelEl, materialType) {
            const values = {};
            if (!(panelEl instanceof HTMLElement)) {
                return values;
            }
            const type = String(materialType || '').trim();
            if (!type) {
                return values;
            }
            panelEl.querySelectorAll(`select[data-customize-filter="${type}"][data-filter-key]`).forEach(selectEl => {
                if (!(selectEl instanceof HTMLSelectElement)) {
                    return;
                }
                const filterKey = String(selectEl.dataset.filterKey || '').trim();
                if (!filterKey) {
                    return;
                }
                values[filterKey] = String(selectEl.value || '').trim();
            });
            return values;
        }

        function isBundleCustomizePanelDetached(panelEl) {
            return panelEl instanceof HTMLElement && String(panelEl.dataset.bundleCustomizeDetached || '') === '1';
        }

        function setBundleCustomizePanelDetached(panelEl, detached = true) {
            if (!(panelEl instanceof HTMLElement)) {
                return;
            }
            if (detached) {
                panelEl.dataset.bundleCustomizeDetached = '1';
            } else {
                delete panelEl.dataset.bundleCustomizeDetached;
            }
        }

        function resetBundleCustomizeDetachedIfEmpty(panelEl) {
            if (!(panelEl instanceof HTMLElement)) {
                return;
            }
            const materialType = String(panelEl.dataset.customizePanel || '').trim();
            if (!materialType) {
                return;
            }
            const values = collectBundleCustomizePanelValues(panelEl, materialType);
            const hasActiveFilter = Object.values(values).some(value => String(value || '').trim() !== '');
            if (!hasActiveFilter) {
                setBundleCustomizePanelDetached(panelEl, false);
            }
        }

        function resolveSharedBundleCustomizeFieldValue(panelEl, fieldKey) {
            if (!(panelEl instanceof HTMLElement)) {
                return '';
            }
            const type = String(panelEl.dataset.customizePanel || '').trim();
            const normalizedFieldKey = String(fieldKey || '').trim();
            if (!type || !normalizedFieldKey) {
                return '';
            }
            const sourceWrap = resolveBundleMaterialWrapByPanel(panelEl, type);
            if (!(sourceWrap instanceof HTMLElement)) {
                return '';
            }
            const sourceTokenSet = new Set(
                collectBundleMaterialTokensFromWrap(sourceWrap, type)
                    .map(token => normalizeBundleMaterialNoticeToken(token))
                    .filter(Boolean),
            );
            if (!sourceTokenSet.size) {
                return '';
            }

            const wraps = collectBundleMaterialWrapsByType(type);
            for (const wrapEl of wraps) {
                if (!(wrapEl instanceof HTMLElement) || wrapEl === sourceWrap) {
                    continue;
                }
                const panelCandidate = wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
                if (!(panelCandidate instanceof HTMLElement) || isBundleCustomizePanelDetached(panelCandidate)) {
                    continue;
                }
                const tokens = collectBundleMaterialTokensFromWrap(wrapEl, type);
                const intersects = tokens.some(token =>
                    sourceTokenSet.has(normalizeBundleMaterialNoticeToken(token)),
                );
                if (!intersects) {
                    continue;
                }
                const selectEl = panelCandidate.querySelector(
                    `select[data-customize-filter="${type}"][data-filter-key="${normalizedFieldKey}"]`,
                );
                if (!(selectEl instanceof HTMLSelectElement)) {
                    continue;
                }
                const value = String(selectEl.value || '').trim();
                if (value) {
                    return value;
                }
            }
            return '';
        }

        function tryReattachDetachedBundleCustomizePanel(panelEl, fieldKey, fieldValue) {
            if (!(panelEl instanceof HTMLElement) || !isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }
            const currentValue = String(fieldValue || '').trim();
            if (!currentValue) {
                resetBundleCustomizeDetachedIfEmpty(panelEl);
                return !isBundleCustomizePanelDetached(panelEl);
            }
            const peerValue = resolveSharedBundleCustomizeFieldValue(panelEl, fieldKey);
            if (peerValue && peerValue === currentValue) {
                setBundleCustomizePanelDetached(panelEl, false);
                return true;
            }
            return false;
        }

        function syncBundleCustomizePanelToggleState(panelEl) {
            if (!(panelEl instanceof HTMLElement)) {
                return;
            }
            const panelId = String(panelEl.id || '').trim();
            const materialType = String(panelEl.dataset.customizePanel || '').trim();
            if (!panelId || !materialType) {
                return;
            }
            const values = collectBundleCustomizePanelValues(panelEl, materialType);
            const hasActiveFilter = Object.values(values).some(value => String(value || '').trim() !== '');
            document.querySelectorAll(`[data-customize-panel-id="${panelId}"]`).forEach(btn => {
                if (btn instanceof HTMLElement) {
                    btn.classList.toggle('is-active', hasActiveFilter);
                    btn.textContent = 'Custom';
                }
            });
        }

        function syncAllBundleCustomizePanelToggleStates(materialType = '') {
            const type = String(materialType || '').trim();
            const selector = type
                ? `.customize-panel[data-customize-panel="${type}"]`
                : '.customize-panel[data-customize-panel]';
            document.querySelectorAll(selector).forEach(panelEl => {
                if (panelEl instanceof HTMLElement) {
                    syncBundleCustomizePanelToggleState(panelEl);
                }
            });
        }

        function openBundleCustomizePanel(panelEl) {
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (!panelEl.hidden) {
                return true;
            }

            const panelId = String(panelEl.id || '').trim();
            if (panelId) {
                const openBtn = document.querySelector(`[data-customize-panel-id="${panelId}"]`);
                if (openBtn instanceof HTMLElement) {
                    openBtn.click();
                }
            }

            if (panelEl.hidden) {
                panelEl.hidden = false;
            }
            return !panelEl.hidden;
        }

        function syncBundleCustomizeSelectDisplay(selectEl) {
            if (!(selectEl instanceof HTMLSelectElement)) {
                return;
            }
            if (selectEl.__customizeAutocompleteUi?.syncDisplayFromSelect) {
                selectEl.__customizeAutocompleteUi.syncDisplayFromSelect();
                return;
            }
            const inputWrapper = selectEl.closest('.input-wrapper');
            if (!(inputWrapper instanceof HTMLElement)) {
                return;
            }
            const displayEl = inputWrapper.querySelector(
                '.customize-filter-autocomplete .customize-filter-display, .customize-filter-display',
            );
            if (!(displayEl instanceof HTMLInputElement)) {
                return;
            }
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            displayEl.value = selectedOption && selectedOption.value ? String(selectedOption.textContent || '') : '';
            const firstOption = selectEl.options[0];
            displayEl.placeholder = String(firstOption?.textContent || '-- Semua --');
        }

        function applyBundleCustomizePanelValues(panelEl, materialType, values, dispatchChange = false) {
            if (!(panelEl instanceof HTMLElement) || !values || typeof values !== 'object') {
                return false;
            }
            const type = String(materialType || '').trim();
            if (!type) {
                return false;
            }
            let hasChanged = false;
            panelEl.querySelectorAll(`select[data-customize-filter="${type}"][data-filter-key]`).forEach(selectEl => {
                if (!(selectEl instanceof HTMLSelectElement)) {
                    return;
                }
                const filterKey = String(selectEl.dataset.filterKey || '').trim();
                if (!filterKey || !Object.prototype.hasOwnProperty.call(values, filterKey)) {
                    return;
                }
                const nextValue = String(values[filterKey] || '').trim();
                if (String(selectEl.value || '').trim() === nextValue) {
                    syncBundleCustomizeSelectDisplay(selectEl);
                    return;
                }
                if (nextValue) {
                    const hasOption = Array.from(selectEl.options).some(option => String(option.value || '').trim() === nextValue);
                    if (!hasOption) {
                        const fallbackOption = document.createElement('option');
                        fallbackOption.value = nextValue;
                        fallbackOption.textContent = nextValue;
                        selectEl.appendChild(fallbackOption);
                    }
                }
                selectEl.value = nextValue;
                selectEl.dataset.bundleSynced = '1';
                syncBundleCustomizeSelectDisplay(selectEl);
                if (dispatchChange) {
                    selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                hasChanged = true;
            });
            syncBundleCustomizePanelToggleState(panelEl);
            if (hasChanged && panelEl.hidden && panelHasActiveCustomizeValues(panelEl)) {
                openBundleCustomizePanel(panelEl);
            }
            return hasChanged;
        }

        function resolveBundleSourceRowFromPanel(panelEl, materialType) {
            if (!(panelEl instanceof HTMLElement)) {
                return null;
            }
            const type = String(materialType || '').trim();
            ensureBundleRowPanelSyncLinks(
                panelEl.closest('[data-additional-work-item="true"]') ||
                    panelEl.closest('#inputFormContainer') ||
                    document,
            );

            const panelSyncId = String(panelEl.dataset.bundleRowSyncId || '').trim();
            if (panelSyncId) {
                const rowBySyncId = document.querySelector(`.material-type-row[data-bundle-row-sync-id="${panelSyncId}"]`);
                if (rowBySyncId instanceof HTMLElement) {
                    return rowBySyncId;
                }
            }

            const panelId = String(panelEl.id || '').trim();
            if (panelId) {
                const siblingCandidates = [panelEl.previousElementSibling, panelEl.nextElementSibling];
                for (const siblingEl of siblingCandidates) {
                    if (!(siblingEl instanceof HTMLElement) || !siblingEl.matches('.material-type-row')) {
                        continue;
                    }
                    const siblingToggleBtn = siblingEl.querySelector(`[data-customize-panel-id="${panelId}"]`);
                    if (siblingToggleBtn instanceof HTMLElement) {
                        return siblingEl;
                    }
                }

                const panelOwner =
                    panelEl.closest('[data-additional-work-item="true"]') ||
                    panelEl.closest('#inputFormContainer') ||
                    panelEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
                if (panelOwner instanceof HTMLElement) {
                    const toggleBtnInOwner = panelOwner.querySelector(`[data-customize-panel-id="${panelId}"]`);
                    const rowByOwnerToggle = toggleBtnInOwner?.closest('.material-type-row') || null;
                    if (rowByOwnerToggle instanceof HTMLElement) {
                        return rowByOwnerToggle;
                    }
                }

                const toggleBtnCandidates = Array.from(document.querySelectorAll(`[data-customize-panel-id="${panelId}"]`));
                if (toggleBtnCandidates.length === 1) {
                    const rowByToggle = toggleBtnCandidates[0].closest('.material-type-row');
                    if (rowByToggle instanceof HTMLElement) {
                        return rowByToggle;
                    }
                }
            }
            const wrap = panelEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
            if (wrap instanceof HTMLElement) {
                const rowInWrap = wrap.querySelector(
                    type ? `.material-type-row[data-material-type="${type}"]` : '.material-type-row',
                );
                if (rowInWrap instanceof HTMLElement) {
                    return rowInWrap;
                }
            }
            return null;
        }

        function syncBundleSharedCustomizeFieldFromPanel(panelEl, specificFieldKey, specificFieldValue = '') {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }

            const type = String(panelEl.dataset.customizePanel || '').trim();
            const fieldKey = String(specificFieldKey || '').trim();
            if (!type || !fieldKey) {
                return false;
            }

            const resolvePanelWrap = candidatePanelEl => {
                if (!(candidatePanelEl instanceof HTMLElement)) {
                    return null;
                }
                const directWrap = candidatePanelEl.closest(`[data-material-wrap="${type}"]`);
                if (directWrap instanceof HTMLElement) {
                    return directWrap;
                }
                const typedWrap = candidatePanelEl.closest(`.material-type-filter-item[data-material-type="${type}"]`);
                if (typedWrap instanceof HTMLElement) {
                    return typedWrap;
                }
                return candidatePanelEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
            };

            const collectNormValuesFromWrap = wrapEl => {
                if (!(wrapEl instanceof HTMLElement)) {
                    return [];
                }
                return Array.from(
                    wrapEl.querySelectorAll(`.material-type-row[data-material-type="${type}"] input[data-material-type-hidden="1"]`),
                )
                    .map(hiddenEl => normalizeBundleMaterialNoticeToken(hiddenEl?.value || ''))
                    .filter(Boolean)
                    .filter((value, index, arr) => arr.indexOf(value) === index);
            };

            const sourceWrap = resolvePanelWrap(panelEl);
            const sourceNormValues = collectNormValuesFromWrap(sourceWrap);
            if (!sourceNormValues.length) {
                return false;
            }

            const sourceSelect = panelEl.querySelector(
                `select[data-customize-filter="${type}"][data-filter-key="${fieldKey}"]`,
            );
            const nextValue = String(
                specificFieldValue !== '' ? specificFieldValue : sourceSelect?.value || '',
            ).trim();
            if (!nextValue) {
                // "Tidak Pilih" should only clear this panel, not broadcast blank values.
                return false;
            }

            const wraps = [];
            const seenWraps = new Set();
            const wrapSelectors = [
                `[data-material-wrap="${type}"]`,
                `.material-type-filter-item[data-material-type="${type}"]`,
            ];
            wrapSelectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(wrapEl => {
                    if (!(wrapEl instanceof HTMLElement) || seenWraps.has(wrapEl)) {
                        return;
                    }
                    seenWraps.add(wrapEl);
                    wraps.push(wrapEl);
                });
            });

            const targetPanels = wraps
                .filter(wrapEl => !(sourceWrap instanceof HTMLElement) || wrapEl !== sourceWrap)
                .filter(wrapEl => {
                    const candidateNormValues = collectNormValuesFromWrap(wrapEl);
                    return sourceNormValues.some(value => candidateNormValues.includes(value));
                })
                .map(wrapEl => wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`))
                .filter(panelCandidate => {
                    return panelCandidate instanceof HTMLElement &&
                        panelCandidate !== panelEl &&
                        !isBundleCustomizePanelDetached(panelCandidate);
                });

            if (!targetPanels.length) {
                const fallbackPanels = Array.from(
                    document.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`),
                ).filter(candidatePanel => {
                    if (!(candidatePanel instanceof HTMLElement) || candidatePanel === panelEl) {
                        return false;
                    }
                    if (isBundleCustomizePanelDetached(candidatePanel)) {
                        return false;
                    }
                    const candidateWrap = resolvePanelWrap(candidatePanel);
                    const candidateNormValues = collectNormValuesFromWrap(candidateWrap);
                    return sourceNormValues.some(value => candidateNormValues.includes(value));
                });
                if (!fallbackPanels.length) {
                    return false;
                }
                targetPanels.push(...fallbackPanels);
            }

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                targetPanels.forEach(targetPanel => {
                    const targetSelect = targetPanel.querySelector(
                        `select[data-customize-filter="${type}"][data-filter-key="${fieldKey}"]`,
                    );
                    if (!(targetSelect instanceof HTMLSelectElement)) {
                        return;
                    }

                    if (nextValue) {
                        const hasOption = Array.from(targetSelect.options).some(option => {
                            return String(option.value || '').trim() === nextValue;
                        });
                        if (!hasOption) {
                            const fallbackOption = document.createElement('option');
                            fallbackOption.value = nextValue;
                            fallbackOption.textContent = nextValue;
                            targetSelect.appendChild(fallbackOption);
                        }
                    }

                    if (String(targetSelect.value || '').trim() === nextValue) {
                        syncBundleCustomizeSelectDisplay(targetSelect);
                        return;
                    }

                    targetSelect.value = nextValue;
                    targetSelect.dataset.bundleSynced = '1';
                    syncBundleCustomizeSelectDisplay(targetSelect);
                    hasAnyChanged = true;
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }

            if (hasAnyChanged && typeof syncBundleFromForms === 'function') {
                syncBundleFromForms();
            }
            return hasAnyChanged;
        }

        function syncBundleSharedCustomizeFieldByItemScope(panelEl, specificFieldKey, specificFieldValue = '') {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }

            const type = String(panelEl.dataset.customizePanel || '').trim();
            const fieldKey = String(specificFieldKey || '').trim();
            const nextValue = String(specificFieldValue || '').trim();
            if (!type || !fieldKey || !nextValue) {
                return false;
            }

            const mainRoot = document.getElementById('inputFormContainer');
            if (!(mainRoot instanceof HTMLElement)) {
                return false;
            }

            const additionalRows = getAllAdditionalWorkRows().filter(rowEl => rowEl instanceof HTMLElement);
            const scopeEntries = [
                {
                    scopeEl: mainRoot,
                    collectItemData: () => collectMainWorkItemDraft(),
                },
                ...additionalRows.map((rowEl, index) => ({
                    scopeEl: rowEl,
                    collectItemData: () => collectAdditionalWorkItemData(rowEl, index + 1),
                })),
            ];

            const sourceScope = panelEl.closest('[data-additional-work-item="true"]') || mainRoot;
            if (!(sourceScope instanceof HTMLElement)) {
                return false;
            }
            const sourceEntry = scopeEntries.find(entry => entry.scopeEl === sourceScope);
            if (!sourceEntry) {
                return false;
            }

            const normalizeTokens = rawTokens => {
                return uniqueFilterTokens(Array.isArray(rawTokens) ? rawTokens : [rawTokens])
                    .map(token => normalizeBundleMaterialNoticeToken(token))
                    .filter(Boolean);
            };

            const sourceItemData = sourceEntry.collectItemData();
            const sourceNormValues = normalizeTokens(
                getBundleMaterialTypeValues(sourceItemData?.material_type_filters || {}, type),
            );
            if (!sourceNormValues.length) {
                return false;
            }

            const hasIntersection = (valuesA, valuesB) => {
                return valuesA.some(value => valuesB.includes(value));
            };

            const applyFieldValueToPanel = targetPanel => {
                if (!(targetPanel instanceof HTMLElement)) {
                    return false;
                }
                if (isBundleCustomizePanelDetached(targetPanel)) {
                    return false;
                }
                const targetSelect = targetPanel.querySelector(
                    `select[data-customize-filter="${type}"][data-filter-key="${fieldKey}"]`,
                );
                if (!(targetSelect instanceof HTMLSelectElement)) {
                    return false;
                }

                const hasOption = Array.from(targetSelect.options).some(option => {
                    return String(option.value || '').trim() === nextValue;
                });
                if (!hasOption) {
                    const fallbackOption = document.createElement('option');
                    fallbackOption.value = nextValue;
                    fallbackOption.textContent = nextValue;
                    targetSelect.appendChild(fallbackOption);
                }

                if (String(targetSelect.value || '').trim() === nextValue) {
                    syncBundleCustomizeSelectDisplay(targetSelect);
                    return false;
                }

                targetSelect.value = nextValue;
                targetSelect.dataset.bundleSynced = '1';
                syncBundleCustomizeSelectDisplay(targetSelect);
                return true;
            };

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                scopeEntries.forEach(entry => {
                    const scopeEl = entry.scopeEl;
                    if (!(scopeEl instanceof HTMLElement) || scopeEl === sourceScope) {
                        return;
                    }

                    const targetItemData = entry.collectItemData();
                    const targetNormValues = normalizeTokens(
                        getBundleMaterialTypeValues(targetItemData?.material_type_filters || {}, type),
                    );
                    if (!targetNormValues.length || !hasIntersection(sourceNormValues, targetNormValues)) {
                        return;
                    }

                    const panelCandidates = Array.from(
                        scopeEl.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`),
                    ).filter(targetPanel => {
                        if (!(targetPanel instanceof HTMLElement)) {
                            return false;
                        }
                        const ownerAdditional = targetPanel.closest('[data-additional-work-item="true"]');
                        if (scopeEl.matches('[data-additional-work-item="true"]')) {
                            return ownerAdditional === scopeEl;
                        }
                        return !(ownerAdditional instanceof HTMLElement);
                    });

                    panelCandidates.forEach(targetPanel => {
                        const changed = applyFieldValueToPanel(targetPanel);
                        if (changed) {
                            hasAnyChanged = true;
                        }
                    });
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }

            if (hasAnyChanged && typeof syncBundleFromForms === 'function') {
                syncBundleFromForms();
            }
            return hasAnyChanged;
        }

        function syncBundleSharedCustomizeFieldByRowState(panelEl, specificFieldKey, specificFieldValue = '') {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }
            if (!(panelEl instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(panelEl)) {
                return false;
            }

            const type = String(panelEl.dataset.customizePanel || '').trim();
            const fieldKey = String(specificFieldKey || '').trim();
            const nextValue = String(specificFieldValue || '').trim();
            if (!type || !fieldKey || !nextValue) {
                return false;
            }

            const sourceRowEl = resolveBundleSourceRowFromPanel(panelEl, type);
            const sourceState = getBundleMaterialRowState(sourceRowEl);
            if (!sourceState || !(sourceState.rowEl instanceof HTMLElement)) {
                return false;
            }

            const sourceWrap = sourceState.rowEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
            if (!(sourceWrap instanceof HTMLElement)) {
                return false;
            }

            const sourceNormValues = Array.from(
                sourceWrap.querySelectorAll(`.material-type-row[data-material-type="${type}"] input[data-material-type-hidden="1"]`),
            )
                .map(hiddenEl => normalizeBundleMaterialNoticeToken(hiddenEl?.value || ''))
                .filter(Boolean)
                .filter((value, index, arr) => arr.indexOf(value) === index);
            if (!sourceNormValues.length) {
                return false;
            }

            const targetStates = collectBundleMaterialRowStatesByType(type).filter(state => {
                if (!state || !(state.rowEl instanceof HTMLElement) || state.rowEl === sourceState.rowEl) {
                    return false;
                }
                const rowValue = normalizeBundleMaterialNoticeToken(state.hiddenEl?.value || state.displayEl?.value || '');
                return rowValue && sourceNormValues.includes(rowValue);
            });
            if (!targetStates.length) {
                return false;
            }

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                targetStates.forEach(state => {
                    const targetPanel = resolveBundleCustomizePanelByRow(type, state.rowEl);
                    if (!(targetPanel instanceof HTMLElement)) {
                        return;
                    }
                    if (isBundleCustomizePanelDetached(targetPanel)) {
                        return;
                    }
                    const targetSelect = targetPanel.querySelector(
                        `select[data-customize-filter="${type}"][data-filter-key="${fieldKey}"]`,
                    );
                    if (!(targetSelect instanceof HTMLSelectElement)) {
                        return;
                    }

                    const hasOption = Array.from(targetSelect.options).some(option => {
                        return String(option.value || '').trim() === nextValue;
                    });
                    if (!hasOption) {
                        const fallbackOption = document.createElement('option');
                        fallbackOption.value = nextValue;
                        fallbackOption.textContent = nextValue;
                        targetSelect.appendChild(fallbackOption);
                    }

                    if (String(targetSelect.value || '').trim() === nextValue) {
                        syncBundleCustomizeSelectDisplay(targetSelect);
                        return;
                    }

                    targetSelect.value = nextValue;
                    targetSelect.dataset.bundleSynced = '1';
                    syncBundleCustomizeSelectDisplay(targetSelect);
                    hasAnyChanged = true;
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }

            if (hasAnyChanged && typeof syncBundleFromForms === 'function') {
                syncBundleFromForms();
            }
            return hasAnyChanged;
        }

        function reconcileBundleSharedCustomizeSelections() {
            if (bundleSharedCustomizeReconcileLock || bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }

            const collectNormValuesFromWrapByType = (wrapEl, type) => {
                if (!(wrapEl instanceof HTMLElement) || !type) {
                    return [];
                }
                return Array.from(
                    wrapEl.querySelectorAll(`.material-type-row[data-material-type="${type}"] input[data-material-type-hidden="1"]`),
                )
                    .map(hiddenEl => normalizeBundleMaterialNoticeToken(hiddenEl?.value || ''))
                    .filter(Boolean)
                    .filter((value, index, arr) => arr.indexOf(value) === index);
            };

            let hasAnyChanged = false;
            bundleSharedCustomizeReconcileLock = true;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                bundleCustomizeSupportedTypes.forEach(type => {
                    const wraps = [];
                    const seenWraps = new Set();
                    [
                        `[data-material-wrap="${type}"]`,
                        `.material-type-filter-item[data-material-type="${type}"]`,
                    ].forEach(selector => {
                        document.querySelectorAll(selector).forEach(wrapEl => {
                            if (!(wrapEl instanceof HTMLElement) || seenWraps.has(wrapEl)) {
                                return;
                            }
                            seenWraps.add(wrapEl);
                            wraps.push(wrapEl);
                        });
                    });

                    const entries = wraps
                        .map(wrapEl => {
                            const panelEl = wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`);
                            if (!(panelEl instanceof HTMLElement)) {
                                return null;
                            }
                            if (isBundleCustomizePanelDetached(panelEl)) {
                                return null;
                            }
                            const normValues = collectNormValuesFromWrapByType(wrapEl, type);
                            if (!normValues.length) {
                                return null;
                            }
                            return {
                                wrapEl,
                                panelEl,
                                normValues,
                                values: collectBundleCustomizePanelValues(panelEl, type),
                            };
                        })
                        .filter(Boolean);
                    if (entries.length < 2) {
                        return;
                    }

                    const groups = new Map();
                    entries.forEach(entry => {
                        entry.normValues.forEach(normValue => {
                            if (!groups.has(normValue)) {
                                groups.set(normValue, []);
                            }
                            groups.get(normValue).push(entry);
                        });
                    });

                    groups.forEach(groupEntries => {
                        if (!Array.isArray(groupEntries) || groupEntries.length < 2) {
                            return;
                        }

                        const mergedValues = {};
                        groupEntries.forEach(entry => {
                            Object.entries(entry.values || {}).forEach(([fieldKey, rawValue]) => {
                                const normalizedKey = String(fieldKey || '').trim();
                                const normalizedValue = String(rawValue || '').trim();
                                if (!normalizedKey || !normalizedValue) {
                                    return;
                                }
                                if (!Object.prototype.hasOwnProperty.call(mergedValues, normalizedKey)) {
                                    mergedValues[normalizedKey] = normalizedValue;
                                }
                            });
                        });
                        if (Object.keys(mergedValues).length === 0) {
                            return;
                        }

                        groupEntries.forEach(entry => {
                            const changed = applyBundleCustomizePanelValues(entry.panelEl, type, mergedValues, false);
                            if (changed) {
                                hasAnyChanged = true;
                            }
                        });
                    });
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
                bundleSharedCustomizeReconcileLock = false;
            }

            return hasAnyChanged;
        }

        function scheduleBundleSharedCustomizeSync(panelEl, fieldKey, fieldValue) {
            if (!(panelEl instanceof HTMLElement)) {
                return;
            }
            const normalizedFieldKey = String(fieldKey || '').trim();
            const normalizedFieldValue = String(fieldValue || '').trim();
            if (!normalizedFieldKey || !normalizedFieldValue) {
                return;
            }

            if (bundleSharedCustomizeSyncRafId) {
                cancelAnimationFrame(bundleSharedCustomizeSyncRafId);
                bundleSharedCustomizeSyncRafId = null;
            }
            if (bundleSharedCustomizeSyncTimerId) {
                clearTimeout(bundleSharedCustomizeSyncTimerId);
                bundleSharedCustomizeSyncTimerId = null;
            }

            const executeSync = () => {
                bundleSharedCustomizeSyncRafId = null;
                bundleSharedCustomizeSyncTimerId = null;

                if (!document.body.contains(panelEl)) {
                    return;
                }

                let changed = false;
                changed = syncBundleSharedCustomizeFieldByRowState(panelEl, normalizedFieldKey, normalizedFieldValue) || changed;
                changed = syncBundleSharedCustomizeFieldByItemScope(panelEl, normalizedFieldKey, normalizedFieldValue) || changed;
                changed = syncBundleSharedCustomizeFieldFromPanel(panelEl, normalizedFieldKey, normalizedFieldValue) || changed;

                if (changed && typeof syncBundleFromForms === 'function') {
                    syncBundleFromForms();
                }
            };

            bundleSharedCustomizeSyncRafId = requestAnimationFrame(() => {
                bundleSharedCustomizeSyncTimerId = setTimeout(executeSync, 0);
            });
        }

        function syncSharedBundleMaterialCustomizeAcrossItems(
            materialType,
            sourceRowEl = null,
            specificFieldKey = '',
            specificFieldValue = '',
            sourcePanelEl = null,
        ) {
            if (bundleSharedMaterialCustomizeSyncLock) {
                return false;
            }

            const type = String(materialType || '').trim();
            if (!type) {
                return false;
            }

            const normalizedSpecificFieldKey = String(specificFieldKey || '').trim();
            const normalizedSpecificFieldValue = String(specificFieldValue || '').trim();

            let sourceState = getBundleMaterialRowState(sourceRowEl);
            if (
                !sourceState &&
                sourcePanelEl instanceof HTMLElement
            ) {
                const panelMatchedState = collectBundleMaterialRowStatesByType(type).find(state => {
                    if (!state || !(state.rowEl instanceof HTMLElement)) {
                        return false;
                    }
                    const matchedPanel = resolveBundleCustomizePanelByRow(type, state.rowEl);
                    return matchedPanel === sourcePanelEl;
                });
                if (panelMatchedState) {
                    sourceState = panelMatchedState;
                }
            }
            if (!sourceState && sourcePanelEl instanceof HTMLElement) {
                const sourceRowByPanel = resolveBundleSourceRowFromPanel(sourcePanelEl, type);
                if (sourceRowByPanel instanceof HTMLElement) {
                    sourceState = getBundleMaterialRowState(sourceRowByPanel);
                }
            }

            const sourceRowResolvedEl = sourceState?.rowEl instanceof HTMLElement ? sourceState.rowEl : null;
            if (!sourceState || !(sourceState.hiddenEl instanceof HTMLInputElement)) {
                return false;
            }

            let sourceRawValue = String(sourceState.hiddenEl.value || sourceState.displayEl?.value || '').trim();
            if (!sourceRawValue && sourcePanelEl instanceof HTMLElement) {
                const sourceWrap = sourcePanelEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
                if (sourceWrap instanceof HTMLElement) {
                    const hiddenInWrap = sourceWrap.querySelector(`.material-type-row[data-material-type="${type}"] input[data-material-type-hidden="1"]`);
                    if (hiddenInWrap instanceof HTMLInputElement) {
                        sourceRawValue = String(hiddenInWrap.value || '').trim();
                    }
                }
            }
            const sourceValueNorm = normalizeBundleMaterialNoticeToken(sourceRawValue);
            if (!sourceValueNorm) {
                return false;
            }

            const sourcePanel = sourcePanelEl instanceof HTMLElement
                ? sourcePanelEl
                : resolveBundleCustomizePanelByRow(type, sourceState.rowEl);
            if (!(sourcePanel instanceof HTMLElement)) {
                return false;
            }
            if (isBundleCustomizePanelDetached(sourcePanel)) {
                return false;
            }
            const sourceValues = collectBundleCustomizePanelValues(sourcePanel, type);

            const targetStates = collectBundleMaterialRowStatesByType(type).filter(state => {
                if (!state || state === sourceState || !(state.hiddenEl instanceof HTMLInputElement)) {
                    return false;
                }
                if (sourceRowResolvedEl && state.rowEl === sourceRowResolvedEl) {
                    return false;
                }
                const targetRawValue = String(state.hiddenEl.value || state.displayEl?.value || '').trim();
                return normalizeBundleMaterialNoticeToken(targetRawValue) === sourceValueNorm;
            });
            if (!targetStates.length) {
                return false;
            }

            const valuesToSync = {};
            if (normalizedSpecificFieldKey) {
                const field = normalizedSpecificFieldKey;
                const fallbackValue = Object.prototype.hasOwnProperty.call(sourceValues, field)
                    ? String(sourceValues[field] || '').trim()
                    : '';
                valuesToSync[field] = normalizedSpecificFieldValue !== '' ? normalizedSpecificFieldValue : fallbackValue;
            } else {
                Object.assign(valuesToSync, sourceValues);
            }
            if (Object.keys(valuesToSync).length === 0) {
                return false;
            }

            let hasAnyChanged = false;
            bundleSharedMaterialCustomizeSyncLock = true;
            try {
                targetStates.forEach(state => {
                    const targetPanel = resolveBundleCustomizePanelByRow(type, state.rowEl);
                    if (!(targetPanel instanceof HTMLElement)) {
                        return;
                    }
                    if (isBundleCustomizePanelDetached(targetPanel)) {
                        return;
                    }
                    const changed = applyBundleCustomizePanelValues(targetPanel, type, valuesToSync, true);
                    if (changed) {
                        hasAnyChanged = true;
                    }
                });
            } finally {
                bundleSharedMaterialCustomizeSyncLock = false;
            }

            if (hasAnyChanged && typeof syncBundleFromForms === 'function') {
                syncBundleFromForms();
            }
            return hasAnyChanged;
        }

        function openSyncedBundleCustomizePanelsForSelection(sourcePanelEl, materialType) {
            if (!(sourcePanelEl instanceof HTMLElement)) {
                return false;
            }
            const type = String(materialType || '').trim();
            if (!type) {
                return false;
            }

            const sourceWrap = resolveBundleMaterialWrapByPanel(sourcePanelEl, type);
            if (!(sourceWrap instanceof HTMLElement)) {
                return false;
            }

            const sourceTokenSet = new Set(
                collectBundleMaterialTokensFromWrap(sourceWrap, type)
                    .map(token => normalizeBundleMaterialNoticeToken(token))
                    .filter(Boolean),
            );
            if (!sourceTokenSet.size) {
                return false;
            }

            let openedAny = false;
            document.querySelectorAll(`.customize-panel[data-customize-panel="${type}"]`).forEach(panelEl => {
                if (!(panelEl instanceof HTMLElement) || panelEl === sourcePanelEl) {
                    return;
                }
                if (isBundleCustomizePanelDetached(panelEl)) {
                    return;
                }
                if (!panelHasActiveCustomizeValues(panelEl)) {
                    return;
                }
                const wrapEl = resolveBundleMaterialWrapByPanel(panelEl, type);
                if (!(wrapEl instanceof HTMLElement)) {
                    return;
                }
                const targetTokens = collectBundleMaterialTokensFromWrap(wrapEl, type)
                    .map(token => normalizeBundleMaterialNoticeToken(token))
                    .filter(Boolean);
                const hasIntersection = targetTokens.some(token => sourceTokenSet.has(token));
                if (!hasIntersection) {
                    return;
                }
                if (panelEl.hidden) {
                    openBundleCustomizePanel(panelEl);
                    openedAny = true;
                }
                syncBundleCustomizePanelToggleState(panelEl);
            });

            return openedAny;
        }

        function bindBundleSharedCustomizeSyncListener() {
            if (document.__bundleSharedCustomizeSyncBound) {
                return;
            }
            document.__bundleSharedCustomizeSyncBound = true;

            window.__syncBundleSharedCustomizeFromSelect = function(targetSelect) {
                if (!(targetSelect instanceof HTMLSelectElement)) {
                    return;
                }
                if (!targetSelect.matches('select[data-customize-filter][data-filter-key]')) {
                    return;
                }
                if (bundleForceRealtimeSyncLock) {
                    return;
                }
                if (bundleSharedMaterialCustomizeSyncLock) {
                    return;
                }

                const panelEl = targetSelect.closest('.customize-panel[data-customize-panel]');
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }
                if (isBundleCustomizePanelDetached(panelEl)) {
                    if (typeof syncBundleFromForms === 'function') {
                        syncBundleFromForms();
                    }
                    queueBundlePayloadSync();
                    return;
                }

                const materialType = String(panelEl.dataset.customizePanel || '').trim();
                if (!materialType) {
                    return;
                }

                const fieldKey = String(targetSelect.dataset.filterKey || '').trim();
                const fieldValue = String(targetSelect.value || '').trim();
                if (fieldValue) {
                    scheduleBundleSharedCustomizeSync(panelEl, fieldKey, fieldValue);
                    const forcedNow = forceSyncBundleCustomizeFromSelect(targetSelect);
                    if (forcedNow && typeof syncBundleFromForms === 'function') {
                        syncBundleFromForms();
                    }
                }
                if (fieldValue === '') {
                    if (typeof syncBundleFromForms === 'function') {
                        syncBundleFromForms();
                    }
                    return;
                }

                const runSyncPass = () => {
                    let hasAnyChanged = false;
                    hasAnyChanged =
                        syncBundleSharedCustomizeRealtimeAcrossWraps(panelEl, fieldKey, fieldValue) ||
                        hasAnyChanged;
                    hasAnyChanged =
                        syncBundleSharedCustomizeFieldByRowState(panelEl, fieldKey, fieldValue) || hasAnyChanged;
                    hasAnyChanged =
                        syncBundleSharedCustomizeFieldByItemScope(panelEl, fieldKey, fieldValue) || hasAnyChanged;
                    hasAnyChanged =
                        syncBundleSharedCustomizeFieldFromPanel(panelEl, fieldKey, fieldValue) || hasAnyChanged;
                    const sourceRowEl = resolveBundleSourceRowFromPanel(panelEl, materialType);
                    hasAnyChanged =
                        syncSharedBundleMaterialCustomizeAcrossItems(
                            materialType,
                            sourceRowEl instanceof HTMLElement ? sourceRowEl : null,
                            fieldKey,
                            fieldValue,
                            panelEl,
                        ) || hasAnyChanged;
                    hasAnyChanged = enforceBundleSharedCustomizeRealtime(materialType) || hasAnyChanged;
                    return hasAnyChanged;
                };

                runSyncPass();
                const openedNow = openSyncedBundleCustomizePanelsForSelection(panelEl, materialType);
                if (typeof syncBundleFromForms === 'function') {
                    syncBundleFromForms();
                }
                queueBundlePayloadSync();
                if (openedNow) {
                    return;
                }

                let retryCount = 0;
                const retrySync = () => {
                    retryCount += 1;
                    if (!document.body.contains(panelEl)) {
                        return;
                    }
                    const syncedRetry = runSyncPass();
                    const openedRetry = openSyncedBundleCustomizePanelsForSelection(panelEl, materialType);
                    if ((syncedRetry || openedRetry) && typeof syncBundleFromForms === 'function') {
                        syncBundleFromForms();
                    }
                    queueBundlePayloadSync();
                    if (!openedRetry && retryCount < 2) {
                        setTimeout(retrySync, 80);
                    }
                };
                requestAnimationFrame(() => {
                    setTimeout(retrySync, 0);
                });
            };
            window.__forceBundleCustomizeRealtime = forceSyncBundleCustomizeFromSelect;

            document.addEventListener('change', function(event) {
                const target = event?.target;
                if (!(target instanceof HTMLSelectElement)) {
                    return;
                }
                if (!target.matches('select[data-customize-filter][data-filter-key]')) {
                    return;
                }
                const panelEl = target.closest('.customize-panel[data-customize-panel]');
                if (panelEl instanceof HTMLElement) {
                    const isProgrammaticSync = bundleForceRealtimeSyncLock || bundleSharedMaterialCustomizeSyncLock;
                    if (!isProgrammaticSync) {
                        const wasSyncedFromBundle = String(target.dataset.bundleSynced || '') === '1';
                        delete target.dataset.bundleSynced;
                        if (wasSyncedFromBundle) {
                            setBundleCustomizePanelDetached(panelEl, true);
                        }
                        const fieldKey = String(target.dataset.filterKey || '').trim();
                        const fieldValue = String(target.value || '').trim();
                        tryReattachDetachedBundleCustomizePanel(panelEl, fieldKey, fieldValue);
                        resetBundleCustomizeDetachedIfEmpty(panelEl);
                    }
                    syncBundleCustomizePanelToggleState(panelEl);
                    if (isBundleCustomizePanelDetached(panelEl)) {
                        if (typeof syncBundleFromForms === 'function') {
                            syncBundleFromForms();
                        }
                        queueBundlePayloadSync();
                        return;
                    }
                }
                window.__syncBundleSharedCustomizeFromSelect(target);
            });

            document.addEventListener('change', function(event) {
                const target = event?.target;
                if (!(target instanceof HTMLInputElement)) {
                    return;
                }
                if (!target.matches('input[data-material-type-hidden="1"]')) {
                    return;
                }
                const rowEl = target.closest('.material-type-row[data-material-type]');
                if (!(rowEl instanceof HTMLElement)) {
                    return;
                }
                const type = String(rowEl.dataset.materialType || '').trim();
                if (!type) {
                    return;
                }
                const wrapEl =
                    rowEl.closest(`[data-material-wrap="${type}"]`) ||
                    rowEl.closest(`.material-type-filter-item[data-material-type="${type}"]`) ||
                    rowEl.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
                const panelEl =
                    wrapEl instanceof HTMLElement
                        ? wrapEl.querySelector(`.customize-panel[data-customize-panel="${type}"]`)
                        : null;
                if (panelEl instanceof HTMLElement) {
                    // Material type change indicates this row can rejoin shared-sync scope.
                    setBundleCustomizePanelDetached(panelEl, false);
                    panelEl
                        .querySelectorAll(`select[data-customize-filter="${type}"][data-filter-key]`)
                        .forEach(selectEl => {
                            if (selectEl instanceof HTMLSelectElement) {
                                delete selectEl.dataset.bundleSynced;
                            }
                        });
                }
                rebuildBundleSharedCustomizeRealtimeState();
                let changed = syncBundleSharedCustomizeRealtimeToWrap(type, wrapEl);
                changed = enforceBundleSharedCustomizeRealtime(type) || changed;
                if (changed && typeof syncBundleFromForms === 'function') {
                    syncBundleFromForms();
                }
                queueBundlePayloadSync();
            });

            document.addEventListener('click', function(event) {
                const target = event?.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }
                const toggleBtn = target.closest('[data-customize-toggle]');
                if (toggleBtn instanceof HTMLElement) {
                    const materialType = String(toggleBtn.dataset.customizeToggle || '').trim();
                    if (materialType) {
                        requestAnimationFrame(() => {
                            setTimeout(() => {
                                const panelId = String(toggleBtn.dataset.customizePanelId || '').trim();
                                let wrapEl =
                                    toggleBtn.closest(`[data-material-wrap="${materialType}"]`) ||
                                    toggleBtn.closest(`.material-type-filter-item[data-material-type="${materialType}"]`) ||
                                    toggleBtn.closest('[data-material-wrap], .material-type-filter-item[data-material-type]');
                                if (!(wrapEl instanceof HTMLElement) && panelId) {
                                    const panelEl = document.getElementById(panelId);
                                    if (panelEl instanceof HTMLElement) {
                                        wrapEl = resolveBundleMaterialWrapByPanel(panelEl, materialType);
                                    }
                                }
                                const changed = wrapEl instanceof HTMLElement
                                    ? syncBundleSharedCustomizeRealtimeToWrap(materialType, wrapEl)
                                    : enforceBundleSharedCustomizeRealtime(materialType);
                                if (changed && typeof syncBundleFromForms === 'function') {
                                    syncBundleFromForms();
                                }
                            }, 0);
                        });
                    }
                }
                if (!target.closest('.autocomplete-item')) {
                    return;
                }

                const panelEl = target.closest('.customize-panel[data-customize-panel]');
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }

                const materialType = String(panelEl.dataset.customizePanel || '').trim();
                if (!materialType) {
                    return;
                }
                // No-op: selection sync is handled by `change` event and direct hook.
            });
        }

        bindBundleSharedCustomizeSyncListener();

        function bindBundleSharedCustomizeRealtimeObserver() {
            if (document.__bundleSharedCustomizeRealtimeObserverBound) {
                return;
            }
            document.__bundleSharedCustomizeRealtimeObserverBound = true;

            let rafId = null;
            const scheduleEnforce = () => {
                if (rafId) {
                    return;
                }
                rafId = requestAnimationFrame(() => {
                    rafId = null;
                    const changed = enforceBundleSharedCustomizeRealtime();
                    if (changed && typeof syncBundleFromForms === 'function') {
                        syncBundleFromForms();
                    }
                });
            };

            const observer = new MutationObserver(mutations => {
                let shouldSync = false;
                mutations.forEach(mutation => {
                    if (shouldSync) {
                        return;
                    }
                    mutation.addedNodes.forEach(node => {
                        if (shouldSync || !(node instanceof HTMLElement)) {
                            return;
                        }
                        if (
                            node.matches?.('[data-material-wrap], .material-type-filter-item[data-material-type], .customize-panel[data-customize-panel]') ||
                            node.querySelector?.('[data-material-wrap], .material-type-filter-item[data-material-type], .customize-panel[data-customize-panel]')
                        ) {
                            shouldSync = true;
                        }
                    });
                });
                if (shouldSync) {
                    scheduleEnforce();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        bindBundleSharedCustomizeRealtimeObserver();

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
            if (type === 'ceramic_type') {
                return 'Jenis Keramik';
            }
            const label = bundleMaterialTypeLabels[type] || type;
            return `Jenis ${label}`;
        }

        function getBundleMaterialTypePlaceholder(type) {
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

            if (type === 'ceramic_type') {
                return `
                    <div class="customize-panel material-type-customize-panel" data-customize-panel="${type}" ${panelId ? `id="${panelId}"` : ''} hidden>
                        <div class="customize-grid">
                            ${renderField('Dimensi', 'dimension')}
                            ${renderField('Merek', 'brand')}
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
                ceramic_type: ['brand', 'dimension', 'sub_brand', 'surface', 'code', 'color'],
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

        function clearCustomizeFiltersInRoot(rootEl) {
            if (!rootEl) {
                return;
            }

            rootEl.querySelectorAll('.customize-panel[data-customize-panel]').forEach(panelEl => {
                if (!(panelEl instanceof HTMLElement)) {
                    return;
                }

                panelEl.querySelectorAll('select[data-customize-filter][data-filter-key]').forEach(selectEl => {
                    if (!(selectEl instanceof HTMLSelectElement)) {
                        return;
                    }
                    if (selectEl.value !== '') {
                        selectEl.value = '';
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                panelEl.hidden = true;
                const panelId = String(panelEl.id || '').trim();
                if (!panelId) {
                    return;
                }
                rootEl.querySelectorAll(`[data-customize-panel-id="${panelId}"]`).forEach(btn => {
                    if (btn instanceof HTMLElement) {
                        btn.classList.remove('is-active');
                    }
                });
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
                work_floor: String(entry.work_floor || '').trim(),
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
                restore_scope: String(entry.restore_scope || '').trim().toLowerCase(),
                restore_parent_area_key: String(entry.restore_parent_area_key || '').trim().toLowerCase(),
                restore_parent_field_key: String(entry.restore_parent_field_key || '').trim().toLowerCase(),
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
            let normalized = 'area';
            if (kind === 'field') {
                normalized = 'field';
            } else if (kind === 'floor') {
                normalized = 'floor';
            }
            const hiddenId =
                normalized === 'field'
                    ? 'workFieldValue'
                    : normalized === 'floor'
                      ? 'workFloorValue'
                      : 'workAreaValue';
            const displayId =
                normalized === 'field'
                    ? 'workFieldDisplay'
                    : normalized === 'floor'
                      ? 'workFloorDisplay'
                      : 'workAreaDisplay';
            return getMainFormValue(hiddenId) || getMainFormValue(displayId);
        }

        function collectMainWorkItemDraft() {
            const workType = mainWorkTypeHiddenInput ? String(mainWorkTypeHiddenInput.value || '').trim() : '';
            return normalizeBundleItem(
                {
                    title: mainWorkTypeDisplayInput ? String(mainWorkTypeDisplayInput.value || '').trim() : '',
                    work_floor: getMainTaxonomyValue('floor'),
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

        function collectMainWorkItem() {
            const item = collectMainWorkItemDraft();
            return String(item?.work_type || '').trim() ? item : null;
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
            if (typeof window.materialCalcCreateAdditionalTaxonomyAutocompleteEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalTaxonomyAutocompleteEngine({
                        deps: {
                            bindAutocompleteScrollLock,
                            uniqueFilterTokens,
                            sortFloors,
                            workFloorOptionValues,
                            resolveScopedWorkAreaOptionsByFloor,
                            resolveScopedWorkFieldOptionsByArea,
                            markFloorSortPending,
                            autoMergeDuplicateAdditionalTaxonomyRow,
                        },
                        itemEl,
                        initial,
                    });
                } catch (error) {
                    console.error('additional taxonomy autocomplete engine failed:', error);
                }
            }
        }

        function initAdditionalWorkTypeAutocomplete(itemEl, initial = {}) {
            if (typeof window.materialCalcCreateAdditionalWorkTypeAutocompleteEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalWorkTypeAutocompleteEngine({
                        deps: {
                            bundleFormulaOptions,
                            bindAutocompleteScrollLock,
                            normalizeBundleRowKind,
                            getAdditionalFieldValue,
                            resolveScopedWorkTypeOptionsByTaxonomy,
                        },
                        itemEl,
                        initial,
                    });
                } catch (error) {
                    console.error('additional worktype autocomplete engine failed:', error);
                }
            }
        }

        function initAdditionalMaterialTypeFilters(itemEl, initialFilters = {}) {
            if (typeof window.materialCalcCreateAdditionalMaterialTypeFilterEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalMaterialTypeFilterEngine({
                        deps: {
                            bundleMaterialTypeOrder,
                            sortAlphabetic,
                            uniqueFilterTokens,
                            bundleMaterialTypeOptions,
                            syncBundleFromForms,
                            syncSharedBundleMaterialTypeAcrossItems,
                            linkBundleRowWithCustomizePanel,
                            bundleCustomizeSupportedTypes,
                            getBundleMaterialTypePlaceholder,
                            bundleMaterialTypeLabels,
                            getBundleMaterialTypeValues,
                            nextBundleAdditionalAutocompleteSeq: () => ++bundleAdditionalAutocompleteSeq,
                            nextBundleCustomizePanelSeq: () => ++bundleCustomizePanelSeq,
                        },
                        itemEl,
                        initialFilters,
                    });
                } catch (error) {
                    console.error('additional material type filter engine failed:', error);
                }
            }
        }

        function createAdditionalWorkItemForm(initial = {}, afterElement = null, options = {}) {
            if (typeof window.materialCalcCreateAdditionalWorkItemFormEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalWorkItemFormEngine({
                        deps: {
                            additionalWorkItemsList,
                            normalizeBundleRowKind,
                            normalizeBundleItem,
                            getAllAdditionalWorkRows,
                            escapeHtml,
                            buildBundleMaterialFilterSectionHtml,
                            resolveAdditionalInsertionTarget,
                            bindDimensionExpressionInputs,
                            setAdditionalWorkItemRowKind,
                            refreshAdditionalTaxonomyActionFooters,
                            initAdditionalWorkTaxonomyAutocomplete,
                            initAdditionalWorkTypeAutocomplete,
                            initAdditionalMaterialTypeFilters,
                            applyMaterialCustomizeFiltersToPanels,
                            attachAdditionalWorkItemEvents,
                            applyAdditionalWorkItemVisibility,
                            refreshAdditionalWorkItemHeader,
                            syncBundleFromForms,
                            nextBundleAdditionalAutocompleteSeq: () => ++bundleAdditionalAutocompleteSeq,
                        },
                        initial,
                        afterElement,
                        options,
                    });
                } catch (error) {
                    console.error('additional work item form engine failed:', error);
                }
            }

            return null;
        }
        function getMainTaxonomyContext() {
            return {
                work_floor: getMainTaxonomyValue('floor'),
                work_area: getMainTaxonomyValue('area'),
                work_field: getMainTaxonomyValue('field'),
            };
        }

        function normalizeAdditionalWorkItemStructure(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }

            const grid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            if (!(grid instanceof HTMLElement)) {
                return;
            }

            if (getDirectChildMatching(grid, '.additional-row-taxonomy-grid')) {
                return;
            }

            const floorGroup = grid.querySelector('.additional-work-floor-group');
            const areaGroup = grid.querySelector('.additional-work-area-group');
            const fieldGroup = grid.querySelector('.additional-work-field-group');
            const itemNode = grid.querySelector('.additional-node-item');
            const floorHost = getDirectChildMatching(grid, '[data-floor-children]') || grid.querySelector('[data-floor-children]');
            const areaHost = getDirectChildMatching(grid, '[data-area-children]') || grid.querySelector('[data-area-children]');

            const taxonomyGrid = document.createElement('div');
            taxonomyGrid.className = 'additional-row-taxonomy-grid';
            if (floorGroup instanceof HTMLElement) taxonomyGrid.appendChild(floorGroup);
            if (areaGroup instanceof HTMLElement) taxonomyGrid.appendChild(areaGroup);
            if (fieldGroup instanceof HTMLElement) taxonomyGrid.appendChild(fieldGroup);

            const itemContent = document.createElement('div');
            itemContent.className = 'additional-row-item-content';
            if (itemNode instanceof HTMLElement) itemContent.appendChild(itemNode);

            const directHiddenInputs = Array.from(grid.children).filter(
                child => child instanceof HTMLElement && child.matches('input[type="hidden"]'),
            );

            const nextChildren = [...directHiddenInputs, taxonomyGrid, itemContent];
            if (floorHost instanceof HTMLElement) nextChildren.push(floorHost);
            if (areaHost instanceof HTMLElement) nextChildren.push(areaHost);

            grid.replaceChildren(...nextChildren);
        }

        function setInlineStylesImportant(el, styles = {}) {
            if (!(el instanceof HTMLElement)) {
                return;
            }
            Object.entries(styles).forEach(([prop, value]) => {
                if (value === null || value === undefined || value === '') {
                    el.style.removeProperty(prop);
                    return;
                }
                el.style.setProperty(prop, String(value), 'important');
            });
        }

        function clearInlineStyles(el, props = []) {
            if (!(el instanceof HTMLElement)) {
                return;
            }
            props.forEach(prop => el.style.removeProperty(prop));
        }

        function getDirectChildMatching(parent, selector) {
            if (!(parent instanceof HTMLElement)) {
                return null;
            }
            return Array.from(parent.children).find(child => child instanceof HTMLElement && child.matches(selector)) || null;
        }

        function relocateMainTaxonomyActionButtonsToFooter() {
            if (typeof window.materialCalcCreateMainTaxonomyFooterEngine === 'function') {
                try {
                    return window.materialCalcCreateMainTaxonomyFooterEngine({
                        deps: {
                            getMainAreaChildrenHost,
                            getDirectChildMatching,
                            getDirectAdditionalChildRows,
                            normalizeBundleRowKind,
                            getAdditionalFieldValue,
                        },
                    });
                } catch (error) {
                    console.error('main taxonomy footer engine failed:', error);
                }
            }

            const inputFormContainer = document.getElementById('inputFormContainer');
            if (!(inputFormContainer instanceof HTMLElement)) {
                return null;
            }

            const cardHost =
                document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card') instanceof HTMLElement
                    ? document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card')
                    : null;
            const host =
                cardHost instanceof HTMLElement
                    ? cardHost
                    : (inputFormContainer.parentElement instanceof HTMLElement ? inputFormContainer.parentElement : null);
            if (!(host instanceof HTMLElement)) {
                return null;
            }
            const mainAreaHost = getMainAreaChildrenHost();
            const areaGroup =
                host.querySelector('.work-area-group.taxonomy-inline-group') instanceof HTMLElement
                    ? host.querySelector('.work-area-group.taxonomy-inline-group')
                    : null;
            const fieldGroup =
                host.querySelector('.work-field-group.taxonomy-inline-group') instanceof HTMLElement
                    ? host.querySelector('.work-field-group.taxonomy-inline-group')
                    : null;

            const ensureActionsHost = groupEl => {
                if (!(groupEl instanceof HTMLElement)) {
                    return null;
                }
                let actionsEl = getDirectChildMatching(groupEl, '.taxonomy-level-actions');
                if (!(actionsEl instanceof HTMLElement)) {
                    actionsEl = document.createElement('div');
                    actionsEl.className = 'taxonomy-level-actions';
                    groupEl.appendChild(actionsEl);
                }
                return actionsEl;
            };

            const areaActionsHost = ensureActionsHost(areaGroup);
            const fieldActionsHost = ensureActionsHost(fieldGroup);

            const addAreaBtn = document.getElementById('addAreaFromMainBtn');
            if (addAreaBtn instanceof HTMLElement && areaActionsHost instanceof HTMLElement) {
                if (addAreaBtn.parentElement !== areaActionsHost) {
                    areaActionsHost.appendChild(addAreaBtn);
                }
            }

            const addFieldBtn = document.getElementById('addFieldFromMainBtn');
            if (addFieldBtn instanceof HTMLElement && fieldActionsHost instanceof HTMLElement) {
                if (addFieldBtn.parentElement !== fieldActionsHost) {
                    fieldActionsHost.appendChild(addFieldBtn);
                }
                const toggleMainBtn = fieldActionsHost.querySelector('#toggleMainFieldItemVisibilityBtn');
                if (toggleMainBtn instanceof HTMLElement && addFieldBtn !== toggleMainBtn.previousElementSibling) {
                    fieldActionsHost.insertBefore(addFieldBtn, toggleMainBtn);
                }
            }

            let footer =
                getDirectChildMatching(host, '.main-taxonomy-actions-row') ||
                getDirectChildMatching(mainAreaHost, '.main-taxonomy-actions-row');
            if (!(footer instanceof HTMLElement)) {
                footer = document.createElement('div');
                footer.className = 'main-taxonomy-actions-row';
                host.appendChild(footer);
            }

            const addItemBtn = document.getElementById('addItemFromMainBtn');
            if (addItemBtn instanceof HTMLElement && footer instanceof HTMLElement) {
                if (addItemBtn.parentElement !== footer) {
                    footer.appendChild(addItemBtn);
                }
            }

            if (mainAreaHost instanceof HTMLElement) {
                const mainAreaRows = getDirectAdditionalChildRows(mainAreaHost);
                const firstNonItemRow =
                    mainAreaRows.find(row => {
                        const childKind = normalizeBundleRowKind(
                            getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                        );
                        return childKind !== 'item';
                    }) || null;

                if (firstNonItemRow instanceof HTMLElement) {
                    mainAreaHost.insertBefore(footer, firstNonItemRow);
                } else if (footer.parentElement !== mainAreaHost || mainAreaHost.lastElementChild !== footer) {
                    mainAreaHost.appendChild(footer);
                }
            } else if (footer.parentElement !== host || host.lastElementChild !== footer) {
                host.appendChild(footer);
            }

            [host, mainAreaHost].forEach(container => {
                if (!(container instanceof HTMLElement)) {
                    return;
                }
                Array.from(container.children).forEach(child => {
                    if (
                        child instanceof HTMLElement &&
                        child !== footer &&
                        child.matches('.main-taxonomy-actions-row') &&
                        child.childElementCount === 0
                    ) {
                        child.remove();
                    }
                });
            });

            return footer;
        }

        function ensureAdditionalTaxonomyActionsFooter(itemEl) {
            if (typeof window.materialCalcCreateAdditionalTaxonomyFooterEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalTaxonomyFooterEngine({
                        deps: {
                            getDirectChildMatching,
                            normalizeBundleRowKind,
                            getAdditionalFieldValue,
                            getDirectAdditionalChildRows,
                        },
                        itemEl,
                    });
                } catch (error) {
                    console.error('additional taxonomy footer engine failed:', error);
                }
            }

            if (!(itemEl instanceof HTMLElement)) {
                return null;
            }

            const grid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            if (!(grid instanceof HTMLElement)) {
                return null;
            }

            const areaHost = getDirectChildMatching(grid, '[data-area-children]');
            const floorHost = getDirectChildMatching(grid, '[data-floor-children]');
            const rowKind = normalizeBundleRowKind(
                itemEl.getAttribute('data-row-kind') || getAdditionalFieldValue(itemEl, 'row_kind') || 'area',
            );

            let footer =
                getDirectChildMatching(grid, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(areaHost, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(floorHost, '.additional-taxonomy-actions-row');
            if (!(footer instanceof HTMLElement)) {
                footer = document.createElement('div');
                footer.className = 'additional-taxonomy-actions-row';
            }

            const resolveCell = cellKey => {
                const cell = itemEl.querySelector(`.additional-taxonomy-cell[data-taxonomy-cell="${cellKey}"]`);
                return cell instanceof HTMLElement ? cell : null;
            };
            const areaCell = resolveCell('area');
            const fieldCell = resolveCell('field');
            const fieldCellBody =
                fieldCell instanceof HTMLElement
                    ? fieldCell.querySelector('.additional-taxonomy-cell-body')
                    : null;

            const ensureInlineActionsHost = cellEl => {
                if (!(cellEl instanceof HTMLElement)) {
                    return null;
                }
                let hostEl = getDirectChildMatching(cellEl, '.additional-taxonomy-inline-actions');
                if (!(hostEl instanceof HTMLElement)) {
                    hostEl = document.createElement('div');
                    hostEl.className = 'taxonomy-level-actions additional-taxonomy-inline-actions';
                    cellEl.appendChild(hostEl);
                }
                return hostEl;
            };
            const areaInlineActionsHost = ensureInlineActionsHost(areaCell);

            const moveButtonToHost = (action, hostEl) => {
                const btn = itemEl.querySelector(`[data-action="${action}"]`);
                if (!(btn instanceof HTMLElement)) {
                    return null;
                }
                if (hostEl instanceof HTMLElement) {
                    if (btn.parentElement !== hostEl) {
                        hostEl.appendChild(btn);
                    }
                } else if (btn.parentElement !== footer) {
                    footer.appendChild(btn);
                }
                return btn;
            };

            const addAreaBtn = moveButtonToHost('add-area', areaInlineActionsHost);
            const addFieldBtn = moveButtonToHost('add-field', fieldCellBody);
            if (addFieldBtn instanceof HTMLElement && fieldCellBody instanceof HTMLElement) {
                const toggleBtn = fieldCellBody.querySelector('[data-action="toggle-item-visibility"]');
                if (toggleBtn instanceof HTMLElement && addFieldBtn !== toggleBtn.previousElementSibling) {
                    fieldCellBody.insertBefore(addFieldBtn, toggleBtn);
                }
            }
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            if (addItemBtn instanceof HTMLElement && addItemBtn.parentElement !== footer) {
                footer.appendChild(addItemBtn);
            }

            [addAreaBtn, addFieldBtn].forEach(btn => {
                if (!(btn instanceof HTMLElement)) {
                    return;
                }
                btn.classList.add('is-inline-taxonomy-action');
            });
            if (addFieldBtn instanceof HTMLElement) {
                addFieldBtn.classList.add('is-inline-add-field');
            }
            if (addItemBtn instanceof HTMLElement) {
                addItemBtn.classList.remove('is-inline-taxonomy-action');
            }

            [areaCell].forEach(cell => {
                if (!(cell instanceof HTMLElement)) {
                    return;
                }
                cell.classList.toggle(
                    'has-inline-action',
                    !!cell.querySelector('.additional-taxonomy-inline-actions .taxonomy-level-btn'),
                );
            });
            if (fieldCellBody instanceof HTMLElement) {
                fieldCellBody.classList.toggle(
                    'has-inline-add-field',
                    !!fieldCellBody.querySelector('[data-action="add-field"]'),
                );
            }

            // Item rows hide all taxonomy action buttons. Keep the footer on the row grid
            // (not inside child hosts) so nested hosts stay truly empty and collapse spacing.
            if (rowKind === 'item') {
                if (footer.parentElement !== grid) {
                    grid.appendChild(footer);
                }
                return footer;
            }

            if (areaHost instanceof HTMLElement) {
                const childRows = getDirectAdditionalChildRows(areaHost);
                const firstNonItemRow =
                    childRows.find(row => {
                        const childKind = normalizeBundleRowKind(
                            getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                        );
                        return childKind !== 'item';
                    }) || null;

                if (firstNonItemRow instanceof HTMLElement) {
                    areaHost.insertBefore(footer, firstNonItemRow);
                } else if (footer.parentElement !== areaHost || areaHost.lastElementChild !== footer) {
                    areaHost.appendChild(footer);
                }
            } else if (footer.parentElement !== grid || grid.lastElementChild !== footer) {
                grid.appendChild(footer);
            }

            return footer;
        }

        function getDirectAdditionalRowHost(rowEl, hostSelector) {
            if (typeof window.materialCalcCreateInlineLayoutEngine === 'function') {
                try {
                    return window.materialCalcCreateInlineLayoutEngine({
                        deps: {
                            getDirectChildMatching,
                        },
                        methodName: 'getDirectAdditionalRowHost',
                        args: [rowEl, hostSelector],
                    });
                } catch (error) {
                    console.error('inline layout engine (get row host) failed:', error);
                }
            }

            if (!(rowEl instanceof HTMLElement)) {
                return null;
            }
            const grid = getDirectChildMatching(rowEl, '.additional-work-item-grid');
            if (!(grid instanceof HTMLElement)) {
                return null;
            }
            return getDirectChildMatching(grid, hostSelector);
        }

        function getAdditionalRowLayoutParts(itemEl) {
            if (typeof window.materialCalcCreateInlineLayoutEngine === 'function') {
                try {
                    return window.materialCalcCreateInlineLayoutEngine({
                        deps: {
                            getDirectChildMatching,
                        },
                        methodName: 'getAdditionalRowLayoutParts',
                        args: [itemEl],
                    });
                } catch (error) {
                    console.error('inline layout engine (get parts) failed:', error);
                }
            }

            const grid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            const floorNode = getDirectChildMatching(grid, '.taxonomy-node-floor');
            const floorCard = getDirectChildMatching(floorNode, '.additional-work-floor-group');
            const floorChildren = getDirectChildMatching(floorNode, '.taxonomy-node-children');
            const areaNode = getDirectChildMatching(floorChildren, '.taxonomy-node-area');
            const areaCard = getDirectChildMatching(areaNode, '.additional-work-area-group');
            const areaChildren = getDirectChildMatching(areaNode, '.taxonomy-node-children');
            const fieldNode = getDirectChildMatching(areaChildren, '.taxonomy-node-field');
            const fieldCard = getDirectChildMatching(fieldNode, '.additional-work-field-group');
            const fieldChildren = getDirectChildMatching(fieldNode, '.taxonomy-node-children');
            const itemNode = getDirectChildMatching(fieldChildren, '.taxonomy-node-item');
            const itemGroup = getDirectChildMatching(itemNode, '.additional-worktype-group');
            const itemInputWrapper = getDirectChildMatching(itemGroup, '.input-wrapper');
            const topFloorChildren = getDirectChildMatching(grid, '[data-floor-children]');
            const topAreaChildren = getDirectChildMatching(grid, '[data-area-children]');
            return {
                grid,
                floorNode,
                floorCard,
                floorChildren,
                areaNode,
                areaCard,
                areaChildren,
                fieldNode,
                fieldCard,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            };
        }

        function applyAdditionalInlineTaxonomyRowLayout(itemEl, mode = 'none') {
            if (typeof window.materialCalcCreateInlineLayoutEngine === 'function') {
                try {
                    return window.materialCalcCreateInlineLayoutEngine({
                        deps: {
                            getDirectChildMatching,
                            clearInlineStyles,
                            setInlineStylesImportant,
                        },
                        methodName: 'applyAdditionalInlineTaxonomyRowLayout',
                        args: [itemEl, mode],
                    });
                } catch (error) {
                    console.error('inline layout engine (apply) failed:', error);
                }
            }

            const parts = getAdditionalRowLayoutParts(itemEl);
            if (!parts.grid) {
                return;
            }

            const {
                grid,
                floorNode,
                floorCard,
                floorChildren,
                areaNode,
                areaCard,
                areaChildren,
                fieldNode,
                fieldCard,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            } = parts;

            const resetElements = [
                grid,
                floorNode,
                floorChildren,
                areaNode,
                areaChildren,
                fieldNode,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            ];
            resetElements.forEach(el =>
                clearInlineStyles(el, [
                    'display',
                    'flex-direction',
                    'grid-template-columns',
                    'align-items',
                    'gap',
                    'grid-column',
                    'width',
                    'min-width',
                    'margin-left',
                    'padding-left',
                    'border-left',
                    'max-width',
                ]),
            );

            const cardElements = [floorCard, areaCard, fieldCard];
            cardElements.forEach(card => {
                clearInlineStyles(card, [
                    'display',
                    'grid-template-columns',
                    'align-items',
                    'gap',
                    'grid-column',
                    'width',
                    'min-width',
                    'margin-bottom',
                    'visibility',
                    'pointer-events',
                ]);
                const label = getDirectChildMatching(card, 'label');
                const body = getDirectChildMatching(card, '.material-type-filter-body');
                const actions = getDirectChildMatching(card, '.taxonomy-level-actions');
                clearInlineStyles(label, ['grid-column', 'width', 'margin-bottom']);
                clearInlineStyles(body, ['grid-column', 'width', 'min-width']);
                clearInlineStyles(actions, ['grid-column', 'margin', 'align-self']);
            });

            if (mode === 'none') {
                // Force normal stacked layout (important) so nested item rows do not inherit inline grid effects.
                setInlineStylesImportant(grid, {
                    display: 'flex',
                    'flex-direction': 'column',
                    gap: '0',
                    width: '100%',
                });
                [floorNode, floorChildren, areaNode, areaChildren, fieldNode, fieldChildren, topFloorChildren, topAreaChildren].forEach(
                    el => setInlineStylesImportant(el, { display: 'block', width: '100%', 'min-width': '0' }),
                );
                return;
            }

            // Keep item rows and nested children full-width unless this row itself is inline taxonomy row.
            setInlineStylesImportant(grid, {
                display: 'flex',
                'flex-direction': 'column',
                gap: '0',
                width: '100%',
            });

            setInlineStylesImportant(floorNode, {
                display: 'grid',
                'grid-template-columns': 'repeat(3, minmax(0, 1fr))',
                'align-items': 'start',
                gap: '8px 10px',
                width: '100%',
            });

            [floorChildren, areaNode, areaChildren, fieldNode].forEach(el => {
                setInlineStylesImportant(el, { display: 'contents' });
            });

            setInlineStylesImportant(floorCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '1',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });
            setInlineStylesImportant(areaCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '2',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });
            setInlineStylesImportant(fieldCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '3',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });

            [floorCard, areaCard, fieldCard].forEach(card => {
                const label = getDirectChildMatching(card, 'label');
                const body = getDirectChildMatching(card, '.material-type-filter-body');
                const actions = getDirectChildMatching(card, '.taxonomy-level-actions');
                setInlineStylesImportant(label, {
                    'grid-column': '1 / -1',
                    width: 'auto',
                    'margin-bottom': '0',
                });
                setInlineStylesImportant(body, {
                    'grid-column': '1',
                    width: '100%',
                    'min-width': '0',
                });
                setInlineStylesImportant(actions, {
                    'grid-column': '2',
                    margin: '0',
                    'align-self': 'end',
                });
            });

            [fieldChildren, topFloorChildren, topAreaChildren].forEach(el => {
                setInlineStylesImportant(el, {
                    display: 'block',
                    'grid-column': '1 / -1',
                    width: '100%',
                    'min-width': '0',
                    'margin-left': '0',
                    'padding-left': '0',
                    'border-left': '0',
                });
            });

            // Prevent the built-in item slot from being auto-placed into a grid column in inline rows.
            setInlineStylesImportant(itemNode, {
                display: 'block',
                'grid-column': '1 / -1',
                width: '100%',
                'min-width': '0',
            });
            setInlineStylesImportant(itemGroup, {
                display: 'flex',
                'align-items': 'center',
                width: '100%',
                'min-width': '0',
                'max-width': '100%',
            });
            setInlineStylesImportant(itemInputWrapper, {
                display: 'block',
                width: '100%',
                'min-width': '0',
                'max-width': '100%',
            });

            const floorIsPlaceholder = floorCard instanceof HTMLElement && floorCard.classList.contains('is-inline-placeholder');
            const areaIsPlaceholder = areaCard instanceof HTMLElement && areaCard.classList.contains('is-inline-placeholder');
            if (floorIsPlaceholder) {
                setInlineStylesImportant(floorCard, { visibility: 'hidden', 'pointer-events': 'none' });
            }
            if (areaIsPlaceholder) {
                setInlineStylesImportant(areaCard, { visibility: 'hidden', 'pointer-events': 'none' });
            }
        }

        function setAdditionalWorkItemRowKind(itemEl, rowKind = 'area') {
            if (typeof window.materialCalcCreateAdditionalRowKindEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalRowKindEngine({
                        deps: {
                            normalizeBundleRowKind,
                            ensureAdditionalTaxonomyActionsFooter,
                            getDirectChildMatching,
                            setAdditionalItemContentCollapsed,
                            syncDirectChildItemRowVisibilityForCollapsedParent,
                            getAdditionalFieldValue,
                        },
                        itemEl,
                        rowKind,
                    });
                } catch (error) {
                    console.error('additional row kind engine failed:', error);
                }
            }

            if (!(itemEl instanceof HTMLElement)) {
                return;
            }

            const normalizedKind = normalizeBundleRowKind(rowKind);
            itemEl.setAttribute('data-row-kind', normalizedKind);

            // Remove legacy layout classes no longer used
            itemEl.classList.remove('taxonomy-tree-main', 'taxonomy-group-card', 'additional-area-inline-row', 'additional-field-inline-row');

            const rowKindInput = itemEl.querySelector('[data-field="row_kind"]');
            if (rowKindInput) {
                rowKindInput.value = normalizedKind;
            }

            const parentEl = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
            const isNestedAreaRow =
                normalizedKind === 'area' && !!parentEl && parentEl.matches('[data-main-area-children], [data-floor-children]');

            // Top-level floor rows get card styling; nested items are flat
            const isFloorGroup = normalizedKind === 'area' && !isNestedAreaRow;
            itemEl.classList.toggle('is-floor-group', isFloorGroup);

            // Determine which taxonomy cells are inherited (hidden) from parent context
            const floorInherited = isNestedAreaRow || normalizedKind === 'field' || normalizedKind === 'item';
            const areaInherited = normalizedKind === 'field' || normalizedKind === 'item';
            const fieldInherited = normalizedKind === 'item';

            const applyCell = (selector, inherited) => {
                const cell = itemEl.querySelector(selector);
                if (!(cell instanceof HTMLElement)) {
                    return;
                }
                cell.classList.toggle('is-inherited', inherited);
            };

            applyCell('[data-taxonomy-cell="floor"]', floorInherited);
            applyCell('[data-taxonomy-cell="area"]', areaInherited);
            applyCell('[data-taxonomy-cell="field"]', fieldInherited);

            ensureAdditionalTaxonomyActionsFooter(itemEl);

            // Hide the entire taxonomy header for item rows (all cells are inherited)
            const taxonomyHeader = itemEl.querySelector('.additional-taxonomy-header');
            if (taxonomyHeader) {
                taxonomyHeader.style.display = normalizedKind === 'item' ? 'none' : '';
            }

            // Show/hide action buttons based on row_kind
            const addAreaBtn = itemEl.querySelector('[data-action="add-area"]');
            const addFieldBtn = itemEl.querySelector('[data-action="add-field"]');
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            const rowGrid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            const rowAreaHost = getDirectChildMatching(rowGrid, '[data-area-children]');
            const rowFloorHost = getDirectChildMatching(rowGrid, '[data-floor-children]');
            const actionsRow =
                getDirectChildMatching(rowGrid, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(rowAreaHost, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(rowFloorHost, '.additional-taxonomy-actions-row');
            const isItemRow = normalizedKind === 'item';
            const showAddArea = !isItemRow && !floorInherited;
            const showAddField = !isItemRow && !areaInherited;
            const showAddItem = !isItemRow && !fieldInherited;

            if (addAreaBtn) {
                addAreaBtn.style.display = showAddArea ? '' : 'none';
            }
            if (addFieldBtn) {
                addFieldBtn.style.display = showAddField ? '' : 'none';
            }
            if (addItemBtn) {
                addItemBtn.style.display = showAddItem ? '' : 'none';
            }
            if (actionsRow) {
                actionsRow.style.display = showAddArea || showAddField || showAddItem ? '' : 'none';
            }

            // Item rows don't have visible taxonomy cards, so ensure item content stays visible.
            if (normalizedKind === 'item') {
                setAdditionalItemContentCollapsed(itemEl, false);
            } else {
                const wantsCollapsed = itemEl.dataset.itemContentCollapsed === '1';
                setAdditionalItemContentCollapsed(itemEl, wantsCollapsed);
            }

            const parentRow =
                itemEl.parentElement instanceof HTMLElement
                    ? itemEl.parentElement.closest('.additional-work-item[data-additional-work-item="true"]')
                    : null;
            if (parentRow instanceof HTMLElement) {
                syncDirectChildItemRowVisibilityForCollapsedParent(parentRow);
            }
        }

        function createAndFocusAdditionalWorkItem(initial = {}, afterElement = null, focusField = 'work_type', options = {}) {
            if (typeof window.materialCalcCreateAndFocusAdditionalWorkItemEngine === 'function') {
                try {
                    return window.materialCalcCreateAndFocusAdditionalWorkItemEngine({
                        deps: {
                            createAdditionalWorkItemForm,
                        },
                        initial,
                        afterElement,
                        focusField,
                        options,
                    });
                } catch (error) {
                    console.error('additional item focus engine failed:', error);
                }
            }

            const newForm = createAdditionalWorkItemForm(initial, afterElement, options);
            if (!newForm) {
                return null;
            }

            const openAutocompleteListForInput = inputEl => {
                if (!(inputEl instanceof HTMLElement)) {
                    return;
                }
                if (typeof inputEl.__openAdditionalTaxonomyList === 'function') {
                    inputEl.__openAdditionalTaxonomyList();
                    return;
                }
                if (typeof inputEl.__openAdditionalWorkTypeList === 'function') {
                    inputEl.__openAdditionalWorkTypeList();
                }
            };
            const scheduleFocusAndOpenAutocompleteInput = inputEl => {
                if (!(inputEl instanceof HTMLElement)) {
                    return;
                }
                setTimeout(() => {
                    if (!inputEl.isConnected) {
                        return;
                    }
                    inputEl.focus();
                    openAutocompleteListForInput(inputEl);
                }, 0);
            };

            const selectorMap = {
                work_floor: '[data-field-display="work_floor"]',
                work_area: '[data-field-display="work_area"]',
                work_field: '[data-field-display="work_field"]',
                work_type: '[data-field-display="work_type"]',
            };
            const focusSelector = selectorMap[focusField] || selectorMap.work_type;
            const focusInput = newForm.querySelector(focusSelector);
            if (focusInput) {
                scheduleFocusAndOpenAutocompleteInput(focusInput);
            }
            return newForm;
        }

        function showTaxonomyActionError(message, focusEl = null) {
            if (typeof window.materialCalcShowTaxonomyActionErrorEngine === 'function') {
                try {
                    return window.materialCalcShowTaxonomyActionErrorEngine({
                        message,
                        focusEl,
                    });
                } catch (error) {
                    console.error('taxonomy action error engine failed:', error);
                }
            }

            if (typeof window.showToast === 'function') {
                window.showToast(message, 'error');
            } else {
                alert(message);
            }
            if (focusEl && typeof focusEl.focus === 'function') {
                setTimeout(() => {
                    if (!(focusEl instanceof HTMLElement) || !focusEl.isConnected) {
                        return;
                    }
                    focusEl.focus();
                    if (typeof focusEl.__openAdditionalTaxonomyList === 'function') {
                        focusEl.__openAdditionalTaxonomyList();
                    } else if (typeof focusEl.__openAdditionalWorkTypeList === 'function') {
                        focusEl.__openAdditionalWorkTypeList();
                    }
                }, 0);
            }
        }

        function markFloorSortPending() {
            if (typeof window.materialCalcMarkFloorSortPendingEngine === 'function') {
                try {
                    return window.materialCalcMarkFloorSortPendingEngine({
                        deps: {
                            getIsRebuildingFloorCardOrder: () => isRebuildingFloorCardOrder,
                            setHasPendingFloorSort: value => {
                                hasPendingFloorSort = Boolean(value);
                            },
                        },
                    });
                } catch (error) {
                    console.error('floor sort queue engine (mark) failed:', error);
                }
            }

            if (isRebuildingFloorCardOrder) {
                return;
            }
            hasPendingFloorSort = true;
        }

        function flushPendingFloorSort() {
            if (typeof window.materialCalcFlushPendingFloorSortEngine === 'function') {
                try {
                    return window.materialCalcFlushPendingFloorSortEngine({
                        deps: {
                            getIsRebuildingFloorCardOrder: () => isRebuildingFloorCardOrder,
                            getHasPendingFloorSort: () => hasPendingFloorSort,
                            setHasPendingFloorSort: value => {
                                hasPendingFloorSort = Boolean(value);
                            },
                            sortAdditionalWorkItems,
                        },
                    });
                } catch (error) {
                    console.error('floor sort queue engine (flush) failed:', error);
                }
            }

            if (isRebuildingFloorCardOrder || !hasPendingFloorSort) {
                return;
            }
            hasPendingFloorSort = false;
            sortAdditionalWorkItems();
        }

        function flushFloorSortWhenFocusLeaves(scopeEl) {
            if (typeof window.materialCalcFlushFloorSortWhenFocusLeavesEngine === 'function') {
                try {
                    return window.materialCalcFlushFloorSortWhenFocusLeavesEngine({
                        deps: {
                            getHasPendingFloorSort: () => hasPendingFloorSort,
                            getLastPointerDownTarget: () => lastPointerDownTarget,
                            getLastPointerDownAt: () => lastPointerDownAt,
                            flushPendingFloorSort,
                        },
                        scopeEl,
                    });
                } catch (error) {
                    console.error('floor sort queue engine (focus-leave) failed:', error);
                }
            }

            if (!(scopeEl instanceof HTMLElement) || !hasPendingFloorSort) {
                return;
            }
            setTimeout(() => {
                const recentlyPointerDownInsideScope =
                    lastPointerDownTarget instanceof HTMLElement &&
                    scopeEl.contains(lastPointerDownTarget) &&
                    Date.now() - lastPointerDownAt < 500;
                if (recentlyPointerDownInsideScope) {
                    return;
                }
                const activeEl = document.activeElement;
                if (activeEl instanceof HTMLElement && scopeEl.contains(activeEl)) {
                    return;
                }
                flushPendingFloorSort();
            }, 0);
        }

        function sortMainFloorCards() {
            if (typeof window.materialCalcCreateMainFloorCardSortEngine === 'function') {
                try {
                    return window.materialCalcCreateMainFloorCardSortEngine({
                        deps: {
                            mainTaxonomyGroupCard,
                            getDirectAdditionalChildRows,
                            normalizeBundleRowKind,
                            getAdditionalFieldValue,
                            sortFloors,
                        },
                    });
                } catch (error) {
                    console.error('main floor card sort engine failed:', error);
                }
            }

            const mainAreaHost =
                mainTaxonomyGroupCard instanceof HTMLElement
                    ? mainTaxonomyGroupCard.querySelector('[data-main-area-children]')
                    : null;
            if (!(mainAreaHost instanceof HTMLElement)) {
                return false;
            }

            const directRows = getDirectAdditionalChildRows(mainAreaHost);
            if (directRows.length <= 1) {
                return false;
            }

            const getRowKind = row =>
                normalizeBundleRowKind(getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area');
            const floorRows = directRows.filter(row => getRowKind(row) === 'area');
            if (floorRows.length <= 1) {
                return false;
            }

            const floorValues = floorRows.map(row => getAdditionalFieldValue(row, 'work_floor'));
            const sortedFloorValues = sortFloors([...floorValues]);
            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const originalIndex = new Map(floorRows.map((row, index) => [row, index]));
            const sortedFloorRows = [...floorRows].sort((a, b) => {
                const floorA = getAdditionalFieldValue(a, 'work_floor');
                const floorB = getAdditionalFieldValue(b, 'work_floor');
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                if (priorityA !== priorityB) {
                    return priorityA - priorityB;
                }
                return (originalIndex.get(a) ?? 0) - (originalIndex.get(b) ?? 0);
            });

            let floorIndex = 0;
            const nextRows = directRows.map(row => (getRowKind(row) === 'area' ? sortedFloorRows[floorIndex++] : row));
            const alreadySorted = directRows.every((row, index) => row === nextRows[index]);
            if (alreadySorted) {
                return false;
            }

            nextRows.forEach(row => mainAreaHost.appendChild(row));
            return true;
        }

        function sortBundleItemsByFloorStable(items) {
            if (typeof window.materialCalcCreateBundleFloorSortEngine === 'function') {
                try {
                    return window.materialCalcCreateBundleFloorSortEngine({
                        deps: {
                            sortFloors,
                        },
                        items,
                    });
                } catch (error) {
                    console.error('bundle floor sort engine failed:', error);
                }
            }

            const list = Array.isArray(items) ? [...items] : [];
            if (list.length <= 1) {
                return list;
            }

            const floorValues = list.map(item => String(item?.work_floor || '').trim());
            const sortedFloorValues = sortFloors([...floorValues]);
            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const originalIndex = new Map(list.map((item, index) => [item, index]));
            return list.sort((a, b) => {
                const floorA = String(a?.work_floor || '').trim();
                const floorB = String(b?.work_floor || '').trim();
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                if (priorityA !== priorityB) {
                    return priorityA - priorityB;
                }
                return (originalIndex.get(a) ?? 0) - (originalIndex.get(b) ?? 0);
            });
        }

        function rebuildBundleUiFromSortedFloorOrder() {
            if (typeof window.materialCalcCreateRebuildFloorOrderEngine === 'function') {
                try {
                    return window.materialCalcCreateRebuildFloorOrderEngine({
                        deps: {
                            getIsRebuildingFloorCardOrder: () => isRebuildingFloorCardOrder,
                            setIsRebuildingFloorCardOrder: value => {
                                isRebuildingFloorCardOrder = Boolean(value);
                            },
                            getTopLevelAdditionalRows,
                            collectMainWorkItemDraft,
                            collectAdditionalWorkItemData,
                            sortBundleItemsByFloorStable,
                            getMainAreaChildrenHost,
                            getDirectAdditionalRowHost,
                            swapDirectAdditionalChildRows,
                            setAdditionalFloorValueForRowsInScope,
                            applyMainWorkItemFromBundleItem,
                            applyAdditionalWorkItemFromBundleItem,
                            syncBundleFromForms,
                            relocateFilterSectionToRightGrid,
                            relocateMainTaxonomyActionButtonsToFooter,
                            refreshAdditionalTaxonomyActionFooters,
                        },
                    });
                } catch (error) {
                    console.error('rebuild floor order engine failed:', error);
                }
            }

            if (isRebuildingFloorCardOrder) {
                return false;
            }

            const topLevelRows = getTopLevelAdditionalRows();
            if (topLevelRows.length === 0) {
                return false;
            }

            const mainDraft = collectMainWorkItemDraft();
            const entries = [
                { source: 'main', row: null, data: mainDraft },
                ...topLevelRows
                    .map((row, index) => ({
                        source: 'additional',
                        row,
                        data: collectAdditionalWorkItemData(row, index + 1),
                    }))
                    .filter(entry => entry.data),
            ];
            if (entries.length <= 1) {
                return false;
            }

            const sortedData = sortBundleItemsByFloorStable(entries.map(entry => entry.data));
            const nextMainData = sortedData[0] || null;
            if (!nextMainData || nextMainData === mainDraft) {
                return false;
            }

            const candidateEntry = entries.find(entry => entry.source === 'additional' && entry.data === nextMainData);
            if (!candidateEntry || !(candidateEntry.row instanceof HTMLElement)) {
                return false;
            }

            const candidateRow = candidateEntry.row;
            const candidateData = candidateEntry.data;
            const oldMainFloor = String(mainDraft.work_floor || '').trim();
            const nextMainFloor = String(candidateData.work_floor || '').trim();

            isRebuildingFloorCardOrder = true;
            try {
                const mainAreaHost = getMainAreaChildrenHost();
                const candidateFloorHost = getDirectAdditionalRowHost(candidateRow, '[data-floor-children]');

                if (mainAreaHost instanceof HTMLElement && candidateFloorHost instanceof HTMLElement) {
                    // Swap the nested rows too, so the whole floor card (including its children) moves together.
                    swapDirectAdditionalChildRows(mainAreaHost, candidateFloorHost);
                }

                if (mainAreaHost instanceof HTMLElement && oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(mainAreaHost, nextMainFloor);
                }
                if (candidateFloorHost instanceof HTMLElement && oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(candidateFloorHost, oldMainFloor);
                } else if (oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(candidateRow, oldMainFloor, { excludeRoot: true });
                }

                applyMainWorkItemFromBundleItem(candidateData);
                applyAdditionalWorkItemFromBundleItem(candidateRow, mainDraft);
                syncBundleFromForms();
                relocateFilterSectionToRightGrid();
                relocateMainTaxonomyActionButtonsToFooter();
                refreshAdditionalTaxonomyActionFooters();
            } finally {
                isRebuildingFloorCardOrder = false;
            }

            return true;
        }

        function sortAdditionalWorkItems() {
            if (typeof window.materialCalcCreateSortAdditionalItemsEngine === 'function') {
                try {
                    return window.materialCalcCreateSortAdditionalItemsEngine({
                        deps: {
                            getIsRebuildingFloorCardOrder: () => isRebuildingFloorCardOrder,
                            setHasPendingFloorSort: value => {
                                hasPendingFloorSort = Boolean(value);
                            },
                            rebuildBundleUiFromSortedFloorOrder,
                            sortMainFloorCards,
                            additionalWorkItemsList,
                            getTopLevelAdditionalRows,
                            getAdditionalFieldValue,
                            sortFloors,
                            refreshAdditionalWorkItemHeader,
                        },
                    });
                } catch (error) {
                    console.error('sort additional items engine failed:', error);
                }
            }

            if (isRebuildingFloorCardOrder) {
                return;
            }
            hasPendingFloorSort = false;
            const mainCardSwapped = rebuildBundleUiFromSortedFloorOrder();
            const mainFloorCardsSorted = sortMainFloorCards();
            if (!additionalWorkItemsList) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }
            const items = getTopLevelAdditionalRows();
            if (items.length <= 1) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }

            const floorValues = items.map(item => getAdditionalFieldValue(item, 'work_floor'));
            const sortedFloorValues = sortFloors([...floorValues]);

            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const sortedItems = [...items].sort((a, b) => {
                const floorA = getAdditionalFieldValue(a, 'work_floor');
                const floorB = getAdditionalFieldValue(b, 'work_floor');
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                return priorityA - priorityB;
            });

            const alreadySorted = items.every((item, i) => item === sortedItems[i]);
            if (alreadySorted) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }

            sortedItems.forEach(item => additionalWorkItemsList.appendChild(item));
            refreshAdditionalWorkItemHeader();
        }

        function refreshAdditionalWorkItemHeader() {
            if (typeof window.materialCalcCreateAdditionalItemHeaderEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalItemHeaderEngine({
                        deps: {
                            additionalWorkItemsList,
                            getAllAdditionalWorkRows,
                            mainWorkTypeLabel,
                            normalizeBundleRowKind,
                            getAdditionalFieldValue,
                        },
                    });
                } catch (error) {
                    console.error('additional item header engine failed:', error);
                }
            }

            if (!additionalWorkItemsList) {
                return;
            }
            const items = getAllAdditionalWorkRows();
            const hasAdditionalItems = items.length > 0;

            if (mainWorkTypeLabel) {
                mainWorkTypeLabel.textContent = hasAdditionalItems ? 'Item Pekerjaan 1' : 'Item Pekerjaan';
            }

            const setAdditionalItemLabel = (itemEl, globalNumber) => {
                if (!(itemEl instanceof HTMLElement)) {
                    return;
                }
                const label = itemEl.querySelector('[data-additional-worktype-label]');
                if (label) {
                    label.textContent = `Item Pekerjaan ${globalNumber}`;
                }

                const rowKind = normalizeBundleRowKind(
                    getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                );
                const parentEl = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
                const siblingRows = parentEl
                    ? Array.from(parentEl.children).filter(row =>
                          row instanceof HTMLElement && row.matches('[data-additional-work-item="true"]'),
                      )
                    : [];
                const itemIndexInParent = siblingRows.indexOf(itemEl);
                const shouldShowFieldBreak = rowKind === 'field' && itemIndexInParent > 0;
                itemEl.classList.toggle('field-break', shouldShowFieldBreak);
            };

            let nextGlobalItemNumber = 2;
            items.forEach(itemEl => {
                setAdditionalItemLabel(itemEl, nextGlobalItemNumber);
                nextGlobalItemNumber += 1;
            });
        }

        function getAdditionalFieldValue(itemEl, key) {
            if (typeof window.materialCalcCreateAdditionalFieldEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalFieldEngine({
                        methodName: 'getAdditionalFieldValue',
                        args: [itemEl, key],
                    });
                } catch (error) {
                    console.error('additional field engine (get) failed:', error);
                }
            }

            const el = itemEl.querySelector(`[data-field="${key}"]`);
            const hiddenValue = el ? String(el.value || '').trim() : '';
            if (hiddenValue) {
                return hiddenValue;
            }

            if (key === 'work_floor' || key === 'work_area' || key === 'work_field' || key === 'work_type') {
                const displayEl = itemEl.querySelector(`[data-field-display="${key}"]`);
                return displayEl ? String(displayEl.value || '').trim() : '';
            }

            return '';
        }

        function setAdditionalFieldValue(itemEl, key, value) {
            if (typeof window.materialCalcCreateAdditionalFieldEngine === 'function') {
                try {
                    return window.materialCalcCreateAdditionalFieldEngine({
                        methodName: 'setAdditionalFieldValue',
                        args: [itemEl, key, value],
                    });
                } catch (error) {
                    console.error('additional field engine (set) failed:', error);
                }
            }

            if (!(itemEl instanceof HTMLElement)) {
                return;
            }
            const nextValue = String(value ?? '');
            const hiddenInput = itemEl.querySelector(`[data-field="${key}"]`);
            if (hiddenInput) {
                hiddenInput.value = nextValue;
            }
            const displayInput = itemEl.querySelector(`[data-field-display="${key}"]`);
            if (displayInput) {
                displayInput.value = nextValue;
            }
        }

        function refreshAdditionalItemVisibilityToggleButton(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }
            const toggleBtn = itemEl.querySelector('[data-action="toggle-item-visibility"]');
            if (!(toggleBtn instanceof HTMLElement)) {
                return;
            }
            const collapsed = itemEl.classList.contains('is-item-content-collapsed');
            setToggleItemVisibilityButtonState(toggleBtn, collapsed);
        }

        function syncDirectChildItemRowVisibilityForCollapsedParent(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }
            const isCollapsed = itemEl.classList.contains('is-item-content-collapsed');
            const rowKind = normalizeBundleRowKind(
                itemEl.getAttribute('data-row-kind') || getAdditionalFieldValue(itemEl, 'row_kind') || 'area',
            );
            if (rowKind === 'item') {
                return;
            }

            const childHost = getDirectAdditionalRowHost(itemEl, '[data-area-children]');
            if (!(childHost instanceof HTMLElement)) {
                return;
            }

            getDirectAdditionalChildRows(childHost).forEach(childRow => {
                const childKind = normalizeBundleRowKind(
                    childRow.getAttribute('data-row-kind') || getAdditionalFieldValue(childRow, 'row_kind') || 'area',
                );
                childRow.classList.toggle('is-hidden-by-parent-toggle', isCollapsed && childKind === 'item');
            });
        }

        function setAdditionalItemContentCollapsed(itemEl, collapsed) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }
            itemEl.classList.toggle('is-item-content-collapsed', !!collapsed);
            itemEl.dataset.itemContentCollapsed = collapsed ? '1' : '0';
            syncDirectChildItemRowVisibilityForCollapsedParent(itemEl);
            refreshAdditionalItemVisibilityToggleButton(itemEl);
        }

        function getDirectAdditionalChildRows(hostEl) {
            if (!(hostEl instanceof HTMLElement)) {
                return [];
            }
            return Array.from(hostEl.children).filter(
                row => row instanceof HTMLElement && row.matches('[data-additional-work-item="true"]'),
            );
        }

        function clearDirectAdditionalChildRows(hostEl) {
            if (!(hostEl instanceof HTMLElement)) {
                return;
            }
            getDirectAdditionalChildRows(hostEl).forEach(row => row.remove());
        }

        function refreshAdditionalTaxonomyActionFooters(contextEl = null) {
            relocateMainTaxonomyActionButtonsToFooter();

            const rowSelector = '.additional-work-item[data-additional-work-item="true"]';
            const rows = [];
            const seen = new Set();

            const addRow = row => {
                if (!(row instanceof HTMLElement) || !row.matches(rowSelector) || seen.has(row)) {
                    return;
                }
                seen.add(row);
                rows.push(row);
            };

            if (contextEl instanceof HTMLElement) {
                let current = contextEl.matches(rowSelector) ? contextEl : contextEl.closest(rowSelector);
                while (current instanceof HTMLElement) {
                    addRow(current);
                    current = current.parentElement instanceof HTMLElement ? current.parentElement.closest(rowSelector) : null;
                }
            }

            if (!rows.length) {
                getAllAdditionalWorkRows().forEach(addRow);
            }

            rows.forEach(row => ensureAdditionalTaxonomyActionsFooter(row));
        }

        function moveAdditionalChildRows(sourceHost, targetHost) {
            if (!(sourceHost instanceof HTMLElement) || !(targetHost instanceof HTMLElement) || sourceHost === targetHost) {
                return;
            }
            const rows = getDirectAdditionalChildRows(sourceHost);
            if (!rows.length) {
                return;
            }
            const fragment = document.createDocumentFragment();
            rows.forEach(row => fragment.appendChild(row));
            targetHost.appendChild(fragment);
        }

        function promoteAdditionalRowBeforeRemoval(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return false;
            }

            const rowKind = normalizeBundleRowKind(
                getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
            );
            if (rowKind !== 'area' && rowKind !== 'field') {
                return false;
            }

            const areaChildrenHost = getDirectAdditionalRowHost(itemEl, '[data-area-children]');
            const floorChildrenHost = getDirectAdditionalRowHost(itemEl, '[data-floor-children]');
            let promotedRow = null;
            let promotedFrom = '';

            const areaChildrenRows = getDirectAdditionalChildRows(areaChildrenHost);
            if (areaChildrenRows.length > 0) {
                promotedRow = areaChildrenRows[0];
                promotedFrom = 'area';
            } else if (rowKind === 'area') {
                const floorChildrenRows = getDirectAdditionalChildRows(floorChildrenHost);
                if (floorChildrenRows.length > 0) {
                    promotedRow = floorChildrenRows[0];
                    promotedFrom = 'floor';
                }
            }

            if (!(promotedRow instanceof HTMLElement) || !(itemEl.parentNode instanceof HTMLElement)) {
                return false;
            }

            const parentHost = itemEl.parentNode;
            parentHost.insertBefore(promotedRow, itemEl);

            const promotedAreaChildrenHost = getDirectAdditionalRowHost(promotedRow, '[data-area-children]');
            const promotedFloorChildrenHost = getDirectAdditionalRowHost(promotedRow, '[data-floor-children]');

            if (rowKind === 'field') {
                // Preserve the field context while only removing the first item in that field.
                setAdditionalFieldValue(promotedRow, 'work_floor', getAdditionalFieldValue(itemEl, 'work_floor'));
                setAdditionalFieldValue(promotedRow, 'work_area', getAdditionalFieldValue(itemEl, 'work_area'));
                setAdditionalFieldValue(promotedRow, 'work_field', getAdditionalFieldValue(itemEl, 'work_field'));
            } else if (rowKind === 'area' && promotedFrom === 'area') {
                // Preserve floor+area context while promoting the next field/item inside the same area.
                setAdditionalFieldValue(promotedRow, 'work_floor', getAdditionalFieldValue(itemEl, 'work_floor'));
                setAdditionalFieldValue(promotedRow, 'work_area', getAdditionalFieldValue(itemEl, 'work_area'));
            }

            setAdditionalWorkItemRowKind(promotedRow, rowKind);

            moveAdditionalChildRows(areaChildrenHost, promotedAreaChildrenHost);
            moveAdditionalChildRows(floorChildrenHost, promotedFloorChildrenHost);

            itemEl.remove();
            refreshAdditionalTaxonomyActionFooters(parentHost);
            return true;
        }

        function normalizeTaxonomyValue(value) {
            return String(value || '').trim().toLowerCase();
        }

        function getAdditionalRowTaxonomySnapshot(rowEl) {
            if (!(rowEl instanceof HTMLElement)) {
                return null;
            }

            return {
                rowEl,
                rowKind: normalizeBundleRowKind(
                    rowEl.getAttribute('data-row-kind') || getAdditionalFieldValue(rowEl, 'row_kind') || 'area',
                ),
                workFloor: normalizeTaxonomyValue(getAdditionalFieldValue(rowEl, 'work_floor')),
                workArea: normalizeTaxonomyValue(getAdditionalFieldValue(rowEl, 'work_area')),
                workField: normalizeTaxonomyValue(getAdditionalFieldValue(rowEl, 'work_field')),
            };
        }

        function findDuplicateAdditionalTaxonomyTarget(sourceSnapshot, changedField) {
            if (!sourceSnapshot || !sourceSnapshot.rowEl || !changedField) {
                return null;
            }

            const sourceRow = sourceSnapshot.rowEl;
            const allRows = getAllAdditionalWorkRows().filter(row =>
                row instanceof HTMLElement &&
                row !== sourceRow &&
                !sourceRow.contains(row),
            );
            if (!allRows.length) {
                return null;
            }

            const sourceFloor = sourceSnapshot.workFloor;
            const sourceArea = sourceSnapshot.workArea;
            const sourceField = sourceSnapshot.workField;

            const matchesTaxonomy = candidate => {
                if (!candidate) {
                    return false;
                }

                if (changedField === 'work_floor') {
                    return !!sourceFloor && candidate.workFloor === sourceFloor;
                }

                if (changedField === 'work_area') {
                    if (!sourceArea) {
                        return false;
                    }
                    if (sourceFloor && candidate.workFloor !== sourceFloor) {
                        return false;
                    }
                    return candidate.workArea === sourceArea;
                }

                if (changedField === 'work_field') {
                    if (!sourceField) {
                        return false;
                    }
                    if (sourceFloor && candidate.workFloor !== sourceFloor) {
                        return false;
                    }
                    if (sourceArea && candidate.workArea !== sourceArea) {
                        return false;
                    }
                    return candidate.workField === sourceField;
                }

                return false;
            };

            const preferredKinds =
                changedField === 'work_field'
                    ? ['field', 'area', 'item']
                    : ['area', 'field', 'item'];

            for (const preferredKind of preferredKinds) {
                for (const row of allRows) {
                    const candidate = getAdditionalRowTaxonomySnapshot(row);
                    if (!candidate || candidate.rowKind !== preferredKind) {
                        continue;
                    }
                    if (matchesTaxonomy(candidate)) {
                        return candidate.rowEl;
                    }
                }
            }

            for (const row of allRows) {
                const candidate = getAdditionalRowTaxonomySnapshot(row);
                if (matchesTaxonomy(candidate)) {
                    return candidate.rowEl;
                }
            }

            return null;
        }

        function isDuplicateWithMainTaxonomy(sourceSnapshot, changedField) {
            if (!sourceSnapshot || !changedField) {
                return false;
            }

            const mainFloor = normalizeTaxonomyValue(getMainTaxonomyValue('floor'));
            const mainArea = normalizeTaxonomyValue(getMainTaxonomyValue('area'));
            const mainField = normalizeTaxonomyValue(getMainTaxonomyValue('field'));

            if (changedField === 'work_floor') {
                return !!sourceSnapshot.workFloor && sourceSnapshot.workFloor === mainFloor;
            }

            if (changedField === 'work_area') {
                if (!sourceSnapshot.workArea) {
                    return false;
                }
                if (sourceSnapshot.workFloor && sourceSnapshot.workFloor !== mainFloor) {
                    return false;
                }
                return sourceSnapshot.workArea === mainArea;
            }

            if (changedField === 'work_field') {
                if (!sourceSnapshot.workField) {
                    return false;
                }
                if (sourceSnapshot.workFloor && sourceSnapshot.workFloor !== mainFloor) {
                    return false;
                }
                if (sourceSnapshot.workArea && sourceSnapshot.workArea !== mainArea) {
                    return false;
                }
                return sourceSnapshot.workField === mainField;
            }

            return false;
        }

        function focusTaxonomyTarget(target, changedField) {
            const focusSelectorMap = {
                work_floor: '[data-field-display="work_floor"]',
                work_area: '[data-field-display="work_area"]',
                work_field: '[data-field-display="work_field"]',
            };
            const mainFocusMap = {
                work_floor: 'workFloorDisplay',
                work_area: 'workAreaDisplay',
                work_field: 'workFieldDisplay',
            };

            const focusInput =
                target instanceof HTMLElement
                    ? target.querySelector(focusSelectorMap[changedField] || focusSelectorMap.work_floor)
                    : document.getElementById(mainFocusMap[changedField] || mainFocusMap.work_floor);

            if (!(focusInput instanceof HTMLElement)) {
                return;
            }

            setTimeout(() => {
                if (!(focusInput instanceof HTMLElement) || !focusInput.isConnected) {
                    return;
                }
                focusInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                focusInput.focus();
                if (typeof focusInput.__openAdditionalTaxonomyList === 'function') {
                    focusInput.__openAdditionalTaxonomyList();
                }
            }, 0);
        }

        function canAutoRemoveDuplicateRow(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return false;
            }

            const workType = String(getAdditionalFieldValue(itemEl, 'work_type') || '').trim();
            if (workType !== '') {
                return false;
            }

            const areaChildrenHost = getDirectAdditionalRowHost(itemEl, '[data-area-children]');
            const floorChildrenHost = getDirectAdditionalRowHost(itemEl, '[data-floor-children]');
            const hasAreaChildren = getDirectAdditionalChildRows(areaChildrenHost).length > 0;
            const hasFloorChildren = getDirectAdditionalChildRows(floorChildrenHost).length > 0;

            return !hasAreaChildren && !hasFloorChildren;
        }

        function autoMergeDuplicateAdditionalTaxonomyRow(itemEl, changedField) {
            if (!(itemEl instanceof HTMLElement) || !itemEl.isConnected) {
                return;
            }

            const rowKind = normalizeBundleRowKind(
                itemEl.getAttribute('data-row-kind') || getAdditionalFieldValue(itemEl, 'row_kind') || 'area',
            );
            if (rowKind === 'item') {
                return;
            }

            if (!['work_floor', 'work_area', 'work_field'].includes(changedField)) {
                return;
            }

            const sourceSnapshot = getAdditionalRowTaxonomySnapshot(itemEl);
            if (!sourceSnapshot) {
                return;
            }

            const duplicateAdditionalTarget = findDuplicateAdditionalTaxonomyTarget(sourceSnapshot, changedField);
            const duplicateMainTarget = !duplicateAdditionalTarget && isDuplicateWithMainTaxonomy(sourceSnapshot, changedField);
            if (!duplicateAdditionalTarget && !duplicateMainTarget) {
                return;
            }

            if (!canAutoRemoveDuplicateRow(itemEl)) {
                return;
            }

            const parentBeforeRemoval = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
            itemEl.remove();
            refreshAdditionalTaxonomyActionFooters(parentBeforeRemoval);
            refreshAdditionalWorkItemHeader();
            syncBundleFromForms();

            if (duplicateAdditionalTarget instanceof HTMLElement) {
                focusTaxonomyTarget(duplicateAdditionalTarget, changedField);
                return;
            }

            if (duplicateMainTarget) {
                focusTaxonomyTarget(null, changedField);
            }
        }

        function findLastAdditionalRowByTaxonomy(workFloor = '', workArea = '', workField = '', matchField = true) {
            if (!additionalWorkItemsList) {
                return null;
            }

            const targetFloor = normalizeTaxonomyValue(workFloor);
            const targetArea = normalizeTaxonomyValue(workArea);
            const targetField = normalizeTaxonomyValue(workField);
            if (!targetArea) {
                return null;
            }

            const rows = getAllAdditionalWorkRows();
            let matchedRow = null;
            rows.forEach(row => {
                if (targetFloor) {
                    const rowFloor = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_floor'));
                    if (rowFloor !== targetFloor) {
                        return;
                    }
                }
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

        function initCalculationPageSearch() {
            if (typeof window.materialCalcCreateSearchEngine === 'function') {
                const bridgeEngineApi = window.materialCalcCreateSearchEngine({
                    getBridge: getMaterialCalcVueBridge,
                });
                if (bridgeEngineApi && typeof bridgeEngineApi.refresh === 'function') {
                    return bridgeEngineApi;
                }
            }

            return {
                refresh() {},
            };
        }
        function initTaxonomyScrollTracker() {
            if (typeof window.materialCalcCreateTaxonomyTrackerEngine === 'function') {
                const bridgeTrackerApi = window.materialCalcCreateTaxonomyTrackerEngine({
                    deps: {
                        normalizeTaxonomyValue,
                        getAllAdditionalWorkRows,
                        normalizeBundleRowKind,
                        getAdditionalFieldValue,
                        showTaxonomyActionError,
                        getMainTaxonomyValue,
                        createAndFocusAdditionalWorkItem,
                        findLastAdditionalRowByTaxonomy,
                        getMainAreaChildrenHost,
                        mainTaxonomyGroupCard,
                    },
                });
                if (bridgeTrackerApi && typeof bridgeTrackerApi.refresh === 'function') {
                    return bridgeTrackerApi;
                }
            }

            return {
                refresh() {},
            };
        }
        function initCalculationScrollFab() {
            if (typeof window.materialCalcCreateScrollFabEngine === 'function') {
                const bridgeScrollFabApi = window.materialCalcCreateScrollFabEngine({
                    deps: {
                        getMainTaxonomyValue,
                        getAllAdditionalWorkRows,
                        getAdditionalFieldValue,
                        sortFloors,
                        uniqueFilterTokens,
                        sortAlphabetic,
                    },
                });
                if (bridgeScrollFabApi && typeof bridgeScrollFabApi.refresh === 'function') {
                    return bridgeScrollFabApi;
                }
            }

            return {
                refresh() {},
            };
        }
        function findLastAdditionalAreaCardByWorkArea(workFloor = '', workArea = '') {
            const targetFloor = normalizeTaxonomyValue(workFloor);
            const targetArea = normalizeTaxonomyValue(workArea);
            if (!targetArea) {
                return null;
            }

            let matched = null;
            getTopLevelAdditionalRows().forEach(row => {
                if (row.getAttribute('data-row-kind') !== 'area') {
                    return;
                }
                if (targetFloor) {
                    const rowFloor = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_floor'));
                    if (rowFloor !== targetFloor) {
                        return;
                    }
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
            const targetFloorHost = options.targetFloorHost instanceof HTMLElement ? options.targetFloorHost : null;
            const targetAreaHost = options.targetAreaHost instanceof HTMLElement ? options.targetAreaHost : null;
            const targetFieldHost = options.targetFieldHost instanceof HTMLElement ? options.targetFieldHost : null;
            let parent = additionalWorkItemsList;
            let referenceNode = null;
            const mainHost = getMainAreaChildrenHost();

            if (!additionalWorkItemsList) {
                return { parent, referenceNode };
            }

            if (item.row_kind === 'area') {
                if (forceMainAreaHost && mainHost instanceof HTMLElement) {
                    parent = mainHost;
                    if (beforeElement && beforeElement.parentNode === parent) {
                        referenceNode = beforeElement;
                    } else if (afterElement && afterElement.parentNode === parent) {
                        referenceNode = afterElement.nextSibling;
                    }
                    return { parent, referenceNode };
                }

                let floorRow =
                    (targetFloorHost && targetFloorHost.closest('.additional-work-item[data-row-kind="area"]')) ||
                    (afterElement instanceof HTMLElement
                        ? afterElement.closest('.additional-work-item[data-row-kind="area"]')
                        : null) ||
                    (beforeElement instanceof HTMLElement
                        ? beforeElement.closest('.additional-work-item[data-row-kind="area"]')
                        : null);

                if (
                    floorRow instanceof HTMLElement &&
                    floorRow.parentElement instanceof HTMLElement &&
                    floorRow.parentElement.matches('[data-floor-children]')
                ) {
                    const ownerFloor = floorRow.parentElement.closest('.additional-work-item[data-row-kind="area"]');
                    if (ownerFloor instanceof HTMLElement) {
                        floorRow = ownerFloor;
                    }
                }

                const floorChildrenHost =
                    floorRow instanceof HTMLElement ? getDirectAdditionalRowHost(floorRow, '[data-floor-children]') : null;
                if (floorChildrenHost instanceof HTMLElement) {
                    parent = floorChildrenHost;
                    if (beforeElement && beforeElement.parentNode === parent) {
                        referenceNode = beforeElement;
                    } else if (afterElement && afterElement.parentNode === parent) {
                        referenceNode = afterElement.nextSibling;
                    }
                    return { parent, referenceNode };
                }
            }

            if (item.row_kind !== 'area') {
                if (item.row_kind === 'field' && targetAreaHost instanceof HTMLElement) {
                    const areaChildren = getDirectAdditionalRowHost(targetAreaHost, '[data-area-children]');
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

                if (item.row_kind === 'item' && targetFieldHost instanceof HTMLElement) {
                    const fieldChildren = getDirectAdditionalRowHost(targetFieldHost, '[data-area-children]');
                    if (fieldChildren instanceof HTMLElement) {
                        const hostRowKind = normalizeBundleRowKind(
                            targetFieldHost.getAttribute('data-row-kind') ||
                                getAdditionalFieldValue(targetFieldHost, 'row_kind') ||
                                targetFieldHost.dataset.rowKind ||
                                'area',
                        );
                        parent = fieldChildren;
                        if (beforeElement && beforeElement.parentNode === parent) {
                            referenceNode = beforeElement;
                        } else if (afterElement && afterElement.parentNode === parent) {
                            referenceNode = afterElement.nextSibling;
                        } else {
                            if (hostRowKind === 'area') {
                                // Keep primary-field items ahead of any nested taxonomy rows that may share this host.
                                const firstNonItemRow = getDirectAdditionalChildRows(fieldChildren).find(row => {
                                    const childKind = normalizeBundleRowKind(
                                        getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                                    );
                                    return childKind !== 'item';
                                });
                                if (firstNonItemRow instanceof HTMLElement) {
                                    referenceNode = firstNonItemRow;
                                }
                            } else if (hostRowKind === 'field') {
                                const firstNonItemRow = getDirectAdditionalChildRows(fieldChildren).find(row => {
                                    const childKind = normalizeBundleRowKind(
                                        getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                                    );
                                    return childKind !== 'item';
                                });
                                if (firstNonItemRow instanceof HTMLElement) {
                                    referenceNode = firstNonItemRow;
                                }
                            }
                        }
                        return { parent, referenceNode };
                    }
                }

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
                        afterParent.matches('[data-floor-children]') ||
                        afterParent.matches('[data-main-area-children]')
                    ) {
                        return { parent: afterParent, referenceNode: afterElement.nextSibling };
                    }
                }
                if (beforeElement instanceof HTMLElement && beforeElement.parentNode instanceof HTMLElement) {
                    const beforeParent = beforeElement.parentNode;
                    if (
                        beforeParent.matches('[data-area-children]') ||
                        beforeParent.matches('[data-floor-children]') ||
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
                    hostAreaRow = findLastAdditionalAreaCardByWorkArea(item.work_floor, item.work_area);
                }

                if (hostAreaRow instanceof HTMLElement) {
                    const areaChildren = getDirectAdditionalRowHost(hostAreaRow, '[data-area-children]');
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

                const mainFloorNormalized = normalizeTaxonomyValue(getMainFormValue('workFloorValue'));
                const mainAreaNormalized = normalizeTaxonomyValue(getMainFormValue('workAreaValue'));
                const itemFloorNormalized = normalizeTaxonomyValue(item.work_floor);
                const itemAreaNormalized = normalizeTaxonomyValue(item.work_area);
                if (
                    mainHost instanceof HTMLElement &&
                    itemAreaNormalized &&
                    mainAreaNormalized &&
                    ((itemFloorNormalized && mainFloorNormalized && itemFloorNormalized === mainFloorNormalized) ||
                        (!itemFloorNormalized && !mainFloorNormalized)) &&
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

        async function confirmCalculationFormReset() {
            const message = 'Semua isian form dan konfigurasi item pekerjaan akan dihapus. Lanjut reset form?';
            if (typeof window.showConfirm === 'function') {
                return window.showConfirm({
                    title: 'Konfirmasi Reset',
                    message,
                    confirmText: 'Reset',
                    cancelText: 'Batal',
                    type: 'warning',
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

            if (
                getAdditionalFieldValue(itemEl, 'work_floor') ||
                getAdditionalFieldValue(itemEl, 'work_area') ||
                getAdditionalFieldValue(itemEl, 'work_field')
            ) {
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

        function collectAdditionalRestoreMetadata(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return {
                    restore_scope: '',
                    restore_parent_area_key: '',
                    restore_parent_field_key: '',
                };
            }

            const parentHost = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
            if (!(parentHost instanceof HTMLElement)) {
                return {
                    restore_scope: '',
                    restore_parent_area_key: '',
                    restore_parent_field_key: '',
                };
            }

            if (parentHost.matches('[data-main-area-children]')) {
                return {
                    restore_scope: 'main_area',
                    restore_parent_area_key: '',
                    restore_parent_field_key: '',
                };
            }

            const ownerRow = parentHost.closest('.additional-work-item[data-additional-work-item="true"]');
            if (!(ownerRow instanceof HTMLElement)) {
                return {
                    restore_scope: '',
                    restore_parent_area_key: '',
                    restore_parent_field_key: '',
                };
            }

            const ownerData = {
                work_floor: getAdditionalFieldValue(ownerRow, 'work_floor'),
                work_area: getAdditionalFieldValue(ownerRow, 'work_area'),
                work_field: getAdditionalFieldValue(ownerRow, 'work_field'),
            };
            const ownerRowKind = normalizeBundleRowKind(
                ownerRow.getAttribute('data-row-kind') || getAdditionalFieldValue(ownerRow, 'row_kind') || 'area',
            );

            if (parentHost.matches('[data-floor-children]')) {
                return {
                    restore_scope: 'floor_children',
                    restore_parent_area_key: buildAdditionalRestoreAreaKey(ownerData),
                    restore_parent_field_key: '',
                };
            }

            if (parentHost.matches('[data-area-children]')) {
                if (ownerRowKind === 'field') {
                    return {
                        restore_scope: 'field_children',
                        restore_parent_area_key: buildAdditionalRestoreAreaKey(ownerData),
                        restore_parent_field_key: buildAdditionalRestoreFieldKey(ownerData),
                    };
                }

                return {
                    restore_scope: 'area_children',
                    restore_parent_area_key: buildAdditionalRestoreAreaKey(ownerData),
                    restore_parent_field_key: '',
                };
            }

            return {
                restore_scope: '',
                restore_parent_area_key: '',
                restore_parent_field_key: '',
            };
        }

        function collectAdditionalWorkItemData(itemEl, index = 0) {
            if (!(itemEl instanceof HTMLElement)) {
                return null;
            }

            const displayWorkTypeEl = itemEl.querySelector('[data-field-display="work_type"]');
            const displayWorkTypeTitle = displayWorkTypeEl instanceof HTMLInputElement
                ? String(displayWorkTypeEl.value || '').trim()
                : '';
            const restoreMetadata = collectAdditionalRestoreMetadata(itemEl);

            return normalizeBundleItem(
                {
                    title: displayWorkTypeTitle,
                    row_kind: normalizeBundleRowKind(
                        getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                    ),
                    work_floor: getAdditionalFieldValue(itemEl, 'work_floor'),
                    work_area: getAdditionalFieldValue(itemEl, 'work_area'),
                    work_field: getAdditionalFieldValue(itemEl, 'work_field'),
                    work_type: getAdditionalFieldValue(itemEl, 'work_type'),
                    wall_length: getAdditionalFieldValue(itemEl, 'wall_length'),
                    wall_height: getAdditionalFieldValue(itemEl, 'wall_height'),
                    mortar_thickness: getAdditionalFieldValue(itemEl, 'mortar_thickness'),
                    layer_count: getAdditionalFieldValue(itemEl, 'layer_count'),
                    plaster_sides: getAdditionalFieldValue(itemEl, 'plaster_sides'),
                    skim_sides: getAdditionalFieldValue(itemEl, 'skim_sides'),
                    grout_thickness: getAdditionalFieldValue(itemEl, 'grout_thickness'),
                    ceramic_length: getAdditionalFieldValue(itemEl, 'ceramic_length'),
                    ceramic_width: getAdditionalFieldValue(itemEl, 'ceramic_width'),
                    ceramic_thickness: getAdditionalFieldValue(itemEl, 'ceramic_thickness'),
                    active_fields: getAdditionalActiveParameterFields(itemEl),
                    material_type_filters: collectAdditionalMaterialTypeFilters(itemEl),
                    material_customize_filters: collectAdditionalMaterialCustomizeFilters(itemEl),
                    restore_scope: restoreMetadata.restore_scope,
                    restore_parent_area_key: restoreMetadata.restore_parent_area_key,
                    restore_parent_field_key: restoreMetadata.restore_parent_field_key,
                },
                index,
            );
        }

        function getBundleFormulaLabelByCode(code) {
            const normalizedCode = String(code || '').trim();
            if (!normalizedCode) {
                return '';
            }
            const matched = bundleFormulaOptions.find(item => String(item?.code || '').trim() === normalizedCode);
            return matched ? String(matched.name || matched.code || '').trim() : '';
        }

        function setMainFieldInputValueById(id, value) {
            const inputEl = document.getElementById(id);
            if (!(inputEl instanceof HTMLInputElement)) {
                return;
            }
            inputEl.value = String(value ?? '');
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
            inputEl.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function applyMainWorkItemFromBundleItem(itemData) {
            const item = normalizeBundleItem(itemData || {}, 0);
            const mainRoot = document.getElementById('inputFormContainer') || document;

            if (workTaxonomyFilterApi && typeof workTaxonomyFilterApi.setValues === 'function') {
                workTaxonomyFilterApi.setValues('floor', item.work_floor ? [item.work_floor] : []);
                workTaxonomyFilterApi.setValues('area', item.work_area ? [item.work_area] : []);
                workTaxonomyFilterApi.setValues('field', item.work_field ? [item.work_field] : []);
            }

            if (mainWorkTypeHiddenInput instanceof HTMLInputElement) {
                mainWorkTypeHiddenInput.value = String(item.work_type || '');
                mainWorkTypeHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (mainWorkTypeDisplayInput instanceof HTMLInputElement) {
                const workTypeCode = String(item.work_type || '').trim();
                const itemTitle = String(item.title || '').trim();
                mainWorkTypeDisplayInput.value = workTypeCode
                    ? (itemTitle || getBundleFormulaLabelByCode(workTypeCode))
                    : '';
            }

            setMainFieldInputValueById('wallLength', item.wall_length);
            setMainFieldInputValueById('wallHeight', item.wall_height);
            setMainFieldInputValueById('mortarThickness', item.mortar_thickness);
            setMainFieldInputValueById('layerCount', item.layer_count);
            setMainFieldInputValueById('plasterSides', item.plaster_sides);
            setMainFieldInputValueById('skimSides', item.skim_sides);
            setMainFieldInputValueById('groutThickness', item.grout_thickness);
            setMainFieldInputValueById('ceramicLength', item.ceramic_length);
            setMainFieldInputValueById('ceramicWidth', item.ceramic_width);
            setMainFieldInputValueById('ceramicThickness', item.ceramic_thickness);

            if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.clearAll === 'function') {
                materialTypeFilterMultiApi.clearAll();
            }
            Object.entries(item.material_type_filters || {}).forEach(([type, rawValue]) => {
                const values = Array.isArray(rawValue) ? rawValue : [rawValue];
                if (materialTypeFilterMultiApi && typeof materialTypeFilterMultiApi.setValues === 'function') {
                    materialTypeFilterMultiApi.setValues(type, values);
                }
            });

            clearCustomizeFiltersInRoot(mainRoot);
            applyMaterialCustomizeFiltersToPanels(mainRoot, item.material_customize_filters || {});
            collapseEmptyCustomizePanels(mainRoot);
            syncMaterialCustomizeFiltersPayload();
        }

        function applyAdditionalWorkItemFromBundleItem(itemEl, itemData) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }

            const item = normalizeBundleItem(itemData || {}, 0);
            const isAciType = ['skim_coating', 'coating_floor'].includes(String(item.work_type || '').trim());
            const titleInput = itemEl.querySelector('[data-field="title"]');
            if (titleInput instanceof HTMLInputElement) {
                titleInput.value = String(item.title || '');
            }

            // Keep row_kind / DOM structure as-is to avoid destructive layout changes.
            ['work_floor', 'work_area', 'work_field', 'work_type'].forEach(key => {
                setAdditionalFieldValue(itemEl, key, item[key] || '');
            });
            [
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
            ].forEach(key => {
                setAdditionalFieldValue(itemEl, key, item[key] || '');
            });

            const mortarInput = itemEl.querySelector('[data-field="mortar_thickness"]');
            if (mortarInput instanceof HTMLInputElement) {
                // Preserve unit context before visibility/auto-conversion logic runs.
                mortarInput.dataset.unit = isAciType ? 'mm' : 'cm';
                if (String(item.mortar_thickness || '').trim() !== '') {
                    mortarInput.dataset.mode = isAciType ? 'acian' : 'adukan';
                }
            }

            bundleMaterialTypeOrder.forEach(type => {
                const wrap = itemEl.querySelector(`[data-material-wrap="${type}"]`);
                if (!(wrap instanceof HTMLElement)) {
                    return;
                }
                if (typeof wrap.__clearBundleMaterialTypeValues === 'function') {
                    wrap.__clearBundleMaterialTypeValues();
                }
                const values = getBundleMaterialTypeValues(item.material_type_filters || {}, type);
                if (typeof wrap.__setBundleMaterialTypeValues === 'function') {
                    wrap.__setBundleMaterialTypeValues(values);
                }
            });

            clearCustomizeFiltersInRoot(itemEl);
            applyMaterialCustomizeFiltersToPanels(itemEl, item.material_customize_filters || {});
            collapseEmptyCustomizePanels(itemEl);

            if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                itemEl.__refreshWorkTypeOptions();
            }
            applyAdditionalWorkItemVisibility(itemEl);
        }

        function setAdditionalFloorValueForRowsInScope(scopeEl, floorValue, options = {}) {
            if (!(scopeEl instanceof HTMLElement)) {
                return;
            }
            const excludeRoot = options.excludeRoot === true;
            const rows = [];
            if (!excludeRoot && scopeEl.matches('[data-additional-work-item="true"]')) {
                rows.push(scopeEl);
            }
            scopeEl.querySelectorAll('[data-additional-work-item="true"]').forEach(row => {
                if (row instanceof HTMLElement) {
                    rows.push(row);
                }
            });
            rows.forEach(row => setAdditionalFieldValue(row, 'work_floor', floorValue || ''));
        }

        function swapDirectAdditionalChildRows(hostA, hostB) {
            if (!(hostA instanceof HTMLElement) || !(hostB instanceof HTMLElement) || hostA === hostB) {
                return;
            }

            const rowsA = getDirectAdditionalChildRows(hostA);
            const rowsB = getDirectAdditionalChildRows(hostB);
            if (!rowsA.length && !rowsB.length) {
                return;
            }

            const fragA = document.createDocumentFragment();
            const fragB = document.createDocumentFragment();
            rowsA.forEach(row => fragA.appendChild(row));
            rowsB.forEach(row => fragB.appendChild(row));
            hostA.appendChild(fragB);
            hostB.appendChild(fragA);
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
                const workFloor = getAdditionalFieldValue(row, 'work_floor');
                const workArea = getAdditionalFieldValue(row, 'work_area');
                const workField = getAdditionalFieldValue(row, 'work_field');
                const workType = getAdditionalFieldValue(row, 'work_type');
                const wallLength = getAdditionalFieldValue(row, 'wall_length');
                const wallHeight = getAdditionalFieldValue(row, 'wall_height');
                if (strict && !workFloor) {
                    return {
                        items: [],
                        error: {
                            message: `Item tambahan ${i + 2} belum mengisi Lantai.`,
                            focusEl: row.querySelector('[data-field-display="work_floor"]'),
                        },
                    };
                }
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
                            work_floor: workFloor,
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
            if (strict && additionalItems.length > 0 && !String(mainItem.work_floor || '').trim()) {
                return {
                    items: [],
                    error: {
                        message: 'Lantai pada item pekerjaan utama wajib diisi.',
                        focusEl: document.getElementById('workFloorDisplay'),
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
            refreshDimensionAreaSummaries();
            rebuildBundleSharedCustomizeRealtimeState();
            enforceBundleSharedCustomizeRealtime();
            reconcileBundleSharedCustomizeSelections();
            propagateBundleSharedCustomizeFromCurrentPanels();
            syncAllBundleCustomizePanelToggleStates();
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

            if (removeMainItemBtn) {
                const hasAdditionalRows = getAllAdditionalWorkRows().length > 0;
                removeMainItemBtn.hidden = !hasAdditionalRows;
                removeMainItemBtn.disabled = !hasAdditionalRows;
            }

            const filterBest = document.getElementById('filter_best');
            if (filterBest) {
                filterBest.checked = shouldIncludeBest();
                handleOtherCheckboxes();
            }

            if (calcScrollFabApi && typeof calcScrollFabApi.refresh === 'function') {
                calcScrollFabApi.refresh();
            }
            if (calcTaxonomyScrollTrackerApi && typeof calcTaxonomyScrollTrackerApi.refresh === 'function') {
                calcTaxonomyScrollTrackerApi.refresh();
            }
            if (calcPageSearchApi && typeof calcPageSearchApi.refresh === 'function') {
                calcPageSearchApi.refresh();
            }
        }
        window.__syncBundleFromForms = syncBundleFromForms;

        function applyAdditionalWorkItemVisibility(itemEl) {
            const workType = getAdditionalFieldValue(itemEl, 'work_type');
            const hasWorkType = workType !== '';
            const isRollag = workType === 'brick_rollag';
            const isFloorLike = ['floor_screed', 'coating_floor', 'tile_installation', 'grout_tile', 'adhesive_mix']
                .includes(workType);
            const showLayer = workType === 'brick_rollag' || workType === 'painting';
            const showPlaster = false;
            const showSkim = false;
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
                let currentUnit = String(mortarInput.dataset.unit || '').trim().toLowerCase();
                if (currentUnit !== 'cm' && currentUnit !== 'mm') {
                    // Restored rows can miss this marker; infer a safe baseline from current mode/type.
                    currentUnit = mortarInput.dataset.mode === 'acian' || isAci ? 'mm' : 'cm';
                    mortarInput.dataset.unit = currentUnit;
                }
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
                toggleWrap('material_filters', false, '');
                bundleMaterialTypeOrder.forEach(type => toggleMaterialWrap(type, false));
                if (mortarInput) {
                    mortarInput.dataset.mode = 'adukan';
                }
                refreshDimensionAreaSummaries();
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
            toggleWrap('material_filters', showMaterialFilters, '');

            bundleMaterialTypeOrder.forEach(type => {
                let visible = showMaterialFilters && requiredMaterials.includes(type);
                if (type === 'ceramic_type') {
                    visible = showMaterialFilters && ['tile_installation', 'plinth_ceramic', 'adhesive_mix', 'plinth_adhesive_mix']
                        .includes(workType);
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

            refreshDimensionAreaSummaries();
        }

        function attachAdditionalWorkItemEvents(itemEl) {
            const addBtn = itemEl.querySelector('[data-action="add"]');
            const removeBtn = itemEl.querySelector('[data-action="remove"]');
            const addAreaBtn = itemEl.querySelector('[data-action="add-area"]');
            const addFieldBtn = itemEl.querySelector('[data-action="add-field"]');
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            const toggleItemVisibilityBtn = itemEl.querySelector('[data-action="toggle-item-visibility"]');
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
                    const parentBeforeRemoval = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
                    if (isAdditionalWorkItemFilled(itemEl)) {
                        const confirmed = await confirmAdditionalWorkItemRemoval(
                            'Form item pekerjaan ini sudah terisi. Yakin ingin menghapus?',
                        );
                        if (!confirmed) {
                            return;
                        }
                    }
                    if (!promoteAdditionalRowBeforeRemoval(itemEl)) {
                        itemEl.remove();
                        refreshAdditionalTaxonomyActionFooters(parentBeforeRemoval);
                    }
                    refreshAdditionalWorkItemHeader();
                    syncBundleFromForms();
                });
            }

            if (addAreaBtn) {
                addAreaBtn.addEventListener('click', function() {
                    const floorValue = getAdditionalFieldValue(itemEl, 'work_floor');
                    if (!floorValue) {
                        showTaxonomyActionError(
                            'Isi Lantai terlebih dahulu sebelum menambah Area baru.',
                            itemEl.querySelector('[data-field-display="work_floor"]'),
                        );
                        return;
                    }
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: floorValue,
                            work_area: '',
                            work_field: '',
                            work_type: '',
                            row_kind: 'area',
                        },
                        null,
                        'work_area',
                        itemEl.parentElement instanceof HTMLElement &&
                            itemEl.parentElement.matches('[data-main-area-children]')
                            ? { rowKind: 'area', targetMainArea: true }
                            : { rowKind: 'area', targetFloorHost: itemEl },
                    );
                });
            }

            if (addFieldBtn) {
                addFieldBtn.addEventListener('click', function() {
                    const floorValue = getAdditionalFieldValue(itemEl, 'work_floor');
                    const areaValue = getAdditionalFieldValue(itemEl, 'work_area');
                    if (!floorValue) {
                        showTaxonomyActionError(
                            'Isi Lantai terlebih dahulu sebelum menambah Bidang baru.',
                            itemEl.querySelector('[data-field-display="work_floor"]'),
                        );
                        return;
                    }
                    if (!areaValue) {
                        showTaxonomyActionError(
                            'Isi Area terlebih dahulu sebelum menambah Bidang baru.',
                            itemEl.querySelector('[data-field-display="work_area"]'),
                        );
                        return;
                    }
                    const targetAreaRow =
                        itemEl.closest('.additional-work-item[data-row-kind="area"]') || itemEl;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: floorValue,
                            work_area: areaValue,
                            work_field: '',
                            work_type: '',
                            row_kind: 'field',
                        },
                        null,
                        'work_field',
                        { rowKind: 'field', targetAreaHost: targetAreaRow },
                    );
                });
            }

            if (addItemBtn) {
                addItemBtn.addEventListener('click', function() {
                    const floorValue = getAdditionalFieldValue(itemEl, 'work_floor');
                    const areaValue = getAdditionalFieldValue(itemEl, 'work_area');
                    const fieldValue = getAdditionalFieldValue(itemEl, 'work_field');
                    if (!floorValue) {
                        showTaxonomyActionError(
                            'Isi Lantai terlebih dahulu sebelum menambah Item Pekerjaan.',
                            itemEl.querySelector('[data-field-display="work_floor"]'),
                        );
                        return;
                    }
                    if (!areaValue) {
                        showTaxonomyActionError(
                            'Isi Area terlebih dahulu sebelum menambah Item Pekerjaan.',
                            itemEl.querySelector('[data-field-display="work_area"]'),
                        );
                        return;
                    }
                    const targetFieldRow =
                        normalizeBundleRowKind(
                            getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                        ) === 'field'
                            ? itemEl
                            : itemEl.closest('.additional-work-item[data-row-kind="field"]') || itemEl;
                    const targetFieldChildrenHost = getDirectAdditionalRowHost(targetFieldRow, '[data-area-children]');
                    const firstNonItemRowInField =
                        targetFieldChildrenHost instanceof HTMLElement
                            ? getDirectAdditionalChildRows(targetFieldChildrenHost).find(row => {
                                  const childKind = normalizeBundleRowKind(
                                      getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                                  );
                                  return childKind !== 'item';
                              }) || null
                            : null;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: floorValue,
                            work_area: areaValue,
                            work_field: fieldValue,
                            work_type: '',
                            row_kind: 'item',
                        },
                        null,
                        'work_type',
                        {
                            rowKind: 'item',
                            targetFieldHost: targetFieldRow,
                            beforeElement: firstNonItemRowInField,
                        },
                    );
                });
            }

            if (toggleItemVisibilityBtn) {
                toggleItemVisibilityBtn.addEventListener('click', function() {
                    const nextCollapsed = !itemEl.classList.contains('is-item-content-collapsed');
                    setAdditionalItemContentCollapsed(itemEl, nextCollapsed);
                });
                refreshAdditionalItemVisibilityToggleButton(itemEl);
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

        function buildAdditionalRestoreAreaKey(itemData) {
            const item = itemData && typeof itemData === 'object' ? itemData : {};
            return [
                String(item.work_floor || '').trim().toLowerCase(),
                String(item.work_area || '').trim().toLowerCase(),
            ].join('::');
        }

        function buildAdditionalRestoreFieldKey(itemData) {
            const item = itemData && typeof itemData === 'object' ? itemData : {};
            return [
                String(item.work_floor || '').trim().toLowerCase(),
                String(item.work_area || '').trim().toLowerCase(),
                String(item.work_field || '').trim().toLowerCase(),
            ].join('::');
        }

        function shouldRestoreAdditionalItemInMainTaxonomy(itemData, mainItemData = null) {
            const item = itemData && typeof itemData === 'object' ? itemData : {};
            const mainItem = mainItemData && typeof mainItemData === 'object' ? mainItemData : {};
            const rowKind = normalizeBundleRowKind(item.row_kind || 'area');
            const workFloor = String(item.work_floor || '').trim();
            const workArea = String(item.work_area || '').trim();
            const mainWorkFloor = String(mainItem.work_floor || '').trim();
            const mainWorkArea = String(mainItem.work_area || '').trim();

            if (!mainWorkFloor || workFloor !== mainWorkFloor) {
                return false;
            }

            if (rowKind === 'area') {
                return true;
            }

            if (!mainWorkArea || workArea !== mainWorkArea) {
                return false;
            }

            return rowKind === 'field' || rowKind === 'item';
        }

        function getRestoreOptionsForAdditionalBundleItem(itemData, mainItemData = null, restoredContext = null) {
            const item = itemData && typeof itemData === 'object' ? itemData : {};
            const rowKind = normalizeBundleRowKind(item.row_kind || 'area');
            const options = { rowKind };
            const context = restoredContext && typeof restoredContext === 'object' ? restoredContext : {};
            const restoredAreaRows = context.restoredAreaRows instanceof Map ? context.restoredAreaRows : new Map();
            const restoredFieldRows = context.restoredFieldRows instanceof Map ? context.restoredFieldRows : new Map();
            const restoreScope = String(item.restore_scope || '').trim().toLowerCase();
            const restoreParentAreaKey = String(item.restore_parent_area_key || '').trim().toLowerCase();
            const restoreParentFieldKey = String(item.restore_parent_field_key || '').trim().toLowerCase();

            if (restoreScope === 'main_area') {
                options.targetMainArea = true;
                return options;
            }

            if (restoreScope === 'floor_children') {
                const parentAreaRow = restoredAreaRows.get(restoreParentAreaKey || buildAdditionalRestoreAreaKey(item));
                if (parentAreaRow instanceof HTMLElement) {
                    options.targetFloorHost = parentAreaRow;
                    return options;
                }
            }

            if (restoreScope === 'area_children') {
                const parentAreaRow = restoredAreaRows.get(restoreParentAreaKey || buildAdditionalRestoreAreaKey(item));
                if (parentAreaRow instanceof HTMLElement) {
                    options.targetAreaHost = parentAreaRow;
                    return options;
                }
            }

            if (restoreScope === 'field_children') {
                const parentFieldRow = restoredFieldRows.get(restoreParentFieldKey || buildAdditionalRestoreFieldKey(item));
                if (parentFieldRow instanceof HTMLElement) {
                    options.targetFieldHost = parentFieldRow;
                    return options;
                }
            }

            if (rowKind === 'field') {
                const areaRow = restoredAreaRows.get(buildAdditionalRestoreAreaKey(item));
                if (areaRow instanceof HTMLElement) {
                    options.targetAreaHost = areaRow;
                    return options;
                }
            }

            if (rowKind === 'item') {
                const fieldRow = restoredFieldRows.get(buildAdditionalRestoreFieldKey(item));
                if (fieldRow instanceof HTMLElement) {
                    options.targetFieldHost = fieldRow;
                    return options;
                }

                const areaRow = restoredAreaRows.get(buildAdditionalRestoreAreaKey(item));
                if (areaRow instanceof HTMLElement) {
                    options.targetFieldHost = areaRow;
                    return options;
                }
            }

            if (shouldRestoreAdditionalItemInMainTaxonomy(item, mainItemData)) {
                options.targetMainArea = true;
            }

            return options;
        }

        function restoreAdditionalWorkItemsFromBundle(restoredBundleItems = []) {
            if (!additionalWorkItemsList || !Array.isArray(restoredBundleItems) || restoredBundleItems.length <= 1) {
                return;
            }

            const mainItem = restoredBundleItems[0] && typeof restoredBundleItems[0] === 'object'
                ? restoredBundleItems[0]
                : null;
            const restoredAreaRows = new Map();
            const restoredFieldRows = new Map();

            for (let i = 1; i < restoredBundleItems.length; i += 1) {
                const item = restoredBundleItems[i];
                const restoredRow = createAdditionalWorkItemForm(
                    item,
                    null,
                    getRestoreOptionsForAdditionalBundleItem(item, mainItem, {
                        restoredAreaRows,
                        restoredFieldRows,
                    }),
                );
                const rowKind = normalizeBundleRowKind(item?.row_kind || 'area');
                if (!(restoredRow instanceof HTMLElement)) {
                    continue;
                }
                if (rowKind === 'area') {
                    restoredAreaRows.set(buildAdditionalRestoreAreaKey(item), restoredRow);
                    continue;
                }
                if (rowKind === 'field') {
                    restoredFieldRows.set(buildAdditionalRestoreFieldKey(item), restoredRow);
                }
            }
        }

        const addAreaFromMainBtn = document.getElementById('addAreaFromMainBtn');
        const addFieldFromMainBtn = document.getElementById('addFieldFromMainBtn');
        const addItemFromMainBtn = document.getElementById('addItemFromMainBtn');
        const toggleMainFieldItemVisibilityBtn = document.getElementById('toggleMainFieldItemVisibilityBtn');

        if (toggleMainFieldItemVisibilityBtn) {
            toggleMainFieldItemVisibilityBtn.addEventListener('click', function() {
                toggleMainFieldItemContentCollapsed();
            });
            setMainFieldItemContentCollapsed(false);
        }

        if (addAreaFromMainBtn) {
            addAreaFromMainBtn.addEventListener('click', function() {
                const context = getMainTaxonomyContext();
                if (!context.work_floor) {
                    showTaxonomyActionError(
                        'Isi Lantai utama terlebih dahulu sebelum menambah Area.',
                        document.getElementById('workFloorDisplay'),
                    );
                    return;
                }
                createAndFocusAdditionalWorkItem(
                    {
                        work_floor: context.work_floor,
                        work_area: '',
                        work_field: '',
                        work_type: '',
                        row_kind: 'area',
                    },
                    null,
                    'work_area',
                    { rowKind: 'area', targetMainArea: true },
                );
            });
        }

        if (addFieldFromMainBtn) {
            addFieldFromMainBtn.addEventListener('click', function() {
                const context = getMainTaxonomyContext();
                if (!context.work_floor) {
                    showTaxonomyActionError(
                        'Isi Lantai utama terlebih dahulu sebelum menambah Bidang.',
                        document.getElementById('workFloorDisplay'),
                    );
                    return;
                }
                if (!context.work_area) {
                    showTaxonomyActionError(
                        'Isi Area utama terlebih dahulu sebelum menambah Bidang.',
                        document.getElementById('workAreaDisplay'),
                    );
                    return;
                }
                const afterTarget = findLastAdditionalRowByTaxonomy(context.work_floor, context.work_area, '', false);
                const mainAreaHost = getMainAreaChildrenHost();
                const firstMainAreaRow =
                    mainAreaHost instanceof HTMLElement
                        ? Array.from(mainAreaHost.children).find(el =>
                              el instanceof HTMLElement && el.matches('[data-additional-work-item="true"]'),
                          ) || null
                        : null;
                createAndFocusAdditionalWorkItem(
                    {
                        work_floor: context.work_floor,
                        work_area: context.work_area,
                        work_field: '',
                        work_type: '',
                        row_kind: 'field',
                    },
                    afterTarget,
                    'work_field',
                    { rowKind: 'field', beforeElement: afterTarget ? null : firstMainAreaRow, targetMainArea: true },
                );
            });
        }

        if (addItemFromMainBtn) {
            addItemFromMainBtn.addEventListener('click', function() {
                const context = getMainTaxonomyContext();
                if (!context.work_floor) {
                    showTaxonomyActionError(
                        'Isi Lantai utama terlebih dahulu sebelum menambah Item Pekerjaan.',
                        document.getElementById('workFloorDisplay'),
                    );
                    return;
                }
                if (!context.work_area) {
                    showTaxonomyActionError(
                        'Isi Area utama terlebih dahulu sebelum menambah Item Pekerjaan.',
                        document.getElementById('workAreaDisplay'),
                    );
                        return;
                    }
                const mainAreaHost = getMainAreaChildrenHost();
                const firstMainAreaRow =
                    mainAreaHost instanceof HTMLElement
                        ? Array.from(mainAreaHost.children).find(el =>
                              el instanceof HTMLElement && el.matches('[data-additional-work-item="true"]'),
                          ) || null
                        : null;

                const firstNonMainItemRow =
                    mainAreaHost instanceof HTMLElement
                        ? Array.from(mainAreaHost.children).find(el => {
                              if (!(el instanceof HTMLElement) || !el.matches('[data-additional-work-item="true"]')) {
                                  return false;
                              }
                              const childKind = normalizeBundleRowKind(
                                  getAdditionalFieldValue(el, 'row_kind') || el.dataset.rowKind || 'area',
                              );
                              return childKind !== 'item';
                          }) || null
                        : null;

                createAndFocusAdditionalWorkItem(
                    {
                        work_floor: context.work_floor,
                        work_area: context.work_area,
                        work_field: context.work_field,
                        work_type: '',
                        row_kind: 'item',
                    },
                    null,
                    'work_type',
                    {
                        rowKind: 'item',
                        beforeElement: firstNonMainItemRow,
                        targetMainArea: true,
                    },
                );
            });
        }

        if (addWorkItemBtn) {
            addWorkItemBtn.addEventListener('click', function() {
                const mainItem = collectMainWorkItem();
                if (!mainItem) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Isi item pekerjaan utama dulu, lalu klik + untuk tambah lantai berikutnya.', 'error');
                    } else {
                        alert('Isi item pekerjaan utama dulu, lalu klik + untuk tambah lantai berikutnya.');
                    }
                    if (mainWorkTypeDisplayInput) {
                        mainWorkTypeDisplayInput.focus();
                    }
                    return;
                }

                createAndFocusAdditionalWorkItem(
                    {
                        work_floor: '',
                        work_area: '',
                        work_field: '',
                        work_type: '',
                        row_kind: 'area',
                    },
                    null,
                    'work_floor',
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
                refreshAdditionalTaxonomyActionFooters(
                    lastRow.parentElement instanceof HTMLElement ? lastRow.parentElement : null,
                );
                refreshAdditionalWorkItemHeader();
                syncBundleFromForms();
            });
        }

        if (removeMainItemBtn) {
            removeMainItemBtn.addEventListener('click', async function() {
                const rows = getAllAdditionalWorkRows();
                const firstRow = rows.length > 0 ? rows[0] : null;
                if (!(firstRow instanceof HTMLElement)) {
                    return;
                }

                const confirmed = await confirmAdditionalWorkItemRemoval(
                    'Item pekerjaan utama akan dihapus, lalu item pekerjaan berikutnya dipindahkan menjadi item utama. Lanjut?',
                );
                if (!confirmed) {
                    return;
                }

                const promotedItem = collectAdditionalWorkItemData(firstRow, 0);
                if (!promotedItem) {
                    return;
                }

                applyMainWorkItemFromBundleItem(promotedItem);
                const parentHost = firstRow.parentElement instanceof HTMLElement ? firstRow.parentElement : null;
                firstRow.remove();
                refreshAdditionalTaxonomyActionFooters(parentHost);
                refreshAdditionalWorkItemHeader();
                syncBundleFromForms();
            });
        }

        const restoredBundleItems = parseBundleItemsFromHidden();
        restoreAdditionalWorkItemsFromBundle(restoredBundleItems);
        bindDimensionExpressionInputs(document);
        syncBundleFromForms();
        if (initialMaterialCustomizeFiltersPayloadRaw) {
            const initialMainCustomizeFilters = parseObjectPayload(initialMaterialCustomizeFiltersPayloadRaw);
            const mainRoot = document.getElementById('inputFormContainer') || document;
            applyMaterialCustomizeFiltersToPanels(mainRoot, initialMainCustomizeFilters);
            syncBundleFromForms();
        }
        bindAutoHideEmptyCustomizePanels();
        collapseEmptyCustomizePanels(document);
        calcTaxonomyScrollTrackerApi = initTaxonomyScrollTracker();
        calcScrollFabApi = initCalculationScrollFab();
        calcPageSearchApi = initCalculationPageSearch();

        // Loading State Handler with Real Progress Simulation
        const form = document.getElementById('calculationForm');
        let loadingInterval = null;
        const calcSessionKey = 'materialCalculationSession';
        const calcExpressionStateKey = 'materialCalculationExpressionState';
        const calcPreviewPendingKey = 'materialCalculationPreviewPending';
        let saveSessionTimer = null;
        let isRestoringCalculationSessionState = false;
        let ignoreFormChangeTrackingUntil = 0;
        let isUntouchedPreviewResumeEligible = false;
        let hasUserChangedSincePreviewResume = false;
        let previewResumeBaselineSessionFingerprint = '';
        let lastFastPreviewCacheExpiredAt = 0;
        let skipSessionSaveOnBeforeUnload = false;
        const resetButton = document.getElementById('btnResetForm');
        const projectLocationGroupEl = document.querySelector('.project-location-group');
        const projectLocationSearchInput = document.getElementById('projectLocationSearch');
        const projectAddressInput = document.getElementById('projectAddress');

        const getNormalizedProjectAddressValue = () => {
            const hiddenAddress = projectAddressInput instanceof HTMLInputElement
                ? projectAddressInput.value.trim()
                : '';
            const visibleAddress = projectLocationSearchInput instanceof HTMLInputElement
                ? projectLocationSearchInput.value.trim()
                : '';

            if (hiddenAddress !== '') {
                return hiddenAddress;
            }

            if (visibleAddress !== '' && projectAddressInput instanceof HTMLInputElement) {
                projectAddressInput.value = visibleAddress;
                return visibleAddress;
            }

            return '';
        };

        const clearProjectLocationValidationState = () => {
            if (projectLocationGroupEl instanceof HTMLElement) {
                projectLocationGroupEl.classList.remove('location-required-error');
            }
            if (projectLocationSearchInput instanceof HTMLInputElement) {
                projectLocationSearchInput.removeAttribute('aria-invalid');
            }
        };

        const markProjectLocationAsRequiredError = () => {
            if (projectLocationGroupEl instanceof HTMLElement) {
                projectLocationGroupEl.classList.add('location-required-error');
                projectLocationGroupEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (projectLocationSearchInput instanceof HTMLInputElement) {
                projectLocationSearchInput.setAttribute('aria-invalid', 'true');
                projectLocationSearchInput.focus();
            }
        };

        const validateProjectLocationBeforeSubmit = () => {
            const normalizedAddress = getNormalizedProjectAddressValue();
            if (normalizedAddress !== '') {
                clearProjectLocationValidationState();
                return true;
            }

            markProjectLocationAsRequiredError();
            const message = 'Harap isi alamat proyek terlebih dahulu sebelum menghitung.';
            if (typeof window.showToast === 'function') {
                window.showToast(message, 'error');
            } else {
                alert(message);
            }

            return false;
        };

        if (projectLocationSearchInput instanceof HTMLInputElement) {
            projectLocationSearchInput.addEventListener('input', () => {
                if (projectLocationSearchInput.value.trim() !== '') {
                    clearProjectLocationValidationState();
                }
            });
            projectLocationSearchInput.addEventListener('change', () => {
                if (projectLocationSearchInput.value.trim() !== '') {
                    clearProjectLocationValidationState();
                }
            });
        }

        if (projectAddressInput instanceof HTMLInputElement) {
            projectAddressInput.addEventListener('change', () => {
                if (projectAddressInput.value.trim() !== '') {
                    clearProjectLocationValidationState();
                }
            });
        }

        if (getNormalizedProjectAddressValue() !== '') {
            clearProjectLocationValidationState();
        }

        function initStoreSearchModeControls() {
            if (typeof window.materialCalcCreateStoreModeEngine === 'function') {
                window.materialCalcCreateStoreModeEngine({
                    deps: {
                        isRestoringCalculationSessionState: () => !!isRestoringCalculationSessionState,
                        markUserChangedSincePreviewResume: () => {
                            hasUserChangedSincePreviewResume = true;
                        },
                    },
                });
                return;
            }
        }
        initStoreSearchModeControls();

        if (resetButton) {
            resetButton.addEventListener('click', async function() {
                if (!form) return;
                const confirmed = await confirmCalculationFormReset();
                if (!confirmed) {
                    return;
                }

                ignoreFormChangeTrackingUntil = Date.now() + 1000;
                if (saveSessionTimer) {
                    clearTimeout(saveSessionTimer);
                    saveSessionTimer = null;
                }
                skipSessionSaveOnBeforeUnload = true;
                form.reset();
                localStorage.removeItem(calcSessionKey);
                localStorage.removeItem(calcExpressionStateKey);
                localStorage.removeItem(calcPreviewPendingKey);
                localStorage.removeItem('materialCalculationPreview');
                isUntouchedPreviewResumeEligible = false;
                hasUserChangedSincePreviewResume = false;
                previewResumeBaselineSessionFingerprint = '';

                const cleanResetUrl = new URL(window.location.href);
                cleanResetUrl.search = '';
                cleanResetUrl.hash = '';
                window.history.replaceState({}, '', cleanResetUrl.pathname);

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
                if (workTaxonomyFilterApi && typeof workTaxonomyFilterApi.setValues === 'function') {
                    workTaxonomyFilterApi.setValues('floor', []);
                    workTaxonomyFilterApi.setValues('area', []);
                    workTaxonomyFilterApi.setValues('field', []);
                    if (typeof workTaxonomyFilterApi.refresh === 'function') {
                        workTaxonomyFilterApi.refresh();
                    }
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
                    clearDirectAdditionalChildRows(mainAreaChildrenHost);
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
                refreshAdditionalTaxonomyActionFooters(mainAreaChildrenHost instanceof HTMLElement ? mainAreaChildrenHost : null);
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

            const dimensionExpressionState = collectDimensionExpressionState();
            if (dimensionExpressionState && typeof dimensionExpressionState === 'object') {
                data.dimension_expression_state = dimensionExpressionState;
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

            try {
                const expressionState = collectDimensionExpressionState();
                localStorage.setItem(
                    calcExpressionStateKey,
                    JSON.stringify({
                        updatedAt: Date.now(),
                        data: expressionState && typeof expressionState === 'object' ? expressionState : {},
                    }),
                );
            } catch (error) {
                console.warn('Failed to save dimension expression state', error);
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

        function buildPreviewShortcutComparablePayload(formEl) {
            if (!(formEl instanceof HTMLFormElement)) {
                return null;
            }

            const payload = {};
            const formData = new FormData(formEl);

            formData.forEach((value, key) => {
                if (key === '_token' || key === 'confirm_save') {
                    return;
                }

                const normalizedKey = key.endsWith('[]') ? key.slice(0, -2) : key;
                const normalizedValue = typeof value === 'string' ? value : String(value ?? '');

                if (key.endsWith('[]')) {
                    if (!Array.isArray(payload[normalizedKey])) {
                        payload[normalizedKey] = [];
                    }
                    payload[normalizedKey].push(normalizedValue);
                    return;
                }

                // Match Laravel request behavior for duplicate scalar names:
                // later values overwrite earlier ones (e.g. hidden fallback + checkbox checked).
                payload[normalizedKey] = normalizedValue;
            });

            if (mortarThicknessInput instanceof HTMLInputElement) {
                const currentUnit = String(mortarThicknessInput.dataset.unit || '').trim().toLowerCase();
                payload.mortar_thickness_unit = currentUnit === 'mm' ? 'mm' : 'cm';
            }

            if (
                Object.prototype.hasOwnProperty.call(payload, 'mortar_thickness') &&
                (() => {
                    const selectedWorkType = String(
                        payload.work_type_select ||
                            payload.work_type ||
                            (workTypeSelector instanceof HTMLInputElement ? workTypeSelector.value : ''),
                    )
                        .trim()
                        .toLowerCase();
                    const expectsMm = ['skim_coating', 'coating_floor'].includes(selectedWorkType);
                    const currentUnit =
                        mortarThicknessInput instanceof HTMLInputElement
                            ? String(mortarThicknessInput.dataset.unit || '').trim().toLowerCase()
                            : '';
                    return currentUnit === 'mm' || expectsMm;
                })()
            ) {
                const currentValue = parseFloat(String(payload.mortar_thickness || '').replace(',', '.'));
                if (!isNaN(currentValue)) {
                    payload.mortar_thickness = formatThicknessValue(currentValue / 10);
                }
            }

            // Exclude client-only session helper state that is not part of the server request payload.
            delete payload.customize_panel_state;
            delete payload.dimension_expression_state;

            ['work_items_payload', 'material_customize_filters_payload'].forEach(jsonKey => {
                const raw = payload[jsonKey];
                if (typeof raw !== 'string' || !raw.trim()) {
                    return;
                }
                try {
                    payload[jsonKey] = JSON.parse(raw);
                } catch (error) {
                    // Keep original string if not valid JSON
                }
            });

            return payload;
        }

        function getFastPreviewNavigationUrl(currentPayload, currentSessionPayload) {
            if (!currentPayload && !currentSessionPayload) {
                return null;
            }

            const toFastPreviewComparablePayload = payload => {
                if (!payload || typeof payload !== 'object') {
                    return payload;
                }

                let clonedPayload = null;
                try {
                    clonedPayload = JSON.parse(JSON.stringify(payload));
                } catch (error) {
                    clonedPayload = { ...payload };
                }

                if (clonedPayload && typeof clonedPayload === 'object') {
                    delete clonedPayload.customize_panel_state;
                    delete clonedPayload.dimension_expression_state;
                    ['work_items_payload', 'material_customize_filters_payload'].forEach(jsonKey => {
                        const raw = clonedPayload[jsonKey];
                        if (typeof raw !== 'string' || !raw.trim()) {
                            return;
                        }
                        try {
                            clonedPayload[jsonKey] = JSON.parse(raw);
                        } catch (error) {
                            // keep raw if malformed
                        }
                    });
                }

                return clonedPayload;
            };

            let parsed = null;
            try {
                parsed = JSON.parse(localStorage.getItem('materialCalculationPreview') || 'null');
            } catch (error) {
                return null;
            }

            if (!parsed || typeof parsed !== 'object') {
                return null;
            }

            const currentPayloadFingerprint = currentPayload
                ? buildSessionFingerprint(toFastPreviewComparablePayload(currentPayload))
                : '';
            const currentUiSessionFingerprint = currentSessionPayload
                ? buildSessionFingerprint(currentSessionPayload)
                : '';
            const previewUiFingerprint = String(parsed.uiFingerprint || '').trim();
            if (previewUiFingerprint && currentUiSessionFingerprint) {
                if (previewUiFingerprint !== currentUiSessionFingerprint) {
                    return null;
                }
            } else {
            const previewFingerprint = String(parsed.fingerprint || '').trim();
            if (previewFingerprint) {
                if (previewFingerprint !== currentPayloadFingerprint) {
                    return null;
                }
            } else {
                const previewData = parsed.data;
                if (!previewData || typeof previewData !== 'object') {
                    return null;
                }
                const comparablePreviewData = toFastPreviewComparablePayload(previewData);
                if (buildSessionFingerprint(comparablePreviewData) !== currentPayloadFingerprint) {
                    return null;
                }
            }
            }

            const previewUrl = String(parsed.url || '').trim();
            if (!previewUrl) {
                return null;
            }

            const updatedAt = Number(parsed.updatedAt || 0);
            const maxAgeMs = 1000 * 60 * 60 * 6; // 6 hours (match server preview cache TTL)
            if (Number.isFinite(updatedAt) && updatedAt > 0 && Date.now() - updatedAt > maxAgeMs) {
                lastFastPreviewCacheExpiredAt = Date.now();
                return null;
            }

            try {
                const url = new URL(previewUrl, window.location.origin);
                if (url.origin !== window.location.origin) {
                    return null;
                }
                if (!/\/material-calculations\/preview\//.test(url.pathname)) {
                    return null;
                }
                return url.toString();
            } catch (error) {
                return null;
            }
        }

        function getUntouchedPreviewResumeNavigationUrl(currentSessionPayload = null) {
            if (!isUntouchedPreviewResumeEligible || hasUserChangedSincePreviewResume) {
                return null;
            }

            if (currentSessionPayload && previewResumeBaselineSessionFingerprint) {
                try {
                    const currentFingerprint = buildSessionFingerprint(currentSessionPayload);
                    if (currentFingerprint !== previewResumeBaselineSessionFingerprint) {
                        hasUserChangedSincePreviewResume = true;
                        return null;
                    }
                } catch (error) {
                    return null;
                }
            }

            let parsed = null;
            try {
                parsed = JSON.parse(localStorage.getItem('materialCalculationPreview') || 'null');
            } catch (error) {
                return null;
            }

            if (!parsed || typeof parsed !== 'object') {
                return null;
            }

            const previewUrl = String(parsed.url || '').trim();
            if (!previewUrl) {
                return null;
            }

            const updatedAt = Number(parsed.updatedAt || 0);
            const maxAgeMs = 1000 * 60 * 60 * 6;
            if (Number.isFinite(updatedAt) && updatedAt > 0 && Date.now() - updatedAt > maxAgeMs) {
                lastFastPreviewCacheExpiredAt = Date.now();
                return null;
            }

            try {
                const url = new URL(previewUrl, window.location.origin);
                if (url.origin !== window.location.origin) {
                    return null;
                }
                if (!/\/material-calculations\/preview\//.test(url.pathname)) {
                    return null;
                }
                return url.toString();
            } catch (error) {
                return null;
            }
        }

        function storePendingPreviewFingerprint(comparablePayload, currentSessionPayload) {
            if (
                (!comparablePayload || typeof comparablePayload !== 'object') &&
                (!currentSessionPayload || typeof currentSessionPayload !== 'object')
            ) {
                return;
            }
            let requestFingerprint = '';
            let uiFingerprint = '';
            try {
                if (comparablePayload && typeof comparablePayload === 'object') {
                    requestFingerprint = buildSessionFingerprint(comparablePayload);
                }
                if (currentSessionPayload && typeof currentSessionPayload === 'object') {
                    uiFingerprint = buildSessionFingerprint(currentSessionPayload);
                }
            } catch (error) {
                return;
            }
            try {
                localStorage.setItem(calcPreviewPendingKey, JSON.stringify({
                    updatedAt: Date.now(),
                    fingerprint: requestFingerprint,
                    uiFingerprint,
                }));
            } catch (error) {
                // noop
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
            const sessionMortarUnit = String(state.mortar_thickness_unit || '').trim().toLowerCase();
            let pendingMortarThickness = null;
            let pendingCustomizePanelState = null;
            let pendingMaterialCustomizeFilters = null;
            let pendingDimensionExpressionState = null;
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
                if (key === 'dimension_expression_state' && value && typeof value === 'object') {
                    pendingDimensionExpressionState = value;
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

                if (key === 'work_floors' || key === 'work_areas' || key === 'work_fields') {
                    if (workTaxonomyFilterApi && typeof workTaxonomyFilterApi.setValues === 'function') {
                        const normalizedValues = uniqueFilterTokens(Array.isArray(value) ? value : [value]);
                        const taxonomyKindMap = { work_floors: 'floor', work_areas: 'area', work_fields: 'field' };
                        workTaxonomyFilterApi.setValues(taxonomyKindMap[key] || 'area', normalizedValues);
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
                const rawValue = Array.isArray(pendingMortarThickness)
                    ? parseFloat(pendingMortarThickness[0])
                    : parseFloat(pendingMortarThickness);
                if (!isNaN(rawValue)) {
                    let restoredMmValue = rawValue;
                    if (sessionMortarUnit === 'cm') {
                        restoredMmValue = rawValue * 10;
                    } else if (sessionMortarUnit === 'mm') {
                        restoredMmValue = rawValue;
                    } else {
                        // Legacy migration: older sessions didn't store unit.
                        // Canonical cm value for acian is usually <= 1 (e.g. 0.3 for 3 mm).
                        // Values above that are likely already mm (or stale x10 chains).
                        if (rawValue <= 1) {
                            restoredMmValue = rawValue * 10;
                        } else {
                            restoredMmValue = rawValue;
                            while (restoredMmValue > 10) {
                                restoredMmValue /= 10;
                            }
                        }
                    }
                    mortarThicknessInput.value = formatThicknessValue(restoredMmValue);
                    mortarThicknessInput.dispatchEvent(new Event('change', { bubbles: true }));
                    mortarThicknessInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            if (additionalWorkItemsList) {
                additionalWorkItemsList.innerHTML = '';
                const mainAreaChildrenHost = getMainAreaChildrenHost();
                if (mainAreaChildrenHost) {
                    clearDirectAdditionalChildRows(mainAreaChildrenHost);
                }
                const restoredBundleItems = parseBundleItemsFromHidden();
                restoreAdditionalWorkItemsFromBundle(restoredBundleItems);
                refreshAdditionalTaxonomyActionFooters(mainAreaChildrenHost instanceof HTMLElement ? mainAreaChildrenHost : null);
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

            if (!(pendingDimensionExpressionState && typeof pendingDimensionExpressionState === 'object')) {
                pendingDimensionExpressionState = getStoredDimensionExpressionState();
            }

            if (pendingDimensionExpressionState && typeof pendingDimensionExpressionState === 'object') {
                applyDimensionExpressionState(pendingDimensionExpressionState);
            }

            collapseEmptyCustomizePanels(document);
        }

        function restoreCalculationSession() {
            if (!form || !shouldRestoreCalculationSession()) return;
            const params = new URLSearchParams(window.location.search);
            const resumeRequested = params.get('resume') === '1';
            const autoSubmitRequested = params.get('auto_submit') === '1';
            const raw = localStorage.getItem(calcSessionKey);
            if (!raw) return;

            let parsed = null;
            try {
                parsed = JSON.parse(raw);
            } catch (error) {
                localStorage.removeItem(calcSessionKey);
                localStorage.removeItem(calcExpressionStateKey);
                return;
            }

            const isNormalized = parsed && typeof parsed === 'object' && parsed.normalized === true;
            const state = parsed && typeof parsed === 'object' && parsed.data ? parsed.data : parsed;
            isRestoringCalculationSessionState = true;
            ignoreFormChangeTrackingUntil = Date.now() + 750;
            try {
                applyCalculationSession(state);
            } finally {
                setTimeout(() => {
                    isRestoringCalculationSessionState = false;
                }, 0);
            }

            setTimeout(() => {
                const fallbackExpressionState = getStoredDimensionExpressionState();
                if (fallbackExpressionState) {
                    applyDimensionExpressionState(fallbackExpressionState);
                }
            }, 80);

            const storeSearchModeBoxEl = document.getElementById('storeSearchModeBox');
            if (storeSearchModeBoxEl && typeof storeSearchModeBoxEl.__syncStoreSearchModeControls === 'function') {
                storeSearchModeBoxEl.__syncStoreSearchModeControls();
            }

            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('resume');
            cleanUrl.searchParams.delete('auto_submit');
            window.history.replaceState({}, '', cleanUrl.pathname + cleanUrl.search);

            let hasPreviewCache = false;
            try {
                hasPreviewCache = !!localStorage.getItem('materialCalculationPreview');
            } catch (error) {
                hasPreviewCache = false;
            }
            isUntouchedPreviewResumeEligible = resumeRequested && hasPreviewCache;
            hasUserChangedSincePreviewResume = false;
            previewResumeBaselineSessionFingerprint = '';
            try {
                const restoredSessionPayload = serializeCalculationSession(form);
                if (restoredSessionPayload) {
                    previewResumeBaselineSessionFingerprint = buildSessionFingerprint(restoredSessionPayload);
                }
            } catch (error) {
                previewResumeBaselineSessionFingerprint = '';
            }

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

        function getMaterialCalcVueBridge() {
            return window.materialCalcVueBridge && typeof window.materialCalcVueBridge === 'object'
                ? window.materialCalcVueBridge
                : null;
        }

        function getMaterialCalculationSubmitButton() {
            return form ? form.querySelector('button[type="submit"]') : null;
        }

        function startLoadingState(isFastCachePath, submitButton) {
            const bridge = getMaterialCalcVueBridge();
            const safeButton = submitButton instanceof HTMLButtonElement ? submitButton : null;
            if (
                bridge &&
                bridge.loading &&
                typeof bridge.loading.start === 'function'
            ) {
                bridge.loading.start({
                    isFastCachePath: !!isFastCachePath,
                    submitButton: safeButton,
                });
                return;
            }

            if (safeButton && !safeButton.getAttribute('data-original-text')) {
                safeButton.setAttribute('data-original-text', safeButton.innerHTML);
            }

            document.getElementById('loadingOverlay').style.display = 'flex';

            if (safeButton) {
                safeButton.disabled = true;
                safeButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
            }

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
                { p: 95, t: 'Finalisasi...', s: 'Sedang mengalihkan ke halaman hasil...' },
            ];

            if (loadingInterval) clearInterval(loadingInterval);

            const intervalMs = isFastCachePath ? 35 : 50;
            loadingInterval = setInterval(() => {
                let increment = 0;

                if (progress < 60) {
                    increment = isFastCachePath
                        ? Math.random() * 6 + 4
                        : Math.random() * 4 + 1;
                } else if (progress < 85) {
                    increment = isFastCachePath
                        ? Math.random() * 2.5 + 0.5
                        : Math.random() * 1.5 + 0.2;
                } else if (progress < 98) {
                    increment = isFastCachePath ? 0.12 : 0.05;
                }

                progress = Math.min(progress + increment, 98);

                const percentText = (() => {
                    const scaled = Math.floor(progress * 100);
                    const intPart = Math.floor(scaled / 100);
                    const decPart = (scaled % 100).toString().padStart(2, '0');
                    return `${intPart}.${decPart}%`;
                })();

                bar.style.width = `${progress}%`;
                percent.textContent = percentText;

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

        function completeLoadingStateForNavigation() {
            const bridge = getMaterialCalcVueBridge();
            if (
                bridge &&
                bridge.loading &&
                typeof bridge.loading.completeForNavigation === 'function'
            ) {
                bridge.loading.completeForNavigation();
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            if (overlay && overlay.style.display !== 'none') {
                const bar = document.getElementById('loadingProgressBar');
                const percent = document.getElementById('loadingPercent');
                const title = document.getElementById('loadingTitle');
                const subtitle = document.getElementById('loadingSubtitle');

                if (bar) {
                    bar.style.width = '100%';
                    bar.classList.remove('progress-bar-animated');
                }
                if (percent) percent.textContent = '100.00%';
                if (title) title.textContent = 'Selesai!';
                if (subtitle) subtitle.textContent = 'Memuat hasil perhitungan...';
            }
        }

        // Function to Reset UI
        function resetLoadingState() {
            const bridge = getMaterialCalcVueBridge();
            const submitButton = getMaterialCalculationSubmitButton();
            if (
                bridge &&
                bridge.loading &&
                typeof bridge.loading.reset === 'function'
            ) {
                bridge.loading.reset(submitButton);
                return;
            }

            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'none';

            if (loadingInterval) {
                clearInterval(loadingInterval);
                loadingInterval = null;
            }

            if (submitButton) {
                submitButton.disabled = false;
                const originalText = submitButton.getAttribute('data-original-text');
                if (originalText) {
                    submitButton.innerHTML = originalText;
                } else {
                    submitButton.innerHTML = '<i class="bi bi-search"></i> Hitung';
                }
            }

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

        let useVueBridgeForSessionTracking = false;

        const trackFormMutationForSession = function(isTrustedMutation) {
            if (isTrustedMutation && !isRestoringCalculationSessionState && Date.now() >= ignoreFormChangeTrackingUntil) {
                hasUserChangedSincePreviewResume = true;
                skipSessionSaveOnBeforeUnload = false;
            }
            if (isRestoringCalculationSessionState || Date.now() < ignoreFormChangeTrackingUntil) {
                return;
            }
            if (saveSessionTimer) clearTimeout(saveSessionTimer);
            saveSessionTimer = setTimeout(saveCalculationSession, 250);
        };

        if (form) {
            const nativeFormMutationListener = function(event) {
                if (useVueBridgeForSessionTracking) {
                    return;
                }
                trackFormMutationForSession(!!(event && event.isTrusted));
            };

            form.addEventListener('input', nativeFormMutationListener);
            form.addEventListener('change', nativeFormMutationListener);

            window.addEventListener('material-calc-vue-bridge:ready', function() {
                useVueBridgeForSessionTracking = true;
            });

            window.addEventListener('material-calc-vue-bridge:change', function(event) {
                const detail = event && event.detail ? event.detail : null;
                trackFormMutationForSession(!!(detail && detail.isTrusted));
            });

            form.addEventListener('submit', function(e) {
                if (!validateProjectLocationBeforeSubmit()) {
                    e.preventDefault();
                    return;
                }

                const expressionNormalization = normalizeDimensionExpressionInputsForSubmit(form, { commitValue: false });
                if (!expressionNormalization.ok) {
                    e.preventDefault();
                    if (typeof window.showToast === 'function') {
                        window.showToast(expressionNormalization.message, 'error');
                    } else {
                        alert(expressionNormalization.message);
                    }
                    if (expressionNormalization.focusEl && typeof expressionNormalization.focusEl.focus === 'function') {
                        expressionNormalization.focusEl.focus();
                    }
                    return;
                }
                const storeSearchModeBoxEl = document.getElementById('storeSearchModeBox');
                if (storeSearchModeBoxEl && typeof storeSearchModeBoxEl.__commitStoreSearchModeControls === 'function') {
                    storeSearchModeBoxEl.__commitStoreSearchModeControls();
                }
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

                if (this.checkValidity()) {
                    const committedExpressionNormalization = normalizeDimensionExpressionInputsForSubmit(form, { commitValue: true });
                    if (!committedExpressionNormalization.ok) {
                        e.preventDefault();
                        if (typeof window.showToast === 'function') {
                            window.showToast(committedExpressionNormalization.message, 'error');
                        } else {
                            alert(committedExpressionNormalization.message);
                        }
                        if (committedExpressionNormalization.focusEl && typeof committedExpressionNormalization.focusEl.focus === 'function') {
                            committedExpressionNormalization.focusEl.focus();
                        }
                        return;
                    }

                    const normalizedBundleBuild = buildBundleItems(true);
                    if (normalizedBundleBuild.error) {
                        e.preventDefault();
                        if (typeof window.showToast === 'function') {
                            window.showToast(normalizedBundleBuild.error.message, 'error');
                        } else {
                            alert(normalizedBundleBuild.error.message);
                        }
                        if (normalizedBundleBuild.error.focusEl && typeof normalizedBundleBuild.error.focusEl.focus === 'function') {
                            normalizedBundleBuild.error.focusEl.focus();
                        }
                        return;
                    }
                    const normalizedBundleItems = normalizedBundleBuild.items || [];
                    const normalizedBundleModeEnabled = normalizedBundleItems.length >= 2;
                    if (normalizedBundleModeEnabled) {
                        setMainFormRequired(false);
                        if (workItemsPayloadInput) {
                            workItemsPayloadInput.value = JSON.stringify(normalizedBundleItems);
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

                    const currentSession = serializeCalculationSession(form);
                    const previewShortcutPayload = buildPreviewShortcutComparablePayload(form);
                    lastFastPreviewCacheExpiredAt = 0;
                    const fastPreviewUrl =
                        getFastPreviewNavigationUrl(previewShortcutPayload, currentSession)
                        || getUntouchedPreviewResumeNavigationUrl(currentSession);
                    const isFastCachePath = !!fastPreviewUrl || (currentSession ? isSameAsLastSession(currentSession) : false);
                    saveCalculationSession(currentSession);
                    if (fastPreviewUrl) {
                        e.preventDefault();
                        isUntouchedPreviewResumeEligible = false;
                        window.location.href = fastPreviewUrl;
                        return;
                    }
                    if (lastFastPreviewCacheExpiredAt && typeof window.showToast === 'function') {
                        window.showToast('Cache preview kadaluarsa. Sistem akan menghitung ulang untuk memuat hasil terbaru.', 'warning');
                    }
                    if (previewShortcutPayload) {
                        storePendingPreviewFingerprint(previewShortcutPayload, currentSession);
                    }
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

                    const btn = this.querySelector('button[type="submit"]');
                    startLoadingState(isFastCachePath, btn);
                }
            });
        }
        
        // VISUAL TRICK: Force 100% when browser starts navigation (server responded)
        window.addEventListener('beforeunload', function() {
            // Persist latest values so browser refresh keeps dynamic filters as well.
            if (!skipSessionSaveOnBeforeUnload) {
                saveCalculationSession();
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
