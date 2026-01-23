@extends('layouts.app')

@section('title', 'Database Toko')

@section('content')
<div class="page-content stores-page">
    <div class="container-fluid py-4">
        <!-- Single Row Search & Action Bar -->
        <form action="{{ route('stores.index') }}" method="GET" class="w-100 mb-4" data-search-manual="true">
            <div class="d-flex align-items-center gap-2 w-100 flex-wrap flex-md-nowrap">
                <!-- Search Input -->
                <div class="position-relative flex-grow-1 w-100 w-md-auto">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted small" style="z-index: 10;"></i>
                    <input type="text" name="search" 
                        class="form-control py-2 ps-5 fs-6" 
                        placeholder="Cari nama toko, alamat, atau kota..." 
                        value="{{ request('search') }}">
                </div>

                <!-- Search Button -->
                <button type="submit" class="btn btn-primary-glossy py-2 px-4 rounded-2 btn-sm text-nowrap">
                    Cari
                </button>

                <!-- Add Store Button -->
                <a href="{{ route('stores.create') }}" 
                class="btn btn-primary-glossy py-2 px-4 rounded-2 btn-sm text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Toko
                </a>
            </div>
        </form>

        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3 py-2 px-3 small" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                    <span class="fw-medium">{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close small p-2" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3 py-2 px-3 small" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                    <span class="fw-medium">{{ session('error') }}</span>
                </div>
                <button type="button" class="btn-close small p-2" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Table Layout -->
        <div class="stores-table-wrapper">
            <div class="table-container text-nowrap">
                <table>
                    <thead class="single-header">
                            <tr>
                                <th>Nama Toko</th>
                                <th>Alamat</th>
                                <th>Kota</th>
                                <th>Provinsi</th>
                                <th>No Telp</th>
                                <th>Nama PIC</th>
                                <th class="text-center">Material</th>
                                <th class="text-center">Cabang</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stores as $store)
                                <tr class="store-row">
                                    <td>
                                        <span class="fw-semibold text-dark">{{ $store->name }}</span>
                                    </td>
                                    @if($store->locations->isNotEmpty())
                                        @php $mainLoc = $store->locations->first(); @endphp
                                        <td class="store-scroll-td">
                                            <span class="store-scroll-cell">{{ $mainLoc->address ?? '-' }}</span>
                                        </td>
                                        <td>{{ $mainLoc->city ?? '-' }}</td>
                                        <td>{{ $mainLoc->province ?? '-' }}</td>
                                        <td>{{ $mainLoc->contact_phone ?? '-' }}</td>
                                        <td>{{ $mainLoc->contact_name ?? '-' }}</td>
                                    @else
                                        <td colspan="5"><span class="text-muted fst-italic">Belum ada lokasi</span></td>
                                    @endif
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 fw-medium">
                                            {{ $store->locations->sum('material_availabilities_count') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">
                                            {{ $store->locations->count() }}
                                        </span>
                                    </td>
                                    <td class="text-center action-cell" onclick="event.stopPropagation()">
                                        <div class="btn-group-compact">
                                            <a href="{{ route('stores.show', $store) }}" class="btn btn-primary-glossy btn-action" data-bs-toggle="tooltip" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-action" data-bs-toggle="tooltip" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('stores.destroy', $store) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" onclick="if(confirm('Hapus toko {{ $store->name }}?')) this.form.submit()" class="btn btn-danger btn-action" data-bs-toggle="tooltip" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <div class="bg-light rounded-circle p-3 mb-3">
                                                <i class="bi bi-shop fs-3 text-muted"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark">Belum Ada Toko</h6>
                                            <p class="text-muted small mb-3">Tambahkan toko pertama Anda untuk memulai.</p>
                                            <a href="{{ route('stores.create') }}" class="btn btn-primary-glossy btn-sm px-3">
                                                <i class="bi bi-plus-lg me-1"></i>Tambah
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</div>

<style>
    html, body {
        overflow-y: hidden !important;
    }

    .stores-page,
    .stores-page .container-fluid {
        height: calc(100vh - 70px);
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper {
        flex-grow: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper .card {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper .table-container {
        overflow-y: auto;
        flex-grow: 1;
        box-shadow: none !important;
        margin-top: 0 !important;
    }

    /* ========== TABLE STYLING (IDENTICAL TO MATERIALS) ========== */
    .table-container {
        position: relative;
    }

    .table-container table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
    }

    /* Single-header styling - COMPACT 40px */
    .table-container thead.single-header th {
        height: 40px !important;
        padding: 8px 12px !important;
        box-sizing: border-box;
    }

    .table-container thead {
        position: sticky;
        top: 0;
        z-index: 10;
        height: 40px !important;
    }

    .table-container thead th {
        background-color: #f8fafc;
        font-weight: 600;
        letter-spacing: 0.05em;
        color: #64748b;
        font-size: 12px;
        border: 1px solid #cbd5e1 !important;
        vertical-align: top !important;
        z-index: 20;
    }

    .table-container tbody td {
        border: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
        color: #1e293b !important;
        text-shadow: none !important;
        -webkit-text-stroke: 0 !important;
        /* Compact styles - identical to materials */
        height: 35px !important;
        padding: 2px 8px !important;
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
        max-width: 90px !important;
        text-align: center !important;
    }

    /* Store scroll cells (for long addresses) */
    .store-scroll-td {
        position: relative;
        overflow: hidden;
        max-width: 200px;
    }
    .store-scroll-td.is-scrollable::after {
        content: '...';
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 600;
        color: rgba(15, 23, 42, 0.85);
        background: linear-gradient(90deg, rgba(248, 250, 252, 0) 0%, rgba(248, 250, 252, 0.95) 40%, rgba(248, 250, 252, 1) 100%);
        padding-left: 8px;
        pointer-events: none;
    }
    .store-scroll-td.is-scrolled-end::after {
        opacity: 0;
    }
    .store-scroll-cell {
        display: block;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        scrollbar-color: transparent transparent;
        white-space: nowrap;
    }
    .store-scroll-cell::-webkit-scrollbar {
        height: 0;
    }

    /* ========== ACTION BUTTONS (IDENTICAL TO MATERIALS) ========== */
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
        -webkit-text-stroke: 0 !important;
        text-shadow: none !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action:hover {
        background: transparent !important;
        box-shadow: none !important;
    }
    .btn-group-compact .btn-action {
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action.btn-primary-glossy {
        color: #0f172a !important;
    }
    .btn-group-compact .btn-action.btn-warning {
        color: #b45309 !important;
    }
    .btn-group-compact .btn-action.btn-danger {
        color: #b91c1c !important;
    }
    .btn-group-compact .btn-action i::before {
        -webkit-text-stroke: 0 !important;
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
        border-left: 1px solid rgba(255, 255, 255, 0.35);
    }

    /* ========== MISC ========== */
    .store-row {
        transition: background-color 0.15s ease;
    }

    .badge {
        font-size: 11px !important;
        padding: 0.25em 0.6em;
        font-weight: 500;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .btn-light, .btn-white {
        color: #1e293b !important;
    }
</style>

<script>
// Scroll indicator for address cells
(function() {
    function updateStoreScrollIndicators() {
        const cells = document.querySelectorAll('.store-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.store-scroll-cell');
            if (!scroller) return;
            const isScrollable = scroller.scrollWidth > scroller.clientWidth + 1;
            td.classList.toggle('is-scrollable', isScrollable);
            const atEnd = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 1;
            td.classList.toggle('is-scrolled-end', isScrollable && atEnd);
        });
    }

    function bindStoreScrollHandlers() {
        const cells = document.querySelectorAll('.store-scroll-td');
        cells.forEach(td => {
            const scroller = td.querySelector('.store-scroll-cell');
            if (!scroller || scroller.__storeScrollBound) return;
            scroller.__storeScrollBound = true;
            scroller.addEventListener('scroll', updateStoreScrollIndicators, { passive: true });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateStoreScrollIndicators();
        bindStoreScrollHandlers();
        requestAnimationFrame(updateStoreScrollIndicators);
        setTimeout(updateStoreScrollIndicators, 60);
    });
    window.addEventListener('resize', function() {
        updateStoreScrollIndicators();
        bindStoreScrollHandlers();
    });
    window.addEventListener('load', updateStoreScrollIndicators);
})();
</script>
@endsection