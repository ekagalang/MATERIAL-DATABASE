{{-- Partial View: Ceramic Combinations Table --}}
{{-- Loaded via AJAX when user clicks ceramic tab --}}

@php
    $isGroupMode = $isGroupMode ?? false;
    $contextType = $ceramic->type ?? null;
    $contextLength = (float)($ceramic->dimension_length ?? 0);
    $contextWidth = (float)($ceramic->dimension_width ?? 0);
    $modalId = 'ceramicAllPriceModal-' . ($ceramic->id ?? 'group') . '-' . (int)$contextLength . 'x' . (int)$contextWidth;
    $matchesCeramicContext = function ($item) use ($contextType, $contextLength, $contextWidth) {
        if (!isset($item['ceramic'])) {
            return true;
        }
        $candidate = $item['ceramic'];
        if ($contextType && isset($candidate->type) && $candidate->type !== $contextType) {
            return false;
        }
        $candidateLength = (float)($candidate->dimension_length ?? 0);
        $candidateWidth = (float)($candidate->dimension_width ?? 0);
        if ($contextLength > 0 && $contextWidth > 0) {
            return ($candidateLength === $contextLength && $candidateWidth === $contextWidth)
                || ($candidateLength === $contextWidth && $candidateWidth === $contextLength);
        }
        return true;
    };
@endphp

<div id="preview-top"></div>
<div class="ceramic-combinations-content">
<div class="d-flex align-items-start justify-content-between mb-3">
    <div>
        @if($isGroupMode)
            <h5 class="fw-bold mb-1" style="color: #f59e0b;">
                Komparasi Merek
                <small class="text-muted">
                    ({{ $ceramic->type ?? 'Keramik' }} -
                    {{ (int)$ceramic->dimension_length }}x{{ (int)$ceramic->dimension_width }} cm)
                </small>
            </h5>
            <p class="text-muted small mb-0">Menampilkan kombinasi material Rekomendasi dari berbagai merek untuk ukuran ini.</p>
        @else
            <h5 class="fw-bold mb-0" style="color: #f59e0b;">
                {{ $ceramic->brand ?? 'Keramik' }}
                <small class="text-muted">
                    ({{ $ceramic->type ?? 'Lainnya' }} -
                    {{ (int)$ceramic->dimension_length }}x{{ (int)$ceramic->dimension_width }} cm)
                </small>
            </h5>
        @endif
    </div>

    <button type="button"
        style="border: 1px solid #f59e0b; background-color: transparent; color: #f59e0b;
        padding: 6px 16px; font-size: 13px; font-weight: 600; border-radius: 8px;
        display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;"
        data-ceramic-modal-target="{{ $modalId }}">
        <i class="bi bi-list-ul"></i> Daftar Harga
    </button>
