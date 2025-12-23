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
                <p class="text-muted mb-0" style="font-size: 14px;">
                    {{ $materialCalculation->project_name ?: 'Perhitungan Tanpa Nama' }}
                    <span class="badge ms-2" style="background: linear-gradient(135deg, #891313 0%, #a61515 100%); color: #ffffff; padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 12px;">
                        Luas: {{ number_format($materialCalculation->wall_area, 2) }} m²
                    </span>
                </p>
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
            </style>

            <table class="table-preview">
                <thead class="align-top">
                    <tr>
                        <th colspan="3">Item Pekerjaan</th>
                        <th>Qty / Pekerjaan</th>
                        <th>Satuan</th>
                        <th>Material</th>
                        <th colspan="7">Detail</th>
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
                    @php
                        $costPerM2 = $materialCalculation->wall_area > 0 ? $materialCalculation->total_material_cost / $materialCalculation->wall_area : 0;
                        
                        // Retrieve dynamic Formula Name
                        $params = $materialCalculation->calculation_params ?? [];
                        $workType = $params['work_type'] ?? 'brick_half';
                        $formulaInstance = \App\Services\FormulaRegistry::instance($workType);
                        $formulaName = $formulaInstance ? $formulaInstance::getName() : 'Pekerjaan Dinding';
                        
                        $brickType = $materialCalculation->brick ? $materialCalculation->brick->type : 'Merah';
                    @endphp

                    {{-- ROW 1: BATA --}}
                    <tr class="text-nowrap">
                        {{-- Merged Project Info --}}
                        <td colspan="3" class="text-start align-middle fw-bold" style="font-size: 13px; color: #0f172a;">
                            {{ $formulaName }}
                        </td>

                        {{-- Bata Data --}}
                        <td class="text-end fw-bold">{{ number_format($materialCalculation->brick_quantity, 0, ',', '.') }}</td>
                        <td class="text-start">Bh</td>
                        <td class="fw-bold">Bata</td>
                        <td class="text-muted">{{ $brickType }}</td>
                        <td class="fw-bold">{{ $materialCalculation->brick->brand ?? '-' }}</td>
                        <td class="text-end px-1">{{ ($materialCalculation->brick->dimension_length ?? 0) + 0 }} cm</td>
                        <td class="text-center text-muted px-1">x</td>
                        <td class="px-1">{{ ($materialCalculation->brick->dimension_width ?? 0) + 0 }} cm</td>
                        <td class="text-center text-muted px-1">x</td>
                        <td class="px-1">{{ ($materialCalculation->brick->dimension_height ?? 0) + 0 }} cm</td>
                        <td class="">{{ $materialCalculation->brick->store ?? '-' }}</td>
                        <td class=" text-truncate" style="max-width: 200px">{{ $materialCalculation->brick->address ?? '-' }}</td>
                        <td class="text-end fw-bold pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ Bh</td>
                        <td class="text-end]">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        
                        {{-- Totals Merged --}}
                        <td rowspan="4" class="text-end bg-highlight align-middle">
                            <span class="text-success-dark" style="font-size: 15px;">
                                <div class="d-flex justify-content-between w-100">
                                    <span>Rp</span>
                                    <span>{{ number_format($materialCalculation->total_material_cost, 0, ',', '.') }}</span>
                                </div>
                            </span>
                        </td>
                        </td>
                        <td rowspan="4" class="text-end bg-highlight align-middle">
                            <span class="text-primary-dark" style="font-size: 14px;">
                                <div class="d-flex justify-content-between w-100">
                                    <span>Rp</span>
                                    <span>{{ number_format($costPerM2, 0, ',', '.') }}</span>
                                </div>
                            </span>
                        </td>
                        <td rowspan="4" class="bg-highlight align-middle text-muted fw-bold text-start ps-0" style="max-width: 40px">/ M2</td>

                        <td class="text-end pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->brick_price_per_piece, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ Bh</td>
                    </tr>

                    {{-- ROW 2: SEMEN --}}
                    <tr class="text-nowrap">
                        <td class="text-muted ps-3" style="font-weight: 500;">Panjang</td>
                        <td colspan="2" class="text-start fw-bold">{{ number_format($materialCalculation->wall_length, 2) }} M</td>

                        <td class="text-end fw-bold">{{ number_format($materialCalculation->cement_quantity_sak, 2, ',', '.') }}</td>
                        <td class="text-start">Sak</td>
                        <td class="fw-bold">Semen</td>
                        <td class="text-muted">{{ $materialCalculation->cement->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->cement->brand ?? '-' }}</td>
                        <td colspan="3" class="px-1">{{ $materialCalculation->cement->color ?? '-' }}</td>
                        <td colspan="2" class="fw-bold px-1">{{ ($materialCalculation->cement->package_weight_net ?? 0) + 0 }} Kg</td>
                        <td>{{ $materialCalculation->cement->store ?? '-' }}</td>
                        <td class="small text-muted">{{ $materialCalculation->cement->address ?? '-' }}</td>
                        <td class="text-end fw-bold pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ {{ $materialCalculation->cement->package_unit ?? 'Sak' }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-end pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->cement_price_per_sak, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ {{ $materialCalculation->cement->package_unit ?? 'Sak' }}</td>
                    </tr>

                    {{-- ROW 3: PASIR --}}
                    <tr class="text-nowrap">
                        <td class="text-muted ps-3" style="font-weight: 500;">Tinggi</td>
                        <td colspan="2" class="text-start fw-bold">{{ number_format($materialCalculation->wall_height, 2) }} M</td>

                        <td class="text-end fw-bold">{{ number_format($materialCalculation->sand_m3, 3, ',', '.') }}</td>
                        <td class="text-start">M3</td>
                        <td class="fw-bold">Pasir</td>
                        <td class="text-muted">{{ $materialCalculation->sand->type ?? '-' }}</td>
                        <td class="fw-bold">{{ $materialCalculation->sand->brand ?? '-' }}</td>
                        <td colspan="3" class="px-1">{{ $materialCalculation->sand->sand_name ?? '-' }}</td>
                        <td colspan="2" class="px-1 fw-bold">{{ $materialCalculation->sand->package_volume ? ($materialCalculation->sand->package_volume + 0) . ' M3' : '-' }}</td>
                        <td>{{ $materialCalculation->sand->store ?? '-' }}</td>
                        <td class="small text-muted">{{ $materialCalculation->sand->address ?? '-' }}</td>
                        <td class="fw-bold pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ {{ $materialCalculation->sand->package_unit ?? 'M3' }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_total_cost, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-end pe-1">
                            <div class="d-flex justify-content-between w-100">
                                <span>Rp</span>
                                <span>{{ number_format($materialCalculation->sand_price_per_m3, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td class="text-muted px-1">/ {{ $materialCalculation->sand->package_unit ?? 'M3' }}</td>
                    </tr>

                    {{-- ROW 4: AIR --}}
                    <tr class="text-nowrap">
                        <td class="text-muted ps-3" style="font-weight: 500;">Luas</td>
                        <td colspan="2" class="text-start fw-bold" style="color: #891313;">{{ number_format($materialCalculation->wall_area, 2) }} M2</td>

                        <td class="text-end fw-bold">{{ number_format($materialCalculation->water_liters, 2, ',', '.') }}</td>
                        <td class="text-start">L</td>
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
