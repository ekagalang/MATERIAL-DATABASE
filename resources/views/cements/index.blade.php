@extends('layouts.app')

@section('title', 'Database Semen')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Database Semen</h2>
        <a href="{{ route('cements.create') }}" class="btn btn-success open-modal">
            <i class="bi bi-plus-lg"></i> Tambah Semen
        </a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('cements.index') }}" method="GET" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari jenis, merek, code, warna, toko..."
                       style="width: 100%; padding: 11px 14px 11px 40px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari
            </button>
            @if(request('search'))
                <a href="{{ route('cements.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </div>
    </form>

    @if($cements->count() > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        @php
                            function getCementSortUrl($column, $currentSortBy, $currentDirection, $requestQuery) {
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
                                return route('cements.index', $params);
                            }
                            $cementSortColumns = [
                                'cement_name' => ['label' => 'Material', 'align' => ''],
                                'type' => ['label' => 'Jenis', 'align' => ''],
                                'brand' => ['label' => 'Merek', 'align' => ''],
                                'sub_brand' => ['label' => 'Sub Merek', 'align' => ''],
                                'code' => ['label' => 'Code', 'align' => 'right'],
                                'color' => ['label' => 'Warna', 'align' => 'left'],
                                'package_unit' => ['label' => 'Kemasan', 'align' => ''],
                                'package_weight_net' => ['label' => 'Berat', 'align' => ''],
                                'store' => ['label' => 'Toko', 'align' => ''],
                                'short_address' => ['label' => 'Alamat Singkat', 'align' => ''],
                                'package_price' => ['label' => 'Harga', 'align' => ''],
                                'comparison_price_per_kg' => ['label' => 'Harga / Kg', 'align' => ''],
                            ];
                        @endphp

                        @foreach(['cement_name', 'type'] as $col)
                            <th class="sortable" @if($cementSortColumns[$col]['align']) style="text-align: {{ $cementSortColumns[$col]['align'] }};" @endif>
                                <a href="{{ getCementSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $cementSortColumns[$col]['label'] }}</span>
                                    @if(request('sort_by') == $col)
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        <th>Foto</th>

                        @foreach(['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net', 'store', 'short_address', 'package_price', 'comparison_price_per_kg'] as $col)
                            <th class="sortable" @if($cementSortColumns[$col]['align']) style="text-align: {{ $cementSortColumns[$col]['align'] }};" @endif>
                                <a href="{{ getCementSortUrl($col, request('sort_by'), request('sort_direction'), request()->query()) }}"
                                   style="color: inherit; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                                    <span>{{ $cementSortColumns[$col]['label'] }}</span>
                                    @if(request('sort_by') == $col)
                                        <i class="bi bi-{{ request('sort_direction') == 'asc' ? 'sort-up' : 'sort-down-alt' }}" style="margin-left: 6px; font-size: 12px;"></i>
                                    @else
                                        <i class="bi bi-arrow-down-up" style="margin-left: 6px; font-size: 12px; opacity: 0.3;"></i>
                                    @endif
                                </a>
                            </th>
                        @endforeach

                        <th style="text-align: center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cements as $index => $cement)
                    <tr>
                        <td style="text-align: center; font-weight: 500; color: #64748b;">
                            {{ $cements->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-weight: 600;">Semen</strong>
                        </td>
                        <td style="color: #475569;">{{ $cement->type ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($cement->photo_url)
                                <img src="{{ $cement->photo_url }}"
                                     alt="Photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                            @else
                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                            @endif
                        </td>
                        <td style="color: #475569;">{{ $cement->brand ?? '-' }}</td>
                        <td style="color: #475569;">{{ $cement->sub_brand ?? '-' }}</td>
                        <td style="color: #475569; font-size: 12px; text-align: right;">
                            {{ $cement->code ?? '-' }}
                        </td>
                        <td style="color: #475569; text-align: left;">{{ $cement->color ?? '-' }}</td>
                        <td style="color: #475569; font-size: 13px; text-align: right;">
                            @if($cement->package_unit)
                                <!-- {{ $cement->package_weight_gross }} --> {{ $cement->packageUnit->name ?? $cement->package_unit }}
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align: left; color: #475569; font-size: 12px;">
                            @if($cement->package_weight_net)
                                {{ rtrim(rtrim(number_format($cement->package_weight_net, 2, ',', '.'), '0'), ',') }} Kg
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                                {{ $cement->store ?? '-' }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">
                            {{ $cement->short_address ?? '-' }}
                        </td>
                        <td>
                            @if($cement->package_price)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($cement->package_price, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($cement->comparison_price_per_kg)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($cement->comparison_price_per_kg, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('cements.show', $cement->id) }}"
                                   class="btn btn-primary btn-sm open-modal"
                                   title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('cements.edit', $cement->id) }}"
                                   class="btn btn-warning btn-sm open-modal"
                                   title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('cements.destroy', $cement->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus semen ini?')">
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
            {{ $cements->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🏗️</div>
            <p>{{ request('search') ? 'Tidak ada semen yang sesuai dengan pencarian' : 'Belum ada data semen' }}</p>
            @if(!request('search'))
                <a href="{{ route('cements.create') }}" class="btn btn-primary open-modal" style="margin-top: 16px;">
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
            <h2 id="modalTitle">Form Semen</h2>
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
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
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
                modalTitle.textContent = 'Tambah Semen Baru';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Semen';
            } else {
                modalTitle.textContent = 'Detail Semen';
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

                // Load cement-form.js if not loaded
                if (!window.cementFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/cement-form.js';
                    script.onload = () => {
                        window.cementFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initCementForm === 'function') {
                                initCementForm(modalBody);
                            }
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initCementForm === 'function') {
                            initCementForm(modalBody);
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