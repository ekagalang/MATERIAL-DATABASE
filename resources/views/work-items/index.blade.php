@extends('layouts.app')

@section('title', 'Katalog Item Pekerjaan (Formula)')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Daftar Item Pekerjaan</h2>

        <a href="{{ route('material-calculations.log') }}"
            class="btn btn-primary-glossy btn-sm">
            <i class="bi bi-clock-history"></i>
            Riwayat
        </a>
    </div>

    @if(count($formulas) > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">No</th>
                        <th>Item Pekerjaan</th>
                        <th>Keterangan</th>
                        <th style="width: 200px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($formulas as $index => $formula)
                    <tr>
                        <td style="text-align: center; font-weight: 600; color: var(--text-color);">
                            {{ $index + 1 }}
                        </td>
                        <td>
                            <div style="font-weight: 700; margin-bottom: 4px;">
                                {{ $formula['name'] }}
                            </div>
                        </td>
                        <td>
                            @php
                                $formulaAnalytics = $analytics[$formula['code']] ?? null;
                                $avgCost = $formulaAnalytics['avg_cost_per_m2'] ?? 0;
                                $totalCalcs = $formulaAnalytics['total'] ?? 0;
                            @endphp

                            @if($totalCalcs > 0 && $avgCost > 0)
                                <div style="color: #059669; line-height: 1.5; font-weight: 600;">
                                    <i class="bi bi-graph-up-arrow"></i>
                                    Rata-rata: <strong>Rp {{ number_format($avgCost, 0, ',', '.') }} / M2</strong>
                                </div>
                                <div>
                                    Berdasarkan {{ $totalCalcs }} perhitungan
                                </div>
                            @else
                                <div style="font-style: italic;">
                                    <i class="bi bi-info-circle"></i>
                                    Belum ada data perhitungan untuk item ini
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="{{ route('work-items.analytics', ['code' => $formula['code']]) }}"
                                   class="btn btn-secondary-glossy  btn-sm">
                                    <i class="bi bi-graph-up"></i> Analytics
                                </a>
                                <a href="{{ route('material-calculations.create', ['formula_code' => $formula['code']]) }}"
                                   class="btn btn-primary-glossy  btn-sm">
                                    <i class="bi bi-play-circle"></i> Hitung
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🧩</div>
            <p>Tidak ada formula yang terdaftar di sistem.</p>
        </div>
    @endif

<style>
    table tr td {
        padding: 8px 16px !important;
    }
    table tr:hover td {
        background: #fffafa !important;
    }

    table th {
        font-size: 14px !important;
        padding: 1px 16px !important;
    }

    h2 {
        color: var(--text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        text-shadow: var(--special-text-shadow);
        font-size: 32px !important;
        margin-bottom: 0px !important;
    }
</style>
@endsection