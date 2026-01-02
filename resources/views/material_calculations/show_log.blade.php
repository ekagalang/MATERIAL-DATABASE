@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                    <i class="bi bi-file-text text-primary me-2"></i>Detail Perhitungan
                </h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('material-calculations.log') }}" class="btn-cancel" style="border: 1px solid #64748b; background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('material-calculations.edit', $materialCalculation) }}" class="btn-action" style="background-color: #f59e0b; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="bi bi-pencil-square"></i> Edit
                </a>
                <button type="button" class="btn-action" onclick="window.print()" style="background-color: #0ea5e9; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>

    @php
        $costPerM2 = $materialCalculation->wall_area > 0 ? $materialCalculation->total_material_cost / $materialCalculation->wall_area : 0;

        // Retrieve dynamic Formula Name
        $params = $materialCalculation->calculation_params ?? [];
        $workType = $params['work_type'] ?? 'brick_half';
        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';

        $brickType = $materialCalculation->brick ? $materialCalculation->brick->type : 'Merah';

        // Deteksi kebutuhan material untuk tampilan dinamis
        $hasBrick = $materialCalculation->brick_quantity > 0;
        $hasCement = $materialCalculation->cement_quantity_sak > 0;
        $hasSand = $materialCalculation->sand_m3 > 0;
        $hasCat = $materialCalculation->cat_quantity > 0;
        
        // Calculate rowSpan based on active materials + Water (always 1)
        $rowSpan = 1 + ($hasBrick ? 1 : 0) + ($hasCement ? 1 : 0) + ($hasSand ? 1 : 0) + ($hasCat ? 1 : 0);
        
        // Track rendered rows to place rowspan on the first one
        $isFirstRow = true;
    @endphp

    {{-- Header Info: Item Pekerjaan Details Card --}}
    <div class="container mb-3">
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
                        $isPainting = $workType === 'painting';
                        $paramLabel = $isPainting ? 'LAPIS' : 'TEBAL';
                        $paramUnit = $isPainting ? 'Lapis' : 'cm';
                        $paramValue = $isPainting ? ($params['painting_layers'] ?? 2) : ($materialCalculation->mortar_thickness ?? 2.0);
                        $badgeClass = $isPainting ? 'bg-primary text-white' : 'bg-light';
                        $bgClass = $isPainting ? 'bg-primary text-white' : 'bg-light'; // Badge bg
                        $inputBg = $isPainting ? '#e0f2fe' : '#e9ecef'; // Input bg
                        $inputBorder = $isPainting ? '#38bdf8' : '#dee2e6'; // Input border color
                    @endphp
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge {{ $badgeClass }} border">{{ $paramLabel }}</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: {{ $inputBg }}; border-color: {{ $inputBorder }};">{{ $paramValue }}</div>
                        <span class="input-group-text small px-1" style="font-size: 0.7rem; background-color: {{ $isPainting ? '#bae6fd' : '#e9ecef' }}; border-color: {{ $inputBorder }};">{{ $paramUnit }}</span>
                    </div>
                </div>

                {{-- Panjang --}}
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-light border">PANJANG</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ number_format($materialCalculation->wall_length, 2, '.', '') }}</div>
                        <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                    </div>
                </div>

                {{-- Tinggi / Lebar (untuk Rollag) --}}
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-light border">
                            {{ $workType === 'brick_rollag' ? 'LEBAR' : 'TINGGI' }}
                        </span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #e9ecef;">{{ number_format($materialCalculation->wall_height, 2, '.', '') }}</div>
                        <span class="input-group-text bg-light small px-1" style="font-size: 0.7rem;">M</span>
                    </div>
                </div>

                {{-- Tingkat (hanya untuk Rollag) --}}
                @if($workType === 'brick_rollag')
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-warning text-dark border">TINGKAT</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #fffbeb; border-color: #fcd34d;">{{ $params['layer_count'] ?? 1 }}</div>
                        <span class="input-group-text bg-warning text-dark small px-1" style="font-size: 0.7rem;">Lapis</span>
                    </div>
                </div>
                @endif

                {{-- Sisi Aci (hanya untuk Aci Dinding) --}}
                @if($workType === 'skim_coating')
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-info text-white border">SISI ACI</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #e0f2fe; border-color: #38bdf8;">{{ $params['skim_sides'] ?? 1 }}</div>
                        <span class="input-group-text bg-info text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                    </div>
                </div>
                @endif

                {{-- Sisi Plester (hanya untuk Plester Dinding) --}}
                @if($workType === 'wall_plastering')
                <div style="flex: 0 0 auto; width: 100px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-success text-white border">SISI PLESTER</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #d1fae5; border-color: #34d399;">{{ $params['plaster_sides'] ?? 1 }}</div>
                        <span class="input-group-text bg-success text-white small px-1" style="font-size: 0.7rem;">Sisi</span>
                    </div>
                </div>
                @endif

                {{-- Luas --}}
                <div style="flex: 0 0 auto; width: 120px;">
                    <label class="fw-bold mb-2 text-uppercase text-secondary d-block text-start" style="font-size: 0.75rem;">
                        <span class="badge bg-primary text-white border">LUAS</span>
                    </label>
                    <div class="input-group">
                        <div class="form-control fw-bold text-center px-1" style="background-color: #dbeafe; border-color: #3b82f6;">{{ number_format($materialCalculation->wall_area, 2, '.', '') }}</div>
                        <span class="input-group-text bg-primary text-white small px-1" style="font-size: 0.7rem;">M2</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
    <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
        <div class="table-responsive">
            <style>
                /* Global Text Styling for All Elements */
                h1, h2, h3, h4, h5, h6, p, span, div, a, label, input, select, textarea, button, th, td, i, strong,
                .text-muted, .text-dark, .text-secondary, .small, .fw-bold, .badge {
                    font-family: 'League Spartan', sans-serif !important;
                    color: #ffffff !important;
                    -webkit-text-stroke: 0.2px black !important;
                    text-shadow: 0 1.1px 0 #000000 !important;
                    font-weight: 700 !important;
                }

                /* Override for input/form controls */
                .form-control, .input-group-text {
                    color: #1e293b !important;
                    -webkit-text-stroke: 0 !important;
                    text-shadow: none !important;
                }

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
                    font-size: 12px;
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
                .btn-action:hover {
                    filter: brightness(0.95);
                    transform: translateY(-1px);
                }
                .btn-cancel:hover {
                    background-color: #f1f5f9 !important;
                    color: #334155 !important;
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
                    min-width: 140px;
                }
                .sticky-col-2 {
                    position: sticky;
                    left: 140px;
                    background-color: white;
                    z-index: 2;
                    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
                    min-width: 80px;
                }
                .sticky-col-3 {
                    position: sticky;
                    left: 220px;
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
                .group-end {
                    border-bottom: 3px solid #891313 !important;
                }
                .group-end td {
                    border-bottom: 3px solid #891313 !important;
                }
                .rowspan-cell {
                    border-bottom: 3px solid #891313 !important;
                }
            </style>

            <table class="table-preview">
                <thead class="align-top">
                    <tr>
                        <th class="sticky-col-1">Qty / Pekerjaan</th>
                        <th class="sticky-col-2">Satuan</th>
                        <th class="sticky-col-3">Material</th>
                        <th colspan="4">Detail</th>
                        <th>Toko</th>
                        <th>Alamat</th>
                        <th colspan="2">Harga / Kemasan</th>
                        <th>Harga Komparasi</br> / Pekerjaan</th>
                        <th>Total Biaya</br>Material / Pekerjaan</th>
                        <th colspan="2">Harga Satuan</br> Material / Pekerjaan</th>
                        <th colspan="2">Harga Satuan Beli</th>
                    </tr>
                </thead>
                <tbody>

                    {{-- ROW 1: BATA --}}
                    @if($hasBrick)
                    <tr class="text-nowrap">
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->brick_quantity, 0, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">Bh</td>
                        <td class="fw-bold sticky-col-3">Bata</td>
                        <td class="text-muted">{{ $brickType }}</td>
                        <td class="fw-bold">{{ $materialCalculation->brick->brand ?? '-' }}</td>
                        <td class="text-center text-nowrap">{{ ($materialCalculation->brick->dimension_length ?? 0) + 0 }} x {{ ($materialCalculation->brick->dimension_width ?? 0) + 0 }} x {{ ($materialCalculation->brick->dimension_height ?? 0) + 0 }} cm</td>
                        <td></td>
                        <td class="store-cell">{{ $materialCalculation->brick->store ?? '-' }}</td>
                        <td class="small text-muted address-cell">{{ $materialCalculation->brick->address ?? '-' }}</td>
                        <td class="text-nowrap fw-bold">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ bh</td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        @if($isFirstRow)
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                    <span class="text-success-dark" style="font-size: 15px;">{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                    <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                            @php $isFirstRow = false; @endphp
                        @endif

                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted ps-1">/ bh</td>
                    </tr>
                    @endif

                    {{-- ROW 2: SEMEN --}}
                    @if($hasCement)
                    <tr>
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->cement_quantity_sak, 2, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">Sak</td>
                        <td class="fw-bold sticky-col-3">Semen</td>
                        <td class="text-muted">{{ $materialCalculation->cement->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->cement->brand ?? '-' }}</td>
                        <td>{{ $materialCalculation->cement->color ?? '-' }}</td>
                        <td class="text-start text-nowrap fw-bold">{{ ($materialCalculation->cement->package_weight_net ?? 0) + 0 }} Kg</td>
                        <td class="store-cell">{{ $materialCalculation->cement->store ?? '-' }}</td>
                        <td class="small text-muted address-cell">{{ $materialCalculation->cement->address ?? '-' }}</td>
                        <td class="text-nowrap fw-bold">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->cement->package_unit ?? 'Sak' }}</td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        @if($isFirstRow)
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                    <span class="text-success-dark" style="font-size: 15px;">{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                    <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                            @php $isFirstRow = false; @endphp
                        @endif

                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->cement->package_unit ?? 'Sak' }}</td>
                    </tr>
                    @endif

                    {{-- ROW 3: PASIR --}}
                    @if($hasSand)
                    <tr>
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->sand_m3, 3, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">M3</td>
                        <td class="fw-bold sticky-col-3">Pasir</td>
                        <td class="text-muted">{{ $materialCalculation->sand->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->sand->brand ?? '-' }}</td>
                        <td>{{ $materialCalculation->sand->sand_name ?? '-' }}</td>
                        <td class="text-start text-nowrap fw-bold">{{ $materialCalculation->sand->package_volume ? ($materialCalculation->sand->package_volume + 0) . ' M3' : '-' }}</td>
                        <td class="store-cell">{{ $materialCalculation->sand->store ?? '-' }}</td>
                        <td class="small text-muted address-cell">{{ $materialCalculation->sand->address ?? '-' }}</td>
                        <td class="text-nowrap fw-bold">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->sand->package_unit ?? 'M3' }}</td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        @if($isFirstRow)
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                    <span class="text-success-dark" style="font-size: 15px;">{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                    <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                            @php $isFirstRow = false; @endphp
                        @endif

                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->sand->package_unit ?? 'M3' }}</td>
                    </tr>
                    @endif

                    {{-- ROW 4: CAT (NEW) --}}
                    @if($hasCat)
                    <tr>
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->cat_quantity, 2, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">{{ $materialCalculation->cat->package_unit ?? 'Kmsn' }}</td>
                        <td class="fw-bold sticky-col-3">Cat</td>
                        <td class="text-muted">{{ $materialCalculation->cat->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->cat->brand ?? '-' }}</td>
                        <td>{{ $materialCalculation->cat->color_name ?? '-' }}</td>
                        <td class="text-start text-nowrap fw-bold">{{ ($materialCalculation->cat->package_weight_net ?? 0) + 0 }} Kg</td>
                        <td class="store-cell">{{ $materialCalculation->cat->store ?? '-' }}</td>
                        <td class="small text-muted address-cell">{{ $materialCalculation->cat->address ?? '-' }}</td>
                        <td class="text-nowrap fw-bold">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cat_price_per_package, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->cat->package_unit ?? 'Kmsn' }}</td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cat_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        @if($isFirstRow)
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                    <span class="text-success-dark" style="font-size: 15px;">{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">
                                <div class="d-flex justify-content-between w-100">
                                    <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                    <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td rowspan="{{ $rowSpan }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                            @php $isFirstRow = false; @endphp
                        @endif

                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cat_price_per_package, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->cat->package_unit ?? 'Kmsn' }}</td>
                    </tr>
                    @endif

                    {{-- ROW 5: AIR (ALWAYS LAST) --}}
                    <tr class="group-end">
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->water_liters, 2, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">L</td>
                        <td class="fw-bold sticky-col-3">Air</td>
                        <td class="text-muted">Bersih</td>
                        <td>PDAM</td>
                        <td colspan="2"></td>
                        <td>Customer</td>
                        <td>-</td>
                        <td class="text-center text-muted">-</td>
                        <td></td>
                        <td class="text-center text-muted">-</td>
                        
                        @if($isFirstRow)
                            {{-- Case where NO materials are selected (should not happen, but safe fallback) --}}
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">...</td>
                            <td rowspan="{{ $rowSpan }}" class="text-end bg-highlight align-top rowspan-cell">...</td>
                            <td rowspan="{{ $rowSpan }}" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell">...</td>
                        @endif

                        <td class="text-center text-muted">-</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

