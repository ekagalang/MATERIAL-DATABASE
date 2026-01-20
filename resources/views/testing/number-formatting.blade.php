@extends('layouts.app')

@section('title', 'Testing - Number Formatting')

@section('content')
<div class="container py-4">
    <style>
        .number-formatting-table th {
            background: #f8fafc !important;
            color: #0f172a !important;
            text-shadow: none !important;
            -webkit-text-stroke: 0 !important;
        }
    </style>
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Number Formatting Test</h1>
            <p class="text-muted mb-0">Cek apakah tampilan dinamis (tanpa pembulatan) sama dengan nilai untuk hitung.</p>
        </div>
        <span class="badge text-bg-warning">Testing only</span>
    </div>

    <div class="alert alert-info small mb-4">
        <div class="fw-semibold mb-1">Aturan yang dicek</div>
        <div>1) Jika integer &gt; 0: tampilkan hingga 2 digit setelah koma (tanpa pembulatan, trim nol).</div>
        <div>2) Jika integer = 0: tampilkan sampai 2 digit setelah digit non-zero pertama.</div>
        <div>3) Jika .00, tampil tanpa desimal.</div>
        <div>4) Nilai yang tampil dipakai untuk perhitungan.</div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-semibold">Kondisi contoh</div>
                <div class="small text-muted">Rumus contoh: nilai x {{ $factor }}</div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle number-formatting-table">
                    <thead class="table-light">
                        <tr>
                            <th>Kasus</th>
                            <th>Input</th>
                            <th class="text-end">Display sekarang</th>
                            <th class="text-end">Display target</th>
                            <th class="text-center">Status display</th>
                            <th class="text-end">Calc input sekarang</th>
                            <th class="text-end">Calc input target</th>
                            <th class="text-center">Status calc</th>
                            <th class="text-end">Hasil hitung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td><code>{{ $row['raw'] }}</code></td>
                                <td class="text-end">{{ $row['current_display'] }}</td>
                                <td class="text-end">{{ $row['target_display'] }}</td>
                                <td class="text-center">
                                    @if($row['display_ok'])
                                        <span class="badge text-bg-success">OK</span>
                                    @else
                                        <span class="badge text-bg-danger">NG</span>
                                    @endif
                                </td>
                                <td class="text-end"><code>{{ $row['current_calc'] }}</code></td>
                                <td class="text-end"><code>{{ $row['target_calc'] }}</code></td>
                                <td class="text-center">
                                    @if($row['calc_ok'])
                                        <span class="badge text-bg-success">OK</span>
                                    @else
                                        <span class="badge text-bg-danger">NG</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $row['calc_display'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small text-muted">
                Display sekarang memakai NumberHelper::format. Target memakai aturan dinamis tanpa pembulatan, lalu nilai itu dipakai untuk hitung.
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <div class="fw-semibold">Uji manual</div>
            <div class="small text-muted">Ketik angka sendiri dan lihat perbandingan otomatis.</div>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Input angka</label>
                    <input type="text" id="manualValue" class="form-control" value="10.259" inputmode="decimal">
                    <div class="form-text">Boleh pakai koma atau titik.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Faktor hitung</label>
                    <input type="text" id="manualFactor" class="form-control" value="{{ $factor }}" inputmode="decimal">
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">Output sekarang</div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Display</span>
                            <span id="manualCurrentDisplay">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Calc input</span>
                            <code id="manualCurrentCalc">-</code>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Status display</span>
                            <span id="manualDisplayStatus" class="badge text-bg-secondary">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Status calc</span>
                            <span id="manualCalcStatus" class="badge text-bg-secondary">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-2">Target dinamis</div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Display target</span>
                            <span id="manualTargetDisplay">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Calc input target</span>
                            <code id="manualTargetCalc">-</code>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Hasil hitung</span>
                            <span id="manualCalcResult">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <div class="fw-semibold">Data real (5 kalkulasi terakhir)</div>
            <div class="small text-muted">Ambil beberapa field numerik untuk cek format.</div>
        </div>
        <div class="card-body">
            @if(count($realRows))
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle number-formatting-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Waktu</th>
                                <th>Field</th>
                                <th>Raw</th>
                                <th class="text-end">Display sekarang</th>
                                <th class="text-end">Display target</th>
                                <th class="text-center">Status display</th>
                                <th class="text-end">Calc input sekarang</th>
                                <th class="text-end">Calc input target</th>
                                <th class="text-center">Status calc</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($realRows as $row)
                                <tr>
                                    <td>#{{ $row['calc_id'] }}</td>
                                    <td>{{ $row['created_at'] ?? '-' }}</td>
                                    <td>{{ $row['label'] }}</td>
                                    <td><code>{{ $row['raw'] }}</code></td>
                                    <td class="text-end">{{ $row['current_display'] }}</td>
                                    <td class="text-end">{{ $row['target_display'] }}</td>
                                    <td class="text-center">
                                        @if($row['display_ok'])
                                            <span class="badge text-bg-success">OK</span>
                                        @else
                                            <span class="badge text-bg-danger">NG</span>
                                        @endif
                                    </td>
                                    <td class="text-end"><code>{{ $row['current_calc'] }}</code></td>
                                    <td class="text-end"><code>{{ $row['target_calc'] }}</code></td>
                                    <td class="text-center">
                                        @if($row['calc_ok'])
                                            <span class="badge text-bg-success">OK</span>
                                        @else
                                            <span class="badge text-bg-danger">NG</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">Belum ada data kalkulasi untuk ditampilkan.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        const valueInput = document.getElementById('manualValue');
        const factorInput = document.getElementById('manualFactor');

        const currentDisplayEl = document.getElementById('manualCurrentDisplay');
        const currentCalcEl = document.getElementById('manualCurrentCalc');
        const displayStatusEl = document.getElementById('manualDisplayStatus');
        const calcStatusEl = document.getElementById('manualCalcStatus');
        const targetDisplayEl = document.getElementById('manualTargetDisplay');
        const targetCalcEl = document.getElementById('manualTargetCalc');
        const calcResultEl = document.getElementById('manualCalcResult');

        const normalizeInput = (value) => {
            const trimmed = String(value || '').trim().replace(',', '.');
            if (trimmed === '' || Number.isNaN(Number(trimmed))) return '0';
            return trimmed;
        };

        const formatDynamicPlain = (value) => {
            const normalized = normalizeInput(value);
            const num = Number(normalized);
            if (!Number.isFinite(num)) return '0';
            if (num === 0) return '0';

            const absValue = Math.abs(num);
            const epsilon = Math.min(absValue * 1e-12, 1e-6);
            const adjusted = num + (num >= 0 ? epsilon : -epsilon);
            const sign = adjusted < 0 ? '-' : '';
            const abs = Math.abs(adjusted);
            const intPart = Math.trunc(abs);

            if (intPart > 0) {
                const scaled = Math.trunc(abs * 100);
                const intDisplay = Math.trunc(scaled / 100).toString();
                let decPart = String(scaled % 100).padStart(2, '0');
                decPart = decPart.replace(/0+$/, '');
                return decPart ? `${sign}${intDisplay}.${decPart}` : `${sign}${intDisplay}`;
            }

            let fraction = abs;
            let digits = '';
            let firstNonZeroIndex = null;
            const maxDigits = 30;

            for (let i = 0; i < maxDigits; i++) {
                fraction *= 10;
                const digit = Math.floor(fraction + 1e-12);
                fraction -= digit;
                digits += String(digit);

                if (digit !== 0 && firstNonZeroIndex === null) {
                    firstNonZeroIndex = i;
                }

                if (firstNonZeroIndex !== null && i >= firstNonZeroIndex + 1) {
                    break;
                }
            }

            digits = digits.replace(/0+$/, '');
            if (!digits) return '0';
            return `${sign}0.${digits}`;
        };

        const formatDynamicLocale = (value) => {
            const plain = formatDynamicPlain(value);
            let sign = '';
            let raw = plain;
            if (raw.startsWith('-')) {
                sign = '-';
                raw = raw.slice(1);
            }
            const parts = raw.split('.');
            const intPart = (parts[0] || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const decPart = parts[1] || '';
            if (!decPart) {
                return `${sign}${intPart}`;
            }
            return `${sign}${intPart},${decPart}`;
        };

        const normalizeDynamic = (value) => {
            const plain = formatDynamicPlain(value);
            if (!plain || plain === '-' || plain === '-0') {
                return '0';
            }
            return plain;
        };

        const setStatus = (el, ok) => {
            el.textContent = ok ? 'OK' : 'NG';
            el.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-secondary');
            el.classList.add(ok ? 'text-bg-success' : 'text-bg-danger');
        };

        const updateManual = () => {
            const rawInput = normalizeInput(valueInput.value);
            const factor = Number(normalizeInput(factorInput.value));
            const rawNumber = Number(rawInput);

            const currentDisplay = formatDynamicLocale(rawNumber);
            const currentCalc = normalizeDynamic(rawNumber);

            const targetPlain = normalizeDynamic(rawInput);
            const targetDisplay = formatDynamicLocale(rawInput);

            const resultRaw = Number(targetPlain) * (Number.isFinite(factor) ? factor : 0);
            const resultDisplay = formatDynamicLocale(resultRaw);

            currentDisplayEl.textContent = currentDisplay || '-';
            currentCalcEl.textContent = currentCalc || '-';
            targetDisplayEl.textContent = targetDisplay || '-';
            targetCalcEl.textContent = targetPlain || '-';
            calcResultEl.textContent = resultDisplay || '-';

            setStatus(displayStatusEl, currentDisplay === targetDisplay);
            setStatus(calcStatusEl, currentCalc === targetPlain);
        };

        if (valueInput && factorInput) {
            valueInput.addEventListener('input', updateManual);
            factorInput.addEventListener('input', updateManual);
            updateManual();
        }
    })();
</script>
@endpush
