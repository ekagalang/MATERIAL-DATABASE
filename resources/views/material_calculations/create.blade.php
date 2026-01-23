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
                                <label>Tebal Adukan</label>
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
                        </div>
                    </div>
                </div>

                {{-- CERAMIC FILTERS - ONLY FOR TILE INSTALLATION --}}
                    <div class="filter-section" id="ceramicFilterSection" style="display: none;">
                        <label class="filter-section-title">+ Filter Keramik:</label>

                        {{-- Jenis Keramik - Dynamic from Database --}}
                        @if(isset($ceramicTypes) && $ceramicTypes->count() > 0)
                        <div class="form-group ceramic-filter-row">
                            <label class="ceramic-filter-label">
                                <i class="bi bi-grid-3x3-gap-fill"></i> Jenis Keramik
                            </label>
                            <div class="input-wrapper">
                                <div class="filter-tickbox-list ceramic-tickbox-grid">
                                    @foreach($ceramicTypes as $type)
                                    <div class="tickbox-item">
                                        <input type="checkbox" name="ceramic_types[]" id="ceramic_type_{{ Str::slug($type) }}" value="{{ $type }}">
                                        <label for="ceramic_type_{{ Str::slug($type) }}">
                                            <span class="tickbox-title">{{ $type }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Ukuran Keramik - Dynamic from Database --}}
                        @if(isset($ceramicSizes) && $ceramicSizes->count() > 0)
                        <div class="form-group ceramic-filter-row">
                            <label class="ceramic-filter-label">
                                <i class="bi bi-rulers"></i> Ukuran Keramik
                            </label>
                            <div class="input-wrapper">
                                <div class="filter-tickbox-list ceramic-tickbox-grid">
                                    @foreach($ceramicSizes as $size)
                                    <div class="tickbox-item">
                                        <input type="checkbox" name="ceramic_sizes[]" id="ceramic_size_{{ str_replace('x', '_', $size) }}" value="{{ $size }}">
                                        <label for="ceramic_size_{{ str_replace('x', '_', $size) }}">
                                            <span class="tickbox-title">{{ str_replace('x', ' x ', $size) }} cm</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
            </div>

            {{-- RIGHT COLUMN: FILTERS --}}
            <div class="right-column">
                    {{-- FILTER CHECKBOX (MULTIPLE SELECTION) --}}
                    <div class="filter-section">
                        <label class="filter-section-title">+ Filter by:</label>
                        <div class="filter-tickbox-list">
                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_all" value="all">
                                <label for="filter_all">
                                    <span class="tickbox-title">
                                        Semua
                                    </span>
                                    <span class="tickbox-desc">Menampilkan semua kombinasi material</span>
                                </label>
                            </div>

                            <div class="tickbox-item position-relative">
                                <input type="checkbox" name="price_filters[]" id="filter_best" value="best" checked>
                                <label for="filter_best">
                                    <span class="tickbox-title">
                                        Rekomendasi
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi Most Recommended (Custom Setting)</span>
                                </label>
                                <a href="{{ route('settings.recommendations.index') }}" 
                                   class="position-absolute top-0 end-0 mt-1 me-1 p-1" 
                                   style="z-index: 5; color: #000000 !important;" 
                                   title="Setting Rekomendasi"
                                   onclick="event.preventDefault(); if(typeof openGlobalMaterialModal === 'function') { openGlobalMaterialModal(this.href, document.getElementById('workTypeSelector')?.value); }">
                                    <i class="bi bi-gear-fill"></i>
                                </a>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_common" value="common">
                                <label for="filter_common">
                                    <span class="tickbox-title">
                                        Populer
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi yang paling sering dihitung user</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_cheapest" value="cheapest">
                                <label for="filter_cheapest">
                                    <span class="tickbox-title">
                                        Ekonomis
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga Ekonomis</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_medium" value="medium">
                                <label for="filter_medium">
                                    <span class="tickbox-title">
                                        Moderat
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan harga menengah</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_expensive" value="expensive">
                                <label for="filter_expensive">
                                    <span class="tickbox-title">
                                        Premium
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga Premium</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_custom" value="custom">
                                <label for="filter_custom">
                                    <span class="tickbox-title">
                                        Custom
                                    </span>
                                    <span class="tickbox-desc">Pilih material sendiri secara manual</span>
                                </label>
                            </div>
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
                                                    {{ $nat->brand }} ({{ $nat->package_weight_net }} kg)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

        </div>

        <div class="button-actions">
            <button type="submit" class="btn btn-submit">
                <i class="bi bi-search"></i> Hitung
            </button>
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
<script src="{{ asset('js/material-calculation-form.js') }}"></script>
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
        const wallHeightLabel = document.getElementById('wallHeightLabel');
        const wallHeightGroup = document.getElementById('wallHeightGroup');
        const wallHeightInput = document.getElementById('wallHeight');
        const ceramicFilterSection = document.getElementById('ceramicFilterSection');
        const mortarThicknessInput = document.getElementById('mortarThickness');
        const mortarThicknessUnit = document.getElementById('mortarThicknessUnit');
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

            setWallHeightVisibility(!isRollag);

            if (wallHeightLabel) {
                wallHeightLabel.textContent = 'Tinggi';
            }

            if (workTypeSelector && layerCountGroup && plasterSidesGroup && skimSidesGroup) {
                if (workTypeSelector.value === 'brick_rollag') {
                    layerCountGroup.style.display = 'flex';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
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
                    } else {
                        setMortarThicknessUnit('cm');
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
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
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

                    // Show ceramic dimension inputs for grout_tile
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'flex';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'flex';
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
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'flex';
                    setMortarThicknessUnit('cm');
                    // Restore label to "Tinggi" for other formulas
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                }
            }
        }

        // Handle work type changes - simplified version
        let handleWorkTypeChange;

        if (workTypeSelector) {
            handleWorkTypeChange = function() {
                const selectedWorkType = workTypeSelector.value;

                // Update "Rekomendasi" filter state based on availability
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
            if (workTypeInput && workTypeValue) {
                workTypeInput.value = workTypeValue;
                workTypeInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(field => {
                field.checked = false;
            });

            Object.entries(state).forEach(([key, value]) => {
                if (key === 'work_type_select' || key === 'work_type') return;
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

            const state = parsed && typeof parsed === 'object' && parsed.data ? parsed.data : parsed;
            applyCalculationSession(state);

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
                // Client-side validation for price filters
                const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
                const isAnyFilterChecked = Array.from(filterCheckboxes).some(cb => cb.checked);

                if (!isAnyFilterChecked) {
                    e.preventDefault();
                    if (typeof window.showToast === 'function') {
                        window.showToast('Harap pilih minimal satu filter harga (contoh: Rekomendasi, Ekonomis).', 'error');
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
