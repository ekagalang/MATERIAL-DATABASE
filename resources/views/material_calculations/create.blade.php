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

        {{-- WORK TYPE --}}
        <div class="form-group">
            <label>Item Pekerjaan</label>
            <div class="input-wrapper">
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
        </div>

        <div id="inputFormContainer" style="{{ request('formula_code') ? 'display:block;' : 'display:none;' }}">
            <div id="brickForm" class="work-type-form">
                
                {{-- DIMENSI --}}
                <div class="dimensions-container">
                    <div class="dimension-group">
                        <label>Panjang</label>
                        <div class="input-with-unit">
                            <input type="number" name="wall_length" id="wallLength" step="0.01" min="0.01" 
                                value="{{ request('wall_length') }}" 
                                {{ request('wall_length') ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <span class="operator">x</span>
                    <div class="dimension-group">
                        <label id="wallHeightLabel">Tinggi</label>
                        <div class="input-with-unit">
                            <input type="number" name="wall_height" id="wallHeight" step="0.01" min="0.01"
                                value="{{ request('wall_height') }}"
                                {{ request('wall_height') ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <div class="dimension-group">
                        <label>Tebal</label>
                        <div class="input-with-unit">
                            <input type="number" name="mortar_thickness" step="0.1" min="0.1"
                                value="{{ request('mortar_thickness', 2) }}" 
                                {{ request('mortar_thickness') ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                            <span class="unit">cm</span>
                        </div>
                    </div>
                    {{-- INPUT TINGKAT UNTUK ROLLAG --}}
                    <div class="dimension-group" id="layerCountGroup" style="display: none;">
                        <label>Tingkat</label>
                        <div class="input-with-unit" style="background-color: #fffbeb; border-color: #fcd34d;">
                            <input type="number" name="layer_count" step="1" min="1" value="{{ request('layer_count') ?? 1 }}">
                            <span class="unit" style="background-color: #fef3c7;">Lapis</span>
                        </div>
                    </div>
                </div>

                {{-- FILTER CHECKBOX (MULTIPLE SELECTION) --}}
                <div class="form-group">
                    <label>+ Filter by:</label>
                    <div class="input-wrapper">
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
                </div>

                {{-- CUSTOM FORM --}}
                <div id="customMaterialForm" style="display:none;">
                    
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
                            {{-- TAMPILAN NORMAL (DROPDOWN) --}}
                            <div class="form-group">
                                <label>Bata :</label>
                                <div class="input-wrapper">
                                    <select name="brick_id" class="form-select select-green">
                                        <option value="">-- Pilih Bata --</option>
                                        @foreach($bricks as $brick)
                                            <option value="{{ $brick->id }}">
                                                {{ $brick->brand }} - {{ $brick->type }} ({{ $brick->dimension_length }}x{{ $brick->dimension_width }}x{{ $brick->dimension_height }} cm) - Rp {{ number_format($brick->price_per_piece) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
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
                </div>
            </div>
        </div>

        <div class="button-actions">
            <a href="{{ route('price-analysis.index') }}" class="btn btn-cancel">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
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
        max-width: 700px !important; 
        width: 100% !important;
        background: #fff; 
        padding: 24px; 
        border-radius: 8px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        margin: 10px auto; 
    }
    
    .form-title { 
        font-size: 18px; 
        font-weight: 700; 
        color: #1e293b; 
        margin-bottom: 20px; 
        padding-bottom: 12px; 
        border-bottom: 1px solid #e2e8f0; 
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
        color: #1e293b; 
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
        color: #1e293b; 
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
    
    /* Dimensions container */
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
        cursor: not-allowed;
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
        background: #f8fafc; 
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
        color: #1e293b; 
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
        color: #1e293b;
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
        .form-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .form-group label {
            flex: unset;
            padding-top: 0;
        }

        .dimensions-container {
            flex-direction: column;
            align-items: stretch;
        }

        .dimension-group {
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
    'sands' => $sands,
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

        // Handle Work Type Change for Layer Inputs (Rollag)
        const workTypeSelector = document.getElementById('workTypeSelector');
        const layerCountGroup = document.getElementById('layerCountGroup');
        const wallHeightLabel = document.getElementById('wallHeightLabel');

        function toggleLayerInputs() {
            if (workTypeSelector && layerCountGroup) {
                if (workTypeSelector.value === 'brick_rollag') {
                    layerCountGroup.style.display = 'block';
                    // Change label from "Tinggi" to "Lebar" for Rollag
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Lebar';
                    }
                } else {
                    layerCountGroup.style.display = 'none';
                    // Restore label to "Tinggi" for other formulas
                    if (wallHeightLabel) {
                        wallHeightLabel.textContent = 'Tinggi';
                    }
                }
            }
        }

        if (workTypeSelector) {
            workTypeSelector.addEventListener('change', toggleLayerInputs);
            // Run on init
            toggleLayerInputs();
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

        @if(request('formula_code'))
            const workTypeSelect = document.getElementById('workTypeSelector');
            if(workTypeSelect) {
                const event = new Event('change');
                workTypeSelect.dispatchEvent(event);
            }
        @endif
    })();
</script>
@endpush