@extends('layouts.app')

@section('content')
<div class="card">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-file-alt text-primary"></i>
                Detail Perhitungan
            </h2>
            <p class="text-muted mb-0">
                {{ $materialCalculation->project_name ?: 'Perhitungan Tanpa Nama' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('material-calculations.log') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('material-calculations.edit', $materialCalculation) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button type="button" class="btn btn-info" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Ringkasan Perhitungan -->
    <div class="row mb-4">
        <div class="col-12 col-lg-7">
            <h3 class="mb-3"><i class="bi bi-info-circle text-primary"></i> Ringkasan Perhitungan</h3>
            <div class="table-container">
                <table>
                    <tbody>
                        <tr>
                            <th colspan="2">Informasi Project</th>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal</td>
                            <td class="text-end">
                                <strong>{{ $materialCalculation->created_at->format('d F Y, H:i') }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Project</td>
                            <td class="text-end">
                                <strong>{{ $materialCalculation->project_name ?: '-' }}</strong>
                            </td>
                        </tr>
                        @if($materialCalculation->notes)
                        <tr>
                            <td class="text-muted">Catatan</td>
                            <td class="text-end">
                                {{ $materialCalculation->notes }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="2">Dimensi Dinding</th>
                        </tr>
                        <tr>
                            <td>Panjang</td>
                            <td class="text-end">
                                {{ number_format($materialCalculation->wall_length, 2) }} m
                            </td>
                        </tr>
                        <tr>
                            <td>Tinggi</td>
                            <td class="text-end">
                                {{ number_format($materialCalculation->wall_height, 2) }} m
                            </td>
                        </tr>
                        <tr>
                            <td>Luas</td>
                            <td class="text-end">
                                {{ number_format($materialCalculation->wall_area, 2) }} m<span class="raise">2</span>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">Pemasangan & Adukan</th>
                        </tr>
                        <tr>
                            <td>Jenis pemasangan</td>
                            <td class="text-end">
                                {{ $summary['brick_info']['type'] }}
                            </td>
                        </tr>
                        <tr>
                            <td>Tebal adukan</td>
                            <td class="text-end">
                                {{ $summary['mortar_info']['thickness'] }}
                            </td>
                        </tr>
                        <tr>
                            <td>Formula adukan</td>
                            <td class="text-end">
                                <strong>{{ $summary['mortar_info']['formula'] }}</strong>
                                @if($materialCalculation->use_custom_ratio)
                                    <br>
                                    <span class="badge bg-warning text-dark mt-1">
                                        Custom ratio {{ $materialCalculation->custom_cement_ratio }}:{{ $materialCalculation->custom_sand_ratio }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12 col-lg-5 mt-3 mt-lg-0">
            <h3 class="mb-3"><i class="bi bi-cash-stack text-success"></i> Total Biaya</h3>
            <div class="summary-total">
                <p class="text-muted mb-1">Total estimasi biaya material</p>
                <div class="summary-total-amount">
                    {{ $summary['total_cost'] }}
                </div>
                <p class="text-muted mb-0">
                    Luas dinding: {{ number_format($materialCalculation->wall_area, 2) }} m<span class="raise">2</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Rincian Material & Harga -->
    <h3 class="mb-3"><i class="bi bi-box-seam text-primary"></i> Rincian Material & Harga</h3>
    <div class="table-container">
        @php
            $params = $materialCalculation->calculation_params ?? [];
            $brickDim = $params['brick_dimensions'] ?? null;
        @endphp
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Data material</th>
                    <th>Kebutuhan</th>
                    <th>Harga satuan</th>
                    <th>Total biaya</th>
                </tr>
            </thead>
            <tbody>
                <!-- Bata -->
                <tr>
                    <td>Bata</td>
                    <td>
                        @if($materialCalculation->brick)
                            <div><strong>{{ $materialCalculation->brick->brand }} {{ $materialCalculation->brick->type }}</strong></div>
                        @endif
                        @if($brickDim)
                            <div class="text-muted small">
                                {{ $brickDim['length'] ?? '-' }} x {{ $brickDim['width'] ?? '-' }} x {{ $brickDim['height'] ?? '-' }} cm
                            </div>
                        @elseif(!$materialCalculation->brick)
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        {{ number_format($materialCalculation->brick_quantity, 2) }} buah
                    </td>
                    <td class="text-end">
                        @if($materialCalculation->brick_price_per_piece)
                            Rp {{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }} / buah
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        Rp {{ number_format($materialCalculation->brick_total_cost, 0, ',', '.') }}
                    </td>
                </tr>

                <!-- Semen -->
                <tr>
                    <td>Semen</td>
                    <td>
                        @if($materialCalculation->cement)
                            <div><strong>{{ $materialCalculation->cement->brand }}</strong></div>
                        @endif
                        <div class="text-muted small">
                            {{ $materialCalculation->cement_package_weight ?? 50 }} kg / sak
                        </div>
                    </td>
                    <td class="text-end">
                        {{ number_format($materialCalculation->cement_quantity_sak ?? $materialCalculation->cement_quantity_50kg, 2) }} sak
                        <br>
                        <span class="text-muted small">
                            ({{ number_format($materialCalculation->cement_kg, 2) }} kg)
                        </span>
                    </td>
                    <td class="text-end">
                        @if($materialCalculation->cement_price_per_sak)
                            Rp {{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }} / sak
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        Rp {{ number_format($materialCalculation->cement_total_cost, 0, ',', '.') }}
                    </td>
                </tr>

                <!-- Pasir -->
                <tr>
                    <td>Pasir</td>
                    <td>
                        @if($materialCalculation->sand)
                            <div><strong>{{ $materialCalculation->sand->brand }}</strong></div>
                        @endif
                        <div class="text-muted small">
                            {{ number_format($materialCalculation->sand_m3, 6) }} m<span class="raise">3</span>
                        </div>
                    </td>
                    <td class="text-end">
                        {{ number_format($materialCalculation->sand_sak, 2) }} karung
                        <br>
                        <span class="text-muted small">
                            ({{ number_format($materialCalculation->sand_kg, 2) }} kg)
                        </span>
                    </td>
                    <td class="text-end">
                        @if($materialCalculation->sand_price_per_m3)
                            Rp {{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }} / m<span class="raise">3</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        Rp {{ number_format($materialCalculation->sand_total_cost, 0, ',', '.') }}
                    </td>
                </tr>

                <!-- Air -->
                <tr>
                    <td>Air</td>
                    <td class="text-muted">Kebutuhan air untuk adukan</td>
                    <td class="text-end">
                        {{ number_format($materialCalculation->water_liters, 2) }} liter
                    </td>
                    <td class="text-end text-muted">Tidak dihitung</td>
                    <td class="text-end text-muted">-</td>
                </tr>

                <!-- Total -->
                <tr class="table-total-row">
                    <td colspan="4" class="text-end"><strong>Total biaya material</strong></td>
                    <td class="text-end">
                        <strong>{{ $summary['total_cost'] }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
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
                        <td class="text-end"><strong>{{ $materialCalculation->created_at->format('d F Y, H:i') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Project:</td>
                        <td class="text-end"><strong>{{ $materialCalculation->project_name ?: '-' }}</strong></td>
                    </tr>
                    @if($materialCalculation->notes)
                    <tr>
                        <td class="text-muted" colspan="2">
                            Catatan:<br>
                            <span class="text-dark">{{ $materialCalculation->notes }}</span>
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
                            @if($materialCalculation->use_custom_ratio)
                                <br><span class="badge bg-warning text-dark">Custom Ratio</span>
                            @endif
                        </td>
                    </tr>
                    @if($materialCalculation->use_custom_ratio)
                    <tr>
                        <td>Rasio Custom:</td>
                        <td class="text-end">
                            <strong>{{ $materialCalculation->custom_cement_ratio }}:{{ $materialCalculation->custom_sand_ratio }}</strong>
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
                @if($materialCalculation->brick)
                    <p class="mb-2"><strong>{{ $materialCalculation->brick->brand }} {{ $materialCalculation->brick->type }}</strong></p>
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
                @if($materialCalculation->cement)
                    <p class="mb-2"><strong>{{ $materialCalculation->cement->brand }}</strong></p>
                @endif
                <table class="table table-sm">
                    <tr><td>{{ $summary['materials']['cement']['package_weight'] }} kg:</td><td class="text-end"><strong>{{ $summary['materials']['cement']['quantity_sak'] }}</strong></td></tr>
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
                @if($materialCalculation->sand)
                    <p class="mb-2"><strong>{{ $materialCalculation->sand->brand }}</strong></p>
                @endif
                <table class="table table-sm">
                    <tr><td>Karung:</td><td class="text-end"><strong>{{ number_format($materialCalculation->sand_sak, 2) }} karung</strong></td></tr>
                    <tr><td>Berat:</td><td class="text-end"><strong>{{ number_format($materialCalculation->sand_kg, 2) }} kg</strong></td></tr>
                    <tr><td>Volume:</td><td class="text-end"><strong>{{ number_format($materialCalculation->sand_m3, 6) }} m³</strong></td></tr>
                </table>
                <hr>
                <p class="mb-0">
                    <small class="text-muted">Biaya:</small><br>
                    <strong class="text-success">Rp {{ number_format($materialCalculation->sand_total_cost, 0, ',', '.') }}</strong>
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
    .summary-total {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 18px 20px;
        background: linear-gradient(135deg, #ecfdf5 0%, #dcfce7 100%);
        text-align: center;
    }

    .summary-total-amount {
        font-size: 24px;
        font-weight: 700;
        color: #166534;
        margin-bottom: 6px;
    }

    .table-total-row td {
        border-top: 2px solid #e2e8f0;
        font-size: 14px;
    }

    /* Grid Layout lama (disembunyikan untuk tampilan baru) */
    .info-grid {
        display: none !important;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .material-grid {
        display: none !important;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Sembunyikan kartu total lama di bagian bawah */
    .card.border-0.shadow-lg {
        display: none !important;
    }

    /* Responsive adjustments (tidak berpengaruh karena disembunyikan) */
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
        .btn,
        .card-header,
        .nav {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            padding: 16px;
        }
    }
</style>
@endpush
@endsection
