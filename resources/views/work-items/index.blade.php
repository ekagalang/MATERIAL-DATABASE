@extends('layouts.app')

@section('title', 'Katalog Item Pekerjaan (Formula)')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Daftar Item Pekerjaan</h2>

        <div style="font-size: 14px; color: #64748b; font-weight: 500;">
            Total: {{ count($formulas) }} Item Pekerjaan yang ada
        </div>
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
                        <td style="text-align: center; font-weight: 600; color: #94a3b8;">
                            {{ $index + 1 }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a; font-size: 14.5px; margin-bottom: 4px;">
                                {{ $formula['name'] }}
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 12.5px; color: #64748b; line-height: 1.5;">
                                {{ $formula['description'] ?? 'Formula analisa perhitungan kebutuhan material dan tenaga kerja.' }}
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="{{ route('work-items.analytics', ['code' => $formula['code']]) }}"
                                   class="btn btn-sm"
                                   style="background: #0ea5e9; color: white; flex: 1; justify-content: center; height: 38px; border: none;">
                                    <i class="bi bi-graph-up"></i> Analytics
                                </a>
                                <a href="{{ route('price-analysis.index', ['formula' => $formula['code']]) }}"
                                   class="btn btn-primary btn-sm"
                                   style="flex: 1; justify-content: center; height: 38px;">
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
</div>

<style>
    table tr td {
        padding: 20px 16px !important;
    }
    table tr:hover td {
        background: #fffafa !important;
    }
</style>
@endsection