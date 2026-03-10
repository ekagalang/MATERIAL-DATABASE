(function () {
    window.materialCalcCreateAdditionalTaxonomyFooterEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const itemEl = safeConfig.itemEl || null;

        const getDirectChildMatching = typeof deps.getDirectChildMatching === 'function'
            ? deps.getDirectChildMatching
            : function () { return null; };
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const getDirectAdditionalChildRows = typeof deps.getDirectAdditionalChildRows === 'function'
            ? deps.getDirectAdditionalChildRows
            : function () { return []; };

        function ensureAdditionalTaxonomyActionsFooter(itemEl) {
            if (!(itemEl instanceof HTMLElement)) {
                return null;
            }

            const grid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            if (!(grid instanceof HTMLElement)) {
                return null;
            }

            const areaHost = getDirectChildMatching(grid, '[data-area-children]');
            const floorHost = getDirectChildMatching(grid, '[data-floor-children]');
            const rowKind = normalizeBundleRowKind(
                itemEl.getAttribute('data-row-kind') || getAdditionalFieldValue(itemEl, 'row_kind') || 'area',
            );

            let footer =
                getDirectChildMatching(grid, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(areaHost, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(floorHost, '.additional-taxonomy-actions-row');
            if (!(footer instanceof HTMLElement)) {
                footer = document.createElement('div');
                footer.className = 'additional-taxonomy-actions-row';
            }

            const resolveCell = cellKey => {
                const cell = itemEl.querySelector(`.additional-taxonomy-cell[data-taxonomy-cell="${cellKey}"]`);
                return cell instanceof HTMLElement ? cell : null;
            };
            const areaCell = resolveCell('area');
            const fieldCell = resolveCell('field');
            const fieldCellBody =
                fieldCell instanceof HTMLElement
                    ? fieldCell.querySelector('.additional-taxonomy-cell-body')
                    : null;

            const ensureInlineActionsHost = cellEl => {
                if (!(cellEl instanceof HTMLElement)) {
                    return null;
                }
                let hostEl = getDirectChildMatching(cellEl, '.additional-taxonomy-inline-actions');
                if (!(hostEl instanceof HTMLElement)) {
                    hostEl = document.createElement('div');
                    hostEl.className = 'taxonomy-level-actions additional-taxonomy-inline-actions';
                    cellEl.appendChild(hostEl);
                }
                return hostEl;
            };
            const areaInlineActionsHost = ensureInlineActionsHost(areaCell);

            const moveButtonToHost = (action, hostEl) => {
                const btn = itemEl.querySelector(`[data-action="${action}"]`);
                if (!(btn instanceof HTMLElement)) {
                    return null;
                }
                if (hostEl instanceof HTMLElement) {
                    if (btn.parentElement !== hostEl) {
                        hostEl.appendChild(btn);
                    }
                } else if (btn.parentElement !== footer) {
                    footer.appendChild(btn);
                }
                return btn;
            };

            const addAreaBtn = moveButtonToHost('add-area', areaInlineActionsHost);
            const addFieldBtn = moveButtonToHost('add-field', fieldCellBody);
            if (addFieldBtn instanceof HTMLElement && fieldCellBody instanceof HTMLElement) {
                const toggleBtn = fieldCellBody.querySelector('[data-action="toggle-item-visibility"]');
                if (toggleBtn instanceof HTMLElement && addFieldBtn !== toggleBtn.previousElementSibling) {
                    fieldCellBody.insertBefore(addFieldBtn, toggleBtn);
                }
            }
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            if (addItemBtn instanceof HTMLElement && addItemBtn.parentElement !== footer) {
                footer.appendChild(addItemBtn);
            }

            [addAreaBtn, addFieldBtn].forEach(btn => {
                if (!(btn instanceof HTMLElement)) {
                    return;
                }
                btn.classList.add('is-inline-taxonomy-action');
            });
            if (addFieldBtn instanceof HTMLElement) {
                addFieldBtn.classList.add('is-inline-add-field');
            }
            if (addItemBtn instanceof HTMLElement) {
                addItemBtn.classList.remove('is-inline-taxonomy-action');
            }

            [areaCell].forEach(cell => {
                if (!(cell instanceof HTMLElement)) {
                    return;
                }
                cell.classList.toggle(
                    'has-inline-action',
                    !!cell.querySelector('.additional-taxonomy-inline-actions .taxonomy-level-btn'),
                );
            });
            if (fieldCellBody instanceof HTMLElement) {
                fieldCellBody.classList.toggle(
                    'has-inline-add-field',
                    !!fieldCellBody.querySelector('[data-action="add-field"]'),
                );
            }

            // Item rows hide all taxonomy action buttons. Keep the footer on the row grid
            // (not inside child hosts) so nested hosts stay truly empty and collapse spacing.
            if (rowKind === 'item') {
                if (footer.parentElement !== grid) {
                    grid.appendChild(footer);
                }
                return footer;
            }

            if (areaHost instanceof HTMLElement) {
                const childRows = getDirectAdditionalChildRows(areaHost);
                const firstNonItemRow =
                    childRows.find(row => {
                        const childKind = normalizeBundleRowKind(
                            getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area',
                        );
                        return childKind !== 'item';
                    }) || null;

                if (firstNonItemRow instanceof HTMLElement) {
                    areaHost.insertBefore(footer, firstNonItemRow);
                } else if (footer.parentElement !== areaHost || areaHost.lastElementChild !== footer) {
                    areaHost.appendChild(footer);
                }
            } else if (footer.parentElement !== grid || grid.lastElementChild !== footer) {
                grid.appendChild(footer);
            }

            return footer;
        }

        return ensureAdditionalTaxonomyActionsFooter(itemEl);
    };
})();
