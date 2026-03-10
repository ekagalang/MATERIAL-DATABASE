(function () {
    window.materialCalcCreateAdditionalItemHeaderEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const additionalWorkItemsList = deps.additionalWorkItemsList || null;
        const getAllAdditionalWorkRows = typeof deps.getAllAdditionalWorkRows === 'function'
            ? deps.getAllAdditionalWorkRows
            : function () { return []; };
        const mainWorkTypeLabel = deps.mainWorkTypeLabel || null;
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };

        function refreshAdditionalWorkItemHeader() {
            if (!additionalWorkItemsList) {
                return;
            }
            const items = getAllAdditionalWorkRows();
            const hasAdditionalItems = items.length > 0;

            if (mainWorkTypeLabel) {
                mainWorkTypeLabel.textContent = hasAdditionalItems ? 'Item Pekerjaan 1' : 'Item Pekerjaan';
            }

            const setAdditionalItemLabel = (itemEl, globalNumber) => {
                if (!(itemEl instanceof HTMLElement)) {
                    return;
                }
                const label = itemEl.querySelector('[data-additional-worktype-label]');
                if (label) {
                    label.textContent = `Item Pekerjaan ${globalNumber}`;
                }

                const rowKind = normalizeBundleRowKind(
                    getAdditionalFieldValue(itemEl, 'row_kind') || itemEl.dataset.rowKind || 'area',
                );
                const parentEl = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
                const siblingRows = parentEl
                    ? Array.from(parentEl.children).filter(row =>
                          row instanceof HTMLElement && row.matches('[data-additional-work-item="true"]'),
                      )
                    : [];
                const itemIndexInParent = siblingRows.indexOf(itemEl);
                const shouldShowFieldBreak = rowKind === 'field' && itemIndexInParent > 0;
                itemEl.classList.toggle('field-break', shouldShowFieldBreak);
            };

            let nextGlobalItemNumber = 2;
            items.forEach(itemEl => {
                setAdditionalItemLabel(itemEl, nextGlobalItemNumber);
                nextGlobalItemNumber += 1;
            });
        }

        return refreshAdditionalWorkItemHeader();
    };
})();
