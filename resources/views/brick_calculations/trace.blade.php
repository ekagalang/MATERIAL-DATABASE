@extends('layouts.app')

@section('title', 'Trace Perhitungan Bata')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-0">Trace Perhitungan Bata Step-by-Step</h1>
            <p class="text-muted">Lihat setiap langkah perhitungan seperti di Excel</p>
        </div>
    </div>

    <!-- Input Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Parameter Input</h5>
        </div>
        <div class="card-body">
            <form id="traceForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Panjang Dinding (m)</label>
                        <input type="number" class="form-control" name="wall_length" value="6.2" step="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tinggi Dinding (m)</label>
                        <input type="number" class="form-control" name="wall_height" value="3.0" step="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tebal Adukan (cm)</label>
                        <input type="number" class="form-control" name="mortar_thickness" value="1.0" step="0.1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Pemasangan</label>
                        <select class="form-select" name="installation_type_id" required>
                            @foreach($installationTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Formula Mortar</label>
                        <select class="form-select" name="mortar_formula_id" required>
                            @foreach($mortarFormulas as $formula)
                                <option value="{{ $formula->id }}">
                                    {{ $formula->cement_ratio }}:{{ $formula->sand_ratio }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bata</label>
                        <select class="form-select" name="brick_id">
                            <option value="">- Default -</option>
                            @foreach($bricks as $brick)
                                <option value="{{ $brick->id }}">{{ $brick->brand }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Custom Cement Ratio</label>
                        <input type="number" class="form-control" name="custom_cement_ratio" value="1" step="0.1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Custom Sand Ratio</label>
                        <input type="number" class="form-control" name="custom_sand_ratio" value="4" step="0.1">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Semen</label>
                        <select class="form-select" name="cement_id">
                            <option value="">- Default -</option>
                            @foreach($cements as $cement)
                                <option value="{{ $cement->id }}">
                                    {{ $cement->cement_name }} - {{ $cement->brand }} ({{ $cement->package_weight_net }}kg)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pasir</label>
                        <select class="form-select" name="sand_id">
                            <option value="">- Default -</option>
                            @foreach($sands as $sand)
                                <option value="{{ $sand->id }}">
                                    {{ $sand->sand_name }} - {{ $sand->brand }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-calculator"></i> Trace Perhitungan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Container -->
    <div id="resultsContainer" style="display: none;">
        <div id="traceContent"></div>
    </div>
</div>

<script>
document.getElementById('traceForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const params = Object.fromEntries(formData.entries());

    // Show loading
    document.getElementById('resultsContainer').style.display = 'block';
    document.getElementById('traceContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div><p class="mt-2">Calculating...</p></div>';

    try {
        const response = await fetch('/api/brick-calculator/trace', {
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
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat melakukan perhitungan');
    }
});

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
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-bold">Total Bata</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.total_bricks)} buah</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Semen (kg)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.cement_kg)} kg</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Semen (satuan kemasan)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.cement_sak)} sak</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Semen (m³)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.cement_m3)} m³</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Pasir (m³)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.sand_m3)} m³</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Pasir (satuan kemasan)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.sand_sak)} sak</strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Air (liter)</td>
                            <td class="text-end"><strong>${formatNumber(trace.final_result.water_liters)} liter</strong></td>
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
                        <tr>
                            <td class="fw-bold">Harga Bata (@${formatCurrency(trace.final_result.brick_price_per_piece)}/buah)</td>
                            <td class="text-end">${formatCurrency(trace.final_result.total_brick_price)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Harga Semen (@${formatCurrency(trace.final_result.cement_price_per_sak)}/sak)</td>
                            <td class="text-end">${formatCurrency(trace.final_result.total_cement_price)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Harga Pasir (@${formatCurrency(trace.final_result.sand_price_per_m3)}/m³)</td>
                            <td class="text-end">${formatCurrency(trace.final_result.total_sand_price)}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">TOTAL HARGA</td>
                            <td class="text-end"><strong>${formatCurrency(trace.final_result.grand_total)}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;

    html += `
            </div>
        </div>
    `;

    document.getElementById(containerId).innerHTML = html;
}

function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return parseFloat(num).toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatCurrency(num) {
    if (num === null || num === undefined) return 'Rp 0';
    return 'Rp ' + parseFloat(num).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}
</script>

<style>
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
</style>
@endsection
