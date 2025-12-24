@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="container mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1" style="color: #0f172a; font-size: 22px; letter-spacing: -0.5px;">
                    <i class="fas fa-file-alt text-primary me-2"></i>Detail Perhitungan
                </h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('material-calculations.log') }}" class="btn-cancel" style="border: 1px solid #64748b; background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="{{ route('material-calculations.edit', $materialCalculation) }}" class="btn-action" style="background-color: #f59e0b; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <button type="button" class="btn-action" onclick="window.print()" style="background-color: #0ea5e9; color: white; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; border: none;">
                    <i class="fas fa-print"></i> Print
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
    @endphp

    {{-- Header Info: Item Pekerjaan Details --}}
    <div class="container mb-3">
        <div style="color: #891313; font-weight: 700; font-size: 18px; letter-spacing: 0.3px;">
            {{ $formulaName }} - Panjang {{ number_format($materialCalculation->wall_length, 2) }} M - Tinggi {{ number_format($materialCalculation->wall_height, 2) }} M = Luas {{ number_format($materialCalculation->wall_area, 2) }} M²
        </div>
    </div>

    <div class="card" style="background: #ffffff; padding: 0; border-radius: 16px; margin: 0 auto; max-width: 100%; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06); border: 1px solid rgba(226, 232, 240, 0.6); overflow: hidden;">
        <div class="table-responsive">
            <style>
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
                    <tr class="text-nowrap">
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->brick_quantity, 0, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">Bh</td>
                        <td class="fw-bold sticky-col-3">Bata</td>
                        <td class="text-muted">{{ $brickType }}</td>
                        <td class="fw-bold">{{ $materialCalculation->brick->brand ?? '-' }}</td>
                        <td class="text-center text-nowrap">{{ ($materialCalculation->brick->dimension_length ?? 0) + 0 }} x {{ ($materialCalculation->brick->dimension_width ?? 0) + 0 }} x {{ ($materialCalculation->brick->dimension_height ?? 0) + 0 }} cm</td>
                        <td></td>
                        <td>{{ $materialCalculation->brick->store ?? '-' }}</td>
                        <td class="small text-muted">{{ $materialCalculation->brick->address ?? '-' }}</td>
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
                        <td rowspan="4" class="text-end bg-highlight align-top rowspan-cell">
                            <div class="d-flex justify-content-between w-100">
                                <span class="text-success-dark" style="font-size: 15px;">Rp</span>
                                <span class="text-success-dark" style="font-size: 15px;">{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td rowspan="4" class="text-end bg-highlight align-top rowspan-cell">
                            <div class="d-flex justify-content-between w-100">
                                <span class="text-primary-dark" style="font-size: 14px;">Rp</span>
                                <span class="text-primary-dark" style="font-size: 14px;">{{ number_format($costPerM2, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td rowspan="4" class="bg-highlight align-top text-muted fw-bold text-start ps-1 rowspan-cell" style="max-width: 30px">/ M2</td>
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted ps-1">/ bh</td>
                    </tr>

                    {{-- ROW 2: SEMEN --}}
                    <tr>
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->cement_quantity_sak, 2, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">Sak</td>
                        <td class="fw-bold sticky-col-3">Semen</td>
                        <td class="text-muted">{{ $materialCalculation->cement->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->cement->brand ?? '-' }}</td>
                        <td>{{ $materialCalculation->cement->color ?? '-' }}</td>
                        <td class="text-start text-nowrap fw-bold">{{ ($materialCalculation->cement->package_weight_net ?? 0) + 0 }} Kg</td>
                        <td>{{ $materialCalculation->cement->store ?? '-' }}</td>
                        <td class="small text-muted">{{ $materialCalculation->cement->address ?? '-' }}</td>
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
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->cement->package_unit ?? 'Sak' }}</td>
                    </tr>

                    {{-- ROW 3: PASIR --}}
                    <tr>
                        <td class="text-end fw-bold sticky-col-1">{{ number_format($materialCalculation->sand_m3, 3, ',', '.') }}</td>
                        <td class="text-center sticky-col-2">M3</td>
                        <td class="fw-bold sticky-col-3">Pasir</td>
                        <td class="text-muted">{{ $materialCalculation->sand->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->sand->brand ?? '-' }}</td>
                        <td>{{ $materialCalculation->sand->sand_name ?? '-' }}</td>
                        <td class="text-start text-nowrap fw-bold">{{ $materialCalculation->sand->package_volume ? ($materialCalculation->sand->package_volume + 0) . ' M3' : '-' }}</td>
                        <td>{{ $materialCalculation->sand->store ?? '-' }}</td>
                        <td class="small text-muted">{{ $materialCalculation->sand->address ?? '-' }}</td>
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
                        <td class="text-nowrap">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-nowrap ps-1">/ {{ $materialCalculation->sand->package_unit ?? 'M3' }}</td>
                    </tr>

                    {{-- ROW 4: AIR --}}
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
                        <td class="text-center text-muted">-</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
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
