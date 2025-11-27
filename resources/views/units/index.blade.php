@extends('layouts.app')

@section('title', 'Database Satuan')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Database Satuan</h2>
        <a href="{{ route('units.create') }}" class="btn btn-success open-modal">
            <i class="bi bi-plus-lg"></i> Tambah Satuan
        </a>
    </div>

    <!-- Filter Form -->
    <form action="{{ route('units.index') }}" method="GET" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1;">
                <select name="material_type" 
                        style="width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit;">
                    <option value="">Semua Material Type</option>
                    @foreach($materialTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('material_type') == $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel"></i> Filter
            </button>
            @if(request('material_type'))
                <a href="{{ route('units.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </div>
    </form>

    @if($units->count() > 0)
        <!-- Grid 2 Kolom Tabel -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
            @php
                // Ambil items dari paginator (sudah tersortir dari controller)
                $unitsArray = $units->items();

                $totalUnits = count($unitsArray);
                $halfCount = ceil($totalUnits / 2);
                $leftColumn = array_slice($unitsArray, 0, $halfCount);
                $rightColumn = array_slice($unitsArray, $halfCount);
            @endphp

            <!-- Kolom Kiri -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            @php
                                function getUnitSortUrl($column, $currentSortBy, $currentDirection, $requestQuery) {
                                    $params = array_merge($requestQuery, []);
                                    unset($params['sort_by'], $params['sort_direction']);
                                    if ($currentSortBy === $column) {
                                        if ($currentDirection === 'asc') {
                                            $params['sort_by'] = $column;
                                            $params['sort_direction'] = 'desc';
                                        }
                                    } else {
                                        $params['sort_by'] = $column;
                                        $params['sort_direction'] = 'asc';
                                    }
                                    return route('units.index', $params);
                                }
                                $unitSortColumns = [
                                    'name' => 'Nama',
                                    'code' => 'Kode',
                                    'package_weight' => 'Berat (Kg)',
                                    'material_type' => 'Material',
                                ];
                            @endphp

                            @foreach(['name', 'code', 'package_weight', 'material_type'] as $col)
                                <th class="sortable" style="width: {{ $col == 'name' ? 'auto' : ($col == 'code' ? '80px' : '90px') }};">
                                    <a href="{{ getUnitSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                        <span>{{ $unitSortColumns[$col] }}</span>
                                        @if(request('sort_by') == $col)
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leftColumn as $index => $unit)
                        <tr>
                            <td style="text-align: center; font-weight: 500; color: #64748b;">
                                {{ $index + 1 }}
                            </td>
                            <td style="color: #475569; font-size: 13px; font-weight: 500;">
                                {{ $unit->name }}
                            </td>
                            <td style="text-align: center;">
                                <strong style="color: #0f172a; font-weight: 600; font-size: 13px;">{{ $unit->code }}</strong>
                            </td>
                            <td style="text-align: right; color: #475569; font-size: 13px;">
                                @if($unit->package_weight && $unit->package_weight > 0)
                                    {{ rtrim(rtrim(number_format($unit->package_weight, 2, ',', '.'), '0'), ',') }}
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <span style="display: inline-block; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; font-size: 11px; font-weight: 600; color: #475569;">
                                    {{ ucfirst($unit->material_type) }}
                                </span>
                            </td>
                            <td style="text-align: center">
                                <div class="btn-group" style="display: flex; justify-content: center;">
                                    <a href="{{ route('units.edit', $unit->id) }}"
                                       class="btn btn-warning btn-sm open-modal"
                                       title="Edit"
                                       style="padding: 6px 10px;">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('units.destroy', $unit->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus satuan ini?')"
                                          style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-danger btn-sm"
                                                title="Hapus"
                                                style="padding: 6px 10px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Kolom Kanan -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            @foreach(['name', 'code', 'package_weight', 'material_type'] as $col)
                                <th class="sortable" style="width: {{ $col == 'name' ? 'auto' : ($col == 'code' ? '80px' : '90px') }};">
                                    <a href="{{ getUnitSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                       style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                        <span>{{ $unitSortColumns[$col] }}</span>
                                        @if(request('sort_by') == $col)
                                            <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                        @else
                                            <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                        @endif
                                    </a>
                                </th>
                            @endforeach
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rightColumn as $index => $unit)
                        <tr>
                            <td style="text-align: center; font-weight: 500; color: #64748b;">
                                {{ $halfCount + $index + 1 }}
                            </td>
                            <td style="color: #475569; font-size: 13px; font-weight: 500;">
                                {{ $unit->name }}
                            </td>
                            <td style="text-align: center;">
                                <strong style="color: #0f172a; font-weight: 600; font-size: 13px;">{{ $unit->code }}</strong>
                            </td>
                            <td style="text-align: right; color: #475569; font-size: 13px;">
                                @if($unit->package_weight && $unit->package_weight > 0)
                                    {{ rtrim(rtrim(number_format($unit->package_weight, 2, ',', '.'), '0'), ',') }}
                                @else
                                    <span style="color: #cbd5e1;">-</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <span style="display: inline-block; padding: 4px 8px; background: #f1f5f9; border-radius: 6px; font-size: 11px; font-weight: 600; color: #475569;">
                                    {{ ucfirst($unit->material_type) }}
                                </span>
                            </td>
                            <td style="text-align: center">
                                <div class="btn-group" style="display: flex; justify-content: center;">
                                    <a href="{{ route('units.edit', $unit->id) }}"
                                       class="btn btn-warning btn-sm open-modal"
                                       title="Edit"
                                       style="padding: 6px 10px;">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form action="{{ route('units.destroy', $unit->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus satuan ini?')"
                                          style="display: inline; margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-danger btn-sm"
                                                title="Hapus"
                                                style="padding: 6px 10px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 24px;">
            {{ $units->links() }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>Belum ada satuan yang terdaftar</p>
        </div>
    @endif
</div>

<!-- Floating Modal -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h3 id="modalTitle">Form Satuan</h3>
            <button type="button" id="closeModal" class="floating-modal-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <!-- Content akan di-load via AJAX -->
            <div style="text-align: center; padding: 40px;">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Responsive untuk layar kecil */
@media (max-width: 1024px) {
    .card > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}

/* Floating Modal Styles */
.floating-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.floating-modal.active {
    display: block;
    opacity: 1;
}

.floating-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(4px);
}

