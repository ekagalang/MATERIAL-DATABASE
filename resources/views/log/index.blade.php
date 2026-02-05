@extends('layouts.app')

@section('title', 'Log Aplikasi')

@section('content')
    <div class="log-page">
        <div class="log-hero">
            <div>
                <h2 class="log-title">Log Aplikasi</h2>
                <p class="log-subtitle">
                    Monitor semua activity sistem, error, dan event debugging dalam satu halaman.
                </p>
            </div>
            <div class="log-timezone-pill">
                <i class="bi bi-clock-history"></i>
                Zona waktu: {{ $displayTimezone }} (GMT{{ $displayTimezoneOffset }})
            </div>
        </div>

        <div class="log-stats-grid">
            <div class="log-stat-card">
                <div class="log-stat-label">Total Log</div>
                <div class="log-stat-value">{{ number_format($totalEntries) }}</div>
            </div>
            <div class="log-stat-card">
                <div class="log-stat-label">Hasil Filter</div>
                <div class="log-stat-value">{{ number_format($filteredEntriesCount) }}</div>
            </div>
            <div class="log-stat-card">
                <div class="log-stat-label">File Aktif</div>
                <div class="log-stat-value">{{ $filters['file'] === 'all' ? 'Semua' : $filters['file'] }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('logs.index') }}" class="log-filter-card">
            <div class="log-filter-grid">
                <div>
                    <label class="log-label" for="file">File</label>
                    <select name="file" id="file" class="log-control">
                        <option value="all" {{ $filters['file'] === 'all' ? 'selected' : '' }}>Semua File</option>
                        @foreach ($fileOptions as $file)
                            <option value="{{ $file }}" {{ $filters['file'] === $file ? 'selected' : '' }}>{{ $file }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="log-label" for="status">Status</label>
                    <select name="status" id="status" class="log-control">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="log-label" for="level">Level</label>
                    <select name="level" id="level" class="log-control">
                        @foreach ($levelOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['level'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="log-label" for="date">Tanggal</label>
                    <input type="date" name="date" id="date" value="{{ $filters['date'] }}" class="log-control">
                </div>

                <div>
                    <label class="log-label" for="per_page">Baris/Halaman</label>
                    <select name="per_page" id="per_page" class="log-control">
                        @foreach ($perPageOptions as $perPage)
                            <option value="{{ $perPage }}" {{ (int) $filters['per_page'] === $perPage ? 'selected' : '' }}>
                                {{ $perPage }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="log-search-field">
                    <label class="log-label" for="search">Cari</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="log-control"
                        value="{{ $filters['search'] }}"
                        placeholder="Cari pesan, detail, level, channel, atau file...">
                </div>
            </div>

            <div class="log-filter-actions">
                <button type="submit" class="btn btn-primary-glossy btn-sm">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <a href="{{ route('logs.index') }}" class="btn btn-secondary-glossy btn-sm">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </form>

        <div class="table-container log-table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 175px;">Waktu</th>
                        <th style="width: 105px;">Level</th>
                        <th style="width: 105px;">Status</th>
                        <th style="width: 130px;">Channel</th>
                        <th style="width: 180px;">File</th>
                        <th>Pesan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="log-time-cell">{{ $log['timestamp'] }}</td>
                            <td><span class="log-badge log-level-{{ $log['level'] }}">{{ strtoupper($log['level']) }}</span></td>
                            <td><span class="log-badge log-status-{{ $log['status'] }}">{{ strtoupper($log['status']) }}</span></td>
                            <td class="log-text-cell">{{ $log['channel'] }}</td>
                            <td class="log-text-cell">{{ $log['file'] }}</td>
                            <td>
                                <div class="log-message">{{ $log['message'] }}</div>
                                @if ($log['details'] !== '')
                                    <details class="log-details">
                                        <summary>Lihat detail</summary>
                                        <pre>{{ $log['details'] }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="log-empty">
                                Tidak ada log yang cocok dengan filter saat ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="log-pagination-wrap">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <style>
        .log-page {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .log-hero {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
            padding: 16px 18px;
            border-radius: 16px;
            background: linear-gradient(120deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .log-title {
            margin: 0;
            font-size: 28px;
            letter-spacing: 0.01em;
        }

        .log-subtitle {
            margin: 6px 0 0;
            font-size: 13px;
            color: #cbd5e1;
        }

        .log-timezone-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(148, 163, 184, 0.4);
            font-size: 12px;
            color: #e2e8f0;
            font-weight: 600;
        }

        .log-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 10px;
        }

        .log-stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .log-stat-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .log-stat-value {
            font-size: 21px;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.2;
            word-break: break-word;
        }

        .log-filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
        }

        .log-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }

        .log-search-field {
            grid-column: span 2;
        }

        .log-label {
            display: block;
            font-size: 12px;
            color: #475569;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .log-control {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 8px 10px;
            font-size: 13px;
            color: #111827;
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .log-control:focus {
            border-color: #22d3ee;
            outline: none;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.16);
        }

        .log-filter-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .log-table-container {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .log-table-container table {
            margin-bottom: 0;
        }

        .log-table-container thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-bottom: 1px solid #e2e8f0;
        }

        .log-table-container tbody tr:hover td {
            background: #f8fafc !important;
        }

        .log-time-cell {
            font-size: 12px;
            color: #334155;
            white-space: nowrap;
        }

        .log-text-cell {
            font-size: 12px;
            color: #334155;
        }

        .log-message {
            font-size: 13px;
            color: #0f172a;
            font-weight: 600;
        }

        .log-details {
            margin-top: 6px;
        }

        .log-details summary {
            font-size: 12px;
            cursor: pointer;
            color: #2563eb;
        }

        .log-details pre {
            margin-top: 6px;
            font-size: 11px;
            white-space: pre-wrap;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            max-height: 320px;
            overflow: auto;
        }

        .log-empty {
            text-align: center;
            color: #64748b;
            padding: 24px;
        }

        .log-pagination-wrap {
            margin-top: 2px;
        }

        .log-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .log-level-emergency,
        .log-level-alert,
        .log-level-critical,
        .log-level-error,
        .log-status-error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .log-level-warning,
        .log-status-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .log-level-info,
        .log-level-notice,
        .log-status-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .log-level-debug,
        .log-status-debug {
            background: #e2e8f0;
            color: #334155;
        }

        .log-status-success {
            background: #dcfce7;
            color: #166534;
        }

        .log-status-other {
            background: #ede9fe;
            color: #5b21b6;
        }

        @media (max-width: 860px) {
            .log-search-field {
                grid-column: span 1;
            }

            .log-title {
                font-size: 24px;
            }

            .log-subtitle {
                font-size: 12px;
            }
        }
    </style>
@endsection
