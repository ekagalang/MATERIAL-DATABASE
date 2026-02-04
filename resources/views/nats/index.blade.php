@extends('layouts.app')

@section('title', 'Database Nat')

@section('content')
<style>
    .table-container table tr {
        height: 20px !important;
    }
    .table-container table td {
        display: table-cell !important;
        padding: 0 6px !important;
        font-size: 12px !important;
        height: 20px !important;
        line-height: 20px !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-container table td > * {
        vertical-align: middle;
        display: inline-block;
        line-height: 1.2;
    }

    .table-container table td > div[style*="display: flex"] {
        display: flex !important;
        width: 100%;
        align-items: center;
        height: 100%;
    }

    .table-container table td span[style*="background"] {
        display: inline-block !important;
        line-height: 1.2;
        padding: 2px 6px !important;
        vertical-align: middle;
    }

    .btn-group-compact {
        display: inline-flex !important;
        vertical-align: middle;
    }

    .table-container table {
        width: max-content !important;
        min-width: 100%;
        margin-bottom: 5px;
    }

    .table-container {
        width: 100%;
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: 70vh;
        border-radius: 8px !important;
        margin-bottom: 10px;
    }

    .table-container thead th {
        padding: 4px 6px !important;
        font-size: 14px !important;
        position: sticky;
        top: 0;
        z-index: 10;
        background: #891313 !important;
        color: #fff !important;
    }

    .action-cell {
        padding: 1px 4px !important;
        width: 1px;
        white-space: nowrap;
        vertical-align: middle !important;
    }

    .btn-action {
        padding: 0 !important;
        width: 22px !important;
        height: 20px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 0 !important;
    }

    .btn-action i {
        font-size: 11px !important;
        -webkit-text-stroke: 0.3px currentColor !important;
    }

    .btn-group-compact {
        display: inline-flex !important;
        border-radius: 4px !important;
        overflow: hidden !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
        border: 1px solid rgba(0,0,0,0.1) !important;
    }

    .btn-group-compact .btn {
        margin: 0 !important;
        border-radius: 0 !important;
        border-left: 1px solid rgba(255,255,255,0.2) !important;
    }

    .btn-group-compact .btn:first-child {
        border-left: none !important;
    }
</style>
<div class="card">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;">
        <button
            type="button"
            class="btn btn-primary-glossy  btn-sm"
            style="display: inline-flex; align-items: center; gap: 6px;"
            onclick="window.location.href='{{ route('materials.index') }}'">
            <i class="bi bi-chevron-left" style="color: #ffffff; font-size: 1.2rem;"></i>
        </button>

        <h2 style="margin: 0; flex-shrink: 0;">Database Nat</h2>

        <form id="search-form" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                    id="search-input"
                    name="search"
                    placeholder="Cari jenis, merek, kode, warna, toko..."
                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary-glossy ">
                <i class="bi bi-search"></i> Cari
            </button>
            <button type="button" id="reset-search" class="btn btn-secondary-glossy " style="display: none;">
                <i class="bi bi-x-lg"></i> Reset
            </button>
        </form>

        <a href="{{ route('nats.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Nat
        </a>
    </div>

    <!-- Table Container -->
    <div id="table-container" class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="2">No</th>

                    <th class="sortable" rowspan="2" data-column="type">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Jenis</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="brand">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Merek</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="code">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Kode</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="color">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Warna</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="package_weight">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Berat (Kg)</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="store">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Toko</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="address">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Alamat</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="price_per_bag">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Harga Beli</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th rowspan="2" style="text-align: center">Aksi</th>
                </tr>
            </thead>
            <tbody id="nat-list">
                <tr>
                    <td colspan="10" style="text-align: center; padding: 60px;">
                        <div class="spinner-border" role="status" style="width: 32px; height: 32px; color: #94a3b8;">
    <span class="visually-hidden">Loading...</span>
</div>
                        <div style="margin-top: 16px; color: #64748b; font-weight: 500;">Memuat data...</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="nat-pagination"></div>

    <!-- Empty State Container -->
    <div id="empty-state-container" style="display: none;">
        <div class="empty-state">
            <div class="empty-state-icon">🏗️</div>
            <p id="empty-state-message">Belum ada data nat</p>
            <a href="{{ route('nats.create') }}" class="btn btn-primary-glossy  open-modal" id="add-first-btn" style="margin-top: 16px;">
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
            <h2 id="modalTitle">Form Nat</h2>
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
/* Modal Styles */
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
    color: #ffffff;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative;
    z-index: 10;
}

.floating-modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
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

.table-container thead th {
    background-color: #891313 !important;
    color: #ffffff !important;
    vertical-align: middle !important;
    text-align: center !important;
    white-space: nowrap;
}

input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
}

th.sortable {
    cursor: pointer;
    user-select: none;
}

th.sortable a {
    transition: all 0.2s ease;
}

th.sortable:hover a {
    color: #891313 !important;
}