<style>
    @media print {
        /* Atur page menjadi landscape */
        @page {
            size: landscape;
            margin: 8mm;
        }

        /* Sembunyikan navigation dan buttons - PENTING! */
        .nav,
        nav,
        .navbar,
        button,
        .btn,
        a[href*="kembali"],
        a[href*="edit"],
        a.btn-cancel,
        a.btn-action {
            display: none !important;
        }

        /* Reset body untuk print */
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Container simple */
        .container,
        .container-fluid {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Card tanpa styling */
        .card {
            box-shadow: none !important;
            border: none !important;
            padding: 8px !important;
            margin: 0 !important;
        }

        /* Table responsive - visible all */
        .table-responsive {
            overflow: visible !important;
        }

        /* Table - Simple & Clean */
        .table-preview {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important;
        }

        .table-preview thead {
            display: table-header-group !important;
        }

        .table-preview tbody {
            display: table-row-group !important;
        }

        .table-preview th {
            background: #891313 !important;
            color: white !important;
            padding: 6px 4px !important;
            font-size: 9px !important;
            border: 1px solid #666 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table-preview td {
            padding: 6px 4px !important;
            font-size: 9px !important;
            border: 1px solid #ddd !important;
            vertical-align: middle !important;
        }

        /* Warna tetap muncul */
        .bg-highlight {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .text-success-dark {
            color: #059669 !important;
        }

        .text-primary-dark {
            color: #891313 !important;
        }

        /* Header */
        h2 {
            font-size: 14px !important;
            margin: 8px 0 !important;
        }

        /* Hide hover effects */
        .table-preview tbody tr:hover td {
            background: transparent !important;
        }

        /* Prevent page break di tengah row */
        tr {
            page-break-inside: avoid;
        }
    }
</style>
@endsection
