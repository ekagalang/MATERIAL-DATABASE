(function () {
    window.materialCalcCreateAdditionalTaxonomyAutocompleteEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const itemEl = safeConfig.itemEl;
        const initial = safeConfig.initial && typeof safeConfig.initial === 'object' ? safeConfig.initial : {};

        const bindAutocompleteScrollLock = typeof deps.bindAutocompleteScrollLock === 'function'
            ? deps.bindAutocompleteScrollLock
            : function () {};
        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) { return Array.from(new Set(Array.isArray(values) ? values : [])); };
        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) { return Array.isArray(values) ? values.slice() : []; };
        const workFloorOptionValues = Array.isArray(deps.workFloorOptionValues)
            ? deps.workFloorOptionValues
            : [];
        const resolveScopedWorkAreaOptionsByFloor = typeof deps.resolveScopedWorkAreaOptionsByFloor === 'function'
            ? deps.resolveScopedWorkAreaOptionsByFloor
            : function () { return []; };
        const resolveScopedWorkFieldOptionsByArea = typeof deps.resolveScopedWorkFieldOptionsByArea === 'function'
            ? deps.resolveScopedWorkFieldOptionsByArea
            : function () { return []; };
        const markFloorSortPending = typeof deps.markFloorSortPending === 'function'
            ? deps.markFloorSortPending
            : function () {};
        const autoMergeDuplicateAdditionalTaxonomyRow = typeof deps.autoMergeDuplicateAdditionalTaxonomyRow === 'function'
            ? deps.autoMergeDuplicateAdditionalTaxonomyRow
            : function () {};

