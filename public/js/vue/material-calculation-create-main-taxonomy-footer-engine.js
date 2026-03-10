(function () {
    window.materialCalcCreateMainTaxonomyFooterEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getMainAreaChildrenHost = typeof deps.getMainAreaChildrenHost === 'function'
            ? deps.getMainAreaChildrenHost
            : function () { return null; };
        const getDirectChildMatching = typeof deps.getDirectChildMatching === 'function'
            ? deps.getDirectChildMatching
            : function () { return null; };
        const getDirectAdditionalChildRows = typeof deps.getDirectAdditionalChildRows === 'function'
            ? deps.getDirectAdditionalChildRows
            : function () { return []; };
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };

        function relocateMainTaxonomyActionButtonsToFooter() {
            const inputFormContainer = document.getElementById('inputFormContainer');
            if (!(inputFormContainer instanceof HTMLElement)) {
                return null;
            }

            const cardHost =
                document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card') instanceof HTMLElement
                    ? document.querySelector('#calculationForm .taxonomy-tree-main.taxonomy-group-card')
                    : null;
            const host =
                cardHost instanceof HTMLElement
                    ? cardHost
                    : (inputFormContainer.parentElement instanceof HTMLElement ? inputFormContainer.parentElement : null);
            if (!(host instanceof HTMLElement)) {
                return null;
            }
            const mainAreaHost = getMainAreaChildrenHost();
            const areaGroup =
                host.querySelector('.work-area-group.taxonomy-inline-group') instanceof HTMLElement
                    ? host.querySelector('.work-area-group.taxonomy-inline-group')
                    : null;
            const fieldGroup =
                host.querySelector('.work-field-group.taxonomy-inline-group') instanceof HTMLElement
                    ? host.querySelector('.work-field-group.taxonomy-inline-group')
                    : null;

            const ensureActionsHost = groupEl => {
                if (!(groupEl instanceof HTMLElement)) {
                    return null;
                }
                let actionsEl = getDirectChildMatching(groupEl, '.taxonomy-level-actions');
                if (!(actionsEl instanceof HTMLElement)) {
                    actionsEl = document.createElement('div');
                    actionsEl.className = 'taxonomy-level-actions';
                    groupEl.appendChild(actionsEl);
                }
                return actionsEl;
            };

            const areaActionsHost = ensureActionsHost(areaGroup);
            const fieldActionsHost = ensureActionsHost(fieldGroup);

            const addAreaBtn = document.getElementById('addAreaFromMainBtn');
            if (addAreaBtn instanceof HTMLElement && areaActionsHost instanceof HTMLElement) {
                if (addAreaBtn.parentElement !== areaActionsHost) {
                    areaActionsHost.appendChild(addAreaBtn);
                }
            }

            const addFieldBtn = document.getElementById('addFieldFromMainBtn');
            if (addFieldBtn instanceof HTMLElement && fieldActionsHost instanceof HTMLElement) {
                if (addFieldBtn.parentElement !== fieldActionsHost) {
                    fieldActionsHost.appendChild(addFieldBtn);
                }
                const toggleMainBtn = fieldActionsHost.querySelector('#toggleMainFieldItemVisibilityBtn');
                if (toggleMainBtn instanceof HTMLElement && addFieldBtn !== toggleMainBtn.previousElementSibling) {
                    fieldActionsHost.insertBefore(addFieldBtn, toggleMainBtn);
                }
            }

            let footer =
                getDirectChildMatching(host, '.main-taxonomy-actions-row') ||
                getDirectChildMatching(mainAreaHost, '.main-taxonomy-actions-row');
            if (!(footer instanceof HTMLElement)) {
                footer = document.createElement('div');
                footer.className = 'main-taxonomy-actions-row';
                host.appendChild(footer);
            }

            const addItemBtn = document.getElementById('addItemFromMainBtn');
            if (addItemBtn instanceof HTMLElement && footer instanceof HTMLElement) {
                if (addItemBtn.parentElement !== footer) {
                    footer.appendChild(addItemBtn);
                }
            }

            if (mainAreaHost instanceof HTMLElement) {
                const mainAreaRows = getDirectAdditionalChildRows(mainAreaHost);
                const firstNonItemRow =
                    mainAreaRows.find(row => {
                        const childKind = normalizeBundleRowKind(
                            getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                        );
                        return childKind !== 'item';
                    }) || null;

                if (firstNonItemRow instanceof HTMLElement) {
                    mainAreaHost.insertBefore(footer, firstNonItemRow);
                } else if (footer.parentElement !== mainAreaHost || mainAreaHost.lastElementChild !== footer) {
                    mainAreaHost.appendChild(footer);
                }
            } else if (footer.parentElement !== host || host.lastElementChild !== footer) {
                host.appendChild(footer);
            }

            [host, mainAreaHost].forEach(container => {
                if (!(container instanceof HTMLElement)) {
                    return;
                }
                Array.from(container.children).forEach(child => {
                    if (
                        child instanceof HTMLElement &&
                        child !== footer &&
                        child.matches('.main-taxonomy-actions-row') &&
                        child.childElementCount === 0
                    ) {
                        child.remove();
                    }
                });
            });

            return footer;
        }

        return relocateMainTaxonomyActionButtonsToFooter();
    };
})();
