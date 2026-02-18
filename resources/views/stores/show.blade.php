@extends('layouts.app')

@section('title', $store->name)

@section('content')
<div class="page-content">
    <div class="container-fluid py-4">
        <!-- Store Header Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Identity -->
                    <div class="col-md-8 position-relative" style="z-index: 2;">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                            <!-- Logo functionality removed -->
                            <div>
                                <h1 class="fw-bold text-dark mb-2">{{ $store->name }}</h1>
                                <div class="d-flex flex-wrap gap-3 text-secondary">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-light rounded-circle text-primary">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </div>
                                        <span class="text-dark">{{ $store->locations->count() }} Lokasi Terdaftar</span>
                                    </div>
                                    <div class="vr d-none d-md-block opacity-25"></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="icon-box-sm bg-light rounded-circle text-muted">
                                            <i class="bi bi-clock"></i>
                                        </div>
                                        <span class="text-secondary">Update terakhir {{ $store->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="col-md-4 text-md-end position-relative" style="z-index: 2;">
                        <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                            <div class="d-flex align-items-center gap-3">   
                                <a href="{{ route('stores.index') }}" class="btn btn-secondary-glossy btn-lg px-4 fs-6">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                            <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-lg px-4 fs-6 text-white fw-medium global-open-modal">
                                <i class="bi bi-pencil me-2 text-white"></i>Edit
                            </a>
                            <a href="{{ route('store-locations.create', $store) }}" class="btn btn-primary-glossy btn-lg px-4 fs-6 global-open-modal">
                                <i class="bi bi-plus-lg me-2"></i>Tambah Lokasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Decorative overlay (Fixed: Smaller & Non-overlapping) -->
            <div class="position-absolute bottom-0 end-0 p-4 opacity-10 pe-none d-none d-md-block" style="transform: rotate(-15deg) translate(20px, 20px);">
                <i class="bi bi-shop display-1 text-secondary" style="font-size: 8rem;"></i>
            </div>
        </div>

        <!-- Locations Content -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-bold text-dark mb-0">Daftar Cabang & Lokasi</h4>
        </div>

        @php
            $hasIncompleteLocation = $store->locations->contains('is_incomplete', true);
        @endphp

        @if($hasIncompleteLocation)
            <div class="alert alert-warning small p-2 mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Tanda <i class="bi bi-exclamation-triangle-fill text-warning"></i> menunjukkan data lokasi belum lengkap (contoh: kota, provinsi, atau telepon).
                </div>
            </div>
        @endif

        <div class="table-container text-nowrap">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>Alamat</th>
                        <th>Kecamatan</th>
                        <th>Kota</th>
                        <th>Provinsi</th>
                        <th style="text-align: right;">Nama Kontak</th>
                        <th>No. Telepon</th>
                        <th class="text-center">Material</th>
                        <th style="width: 60px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($store->locations as $location)
                        <tr>
                            <td style="text-align: center;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>{{ $loop->iteration }}</span>
                                    @if($location->is_incomplete)
                                        <i class="bi bi-exclamation-triangle text-warning" data-bs-toggle="tooltip" title="Data lokasi ini belum lengkap"></i>
                                    @endif
                                </div>
                            </td>
                            <td class="text-wrap" style="min-width: 250px; white-space: normal;">{{ $location->address ?? '-' }}</td>
                            <td>{{ $location->district ?? '-' }}</td>
                            <td>{{ $location->city ?? '-' }}</td>
                            <td>{{ $location->province ?? '-' }}</td>
                            <td style="text-align: right;">{{ $location->contact_name ?? '-' }}</td>
                            <td>{{ $location->contact_phone ?? '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('store-locations.materials', [$store, $location]) }}" class="text-decoration-none text-primary fw-medium hover-arrow" style="font-size: 13px;">
                                    {{ $location->material_availabilities_count }} Material <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                            <td class="text-center action-cell">
                                <div class="btn-group-compact">
                                    <a href="{{ route('store-locations.edit', [$store, $location]) }}" class="btn btn-warning btn-action global-open-modal" data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('store-locations.destroy', [$store, $location]) }}" method="POST" class="d-inline"
                                        data-confirm="Apakah Anda yakin ingin menghapus lokasi ini?"
                                        data-confirm-title="Hapus Lokasi"
                                        data-confirm-type="danger"
                                        data-confirm-ok="Ya, Hapus"
                                        data-confirm-cancel="Batal">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action btn-danger" data-bs-toggle="tooltip" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="text-center py-5">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                        <i class="bi bi-geo-alt display-6 text-muted opacity-50"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark">Belum Ada Lokasi</h4>
                                    <p class="text-muted mb-4 mw-md mx-auto" style="max-width: 450px;">
                                        Toko ini belum memiliki cabang atau lokasi gudang yang terdaftar.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Table Styles from materials/index.blade.php */
    .table-container {
        position: relative;
        overflow-x: auto;
        border-radius: 12px;
        box-shadow: none;
        border: 1px solid #e2e8f0;
    }

    .table-container table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
    }

    /* Header Styling */
    .table-container thead th {
        height: 40px !important;
        padding: 8px 12px !important;
        background-color: #f8fafc;
        font-weight: 600;
        letter-spacing: 0.05em;
        color: #64748b;
        font-size: 12px;
        border: 1px solid #cbd5e1 !important;
        vertical-align: middle !important;
        white-space: nowrap;
    }

    /* Body Styling */
    .table-container tbody td {
        border: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
        color: #1e293b !important;
        height: 35px !important;
        padding: 4px 12px !important;
        font-size: 12px !important;
        line-height: 1.3 !important;
    }

    .table-container tbody tr:hover {
        background-color: #fcfcfc;
    }

    /* Force Aksi column width */
    .table-container thead th:last-child,
    .table-container tbody td:last-child {
        width: 90px !important;
        min-width: 90px !important;
        text-align: center !important;
    }

    /* Button Group Compact */
    .btn-group-compact {
        display: inline-flex;
        align-items: center;
        border-radius: 0;
        overflow: visible;
        box-shadow: none;
        background: transparent;
    }
    .btn-group-compact .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 22px;
        width: 26px;
        padding: 0;
        margin: 0;
        border-radius: 0 !important;
        font-size: 12px;
        line-height: 1;
        font-weight: normal !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action:hover {
        background: transparent !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action.btn-warning {
        color: #b45309 !important;
    }
    .btn-group-compact .btn-action.btn-danger {
        color: #b91c1c !important;
    }
    .btn-group-compact .btn-action:first-child {
        border-top-left-radius: 999px !important;
        border-bottom-left-radius: 999px !important;
    }
    .btn-group-compact .btn-action:last-child {
        border-top-right-radius: 999px !important;
        border-bottom-right-radius: 999px !important;
    }
    .btn-group-compact .btn-action + .btn-action {
        border-left: 1px solid rgba(0, 0, 0, 0.1);
    }

    .hover-arrow {
        transition: transform 0.2s ease;
        display: inline-block;
    }
    .hover-arrow:hover {
        transform: translateX(3px);
    }

</style>
@endpush
@endsection