function initAdditionalWorkTaxonomyAutocomplete(itemEl, initial = {}) {
    if (!itemEl) {
        return;
    }
    let enableDuplicateAutoMerge = false;

    const floorDisplayInput = itemEl.querySelector('[data-field-display="work_floor"]');
    const floorHiddenInput = itemEl.querySelector('[data-field="work_floor"]');
    const floorListEl = itemEl.querySelector('[data-field-list="work_floor"]');
    const areaDisplayInput = itemEl.querySelector('[data-field-display="work_area"]');
    const areaHiddenInput = itemEl.querySelector('[data-field="work_area"]');
    const areaListEl = itemEl.querySelector('[data-field-list="work_area"]');
    const fieldDisplayInput = itemEl.querySelector('[data-field-display="work_field"]');
    const fieldHiddenInput = itemEl.querySelector('[data-field="work_field"]');
    const fieldListEl = itemEl.querySelector('[data-field-list="work_field"]');

    if (
        !floorDisplayInput ||
        !floorHiddenInput ||
        !floorListEl ||
        !areaDisplayInput ||
        !areaHiddenInput ||
        !areaListEl ||
        !fieldDisplayInput ||
        !fieldHiddenInput ||
        !fieldListEl
    ) {
        return;
    }

    bindAutocompleteScrollLock(floorListEl);
    bindAutocompleteScrollLock(areaListEl);
    bindAutocompleteScrollLock(fieldListEl);

    const normalize = text =>
        String(text || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/gi, '')
            .trim();

    const setupAutocomplete = ({ displayInput, hiddenInput, listEl, getOptions, onChanged, openToBottomOnEmpty = false }) => {
        const closeList = () => {
            listEl.style.display = 'none';
        };

        const getFilteredOptions = term => {
            const options = uniqueFilterTokens(getOptions() || []);
            const query = normalize(term);
            if (!query) return options;
            return options.filter(option => normalize(option).includes(query));
        };

        const applyRawValue = (value, options = {}) => {
            const shouldTrim = options.trim !== false;
            const finalValue = shouldTrim ? String(value || '').trim() : String(value || '');
            displayInput.value = finalValue;
            if (hiddenInput.value !== finalValue) {
                hiddenInput.value = finalValue;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (typeof onChanged === 'function') {
                onChanged();
            }
        };

        const renderList = term => {
            listEl.innerHTML = '';

            const emptyItem = document.createElement('div');
            emptyItem.className = 'autocomplete-item';
            emptyItem.textContent = '- Tidak Pilih -';
            emptyItem.addEventListener('click', function() {
                applyRawValue('');
                closeList();
            });
            listEl.appendChild(emptyItem);

            getFilteredOptions(term).forEach(option => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = option;
                item.addEventListener('click', function() {
                    applyRawValue(option);
                    closeList();
                });
                listEl.appendChild(item);
            });

            listEl.style.display = 'block';
            if (openToBottomOnEmpty && !normalize(term)) {
                requestAnimationFrame(() => {
                    listEl.scrollTop = listEl.scrollHeight;
                });
            }
        };

        const findExactMatch = term => {
            const query = normalize(term);
            if (!query) return null;
            return getFilteredOptions('').find(option => normalize(option) === query) || null;
        };

        displayInput.addEventListener('focus', function() {
            renderList('');
        });

        displayInput.addEventListener('input', function() {
            const term = String(displayInput.value || '');
            applyRawValue(term, { trim: false });
            renderList(term);
        });

        displayInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                const exact = findExactMatch(displayInput.value || '');
                if (exact) {
                    applyRawValue(exact);
                } else {
                    applyRawValue(displayInput.value || '', { trim: true });
                }
                closeList();
                event.preventDefault();
            } else if (event.key === 'Escape') {
                closeList();
            }
        });

        displayInput.addEventListener('blur', function() {
            setTimeout(() => {
                const normalizedValue = String(hiddenInput.value || '').trim();
                hiddenInput.value = normalizedValue;
                displayInput.value = normalizedValue;
                closeList();
                if (typeof onChanged === 'function') {
                    onChanged();
                }
            }, 150);
        });

        document.addEventListener('click', function(event) {
            if (event.target === displayInput || listEl.contains(event.target)) return;
            closeList();
        });

        hiddenInput.addEventListener('change', function() {
            const value = String(hiddenInput.value || '');
            if (displayInput.value !== value) {
                displayInput.value = value;
            }
            if (typeof onChanged === 'function') {
                onChanged();
            }
        });

        return {
            setValue(value) {
                applyRawValue(value);
            },
            openList() {
                renderList(String(displayInput.value || ''));
            },
        };
    };

    const floorAutocomplete = setupAutocomplete({
        displayInput: floorDisplayInput,
        hiddenInput: floorHiddenInput,
        listEl: floorListEl,
        getOptions: () => sortFloors(uniqueFilterTokens([...workFloorOptionValues, floorHiddenInput.value])),
        openToBottomOnEmpty: true,
        onChanged: () => {
            if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                itemEl.__refreshWorkTypeOptions();
            }
            markFloorSortPending();
            if (enableDuplicateAutoMerge) {
                autoMergeDuplicateAdditionalTaxonomyRow(itemEl, 'work_floor');
            }
        },
    });

    const areaAutocomplete = setupAutocomplete({
        displayInput: areaDisplayInput,
        hiddenInput: areaHiddenInput,
        listEl: areaListEl,
        getOptions: () => resolveScopedWorkAreaOptionsByFloor(floorHiddenInput.value, areaHiddenInput.value),
        onChanged: () => {
            if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                itemEl.__refreshWorkTypeOptions();
            }
            if (enableDuplicateAutoMerge) {
                autoMergeDuplicateAdditionalTaxonomyRow(itemEl, 'work_area');
            }
        },
    });

    const fieldAutocomplete = setupAutocomplete({
        displayInput: fieldDisplayInput,
        hiddenInput: fieldHiddenInput,
        listEl: fieldListEl,
        getOptions: () =>
            resolveScopedWorkFieldOptionsByArea(floorHiddenInput.value, areaHiddenInput.value, fieldHiddenInput.value),
        onChanged: () => {
            if (typeof itemEl.__refreshWorkTypeOptions === 'function') {
                itemEl.__refreshWorkTypeOptions();
            }
            if (enableDuplicateAutoMerge) {
                autoMergeDuplicateAdditionalTaxonomyRow(itemEl, 'work_field');
            }
        },
    });

    const initialFloor = String(initial.work_floor || '').trim();
    const initialArea = String(initial.work_area || '').trim();
    const initialField = String(initial.work_field || '').trim();
    if (initialFloor) {
        floorAutocomplete.setValue(initialFloor);
    }
    if (initialArea) {
        areaAutocomplete.setValue(initialArea);
    }
    if (initialField) {
        fieldAutocomplete.setValue(initialField);
    }
    enableDuplicateAutoMerge = true;

    floorDisplayInput.__openAdditionalTaxonomyList = () => floorAutocomplete.openList();
    areaDisplayInput.__openAdditionalTaxonomyList = () => areaAutocomplete.openList();
    fieldDisplayInput.__openAdditionalTaxonomyList = () => fieldAutocomplete.openList();
}

        return initAdditionalWorkTaxonomyAutocomplete(itemEl, initial);
    };
})();