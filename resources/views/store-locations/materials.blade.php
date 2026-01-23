@extends('layouts.app')

@section('title', 'Material ' . $store->name)

@section('content')
<!-- Reuse inline scripts from materials.index -->
<script>
(function() {
    document.documentElement.classList.add('materials-booting');
    document.documentElement.classList.add('materials-lock');
})();
(function() {
    const savedTab = localStorage.getItem('materialActiveTab');
    if (savedTab) {
        window.__materialSavedTab = savedTab;
    }
})();
(function() {
    window.addEventListener('load', function() {
        document.documentElement.classList.remove('materials-booting');
    });
    window.setTimeout(() => {
        document.documentElement.classList.remove('materials-booting');
    }, 2000);
})();
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('materials-lock');
});
</script>

<style>
/* Reuse styles from materials.index via copy-paste or shared file */
/* Minimal required overrides */
:root {
    --tab-foot-radius: 18px;
    --tab-active-bg: #91C6BC;
    overflow: hidden;
}
html { scroll-padding-top: 80px; scroll-behavior: smooth; }
html.materials-booting .material-tab-panel,
html.materials-booting .material-tab-action,
html.materials-booting #emptyMaterialState,
html.materials-booting .material-tabs,
html.materials-booting .material-tab-actions {
    opacity: 0;
    visibility: hidden;
}
html.materials-booting .page-content {
    opacity: 0;
    visibility: hidden;
}

/* Layout Fixes */
.material-tab-header {
    display: flex;
    align-items: flex-end; /* Align bottom to match tabs */
    justify-content: space-between;
    gap: 12px;
    padding-top: 10px;
    margin-bottom: 0;
    border-bottom: 2px solid #e2e8f0;
}

.material-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    flex: 1;
}

.material-tab-actions {
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    margin-bottom: -2px; /* Pull down to overlap border if needed */
    z-index: 10;
}

/* Hide inactive search bars to prevent overlap */
.material-tab-action {
    display: none;
    align-items: center;
    gap: 8px;
}

/* Show only active search bar */
.material-tab-action.active {
    display: flex;
    background: #91C6BC;
    border-color: #91C6BC;
    border: 2px solid #91C6BC;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 8px 12px 4px;
    height: 48px;
    box-sizing: border-box;
}

.material-tab-btn.active { --tab-border-color: #91C6BC; }

/* Sticky footer styles */
.material-footer-sticky {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 72px;
    z-index: 1;
    background: transparent;
    border-top: none;
    box-shadow: none;
    padding: 6px 0 10px;
}

/* Page specific header */
.store-material-header {
    margin-bottom: 20px;
}

.material-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.material-search-input {
    position: relative;
    width: 200px; /* Or auto/flex depending on desired width */
}

.material-search-input input {
    width: 100%;
    height: 34px;
    padding: 4px 10px 4px 30px; /* Left padding for icon */
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.2s ease;
}

.material-search-input i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #64748b; /* Or var(--text-color) if available */
    opacity: 0.8;
    z-index: 2;
    pointer-events: none;
}

.material-tab-card {
    background: #fff;
    border: 2px solid #91C6BC;
    border-top: none;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
    position: relative;
    z-index: 5;
    margin-top: -2px;
}
</style>

