@extends('layouts.app')

@section('title', 'Preview Paket Pekerjaan')

@section('content')
    @php
        $bundleName = $bundle['name'] ?? 'Paket Pekerjaan';
        $bundleItems = $bundle['items'] ?? [];
        $bundleCombinations = $bundle['combinations'] ?? [];
        $bundleBestTotal = (float) ($bundle['best_total'] ?? 0);
    @endphp

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button type="button" id="btnResetSession" class="btn-cancel"
                style="border: 1px solid #891313; background-color: transparent; color: #891313; padding: 10px 20px; border-radius: 10px;">
                <i class="bi bi-arrow-left"></i> Kembali
            </button>
            <h2 class="fw-bold mb-0" style="color: #0f172a;">Preview Paket Pekerjaan</h2>
            <div></div>
        </div>

        <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e2e8f0;">
            <div class="card-body">
                <h4 class="fw-bold mb-2">{{ $bundleName }}</h4>
                <div class="text-muted mb-2">Total item pekerjaan: {{ count($bundleItems) }}</div>
                <div class="fw-semibold">
                    Total terbaik paket:
                    <span class="text-danger">Rp {{ number_format($bundleBestTotal, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e2e8f0;">
            <div class="card-header bg-white fw-bold">Kombinasi Paket (Akumulasi Per Item)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Label Kombinasi</th>
                                <th style="width: 220px;">Total Biaya Paket</th>
                                <th>Rincian Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bundleCombinations as $combo)
                                <tr>
                                    <td class="fw-semibold">{{ $combo['label'] ?? '-' }}</td>
                                    <td class="fw-bold text-danger">
                                        Rp {{ number_format((float) ($combo['grand_total'] ?? 0), 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if (!empty($combo['items']) && is_array($combo['items']))
                                            @foreach ($combo['items'] as $itemRow)
                                                <div class="small mb-1">
                                                    <strong>{{ $itemRow['title'] ?? '-' }}</strong>
                                                    ({{ $itemRow['work_type'] ?? '-' }})
                                                    <span class="text-muted">
                                                        - Rp
                                                        {{ number_format((float) ($itemRow['grand_total'] ?? 0), 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        Tidak ada kombinasi paket yang lengkap untuk semua item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="border-radius: 14px; border: 1px solid #e2e8f0;">
            <div class="card-header bg-white fw-bold">Rincian Item Pekerjaan</div>
            <div class="card-body">
                @forelse ($bundleItems as $index => $itemPayload)
                    @php
                        $itemTitle = $itemPayload['title'] ?? 'Item ' . ($index + 1);
                        $itemWorkType = $itemPayload['work_type'] ?? ($itemPayload['requestData']['work_type'] ?? '-');
                        $itemCombinations = [];
                        $projects = $itemPayload['projects'] ?? [];
                        foreach ($projects as $project) {
                            $projectCombinations = $project['combinations'] ?? [];
                            if (!is_array($projectCombinations)) {
                                continue;
                            }
                            foreach ($projectCombinations as $label => $rows) {
                                if (!is_array($rows) || empty($rows)) {
                                    continue;
                                }
                                $minRow = null;
                                foreach ($rows as $row) {
                                    if (!is_array($row) || !isset($row['result']['grand_total'])) {
                                        continue;
                                    }
                                    if ($minRow === null || ($row['result']['grand_total'] ?? PHP_FLOAT_MAX) < ($minRow['result']['grand_total'] ?? PHP_FLOAT_MAX)) {
                                        $minRow = $row;
                                    }
                                }
                                if ($minRow) {
                                    $itemCombinations[$label] = $minRow;
                                }
                            }
                        }
                    @endphp

                    <div class="mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold mb-1">{{ $itemTitle }}</h6>
                        <div class="text-muted small mb-2">Work type: {{ $itemWorkType }}</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Label</th>
                                        <th style="width: 220px;">Grand Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($itemCombinations as $label => $row)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>
                                                Rp
                                                {{ number_format((float) ($row['result']['grand_total'] ?? 0), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted">Tidak ada kombinasi untuk item ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada item pekerjaan di paket.</div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const baseCreateUrl = "{{ $requestData['referrer'] ?? route('material-calculations.create') }}";
            const createPageUrl = baseCreateUrl.includes('?') ?
                `${baseCreateUrl}&resume=1` :
                `${baseCreateUrl}?resume=1`;

            const sessionPayload = @json($requestData ?? []);
            if (sessionPayload && Object.keys(sessionPayload).length) {
                try {
                    localStorage.setItem('materialCalculationSession', JSON.stringify({
                        updatedAt: Date.now(),
                        data: sessionPayload,
                        autoSubmit: false,
                        normalized: true,
                    }));
                } catch (error) {
                    console.warn('Failed to cache calculation session', error);
                }
            }

            document.getElementById('btnResetSession')?.addEventListener('click', function() {
                window.location.href = createPageUrl;
            });
        });
    </script>
@endsection
