@extends('layouts.app')

@section('content')
<div class="card">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-file-alt text-primary"></i> 
                        Detail Perhitungan
                    </h2>
                    <p class="text-muted mb-0">
                        {{ $brickCalculation->project_name ?: 'Perhitungan Tanpa Nama' }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('brick-calculations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('brick-calculations.edit', $brickCalculation) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button type="button" class="btn btn-info" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <!-- Project Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Informasi Project
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td class="text-muted">Tanggal:</td>
                        <td class="text-end"><strong>{{ $brickCalculation->created_at->format('d F Y, H:i') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Project:</td>
                        <td class="text-end"><strong>{{ $brickCalculation->project_name ?: '-' }}</strong></td>
                    </tr>
                    @if($brickCalculation->notes)
                    <tr>
                        <td class="text-muted" colspan="2">
                            Catatan:<br>
                            <span class="text-dark">{{ $brickCalculation->notes }}</span>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Wall Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-ruler-combined"></i> Dimensi Dinding
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Panjang:</td>
                        <td class="text-end"><strong>{{ $summary['wall_info']['length'] }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tinggi:</td>
                        <td class="text-end"><strong>{{ $summary['wall_info']['height'] }}</strong></td>
                    </tr>
                    <tr class="table-primary">
                        <td><strong>Luas Total:</strong></td>
                        <td class="text-end"><strong>{{ $summary['wall_info']['area'] }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Installation Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-th"></i> Jenis Pemasangan
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Jenis:</td>
                        <td class="text-end"><strong>{{ $summary['brick_info']['type'] }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tebal Adukan:</td>
                        <td class="text-end"><strong>{{ $summary['mortar_info']['thickness'] }}</strong></td>
                    </tr>
                    <tr>
                        <td>Formula:</td>
                        <td class="text-end">
                            <strong>{{ $summary['mortar_info']['formula'] }}</strong>
                            @if($brickCalculation->use_custom_ratio)
                                <br><span class="badge bg-warning text-dark">Custom Ratio</span>
                            @endif
                        </td>
                    </tr>
                    @if($brickCalculation->use_custom_ratio)
                    <tr>
                        <td>Rasio Custom:</td>
                        <td class="text-end">
                            <strong>{{ $brickCalculation->custom_cement_ratio }}:{{ $brickCalculation->custom_sand_ratio }}</strong>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Material Requirements -->
    <div class="material-grid">
        <!-- Bata -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-th-large"></i> Bata
                </h5>
            </div>
            <div class="card-body">
                @if($brickCalculation->brick)
                    <p class="mb-2"><strong>{{ $brickCalculation->brick->brand }} {{ $brickCalculation->brick->type }}</strong></p>
                @endif
                <h3 class="text-warning mb-0">{{ $summary['brick_info']['quantity'] }}</h3>
                <hr>
                <p class="mb-0">
                    <small class="text-muted">Biaya:</small><br>
                    <strong class="text-success">{{ $summary['brick_info']['cost'] }}</strong>
                </p>
            </div>
        </div>

        <!-- Semen -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-box"></i> Semen
                </h5>
            </div>
            <div class="card-body">
                @if($brickCalculation->cement)
                    <p class="mb-2"><strong>{{ $brickCalculation->cement->brand }}</strong></p>
                @endif
                <table class="table table-sm">
                    <tr><td>40 kg:</td><td class="text-end"><strong>{{ $summary['materials']['cement']['40kg'] }}</strong></td></tr>
                    <tr><td>50 kg:</td><td class="text-end"><strong>{{ $summary['materials']['cement']['50kg'] }}</strong></td></tr>
                    <tr><td>Total:</td><td class="text-end"><strong>{{ $summary['materials']['cement']['kg'] }}</strong></td></tr>
                </table>
                <hr>
                <p class="mb-0">
                    <small class="text-muted">Biaya:</small><br>
                    <strong class="text-success">{{ $summary['materials']['cement']['cost'] }}</strong>
                </p>
            </div>
        </div>

        <!-- Pasir -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header" style="background-color: #8B4513; color: white;">
                <h5 class="mb-0">
                    <i class="fas fa-mountain"></i> Pasir
                </h5>
            </div>
            <div class="card-body">
                @if($brickCalculation->sand)
                    <p class="mb-2"><strong>{{ $brickCalculation->sand->brand }}</strong></p>
                @endif
                <table class="table table-sm">
                    <tr><td>Karung:</td><td class="text-end"><strong>{{ number_format($brickCalculation->sand_sak, 2) }} karung</strong></td></tr>
                    <tr><td>Berat:</td><td class="text-end"><strong>{{ number_format($brickCalculation->sand_kg, 2) }} kg</strong></td></tr>
                    <tr><td>Volume:</td><td class="text-end"><strong>{{ number_format($brickCalculation->sand_m3, 6) }} m³</strong></td></tr>
                </table>
                <hr>
                <p class="mb-0">
                    <small class="text-muted">Biaya:</small><br>
                    <strong class="text-success">Rp {{ number_format($brickCalculation->sand_total_cost, 0, ',', '.') }}</strong>
                </p>
            </div>
        </div>

        <!-- Air -->
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tint"></i> Air
                </h5>
            </div>
            <div class="card-body">
                <h3 class="text-info mb-0">{{ $summary['materials']['water']['liters'] }}</h3>
                <p class="text-muted mb-0">Kebutuhan air untuk adukan</p>
            </div>
        </div>
    </div>

    <!-- Total Cost -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body bg-success text-white text-center py-4">
                    <h4 class="mb-2">Total Estimasi Biaya Material</h4>
                    <h1 class="mb-0 display-3">{{ $summary['total_cost'] }}</h1>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Grid Layout */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.material-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .info-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .material-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 767px) {
    .info-grid,
    .material-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .btn, .card-header {
        display: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>
@endpush
@endsection