@extends('layouts.app')

@section('title', 'Setting Rekomendasi Material')

@section('content')
<div id="recommendations-content-wrapper">
    <div class="card">
        <div class="card-header-actions">
            <h3 class="form-title"><i class="bi bi-bookmark-star-fill text-warning me-2"></i>Setting Rekomendasi Material</h3>
            <button type="button" class="recommendation-save-btn" id="btnSaveRecommendations">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
        </div>

        {{-- DROPDOWN SELECTOR --}}
        <div class="work-type-selector-wrapper">
            <label class="selector-label">Pilih Item Pekerjaan:</label>
            <select id="workTypeSelector" class="form-select work-type-dropdown">
                @foreach($formulas as $formulaIndex => $formula)
                    <option value="{{ $formula['code'] }}" {{ $formulaIndex === 0 ? 'selected' : '' }}>
                        {{ $formula['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <form action="{{ route('settings.recommendations.store') }}" method="POST" id="recommendationForm">
            @csrf

            {{-- CONTENT AREA (Changes based on dropdown selection) --}}
            <div id="workTypeContentWrapper">
                @foreach($formulas as $formulaIndex => $formula)
                    @php
                        $workTypeCode = $formula['code'];
                        $workTypeName = $formula['name'];
                        $workTypeRecs = $groupedRecommendations[$workTypeCode] ?? collect();
                        $isFirst = $formulaIndex === 0;
                    @endphp

                    <div class="work-type-content" data-work-type="{{ $workTypeCode }}" style="{{ $isFirst ? '' : 'display:none;' }}">
                        <div class="recommendation-tab-wrapper">
                            {{-- Tab Header --}}
                            <div class="recommendation-tab-header">
                                <div class="recommendation-tabs" data-work-type="{{ $workTypeCode }}">
                                    @forelse($workTypeRecs as $recIndex => $rec)
                                        <button type="button"
                                                class="recommendation-tab-btn {{ $recIndex === 0 ? 'active' : '' }}"
                                                data-tab="{{ $workTypeCode }}-rec-{{ $recIndex }}"
                                                data-work-type="{{ $workTypeCode }}">
                                            <span>Rekomendasi {{ $recIndex + 1 }}</span>
                                        </button>
                                    @empty
                                        <button type="button"
                                                class="recommendation-tab-btn active"
                                                data-tab="{{ $workTypeCode }}-rec-0"
                                                data-work-type="{{ $workTypeCode }}">
                                            <span>Rekomendasi 1</span>
                                        </button>
                                    @endforelse

                                    <button type="button" class="recommendation-add-btn" data-work-type="{{ $workTypeCode }}" title="Tambah Rekomendasi">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Tab Panels --}}
                            <div class="recommendation-tab-panels" data-work-type="{{ $workTypeCode }}">
                                @forelse($workTypeRecs as $recIndex => $rec)
                                    <div class="recommendation-tab-panel {{ $recIndex === 0 ? 'active' : 'hidden' }}"
                                         data-tab="{{ $workTypeCode }}-rec-{{ $recIndex }}"
                                         data-work-type="{{ $workTypeCode }}">
                                        @include('settings.recommendations.partials.row', [
                                            'index' => $workTypeCode . '_' . $recIndex,
                                            'rec' => $rec,
                                            'workTypeCode' => $workTypeCode,
                                            'formulas' => $formulas
                                        ])
                                    </div>
                                @empty
                                    <div class="recommendation-tab-panel active"
                                         data-tab="{{ $workTypeCode }}-rec-0"
                                         data-work-type="{{ $workTypeCode }}">
                                        @include('settings.recommendations.partials.row', [
                                            'index' => $workTypeCode . '_0',
                                            'rec' => null,
                                            'workTypeCode' => $workTypeCode,
                                            'formulas' => $formulas
                                        ])
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

    {{-- TEMPLATE ROW (Hidden) --}}
    <div id="rowTemplate" style="display: none;">
        @include('settings.recommendations.partials.row', ['index' => 'INDEX_PLACEHOLDER', 'rec' => null, 'formulas' => $formulas])
    </div>

    {{-- RAW DATA FOR JS (Used by modal and regular load) --}}
    <script type="application/json" id="recommendationRawData">
    {!! json_encode([
        'bricks' => $bricks,
        'cements' => $cements,
        'sands' => $sands,
        'formulas' => $formulas
    ]) !!}
    </script>
</div>
@endsection

@push('styles')
<style data-modal-style="recommendations-settings">
    /* Main wrapper and card - works for both page and modal */
    #recommendations-content-wrapper .card {
        background-color: #ffffff;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
    }

    /* Modal-specific adjustments */
    .modal-body #recommendations-content-wrapper .card {
        box-shadow: none;
        border: none;
        margin: 0;
        padding: 20px;
    }

    /* Remove padding from form to let tabs be full width */
    #recommendationForm {
        margin: 0;
        padding: 0;
    }

    #workTypeContentWrapper {
        margin: 0;
        padding: 0;
    }

    .card-header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .form-title {
        margin: 0;
        flex: 1;
    }

    /* Work Type Selector - Clean and modern */
    .work-type-selector-wrapper {
        padding: 20px;
        border-radius: 12px;
        background: linear-gradient(135deg, #fffbfb 0%, #fff5f5 100%);
        border: 1.5px solid #fecaca;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.08);
    }
    .selector-label {
        color: #891313;
        font-weight: 700;
        margin-bottom: 10px;
        display: block;
        font-size: 14px;
        letter-spacing: 0.3px;
    }
    .work-type-dropdown {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
    }

    /* Tab styles - Exact copy from materials/index.blade.php */
    .recommendation-tab-wrapper {
        margin-top: 20px;
        --tab-surface: #ffffff;
        --tab-foot-radius: 16px;
        margin-left: 0;
        margin-right: 0;
        padding: 0;
    }

    .recommendation-tab-header {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        margin-bottom: -1px;
        position: relative;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
        padding: 0;
        margin-left: 0;
        margin-right: 0;
        height: auto;
    }
    /* Horizontal scrollbar only */
    .recommendation-tab-header::-webkit-scrollbar {
        height: 6px;
        width: 0;
    }
    .recommendation-tab-header::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    .recommendation-tab-header::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .recommendation-tab-header::-webkit-scrollbar-corner {
        display: none;
    }
    .recommendation-tab-header::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        border-bottom: 1px solid #e2e8f0;
        z-index: 0;
    }
    .recommendation-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 1px;
        position: relative;
        z-index: 1;
        flex: 1;
        min-width: min-content;
        margin: 0;
        padding: 0;
    }
    .recommendation-tab-btn {
        --tab-border-color: transparent;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border: 1px solid #F6F3C2;
        border-bottom: none;
        border-radius: 12px 12px 0 0;
        text-decoration: none;
        color: #64748b;
        font-weight: 600;
        background: #f8fafc;
        transition: all 0.2s ease;
        cursor: pointer;
        margin-bottom: -1px;
        position: relative;
        white-space: nowrap;
        z-index: 1;
        font-size: 14px;
    }

    .recommendation-tab-btn::before,
    .recommendation-tab-btn::after {
        content: none;
    }

    .recommendation-tab-btn:hover {
        color: #891313;
        background: #fff5f5;
    }

    .recommendation-tab-btn.active {
        background: #F6F3C2;
        color: #891313;
        border-width: 2px;
        border-bottom: none;
        position: relative;
        z-index: 5;
        font-weight: 700;
        padding-bottom: 12px;
    }

    .recommendation-tab-btn.active::before,
    .recommendation-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        width: var(--tab-foot-radius);
        height: var(--tab-foot-radius);
        background: transparent;
        pointer-events: none;
        overflow: hidden;
    }

    .recommendation-tab-btn.active::before {
        right: 100%;
        background:
            radial-gradient(
                circle at 0 0,
                transparent calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) var(--tab-foot-radius),
                #F6F3C2 var(--tab-foot-radius)
            );
        background-position: bottom right;
    }

    .recommendation-tab-btn.active::after {
        left: 100%;
        background:
            radial-gradient(
                circle at 100% 0,
                transparent calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) var(--tab-foot-radius),
                #F6F3C2 var(--tab-foot-radius)
            );
        background-position: bottom left;
    }

    .recommendation-tab-btn.active.first-visible::before {
        content: none;
    }

    .recommendation-tab-btn.active.last-visible::after {
        content: none;
    }

    /* Add/Save buttons - Enhanced 3D floating effect */
    .recommendation-add-btn, .recommendation-save-btn {
        position: relative;
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateY(0);
        border: none !important;
        cursor: pointer;
        font-family: var(--primary-font);
        color: var(--text-color);
        -webkit-text-stroke: var(--text-stroke);
        text-shadow: var(--text-shadow);
    }
    .recommendation-add-btn:hover, .recommendation-save-btn:hover {
        box-shadow: 0 12px 20px -5px rgba(50, 50, 93, 0.25), 0 8px 16px -8px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);
    }
    .recommendation-add-btn:active, .recommendation-save-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(50, 50, 93, 0.11), 0 1px 2px rgba(0, 0, 0, 0.08);
    }
    .recommendation-add-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f8fafc;
        margin-bottom: 3px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #64748b;
        margin-left: 10px;
    }
    .recommendation-add-btn:hover {
        background: #fff;
        color: #891313;
    }
    .recommendation-save-btn {
        padding: 10px 24px;
        border-radius: 8px;
        background: #891313;
        color: #fff !important;
        -webkit-text-stroke: 0 !important;
        text-shadow: none !important;
        margin-bottom: 3px;
        margin-left: auto;
        white-space: nowrap;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .recommendation-save-btn:hover {
        background: #a51515;
    }

    /* Tab Panel - EXACT copy from materials/index.blade.php */
    .recommendation-tab-panel {
        padding-top: 0;
        margin-top: -1px;
    }
    .recommendation-tab-panel.hidden {
        display: none !important;
    }

    /* Outer wrapper with yellow background - EXACT like material-tab-card */
    .recommendation-card {
        border-radius: 0 0 12px 12px;
        background: #F6F3C2;
        padding: 20px;
        margin-top: 0;
        position: relative;
        z-index: 3;
    }
    .recommendation-tab-panel.active .recommendation-card {
        border-color: #891313;
        box-shadow:
            0 4px 6px -1px rgba(137, 19, 19, 0.08),
            0 2px 4px -1px rgba(137, 19, 19, 0.06);
    }

    /* Card header row styling */
    .card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(137, 19, 19, 0.15);
    }

    .card-title-text {
        font-weight: 600;
        font-size: 14px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Material grid - Direct layout without nested card appearance */
    .material-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    /* Individual material sections */
    .material-section {
        background: rgba(255, 255, 255, 0.6);
        padding: 16px;
        border-radius: 8px;
        border: 1px solid rgba(226, 232, 240, 0.5);
        transition: all 0.2s ease;
    }
    .material-section:hover {
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(203, 213, 225, 0.7);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    /* Section header - icon + text */
    .section-header {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        font-weight: 700;
        font-size: 10px;
        color: #64748b;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
    }
    .section-header i {
        font-size: 13px;
    }
    /* Form groups inside material sections */
    .form-group {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .form-group:last-child {
        margin-bottom: 0;
    }
    .form-group label {
        flex: 0 0 65px;
        margin-bottom: 0;
        text-align: left;
        font-weight: 500;
        color: #64748b;
        font-size: 11px;
    }
    .input-wrapper {
        flex: 1;
        min-width: 0;
    }

    /* Select styling */
    .recommendation-card select,
    .work-type-dropdown {
        width: 100%;
        padding: 8px 12px;
        font-family: var(--primary-font) !important;
        color: var(--text-color) !important;
        -webkit-text-stroke: var(--text-stroke) !important;
        text-shadow: var(--text-shadow) !important;
        background-color: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 6px;
        font-size: 13px;
        box-shadow: none !important;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .recommendation-card select:hover,
    .work-type-dropdown:hover {
        border-color: #cbd5e1 !important;
    }
    .recommendation-card select:focus,
    .work-type-dropdown:focus {
        outline: none !important;
        border-color: #891313 !important;
        border-width: 2px !important;
        box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1) !important;
        background-color: #fffbfb !important;
        transform: translateY(-1px);
    }

    /* Colored select backgrounds */
    .select-green { background-color: #d1fae5 !important; }
    .select-blue { background-color: #bfdbfe !important; }
    .select-pink { background-color: #fbcfe8 !important; }
    .select-orange { background-color: #fed7aa !important; }
    .select-gray { background-color: #e2e8f0 !important; }
    .select-gray-light { background-color: #f1f5f9 !important; }

    /* Work Type Content Visibility */
    .work-type-content {
        transition: opacity 0.3s ease;
        margin: 0;
        padding: 0;
    }
    .work-type-content[style*="display:none"],
    .work-type-content[style*="display: none"] {
        display: none !important;
    }

    /* Modal-specific overrides - Make modal look exactly like page */
    .modal-dialog {
        max-width: 1400px !important;
        width: 95vw !important;
    }

    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modal-body {
        padding: 0;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-body #recommendations-content-wrapper {
        width: 100%;
    }

    .modal-body #recommendations-content-wrapper .card {
        border: none;
        box-shadow: none;
        border-radius: 0;
        margin: 0;
    }

    /* Card header in modal - no sticky, clean spacing */
    .modal-body .card-header-actions {
        position: relative;
        background: #fff;
        z-index: 1;
        padding: 24px 24px 20px 24px;
        margin: 0;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Work type selector in modal */
    .modal-body .work-type-selector-wrapper {
        margin-left: 24px;
        margin-right: 24px;
    }

    /* Tab wrapper in modal - ensure same styling */
    .modal-body .recommendation-tab-wrapper {
        margin-left: 24px;
        margin-right: 24px;
        margin-top: 20px;
    }

    /* Tabs in modal - inherit all page styling */
    .modal-body .recommendation-tab-header,
    .modal-body .recommendation-tabs,
    .modal-body .recommendation-tab-btn,
    .modal-body .recommendation-tab-btn.active,
    .modal-body .recommendation-tab-panel,
    .modal-body .recommendation-card {
        /* All styling inherited from page version - no overrides */
    }

    /* Ensure tab buttons work in modal */
    .modal-body .recommendation-tab-btn {
        pointer-events: auto;
        cursor: pointer;
    }

    /* Scrollbar for modal body */
    .modal-body::-webkit-scrollbar {
        width: 10px;
    }
    .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 5px;
    }
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Delete button styling */
    .recommendation-card .btn-remove {
        background: #fff;
        color: #dc2626;
        border: 1.5px solid #dc2626;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .recommendation-card .btn-remove:hover {
        background: #fef2f2;
        border-color: #b91c1c;
        color: #b91c1c;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.2);
    }
    .recommendation-card .btn-remove:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .material-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 992px) {
        .material-grid {
            grid-template-columns: 1fr;
        }

        .recommendation-tab-btn {
            padding: 10px 16px;
            font-size: 13px;
        }

        .card-header-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .recommendation-save-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .recommendation-tab-panel {
            padding: 12px;
        }

        .recommendation-card {
            padding: 12px;
        }

        .material-section {
            padding: 12px;
        }

        .form-group {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .form-group label {
            flex: unset;
            width: 100%;
        }

        .work-type-selector-wrapper {
            padding: 12px;
        }
    }

    /* Print styles */
    @media print {
        .recommendation-add-btn,
        .recommendation-save-btn,
        .btn-danger {
            display: none !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/recommendations-form.js') }}?v={{ time() }}"></script>
<script>
    // This script block initializes the form for both page loads and modal loads
    (function() {
        function initForm() {
            if (typeof window.initRecommendationsForm === 'function') {
                console.log('Initializing Recommendations Form...');

                // Find the recommendations content wrapper
                const wrapper = document.querySelector('#recommendations-content-wrapper');
                if (wrapper) {
                    // Remove previous initialization flag to allow re-init
                    wrapper.removeAttribute('data-recommendations-form-initialized');

                    // Initialize the form
                    window.initRecommendationsForm(wrapper);

                    console.log('Recommendations Form initialized successfully');
                } else {
                    console.warn('Recommendations content wrapper not found');
                }
            } else {
                console.error('initRecommendationsForm function not found!');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initForm();
        });

        // Initialize when modal is shown (for Bootstrap modal)
        document.addEventListener('shown.bs.modal', function(event) {
            const modal = event.target;
            // Check if this modal contains recommendations form
            if (modal.querySelector('#recommendations-content-wrapper')) {
                console.log('Modal shown, initializing recommendations form...');
                setTimeout(function() {
                    initForm();
                }, 100);
            }
        });

        // Also listen for custom modal shown event (if using custom modal system)
        document.addEventListener('modalContentLoaded', function(event) {
            if (document.querySelector('#recommendations-content-wrapper')) {
                console.log('Modal content loaded, initializing recommendations form...');
                setTimeout(function() {
                    initForm();
                }, 100);
            }
        });

        // Global function to manually initialize (can be called from modal handler)
        window.initRecommendationsFormManually = function() {
            console.log('Manual initialization triggered...');
            initForm();
        };
    })();
</script>
@endpush