@extends('layouts.app')

@section('content')
<div class="card">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-history text-primary"></i> 
                        Riwayat Perhitungan
                    </h2>
                    <p class="text-muted mb-0">Daftar semua perhitungan yang pernah dibuat</p>
                </div>
                <div>
                    <a href="{{ route('material-calculations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Perhitungan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('material-calculations.log') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Cari project...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="installation_type">
                            <option value="">-- Semua Jenis --</option>
                            @foreach($installationTypes as $type)
                                <option value="{{ $type->id }}" 
                                        {{ request('installation_type') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               class="form-control" 
                               name="date_from" 
                               value="{{ request('date_from') }}"
                               placeholder="Dari">
                    </div>
                    <div class="col-md-2">
                        <input type="date" 
                               class="form-control" 
                               name="date_to" 
                               value="{{ request('date_to') }}"
                               placeholder="Sampai">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($calculations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Project</th>
                                <th>Dimensi</th>
                                <th>Jenis</th>
                                <th>Bata</th>
                                <th>Semen</th>
                                <th>Pasir (m³)</th>
                                <th>Total Biaya</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calculations as $calc)
                            <tr>
                                <td class="text-nowrap">
                                    {{ $calc->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ $calc->project_name ?: '-' }}</strong>
                                    @if($calc->notes)
                                        <br><small class="text-muted">{{ Str::limit($calc->notes, 30) }}</small>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $calc->wall_length }}m × {{ $calc->wall_height }}m
                                    <br><small class="text-muted">({{ number_format($calc->wall_area, 2) }} m²)</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $calc->installationType->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->brick_quantity, 0) }} buah
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->cement_quantity_sak ?? $calc->cement_quantity_50kg, 2) }} sak
                                    <br><small class="text-muted">({{ $calc->cement_package_weight ?? 50 }}kg)</small>
                                </td>
                                <td class="text-end">
                                    {{ number_format($calc->sand_m3, 4) }} m³
                                </td>
                                <td class="text-end text-success">
                                    <strong>Rp {{ number_format($calc->total_material_cost, 0, ',', '.') }}</strong>
                                </td>
                                <td class="text-end text-nowrap">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('material-calculations.show', $calc) }}" 
                                           class="btn btn-outline-primary"
                                           title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('material-calculations.edit', $calc) }}" 
                                           class="btn btn-outline-warning"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('material-calculations.destroy', $calc) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus perhitungan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-outline-danger"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
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
                <div class="card-footer bg-white">
                    {{ $calculations->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada data perhitungan</h5>
                    <p class="text-muted">Silakan buat perhitungan baru</p>
                    <a href="{{ route('material-calculations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Perhitungan
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Floating Modal Container -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Perhitungan</h2>
            <button class="floating-modal-close" id="closeModal">&times;</button>
        </div>
        <div class="floating-modal-body" id="modalBody">
            <div style="text-align: center; padding: 60px; color: #94a3b8;">
                <div style="font-size: 48px; margin-bottom: 16px;">⌛</div>
                <div style="font-weight: 500;">Loading...</div>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = modal.querySelector('.floating-modal-backdrop');

    const placeholder = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⌛</div><div style="font-weight: 500;">Loading...</div></div>';

    function parseJsonPayload(doc, id) {
        const el = doc.getElementById(id);
        if (!el) return null;
        try {
            return JSON.parse(el.textContent);
        } catch (error) {
            console.error('Gagal parse payload', error);
            return null;
        }
    }

    function ensureScriptLoaded(src, flagName, callback) {
        if (window[flagName]) {
            callback();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.onload = function() {
            window[flagName] = true;
            callback();
        };
        document.head.appendChild(script);
    }

    function initCreateForm(payload) {
        ensureScriptLoaded('/js/material-calculation-form.js', 'materialCalculationFormLoaded', function() {
            if (typeof initMaterialCalculationForm === 'function') {
                initMaterialCalculationForm(modalBody, payload);
            }
        });
    }

    function initEditForm(payload) {
        ensureScriptLoaded('/js/material-calculation-edit.js', 'materialCalculationEditLoaded', function() {
            if (typeof initMaterialCalculationEdit === 'function') {
                initMaterialCalculationEdit(modalBody, payload);
            }
        });
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(function() {
            modalBody.innerHTML = placeholder;
        }, 200);
    }

    document.querySelectorAll('.open-modal').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            modalBody.innerHTML = placeholder;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const createPayload = parseJsonPayload(doc, 'materialCalculationFormData');
                    const editPayload = parseJsonPayload(doc, 'materialCalculationEditData');
                    const content = doc.querySelector('form') || doc.querySelector('.card') || doc.body;

                    modalBody.innerHTML = content ? content.outerHTML : html;

                    // Simple fallback to reveal form if script belum ready
                    (function bootstrapWorkTypeToggle() {
                        const selector = modalBody.querySelector('#workTypeSelector');
                        const inputContainer = modalBody.querySelector('#inputFormContainer');
                        const brickForm = modalBody.querySelector('#brickForm');
                        const otherForm = modalBody.querySelector('#otherForm');

                        function toggle() {
                            const value = selector ? selector.value : '';
                            if (!selector || !inputContainer) return;

                            if (!value) {
                                inputContainer.style.display = 'none';
                                return;
                            }

                            inputContainer.style.display = 'block';
                            if (brickForm) brickForm.style.display = value.includes('brick') ? 'block' : 'none';
                            if (otherForm) otherForm.style.display = value.includes('brick') ? 'none' : 'block';
                        }

                        if (selector) {
                            selector.addEventListener('change', toggle);
                            toggle();
                        }
                    })();

                    if (url.includes('/create')) {
                        modalTitle.textContent = 'Perhitungan Material Baru';
                        initCreateForm(createPayload);
                    } else if (url.includes('/edit')) {
                        modalTitle.textContent = 'Edit Perhitungan';
                        initEditForm(editPayload);
                    } else {
                        modalTitle.textContent = 'Detail Perhitungan';
                    }
                })
                .catch(function(error) {
                    console.error('Fetch error:', error);
                    modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">!</div><div style="font-weight: 500;">Gagal memuat konten. Coba lagi.</div></div>';
                });
        });
    });

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

