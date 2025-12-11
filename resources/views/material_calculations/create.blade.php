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
                            <input type="number" name="wall_length" step="0.01" min="0.01" 
                                value="{{ request('wall_length') }}" 
                                {{ request('wall_length') ? 'readonly style=background-color:#f1f5f9;' : '' }}>
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <span class="operator">x</span>
                    <div class="dimension-group">
                        <label>Tinggi</label>
                        <div class="input-with-unit">
                            <input type="number" name="wall_height" step="0.01" min="0.01" 
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
                </div>

                {{-- FILTER RADIO --}}
                <div class="form-group">
                    <label>+ Filter by:</label>
                    <div class="input-wrapper">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="price_filter" id="filter_cheapest" value="cheapest" autocomplete="off">
                            <label class="btn btn-outline-success btn-sm" for="filter_cheapest">Termurah</label>
                        
                            <input type="radio" class="btn-check" name="price_filter" id="filter_expensive" value="expensive" autocomplete="off">
                            <label class="btn btn-outline-danger btn-sm" for="filter_expensive">Termahal</label>
                        
                            <input type="radio" class="btn-check" name="price_filter" id="filter_custom" value="custom" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm" for="filter_custom">Custom</label>
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
                                <label>Merek :</label>
                                <div class="input-wrapper">
                                    <select id="customBrickBrand" name="custom_brick_brand" class="select-green">
                                        <option value="">-- Pilih Merk --</option>
                                        @foreach($bricks->groupBy('brand')->keys() as $brand)
                                            <option value="{{ $brand }}">{{ $brand }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Dimensi :</label>
                                <div class="input-wrapper">
                                    <select id="customBrickDimension" name="brick_id" class="select-blue">
                                        <option value="">-- Pilih Dimensi --</option>
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
    'formulaDescriptions' => $formulaDescriptions,
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

        const filterRadios = document.querySelectorAll('input[name="price_filter"]');
        const customForm = document.getElementById('customMaterialForm');
        
        function toggleCustomForm() {
            const selected = document.querySelector('input[name="price_filter"]:checked');
            if (selected && selected.value === 'custom') {
                customForm.style.display = 'block';
            } else {
                customForm.style.display = 'none';
            }
        }

        toggleCustomForm();
        filterRadios.forEach(radio => radio.addEventListener('change', toggleCustomForm));

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