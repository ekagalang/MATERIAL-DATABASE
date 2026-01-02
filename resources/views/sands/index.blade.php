@extends('layouts.app')

@section('title', 'Database Pasir')

@section('content')
<div class="card">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;">
        <button
            type="button"
            class="btn btn-primary btn-sm"
            style="display: inline-flex; align-items: center; gap: 6px;"
            onclick="window.location.href='{{ route('materials.index') }}'">
            <i class="bi bi-chevron-left" style="color: #ffffff; font-size: 1.2rem;"></i>
        </button>
        <h2 style="margin: 0; flex-shrink: 0;">Database Pasir</h2>

        <form id="search-form" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                       id="search-input"
                       name="search"
                       placeholder="Cari jenis, merek, toko..."
                       style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari
            </button>
            <button type="button" id="reset-button" class="btn btn-secondary" style="display: none;">
                <i class="bi bi-x-lg"></i> Reset
            </button>
        </form>

        <a href="{{ route('sands.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Pasir
        </a>
    </div>

    <div id="table-container-wrapper" style="display: none;">
        <div class="table-container">
            <table>
                <thead>
                    <tr class="dim-group-row">
                        <th rowspan="2">No</th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="type">
                            <span>Jenis</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="brand">
                            <span>Merek</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="package_unit">
                            <span>Kemasan</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;" data-column="dimension_length">
                            <span>Dimensi Kemasan (M)</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="package_volume">
                            <span>Volume</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="store">
                            <span>Toko</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="short_address">
                            <span>Alamat Singkat</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="package_price">
                            <span>Harga Beli</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable text-nowrap" rowspan="2" data-column="comparison_price_per_m3">
                            <span>Harga Komparasi (/ M3)</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th rowspan="2" style="text-align: center">Aksi</th>
                    </tr>
                    <tr class="dim-sub-row">
                        <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">P</th>
                        <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">L</th>
                        <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">T</th>
                    </tr>
                </thead>
                <tbody id="sand-list">
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 60px;">
                            <div class="spinner-border" role="status" style="width: 32px; height: 32px; color: #94a3b8;">
    <span class="visually-hidden">Loading...</span>
</div>
                            <div style="margin-top: 16px; color: #64748b;">Memuat data...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="sand-pagination"></div>
    </div>

    <div id="empty-state-container" style="display: none;">
        <div class="empty-state">
            <div class="empty-state-icon">🏖️</div>
            <p id="empty-state-message">Belum ada data pasir</p>
            <a href="{{ route('sands.create') }}" class="btn btn-primary open-modal" style="margin-top: 16px;" id="empty-state-add-button">
                <i class="bi bi-plus-lg"></i> Tambah Data Pertama
            </a>
        </div>
    </div>
</div>

<!-- Floating Modal Container -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Form Pasir</h2>
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
    position: relative;
    overflow: hidden;
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    padding: 8px 0;
    position: relative;
    z-index: 1;
    flex: 1;
}

.floating-modal-header h2::before {
    content: '';
    position: absolute;
    left: -32px;
    right: -200px;
    top: 0;
    bottom: 0;
    background: #891313;
    z-index: -1;
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

/* Input focus styles */
input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
}

/* Sortable header styles */
th.sortable {
    cursor: pointer;
    user-select: none;
}

th.sortable:hover {
    color: #fecaca !important;
}

th.sortable:hover i {
    opacity: 1 !important;
}

th.sortable i {
    transition: opacity 0.2s ease;
}

.table-container table {
    border-collapse: collapse;
    border-spacing: 0;
}

.table-container thead th {
    background-color: #891313 !important;
    color: #ffffff !important;
    vertical-align: top !important;
    text-align: center !important;
    white-space: nowrap;
}

.table-container table td {
    vertical-align: top !important;
}

.table-container thead .dim-group-row th {
    border-bottom: 0 !important;
    padding-bottom: 6px !important;
    line-height: 1.2;
}

.table-container thead .dim-sub-row th {
    border-top: 0 !important;
    border-bottom: 0 !important;
    border-left: 0 !important;
    border-right: 0 !important;
    padding: 8px 2px 10px 2px !important;
    width: 40px;
    position: relative;
    line-height: 1.1;
    vertical-align: middle;
}

.table-container tbody td.dim-cell {
    padding: 14px 2px !important;
    width: 40px;
    border-left: 0 !important;
    border-right: 0 !important;
    position: relative;
}

/* Header 'x' separator */
.table-container thead .dim-sub-row th + th::before {
    content: 'x';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.5);
    font-size: 11px;
    pointer-events: none;
}

/* Body 'x' separator */
.table-container tbody td.dim-cell + td.dim-cell::before {
    content: 'x';
    position: absolute;
    left: -6px;
    top: 24px;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 11px;
    pointer-events: none;
}

