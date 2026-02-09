@extends('layouts.app')

@section('title', 'Trace Perhitungan Material')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-0">Trace Perhitungan Material Step-by-Step</h1>
            <p class="text-muted">Lihat setiap langkah perhitungan seperti di Excel</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">          
            <div class="card-body p-5">
                <form id="traceForm">
                    <!-- Section 1: Jenis Pekerjaan -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px; border: 3px solid #0d6efd;">
                                <strong class="fs-5">1</strong>
                            </div>
                            <h5 class="mb-0 ms-3 fw-bold">Jenis Pekerjaan</h5>
                        </div>
                        
                        <div class="ps-5">
                            <select class="form-select form-select-lg rounded-3 shadow-sm" id="formulaSelector" name="formula_code" required>
                                <option value="">-- Pilih Jenis Pekerjaan --</option>
                                @foreach($availableFormulas as $formula)
                                    <option value="{{ $formula['code'] }}" {{ $loop->first ? 'selected' : '' }}>
                                        {{ $formula['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Setiap jenis pekerjaan memiliki rumus perhitungan yang berbeda.
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary opacity-25">

                    <!-- Section 2: Dimensi Pekerjaan (Dengan suffix di dalam input) -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px; border: 3px solid #0d6efd;">
                                <strong class="fs-5">2</strong>
                            </div>
                            <h5 class="mb-0 ms-3 fw-bold">Dimensi Pekerjaan</h5>
                        </div>
                        
                        <div class="ps-5">
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label fw-semibold">Panjang Dinding</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5" 
                                            name="wall_length" value="6.2" step="0.01" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            meter
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-lg-3 col-md-6" id="wallHeightGroup">
                                    <label class="form-label fw-semibold">Tinggi Dinding</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5" 
                                            name="wall_height" value="3.0" step="0.01" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            meter
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-lg-3 col-md-6" id="mortarThicknessGroup">
                                    <label class="form-label fw-semibold">Tebal Adukan</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="mortar_thickness" value="1.0" step="0.01">
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            cm
                                        </span>
                                    </div>
                                </div>
                                
                                {{-- Input Tingkat untuk Rollag --}}
                                <div class="col-lg-3 col-md-6" id="layerCountGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Tingkat</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="layer_count" value="1" step="1" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            lapis
                                        </span>
                                    </div>
                                </div>

                                {{-- Input Sisi Plesteran untuk Wall Plastering --}}
                                <div class="col-lg-3 col-md-6" id="plasterSidesGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Sisi Plesteran</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="plaster_sides" value="1" step="1" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            sisi
                                        </span>
                                    </div>
                                </div>

                                {{-- Input Sisi Aci untuk Skim Coating --}}
                                <div class="col-lg-3 col-md-6" id="skimSidesGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Sisi Aci</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="skim_sides" value="1" step="1" required>
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            sisi
                                        </span>
                                    </div>
                                </div>

                                {{-- Input Tebal Nat untuk Tile Installation --}}
                                <div class="col-lg-3 col-md-6" id="groutThicknessGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Tebal Nat</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="grout_thickness" value="3" step="0.1">
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            mm
                                        </span>
                                    </div>
                                </div>

                                {{-- Input Panjang Keramik untuk Grout Tile --}}
                                <div class="col-lg-3 col-md-6" id="ceramicLengthGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Panjang Keramik</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="ceramic_length" value="30" step="0.1" min="1">
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            cm
                                        </span>
                                    </div>
                                </div>

                                {{-- Input Lebar Keramik untuk Grout Tile --}}
                                <div class="col-lg-3 col-md-6" id="ceramicWidthGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Lebar Keramik</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="ceramic_width" value="30" step="0.1" min="1">
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            cm
                                        </span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6" id="ceramicThicknessGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Tebal Keramik</label>
                                    <div class="position-relative">
                                        <input type="text" inputmode="decimal" class="form-control form-control-lg rounded-3 shadow-sm pe-5"
                                            name="ceramic_thickness" value="8" step="0.1" min="0.1">
                                        <span class="position-absolute end-0 top-50 translate-middle-y pe-3 text-muted fw-medium">
                                            mm
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary opacity-25">

                    <!-- Section 3: Material yang Digunakan -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; min-width: 40px; border: 3px solid #0d6efd;">
                                <strong class="fs-5">3</strong>
                            </div>
                            <h5 class="mb-0 ms-3 fw-bold">Material yang Digunakan</h5>
                        </div>
                        
                        <div class="ps-5">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Bata</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="brick_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @foreach($bricks as $brick)
                                            <option value="{{ $brick->id }}">
                                                {{ $brick->brand }} ({{ $brick->dimension_length }}×{{ $brick->dimension_width }}×{{ $brick->dimension_height }} cm)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Semen</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="cement_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @foreach($cements as $cement)
                                            <option value="{{ $cement->id }}">
                                                {{ $cement->cement_name }} - {{ $cement->brand }} ({{ $cement->package_weight_net }} kg)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pasir</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="sand_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @foreach($sands as $sand)
                                            <option value="{{ $sand->id }}">
                                                {{ $sand->sand_name }} - {{ $sand->brand }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12" id="natSection" style="display: none;">
                                    <label class="form-label fw-semibold">Nat</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="nat_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @if(isset($nats))
                                            @foreach($nats as $nat)
                                                <option value="{{ $nat->id }}">
                                                    {{ $nat->nat_name ?? $nat->brand }} ({{ $nat->package_weight_net }} kg) - @currency($nat->package_price)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12" id="catSection" style="display: none;">
                                    <label class="form-label fw-semibold">Cat</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="cat_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @if(isset($cats))
                                            @foreach($cats as $cat)
                                                <option value="{{ $cat->id }}">
                                                    {{ $cat->cat_name }} - {{ $cat->brand }} ({{ $cat->package_weight_net }} kg)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12" id="ceramicSection" style="display: none;">
                                    <label class="form-label fw-semibold">Keramik</label>
                                    <select class="form-select form-select-lg rounded-3 shadow-sm" name="ceramic_id">
                                        <option value="">-- Gunakan Default --</option>
                                        @if(isset($ceramics))
                                            @foreach($ceramics as $ceramic)
                                                <option value="{{ $ceramic->id }}" data-length="{{ $ceramic->dimension_length }}"
                                                    data-width="{{ $ceramic->dimension_width }}"
                                                    data-thickness="{{ $ceramic->dimension_thickness }}">
                                                    {{ $ceramic->brand }} - {{ $ceramic->color }} ({{ $ceramic->dimension_length }}×{{ $ceramic->dimension_width }} cm, {{ $ceramic->pieces_per_package }} pcs/dus)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <div class="form-text text-muted mt-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Kosongkan pilihan untuk menggunakan material default dari database.
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs -->
                    <input type="hidden" name="installation_type_id" value="{{ $installationTypes->first()->id ?? 1 }}">
                    <input type="hidden" name="mortar_formula_id" value="{{ $defaultMortarFormula->id ?? ($mortarFormulas->first()->id ?? 1) }}">

                    <!-- Submit Button -->
                    <div class="text-center pt-4 mt-5 border-top border-secondary">
                        <button type="submit" class="custom-red-button">
                            <i class="bi bi-calculator me-2"></i>
                            Mulai Trace Perhitungan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Container -->
    <div id="resultsContainer" style="display: none;">
        <div id="traceContent"></div>
    </div>
</div>

<script>
function syncCeramicDimensionsFromSelection(force = false) {
    const formulaSelector = document.getElementById('formulaSelector');
    if (!formulaSelector || formulaSelector.value !== 'grout_tile') {
        return;
    }

    const form = document.getElementById('traceForm');
    const ceramicSelect = form?.querySelector('select[name="ceramic_id"]');
    if (!ceramicSelect || !ceramicSelect.value) {
        return;
    }

    const selectedOption = ceramicSelect.options[ceramicSelect.selectedIndex];
    if (!selectedOption) {
        return;
    }

    [
        ['ceramic_length', 'length'],
        ['ceramic_width', 'width'],
        ['ceramic_thickness', 'thickness'],
    ].forEach(([fieldName, dataKey]) => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        const optionValue = selectedOption.dataset[dataKey];
        if (!field || !optionValue) {
            return;
        }

        const currentValue = parseFloat(field.value);
        const shouldSet = force || !field.value || Number.isNaN(currentValue) || currentValue <= 0;
        if (!shouldSet) {
            return;
        }

        field.value = optionValue;
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

let isApplyingQueryParams = false;

const ceramicSelectForSync = document.querySelector('select[name="ceramic_id"]');
if (ceramicSelectForSync) {
    ceramicSelectForSync.addEventListener('change', function() {
        syncCeramicDimensionsFromSelection(!isApplyingQueryParams);
    });
}

document.getElementById('traceForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    syncCeramicDimensionsFromSelection(false);

    const formData = new FormData(this);
    const params = Object.fromEntries(formData.entries());

    // Selalu gunakan strip tambahan di sisi kiri & bawah
    params.has_additional_layer = true;

    // Remove parameter yang tidak diperlukan untuk formula painting
    if (params.formula_code === 'painting') {
        // Hapus mortar_thickness jika kosong atau 0
        if (!params.mortar_thickness || params.mortar_thickness === '0' || params.mortar_thickness === 0) {
            delete params.mortar_thickness;
        }
        // Set default mortar_thickness ke 1 jika masih ada
        if (params.mortar_thickness) {
            params.mortar_thickness = 1;
        }
    }

    // Show loading
    document.getElementById('resultsContainer').style.display = 'block';
    document.getElementById('traceContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div><p class="mt-2">Calculating...</p></div>';

    try {
        const response = await fetch('/api/material-calculator/trace', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(params)
        });

        const data = await response.json();

        if (data.success) {
            renderTrace(data.data, 'traceContent');
        } else {
            console.error('Validation Error:', data);
            let errorMsg = 'Error: ' + (data.message || 'Validation failed');

            // Tampilkan detail error jika ada
            if (data.errors) {
                errorMsg += '\n\nDetail:\n';
                for (const [field, messages] of Object.entries(data.errors)) {
                    errorMsg += `- ${field}: ${messages.join(', ')}\n`;
                }
            }

            // Tampilkan di console juga
            console.log('Params sent:', Object.fromEntries(new FormData(this).entries()));

            window.showToast(errorMsg, 'error');
            document.getElementById('traceContent').innerHTML = `
                <div class="alert alert-danger">
                    <h5>Validation Error</h5>
                    <p>${data.message}</p>
                    ${data.errors ? '<pre>' + JSON.stringify(data.errors, null, 2) + '</pre>' : ''}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        const message = 'Terjadi kesalahan saat melakukan perhitungan';
        window.showToast(message, 'error');
    }
});

document.getElementById('formulaSelector').addEventListener('change', function() {
    const layerCountGroup = document.getElementById('layerCountGroup');
    const plasterSidesGroup = document.getElementById('plasterSidesGroup');
    const skimSidesGroup = document.getElementById('skimSidesGroup');
    const groutThicknessGroup = document.getElementById('groutThicknessGroup');
    const ceramicLengthGroup = document.getElementById('ceramicLengthGroup');
    const ceramicWidthGroup = document.getElementById('ceramicWidthGroup');
    const ceramicThicknessGroup = document.getElementById('ceramicThicknessGroup');
    const mortarThicknessGroup = document.getElementById('mortarThicknessGroup');
    const catSection = document.getElementById('catSection');
    const ceramicSection = document.getElementById('ceramicSection');
    const natSection = document.getElementById('natSection');
    const wallHeightGroup = document.getElementById('wallHeightGroup');
    const wallHeightInput = document.querySelector('input[name="wall_height"]');
    const wallHeightDefaultDisplay = wallHeightGroup ? getComputedStyle(wallHeightGroup).display : 'block';

    // Get material sections
    const brickSelect = document.querySelector('select[name="brick_id"]');
    const brickSection = brickSelect?.closest('.col-12');
    const cementSelect = document.querySelector('select[name="cement_id"]');
    const cementSection = cementSelect?.closest('.col-md-6') || cementSelect?.closest('.col-12');
    const sandSelect = document.querySelector('select[name="sand_id"]');
    const sandSection = sandSelect?.closest('.col-md-6') || sandSelect?.closest('.col-12');
    
    // Explicitly set brick section visibility logic
    if (this.value === 'brick_full' || this.value === 'brick_half' || this.value === 'brick_quarter' || this.value === 'brick_rollag') {
        if (brickSection) brickSection.style.display = 'block';
        if (brickSelect) brickSelect.disabled = false;
    } else {
        if (brickSection) brickSection.style.display = 'none';
        if (brickSelect) {
            brickSelect.disabled = true;
            brickSelect.value = '';
        }
    }

    if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'none';

    if (this.value === 'brick_rollag') {
        if (wallHeightGroup) wallHeightGroup.style.display = 'none';
        if (wallHeightInput) {
            wallHeightInput.required = false;
            wallHeightInput.disabled = true;
        }
        layerCountGroup.style.display = 'block';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'block';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    } else if (this.value === 'wall_plastering') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'block';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'block';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    } else if (this.value === 'skim_coating') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'block';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'none';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    } else if (this.value === 'painting') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'block';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'none';
        if (cementSection) cementSection.style.display = 'none';
        if (sandSection) sandSection.style.display = 'none';
        if (catSection) catSection.style.display = 'block';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    } else if (this.value === 'floor_screed' || this.value === 'coating_floor') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'block';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    } else if (this.value === 'tile_installation') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'block';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'block';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'block';
        if (natSection) natSection.style.display = 'block';
    } else if (this.value === 'grout_tile') {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'block';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'block';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'block';
        if (ceramicThicknessGroup) ceramicThicknessGroup.style.display = 'block';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'none';
        if (cementSection) cementSection.style.display = 'none';
        if (sandSection) sandSection.style.display = 'none';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'block';
        if (natSection) natSection.style.display = 'block';
    } else {
        if (wallHeightGroup) wallHeightGroup.style.display = wallHeightDefaultDisplay;
        if (wallHeightInput) {
            wallHeightInput.required = true;
            wallHeightInput.disabled = false;
        }
        layerCountGroup.style.display = 'none';
        plasterSidesGroup.style.display = 'none';
        skimSidesGroup.style.display = 'none';
        if (groutThicknessGroup) groutThicknessGroup.style.display = 'none';
        if (ceramicLengthGroup) ceramicLengthGroup.style.display = 'none';
        if (ceramicWidthGroup) ceramicWidthGroup.style.display = 'none';
        if (mortarThicknessGroup) mortarThicknessGroup.style.display = 'block';
        if (cementSection) cementSection.style.display = 'block';
        if (sandSection) sandSection.style.display = 'block';
        if (catSection) catSection.style.display = 'none';
        if (ceramicSection) ceramicSection.style.display = 'none';
        if (natSection) natSection.style.display = 'none';
    }
});

// Initial check in case the default selection is brick_rollag
document.getElementById('formulaSelector').dispatchEvent(new Event('change'));

function applyQueryParamsToTraceForm() {
    const params = new URLSearchParams(window.location.search);
    if (!params.size) {
        return;
    }

    const form = document.getElementById('traceForm');
    if (!form) {
        return;
    }

    const formulaSelector = document.getElementById('formulaSelector');
    const formulaCode = params.get('formula_code');
    if (formulaSelector && formulaCode) {
        formulaSelector.value = formulaCode;
        formulaSelector.dispatchEvent(new Event('change', { bubbles: true }));
    }

    isApplyingQueryParams = true;
    params.forEach((value, key) => {
        if (key === 'formula_code' || key === 'auto_trace') {
            return;
        }
        const field = form.querySelector(`[name="${key}"]`);
        if (!field) {
            return;
        }
        field.value = value;
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
    isApplyingQueryParams = false;

    if (formulaSelector) {
        formulaSelector.dispatchEvent(new Event('change', { bubbles: true }));
    }

    const shouldAutoTrace = params.get('auto_trace') === '1';
    const hasExplicitCeramicDimensions =
        params.has('ceramic_length') || params.has('ceramic_width') || params.has('ceramic_thickness');
    syncCeramicDimensionsFromSelection(shouldAutoTrace && !hasExplicitCeramicDimensions);

    if (shouldAutoTrace) {
        setTimeout(() => {
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }, 50);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyQueryParamsToTraceForm);
} else {
    applyQueryParamsToTraceForm();
}

function renderTrace(trace, containerId) {
    let html = `
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">${trace.mode}</h4>
            </div>
            <div class="card-body">
    `;

    // Render each step
    trace.steps.forEach(step => {
        html += `
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-right-circle"></i>
                        Step ${step.step}: ${step.title}
                    </h5>
                    ${step.formula ? `<small class="text-muted"><strong>Formula:</strong> ${step.formula}</small>` : ''}
                    ${step.info ? `<div class="mt-2"><span class="badge bg-info">${step.info}</span></div>` : ''}
                </div>
                <div class="card-body">
        `;

        // Show explanation if exists
        if (step.explanation) {
            html += `<div class="alert alert-info mb-3">`;
            Object.entries(step.explanation).forEach(([key, value]) => {
                html += `<div class="mb-1"><strong>${key}:</strong> ${value}</div>`;
            });
            html += `</div>`;
        }

        html += `<table class="table table-sm table-bordered mb-0"><tbody>`;

        Object.entries(step.calculations).forEach(([key, value]) => {
            html += `
                <tr>
                    <td class="fw-bold" style="width: 40%">${key}</td>
                    <td><code>${value}</code></td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    });

    // Final Result
    html += `
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Hasil Akhir</h5>
            </div>
            <div class="card-body">
    `;

    // Render hasil berdasarkan tipe formula
    const result = trace.final_result;

    // Cek apakah ini tile installation (ada data keramik)
    if (result.total_tiles !== undefined && result.total_tiles > 0) {
        // TILE INSTALLATION RESULT
        html += `
            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-grid-3x3-gap"></i> Kebutuhan Keramik</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <td class="fw-bold" style="width: 60%">Total Keramik</td>
                        <td class="text-end"><strong>${formatNumber(result.total_tiles)} pcs</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Kebutuhan Dus Keramik</td>
                        <td class="text-end"><strong>${formatNumber(result.tiles_packages)} dus</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Keramik per M2</td>
                        <td class="text-end">${formatNumber(result.tiles_per_m2)} pcs/M2</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Dus per M2</td>
                        <td class="text-end">${formatNumber(result.tiles_packages_per_m2)} dus/M2</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-bricks"></i> Kebutuhan Adukan Semen</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <td class="fw-bold" style="width: 60%">Semen (sak)</td>
                        <td class="text-end"><strong>${formatNumber(result.cement_sak)} sak</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Semen (kg)</td>
                        <td class="text-end">${formatNumber(result.cement_kg)} kg</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Semen (M3)</td>
                        <td class="text-end">${formatNumber(result.cement_m3)} M3</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Pasir (M3)</td>
                        <td class="text-end"><strong>${formatNumber(result.sand_m3)} M3</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Pasir (sak)</td>
                        <td class="text-end">${formatNumber(result.sand_sak)} sak</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Air untuk Semen (liter)</td>
                        <td class="text-end">${formatNumber(result.water_cement_liters)} liter</td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-grid"></i> Kebutuhan Nat (Grout)</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <td class="fw-bold" style="width: 60%">Nat (bungkus)</td>
                        <td class="text-end"><strong>${formatNumber(result.grout_packages)} bungkus</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Nat (kg)</td>
                        <td class="text-end">${formatNumber(result.grout_kg)} kg</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Nat (M3)</td>
                        <td class="text-end">${formatNumber(result.grout_m3)} M3</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Air untuk Nat (liter)</td>
                        <td class="text-end">${formatNumber(result.water_grout_liters)} liter</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold">Total Air Keseluruhan (liter)</td>
                        <td class="text-end"><strong>${formatNumber(result.total_water_liters)} liter</strong></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-currency-dollar"></i> Rincian Harga</h6>
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold" style="width: 60%">Harga Keramik (@${formatCurrency(result.ceramic_price_per_package)}/dus)</td>
                        <td class="text-end">${formatCurrency(result.total_ceramic_price)}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Harga Semen (@${formatCurrency(result.cement_price_per_sak)}/sak)</td>
                        <td class="text-end">${formatCurrency(result.total_cement_price)}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Harga Pasir (@${formatCurrency(result.sand_price_per_m3)}/M3)</td>
                        <td class="text-end">${formatCurrency(result.total_sand_price)}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Harga Nat (@${formatCurrency(result.grout_price_per_package)}/bungkus)</td>
                        <td class="text-end">${formatCurrency(result.total_grout_price)}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold fs-5">TOTAL HARGA</td>
                        <td class="text-end fs-5"><strong>${formatCurrency(result.grand_total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        `;
    } else if (result.grout_packages !== undefined && result.grout_packages > 0) {
        // GROUT ONLY RESULT
        html += `
            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-grid"></i> Kebutuhan Nat (Grout)</h6>
            <table class="table table-bordered mb-4">
                <tbody>
                    <tr>
                        <td class="fw-bold" style="width: 60%">Nat (bungkus)</td>
                        <td class="text-end"><strong>${formatNumber(result.grout_packages)} bungkus</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Nat (kg)</td>
                        <td class="text-end">${formatNumber(result.grout_kg)} kg</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Nat (M3)</td>
                        <td class="text-end">${formatNumber(result.grout_m3)} M3</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Air untuk Nat (liter)</td>
                        <td class="text-end">${formatNumber(result.water_grout_liters)} liter</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold">Total Air Keseluruhan (liter)</td>
                        <td class="text-end"><strong>${formatNumber(result.total_water_liters)} liter</strong></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-currency-dollar"></i> Rincian Harga</h6>
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr>
                        <td class="fw-bold">Harga Nat (@${formatCurrency(result.grout_price_per_package)}/bungkus)</td>
                        <td class="text-end">${formatCurrency(result.total_grout_price)}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold fs-5">TOTAL HARGA</td>
                        <td class="text-end fs-5"><strong>${formatCurrency(result.grand_total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        `;
    } else {
        // DEFAULT RESULT (Brick, Plastering, etc.)
        html += `
            <table class="table table-bordered mb-0">
                <tbody>
        `;

        if (result.total_bricks > 0) {
            html += `
                    <tr>
                        <td class="fw-bold">Total Bata</td>
                        <td class="text-end"><strong>${formatNumber(result.total_bricks)} buah</strong></td>
                    </tr>
            `;
        }

        html += `
                    <tr>
                        <td class="fw-bold">Semen (kg)</td>
                        <td class="text-end"><strong>${formatNumber(result.cement_kg)} kg</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Semen (satuan kemasan)</td>
                        <td class="text-end"><strong>${formatNumber(result.cement_sak)} sak</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Semen (M3)</td>
                        <td class="text-end"><strong>${formatNumber(result.cement_m3)} M3</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Pasir (M3)</td>
                        <td class="text-end"><strong>${formatNumber(result.sand_m3)} M3</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Pasir (satuan kemasan)</td>
                        <td class="text-end"><strong>${formatNumber(result.sand_sak)} sak</strong></td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Air (liter)</td>
                        <td class="text-end"><strong>${formatNumber(result.water_liters)} liter</strong></td>
                    </tr>
                </tbody>
            </table>

            <table class="table table-bordered mt-3 mb-0">
                <thead class="table-light">
                    <tr>
                        <th colspan="2" class="text-center">Rincian Harga</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (result.total_bricks > 0) {
            html += `
                    <tr>
                        <td class="fw-bold">Harga Bata (@${formatCurrency(result.brick_price_per_piece)}/buah)</td>
                        <td class="text-end">${formatCurrency(result.total_brick_price)}</td>
                    </tr>
            `;
        }

        html += `
                    <tr>
                        <td class="fw-bold">Harga Semen (@${formatCurrency(result.cement_price_per_sak)}/sak)</td>
                        <td class="text-end">${formatCurrency(result.total_cement_price)}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Harga Pasir (@${formatCurrency(result.sand_price_per_m3)}/M3)</td>
                        <td class="text-end">${formatCurrency(result.total_sand_price)}</td>
                    </tr>
                    <tr class="table-success">
                        <td class="fw-bold">TOTAL HARGA</td>
                        <td class="text-end"><strong>${formatCurrency(result.grand_total)}</strong></td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    html += `
            </div>
        </div>
    `;

    html += `
            </div>
        </div>
    `;

    document.getElementById(containerId).innerHTML = html;
}

function formatFixedPlain(value, decimals = 2) {
    const num = Number(value);
    const resolvedDecimals = Math.max(0, decimals);
    if (!isFinite(num)) {
        if (resolvedDecimals === 0) return '0';
        return '0.' + ''.padEnd(resolvedDecimals, '0');
    }
    const factor = 10 ** resolvedDecimals;
    const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
    const sign = truncated < 0 ? '-' : '';
    const abs = Math.abs(truncated);
    const intPart = Math.floor(abs / factor).toString();
    if (resolvedDecimals === 0) {
        return `${sign}${intPart}`;
    }
    const decPart = (abs % factor).toString().padStart(resolvedDecimals, '0');
    return `${sign}${intPart}.${decPart}`;
}

function formatFixedLocale(value, decimals = 2) {
    const plain = formatFixedPlain(value, decimals);
    const parts = plain.split('.');
    const intPart = parts[0] || '0';
    const decPart = parts[1] || '';
    const sign = intPart.startsWith('-') ? '-' : '';
    const digits = sign ? intPart.slice(1) : intPart;
    const withThousands = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (!decPart || /^0+$/.test(decPart)) {
        return `${sign}${withThousands}`;
    }
    return `${sign}${withThousands},${decPart}`;
}

function formatDynamicPlain(value) {
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

function formatDynamicLocale(value) {
    const plain = formatDynamicPlain(value);
    if (!plain) return '';
    const parts = plain.split('.');
    const intPart = parts[0] || '0';
    const decPart = parts[1] || '';
    const sign = intPart.startsWith('-') ? '-' : '';
    const digits = sign ? intPart.slice(1) : intPart;
    const withThousands = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    if (!decPart) {
        return `${sign}${withThousands}`;
    }
    return `${sign}${withThousands},${decPart}`;
}

function formatNumber(num) {
    return formatDynamicLocale(num);
}

function formatCurrency(num) {
    return 'Rp ' + formatFixedLocale(num, 0);
}
</script>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #0d6efd, #0a58ca) !important;
    }

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}

.table-bordered td {
    vertical-align: middle;
}
    .custom-red-button {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        font-size: 1.125rem;            /* setara dengan btn-lg */
        font-weight: 600;
        padding: 0.75rem 2.5rem;        /* setara dengan px-5 py-3 */
        border: none;
        border-radius: 50px;            /* setara dengan rounded-pill */
        box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 260px;
    }

    .custom-red-button:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        transform: translateY(-3px);
        box-shadow: 0 15px 25px rgba(231, 76, 60, 0.4);
    }

    .custom-red-button:active {
        transform: translateY(0);
        box-shadow: 0 5px 10px rgba(231, 76, 60, 0.3);
    }

    .custom-red-button i {
        font-size: 1.3em;
        vertical-align: middle;
    }
</style>
@endsection

