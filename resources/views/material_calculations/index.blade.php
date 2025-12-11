@extends('layouts.app')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="mb-5">
                <i class="bi bi-calculator" style="font-size: 5rem; color: #0d6efd;"></i>
            </div>

            <h1 class="display-4 mb-3">Kalkulator Material</h1>
            <p class="lead text-muted mb-5">
                Hitung kebutuhan material untuk berbagai jenis pekerjaan konstruksi dengan mudah dan akurat
            </p>

            <div class="d-grid gap-3 d-md-flex justify-content-md-center mb-5">
                <a href="{{ route('price-analysis.index') }}" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-plus-circle me-2"></i>
                    Buat Pekerjaan Baru
                </a>
                <a href="{{ route('material-calculations.log') }}" class="btn btn-outline-secondary btn-lg px-5">
                    <i class="bi bi-clock-history me-2"></i>
                    Lihat Riwayat
                </a>
            </div>

            <div class="row mt-5 pt-5">
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-speedometer2 text-primary" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-3">Perhitungan Cepat</h5>
                        <p class="text-muted">Dapatkan hasil perhitungan material dalam hitungan detik</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-layers text-primary" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-3">Multi Jenis Pekerjaan</h5>
                        <p class="text-muted">Support berbagai jenis pekerjaan dengan formula yang berbeda</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-graph-up text-primary" style="font-size: 2.5rem;"></i>
                        <h5 class="mt-3">Trace Perhitungan</h5>
                        <p class="text-muted">Lihat detail step-by-step setiap perhitungan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Modal Container -->
<div id="floatingModal" class="floating-modal">
    <div class="floating-modal-backdrop"></div>
    <div class="floating-modal-content">
        <div class="floating-modal-header">
            <h2 id="modalTitle">Buat Pekerjaan Baru</h2>
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
}

.floating-modal-content {
    position: relative;
    width: 50%;
    max-width: 1400px;
    max-height: 90vh;
    margin: 5vh auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.floating-modal-header {
    padding: 24px 32px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.floating-modal-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
}

.floating-modal-close {
    width: 36px;
    height: 36px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    font-size: 24px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.floating-modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
    transform: rotate(90deg);
}

.floating-modal-body {
    padding: 32px;
    overflow-y: auto;
    flex: 1;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .floating-modal-content {
        width: 100%;
        max-width: 100%;
        height: 100%;
        max-height: 100%;
        margin: 0;
        border-radius: 0;
    }

    .floating-modal-header {
        padding: 20px;
    }

    .floating-modal-body {
        padding: 20px;
    }
}
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('floatingModal');
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');
    const closeBtn = document.getElementById('closeModal');
    const backdrop = document.querySelector('.floating-modal-backdrop');

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

    function syncModalStyles(doc) {
        if (!doc) return;
        document.head.querySelectorAll('style[data-modal-style]').forEach(el => el.remove());
        doc.querySelectorAll('style[data-modal-style]').forEach(style => {
            const clone = document.createElement('style');
            clone.setAttribute('data-modal-style', 'material-calculation');
            clone.textContent = style.textContent;
            document.head.appendChild(clone);
        });
    }

    function initCreateForm(payload) {
        ensureScriptLoaded('/js/material-calculation-form.js', 'materialCalculationFormLoaded', function() {
            if (typeof initMaterialCalculationForm === 'function') {
                initMaterialCalculationForm(modalBody, payload);
            }
        });
    }

    document.querySelectorAll('.open-modal').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            modalBody.innerHTML = placeholder;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const content = doc.querySelector('form') || doc.querySelector('.container-fluid') || doc.body;
                    syncModalStyles(doc);
                    const createPayload = parseJsonPayload(doc, 'materialCalculationFormData');

                    modalBody.innerHTML = content ? content.outerHTML : html;

                    (function bootstrapWorkTypeToggle() {
                        const selector = modalBody.querySelector('#workTypeSelector');
                        const inputContainer = modalBody.querySelector('#inputFormContainer');
                        const brickForm = modalBody.querySelector('#brickForm');
                        const otherForm = modalBody.querySelector('#otherForm');
                        function toggle() {
                            if (!selector || !inputContainer) return;
                            const value = selector.value;
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
                        modalTitle.textContent = 'Buat Pekerjaan Baru';
                        initCreateForm(createPayload);
                    } else {
                        modalTitle.textContent = 'Detail';
                    }
                })
                .catch(err => {
                    modalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #ef4444;"><div style="font-size: 48px; margin-bottom: 16px;">!</div><div style="font-weight: 500;">Gagal memuat form. Silakan coba lagi.</div></div>';
                    console.error('Fetch error:', err);
                });
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        setTimeout(() => {
            modalBody.innerHTML = placeholder;
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
@endpush
