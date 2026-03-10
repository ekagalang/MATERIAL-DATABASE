(function () {
    window.materialCalcCreateAdditionalMaterialTypeFilterEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const itemEl = safeConfig.itemEl;
        const initialFilters = safeConfig.initialFilters && typeof safeConfig.initialFilters === 'object'
            ? safeConfig.initialFilters
            : {};

        const bundleMaterialTypeOrder = Array.isArray(deps.bundleMaterialTypeOrder)
            ? deps.bundleMaterialTypeOrder
            : [];
        const sortAlphabetic = typeof deps.sortAlphabetic === 'function'
            ? deps.sortAlphabetic
            : function (values) { return Array.isArray(values) ? values.slice().sort() : []; };
        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) { return Array.from(new Set(Array.isArray(values) ? values : [])); };
        const bundleMaterialTypeOptions = deps.bundleMaterialTypeOptions && typeof deps.bundleMaterialTypeOptions === 'object'
            ? deps.bundleMaterialTypeOptions
            : {};
        const syncBundleFromForms = typeof deps.syncBundleFromForms === 'function'
            ? deps.syncBundleFromForms
            : function () {};
        const syncSharedBundleMaterialTypeAcrossItems = typeof deps.syncSharedBundleMaterialTypeAcrossItems === 'function'
            ? deps.syncSharedBundleMaterialTypeAcrossItems
            : function () {};
        const linkBundleRowWithCustomizePanel = typeof deps.linkBundleRowWithCustomizePanel === 'function'
            ? deps.linkBundleRowWithCustomizePanel
            : function () {};
        const bundleCustomizeSupportedTypes = deps.bundleCustomizeSupportedTypes instanceof Set
            ? deps.bundleCustomizeSupportedTypes
            : new Set();
        const getBundleMaterialTypePlaceholder = typeof deps.getBundleMaterialTypePlaceholder === 'function'
            ? deps.getBundleMaterialTypePlaceholder
            : function (type) { return String(type || ''); };
        const bundleMaterialTypeLabels = deps.bundleMaterialTypeLabels && typeof deps.bundleMaterialTypeLabels === 'object'
            ? deps.bundleMaterialTypeLabels
            : {};
        const getBundleMaterialTypeValues = typeof deps.getBundleMaterialTypeValues === 'function'
            ? deps.getBundleMaterialTypeValues
            : function () { return []; };
        const nextBundleAdditionalAutocompleteSeq = typeof deps.nextBundleAdditionalAutocompleteSeq === 'function'
            ? deps.nextBundleAdditionalAutocompleteSeq
            : function () { return Date.now(); };
        const nextBundleCustomizePanelSeq = typeof deps.nextBundleCustomizePanelSeq === 'function'
            ? deps.nextBundleCustomizePanelSeq
            : function () { return Date.now(); };

function initAdditionalMaterialTypeFilters(itemEl, initialFilters = {}) {
    if (!itemEl) {
        return;
    }

    const normalizeOption = value => String(value ?? '').trim().toLowerCase();

    bundleMaterialTypeOrder.forEach(type => {
        const wrap = itemEl.querySelector(`[data-material-wrap="${type}"]`);
        const baseRow = wrap?.querySelector('.material-type-row-base');
        const baseDisplay = baseRow?.querySelector('.autocomplete-input[data-material-display="1"]');
        const baseHidden = baseRow?.querySelector('input[data-material-type-hidden="1"]');
        const baseList = baseRow?.querySelector('.autocomplete-list');
        const extraRowsContainer = wrap?.querySelector('.material-type-extra-rows');
        const baseDeleteBtn = baseRow?.querySelector('[data-material-type-action="remove"]');
        const baseAddBtn = baseRow?.querySelector('[data-material-type-action="add"]');
        const options = sortAlphabetic(uniqueFilterTokens(bundleMaterialTypeOptions[type] || []));
        let isSyncing = false;

        if (
            !wrap ||
            !baseRow ||
            !baseDisplay ||
            !baseHidden ||
            !baseList ||
            !extraRowsContainer ||
            !baseDeleteBtn ||
            !baseAddBtn
        ) {
            return;
        }

        const updateRowButtons = () => {
            const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
            const hasExtra = extraRows.length > 0;
            baseRow.classList.toggle('has-multiple', hasExtra);
            wrap.classList.toggle('has-extra-rows', hasExtra);
            baseDeleteBtn.classList.toggle('is-visible', hasExtra);
            extraRows.forEach(row => {
                const deleteBtn = row.querySelector('[data-material-type-action="remove"]');
                if (deleteBtn) {
                    deleteBtn.classList.add('is-visible');
                }
            });
        };

        const getRowStates = () => {
            const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
            return rows.map(row => row.__bundleMaterialRowState).filter(Boolean);
        };

        const getHiddenInputs = () => getRowStates().map(row => row.hiddenEl).filter(Boolean);

        const getAvailableOptions = (term = '', currentHiddenEl = null, includeCurrentSelection = false) => {
            const query = normalizeOption(term);
            const selectedSet = new Set();
            getHiddenInputs().forEach(hiddenEl => {
                if (!hiddenEl) return;
                if (includeCurrentSelection && hiddenEl === currentHiddenEl) return;
                const normalized = normalizeOption(hiddenEl.value);
                if (normalized) {
                    selectedSet.add(normalized);
                }
            });
            const available = options.filter(option => {
                const normalized = normalizeOption(option);
                if (!normalized || selectedSet.has(normalized)) return false;
                if (!query) return true;
                return normalized.includes(query);
            });
            return sortAlphabetic(available);
        };

        const refreshOpenLists = () => {
            getRowStates().forEach(rowState => {
                if (rowState.listEl && rowState.listEl.style.display === 'block') {
                    rowState.renderList(rowState.displayEl.value || '');
                }
            });
        };

        const enforceUniqueSelection = () => {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const seen = new Set();
                getRowStates().forEach(rowState => {
                    const currentValue = String(rowState.hiddenEl.value || '').trim();
                    const normalized = normalizeOption(currentValue);
                    if (!normalized) return;

                    if (seen.has(normalized)) {
                        rowState.displayEl.value = '';
                        rowState.hiddenEl.value = '';
                        return;
                    }
                    seen.add(normalized);
                });
            } finally {
                isSyncing = false;
            }
        };

        const syncRows = () => {
            enforceUniqueSelection();
            refreshOpenLists();
            syncBundleFromForms();
        };

        const setupAutocomplete = rowState => {
            const { rowEl, displayEl, hiddenEl, listEl } = rowState;

            const closeList = () => {
                listEl.style.display = 'none';
            };

            const applySelection = optionValue => {
                const previousValue = String(rowState.__lastSharedTypeValue ?? hiddenEl.value ?? '').trim();
                const finalValue = String(optionValue || '').trim();
                displayEl.value = finalValue;
                if (hiddenEl.value !== finalValue) {
                    hiddenEl.value = finalValue;
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    hiddenEl.value = finalValue;
                }
                closeList();
                syncRows();
                syncSharedBundleMaterialTypeAcrossItems(type, rowState, previousValue);
            };

            const renderList = (term = '') => {
                listEl.innerHTML = '';

                const emptyItem = document.createElement('div');
                emptyItem.className = 'autocomplete-item';
                emptyItem.textContent = '- Tidak Pilih -';
                emptyItem.addEventListener('click', function() {
                    applySelection('');
                });
                listEl.appendChild(emptyItem);

                getAvailableOptions(term, hiddenEl).forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = option;
                    item.addEventListener('click', function() {
                        applySelection(option);
                    });
                    listEl.appendChild(item);
                });

                listEl.style.display = 'block';
            };

            const findExactAvailableOption = term => {
                const query = normalizeOption(term);
                if (!query) return null;
                const available = getAvailableOptions(term, hiddenEl, true);
                return available.find(option => normalizeOption(option) === query) || null;
            };

            rowState.closeList = closeList;
            rowState.renderList = renderList;
            rowState.__lastSharedTypeValue = String(hiddenEl.value || '').trim();
            rowEl.__bundleMaterialRowState = rowState;
            linkBundleRowWithCustomizePanel(rowEl, type);

            displayEl.addEventListener('focus', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                renderList('');
            });

            displayEl.addEventListener('input', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                const previousValue = String(rowState.__lastSharedTypeValue ?? hiddenEl.value ?? '').trim();
                const term = this.value || '';
                renderList(term);

                if (!term.trim()) {
                    if (hiddenEl.value !== '') {
                        hiddenEl.value = '';
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        hiddenEl.value = '';
                    }
                    syncRows();
                    syncSharedBundleMaterialTypeAcrossItems(type, rowState, previousValue);
                    return;
                }

                const exactMatch = findExactAvailableOption(term);
                if (exactMatch) {
                    if (hiddenEl.value !== exactMatch) {
                        hiddenEl.value = exactMatch;
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        hiddenEl.value = exactMatch;
                    }
                } else {
                    if (hiddenEl.value !== '') {
                        hiddenEl.value = '';
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        hiddenEl.value = '';
                    }
                }
                syncRows();
                syncSharedBundleMaterialTypeAcrossItems(type, rowState, previousValue);
            });

            displayEl.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                const exactMatch = findExactAvailableOption(displayEl.value || '');
                if (exactMatch) {
                    applySelection(exactMatch);
                    event.preventDefault();
                }
            });

            displayEl.addEventListener('blur', function() {
                setTimeout(closeList, 150);
            });

            document.addEventListener('click', function(event) {
                if (event.target === displayEl || listEl.contains(event.target)) return;
                closeList();
            });
        };

        const createExtraRow = (value = '') => {
            const rowEl = document.createElement('div');
            rowEl.className = 'material-type-row material-type-row-extra';
            rowEl.dataset.materialType = type;
            const supportsCustomize = bundleCustomizeSupportedTypes.has(String(type || '').trim());

            const inputWrapperEl = document.createElement('div');
            inputWrapperEl.className = 'input-wrapper';

            const autocompleteEl = document.createElement('div');
            autocompleteEl.className = 'work-type-autocomplete';

            const inputShellEl = document.createElement('div');
            inputShellEl.className = 'work-type-input';

            const displayEl = document.createElement('input');
            displayEl.type = 'text';
            displayEl.className = 'autocomplete-input';
            displayEl.dataset.materialDisplay = '1';
            displayEl.placeholder = getBundleMaterialTypePlaceholder(type);
            displayEl.autocomplete = 'off';
            displayEl.value = String(value || '');

            const listEl = document.createElement('div');
            listEl.className = 'autocomplete-list';
            listEl.id = `bundleMaterial-list-${type}-${nextBundleAdditionalAutocompleteSeq()}`;

            const hiddenEl = document.createElement('input');
            hiddenEl.type = 'hidden';
            hiddenEl.dataset.materialTypeHidden = '1';
            hiddenEl.dataset.field = `material_type_${type}`;
            hiddenEl.setAttribute('data-field', `material_type_${type}`);
            hiddenEl.value = String(value || '');

            inputShellEl.appendChild(displayEl);
            autocompleteEl.appendChild(inputShellEl);
            autocompleteEl.appendChild(listEl);
            inputWrapperEl.appendChild(autocompleteEl);
            inputWrapperEl.appendChild(hiddenEl);

            const actionEl = document.createElement('div');
            actionEl.className = 'material-type-row-actions';
            actionEl.innerHTML = `
                <button type="button" class="material-type-row-btn material-type-row-btn-delete is-visible"
                    data-material-type-action="remove" title="Hapus baris">
                    <i class="bi bi-trash"></i>
                </button>
                <button type="button" class="material-type-row-btn material-type-row-btn-add"
                    data-material-type-action="add" title="Tambah baris">
                    <i class="bi bi-plus-lg"></i>
                </button>
            `;

            rowEl.appendChild(inputWrapperEl);
            rowEl.appendChild(actionEl);
            let rowCustomizePanelEl = null;
            if (supportsCustomize) {
                const customizeBtn = document.createElement('button');
                customizeBtn.type = 'button';
                customizeBtn.className = 'material-type-row-btn material-type-row-btn-customize';
                customizeBtn.dataset.customizeToggle = type;
                customizeBtn.title = `Custom ${bundleMaterialTypeLabels[type] || type}`;
                customizeBtn.textContent = 'Custom';

                const templatePanel = wrap.querySelector(`[data-customize-panel="${type}"]`);
                if (templatePanel) {
                    const panelSeq = nextBundleCustomizePanelSeq();
                    const panelId = `bundleCustomizePanel-${type}-extra-${panelSeq}`;
                    rowCustomizePanelEl = templatePanel.cloneNode(true);
                    rowCustomizePanelEl.hidden = true;
                    rowCustomizePanelEl.id = panelId;
                    rowCustomizePanelEl.dataset.customizePanel = type;
                    rowCustomizePanelEl.querySelectorAll('.customize-filter-autocomplete').forEach(el => el.remove());
                    rowCustomizePanelEl.querySelectorAll('select[data-customize-filter]').forEach((selectEl, index) => {
                        selectEl.value = '';
                        selectEl.style.display = '';
                        selectEl.tabIndex = 0;
                        delete selectEl.dataset.customizeAutocompleteBound;
                        if (selectEl.id) {
                            selectEl.id = `${selectEl.id}-extra-${panelSeq}-${index}`;
                        }
                    });
                    customizeBtn.dataset.customizePanelId = panelId;
                }

                rowEl.appendChild(customizeBtn);
            }
            extraRowsContainer.appendChild(rowEl);
            if (rowCustomizePanelEl) {
                extraRowsContainer.appendChild(rowCustomizePanelEl);
                rowEl.__customizePanelEl = rowCustomizePanelEl;
            }

            setupAutocomplete({
                rowEl,
                displayEl,
                hiddenEl,
                listEl,
                renderList() {},
                closeList() {},
            });

            updateRowButtons();
            return rowEl;
        };

        const setValues = values => {
            const tokens = uniqueFilterTokens(Array.isArray(values) ? values : [values]);
            while (extraRowsContainer.firstChild) {
                extraRowsContainer.removeChild(extraRowsContainer.firstChild);
            }
            baseDisplay.value = '';
            baseHidden.value = '';

            const firstValue = tokens[0] || '';
            baseDisplay.value = firstValue;
            baseHidden.value = firstValue;

            tokens.slice(1).forEach(token => {
                createExtraRow(token);
            });
            updateRowButtons();
            syncRows();
        };

        const removeBaseRow = () => {
            const extraRows = Array.from(extraRowsContainer.querySelectorAll('.material-type-row-extra'));
            if (extraRows.length > 0) {
                const firstExtra = extraRows[0];
                const state = firstExtra.__bundleMaterialRowState;
                const promoted = String(state?.hiddenEl?.value ?? state?.displayEl?.value ?? '').trim();
                baseDisplay.value = promoted;
                baseHidden.value = promoted;
                if (firstExtra.__customizePanelEl) {
                    firstExtra.__customizePanelEl.remove();
                }
                firstExtra.remove();
                updateRowButtons();
                syncRows();
                return;
            }

            baseDisplay.value = '';
            baseHidden.value = '';
            syncRows();
        };

        wrap.addEventListener('click', function(event) {
            const target = event?.target;
            if (!(target instanceof HTMLElement)) return;
            const actionBtn = target.closest('[data-material-type-action]');
            if (!actionBtn || !wrap.contains(actionBtn)) return;

            const action = String(actionBtn.dataset.materialTypeAction || '').trim();
            if (!action) return;

            if (action === 'add') {
                event.preventDefault();
                createExtraRow('');
                updateRowButtons();
                syncRows();
                return;
            }

            if (action === 'remove') {
                event.preventDefault();
                const row = actionBtn.closest('.material-type-row');
                if (!row) return;

                if (row.classList.contains('material-type-row-base')) {
                    removeBaseRow();
                    return;
                }

                if (row.__customizePanelEl) {
                    row.__customizePanelEl.remove();
                }
                row.remove();
                updateRowButtons();
                syncRows();
            }
        });

        setupAutocomplete({
            rowEl: baseRow,
            displayEl: baseDisplay,
            hiddenEl: baseHidden,
            listEl: baseList,
            renderList() {},
            closeList() {},
        });

        baseHidden.dataset.materialTypeHidden = '1';
        baseHidden.setAttribute('data-field', `material_type_${type}`);

        const initialValues = getBundleMaterialTypeValues(initialFilters, type);
        setValues(initialValues);

        wrap.__setBundleMaterialTypeValues = setValues;
        wrap.__clearBundleMaterialTypeValues = function() {
            setValues([]);
        };
    });
}

        return initAdditionalMaterialTypeFilters(itemEl, initialFilters);
    };
})();
