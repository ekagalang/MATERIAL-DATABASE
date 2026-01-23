@extends('layouts.app')

@section('title', 'Database Cat')

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
        <h2 style="margin: 0; flex-shrink: 0;">Database Cat</h2>

        <form id="search-form" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                       id="search-input"
                       name="search"
                       placeholder="Cari cat, merek, warna, toko..."
                       style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary-glossy ">
                <i class="bi bi-search"></i> Cari
            </button>
            <button type="button" id="reset-button" class="btn btn-secondary-glossy " style="display: none;">
                <i class="bi bi-x-lg"></i> Reset
            </button>
        </form>

        <a href="{{ route('cats.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Cat
        </a>
    </div>

    <div id="table-container-wrapper" style="display: none;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th class="sortable" data-column="type">
                            <span>Jenis</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="brand">
                            <span>Merek</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="sub_brand">
                            <span>Sub Merek</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="color_code">
                            <span>Code</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="color_name">
                            <span>Warna</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="package_unit">
                            <span>Kemasan</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="volume">
                            <span>Volume</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="package_weight_net">
                            <span>Berat Bersih</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="store">
                            <span>Toko</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="address">
                            <span>Alamat</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="purchase_price">
                            <span>Harga Beli</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th class="sortable" data-column="comparison_price_per_kg">
                            <span>Harga Komparasi (/ Kg)</span>
                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                        </th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="cat-list">
                    <tr>
                        <td colspan="15" style="text-align: center; padding: 60px;">
                            <div class="spinner-border" role="status" style="width: 32px; height: 32px; color: #94a3b8;">
    <span class="visually-hidden">Loading...</span>
</div>
                            <div style="margin-top: 16px; color: #64748b;">Memuat data...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination" id="cat-pagination"></div>
    </div>

    <div id="empty-state-container" style="display: none;">
        <div class="empty-state">
            <div class="empty-state-icon">🎨</div>
            <p id="empty-state-message">Belum ada data cat</p>
            <a href="{{ route('cats.create') }}" class="btn btn-primary-glossy  open-modal" style="margin-top: 16px;" id="empty-state-add-button">
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
            <h2 id="modalTitle">Form Cat</h2>
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
    position: relative;
    z-index: 2;
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

/* Table fixed column widths */
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

table th:nth-child(1), table td:nth-child(1) { width: 50px; text-align: center; }
table th:nth-child(2), table td:nth-child(2) { width: 100px; }
table th:nth-child(3), table td:nth-child(3) { width: 120px; }
table th:nth-child(4), table td:nth-child(4) { width: 70px; text-align: center; }
table th:nth-child(5), table td:nth-child(5) { width: 120px; }
table th:nth-child(6), table td:nth-child(6) { width: 120px; }
table th:nth-child(7), table td:nth-child(7) { width: 100px; }
table th:nth-child(8), table td:nth-child(8) { width: 130px; }
table th:nth-child(9), table td:nth-child(9) { width: 100px; }
table th:nth-child(10), table td:nth-child(10) { width: 110px; text-align: right; }
table th:nth-child(11), table td:nth-child(11) { width: 110px; text-align: right; }
table th:nth-child(12), table td:nth-child(12) { width: 130px; }
table th:nth-child(13), table td:nth-child(13) { width: 200px; }
table th:nth-child(14), table td:nth-child(14) { width: 120px; }
table th:nth-child(15), table td:nth-child(15) { width: 120px; }
table th:nth-child(16), table td:nth-child(16) { width: 150px; text-align: center; }

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
</style>

