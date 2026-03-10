(function () {
    'use strict';

    window.materialCalcCreateCeramicTypeEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const ceramicTypeOptions = Array.isArray(safeConfig.ceramicTypeOptions)
            ? safeConfig.ceramicTypeOptions
            : [];

        const sortAlphabetic = typeof deps.sortAlphabetic === 'function'
            ? deps.sortAlphabetic
            : function (values) {
                return Array.isArray(values) ? values.slice().sort() : [];
            };
        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) {
                return Array.from(new Set(Array.isArray(values) ? values : []));
            };

        // Ceramic type autocomplete extracted from Blade for bridge-first execution.
        function initCeramicTypeFilterAutocomplete() {
            const displayEl = document.getElementById('ceramicTypeDisplay');
            const hiddenEl = document.getElementById('ceramicTypeSelector');
            const listEl = document.getElementById('ceramicType-list');
            const options = sortAlphabetic(uniqueFilterTokens(ceramicTypeOptions));

            const emptyApi = {
                clear() {},
            };

            if (!displayEl || !hiddenEl || !listEl) {
                return emptyApi;
            }

            const normalizeOption = value => String(value ?? '').trim().toLowerCase();

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
            };

            const getAvailableOptions = term => {
                const query = normalizeOption(term);
                const selected = normalizeOption(hiddenEl.value);
                const available = options.filter(option => {
                    const normalized = normalizeOption(option);
                    if (!normalized) return false;
                    if (selected && normalized === selected) return false;
                    if (!query) return true;
                    return normalized.includes(query);
                });
                return sortAlphabetic(available);
            };

            const renderList = (term = '') => {
                listEl.innerHTML = '';

                const emptyItem = document.createElement('div');
                emptyItem.className = 'autocomplete-item';
                emptyItem.textContent = '- Tidak Pilih -';
                emptyItem.addEventListener('click', function () {
                    applySelection('');
                });
                listEl.appendChild(emptyItem);

                getAvailableOptions(term).forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = option;
                    item.addEventListener('click', function () {
                        applySelection(option);
                    });
                    listEl.appendChild(item);
                });

                listEl.style.display = 'block';
            };

            const findExactOption = term => {
                const query = normalizeOption(term);
                if (!query) return null;
                return options.find(option => normalizeOption(option) === query) || null;
            };

            displayEl.addEventListener('focus', function () {
                if (displayEl.readOnly || displayEl.disabled) return;
                renderList('');
            });

            displayEl.addEventListener('input', function () {
                if (displayEl.readOnly || displayEl.disabled) return;
                const term = this.value || '';
                renderList(term);

                if (!term.trim()) {
                    if (hiddenEl.value) {
                        hiddenEl.value = '';
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                const exactMatch = findExactOption(term);
                if (exactMatch) {
                    if (hiddenEl.value !== exactMatch) {
                        hiddenEl.value = exactMatch;
                        hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else if (hiddenEl.value) {
                    hiddenEl.value = '';
                    hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            displayEl.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                const exactMatch = findExactOption(displayEl.value || '');
                if (exactMatch) {
                    applySelection(exactMatch);
                    event.preventDefault();
                }
            });

            displayEl.addEventListener('blur', function () {
                setTimeout(closeList, 150);
            });

            document.addEventListener('click', function (event) {
                if (event.target === displayEl || listEl.contains(event.target)) return;
                closeList();
            });

            hiddenEl.addEventListener('change', function () {
                if (displayEl.value !== hiddenEl.value) {
                    displayEl.value = hiddenEl.value;
                }
            });

            if (options.length === 0) {
                displayEl.disabled = true;
                displayEl.placeholder = 'Tidak ada data jenis keramik';
            }

            return {
                clear() {
                    applySelection('');
                },
            };
        }

        return initCeramicTypeFilterAutocomplete();
    };
})();
