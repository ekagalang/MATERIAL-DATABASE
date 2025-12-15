@extends('layouts.app')

@section('title', 'Semua Material')

@section('content')
<div class="db-dropdown card" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; color: #0f172a; font-weight: 700;">
                <span>Sub Menu Database</span>
            </div>
            <button class="db-dropdown-toggle btn btn-primary btn-sm" data-target="#dbDropdownBody" aria-expanded="true" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-chevron-down" style="color: #ffffff; font-size: 1.2rem;"></i>
            </button>
        </div>
        <div class="db-dropdown-body" id="dbDropdownBody" style="margin-top: 12px;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="{{ route('bricks.index') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-bricks"></i> Bata
                </a>
                <a href="{{ route('sands.index') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-droplet"></i> Pasir
                </a>
                <a href="{{ route('cements.index') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-bucket"></i> Semen
                </a>
                <a href="{{ route('cats.index') }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="bi bi-palette"></i> Cat
                </a>
            </div>
        </div>
    </div>

<div class="card">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px; flex-wrap: wrap;">
        <h2 style="margin: 0; flex-shrink: 0;">Database Material</h2>

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

        <div style="display: flex; gap: 12px; flex-shrink: 0;">
            <button type="button" id="openMaterialChoiceModal" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </button>
            <a href="{{ route('materials.settings') }}" class="btn btn-secondary">
                <i class="bi bi-gear"></i> Pengaturan Tampilan
            </a>
        </div>
    </div>

    @if(count($materials) > 0)
        @foreach($materials as $material)
            <div class="material-section" id="section-{{ $material['type'] }}" style="margin-bottom: 24px;">
                <div class="material-section-header">
                    <h3 style="margin: 0; color: #891313; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        {{ $material['label'] }}
                        <span style="color: #94a3b8; font-size: 14px; font-weight: 400;">
                            ({{ $material['count'] }} items)
                        </span>
                        <a href="{{ route($material['type'] . 's.index', request()->query()) }}" class="btn btn-sm btn-primary">
                        Lihat Semua <i class="bi bi-arrow-right"></i>
                    </a>
                    </h3>
                    
                </div>

                @if($material['data']->count() > 0)
                    <div class="table-container">
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
                                        'short_address' => 'Alamat Singkat',
                                        'price_per_piece' => 'Harga / Buah',
                                        'comparison_price_per_m3' => 'Harga / M3',
                                    ];
                                    $sandSortable = [
                                        'type' => 'Jenis',
                                        'brand' => 'Merek',
                                        'package_unit' => 'Kemasan',
                                        'dimension_length' => 'Dimensi (M)',
                                        'package_volume' => 'Volume',
                                        'store' => 'Toko',
                                        'short_address' => 'Alamat Singkat',
                                        'package_price' => 'Harga',
                                        'comparison_price_per_m3' => 'Harga / M3',
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
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['brand','form'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $brickSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                                <span>Dimensi (cm)</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['package_volume','store','short_address','price_per_piece','comparison_price_per_m3'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $brickSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
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
                                @elseif($material['type'] == 'sand')
                                    <tr class="dim-group-row">
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Material</th>
                                        <th class="sortable" rowspan="2">
                                            <a href="{{ getMaterialSortUrl('type', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                <span>{{ $sandSortable['type'] }}</span>
                                                @if(request('sort_by') == 'type')
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['brand','package_unit'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $sandSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                                    @endif
                                                </a>
                                            </th>
                                        @endforeach
                                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                                            <a href="{{ getMaterialSortUrl('dimension_length', request('sort_by'), request('sort_direction')) }}"
                                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                                <span>Dimensi (M)</span>
                                                @if(in_array(request('sort_by'), ['dimension_length','dimension_width','dimension_height']))
                                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="font-size: 12px;"></i>
                                                @else
                                                    <i class="bi bi-arrow-down-up" style="font-size: 12px; opacity: 0.3;"></i>
                                                @endif
                                            </a>
                                        </th>
                                        @foreach(['package_volume','store','short_address','package_price','comparison_price_per_m3'] as $col)
                                            <th class="sortable" rowspan="2">
                                                <a href="{{ getMaterialSortUrl($col, request('sort_by'), request('sort_direction')) }}"
                                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                                    <span>{{ $sandSortable[$col] }}</span>
                                                    @if(request('sort_by') == $col)
                                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
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
                                        <th style="text-align: right;">Code</th>
                                        <th style="text-align: left;">Warna</th>
                                        <th>Kemasan</th>
                                        <th>Volume</th>
                                        <th style="text-align: left;">Berat Bersih</th>
                                        <th>Toko</th>
                                        <th style="text-align: left;">Alamat Singkat</th>
                                        <th>Harga</th>
                                        <th>Harga / Kg</th>
                                        <th>Aksi</th>
                                    </tr>
                                @elseif($material['type'] == 'cement')
                                    <tr>
                                        <th>No</th>
                                        <th>Material</th>
                                        <th>Jenis</th>
                                        <th>Merek</th>
                                        <th>Sub Merek</th>
                                        <th style="text-align: right">Code</th>
                                        <th style="text-align: left">Warna</th>
                                        <th>Kemasan</th>
                                        <th>Berat</th>
                                        <th>Toko</th>
                                        <th>Alamat Singkat</th>
                                        <th>Harga</th>
                                        <th>Harga / Kg</th>
                                        <th>Aksi</th>
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
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 1, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
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
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
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
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
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
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
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
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
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
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
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
                                                {{ $item->packageUnit->name ?? $item->package_unit }}</br> ({{ $item->package_weight_gross }} Kg)
                                            @else
                                                <span style="color: #cbd5e1;">—</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
                                            @if(!is_null($item->dimension_length))
                                                {{ rtrim(rtrim(number_format($item->dimension_length, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
                                            @if(!is_null($item->dimension_width))
                                                {{ rtrim(rtrim(number_format($item->dimension_width, 2, ',', '.'), '0'), ',') }}
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 1px 2px;">
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
                                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">{{ $item->short_address ?? '-' }}</td>
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

                    <div class="pagination" style="margin-top: 16px;">
                        {{ $material['data']->appends(request()->query())->links('pagination::simple-default') }}
                    </div>
                @else
                    <div style="padding: 40px; text-align: center; color: #94a3b8;">
                        Tidak ada data {{ strtolower($material['label']) }}
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada material yang ditampilkan</p>
            <p style="font-size: 14px; color: #94a3b8;">Atur material yang ingin ditampilkan di pengaturan</p>
            <a href="{{ route('materials.settings') }}" class="btn btn-primary" style="margin-top: 16px;">
                <i class="bi bi-gear"></i> Pengaturan Tampilan
            </a>
        </div>
    @endif
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
            <button class="floating-modal-close" id="closeModal">&times;</button>
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
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
}

.floating-modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #94a3b8;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.floating-modal-close:hover {
    background: #fee2e2;
    color: #ef4444;
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
}

.table-container thead .dim-sub-row th {
    border-top: 0 !important;
    border-bottom: 0 !important;
    border-left: 0 !important;
    border-right: 0 !important;
    padding: 1px 2px;
    width: 40px;
    position: relative;
}

.table-container tbody td.dim-cell {
    padding: 1px 2px !important;
    width: 40px;
    border-left: 0 !important;
    border-right: 0 !important;
    position: relative;
}

.table-container thead .dim-sub-row th + th::before,
.table-container tbody td.dim-cell + td.dim-cell::before {
    content: 'x';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 11px;
    pointer-events: none;
}

.table-container tbody td.volume-cell {
    padding: 6px 8px !important;
    width: 90px;
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

.material-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.material-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    color: #0f172a;
    font-weight: 600;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.material-nav-link:hover {
    border-color: #891313;
    color: #891313;
}

.material-nav-count {
    display: inline-block;
    min-width: 26px;
    padding: 4px 8px;
    background: #eef2ff;
    border-radius: 999px;
    font-size: 12px;
    color: #4338ca;
    text-align: center;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = modal.querySelector('.floating-modal-backdrop');

    function interceptFormSubmit() {
        const form = modalBody.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Let form submit normally
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
    function loadMaterialFormScript(materialType, modalBody) {
        const scriptProperty = `${materialType}FormScriptLoaded`;
        const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

        if (!window[scriptProperty]) {
            const script = document.createElement('script');
            script.src = `/js/${materialType}-form.js`;
            script.onload = () => {
                window[scriptProperty] = true;
                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        window[initFunctionName](modalBody);
                    }
                    interceptFormSubmit();
                }, 100);
            };
            document.head.appendChild(script);
        } else {
            setTimeout(() => {
                if (typeof window[initFunctionName] === 'function') {
                    window[initFunctionName](modalBody);
                }
                interceptFormSubmit();
            }, 100);
        }
    }

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
            } else if (action === 'edit') {
                modalTitle.textContent = `Edit ${materialLabel}`;
            } else if (action === 'show') {
                modalTitle.textContent = `Detail ${materialLabel}`;
            } else {
                modalTitle.textContent = materialLabel;
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

    // Material Choice Modal
    const materialChoiceModal = document.getElementById('materialChoiceModal');
    const openMaterialChoiceBtn = document.getElementById('openMaterialChoiceModal');
    const closeMaterialChoiceBtn = document.getElementById('closeMaterialChoiceModal');
    const materialChoiceBackdrop = materialChoiceModal.querySelector('.floating-modal-backdrop');

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
        card.addEventListener('click', function(e) {
            closeMaterialChoiceModal();
            // The open-modal class will handle opening the form modal
        });
    });

    // Dropdown card toggle
    const dbToggle = document.querySelector('.db-dropdown-toggle');
    const dbBody = document.querySelector(dbToggle?.getAttribute('data-target'));
    if (dbToggle && dbBody) {
        // default closed
        dbToggle.setAttribute('aria-expanded', 'false');
        dbBody.classList.remove('open');
        dbToggle.addEventListener('click', () => {
            const expanded = dbToggle.getAttribute('aria-expanded') === 'true';
            dbToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            dbBody.classList.toggle('open', !expanded);
        });
    }
});
</script>
@endsection