th.sortable:hover i {
    opacity: 1 !important;
}

th.sortable i {
    transition: opacity 0.2s ease;
}
</style>

<!-- API Helper Script -->
<script src="{{ asset('js/api-helper.js') }}"></script>

<script>
// STATE MANAGEMENT
let currentPage = 1;
let currentSearch = '';
let currentSortBy = null;
let currentSortDirection = null;

// HELPER FUNCTIONS
function formatPrice(value) {
    if (!value || value === null) {
        return '<span style="color: #cbd5e1;">—</span>';
    }
    return `
        <div style="display: flex; width: 100%; font-size: 13px;">
            <span style="color: #64748b; font-weight: 500;">Rp</span>
            <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                ${parseInt(value).toLocaleString('id-ID')}
            </span>
        </div>
    `;
}

// CORE FUNCTIONS
async function loadNats(page = 1, search = '', sortBy = null, sortDirection = null) {
    currentPage = page;
    currentSearch = search;
    currentSortBy = sortBy;
    currentSortDirection = sortDirection;

    const natList = document.getElementById('nat-list');
    natList.innerHTML = `
        <tr>
            <td colspan="10" style="text-align: center; padding: 60px;">
                <div class="spinner-border" role="status" style="width: 32px; height: 32px; color: #94a3b8;">
    <span class="visually-hidden">Loading...</span>
</div>
                <div style="margin-top: 16px; color: #64748b; font-weight: 500;">Memuat data...</div>
            </td>
        </tr>
    `;

    const params = {
        per_page: 9999 // Get all data without pagination
    };

    if (search) params.search = search;
    if (sortBy) params.sort_by = sortBy;
    if (sortDirection) params.sort_direction = sortDirection;

    try {
        const response = await api.get('/nats', params);

        if (response.success && response.data.length > 0) {
            const pagination = response.meta || response.pagination;
            renderNats(response.data, pagination);
            showTable();
        } else {
            showEmptyState(search);
        }
    } catch (error) {
        console.error('Error loading nats:', error);
        natList.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 60px; color: #ef4444;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 32px;"></i>
                    <div style="margin-top: 16px; font-weight: 500;">Gagal memuat data. Silakan coba lagi.</div>
                </td>
            </tr>
        `;
    }
}

function renderNats(nats, pagination) {
    const natList = document.getElementById('nat-list');

    const html = nats.map((nat, index) => {
        const rowNumber = index + 1;

        return `
            <tr>
                <td style="text-align: center; font-weight: 500; color: #64748b;">
                    ${rowNumber}
                </td>
                <td style="color: #475569;">${nat.type || '-'}</td>
                <td style="color: #475569;">${nat.brand || '-'}</td>
                <td style="color: #475569;">${nat.code || '-'}</td>
                <td style="color: #475569;">${nat.color || '-'}</td>
                <td style="text-align: center; color: #475569;">${nat.package_weight || '-'}</td>
                <td>
                    <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                        ${nat.store || '-'}
                    </span>
                </td>
                <td style="color: #64748b; font-size: 12px; line-height: 1.5;">
                    ${nat.address || '-'}
                </td>
                <td>
                    ${formatPrice(nat.price_per_bag)}
                </td>
                <td style="text-align: center">
                    <div class="btn-group">
                        <a href="/nats/${nat.id}"
                           class="btn btn-primary-glossy  btn-sm open-modal"
                           title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>

                        <a href="/nats/${nat.id}/edit"
                           class="btn btn-warning btn-sm open-modal"
                           title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <button type="button"
                                class="btn btn-danger btn-sm"
                                title="Hapus"
                                onclick="deleteNat(${nat.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    natList.innerHTML = html;
    attachModalHandlers();
}

function renderPagination(pagination) {
    const paginationContainer = document.getElementById('nat-pagination');

    if (!pagination || pagination.last_page <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const prevDisabled = pagination.current_page === 1;
    const nextDisabled = pagination.current_page === pagination.last_page;

    const html = `
        <button class="btn btn-secondary-glossy  btn-sm"
                ${prevDisabled ? 'disabled' : ''}
                onclick="loadNats(${pagination.current_page - 1}, currentSearch, currentSortBy, currentSortDirection)">
            <i class="bi bi-chevron-left"></i> Sebelumnya
        </button>
        <span style="padding: 0 16px; font-weight: 500; color: #475569;">
            Halaman ${pagination.current_page} dari ${pagination.last_page}
        </span>
        <button class="btn btn-secondary-glossy  btn-sm"
                ${nextDisabled ? 'disabled' : ''}
                onclick="loadNats(${pagination.current_page + 1}, currentSearch, currentSortBy, currentSortDirection)">
            Selanjutnya <i class="bi bi-chevron-right"></i>
        </button>
    `;

    paginationContainer.innerHTML = html;
}

function showTable() {
    document.getElementById('table-container').style.display = 'block';
    document.getElementById('empty-state-container').style.display = 'none';
}

function showEmptyState(search) {
    document.getElementById('table-container').style.display = 'none';
    document.getElementById('empty-state-container').style.display = 'block';

    const message = search
        ? 'Tidak ada data nat yang sesuai dengan pencarian'
        : 'Belum ada data nat';

    document.getElementById('empty-state-message').textContent = message;
    document.getElementById('add-first-btn').style.display = search ? 'none' : 'inline-block';
}

async function deleteNat(id) {
    const confirmed = await window.showConfirm({
        message: 'Yakin ingin menghapus data nat ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        type: 'danger'
    });
    if (!confirmed) return;

    try {
        const result = await api.delete(`/nats/${id}`);

        if (result.success) {
            window.showToast('Data nat berhasil dihapus.', 'success');
            loadNats(currentPage, currentSearch, currentSortBy, currentSortDirection);
        } else {
            const message = 'Gagal menghapus data: ' + (result.message || 'Terjadi kesalahan');
            window.showToast(message, 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        const message = 'Gagal menghapus data. Silakan coba lagi.';
        window.showToast(message, 'error');
    }
}

// SEARCH FUNCTIONALITY
function setupSearch() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const resetBtn = document.getElementById('reset-search');

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = searchInput.value.trim();

        resetBtn.style.display = searchValue ? 'inline-block' : 'none';
        loadNats(1, searchValue, currentSortBy, currentSortDirection);
    });

    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        resetBtn.style.display = 'none';
        loadNats(1, '', currentSortBy, currentSortDirection);
    });
}

