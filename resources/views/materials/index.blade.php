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

                <div class="material-settings-dropdown">
                    <button type="button" id="materialSettingsToggle" class="material-settings-btn">
                        <i class="bi bi-sliders"></i>
                        <span>Filter</span>
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
        </div>

        <!-- Empty state when no materials selected -->
        <div id="emptyMaterialState" style="display: block; padding: 60px 40px; text-align: center; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; margin-top: 20px;">
            <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.3;">📋</div>
            <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 18px; font-weight: 700;">Tidak Ada Material yang Ditampilkan</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Pilih material yang ingin ditampilkan dari dropdown <strong>"Filter"</strong> di atas.</p>
        </div>

        @if(count($materials) > 0)
            @foreach($materials as $material)
                {{-- Removed 'material-section' class to prevent global CSS margin-top conflict which causes gap between tab and content --}}
                <div class="material-tab-panel {{ $material['type'] === $activeTab ? 'active' : 'hidden' }}" data-tab="{{ $material['type'] }}" id="section-{{ $material['type'] }}" style="margin-bottom: 24px;">
                    <div class="material-tab-card">
                    
                    {{-- Search Bar - Always Visible --}}
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 12px; flex-wrap: wrap;">
                        <form action="{{ route('materials.index') }}" method="GET" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
                            {{-- Preserve current tab when searching --}}
                            <input type="hidden" name="tab" value="{{ $material['type'] }}">
                            
                            <div style="flex: 1; position: relative;">
                                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 16px;"></i>
                                <input type="text"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Cari {{ strtolower($material['label']) }}..."
                                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.2s ease;">
                            </div>
                            <button type="submit" class="btn btn-primary-glossy">
                                <i class="bi bi-search"></i> Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('materials.index', ['tab' => $material['type']]) }}" class="btn btn-secondary">
                                    <i class="bi bi-x-lg"></i> Reset
                                </a>
                            @endif
                        </form>

                        <div style="display: flex; gap: 12px; flex-shrink: 0;">
                            <a href="{{ route($material['type'] . 's.create') }}"
                            class="btn btn-glossy open-modal">
                                <i class="bi bi-plus-lg"></i> Tambah {{ $material['label'] }}
                            </a>
                        </div>
                    </div>

                    @if($material['data']->count() > 0)
                <div class="table-container text-nowrap">
                    <table>
                        <thead>
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
                                    'comparison_price_per_m3' => 'Harga <br> Komparasi (/ M3)',
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
                                    'comparison_price_per_m3' => 'Harga <br> Komparasi (/ M3)',
                                ];
                                $catSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'color_code' => 'Kode',
                                    'color_name' => 'Warna',
                                    'package_unit' => 'Kemasan',
                                    'volume' => 'Volume',
                                    'package_weight_net' => 'Berat Bersih',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'purchase_price' => 'Harga Beli',
                                    'comparison_price_per_kg' => 'Harga <br> Komparasi (/ Kg)',
                                ];
                                $cementSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'code' => 'Kode',
                                    'color' => 'Warna',
                                    'package_unit' => 'Kemasan',
                                    'dimension_length' => 'Dimensi (cm)',
                                    'package_weight_net' => 'Berat Bersih',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'package_price' => 'Harga Beli',
                                    'comparison_price_per_kg' => 'Harga <br> Komparasi (/ Kg)',
                                ];
                                $ceramicSortable = [
                                    'type' => 'Jenis',
                                    'brand' => 'Merek',
                                    'sub_brand' => 'Sub Merek',
                                    'code' => 'Kode',
                                    'color' => 'Warna',
                                    'form' => 'Bentuk',
                                    'packaging' => 'Kemasan',
                                    'pieces_per_package' => 'Volume',
                                    'area_per_piece' => 'Luas (M² / Lbr)',
                                    'dimension_length' => 'Dimensi (cm)',
                                    'store' => 'Toko',
                                    'address' => 'Alamat',
                                    'price_per_package' => 'Harga / Kemasan',
                                    'comparison_price_per_m2' => 'Harga Komparasi <br> (/ M²)',
                                ];
                                @endphp
                                    @if($material['type'] == 'brick')
                                        <tr class="dim-group-row">
                                            <th rowspan="2">No</th>
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{!! $brickSortable[$col] !!}</span>
                                                        @if(request('sort_by') == $col)
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            @endforeach
                                            <th rowspan="2" class="action-cell">Aksi</th>
                                        </tr>
                                        <tr class="dim-sub-row">
                                            @foreach(['P', 'L', 'T'] as $label)
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">{{ $label }}</th>
                                            @endforeach
                                        </tr>
                                            @elseif($material['type'] == 'sand')
                                                                    <tr class="dim-group-row">
                                                                        <th rowspan="2">No</th>
                                                                        <th class="sortable" rowspan="2">
                                                                            <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
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
                                                                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                    <span>{!! $sandSortable[$col] !!}</span>
                                                                                    @if(request('sort_by') == $col)
                                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                    @else
                                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                    @endif
                                                                                    </a>
                                                                                </th>
                                                                            @endforeach
                                                                        <th rowspan="2" class="action-cell">Aksi</th>
                                                                    </tr>
                                                                    <tr class="dim-sub-row">
                                                                        @foreach(['P', 'L', 'T'] as $label)
                                                                            <th style="text-align: center; font-size: 12px; padding: 1px 2px; width: 40px;">{{ $label }}</th>
                                                                        @endforeach
                                                                    </tr>
                                                                        @elseif($material['type'] == 'cat')
                                                                    <tr>
                                                                        <th>No</th>
                                                                        @foreach(['type','brand','sub_brand'] as $col)
                                                                            <th class="sortable">
                                                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                    <span>{{ $catSortable[$col] }}</span>
                                                                                    @if(request('sort_by') == $col)
                                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                    @else
                                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                    @endif
                                                                                </a>
                                                                            </th>
                                                                        @endforeach
                                                                        <th class="sortable">
                                                                            <a href="{{ getMaterialSortUrl('color_code', request('sort_by'), request('sort_direction')) }}"
                                                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                <span>{{ $catSortable['color_code'] }}</span>
                                                                                @if(request('sort_by') == 'color_code')
                                                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                @else
                                                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                @endif
                                                                            </a>
                                                                        </th>
                                                                        <th class="sortable">
                                                                            <a href="{{ getMaterialSortUrl('color_name', request('sort_by'), request('sort_direction')) }}"
                                                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                <span>{{ $catSortable['color_name'] }}</span>
                                                                                @if(request('sort_by') == 'color_name')
                                                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                @else
                                                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                @endif
                                                                            </a>
                                                                        </th>
                                                                        @foreach(['package_unit','volume'] as $col)
                                                                            <th class="sortable">
                                                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                    <span>{{ $catSortable[$col] }}</span>
                                                                                    @if(request('sort_by') == $col)
                                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                    @else
                                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                    @endif
                                                                                </a>
                                                                            </th>
                                                                        @endforeach
                                                                        <th class="sortable">
                                                                            <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction')) }}"
                                                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                <span>{{ $catSortable['package_weight_net'] }}</span>
                                                                                @if(request('sort_by') == 'package_weight_net')
                                                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                @else
                                                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                @endif
                                                                            </a>
                                                                        </th>
                                                                        @foreach(['store','address','purchase_price','comparison_price_per_kg'] as $col)
                                                                            <th class="sortable">
                                                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                    <span>{!! $catSortable[$col] !!}</span>
                                                                                    @if(request('sort_by') == $col)
                                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                    @else
                                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                    @endif
                                                                                </a>
                                                                            </th>
                                                                        @endforeach
                                                                        <th class="action-cell">Aksi</th>
                                                                    </tr>
                                                                @elseif($material['type'] == 'cement')
                                                                    <tr class="dim-group-row">
                                                                        <th rowspan="2">No</th>
                                                                        @foreach(['type','brand','sub_brand'] as $col)
                                                                            <th class="sortable" rowspan="2">
                                                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                    <span>{{ $cementSortable[$col] }}</span>
                                                                                    @if(request('sort_by') == $col)
                                                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                    @else
                                                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                    @endif
                                                                                </a>
                                                                            </th>
                                                                        @endforeach
                                                                        <th class="sortable" rowspan="2">
                                                                            <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction')) }}"
                                                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                                                <span>{{ $cementSortable['code'] }}</span>
                                                                                @if(request('sort_by') == 'code')
                                                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                                                @else
                                                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                                                @endif
                                                                            </a>
                                                                        </th>
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction')) }}"
                                            style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $cementSortable['color'] }}</span>
                                                @if(request('sort_by') == 'color')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                            style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $cementSortable['package_unit'] }}</span>
                                                @if(request('sort_by') == 'package_unit')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        
                                        @foreach(['package_weight_net','store','address','package_price','comparison_price_per_kg'] as $col)
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                            style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{!! $cementSortable[$col] !!}</span>
                                                @if(request('sort_by') == $col)
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @endforeach
                                        <th rowspan="2" class="action-cell">Aksi</th>
                                    </tr>
                                    {{-- Removed dim-sub-row for cement --}}
                                @elseif($material['type'] == 'ceramic')
                                    <tr class="dim-group-row">
                                        <th rowspan="2">No</th>
                                        @foreach(['type','brand','sub_brand','code','color','form'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th rowspan="2">Kemasan</th>
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl('pieces_per_package', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>{{ $ceramicSortable['pieces_per_package'] }}</span>
                                                @if(request('sort_by') == 'pieces_per_package')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th rowspan="2">{{ $ceramicSortable['area_per_piece'] }}</th>
                                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                <span>Dimensi (cm)</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_thickness']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['store','address','price_per_package','comparison_price_per_m2'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{!! $ceramicSortable[$col] !!}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th rowspan="2" class="action-cell">Aksi</th>
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
                                    <td>
                                        {{ $material['data']->firstItem() + $index }}
                                    </td>
                                    @if($material['type'] == 'brick')
                                        <td>{{ $item->type ?? '-' }}</td>
                                        <td>{{ $item->brand ?? '-' }}</td>
                                        <td>{{ $item->form ?? '-' }}</td>
                                        <td class="dim-cell">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell">
                                            @if(!is_null($item->dimension_height))
                                                {{ rtrim(rtrim(number_format($item->dimension_height, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="volume-cell">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }} M3
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->store ?? '-' }}</td>
                                        <td>{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->price_per_piece)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->price_per_piece, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cat')
                                        <td>{{ $item->type ?? '-' }}</td>
                                        <td>{{ $item->brand ?? '-' }}</td>
                                        <td>{{ $item->sub_brand ?? '-' }}</td>
                                        <td class="dim-cell">{{ $item->color_code ?? '-' }}</td>
                                        <td class="dim-cell">{{ $item->color_name ?? '-' }}</td>
                                        <td class="dim-cell">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }} ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->volume)
                                                {{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }} {{ $item->volume_unit }}
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 2px 6px; background: rgba(255, 255, 255, 0.1); border-radius: 6px; font-size: 12px; font-weight: 500;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->purchase_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->purchase_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'cement')
                                        <td>{{ $item->type ?? '-' }}</td>
                                        <td>{{ $item->brand ?? '-' }}</td>
                                        <td>{{ $item->sub_brand ?? '-' }}</td>
                                        <td class="dim-cell" style="font-size: 12px; text-align:right;">{{ $item->code ?? '-' }}</td>
                                        <td class="dim-cell" style="text-align:left;">{{ $item->color ?? '-' }}</td>
                                        <td class="dim-cell" style="font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <!-- Dimensi Data Removed -->
                                        
                                        <td style="text-align: right; font-size: 13px;">
                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                {{ rtrim(rtrim(number_format($item->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 2px 6px; background: rgba(255, 255, 255, 0.1); border-radius: 6px; font-size: 12px; font-weight: 500;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_kg)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_kg, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'sand')
                                        <td>{{ $item->type ?? '-' }}</td>
                                        <td>{{ $item->brand ?? '-' }}</td>
                                        <td style="font-size: 13px;">
                                            @if($item->package_unit)
                                                {{ $item->packageUnit?->name ?? $item->package_unit }} ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_height))
                                                {{ rtrim(rtrim(number_format($item->dimension_height, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="volume-cell" style="text-align: right; font-size: 12px;">
                                            @if($item->package_volume)
                                                {{ number_format($item->package_volume, 6, ',', '.') }} M3
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 2px 6px; background: rgba(255, 255, 255, 0.1); border-radius: 6px; font-size: 12px; font-weight: 500;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->package_price)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->package_price, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m3)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m3, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @elseif($material['type'] == 'ceramic')
                                        <td>{{ $item->type ?? '-' }}</td>
                                        <td>{{ $item->brand ?? '-' }}</td>
                                        <td>{{ $item->sub_brand ?? '-' }}</td>
                                        <td style="font-size: 12px;">{{ $item->code ?? '-' }}</td>
                                        <td>{{ $item->color ?? '-' }}</td>
                                        <td>{{ $item->form ?? '-' }}</td>
                                        <td style="font-size: 13px;">{{ $item->packaging ?? '-' }}</td>
                                        <td style="text-align: right; font-size: 13px;">
                                            @if($item->pieces_per_package)
                                                {{ number_format($item->pieces_per_package, 0, ',', '.') }} Lbr
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right; font-size: 12px;">
                                            @if($item->area_per_piece)
                                                {{ number_format($item->area_per_piece, 4, ',', '.') }} M²
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                            @if(!is_null($item->dimension_thickness))
                                                {{ rtrim(rtrim(number_format($item->dimension_thickness, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td><span style="display: inline-block; padding: 2px 6px; background: rgba(255, 255, 255, 0.1); border-radius: 6px; font-size: 12px; font-weight: 500;">{{ $item->store ?? '-' }}</span></td>
                                        <td style="font-size: 12px; line-height: 1.5;">{{ $item->address ?? '-' }}</td>
                                        <td>
                                            @if($item->price_per_package)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->price_per_package, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->comparison_price_per_m2)
                                                <div style="display: flex; width: 100%; font-size: 13px;"><span>Rp</span><span style="font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">{{ number_format($item->comparison_price_per_m2, 0, ',', '.') }}</span></div>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center action-cell">
                                        <div class="btn-group-compact">
                                            <a href="{{ route($material['type'] . 's.show', $item->id) }}" class="btn btn-primary-glossy btn-action open-modal" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route($material['type'] . 's.edit', $item->id) }}" class="btn btn-warning btn-action open-modal" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="position: relative; margin-top: 20px; margin-bottom: -20px; display: flex; align-items: center; justify-content: center; min-height: 80px;">
                        
                        <!-- Left Area: Stats Info (Absolute Positioned) -->
                        <div style="position: absolute; left: 0; top: 38%; transform: translateY(-50%); display: flex; flex-direction: row; gap: 8px;">

                            <!-- HEXAGON PER MATERIAL -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                                title="Total {{ $material['label'] }}">
                                
                                <div style="position: relative; width: 74px; height: 74px; display: flex; align-items: center; justify-content: center;">
                                    <img src="./assets/hex1.png"
                                        alt="Hexagon"
                                        style="width: 74px; height: 74px;">

                                    <div style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                        <span style="font-size: 32px; font-weight: 800; line-height: 1; color: #ffffff !important; -webkit-text-stroke: 1.5px #000; text-shadow: 2px 2px 0 #000;">
                                            {{ number_format($material['db_count'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                                
                                <span style="font-size: 10px; font-weight: 700 !important; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px; color: #000 !important;">
                                    {{ $material['label'] }}
                                </span>
                            </div>

                            <!-- HEXAGON TOTAL -->
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start;"
                                title="Total Semua Material">
                                
                                <div style="position: relative; width: 74px; height: 74px; display: flex; align-items: center; justify-content: center;">
                                    <img src="./assets/hex2.png"
                                        alt="Hexagon"
                                        style="width: 74px; height: 74px;">

                                    <div style="position: absolute; display: flex; align-items: center; justify-content: center; width: 64px;">
                                        <span style="font-size: 32px; font-weight: 800; line-height: 1; color: #ffffff !important; -webkit-text-stroke: 1.5px #000; text-shadow: 2px 2px 0 #000;">
                                            {{ number_format($grandTotal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                <span style="font-size: 10px; font-weight: 700 !important; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px; color: #000 !important; text-align: center; line-height: 1.2;">
                                    SEMUA MATERIAL
                                </span>
                            </div>

                        </div>

                        <!-- Center Area: Pagination & Kanggo Logo -->
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; top: -10px;">



                            <!-- Kanggo A-Z Pagination (Logo & Letters) -->
                            @if(!request('search'))
                            <div class="kanggo-container" style="padding-top: 0;">
                                <div class="kanggo-logo">
                                    <img src="/Pagination/kangg.png" alt="Kanggo" style="height: 70px; width: auto;">
                                </div>
                                <div class="kanggo-letters" style="justify-content: center; margin-top: 5px; height: 80px;">
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
                                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 0 0 2px;">
                                                    <!-- Up Arrow (Prev Page) -->
                                                    <a href="{{ $currentPage > 1 ? $getPageUrl($currentPage - 1) : '#' }}" 
                                                       class="page-arrow-btn {{ $currentPage <= 1 ? 'disabled' : '' }}"
                                                       title="{{ $currentPage > 1 ? 'Halaman Sebelumnya' : '' }}"
                                                       style="color: {{ $currentPage > 1 ? '#0046FF' : '#cbd5e1' }} !important;">
                                                        <i class="bi bi-chevron-up"></i>
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
                                                       title="{{ $currentPage < $lastPage ? 'Halaman Selanjutnya' : '' }}"
                                                       style="color: {{ $currentPage < $lastPage ? '#0046FF' : '#cbd5e1' }} !important;">
                                                        <i class="bi bi-chevron-down"></i>
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
                        <div style="position: absolute; right: 0; top: 50%; transform: translateY(calc(-50% - 10px));">
                            <a href="{{ route($material['type'] . 's.index', request()->query()) }}" class="btn btn-primary-glossy">
                                Lihat Semua <i class="bi bi-arrow-right" style="margin-left: 6px;"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div style="padding: 60px 40px; text-align: center; color: #64748b; background: #fff; border-radius: 12px; border: 1px dashed #e2e8f0; margin-top: 20px;">
                        <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">🔍</div>
                        <h4 style="margin: 0 0 8px 0; font-weight: 700; color: #0f172a;">Tidak ada data ditemukan</h4>
                        <p style="margin: 0 0 24px 0; font-size: 14px;">
                            @if(request('search'))
                                Pencarian untuk "<strong>{{ request('search') }}</strong>" di kategori {{ $material['label'] }} tidak membuahkan hasil.
                            @else
                                Belum ada data {{ strtolower($material['label']) }} yang tersedia.
                            @endif
                        </p>
                        @if(request('search'))
                            <a href="{{ route('materials.index', ['tab' => $material['type']]) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Pencarian
                            </a>
                        @else
                            <a href="{{ route($material['type'] . 's.create') }}" class="btn btn-primary-glossy open-modal">
                                <i class="bi bi-plus-lg"></i> Tambah {{ $material['label'] }} Baru
                            </a>
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
            <a href="{{ route('materials.settings') }}" class="btn btn-primary-glossy" style="margin-top: 16px;">
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
                <a href="{{ route('ceramics.create') }}" class="material-choice-card open-modal">
                    <div class="material-choice-icon">🟫</div>
                    <div class="material-choice-label">Keramik</div>
                    <div class="material-choice-desc">Tambah data keramik</div>
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
        
        const settingsDropdown = tabContainer.querySelector('.material-settings-dropdown');

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
            
            // Always move settings dropdown to the end
            if (settingsDropdown) {
                tabContainer.appendChild(settingsDropdown);
            }
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

        // Ensure tab container is always visible so Filter button remains accessible
        if (tabContainer) {
            tabContainer.style.display = 'flex';
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

    // Expose closeModal as global function for form cancel buttons
    window.closeFloatingModal = closeModal;

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
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.tab);
                // Also update the stored URL when tab changes to ensure we return to this tab
                // We construct a new URL with the updated 'tab' parameter
                const url = new URL(window.location.href);
                url.searchParams.set('tab', btn.dataset.tab);
                // Reset page to 1 when switching tabs to avoid empty pages
                url.searchParams.delete(btn.dataset.tab + '_page'); 
                localStorage.setItem('lastMaterialsUrl', url.toString());
                // Note: We don't pushState here to avoid reload, but saving to LS is enough for Navbar return
            });
        });
    }

    // --- Save Current State for Navbar Return ---
    // Save the full current URL to localStorage on page load
    localStorage.setItem('lastMaterialsUrl', window.location.href);

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