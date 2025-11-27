@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-balance-scale text-primary"></i>
                        Perbandingan 3 Metode Perhitungan
                    </h2>
                    <p class="text-muted mb-0">Bandingkan hasil dari 3 metode berbeda untuk menentukan yang paling tepat</p>
                </div>
                <div>
                    <a href="{{ route('brick-calculator.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i>
        <strong>Tentang 3 Metode:</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Mode Professional:</strong> Berbasis volume mortar dengan data empiris terverifikasi (sistem saat ini)</li>
            <li><strong>Mode Field:</strong> Berbasis kemasan dengan engineering factors (dari rumus 2.xlsx - shrinkage 15%, water 30%)</li>
            <li><strong>Mode Simple:</strong> Berbasis kemasan sederhana dengan volume sak terkoreksi (estimasi cepat)</li>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <!-- Input Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Input Data
                    </h5>
                </div>
                <div class="card-body">
                    <form id="comparisonForm">
                        <!-- Dimensi Dinding -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Panjang Dinding (m)</label>
                            <input type="number" class="form-control" id="wall_length" name="wall_length"
                                   step="0.01" min="0.01" value="6.2" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tinggi Dinding (m)</label>
                            <input type="number" class="form-control" id="wall_height" name="wall_height"
                                   step="0.01" min="0.01" value="3.0" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Luas Dinding</label>
                            <input type="text" class="form-control bg-light" id="wall_area_display" readonly>
                        </div>

                        <hr>

                        <!-- Jenis Pemasangan -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jenis Pemasangan</label>
                            <select class="form-select" id="installation_type_id" name="installation_type_id" required>
                                @foreach($installationTypes as $type)
                                    <option value="{{ $type->id }}" {{ $type->code == 'half' ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tebal Adukan -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tebal Adukan (cm)</label>
                            <input type="number" class="form-control" id="mortar_thickness" name="mortar_thickness"
                                   step="0.1" min="0.1" max="10" value="1.0" required>
                        </div>

                        <!-- Formula Adukan -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Formula Adukan</label>
                            <select class="form-select" id="mortar_formula_id" name="mortar_formula_id" required>
                                @foreach($mortarFormulas as $formula)
                                    <option value="{{ $formula->id }}" {{ $formula->is_default ? 'selected' : '' }}>
                                        {{ $formula->name }} (1:{{ $formula->sand_ratio }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Bata -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Jenis Bata</label>
                            <select class="form-select" id="brick_id" name="brick_id">
                                <option value="">Default (KUO SHIN)</option>
                                @foreach($bricks as $brick)
                                    <option value="{{ $brick->id }}">
                                        {{ $brick->brand }} - {{ $brick->dimension_length }}×{{ $brick->dimension_width }}×{{ $brick->dimension_height }}cm
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <!-- Custom Ratio (untuk Mode 2 & 3) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Custom Ratio (Opsional)</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" class="form-control" id="custom_cement_ratio"
                                           name="custom_cement_ratio" placeholder="Semen" min="1" value="1">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" id="custom_sand_ratio"
                                           name="custom_sand_ratio" placeholder="Pasir" min="1" value="4">
                                </div>
                            </div>
                            <small class="text-muted">Contoh: 1:4 berarti 1 semen : 4 pasir</small>
                        </div>

                        <!-- Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-calculator"></i> Hitung & Bandingkan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Panel -->
        <div class="col-lg-8">
            <div id="loadingState" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Menghitung dengan 3 metode...</p>
            </div>

            <div id="resultsContainer" style="display: none;">
                <!-- Summary Comparison Table -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-table"></i> Ringkasan Perbandingan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Material</th>
                                        <th width="26%" class="text-center bg-primary text-white">
                                            Mode 1: Professional
                                            <br><small>(Volume Mortar)</small>
                                        </th>
                                        <th width="27%" class="text-center bg-info text-white">
                                            Mode 2: Field
                                            <br><small>(Package Engineering)</small>
                                        </th>
                                        <th width="27%" class="text-center bg-warning">
                                            Mode 3: Simple
                                            <br><small>(Package Basic)</small>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="comparisonTableBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detailed Results for Each Mode -->
                <div class="row">
                    <!-- Mode 1 -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-primary h-100">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-flask"></i> Mode 1: Professional
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="mode1Details">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mode 2 -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-info h-100">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-box"></i> Mode 2: Field
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="mode2Details">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mode 3 -->
                    <div class="col-lg-4 mb-4">
                        <div class="card border-warning h-100">
                            <div class="card-header bg-warning">
                                <h6 class="mb-0">
                                    <i class="fas fa-tachometer-alt"></i> Mode 3: Simple
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="mode3Details">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analysis & Recommendation -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> Analisa & Rekomendasi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="analysisContent">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Initial State -->
            <div id="initialState" class="text-center py-5">
                <i class="fas fa-calculator fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Masukkan data dan klik "Hitung & Bandingkan"</h4>
                <p class="text-muted">Sistem akan menghitung dengan 3 metode berbeda secara bersamaan</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('comparisonForm');
    const wallLength = document.getElementById('wall_length');
    const wallHeight = document.getElementById('wall_height');
    const wallAreaDisplay = document.getElementById('wall_area_display');

    // Calculate wall area
    function updateWallArea() {
        const length = parseFloat(wallLength.value) || 0;
        const height = parseFloat(wallHeight.value) || 0;
        const area = length * height;
        wallAreaDisplay.value = area > 0 ? area.toFixed(2) + ' m²' : '-';
    }

    wallLength.addEventListener('input', updateWallArea);
    wallHeight.addEventListener('input', updateWallArea);
    updateWallArea();

    // Form submit
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Show loading
        document.getElementById('initialState').style.display = 'none';
        document.getElementById('resultsContainer').style.display = 'none';
        document.getElementById('loadingState').style.display = 'block';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('{{ route("api.brick-calculator.compare-modes") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                displayResults(result.data);
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghitung');
        } finally {
            document.getElementById('loadingState').style.display = 'none';
        }
    });

    function displayResults(data) {
        const mode1 = data.mode_1_professional;
        const mode2 = data.mode_2_field;
        const mode3 = data.mode_3_simple;

        // Populate comparison table
        const tableBody = document.getElementById('comparisonTableBody');
        tableBody.innerHTML = `
            <tr>
                <td><strong>Total Bata</strong></td>
                <td class="text-center">${formatNumber(mode1.total_bricks)} buah</td>
                <td class="text-center">${formatNumber(mode2.total_bricks)} buah</td>
                <td class="text-center">${formatNumber(mode3.total_bricks)} buah</td>
            </tr>
            <tr>
                <td><strong>Semen (kg)</strong></td>
                <td class="text-center">${formatNumber(mode1.cement_kg)} kg</td>
                <td class="text-center">${formatNumber(mode2.cement_kg)} kg</td>
                <td class="text-center">${formatNumber(mode3.cement_kg)} kg</td>
            </tr>
            <tr>
                <td><strong>Semen (sak)</strong></td>
                <td class="text-center bg-primary bg-opacity-10">${formatNumber(mode1.cement_sak)} sak @ ${formatNumber(mode1.cement_weight_per_sak)}kg</td>
                <td class="text-center bg-info bg-opacity-10">${formatNumber(mode2.cement_sak)} sak @ ${formatNumber(mode2.cement_weight_per_sak)}kg</td>
                <td class="text-center bg-warning bg-opacity-10">${formatNumber(mode3.cement_sak)} sak @ ${formatNumber(mode3.cement_weight_per_sak)}kg</td>
            </tr>
            <tr>
                <td><strong>Pasir (m³)</strong></td>
                <td class="text-center">${formatNumber(mode1.sand_m3)} m³</td>
                <td class="text-center">${formatNumber(mode2.sand_m3)} m³</td>
                <td class="text-center">${formatNumber(mode3.sand_m3)} m³</td>
            </tr>
            <tr>
                <td><strong>Pasir (kg)</strong></td>
                <td class="text-center">${formatNumber(mode1.sand_kg)} kg</td>
                <td class="text-center">${formatNumber(mode2.sand_kg)} kg</td>
                <td class="text-center">${formatNumber(mode3.sand_kg)} kg</td>
            </tr>
            <tr>
                <td><strong>Air (liter)</strong></td>
                <td class="text-center">${formatNumber(mode1.water_liters)} L</td>
                <td class="text-center">${formatNumber(mode2.water_liters)} L</td>
                <td class="text-center">${formatNumber(mode3.water_liters)} L</td>
            </tr>
        `;

        // Populate detailed views
        document.getElementById('mode1Details').innerHTML = buildModeDetails(mode1);
        document.getElementById('mode2Details').innerHTML = buildModeDetails(mode2);
        document.getElementById('mode3Details').innerHTML = buildModeDetails(mode3);

        // Analysis
        document.getElementById('analysisContent').innerHTML = buildAnalysis(mode1, mode2, mode3);

        // Show results
        document.getElementById('resultsContainer').style.display = 'block';
    }

    function buildModeDetails(mode) {
        return `
            <p class="mb-2"><small class="text-muted">${mode.method}</small></p>
            <hr>
            <div class="mb-2">
                <strong>Bata:</strong>
                <div class="text-end">${formatNumber(mode.total_bricks)} buah</div>
            </div>
            <div class="mb-2">
                <strong>Semen:</strong>
                <div class="text-end">${formatNumber(mode.cement_sak)} sak @ ${formatNumber(mode.cement_weight_per_sak)}kg</div>
                <div class="text-end text-muted small">${formatNumber(mode.cement_kg)} kg total</div>
            </div>
            <div class="mb-2">
                <strong>Pasir:</strong>
                <div class="text-end">${formatNumber(mode.sand_m3)} m³</div>
                ${mode.sand_sak ? `<div class="text-end text-muted small">${formatNumber(mode.sand_sak)} sak</div>` : ''}
            </div>
            <div class="mb-2">
                <strong>Air:</strong>
                <div class="text-end">${formatNumber(mode.water_liters)} liter</div>
            </div>
            <hr>
            <div class="small text-muted">
                <strong>Ratio:</strong> ${mode.ratio_used}
            </div>
        `;
    }

    function buildAnalysis(mode1, mode2, mode3) {
        const avgCement = (mode1.cement_kg + mode2.cement_kg + mode3.cement_kg) / 3;
        const minCement = Math.min(mode1.cement_kg, mode2.cement_kg, mode3.cement_kg);
        const maxCement = Math.max(mode1.cement_kg, mode2.cement_kg, mode3.cement_kg);
        const diffPercent = ((maxCement - minCement) / minCement * 100).toFixed(1);

        let mostAccurate = mode1.mode;
        let recommended = 'Mode 1 (Professional)';

        return `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-chart-bar"></i> Statistik:</h6>
                    <ul>
                        <li>Selisih semen max-min: <strong>${diffPercent}%</strong></li>
                        <li>Semen terendah: <strong>${formatNumber(minCement)} kg</strong></li>
                        <li>Semen tertinggi: <strong>${formatNumber(maxCement)} kg</strong></li>
                        <li>Rata-rata: <strong>${formatNumber(avgCement)} kg</strong></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-thumbs-up"></i> Rekomendasi:</h6>
                    <div class="alert alert-success mb-0">
                        <strong>${recommended}</strong>
                        <p class="mb-0 small">Menggunakan data empiris terverifikasi dengan interpolasi akurat. Paling cocok untuk estimasi RAB dan tender profesional.</p>
                    </div>
                </div>
            </div>
            <hr>
            <h6><i class="fas fa-info-circle"></i> Kapan Menggunakan:</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Mode Professional</h6>
                            <ul class="small mb-0">
                                <li>RAB & Tender</li>
                                <li>Project Management</li>
                                <li>Estimasi presisi</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body">
                            <h6 class="card-title text-info">Mode Field</h6>
                            <ul class="small mb-0">
                                <li>Estimasi lapangan</li>
                                <li>Pembelian material</li>
                                <li>Komunikasi dengan tukang</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h6 class="card-title text-warning">Mode Simple</h6>
                            <ul class="small mb-0">
                                <li>Estimasi cepat</li>
                                <li>Cross-check kasar</li>
                                <li>Perhitungan sederhana</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\.00$/, '');
    }
});
</script>
@endpush
@endsection