// SORT FUNCTIONALITY
function setupSort() {
    const sortableHeaders = document.querySelectorAll('th.sortable');

    sortableHeaders.forEach(header => {
        const link = header.querySelector('a');
        const column = header.getAttribute('data-column');

        link.addEventListener('click', function(e) {
            e.preventDefault();

            let newSortBy = column;
            let newSortDirection = 'asc';

            if (currentSortBy === column) {
                if (currentSortDirection === 'asc') {
                    newSortDirection = 'desc';
                } else {
                    newSortBy = null;
                    newSortDirection = null;
                }
            }

            updateSortIcons(newSortBy, newSortDirection);
            loadNats(currentPage, currentSearch, newSortBy, newSortDirection);
        });
    });
}

function updateSortIcons(sortBy, sortDirection) {
    const sortableHeaders = document.querySelectorAll('th.sortable');

    sortableHeaders.forEach(header => {
        const icon = header.querySelector('.sort-icon');
        const column = header.getAttribute('data-column');

        if (column === sortBy) {
            if (sortDirection === 'asc') {
                icon.className = 'bi bi-sort-up-alt sort-icon';
                icon.style.opacity = '1';
            } else if (sortDirection === 'desc') {
                icon.className = 'bi bi-sort-down-alt sort-icon';
                icon.style.opacity = '1';
            }
        } else {
            icon.className = 'bi bi-arrow-down-up sort-icon';
            icon.style.opacity = '0.3';
        }
    });
}

// MODAL FUNCTIONALITY
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
                // Let form submit normally
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
                modalTitle.textContent = 'Tambah Data Nat Baru';
                closeBtn.style.display = 'none';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Data Nat';
                closeBtn.style.display = 'none';
            } else {
                modalTitle.textContent = 'Detail Data Nat';
                closeBtn.style.display = 'flex';
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

                // Function to initialize store autocomplete
                function initStoreAutocompleteForModal() {
                    if (!window.storeAutocompleteLoaded) {
                        const storeScript = document.createElement('script');
                        storeScript.src = '/js/store-autocomplete.js?v=' + Date.now();
                        storeScript.onload = () => {
                            window.storeAutocompleteLoaded = true;
                            if (typeof initStoreAutocomplete === 'function') {
                                initStoreAutocomplete(modalBody);
                            }
                        };
                        document.head.appendChild(storeScript);
                    } else {
                        if (typeof initStoreAutocomplete === 'function') {
                            initStoreAutocomplete(modalBody);
                        }
                    }
                }

                if (!window.natFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/nat-form.js?v=' + Date.now();
                    script.onload = () => {
                        window.natFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initNatForm === 'function') {
                                initNatForm(modalBody);
                            }
                            initStoreAutocompleteForModal();
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initNatForm === 'function') {
                            initNatForm(modalBody);
                        }
                        initStoreAutocompleteForModal();
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

        loadNats(currentPage, currentSearch, currentSortBy, currentSortDirection);
    }

    closeBtn.addEventListener('click', window.closeFloatingModal);
    backdrop.addEventListener('click', window.closeFloatingModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            window.closeFloatingModal();
        }
    });
}

// INITIALIZE
document.addEventListener('DOMContentLoaded', function() {
    setupSearch();
    setupSort();
    initModalHandlers();
    loadNats(1);
});
</script>
@endsection
