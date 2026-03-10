(function () {
    window.materialCalcCreateAdditionalFieldEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const methodName = String(safeConfig.methodName || '').trim();
        const args = Array.isArray(safeConfig.args) ? safeConfig.args : [];

        function getAdditionalFieldValue(itemEl, key) {
            const el = itemEl.querySelector(`[data-field="${key}"]`);
            const hiddenValue = el ? String(el.value || '').trim() : '';
            if (hiddenValue) {
                return hiddenValue;
            }

            if (key === 'work_floor' || key === 'work_area' || key === 'work_field' || key === 'work_type') {
                const displayEl = itemEl.querySelector(`[data-field-display="${key}"]`);
                return displayEl ? String(displayEl.value || '').trim() : '';
            }

            return '';
        }

        function setAdditionalFieldValue(itemEl, key, value) {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }
            const nextValue = String(value ?? '');
            const hiddenInput = itemEl.querySelector(`[data-field="${key}"]`);
            if (hiddenInput) {
                hiddenInput.value = nextValue;
            }
            const displayInput = itemEl.querySelector(`[data-field-display="${key}"]`);
            if (displayInput) {
                displayInput.value = nextValue;
            }
        }

        if (methodName === 'getAdditionalFieldValue') {
            return getAdditionalFieldValue(args[0], args[1]);
        }
        if (methodName === 'setAdditionalFieldValue') {
            return setAdditionalFieldValue(args[0], args[1], args[2]);
        }
    };
})();