</div>

    @if(!empty($combinations))
        @php
            $requestedFilters = $requestData['price_filters'] ?? [];
            $filterCategories = ['Rekomendasi', 'Populer', 'Ekonomis', 'Moderat', 'Premium'];
            if (in_array('custom', $requestedFilters, true)) {
                $filterCategories[] = 'Custom';
            }
            $priceRankMap = [];
            $needsPriceRanks = count(array_intersect($filterCategories, ['Ekonomis', 'Moderat', 'Premium'])) > 0;
            if ($needsPriceRanks && !empty($combinations)) {
                $priceCandidates = [];
                foreach ($combinations as $label => $items) {
                    foreach ($items as $item) {
                        if (!$matchesCeramicContext($item)) {
                            continue;
                        }
                        $priceCandidates[] = [
                            'label' => $label,
                            'item' => $item,
                            'grand_total' => (float)($item['result']['grand_total'] ?? 0),
                        ];
                    }
                }

                usort($priceCandidates, function ($a, $b) {
                    if ($a['grand_total'] === $b['grand_total']) {
                        return strcmp($a['label'], $b['label']);
                    }
                    return $a['grand_total'] <=> $b['grand_total'];
                });

                $totalCandidates = count($priceCandidates);
                if ($totalCandidates > 0) {
                    $EkonomisLimit = min(3, $totalCandidates);
                    $PremiumCount = min(3, $totalCandidates);
                    $PremiumStartIndex = $totalCandidates - $PremiumCount;

                    for ($i = 0; $i < $EkonomisLimit; $i++) {
                        $priceRankMap['Ekonomis ' . ($i + 1)] = $priceCandidates[$i];
                    }

                    for ($i = 0; $i < $PremiumCount; $i++) {
                        $priceRankMap['Premium ' . ($i + 1)] = $priceCandidates[$PremiumStartIndex + $i];
                    }

                    $middleIndex = (int) floor(($totalCandidates - 1) / 2);
                    $startIndex = max(0, $middleIndex - 1);
                    $medianCombos = array_slice($priceCandidates, $startIndex, 3);
                    $medianRank = 0;

                    foreach ($medianCombos as $combo) {
                        $medianRank++;
                        $priceRankMap['Moderat ' . $medianRank] = $combo;
                    }
                }
            }
            $rekapRows = [];

            foreach ($filterCategories as $filterType) {
                $maxCount = $filterType === 'Custom' ? 1 : 3;
                for ($i = 1; $i <= $maxCount; $i++) {
                    $key = $filterType . ' ' . $i;
                    $matchItem = null;

                    if (isset($priceRankMap[$key])) {
                        $matchItem = $priceRankMap[$key]['item'] ?? null;
                    } else {
                        foreach ($combinations as $label => $items) {
                            $labelParts = array_map('trim', explode('=', $label));
                            // Check if this group of items corresponds to the current key (e.g. "Rekomendasi 1")
                            if (in_array($key, $labelParts)) {
                                foreach ($items as $item) {
                                    if ($matchesCeramicContext($item)) {
                                        $matchItem = $item;
                                        break;
                                    }
                                }
                                break; 
                            }
                        }
                    }

                    $rekapRows[$key] = [
                        'key' => $key,
                        'item' => $matchItem,
                    ];
                }
            }

            $flatCombinations = array_values($rekapRows);
            $detailCombinations = array_values(array_filter($rekapRows, function ($row) {
                return !empty($row['item']);
            }));
            $hasCement = false;
            $hasSand = false;
            $hasCeramic = false;
            $hasNat = false;

            foreach ($detailCombinations as $combo) {
                $item = $combo['item'];
                if (isset($item['cement'])) $hasCement = true;
                if (isset($item['sand'])) $hasSand = true;
                if (isset($item['ceramic'])) $hasCeramic = true;
                if (isset($item['nat'])) $hasNat = true;
            }

            $labelColors = [
                'Semua' => [
                    1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                    2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#475569'],
                    3 => ['bg' => '#ffffff', 'border' => '#e2e8f0', 'text' => '#64748b'],
                ],
                'Rekomendasi' => [
                    1 => ['bg' => '#fca5a5', 'border' => '#f87171', 'text' => '#991b1b'],
                    2 => ['bg' => '#fecaca', 'border' => '#fca5a5', 'text' => '#dc2626'],
                    3 => ['bg' => '#fee2e2', 'border' => '#fecaca', 'text' => '#ef4444'],
                ],
                'Populer' => [
                    1 => ['bg' => '#93c5fd', 'border' => '#60a5fa', 'text' => '#1e40af'],
                    2 => ['bg' => '#bfdbfe', 'border' => '#93c5fd', 'text' => '#2563eb'],
                    3 => ['bg' => '#dbeafe', 'border' => '#bfdbfe', 'text' => '#3b82f6'],
                ],
                'Ekonomis' => [
                    1 => ['bg' => '#6ee7b7', 'border' => '#34d399', 'text' => '#065f46'],
                    2 => ['bg' => '#a7f3d0', 'border' => '#6ee7b7', 'text' => '#16a34a'],
                    3 => ['bg' => '#d1fae5', 'border' => '#a7f3d0', 'text' => '#22c55e'],
                ],
                'Moderat' => [
                    1 => ['bg' => '#fcd34d', 'border' => '#fbbf24', 'text' => '#92400e'],
                    2 => ['bg' => '#fde68a', 'border' => '#fcd34d', 'text' => '#b45309'],
                    3 => ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#d97706'],
                ],
                'Premium' => [
                    1 => ['bg' => '#d8b4fe', 'border' => '#c084fc', 'text' => '#6b21a8'],
                    2 => ['bg' => '#e9d5ff', 'border' => '#d8b4fe', 'text' => '#7c3aed'],
                    3 => ['bg' => '#f3e8ff', 'border' => '#e9d5ff', 'text' => '#9333ea'],
                ],
                'Custom' => [
                    1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                    2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                    3 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                ],
            ];

            $rekapLabelColors = [
                'Populer' => [
                    1 => ['bg' => '#93c5fd', 'text' => '#1e40af'],
                    2 => ['bg' => '#bfdbfe', 'text' => '#2563eb'],
                    3 => ['bg' => '#dbeafe', 'text' => '#3b82f6'],
                ],
                'Ekonomis' => [
                    1 => ['bg' => '#6ee7b7', 'text' => '#065f46'],
                    2 => ['bg' => '#a7f3d0', 'text' => '#16a34a'],
                    3 => ['bg' => '#d1fae5', 'text' => '#22c55e'],
                ],
                'Moderat' => [
                    1 => ['bg' => '#fcd34d', 'text' => '#92400e'],
                    2 => ['bg' => '#fde68a', 'text' => '#b45309'],
                    3 => ['bg' => '#fef3c7', 'text' => '#d97706'],
                ],
                'Premium' => [
                    1 => ['bg' => '#d8b4fe', 'text' => '#6b21a8'],
                    2 => ['bg' => '#e9d5ff', 'text' => '#7c3aed'],
                    3 => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
                ],
                'Rekomendasi' => [
                    1 => ['bg' => '#fca5a5', 'text' => '#991b1b'],
                    2 => ['bg' => '#fecaca', 'text' => '#dc2626'],
                    3 => ['bg' => '#fee2e2', 'text' => '#ef4444'],
                ],
                'Custom' => [
                    1 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                    2 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                    3 => ['bg' => '#f8fafc', 'text' => '#64748b'],
                ],
                'Semua' => [
                    1 => ['bg' => '#f8fafc', 'text' => '#475569'],
                    2 => ['bg' => '#f8fafc', 'text' => '#475569'],
                    3 => ['bg' => '#ffffff', 'text' => '#64748b'],
                ],
            ];

            $cementColors = ['#B0BEC5', '#90CAF9', '#CE93D8', '#80CBC4', '#CFD8DC', '#9FA8DA', '#B3E5FC', '#81D4FA'];
            $sandColors = ['#FFF59D', '#AED581', '#FFE0B2', '#DCE775', '#FFF176', '#C5E1A5', '#FFE082', '#F0F4C3'];
            $ceramicColors = ['#E0F7FA', '#E1F5FE', '#F3E5F5', '#FBE9E7', '#ECEFF1', '#FAFAFA', '#FFF3E0', '#E8EAF6'];
            $natColors = ['#CFD8DC', '#B0BEC5', '#90A4AE', '#78909C', '#D7CCC8', '#BCAAA4', '#A1887F', '#8D6E63'];
            $availableColors = array_merge($cementColors, $sandColors, $ceramicColors, $natColors);

            $signatureCount = [];
            foreach ($rekapRows as $row) {
                $item = $row['item'];
                if (empty($item)) {
                    continue;
                }
                $signature = ($item['ceramic']->id ?? '0') . '-' .
                    ($item['nat']->id ?? '0') . '-' .
                    ($item['cement']->id ?? '0') . '-' .
                    ($item['sand']->id ?? '0');
                if (!isset($signatureCount[$signature])) {
                    $signatureCount[$signature] = 0;
                }
                $signatureCount[$signature]++;
            }

            $globalColorMap = [];
            $combinationColorMap = [];
            $colorIndex = 0;

            foreach ($rekapRows as $key => $row) {
                $item = $row['item'];
                if (empty($item)) {
                    $globalColorMap[$key] = '#ffffff';
                    continue;
                }
                $signature = ($item['ceramic']->id ?? '0') . '-' .
                    ($item['nat']->id ?? '0') . '-' .
                    ($item['cement']->id ?? '0') . '-' .
                    ($item['sand']->id ?? '0');

                if (($signatureCount[$signature] ?? 0) > 1) {
                    if (isset($combinationColorMap[$signature])) {
                        $globalColorMap[$key] = $combinationColorMap[$signature];
                    } else {
                        $color = $availableColors[$colorIndex % count($availableColors)];
                        $globalColorMap[$key] = $color;
                        $combinationColorMap[$signature] = $color;
                        $colorIndex++;
                    }
                } else {
                    $globalColorMap[$key] = '#ffffff';
                }
            }

            $cementColorMap = [];
            $cementDataColorMap = [];
            $colorIndex = 0;
            foreach ($rekapRows as $key => $row) {
                $cement = $row['item']['cement'] ?? null;
                if (!$cement) {
                    $cementColorMap[$key] = '#ffffff';
                    continue;
                }
                $dataSignature = $cement->brand . '-' .
                    ($cement->color ?? '-') . '-' .
                    $cement->package_weight_net . '-' .
                    ($cement->price ?? '0');

                if (isset($cementDataColorMap[$dataSignature])) {
                    $cementColorMap[$key] = $cementDataColorMap[$dataSignature];
                } else {
                    $color = $cementColors[$colorIndex % count($cementColors)];
                    $cementColorMap[$key] = $color;
                    $cementDataColorMap[$dataSignature] = $color;
                    $colorIndex++;
                }
            }

            $sandColorMap = [];
            $sandDataColorMap = [];
            $colorIndex = 0;
            foreach ($rekapRows as $key => $row) {
                $sand = $row['item']['sand'] ?? null;
                if (!$sand) {
                    $sandColorMap[$key] = '#ffffff';
                    continue;
                }
                $dataSignature = $sand->brand . '-' .
                    ($sand->package_unit ?? '-') . '-' .
                    ($sand->package_volume ?? '0') . '-' .
                    ($sand->package_price ?? '0');

                if (isset($sandDataColorMap[$dataSignature])) {
                    $sandColorMap[$key] = $sandDataColorMap[$dataSignature];
                } else {
                    $color = $sandColors[$colorIndex % count($sandColors)];
                    $sandColorMap[$key] = $color;
                    $sandDataColorMap[$dataSignature] = $color;
                    $colorIndex++;
                }
            }

            $ceramicColorMap = [];
            $ceramicDataColorMap = [];
            $colorIndex = 0;
            foreach ($rekapRows as $key => $row) {
                $ceramic = $row['item']['ceramic'] ?? null;
                if (!$ceramic) {
                    $ceramicColorMap[$key] = '#ffffff';
                    continue;
                }
                $dataSignature = $ceramic->brand . '-' .
                    ($ceramic->color ?? '-') . '-' .
                    $ceramic->dimension_length . '-' .
                    $ceramic->dimension_width . '-' .
                    ($ceramic->price_per_package ?? '0');

                if (isset($ceramicDataColorMap[$dataSignature])) {
                    $ceramicColorMap[$key] = $ceramicDataColorMap[$dataSignature];
                } else {
                    $color = $ceramicColors[$colorIndex % count($ceramicColors)];
                    $ceramicColorMap[$key] = $color;
                    $ceramicDataColorMap[$dataSignature] = $color;
                    $colorIndex++;
                }
            }

            $natColorMap = [];
            $natDataColorMap = [];
            $colorIndex = 0;
            foreach ($rekapRows as $key => $row) {
                $nat = $row['item']['nat'] ?? null;
                if (!$nat) {
                    $natColorMap[$key] = '#ffffff';
                    continue;
                }
                $dataSignature = $nat->brand . '-' .
                    ($nat->color ?? 'Nat') . '-' .
                    $nat->package_weight_net . '-' .
                    ($nat->package_price ?? '0');

                if (isset($natDataColorMap[$dataSignature])) {
                    $natColorMap[$key] = $natDataColorMap[$dataSignature];
                } else {
                    $color = $natColors[$colorIndex % count($natColors)];
                    $natColorMap[$key] = $color;
                    $natDataColorMap[$dataSignature] = $color;
                    $colorIndex++;
                }
            }

            $materialsShown = [];
            if ($hasCement) $materialsShown[] = 'cement';
            if ($hasSand) $materialsShown[] = 'sand';
            if ($hasCeramic) $materialsShown[] = 'ceramic';
            if ($hasNat) $materialsShown[] = 'nat';
            $lastMaterial = end($materialsShown);

            $area = (float)($requestData['wall_length'] ?? 0) * (float)($requestData['wall_height'] ?? 0);
        @endphp

        <style>
            .table-rekap-global th {
                padding: 8px 10px !important;
                font-size: 13px !important;
            }
            .table-rekap-global td {
                padding: 8px 10px !important;
                border: 1px solid #f1f5f9;
            }
            /* Global Text Styling */
            .table-preview th,
            .table-preview td,
            .table-preview span,
            .table-preview div,
            .table-preview a,
            .table-preview label,
            .table-preview button {
                font-family: 'Nunito', sans-serif !important;
                color: #000000 !important;
                font-weight: 700 !important;
            }

            /* Table Styling */
            .table-preview {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 13px;
                margin: 0;
            }
            .table-preview th {
                background: #891313;
                color: #ffffff;
                text-align: center;
                font-weight: 900;
                padding: 14px 16px;
                border: 1px solid #d1d5db;
                font-size: 14px;
                letter-spacing: 0.3px;
                white-space: nowrap;
            }
            .table-preview td {
                padding: 14px 16px;
                border: 1px solid #f1f5f9;
                vertical-align: top;
                white-space: nowrap;
            }
            .table-preview td.preview-scroll-td {
                position: relative;
                overflow: hidden;
                white-space: nowrap;
                text-align: left;
            }
            .table-preview td.preview-store-cell {
                width: 150px;
                min-width: 150px;
                max-width: 150px;
            }
            .table-preview td.preview-address-cell {
                width: 200px;
                min-width: 200px;
                max-width: 200px;
            }
            .table-preview th.preview-store-cell {
                width: 150px;
                min-width: 150px;
                max-width: 150px;
            }
            .table-preview th.preview-address-cell {
                width: 200px;
                min-width: 200px;
                max-width: 200px;
            }
            .table-preview td.preview-scroll-td.is-scrollable::after {
                content: '...';
                position: absolute;
                right: 6px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
                font-weight: 600;
                color: rgba(15, 23, 42, 0.85);
                background: linear-gradient(90deg, rgba(248, 250, 252, 0) 0%, rgba(248, 250, 252, 0.95) 40%, rgba(248, 250, 252, 1) 100%);
                padding-left: 8px;
                pointer-events: none;
            }
            .table-preview td.preview-scroll-td.is-scrolled-end::after {
                opacity: 0;
            }
            .table-preview .preview-scroll-cell {
                display: block;
                width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                scrollbar-width: none;
                scrollbar-color: transparent transparent;
                white-space: nowrap;
            }
            .table-preview .preview-scroll-cell::-webkit-scrollbar {
                height: 0;
            }
            .table-preview tbody tr:last-child td {
                border-bottom: none;
            }
            .table-preview tbody tr:hover td {
                background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
            }
            .bg-highlight {
                background: linear-gradient(to right, #f8fafc 0%, #f1f5f9 100%) !important;
            }
            .text-primary-dark {
                color: #891313;
                font-weight: 700;
            }
            .text-success-dark {
                color: #059669;
                font-weight: 700;
            }
            .sticky-col {
                position: sticky;
                left: 0;
                background-color: white;
                z-index: 1;
            }
            .sticky-col-1 {
                position: sticky;
                left: 0;
                background-color: white;
                z-index: 2;
                box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                min-width: 90px;
                max-width: 105px;
                width: 90px;
            }
            .sticky-col-2 {
                position: sticky;
                left: 105px;
                background-color: white;
                z-index: 2;
                box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                min-width: 80px;
            }
            .sticky-col-3 {
                position: sticky;
                left: 202px;
                background-color: white;
                z-index: 2;
                box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                min-width: 100px;
            }
            .table-preview thead th.sticky-col-1,
            .table-preview thead th.sticky-col-2,
            .table-preview thead th.sticky-col-3 {
                background-color: #891313;
                z-index: 3;
            }
            .table-preview tbody tr:hover td.sticky-col-1,
            .table-preview tbody tr:hover td.sticky-col-2,
            .table-preview tbody tr:hover td.sticky-col-3 {
                background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
            }
            .btn-select {
                background: linear-gradient(135deg, #891313 0%, #a61515 100%);
                color: #ffffff;
                border: none;
                padding: 6px 16px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                cursor: pointer;
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 2px 4px rgba(137, 19, 19, 0.2);
            }
            .btn-select:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
            }
            .group-divider {
                border-top: 2px solid #891313 !important;
            }
            .group-end {
                border-bottom: 3px solid #891313 !important;
            }
            .group-end td {
                border-bottom: 3px solid #891313 !important;
            }
            .rowspan-cell {
                border-bottom: 3px solid #891313 !important;
            }
            .sticky-col-label {
                position: sticky;
                left: 0;
                z-index: 2;
                box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                min-width: 320px;
            }
        </style>

        <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
            <div class="table-responsive">
                <table class="table-preview table-rekap-global" style="margin: 0;">
                <thead>
                    <tr>
                        <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 0; z-index: 3; width: 80px; min-width: 80px;">Rekap</th>
                        <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 80px; z-index: 3; width: 140px; min-width: 140px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.3);">Grand Total</th>
                        @if($hasCement)
                        <th colspan="2" style="background: #891313; color: white;">Semen</th>
                        @endif
                        @if($hasSand)
                        <th colspan="2" style="background: #891313; color: white;">Pasir</th>
                        @endif
                        @if($hasCeramic)
                        <th colspan="2" style="background: #891313; color: white;">Keramik</th>
                        @endif
                        @if($hasNat)
                        <th colspan="2" style="background: #891313; color: white;">Nat</th>
                        @endif
                    </tr>
                    <tr>
                        @if($hasCement)
                        <th style="background: #891313; color: white;">Merek</th>
                        <th style="background: #891313; color: white;">Detail</th>
                        @endif
                        @if($hasSand)
                        <th style="background: #891313; color: white;">Merek</th>
                        <th style="background: #891313; color: white;">Detail</th>
                        @endif
                        @if($hasCeramic)
                        <th style="background: #891313; color: white;">Merek</th>
                        <th style="background: #891313; color: white;">Detail</th>
                        @endif
                        @if($hasNat)
                        <th style="background: #891313; color: white;">Merek</th>
                        <th style="background: #891313; color: white;">Detail</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($flatCombinations as $combo)
                        @php
                            $label = $combo['key'];
                            $item = $combo['item'];
                            $hasItem = !empty($item);
                            $res = $hasItem ? ($item['result'] ?? []) : [];
                            $grandTotal = $hasItem ? ($res['grand_total'] ?? ($item['total_cost'] ?? 0)) : null;
                            $primaryLabel = $label;
                            $labelPrefix = preg_replace('/\s+\d+.*$/', '', $primaryLabel);
                            $labelPrefix = trim($labelPrefix);
                            preg_match('/\s+(\d+)/', $primaryLabel, $matches);
                            $number = isset($matches[1]) ? (int)$matches[1] : 1;
                            $number = max(1, min(3, $number));
                            $labelColor = $rekapLabelColors[$labelPrefix][$number] ?? ['bg' => '#ffffff', 'text' => '#000000'];
                            $bgColor = $globalColorMap[$label] ?? '#ffffff';
                            $cementBgColor = $cementColorMap[$label] ?? '#ffffff';
                            $sandBgColor = $sandColorMap[$label] ?? '#ffffff';
                            $ceramicBgColor = $ceramicColorMap[$label] ?? '#ffffff';
                            $natBgColor = $natColorMap[$label] ?? '#ffffff';
                        @endphp
                        <tr>
                            <td style="font-weight: 700; position: sticky; left: 0; z-index: 2; background: {{ $labelColor['bg'] }}; color: {{ $labelColor['text'] }}; padding: 4px 8px; vertical-align: middle; width: 80px; min-width: 80px;">
                                <a href="#detail-{{ strtolower(str_replace(' ', '-', $label)) }}" style="color: inherit; text-decoration: none; display: block; cursor: pointer;">
                                    {{ $primaryLabel }}
                                </a>
                            </td>
                            <td class="text-end fw-bold" style="position: sticky; left: 80px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1); background: {{ $bgColor }}; padding: 4px 8px; vertical-align: middle; width: 140px; min-width: 140px;">
                                <div class="d-flex justify-content-between w-100">
                                    <span>Rp</span>
                                    <span>{{ $grandTotal !== null ? \App\Helpers\NumberHelper::format($grandTotal) : '-' }}</span>
                                </div>
                            </td>
                            @if($hasCement)
                            <td style="background: {{ $cementBgColor }}; vertical-align: middle;">
                                {{ $hasItem && isset($item['cement']) ? $item['cement']->brand : '-' }}
                            </td>
                            <td class="text-muted small" style="background: {{ $cementBgColor }}; vertical-align: middle; {{ $lastMaterial !== 'cement' ? 'border-right: 2px solid #891313;' : '' }}">
                                {{ $hasItem && isset($item['cement']) ? (($item['cement']->color ?? '-') . ' - ' . ($item['cement']->package_weight_net + 0) . ' Kg') : '-' }}
                            </td>
                            @endif
                            @if($hasSand)
                            <td style="background: {{ $sandBgColor }}; vertical-align: middle;">
                                {{ $hasItem && isset($item['sand']) ? $item['sand']->brand : '-' }}
                            </td>
                            <td class="text-muted small" style="background: {{ $sandBgColor }}; vertical-align: middle; {{ $lastMaterial !== 'sand' ? 'border-right: 2px solid #891313;' : '' }}">
                                {{ $hasItem && isset($item['sand']) ? (($item['sand']->package_unit ?? '-') . ' - ' . ($item['sand']->package_volume ? (($item['sand']->package_volume + 0) . ' M3') : '-')) : '-' }}
                            </td>
                            @endif
                            @if($hasCeramic)
                            <td style="background: {{ $ceramicBgColor }}; vertical-align: middle;">
                                {{ $hasItem && isset($item['ceramic']) ? $item['ceramic']->brand : '-' }}
                            </td>
                            <td class="text-muted small" style="background: {{ $ceramicBgColor }}; vertical-align: middle; {{ $lastMaterial !== 'ceramic' ? 'border-right: 2px solid #891313;' : '' }}">
                                {{ $hasItem && isset($item['ceramic']) ? (($item['ceramic']->color ?? '-') . ' (' . ($item['ceramic']->dimension_length + 0) . 'x' . ($item['ceramic']->dimension_width + 0) . ')') : '-' }}
                            </td>
                            @endif
                            @if($hasNat)
                            <td style="background: {{ $natBgColor }}; vertical-align: middle;">
                                {{ $hasItem && isset($item['nat']) ? $item['nat']->brand : '-' }}
                            </td>
                            <td class="text-muted small" style="background: {{ $natBgColor }}; vertical-align: middle;">
                                {{ $hasItem && isset($item['nat']) ? (($item['nat']->color ?? 'Nat') . ' (' . ($item['nat']->package_weight_net + 0) . ' Kg)') : '-' }}
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden; position: relative; z-index: 1;">
            <div class="table-responsive">
                <table class="table-preview">
                <thead class="align-top">
                    <tr>
                        <th class="sticky-col-1">Qty<br>/ Pekerjaan</th>
                        <th class="sticky-col-2">Satuan</th>
                        <th class="sticky-col-3">Material</th>
                        <th colspan="4">Detail</th>
                        <th class="preview-store-cell">Toko</th>
                        <th class="preview-address-cell">Alamat</th>
                        <th colspan="2">Harga Beli</th>
                        <th>Biaya<br>/ Material</th>
                        <th>Total Biaya</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                        <th colspan="2">Biaya Komparasi<br>/ Material</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $comboIndex = 0; @endphp
                    @foreach($detailCombinations as $combo)
                        @php
                            $comboIndex++;
                            $label = $combo['key'];
                            $item = $combo['item'];
                            $res = $item['result'];
                            $areaForCost = $area > 0 ? $area : (float)($requestData['area'] ?? 0);
                            // Normalize areaForCost karena non-rupiah (M2), normalize hasil pembagian
                            $normalizedArea = \App\Helpers\NumberHelper::normalize($areaForCost);
                            $costPerM2 = $normalizedArea > 0
                                ? \App\Helpers\NumberHelper::normalize($res['grand_total'] / $normalizedArea)
                                : 0;
                            $cementWeight = isset($item['cement']) ? ($item['cement']->package_weight_net ?? 0) : 0;
                            if ($cementWeight <= 0) {
                                $cementWeight = 1;
                            }
                            $ceramicArea = 0;
                            if (isset($item['ceramic']) && $item['ceramic']->dimension_length && $item['ceramic']->dimension_width) {
                                $ceramicArea = ($item['ceramic']->dimension_length / 100) * ($item['ceramic']->dimension_width / 100);
                            }
                            if ($ceramicArea <= 0) {
                                $ceramicArea = 1;
                            }
                            $natWeight = isset($item['nat']) ? ($item['nat']->package_weight_net ?? 0) : 0;
                            if ($natWeight <= 0) {
                                $natWeight = 1;
                            }

                            $cementPricePerSak = $res['cement_price_per_sak'] ?? (isset($item['cement']) ? ($item['cement']->package_price ?? 0) : 0);
                            $ceramicPricePerPackage = $res['ceramic_price_per_package'] ?? (isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0);
                            $groutPricePerPackage = $res['grout_price_per_package'] ?? (isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0);
                            $sandPricePerM3 = $res['sand_price_per_m3'] ?? 0;
                            if ($sandPricePerM3 <= 0 && isset($item['sand'])) {
                                $sandPricePerM3 = $item['sand']->comparison_price_per_m3 ?? 0;
                                if ($sandPricePerM3 <= 0 && ($item['sand']->package_price ?? 0) > 0 && ($item['sand']->package_volume ?? 0) > 0) {
                                    $sandPricePerM3 = $item['sand']->package_price / $item['sand']->package_volume;
                                }
                            }

                            $tilesPerPackage = $res['tiles_per_package'] ?? (isset($item['ceramic']) ? ($item['ceramic']->pieces_per_package ?? 0) : 0);
                            $tilesPackages = $res['tiles_packages'] ?? (($tilesPerPackage > 0) ? ceil(($res['total_tiles'] ?? 0) / $tilesPerPackage) : 0);

                            $formatNum = function($num, $decimals = null) {
                                return \App\Helpers\NumberHelper::format($num);
                            };
                            $formatMoney = function($num) {
                                return \App\Helpers\NumberHelper::format($num, 0);
                            };
                            $formatRaw = function($num, $decimals = 6) {
                                return \App\Helpers\NumberHelper::format($num, $decimals);
                            };

                            $materialConfig = [
                                'cement' => [
                                    'name' => 'Semen',
                                    'check_field' => 'cement_sak',
                                    'qty' => $res['cement_sak'] ?? 0,
                                    'qty_debug' => 'Kebutuhan semen untuk area ' . $formatNum($areaForCost) . ' M2',
                                    'unit' => 'Sak',
                                    'comparison_unit' => 'Kg',
                                    'detail_value' => $cementWeight,
                                    'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($cementWeight) . ' Kg',
                                    'object' => $item['cement'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['cement']) ? ($item['cement']->color ?? '-') : '-',
                                    'detail_extra' => isset($item['cement']) ? (($item['cement']->package_weight_net + 0) . ' Kg') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['cement']) ? ($item['cement']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                    'price_per_unit' => $cementPricePerSak,
                                    'price_unit_label' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                    'price_calc_qty' => $res['cement_sak'] ?? 0,
                                    'price_calc_unit' => 'Sak',
                                    'total_price' => $res['total_cement_price'] ?? 0,
                                    'unit_price' => $cementPricePerSak,
                                    'unit_price_label' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                ],
                                'sand' => [
                                    'name' => 'Pasir',
                                    'check_field' => 'sand_m3',
                                    'qty' => $res['sand_m3'] ?? 0,
                                    'qty_debug' => 'Kebutuhan pasir untuk area ' . $formatNum($areaForCost) . ' M2',
                                    'unit' => 'M3',
                                    'comparison_unit' => 'M3',
                                    'detail_value' => isset($item['sand']) && ($item['sand']->package_volume ?? 0) > 0 ? $item['sand']->package_volume : 1,
                                    'detail_value_debug' => isset($item['sand']) ? ('Volume per kemasan: ' . $formatNum($item['sand']->package_volume ?? 0) . ' M3') : '-',
                                    'object' => $item['sand'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['sand']) ? ($item['sand']->package_unit ?? '-') : '-',
                                    'detail_extra' => isset($item['sand']) ? ($item['sand']->package_volume ? (($item['sand']->package_volume + 0) . ' M3') : '-') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['sand']) ? ($item['sand']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                    'price_per_unit' => $sandPricePerM3,
                                    'price_unit_label' => 'M3',
                                    'price_calc_qty' => $res['sand_m3'] ?? 0,
                                    'price_calc_unit' => 'M3',
                                    'total_price' => $res['total_sand_price'] ?? 0,
                                    'unit_price' => $sandPricePerM3,
                                    'unit_price_label' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                ],
                                'ceramic' => [
                                    'name' => 'Keramik',
                                    'check_field' => 'total_tiles',
                                    'qty' => $res['total_tiles'] ?? 0,
                                    'qty_debug' => 'Kebutuhan keramik untuk area ' . $formatNum($areaForCost) . ' M2',
                                    'unit' => 'Bh',
                                    'comparison_unit' => 'M2',
                                    'detail_value' => $ceramicArea,
                                    'detail_value_debug' => isset($item['ceramic']) ? ('Rumus: (' . $formatNum($item['ceramic']->dimension_length) . '/100) x (' . $formatNum($item['ceramic']->dimension_width) . '/100) = ' . $formatNum($ceramicArea) . ' M2') : '-',
                                    'object' => $item['ceramic'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['ceramic']) ? ($item['ceramic']->color ?? '-') : '-',
                                    'detail_extra' => isset($item['ceramic']) ? (($item['ceramic']->dimension_length + 0) . 'x' . ($item['ceramic']->dimension_width + 0) . ' cm') : '-',
                                    'detail_extra_debug' => isset($item['ceramic']) ? ('Luas: ' . $formatNum($ceramicArea) . ' M2 per keping') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0,
                                    'package_unit' => 'Dus',
                                    'price_per_unit' => $ceramicPricePerPackage,
                                    'price_unit_label' => 'Dus',
                                    'price_calc_qty' => $tilesPackages,
                                    'price_calc_unit' => 'Dus',
                                    'total_price' => $res['total_ceramic_price'] ?? 0,
                                    'unit_price' => $ceramicPricePerPackage,
                                    'unit_price_label' => 'Dus',
                                ],
                                'nat' => [
                                    'name' => 'Nat',
                                    'check_field' => 'grout_packages',
                                    'qty' => $res['grout_packages'] ?? 0,
                                    'qty_debug' => 'Kebutuhan nat untuk area ' . $formatNum($areaForCost) . ' M2',
                                    'unit' => 'Bks',
                                    'comparison_unit' => 'Kg',
                                    'detail_value' => $natWeight,
                                    'detail_value_debug' => 'Berat per kemasan: ' . $formatNum($natWeight) . ' Kg',
                                    'object' => $item['nat'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['nat']) ? ($item['nat']->color ?? 'Nat') : 'Nat',
                                    'detail_extra' => isset($item['nat']) ? (($item['nat']->package_weight_net + 0) . ' Kg') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                    'price_per_unit' => $groutPricePerPackage,
                                    'price_unit_label' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                    'price_calc_qty' => $res['grout_packages'] ?? 0,
                                    'price_calc_unit' => 'Bks',
                                    'total_price' => $res['total_grout_price'] ?? 0,
                                    'unit_price' => $groutPricePerPackage,
                                    'unit_price_label' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                ],
                                'water' => [
                                    'name' => 'Air',
                                    'check_field' => 'total_water_liters',
                                    'qty' => $res['total_water_liters'] ?? ($res['water_liters'] ?? 0),
                                    'qty_debug' => 'Kebutuhan air untuk area ' . $formatNum($areaForCost) . ' M2',
                                    'unit' => 'L',
                                    'comparison_unit' => 'L',
                                    'detail_value' => 1,
                                    'object' => null,
                                    'type_field' => null,
                                    'type_display' => 'Bersih',
                                    'brand_field' => null,
                                    'brand_display' => 'PDAM',
                                    'detail_display' => '',
                                    'detail_extra' => '',
                                    'store_field' => null,
                                    'store_display' => 'Customer',
                                    'address_field' => null,
                                    'address_display' => '-',
                                    'package_price' => 0,
                                    'package_unit' => '',
                                    'total_price' => 0,
                                    'unit_price' => 0,
                                    'unit_price_label' => '',
                                    'is_special' => true,
                                ],
                            ];

                            $visibleMaterials = array_filter($materialConfig, function($mat) {
                                return isset($mat['qty']) && $mat['qty'] > 0;
                            });

                            $rowCount = count($visibleMaterials);
                        @endphp

                        <tr class="{{ $comboIndex === 1 ? '' : 'group-divider' }}" id="detail-{{ strtolower(str_replace(' ', '-', $label)) }}">
                            <td colspan="3" class="text-start align-middle sticky-label-row sticky-col-label" style="background: #f8fafc; padding: 10px 16px; font-weight: 600;">
                                @php
                                    $labelParts = array_map('trim', explode('=', $label));
                                @endphp
                                <div style="display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                    <span style="color: #891313; font-weight: 700; font-size: 11px;">#{{ $comboIndex }}</span>
                                    @foreach($labelParts as $index => $singleLabel)
                                        @php
                                            $labelPrefix = preg_replace('/\s+\d+.*$/', '', $singleLabel);
                                            $labelPrefix = trim($labelPrefix);
                                            preg_match('/\s+(\d+)/', $singleLabel, $matches);
                                            $number = isset($matches[1]) ? (int)$matches[1] : 1;
                                            $number = max(1, min(3, $number));
                                            $colorSet = $labelColors[$labelPrefix] ?? [
                                                1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                3 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                            ];
                                            $color = $colorSet[$number];
                                        @endphp
                                        <a href="#preview-top" class="filter-back-top" style="text-decoration: none; color: inherit; display: inline-block;">
                                            <span class="badge" style="background: {{ $color['bg'] }}; border: 1.5px solid {{ $color['border'] }}; color: {{ $color['text'] }}; padding: 3px 8px; border-radius: 5px; font-weight: 600; font-size: 10px; white-space: nowrap;">
                                                {{ $singleLabel }}
                                            </span>
                                        </a>
                                        @if($index < count($labelParts) - 1)
                                            <span style="color: #94a3b8; font-size: 10px; font-weight: 600;">=</span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td colspan="18" style="background: #f8fafc;"></td>
                        </tr>

                        @php $matIndex = 0; @endphp
                        @foreach($visibleMaterials as $matKey => $mat)
                            @php
                                $matIndex++;
                                $isFirstMaterial = $matIndex === 1;
                                $isLastMaterial = $matIndex === count($visibleMaterials);

                                // Calculate values early for use in columns
                                $comparisonUnit = $mat['comparison_unit'] ?? ($mat['unit'] ?? '');
                                $detailValue = $mat['detail_value'] ?? 1;
                                $pricePerUnit = $mat['price_per_unit'] ?? ($mat['package_price'] ?? 0);
                                $priceUnitLabel = $mat['price_unit_label'] ?? ($mat['package_unit'] ?? '');
                                $priceCalcQty = $mat['price_calc_qty'] ?? ($mat['qty'] ?? 0);
                                $priceCalcUnit = $mat['price_calc_unit'] ?? ($mat['unit'] ?? '');
                                // Rumus baru: (Harga beli / ukuran per kemasan) * Qty per pekerjaan
                                $conversionFactor = 1;
                                if ($matKey === 'sand') {
                                     $conversionFactor = $mat['detail_value'] ?? 1;
                                } elseif ($matKey === 'ceramic') {
                                     $conversionFactor = $mat['object']->pieces_per_package ?? 1;
                                }
                                
                                $normalizedPrice = \App\Helpers\NumberHelper::normalize($mat['package_price'] ?? 0);
                                $normalizedSize = \App\Helpers\NumberHelper::normalize($conversionFactor);
                                $normalizedQty = \App\Helpers\NumberHelper::normalize($mat['qty'] ?? 0);
                                
                                $unitPrice = ($normalizedSize > 0) ? ($normalizedPrice / $normalizedSize) : 0;
                                $unitPrice = \App\Helpers\NumberHelper::normalize($unitPrice);
                                
                                $hargaKomparasi = \App\Helpers\NumberHelper::normalize($unitPrice * $normalizedQty);

                                $qtyTitleParts = [];
                                if (!empty($mat['qty_debug'])) {
                                    $qtyTitleParts[] = $mat['qty_debug'];
                                }
                                $qtyTitleParts[] = 'Nilai tampil: ' . $formatNum($mat['qty']) . ' ' . ($mat['unit'] ?? '');
                                $qtyTitle = implode(' | ', $qtyTitleParts);

                                $detailTitleParts = [];
                                if (!empty($mat['detail_value_debug'])) {
                                    $detailTitleParts[] = $mat['detail_value_debug'];
                                }
                                if (!empty($mat['detail_extra_debug'])) {
                                    $detailTitleParts[] = $mat['detail_extra_debug'];
                                }
                                if (!empty($mat['detail_extra'])) {
                                    $detailTitleParts[] = 'Nilai tampil: ' . $mat['detail_extra'];
                                }
                                $detailTitle = implode(' | ', $detailTitleParts);

                                $packagePriceTitleParts = [];
                                $packagePriceTitleParts[] = 'Nilai tampil: Rp ' . $formatMoney($mat['package_price']) . ' / ' . $mat['package_unit'];
                                if ($priceUnitLabel !== $mat['package_unit'] || abs($pricePerUnit - $mat['package_price']) > 0.00001) {
                                    $packagePriceTitleParts[] = 'Harga unit formula: Rp ' . $formatMoney($pricePerUnit) . ' / ' . $priceUnitLabel;
                                }
                                if ($matKey === 'sand' && $detailValue > 0) {
                                    $convertedSand = $mat['package_price'] / $detailValue;
                                    $packagePriceTitleParts[] = 'Konversi: Rp ' . $formatMoney($mat['package_price']) . ' / ' . $formatNum($detailValue) . ' ' . $comparisonUnit . ' = Rp ' . $formatMoney($convertedSand) . ' / ' . $comparisonUnit;
                                }
                                $packagePriceTitle = implode(' | ', $packagePriceTitleParts);
                            @endphp
                            <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                                <td class="text-end fw-bold sticky-col-1" title="{{ $qtyTitle }}">@format($mat['qty'])</td>
                                <td class="text-center sticky-col-2">{{ $mat['unit'] }}</td>
                                <td class="fw-bold sticky-col-3">{{ $mat['name'] }}</td>

                                <td class="text-muted">{{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}</td>
                                <td class="fw-bold">{{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}</td>
                                <td class="{{ $matKey === 'brick' ? 'text-center text-nowrap' : '' }}">{{ $mat['detail_display'] }}</td>
                                <td class="{{ $matKey === 'cement' || $matKey === 'sand' ? 'text-start text-nowrap fw-bold' : '' }}" title="{{ $detailTitle }}">{{ $mat['detail_extra'] ?? '' }}</td>
                                <td class="preview-scroll-td preview-store-cell">
                                    <div class="preview-scroll-cell">{{ $mat['store_display'] ?? ($mat['object']->{$mat['store_field']} ?? '-') }}</div>
                                </td>
                                <td class="preview-scroll-td preview-address-cell small text-muted">
                                    <div class="preview-scroll-cell">{{ $mat['address_display'] ?? ($mat['object']->{$mat['address_field']} ?? '-') }}</div>
                                </td>

                                @if(isset($mat['is_special']) && $mat['is_special'])
                                    <td class="text-center text-muted">-</td>
                                    <td></td>
                                @else
                                    <td class="text-nowrap fw-bold" title="{{ $packagePriceTitle }}">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>@price($mat['package_price'])</span>
                                        </div>
                                    </td>
                                    <td class="text-muted text-nowrap ps-1">/ {{ $mat['package_unit'] }}</td>
                                @endif

                                @if(isset($mat['is_special']) && $mat['is_special'])
                                    <td class="text-center text-muted">-</td>
                                @else
                                    @php
                                        // Hitung harga komparasi: (harga / ukuran) * qty
                                        $hargaKomparasiDebugParts = [];
                                        $hargaKomparasiDebugParts[] = "Rumus: (Rp " . $formatMoney($normalizedPrice) . " / " . $formatNum($normalizedSize) . ") x " . $formatNum($normalizedQty) . " = Rp " . $formatMoney($hargaKomparasi);
                                        $hargaKomparasiDebug = implode(' | ', $hargaKomparasiDebugParts);
                                    @endphp
                                    <td class="text-nowrap" title="{{ $hargaKomparasiDebug }}">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>{{ $formatMoney($hargaKomparasi) }}</span>
                                        </div>
                                    </td>
                                @endif

                                @if($isFirstMaterial)
                                    @php
                                        // Build debug breakdown for grand_total (harga per kemasan × qty)
                                        $grandTotalParts = [];
                                        $calculatedGrandTotal = 0;
                                        foreach($visibleMaterials as $debugMatKey => $debugMat) {
                                            if (!isset($debugMat['is_special']) || !$debugMat['is_special']) {
                                                // Rumus baru: (Harga beli / ukuran per kemasan) * Qty per pekerjaan
                                                $debugConversionFactor = 1;
                                                if ($debugMatKey === 'sand') {
                                                     $debugConversionFactor = $debugMat['detail_value'] ?? 1;
                                                } elseif ($debugMatKey === 'ceramic') {
                                                     $debugConversionFactor = $debugMat['object']->pieces_per_package ?? 1;
                                                }
                                                
                                                $debugNormalizedPrice = \App\Helpers\NumberHelper::normalize($debugMat['package_price'] ?? 0);
                                                $debugNormalizedSize = \App\Helpers\NumberHelper::normalize($debugConversionFactor);
                                                $debugNormalizedQty = \App\Helpers\NumberHelper::normalize($debugMat['qty'] ?? 0);
                                                
                                                $debugUnitPrice = ($debugNormalizedSize > 0) ? ($debugNormalizedPrice / $debugNormalizedSize) : 0;
                                                $debugUnitPrice = \App\Helpers\NumberHelper::normalize($debugUnitPrice);
                                                
                                                $calcPrice = \App\Helpers\NumberHelper::normalize($debugUnitPrice * $debugNormalizedQty);
                                                $calculatedGrandTotal += $calcPrice;
                                                
                                                $grandTotalParts[] = $debugMat['name'] . " ((Rp " . $formatMoney($debugNormalizedPrice) . " / " . $formatNum($debugNormalizedSize) . ") x " . $formatNum($debugNormalizedQty) . "): Rp " . $formatMoney($calcPrice);
                                            }
                                        }
                                        $grandTotalValue = \App\Helpers\NumberHelper::normalize($calculatedGrandTotal);
                                        $grandTotalDebug = "Rumus: " . implode(' + ', $grandTotalParts);
                                        $grandTotalDebug .= " | Total: Rp " . $formatMoney($grandTotalValue);

                                        // Build debug for costPerM2 (normalize areaForCost karena non-rupiah, normalize hasil pembagian)
                                        $normalizedAreaForCost = \App\Helpers\NumberHelper::normalize($areaForCost);
                                        $calculatedCostPerM2 = $normalizedAreaForCost > 0
                                            ? \App\Helpers\NumberHelper::normalize($grandTotalValue / $normalizedAreaForCost)
                                            : 0;
                                        $costPerM2Debug = "Rumus: Rp " . $formatMoney($grandTotalValue) . " / " . $formatNum($normalizedAreaForCost) . " M2";
                                        $costPerM2Debug .= " | Nilai tampil: Rp " . $formatMoney($calculatedCostPerM2) . " / M2";
                                    @endphp
                                    <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell" title="{{ $grandTotalDebug }}">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                            <span class="text-success-dark" style="font-size: 15px;">@price($grandTotalValue)</span>
                                        </div>
                                    </td>
                                    <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell" title="{{ $costPerM2Debug }}">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                            <span class="text-primary-dark" style="font-size: 14px;">@price($calculatedCostPerM2)</span>
                                        </div>
                                    </td>
                                    <td rowspan="{{ $rowCount }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                                @endif

                                @if(isset($mat['is_special']) && $mat['is_special'])
                                    <td class="text-center text-muted">-</td>
                                    <td></td>
                                @else
                                    @php
                                        // Normalize qty untuk konsistensi
                                        $normalizedQtyValue = \App\Helpers\NumberHelper::normalize($mat['qty'] ?? 0);
                                        // Gunakan harga komparasi yang sudah dihitung (sesuai formula)
                                        // Normalize ke 0 decimal agar perhitungan backward (total / qty) sesuai dengan angka yang ditampilkan
                                        $totalPriceValue = \App\Helpers\NumberHelper::normalize($hargaKomparasi, 0);

                                        // Normalisasi nilai agar sesuai dengan yang ditampilkan (mengikuti aturan NumberHelper)
                                        // Ini memastikan perhitungan menggunakan nilai yang sama dengan yang user lihat
                                        $normalizedDetailValue = \App\Helpers\NumberHelper::normalize($detailValue);

                                        // Untuk sand, hanya hitung total_price / qty (tanpa pembagian detail_value)
                                        if ($matKey === 'sand') {
                                            $actualBuyPrice = ($normalizedQtyValue > 0)
                                                ? \App\Helpers\NumberHelper::normalize($totalPriceValue / $normalizedQtyValue)
                                                : 0;
                                            $hargaBeliAktualDebug = "Rumus: Rp " . $formatMoney($totalPriceValue) . " / " . $formatNum($normalizedQtyValue) . " " . $mat['unit'] . " = Rp " . $formatMoney($actualBuyPrice);
                                        } else {
                                            $actualBuyPrice = ($normalizedQtyValue > 0 && $normalizedDetailValue > 0)
                                                ? \App\Helpers\NumberHelper::normalize($totalPriceValue / $normalizedQtyValue / $normalizedDetailValue)
                                                : 0;
                                            $hargaBeliAktualDebug = "Rumus: Rp " . $formatMoney($totalPriceValue) . " / " . $formatNum($normalizedQtyValue) . " " . $mat['unit'] . " / " . $formatNum($normalizedDetailValue) . " " . $comparisonUnit . " = Rp " . $formatMoney($actualBuyPrice);
                                        }
                                    @endphp
                                    <td class="text-nowrap" title="{{ $hargaBeliAktualDebug }}">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>@price($actualBuyPrice)</span>
                                        </div>
                                    </td>
                                    <td class="text-muted text-nowrap ps-1">/ {{ $comparisonUnit }}</td>
                                @endif

                                @if($isFirstMaterial)
                                    <td rowspan="{{ $rowCount }}" class="text-center align-top rowspan-cell">
                                        @php
                                            $traceFormulaCode = $requestData['formula_code']
                                                ?? $requestData['work_type']
                                                ?? null;
                                            $traceParams = [
                                                'formula_code' => $traceFormulaCode,
                                                'work_type' => $requestData['work_type'] ?? null,
                                                'wall_length' => $requestData['wall_length'] ?? null,
                                                'wall_height' => $requestData['wall_height'] ?? null,
                                                'area' => $requestData['area'] ?? null,
                                                'mortar_thickness' => $requestData['mortar_thickness'] ?? null,
                                                'grout_thickness' => $requestData['grout_thickness'] ?? null,
                                                'painting_layers' => $requestData['painting_layers'] ?? null,
                                                'layer_count' => $requestData['layer_count'] ?? null,
                                                'auto_trace' => 1,
                                            ];
                                            
                                            if (isset($item['cement'])) {
                                                $traceParams['cement_id'] = $item['cement']->id;
                                            }
                                            if (isset($item['sand'])) {
                                                $traceParams['sand_id'] = $item['sand']->id;
                                            }
                                            if (isset($item['ceramic'])) {
                                                $traceParams['ceramic_id'] = $item['ceramic']->id;
                                            }
                                            if (isset($item['nat'])) {
                                                $traceParams['nat_id'] = $item['nat']->id;
                                            }
                                            $traceUrl = route('material-calculator.trace') . '?' . http_build_query(array_filter($traceParams, function ($value) {
                                                return $value !== null && $value !== '';
                                            }));
                                        @endphp
                                        <div class="d-flex flex-column gap-2 align-items-center">
                                            <a href="{{ $traceUrl }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
                                                <i class="bi bi-diagram-3 me-1"></i> Trace
                                            </a>
                                            <form action="{{ route('material-calculations.store') }}" method="POST" style="margin: 0;">
                                                @csrf
                                                @foreach($requestData as $key => $value)
                                                    @if($key != '_token' && $key != 'cement_id' && $key != 'sand_id' && $key != 'brick_ids' && $key != 'brick_id' && $key != 'price_filters')
                                                        @if(is_array($value))
                                                            @foreach($value as $v)
                                                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                                            @endforeach
                                                        @else
                                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                        @endif
                                                    @endif
                                                @endforeach
                                                @if(isset($item['cement']))
                                                    <input type="hidden" name="cement_id" value="{{ $item['cement']->id }}">
                                                @endif
                                                @if(isset($item['sand']))
                                                    <input type="hidden" name="sand_id" value="{{ $item['sand']->id }}">
                                                @endif
                                                @if(isset($item['ceramic']))
                                                    <input type="hidden" name="ceramic_id" value="{{ $item['ceramic']->id }}">
                                                @endif
                                                @if(isset($item['nat']))
                                                    <input type="hidden" name="nat_id" value="{{ $item['nat']->id }}">
                                                @endif
                                                <input type="hidden" name="confirm_save" value="1">
                                                <button type="submit" class="btn-select">
                                                    <i class="bi bi-check-circle me-1"></i> Pilih
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4 text-center container">
            <p style="font-size: 13px;">
                <i class="bi bi-info-circle me-1"></i> Gunakan tombol <span class="text-muted">Pilih</span> pada kolom Aksi untuk menyimpan perhitungan ini ke proyek Anda.
            </p>
        </div>
    @else
        <div class="alert alert-warning border-warning">
            <div class="d-flex gap-3">
                <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-1">Tidak ada kombinasi tersedia</h6>
                    <p class="mb-0 small">Meskipun data Keramik tersedia, perhitungan memerlukan data material pendukung lengkap. Pastikan Anda telah menginput data berikut di database:</p>
                    <ul class="mb-0 small mt-1">
                        <li><strong>Semen (Tipe Nat):</strong> Di menu Semen, pastikan ada minimal satu data dengan Tipe = "Nat".</li>
                        <li><strong>Semen (Biasa):</strong> Semen untuk adukan perekat.</li>
                        <li><strong>Pasir:</strong> Pasir dengan data harga dan volume/kemasan yang lengkap.</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- MODAL ALL PRICES --}}
