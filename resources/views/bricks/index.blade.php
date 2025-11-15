@extends('layouts.app')

@section('title', 'Database Bata')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Database Bata</h2>
        <a href="{{ route('bricks.create') }}" class="btn btn-success open-modal">+ Tambah Bata</a>
    </div>

    <!-- Search Form -->
    <form action="{{ route('bricks.index') }}" method="GET" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari jenis, merek, bentuk, toko..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search'))
                <a href="{{ route('bricks.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    @if($bricks->count() > 0)
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Material</th>
                        <th>Jenis</th>
                        <th style="width: 80px;">Foto</th>
                        <th>Merek</th>
                        <th>Bentuk</th>
                        <th>Dimensi (cm)</th>
                        <th>Volume (M3)</th>
                        <th>Toko</th>
                        <th>Alamat Singkat</th>
                        <th>Harga / Buah</th>
                        <th>Harga / M3</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bricks as $index => $brick)
                    <tr>
                        <td>{{ $bricks->firstItem() + $index }}</td>
                        <td><strong>{{ $brick->material_name }}</strong></td>
                        <td>{{ $brick->type ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($brick->photo_url)
                                <img src="{{ $brick->photo_url }}"
                                     alt="Photo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <span style="color: #95a5a6; display: none;">📷</span>
                            @else
                                <span style="color: #95a5a6;">-</span>
                            @endif
                        </td>
                        <td>{{ $brick->brand ?? '-' }}</td>
                        <td>{{ $brick->form ?? '-' }}</td>
                        <td>
                            @if($brick->dimension_length && $brick->dimension_width && $brick->dimension_height)
                                {{ number_format($brick->dimension_length, 1, ',', '.') }} × 
                                {{ number_format($brick->dimension_width, 1, ',', '.') }} × 
                                {{ number_format($brick->dimension_height, 1, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: right">
                            @if($brick->package_volume)
                                {{ number_format($brick->package_volume, 6, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $brick->store ?? '-' }}</td>
                        <td style="max-width: 300px; white-space: normal; word-break: break-word;">
                            {{ $brick->short_address ?? '-' }}
                        </td>
                        <td>
                            @if($brick->price_per_piece)
                                <div style="display: flex; width: 120px;">
                                    <span style="width: 30px;">Rp.</span>

                                    <span style="text-align: right; flex: 1;">
                                        {{ number_format($brick->price_per_piece, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($brick->comparison_price_per_m3)
                                <div style="display: flex; width: 120px;">
                                    <span style="width: 30px;">Rp.</span>

                                    <span style="text-align: right; flex: 1;">
                                        {{ number_format($brick->comparison_price_per_m3, 0, ',', '.') }}
                                    </span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="actions" style="justify-content: center;">
                                <a href="{{ route('bricks.show', $brick->id) }}" class="btn btn-primary btn-sm open-modal">Detail</a>
                                <a href="{{ route('bricks.edit', $brick->id) }}" class="btn btn-warning btn-sm open-modal">Edit</a>
                                <form action="{{ route('bricks.destroy', $brick->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data bata ini?')">
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
            {{ $bricks->links('pagination::simple-default') }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">🧱</div>
            <p>{{ request('search') ? 'Tidak ada data bata yang sesuai dengan pencarian' : 'Belum ada data bata' }}</p>
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

/* Scrollbar styling */
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

.raise {
    font-size: 0.7em;
    vertical-align: super;
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
                // Tidak perlu e.preventDefault() karena kita mau submit form biasa
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
                modalTitle.textContent = 'Tambah Data Bata Baru';
            } else if (url.includes('/edit')) {
                modalTitle.textContent = 'Edit Data Bata';
            } else {
                modalTitle.textContent = 'Detail Data Bata';
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
                    script.src = '/js/brick-form.js';
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
                modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #e74c3c;">Gagal memuat form. Silakan coba lagi.</div>';
                console.error('Fetch error:', err);
            });
        });
    });

    // Close modal
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #95a5a6;"><div style="font-size: 48px; margin-bottom: 10px;">⏳</div><div>Loading...</div></div>';
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
