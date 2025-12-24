@extends('layouts.app')

@section('title', 'Semua Material')

@section('content')
<!-- Inline script to restore tab ASAP before page render -->
<script>
(function() {
    const savedTab = localStorage.getItem('materialActiveTab');
    if (savedTab) {
        // Set a flag that will be checked by main script
        window.__materialSavedTab = savedTab;
    }
})();
</script>

<div class="card">
    @php
        $availableTypes = collect($materials)->pluck('type')->toArray();
        // Check if there's a saved tab from localStorage (set by inline script)
        $activeTab = request('tab');
        if (!$activeTab && !empty($availableTypes)) {
            // Will be overridden by JavaScript if localStorage has value
            $activeTab = $materials[0]['type'] ?? null;
        }
        if (!in_array($activeTab, $availableTypes)) {
            $activeTab = $materials[0]['type'] ?? null;
        }
    @endphp
    <div class="material-tab-wrapper">
        <style>
            /* Force header visibility */
            .table-container thead th {
                background-color: #891313 !important;
                color: #ffffff !important;
                vertical-align: top !important;
                text-align: center !important;
            }
            
            /* Table Body Alignment: Top Center */
            .table-container table td {
                vertical-align: top !important;
                background-color: #ffffff !important;
            }

            /* Ensure body scroll is not locked */
            body {
                overflow: auto !important;
            }
        </style>
        <div class="material-tab-header">
            <div class="material-tabs">
                @foreach($materials as $material)
                    <button type="button"
                            class="material-tab-btn {{ $material['type'] === $activeTab ? 'active' : '' }}"
                            data-tab="{{ $material['type'] }}"
                            aria-selected="{{ $material['type'] === $activeTab ? 'true' : 'false' }}">
                        <span>{{ $material['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="material-settings-dropdown">
                <button type="button" id="materialSettingsToggle" class="material-settings-btn">
                    <i class="bi bi-sliders"></i>
                    <span>Filter</span>
                    <i class="bi bi-chevron-down" style="font-size: 12px; transition: transform 0.2s;"></i>
                </button>
                <div class="material-settings-menu" id="materialSettingsMenu">
                    <div style="padding: 12px 16px; border-bottom: 1.5px solid #e2e8f0; background: #f8fafc;">
                        <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #0f172a;">Pilih Material yang Ditampilkan</h4>
                    </div>
                    <div class="material-settings-grid">
                        @foreach($allSettings as $setting)
                            <label class="material-setting-item" for="material-checkbox-{{ $setting->material_type }}">
                                <input type="checkbox"
                                       id="material-checkbox-{{ $setting->material_type }}"
                                       class="material-toggle-checkbox"
                                       data-material="{{ $setting->material_type }}"
                                       autocomplete="off">
                                <span class="material-setting-checkbox"></span>
                                <span class="material-setting-label">{{ \App\Models\MaterialSetting::getMaterialLabel($setting->material_type) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div style="padding: 12px 16px; border-top: 1.5px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: center;">
                        <button type="button" id="resetMaterialFilter" class="btn btn-sm btn-secondary" style="font-size: 12px;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state when no materials selected -->
        <div id="emptyMaterialState" style="display: block; padding: 60px 40px; text-align: center; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; margin-top: 20px;">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">📋</div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 18px; font-weight: 700;">Tidak Ada Material yang Ditampilkan</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Pilih material yang ingin ditampilkan dari dropdown <strong>"Filter"</strong> di atas.</p>
        </div>

        @if(count($materials) > 0)
            @foreach($materials as $material)
                <div class="material-section material-tab-panel {{ $material['type'] === $activeTab ? 'active' : 'hidden' }}" data-tab="{{ $material['type'] }}" id="section-{{ $material['type'] }}" style="margin-bottom: 24px;">
                    <div class="material-tab-card">
                    

                    @if($material['data']->count() > 0)
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 12px; flex-wrap: wrap;">
                        <form action="{{ route('materials.index') }}" method="GET" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
                            <div style="flex: 1; position: relative;">
                                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                                <input type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Cari semua material..."
                                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('materials.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Reset
                                </a>
                            @endif
                        </form>

                        {{-- Tombol Lama (Commented Out) --}}
                        {{-- <div style="display: flex; gap: 12px; flex-shrink: 0;">
                            <button type="button" id="openMaterialChoiceModal" class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> Tambah Data
                            </button>
                        </div> --}}

                        {{-- Tombol Baru (Spesifik per Material) --}}
                        <div style="display: flex; gap: 12px; flex-shrink: 0;">
                            <a href="{{ route($material['type'] . 's.create') }}" class="btn btn-success open-modal">
                                <i class="bi bi-plus-lg"></i> Tambah {{ $material['label'] }}
                            </a>
                        </div>
                    </div>
                <div class="table-container">
                    <table>
                        <thead style="background-color: #891313; color: white;">
                            @php                                   
                              if (!function_exists('getMaterialSortUrl')) {
                                    function getMaterialSortUrl($column, $currentSortBy, $currentDirection) {
                                        $params = array_merge(request()->query(), []);
                                        unset($params['sort_by'], $params['sort_direction']);
                                        if ($currentSortBy === $column) {
                                            if ($currentDirection === 'asc') {
                                                $params['sort_by'] = $column;
                                                $params['sort_direction'] = 'desc';
                                            }
                                        } else {
                                            $params['sort_by'] = $column;
                                            $params['sort_direction'] = 'asc';
                                        }
                                        return route('materials.index', $params);
                                    }
                                }
                                $brickSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'form' => 'Bentuk',
                                    'dimension_length' => 'Dimensi (cm)',
                                    'package_volume' => 'Volume',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'price_per_piece' => 'Harga Beli',
                                    'comparison_price_per_m3' => 'Harga Komparasi (/ M3)',
                                ];
                                $sandSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'package_unit' => 'Kemasan',
                                    'dimension_length' => 'Dimensi Kemasan (M)',
                                    'package_volume' => 'Volume',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'package_price' => 'Harga Beli',
                                    'comparison_price_per_m3' => 'Harga Komparasi (/ M3)',
                            ];
                            @endphp
                            @if($material['type'] == 'brick')
                                <tr class="dim-group-row">
                                    <th rowspan="2">No</th>
                                    <th rowspan="2">Material</th>
                                    <th class="sortable" rowspan="2">
                                        <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                           style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                            <span>{{ $brickSortable['type'] }}</span>
                                            @if(request('sort_by') == 'type')
                                                <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                            @else
                                                <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                            @endif
                                        </a>
                                    </th>
                                    @foreach(['brand','form'] as $col)
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                <span>{{ $brickSortable[$col] }}</span>
                                                @if(request('sort_by') == $col)
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @endforeach
                                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                                <span>Dimensi (cm)</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['package_volume','store','address','price_per_piece','comparison_price_per_m3'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $brickSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr class="dim-sub-row">
                                        @foreach(['P', 'L', 'T'] as $label)
                        <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                @elseif($material['type'] == 'sand')
                                    <tr class="dim-group-row">
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Material</th>
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                <span>{{ $sandSortable['type'] }}</span>
                                                @if(request('sort_by') == 'type')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['brand','package_unit'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $sandSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                                <span>Dimensi Kemasan (M)</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['package_volume','store','address','package_price','comparison_price_per_m3'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $sandSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr class="dim-sub-row">
                                        @foreach(['P', 'L', 'T'] as $label)
                                            <th style="text-align: center; font-size: 12px; padding: 1px 2px; width: 40px;">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                @elseif($material['type'] == 'cat')
                                    <tr>
                                        <th>No</th>
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Merek</th>
                                        <th>Sub Merek</th>
                                        <th style="text-align: right;">Kode</th>
                                        <th style="text-align: left;">Warna</th>
                                        <th>Kemasan</th>
                                        <th>Volume</th>
                                        <th style="text-align: left;">Berat Bersih</th>
                                        <th>Toko</th>
                                        <th style="text-align: left;">Alamat</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Komparasi (/ Kg)</th>
                                        <th>Aksi</th>
                                    </tr>
                                @elseif($material['type'] == 'cement')
                                    <tr class="dim-group-row">
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Material</th>
                                        <th rowspan="2">Jenis</th>
                                        <th rowspan="2">Merek</th>
                                        <th rowspan="2">Sub Merek</th>
                                        <th rowspan="2" style="text-align: right">Kode</th>
                                        <th rowspan="2" style="text-align: left">Warna</th>
                                        <th rowspan="2">Kemasan</th>
                                        <th colspan="3" style="text-align: center;">Dimensi (cm)</th>
                                        <th rowspan="2">Berat Bersih</th>
                                        <th rowspan="2">Toko</th>
                                        <th rowspan="2">Alamat</th>
                                        <th rowspan="2">Harga Beli</th>
                                        <th rowspan="2">Harga Komparasi (/ Kg)</th>
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr class="dim-sub-row">
                                        <th style="text-align: center; font-size: 12px; width: 40px; padding: 2px;">P</th>
                                        <th style="text-align: center; font-size: 12px; width: 40px; padding: 2px;">L</th>
                                        <th style="text-align: center; font-size: 12px; width: 40px; padding: 2px;">T</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody>
                                @foreach($material['data'] as $index => $item)
                                <tr>
                                    <td style="text-align: center; font-weight: 500; color: #64748b;">
                                        {{ $material['data']->firstItem() + $index }}
                                    </td>
                                    @if($material['type'] == 'brick')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Bata</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->form ?? '-' }}</td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                {{ rtrim(rtrim(number_format($item->dimension_height, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="volume-cell" style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }} M3
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->price_per_piece)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->price_per_piece, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cat')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Cat</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 12px; text-align:right;">{{ $item->color_code ?? '-' }}</td>
                                        <td style="color: #475569; text-align:left;">{{ $item->color_name ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}<br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->volume)
                                                {{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }} {{ $item->volume_unit }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 13px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->purchase_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->purchase_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cement')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Semen</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 12px; text-align:right;">{{ $item->code ?? '-' }}</td>
                                        <td style="color: #475569; text-align:left;">{{ $item->color ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <!-- Dimensi Data -->
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length * 100, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width * 100, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                {{ rtrim(rtrim(number_format($item->dimension_height * 100, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; color: #475569; font-size: 13px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'sand')
                                        <td><strong style="color: #0f172a; font-weight: 600;">Pasir</strong></td>
                                        <td style="color: #475569;">{{ $item->type ?? '-' }}</td>
                                        <td style="color: #475569;">{{ $item->brand ?? '-' }}</td>
                                        <td style="color: #475569; font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}<br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                {{ rtrim(rtrim(number_format($item->dimension_height, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="volume-cell" style="text-align: right; color: #475569; font-size: 12px;">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }} M3
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span style="color: #64748b; font-weight: 500;">Rp</span><span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route($material['type'] . 's.show', $item->id) }}" class="btn btn-primary btn-sm open-modal" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route($material['type'] . 's.edit', $item->id) }}" class="btn btn-warning btn-sm open-modal" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="position: relative;">
                        
                        <!-- Left Area: Stats Info (Absolute Positioned) -->
                        <div style="position: absolute; left: 0; top: 15px; display: flex; flex-direction: column; gap: 6px; text-align: left;">
                            <div style="font-size: 13px; color: #64748b; background: #f8fafc; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <div style="margin-bottom: 2px;">
                                    Total {{ $material['label'] }}: <strong style="color: #0f172a;">{{ number_format($material['db_count'], 0, ',', '.') }}</strong>
                                </div>
                                <div style="border-top: 1px dashed #cbd5e1; margin: 4px 0; padding-top: 4px;">
                                    Total Semua Material: <strong style="color: #0f172a;">{{ number_format($grandTotal, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Right Area: Pagination & Kanggo Logo -->
                        <div style="display: flex; flex-direction: column; align-items: flex-end; width: 100%; padding-right: 160px;">
                            


                            <!-- Kanggo A-Z Pagination (Logo & Letters) -->
                            @if(!request('search'))
                            <div class="kanggo-container" style="justify-content: flex-end;">
                                <div class="kanggo-logo">
                                    <img src="/Pagination/kangg.png" alt="Kanggo" style="height: 70px; width: auto; padding-bottom: 6px;">
                                </div>
                                <div class="kanggo-letters" style="justify-content: flex-end;">
                                    @php
                                        // Get active letters and find current position
                                        $activeLetters = $material['active_letters'];
                                        $currentLetter = $material['current_letter'];
                                        $currentPosition = array_search($currentLetter, $activeLetters);
                                        if ($currentPosition === false) $currentPosition = -1;
                                        
                                        // Pagination Logic variables
                                        $paginator = $material['data'];
                                        $pageName = $material['type'] . '_page';
                                        $currentPage = $paginator->currentPage();
                                        $lastPage = $paginator->lastPage();
                                        
                                        // Helper to build page URL
                                        $getPageUrl = function($page) use ($pageName) {
                                            $params = request()->query();
                                            $params[$pageName] = $page;
                                            return route('materials.index', $params);
                                        };
                                    @endphp

                                    @foreach(range('A', 'Z') as $index => $char)
                                        @php
                                            $isActive = in_array($char, $activeLetters);
                                            $isCurrent = $char === $currentLetter;
                                            $imgIndex = $index + 1; // 0-based index to 1-based (1.png for A, etc)

                                            // Calculate gradient for buttons before current
                                            $gradientClass = '';
                                            $gradientStyle = '';

                                            if ($isActive && !$isCurrent) {
                                                $letterPosition = array_search($char, $activeLetters);

                                                if ($letterPosition !== false && $currentPosition !== false && $letterPosition < $currentPosition) {
                                                    // This button is before current - apply gradient
                                                    $gradientClass = 'gradient-active';
                                                    $totalSteps = $currentPosition; // Total buttons before current
                                                    $positionIndex = $letterPosition; // 0-based position

                                                    // Calculate intensity
                                                    $intensity = $totalSteps > 0 ? ($positionIndex / $totalSteps) : 0;

                                                    // Approach baru: Sepia full + saturate tinggi + brightness untuk gradasi
                                                    $sepia = 1.0;
                                                    $saturate = 3.5 + ($intensity * 1.0); 
                                                    $hueRotate = -35;
                                                    $brightness = 2.5 - ($intensity * 1.2); 
                                                    $contrast = 1.3;

                                                    $gradientStyle = "filter: grayscale(0%) sepia({$sepia}) saturate({$saturate}) hue-rotate({$hueRotate}deg) brightness({$brightness}) contrast({$contrast});";
                                                }
                                            }
                                        @endphp

                                        @if($isActive)
                                            @if($isCurrent)
                                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 2px;">
                                                    <!-- Up Arrow (Prev Page) -->
                                                    <a href="{{ $currentPage > 1 ? $getPageUrl($currentPage - 1) : '#' }}" 
                                                       class="page-arrow-btn {{ $currentPage <= 1 ? 'disabled' : '' }}"
                                                       title="{{ $currentPage > 1 ? 'Halaman Sebelumnya' : '' }}">
                                                        <i class="bi bi-caret-up-fill"></i>
                                                    </a>

                                                    <!-- Active Letter Image -->
                                                    <a href="{{ route('materials.index', array_merge(request()->query(), ['tab' => $material['type'], $material['type'] . '_letter' => $char])) }}"
                                                       class="kanggo-img-link current"
                                                       style="margin: 2px 0;">
                                                        <img src="/Pagination/{{ $imgIndex }}.png" alt="{{ $char }}" class="kanggo-img">
                                                    </a>

                                                    <!-- Down Arrow (Next Page) -->
                                                    <a href="{{ $currentPage < $lastPage ? $getPageUrl($currentPage + 1) : '#' }}" 
                                                       class="page-arrow-btn {{ $currentPage >= $lastPage ? 'disabled' : '' }}"
                                                       title="{{ $currentPage < $lastPage ? 'Halaman Selanjutnya' : '' }}">
                                                        <i class="bi bi-caret-down-fill"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <a href="{{ route('materials.index', array_merge(request()->query(), ['tab' => $material['type'], $material['type'] . '_letter' => $char])) }}"
                                                   class="kanggo-img-link {{ $gradientClass }}"
                                                   style="{{ $gradientStyle }}">
                                                    <img src="/Pagination/{{ $imgIndex }}.png" alt="{{ $char }}" class="kanggo-img">
                                                </a>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Right Area: Button (Absolute Positioned) -->
                        <div style="position: absolute; right: 0; top: 25px;">
                            <a href="{{ route($material['type'] . 's.index', request()->query()) }}" class="btn btn-primary" style="background: #891313; border-color: #891313; box-shadow: 0 4px 6px rgba(137, 19, 19, 0.2); padding: 10px 24px; border-radius: 12px;">
                                Lihat Semua <i class="bi bi-arrow-right" style="margin-left: 6px;"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div style="padding: 40px; text-align: center; color: #94a3b8;">
                        @if(request('search'))
                            Tidak ada data {{ strtolower($material['label']) }} yang cocok dengan pencarian "{{ request('search') }}"
                        @else
                            Tidak ada data {{ strtolower($material['label']) }} untuk huruf <strong>{{ $material['current_letter'] }}</strong>
                        @endif
                    </div>
                @endif
                    </div>
                </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada material yang ditampilkan</p>
            <p style="font-size: 14px; color: #94a3b8;">Atur material yang ingin ditampilkan di pengaturan</p>
            <a href="{{ route('materials.settings') }}" class="btn btn-primary" style="margin-top: 16px;">
                <i class="bi bi-gear"></i> Pengaturan Filter
            </a>
        </div>
    @endif
    </div>
</div>

<!-- Material Choice Modal -->
<div id="materialChoiceModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content" style="width: 600px;">
        <div class="floating-modal-header">
            <h2>Pilih Jenis Material</h2>
            <button class="floating-modal-close" id="closeMaterialChoiceModal">&times;</button>
        </div>
        <div class="floating-modal-body">
            <p style="color: #64748b; margin-bottom: 24px;">Pilih jenis material yang ingin Anda tambahkan:</p>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <a href="{{ route('bricks.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🧱</div>
                    <div class="material-choice-label">Bata</div>
                    <div class="material-choice-desc">Tambah data bata</div>
                </a>
                <a href="{{ route('cats.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🎨</div>
                    <div class="material-choice-label">Cat</div>
                    <div class="material-choice-desc">Tambah data cat</div>
                </a>
                <a href="{{ route('cements.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🏗️</div>
                    <div class="material-choice-label">Semen</div>
                    <div class="material-choice-desc">Tambah data semen</div>
                </a>
                <a href="{{ route('sands.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">⛱️</div>
                    <div class="material-choice-label">Pasir</div>
                    <div class="material-choice-desc">Tambah data pasir</div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Floating Modal -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Detail Material</h2>
            <button class="floating-modal-close" id="globalCloseModal">&times;</button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <div style="text-align: center; padding: 60px; color: #94a3b8;">
                <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                <div style="font-weight: 500;">Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Page Arrow Buttons */
.page-arrow-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 14px;
    color: #891313;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s;
    cursor: pointer;
    line-height: 1;
}

.page-arrow-btn:hover {
    color: #e10009;
    transform: scale(1.2);
}

.page-arrow-btn.disabled {
    color: #cbd5e1;
    pointer-events: none;
    cursor: default;
}

/* Modal Styles - Modern & Minimalist */
.floating-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

.floating-modal.active {
    display: block;
}

.floating-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.floating-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
    max-width: 95%;
    max-height: 95vh;
    width: 1200px;
    overflow: hidden;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.floating-modal-header {
    padding: 24px 32px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    position: relative; /* Added for ::before positioning */
    overflow: hidden;   /* Added to contain the extended ::before */
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #ffffff; /* Changed text color to white */
    padding: 8px 0; /* Added padding */
    position: relative; /* Added for z-index and ::before relative positioning */
    z-index: 1; /* Ensures text is above the ::before background */
    flex: 1; /* Allows h2 to take available space */
}

.floating-modal-header h2::before {
    content: '';
    position: absolute;
    left: -32px; /* Compensates for parent padding-left */
    right: -200px; /* Extends far enough to cover the button and right edge */
    top: 0;
    bottom: 0;
    background: #891313;
    z-index: -1; /* Places the background behind the h2 text */
}

.floating-modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #ffffff; /* Changed to white */
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative; /* Added position */
    z-index: 10; /* Added z-index */
}

.floating-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
}

.floating-modal-body {
    padding: 32px;
    overflow-y: auto;
    max-height: calc(95vh - 90px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translate(-50%, -48%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

/* Scrollbar styling */
.floating-modal-body::-webkit-scrollbar {
    width: 10px;
}

.floating-modal-body::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 5px;
}

.floating-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}

.floating-modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.table-container table {
    border-collapse: collapse;
    border-spacing: 0;
}

.table-container thead th {
    white-space: nowrap;
}

.table-container thead .dim-group-row th {
    border-bottom: 0 !important;
    padding-bottom: 6px !important;
    line-height: 1.2;
}

.table-container thead .dim-sub-row th {
    border-top: 0 !important;
    border-bottom: 0 !important;
    border-left: 0 !important;
    border-right: 0 !important;
    padding: 8px 2px 10px 2px !important;
    width: 40px;
    position: relative;
    line-height: 1.1;
    vertical-align: middle;
}

.table-container tbody td.dim-cell {
    padding: 14px 2px !important;
    width: 40px;
    border-left: 0 !important;
    border-right: 0 !important;
    position: relative;
}

/* Header 'x' separator - Keep centered vertically as header height is fixed/small */
.table-container thead .dim-sub-row th + th::before {
    content: 'x';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.5); /* Semi-transparent white for red background */
    font-size: 11px;
    pointer-events: none;
}

/* Body 'x' separator - Fixed top position to match top-aligned text */
.table-container tbody td.dim-cell + td.dim-cell::before {
    content: 'x';
    position: absolute;
    left: -6px;
    top: 24px; /* Align with top-aligned text */
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 11px;
    pointer-events: none;
}

.table-container tbody td.volume-cell {
    padding: 14px 8px !important;
    width: 90px;
}

.table-container thead th,
.table-container thead th a,
.table-container thead th i {
    color: #ffffff !important;
}

.table-container thead th i {
    opacity: 1 !important;
}

.db-dropdown-body {
    display: none;
}

.db-dropdown-body.open {
    display: block;
}

.db-dropdown-toggle i {
    transition: transform 0.2s ease;
}

.db-dropdown-toggle[aria-expanded="false"] i {
    transform: rotate(-90deg);
}

.material-tabs {
    display: flex;
    flex-wrap: wrap;
    margin: 0;
    gap: 1px;
    padding: 0px;
    position: relative;
    z-index: 1;
}

.material-tab-header {
    display: flex;
    align-items: flex-end;
    gap: 5px;
    margin-bottom: -1px;
    position: relative;
}

.material-tab-header::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    border-bottom: 1px solid #e2e8f0;
    z-index: 0;
}

.material-tab-wrapper {
    --tab-surface: #ffffff;
    --tab-foot-radius: 16px;
}

.material-tab-btn {
    --tab-border-color: #e2e8f0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: 1px solid #e2e8f0;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    text-decoration: none;
    color: #64748b;
    font-weight: 600;
    background: #f8fafc;
    transition: all 0.2s ease;
    cursor: pointer;
    margin-bottom: -1px;
    position: relative;
    z-index: 1;
}

.material-tab-btn::before,
.material-tab-btn::after {
    content: none;
}

.material-tab-btn:hover {
    color: #891313;
    background: #fff5f5;
}

.material-tab-btn.active {
    --tab-border-color: #891313;
    background: #FCF8E8;
    color: #891313;
    border-color: #891313;
    border-width: 2px;
    border-bottom: none;
    position: relative;
    z-index: 5;
    font-weight: 700;
    padding-bottom: 14px;
}

.material-tab-btn.active::before,
.material-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: var(--tab-foot-radius);
    height: var(--tab-foot-radius);
    background: transparent;
    pointer-events: none;
}

.material-tab-btn.active::before {
    right: 100%;
    background:
        radial-gradient(
            circle at 0 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            #FCF8E8 var(--tab-foot-radius)
        );
    background-position: bottom right;
}

.material-tab-btn.active::after {
    left: 100%;
    background:
        radial-gradient(
            circle at 100% 0,
            transparent calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
            var(--tab-border-color) var(--tab-foot-radius),
            #FCF8E8 var(--tab-foot-radius)
        );
    background-position: bottom left;
}

.material-tab-btn.active.first-visible::before {
    content: none;
}

.material-tab-btn.active.last-visible::after {
    content: none;
}

.material-tab-btn .material-nav-count {
    display: inline-block;
    min-width: 26px;
    padding: 4px 8px;
    background: rgba(255,255,255,0.18);
    border-radius: 999px;
    font-size: 12px;
    color: inherit;
    text-align: center;
}

.material-tab-panel {
    padding-top: 0;
    margin-top: -1px;
}

.material-tab-panel.hidden {
    display: none !important;
}

.material-tab-card {
    border: 2px solid #e2e8f0;
    border-radius: 0 0 12px 12px;
    background: #FCF8E8;
    padding: 20px;
    margin-top: 0;
    position: relative;
    z-index: 3;
}

.material-tab-panel.active .material-tab-card {
    border-color: #891313;
    box-shadow:
        0 4px 6px -1px rgba(137, 19, 19, 0.08),
        0 2px 4px -1px rgba(137, 19, 19, 0.06);
}

.material-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid #e2e8f0;
}
/* Input focus styles */
input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
}

/* Material Choice Cards */
.material-choice-card {
    display: block;
    padding: 24px;
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.material-choice-card:hover {
    border-color: #891313;
    background: #fff5f5;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(137, 19, 19, 0.15);
}

.material-choice-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.material-choice-label {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
}

.material-choice-desc {
    font-size: 13px;
    color: #64748b;
}

/* Material Settings Dropdown */
.material-settings-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px 12px 0 0;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    z-index: 1;
    width: 100%;
}

.material-settings-btn i:last-child {
    margin-left: auto;
}

.material-settings-dropdown {
    flex: 1;
    min-width: 220px;
    position: static;
}

.material-settings-btn:hover {
    background: #fff5f5;
    color: #891313;
    border-color: #891313;
}

.material-settings-btn.active {
    background: #ffffff;
    color: #891313;
    border-color: #891313;
    font-weight: 700;
}

.material-settings-btn.active i:last-child {
    transform: rotate(180deg);
}

.material-settings-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 2px solid #891313;
    box-shadow: 0 8px 24px rgba(137, 19, 19, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1000;
}

.material-settings-grid {
    padding: 12px 16px;
    max-height: 280px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px 12px;
}

.material-setting-item {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
}

.material-settings-grid::-webkit-scrollbar {
    width: 8px;
}

.material-settings-grid::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.material-settings-grid::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.material-settings-grid::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@media (max-width: 992px) {
    .material-settings-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .material-settings-grid {
        grid-template-columns: 1fr;
    }
}

.material-settings-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.material-setting-item {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background 0.15s ease;
    user-select: none;
    position: relative;
}

.material-setting-item:hover {
    background: #f8fafc;
}

.material-setting-item:active {
    background: #f1f5f9;
}

.material-toggle-checkbox {
    display: none;
}

.material-setting-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
    background: #ffffff;
}

.material-toggle-checkbox:checked + .material-setting-checkbox {
    background: #891313;
    border-color: #891313;
}

.material-toggle-checkbox:checked + .material-setting-checkbox::after {
    content: '\F26B';
    font-family: 'bootstrap-icons';
    color: #ffffff;
    font-size: 12px;
    font-weight: bold;
}

.material-setting-label {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
    color: #0f172a;
}

/* Kanggo Pagination Styles */
.kanggo-container {
    padding-top: 10px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    user-select: none;
}

.kanggo-logo {
    display: flex;
    align-items: center;
    margin-right: 2px;
}

.kanggo-letters {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 2px;
}

.kanggo-img {
    height: 25px; /* Ukuran lebih kecil untuk logo page */
    width: auto;
    display: block;
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Link Wrapper */
.kanggo-img-link {
    display: inline-block;
    cursor: pointer;
    padding: 2px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

/* Default state: Grey (untuk button yang tidak di gradasi) */
.kanggo-img-link .kanggo-img {
    filter: grayscale(100%);
    opacity: 0.6;
    transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Gradient Active: Warna selalu aktif untuk button sebelum current */
.kanggo-img-link.gradient-active .kanggo-img {
    opacity: 1 !important;
    /* Filter akan di-override oleh inline style untuk gradasi */
}

/* Hover state: Color & Animation */
.kanggo-img-link:hover .kanggo-img {
    transform: translateY(-4px) scale(1.1);
}

/* Hover untuk gradient active: tidak ubah warna, hanya animasi */
.kanggo-img-link.gradient-active:hover .kanggo-img {
    transform: translateY(-4px) scale(1.1);
    /* Warna tetap sesuai gradasi */
}

/* Hover untuk default (grey): baru muncul warna */
.kanggo-img-link:not(.gradient-active):not(.current):hover .kanggo-img {
    filter: grayscale(0%);
    opacity: 1;
}

/* Current state: Color, Big, Shadow */
.kanggo-img-link.current .kanggo-img {
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15)) grayscale(0%);
    opacity: 1;
    z-index: 10;
    position: relative;
}

/* Disabled State */
.kanggo-img-wrapper {
    display: inline-block;
    padding: 2px;
    pointer-events: none;
}

.kanggo-img-wrapper.disabled .kanggo-img {
    opacity: 0.3;
    filter: grayscale(100%);
    transform: scale(0.9);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Safety check: Unlock scroll on load
    document.body.style.overflow = '';

    // Load saved filter from localStorage
    const STORAGE_KEY = 'material_filter_preferences';
    let savedFilter = null;
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        savedFilter = stored ? JSON.parse(stored) : { selected: [], order: [] };
    } catch (e) {
        savedFilter = { selected: [], order: [] };
    }
    let materialOrder = savedFilter.order || [];

    // Material Settings Dropdown
    const settingsToggle = document.getElementById('materialSettingsToggle');
    const settingsMenu = document.getElementById('materialSettingsMenu');

    if (settingsToggle && settingsMenu) {
        settingsToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = settingsMenu.classList.contains('active');

            if (isActive) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
            } else {
                settingsMenu.classList.add('active');
                settingsToggle.classList.add('active');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!settingsToggle.contains(e.target) && !settingsMenu.contains(e.target)) {
                settingsMenu.classList.remove('active');
                settingsToggle.classList.remove('active');
            }
        });

        // Prevent dropdown from closing when clicking inside menu
        settingsMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Material Toggle Checkboxes - Save to localStorage
    const toggleCheckboxes = document.querySelectorAll('.material-toggle-checkbox');
    const allTabButtons = document.querySelectorAll('.material-tab-btn');
    const allTabPanels = document.querySelectorAll('.material-tab-panel');

    // Tab switching function (declared early to avoid reference errors)
    const tabButtons = Array.from(allTabButtons);
    const tabPanels = Array.from(allTabPanels);
    const setActiveTab = (tab) => {
        tabButtons.forEach(btn => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        tabPanels.forEach(panel => {
            const isActive = panel.dataset.tab === tab;
            panel.classList.toggle('hidden', !isActive);
            panel.classList.toggle('active', isActive);
            if (isActive) {
                panel.removeAttribute('aria-hidden');
            } else {
                panel.setAttribute('aria-hidden', 'true');
            }
        });

        // Save active tab to localStorage
        localStorage.setItem('materialActiveTab', tab);
    };

    // Function to save filter preferences to localStorage
    function saveFilterToLocalStorage(selected, order) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                selected: selected,
                order: order
            }));
        } catch (e) {
            console.error('Failed to save filter to localStorage:', e);
        }
    }

    // Function to reorder tabs based on materialOrder
    function reorderTabs() {
        const tabContainer = document.querySelector('.material-tabs');
        if (!tabContainer) return;

        // Create a map of current tab buttons
        const tabButtons = {};

        // Always remove all position classes first
        allTabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            tabButtons[tabType] = btn;
            btn.classList.remove('first-visible', 'last-visible');
        });

        // Only reorder and add classes if materialOrder has items
        if (materialOrder.length > 0) {
            // Reorder based on materialOrder
            materialOrder.forEach((type, index) => {
                if (tabButtons[type]) {
                    tabContainer.appendChild(tabButtons[type]);

                    // Add position classes for concave legs styling
                    if (index === 0) {
                        tabButtons[type].classList.add('first-visible');
                    }
                    if (index === materialOrder.length - 1) {
                        tabButtons[type].classList.add('last-visible');
                    }
                }
            });
        }
    }

    // Function to update tab visibility based on checkboxes
    function updateTabVisibility(preferredTab = null) {
        console.log('[updateTabVisibility] Started');
        const checkedMaterials = [];
        const emptyState = document.getElementById('emptyMaterialState');
        const tabContainer = document.querySelector('.material-tabs');

        // First, collect all checked materials
        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            if (checkbox.checked) {
                checkedMaterials.push(materialType);
                // Add to order if not already there
                if (!materialOrder.includes(materialType)) {
                    materialOrder.push(materialType);
                }
            }
        });

        console.log('[updateTabVisibility] Checked materials:', checkedMaterials);
        console.log('[updateTabVisibility] Material order before filter:', [...materialOrder]);

        // Remove unchecked materials from order
        materialOrder = materialOrder.filter(item => checkedMaterials.includes(item));

        console.log('[updateTabVisibility] Material order after filter:', [...materialOrder]);

        // Show/hide empty state
        if (emptyState) {
            if (checkedMaterials.length === 0) {
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
            }
        }

        // Reorder tabs based on tick order
        reorderTabs();

        // Show/hide tabs and panels based on checked materials (in order)
        allTabButtons.forEach(btn => {
            const tabType = btn.getAttribute('data-tab');
            if (checkedMaterials.includes(tabType)) {
                btn.style.display = 'inline-flex';
            } else {
                btn.style.display = 'none';
            }
        });

        allTabPanels.forEach(panel => {
            const panelType = panel.getAttribute('data-tab');
            if (checkedMaterials.includes(panelType)) {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        });

        // Hide tab container when nothing is selected (so Filter can stretch full width)
        if (tabContainer) {
            tabContainer.style.display = checkedMaterials.length > 0 ? 'flex' : 'none';
        }

        // Auto-activate tab (prefer saved tab, fallback to first visible)
        if (materialOrder.length > 0) {
            let tabToActivate = materialOrder[0];

            // If there's a preferred tab and it exists in checked materials, use it
            if (preferredTab && checkedMaterials.includes(preferredTab)) {
                tabToActivate = preferredTab;
            }

            setActiveTab(tabToActivate);
        }

        // Save to localStorage
        saveFilterToLocalStorage(checkedMaterials, materialOrder);
    }

    // Listen to checkbox changes FIRST (before restore)
    toggleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            console.log('[Checkbox Change] Material:', checkbox.getAttribute('data-material'), 'Checked:', checkbox.checked);
            updateTabVisibility();
        });
    });

    // Restore checkboxes from localStorage
    console.log('[Restore] Saved filter:', savedFilter);
    console.log('[Restore] Initial materialOrder:', [...materialOrder]);

    if (savedFilter.selected && savedFilter.selected.length > 0) {
        console.log('[Restore] Restoring', savedFilter.selected.length, 'materials');
        toggleCheckboxes.forEach(checkbox => {
            const materialType = checkbox.getAttribute('data-material');
            checkbox.checked = savedFilter.selected.includes(materialType);
        });
    } else {
        console.log('[Restore] No saved filter, unchecking all');
        // Force uncheck all checkboxes if no saved filter
        toggleCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        materialOrder = [];
    }

    // Initialize page state: restore from localStorage or show empty state
    console.log('[Restore] Calling updateTabVisibility');
    const savedTab = window.__materialSavedTab || localStorage.getItem('materialActiveTab');
    updateTabVisibility(savedTab);

    // Reset Material Filter Button
    const resetFilterBtn = document.getElementById('resetMaterialFilter');
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function() {
            // Uncheck all checkboxes
            toggleCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            // Clear materialOrder
            materialOrder = [];

            // Clear localStorage
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                console.error('Failed to clear localStorage:', e);
            }

            // Update tab visibility (hide all)
            updateTabVisibility();
        });
    }
    
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('globalCloseModal');
    const backdrop = modal ? modal.querySelector('.floating-modal-backdrop') : null;

    function interceptFormSubmit() {
        if (!modalBody) return;
        const form = modalBody.querySelector('form');
        if (form) {
            // Add hidden input to tell controller to redirect to materials.index
            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = '_redirect_to_materials';
            redirectInput.value = '1';
            form.appendChild(redirectInput);

            form.addEventListener('submit', function(e) {
                // Show loading state before submit
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Menyimpan...';
                }
                // Let form submit normally, controller will redirect to materials.index
            });
        }
    }

    // Helper function to determine material type and action from URL
    function getMaterialInfo(url) {
        let materialType = '';
        let action = '';
        let materialLabel = 'Material';

        if (url.includes('/bricks/')) {
            materialType = 'brick';
            materialLabel = 'Bata';
        } else if (url.includes('/cats/')) {
            materialType = 'cat';
            materialLabel = 'Cat';
        } else if (url.includes('/cements/')) {
            materialType = 'cement';
            materialLabel = 'Semen';
        } else if (url.includes('/sands/')) {
            materialType = 'sand';
            materialLabel = 'Pasir';
        }

        if (url.includes('/create')) {
            action = 'create';
        } else if (url.includes('/edit')) {
            action = 'edit';
        } else if (url.includes('/show')) {
            action = 'show';
        }

        return { materialType, action, materialLabel };
    }

    // Helper function to load material-specific form script
    function loadMaterialFormScript(materialType, modalBodyEl) {
        const scriptProperty = `${materialType}FormScriptLoaded`;
        const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

        if (!window[scriptProperty]) {
            const script = document.createElement('script');
            script.src = `/js/${materialType}-form.js`;
            script.onload = () => {
                window[scriptProperty] = true;
                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        window[initFunctionName](modalBodyEl);
                    }
                    interceptFormSubmit();
                }, 100);
            };
            document.head.appendChild(script);
        } else {
            setTimeout(() => {
                if (typeof window[initFunctionName] === 'function') {
                    window[initFunctionName](modalBodyEl);
                }
                interceptFormSubmit();
            }, 100);
        }
    }

    if (modal && modalBody && modalTitle && closeBtn && backdrop) {
        document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            const { materialType, action, materialLabel } = getMaterialInfo(url);

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update title based on action
            if (action === 'create') {
                modalTitle.textContent = `Tambah ${materialLabel} Baru`;
                closeBtn.style.display = 'none'; // Hide close button
            } else if (action === 'edit') {
                modalTitle.textContent = `Edit ${materialLabel}`;
                closeBtn.style.display = 'none'; // Hide close button
            } else if (action === 'show') {
                modalTitle.textContent = `Detail ${materialLabel}`;
                closeBtn.style.display = 'flex'; // Show close button
            } else {
                modalTitle.textContent = materialLabel;
                closeBtn.style.display = 'flex';
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('form') || doc.querySelector('.card') || doc.body;
                modalBody.innerHTML = content ? content.outerHTML : html;

                // Load material-specific form script if needed
                if (materialType && (action === 'create' || action === 'edit')) {
                    loadMaterialFormScript(materialType, modalBody);
                } else {
                    interceptFormSubmit();
                }
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                console.error('Fetch error:', err);
            });
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    }

    // Material Choice Modal
    const materialChoiceModal = document.getElementById('materialChoiceModal');
    const openMaterialChoiceBtn = document.getElementById('openMaterialChoiceModal');
    const closeMaterialChoiceBtn = document.getElementById('closeMaterialChoiceModal');
    const materialChoiceBackdrop = materialChoiceModal ? materialChoiceModal.querySelector('.floating-modal-backdrop') : null;

    if (materialChoiceModal && openMaterialChoiceBtn && closeMaterialChoiceBtn && materialChoiceBackdrop) {
        // Open material choice modal
        openMaterialChoiceBtn.addEventListener('click', function() {
            materialChoiceModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        // Close material choice modal
        function closeMaterialChoiceModal() {
            materialChoiceModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        closeMaterialChoiceBtn.addEventListener('click', closeMaterialChoiceModal);
        materialChoiceBackdrop.addEventListener('click', closeMaterialChoiceModal);

        // Close material choice modal on ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && materialChoiceModal.classList.contains('active')) {
                closeMaterialChoiceModal();
            }
        });

        // When user clicks a material choice, close the choice modal first
        document.querySelectorAll('.material-choice-card').forEach(card => {
            card.addEventListener('click', function() {
                closeMaterialChoiceModal();
                // The open-modal class will handle opening the form modal
            });
        });
    }

    // Initialize tab click listeners
    if (tabButtons.length) {
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
        });
    }

    // Add click handlers to "Lihat Semua" buttons to save current tab
    document.querySelectorAll('a[href*="bricks.index"], a[href*="cats.index"], a[href*="cements.index"], a[href*="sands.index"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Save current active tab before navigation
            const activeTab = document.querySelector('.material-tab-btn.active');
            if (activeTab) {
                const currentTab = activeTab.dataset.tab;
                localStorage.setItem('materialActiveTab', currentTab);
            }
        });
    });

    // Add click handlers to pagination links to preserve current tab
    document.querySelectorAll('.kanggo-img-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Save current active tab before navigation
            const activeTab = document.querySelector('.material-tab-btn.active');
            if (activeTab) {
                const currentTab = activeTab.dataset.tab;
                localStorage.setItem('materialActiveTab', currentTab);
            }
        });
    });

});
</script>
@endsection
