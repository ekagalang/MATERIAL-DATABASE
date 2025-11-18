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
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Material Type</th>
                        <th>Kode</th>
                        <th>Nama Satuan</th>
                        <th>Berat Kemasan (Kg)</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $index => $unit)
                    <tr>
                        <td style="text-align: center; font-weight: 500; color: #64748b;">
                            {{ $units->firstItem() + $index }}
                        </td>
                        <td>
                            <span style="display: inline-block; padding: 4px 10px; background: #f1f5f9; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569;">
                                {{ ucfirst($unit->material_type) }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-weight: 600;">{{ $unit->code }}</strong>
                        </td>
                        <td style="color: #475569;">{{ $unit->name }}</td>
                        <td style="text-align: right; color: #475569; font-size: 13px;">
                            {{ $unit->package_weight }}
                        </td>
                        <td style="color: #64748b; font-size: 13px;">{{ $unit->description ?? '-' }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('units.edit', $unit->id) }}"
                                   class="btn btn-warning btn-sm open-modal"
                                   title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <form action="{{ route('units.destroy', $unit->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin ingin menghapus satuan ini?')"
                                      style="display: inline;">
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

        <!-- Pagination -->
        <div style="margin-top: 24px;">
            {{ $units->links() }}
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon">📏</div>
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