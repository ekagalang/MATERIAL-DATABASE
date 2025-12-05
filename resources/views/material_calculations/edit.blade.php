@extends('layouts.app')

@php
    $formulaDescriptions = [];
    foreach ($availableFormulas as $formula) {
        $formulaDescriptions[$formula['code']] = $formula['description'] ?? '';
    }

    $calculationParams = $materialCalculation->calculation_params ?? [];
    $currentWorkType = $calculationParams['work_type'] ?? ($availableFormulas[0]['code'] ?? '');
@endphp

@section('content')
<div class="card">
    <h3 class="form-title"><i class="fas fa-edit text-warning"></i> Edit Perhitungan</h3>
    <p class="text-muted" style="margin-top:-6px;">{{ $materialCalculation->project_name ?: 'Perhitungan Tanpa Nama' }}</p>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div>
                <strong>Terdapat kesalahan pada input:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{--
    <form action="{{ route('material-calculations.update', $materialCalculation) }}" method="POST" id="calculatorForm">
        @csrf
        @method('PUT')

        <div class="section-title">Dimensi Dinding</div>
        <div class="dimensions-container">
            <div class="dimension-group">
                <label>Panjang</label>
                <div class="input-with-unit">
                    <input
                        type="number"
                        name="wall_length"
                        id="wall_length"
                        value="{{ old('wall_length', $materialCalculation->wall_length) }}"
                        step="0.01"
                        min="0.01"
                    >
                    <span class="unit">M</span>
                </div>
            </div>
            <span class="operator">x</span>
            <div class="dimension-group">
                <label>Tinggi</label>
                <div class="input-with-unit">
                    <input
                        type="number"
                        name="wall_height"
                        id="wall_height"
                        value="{{ old('wall_height', $materialCalculation->wall_height) }}"
                        step="0.01"
                        min="0.01"
                    >
                    <span class="unit">M</span>
                </div>
            </div>
            <span class="operator">=</span>
            <div class="dimension-group">
                <label>Luas</label>
                <div class="input-with-unit">
                    <input
                        type="text"
                        id="wall_area_display"
                        readonly
                        style="background:#f8fafc;"
                    >
                    <span class="unit">M2</span>
                </div>
            </div>
        </div>

        <div class="section-title">Jenis Pemasangan</div>
        <div class="row">
            <label>Jenis</label>
            <div style="flex:1;">
                <select name="installation_type_id" id="installation_type_id" required>
                    @foreach($installationTypes as $type)
                        <option value="{{ $type->id }}" {{ old('installation_type_id', $materialCalculation->installation_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" id="installation_description" style="display:block;margin-top:6px;"></small>
            </div>
        </div>

        <div class="section-title">Adukan</div>
        <div class="row">
            <label>Tebal Adukan (cm)</label>
            <div style="flex:1;"><input type="number" name="mortar_thickness" id="mortar_thickness" value="{{ old('mortar_thickness', 1.0) }}" step="0.1" min="0.1" max="10"></div>
        </div>
        <div class="row">
            <label>Metode Formula</label>
            <div style="flex:1; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="ratio_method" id="ratio_preset" value="preset" {{ old('use_custom_ratio', $materialCalculation->use_custom_ratio) != '1' ? 'checked' : '' }}> <span>Gunakan Formula Preset</span></label>
                <label style="display:flex; align-items:center; gap:6px;"><input type="radio" name="ratio_method" id="ratio_custom" value="custom" {{ old('use_custom_ratio', $materialCalculation->use_custom_ratio) == '1' ? 'checked' : '' }}> <span>Input Rasio Manual</span></label>
                <input type="hidden" name="use_custom_ratio" id="use_custom_ratio" value="{{ old('use_custom_ratio', $materialCalculation->use_custom_ratio ? '1' : '0') }}">
            </div>
        </div>

        <div id="preset_section" style="margin-bottom:18px;">
            <div class="row">
                <label>Formula Preset</label>
                <div style="flex:1;">
                    <select name="mortar_formula_id" id="mortar_formula_id">
                        @foreach($mortarFormulas as $formula)
                            <option value="{{ $formula->id }}" data-cement="{{ $formula->cement_ratio }}" data-sand="{{ $formula->sand_ratio }}" data-water="{{ $formula->water_ratio }}" {{ old('mortar_formula_id', $defaultMortarFormula->id ?? '') == $formula->id ? 'selected' : '' }}>
                                {{ $formula->name }} ({{ $formula->cement_ratio }}:{{ $formula->sand_ratio }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="display:block;margin-top:6px;">Pilih formula campuran yang sudah tersedia</small>
                </div>
            </div>
        </div>

        <div id="custom_section" style="display:none; margin-bottom:18px;">
            <div class="row">
                <label>Rasio Custom</label>
                <div style="flex:1; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                    <input type="number" name="custom_cement_ratio" id="custom_cement_ratio" value="{{ old('custom_cement_ratio', $materialCalculation->custom_cement_ratio ?? 1) }}" step="0.1" min="0.1" placeholder="Semen">
                    <span style="color:#94a3b8;">:</span>
                    <input type="number" name="custom_sand_ratio" id="custom_sand_ratio" value="{{ old('custom_sand_ratio', $materialCalculation->custom_sand_ratio ?? 4) }}" step="0.1" min="0.1" placeholder="Pasir">
                    <span style="color:#94a3b8;">Air</span>
                    <input type="number" name="custom_water_ratio" id="custom_water_ratio" value="{{ old('custom_water_ratio', $materialCalculation->custom_water_ratio ?? 0.5) }}" step="0.1" min="0" placeholder="Air">
                </div>
            </div>
        </div>

        <div class="alert alert-info" style="margin-bottom:18px;">
            <small><i class="fas fa-blender"></i> Rasio yang digunakan: <strong id="ratio_display">1:4</strong></small>
        </div>

        <div class="section-title">Material (opsional)</div>
        <div class="row">
            <label>Bata</label>
            <div style="flex:1;">
                <select name="brick_id" id="brick_id">
                    @foreach($bricks as $brick)
                        <option value="{{ $brick->id }}" data-price="{{ $brick->price_per_piece }}" {{ old('brick_id', $materialCalculation->brick_id ?? $bricks->first()->id) == $brick->id ? 'selected' : '' }}>
                            {{ $brick->brand }} {{ $brick->type }} - Rp {{ number_format($brick->price_per_piece, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <label>Semen</label>
            <div style="flex:1;">
                <select name="cement_id" id="cement_id">
                    @foreach($cements as $cement)
                        <option value="{{ $cement->id }}" data-price="{{ $cement->package_price }}" {{ old('cement_id', $materialCalculation->cement_id ?? $cements->first()->id) == $cement->id ? 'selected' : '' }}>
                            {{ $cement->brand }} {{ $cement->sub_brand }} - Rp {{ number_format($cement->package_price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <label>Pasir</label>
            <div style="flex:1;">
                <select name="sand_id" id="sand_id">
                    @foreach($sands as $sand)
                        <option value="{{ $sand->id }}" data-price="{{ $sand->package_price }}" {{ old('sand_id', $materialCalculation->sand_id ?? $sands->first()->id) == $sand->id ? 'selected' : '' }}>
                            {{ $sand->brand }} {{ $sand->type }} - Rp {{ number_format($sand->package_price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="section-title">Aksi</div>
        <div class="button-actions" style="gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-info" id="btnPreview"><i class="fas fa-eye"></i> Preview Hasil</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update Perhitungan</button>
            <button type="button" class="btn btn-secondary"
                onclick="(function(){const closeBtn = window.parent && window.parent.document ? window.parent.document.getElementById('closeModal') : null; if (closeBtn) { closeBtn.click(); } else { window.location.href='{{ route('material-calculations.log') }}'; }})();">
                <i class="fas fa-times"></i> Batal
            </button>
        </div>

        <div class="card" style="margin-top:24px; padding:16px; background:#f8fafc;">
            <h5 style="margin-top:0; margin-bottom:12px;">Hasil Perhitungan Saat Ini</h5>
            <p style="margin:0; font-size:14px; color:#475569;">Panjang: <strong>{{ $materialCalculation->wall_length }} m</strong> &nbsp; | &nbsp; Tinggi: <strong>{{ $materialCalculation->wall_height }} m</strong> &nbsp; | &nbsp; Luas: <strong>{{ number_format($materialCalculation->wall_area, 2) }} m2</strong></p>
            <p style="margin:6px 0; font-size:14px; color:#475569;">Bata: <strong>{{ number_format($materialCalculation->brick_quantity, 0) }} buah</strong> &nbsp; | &nbsp; Semen {{ $materialCalculation->cement_package_weight ?? 50 }}kg: <strong>{{ number_format($materialCalculation->cement_quantity_sak ?? $materialCalculation->cement_quantity_50kg, 2) }} sak</strong> &nbsp; | &nbsp; Pasir: <strong>{{ number_format($materialCalculation->sand_m3, 4) }} m³</strong></p>
            <div class="alert alert-secondary" style="margin:10px 0 0;">Total Biaya Saat Ini: <strong>Rp {{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</strong></div>
        </div>

        <div id="resultPanel" class="card" style="display:none; margin-top:16px; padding:16px;">
            <h5 style="margin-top:0;" class="text-success"><i class="fas fa-chart-line"></i> Preview Hasil Baru</h5>
            <div id="resultContent"></div>
        </div>
    </form>
    --}}

    <form action="{{ route('material-calculations.update', $materialCalculation) }}" method="POST" id="calculationForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="installation_type_id" value="{{ $materialCalculation->installation_type_id }}">
        <input type="hidden" name="mortar_formula_id" value="{{ $materialCalculation->mortar_formula_id }}">
        <input type="hidden" name="use_custom_ratio" value="{{ $materialCalculation->use_custom_ratio ? 1 : 0 }}">
        <input type="hidden" name="custom_cement_ratio" value="{{ $materialCalculation->custom_cement_ratio }}">
        <input type="hidden" name="custom_sand_ratio" value="{{ $materialCalculation->custom_sand_ratio }}">
        <input type="hidden" name="custom_water_ratio" value="{{ $materialCalculation->custom_water_ratio }}">

        <div class="form-group">
            <label>Item Pekerjaan</label>
            <div class="input-wrapper">
                <select id="workTypeSelector" name="work_type" required>
                    <option value="">-- Pilih Item Pekerjaan --</option>
                    @foreach($availableFormulas as $formula)
                        <option value="{{ $formula['code'] }}"
                            {{ old('work_type', $currentWorkType) == $formula['code'] ? 'selected' : '' }}>
                            {{ $formula['name'] }}
                        </option>
                    @endforeach
                </select>
                <small id="workTypeDescription" class="text-muted"></small>
            </div>
        </div>

        <div id="inputFormContainer" style="display:none;">
            <div id="brickForm" class="work-type-form" style="display:none;">
                <div class="dimensions-container">
                    <div class="dimension-group">
                        <label>Panjang</label>
                        <div class="input-with-unit">
                            <input
                                type="number"
                                id="wallLength"
                                name="wall_length"
                                step="0.01"
                                min="0.01"
                                value="{{ old('wall_length', $materialCalculation->wall_length) }}"
                            >
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <span class="operator">x</span>
                    <div class="dimension-group">
                        <label>Tinggi</label>
                        <div class="input-with-unit">
                            <input
                                type="number"
                                id="wallHeight"
                                name="wall_height"
                                step="0.01"
                                min="0.01"
                                value="{{ old('wall_height', $materialCalculation->wall_height) }}"
                            >
                            <span class="unit">M</span>
                        </div>
                    </div>
                    <span class="operator">=</span>
                    <div class="dimension-group">
                        <label>Luas</label>
                        <div class="input-with-unit">
                            <input
                                type="number"
                                id="wallArea"
                                name="wall_area"
                                readonly
                                value="{{ old('wall_area', $materialCalculation->wall_area) }}"
                            >
                            <span class="unit">M2</span>
                        </div>
                    </div>
                    <div class="dimension-group">
                        <label>Tebal</label>
                        <div class="input-with-unit">
                            <input
                                type="number"
                                name="mortar_thickness"
                                value="{{ old('mortar_thickness', $materialCalculation->mortar_thickness) }}"
                                step="0.1"
                                min="0.1"
                                max="10"
                            >
                            <span class="unit">cm</span>
                        </div>
                    </div>
                </div>

                @php
                    $currentPriceFilter = old('price_filter', $calculationParams['price_filter'] ?? 'cheapest');
                @endphp

                <div class="form-group">
                    <label>+ Filter by:</label>
                    <div class="input-wrapper">
                        <select id="priceFilter" name="price_filter">
                            <option value="cheapest" {{ $currentPriceFilter === 'cheapest' ? 'selected' : '' }}>Termurah</option>
                            <option value="expensive" {{ $currentPriceFilter === 'expensive' ? 'selected' : '' }}>Termahal</option>
                            <option value="custom" {{ $currentPriceFilter === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>
                </div>

                <div id="customMaterialForm" style="display:none;">
                    <div class="material-section">
                        <h4 class="section-header">Bata</h4>
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
                    </div>

                    <div class="material-section">
                        <h4 class="section-header">Semen</h4>
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
                                    <option value="">-- Pilih Merk --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="material-section">
                        <h4 class="section-header">Pasir</h4>
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
                                    <option value="">-- Pilih Kemasan --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="otherForm" class="work-type-form" style="display:none;">
                <div class="alert alert-info" style="margin-top:12px;">
                    <i class="bi bi-info-circle"></i> Form untuk jenis pekerjaan ini akan segera tersedia
                </div>
            </div>
        </div>

        <div class="button-actions">
            <button type="button" class="btn btn-cancel"
                    onclick="(function(){const closeBtn = window.parent && window.parent.document ? window.parent.document.getElementById('closeModal') : null; if (closeBtn) { closeBtn.click(); } else { window.location.href='{{ route('material-calculations.show', $materialCalculation) }}'; }})();">
                <i class="bi bi-x-lg"></i> Batalkan
            </button>
            <button type="submit" class="btn btn-submit">
                <i class="bi bi-check-lg"></i> Simpan Perubahan
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

    .form-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
    .section-title { font-weight: 700; font-size: 16px; color: #1e293b; margin: 24px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #f8fafc; }
    .row { display: flex; align-items: center; gap: 16px; margin-bottom: 18px; }
    .row label { flex: 0 0 180px; font-weight: 600; color: #475569; font-size: 14px; }
    input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #334155; background: #ffffff; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-family: inherit; }
    textarea { resize: vertical; min-height: 90px; }
    input[type=\"text\"]:focus, input[type=\"number\"]:focus, select:focus, textarea:focus { outline: none; border-color: #891313; box-shadow: 0 0 0 4px rgba(137,19,19,0.08); background: #fffbfb; }
    select { cursor: pointer; appearance: none; background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E\"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
    .btn { padding: 11px 20px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: inline-flex; align-items: center; gap: 8px; font-family: inherit; }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .btn-secondary { background: transparent; color: #64748b; border: 1.5px solid #e2e8f0; }
    .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
    .btn-success { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: #fff; border: none; box-shadow: 0 2px 8px rgba(22,163,74,0.3); }
    .btn-success:hover { background: linear-gradient(135deg, #15803d 0%, #166534 100%); box-shadow: 0 4px 12px rgba(22,163,74,0.4); }
    .btn-info { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #fff; }
    .btn-info:hover { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }

    /* Dimensions layout like create view */
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
    .alert { padding: 16px 20px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
    .alert-danger { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 1.5px solid #fca5a5; color: #991b1b; }
    .alert-secondary { background: #e2e8f0; color: #111827; border: 1px solid #cbd5e1; }
    .button-actions { display: flex; justify-content: flex-start; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
    @media (max-width: 768px) { .row { flex-direction: column; align-items: flex-start; gap: 8px; } .row label { flex: unset; } .button-actions { flex-direction: column-reverse; } .btn { width: 100%; justify-content: center; } }
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
    })();
</script>
@endpush
