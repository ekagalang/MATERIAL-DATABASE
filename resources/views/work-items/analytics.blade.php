@extends('layouts.app')

@section('title', 'Analytics - ' . $formula['name'])

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                    <i class="bi bi-graph-up text-primary me-2"></i>Analytics Material
                </h2>
                <p class="text-muted mb-0" style="font-size: 14px;">
                    {{ $formula['name'] }}
                </p>
            </div>
            <a href="{{ route('work-items.index') }}" class="btn-cancel" style="border: 1px solid #64748b; background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($analytics['total_calculations'] > 0)
        <!-- Summary Cards -->
        <div class="container mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card" style="background: linear-gradient(135deg, #891313 0%, #a61515 100%); color: white; border: none; padding: 20px; border-radius: 12px;">
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Perhitungan</div>
                        <div style="font-size: 32px; font-weight: 700;">{{ number_format($analytics['total_calculations']) }}</div>
                        <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">Data tersimpan</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; border: none; padding: 20px; border-radius: 12px;">
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Luas</div>
                        <div style="font-size: 32px; font-weight: 700;">{{ number_format($analytics['total_area'], 2) }}</div>
                        <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">M2</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card" style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: white; border: none; padding: 20px; border-radius: 12px;">
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Biaya Material</div>
                        <div style="font-size: 28px; font-weight: 700;">Rp {{ number_format($analytics['total_brick_cost'] + $analytics['total_cement_cost'] + $analytics['total_sand_cost'], 0, ',', '.') }}</div>
                        <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">Akumulasi</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 20px; border-radius: 12px;">
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Rata-rata / M2</div>
                        <div style="font-size: 28px; font-weight: 700;">Rp {{ number_format($analytics['avg_cost_per_m2'], 0, ',', '.') }}</div>
                        <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">Per meter persegi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Material Dominan -->
        <div class="container">
            <div class="row g-4">
                <!-- Bata Dominan -->
                <div class="col-md-4">
                    <div class="card" style="padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;">
                            <i class="bi bi-bricks" style="color: #dc2626;"></i> Bata Paling Sering Digunakan
                        </h3>
                        @if(count($analytics['brick_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                @foreach($analytics['brick_counts'] as $brand => $data)
                                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 700; color: #991b1b; font-size: 14px;">{{ $brand }}</span>
                                            <span style="background: #dc2626; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            <div>{{ $data['brick']->type ?? '-' }}</div>
                                            <div>{{ $data['brick']->dimension_length }}×{{ $data['brick']->dimension_width }}×{{ $data['brick']->dimension_height }} cm</div>
                                            <div style="font-weight: 600; color: #059669;">Rp {{ number_format($data['brick']->price_per_piece, 0, ',', '.') }}/bh</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted">Tidak ada data</div>
                        @endif
                    </div>
                </div>

                <!-- Semen Dominan -->
                <div class="col-md-4">
                    <div class="card" style="padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;">
                            <i class="bi bi-bucket-fill" style="color: #2563eb;"></i> Semen Paling Sering Digunakan
                        </h3>
                        @if(count($analytics['cement_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                @foreach($analytics['cement_counts'] as $brand => $data)
                                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $brand }}</span>
                                            <span style="background: #2563eb; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            <div>{{ $data['cement']->type ?? '-' }}</div>
                                            <div>{{ $data['cement']->package_weight_net }} Kg / {{ $data['cement']->package_unit }}</div>
                                            <div style="font-weight: 600; color: #059669;">Rp {{ number_format($data['cement']->package_price, 0, ',', '.') }}/{{ $data['cement']->package_unit }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted">Tidak ada data</div>
                        @endif
                    </div>
                </div>

                <!-- Pasir Dominan -->
                <div class="col-md-4">
                    <div class="card" style="padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px;">
                            <i class="bi bi-cone-striped" style="color: #d97706;"></i> Pasir Paling Sering Digunakan
                        </h3>
                        @if(count($analytics['sand_counts']) > 0)
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                @foreach($analytics['sand_counts'] as $brand => $data)
                                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span style="font-weight: 700; color: #92400e; font-size: 14px;">{{ $brand }}</span>
                                            <span style="background: #d97706; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                                                {{ $data['count'] }}x
                                            </span>
                                        </div>
                                        <div style="font-size: 11px; color: #64748b;">
                                            <div>{{ $data['sand']->type ?? '-' }}</div>
                                            <div>{{ $data['sand']->sand_name ?? '-' }}</div>
                                            <div style="font-weight: 600; color: #059669;">Rp {{ number_format($data['sand']->package_price, 0, ',', '.') }}/{{ $data['sand']->package_unit }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted">Tidak ada data</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container">
            <div class="card" style="padding: 60px; text-align: center; border-radius: 12px;">
                <div style="font-size: 64px; margin-bottom: 16px;">📊</div>
                <h3 style="color: #64748b; font-size: 18px; margin-bottom: 8px;">Belum Ada Data Analytics</h3>
                <p style="color: #94a3b8; font-size: 14px; margin-bottom: 24px;">
                    Belum ada perhitungan yang tersimpan untuk item pekerjaan ini.<br>
                    Mulai hitung untuk melihat analytics material.
                </p>
                <a href="{{ route('price-analysis.index', ['formula' => $formula['code']]) }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="bi bi-play-circle"></i> Mulai Hitung Analisa
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
