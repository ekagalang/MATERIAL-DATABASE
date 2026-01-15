{{-- Partial View: Ceramic Combinations Table --}}
{{-- Loaded via AJAX when user clicks ceramic tab --}}

@php
    $isGroupMode = $isGroupMode ?? false;
@endphp

<div id="preview-top"></div>
<div class="ceramic-combinations-content">
    @if($isGroupMode)
        <h5 class="fw-bold mb-1" style="color: #f59e0b;">
            Komparasi Merek
            <small class="text-muted">
                ({{ $ceramic->type ?? 'Keramik' }} -
                {{ (int)$ceramic->dimension_length }}x{{ (int)$ceramic->dimension_width }} cm)
            </small>
        </h5>
        <p class="text-muted small mb-3">Menampilkan kombinasi material terbaik dari berbagai merek untuk ukuran ini.</p>
    @else
        <h5 class="fw-bold mb-3" style="color: #f59e0b;">
            {{ $ceramic->brand ?? 'Keramik' }}
            <small class="text-muted">
                ({{ $ceramic->type ?? 'Lainnya' }} -
                {{ (int)$ceramic->dimension_length }}x{{ (int)$ceramic->dimension_width }} cm)
            </small>
        </h5>
    @endif

    @if(!empty($combinations))
        @php
            $requestedFilters = $requestData['price_filters'] ?? [];
            $filterCategories = ['TerBAIK', 'TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'];
            if (in_array('custom', $requestedFilters, true)) {
                $filterCategories[] = 'Custom';
            }
            $rekapRows = [];

            foreach ($filterCategories as $filterType) {
                $maxCount = $filterType === 'Custom' ? 1 : 3;
                for ($i = 1; $i <= $maxCount; $i++) {
                    $key = $filterType . ' ' . $i;
                    $matchItem = null;

                    foreach ($combinations as $label => $items) {
                        $labelParts = array_map('trim', explode('=', $label));
                        // Check if this group of items corresponds to the current key (e.g. "TerBAIK 1")
                        if (in_array($key, $labelParts)) {
                            // Found the group! Now we need the specific item for this key if possible.
                            // In most cases, $items has 1 element or they are identical for this label.
                            // Just take the first one.
                            $matchItem = $items[0] ?? null;
                            break; 
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
                'TerBAIK' => [
                    1 => ['bg' => '#fca5a5', 'border' => '#f87171', 'text' => '#991b1b'],
                    2 => ['bg' => '#fecaca', 'border' => '#fca5a5', 'text' => '#dc2626'],
                    3 => ['bg' => '#fee2e2', 'border' => '#fecaca', 'text' => '#ef4444'],
                ],
                'TerUMUM' => [
                    1 => ['bg' => '#93c5fd', 'border' => '#60a5fa', 'text' => '#1e40af'],
                    2 => ['bg' => '#bfdbfe', 'border' => '#93c5fd', 'text' => '#2563eb'],
                    3 => ['bg' => '#dbeafe', 'border' => '#bfdbfe', 'text' => '#3b82f6'],
                ],
                'TerMURAH' => [
                    1 => ['bg' => '#6ee7b7', 'border' => '#34d399', 'text' => '#065f46'],
                    2 => ['bg' => '#a7f3d0', 'border' => '#6ee7b7', 'text' => '#16a34a'],
                    3 => ['bg' => '#d1fae5', 'border' => '#a7f3d0', 'text' => '#22c55e'],
                ],
                'TerSEDANG' => [
                    1 => ['bg' => '#fcd34d', 'border' => '#fbbf24', 'text' => '#92400e'],
                    2 => ['bg' => '#fde68a', 'border' => '#fcd34d', 'text' => '#b45309'],
                    3 => ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#d97706'],
                ],
                'TerMAHAL' => [
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
                'TerUMUM' => [
                    1 => ['bg' => '#93c5fd', 'text' => '#1e40af'],
                    2 => ['bg' => '#bfdbfe', 'text' => '#2563eb'],
                    3 => ['bg' => '#dbeafe', 'text' => '#3b82f6'],
                ],
                'TerMURAH' => [
                    1 => ['bg' => '#6ee7b7', 'text' => '#065f46'],
                    2 => ['bg' => '#a7f3d0', 'text' => '#16a34a'],
                    3 => ['bg' => '#d1fae5', 'text' => '#22c55e'],
                ],
                'TerSEDANG' => [
                    1 => ['bg' => '#fcd34d', 'text' => '#92400e'],
                    2 => ['bg' => '#fde68a', 'text' => '#b45309'],
                    3 => ['bg' => '#fef3c7', 'text' => '#d97706'],
                ],
                'TerMAHAL' => [
                    1 => ['bg' => '#d8b4fe', 'text' => '#6b21a8'],
                    2 => ['bg' => '#e9d5ff', 'text' => '#7c3aed'],
                    3 => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
                ],
                'TerBAIK' => [
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
                                    <span>{{ $grandTotal !== null ? number_format($grandTotal, 0, ',', '.') : '-' }}</span>
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
                        <th colspan="2">Harga / Kemasan</th>
                        <th>Harga Komparasi</br> / Pekerjaan</th>
                        <th>Total Biaya</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Beli Aktual<br>/ Satuan Komparasi</th>
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
                            $costPerM2 = $area > 0 ? $res['grand_total'] / $area : 0;
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

                            $materialConfig = [
                                'cement' => [
                                    'name' => 'Semen',
                                    'check_field' => 'cement_sak',
                                    'qty' => $res['cement_sak'] ?? 0,
                                    'unit' => 'Sak',
                                    'comparison_unit' => 'Kg',
                                    'detail_value' => $cementWeight,
                                    'object' => $item['cement'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['cement']) ? ($item['cement']->color ?? '-') : '-',
                                    'detail_extra' => isset($item['cement']) ? (($item['cement']->package_weight_net + 0) . ' Kg') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['cement']) ? ($item['cement']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                    'total_price' => $res['total_cement_price'] ?? 0,
                                    'unit_price' => $res['total_cement_price'] ?? 0,
                                    'unit_price_label' => isset($item['cement']) ? ($item['cement']->package_unit ?? 'Sak') : 'Sak',
                                ],
                                'sand' => [
                                    'name' => 'Pasir',
                                    'check_field' => 'sand_m3',
                                    'qty' => $res['sand_m3'] ?? 0,
                                    'unit' => 'M3',
                                    'comparison_unit' => 'M3',
                                    'detail_value' => 1,
                                    'object' => $item['sand'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['sand']) ? ($item['sand']->package_unit ?? '-') : '-',
                                    'detail_extra' => isset($item['sand']) ? ($item['sand']->package_volume ? (($item['sand']->package_volume + 0) . ' M3') : '-') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['sand']) ? ($item['sand']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                    'total_price' => $res['total_sand_price'] ?? 0,
                                    'unit_price' => $res['total_sand_price'] ?? 0,
                                    'unit_price_label' => isset($item['sand']) ? ($item['sand']->package_unit ?? 'Karung') : 'Karung',
                                ],
                                'ceramic' => [
                                    'name' => 'Keramik',
                                    'check_field' => 'total_tiles',
                                    'qty' => $res['total_tiles'] ?? 0,
                                    'unit' => 'Bh',
                                    'comparison_unit' => 'M2',
                                    'detail_value' => $ceramicArea,
                                    'object' => $item['ceramic'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['ceramic']) ? ($item['ceramic']->color ?? '-') : '-',
                                    'detail_extra' => isset($item['ceramic']) ? (($item['ceramic']->dimension_length + 0) . 'x' . ($item['ceramic']->dimension_width + 0) . ' cm') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0,
                                    'package_unit' => 'Dus',
                                    'total_price' => $res['total_ceramic_price'] ?? 0,
                                    'unit_price' => isset($item['ceramic']) ? ($item['ceramic']->price_per_package ?? 0) : 0,
                                    'unit_price_label' => 'Dus',
                                ],
                                'nat' => [
                                    'name' => 'Nat',
                                    'check_field' => 'grout_packages',
                                    'qty' => $res['grout_packages'] ?? 0,
                                    'unit' => 'Bks',
                                    'comparison_unit' => 'Kg',
                                    'detail_value' => $natWeight,
                                    'object' => $item['nat'] ?? null,
                                    'type_field' => 'type',
                                    'brand_field' => 'brand',
                                    'detail_display' => isset($item['nat']) ? ($item['nat']->color ?? 'Nat') : 'Nat',
                                    'detail_extra' => isset($item['nat']) ? (($item['nat']->package_weight_net + 0) . ' Kg') : '-',
                                    'store_field' => 'store',
                                    'address_field' => 'address',
                                    'package_price' => isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0,
                                    'package_unit' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                    'total_price' => $res['total_grout_price'] ?? 0,
                                    'unit_price' => isset($item['nat']) ? ($item['nat']->package_price ?? 0) : 0,
                                    'unit_price_label' => isset($item['nat']) ? ($item['nat']->package_unit ?? 'Bks') : 'Bks',
                                ],
                                'water' => [
                                    'name' => 'Air',
                                    'check_field' => 'total_water_liters',
                                    'qty' => $res['total_water_liters'] ?? ($res['water_liters'] ?? 0),
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
                                        <a href="#preview-top" style="text-decoration: none; color: inherit; display: inline-block;">
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
                            @endphp
                            <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                                <td class="text-end fw-bold sticky-col-1">@format($mat['qty'])</td>
                                <td class="text-center sticky-col-2">{{ $mat['unit'] }}</td>
                                <td class="fw-bold sticky-col-3">{{ $mat['name'] }}</td>

                                <td class="text-muted">{{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}</td>
                                <td class="fw-bold">{{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}</td>
                                <td class="{{ $matKey === 'brick' ? 'text-center text-nowrap' : '' }}">{{ $mat['detail_display'] }}</td>
                                <td class="{{ $matKey === 'cement' || $matKey === 'sand' ? 'text-start text-nowrap fw-bold' : '' }}">{{ $mat['detail_extra'] ?? '' }}</td>
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
                                    <td class="text-nowrap fw-bold">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>{{ number_format($mat['package_price'], 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                    <td class="text-muted text-nowrap ps-1">/ {{ $mat['package_unit'] }}</td>
                                @endif

                                @if(isset($mat['is_special']) && $mat['is_special'])
                                    <td class="text-center text-muted">-</td>
                                @else
                                    <td class="text-nowrap">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>{{ number_format($mat['total_price'], 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                @endif

                                @if($isFirstMaterial)
                                    <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                            <span class="text-success-dark" style="font-size: 15px;">{{ number_format($res['grand_total'], 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                    <td rowspan="{{ $rowCount }}" class="text-end bg-highlight align-top rowspan-cell">
                                        <div class="d-flex justify-content-between w-100">
                                            <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                            <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                        </div>
                                    </td>
                                    <td rowspan="{{ $rowCount }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                                @endif

                                @if(isset($mat['is_special']) && $mat['is_special'])
                                    <td class="text-center text-muted">-</td>
                                    <td></td>
                                @else
                                    @php
                                        $comparisonUnit = $mat['comparison_unit'] ?? ($mat['unit'] ?? '');
                                        $detailValue = $mat['detail_value'] ?? 1;
                                        $qtyValue = $mat['qty'] ?? 0;
                                        $totalPriceValue = $mat['total_price'] ?? 0;
                                        $actualBuyPrice = ($qtyValue > 0 && $detailValue > 0)
                                            ? ($totalPriceValue / $qtyValue / $detailValue)
                                            : 0;
                                    @endphp
                                    <td class="text-nowrap">
                                        <div class="d-flex justify-content-between w-100">
                                            <span>Rp</span>
                                            <span>{{ number_format($actualBuyPrice, 0, ',', '.') }}</span>
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
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada kombinasi tersedia untuk keramik ini.
        </div>
    @endif
</div>
