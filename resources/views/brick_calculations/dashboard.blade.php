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
                        Kalkulator Perhitungan Bata
                    </h2>
                    <p class="text-muted mb-0">Hitung kebutuhan material untuk pembangunan dinding bata</p>
                </div>
                <div>
                    <a href="{{ route('brick-calculations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Perhitungan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="fas fa-file-alt fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Perhitungan</h6>
                            <h3 class="mb-0">{{ $totalCalculations }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Estimasi Biaya</h6>
                            <h3 class="mb-0">Rp {{ number_format($totalCost, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="fas fa-chart-pie fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Jenis Pemasangan</h6>
                            <h3 class="mb-0">{{ $calculationsByType->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-bolt text-warning"></i> Aksi Cepat
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="{{ route('brick-calculations.create') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-calculator"></i> Hitung Baru
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('brick-calculations.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-history"></i> Riwayat
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('bricks.index') }}" class="btn btn-outline-info w-100">
                                <i class="fas fa-th"></i> Data Bata
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('cements.index') }}" class="btn btn-outline-success w-100">
                                <i class="fas fa-box"></i> Data Semen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Calculations -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-clock"></i> Perhitungan Terbaru
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($recentCalculations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Project</th>
                                        <th>Jenis</th>
                                        <th>Luas</th>
                                        <th>Biaya</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCalculations as $calc)
                                    <tr>
                                        <td class="text-nowrap">
                                            {{ $calc->created_at->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            <strong>{{ $calc->project_name ?: 'Tanpa Nama' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $calc->installationType->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($calc->wall_area, 2) }} m²</td>
                                        <td class="text-success">
                                            <strong>Rp {{ number_format($calc->total_material_cost, 0, ',', '.') }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('brick-calculations.show', $calc) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada perhitungan</p>
                            <a href="{{ route('brick-calculations.create') }}" class="btn btn-primary">
                                Buat Perhitungan Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics by Type -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar"></i> Statistik per Jenis
                    </h5>
                </div>
                <div class="card-body">
                    @if($calculationsByType->count() > 0)
                        @foreach($calculationsByType as $stat)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">{{ $stat->installationType->name ?? '-' }}</span>
                                <span class="text-muted">{{ $stat->count }} kali</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" 
                                     role="progressbar" 
                                     style="width: {{ ($stat->count / $totalCalculations) * 100 }}%">
                                </div>
                            </div>
                            <small class="text-muted">
                                Total: Rp {{ number_format($stat->total_cost, 0, ',', '.') }}
                            </small>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Belum ada data statistik</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection