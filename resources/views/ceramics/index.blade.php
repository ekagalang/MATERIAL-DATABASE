@extends('layouts.app')

@section('title', 'Database Keramik')

@section('content')
<div class="card">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;">
        <button
            type="button"
            class="btn btn-primary-glossy  btn-sm"
            style="display: inline-flex; align-items: center; gap: 6px;"
            onclick="window.location.href='{{ route('materials.index') }}'">
            <i class="bi bi-chevron-left" style="color: #ffffff; font-size: 1.2rem;"></i>
        </button>

        <h2 style="margin: 0; flex-shrink: 0;">Database Keramik</h2>

        <form id="search-form" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                    id="search-input"
                    name="search"
                    placeholder="Cari merek, kode, warna, toko..."
                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary-glossy ">
                <i class="bi bi-search"></i> Cari
            </button>
            <button type="button" id="reset-search" class="btn btn-secondary-glossy " style="display: none;">
                <i class="bi bi-x-lg"></i> Reset
            </button>
        </form>

        <a href="{{ route('ceramics.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Keramik
        </a>
    </div>

    <!-- Table Container -->
    <div id="table-container" class="table-container text-nowrap" style="overflow-x: auto; display: none;">
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

                    <th class="sortable" rowspan="2" data-column="sub_brand">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Sub Merek</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="code">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Code</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="color">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Warna</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="form">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Bentuk</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th rowspan="2">Kemasan</th>

                    <th class="sortable" rowspan="2" data-column="pieces_per_package">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Volume</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th rowspan="2">Luas (M2 / Lbr)</th>

                    <th class="sortable" colspan="3" style="text-align: center;" data-column="dimension_length">
                        <a href="#" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span>Dimensi (cm)</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="font-size: 12px; opacity: 0.3;"></i>
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

                    <th class="sortable" rowspan="2" data-column="price_per_package">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Harga / Kemasan</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th class="sortable" rowspan="2" data-column="comparison_price_per_m2">
                        <a href="#" style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>Harga Komparasi (/ Satuan)</span>
                            <i class="bi bi-arrow-down-up sort-icon" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </a>
                    </th>

                    <th rowspan="2" style="text-align: center">Aksi</th>
                </tr>
                <tr class="dim-sub-row">
                    <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">P</th>
                    <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">L</th>
                    <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">T</th>
                </tr>
            </thead>
            <tbody id="ceramic-list">
                <tr>
                    <td colspan="18" style="text-align: center; padding: 60px;">
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
    <div class="pagination" id="ceramic-pagination"></div>

    <!-- Empty State Container -->
    <div id="empty-state-container" style="display: none;">
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p id="empty-state-message">Belum ada data keramik</p>
            <a href="{{ route('ceramics.create') }}" class="btn btn-primary-glossy  open-modal" id="add-first-btn" style="margin-top: 16px;">
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
            <h2 id="modalTitle">Form Keramik</h2>
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
    background-color: #891313 !important;
    color: #ffffff !important;
    vertical-align: top !important;
    text-align: center !important;
    white-space: nowrap;
    padding: 10px 8px !important;
    font-size: 14px;
}

.table-container table td {
    vertical-align: middle !important;
    padding: 10px 8px !important;
    font-size: 12px;
}

.table-container thead .dim-sub-row th {
    border-top: 0 !important;
    border-left: 0 !important;
    border-right: 0 !important;
    padding: 8px 2px 10px 2px !important;
    width: 40px;
    position: relative;
    line-height: 1.1;
    vertical-align: middle;
}

.table-container tbody td.dim-cell {
    padding: 10px 2px !important;
    width: 40px;
    border-left: 0 !important;
    border-right: 0 !important;
    position: relative;
    text-align: center;
    font-size: 12px;
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
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 11px;
    pointer-events: none;
}

/* Input focus styles */
input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;    
}

/* Badge/Tag styles */
.badge {
    display: inline-block;
    padding: 4px 10px;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    color: #475569;
}

/* Sortable header styles */
th.sortable {
    cursor: pointer;
    user-select: none;
}

th.sortable a {
    transition: all 0.2s ease;
}