<div class="page-content">
    <div class="container-fluid py-4">
        <!-- Location Info Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-4" style="display: flex; justify-content: space-between;">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">{{ $store->name ?? 'Toko Tanpa Nama' }}</h4>
                        <p class="text-secondary mb-0 small">
                            <i class="bi bi-geo-alt me-1"></i> 
                            {{ $location->address ?? $location->city ?? 'Lokasi Tanpa Alamat' }}
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">   
                    <a href="{{ route('stores.show', $store) }}" class="btn-cancel"
                    style="border: 1px solid #891313; background-color: transparent; color: #891313;
                    padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 10px;
                    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Material Tabs (Same structure as index) -->
        @php
            $activeTab = request('tab') ?? ($materials[0]['type'] ?? null);
        @endphp

        <div class="material-tab-wrapper">
            <div class="material-tab-header">
                <div class="material-tabs">
                    @foreach($materials as $material)
                        <button type="button"
                                class="material-tab-btn {{ $material['type'] === $activeTab ? 'active' : '' }}"
                                data-tab="{{ $material['type'] }}">
                            <span>{{ $material['label'] }}</span>
                            <span class="badge bg-white text-dark ms-2 rounded-pill small" style="font-size: 10px;">{{ $material['count'] }}</span>
                        </button>
                    @endforeach
                </div>
                
                <div class="material-tab-actions">
                    @foreach($materials as $material)
                        <div class="material-tab-action {{ $material['type'] === $activeTab ? 'active' : '' }}" data-tab="{{ $material['type'] }}">
                            <form action="{{ url()->current() }}" method="GET" class="material-search-form">
                                <input type="hidden" name="tab" value="{{ $material['type'] }}">
                                <div class="material-search-input">
                                    <i class="bi bi-search"></i>
                                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari {{ strtolower($material['label']) }}...">
                                </div>
                                <button type="submit" class="btn btn-primary-glossy btn-sm">Cari</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Content Panels -->
            @if(empty($materials))
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <div class="mb-3 text-muted opacity-50">
                        <i class="bi bi-box-seam display-1"></i>
                    </div>
                    <h5 class="fw-bold">Belum Ada Material</h5>
                    <p class="text-muted small">Lokasi ini belum memiliki data material yang terdaftar.</p>
                </div>
            @else
                @foreach($materials as $material)
                    <div class="material-tab-panel {{ $material['type'] === $activeTab ? 'active' : 'hidden' }}" data-tab="{{ $material['type'] }}" id="section-{{ $material['type'] }}">
                        <div class="material-tab-card">
                        @if($material['data']->count() > 0)
                            <div class="table-container text-nowrap">
                                <table>
                                    <thead class="{{ in_array($material['type'], ['brick','sand','ceramic','cement','cat']) ? 'has-dim-sub' : 'single-header' }}">
                                        @php                                   
                                            if (!function_exists('getMaterialSortUrl')) {
                                                function getMaterialSortUrl($column, $currentSortBy, $currentDirection) {
                                                    $params = array_merge(request()->query(), []);
                                                    unset($params['sort_by'], $params['sort_direction']);
                                                    if ($currentSortBy === $column) {
                                                        if ($currentDirection === 'asc') {
                                                            $params['sort_by'] = $column;
                                                            $params['sort_direction'] = 'desc';
                                                        } elseif ($currentDirection === 'desc') {
                                                            unset($params['sort_by'], $params['sort_direction']);
                                                        } else {
                                                            $params['sort_by'] = $column;
                                                            $params['sort_direction'] = 'asc';
                                                        }
                                                    } else {
                                                        $params['sort_by'] = $column;
                                                        $params['sort_direction'] = 'asc';
                                                    }
                                                    // Use current URL to stay on store page
                                                    return url()->current() . '?' . http_build_query($params);
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
                                                'surface' => 'Permukaan',
                                                'packaging' => 'Kemasan',
                                                'pieces_per_package' => 'Volume',
                                                'coverage_per_package' => 'Luas (M2 / Dus)',
                                                'dimension_length' => 'Dimensi (cm)',
                                                'store' => 'Toko',
                                                'address' => 'Alamat',
                                                'price_per_package' => 'Harga / Kemasan',
                                                'comparison_price_per_m2' => 'Harga Komparasi <br> (/ M2)',
                                            ];
                                        @endphp

                                        @if($material['type'] == 'brick')
                                            <tr class="dim-group-row">
                                                <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                                <th class="sortable" rowspan="2" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $brickSortable['type'] }}</span>
                                                        @if(request('sort_by') == 'type')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $brickSortable['brand'] }}</span>
                                                        @if(request('sort_by') == 'brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $brickSortable['form'] }}</span>
                                                        @if(request('sort_by') == 'form')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Dimensi (cm)</span>
                                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" colspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Volume</span>
                                                        @if(request('sort_by') == 'package_volume')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('price_per_piece', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Beli</span>
                                                        @if(request('sort_by') == 'price_per_piece')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction')) }}"
                                                        style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Komparasi</span>
                                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            </tr>
                                            <tr class="dim-sub-row">
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                            </tr>

                                        @elseif($material['type'] == 'sand')
                                            <tr class="dim-group-row">
                                                <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                                <th class="sortable" rowspan="2" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $sandSortable['type'] }}</span>
                                                        @if(request('sort_by') == 'type')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $sandSortable['brand'] }}</span>
                                                        @if(request('sort_by') == 'brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $sandSortable['package_unit'] }}</span>
                                                        @if(request('sort_by') == 'package_unit')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center; font-size: 13px; width: 120px; min-width: 120px;">
                                                    <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Dimensi (cm)</span>
                                                        @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_volume', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Volume</span>
                                                        @if(request('sort_by') == 'package_volume')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Beli</span>
                                                        @if(request('sort_by') == 'package_price')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('comparison_price_per_m3', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Komparasi</span>
                                                        @if(request('sort_by') == 'comparison_price_per_m3')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            </tr>
                                            <tr class="dim-sub-row">
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                                <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                            </tr>

                                        @elseif($material['type'] == 'cat')
                                            <tr class="dim-group-row">
                                                <th style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                                <th class="sortable" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['type'] }}</span>
                                                        @if(request('sort_by') == 'type')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['brand'] }}</span>
                                                        @if(request('sort_by') == 'brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" style="text-align: start;">
                                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['sub_brand'] }}</span>
                                                        @if(request('sort_by') == 'sub_brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" style="text-align: right;">
                                                    <a href="{{ getMaterialSortUrl('color_code', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['color_code'] }}</span>
                                                        @if(request('sort_by') == 'color_code')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('color_name', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['color_name'] }}</span>
                                                        @if(request('sort_by') == 'color_name')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['package_unit'] }}</span>
                                                        @if(request('sort_by') == 'package_unit')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('volume', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $catSortable['volume'] }}</span>
                                                        @if(request('sort_by') == 'volume')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Berat Bersih</span>
                                                        @if(request('sort_by') == 'package_weight_net')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('purchase_price', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Beli</span>
                                                        @if(request('sort_by') == 'purchase_price')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Komparasi</span>
                                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            </tr>
                                            
                                        @elseif($material['type'] == 'cement')
                                            <tr class="dim-group-row">
                                                <th rowspan="2" style="text-align: center; width: 40px; min-width: 40px;">No</th>
                                                <th class="sortable" rowspan="2" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['type'] }}</span>
                                                        @if(request('sort_by') == 'type')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['brand'] }}</span>
                                                        @if(request('sort_by') == 'brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['sub_brand'] }}</span>
                                                        @if(request('sort_by') == 'sub_brand')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: right;">
                                                    <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['code'] }}</span>
                                                        @if(request('sort_by') == 'code')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: left;">
                                                    <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['color'] }}</span>
                                                        @if(request('sort_by') == 'color')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_unit', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>{{ $cementSortable['package_unit'] }}</span>
                                                        @if(request('sort_by') == 'package_unit')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_weight_net', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Berat Bersih</span>
                                                        @if(request('sort_by') == 'package_weight_net')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('package_price', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Beli</span>
                                                        @if(request('sort_by') == 'package_price')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                                <th class="sortable" colspan="3" style="text-align: center;">
                                                    <a href="{{ getMaterialSortUrl('comparison_price_per_kg', request('sort_by'), request('sort_direction')) }}"
                                                    style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                        <span>Harga Komparasi</span>
                                                        @if(request('sort_by') == 'comparison_price_per_kg')
                                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            </tr>

                                        @elseif($material['type'] == 'ceramic')
                                        <tr class="dim-group-row">
                                            <th class="ceramic-sticky-col col-no" rowspan="2" style="text-align: center;">No</th>
                                            <th class="sortable ceramic-sticky-col col-type" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['type'] }}</span>
                                                    @if(request('sort_by') == 'type')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable ceramic-sticky-col col-dim-group" colspan="3" style="text-align: center; font-size: 13px;">
                                                <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Dimensi (cm)</span>
                                                    @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_thickness']))
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable ceramic-sticky-col col-brand ceramic-sticky-edge" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('brand', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['brand'] }}</span>
                                                    @if(request('sort_by') == 'brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('sub_brand', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['sub_brand'] }}</span>
                                                    @if(request('sort_by') == 'sub_brand')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('surface', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['surface'] }}</span>
                                                    @if(request('sort_by') == 'surface')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: right;">
                                                <a href="{{ getMaterialSortUrl('code', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Nomor Seri<br>(   Kode Pembakaran)</span>
                                                    @if(request('sort_by') == 'code')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: left;">
                                                <a href="{{ getMaterialSortUrl('color', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Corak ({{ $ceramicSortable['color'] }})</span>
                                                    @if(request('sort_by') == 'color')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('form', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['form'] }}</span>
                                                    @if(request('sort_by') == 'form')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('packaging', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>{{ $ceramicSortable['packaging'] }}</span>
                                                    @if(request('sort_by') == 'packaging')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="2" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('coverage_per_package', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Luas<br>(/ Dus)</span>
                                                    @if(request('sort_by') == 'coverage_per_package')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('price_per_package', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Beli</span>
                                                    @if(request('sort_by') == 'price_per_package')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            <th class="sortable" colspan="3" rowspan="2" style="text-align: center;">
                                                <a href="{{ getMaterialSortUrl('comparison_price_per_m2', request('sort_by'), request('sort_direction')) }}"
                                                style="color: inherit; text-decoration: none; display: inline-flex; align-items: flex-start; justify-content: center; gap: 6px;">
                                                    <span>Harga Komparasi</span>
                                                    @if(request('sort_by') == 'comparison_price_per_m2')
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up-alt' : 'sort-down sort-style' }}" style="font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up sort-style" style="font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        </tr>
                                        <tr class="dim-sub-row">
                                            <th class="ceramic-sticky-col col-dim-p" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">P</th>
                                            <th class="ceramic-sticky-col col-dim-l" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">L</th>
                                            <th class="ceramic-sticky-col col-dim-t" style="text-align: center; font-size: 12px; padding: 0 2px; width: 50px;">T</th>
                                        </tr>
                                        @endif
                                    </thead>

                                    @php
                                        $letterGroups = $material['data']->groupBy(function ($item) use ($material) {
                                            $groupValue = $item->brand ?? '';
                                            $groupValue = trim((string) $groupValue);
                                            return $groupValue !== '' ? strtoupper(substr($groupValue, 0, 1)) : '#';
                                        });
                                        $orderedGroups = collect();
                                        $isSorting = request()->filled('sort_by');
                                        if ($isSorting) {
                                            $orderedGroups['*'] = $material['data'];
                                        } else {
                                            // Assuming basic grouping logic if active_letters not present, or just use keys
                                            $keys = $letterGroups->keys()->sort();
                                            foreach ($keys as $key) {
                                                $orderedGroups[$key] = $letterGroups[$key];
                                            }
                                        }
                                        $rowNumber = 1;
                                    @endphp
                                    <tbody>
                                        @foreach($orderedGroups as $letter => $items)
                                            @php
                                                $anchorId = $isSorting ? null : ($letter === '#' ? 'other' : $letter);
                                            @endphp
                                            @foreach($items as $item)
                                                @php
                                                    $rowAnchorId = (!$isSorting && $loop->first) ? $material['type'] . '-letter-' . $anchorId : null;
                                                    $searchParts = array_filter([
                                                        $item->type ?? null,
                                                        $item->material_name ?? null,
                                                        $item->cat_name ?? null,
                                                        $item->cement_name ?? null,
                                                        $item->sand_name ?? null,
                                                        $item->brand ?? null,
                                                        $item->sub_brand ?? null,
                                                        $item->code ?? null,
                                                        $item->color ?? null,
                                                        $item->color_name ?? null,
                                                        $item->form ?? null,
                                                        $item->surface ?? null,
                                                    ], function ($value) {
                                                        return !is_null($value) && trim((string) $value) !== '';
                                                    });
                                                    $searchValue = strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $searchParts))));
                                                @endphp
                                                <tr data-material-tab="{{ $material['type'] }}" data-material-id="{{ $item->id }}" data-material-kind="{{ $item->type ?? '' }}" data-material-search="{{ $searchValue }}">
                                                    <td class="{{ $material['type'] == 'ceramic' ? 'ceramic-sticky-col col-no' : '' }}" @if($rowAnchorId) id="{{ $rowAnchorId }}" @endif @if($material['type'] == 'ceramic') style="text-align: center;" @elseif($material['type'] == 'cement') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'sand') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'cat') style="text-align: center; width: 40px; min-width: 40px;" @elseif($material['type'] == 'brick') style="text-align: center; width: 40px; min-width: 40px;" @endif>
                                                        {{ $rowNumber++ }}
                                                    </td>

                                                        @if($material['type'] == 'brick')
                                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
                                                        <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_length))
                                                                @format($item->dimension_length)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_width))
                                                                @format($item->dimension_width)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_height))
                                                                @format($item->dimension_height)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-right-none" style="text-align: right; width: 80px; min-width: 80px; font-size: 12px;">
                                                            @if($item->package_volume)
                                                                @format($item->package_volume)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">M3</td>                                                        
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                                            @if($item->price_per_piece)
                                                                @price($item->price_per_piece)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 60px; min-width: 60px;">/ Bh</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->comparison_price_per_m3)
                                                                @price($item->comparison_price_per_m3)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

                                                    @elseif($material['type'] == 'cat')
                                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                                        <td style="text-align: start;">{{ $item->sub_brand ?? '-' }}</td>
                                                        <td style="text-align: right; font-size: 12px;">{{ $item->color_code ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->color_name ?? '-' }}</td>
                                                        <td class="border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
                                                            @if($item->package_unit)
                                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 50px; min-width: 50px;">
                                                            @if($item->package_weight_gross)
                                                                (  @format($item->package_weight_gross )
                                                            @else
                                                                <span>(  -</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 50px; min-width: 50px;">Kg  )</td>
                                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                                            @if($item->volume)
                                                                @format($item->volume)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">L</td>
                                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px;">
                                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                                @format($item->package_weight_net)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">Kg</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->purchase_price)
                                                                @price($item->purchase_price)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->comparison_price_per_kg)
                                                                @price($item->comparison_price_per_kg)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

                                                    @elseif($material['type'] == 'cement')
                                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
                                                        <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
                                                        <td style="text-align: center; font-size: 13px;">
                                                            @if($item->package_unit)
                                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 12px;">
                                                            @if($item->package_weight_net && $item->package_weight_net > 0)
                                                                @format($item->package_weight_net)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">Kg</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->package_price)
                                                                @price($item->package_price)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->comparison_price_per_kg)
                                                                @price($item->comparison_price_per_kg)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ Kg</td>

                                                    @elseif($material['type'] == 'sand')
                                                        <td style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                                        <td style="text-align: center; font-size: 13px;">
                                                            @if($item->package_unit)
                                                                {{ $item->packageUnit?->name ?? $item->package_unit }}
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_length))
                                                                @format($item->dimension_length)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell border-left-none border-right-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_width))
                                                                @format($item->dimension_width)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell border-left-none" style="text-align: center; font-size: 12px; width: 40px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_height))
                                                                @format($item->dimension_height)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-right-none" style="text-align: right; width: 60px; min-width: 60px; font-size: 12px;">
                                                            @if($item->package_volume)
                                                                @format($item->package_volume)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 30px; min-width: 30px;">M3</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->package_price)
                                                                @price($item->package_price)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 80px; min-width: 80px;">/ {{ $item->packageUnit?->name ?? $item->package_unit ?? '-' }}</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->comparison_price_per_m3)
                                                                @price($item->comparison_price_per_m3)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M3</td>

                                                    @elseif($material['type'] == 'ceramic')
                                                        <td class="ceramic-sticky-col col-type" style="text-align: left;">{{ $item->type ?? '-' }}</td>
                                                        <td class="dim-cell ceramic-sticky-col col-dim-p border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_length))
                                                                @format($item->dimension_length)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell ceramic-sticky-col col-dim-l border-left-none border-right-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_width))
                                                                @format($item->dimension_width)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="dim-cell ceramic-sticky-col col-dim-t border-left-none" style="text-align: center; font-size: 12px; padding: 0 2px;">
                                                            @if(!is_null($item->dimension_thickness))
                                                                @format($item->dimension_thickness)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="ceramic-sticky-col col-brand ceramic-sticky-edge" style="text-align: center;">{{ $item->brand ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->sub_brand ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->surface ?? '-' }}</td>
                                                        <td style="text-align: right; font-size: 12px;">{{ $item->code ?? '-' }}</td>
                                                        <td style="text-align: left;">{{ $item->color ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $item->form ?? '-' }}</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">{{ $item->packaging ?? '-' }}</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 13px;">
                                                            @if($item->pieces_per_package)
                                                                (  @format($item->pieces_per_package)
                                                            @else
                                                                <span>(  -</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px; font-size: 13px;">Pcs  )</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px; font-size: 12px;">
                                                            @if($item->coverage_per_package)
                                                                @format($item->coverage_per_package)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px; font-size: 12px;">M2</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->price_per_package)
                                                                @price($item->price_per_package)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 60px; min-width: 60px;">/ Dus</td>
                                                        <td class="border-right-none" style="text-align: right; width: 40px; min-width: 40px;">Rp</td>
                                                        <td class="border-left-none border-right-none" style="text-align: right; width: 80px; min-width: 80px;">
                                                            @if($item->comparison_price_per_m2)
                                                                @price($item->comparison_price_per_m2)
                                                            @else
                                                                <span>-</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-left-none" style="text-align: left; width: 40px; min-width: 40px;">/ M2</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- Add JS for tab switching --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.material-tab-btn');
        const panels = document.querySelectorAll('.material-tab-panel');
        const actions = document.querySelectorAll('.material-tab-action');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;

                // Update Tabs
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Update Panels
                panels.forEach(p => {
                    p.classList.add('hidden');
                    p.classList.remove('active');
                    if (p.dataset.tab === target) {
                        p.classList.remove('hidden');
                        p.classList.add('active');
                    }
                });

                // Update Actions
                actions.forEach(a => {
                    a.classList.remove('active');
                    if (a.dataset.tab === target) {
                        a.classList.add('active');
                    }
                });
                
                // Save state
                localStorage.setItem('materialActiveTab', target);
            });
        });
    });
</script>
@endsection
