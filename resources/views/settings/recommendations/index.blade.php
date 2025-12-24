@extends('layouts.app')

@section('title', 'Setting Rekomendasi Material')

@section('content')
<div id="recommendations-content-wrapper">
    <div class="card">
        <div class="recommendation-tab-wrapper">
            <div class="recommendation-tab-header">
                <div class="recommendation-tabs" id="recommendationTabs">
                    @forelse($recommendations as $index => $rec)
                        <button type="button"
                                class="recommendation-tab-btn {{ $index === 0 ? 'active' : '' }}"
                                data-tab="rec-{{ $index }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                            <span>Rekomendasi {{ $index + 1 }}</span>
                        </button>
                    @empty
                        <button type="button"
                                class="recommendation-tab-btn active"
                                data-tab="rec-0"
                                aria-selected="true">
                            <span>Rekomendasi 1</span>
                        </button>
                    @endforelse

                    <button type="button" class="recommendation-add-btn" id="btnAddTab" title="Tambah Rekomendasi">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>

                <button type="button" class="recommendation-save-btn" id="btnSaveRecommendations">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
            </div>

            <form action="{{ route('settings.recommendations.store') }}" method="POST" id="recommendationForm">
                @csrf

                <div id="recommendationList">
                    @forelse($recommendations as $index => $rec)
                        <div class="recommendation-tab-panel {{ $index === 0 ? 'active' : 'hidden' }}" data-tab="rec-{{ $index }}">
                            @include('settings.recommendations.partials.row', ['index' => $index, 'rec' => $rec])
                        </div>
                    @empty
                        <div class="recommendation-tab-panel active" data-tab="rec-0">
                            @include('settings.recommendations.partials.row', ['index' => 0, 'rec' => null])
                        </div>
                    @endforelse
                </div>
            </form>
        </div>
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
    /* Tab Wrapper */
    .recommendation-tab-wrapper {
        --tab-surface: #ffffff;
        --tab-foot-radius: 16px;
    }

    /* Tab Header */
    .recommendation-tab-header {
        display: flex;
        align-items: flex-end;
        gap: 5px;
        margin-bottom: -1px;
        position: relative;
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

    /* Tabs Container */
    .recommendation-tabs {
        display: flex;
        flex-wrap: wrap;
        margin: 0;
        gap: 1px;
        padding: 0px;
        position: relative;
        z-index: 1;
        flex: 1;
    }

    /* Tab Button */
    .recommendation-tab-btn {
        --tab-border-color: #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        border: 1px solid #e2e8f0;
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
    }

    .recommendation-tab-btn:hover {
        color: #891313;
        background: #fff5f5;
    }

    .recommendation-tab-btn.active {
        --tab-border-color: #891313;
        background: #FCF8E8;
        color: #891313;
        border-color: #891313;
        border-width: 2px;
        border-bottom: none;
        position: relative;
        z-index: 5;
        font-weight: 700;
        padding-bottom: 14px;
    }

    .recommendation-tab-btn.active::before,
    .recommendation-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        width: var(--tab-foot-radius);
        height: var(--tab-foot-radius);
        background: transparent;
        pointer-events: none;
    }

    .recommendation-tab-btn.active::before {
        right: 100%;
        background:
            radial-gradient(
                circle at 0 0,
                transparent calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) calc(var(--tab-foot-radius) - 2px),
                var(--tab-border-color) var(--tab-foot-radius),
                #FCF8E8 var(--tab-foot-radius)
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
                #FCF8E8 var(--tab-foot-radius)
            );
        background-position: bottom left;
    }

    /* Hide left curve on first tab */
    .recommendation-tab-btn.active.first-tab::before {
        content: none;
    }

    /* Hide right curve on last tab */
    .recommendation-tab-btn.active.last-tab::after {
        content: none;
    }

    /* Add button */
    .recommendation-add-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 3px;
    }

    .recommendation-add-btn:hover {
        background: #891313;
        color: #ffffff;
        border-color: #891313;
    }

    /* Save button */
    .recommendation-save-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 24px;
        border: 2px solid #891313;
        border-radius: 8px;
        background: #891313;
        color: #ffffff;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 3px;
        margin-left: auto;
        white-space: nowrap;
    }

    .recommendation-save-btn:hover {
        background: #6d0f0f;
        border-color: #6d0f0f;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(137, 19, 19, 0.2);
    }

    /* Tab Panel */
    .recommendation-tab-panel {
        padding: 20px;
        background: #FCF8E8;
        border: 2px solid #891313;
        border-top: none;
        margin-top: -1px;
    }

    .recommendation-tab-panel.hidden {
        display: none;
    }

    .recommendation-tab-panel.active {
        display: block;
    }

    /* Existing card styles */
    .recommendation-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
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
        const dataEl = document.getElementById('recommendationRawData');
        let rawData = null;

        if (dataEl) {
            rawData = JSON.parse(dataEl.textContent);
        }

        // Tab switching functionality
        function switchTab(tabId) {
            // Update buttons
            document.querySelectorAll('.recommendation-tab-btn').forEach(btn => {
                const isActive = btn.dataset.tab === tabId;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            // Update panels
            document.querySelectorAll('.recommendation-tab-panel').forEach(panel => {
                const isActive = panel.dataset.tab === tabId;
                panel.classList.toggle('hidden', !isActive);
                panel.classList.toggle('active', isActive);
            });

            // Update first/last tab classes
            updateTabPositionClasses();
        }

        // Update first and last tab classes
        function updateTabPositionClasses() {
            const allTabs = document.querySelectorAll('.recommendation-tab-btn');

            // Remove all position classes first
            allTabs.forEach(tab => {
                tab.classList.remove('first-tab', 'last-tab');
            });

            // Add classes to first and last tabs
            if (allTabs.length > 0) {
                allTabs[0].classList.add('first-tab');
                allTabs[allTabs.length - 1].classList.add('last-tab');
            }
        }

        // Add click handlers to tab buttons
        function attachTabClickHandlers() {
            document.querySelectorAll('.recommendation-tab-btn').forEach(btn => {
                // Remove existing listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);

                newBtn.addEventListener('click', function(e) {
                    switchTab(this.dataset.tab);
                });
            });
        }

        // Initialize existing row cards
        function initializeRow(card) {
            if (!rawData) return;

            // Remove button handler
            const btnRemove = card.querySelector('.btn-remove');
            if (btnRemove) {
                btnRemove.addEventListener('click', function() {
                    // Find parent panel
                    const panel = card.closest('.recommendation-tab-panel');
                    const tabId = panel.dataset.tab;
                    removeTab(tabId);
                });
            }

            // Initialize selects using the same logic as recommendations-form.js
            const brickBrandSelect = card.querySelector('.brick-brand-select');
            const brickDimSelect = card.querySelector('.brick-dim-select');

            if (brickBrandSelect && brickDimSelect && rawData.bricks) {
                const uniqueBrickBrands = [...new Set(rawData.bricks.map(b => b.brand))];
                populateSelect(brickBrandSelect, uniqueBrickBrands, brickBrandSelect.dataset.selected);

                brickBrandSelect.addEventListener('change', () => {
                    const brand = brickBrandSelect.value;
                    const filtered = rawData.bricks.filter(b => b.brand === brand);

                    brickDimSelect.innerHTML = '<option value="">-- Pilih Dimensi --</option>';
                    filtered.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.id;
                        opt.textContent = `${b.type} (${b.dimension_length}x${b.dimension_width}x${b.dimension_height}) - Rp ${Number(b.price_per_piece).toLocaleString('id-ID')}`;
                        if (b.id == brickDimSelect.dataset.selected) opt.selected = true;
                        brickDimSelect.appendChild(opt);
                    });
                });
                if (brickBrandSelect.value) brickBrandSelect.dispatchEvent(new Event('change'));
            }

            // Cement logic
            const cementTypeSelect = card.querySelector('.cement-type-select');
            const cementBrandSelect = card.querySelector('.cement-brand-select');

            if (cementTypeSelect && cementBrandSelect && rawData.cements) {
                const uniqueCementTypes = [...new Set(rawData.cements.map(c => c.cement_name))];
                populateSelect(cementTypeSelect, uniqueCementTypes, cementTypeSelect.dataset.selected);

                cementTypeSelect.addEventListener('change', () => {
                    const type = cementTypeSelect.value;
                    const filtered = rawData.cements.filter(c => c.cement_name === type);

                    cementBrandSelect.innerHTML = '<option value="">-- Pilih Produk --</option>';
                    filtered.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = `${c.brand} (${c.package_weight_net} kg) - Rp ${Number(c.package_price).toLocaleString('id-ID')}`;
                        if (c.id == cementBrandSelect.dataset.selected) opt.selected = true;
                        cementBrandSelect.appendChild(opt);
                    });
                });
                if (cementTypeSelect.value) cementTypeSelect.dispatchEvent(new Event('change'));
            }

            // Sand logic
            const sandTypeSelect = card.querySelector('.sand-type-select');
            const sandBrandSelect = card.querySelector('.sand-brand-select');
            const sandPkgSelect = card.querySelector('.sand-pkg-select');

            if (sandTypeSelect && sandBrandSelect && sandPkgSelect && rawData.sands) {
                const uniqueSandTypes = [...new Set(rawData.sands.map(s => s.sand_name))];
                populateSelect(sandTypeSelect, uniqueSandTypes, sandTypeSelect.dataset.selected);

                sandTypeSelect.addEventListener('change', () => {
                    const type = sandTypeSelect.value;
                    const filtered = rawData.sands.filter(s => s.sand_name === type);
                    const brands = [...new Set(filtered.map(s => s.brand))];

                    sandBrandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';
                    brands.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b;
                        opt.textContent = b;
                        if (b == sandBrandSelect.dataset.selected) opt.selected = true;
                        sandBrandSelect.appendChild(opt);
                    });
                    sandPkgSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';
                });

                sandBrandSelect.addEventListener('change', () => {
                    const type = sandTypeSelect.value;
                    const brand = sandBrandSelect.value;
                    const filtered = rawData.sands.filter(s => s.sand_name === type && s.brand === brand);

                    sandPkgSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';
                    filtered.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        const vol = s.package_volume > 0 ? `${s.package_volume} m³` : `${s.package_weight_net} kg`;
                        opt.textContent = `${vol} - Rp ${Number(s.package_price).toLocaleString('id-ID')}`;
                        if (s.id == sandPkgSelect.dataset.selected) opt.selected = true;
                        sandPkgSelect.appendChild(opt);
                    });
                });

                if (sandTypeSelect.value) {
                    sandTypeSelect.dispatchEvent(new Event('change'));
                    if (sandBrandSelect.dataset.selected) {
                        sandBrandSelect.dispatchEvent(new Event('change'));
                    }
                }
            }
        }

        function populateSelect(select, values, selectedValue) {
            const first = select.firstElementChild;
            select.innerHTML = '';
            select.appendChild(first);
            values.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = v;
                if (v == selectedValue) opt.selected = true;
                select.appendChild(opt);
            });
        }

        // Initialize existing cards
        document.querySelectorAll('.recommendation-card').forEach(card => {
            initializeRow(card);
        });

        // Attach tab handlers
        attachTabClickHandlers();

        // Update tab position classes on load
        updateTabPositionClasses();

        // Add new tab functionality
        let tabCounter = {{ count($recommendations) > 0 ? count($recommendations) : 1 }};
        const btnAddTab = document.getElementById('btnAddTab');
        const recommendationTabs = document.getElementById('recommendationTabs');
        const recommendationList = document.getElementById('recommendationList');
        const rowTemplate = document.getElementById('rowTemplate');

        if (btnAddTab) {
            btnAddTab.addEventListener('click', function() {
                const newTabId = 'rec-' + tabCounter;
                const newTabLabel = 'Rekomendasi ' + (tabCounter + 1);

                // Create new tab button
                const newTabBtn = document.createElement('button');
                newTabBtn.type = 'button';
                newTabBtn.className = 'recommendation-tab-btn';
                newTabBtn.dataset.tab = newTabId;
                newTabBtn.setAttribute('aria-selected', 'false');
                newTabBtn.innerHTML = `<span>${newTabLabel}</span>`;

                // Insert before add button
                recommendationTabs.insertBefore(newTabBtn, btnAddTab);

                // Clone template
                const templateCard = rowTemplate.querySelector('.recommendation-card');
                const clone = templateCard.cloneNode(true);

                // Replace placeholders in HTML
                clone.innerHTML = clone.innerHTML.replace(/INDEX_PLACEHOLDER/g, tabCounter);

                // Update data-index
                clone.dataset.index = tabCounter;

                // Create new panel
                const newPanel = document.createElement('div');
                newPanel.className = 'recommendation-tab-panel hidden';
                newPanel.dataset.tab = newTabId;
                newPanel.appendChild(clone);
                recommendationList.appendChild(newPanel);

                // Initialize the new card
                initializeRow(clone);

                // Switch to new tab
                switchTab(newTabId);

                // Re-attach all tab handlers
                attachTabClickHandlers();

                // Update tab position classes
                updateTabPositionClasses();

                tabCounter++;
            });
        }

        // Close tab functionality
        function removeTab(tabId) {
            const tabBtn = document.querySelector(`.recommendation-tab-btn[data-tab="${tabId}"]`);
            const tabPanel = document.querySelector(`.recommendation-tab-panel[data-tab="${tabId}"]`);

            if (!tabBtn || !tabPanel) return;

            const wasActive = tabBtn.classList.contains('active');

            // Remove tab and panel
            tabBtn.remove();
            tabPanel.remove();

            // If we removed the active tab, activate the first remaining tab
            if (wasActive) {
                const firstTab = document.querySelector('.recommendation-tab-btn');
                if (firstTab) {
                    switchTab(firstTab.dataset.tab);
                }
            }

            // Update first/last tab classes
            updateTabPositionClasses();
        }

        // Save button functionality
        const btnSave = document.getElementById('btnSaveRecommendations');
        const recommendationForm = document.getElementById('recommendationForm');

        if (btnSave && recommendationForm) {
            btnSave.addEventListener('click', function() {
                recommendationForm.submit();
            });
        }
    });
</script>
@endpush