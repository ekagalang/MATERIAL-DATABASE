@extends('layouts.app')

@section('title', 'Setting Rekomendasi Material')

@section('content')
<style>
    .unit-style {
        color: var(--special-text-color);
        font-weight: var(--special-font-weight);
        -webkit-text-stroke: var(--special-text-stroke);
        font-size: 32px;
    }
</style>

<div id="recommendations-content-wrapper">
    <div class="card-header-actions">
            <h3 class="unit-style">Setting Rekomendasi Material</h3>
            <button type="button" class="btn btn-primary-glossy" id="btnSaveRecommendations">
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

    {{-- TEMPLATE ROW (Hidden) --}}
    <div id="rowTemplate" style="display: none;">
        @include('settings.recommendations.partials.row', ['index' => 'INDEX_PLACEHOLDER', 'rec' => null, 'formulas' => $formulas])
    </div>

    {{-- RAW DATA FOR JS (Used by modal and regular load) --}}
    <script type="application/json" id="recommendationRawData">
    {!! json_encode([
        'bricks' => $bricks,
        'cements' => $cements,
        'nats' => $nats,
        'sands' => $sands,
        'cats' => $cats,
        'ceramics' => $ceramics,
        'formulas' => $formulas
    ]) !!}
    </script>
</div>
@endsection

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
