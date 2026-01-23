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
                            <a href="{{ route('stores.edit', $store) }}" class="btn btn-light border btn-lg px-4 fs-6 text-dark fw-medium">
                                <i class="bi bi-pencil me-2 text-muted"></i>Edit
                            </a>
                            <a href="{{ route('store-locations.create', $store) }}" class="btn btn-primary-glossy btn-lg px-4 fs-6">
                                <i class="bi bi-plus-lg me-2"></i>Tambah Lokasi
                            </a>
                            <a href="{{ route('stores.index') }}" class="btn btn-light btn-sm shadow-sm border">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
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

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4 py-3" role="alert">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-white bg-opacity-25 rounded-circle me-3 p-1">
                        <i class="bi bi-check-lg fs-5"></i>
                    </div>
                    <span class="fw-medium">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close p-3" data-bs-dismiss="alert"></button>
            </div>
        @endif

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
                                <div class="d-flex flex-column align-items-center justify-content-center gap-1">
                                    <span class="fw-bold">{{ $location->material_availabilities_count }} material</span>
                                    <a href="{{ route('store-locations.materials', [$store, $location]) }}" class="text-decoration-none small text-primary fw-medium hover-arrow">
                                        Lihat Material <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="text-center action-cell">
                                <div class="btn-group-compact">
                                    <a href="{{ route('store-locations.edit', [$store, $location]) }}" class="btn btn-warning btn-action" data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('store-locations.destroy', [$store, $location]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus lokasi ini?');" class="d-inline">
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
    /* Styles adapted from materials/index.blade.php */
    .table-container {
        position: relative;
        overflow-x: auto;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }
    .table-container table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
    }
    .table-container thead th {
        border-top: 0;
        border-bottom: 1px solid #dee2e6 !important;
        background: #f8f9fa;
        padding: 0.75rem 1rem !important;
        vertical-align: middle !important;
        font-size: 11px !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
    }
    .table-container tbody td {
        border-top: 1px solid #f1f5f9 !important;
        padding: 0.5rem 1rem !important; /* Smaller padding */
        vertical-align: middle !important;
        font-size: 13px;
    }
    .table-container tbody tr:hover {
        background-color: #f8f9fa;
    }
    .btn-group-compact {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
  }
  .btn-group-compact .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 28px;
      width: 30px;
      padding: 0;
      margin: 0;
      background: transparent;
      border: none;
      font-size: 13px;
      line-height: 1;
      color: #64748b;
  }
  .btn-group-compact .btn-action:hover {
      background: #f1f5f9;
      color: #0f172a;
  }
  .btn-group-compact .btn-action + .btn-action {
      border-left: 1px solid #e2e8f0;
  }
  .btn-light, .btn-white {
      color: #1e293b !important;
  }
  .btn-outline-primary {
      color: var(--bs-primary) !important;
  }
  .btn-outline-primary:hover {
      color: #fff !important;
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
