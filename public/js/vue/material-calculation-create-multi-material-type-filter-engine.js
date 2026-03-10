(function () {
    window.materialCalcCreateMultiMaterialTypeFilterEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const formPayload = safeConfig.formPayload;
        const resolvedOptionsByType = safeConfig.optionsByType && typeof safeConfig.optionsByType === 'object'
            ? safeConfig.optionsByType
            : {};

        const sortAlphabetic = typeof deps.sortAlphabetic === 'function'
            ? deps.sortAlphabetic
            : function (values) {
                return Array.isArray(values)
                    ? values.slice().sort(function (a, b) {
                        return String(a ?? '').localeCompare(String(b ?? ''), 'id-ID');
                    })
                    : [];
            };
        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) {
                return Array.from(new Set(Array.isArray(values) ? values : []));
            };
        const syncSharedBundleMaterialTypeAcrossItems = typeof deps.syncSharedBundleMaterialTypeAcrossItems === 'function'
            ? deps.syncSharedBundleMaterialTypeAcrossItems
            : function () {};
        const linkBundleRowWithCustomizePanel = typeof deps.linkBundleRowWithCustomizePanel === 'function'
            ? deps.linkBundleRowWithCustomizePanel
            : function () {};
        const materialTypeLabels = deps.materialTypeLabels && typeof deps.materialTypeLabels === 'object'
            ? deps.materialTypeLabels
            : {};

function initMultiMaterialTypeFilters(formPayload) {
    const optionsByType = resolvedOptionsByType;
    const itemElements = document.querySelectorAll('.material-type-filter-item[data-material-type]');
    const api = {
        setValues(type, values) {},
        clearHiddenRows() {},
        clearAll() {},
    };
    const typeControllers = {};
    let extraRowSequence = 0;
    let customizePanelSequence = 0;

    function createActionButton(type, action) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `material-type-row-btn ${action === 'add' ? 'material-type-row-btn-add' : 'material-type-row-btn-delete'}`;
        btn.dataset.materialTypeAction = action;
        btn.dataset.materialType = type;
        btn.title = action === 'add' ? 'Tambah baris' : 'Hapus baris';
        btn.innerHTML = action === 'add'
            ? '<i class="bi bi-plus-lg"></i>'
            : '<i class="bi bi-trash"></i>';
        return btn;
    }

    function normalizeOption(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    itemElements.forEach(itemEl => {
        const type = itemEl.dataset.materialType;
        const baseRow = itemEl.querySelector('.material-type-row-base');
        const baseDisplay = itemEl.querySelector(`#materialTypeDisplay-${type}`);
        const baseHidden = itemEl.querySelector(`#materialTypeSelector-${type}`);
        const baseList = itemEl.querySelector(`#materialType-list-${type}`);
        const extraRowsContainer = itemEl.querySelector('.material-type-extra-rows');
        const baseDeleteBtn = baseRow?.querySelector('[data-material-type-action="remove"]');
        const baseAddBtn = baseRow?.querySelector('[data-material-type-action="add"]');
        const basePlaceholder = baseDisplay?.getAttribute('placeholder') || 'Pilih atau ketik...';
        const options = optionsByType[type] || [];
        let isSyncing = false;

        if (
            !type ||
            !baseRow ||
            !baseDisplay ||
            !baseHidden ||
            !baseList ||
            !extraRowsContainer ||
            !baseAddBtn ||
            !baseDeleteBtn
        ) {
            return;
        }

        baseHidden.dataset.materialTypeHidden = '1';

        const updateRowButtons = () => {
            const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
            const hasExtra = extraRows.length > 0;
            baseRow.classList.toggle('has-multiple', hasExtra);
            itemEl.classList.toggle('has-extra-rows', hasExtra);
            baseDeleteBtn.classList.toggle('is-visible', hasExtra);
            extraRows.forEach(row => {
                const deleteBtn = row.querySelector('[data-material-type-action="remove"]');
                if (deleteBtn) {
                    deleteBtn.classList.toggle('is-visible', true);
                }
            });
        };

        const getRowStates = () => {
            const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
            return rows.map(row => row.__materialTypeRowState).filter(Boolean);
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
                        rowState.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
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
                }
                syncRows();
                syncSharedBundleMaterialTypeAcrossItems(type, rowState, previousValue);
                closeList();
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
                // Allow the current row's selected value to remain valid while typing.
                const available = getAvailableOptions(term, hiddenEl, true);
                return available.find(option => normalizeOption(option) === query) || null;
            };

            rowState.closeList = closeList;
            rowState.renderList = renderList;
            rowState.__lastSharedTypeValue = String(hiddenEl.value || '').trim();
            rowEl.__materialTypeRowState = rowState;
            linkBundleRowWithCustomizePanel(rowEl, type);

            displayEl.addEventListener('focus', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                // On focus, show full available options (not filtered by current selected value).
                renderList('');
            });

            displayEl.addEventListener('input', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                const previousValue = String(rowState.__lastSharedTypeValue ?? hiddenEl.value ?? '').trim();
                const term = this.value || '';
                renderList(term);

                if (!term.trim()) {
                    if (hiddenEl.value) {
                        hiddenEl.value = '';
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
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
                    }
                } else if (hiddenEl.value) {
                    hiddenEl.value = '';
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
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

            hiddenEl.addEventListener('change', function() {
                if (displayEl.value !== hiddenEl.value) {
                    displayEl.value = hiddenEl.value;
                }
                if (!isSyncing) {
                    syncRows();
                }
            });

            if (options.length === 0) {
                displayEl.disabled = true;
                displayEl.placeholder = `Tidak ada data untuk ${type}`;
            }
        };

        const buildExtraRow = (value = '') => {
            const rowEl = document.createElement('div');
            rowEl.className = 'material-type-row material-type-row-extra';
            rowEl.dataset.materialType = type;
            const supportsCustomize = ['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'nat'].includes(type);

            const inputWrapperEl = document.createElement('div');
            inputWrapperEl.className = 'input-wrapper';
            const autocompleteEl = document.createElement('div');
            autocompleteEl.className = 'work-type-autocomplete';
            const inputShellEl = document.createElement('div');
            inputShellEl.className = 'work-type-input';

            const displayEl = document.createElement('input');
            displayEl.type = 'text';
            displayEl.className = 'autocomplete-input';
            displayEl.placeholder = basePlaceholder;
            displayEl.autocomplete = 'off';
            displayEl.value = String(value || '');

            const listEl = document.createElement('div');
            listEl.className = 'autocomplete-list';
            listEl.id = `materialType-list-${type}-extra-${++extraRowSequence}`;

            const hiddenEl = document.createElement('input');
            hiddenEl.type = 'hidden';
            hiddenEl.name = `material_type_filters_extra[${type}][]`;
            hiddenEl.value = String(value || '');
            hiddenEl.dataset.materialTypeHidden = '1';
            hiddenEl.dataset.materialTypeExtra = '1';

            inputShellEl.appendChild(displayEl);
            autocompleteEl.appendChild(inputShellEl);
            autocompleteEl.appendChild(listEl);
            inputWrapperEl.appendChild(autocompleteEl);
            inputWrapperEl.appendChild(hiddenEl);

            const actionEl = document.createElement('div');
            actionEl.className = 'material-type-row-actions';
            const deleteBtn = createActionButton(type, 'remove');
            const addBtn = createActionButton(type, 'add');
            actionEl.appendChild(deleteBtn);
            actionEl.appendChild(addBtn);

            rowEl.appendChild(inputWrapperEl);
            rowEl.appendChild(actionEl);
            let rowCustomizePanelEl = null;
            if (supportsCustomize) {
                const customizeBtn = document.createElement('button');
                customizeBtn.type = 'button';
                customizeBtn.className = 'material-type-row-btn material-type-row-btn-customize';
                customizeBtn.dataset.customizeToggle = type;
                customizeBtn.title = `Custom ${materialTypeLabels[type] || type}`;
                customizeBtn.textContent = 'Custom';

                const templatePanel = itemEl.querySelector(`[data-customize-panel="${type}"]`) ||
                    document.getElementById(`customizePanel-${type}`);
                if (templatePanel) {
                    const panelId = `customizePanel-${type}-extra-${++customizePanelSequence}`;
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
                            selectEl.id = `${selectEl.id}-extra-${customizePanelSequence}-${index}`;
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
            updateRowButtons();

            setupAutocomplete({
                rowEl,
                displayEl,
                hiddenEl,
                listEl,
                renderList() {},
                closeList() {},
            });
            syncRows();

            deleteBtn.addEventListener('click', function() {
                if (rowEl.__customizePanelEl) {
                    rowEl.__customizePanelEl.remove();
                }
                rowEl.remove();
                updateRowButtons();
                syncRows();
            });
            addBtn.addEventListener('click', function() {
                buildExtraRow('');
            });

            return rowEl;
        };

        const removeBaseRow = () => {
            const extraRows = Array.from(extraRowsContainer.querySelectorAll('.material-type-row-extra'));

            // If there are extra rows, promote the first extra row value to base
            // so deleting on base behaves like deleting the clicked (first) row.
            if (extraRows.length > 0) {
                const firstExtraRow = extraRows[0];
                const firstState = firstExtraRow.__materialTypeRowState;
                const promotedValue = String(
                    firstState?.hiddenEl?.value ??
                    firstState?.displayEl?.value ??
                    '',
                ).trim();

                baseDisplay.value = promotedValue;
                if (baseHidden.value !== promotedValue) {
                    baseHidden.value = promotedValue;
                    baseHidden.dispatchEvent(new Event('change', { bubbles: true }));
                }

                firstExtraRow.remove();
                if (firstExtraRow.__customizePanelEl) {
                    firstExtraRow.__customizePanelEl.remove();
                }
                updateRowButtons();
                syncRows();
                return;
            }

            // If this is the only row, clear its value.
            if (baseDisplay.value || baseHidden.value) {
                baseDisplay.value = '';
                baseHidden.value = '';
                baseHidden.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                syncRows();
            }
        };

        baseAddBtn.addEventListener('click', function() {
            buildExtraRow('');
        });

        baseDeleteBtn.addEventListener('click', function() {
            removeBaseRow();
        });

        setupAutocomplete({
            rowEl: baseRow,
            displayEl: baseDisplay,
            hiddenEl: baseHidden,
            listEl: baseList,
            renderList() {},
            closeList() {},
        });

        itemEl.__setMaterialTypeValues = function(values) {
            const tokens = uniqueFilterTokens(Array.isArray(values) ? values : [values]);
            while (extraRowsContainer.firstChild) {
                extraRowsContainer.removeChild(extraRowsContainer.firstChild);
            }

            const first = tokens[0] || '';
            baseDisplay.value = first;
            baseHidden.value = first;
            baseHidden.dispatchEvent(new Event('change', { bubbles: true }));

            tokens.slice(1).forEach(token => buildExtraRow(token));
            updateRowButtons();
            syncRows();
        };

        itemEl.__clearExtraRows = function() {
            while (extraRowsContainer.firstChild) {
                extraRowsContainer.removeChild(extraRowsContainer.firstChild);
            }
            updateRowButtons();
            syncRows();
        };

        typeControllers[type] = {
            setValues: itemEl.__setMaterialTypeValues,
            clearExtraRows: itemEl.__clearExtraRows,
        };

        updateRowButtons();
        syncRows();
    });

    api.setValues = function(type, values) {
        const controller = typeControllers[type];
        if (!controller || typeof controller.setValues !== 'function') return;
        controller.setValues(values);
    };

    api.clearHiddenRows = function() {
        itemElements.forEach(itemEl => {
            if (itemEl.style.display !== 'none') return;
            const type = itemEl.dataset.materialType;
            const controller = typeControllers[type];
            if (controller && typeof controller.clearExtraRows === 'function') {
                controller.clearExtraRows();
            }
        });
    };

    api.clearAll = function() {
        itemElements.forEach(itemEl => {
            if (typeof itemEl.__setMaterialTypeValues === 'function') {
                itemEl.__setMaterialTypeValues([]);
            }
        });
    };

    return api;
}


        return initMultiMaterialTypeFilters(formPayload);
    };
})();