th.sortable:hover a {
    color: #c7d2fe !important;
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
// ========================================
// STATE MANAGEMENT
// ========================================
let currentPage = 1;
let currentSearch = '';
let currentSortBy = null;
let currentSortDirection = null;

// ========================================
// HELPER FUNCTIONS
// ========================================

function formatSmartDecimalPlain(value, maxDecimals = 8) {
    const num = Number(value);
    if (!isFinite(num)) return '';
    if (Math.floor(num) === num) return num.toString();

    const str = num.toFixed(10);
    const decimalPart = (str.split('.')[1] || '');
    let firstNonZero = decimalPart.length;
    for (let i = 0; i < decimalPart.length; i++) {
        if (decimalPart[i] !== '0') {
            firstNonZero = i;
            break;
        }
    }

    if (firstNonZero === decimalPart.length) return num.toString();

    const precision = Math.min(firstNonZero + 2, maxDecimals);
    return num.toFixed(precision).replace(/\.?0+$/, '');
}

function formatDimension(value) {
    if (!value || value === null) {
        return '<span style="color: #cbd5e1;">-</span>';
    }
    const formatted = formatSmartDecimalPlain(value).replace('.', ',');
    return formatted;
}

function formatAreaPerPiece(length, width) {
    if (!length || !width) {
        return '<span style="color: #cbd5e1;">—</span>';
    }
    // Convert cm to m and calculate area
    const lengthM = parseFloat(length) / 100;
    const widthM = parseFloat(width) / 100;
    const area = lengthM * widthM;
    return formatSmartDecimalPlain(area).replace('.', ',');
}

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

// ========================================
// CORE FUNCTIONS
// ========================================

async function loadCeramics(page = 1, search = '', sortBy = null, sortDirection = null) {
    // Update state
    currentPage = page;
    currentSearch = search;
    currentSortBy = sortBy;
    currentSortDirection = sortDirection;

    // Show loading
    const ceramicList = document.getElementById('ceramic-list');
    ceramicList.innerHTML = `
        <tr>
            <td colspan="18" style="text-align: center; padding: 60px;">
                <div class="spinner-border" role="status" style="width: 32px; height: 32px; color: #94a3b8;">
    <span class="visually-hidden">Loading...</span>
</div>
                <div style="margin-top: 16px; color: #64748b; font-weight: 500;">Memuat data...</div>
            </td>
        </tr>
    `;

    // Build query parameters
    const params = {
        per_page: 9999
    };

    if (search) params.search = search;
    if (sortBy) params.sort_by = sortBy;
    if (sortDirection) params.sort_direction = sortDirection;

    try {
        const response = await api.get('/ceramics', params);

        if (response.success && response.data.length > 0) {
            const pagination = response.meta || response.pagination;
            renderCeramics(response.data, pagination);
            showTable();
        } else {
            showEmptyState(search);
        }
    } catch (error) {
        console.error('Error loading ceramics:', error);
        ceramicList.innerHTML = `
            <tr>
                <td colspan="18" style="text-align: center; padding: 60px; color: #ef4444;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 32px;"></i>
                    <div style="margin-top: 16px; font-weight: 500;">Gagal memuat data. Silakan coba lagi.</div>
                </td>
            </tr>
        `;
    }
}

function renderCeramics(ceramics, pagination) {
    const ceramicList = document.getElementById('ceramic-list');

    const html = ceramics.map((ceramic, index) => {
        const rowNumber = index + 1;
        // Access nested properties safely
        const length = ceramic.dimensions?.length;
        const width = ceramic.dimensions?.width;
        const thickness = ceramic.dimensions?.thickness;
        
        const packagingType = ceramic.packaging?.type;
        const pieces = ceramic.packaging?.pieces;
        
        const pricePerPackage = ceramic.price?.per_package;
        const pricePerM2 = ceramic.price?.per_m2;

        const areaPerPiece = formatAreaPerPiece(length, width);

        return `
            <tr>
                <td style="text-align: center; font-weight: 500; color: #64748b;">
                    ${rowNumber}
                </td>
                <td style="color: #475569;">${ceramic.type || '-'}</td>
                <td style="color: #475569;">${ceramic.brand || '-'}</td>
                <td style="color: #475569;">${ceramic.sub_brand || '-'}</td>
                <td style="color: #475569;">${ceramic.code || '-'}</td>
                <td style="color: #475569;">${ceramic.color || '-'}</td>
                <td style="color: #475569;">${ceramic.form || '-'}</td>
                <td style="color: #475569;">
                    <span style="display: inline-block; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; font-size: 12px;">
                        ${packagingType || 'Dus'}
                    </span>
                </td>
                <td style="color: #475569; text-align: center;">
                    ${pieces || '-'} Lbr
                </td>
                <td style="color: #475569; text-align: right; font-family: monospace;">
                    ${areaPerPiece} M²
                </td>
                <td class="dim-cell">
                    ${formatDimension(length)}
                </td>
                <td class="dim-cell">
                    ${formatDimension(width)}
                </td>
                <td class="dim-cell">
                    ${formatDimension(thickness)}
                </td>
                <td>
                    <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                        ${ceramic.store?.name || ceramic.store || '-'}
                    </span>
                </td>
                <td style="color: #64748b; font-size: 12px; line-height: 1.5; max-width: 180px;">
                    ${ceramic.store?.address || ceramic.address || '-'}
                </td>
                <td>
                    ${formatPrice(pricePerPackage)}
                </td>
                <td>
                    ${formatPrice(pricePerM2)}
                </td>
                <td style="text-align: center">
                    <div class="btn-group">
                        <a href="/ceramics/${ceramic.id}"
                           class="btn btn-primary-glossy  btn-sm open-modal"
                           title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>

                        <a href="/ceramics/${ceramic.id}/edit"
                           class="btn btn-warning btn-sm open-modal"
                           title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </a>

                        <button type="button"
                                class="btn btn-danger btn-sm"
                                title="Hapus"
                                onclick="deleteCeramic(${ceramic.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    ceramicList.innerHTML = html;
    attachModalHandlers();
}

function renderPagination(pagination) {
    const paginationContainer = document.getElementById('ceramic-pagination');

    if (!pagination || pagination.last_page <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const prevDisabled = pagination.current_page === 1;
    const nextDisabled = pagination.current_page === pagination.last_page;

    const html = `
        <button class="btn btn-secondary-glossy  btn-sm"
                ${prevDisabled ? 'disabled' : ''}
                onclick="loadCeramics(${pagination.current_page - 1}, currentSearch, currentSortBy, currentSortDirection)">
            <i class="bi bi-chevron-left"></i> Sebelumnya
        </button>
        <span style="padding: 0 16px; font-weight: 500; color: #475569;">
            Halaman ${pagination.current_page} dari ${pagination.last_page}
        </span>
        <button class="btn btn-secondary-glossy  btn-sm"
                ${nextDisabled ? 'disabled' : ''}
                onclick="loadCeramics(${pagination.current_page + 1}, currentSearch, currentSortBy, currentSortDirection)">
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
        ? 'Tidak ada data keramik yang sesuai dengan pencarian'
        : 'Belum ada data keramik';

    document.getElementById('empty-state-message').textContent = message;
    document.getElementById('add-first-btn').style.display = search ? 'none' : 'inline-block';
}

async function deleteCeramic(id) {
    const confirmed = await window.showConfirm({
        message: 'Yakin ingin menghapus data keramik ini?',
        confirmText: 'Hapus',
        cancelText: 'Batal',
        type: 'danger'
    });
    if (!confirmed) return;

    try {
        const result = await api.delete(`/ceramics/${id}`);

        if (result.success) {
            window.showToast('Data keramik berhasil dihapus.', 'success');
            loadCeramics(currentPage, currentSearch, currentSortBy, currentSortDirection);
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

// ========================================
// SEARCH FUNCTIONALITY
// ========================================

function setupSearch() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const resetBtn = document.getElementById('reset-search');

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = searchInput.value.trim();

        resetBtn.style.display = searchValue ? 'inline-block' : 'none';
        loadCeramics(1, searchValue, currentSortBy, currentSortDirection);
    });

    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        resetBtn.style.display = 'none';
        loadCeramics(1, '', currentSortBy, currentSortDirection);
    });
}

// ========================================
// SORT FUNCTIONALITY
// ========================================

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
            loadCeramics(currentPage, currentSearch, newSortBy, newSortDirection);
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

// ========================================
// MODAL FUNCTIONALITY
// ========================================

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
                modalTitle.textContent = 'Tambah Data Keramik Baru';
                closeBtn.style.display = 'none';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Data Keramik';
                closeBtn.style.display = 'none';
            } else {
                modalTitle.textContent = 'Detail Data Keramik';
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

                if (!window.ceramicFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/ceramic-form.js?v=' + Date.now();
                    script.onload = () => {
                        window.ceramicFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initCeramicForm === 'function') {
                                initCeramicForm(modalBody);
                            }
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initCeramicForm === 'function') {
                            initCeramicForm(modalBody);
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
        loadCeramics(currentPage, currentSearch, currentSortBy, currentSortDirection);
    }

    closeBtn.addEventListener('click', window.closeFloatingModal);
    backdrop.addEventListener('click', window.closeFloatingModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            window.closeFloatingModal();
        }
    });
}

// ========================================
// INITIALIZE ON PAGE LOAD
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    setupSearch();
    setupSort();
    initModalHandlers();
    loadCeramics(1);
});
</script>
@endsection
