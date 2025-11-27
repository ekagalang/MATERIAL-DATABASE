@extends('layouts.app')

@section('content')
<div class="card">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-calculator text-primary"></i> 
                        Perhitungan Baru
                    </h2>
                    <p class="text-muted mb-0">Hitung kebutuhan material untuk pembangunan dinding bata</p>
                </div>
                <div>
                    <a href="{{ route('brick-calculator.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('brick-calculations.store') }}" method="POST" id="calculatorForm">
        @csrf
        
        <div class="row">
            <!-- Input Panel -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i> Input Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Project Info -->
                        <div class="mb-3">
                            <label class="form-label">Nama Project (Opsional)</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="project_name" 
                                   value="{{ old('project_name') }}"
                                   placeholder="Contoh: Dinding Ruang Tamu">
                        </div>

                        <hr>

                        <!-- Dimensi Dinding -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-ruler-combined"></i> Dimensi Dinding
                        </h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Panjang <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           name="wall_length" 
                                           id="wall_length"
                                           value="{{ old('wall_length', 6.2) }}"
                                           step="0.01"
                                           min="0.01"
                                           required>
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tinggi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           name="wall_height" 
                                           id="wall_height"
                                           value="{{ old('wall_height', 3.0) }}"
                                           step="0.01"
                                           min="0.01"
                                           required>
                                    <span class="input-group-text">m</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-info-circle"></i> 
                                    Luas dinding: <strong id="wall_area_display">0</strong> m²
                                </small>
                            </div>
                        </div>

                        <hr>

                        <!-- Jenis Pemasangan -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-th"></i> Jenis Pemasangan Bata
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">Pilih Jenis <span class="text-danger">*</span></label>
                            <select class="form-select" name="installation_type_id" id="installation_type_id" required>
                                @foreach($installationTypes as $type)
                                    <option value="{{ $type->id }}" 
                                            {{ old('installation_type_id', $defaultInstallationType->id ?? '') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" id="installation_description"></small>
                        </div>

                        <hr>

                        <!-- Adukan -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-blender"></i> Adukan Semen
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">Tebal Adukan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" 
                                    class="form-control" 
                                    name="mortar_thickness" 
                                    id="mortar_thickness"
                                    value="{{ old('mortar_thickness', 1.0) }}"
                                    step="0.1"
                                    min="0.1"
                                    max="10"
                                    required>
                                <span class="input-group-text">cm</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Metode Formula <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" 
                                    type="radio" 
                                    name="ratio_method" 
                                    id="ratio_preset" 
                                    value="preset"
                                    {{ old('use_custom_ratio') != '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ratio_preset">
                                    <strong>Gunakan Formula Preset</strong>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" 
                                    type="radio" 
                                    name="ratio_method" 
                                    id="ratio_custom" 
                                    value="custom"
                                    {{ old('use_custom_ratio') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ratio_custom">
                                    <strong>Input Rasio Manual</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Preset Formula Section -->
                        <div id="preset_section" class="mb-3">
                            <label class="form-label">Formula Preset <span class="text-danger">*</span></label>
                            <select class="form-select" name="mortar_formula_id" id="mortar_formula_id">
                                @foreach($mortarFormulas as $formula)
                                    <option value="{{ $formula->id }}"
                                            data-cement="{{ $formula->cement_ratio }}"
                                            data-sand="{{ $formula->sand_ratio }}"
                                            data-water="{{ $formula->water_ratio }}"
                                            {{ old('mortar_formula_id', $defaultMortarFormula->id ?? '') == $formula->id ? 'selected' : '' }}>
                                        {{ $formula->name }} ({{ $formula->cement_ratio }}:{{ $formula->sand_ratio }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pilih formula campuran yang sudah tersedia</small>
                        </div>

                        <!-- Custom Ratio Section -->
                        <div id="custom_section" class="mb-3" style="display: none;">
                            <input type="hidden" name="use_custom_ratio" id="use_custom_ratio" value="0">
                            
                            <label class="form-label">Rasio Campuran Custom <span class="text-danger">*</span></label>
                            
                            <div class="card border-warning">
                                <div class="card-body">
                                    <div class="row g-2 align-items-center mb-2">
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Semen</label>
                                            <input type="number" 
                                                class="form-control form-control-sm" 
                                                name="custom_cement_ratio"
                                                id="custom_cement_ratio"
                                                value="{{ old('custom_cement_ratio', 1) }}"
                                                step="0.1"
                                                min="0.1">
                                        </div>
                                        <div class="col-1 text-center">
                                            <strong>:</strong>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small mb-1">Pasir</label>
                                            <input type="number" 
                                                class="form-control form-control-sm" 
                                                name="custom_sand_ratio"
                                                id="custom_sand_ratio"
                                                value="{{ old('custom_sand_ratio', 4) }}"
                                                step="0.1"
                                                min="0.1">
                                        </div>
                                        <div class="col-3">
                                            <label class="form-label small mb-1">Air (opt)</label>
                                            <input type="number" 
                                                class="form-control form-control-sm" 
                                                name="custom_water_ratio"
                                                id="custom_water_ratio"
                                                value="{{ old('custom_water_ratio', 0.5) }}"
                                                step="0.1"
                                                min="0">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        Contoh: 1:4 artinya 1 bagian semen dengan 4 bagian pasir
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Display Ratio -->
                        <div class="alert alert-info mb-0">
                            <small>
                                <i class="fas fa-blender"></i> 
                                Rasio yang digunakan: <strong id="ratio_display">1:4</strong>
                            </small>
                        </div>

                        <hr>

                        <!-- Material Selection -->
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-box"></i> Pilih Material (Opsional)
                        </h6>

                        <div class="mb-3">
                            <label class="form-label">Bata</label>
                            <select class="form-select" name="brick_id" id="brick_id">
                                @foreach($bricks as $brick)
                                    <option value="{{ $brick->id }}"
                                            data-price="{{ $brick->price_per_piece }}"
                                            {{ old('brick_id', $bricks->first()->id ?? '') == $brick->id ? 'selected' : '' }}>
                                        {{ $brick->brand }} {{ $brick->type }} - Rp {{ number_format($brick->price_per_piece, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Semen</label>
                            <select class="form-select" name="cement_id" id="cement_id">
                                @foreach($cements as $cement)
                                    <option value="{{ $cement->id }}"
                                            data-price="{{ $cement->package_price }}"
                                            {{ old('cement_id', $cements->first()->id ?? '') == $cement->id ? 'selected' : '' }}>
                                        {{ $cement->brand }} {{ $cement->sub_brand }} - Rp {{ number_format($cement->package_price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pasir</label>
                            <select class="form-select" name="sand_id" id="sand_id">
                                @foreach($sands as $sand)
                                    <option value="{{ $sand->id }}"
                                            data-price="{{ $sand->package_price }}"
                                            {{ old('sand_id', $sands->first()->id ?? '') == $sand->id ? 'selected' : '' }}>
                                        {{ $sand->brand }} {{ $sand->type }} - Rp {{ number_format($sand->package_price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-info" id="btnPreview">
                                <i class="fas fa-eye"></i> Preview Hasil
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perhitungan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Panel -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm mb-4" id="resultPanel" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> Hasil Perhitungan
                        </h5>
                    </div>
                    <div class="card-body" id="resultContent">
                        <!-- Will be filled by JavaScript -->
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> Informasi Jenis Pemasangan
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($installationTypes as $type)
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="text-primary">
                                <i class="fas fa-check-circle"></i> {{ $type->name }}
                            </h6>
                            <p class="text-muted mb-0 small">{{ $type->description }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const installationTypes = @json($installationTypes);
    
    // Pastikan semua elemen ada
    const presetRadio = document.getElementById('ratio_preset');
    const customRadio = document.getElementById('ratio_custom');
    const presetSection = document.getElementById('preset_section');
    const customSection = document.getElementById('custom_section');
    const useCustomRatioInput = document.getElementById('use_custom_ratio');

    // Toggle between preset and custom
    function toggleRatioMethod() {
        console.log('toggleRatioMethod called');
        console.log('customRadio exists:', customRadio);
        console.log('customRadio.checked:', customRadio ? customRadio.checked : 'N/A');
        console.log('presetSection:', presetSection);
        console.log('customSection:', customSection);

        if (customRadio && customRadio.checked) {
            console.log('Showing CUSTOM section');
            if (presetSection) {
                presetSection.style.display = 'none';
                console.log('Preset hidden');
            }
            if (customSection) {
                customSection.style.display = 'block';
                console.log('Custom shown, display:', customSection.style.display);
            }
            if (useCustomRatioInput) useCustomRatioInput.value = '1';

            // Disable preset select
            const formulaSelect = document.getElementById('mortar_formula_id');
            if (formulaSelect) formulaSelect.required = false;

            // Enable custom inputs
            const cementInput = document.getElementById('custom_cement_ratio');
            const sandInput = document.getElementById('custom_sand_ratio');
            if (cementInput) cementInput.required = true;
            if (sandInput) sandInput.required = true;

            updateRatioDisplay();
        } else {
            console.log('Showing PRESET section');
            if (presetSection) {
                presetSection.style.display = 'block';
                console.log('Preset shown');
            }
            if (customSection) {
                customSection.style.display = 'none';
                console.log('Custom hidden');
            }
            if (useCustomRatioInput) useCustomRatioInput.value = '0';

            // Enable preset select
            const formulaSelect = document.getElementById('mortar_formula_id');
            if (formulaSelect) formulaSelect.required = true;

            // Disable custom inputs
            const cementInput = document.getElementById('custom_cement_ratio');
            const sandInput = document.getElementById('custom_sand_ratio');
            if (cementInput) cementInput.required = false;
            if (sandInput) sandInput.required = false;

            updateRatioDisplay();
        }
    }
    
    // Update ratio display
    function updateRatioDisplay() {
        let ratioText = '';
        
        if (customRadio && customRadio.checked) {
            const cementInput = document.getElementById('custom_cement_ratio');
            const sandInput = document.getElementById('custom_sand_ratio');
            const cement = cementInput ? (cementInput.value || 1) : 1;
            const sand = sandInput ? (sandInput.value || 4) : 4;
            ratioText = `${cement}:${sand}`;
        } else {
            const select = document.getElementById('mortar_formula_id');
            if (select) {
                const selected = select.options[select.selectedIndex];
                const cement = selected.getAttribute('data-cement');
                const sand = selected.getAttribute('data-sand');
                ratioText = `${cement}:${sand}`;
            }
        }
        
        const displayElement = document.getElementById('ratio_display');
        if (displayElement) {
            displayElement.textContent = ratioText;
        }
    }
    
    // Event listeners for ratio method
    if (presetRadio) {
        console.log('Adding event listener to preset radio');
        presetRadio.addEventListener('change', function() {
            console.log('Preset radio CHANGED');
            toggleRatioMethod();
        });
    } else {
        console.log('ERROR: Preset radio not found!');
    }

    if (customRadio) {
        console.log('Adding event listener to custom radio');
        customRadio.addEventListener('change', function() {
            console.log('Custom radio CHANGED');
            toggleRatioMethod();
        });
    } else {
        console.log('ERROR: Custom radio not found!');
    }
    
    // Event listeners untuk formula select dan custom inputs
    const formulaSelect = document.getElementById('mortar_formula_id');
    if (formulaSelect) {
        formulaSelect.addEventListener('change', updateRatioDisplay);
    }
    
    const cementInput = document.getElementById('custom_cement_ratio');
    const sandInput = document.getElementById('custom_sand_ratio');
    if (cementInput) cementInput.addEventListener('input', updateRatioDisplay);
    if (sandInput) sandInput.addEventListener('input', updateRatioDisplay);
    
    // Update wall area
    function updateWallArea() {
        const lengthInput = document.getElementById('wall_length');
        const heightInput = document.getElementById('wall_height');
        const length = lengthInput ? (parseFloat(lengthInput.value) || 0) : 0;
        const height = heightInput ? (parseFloat(heightInput.value) || 0) : 0;
        const area = (length * height).toFixed(2);
        
        const displayElement = document.getElementById('wall_area_display');
        if (displayElement) {
            displayElement.textContent = area;
        }
    }

    // Update installation description
    function updateInstallationDescription() {
        const select = document.getElementById('installation_type_id');
        if (select) {
            const selectedId = select.value;
            const type = installationTypes.find(t => t.id == selectedId);
            const descElement = document.getElementById('installation_description');
            if (type && descElement) {
                descElement.textContent = type.description;
            }
        }
    }

    // Event listeners
    const lengthInput = document.getElementById('wall_length');
    const heightInput = document.getElementById('wall_height');
    const typeSelect = document.getElementById('installation_type_id');
    
    if (lengthInput) lengthInput.addEventListener('input', updateWallArea);
    if (heightInput) heightInput.addEventListener('input', updateWallArea);
    if (typeSelect) typeSelect.addEventListener('change', updateInstallationDescription);

    // Preview calculation
    const previewBtn = document.getElementById('btnPreview');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('calculatorForm'));
            
            fetch('{{ route('api.brick-calculator.calculate') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResult(data.summary);
                    const resultPanel = document.getElementById('resultPanel');
                    if (resultPanel) {
                        resultPanel.style.display = 'block';
                        resultPanel.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghitung');
            });
        });
    }

    function displayResult(summary) {
        const html = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h6 class="text-primary"><i class="fas fa-ruler"></i> Informasi Dinding</h6>
                    <table class="table table-sm">
                        <tr><td>Panjang:</td><td class="text-end"><strong>${summary.wall_info.length}</strong></td></tr>
                        <tr><td>Tinggi:</td><td class="text-end"><strong>${summary.wall_info.height}</strong></td></tr>
                        <tr><td>Luas:</td><td class="text-end"><strong>${summary.wall_info.area}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-6 mb-3">
                    <h6 class="text-primary"><i class="fas fa-th"></i> Kebutuhan Bata</h6>
                    <table class="table table-sm">
                        <tr><td>Jumlah:</td><td class="text-end"><strong>${summary.brick_info.quantity}</strong></td></tr>
                        <tr><td>Jenis:</td><td class="text-end"><strong>${summary.brick_info.type}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.brick_info.cost}</strong></td></tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h6 class="text-primary"><i class="fas fa-box"></i> Semen</h6>
                    <table class="table table-sm">
                        <tr><td>40 kg:</td><td class="text-end"><strong>${summary.materials.cement['40kg']}</strong></td></tr>
                        <tr><td>50 kg:</td><td class="text-end"><strong>${summary.materials.cement['50kg']}</strong></td></tr>
                        <tr><td>Total kg:</td><td class="text-end"><strong>${summary.materials.cement.kg}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.materials.cement.cost}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-primary"><i class="fas fa-mountain"></i> Pasir</h6>
                    <table class="table table-sm">
                        <tr><td>Karung:</td><td class="text-end"><strong>${summary.materials.sand.sak}</strong></td></tr>
                        <tr><td>Berat:</td><td class="text-end"><strong>${summary.materials.sand.kg}</strong></td></tr>
                        <tr><td>Volume:</td><td class="text-end"><strong>${summary.materials.sand.m3}</strong></td></tr>
                        <tr><td>Biaya:</td><td class="text-end text-success"><strong>${summary.materials.sand.cost}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-primary"><i class="fas fa-tint"></i> Air</h6>
                    <table class="table table-sm">
                        <tr><td>Kebutuhan:</td><td class="text-end"><strong>${summary.materials.water.liters}</strong></td></tr>
                    </table>
                </div>
            </div>
            <hr>
            <div class="alert alert-success mb-0">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave"></i> 
                    Total Estimasi Biaya: <strong>${summary.total_cost}</strong>
                </h5>
            </div>
        `;
        
        const resultContent = document.getElementById('resultContent');
        if (resultContent) {
            resultContent.innerHTML = html;
        }
    }

    // Initial calls
    console.log('=== INITIAL CALLS ===');
    updateWallArea();
    updateInstallationDescription();
    console.log('Calling toggleRatioMethod on page load...');
    toggleRatioMethod(); // Panggil saat load pertama kali
    updateRatioDisplay();
    console.log('=== INITIALIZATION COMPLETE ===');
});
</script>
@endpush
@endsection