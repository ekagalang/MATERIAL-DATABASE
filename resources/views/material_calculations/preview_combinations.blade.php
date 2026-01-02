@extends('layouts.app')

@section('title', 'Pilih Kombinasi Material')

@section('content')
<div class="container-fluid py-4">
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                    Pilih Kombinasi Material
                </h2>
            </div>
            
            <a href="javascript:history.back()" class="btn-cancel" style="border: 1px solid #891313; background-color: transparent; color: #891313; padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                <i class="bi bi-arrow-left"></i> Kembali Filter
            </a>
        </div>
    </div>

    @if(empty($projects))
        <div class="container">
            <div class="alert" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 12px; padding: 16px 20px; color: #856404;">
                <i class="bi bi-exclamation-triangle me-2"></i> Tidak ditemukan data material yang cocok dengan filter Anda.
            </div>
        </div>
    @else

        {{-- TABEL REKAP GLOBAL (untuk semua bata) --}}
        @php
            // Prepare rekap data global untuk semua bata
            $filterCategories = ['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'];
            $globalRekapData = [];
            $hasBrick = false;
            $hasCement = false;
            $hasSand = false;
            $hasCat = false;

            // Get historical frequency data for TerUMUM from database
            $historicalFrequency = DB::table('brick_calculations')
                ->select('cement_id', 'sand_id', DB::raw('count(*) as frequency'))
                ->groupBy('cement_id', 'sand_id')
                ->orderByDesc('frequency')
                ->get()
                ->keyBy(function($item) {
                    return $item->cement_id . '-' . $item->sand_id;
                });

            // Definisi warna label untuk kolom Rekap (sama dengan yang di tabel utama)
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
            ];

            // Collect all combinations from all bricks
            $allCombinations = [];

            foreach ($projects as $project) {
                foreach ($project['combinations'] as $label => $items) {
                    foreach ($items as $item) {
                        $labelParts = array_map('trim', explode('=', $label));

                        foreach ($labelParts as $singleLabel) {
                            foreach ($filterCategories as $filterType) {
                                if (str_starts_with($singleLabel, $filterType)) {
                                    preg_match('/' . $filterType . '\s+(\d+)/', $singleLabel, $matches);
                                    if (isset($matches[1])) {
                                        $number = $matches[1];
                                        $key = $filterType . ' ' . $number;

                                        // Store all combinations for later processing
                                        if (!isset($allCombinations[$key])) {
                                            $allCombinations[$key] = [];
                                        }

                                        $allCombinations[$key][] = [
                                            'project' => $project,
                                            'item' => $item,
                                        ];
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // Second pass: Select best combination for each filter type
            foreach ($allCombinations as $key => $combinations) {
                $filterType = preg_replace('/\s+\d+.*$/', '', $key);
                $selectedCombination = null;

                if ($filterType === 'TerUMUM') {
                    // For TerUMUM: pick based on HISTORICAL frequency from database
                    $maxHistoricalFreq = 0;
                    $mostCommonHistorical = null;

                    foreach ($combinations as $combo) {
                        // Safe check for masonry materials
                        if (isset($combo['item']['cement']) && isset($combo['item']['sand'])) {
                            $materialKey = $combo['item']['cement']->id . '-' . $combo['item']['sand']->id;
                            $histFreq = isset($historicalFrequency[$materialKey]) ? $historicalFrequency[$materialKey]->frequency : 0;

                            if ($histFreq > $maxHistoricalFreq) {
                                $maxHistoricalFreq = $histFreq;
                                $mostCommonHistorical = $combo;
                            }
                        } else {
                            // For non-masonry (e.g. painting), just take the first one or logic for 'most common' painting
                            // Fallback to cheapest for now if no history logic
                            $mostCommonHistorical = $combo;
                        }
                    }

                    if ($mostCommonHistorical) {
                        $selectedCombination = $mostCommonHistorical;
                    }
                } else {
                    // For other filter types: use price-based selection
                    foreach ($combinations as $combo) {
                        if (!$selectedCombination) {
                            $selectedCombination = $combo;
                            continue;
                        }

                        $currentTotal = $combo['item']['result']['grand_total'];
                        $selectedTotal = $selectedCombination['item']['result']['grand_total'];

                        if ($filterType === 'TerMAHAL') {
                            // Pick the HIGHEST price
                            if ($currentTotal > $selectedTotal) {
                                $selectedCombination = $combo;
                            }
                        } else {
                            // For TerMURAH, TerSEDANG: pick the LOWEST price
                            if ($currentTotal < $selectedTotal) {
                                $selectedCombination = $combo;
                            }
                        }
                    }
                }

                // Store the selected combination
                if ($selectedCombination) {
                    $project = $selectedCombination['project'];
                    $item = $selectedCombination['item'];
                    $res = $item['result'];

                    if (($res['total_bricks'] ?? 0) > 0) $hasBrick = true;
                    if (($res['cement_sak'] ?? 0) > 0) $hasCement = true;
                    if (($res['sand_m3'] ?? 0) > 0) $hasSand = true;
                    if (($res['cat_packages'] ?? 0) > 0) $hasCat = true;

                    $rekapEntry = [
                        'grand_total' => $item['result']['grand_total'],
                        'brick_id' => $project['brick']->id,
                        'brick_brand' => $project['brick']->brand,
                        'brick_detail' => ($project['brick']->type ?? '-') . ' - ' .
                                        ($project['brick']->dimension_length + 0) . ' x ' .
                                        ($project['brick']->dimension_width + 0) . ' x ' .
                                        ($project['brick']->dimension_height + 0) . ' cm',
                        'filter_label' => $key,
                    ];

                    if (isset($item['cement'])) {
                        $rekapEntry['cement_id'] = $item['cement']->id;
                        $rekapEntry['cement_brand'] = $item['cement']->brand;
                        $rekapEntry['cement_detail'] = ($item['cement']->color ?? '-') . ' - ' . ($item['cement']->package_weight_net + 0) . ' Kg';
                    }

                    if (isset($item['sand'])) {
                        $rekapEntry['sand_id'] = $item['sand']->id;
                        $rekapEntry['sand_brand'] = $item['sand']->brand;
                        $rekapEntry['sand_detail'] = ($item['sand']->package_unit ?? '-') . ' - ' . (($item['sand']->package_volume ?? 0) > 0 ? (($item['sand']->package_volume + 0) . ' M3') : '-');
                    }

                    if (isset($item['cat'])) {
                        $rekapEntry['cat_id'] = $item['cat']->id;
                        $rekapEntry['cat_brand'] = $item['cat']->brand;
                        $rekapEntry['cat_detail'] = ($item['cat']->cat_name ?? '-') . ' - ' . ($item['cat']->color_name ?? '-') . ' (' . ($item['cat']->package_weight_net + 0) . ' kg)';
                    }

                    $globalRekapData[$key] = $rekapEntry;
                }
            }

            // Generate color mapping for combinations
            $globalColorMap = [];
            $brickColorMap = [];
            $cementColorMap = [];
            $sandColorMap = [];

            // BATA: Pastel Hangat (Nuansa Tanah Liat & Kemerahan)
            // Variasi: Dari Pink Salem ke Coklat Susu
            $brickColors = [
                '#FFAB91', // Deep Orange lighten-3 (Salem Oranye) -> Beda dari pink
                '#F48FB1', // Pink lighten-3 (Pink Permen) -> Pink standar
                '#BCAAA4', // Brown lighten-3 (Coklat Mocca) -> Netral hangat
                '#EF9A9A', // Red lighten-3 (Merah Pudar) -> Merah lembut
                '#E1BEE7', // Purple lighten-4 (Ungu Anggrek Pudar) -> Sentuhan beda
                '#FFCCBC', // Deep Orange lighten-4 (Peach Pucat)
                '#D7CCC8', // Brown lighten-4 (Coklat Abu/Taupe)
                '#FF8A80', // Red Accent-1 (Coral Lembut)
            ];

            // SEMEN: Pastel Dingin (Nuansa Beton & Langit)
            // Variasi: Dari Abu, Ungu, ke Hijau Mint
            $cementColors = [
                '#B0BEC5', // Blue Grey lighten-3 (Abu Semen Standar)
                '#90CAF9', // Blue lighten-3 (Biru Langit) -> Sangat biru
                '#CE93D8', // Purple lighten-3 (Ungu Lavender) -> Pembeda utama
                '#80CBC4', // Teal lighten-3 (Hijau Tosca Pudar) -> Nuansa kehijauan
                '#CFD8DC', // Blue Grey lighten-4 (Abu Perak)
                '#9FA8DA', // Indigo lighten-3 (Biru Ungu/Periwinkle)
                '#B3E5FC', // Light Blue lighten-3 (Biru Es)
                '#81D4FA', // Light Blue lighten-2 (Biru Awan)
            ];

            // PASIR: Pastel Alam (Nuansa Gurun & Tumbuhan)
            // Variasi: Dari Kuning Mentega ke Hijau Pistachio
            $sandColors = [
                '#FFF59D', // Yellow lighten-3 (Kuning Kenari) -> Kuning jelas
                '#AED581', // Light Green lighten-2 (Hijau Pistachio) -> Hijau jelas
                '#FFE0B2', // Orange lighten-4 (Krem Biscuits) -> Oranye pudar
                '#DCE775', // Lime lighten-2 (Hijau Pupus) -> Hijau kekuningan
                '#FFF176', // Yellow lighten-2 (Kuning Jagung Muda)
                '#C5E1A5', // Light Green lighten-3 (Hijau Melon)
                '#FFE082', // Amber lighten-3 (Kuning Telur) -> Lebih gelap
                '#F0F4C3', // Lime lighten-4 (Putih Tulang Kehijauan)
            ];

            // CAT: Pastel Cerah (Nuansa Dekoratif)
            $catColors = [
                '#F8BBD0', // Pink lighten-4
                '#E1BEE7', // Purple lighten-4
                '#D1C4E9', // Deep Purple lighten-4
                '#C5CAE9', // Indigo lighten-4
                '#BBDEFB', // Blue lighten-4
                '#B2EBF2', // Cyan lighten-4
                '#B2DFDB', // Teal lighten-4
                '#C8E6C9', // Green lighten-4
            ];

            // Grand Total: Use combined palette
            $availableColors = array_merge($brickColors, $cementColors, $sandColors, $catColors);

            // Color map for Grand Total - only color if combination appears more than once
            $colorIndex = 0;
            $combinationColorMap = []; // Track colors by combination signature
            $signatureCount = []; // Count occurrences of each signature

            // First pass: count how many times each signature appears
            foreach ($globalRekapData as $key1 => $data1) {
                // Generate safe signature
                if (isset($data1['cat_id'])) {
                    $signature = $data1['brick_id'] . '-cat-' . $data1['cat_id'];
                } else {
                    $signature = $data1['brick_id'] . '-' . ($data1['cement_id'] ?? 0) . '-' . ($data1['sand_id'] ?? 0);
                }

                if (!isset($signatureCount[$signature])) {
                    $signatureCount[$signature] = 0;
                }
                $signatureCount[$signature]++;
            }

            // Second pass: assign colors only to non-unique combinations
            foreach ($globalRekapData as $key1 => $data1) {
                if (!isset($globalColorMap[$key1])) {
                    // Create unique signature for this combination
                    if (isset($data1['cat_id'])) {
                        $signature = $data1['brick_id'] . '-cat-' . $data1['cat_id'];
                    } else {
                        $signature = $data1['brick_id'] . '-' . ($data1['cement_id'] ?? 0) . '-' . ($data1['sand_id'] ?? 0);
                    }

                    // Only assign color if this combination appears more than once
                    if ($signatureCount[$signature] > 1) {
                        if (isset($combinationColorMap[$signature])) {
                            // Use existing color for this combination
                            $globalColorMap[$key1] = $combinationColorMap[$signature];
                        } else {
                            // Assign new color for this recurring combination
                            $color = $availableColors[$colorIndex % count($availableColors)];
                            $globalColorMap[$key1] = $color;
                            $combinationColorMap[$signature] = $color;
                            $colorIndex++;
                        }
                    } else {
                        // Unique combination - white background (must be opaque for sticky columns)
                        $globalColorMap[$key1] = '#ffffff';
                    }
                }
            }

            // Color map for Brick - based on complete data (brand, size, price, type)
            // Use BRICK COLOR PALETTE (warm colors)
            $colorIndex = 0;
            $brickDataColorMap = []; // Track colors by complete brick data

            foreach (['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType) {
                for ($i = 1; $i <= 3; $i++) {
                    $key = $filterType . ' ' . $i;
                    if (isset($globalRekapData[$key])) {
                        $project = null;
                        // Find the project data for this key
                        foreach ($projects as $p) {
                            foreach ($p['combinations'] as $label => $items) {
                                foreach ($items as $item) {
                                    if (isset($globalRekapData[$key]) &&
                                        $p['brick']->id === $globalRekapData[$key]['brick_id']) {
                                        $project = $p;
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($project) {
                            // Create signature based on complete brick data (WITHOUT filterType)
                            $brick = $project['brick'];
                            $dataSignature = $brick->brand . '-' .
                                           $brick->type . '-' .
                                           $brick->dimension_length . '-' .
                                           $brick->dimension_width . '-' .
                                           $brick->dimension_height . '-' .
                                           ($brick->price ?? '0');

                            if (isset($brickDataColorMap[$dataSignature])) {
                                $brickColorMap[$key] = $brickDataColorMap[$dataSignature];
                            } else {
                                $color = $brickColors[$colorIndex % count($brickColors)];
                                $brickColorMap[$key] = $color;
                                $brickDataColorMap[$dataSignature] = $color;
                                $colorIndex++;
                            }
                        }
                    }
                }
            }

            // Color map for Cement - based on complete data (brand, color, weight, price)
            // Use CEMENT COLOR PALETTE (cool colors)
            $colorIndex = 0;
            $cementDataColorMap = []; // Track colors by complete cement data

            foreach (['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType) {
                for ($i = 1; $i <= 3; $i++) {
                    $key = $filterType . ' ' . $i;
                    if (isset($globalRekapData[$key])) {
                        $cement = null;
                        // Find the cement data for this key
                        foreach ($projects as $p) {
                            foreach ($p['combinations'] as $label => $items) {
                                foreach ($items as $item) {
                                    if (isset($globalRekapData[$key]) && 
                                        isset($globalRekapData[$key]['cement_id']) &&
                                        isset($item['cement']) &&
                                        $item['cement']->id === $globalRekapData[$key]['cement_id']) {
                                        $cement = $item['cement'];
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($cement) {
                            // Create signature based on complete cement data (WITHOUT filterType)
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
                    }
                }
            }

            // Color map for Sand - based on complete data (brand, unit, volume, price)
            // Use SAND COLOR PALETTE (earth/yellow tones)
            $colorIndex = 0;
            $sandDataColorMap = []; // Track colors by complete sand data

            foreach (['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType) {
                for ($i = 1; $i <= 3; $i++) {
                    $key = $filterType . ' ' . $i;
                    if (isset($globalRekapData[$key])) {
                        $sand = null;
                        // Find the sand data for this key
                        foreach ($projects as $p) {
                            foreach ($p['combinations'] as $label => $items) {
                                foreach ($items as $item) {
                                    if (isset($globalRekapData[$key]) && 
                                        isset($globalRekapData[$key]['sand_id']) &&
                                        isset($item['sand']) &&
                                        $item['sand']->id === $globalRekapData[$key]['sand_id']) {
                                        $sand = $item['sand'];
                                        break 3;
                                    }
                                }
                            }
                        }

                        if ($sand) {
                            // Create signature based on complete sand data (WITHOUT filterType)
                            $dataSignature = $sand->brand . '-' .
                                           ($sand->package_unit ?? '-') . '-' .
                                           ($sand->package_volume ?? '0') . '-' .
                                           ($sand->price ?? '0');

                            if (isset($sandDataColorMap[$dataSignature])) {
                                $sandColorMap[$key] = $sandDataColorMap[$dataSignature];
                            } else {
                                $color = $sandColors[$colorIndex % count($sandColors)];
                                $sandColorMap[$key] = $color;
                                $sandDataColorMap[$dataSignature] = $color;
                                $colorIndex++;
                            }
                        }
                    }
                }
            }

            // Color map for Cat
            $colorIndex = 0;
            $catDataColorMap = [];
            $catColorMap = [];

            foreach (['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType) {
                for ($i = 1; $i <= 3; $i++) {
                    $key = $filterType . ' ' . $i;
                    if (isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_id'])) {
                        // Create signature
                        $catId = $globalRekapData[$key]['cat_id'];
                        $catBrand = $globalRekapData[$key]['cat_brand'];
                        $dataSignature = $catId . '-' . $catBrand;

                        if (isset($catDataColorMap[$dataSignature])) {
                            $catColorMap[$key] = $catDataColorMap[$dataSignature];
                        } else {
                            $color = $catColors[$colorIndex % count($catColors)];
                            $catColorMap[$key] = $color;
                            $catDataColorMap[$dataSignature] = $color;
                            $colorIndex++;
                        }
                    }
                }
            }
        @endphp

        @if(count($globalRekapData) > 0)
        <div class="container mb-4">
            @php
                if (!isset($area)) {
                    $area = ($requestData['wall_length'] ?? 0) * ($requestData['wall_height'] ?? 0);
                }
            @endphp
            <div class="card p-3 shadow-sm border-0" style="background-color: #fdfdfd; border-radius: 12px;">
                <div class="d-flex flex-wrap align-items-end gap-3 justify-content-between">
                    {{-- Jenis Item Pekerjaan --}}
                    <div style="flex: 1; min-width: 250px;">
                        <label class="fw-bold mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="bi bi-briefcase me-1"></i>Jenis Item Pekerjaan
                        </label>
                        <div class="form-control fw-bold border-secondary text-dark" style="background-color: #e9ecef; opacity: 1;">
                            {{ $formulaName }}
                        </div>
                    </div>

                    {{-- Tebal Spesi / Lapis Cat --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        @php
                            $isPainting = (isset($requestData['work_type']) && $requestData['work_type'] === 'painting');
                            $paramLabel = $isPainting ? 'LAPIS' : 'TEBAL';
                            $paramUnit = $isPainting ? 'Lapis' : 'cm';
                            $paramValue = $isPainting ? ($requestData['painting_layers'] ?? 2) : ($requestData['mortar_thickness'] ?? 2.0);
                            $badgeClass = $isPainting ? 'bg-primary text-white' : 'bg-light';
                        @endphp
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge {{ $badgeClass }} border">{{ $paramLabel }}</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ $paramValue }}</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">{{ $paramUnit }}</span>
                        </div>
                    </div>

                    {{-- Panjang --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-light border">PANJANG</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ number_format((float)$requestData['wall_length'], 2, '.', '') }}</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>

                    {{-- Tinggi / Lebar (untuk Rollag) --}}
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-ligh border">
                                {{ isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag' ? 'LEBAR' : 'TINGGI' }}
                            </span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ number_format((float)$requestData['wall_height'], 2, '.', '') }}</div>
                            <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                        </div>
                    </div>

                    {{-- Tingkat (hanya untuk Rollag) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'brick_rollag')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-warning border">TINGKAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #fffbeb; border-color: #fcd34d;">{{ $requestData['layer_count'] ?? 1 }}</div>
                            <span class="input-group-text bg-warning small px-1" style="font-size: 0.7rem;">Lapis</span>
                        </div>
                    </div>
                    @endif

                    {{-- Sisi Aci (hanya untuk Aci Dinding) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'skim_coating')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-info text-white border">SISI ACI</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #e0f2fe; border-color: #38bdf8;">{{ $requestData['skim_sides'] ?? 1 }}</div>
                            <span class="input-group-text bg-info text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                        </div>
                    </div>
                    @endif

                    {{-- Sisi Plester (hanya untuk Plester Dinding) --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'wall_plastering')
                    <div style="flex: 0 0 auto; width: 100px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-success text-white border">SISI PLESTER</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #d1fae5; border-color: #34d399;">{{ $requestData['plaster_sides'] ?? 1 }}</div>
                            <span class="input-group-text bg-success text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                        </div>
                    </div>
                    @endif

                    {{-- Lapis Pengecatan --}}
                    @if(isset($requestData['work_type']) && $requestData['work_type'] === 'wall_painting')
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-primary text-white border border-primary">LAPIS CAT</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center px-1" style="background-color: #dbeafe; border-color: #3b82f6;">{{ $requestData['paint_layers'] ?? 1 }}</div>
                            <span class="input-group-text bg-primary text-white small px-1" style="font-size: 0.7rem;">Lapisan</span>
                        </div>
                    </div>
                    @endif

                    {{-- Luas --}}
                    <div style="flex: 0 0 auto; width: 120px;">
                        <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                            <span class="badge bg-danger text-white border border-danger">LUAS</span>
                        </label>
                        <div class="input-group">
                            <div class="form-control fw-bold text-center bg-white text-danger px-1" style="border-color: #dc3545;">{{ number_format($area, 2) }}</div>
                            <span class="input-group-text bg-danger text-white small px-1" style="font-size: 0.7rem; border-color: #dc3545;">M2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
                <div class="table-responsive">
                    <style>
                        .table-rekap-global th {
                            padding: 8px 10px !important;
                            font-size: 13px !important;
                        }
                        .table-rekap-global td {
                            padding: 8px 10px !important;
                        }
                    </style>
                    <table class="table-preview table-rekap-global" style="margin: 0;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 0; z-index: 3; width: 80px; min-width: 80px;">Rekap</th>
                                <th rowspan="2" style="background: #891313; color: white; position: sticky; left: 80px; z-index: 3; width: 120px; min-width: 120px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.3);">Grand Total</th>
                                @if($hasBrick)
                                <th colspan="2" style="background: #891313; color: white;">Bata</th>
                                @endif
                                @if($hasCement)
                                <th colspan="2" style="background: #891313; color: white;">Semen</th>
                                @endif
                                @if($hasSand)
                                <th colspan="2" style="background: #891313; color: white;">Pasir</th>
                                @endif
                                @if($hasCat)
                                <th colspan="2" style="background: #891313; color: white;">Cat</th>
                                @endif
                            </tr>
                            <tr>
                                @if($hasBrick)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasCement)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasSand)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                                @if($hasCat)
                                <th style="background: #891313; color: white;">Merek</th>
                                <th style="background: #891313; color: white;">Detail</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType)
                                @for($i = 1; $i <= 3; $i++)
                                    @php
                                        $key = $filterType . ' ' . $i;
                                        $bgColor = $globalColorMap[$key] ?? '#ffffff';
                                        $brickBgColor = $brickColorMap[$key] ?? '#ffffff';
                                        $cementBgColor = $cementColorMap[$key] ?? '#ffffff';
                                        $sandBgColor = $sandColorMap[$key] ?? '#ffffff';
                                        $catBgColor = $catColorMap[$key] ?? '#ffffff';

                                        // Get label color untuk kolom Rekap
                                        $labelColor = $rekapLabelColors[$filterType][$i] ?? ['bg' => '#ffffff', 'text' => '#000000'];
                                    @endphp
                                    <tr>
                                        {{-- Column 1: Filter Label --}}
                                        <td style="font-weight: 700; position: sticky; left: 0; z-index: 2; background: {{ $labelColor['bg'] }}; color: {{ $labelColor['text'] }}; padding: 4px 8px; vertical-align: middle; width: 80px; min-width: 80px;">
                                            <a href="#detail-{{ strtolower(str_replace(' ', '-', $key)) }}" style="color: inherit; text-decoration: none; display: block; cursor: pointer;">
                                                {{ $key }}
                                            </a>
                                        </td>

                                        {{-- Column 2: Grand Total --}}
                                        <td class="text-end fw-bold" style="position: sticky; left: 80px; box-shadow: 2px 0 4px -2px rgba(0, 0, 0, 0.1); background: {{ $bgColor }}; padding: 4px 8px; vertical-align: middle; width: 120px; min-width: 120px;">
                                            @if(isset($globalRekapData[$key]))
                                                <div class="d-flex justify-content-between w-100">
                                                    <span>Rp</span>
                                                    <span>{{ number_format($globalRekapData[$key]['grand_total'], 0, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 3: Merek Bata --}}
                                        @if($hasBrick)
                                        <td style="background: {{ $brickBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                <div title="Grand Total: Rp {{ number_format($globalRekapData[$key]['grand_total'], 0, ',', '.') }}">
                                                    {{ $globalRekapData[$key]['brick_brand'] }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 4: Detail Bata --}}
                                        <td class="text-muted small" style="background: {{ $brickBgColor }}; vertical-align: middle; border-right: 2px solid #891313;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['brick_detail'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 5: Merek Semen --}}
                                        @if($hasCement)
                                        <td style="background: {{ $cementBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['cement_brand'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 6: Detail Semen --}}
                                        <td class="text-muted small" style="background: {{ $cementBgColor }}; vertical-align: middle; border-right: 2px solid #891313;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['cement_detail'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 7: Merek Pasir --}}
                                        @if($hasSand)
                                        <td style="background: {{ $sandBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]))
                                                {{ $globalRekapData[$key]['sand_brand'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 8: Detail Pasir --}}
                                        <td class="text-muted small" style="background: {{ $sandBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['sand_brand']))
                                                {{ $globalRekapData[$key]['sand_detail'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif

                                        {{-- Column 9: Merek Cat --}}
                                        @if($hasCat)
                                        <td style="background: {{ $catBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_brand']))
                                                {{ $globalRekapData[$key]['cat_brand'] }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- Column 10: Detail Cat --}}
                                        <td class="text-muted small" style="background: {{ $catBgColor }}; vertical-align: middle;">
                                            @if(isset($globalRekapData[$key]) && isset($globalRekapData[$key]['cat_detail']))
                                                {{ $globalRekapData[$key]['cat_detail'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                @endfor
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- SINGLE TABLE FOR ALL COMBINATIONS --}}
        <div class="container">
            <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden; position: relative; z-index: 1;">
                <div class="table-responsive">
                                <style>
                                    /* Global Text Styling */
                                    .table-preview th,
                                    .table-preview td,
                                    .table-preview span,
                                    .table-preview div,
                                    .table-preview a,
                                    .table-preview label,
                                    .table-preview button {
                                        font-family: 'League Spartan', sans-serif !important;
                                        color: #ffffff !important;
                                        -webkit-text-stroke: 0.2px black !important;
                                        text-shadow: 0 1.1px 0 #000000 !important;
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
                                        border: none;
                                        font-size: 14px;
                                        letter-spacing: 0.3px;
                                        white-space: nowrap;
                                    }
                                    .table-preview td {
                                        padding: 14px 16px;
                                        border-bottom: 1px solid #f1f5f9;
                                        vertical-align: top;
                                        white-space: nowrap;
                                    }
                                    .table-preview td.store-cell,
                                    .table-preview td.address-cell {
                                        white-space: normal;
                                        word-wrap: break-word;
                                        word-break: break-word;
                                        max-width: 200px;
                                        min-width: 150px;
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
                                        left: 200px;
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

                                <table class="table-preview">
                                    <thead class="align-top">
                                        <tr>
                                            <th class="sticky-col-1">Qty<br>/ Pekerjaan</th>
                                            <th class="sticky-col-2">Satuan</th>
                                            <th class="sticky-col-3">Material</th>
                                            <th colspan="4">Detail</th>
                                            <th>Toko</th>
                                            <th>Alamat</th>
                                            <th colspan="2">Harga / Kemasan</th>
                                            <th>Harga Komparasi</br> / Pekerjaan</th>
                                            <th>Total Biaya</br> Material / Pekerjaan</th>
                                            <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                                            <th colspan="2">Harga Satuan Beli</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $globalIndex = 0;
                                            // Collect ALL filtered combinations from ALL projects
                                            // Display them in the order of the recap table
                                            $allFilteredCombinations = [];

                                            foreach (['TerUMUM', 'TerMURAH', 'TerSEDANG', 'TerMAHAL'] as $filterType) {
                                                for ($i = 1; $i <= 3; $i++) {
                                                    $key = $filterType . ' ' . $i;

                                                    // Check if this filter exists in global recap
                                                    if (isset($globalRekapData[$key])) {
                                                        $rekapData = $globalRekapData[$key];

                                                        // Search through ALL projects to find the matching combination
                                                        foreach ($projects as $project) {
                                                            // Check if this project uses the brick from recap
                                                            if ($rekapData['brick_id'] === $project['brick']->id) {
                                                                // Find the matching combination in this project
                                                                foreach ($project['combinations'] as $label => $items) {
                                                                    foreach ($items as $item) {
                                                                        $match = false;
                                                                        if (isset($rekapData['cat_id']) && isset($item['cat'])) {
                                                                            // Match by Cat ID
                                                                            if ($item['cat']->id === $rekapData['cat_id']) {
                                                                                $match = true;
                                                                            }
                                                                        } elseif (isset($rekapData['cement_id']) && isset($rekapData['sand_id']) && isset($item['cement']) && isset($item['sand'])) {
                                                                            // Match by Cement & Sand ID
                                                                            if ($item['cement']->id === $rekapData['cement_id'] && $item['sand']->id === $rekapData['sand_id']) {
                                                                                $match = true;
                                                                            }
                                                                        }

                                                                        if ($match) {
                                                                            $allFilteredCombinations[] = [
                                                                                'label' => $key, // Use recap label
                                                                                'item' => $item,
                                                                                'brick' => $project['brick']
                                                                            ];
                                                                            break 3; // Found it, move to next filter
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        @endphp

                                        @foreach($allFilteredCombinations as $combo)
                                            @php
                                                $globalIndex++;
                                                $label = $combo['label'];
                                                $item = $combo['item'];
                                                $brick = $combo['brick'];
                                                $res = $item['result'];
                                                $isFirstOption = ($globalIndex === 1);
                                                $costPerM2 = $area > 0 ? $res['grand_total'] / $area : 0;

                                                // ========================================
                                                // DYNAMIC MATERIAL CONFIGURATION
                                                // To add new material, just add to this array!
                                                // ========================================
                                                $materialConfig = [
                                                    'brick' => [
                                                        'name' => 'Bata',
                                                        'check_field' => 'total_bricks',
                                                        'qty' => $res['total_bricks'] ?? 0,
                                                        'unit' => 'Bh',
                                                        'object' => $brick,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => ($brick->dimension_length + 0) . ' x ' . ($brick->dimension_width + 0) . ' x ' . ($brick->dimension_height + 0) . ' cm',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => $brick->price_per_piece ?? 0,
                                                        'package_unit' => 'bh',
                                                        'total_price' => $res['total_brick_price'] ?? 0,
                                                        'unit_price' => $brick->price_per_piece ?? 0,
                                                        'unit_price_label' => 'bh',
                                                    ],
                                                    'cement' => [
                                                        'name' => 'Semen',
                                                        'check_field' => 'cement_sak',
                                                        'qty' => $res['cement_sak'] ?? 0,
                                                        'unit' => 'Sak',
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
                                                    'cat' => [
                                                        'name' => 'Cat',
                                                        'check_field' => 'cat_packages',
                                                        'qty' => $res['cat_packages'] ?? 0,
                                                        'unit' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Kmsn') : 'Kmsn',
                                                        'object' => $item['cat'] ?? null,
                                                        'type_field' => 'type',
                                                        'brand_field' => 'brand',
                                                        'detail_display' => isset($item['cat']) ? ($item['cat']->color_name ?? '-') : '-',
                                                        'detail_extra' => isset($item['cat']) ? (($item['cat']->package_weight_net + 0) . ' Kg') : '-',
                                                        'store_field' => 'store',
                                                        'address_field' => 'address',
                                                        'package_price' => isset($item['cat']) ? ($item['cat']->purchase_price ?? 0) : 0,
                                                        'package_unit' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                        'total_price' => $res['total_cat_price'] ?? 0,
                                                        'unit_price' => $res['cat_price_per_package'] ?? 0,
                                                        'unit_price_label' => isset($item['cat']) ? ($item['cat']->package_unit ?? 'Galon') : 'Galon',
                                                    ],
                                                    'water' => [
                                                        'name' => 'Air',
                                                        'check_field' => 'water_liters',
                                                        'qty' => $res['water_liters'] ?? 0,
                                                        'unit' => 'L',
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
                                                        'is_special' => true, // Special handling for water
                                                    ],
                                                ];

                                                // Filter materials: only show if qty > 0
                                                $visibleMaterials = array_filter($materialConfig, function($mat) {
                                                    return isset($mat['qty']) && $mat['qty'] > 0;
                                                });

                                                // Calculate rowspan based on visible materials
                                                $rowCount = count($visibleMaterials);
                                            @endphp

                                                {{-- ROW 0: GROUP NAME / LABEL --}}
                                                <tr class="{{ $isFirstOption ? '' : 'group-divider' }}" id="detail-{{ strtolower(str_replace(' ', '-', $label)) }}">
                                                    <td colspan="3" class="text-start align-middle sticky-label-row sticky-col-label" style="background: #f8fafc; padding: 10px 16px; font-weight: 600;">
                                                        @php
                                                            // Definisi warna dengan 3 level gradasi (1=gelap, 2=sedang, 3=cerah)
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

                                                            // Split label berdasarkan " = " untuk handle multiple labels
                                                            $labelParts = array_map('trim', explode('=', $label));
                                                        @endphp
                                                        <div style="display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                                            <span style="color: #891313; font-weight: 700; font-size: 11px;">
                                                                #{{ $globalIndex }}
                                                            </span>
                                                            @foreach($labelParts as $index => $singleLabel)
                                                                @php
                                                                    // Extract prefix dari label (sebelum angka)
                                                                    $labelPrefix = preg_replace('/\s+\d+.*$/', '', $singleLabel);
                                                                    $labelPrefix = trim($labelPrefix);

                                                                    // Extract nomor dari label (contoh: "TerBAIK 1" -> 1)
                                                                    preg_match('/\s+(\d+)/', $singleLabel, $matches);
                                                                    $number = isset($matches[1]) ? (int)$matches[1] : 1;

                                                                    // Batasi number ke range 1-3
                                                                    $number = max(1, min(3, $number));

                                                                    // Ambil warna berdasarkan prefix dan number
                                                                    $colorSet = $labelColors[$labelPrefix] ?? [
                                                                        1 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                        2 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                        3 => ['bg' => '#f8fafc', 'border' => '#cbd5e1', 'text' => '#64748b'],
                                                                    ];
                                                                    $color = $colorSet[$number];
                                                                @endphp
                                                                <span class="badge" style="background: {{ $color['bg'] }}; border: 1.5px solid {{ $color['border'] }}; color: {{ $color['text'] }}; padding: 3px 8px; border-radius: 5px; font-weight: 600; font-size: 10px; white-space: nowrap;">
                                                                    {{ $singleLabel }}
                                                                </span>
                                                                @if($index < count($labelParts) - 1)
                                                                    <span style="color: #94a3b8; font-size: 10px; font-weight: 600;">=</span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td colspan="18" style="background: #f8fafc;"></td>
                                                </tr>

                                                {{-- DYNAMIC MATERIAL ROWS --}}
                                                @php $matIndex = 0; @endphp
                                                @foreach($visibleMaterials as $matKey => $mat)
                                                    @php
                                                        $matIndex++;
                                                        $isFirstMaterial = $matIndex === 1;
                                                        $isLastMaterial = $matIndex === count($visibleMaterials);
                                                    @endphp
                                                    <tr class="{{ $isLastMaterial ? 'group-end' : '' }}">
                                                        {{-- Column 1-3: Qty, Unit, Material Name --}}
                                                        <td class="text-end fw-bold sticky-col-1">{{ $mat['unit'] === 'M3' ? number_format($mat['qty'], 3, ',', '.') : number_format($mat['qty'], 2, ',', '.') }}</td>
                                                        <td class="text-center sticky-col-2">{{ $mat['unit'] }}</td>
                                                        <td class="fw-bold sticky-col-3">{{ $mat['name'] }}</td>

                                                        {{-- Column 4-9: Material Details --}}
                                                        <td class="text-muted">{{ $mat['type_display'] ?? ($mat['object']->{$mat['type_field']} ?? '-') }}</td>
                                                        <td class="fw-bold">{{ $mat['brand_display'] ?? ($mat['object']->{$mat['brand_field']} ?? '-') }}</td>
                                                        <td class="{{ $matKey === 'brick' ? 'text-center text-nowrap' : '' }}">{{ $mat['detail_display'] }}</td>
                                                        <td class="{{ $matKey === 'cement' || $matKey === 'sand' ? 'text-start text-nowrap fw-bold' : '' }}">{{ $mat['detail_extra'] ?? '' }}</td>
                                                        <td class="store-cell">{{ $mat['store_display'] ?? ($mat['object']->{$mat['store_field']} ?? '-') }}</td>
                                                        <td class="small text-muted address-cell">{{ $mat['address_display'] ?? ($mat['object']->{$mat['address_field']} ?? '-') }}</td>

                                                        {{-- Column 10-11: Package Price --}}
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

                                                        {{-- Column 12: Total Price (Harga Komparasi) --}}
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

                                                        {{-- Column 13-15: Rowspan columns (Grand Total, Cost per M2, Action) --}}
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

                                                        {{-- Column 16-17: Unit Price --}}
                                                        @if(isset($mat['is_special']) && $mat['is_special'])
                                                            <td class="text-center text-muted">-</td>
                                                            <td></td>
                                                        @else
                                                            <td class="text-nowrap">
                                                                <div class="d-flex justify-content-between w-100">
                                                                    <span>Rp</span>
                                                                    <span>{{ number_format($mat['unit_price'], 0, ',', '.') }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="text-muted text-nowrap ps-1">/ {{ $mat['unit_price_label'] }}</td>
                                                        @endif

                                                        {{-- Column 18: Action (Rowspan) --}}
                                                        @if($isFirstMaterial)
                                                            <td rowspan="{{ $rowCount }}" class="text-center align-top rowspan-cell">
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
                                                                    <input type="hidden" name="brick_id" value="{{ $brick->id }}">
                                                                    @if(isset($item['cement']))
                                                                        <input type="hidden" name="cement_id" value="{{ $item['cement']->id }}">
                                                                    @endif
                                                                    @if(isset($item['sand']))
                                                                        <input type="hidden" name="sand_id" value="{{ $item['sand']->id }}">
                                                                    @endif
                                                                    @if(isset($item['cat']))
                                                                        <input type="hidden" name="cat_id" value="{{ $item['cat']->id }}">
                                                                    @endif
                                                                    <input type="hidden" name="price_filters[]" value="custom">
                                                                    <input type="hidden" name="confirm_save" value="1">
                                                                    <button type="submit" class="btn-select">
                                                                        <i class="bi bi-check-circle me-1"></i> Pilih
                                                                    </button>
                                                                </form>
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
                            <p class="" style="font-size: 13px;">
                                <i class="bi bi-info-circle me-1"></i> Gunakan tombol <span class="text-muted">Pilih</span> pada kolom Aksi untuk menyimpan perhitungan ini ke proyek Anda.
                            </p>
                        </div>
            </div>
        </div>
    @endif
</div>

<style>
    /* Global Text Styling for All Elements */
    h1, h2, h3, h4, h5, h6, p, span, div, a, label, input, select, textarea, button, th, td, i, strong {
        font-family: 'League Spartan', sans-serif !important;
        color: #ffffff !important;
        -webkit-text-stroke: 0.2px black !important;
        text-shadow: 0 1.1px 0 #000000 !important;
        font-weight: 700 !important;
    }

    /* Smooth scroll untuk seluruh halaman */
    html {
        scroll-behavior: smooth;
    }

    /* Hover effect untuk button cancel */
    .btn-cancel:hover {
        background: linear-gradient(135deg, #891313 0%, #a61515 100%) !important;
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
    }

    /* Hover effect untuk link rekap - tambahkan underline saat hover */
    .table-preview tbody td a:hover {
        text-decoration: underline !important;
        opacity: 0.8;
    }

    /* Highlight effect dengan blinking border untuk target row */
    /* Exclude sticky columns to preserve sticky behavior */
    tr:target td:not(.sticky-col-1):not(.sticky-col-2):not(.sticky-col-3):not(.sticky-col-label) {
        animation: border-blink 1.5s ease-in-out 3;
    }

    /* Apply animation to sticky columns without changing position */
    tr:target td.sticky-col-1,
    tr:target td.sticky-col-2,
    tr:target td.sticky-col-3,
    tr:target td.sticky-col-label {
        animation: border-blink-sticky 1.5s ease-in-out 3;
    }

    @keyframes border-blink {
        0%, 100% {
            box-shadow: inset 0 0 0 0px transparent;
            background-color: transparent;
        }
        25% {
            box-shadow: inset 0 0 0 3px #891313;
            background-color: rgba(137, 19, 19, 0.05);
        }
        50% {
            box-shadow: inset 0 0 0 3px transparent;
            background-color: transparent;
        }
        75% {
            box-shadow: inset 0 0 0 3px #891313;
            background-color: rgba(137, 19, 19, 0.05);
        }
    }

    /* Animation khusus untuk sticky columns - tanpa mengubah position */
    @keyframes border-blink-sticky {
        0%, 100% {
            box-shadow: inset 0 0 0 0px transparent;
            background-color: transparent;
        }
        25% {
            box-shadow: inset 0 0 0 3px #891313;
            background-color: rgba(137, 19, 19, 0.05);
        }
        50% {
            box-shadow: inset 0 0 0 3px transparent;
            background-color: transparent;
        }
        75% {
            box-shadow: inset 0 0 0 3px #891313;
            background-color: rgba(137, 19, 19, 0.05);
        }
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush