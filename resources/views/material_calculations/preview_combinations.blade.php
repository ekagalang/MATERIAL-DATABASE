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
                
                @php
                    $headerTitle = '';
                    if(isset($projects) && count($projects) > 0) {
                        if (count($projects) == 1) {
                            $firstBrick = $projects[0]['brick'];
                            $headerTitle = "untuk bata <strong>{$firstBrick->brand}</strong>";
                        } else {
                            $count = count($projects);
                            $headerTitle = "untuk <strong>{$count} Jenis Bata</strong> yang dipilih";
                        }
                    }
                    
                    $area = 0;
                    if(isset($requestData['wall_length']) && isset($requestData['wall_height'])) {
                        $area = $requestData['wall_length'] * $requestData['wall_height'];
                    }
                @endphp

                <p class="text-muted mb-0" style="font-size: 14px;">
                    Menampilkan opsi kombinasi {!! $headerTitle !!}
                    <span class="badge" style="background: linear-gradient(135deg, #891313 0%, #a61515 100%); color: #ffffff; padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 12px;">
                        Luas: {{ number_format($area, 2) }} m²
                    </span>
                </p>
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
        
        {{-- TABS NAVIGATION --}}
        @if(count($projects) > 1)
            <div class="container mb-4">
                <ul class="nav nav-pills p-2 rounded" id="brickTabs" role="tablist" style="background: #ffffff; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border-radius: 12px; gap: 4px;">
                    @foreach($projects as $index => $project)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                                    id="brick-tab-{{ $index }}" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#brick-content-{{ $index }}" 
                                    type="button" 
                                    role="tab">
                                {{ $project['brick']->brand }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- TAB CONTENTS --}}
        <div class="tab-content" id="brickTabsContent">
            @foreach($projects as $index => $project)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                     id="brick-content-{{ $index }}" role="tabpanel">
                    
                    @if(empty($project['combinations']))
                        <div class="container">
                            <div class="alert" style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 12px; padding: 16px 20px; color: #0c5460;">
                                <i class="bi bi-info-circle me-2"></i> 
                                @if(in_array('best', $requestData['price_filters'] ?? []) && count($requestData['price_filters'] ?? []) == 1)
                                    Belum ada rekomendasi material untuk bata ini. Silakan atur di menu <a href="{{ route('settings.recommendations.index') }}" class="alert-link global-open-modal">Setting Rekomendasi</a>.
                                @else
                                    Tidak ada kombinasi material yang cocok untuk bata ini.
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
                            <div class="table-responsive">
                                <style>
                                    /* Tab Navigation Styling */
                                    .nav-pills .nav-link {
                                        padding: 10px 20px;
                                        border-radius: 10px;
                                        font-weight: 600;
                                        font-size: 14px;
                                        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                                        border: none;
                                        background: transparent;
                                        color: #64748b;
                                    }
                                    .nav-pills .nav-link:hover {
                                        background: #f8fafc;
                                        color: #334155;
                                    }
                                    .nav-pills .nav-link.active {
                                        background: linear-gradient(135deg, #891313 0%, #a61515 100%) !important;
                                        color: #ffffff !important;
                                        box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
                                    }

                                    /* Table Styling */
                                    .table-preview { 
                                        width: 100%; 
                                        border-collapse: separate;
                                        border-spacing: 0;
                                        font-size: 13px; 
                                        color: #1e293b; 
                                        margin: 0;
                                    }
                                    .table-preview th { 
                                        background: #891313;
                                        color: #ffffff; 
                                        text-align: center; 
                                        font-weight: 900; 
                                        padding: 14px 16px; 
                                        border: none;
                                        font-size: 12px;
                                        letter-spacing: 0.3px;
                                        white-space: nowrap;
                                    }
                                    .table-preview td { 
                                        padding: 14px 16px;
                                        border-bottom: 1px solid #f1f5f9;
                                        vertical-align: middle; 
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
                                </style>

                                <table class="table-preview">
                                    <thead class="align-top">
                                        <tr>
                                            <th>No</th>
                                            <th colspan="3">Item Pekerjaan</th>
                                            <th>Opsi</th>
                                            <th>Qty / Pekerjaan</th>
                                            <th>Satuan</th>
                                            <th>Material</th>
                                            <th colspan="7">Detail</th>
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
                                        @php $globalIndex = 0; @endphp
                                        @foreach($project['combinations'] as $label => $items)
                                            @foreach($items as $item)
                                                @php
                                                    $globalIndex++;
                                                    $res = $item['result'];
                                                    $isFirstOption = ($globalIndex === 1);
                                                    $costPerM2 = $area > 0 ? $res['grand_total'] / $area : 0;
                                                @endphp

                                                {{-- ROW 1: BATA + MERGED CELLS --}}
                                                <tr class="{{ $isFirstOption ? '' : 'group-divider' }} text-nowrap">
                                                    {{-- Merged Project Info --}}
                                                    @if($isFirstOption)
                                                        <td rowspan="4" class="text-center align-middle fw-bold" style="font-size: 14px; color: #891313;">
                                                            {{ $loop->parent->iteration }}
                                                        </td>
                                                        <td colspan="3" class="text-start align-middle fw-bold ps-3" style="font-size: 13px; color: #0f172a;">
                                                            {{ $formulaName }}
                                                        </td>
                                                    @else
                                                        <td colspan="4" rowspan="4" class="bg-light border-end"></td>
                                                    @endif

                                                    {{-- Option Number --}}
                                                    <td class="text-start align-top" rowspan="4">
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

                                                    {{-- Bata Data --}}
                                                    <td class="text-end fw-bold">{{ number_format($res['total_bricks'], 0, ',', '.') }}</td>
                                                    <td class="text-center">Bh</td>
                                                    <td class="fw-bold">Bata</td>
                                                    <td class="text-muted">{{ $project['brick']->type ?? '-' }}</td>
                                                    <td class="fw-bold">{{ $project['brick']->brand }}</td>
                                                    <td class="text-center text-nowrap">{{ $project['brick']->dimension_length + 0 }} cm</td>
                                                    <td class="text-center text-muted">x</td>
                                                    <td class="text-center text-nowrap">{{ $project['brick']->dimension_width + 0 }} cm</td>
                                                    <td class="text-center text-muted">x</td>
                                                    <td class="text-center text-nowrap">{{ $project['brick']->dimension_height + 0 }} cm</td>
                                                    <td>{{ $project['brick']->store ?? '-' }}</td>
                                                    <td class="small text-muted">{{ $project['brick']->address ?? '-' }}</td>
                                                    <td class="text-nowrap fw-bold">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($project['brick']->price_per_piece, 0, ',', '.') }}</span></td>
                                                        </div>
                                                    <td class="text-muted text-nowrap ps-1">/ bh</td>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($res['total_brick_price'], 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>

                                                    {{-- Totals Merged --}}
                                                    <td rowspan="4" class="text-end bg-highlight align-top">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                                            <span class="text-success-dark" style="font-size: 15px;">{{ number_format($res['grand_total'], 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td rowspan="4" class="text-end bg-highlight align-top">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                                            <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td rowspan="4" class="bg-highlight align-top text-muted fw-bold text-start ps-1" style="max-width: 30px">/ M2</td>

                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($project['brick']->price_per_piece, 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted ps-1">/ bh</td>

                                                    {{-- Select Button (End of Table) --}}
                                                    <td rowspan="4" class="text-center align-top">
                                                        <form action="{{ route('material-calculations.store') }}" method="POST" style="margin: 0;">
                                                            @csrf
                                                            {{-- Inject Data Request --}}
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

                                                            <input type="hidden" name="brick_id" value="{{ $project['brick']->id }}">
                                                            <input type="hidden" name="cement_id" value="{{ $item['cement']->id }}">
                                                            <input type="hidden" name="sand_id" value="{{ $item['sand']->id }}">
                                                            <input type="hidden" name="price_filters[]" value="custom">
                                                            <input type="hidden" name="confirm_save" value="1"> 

                                                            <button type="submit" class="btn-select">
                                                                <i class="bi bi-check-circle me-1"></i> Pilih
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>

                                                {{-- ROW 2: SEMEN --}}
                                                <tr>
                                                    @if($isFirstOption)
                                                        <td class="text-muted ps-3" style="font-weight: 500;">Panjang</td>
                                                        <td colspan="2" class="text-start fw-bold">{{ $requestData['wall_length'] }} M</td>
                                                    @endif

                                                    <td class="text-end fw-bold">{{ number_format($res['cement_sak'], 2, ',', '.') }}</td>
                                                    <td class="text-center">Sak</td>
                                                    <td class="fw-bold">Semen</td>
                                                    <td class="text-muted">{{ $item['cement']->type ?? '-' }}</td>
                                                    <td class="fw-bold">{{ $item['cement']->brand }}</td>
                                                    <td>{{ $item['cement']->color ?? '-' }}</td>
                                                    <td colspan="4" class="text-center fw-bold">{{ $item['cement']->package_weight_net + 0 }} Kg</td>
                                                    <td>{{ $item['cement']->store ?? '-' }}</td>
                                                    <td class="small text-muted">{{ $item['cement']->address ?? '-' }}</td>
                                                    <td class="text-nowrap fw-bold">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($item['cement']->package_price, 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted text-nowrap ps-1">/ {{ $item['cement']->package_unit }}</td>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($res['total_cement_price'], 0, ',', '.') }}</span>
                                                        </div>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($res['total_cement_price'], 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted text-nowrap ps-1">/ {{ $item['cement']->package_unit }}</td>
                                                </tr>

                                                {{-- ROW 3: PASIR --}}
                                                <tr>
                                                    @if($isFirstOption)
                                                        <td class="text-muted ps-3" style="font-weight: 500;">Tinggi</td>
                                                        <td colspan="2" class="text-start fw-bold">{{ $requestData['wall_height'] }} M</td>
                                                    @endif

                                                    <td class="text-end fw-bold">{{ number_format($res['sand_m3'], 3, ',', '.') }}</td>
                                                    <td class="text-center">M3</td>
                                                    <td class="fw-bold">Pasir</td>
                                                    <td class="text-muted">{{ $item['sand']->type ?? '-' }}</td>
                                                    <td class="fw-bold">{{ $item['sand']->brand }}</td>
                                                    <td>{{ $item['sand']->sand_name ?? '-' }}</td>
                                                    <td colspan="4" class="text-center fw-bold">{{ $item['sand']->package_volume ? ($item['sand']->package_volume + 0) . ' M3' : '-' }}</td>
                                                    <td>{{ $item['sand']->store ?? '-' }}</td>
                                                    <td class="small text-muted">{{ $item['sand']->address ?? '-' }}</td>
                                                    <td class="text-nowrap fw-bold">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($item['sand']->package_price, 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted text-nowrap ps-1">/ {{ $item['sand']->package_unit }}</td>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($res['total_sand_price'], 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <div class="d-flex justify-content-between w-100">
                                                            <span>Rp</span>
                                                            <span>{{ number_format($res['total_sand_price'], 0, ',', '.') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted text-nowrap ps-1">/ {{ $item['sand']->package_unit }}</td>
                                                </tr>

                                                {{-- ROW 4: AIR --}}
                                                <tr>
                                                    @if($isFirstOption)
                                                        <td class="text-muted ps-3" style="font-weight: 500;">Luas</td>
                                                        <td colspan="2" class="text-start fw-bold" style="color: #891313;">{{ number_format($area, 2) }} M2</td>
                                                    @endif

                                                    <td class="text-end fw-bold">{{ number_format($res['water_liters'], 2, ',', '.') }}</td>
                                                    <td class="text-center">L</td>
                                                    <td class="fw-bold">Air</td>
                                                    <td class="text-muted">Bersih</td>
                                                    <td>PDAM</td>
                                                    <td colspan="5"></td>
                                                    <td>Customer</td>
                                                    <td>-</td>
                                                    <td class="text-center text-muted">-</td>
                                                    <td></td>
                                                    <td class="text-center text-muted">-</td>
                                                    <td class="text-center text-muted">-</td>
                                                    <td></td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-4 text-center container">
                            <p class="text-muted" style="font-size: 13px;">
                                <i class="bi bi-info-circle me-1"></i> Gunakan tombol <strong>Pilih</strong> pada kolom Opsi untuk menyimpan perhitungan ini ke proyek Anda.
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    /* Hover effect untuk button cancel */
    .btn-cancel:hover {
        background: linear-gradient(135deg, #891313 0%, #a61515 100%) !important;
        color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(137, 19, 19, 0.3);
    }

    /* Tab navigation hover */
    .nav-link:not(.active):hover {
        background: #f8fafc !important;
        color: #334155 !important;
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush