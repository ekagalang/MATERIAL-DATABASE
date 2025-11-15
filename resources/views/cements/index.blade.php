@extends('layouts.app')

@section('title', 'Database Semen')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Database Semen</h2>
        <a href="{{ route('cements.create') }}" class="btn btn-success open-modal">+ Tambah Semen</a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('cements.index') }}" method="GET" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari jenis, merek, code, warna, toko..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search'))
                <a href="{{ route('cements.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    @if($cements->count() > 0)
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Merek</th>
                        <th>Sub Merek</th>
                        <th>Code</th>
                        <th>Warna</th>
                        <th>Kemasan</th>
                        <th>Berat Bersih</th>
                        <th>Toko</th>
                        <th>Alamat Singkat</th>
                        <th>Harga Kemasan</th>
                        <th>Harga/Kg</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cements as $index => $cement)
                    <tr>
                        <td>{{ $cements->firstItem() + $index }}</td>
                        <td><strong>Semen</strong></td>
                        <td>{{ $cement->type ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($cement->photo_url)
                                <img src="{{ $cement->photo_url }}"
                                     alt="Photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span style="color: #95a5a6; display: none;">📷</span>
                            @else
                                <span style="color: #95a5a6;">-</span>
                            @endif
                        </td>
                        <td>{{ $cement->brand ?? '-' }}</td>
                        <td>{{ $cement->sub_brand ?? '-' }}</td>
                        <td>{{ $cement->code ?? '-' }}</td>
                        <td>{{ $cement->color ?? '-' }}</td>
                        <td>
                            @if($cement->package_unit)
                                {{ $cement->package_weight_gross }} {{ $cement->package_unit }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($cement->package_weight_net)
                                {{ number_format($cement->package_weight_net, 2, ',', '.') }} Kg
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $cement->store ?? '-' }}</td>
                        <td>{{ $cement->short_address ?? '-' }}</td>
                        <td style="text-align: right;">
                            @if($cement->package_price)
                                <div style="display: flex; width: 120px;">
                                    <span style="width: 30px;">Rp.</span>
                                    <span style="text-align: right; flex: 1;">
                                        {{ number_format($cement->package_price, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($cement->comparison_price_per_kg)
                                <div style="display: flex; width: 120px;">
                                    <span style="width: 30px;">Rp.</span>
                                    <span style="text-align: right; flex: 1;">
                                        {{ number_format($cement->comparison_price_per_kg, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="actions" style="justify-content: center;">
                                <a href="{{ route('cements.show', $cement->id) }}" class="btn btn-primary btn-sm open-modal">Detail</a>
                                <a href="{{ route('cements.edit', $cement->id) }}" class="btn btn-warning btn-sm open-modal">Edit</a>
                                <form action="{{ route('cements.destroy', $cement->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus semen ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
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
            <div style="text-align: center; padding: 40px; color: #95a5a6;">
                <div style="font-size: 48px; margin-bottom: 10px;">⏳</div>
                <div>Loading...</div>
            </div>
        </div>
    </div>
</div>

<style>
.floating-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
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
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.floating-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 95%;
    max-height: 95vh;
    width: 1200px;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

.floating-modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #e3e3e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #2c3e50;
}

.floating-modal-close {
    background: none;
    border: none;
    font-size: 32px;
    color: #95a5a6;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.floating-modal-close:hover {
    background: #e74c3c;
    color: #fff;
}

.floating-modal-body {
    padding: 30px;
    overflow-y: auto;
    max-height: calc(95vh - 80px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translate(-50%, -45%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.floating-modal-body::-webkit-scrollbar {
    width: 8px;
}

.floating-modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.floating-modal-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.floating-modal-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}
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
                modalTitle.textContent = 'Tambah Semen Baru';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Semen';
            } else {
                modalTitle.textContent = 'Detail Semen';
            }
            
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;

                if (!window.cementFormScriptLoaded) {
                    const script = document.createElement('script');
                    script.src = '/js/cement-form.js';
                    script.onload = () => {
                        window.cementFormScriptLoaded = true;
                        setTimeout(() => {
                            if (typeof initCementForm === 'function') {
                                initCementForm();
                            }
                            interceptFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof initCementForm === 'function') {
                            initCementForm();
                        }
                        interceptFormSubmit();
                    }, 100);
                }
            })
            .catch(error => {
                modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;"><div style="font-size: 48px; margin-bottom: 10px;">❌</div><div>Gagal memuat form</div></div>';
            });
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #95a5a6;"><div style="font-size: 48px; margin-bottom: 10px;">⏳</div><div>Loading...</div></div>';
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