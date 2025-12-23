@extends('layouts.app')

@section('title', 'Setting Rekomendasi Material')

@section('content')
<div id="recommendations-content-wrapper">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-primary mb-1">
                    <i class="bi bi-star-fill me-2"></i>Setting Rekomendasi (TerBAIK)
                </h2>
                <p class="text-muted mb-0">
                    Atur kombinasi material yang akan muncul sebagai opsi "TerBAIK".
                </p>
            </div>
            <a href="{{ route('material-calculations.create') }}" class="btn btn-outline-secondary">
                <i class="bi bi-calculator me-1"></i> Ke Kalkulator
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('settings.recommendations.store') }}" method="POST" id="recommendationForm">
            @csrf
            
            <div id="recommendationList">
                @forelse($recommendations as $index => $rec)
                    @include('settings.recommendations.partials.row', ['index' => $index, 'rec' => $rec])
                @empty
                    {{-- Empty state will be handled by JS adding one row if needed --}}
                @endforelse
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-primary" id="btnAddRow">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Rekomendasi
                </button>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- TEMPLATE ROW (Hidden) --}}
    <div id="rowTemplate" style="display: none;">
        @include('settings.recommendations.partials.row', ['index' => 'INDEX_PLACEHOLDER', 'rec' => null])
    </div>

    {{-- RAW DATA FOR JS (Used by modal and regular load) --}}
    <script type="application/json" id="recommendationRawData">
    {!! json_encode([
        'bricks' => $bricks,
        'cements' => $cements,
        'sands' => $sands
    ]) !!}
    </script>
</div>
@endsection

@push('styles')
<style>
    .recommendation-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
        position: relative;
        transition: all 0.2s ease;
    }

    .recommendation-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: #cbd5e1;
    }

    .card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .card-title-text {
        font-weight: 700;
        color: #334155;
        font-size: 14px;
    }

    .material-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }

    .material-section {
        background: #f8fafc;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
    }

    .section-header {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .form-group label {
        flex: 0 0 80px;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 0;
        text-align: left;
    }

    .input-wrapper {
        flex: 1;
    }

    select.form-select {
        font-size: 13px;
        border-color: #cbd5e1;
        width: 100%;
    }

    /* Colored Selects */
    .select-green { background-color: #d1fae5 !important; border-color: #6ee7b7 !important; }
    .select-blue { background-color: #bfdbfe !important; border-color: #93c5fd !important; }
    .select-pink { background-color: #fbcfe8 !important; border-color: #f9a8d4 !important; }
    .select-orange { background-color: #fed7aa !important; border-color: #fdba74 !important; }
    .select-gray { background-color: #e2e8f0 !important; border-color: #cbd5e1 !important; }
    .select-gray-light { background-color: #f1f5f9 !important; border-color: #e2e8f0 !important; }

    @media (max-width: 992px) {
        .material-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/recommendations-form.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // If not loaded in modal, initialize here
        if (typeof initRecommendationsForm === 'function') {
            const container = document.body;
            const dataEl = document.getElementById('recommendationRawData');
            if (dataEl && !window.globalrecommendationsFormScriptLoaded) {
                const rawData = JSON.parse(dataEl.textContent);
                initRecommendationsForm(container, rawData);
            }
        }
    });
</script>
@endpush