<script src="{{ asset('js/api-helper.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // State management
    let currentPage = 1;
    let currentSearch = '';
    let currentSortBy = null;
    let currentSortDirection = null;

    // Load cats from API
    async function loadCats(page = 1, search = '', sortBy = null, sortDirection = null) {
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

        const response = await api.get('/cats', params);

        if (response.success && response.data.length > 0) {
            const pagination = response.meta || response.pagination;
            renderCats(response.data, pagination);
            updateSortIcons();
            showTable();
        } else {
            showEmptyState(search);
        }
    }

    function formatFixedPlain(value, decimals = 2) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        const factor = 10 ** decimals;
        const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
        const sign = truncated < 0 ? '-' : '';
        const abs = Math.abs(truncated);
        const intPart = Math.floor(abs / factor).toString();
        const decPart = (abs % factor).toString().padStart(decimals, '0');
        return `${sign}${intPart}.${decPart}`;
    }

    function formatDynamicPlain(value) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        if (num === 0) return '0';

        const absValue = Math.abs(num);
        const epsilon = Math.min(absValue * 1e-12, 1e-6);
        const adjusted = num + (num >= 0 ? epsilon : -epsilon);
        const sign = adjusted < 0 ? '-' : '';
        const abs = Math.abs(adjusted);
        const intPart = Math.trunc(abs);

        if (intPart > 0) {
            const scaled = Math.trunc(abs * 100);
            const intDisplay = Math.trunc(scaled / 100).toString();
            let decPart = String(scaled % 100).padStart(2, '0');
            decPart = decPart.replace(/0+$/, '');
            return decPart ? `${sign}${intDisplay}.${decPart}` : `${sign}${intDisplay}`;
        }

        let fraction = abs;
        let digits = '';
        let firstNonZeroIndex = null;
        const maxDigits = 30;

        for (let i = 0; i < maxDigits; i++) {
            fraction *= 10;
            const digit = Math.floor(fraction + 1e-12);
            fraction -= digit;
            digits += String(digit);

            if (digit !== 0 && firstNonZeroIndex === null) {
                firstNonZeroIndex = i;
            }

            if (firstNonZeroIndex !== null && i >= firstNonZeroIndex + 1) {
                break;
            }
        }

        digits = digits.replace(/0+$/, '');
        if (!digits) return '0';
        return `${sign}0.${digits}`;
    }

    function formatSmartDecimalPlain(value) {
        return formatDynamicPlain(value);
    }

    // Render cats table
    function renderCats(cats, pagination) {
        const tbody = document.getElementById('cat-list');
        const html = cats.map((cat, index) => {
            const rowNumber = index + 1;

            function formatWeight(value) {
                if (!value) return '<span style="color: #cbd5e1;">—</span>';
                return formatSmartDecimalPlain(value).replace('.', ',') + ' Kg';
            }

            function formatVolume(value, unit) {
                if (!value) return '<span style="color: #cbd5e1;">—</span>';
                return formatSmartDecimalPlain(value).replace('.', ',') + ' ' + (unit || 'L');
            }

            return `
                <tr class="text-nowrap">
                    <td style="text-align: center; font-weight: 500; color: #64748b;">
                        ${rowNumber}
                    </td>
                    <td style="color: #475569;">${cat.type || '-'}</td>
                    <td style="color: #475569;">${cat.brand || '-'}</td>
                    <td style="color: #475569;">${cat.sub_brand || '-'}</td>
                    <td style="color: #475569; font-size: 12px; text-align:right;">
                        ${cat.color_code || '-'}
                    </td>
                    <td style="color: #475569; text-align:left;">${cat.color_name || '-'}</td>
                    <td style="color: #475569; font-size: 13px;">
                        ${cat.package_unit ? `
                            ${cat.package_unit} (${cat.package_weight_gross || '-'} Kg)
                        ` : '<span style="color: #cbd5e1;">—</span>'}
                    </td>
                    <td style="text-align: right; color: #475569; font-size: 12px;">
                        ${formatVolume(cat.volume, cat.volume_unit)}
                    </td>
                    <td style="text-align: left; color: #475569; font-size: 12px;">
                        ${formatWeight(cat.package_weight_net)}
                    </td>
                    <td>
                        <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                            ${cat.store || '-'}
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 12px; line-height: 1.5; text-align: left;">
                        ${cat.address || '-'}
                    </td>
                    <td>
                        ${cat.purchase_price ? `
                            <div style="display: flex; width: 100%; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Rp</span>
                                <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                    ${api.formatRupiah(cat.purchase_price)}
                                </span>
                            </div>
                        ` : '<span style="color: #cbd5e1;">—</span>'}
                    </td>
                    <td>
                        ${cat.comparison_price_per_kg ? `
                            <div style="display: flex; width: 100%; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Rp</span>
                                <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                    ${api.formatRupiah(cat.comparison_price_per_kg)}
                                </span>
                            </div>
                        ` : '<span style="color: #cbd5e1;">—</span>'}
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/cats/${cat.id}"
                               class="btn btn-primary-glossy  btn-sm open-modal"
                               title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="/cats/${cat.id}/edit"
                               class="btn btn-warning btn-sm open-modal"
                               title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <button type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="deleteCat(${cat.id})"
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
        const container = document.getElementById('cat-pagination');

        // Hide pagination if only 1 page or no data
        if (!pagination || pagination.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">';

        // Previous button
        if (pagination.current_page > 1) {
            html += `<button onclick="loadCats(${pagination.current_page - 1}, '${currentSearch}', '${currentSortBy}', '${currentSortDirection}')" class="btn btn-secondary-glossy  btn-sm">← Sebelumnya</button>`;
        } else {
            html += `<button class="btn btn-secondary-glossy  btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">← Sebelumnya</button>`;
        }

        // Page info
        html += `<span style="color: #64748b; font-size: 14px;">Halaman ${pagination.current_page} dari ${pagination.last_page}</span>`;

        // Next button
        if (pagination.current_page < pagination.last_page) {
            html += `<button onclick="loadCats(${pagination.current_page + 1}, '${currentSearch}', '${currentSortBy}', '${currentSortDirection}')" class="btn btn-secondary-glossy  btn-sm">Selanjutnya →</button>`;
        } else {
            html += `<button class="btn btn-secondary-glossy  btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">Selanjutnya →</button>`;
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
            message.textContent = 'Tidak ada cat yang sesuai dengan pencarian';
            addButton.style.display = 'none';
        } else {
            message.textContent = 'Belum ada data cat';
            addButton.style.display = 'inline-block';
        }
    }

    // Search form handler
    document.getElementById('search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchValue = document.getElementById('search-input').value;
        loadCats(1, searchValue, currentSortBy, currentSortDirection);
    });

    // Reset button handler
    document.getElementById('reset-button').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        loadCats(1, '', currentSortBy, currentSortDirection);
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
                    loadCats(currentPage, currentSearch, null, null);
                    return;
                }
            }

            loadCats(currentPage, currentSearch, column, direction);
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
                    activeHeader.className = 'bi bi-sort-up-alt';
                } else {
                    activeHeader.className = 'bi bi-sort-down-alt';
                }
            }
        }
    }

    // Delete cat
    window.deleteCat = async function(id) {
        const confirmed = await window.showConfirm({
            message: 'Yakin ingin menghapus cat ini?',
            confirmText: 'Hapus',
            cancelText: 'Batal',
            type: 'danger'
        });
        if (!confirmed) return;

        try {
            const result = await api.delete(`/cats/${id}`);
            if (result.success) {
                window.showToast('Data cat berhasil dihapus.', 'success');
                loadCats(currentPage, currentSearch, currentSortBy, currentSortDirection);
            } else {
                const message = 'Gagal menghapus data: ' + (result.message || 'Terjadi kesalahan');
                window.showToast(message, 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            const message = 'Gagal menghapus data. Silakan coba lagi.';
            window.showToast(message, 'error');
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
                    modalTitle.textContent = 'Tambah Cat Baru';
                } else if (url.includes('/edit')) {
                    modalTitle.textContent = 'Edit Cat';
                } else {
                    modalTitle.textContent = 'Detail Cat';
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

                    if (!window.catFormScriptLoaded) {
                        const script = document.createElement('script');
                        script.src = '/js/cat-form.js?v=' + Date.now();
                        script.onload = () => {
                            window.catFormScriptLoaded = true;
                            setTimeout(() => {
                                if (typeof initCatForm === 'function') {
                                    initCatForm(modalBody);
                                }
                                initStoreAutocompleteForModal();
                                interceptFormSubmit();
                            }, 100);
                        };
                        document.head.appendChild(script);
                    } else {
                        setTimeout(() => {
                            if (typeof initCatForm === 'function') {
                                initCatForm(modalBody);
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
            // Reload data after modal closes
            loadCats(currentPage, currentSearch, currentSortBy, currentSortDirection);
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
    loadCats();
});
</script>
@endsection