.floating-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

.floating-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 1.5px solid #f1f5f9;
}

.floating-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #0f172a;
}

.floating-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    line-height: 1;
    transition: all 0.2s ease;
    border-radius: 6px;
}

.floating-modal-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.floating-modal-body {
    padding: 28px;
    overflow-y: auto;
    flex: 1;
}

/* Form inside modal */
.floating-modal-body .form-group {
    margin-bottom: 20px;
}

.floating-modal-body label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #475569;
    font-size: 14px;
}

.floating-modal-body .form-control {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s ease;
}

.floating-modal-body .form-control:focus {
    outline: none;
    border-color: #891313;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1);
}

.floating-modal-body .form-text {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #94a3b8;
}

.floating-modal-body .text-danger {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    display: block;
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
    color: #891313 !important;
}

th.sortable:hover i {
    opacity: 1 !important;
}

th.sortable i {
    transition: opacity 0.2s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = modal.querySelector('.floating-modal-backdrop');

    // Intercept form submission in modal
    function interceptFormSubmit() {
        const form = modalBody.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Let form submit normally - akan redirect
            });
        }
    }

    // Open modal
    document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            // Show modal
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update title
            if (url.includes('/create')) {
                modalTitle.textContent = 'Tambah Satuan Baru';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Satuan';
            } else {
                modalTitle.textContent = 'Detail Satuan';
            }

            // Load content via AJAX
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
                interceptFormSubmit();
            })
            .catch(err => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">⚠️</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                console.error('Fetch error:', err);
            });
        });
    });

    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner-border" role="status"></div></div>';
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
@endsection