@extends('layouts.app')

@section('title', 'Perbandingan Bata')

@section('content')
<div class="container py-4">
    {{-- HEADER & NAVIGATION --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary"><i class="bi bi-bar-chart-steps me-2"></i>Perbandingan Biaya Bata</h2>
            <p class="text-muted mb-0">
                Membandingkan <strong>{{ count($comparisons) }}</strong> jenis bata untuk dinding seluas 
                <strong>{{ number_format($wallArea, 2) }} m²</strong> ({{ $wallLength }}m x {{ $wallHeight }}m).
            </p>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali Pilih
        </a>
    </div>

    {{-- INFO MORTAR BASELINE --}}
    <div class="alert alert-light border-start border-4 border-info shadow-sm mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="text-info fs-3"><i class="bi bi-info-circle-fill"></i></div>
            <div>
                <strong class="text-dark">Basis Perhitungan Adukan:</strong><br>
                Menggunakan material termurah saat ini: 
                <span class="badge bg-secondary">{{ $refCement->brand }}</span> dan 
                <span class="badge bg-warning text-dark">{{ $refSand->brand }}</span> 
                dengan tebal spesi <strong>{{ $mortarThickness }} cm</strong>.
            </div>
        </div>
    </div>

    {{-- COMPARISON TABLE --}}
    <div class="card shadow-sm border-0 mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="py-3 bg-primary text-white">Ranking</th>
                        <th class="py-3 bg-primary text-white text-start ps-3">Merek & Jenis Bata</th>
                        <th class="py-3 bg-primary text-white">Harga Bata /pcs</th>
                        <th class="py-3 bg-primary text-white">Kebutuhan Bata</th>
                        <th class="py-3 bg-primary text-white">Est. Biaya Mortar</th>
                        <th class="py-3 bg-primary text-white">Total Biaya Proyek</th>
                        <th class="py-3 bg-primary text-white">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisons as $index => $item)
                        <tr class="{{ $index == 0 ? 'bg-success bg-opacity-10 border-start border-5 border-success' : '' }}">
                            <td class="fw-bold fs-5 text-secondary">
                                @if($index == 0) 🏆 #1 @else #{{ $index + 1 }} @endif
                            </td>
                            <td class="text-start ps-3">
                                <div class="fw-bold text-dark">{{ $item['brick']->brand }}</div>
                                <div class="text-muted small">{{ $item['brick']->type }}</div>
                                <div class="text-muted small fst-italic">{{ $item['brick']->dimension_length }}x{{ $item['brick']->dimension_width }}x{{ $item['brick']->dimension_height }} cm</div>
                            </td>
                            <td>
                                Rp {{ number_format($item['result']['brick_price_per_piece'], 0, ',', '.') }}
                            </td>
                            <td>
                                <div class="fw-bold">{{ number_format($item['result']['total_bricks'], 0) }} pcs</div>
                                <small class="text-muted">Rp {{ number_format($item['result']['total_brick_price'], 0, ',', '.') }}</small>
                            </td>
                            <td>
                                {{-- Hitung biaya mortar saja (Total - Bata) --}}
                                @php $mortarCost = $item['total_cost'] - $item['result']['total_brick_price']; @endphp
                                <div class="text-primary fw-bold">Rp {{ number_format($mortarCost, 0, ',', '.') }}</div>
                                <small class="text-muted">Semen + Pasir</small>
                            </td>
                            <td class="bg-light">
                                <div class="fw-bold text-success fs-5">Rp {{ number_format($item['total_cost'], 0, ',', '.') }}</div>
                                <small class="text-muted fw-bold">Rp {{ number_format($item['cost_per_m2'], 0, ',', '.') }} / m²</small>
                            </td>
                            <td>
                                <a href="{{ route('material-calculations.create', [
                                    'brick_id' => $item['brick']->id,
                                    'wall_length' => $wallLength,
                                    'wall_height' => $wallHeight,
                                    'mortar_thickness' => $mortarThickness,
                                    'installation_type_id' => $requestData['installation_type_id'],
                                    'formula_code' => $requestData['formula_code']
                                ]) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                    Pilih <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection