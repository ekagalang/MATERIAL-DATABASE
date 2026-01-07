@extends('layouts.app')

@php
    $formulaDescriptions = [];
    $formulas = $availableFormulas ?? $formulas ?? [];
    foreach ($formulas as $formula) {
        $formulaDescriptions[$formula['code']] = $formula['description'] ?? '';
    }
    
    // Cek Single Brick (Carry Over)
    $isSingleCarryOver = request()->has('brick_id');
    $singleBrick = $isSingleCarryOver ? $bricks->find(request('brick_id')) : null;

    // FIX: Definisikan variable $isMultiBrick dengan benar
    $isMultiBrick = isset($selectedBricks) && $selectedBricks->count() > 0;
@endphp

@section('content')
<div class="card">
    <h3 class="form-title"><i class="bi bi-calculator text-primary"></i> Perhitungan Material Baru</h3>

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

        {{-- Hidden fields for default values --}}
        <input type="hidden" name="installation_type_id" value="{{ $defaultInstallationType->id ?? '' }}">
        <input type="hidden" name="mortar_formula_id" value="{{ $defaultMortarFormula->id ?? '' }}">

        {{-- TWO COLUMN LAYOUT - ALWAYS VISIBLE --}}
        <div class="two-column-layout">
            {{-- LEFT COLUMN: FORM INPUTS --}}
            <div class="left-column">
                {{-- WORK TYPE --}}
                <div class="form-group-fullwidth">
                    <label>Item Pekerjaan</label>
                    <select id="workTypeSelector" name="work_type_select" required {{ request('formula_code') ? 'disabled' : '' }}>
                        <option value="">-- Pilih Item Pekerjaan --</option>
                        @foreach($formulas as $formula)
                            <option value="{{ $formula['code'] }}" {{ request('formula_code') == $formula['code'] ? 'selected' : '' }}>
                                {{ $formula['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @if(request('formula_code'))
                        <input type="hidden" name="work_type" value="{{ request('formula_code') }}">
                    @endif
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

                            <div class="dimension-item">
                                <label id="wallHeightLabel">Tinggi</label>
                                <div class="input-with-unit">
                                    <input type="number" name="wall_height" id="wallHeight" step="0.01" min="0.01"
                                        value="{{ request('wall_height') }}" required>
                                    <span class="unit">M</span>
                                </div>
                            </div>

                            <div class="dimension-item" id="mortarThicknessGroup">
                                <label>Tebal</label>
                                <div class="input-with-unit">
                                    <input type="number" name="mortar_thickness" id="mortarThickness" step="0.1" min="0.1"
                                        value="{{ request('mortar_thickness', 2) }}">
                                    <span class="unit">cm</span>
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
                                <label>Sisi Aci</label>
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
                        <div class="mb-3">
                            <label class="fw-semibold mb-2" style="font-size: 0.9rem; color: #f59e0b;">
                                <i class="bi bi-grid-3x3-gap-fill"></i> Jenis Keramik
                            </label>
                            <div class="filter-tickbox-list">
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
                        @endif

                        {{-- Ukuran Keramik - Dynamic from Database --}}
                        @if(isset($ceramicSizes) && $ceramicSizes->count() > 0)
                        <div>
                            <label class="fw-semibold mb-2" style="font-size: 0.9rem; color: #f59e0b;">
                                <i class="bi bi-rulers"></i> Ukuran Keramik
                            </label>
                            <div class="filter-tickbox-list">
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
                                        TerBAIK
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi Most Recommended (Custom Setting)</span>
                                </label>
                                <a href="{{ route('settings.recommendations.index') }}" class="global-open-modal btn btn-sm btn-link text-muted position-absolute top-0 end-0 mt-1 me-1 p-1" style="z-index: 5;" title="Setting Rekomendasi">
                                    <i class="bi bi-gear-fill"></i>
                                </a>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_common" value="common">
                                <label for="filter_common">
                                    <span class="tickbox-title">
                                        TerUMUM
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi yang paling sering dihitung user</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_cheapest" value="cheapest">
                                <label for="filter_cheapest">
                                    <span class="tickbox-title">
                                        TerMURAH
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga termurah</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_medium" value="medium">
                                <label for="filter_medium">
                                    <span class="tickbox-title">
                                        TerSEDANG
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan harga menengah</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_expensive" value="expensive">
                                <label for="filter_expensive">
                                    <span class="tickbox-title">
                                        TerMAHAL
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga termahal</span>
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
                        <div class="material-section">
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
                                                    {{ $brick->brand }} - {{ $brick->type }} ({{ $brick->dimension_length }}x{{ $brick->dimension_width }}x{{ $brick->dimension_height }} cm) - Rp {{ number_format($brick->price_per_piece) }}
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
                        <div class="material-section">
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
                        <div class="material-section">
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
                        <div class="material-section" id="catSection" style="display: none;">
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
                        <div class="material-section" id="ceramicSection" style="display: none;">
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
                        <div class="material-section" id="natSection" style="display: none;">
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
                <i class="bi bi-search"></i> Hitung / Cari Kombinasi
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style data-modal-style="material-calculation">
    * { box-sizing: border-box; }
    
    .card {
        max-width: 1200px !important;
        width: 100% !important;
        background: #fff;
        padding: 24px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin: 10px auto;
    }

    /* Two Column Layout */
    .two-column-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        width: 100%;
    }

    .left-column,
    .right-column {
        min-width: 0;
    }

    .filter-section {
        /* Sticky positioning removed */
    }

    .filter-section-title {
        font-weight: 600;
        color: inherit;
        font-size: 14px;
        margin-bottom: 12px;
        display: block;
    }
    
    .form-title {
        font-size: 18px;
        font-weight: 700;
        color: inherit;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Full Width Form Group (for Item Pekerjaan) */
    .form-group-fullwidth {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
    }

    .form-group-fullwidth label {
        font-weight: 600;
        color: inherit;
        font-size: 14px;
    }

    .form-group-fullwidth select {
        width: 100%;
    }

    .form-group {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }

    .form-group label {
        flex: 0 0 120px;
        font-weight: 400;
        color: inherit;
        font-size: 14px;
        padding-top: 10px;
        text-align: left;
    }
    
    .input-wrapper { 
        flex: 1; 
    }
    
    input[type="text"], 
    input[type="number"], 
    select { 
        width: 100%; 
        padding: 8px 12px; 
        border: 1px solid #cbd5e1; 
        border-radius: 4px; 
        font-size: 14px; 
        color: var(--text-color) !important; 
        -webkit-text-stroke: var(--text-stroke) !important;
        text-shadow: var(--text-shadow) !important;
        background: #fff; 
        font-family: inherit; 
    }
    
    /* Hide number input arrows */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
    
    input[type="text"]:focus, 
    input[type="number"]:focus, 
    select:focus { 
        outline: none; 
        border-color: #64748b; 
    }
    
    select { 
        cursor: pointer; 
        appearance: none; 
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E"); 
        background-repeat: no-repeat; 
        background-position: right 12px center; 
        padding-right: 32px; 
    }
    
    /* Colored select backgrounds */
    .select-green { background-color: #d1fae5 !important; }
    .select-blue { background-color: #bfdbfe !important; }
    .select-pink { background-color: #fbcfe8 !important; }
    .select-orange { background-color: #fed7aa !important; }
    .select-gray { background-color: #e2e8f0 !important; }
    .select-gray-light { background-color: #f1f5f9 !important; }
    .select-yellow { background-color: #fef3c7 !important; }
    
    /* Dimensions container - Vertical Layout */
    .dimensions-container-vertical {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 16px;
        width: 100%;
    }

    .dimension-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
        width: 100%;
    }

    .dimension-item label {
        font-size: 14px;
        color: inherit;
        font-weight: 600;
        margin: 0;
        padding: 0;
    }

    /* Old dimensions container (keep for backwards compatibility) */
    .dimensions-container {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 16px;
        flex-wrap: wrap;
        width: 100%;
    }

    .dimension-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
        min-width: 100px;
    }

    .dimension-group label {
        font-size: 12px;
        color: #64748b;
        font-weight: 400;
        margin: 0;
        padding: 0;
    }
    
    .input-with-unit { 
        display: flex; 
        align-items: center; 
        border: 1px solid #cbd5e1; 
        border-radius: 4px; 
        background: #fff;
        overflow: hidden;
        width: 100%;
    }
    
    .input-with-unit input {
        border: none;
        padding: 8px 8px;
        flex: 1;
        width: 100%;
        text-align: center;
    }
    
    .input-with-unit input:focus { 
        border: none;
        outline: none;
    }
    
    /* Readonly/disabled input style */
    .input-with-unit input:read-only,
    .input-with-unit input[readonly] {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }
    
    .input-with-unit .unit { 
        padding: 8px 10px; 
        color: #64748b; 
        font-size: 12px; 
        font-weight: 600;
        border-left: 1px solid #cbd5e1;
    }
    
    .operator { 
        font-weight: 700; 
        color: #94a3b8; 
        padding: 0 4px;
        margin-bottom: 8px;
        flex: 0 0 auto;
    }
    
    /* Material sections */
    .material-section { 
        margin-top: 16px;
        margin-bottom: 16px;
    }
    
    .section-header { 
        font-weight: 700; 
        font-size: 15px; 
        color: inherit; 
        margin-bottom: 12px;
    }
    
    /* Buttons */
    .button-actions { 
        display: flex; 
        justify-content: flex-end; 
        gap: 12px; 
        margin-top: 24px; 
        padding-top: 16px; 
        border-top: 1px solid #e2e8f0; 
    }
    
    .btn { 
        padding: 10px 24px; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 14px; 
        font-weight: 600; 
        transition: all 0.2s; 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        font-family: inherit; 
    }
    
    .btn:hover { 
        transform: translateY(-1px); 
        box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
    }
    
    .btn-cancel { 
        background: #fff; 
        color: #dc2626; 
        border: 1px solid #dc2626; 
    }
    
    .btn-cancel:hover { 
        background: #fef2f2; 
    }
    
    .btn-submit { 
        background: #16a34a; 
        color: #fff; 
        border: none; 
    }
    
    .btn-submit:hover { 
        background: #15803d; 
    }
    
    /* Alert */
    .alert { 
        padding: 12px 16px; 
        border-radius: 4px; 
        margin-bottom: 16px; 
        font-size: 14px; 
    }
    
    .alert-danger { 
        background: #fee2e2; 
        border: 1px solid #fca5a5; 
        color: #991b1b; 
    }
    
    .alert ul { 
        margin: 8px 0 0 20px; 
        line-height: 1.6; 
    }
    
    .alert-info {
        background: #dbeafe;
        border: 1px solid #93c5fd;
        color: #1e40af;
    }

    .text-muted {
        color: #64748b;
        font-size: 13px;
    }

    /* Filter tickbox list */
    .filter-tickbox-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    .tickbox-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .tickbox-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    /* Filter background colors */
    .tickbox-item:has(#filter_all) {
        background: #ffffff;
        border-color: #cbd5e1;
    }

    .tickbox-item:has(#filter_best) {
        background: #fee2e2;
        border-color: #fca5a5;
    }

    .tickbox-item:has(#filter_common) {
        background: #dbeafe;
        border-color: #93c5fd;
    }

    .tickbox-item:has(#filter_cheapest) {
        background: #d1fae5;
        border-color: #6ee7b7;
    }

    .tickbox-item:has(#filter_medium) {
        background: #fef3c7;
        border-color: #fcd34d;
    }

    .tickbox-item:has(#filter_expensive) {
        background: #f3e8ff;
        border-color: #d8b4fe;
    }

    /* Hover states untuk setiap filter */
    .tickbox-item:has(#filter_all):hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .tickbox-item:has(#filter_best):hover {
        background: #fecaca;
        border-color: #f87171;
    }

    .tickbox-item:has(#filter_common):hover {
        background: #bfdbfe;
        border-color: #60a5fa;
    }

    .tickbox-item:has(#filter_cheapest):hover {
        background: #a7f3d0;
        border-color: #34d399;
    }

    .tickbox-item:has(#filter_medium):hover {
        background: #fde68a;
        border-color: #fbbf24;
    }

    .tickbox-item:has(#filter_expensive):hover {
        background: #e9d5ff;
        border-color: #c084fc;
    }

    .tickbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        cursor: pointer;
        flex-shrink: 0;
        accent-color: #891313;
    }

    .tickbox-item label {
        flex: 1;
        cursor: pointer;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .tickbox-item .tickbox-title {
        font-weight: 600;
        font-size: 14px;
        color: inherit;
        display: flex;
        align-items: center;
    }

    .tickbox-item .tickbox-desc {
        font-size: 12px;
        color: #64748b;
        line-height: 1.4;
    }

    .tickbox-item input[type="checkbox"]:checked ~ label {
        color: #0f172a;
    }

    .tickbox-item input[type="checkbox"]:checked ~ label .tickbox-title {
        font-weight: 700;
    }

    /* Checked states untuk setiap filter */
    .tickbox-item:has(#filter_all:checked) {
        background: #f8fafc;
        border-color: #64748b;
        box-shadow: 0 2px 8px rgba(100, 116, 139, 0.2);
    }

    .tickbox-item:has(#filter_best:checked) {
        background: #fecaca;
        border-color: #dc2626;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
    }

    .tickbox-item:has(#filter_common:checked) {
        background: #bfdbfe;
        border-color: #2563eb;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
    }

    .tickbox-item:has(#filter_cheapest:checked) {
        background: #a7f3d0;
        border-color: #16a34a;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.2);
    }

    .tickbox-item:has(#filter_medium:checked) {
        background: #fde68a;
        border-color: #eab308;
        box-shadow: 0 2px 8px rgba(234, 179, 8, 0.2);
    }

    .tickbox-item:has(#filter_expensive:checked) {
        background: #e9d5ff;
        border-color: #9333ea;
        box-shadow: 0 2px 8px rgba(147, 51, 234, 0.2);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .card {
            max-width: 100% !important;
        }

        .two-column-layout {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .form-group-fullwidth {
            width: 100%;
        }

        .form-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .form-group label {
            flex: unset;
            padding-top: 0;
        }

        .dimensions-container,
        .dimensions-container-vertical {
            flex-direction: column;
            align-items: stretch;
        }

        .dimension-group,
        .dimension-item {
            width: 100%;
        }

        .input-with-unit input {
            width: 100%;
        }

        .button-actions {
            flex-direction: column-reverse;
        }

        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@push('scripts')
{{-- Load JS Asli --}}
<script type="application/json" id="materialCalculationFormData">
{!! json_encode([
    'formulaDescriptions' => [
        'brick_quarter' => 'Menghitung pemasangan Bata 1/4 dengan metode Volume Mortar, termasuk strip adukan di sisi kiri dan bawah.',
        'brick_rollag' => 'Menghitung pemasangan Bata Rollag dengan input tingkat adukan dan tingkat bata.'
    ],
    'bricks' => $bricks,
    'cements' => $cements,
    'nats' => $nats ?? [],
    'sands' => $sands,
    'cats' => $cats ?? [],
    'ceramics' => $ceramics ?? [],
]) !!}
</script>
<script src="{{ asset('js/material-calculation-form.js') }}"></script>
<script>
    (function() {
        const dataScript = document.getElementById('materialCalculationFormData');
        const payload = dataScript ? JSON.parse(dataScript.textContent) : null;
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
        function handleAllCheckbox() {
            if (filterAll && filterAll.checked) {
                // Check all other checkboxes except custom
                filterCheckboxes.forEach(checkbox => {
                    if (checkbox === filterAll) return;

                    if (checkbox.value === 'custom') {
                        checkbox.checked = false;
                    } else {
                        checkbox.checked = true;
                    }
                });
            }
        }

        // Function to uncheck "Semua" if any other checkbox is unchecked
        function handleOtherCheckboxes() {
            const allOthersChecked = Array.from(filterCheckboxes).every(checkbox => {
                return checkbox === filterAll || checkbox.checked;
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
        const ceramicFilterSection = document.getElementById('ceramicFilterSection');

        function toggleLayerInputs() {
            const mortarThicknessGroup = document.getElementById('mortarThicknessGroup');
            const layerCountLabel = document.getElementById('layerCountLabel');
            const layerCountUnit = document.getElementById('layerCountUnit');
            const layerCountInputWrapper = document.getElementById('layerCountInputWrapper');

            if (workTypeSelector && layerCountGroup && plasterSidesGroup && skimSidesGroup) {
                if (workTypeSelector.value === 'brick_rollag') {
                    layerCountGroup.style.display = 'block';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
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

                    // Change label from "Tinggi" to "Lebar" for Rollag
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else if (workTypeSelector.value === 'wall_plastering') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'block';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
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
                    skimSidesGroup.style.display = 'block';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    // Restore label to "Tinggi" for Skim Coating
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'painting') {
                    layerCountGroup.style.display = 'block';
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
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
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
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'block';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'block';
                    // Restore label to "Tinggi"
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else if (workTypeSelector.value === 'grout_tile') {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'block';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'none';

                    // Show ceramic dimension inputs for grout_tile
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'block';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'block';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';

                    // Restore label to "Tinggi"
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                } else {
                    layerCountGroup.style.display = 'none';
                    plasterSidesGroup.style.display = 'none';
                    skimSidesGroup.style.display = 'none';
                    if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
                    if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
                    if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
                    if (ceramicFilterSection) ceramicFilterSection.style.display = 'none';
                    if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
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

                // Toggle special inputs (layer count, plaster sides, skim sides)
                toggleLayerInputs();

                // Show/hide material sections based on work type in custom form
                setTimeout(() => {
                    const allSections = document.querySelectorAll('#customMaterialForm .material-section');
                    let brickSec = null;
                    let cementSec = null;
                    let sandSec = null;
                    let catSec = null;
                    let ceramicSec = null;
                    let natSec = null;

                    allSections.forEach(section => {
                        const header = section.querySelector('h4.section-header');
                        if (header) {
                            const headerText = header.textContent.trim();
                            if (headerText === 'Bata') {
                                brickSec = section;
                            } else if (headerText === 'Semen') {
                                cementSec = section;
                            } else if (headerText === 'Pasir') {
                                sandSec = section;
                            } else if (headerText === 'Cat') {
                                catSec = section;
                            } else if (headerText === 'Keramik') {
                                ceramicSec = section;
                            } else if (headerText === 'Nat') {
                                natSec = section;
                            }
                        }
                    });

                    if (selectedWorkType === 'wall_plastering') {
                        // Wall plastering: hide brick, show cement and sand
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'block';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
                    } else if (selectedWorkType === 'skim_coating') {
                        // Skim coating: hide brick and sand, show cement
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'none';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
                    } else if (selectedWorkType === 'painting') {
                        // Painting: hide brick, cement, sand, show cat
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'none';
                        if (sandSec) sandSec.style.display = 'none';
                        if (catSec) catSec.style.display = 'block';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
                    } else if (selectedWorkType === 'floor_screed') {
                        // Floor Screed: hide brick and cat, show cement and sand
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'block';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
                    } else if (selectedWorkType === 'coating_floor') {
                        // Coating Floor: hide brick, sand and cat, show cement
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'none';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
                    } else if (selectedWorkType === 'tile_installation') {
                        // Tile Installation: show cement, sand, ceramic, nat
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'block';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'block';
                        if (natSec) natSec.style.display = 'block';
                    } else if (selectedWorkType === 'grout_tile') {
                        // Grout Tile: show ceramic, nat only
                        if (brickSec) brickSec.style.display = 'none';
                        if (cementSec) cementSec.style.display = 'none';
                        if (sandSec) sandSec.style.display = 'none';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'block';
                        if (natSec) natSec.style.display = 'block';
                    } else {
                        // Brick formulas: show brick, cement, sand, hide cat
                        if (brickSec) brickSec.style.display = 'block';
                        if (cementSec) cementSec.style.display = 'block';
                        if (sandSec) sandSec.style.display = 'block';
                        if (catSec) catSec.style.display = 'none';
                        if (ceramicSec) ceramicSec.style.display = 'none';
                        if (natSec) natSec.style.display = 'none';
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

        // Initialize on page load if formula_code exists or work type is selected
        @if(request('formula_code'))
            const workTypeSelect = document.getElementById('workTypeSelector');
            if(workTypeSelect) {
                // Set the value first
                workTypeSelect.value = '{{ request('formula_code') }}';
                // Then trigger change
                const event = new Event('change');
                workTypeSelect.dispatchEvent(event);
            }
        @else
            // Force initial check even without formula_code
            if (workTypeSelector && workTypeSelector.value) {
                handleWorkTypeChange();
            }
        @endif
    })();
</script>
@endpush