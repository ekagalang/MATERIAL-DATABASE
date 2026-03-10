(function () {
    window.materialCalcCreateWorkTaxonomyFilterEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const formPayload = safeConfig.formPayload;

        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) {
                return Array.isArray(values) ? values.slice() : [];
            };

        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) {
                return Array.from(new Set(Array.isArray(values) ? values : []));
            };

        const sortAlphabetic = typeof deps.sortAlphabetic === 'function'
            ? deps.sortAlphabetic
            : function (values) {
                return Array.isArray(values)
                    ? values.slice().sort(function (a, b) {
                        return String(a ?? '').localeCompare(String(b ?? ''), 'id-ID');
                    })
                    : [];
            };

function initWorkTaxonomyFilters(formPayload) {
    const workFloorRows = document.getElementById('workFloorRows');
    const workAreaRows = document.getElementById('workAreaRows');
    const workFieldRows = document.getElementById('workFieldRows');
    const workFloorExtraRows = document.getElementById('workFloorExtraRows');
    const workAreaExtraRows = document.getElementById('workAreaExtraRows');
    const workFieldExtraRows = document.getElementById('workFieldExtraRows');
    const workFloorExtraSection = document.getElementById('workFloorExtraSection');
    const workAreaExtraSection = document.getElementById('workAreaExtraSection');
    const workFieldExtraSection = document.getElementById('workFieldExtraSection');
    const rightColumn = document.querySelector('#calculationForm .right-column');
    const emptyApi = {
        setValues() {},
        getValues() { return []; },
        subscribe() { return function() {}; },
        refresh() {},
    };

    if (
        !workFloorRows ||
        !workAreaRows ||
        !workFieldRows ||
        !workFloorExtraRows ||
        !workAreaExtraRows ||
        !workFieldExtraRows
    ) {
        return emptyApi;
    }

    // Keep taxonomy-extra rows as a dedicated grouping section at the bottom.
    if (
        rightColumn instanceof HTMLElement &&
        workFloorExtraSection instanceof HTMLElement &&
        workAreaExtraSection instanceof HTMLElement &&
        workFieldExtraSection instanceof HTMLElement
    ) {
        rightColumn.appendChild(workFloorExtraSection);
        rightColumn.appendChild(workAreaExtraSection);
        rightColumn.appendChild(workFieldExtraSection);
    }

    const normalizeOption = value => String(value ?? '').trim().toLowerCase();
    const baseFloorOptions = sortFloors(
        uniqueFilterTokens((formPayload?.workFloors || []).map(item => item?.name || '')),
    );
    const baseAreaOptions = sortAlphabetic(
        uniqueFilterTokens((formPayload?.workAreas || []).map(item => item?.name || '')),
    );
    const baseFieldOptions = sortAlphabetic(
        uniqueFilterTokens((formPayload?.workFields || []).map(item => item?.name || '')),
    );
    const normalizedGroupings = Array.isArray(formPayload?.workItemGroupings)
        ? formPayload.workItemGroupings
            .map(item => ({
                work_floor: String(item?.work_floor || '').trim(),
                work_floor_norm: normalizeOption(item?.work_floor || ''),
                work_area: String(item?.work_area || '').trim(),
                work_area_norm: normalizeOption(item?.work_area || ''),
                work_field: String(item?.work_field || '').trim(),
                work_field_norm: normalizeOption(item?.work_field || ''),
                formula_code: String(item?.formula_code || '').trim(),
            }))
            .filter(item => item.formula_code !== '')
        : [];
    const listeners = new Set();
    let taxonomyRowListSequence = 0;
    let floorController = null;
    let areaController = null;
    let fieldController = null;

    const parseInitialValues = rowsContainer => {
        if (!rowsContainer) return [];
        try {
            const raw = rowsContainer.dataset.initialValues || '[]';
            const parsed = JSON.parse(raw);
            return uniqueFilterTokens(Array.isArray(parsed) ? parsed : [parsed]);
        } catch (error) {
            return [];
        }
    };

    const notifyChanged = () => {
        listeners.forEach(callback => {
            try {
                callback();
            } catch (error) {
                console.warn('work taxonomy callback failed', error);
            }
        });
    };

    const initKind = ({ kind, rowsContainer, extraRowsContainer, inputName, placeholder, initialOptions, onRowsChanged, sortFn }) => {
        const baseRow = rowsContainer.querySelector('.material-type-row-base');
        const baseDisplay = baseRow?.querySelector('input[data-taxonomy-display="1"]');
        const baseHidden = baseRow?.querySelector('input[data-taxonomy-hidden="1"]');
        const baseList = baseRow?.querySelector('.autocomplete-list');
        const baseDeleteBtn = baseRow?.querySelector('[data-taxonomy-action="remove"]');
        const baseAddBtn = baseRow?.querySelector('[data-taxonomy-action="add"]');
        const extraSectionEl = document.getElementById(
            kind === 'floor'
                ? 'workFloorExtraSection'
                : kind === 'area'
                  ? 'workAreaExtraSection'
                  : 'workFieldExtraSection',
        );

        if (
            !baseRow ||
            !baseDisplay ||
            !baseHidden ||
            !baseList ||
            !baseDeleteBtn ||
            !baseAddBtn ||
            !extraRowsContainer
        ) {
            return null;
        }

        baseHidden.name = inputName;
        const effectiveSortFn = typeof sortFn === 'function' ? sortFn : sortAlphabetic;
        const shouldAutoScrollBottomOnOpen = kind === 'floor';
        let currentOptions = effectiveSortFn(uniqueFilterTokens(initialOptions));
        let isSyncing = false;
        let isSorting = false;

        const getRowStates = () => {
            const rows = [baseRow, ...extraRowsContainer.querySelectorAll('.material-type-row-extra')];
            return rows.map(row => row.__taxonomyRowState).filter(Boolean);
        };

        const getHiddenInputs = () => getRowStates().map(state => state.hiddenEl).filter(Boolean);

        const updateRowButtons = () => {
            const extraRows = extraRowsContainer.querySelectorAll('.material-type-row-extra');
            const hasExtra = extraRows.length > 0;
            baseRow.classList.toggle('has-multiple', hasExtra);
            baseDeleteBtn.classList.toggle('is-visible', hasExtra);
            if (extraSectionEl) {
                extraSectionEl.hidden = !hasExtra;
            }
            extraRows.forEach(row => {
                const btn = row.querySelector('[data-taxonomy-action="remove"]');
                if (btn) {
                    btn.classList.add('is-visible');
                }
            });
        };

        const getAvailableOptions = (term = '', currentHiddenEl = null, includeCurrentSelection = false) => {
            const query = normalizeOption(term);
            const selectedSet = new Set();
            getHiddenInputs().forEach(hiddenEl => {
                if (!hiddenEl) return;
                if (includeCurrentSelection && hiddenEl === currentHiddenEl) return;
                const normalizedValue = normalizeOption(hiddenEl.value);
                if (normalizedValue) {
                    selectedSet.add(normalizedValue);
                }
            });

            const options = uniqueFilterTokens(currentOptions);
            const filtered = options.filter(option => {
                const normalized = normalizeOption(option);
                if (!normalized) return false;
                if (selectedSet.has(normalized)) return false;
                if (!query) return true;
                return normalized.includes(query);
            });

            return effectiveSortFn(filtered);
        };

        const refreshOpenLists = () => {
            getRowStates().forEach(state => {
                if (state.listEl && state.listEl.style.display === 'block') {
                    state.renderList(state.displayEl.value || '');
                }
            });
        };

        const enforceUniqueSelections = () => {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const seen = new Set();
                getRowStates().forEach(state => {
                    const currentValue = String(state.hiddenEl.value || '').trim();
                    if (!currentValue) {
                        return;
                    }
                    const normalized = normalizeOption(currentValue);
                    if (!normalized) {
                        return;
                    }

                    if (seen.has(normalized)) {
                        state.hiddenEl.value = '';
                        state.displayEl.value = '';
                        state.hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                        return;
                    }

                    seen.add(normalized);
                });
            } finally {
                isSyncing = false;
            }
        };

        const sortRows = () => {
            if (isSorting) return;
            isSorting = true;
            try {
                const currentValues = getHiddenInputs()
                    .map(input => String(input.value || '').trim())
                    .filter(Boolean);
                if (currentValues.length <= 1) return;
                const sorted = effectiveSortFn([...currentValues]);
                const isSameOrder = currentValues.every((v, i) => v === sorted[i]);
                if (!isSameOrder) {
                    setValues(sorted);
                }
            } finally {
                isSorting = false;
            }
        };

        const syncRows = () => {
            enforceUniqueSelections();
            sortRows();
            refreshOpenLists();
            if (typeof onRowsChanged === 'function') {
                onRowsChanged();
            }
        };

        const createRowState = (rowEl, displayEl, hiddenEl, listEl) => {
            const closeList = () => {
                listEl.style.display = 'none';
            };

            const applySelection = optionValue => {
                const finalValue = String(optionValue || '').trim();
                displayEl.value = finalValue;
                if (hiddenEl.value !== finalValue) {
                    hiddenEl.value = finalValue;
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                closeList();
                syncRows();
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
                if (shouldAutoScrollBottomOnOpen && !normalizeOption(term)) {
                    requestAnimationFrame(() => {
                        listEl.scrollTop = listEl.scrollHeight;
                    });
                }
            };

            const findExactOption = term => {
                const query = normalizeOption(term);
                if (!query) return null;
                const available = getAvailableOptions(term, hiddenEl, true);
                return available.find(option => normalizeOption(option) === query) || null;
            };

            const rowState = {
                rowEl,
                displayEl,
                hiddenEl,
                listEl,
                closeList,
                renderList,
            };
            rowEl.__taxonomyRowState = rowState;

            displayEl.addEventListener('focus', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                renderList('');
            });

            displayEl.addEventListener('input', function() {
                if (displayEl.readOnly || displayEl.disabled) return;
                const typed = String(this.value || '');
                hiddenEl.value = typed;
                renderList(this.value || '');
                syncRows();
            });

            displayEl.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    const exactMatch = findExactOption(displayEl.value || '');
                    if (exactMatch) {
                        applySelection(exactMatch);
                    } else {
                        applySelection(displayEl.value || '');
                    }
                    event.preventDefault();
                    return;
                }
                if (event.key === 'Escape') {
                    closeList();
                }
            });

            displayEl.addEventListener('blur', function() {
                setTimeout(() => {
                    const normalizedValue = String(hiddenEl.value || '').trim();
                    hiddenEl.value = normalizedValue;
                    displayEl.value = normalizedValue;
                    closeList();
                    syncRows();
                }, 150);
            });

            document.addEventListener('click', function(event) {
                if (event.target === displayEl || listEl.contains(event.target)) return;
                closeList();
            });

            hiddenEl.addEventListener('change', function() {
                const value = String(hiddenEl.value || '');
                if (displayEl.value !== value) {
                    displayEl.value = value;
                }
            });

            return rowState;
        };

        const createExtraRow = (value = '') => {
            const rowEl = document.createElement('div');
            rowEl.className = 'material-type-row material-type-row-extra';
            rowEl.dataset.taxonomyKind = kind;

            const inputWrapper = document.createElement('div');
            inputWrapper.className = 'input-wrapper';

            const autocompleteWrap = document.createElement('div');
            autocompleteWrap.className = 'work-type-autocomplete';

            const inputShell = document.createElement('div');
            inputShell.className = 'work-type-input';

            const displayEl = document.createElement('input');
            displayEl.type = 'text';
            displayEl.className = 'autocomplete-input';
            displayEl.placeholder = placeholder;
            displayEl.autocomplete = 'off';
            displayEl.dataset.taxonomyDisplay = '1';
            displayEl.value = String(value || '');

            const listEl = document.createElement('div');
            listEl.className = 'autocomplete-list';
            listEl.id = `workTaxonomy-${kind}-list-${++taxonomyRowListSequence}`;

            const hiddenEl = document.createElement('input');
            hiddenEl.type = 'hidden';
            hiddenEl.name = inputName;
            hiddenEl.value = String(value || '').trim();
            hiddenEl.dataset.taxonomyHidden = '1';

            inputShell.appendChild(displayEl);
            autocompleteWrap.appendChild(inputShell);
            autocompleteWrap.appendChild(listEl);
            inputWrapper.appendChild(autocompleteWrap);
            inputWrapper.appendChild(hiddenEl);

            const actions = document.createElement('div');
            actions.className = 'material-type-row-actions';
            actions.innerHTML = `
                <button type="button"
                    class="material-type-row-btn material-type-row-btn-delete is-visible"
                    data-taxonomy-action="remove"
                    data-taxonomy-kind="${kind}"
                    title="Hapus baris">
                    <i class="bi bi-trash"></i>
                </button>
                <button type="button"
                    class="material-type-row-btn material-type-row-btn-add"
                    data-taxonomy-action="add"
                    data-taxonomy-kind="${kind}"
                    title="Tambah baris">
                    <i class="bi bi-plus-lg"></i>
                </button>
            `;

            rowEl.appendChild(inputWrapper);
            rowEl.appendChild(actions);
            extraRowsContainer.appendChild(rowEl);
            createRowState(rowEl, displayEl, hiddenEl, listEl);
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
                const state = firstExtra.__taxonomyRowState;
                const promoted = String(state?.hiddenEl?.value ?? '').trim();
                baseDisplay.value = promoted;
                baseHidden.value = promoted;
                firstExtra.remove();
                updateRowButtons();
                syncRows();
                return;
            }

            baseDisplay.value = '';
            baseHidden.value = '';
            syncRows();
        };

        const handleRowActionClick = function(event) {
            const target = event?.target;
            if (!(target instanceof HTMLElement)) return;
            const actionBtn = target.closest('[data-taxonomy-action]');
            if (!actionBtn) return;

            const action = String(actionBtn.dataset.taxonomyAction || '').trim();
            if (!action) return;

            if (action === 'add') {
                event.preventDefault();
                createExtraRow('');
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
                row.remove();
                updateRowButtons();
                syncRows();
            }
        };

        rowsContainer.addEventListener('click', handleRowActionClick);
        extraRowsContainer.addEventListener('click', handleRowActionClick);

        createRowState(baseRow, baseDisplay, baseHidden, baseList);
        baseHidden.value = String(baseHidden.value || '').trim();
        baseDisplay.value = baseHidden.value;
        updateRowButtons();

        return {
            setValues,
            setOptions(nextOptions) {
                currentOptions = effectiveSortFn(uniqueFilterTokens(nextOptions || []));
                refreshOpenLists();
            },
            getValues() {
                return uniqueFilterTokens(getHiddenInputs().map(input => input.value));
            },
        };
    };

    const computeAreaOptions = floorApi => {
        let scopedOptions = [...baseAreaOptions];

        if (areaController) {
            scopedOptions = uniqueFilterTokens([...scopedOptions, ...areaController.getValues()]);
        }

        return sortAlphabetic(scopedOptions);
    };

    const computeFieldOptions = (floorApi, areaApi) => {
        let scopedOptions = [...baseFieldOptions];

        if (fieldController) {
            scopedOptions = uniqueFilterTokens([...scopedOptions, ...fieldController.getValues()]);
        }

        return sortAlphabetic(scopedOptions);
    };

    floorController = initKind({
        kind: 'floor',
        rowsContainer: workFloorRows,
        extraRowsContainer: workFloorExtraRows,
        inputName: 'work_floors[]',
        placeholder: 'Pilih atau ketik lantai...',
        initialOptions: baseFloorOptions,
        sortFn: sortFloors,
        onRowsChanged() {
            if (areaController) {
                areaController.setOptions(computeAreaOptions(floorController));
            }
            if (fieldController) {
                fieldController.setOptions(computeFieldOptions(floorController, areaController));
            }
            notifyChanged();
            markFloorSortPending();
        },
    });

    areaController = initKind({
        kind: 'area',
        rowsContainer: workAreaRows,
        extraRowsContainer: workAreaExtraRows,
        inputName: 'work_areas[]',
        placeholder: 'Pilih atau ketik area...',
        initialOptions: baseAreaOptions,
        onRowsChanged() {
            if (fieldController) {
                fieldController.setOptions(computeFieldOptions(floorController, areaController));
            }
            notifyChanged();
        },
    });

    fieldController = initKind({
        kind: 'field',
        rowsContainer: workFieldRows,
        extraRowsContainer: workFieldExtraRows,
        inputName: 'work_fields[]',
        placeholder: 'Pilih atau ketik bidang...',
        initialOptions: baseFieldOptions,
        onRowsChanged() {
            notifyChanged();
        },
    });

    if (!floorController || !areaController || !fieldController) {
        return emptyApi;
    }

    floorController.setValues(parseInitialValues(workFloorRows));
    areaController.setValues(parseInitialValues(workAreaRows));
    fieldController.setValues(parseInitialValues(workFieldRows));
    areaController.setOptions(computeAreaOptions(floorController));
    fieldController.setOptions(computeFieldOptions(floorController, areaController));

    return {
        setValues(kind, values) {
            const type = String(kind || '').trim();
            if (type === 'floor') {
                floorController.setValues(values);
                return;
            }
            if (type === 'area') {
                areaController.setValues(values);
                return;
            }
            if (type === 'field') {
                fieldController.setValues(values);
            }
        },
        getValues(kind) {
            const type = String(kind || '').trim();
            if (type === 'floor') {
                return floorController.getValues();
            }
            if (type === 'area') {
                return areaController.getValues();
            }
            if (type === 'field') {
                return fieldController.getValues();
            }
            return [];
        },
        subscribe(callback) {
            if (typeof callback !== 'function') {
                return function() {};
            }
            listeners.add(callback);
            return function unsubscribe() {
                listeners.delete(callback);
            };
        },
        refresh() {
            areaController.setOptions(computeAreaOptions(floorController));
            fieldController.setOptions(computeFieldOptions(floorController, areaController));
            notifyChanged();
        },
    };
}

        return initWorkTaxonomyFilters(formPayload);
    };
})();