@php
    $allPriceCandidates = [];
    if (!empty($combinations)) {
        foreach ($combinations as $label => $items) {
            foreach ($items as $item) {
                if (!$matchesCeramicContext($item)) {
                    continue;
                }
                $allPriceCandidates[] = [
                    'label' => $label,
                    'item' => $item,
                    'grand_total' => (float)($item['result']['grand_total'] ?? 0),
                ];
            }
        }
    }

    // Sort by price
    usort($allPriceCandidates, function ($a, $b) {
        if ($a['grand_total'] === $b['grand_total']) {
            return strcmp($a['label'], $b['label']);
        }
        return $a['grand_total'] <=> $b['grand_total'];
    });

    $allPriceRows = [];
    $sortedCount = count($allPriceCandidates);
    $EkonomisLimit = min(3, $sortedCount);
    $PremiumStart = $sortedCount > 0 ? max(1, $sortedCount - 2) : 1;
    $middleStart = 0;
    $middleEnd = -1;
    if ($sortedCount > 0) {
        $middleIndex = (int) floor(($sortedCount - 1) / 2);
        $middleStart = max(0, $middleIndex - 1);
        $middleEnd = min($sortedCount - 1, $middleStart + 2);
    }

    foreach ($allPriceCandidates as $index => $candidate) {
        $sortedIndex = $index + 1;
        $displayLabel = 'Harga ' . $sortedIndex;
        if ($sortedIndex <= $EkonomisLimit) {
            $displayLabel = 'Ekonomis ' . $sortedIndex;
        } elseif ($sortedIndex >= $PremiumStart) {
            $displayLabel = 'Premium ' . ($sortedIndex - $PremiumStart + 1);
        } elseif ($index >= $middleStart && $index <= $middleEnd) {
            $displayLabel = 'Moderat ' . ($sortedIndex - $middleStart);
        }

        $allPriceRows[] = [
            'index' => $sortedIndex,
            'display_label' => $displayLabel,
            'grand_total' => $candidate['grand_total'],
            'brand' => isset($candidate['item']['ceramic']) ? $candidate['item']['ceramic']->brand : '-'
        ];
    }
    
    // Separate Best and Common for quick view
    $bestRows = array_filter($allPriceRows, function ($row) {
        return isset($row['display_label']) && strpos($row['display_label'], 'Rekomendasi') !== false;
    });
    $commonRows = array_filter($allPriceRows, function ($row) {
        return isset($row['display_label']) && strpos($row['display_label'], 'Populer') !== false;
    });
