@extends('layouts.app')

@php
    $formulaDescriptions = [];
    foreach ($availableFormulas as $formula) {
        $formulaDescriptions[$formula['code']] = $formula['description'] ?? '';
    }

    $calculationParams = $materialCalculation->calculation_params ?? [];
    $currentWorkType = $calculationParams['work_type'] ?? ($availableFormulas[0]['code'] ?? '');
    
    // Existing filters
    $existingFilters = $calculationParams['price_filters'] ?? [$calculationParams['price_filter'] ?? 'best'];
@endphp

@section('content')
<div class="card">
    <h3 class="form-title"><i class="bi bi-pencil-square text-warning"></i> Edit Perhitungan</h3>

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

    <form action="{{ route('material-calculations.update', $materialCalculation) }}" method="POST" id="calculationForm">
        @csrf
        @method('PUT')

        {{-- WORK TYPE --}}
        <div class="form-group">
            <label>Item Pekerjaan</label>
            <div class="input-wrapper">
                <select id="workTypeSelector" name="work_type_select" required>
                    <option value="">-- Pilih Item Pekerjaan --</option>
                    @foreach($availableFormulas as $formula)
                        <option value="{{ $formula['code'] }}" {{ old('work_type', $currentWorkType) == $formula['code'] ? 'selected' : '' }}>
                            {{ $formula['name'] }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="work_type" value="{{ old('work_type', $currentWorkType) }}">
            </div>
        </div>

        <div id="inputFormContainer" style="display:block;">
            <div id="brickForm" class="work-type-form">
                
                {{-- DIMENSI --}}
                <div class="dimensions-container">
                    <div class="dimension-group">
                        <label>Panjang</label>
                        <div class="input-with-unit">
                            <input type="number" name="wall_length" step="0.01" min="0.01" 
                                value="{{ old('wall_length', $materialCalculation->wall_length) }}">
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <span class="operator" id="wallHeightOperator">x</span>
                    <div class="dimension-group" id="wallHeightGroup">
                        <label id="wallHeightLabel">Tinggi</label>
                        <div class="input-with-unit">
                            <input type="number" name="wall_height" step="0.01" min="0.01" 
                                value="{{ old('wall_height', $materialCalculation->wall_height) }}">
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <div class="dimension-group">
                        <label>Tebal</label>
                        <div class="input-with-unit">
                            <input type="number" name="mortar_thickness" step="0.1" min="0.1"
                                value="{{ old('mortar_thickness', $materialCalculation->mortar_thickness) }}">
                            <span class="unit">cm</span>
                        </div>
                    </div>
                </div>

                {{-- INPUT TAMBAHAN (ROLLAG/PLESTER/ACI) --}}
                <div class="dimensions-container" style="margin-top: 12px;">
                    {{-- INPUT TINGKAT UNTUK ROLLAG --}}
                    <div class="dimension-group" id="layerCountGroup" style="display: none;">
                        <label>Tingkat</label>
                        <div class="input-with-unit" style="background-color: #fffbeb; border-color: #fcd34d;">
                            <input type="number" name="layer_count" step="1" min="1" value="{{ old('layer_count', $calculationParams['layer_count'] ?? 1) }}">
                            <span class="unit" style="background-color: #fef3c7;">Lapis</span>
                        </div>
                    </div>

                    {{-- INPUT SISI PLESTERAN --}}
                    <div class="dimension-group" id="plasterSidesGroup" style="display: none;">
                        <label>Sisi Plesteran</label>
                        <div class="input-with-unit" style="background-color: #e0f2fe; border-color: #7dd3fc;">
                            <input type="number" name="plaster_sides" step="1" min="1" value="{{ old('plaster_sides', $calculationParams['plaster_sides'] ?? 1) }}">
                            <span class="unit" style="background-color: #bae6fd;">Sisi</span>
                        </div>
                    </div>

                    {{-- INPUT SISI ACI --}}
                    <div class="dimension-group" id="skimSidesGroup" style="display: none;">
                        <label>Sisi Aci</label>
                        <div class="input-with-unit" style="background-color: #e0e7ff; border-color: #a5b4fc;">
                            <input type="number" name="skim_sides" step="1" min="1" value="{{ old('skim_sides', $calculationParams['skim_sides'] ?? 1) }}">
                            <span class="unit" style="background-color: #c7d2fe;">Sisi</span>
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
                                        <i class="bi bi-collection me-2 text-secondary"></i>Semua
                                    </span>
                                    <span class="tickbox-desc">Menampilkan semua kombinasi material</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_best" value="best" {{ in_array('best', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_best">
                                    <span class="tickbox-title">
                                        <i class="bi bi-star-fill me-2 text-primary"></i>TerBAIK
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi Most Recommended (Custom Setting)</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_common" value="common" {{ in_array('common', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_common">
                                    <span class="tickbox-title">
                                        <i class="bi bi-people-fill me-2 text-info"></i>TerUMUM
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi yang paling sering dihitung user</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_cheapest" value="cheapest" {{ in_array('cheapest', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_cheapest">
                                    <span class="tickbox-title">
                                        <i class="bi bi-cash-coin me-2 text-success"></i>TerMURAH
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga termurah</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_medium" value="medium" {{ in_array('medium', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_medium">
                                    <span class="tickbox-title">
                                        <i class="bi bi-graph-up me-2 text-warning"></i>TerSEDANG
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan harga menengah</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_expensive" value="expensive" {{ in_array('expensive', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_expensive">
                                    <span class="tickbox-title">
                                        <i class="bi bi-gem me-2 text-danger"></i>TerMAHAL
                                    </span>
                                    <span class="tickbox-desc">3 kombinasi dengan total harga termahal</span>
                                </label>
                            </div>

                            <div class="tickbox-item">
                                <input type="checkbox" name="price_filters[]" id="filter_custom" value="custom" {{ in_array('custom', $existingFilters) ? 'checked' : '' }}>
                                <label for="filter_custom">
                                    <span class="tickbox-title">
                                        <i class="bi bi-sliders me-2 text-dark"></i>Custom
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
                        <div class="form-group">
                            <label>Merek :</label>
                            <div class="input-wrapper">
                                <select id="customBrickBrand" name="custom_brick_brand" class="select-green">
                                    <option value="">-- Pilih Merk --</option>
                                    @foreach($bricks->groupBy('brand')->keys() as $brand)
                                        <option value="{{ $brand }}" {{ (isset($materialCalculation->brick) && $materialCalculation->brick->brand == $brand) ? 'selected' : '' }}>{{ $brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Dimensi :</label>
                            <div class="input-wrapper">
                                <select id="customBrickDimension" name="brick_id" class="select-blue">
                                    <option value="">-- Pilih Dimensi --</option>
                                    @if(isset($materialCalculation->brick))
                                        <option value="{{ $materialCalculation->brick_id }}" selected>
                                            {{ $materialCalculation->brick->dimension_length }} × {{ $materialCalculation->brick->dimension_width }} × {{ $materialCalculation->brick->dimension_height }} cm
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 2. SEMEN SECTION --}}
                    <div class="material-section">
                        <h4 class="section-header">Semen</h4>
                        <div class="form-group">
                            <label>Jenis :</label>
                            <div class="input-wrapper">
                                <select id="customCementType" name="custom_cement_type" class="select-pink">
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach($cements->groupBy('cement_name')->keys() as $type)
                                        <option value="{{ $type }}" {{ (isset($materialCalculation->cement) && $materialCalculation->cement->cement_name == $type) ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Merek :</label>
                            <div class="input-wrapper">
                                <select id="customCementBrand" name="cement_id" class="select-orange">
                                    <option value="">-- Pilih Merk --</option>
                                    @if(isset($materialCalculation->cement))
                                        <option value="{{ $materialCalculation->cement_id }}" selected>
                                            {{ $materialCalculation->cement->brand }} ({{ $materialCalculation->cement->package_weight_net }}kg)
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- 3. PASIR SECTION --}}
                    <div class="material-section">
                        <h4 class="section-header">Pasir</h4>
                        <div class="form-group">
                            <label>Jenis :</label>
                            <div class="input-wrapper">
                                <select id="customSandType" name="custom_sand_type" class="select-gray">
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach($sands->groupBy('sand_name')->keys() as $type)
                                        <option value="{{ $type }}" {{ (isset($materialCalculation->sand) && $materialCalculation->sand->sand_name == $type) ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Merek :</label>
                            <div class="input-wrapper">
                                <select id="customSandBrand" name="custom_sand_brand" class="select-gray">
                                    <option value="">-- Pilih Merk --</option>
                                    @if(isset($materialCalculation->sand))
                                        <option value="{{ $materialCalculation->sand->brand }}" selected>{{ $materialCalculation->sand->brand }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Kemasan :</label>
                            <div class="input-wrapper">
                                <select id="customSandPackage" name="sand_id" class="select-gray-light">
                                    <option value="">-- Pilih Kemasan --</option>
                                    @if(isset($materialCalculation->sand))
                                        <option value="{{ $materialCalculation->sand_id }}" selected>{{ $materialCalculation->sand->package_volume }} M3</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="button-actions">
            <a href="{{ route('material-calculations.show', $materialCalculation) }}" class="btn btn-cancel">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-submit">
                <i class="bi bi-save"></i> Perbarui Perhitungan
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
        color: inherit; 
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
    
    .alert-info {
        background: #dbeafe;
        border: 1px solid #93c5fd;
        color: #1e40af;
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
        color: #891313;
    }

    .tickbox-item:has(input[type="checkbox"]:checked) {
        background: #fff1f2;
        border-color: #891313;
        box-shadow: 0 2px 8px rgba(137, 19, 19, 0.15);
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
                return checkbox === filterAll || (checkbox.value !== 'custom' && checkbox.checked);
            });

            if (filterAll && !allOthersChecked) {
                filterAll.checked = false;
            }
        }

        // Initialize form visibility on page load
        toggleCustomForm();

        // Trigger change on load for custom selects to populate them if editing custom
        @if(in_array('custom', $existingFilters))
            const brickBrand = document.getElementById('customBrickBrand');
            if(brickBrand) brickBrand.dispatchEvent(new Event('change'));
            
            const cementType = document.getElementById('customCementType');
            if(cementType) cementType.dispatchEvent(new Event('change'));
            
            const sandType = document.getElementById('customSandType');
            if(sandType) sandType.dispatchEvent(new Event('change'));
        @endif

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

        // Sync workTypeSelector with hidden work_type input
        const workTypeSelect = document.getElementById('workTypeSelector');
        const workTypeHidden = document.querySelector('input[name="work_type"]');
        const layerCountGroup = document.getElementById('layerCountGroup');
        const plasterSidesGroup = document.getElementById('plasterSidesGroup');
        const skimSidesGroup = document.getElementById('skimSidesGroup');
        const wallHeightGroup = document.getElementById('wallHeightGroup');
        const wallHeightOperator = document.getElementById('wallHeightOperator');
        const wallHeightInput = document.querySelector('input[name="wall_height"]');
        const wallHeightDefaultDisplay = wallHeightGroup ? getComputedStyle(wallHeightGroup).display : 'flex';
        const wallHeightOperatorDisplay = wallHeightOperator ? getComputedStyle(wallHeightOperator).display : 'inline-block';
        // Note: Label element might need an ID in HTML first, but we can try to find it relative to input if not.
        // Assuming label is generic for now or finding by text content logic is complex without IDs.
        
        function handleWorkTypeChange() {
            if (!workTypeSelect) return;
            
            const val = workTypeSelect.value;
            const isRollag = val === 'brick_rollag';
            if (workTypeHidden) workTypeHidden.value = val;

            // Hide all first
            if(layerCountGroup) layerCountGroup.style.display = 'none';
            if(plasterSidesGroup) plasterSidesGroup.style.display = 'none';
            if(skimSidesGroup) skimSidesGroup.style.display = 'none';
            if (wallHeightGroup) wallHeightGroup.style.display = isRollag ? 'none' : wallHeightDefaultDisplay;
            if (wallHeightOperator) wallHeightOperator.style.display = isRollag ? 'none' : wallHeightOperatorDisplay;
            if (wallHeightInput) {
                wallHeightInput.required = !isRollag;
                wallHeightInput.disabled = isRollag;
            }

            if (val === 'brick_rollag') {
                if(layerCountGroup) layerCountGroup.style.display = 'flex'; // dimension-group is flex column or flex
            } else if (val === 'wall_plastering') {
                if(plasterSidesGroup) plasterSidesGroup.style.display = 'flex';
            } else if (val === 'skim_coating') {
                if(skimSidesGroup) skimSidesGroup.style.display = 'flex';
            }
        }

        if(workTypeSelect) {
            workTypeSelect.addEventListener('change', handleWorkTypeChange);
            // Run on init
            handleWorkTypeChange();
        }
    })();
</script>
@endpush
