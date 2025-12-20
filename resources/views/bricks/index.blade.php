@extends('layouts.app')

@section('title', 'Database Bata')

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

        <h2 style="margin: 0; flex-shrink: 0;">Database Bata</h2>

        <form action="{{ route('bricks.index') }}" method="GET" style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 320px; margin: 0;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari jenis, merek, bentuk, toko..." 
                    style="width: 100%; padding: 11px 14px 11px 36px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari
            </button>
            @if(request('search'))
                <a href="{{ route('bricks.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </form>

        <a href="{{ route('bricks.create') }}" class="btn btn-success open-modal" style="flex-shrink: 0;">
            <i class="bi bi-plus-lg"></i> Tambah Bata
        </a>
    </div>

    @if($bricks->count() > 0)
        <div class="table-container">
            <table>
                <thead>
                    @php
                        $sortableColumns = [
                            'material_name' => 'Material',
                            'type' => 'Jenis',
                            'brand' => 'Merek',
                            'form' => 'Bentuk',
                            'dimension_length' => 'Panjang (cm)',
                            'dimension_width' => 'Lebar (cm)',
                            'dimension_height' => 'Tinggi (cm)',
                            'package_volume' => 'Volume',
                            'store' => 'Toko',
                            'address' => 'Alamat',
                            'price_per_piece' => 'Harga Beli',
                            'comparison_price_per_m3' => 'Harga Komparasi (/ M3)',
                        ];

                        // Function to get next sort state
                        function getNextSortUrl($column, $currentSortBy, $currentDirection, $requestQuery) {
                            $params = array_merge($requestQuery, []);
                            unset($params['sort_by'], $params['sort_direction']);

                            // 3-state logic: asc -> desc -> reset (no sort)
                            if ($currentSortBy === $column) {
                                if ($currentDirection === 'asc') {
                                    $params['sort_by'] = $column;
                                    $params['sort_direction'] = 'desc';
                                }
                                // If desc, don't add params (reset to default)
                            } else {
                                $params['sort_by'] = $column;
                                $params['sort_direction'] = 'asc';
                            }

                            return route('bricks.index', $params);
                        }
                    @endphp
                    <tr class="dim-group-row">
                        <th rowspan="2">No</th>

                        @foreach(['material_name', 'type'] as $column)
                            <th class="sortable" rowspan="2">
                                <a href="{{ getNextSortUrl($column, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $sortableColumns[$column] }}</span>
                                    @if(request('sort_by') == $column)
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        @foreach(['brand', 'form'] as $column)
                            <th class="sortable" rowspan="2">
                                <a href="{{ getNextSortUrl($column, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $sortableColumns[$column] }}</span>
                                    @if(request('sort_by') == $column)
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        <th class="sortable" colspan="3" style="text-align: center; font-size: 13px;">
                            <a href="{{ getNextSortUrl('dimension_length', request('sort_by'), request('sort_direction'), request()->query()) }}"
                               style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <span>Dimensi (cm)</span>
                                @if(in_array(request('sort_by'), ['dimension_length', 'dimension_width', 'dimension_height']))
                                    <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="font-size: 12px;"></i>
                                @else
                                    <i class="bi bi-arrow-down-up" style="font-size: 12px; opacity: 0.3;"></i>
                                @endif
                            </a>
                        </th>

                        @foreach(['package_volume', 'store', 'address', 'price_per_piece', 'comparison_price_per_m3'] as $column)
                            <th class="sortable" rowspan="2">
                                <a href="{{ getNextSortUrl($column, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $sortableColumns[$column] }}</span>
                                    @if(request('sort_by') == $column)
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        <th rowspan="2" style="text-align: center">Aksi</th>
                    </tr>
                    <tr class="dim-sub-row">
                        @foreach(['P', 'L', 'T'] as $label)
                            <th style="text-align: center; font-size: 12px; padding: 0 2px; width: 40px;">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($bricks as $index => $brick)
                    <tr>
                        <td style="text-align: center; font-weight: 500; color: #64748b;">
                            {{ $bricks->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-weight: 600;">{{ $brick->material_name }}</strong>
                        </td>
                        <td style="color: #475569;">{{ $brick->type ?? '-' }}</td>
                        <td style="color: #475569;">{{ $brick->brand ?? '-' }}</td>
                        <td style="color: #475569;">{{ $brick->form ?? '-' }}</td>
                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                            @if(!is_null($brick->dimension_length))
                                {{ rtrim(rtrim(number_format($brick->dimension_length, 1, ',', '.'), '0'), ',') }}
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                            @if(!is_null($brick->dimension_width))
                                {{ rtrim(rtrim(number_format($brick->dimension_width, 1, ',', '.'), '0'), ',') }}
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                        <td class="dim-cell" style="text-align: center; color: #475569; font-size: 12px; width: 40px; padding: 0 2px;">
                            @if(!is_null($brick->dimension_height))
                                {{ rtrim(rtrim(number_format($brick->dimension_height, 1, ',', '.'), '0'), ',') }}
                            @else
                                <span style="color: #cbd5e1;">-</span>
                            @endif
                        </td>
                        <td class="volume-cell" style="text-align: right; color: #475569; font-size: 12px;">
                            @if($brick->package_volume)
                                {{ number_format($brick->package_volume, 6, ',', '.') }} M3
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                                {{ $brick->store ?? '-' }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">
                            {{ $brick->address ?? '-' }}
                        </td>
                        <td>
                            @if($brick->price_per_piece)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($brick->price_per_piece, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($brick->comparison_price_per_m3)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($brick->comparison_price_per_m3, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center">
                            <div class="btn-group">
                                <a href="{{ route('bricks.show', $brick->id) }}"
                                   class="btn btn-primary btn-sm open-modal"
                                   title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('bricks.edit', $brick->id) }}"
                                   class="btn btn-warning btn-sm open-modal"
                                   title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('bricks.destroy', $brick->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus data bata ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-danger btn-sm"
                                            title="Hapus">
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

        <div class="pagination">
            {{ $bricks->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🧱</div>
            <p>{{ request('search') ? 'Tidak ada data bata yang sesuai dengan pencarian' : 'Belum ada data bata' }}</p>
            @if(!request('search'))
                <a href="{{ route('bricks.create') }}" class="btn btn-primary open-modal" style="margin-top: 16px;">
                    <i class="bi bi-plus-lg"></i> Tambah Data Pertama
                </a>
            @endif
        </div>
    @endif
</div>

<!-- Floating Modal Container -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Form Bata</h2>
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
    position: relative; /* Added for ::before positioning */
    overflow: hidden; /* Added to contain the extended ::before */
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #ffffff; /* Changed text color to white */
    padding: 8px 0; /* Added padding */
    position: relative; /* Added for z-index and ::before relative positioning */
    z-index: 1; /* Ensures text is above the ::before background */
    flex: 1; /* Allows h2 to take available space */
}

.floating-modal-header h2::before {
    content: '';
    position: absolute;
    left: -32px; /* Compensates for parent padding-left */
    right: -200px; /* Extends far enough to cover the button and right edge */
    top: 0;
    bottom: 0;
    background: #891313;
    z-index: -1; /* Places the background behind the h2 text */
}

.floating-modal-close {
    background: transparent;
    border: none;
    font-size: 28px;
    color: #ffffff; /* Changed to white to be visible on red */
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative; /* Added position */
    z-index: 10; /* Added z-index to sit above h2 */
}

.floating-modal-close:hover {
    background: rgba(255, 255, 255, 0.1); /* Changed hover to semi-transparent white */
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

/* Input focus styles */
input[type="text"]:focus {
    outline: none;
    border-color: #891313 !important;
    box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
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
            
            // Update title and close button visibility
            if (url.includes('/create')) {
                modalTitle.textContent = 'Tambah Data Bata Baru';
                closeBtn.style.display = 'none'; // Hide close button
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Data Bata';
                closeBtn.style.display = 'none'; // Hide close button
            } else {
                modalTitle.textContent = 'Detail Data Bata';
                closeBtn.style.display = 'flex'; // Show close button
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

                // Load brick-form.js if not loaded
                if (!window.brickFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/brick-form.js?v=' + Date.now();
                    script.onload = () => {
                        window.brickFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initBrickForm === 'function') {
                                initBrickForm(modalBody);
                            }
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initBrickForm === 'function') {
                            initBrickForm(modalBody);
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

    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
@endsection
