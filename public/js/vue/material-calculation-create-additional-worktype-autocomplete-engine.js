(function () {
    window.materialCalcCreateAdditionalWorkTypeAutocompleteEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const itemEl = safeConfig.itemEl;
        const initial = safeConfig.initial && typeof safeConfig.initial === 'object' ? safeConfig.initial : {};

        const bundleFormulaOptions = Array.isArray(deps.bundleFormulaOptions)
            ? deps.bundleFormulaOptions
            : [];
        const bindAutocompleteScrollLock = typeof deps.bindAutocompleteScrollLock === 'function'
            ? deps.bindAutocompleteScrollLock
            : function () {};
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const resolveScopedWorkTypeOptionsByTaxonomy = typeof deps.resolveScopedWorkTypeOptionsByTaxonomy === 'function'
            ? deps.resolveScopedWorkTypeOptionsByTaxonomy
            : function () { return []; };

function initAdditionalWorkTypeAutocomplete(itemEl, initial = {}) {
    if (!itemEl) {
        return;
    }

    const displayInput = itemEl.querySelector('[data-field-display="work_type"]');
    const hiddenInput = itemEl.querySelector('[data-field="work_type"]');
    const listEl = itemEl.querySelector('[data-field-list="work_type"]');

    if (!displayInput || !hiddenInput || !listEl || bundleFormulaOptions.length === 0) {
        return;
    }

    bindAutocompleteScrollLock(listEl);

    const baseOptions = bundleFormulaOptions
        .filter(option => option && option.code && option.name)
        .map(option => ({
            code: String(option.code),
            name: String(option.name),
        }));

    const getScopedOptions = () => {
        const rowKind = normalizeBundleRowKind(
            getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
        );
        if (rowKind === 'item') {
            return baseOptions;
        }

        const selectedFloor = getAdditionalFieldValue(itemEl, 'work_floor');
        const selectedArea = getAdditionalFieldValue(itemEl, 'work_area');
        const selectedField = getAdditionalFieldValue(itemEl, 'work_field');
        const scoped = resolveScopedWorkTypeOptionsByTaxonomy(selectedFloor, selectedArea, selectedField);
        if (!Array.isArray(scoped) || scoped.length === 0) {
            return baseOptions;
        }
        return scoped
            .filter(option => option && option.code && option.name)
            .map(option => ({
                code: String(option.code),
                name: String(option.name),
            }));
    };

    const normalize = text =>
        String(text || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/gi, '')
            .trim();

    const filterOptions = term => {
        const options = getScopedOptions();
        const query = normalize(term);
        if (!query) return options;
        return options.filter(option => {
            const name = normalize(option.name);
            const code = normalize(option.code);
            return name.includes(query) || code.includes(query);
        });
    };

    const findExactMatch = term => {
        const options = getScopedOptions();
        const query = normalize(term);
        if (!query) return null;
        return options.find(option => normalize(option.name) === query || normalize(option.code) === query) || null;
    };

    const closeList = () => {
        listEl.style.display = 'none';
    };

    const openList = () => {
        renderList(filterOptions(''));
    };

    const applySelection = option => {
        if (!option) return;
        displayInput.value = option.name;
        if (hiddenInput.value !== option.code) {
            hiddenInput.value = option.code;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const titleInput = itemEl.querySelector('[data-field="title"]');
        if (titleInput && !String(titleInput.value || '').trim()) {
            titleInput.value = option.name;
        }
        closeList();
    };

    const renderList = items => {
        listEl.innerHTML = '';
        items.forEach(option => {
            const row = document.createElement('div');
            row.className = 'autocomplete-item';
            row.textContent = option.name;
            row.addEventListener('click', function() {
                applySelection(option);
            });
            listEl.appendChild(row);
        });
        listEl.style.display = items.length > 0 ? 'block' : 'none';
    };

    displayInput.addEventListener('focus', function() {
        if (displayInput.readOnly || displayInput.disabled) return;
        openList();
    });

    displayInput.addEventListener('input', function() {
        if (displayInput.readOnly || displayInput.disabled) return;
        const term = this.value || '';
        renderList(filterOptions(term));

        if (!term.trim()) {
            if (hiddenInput.value) {
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            return;
        }

        const exactMatch = findExactMatch(term);
        if (exactMatch && hiddenInput.value !== exactMatch.code) {
            hiddenInput.value = exactMatch.code;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    displayInput.addEventListener('keydown', function(event) {
        if (event.key !== 'Enter') return;
        const exactMatch = findExactMatch(displayInput.value);
        if (exactMatch) {
            applySelection(exactMatch);
            event.preventDefault();
        }
    });

    displayInput.addEventListener('blur', function() {
        setTimeout(closeList, 150);
    });

    document.addEventListener('click', function(event) {
        if (event.target === displayInput || listEl.contains(event.target)) return;
        closeList();
    });

    hiddenInput.addEventListener('change', function() {
        const options = getScopedOptions();
        const selected = options.find(option => option.code === hiddenInput.value);
        if (selected) {
            if (displayInput.value !== selected.name) {
                displayInput.value = selected.name;
            }
            return;
        }
        if (!hiddenInput.value) {
            displayInput.value = '';
        }
    });

    const refreshOptions = () => {
        const options = getScopedOptions();
        const selected = options.find(option => option.code === hiddenInput.value);
        if (selected) {
            if (displayInput.value !== selected.name) {
                displayInput.value = selected.name;
            }
        } else if (listEl.style.display === 'block') {
            renderList(filterOptions(displayInput.value || ''));
        }
    };

    const initialWorkType = String(initial.work_type || '').trim();
    if (initialWorkType) {
        hiddenInput.value = initialWorkType;
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        displayInput.value = '';
        hiddenInput.value = '';
    }

    // Expose helper so newly added rows can reliably auto-open the dropdown.
    displayInput.__openAdditionalWorkTypeList = openList;
    itemEl.__refreshWorkTypeOptions = refreshOptions;
}


        return initAdditionalWorkTypeAutocomplete(itemEl, initial);
    };
})();