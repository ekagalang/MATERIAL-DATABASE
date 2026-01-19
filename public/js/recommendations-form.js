(function() {
    'use strict';

    window.initRecommendationsForm = function(scope, prefillType = null) {
        if (!scope) return;

        // Helper to apply prefill logic
        const applyPrefill = (selector, type) => {
            if (typeof type === 'string' && type) {
                console.log('[Recommendations] Prefilling work type:', type);
                
                const options = Array.from(selector.options);
                
                // Try exact match
                let match = options.find(opt => opt.value === type);
                
                // Try loose match if exact fails
                if (!match) {
                    const normalizedPrefill = type.trim().toLowerCase();
                    match = options.find(opt => opt.value.trim().toLowerCase() === normalizedPrefill);
                }

                if (match) {
                    console.log('[Recommendations] Found match:', match.value);
                    selector.value = match.value;
                    // Manually trigger change to update UI
                    selector.dispatchEvent(new Event('change'));
                } else {
                    console.warn('[Recommendations] Prefill type not found in options:', type);
                }
            }
        };

        const isInitialized = scope.getAttribute('data-recommendations-form-initialized') === 'true';
        const workTypeSelector = scope.querySelector('#workTypeSelector');

        // If already initialized, just apply prefill and exit
        if (isInitialized) {
            console.log('[Recommendations] Form already initialized.');
            if (prefillType && workTypeSelector) {
                applyPrefill(workTypeSelector, prefillType);
            }
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

                // Apply prefill if available (using helper from closure)
                if (prefillType) {
                    applyPrefill(workTypeSelector, prefillType);
                }

                const showWorkTypeContent = (workType) => {
                    console.log('[Recommendations] Switching to work type:', workType);
                    scope.querySelectorAll('.work-type-content').forEach(content => {
                        content.style.display = (content.dataset.workType === workType) ? 'block' : 'none';
                    });
                };

                // Set initial state (if not prefilled, it will use default/first option)
                // If prefilled, the change event in applyPrefill handles this, but calling it again is safe.
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

            const formulaMaterials = {};
            (rawData.formulas || []).forEach(formula => {
                if (formula && formula.code) {
                    formulaMaterials[formula.code] = Array.isArray(formula.materials) ? formula.materials : [];
                }
            });

            const materialCollections = {
                brick: rawData.bricks || [],
                cement: rawData.cements || [],
                sand: rawData.sands || [],
                cat: rawData.cats || [],
                ceramic: rawData.ceramics || [],
                nat: rawData.nats || []
            };

            const formatCurrency = (value) => {
                const numberValue = Number(value);
                if (!Number.isFinite(numberValue) || numberValue <= 0) return null;
                return `Rp ${Math.round(numberValue).toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
            };

            const isPositiveNumber = (value) => Number(value) > 0;

            const formatters = {
                brick: (item) => {
                    const name = [item.brand, item.type].filter(Boolean).join(' ') || 'Bata';
                    const hasDims = isPositiveNumber(item.dimension_length) &&
                        isPositiveNumber(item.dimension_width) &&
                        isPositiveNumber(item.dimension_height);
                    const dims = hasDims
                        ? `${item.dimension_length}x${item.dimension_width}x${item.dimension_height} cm`
                        : null;
                    const price = formatCurrency(item.price_per_piece);
                    return `${name}${dims ? ` (${dims})` : ''}${price ? ` - ${price}` : ''}`;
                },
                cement: (item) => {
                    const name = [item.cement_name, item.brand].filter(Boolean).join(' - ') || 'Semen';
                    const weight = isPositiveNumber(item.package_weight_net) ? `${item.package_weight_net} kg` : null;
                    const price = formatCurrency(item.package_price);
                    return `${name}${weight ? ` (${weight})` : ''}${price ? ` - ${price}` : ''}`;
                },
                sand: (item) => {
                    const name = [item.sand_name, item.brand].filter(Boolean).join(' - ') || 'Pasir';
                    const volume = isPositiveNumber(item.package_volume)
                        ? `${item.package_volume} M3`
                        : (isPositiveNumber(item.package_weight_net) ? `${item.package_weight_net} kg` : null);
                    const price = formatCurrency(item.comparison_price_per_m3 || item.package_price);
                    return `${name}${volume ? ` (${volume})` : ''}${price ? ` - ${price}` : ''}`;
                },
                cat: (item) => {
                    const name = [item.brand, item.cat_name].filter(Boolean).join(' - ') || 'Cat';
                    const volume = isPositiveNumber(item.volume)
                        ? `${item.volume} ${item.volume_unit || 'L'}`
                        : (isPositiveNumber(item.package_weight_net) ? `${item.package_weight_net} kg` : null);
                    const price = formatCurrency(item.purchase_price);
                    return `${name}${volume ? ` (${volume})` : ''}${price ? ` - ${price}` : ''}`;
                },
                ceramic: (item) => {
                    const name = [item.brand, item.type].filter(Boolean).join(' - ') || 'Keramik';
                    const hasDims = isPositiveNumber(item.dimension_length) && isPositiveNumber(item.dimension_width);
                    const dims = hasDims ? `${item.dimension_length}x${item.dimension_width} cm` : null;
                    const pieces = isPositiveNumber(item.pieces_per_package) ? `${item.pieces_per_package} pcs` : null;
                    const price = formatCurrency(item.price_per_package);
                    const details = [dims, pieces].filter(Boolean).join(', ');
                    return `${name}${details ? ` (${details})` : ''}${price ? ` - ${price}` : ''}`;
                },
                nat: (item) => {
                    const name = [item.brand, item.cement_name].filter(Boolean).join(' - ') || 'Nat';
                    const weight = isPositiveNumber(item.package_weight_net) ? `${item.package_weight_net} kg` : null;
                    const price = formatCurrency(item.package_price);
                    return `${name}${weight ? ` (${weight})` : ''}${price ? ` - ${price}` : ''}`;
                }
            };

            const populateMaterialSelect = (selectEl, materialType) => {
                if (!selectEl) return;
                const items = materialCollections[materialType] || [];
                const selectedValue = selectEl.dataset.selected;
                const placeholder =
                    selectEl.querySelector('option')?.textContent ||
                    '-- Pilih --';

                selectEl.innerHTML = `<option value="">${placeholder}</option>`;

                const formatItem = formatters[materialType];
                const options = items.map(item => ({
                    id: item.id,
                    label: formatItem ? formatItem(item) : (item.name || item.brand || String(item.id))
                })).filter(opt => opt.label);

                options.sort((a, b) => a.label.localeCompare(b.label, 'id-ID'));

                options.forEach(opt => {
                    const optionEl = document.createElement('option');
                    optionEl.value = opt.id;
                    optionEl.textContent = opt.label;
                    if (String(opt.id) === String(selectedValue)) optionEl.selected = true;
                    selectEl.appendChild(optionEl);
                });
            };

            const applyMaterialVisibility = (card, workType) => {
                const required = (formulaMaterials[workType] || []);
                const showAll = required.length === 0;

                card.querySelectorAll('.material-section').forEach(section => {
                    const type = section.dataset.materialType;
                    const shouldShow = showAll || required.includes(type);
                    section.style.display = shouldShow ? '' : 'none';
                    const select = section.querySelector('select');
                    if (select) {
                        select.disabled = !shouldShow;
                    }
                });
            };

            const initializeRow = (card) => {
                const panel = card.closest('.recommendation-tab-panel');
                const workType = panel ? panel.dataset.workType : null;

                card.querySelectorAll('select.material-select').forEach(selectEl => {
                    populateMaterialSelect(selectEl, selectEl.dataset.materialType);
                });

                if (workType) {
                    applyMaterialVisibility(card, workType);
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
