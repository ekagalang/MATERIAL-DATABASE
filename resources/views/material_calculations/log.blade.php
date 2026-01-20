@extends('layouts.app')

@push('styles')
<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
<style>
    /* Override for form controls to keep them readable */
    .form-control, .form-select, .input-group-text {
        color: #1e293b !important;
        -webkit-text-stroke: 0 !important;
        text-shadow: none !important;
    }

    /* Exception for Total Biaya - keep green */
    .text-success, .text-success strong {
        color: #059669 !important;
        -webkit-text-stroke: 0 !important;
        text-shadow: 0 !important;
    }

    .flatpickr-calendar {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #891313;
        border-color: #f3abae;
    }
    .flatpickr-day.inRange {
        background: #FDACAC;
        border-color: #FDACAC;
        box-shadow: none;
    }

    /* Custom Kanggo Pagination Styles */
    .kanggo-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px 0;
    }

    .kanggo-logo img {
        height: 62px;
        width: auto;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        transition: transform 0.3s ease;
    }

    /* Hover removed as requested */

    .kanggo-pages {
        display: flex;
        align-items: center;
        gap: 2px;
        padding: 4px 2px;
     }

    .page-number, .page-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: transparent;
        border: none;
        font-family: 'Nunito', sans-serif !important;
        font-weight: 800;
    }

    .page-number {
        width: 20px;
        height: 20px;
        color: #64748b;
        font-size: 11px;
        position: relative;
        z-index: 1;
    }

    /* The Donut Frame using number.png */
    .page-number::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('/Pagination/number.png');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        z-index: -1;
        filter: grayscale(100%);
        opacity: 0.4;
        transition: all 0.25s ease;
    }

    .page-number:hover {
        transform: translateY(-3px);
        color: #891313;
    }

    .page-number:hover::before {
        filter: grayscale(0%) drop-shadow(0 5px 15px rgba(137, 19, 19, 0.3));
        opacity: 1;
    }

    .page-number.active {
        color: #891313;
    }

    .page-number.active::before {
        filter: grayscale(0%) drop-shadow(0 8px 20px rgba(137, 19, 19, 0.4));
        opacity: 1;
    }

    .page-arrow {
        width: 38px;
        height: 38px;
        color: #891313;
        background: #ffffff;
        border-radius: 50%;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        font-size: 18px;
        border: 1px solid #e2e8f0;
    }

    .page-arrow:not(.disabled):hover {
        transform: translateY(-3px);
        background: #891313;
        color: #ffffff;
        box-shadow: 0 5px 12px rgba(137, 19, 19, 0.2);
    }

    .page-arrow.disabled {
        opacity: 0.3;
        cursor: not-allowed;
        background: #f1f5f9;
    }

    .page-dots {
        color: #cbd5e1;
        font-weight: 700;
        margin: 0 4px;
    }

    .log-style
    {
        color: var(--text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        font-size: 32px;
    }
</style>
@endpush

@section('content')
<!-- Header -->
<div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1 log-style">
                        Riwayat Perhitungan 
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('work-items.index') }}" class="btn-cancel" style="border: 1px solid #64748b; background-color: transparent; color: #64748b; padding: 8px 16px; font-size: 14px; font-weight: 600; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <a href="{{ route('material-calculations.create') }}" class="btn btn-primary-glossy ">
                        <i class="bi bi-plus-lg"></i> Perhitungan Baru
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <form action="{{ route('material-calculations.log') }}" method="GET">
        @php
            $hasFilter = request('search') || request('work_type') || request('date_from') || request('date_to');
        @endphp

        <div class="d-flex flex-wrap align-items-end gap-3">
            {{-- Search Input (Flexible Width) --}}
            <div class="flex-grow-1" style="min-width: 250px;">
                <label class="form-label fw-bold text-dark small mb-1">Pencarian</label>
                <input type="text"
                    class="form-control"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari Luas, Jenis, Biaya, Merek...">
            </div>

            {{-- Work Type Select (Fixed Width) --}}
            <div style="flex: 0 0 220px;">
                <label class="form-label fw-bold text-dark small mb-1">Jenis Pekerjaan</label>
                <select class="form-select" name="work_type">
                    <option value="">-- Semua Jenis --</option>
                    @foreach($availableFormulas as $formula)
                        <option value="{{ $formula['code'] }}"
                            {{ request('work_type') == $formula['code'] ? 'selected' : '' }}>
                            {{ $formula['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range (Fixed Width) --}}
            <div style="flex: 0 0 200px;">
                <label>Rentang Tanggal</label>
                <input type="text"
                    class="form-control"
                    id="dateRangePicker"
                    placeholder="Pilih Tanggal"
                    readonly>
                <input type="hidden" name="date_from" id="dateFrom" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" id="dateTo" value="{{ request('date_to') }}">
            </div>

            {{-- Action Buttons --}}
            <div style="flex: 0 0 auto;">
                @if($hasFilter)
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary-glossy " title="Cari" style="min-width: 42px;">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="{{ route('material-calculations.log') }}" class="btn btn-danger" title="Reset" style="min-width: 42px;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                @else
                    <button type="submit" class="btn btn-primary-glossy " style="min-width: 42px;">
                        <i class="bi bi-search"></i>
                    </button>
                @endif
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px; overflow: hidden; background: transparent;">
        <div class="card-body p-0">
            @if($calculations->count() > 0)
                <div class="table-responsive">
                    <style>
                        .table-preview {
                            width: 100%;
                            border-collapse: separate;
                            border-spacing: 0;
                            font-size: 13px;
                            margin: 0;
                            background: #fff;
                        }
                        .table-preview th {
                            background: #891313 !important;
                            color: #ffffff !important;
                            text-align: center;
                            font-weight: 900;
                            padding: 10px 16px;
                            border: none;
                            font-size: 16px;
                            letter-spacing: 0.3px;
                            white-space: nowrap;
                        }
                        .table-preview td {
                            padding: 12px 16px;
                            border-bottom: 1px solid #f1f5f9;
                            vertical-align: middle;
                            color: #1e293b !important;
                            -webkit-text-stroke: 0 !important;
                            text-shadow: none !important;
                        }
                        /* Restore Spartan for specific elements in TD */
                        .table-preview td .text-success strong {
                            color: #059669 !important;
                        }
                        .table-preview tbody tr:hover td {
                            background-color: #f8fafc;
                        }
                        .table-preview tbody tr:last-child td {
                            border-bottom: none;
                        }
                    </style>
                    <table class="table-preview">
                        <thead>
                            <tr>
                                <th style="border-top-left-radius: 16px;">Tanggal</th>
                                <th>Luas Bidang</th>
                                <th>Item Pekerjaan</th>
                                <th>Total Biaya</th>
                                <th style="border-top-right-radius: 16px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calculations as $calc)
                            <tr>
                                <td class="text-start">
                                    <div>{{ $calc->created_at->format('d M Y') }} {{ $calc->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="text-center">
                                    <span class="text-dark">
                                        @format($calc->wall_area) M2
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary w-100 py-2">
                                        @php
                                            $workType = $calc->calculation_params['work_type'] ?? null;
                                            // Use FormulaRegistry to get dynamic names
                                            $formulaNames = \App\Services\FormulaRegistry::options();
                                            $displayName = $formulaNames[$workType] ?? ($calc->installationType->name ?? '-');
                                        @endphp
                                        {{ $displayName }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="text-success">
                                        <strong>@currency($calc->total_material_cost)</strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('material-calculations.show', $calc) }}"
                                           class="btn btn-primary-glossy btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('material-calculations.edit', $calc) }}"
                                           class="btn btn-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="{{ route('material-calculations.destroy', $calc) }}" method="POST" class="d-inline" data-confirm="Hapus data ini?" data-confirm-ok="Hapus" data-confirm-cancel="Batal">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
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
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fa-3x text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">Tidak ada data perhitungan</h5>
                    <p class="text-muted">Silakan buat perhitungan baru</p>
                    <a href="{{ route('material-calculations.create') }}" class="btn btn-primary-glossy ">
                        <i class="bi bi-plus-lg"></i> Buat Perhitungan
                    </a>
                </div>
            @endif
        </div>
        
        @if($calculations->count() > 0)
            <div class="card-footer bg-white border-top-0 pt-2 pb-0">
                <div class="kanggo-pagination">
                    <div class="kanggo-logo">
                        <img src="/Pagination/kangg.png" alt="Kanggo">
                    </div>
                    <div class="kanggo-pages">
                        {{-- Pagination Elements --}}
                        @foreach ($calculations->links()->elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <span class="page-dots">{{ $element }}</span>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $calculations->currentPage())
                                        <span class="page-number active">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="page-number">{{ $page }}</a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
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

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current date_from and date_to values from hidden inputs
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    
    const dateFromVal = dateFromInput.value;
    const dateToVal = dateToInput.value;

    // Helper: Parse YYYY-MM-DD string to Date object (local time)
    const parseDate = (str) => {
        if (!str) return null;
        const parts = str.split('-');
        if (parts.length !== 3) return null;
        // Note: Month is 0-indexed in JS Date
        return new Date(parts[0], parts[1] - 1, parts[2]);
    };

    const defaultStartDate = parseDate(dateFromVal);
    const defaultEndDate = parseDate(dateToVal);
    const defaultDates = (defaultStartDate && defaultEndDate) ? [defaultStartDate, defaultEndDate] : null;

    // Initialize Flatpickr
    const fp = flatpickr("#dateRangePicker", {
        mode: "range",
        dateFormat: "d/m/Y",
        locale: "id",
        allowInput: false,
        showMonths: 2,
        defaultDate: defaultDates,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Format dates as YYYY-MM-DD for backend
                const formatDate = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                dateFromInput.value = formatDate(selectedDates[0]);
                dateToInput.value = formatDate(selectedDates[1]);
            } else if (selectedDates.length === 0) {
                // Clear the hidden inputs when date range is cleared
                dateFromInput.value = '';
                dateToInput.value = '';
            }
        },
        onClose: function(selectedDates, dateStr, instance) {
            // Clear if only one date selected (incomplete range)
            if (selectedDates.length === 1) {
                instance.clear();
                dateFromInput.value = '';
                dateToInput.value = '';
            }
        }
    });

    // Show/hide clear button logic removed as requested - using main reset button instead
});
</script>
@endsection

