@extends('layouts.app')

@section('title', 'Database Toko')

@section('content')
<div class="page-content stores-page">
    <div class="container-fluid pt-1 pb-4">
        <!-- Single Row Search & Action Bar -->
        <form action="{{ route('stores.index') }}" method="GET" class="w-100 mb-3 mt-0" data-search-manual="true">
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
                    <i class="bi bi-search me-1"></i>Cari
                </button>

                @if(request()->filled('search'))
                    <a href="{{ route('stores.index') }}" class="btn btn-secondary-glossy py-2 px-4 rounded-2 btn-sm text-nowrap">
                        <i class="bi bi-x-lg me-1"></i> Reset
                    </a>
                @endif

                <!-- Add Store Button -->
                <a href="{{ route('stores.create') }}" 
                class="btn btn-primary-glossy py-2 px-4 rounded-2 btn-sm text-nowrap global-open-modal">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Toko
                </a>
            </div>
        </form>

        <!-- Table Layout -->
        @php
            $storeLocationPoints = $stores
                ->flatMap(function ($store) {
                    return $store->locations->map(function ($location) use ($store) {
                        return [
                            'store_name' => (string) $store->name,
                            'address' => trim((string) ($location->formatted_address ?: $location->address ?: '-')),
                            'city' => trim((string) ($location->city ?? '')),
                            'province' => trim((string) ($location->province ?? '')),
                            'latitude' => is_numeric($location->latitude) ? (float) $location->latitude : null,
                            'longitude' => is_numeric($location->longitude) ? (float) $location->longitude : null,
                            'service_radius_km' => is_numeric($location->service_radius_km) ? (float) $location->service_radius_km : null,
                        ];
                    });
                })
                ->filter(fn($point) => is_numeric($point['latitude']) && is_numeric($point['longitude']))
                ->values();
        @endphp

        <div class="stores-map-card card border-0 shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 fw-bold text-dark">Lokasi Semua Toko</h6>
                </div>

                @if ($storeLocationPoints->isNotEmpty())
                    <div id="storesIndexLocationsMap"
                        class="stores-index-map"
                        data-google-maps-api-key="{{ config('services.google.maps_api_key') }}"
                        data-store-label-min-zoom="12"
                        data-store-marker-icon="{{ asset('images/store-marker.svg') }}"></div>
                @else
                    <div class="alert alert-light border mb-0 py-2 px-3 small text-muted">
                        Belum ada lokasi toko yang memiliki koordinat.
                    </div>
                @endif
            </div>
        </div>

        <div class="stores-table-wrapper">
            <div class="table-container text-nowrap">
                <table>
                    <thead class="single-header">
                            <tr>
                                <th style="text-align: center; width: 40px; min-width: 40px;">No</th>
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
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td class="store-name-td">
                                        <span class="store-name-cell fw-semibold text-dark" title="{{ $store->name }}">{{ $store->name }}</span>
                                    </td>
                                    @if($store->locations->isNotEmpty())
                                        @php $mainLoc = $store->locations->first(); @endphp
                                        <td class="store-scroll-td">
                                            <span class="store-scroll-cell" title="{{ $mainLoc->address ?? '-' }}">{{ $mainLoc->address ?? '-' }}</span>
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
                                    <td class="text-center action-cell">
                                        <div class="btn-group-compact">
                                            <a href="{{ route('stores.show', $store) }}" class="btn btn-primary-glossy btn-action" data-bs-toggle="tooltip" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-action global-open-modal" data-bs-toggle="tooltip" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('stores.destroy', $store) }}" method="POST" class="d-inline"
                                                data-confirm="Apakah Anda yakin ingin menghapus toko {{ $store->name }}? Data yang dihapus tidak dapat dikembalikan."
                                                data-confirm-title="Hapus Toko"
                                                data-confirm-type="danger"
                                                data-confirm-ok="Ya, Hapus"
                                                data-confirm-cancel="Batal">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-action" data-bs-toggle="tooltip" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
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

    .stores-map-card {
        flex: 0 0 auto;
    }

    .stores-index-map {
        width: 100%;
        height: 230px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
    }

    .stores-map-legend {
        font-size: 12px;
        color: #64748b;
        display: inline-flex;
        align-items: center;
    }

    .stores-table-wrapper .card {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .stores-table-wrapper .table-container {
        overflow-y: auto;
        overflow-x: hidden;
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
        table-layout: auto !important;
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

    .table-container tbody td.store-name-td {
        height: auto !important;
        min-width: 180px;
        max-width: 320px;
    }

    .store-name-cell {
        display: block;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.25;
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
        width: clamp(240px, 30vw, 420px);
        max-width: clamp(240px, 30vw, 420px);
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
        cursor: ew-resize;
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
    /* Keep table rows as native table layout so tbody aligns with thead */
    .table-container tbody tr,
    .table-container .store-row {
        display: table-row !important;
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
            // Allow normal mouse wheel to pan horizontally inside the address cell.
            scroller.addEventListener('wheel', function(e) {
                const delta = Math.abs(e.deltaX) > 0 ? e.deltaX : e.deltaY;
                if (!delta) return;
                scroller.scrollLeft += delta;
                e.preventDefault();
            }, { passive: false });
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

document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('storesIndexLocationsMap');
    if (!mapEl) return;

    const points = @json($storeLocationPoints);
    if (!Array.isArray(points) || points.length === 0) return;

    const apiKey = mapEl.dataset.googleMapsApiKey || '';
    if (!window.GoogleMapsPicker || typeof window.GoogleMapsPicker.loadApi !== 'function') {
        console.warn('GoogleMapsPicker helper is not available for stores index map.');
        return;
    }

    const ensureStoreMarkerLabelStyle = function() {
        if (document.getElementById('stores-index-marker-label-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'stores-index-marker-label-style';
        style.textContent = `
            .stores-index-marker-label-overlay {
                position: absolute;
                transform: translate3d(18px, -31px, 0);
                pointer-events: none;
                will-change: transform, left, top;
            }
            .stores-index-marker-label {
                display: inline-block;
                color: #0f172a;
                font-size: 12px;
                font-weight: 600;
                line-height: 1.15;
                white-space: nowrap;
                letter-spacing: 0.05px;
                text-shadow:
                    -1px -1px 0 #ffffff,
                    1px -1px 0 #ffffff,
                    -1px 1px 0 #ffffff,
                    1px 1px 0 #ffffff,
                    0 0 2px rgba(255, 255, 255, 0.95),
                    0 1px 2px rgba(15, 23, 42, 0.2);
            }
        `;
        document.head.appendChild(style);
    };

    const buildStoreMarkerLabelText = function(name) {
        const text = String(name || '').trim();
        if (!text) return 'Toko';
        return text.length <= 26 ? text : `${text.slice(0, 25)}...`;
    };

    const createStoreIcon = function() {
        const iconUrl = mapEl.dataset.storeMarkerIcon || '/images/store-marker.svg';
        return {
            url: iconUrl,
            scaledSize: new google.maps.Size(30, 30),
            anchor: new google.maps.Point(15, 30),
        };
    };

    const escapeHtml = function(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const buildStoreInfoContent = function(point) {
        const addressText = point.address ? escapeHtml(point.address) : '-';
        const cityProvinceText = [point.city, point.province].filter(Boolean).map(escapeHtml).join(', ');
        const radiusText = Number.isFinite(Number(point.service_radius_km))
            ? `<div style="font-size:12px;color:#475569;">Radius layanan: ${escapeHtml(point.service_radius_km)} km</div>`
            : '';

        return `
            <div style="min-width:220px;line-height:1.45;">
                <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">${escapeHtml(point.store_name || '-')}</div>
                <div style="font-size:12px;color:#64748b;">${addressText}</div>
                ${cityProvinceText ? `<div style="font-size:12px;color:#64748b;">${cityProvinceText}</div>` : ''}
                ${radiusText}
            </div>
        `;
    };

    const createStoreNameOverlay = function(map, position, storeName, minZoom) {
        if (!window.google?.maps || typeof window.google.maps.OverlayView !== 'function') {
            return null;
        }

        const latLng = new google.maps.LatLng(position.lat, position.lng);
        const labelText = buildStoreMarkerLabelText(storeName);

        class StoreNameOverlay extends google.maps.OverlayView {
            constructor() {
                super();
                this.containerEl = null;
            }

            onAdd() {
                const container = document.createElement('div');
                container.className = 'stores-index-marker-label-overlay';

                const label = document.createElement('span');
                label.className = 'stores-index-marker-label';
                label.textContent = labelText;
                container.appendChild(label);

                this.containerEl = container;
                const panes = this.getPanes();
                if (panes?.overlayLayer) {
                    panes.overlayLayer.appendChild(container);
                }
            }

            draw() {
                if (!this.containerEl) return;

                const currentZoom = typeof map.getZoom === 'function' ? Number(map.getZoom()) : NaN;
                const hiddenByZoom = Number.isFinite(currentZoom) && currentZoom < minZoom;
                this.containerEl.style.display = hiddenByZoom ? 'none' : 'block';
                if (hiddenByZoom) return;

                const projection = this.getProjection();
                if (!projection) return;

                const pixel = projection.fromLatLngToDivPixel(latLng);
                if (!pixel) return;

                this.containerEl.style.left = `${Math.round(pixel.x)}px`;
                this.containerEl.style.top = `${Math.round(pixel.y)}px`;
            }

            onRemove() {
                if (this.containerEl?.parentNode) {
                    this.containerEl.parentNode.removeChild(this.containerEl);
                }
                this.containerEl = null;
            }
        }

        const overlay = new StoreNameOverlay();
        overlay.setMap(map);
        return overlay;
    };

    window.GoogleMapsPicker.loadApi(apiKey)
        .then(function() {
            if (!window.google?.maps) return;

            ensureStoreMarkerLabelStyle();

            const map = new google.maps.Map(mapEl, {
                center: { lat: Number(points[0].latitude), lng: Number(points[0].longitude) },
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                gestureHandling: 'greedy',
                scrollwheel: true,
            });

            const bounds = new google.maps.LatLngBounds();
            const infoWindow = new google.maps.InfoWindow();
            const icon = createStoreIcon();
            const markerNameOverlays = [];
            const parsedLabelMinZoom = Number(mapEl.dataset.storeLabelMinZoom);
            const storeLabelMinZoom = Number.isFinite(parsedLabelMinZoom) ? parsedLabelMinZoom : 12;
            const activeRadiusCircle = new google.maps.Circle({
                map,
                radius: 0,
                strokeColor: '#2563eb',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.08,
                clickable: false,
                visible: false,
            });

            points.forEach(function(point) {
                const lat = Number(point.latitude);
                const lng = Number(point.longitude);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                const position = { lat, lng };
                bounds.extend(position);

                const marker = new google.maps.Marker({
                    map,
                    position,
                    title: point.store_name || 'Toko',
                    icon,
                    zIndex: 10,
                });

                const nameOverlay = createStoreNameOverlay(map, position, point.store_name, storeLabelMinZoom);
                if (nameOverlay) {
                    markerNameOverlays.push(nameOverlay);
                }

                marker.addListener('click', function() {
                    infoWindow.setContent(buildStoreInfoContent(point));
                    infoWindow.open(map, marker);

                    const radiusKm = Number(point.service_radius_km);
                    if (Number.isFinite(radiusKm) && radiusKm > 0) {
                        activeRadiusCircle.setCenter(position);
                        activeRadiusCircle.setRadius(radiusKm * 1000);
                        activeRadiusCircle.setVisible(true);
                    } else {
                        activeRadiusCircle.setVisible(false);
                    }
                });
            });

            if (markerNameOverlays.length > 0 && typeof map.addListener === 'function') {
                map.addListener('zoom_changed', function() {
                    markerNameOverlays.forEach(function(overlay) {
                        if (overlay && typeof overlay.draw === 'function') {
                            overlay.draw();
                        }
                    });
                });
            }

            map.addListener('click', function() {
                infoWindow.close();
                activeRadiusCircle.setVisible(false);
            });

            if (points.length === 1) {
                map.setCenter(bounds.getCenter());
                map.setZoom(14);
            } else {
                map.fitBounds(bounds, 70);
                google.maps.event.addListenerOnce(map, 'bounds_changed', function() {
                    if (map.getZoom() > 14) {
                        map.setZoom(14);
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Failed to initialize stores index map:', error);
        });
});
</script>

@endsection
