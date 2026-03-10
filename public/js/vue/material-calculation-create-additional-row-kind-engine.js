(function () {
    window.materialCalcCreateAdditionalRowKindEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const itemEl = safeConfig.itemEl || null;
        const rowKind = safeConfig.rowKind;

        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const ensureAdditionalTaxonomyActionsFooter = typeof deps.ensureAdditionalTaxonomyActionsFooter === 'function'
            ? deps.ensureAdditionalTaxonomyActionsFooter
            : function () { return null; };
        const getDirectChildMatching = typeof deps.getDirectChildMatching === 'function'
            ? deps.getDirectChildMatching
            : function () { return null; };
        const setAdditionalItemContentCollapsed = typeof deps.setAdditionalItemContentCollapsed === 'function'
            ? deps.setAdditionalItemContentCollapsed
            : function () {};
        const syncDirectChildItemRowVisibilityForCollapsedParent = typeof deps.syncDirectChildItemRowVisibilityForCollapsedParent === 'function'
            ? deps.syncDirectChildItemRowVisibilityForCollapsedParent
            : function () {};
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };

        function setAdditionalWorkItemRowKind(itemEl, rowKind = 'area') {
            if (!(itemEl instanceof HTMLElement)) {
                return;
            }

            const normalizedKind = normalizeBundleRowKind(rowKind);
            itemEl.setAttribute('data-row-kind', normalizedKind);

            // Remove legacy layout classes no longer used
            itemEl.classList.remove('taxonomy-tree-main', 'taxonomy-group-card', 'additional-area-inline-row', 'additional-field-inline-row');

            const rowKindInput = itemEl.querySelector('[data-field="row_kind"]');
            if (rowKindInput) {
                rowKindInput.value = normalizedKind;
            }

            const parentEl = itemEl.parentElement instanceof HTMLElement ? itemEl.parentElement : null;
            const isNestedAreaRow =
                normalizedKind === 'area' && !!parentEl && parentEl.matches('[data-main-area-children], [data-floor-children]');

            // Top-level floor rows get card styling; nested items are flat
            const isFloorGroup = normalizedKind === 'area' && !isNestedAreaRow;
            itemEl.classList.toggle('is-floor-group', isFloorGroup);

            // Determine which taxonomy cells are inherited (hidden) from parent context
            const floorInherited = isNestedAreaRow || normalizedKind === 'field' || normalizedKind === 'item';
            const areaInherited = normalizedKind === 'field' || normalizedKind === 'item';
            const fieldInherited = normalizedKind === 'item';

            const applyCell = (selector, inherited) => {
                const cell = itemEl.querySelector(selector);
                if (!(cell instanceof HTMLElement)) {
                    return;
                }
                cell.classList.toggle('is-inherited', inherited);
            };

            applyCell('[data-taxonomy-cell="floor"]', floorInherited);
            applyCell('[data-taxonomy-cell="area"]', areaInherited);
            applyCell('[data-taxonomy-cell="field"]', fieldInherited);

            ensureAdditionalTaxonomyActionsFooter(itemEl);

            // Hide the entire taxonomy header for item rows (all cells are inherited)
            const taxonomyHeader = itemEl.querySelector('.additional-taxonomy-header');
            if (taxonomyHeader) {
                taxonomyHeader.style.display = normalizedKind === 'item' ? 'none' : '';
            }

            // Show/hide action buttons based on row_kind
            const addAreaBtn = itemEl.querySelector('[data-action="add-area"]');
            const addFieldBtn = itemEl.querySelector('[data-action="add-field"]');
            const addItemBtn = itemEl.querySelector('[data-action="add-item"]');
            const rowGrid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            const rowAreaHost = getDirectChildMatching(rowGrid, '[data-area-children]');
            const rowFloorHost = getDirectChildMatching(rowGrid, '[data-floor-children]');
            const actionsRow =
                getDirectChildMatching(rowGrid, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(rowAreaHost, '.additional-taxonomy-actions-row') ||
                getDirectChildMatching(rowFloorHost, '.additional-taxonomy-actions-row');
            const isItemRow = normalizedKind === 'item';
            const showAddArea = !isItemRow && !floorInherited;
            const showAddField = !isItemRow && !areaInherited;
            const showAddItem = !isItemRow && !fieldInherited;

            if (addAreaBtn) {
                addAreaBtn.style.display = showAddArea ? '' : 'none';
            }
            if (addFieldBtn) {
                addFieldBtn.style.display = showAddField ? '' : 'none';
            }
            if (addItemBtn) {
                addItemBtn.style.display = showAddItem ? '' : 'none';
            }
            if (actionsRow) {
                actionsRow.style.display = showAddArea || showAddField || showAddItem ? '' : 'none';
            }

            // Item rows don't have visible taxonomy cards, so ensure item content stays visible.
            if (normalizedKind === 'item') {
                setAdditionalItemContentCollapsed(itemEl, false);
            } else {
                const wantsCollapsed = itemEl.dataset.itemContentCollapsed === '1';
                setAdditionalItemContentCollapsed(itemEl, wantsCollapsed);
            }

            const parentRow =
                itemEl.parentElement instanceof HTMLElement
                    ? itemEl.parentElement.closest('.additional-work-item[data-additional-work-item="true"]')
                    : null;
            if (parentRow instanceof HTMLElement) {
                syncDirectChildItemRowVisibilityForCollapsedParent(parentRow);
            }
        }

        return setAdditionalWorkItemRowKind(itemEl, rowKind);
    };
})();
