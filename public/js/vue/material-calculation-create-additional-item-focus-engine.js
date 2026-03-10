(function () {
    window.materialCalcCreateAndFocusAdditionalWorkItemEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const initial = safeConfig.initial && typeof safeConfig.initial === 'object' ? safeConfig.initial : {};
        const afterElement = safeConfig.afterElement || null;
        const focusField = String(safeConfig.focusField || 'work_type');
        const options = safeConfig.options && typeof safeConfig.options === 'object' ? safeConfig.options : {};

        const createAdditionalWorkItemForm = typeof deps.createAdditionalWorkItemForm === 'function'
            ? deps.createAdditionalWorkItemForm
            : function () { return null; };

        function createAndFocusAdditionalWorkItem(initial = {}, afterElement = null, focusField = 'work_type', options = {}) {
            const newForm = createAdditionalWorkItemForm(initial, afterElement, options);
            if (!newForm) {
                return null;
            }

            const openAutocompleteListForInput = inputEl => {
                if (!(inputEl instanceof HTMLElement)) {
                    return;
                }
                if (typeof inputEl.__openAdditionalTaxonomyList === 'function') {
                    inputEl.__openAdditionalTaxonomyList();
                    return;
                }
                if (typeof inputEl.__openAdditionalWorkTypeList === 'function') {
                    inputEl.__openAdditionalWorkTypeList();
                }
            };

            const scheduleFocusAndOpenAutocompleteInput = inputEl => {
                if (!(inputEl instanceof HTMLElement)) {
                    return;
                }
                setTimeout(() => {
                    if (!inputEl.isConnected) {
                        return;
                    }
                    inputEl.focus();
                    openAutocompleteListForInput(inputEl);
                }, 0);
            };

            const selectorMap = {
                work_floor: '[data-field-display="work_floor"]',
                work_area: '[data-field-display="work_area"]',
                work_field: '[data-field-display="work_field"]',
                work_type: '[data-field-display="work_type"]',
            };
            const focusSelector = selectorMap[focusField] || selectorMap.work_type;
            const focusInput = newForm.querySelector(focusSelector);
            if (focusInput) {
                scheduleFocusAndOpenAutocompleteInput(focusInput);
            }
            return newForm;
        }

        return createAndFocusAdditionalWorkItem(initial, afterElement, focusField, options);
    };

    window.materialCalcShowTaxonomyActionErrorEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const message = String(safeConfig.message || '');
        const focusEl = safeConfig.focusEl || null;

        function showTaxonomyActionError(message, focusEl = null) {
            if (typeof window.showToast === 'function') {
                window.showToast(message, 'error');
            } else {
                alert(message);
            }
            if (focusEl && typeof focusEl.focus === 'function') {
                setTimeout(() => {
                    if (!(focusEl instanceof HTMLElement) || !focusEl.isConnected) {
                        return;
                    }
                    focusEl.focus();
                    if (typeof focusEl.__openAdditionalTaxonomyList === 'function') {
                        focusEl.__openAdditionalTaxonomyList();
                    } else if (typeof focusEl.__openAdditionalWorkTypeList === 'function') {
                        focusEl.__openAdditionalWorkTypeList();
                    }
                }, 0);
            }
        }

        return showTaxonomyActionError(message, focusEl);
    };
})();
