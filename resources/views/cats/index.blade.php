@extends('layouts.app')

@section('title', 'Database Cat')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
        <h2 style="margin-bottom: 0;">Database Cat</h2>
        <a href="{{ route('cats.create') }}" class="btn btn-success open-modal">
            <i class="bi bi-plus-lg"></i> Tambah Cat
        </a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('cats.index') }}" method="GET" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="flex: 1; position: relative;">
                <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Cari cat, merek, warna, toko..." 
                       style="width: 100%; padding: 11px 14px 11px 40px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-family: inherit; transition: all 0.2s ease;">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Cari
            </button>
            @if(request('search'))
                <a href="{{ route('cats.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Reset
                </a>
            @endif
        </div>
    </form>

    @if($cats->count() > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th>Foto</th>
                        <th>Merek</th>
                        <th>Sub Merek</th>
                        <th>Code Warna</th>
                        <th>Nama Warna</th>
                        <th>Kemasan</th>
                        <th>Volume</th>
                        <th>Berat Bersih</th>
                        <th>Toko</th>
                        <th>Alamat Singkat</th>
                        <th>Harga Beli</th>
                        <th>Harga/Kg</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cats as $index => $cat)
                    <tr>
                        <td style="text-align: center; font-weight: 500; color: #64748b;">
                            {{ $cats->firstItem() + $index }}
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-weight: 600;">Cat</strong>
                        </td>
                        <td style="color: #475569;">{{ $cat->type ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($cat->photo_url)
                                <img src="{{ $cat->photo_url }}"
                                     alt="Photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1.5px solid #e2e8f0;">
                                <span style="color: #cbd5e1; display: none; font-size: 24px;">📷</span>
                            @else
                                <span style="color: #cbd5e1; font-size: 20px;">—</span>
                            @endif
                        </td>
                        <td style="color: #475569;">{{ $cat->brand ?? '-' }}</td>
                        <td style="color: #475569;">{{ $cat->sub_brand ?? '-' }}</td>
                        <td style="color: #475569; font-size: 12px;">
                            {{ $cat->color_code ?? '-' }}
                        </td>
                        <td style="color: #475569;">{{ $cat->color_name ?? '-' }}</td>
                        <td style="color: #475569; font-size: 13px;">
                            @if($cat->package_unit)
                                {{ $cat->package_unit }} ({{ $cat->package_weight_gross }} Kg)
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right; color: #475569; font-size: 12px;">
                            @if($cat->volume)
                                {{ number_format($cat->volume, 2, ',', '.') }} {{ $cat->volume_unit }}
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td style="text-align: right; color: #475569; font-size: 12px;">
                            @if($cat->package_weight_net)
                                {{ number_format($cat->package_weight_net, 2, ',', '.') }} Kg
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 500; color: #475569;">
                                {{ $cat->store ?? '-' }}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 12px; line-height: 1.5;">
                            {{ $cat->short_address ?? '-' }}
                        </td>
                        <td>
                            @if($cat->purchase_price)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($cat->purchase_price, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            @if($cat->comparison_price_per_kg)
                                <div style="display: flex; width: 100%; font-size: 13px;">
                                    <span style="color: #64748b; font-weight: 500;">Rp</span>
                                    <span style="color: #0f172a; font-weight: 600; text-align: right; flex: 1; margin-left: 4px;">
                                        {{ number_format($cat->comparison_price_per_kg, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                <span style="color: #cbd5e1;">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('cats.show', $cat->id) }}"
                                   class="btn btn-primary btn-sm open-modal"
                                   title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="{{ route('cats.edit', $cat->id) }}"
                                   class="btn btn-warning btn-sm open-modal"
                                   title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('cats.destroy', $cat->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus cat ini?')">
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
            {{ $cats->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🎨</div>
            <p>{{ request('search') ? 'Tidak ada cat yang sesuai dengan pencarian' : 'Belum ada data cat' }}</p>
            @if(!request('search'))
                <a href="{{ route('cats.create') }}" class="btn btn-primary open-modal" style="margin-top: 16px;">
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

/* Table fixed column widths */
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

                if (!window.catFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/cat-form.js';
                    script.onload = () => {
                        window.catFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initCatForm === 'function') {
                                initCatForm(modalBody);
                            }
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initCatForm === 'function') {
                            initCatForm(modalBody);
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

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
});
</script>
@endsection