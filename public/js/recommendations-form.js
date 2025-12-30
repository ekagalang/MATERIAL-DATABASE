(function() {
    'use strict';

    window.initRecommendationsForm = function(scope) {
        if (!scope || scope.getAttribute('data-recommendations-form-initialized') === 'true') {
            return;
        }

        console.log('[Recommendations] Starting initialization...');

        // Immediately hide content with inline style to prevent any flicker
        const originalDisplay = scope.style.display;
        scope.style.opacity = '0';
        scope.style.visibility = 'hidden';

        // Inject CSS for modal context
        const cssId = 'recommendations-modal-css';
        const cssAlreadyLoaded = !!document.getElementById(cssId);

        if (!cssAlreadyLoaded) {
            // Inject CSS with preload for faster loading
            const preload = document.createElement('link');
            preload.rel = 'preload';
            preload.as = 'style';
            preload.href = '/css/recommendations.css';
            document.head.appendChild(preload);

            const link = document.createElement('link');
            link.id = cssId;
            link.rel = 'stylesheet';
            link.href = '/css/recommendations.css';

            // Use a promise-based approach for better control
            link.onload = function() {
                console.log('[Recommendations] CSS loaded, continuing initialization...');
                // Small delay to ensure CSS is fully applied
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        continueInit();
                    });
                });
            };
            link.onerror = function() {
                console.error('[Recommendations] Failed to load CSS, continuing anyway...');
                continueInit();
            };
            document.head.appendChild(link);
        } else {
            // CSS already loaded
            console.log('[Recommendations] CSS already loaded, continuing initialization...');
            // Still use requestAnimationFrame to ensure styles are applied
            requestAnimationFrame(() => {
                continueInit();
            });
        }

        // Function to continue initialization after CSS is ready
        function continueInit() {
            scope.setAttribute('data-recommendations-form-initialized', 'true');

            // Get raw data - search in both scope and document
            let dataEl = scope.querySelector('#recommendationRawData');
            if (!dataEl) {
                dataEl = document.getElementById('recommendationRawData');
            }

            if (!dataEl) {
                console.error('[Recommendations] Raw data element not found!');
                // Show content even on error
                scope.style.opacity = '1';
                scope.style.visibility = 'visible';
                return;
            }

            let rawData;
            try {
                rawData = JSON.parse(dataEl.textContent);
                console.log('[Recommendations] Raw data loaded:', rawData);
            } catch (e) {
                console.error('[Recommendations] Failed to parse raw data:', e);
                // Show content even on error
                scope.style.opacity = '1';
                scope.style.visibility = 'visible';
                return;
            }

            // Initialize work type selector
            const workTypeSelector = scope.querySelector('#workTypeSelector');
            if (workTypeSelector) {
                console.log('[Recommendations] Initializing work type selector...');

                const showWorkTypeContent = (workType) => {
                    console.log('[Recommendations] Switching to work type:', workType);
                    scope.querySelectorAll('.work-type-content').forEach(content => {
                        content.style.display = (content.dataset.workType === workType) ? 'block' : 'none';
                    });
                };

                // Set initial state
                showWorkTypeContent(workTypeSelector.value);

                // Add change listener
                workTypeSelector.addEventListener('change', function() {
                    console.log('[Recommendations] Work type changed to:', this.value);
                    showWorkTypeContent(this.value);
                });

                console.log('[Recommendations] Work type selector initialized');
            } else {
                console.warn('[Recommendations] Work type selector not found');
            }

            const switchTab = (tabId, workType) => {
                if (!workType) workType = tabId.split('-rec-')[0];

                const tabButtons = scope.querySelectorAll(`.recommendation-tab-btn[data-work-type="${workType}"]`);
                let activeBtn = null;

                tabButtons.forEach(btn => {
                    const isActive = btn.dataset.tab === tabId;
                    btn.classList.toggle('active', isActive);
                    if (isActive) activeBtn = btn;
                });

                // Update first-visible and last-visible classes for rounded corner effect
                if (activeBtn && tabButtons.length > 0) {
                    tabButtons.forEach(btn => {
                        btn.classList.remove('first-visible', 'last-visible');
                    });

                    // Add first-visible if it's the first tab
                    if (activeBtn === tabButtons[0]) {
                        activeBtn.classList.add('first-visible');
                    }

                    // Add last-visible if it's the last tab (before the add button)
                    if (activeBtn === tabButtons[tabButtons.length - 1]) {
                        activeBtn.classList.add('last-visible');
                    }
                }

                scope.querySelectorAll(`.recommendation-tab-panel[data-work-type="${workType}"]`).forEach(panel => {
                    const isActive = panel.dataset.tab === tabId;
                    panel.classList.toggle('active', isActive);
                    panel.classList.toggle('hidden', !isActive);
                });
            };

            scope.addEventListener('click', function(e) {
                const target = e.target;
                const tabBtn = target.closest('.recommendation-tab-btn');
                if (tabBtn) {
                    e.preventDefault();
                    switchTab(tabBtn.dataset.tab, tabBtn.dataset.workType);
                    return;
                }

                const addBtn = target.closest('.recommendation-add-btn');
                if (addBtn) {
                    e.preventDefault();
                    const workType = addBtn.dataset.workType;
                    const tabContainer = scope.querySelector(`.recommendation-tabs[data-work-type="${workType}"]`);
                    const panelContainer = scope.querySelector(`.recommendation-tab-panels[data-work-type="${workType}"]`);
                    const rowTemplate = document.getElementById('rowTemplate');
                    if (!tabContainer || !panelContainer || !rowTemplate) return;

                    const newIndex = tabContainer.querySelectorAll('.recommendation-tab-btn').length;
                    const newTabId = `${workType}-rec-${newIndex}`;

                    const newTabBtn = document.createElement('button');
                    newTabBtn.type = 'button';
                    newTabBtn.className = 'recommendation-tab-btn';
                    newTabBtn.dataset.tab = newTabId;
                    newTabBtn.dataset.workType = workType;
                    newTabBtn.innerHTML = `<span>Rekomendasi ${newIndex + 1}</span>`;
                    tabContainer.insertBefore(newTabBtn, addBtn);

                    const clone = rowTemplate.firstElementChild.cloneNode(true);
                    clone.innerHTML = clone.innerHTML.replace(/INDEX_PLACEHOLDER/g, `${workType}_${newIndex}`);

                    const workTypeInput = clone.querySelector('input[name*="work_type"]');
                    if (workTypeInput) workTypeInput.value = workType;

                    const newPanel = document.createElement('div');
                    newPanel.className = 'recommendation-tab-panel';
                    newPanel.dataset.tab = newTabId;
                    newPanel.dataset.workType = workType;
                    newPanel.appendChild(clone);
                    panelContainer.appendChild(newPanel);

                    initializeRow(clone);
                    switchTab(newTabId, workType);
                    return;
                }

                const removeBtn = target.closest('.btn-remove');
                if (removeBtn) {
                    e.preventDefault();
                    const panel = removeBtn.closest('.recommendation-tab-panel');
                    if (panel) {
                        const tabId = panel.dataset.tab;
                        const workType = panel.dataset.workType;
                        const tabBtn = scope.querySelector(`.recommendation-tab-btn[data-tab="${tabId}"]`);
                        if (tabBtn) tabBtn.remove();
                        panel.remove();

                        const firstBtn = scope.querySelector(`.recommendation-tab-btn[data-work-type="${workType}"]`);
                        if (firstBtn) switchTab(firstBtn.dataset.tab, workType);
                    }
                    return;
                }

                const saveBtn = target.closest('#btnSaveRecommendations');
                if(saveBtn) {
                    e.preventDefault();
                    const form = scope.querySelector('#recommendationForm');
                    if (form) form.submit();
                    return;
                }
            });

            const populateSelect = (selectEl, values, selectedValue, placeholder = '-- Pilih --') => {
                if (!selectEl) return;
                selectEl.innerHTML = `<option value="">${placeholder}</option>`;
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    if (String(v) === String(selectedValue)) opt.selected = true;
                    selectEl.appendChild(opt);
                });
            };

            const initializeRow = (card) => {
                const brickBrandSelect = card.querySelector('.brick-brand-select');
                const brickDimSelect = card.querySelector('.brick-dim-select');
                if (brickBrandSelect && brickDimSelect && rawData.bricks) {
                    const uniqueBrickBrands = [...new Set(rawData.bricks.map(b => b.brand))].sort();
                    populateSelect(brickBrandSelect, uniqueBrickBrands, brickBrandSelect.dataset.selected, '-- Pilih Merk --');
                    brickBrandSelect.addEventListener('change', () => {
                        const brand = brickBrandSelect.value;
                        const filtered = rawData.bricks.filter(b => b.brand === brand);
                        brickDimSelect.innerHTML = '<option value="">-- Pilih Dimensi --</option>';
                        filtered.forEach(b => {
                            const opt = document.createElement('option');
                            opt.value = b.id;
                            opt.textContent = `${b.type} (${b.dimension_length}x${b.dimension_width}x${b.dimension_height}) - Rp ${Number(b.price_per_piece).toLocaleString('id-ID')}`;
                            if (String(b.id) === brickDimSelect.dataset.selected) opt.selected = true;
                            brickDimSelect.appendChild(opt);
                        });
                    });
                    if (brickBrandSelect.dataset.selected) brickBrandSelect.dispatchEvent(new Event('change'));
                }

                const cementTypeSelect = card.querySelector('.cement-type-select');
                const cementBrandSelect = card.querySelector('.cement-brand-select');
                if (cementTypeSelect && cementBrandSelect && rawData.cements) {
                    const uniqueCementTypes = [...new Set(rawData.cements.map(c => c.cement_name))].sort();
                    populateSelect(cementTypeSelect, uniqueCementTypes, cementTypeSelect.dataset.selected, '-- Pilih Jenis --');
                    cementTypeSelect.addEventListener('change', () => {
                        const type = cementTypeSelect.value;
                        const filtered = rawData.cements.filter(c => c.cement_name === type);
                        cementBrandSelect.innerHTML = '<option value="">-- Pilih Produk --</option>';
                        filtered.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = `${c.brand} (${c.package_weight_net} kg) - Rp ${Number(c.package_price).toLocaleString('id-ID')}`;
                            if (String(c.id) === cementBrandSelect.dataset.selected) opt.selected = true;
                            cementBrandSelect.appendChild(opt);
                        });
                    });
                    if (cementTypeSelect.dataset.selected) cementTypeSelect.dispatchEvent(new Event('change'));
                }

                const sandTypeSelect = card.querySelector('.sand-type-select');
                const sandBrandSelect = card.querySelector('.sand-brand-select');
                const sandPkgSelect = card.querySelector('.sand-pkg-select');
                if (sandTypeSelect && sandBrandSelect && sandPkgSelect && rawData.sands) {
                    const uniqueSandTypes = [...new Set(rawData.sands.map(s => s.sand_name))].sort();
                    populateSelect(sandTypeSelect, uniqueSandTypes, sandTypeSelect.dataset.selected, '-- Pilih Jenis --');
                    sandTypeSelect.addEventListener('change', () => {
                        const type = sandTypeSelect.value;
                        const filtered = rawData.sands.filter(s => s.sand_name === type);
                        const brands = [...new Set(filtered.map(s => s.brand))].sort();
                        populateSelect(sandBrandSelect, brands, sandBrandSelect.dataset.selected, '-- Pilih Merk --');
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
                            const vol = s.package_volume > 0 ? `${s.package_volume} M3` : `${s.package_weight_net} kg`;
                            opt.textContent = `${vol} - Rp ${Number(s.package_price).toLocaleString('id-ID')}`;
                            if (String(s.id) === sandPkgSelect.dataset.selected) opt.selected = true;
                            sandPkgSelect.appendChild(opt);
                        });
                    });
                    if (sandTypeSelect.dataset.selected) {
                        sandTypeSelect.dispatchEvent(new Event('change'));
                        if (sandBrandSelect.dataset.selected) sandBrandSelect.dispatchEvent(new Event('change'));
                    }
                }
            };

            scope.querySelectorAll('.recommendation-card').forEach(initializeRow);

            const initialActiveTab = scope.querySelector('.recommendation-tab-btn.active');
            if (initialActiveTab) {
                switchTab(initialActiveTab.dataset.tab, initialActiveTab.dataset.workType);
            } else {
                const firstTab = scope.querySelector('.recommendation-tab-btn');
                if(firstTab) {
                    switchTab(firstTab.dataset.tab, firstTab.dataset.workType);
                }
            }

            // Smoothly show content after everything is initialized
            scope.style.transition = 'opacity 0.2s ease-in, visibility 0s';
            scope.style.opacity = '1';
            scope.style.visibility = 'visible';

            console.log('[Recommendations] Initialization complete!');
        }
    };
})();