@endphp

<style>
    /* Ensure ceramic all price modal appears on top */
    .modal.modal-high[id^="ceramicAllPriceModal"] {
        z-index: 20050 !important;
    }
    .modal.modal-high[id^="ceramicAllPriceModal"] .modal-dialog {
        max-width: 520px !important;
        width: 92vw;
    }
    .modal.modal-high[id^="ceramicAllPriceModal"] .modal-body {
        padding: 12px 16px;
    }
    .modal.modal-high[id^="ceramicAllPriceModal"] .all-price-table th,
    .modal.modal-high[id^="ceramicAllPriceModal"] .all-price-table td {
        padding: 4px 6px;
        font-size: 12px;
        line-height: 1.2;
    }
    .modal.modal-high[id^="ceramicAllPriceModal"] .all-price-table th {
        font-weight: 700;
    }
</style>

@if(count($allPriceRows) > 0)
    <div class="modal fade modal-high" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Daftar Semua Grand Total</h5>
                        <div class="small text-muted">Ringkas: hanya label dan grand total.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                        @if(count($bestRows) > 0)
                            <div class="fw-bold mb-1">Rekomendasi</div>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Label</th>
                                            <th>Merek</th>
                                            <th class="text-end" style="width: 160px;">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bestRows as $index => $row)
                                            <tr>
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td>{{ $row['display_label'] }}</td>
                                                <td>{{ $row['brand'] }}</td>
                                                <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if(count($commonRows) > 0)
                            <div class="fw-bold mb-1">Populer</div>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Label</th>
                                            <th>Merek</th>
                                            <th class="text-end" style="width: 160px;">Grand Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($commonRows as $index => $row)
                                            <tr>
                                                <td class="text-muted">{{ $index + 1 }}</td>
                                                <td>{{ $row['display_label'] }}</td>
                                                <td>{{ $row['brand'] }}</td>
                                                <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="fw-bold mb-1">Semua Harga (Ekonomis &rarr; Premium)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0 all-price-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>Label</th>
                                        <th>Merek</th>
                                        <th class="text-end" style="width: 160px;">Grand Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allPriceRows as $row)
                                        <tr>
                                            <td class="text-muted">{{ $row['index'] }}</td>
                                            <td>{{ $row['display_label'] }}</td>
                                            <td>{{ $row['brand'] }}</td>
                                            <td class="text-end">Rp {{ \App\Helpers\NumberHelper::format($row['grand_total'], 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endif