.table-container tbody td.volume-cell {
    padding: 14px 8px !important;
    width: 90px;
}
</style>

<script src="{{ asset('js/api-helper.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State management
    let currentPage = 1;
    let currentSearch = '';
    let currentSortBy = null;
    let currentSortDirection = null;

    // Load sands from API
    async function loadSands(page = 1, search = '', sortBy = null, sortDirection = null) {
        currentPage = page;
        currentSearch = search;
        currentSortBy = sortBy;
        currentSortDirection = sortDirection;

        // Update reset button visibility
        const resetButton = document.getElementById('reset-button');
        if (search) {
            resetButton.style.display = 'inline-block';
        } else {
            resetButton.style.display = 'none';
        }

        const params = {
            per_page: 9999 // Get all data without pagination
        };

        if (search) params.search = search;
        if (sortBy) {
            params.sort_by = sortBy;
            params.sort_direction = sortDirection || 'asc';
        }

        const response = await api.get('/sands', params);

        if (response.success && response.data.length > 0) {
            const pagination = response.meta || response.pagination;
            renderSands(response.data, pagination);
            updateSortIcons();
            showTable();
        } else {
            showEmptyState(search);
        }
    }

    // Render sands table
    function renderSands(sands, pagination) {
        const tbody = document.getElementById('sand-list');
        const html = sands.map((sand, index) => {
            const rowNumber = index + 1;

            function formatDimension(value) {
                if (!value) return '<span style="color: #cbd5e1;">-</span>';
                return parseFloat(value).toString().replace('.', ',');
            }

            function formatVolume(value) {
                if (!value) return '<span style="color: #cbd5e1;">—</span>';
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 6,
                    maximumFractionDigits: 6
                }).format(value) + ' M3';
            }

            return `
                <tr>
                    <td style="text-align: center; font-weight: 500; color: #64748b;">
                        ${rowNumber}
                    </td>
                    <td style="color: #475569;">${sand.type || '-'}</td>
                    <td style="color: #475569;">${sand.brand || '-'}</td>
                    <td>
                        <span style="display: inline-block; border-radius: 6px; font-size: 12px; font-weight: 600;">
                            ${sand.package_unit || '-'}
                        </span>
                    </td>
                    <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                        ${formatDimension(sand.dimension_length)}
                    </td>
                    <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                        ${formatDimension(sand.dimension_width)}
                    </td>
                    <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                        ${formatDimension(sand.dimension_height)}
                    </td>
                    <td class="volume-cell" style="text-align: right; color: #475569; font-size: 12px;">
                        ${formatVolume(sand.package_volume)}
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                            ${sand.store || '-'}
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 12px; line-height: 1.5;">
                        ${sand.short_address || '-'}
                    </td>
                    <td>
                        ${sand.package_price ? `
                            <div style="display: flex; width: 100%; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Rp</span>
                                <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                    ${api.formatRupiah(sand.package_price)}
                                </span>
                            </div>
                        ` : '<span style="color: #cbd5e1;">—</span>'}
                    </td>
                    <td>
                        ${sand.comparison_price_per_m3 ? `
                            <div style="display: flex; width: 100%; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Rp</span>
                                <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                    ${api.formatRupiah(sand.comparison_price_per_m3)}
                                </span>
                            </div>
                        ` : '<span style="color: #cbd5e1;">—</span>'}
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/sands/${sand.id}"
                               class="btn btn-primary btn-sm open-modal"
                               title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="/sands/${sand.id}/edit"
                               class="btn btn-warning btn-sm open-modal"
                               title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <button type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="deleteSand(${sand.id})"
                                    title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = html;

        // Re-attach modal handlers for new buttons
        attachModalHandlers();
    }

    // Render pagination
    function renderPagination(pagination) {
        const container = document.getElementById('sand-pagination');

        // Hide pagination if only 1 page or no data
        if (!pagination || pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">';

        // Previous button
        if (pagination.current_page > 1) {
            html += `<button onclick="loadSands(${pagination.current_page - 1}, '${currentSearch}', '${currentSortBy}', '${currentSortDirection}')" class="btn btn-secondary btn-sm">← Sebelumnya</button>`;
        } else {
            html += `<button class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">← Sebelumnya</button>`;
        }

        // Page info
        html += `<span style="color: #64748b; font-size: 14px;">Halaman ${pagination.current_page} dari ${pagination.last_page}</span>`;

        // Next button
        if (pagination.current_page < pagination.last_page) {
            html += `<button onclick="loadSands(${pagination.current_page + 1}, '${currentSearch}', '${currentSortBy}', '${currentSortDirection}')" class="btn btn-secondary btn-sm">Selanjutnya →</button>`;
        } else {
            html += `<button class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">Selanjutnya →</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    // Show/hide containers
    function showTable() {
        document.getElementById('table-container-wrapper').style.display = 'block';
        document.getElementById('empty-state-container').style.display = 'none';
    }

    function showEmptyState(hasSearch) {
        document.getElementById('table-container-wrapper').style.display = 'none';
        document.getElementById('empty-state-container').style.display = 'block';

        const message = document.getElementById('empty-state-message');
        const addButton = document.getElementById('empty-state-add-button');

        if (hasSearch) {
            message.textContent = 'Tidak ada data pasir yang sesuai dengan pencarian';
            addButton.style.display = 'none';
        } else {
            message.textContent = 'Belum ada data pasir';
            addButton.style.display = 'inline-block';
        }
    }

    // Search form handler
    document.getElementById('search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = document.getElementById('search-input').value;
        loadSands(1, searchValue, currentSortBy, currentSortDirection);
    });

    // Reset button handler
    document.getElementById('reset-button').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        loadSands(1, '', currentSortBy, currentSortDirection);
    });

    // Sort functionality
    document.querySelectorAll('th.sortable').forEach(header => {
        header.addEventListener('click', function() {
            const column = this.dataset.column;
            let direction = 'asc';

            if (currentSortBy === column) {
                if (currentSortDirection === 'asc') {
                    direction = 'desc';
                } else if (currentSortDirection === 'desc') {
                    // Reset sort
                    currentSortBy = null;
                    currentSortDirection = null;
                    loadSands(currentPage, currentSearch, null, null);
                    return;
                }
            }

            loadSands(currentPage, currentSearch, column, direction);
        });
    });

    // Update sort icons
    function updateSortIcons() {
        document.querySelectorAll('th.sortable i').forEach(icon => {
            icon.className = 'bi bi-arrow-down-up';
            icon.style.opacity = '0.3';
        });

        if (currentSortBy) {
            const activeHeader = document.querySelector(`th.sortable[data-column="${currentSortBy}"] i`);
            if (activeHeader) {
                activeHeader.style.opacity = '1';
                if (currentSortDirection === 'asc') {
                    activeHeader.className = 'bi bi-sort-up';
                } else {
                    activeHeader.className = 'bi bi-sort-down-alt';
                }
            }
        }
    }

    // Delete sand
    window.deleteSand = async function(id) {
        if (!confirm('Yakin ingin menghapus data pasir ini?')) return;

        const result = await api.delete(`/sands/${id}`);
        if (result.success) {
            loadSands(currentPage, currentSearch, currentSortBy, currentSortDirection);
        } else {
            alert('Gagal menghapus data: ' + result.message);
        }
    };

    // Modal functionality
    function attachModalHandlers() {
        document.querySelectorAll('.open-modal').forEach(link => {
            const newLink = link.cloneNode(true);
            link.parentNode.replaceChild(newLink, link);
        });

        initModalHandlers();
    }

    function initModalHandlers() {
        const modal = document.getElementById('floatingModal');
        const modalBody = document.getElementById('modalBody');
        const modalTitle = document.getElementById('modalTitle');
        const closeBtn = document.getElementById('closeModal');
        const backdrop = modal.querySelector('.floating-modal-backdrop');

        function interceptFormSubmit() {
            const form = modalBody.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Let form submit normally - akan redirect
                });
            }
        }

        document.querySelectorAll('.open-modal').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;

                modal.classList.add('active');
                document.body.style.overflow = 'hidden';

                if (url.includes('/create')) {
                    modalTitle.textContent = 'Tambah Data Pasir Baru';
                } else if (url.includes('/edit')) {
                    modalTitle.textContent = 'Edit Data Pasir';
                } else {
                    modalTitle.textContent = 'Detail Data Pasir';
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

                    if (!window.sandFormScriptLoaded) {
                        const script = document.createElement('script');
                        script.src = '/js/sand-form.js';
                        script.onload = () => {
                            window.sandFormScriptLoaded = true;
                            setTimeout(() => {
                                if (typeof initSandForm === 'function') {
                                    initSandForm(modalBody);
                                }
                                interceptFormSubmit();
                            }, 100);
                        };
                        document.head.appendChild(script);
                    } else {
                        setTimeout(() => {
                            if (typeof initSandForm === 'function') {
                                initSandForm(modalBody);
                            }
                            interceptFormSubmit();
                        }, 100);
                    }
                })
                .catch(err => {
                    modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                    console.error('Fetch error:', err);
                });
            });
        });

        window.closeFloatingModal = function() {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border" role="status"></div></div>';
            }, 300);
            // Reload data after modal closes
            loadSands(currentPage, currentSearch, currentSortBy, currentSortDirection);
        }

        closeBtn.addEventListener('click', window.closeFloatingModal);
        backdrop.addEventListener('click', window.closeFloatingModal);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                window.closeFloatingModal();
            }
        });
    }

    // Initialize
    initModalHandlers();
    loadSands();
});
</script>
@endsection
