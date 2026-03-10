(function () {
    window.materialCalcCreateRebuildFloorOrderEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getIsRebuildingFloorCardOrder = typeof deps.getIsRebuildingFloorCardOrder === 'function'
            ? deps.getIsRebuildingFloorCardOrder
            : function () { return false; };
        const setIsRebuildingFloorCardOrder = typeof deps.setIsRebuildingFloorCardOrder === 'function'
            ? deps.setIsRebuildingFloorCardOrder
            : function () {};
        const getTopLevelAdditionalRows = typeof deps.getTopLevelAdditionalRows === 'function'
            ? deps.getTopLevelAdditionalRows
            : function () { return []; };
        const collectMainWorkItemDraft = typeof deps.collectMainWorkItemDraft === 'function'
            ? deps.collectMainWorkItemDraft
            : function () { return null; };
        const collectAdditionalWorkItemData = typeof deps.collectAdditionalWorkItemData === 'function'
            ? deps.collectAdditionalWorkItemData
            : function () { return null; };
        const sortBundleItemsByFloorStable = typeof deps.sortBundleItemsByFloorStable === 'function'
            ? deps.sortBundleItemsByFloorStable
            : function (items) { return Array.isArray(items) ? items : []; };
        const getMainAreaChildrenHost = typeof deps.getMainAreaChildrenHost === 'function'
            ? deps.getMainAreaChildrenHost
            : function () { return null; };
        const getDirectAdditionalRowHost = typeof deps.getDirectAdditionalRowHost === 'function'
            ? deps.getDirectAdditionalRowHost
            : function () { return null; };
        const swapDirectAdditionalChildRows = typeof deps.swapDirectAdditionalChildRows === 'function'
            ? deps.swapDirectAdditionalChildRows
            : function () {};
        const setAdditionalFloorValueForRowsInScope = typeof deps.setAdditionalFloorValueForRowsInScope === 'function'
            ? deps.setAdditionalFloorValueForRowsInScope
            : function () {};
        const applyMainWorkItemFromBundleItem = typeof deps.applyMainWorkItemFromBundleItem === 'function'
            ? deps.applyMainWorkItemFromBundleItem
            : function () {};
        const applyAdditionalWorkItemFromBundleItem = typeof deps.applyAdditionalWorkItemFromBundleItem === 'function'
            ? deps.applyAdditionalWorkItemFromBundleItem
            : function () {};
        const syncBundleFromForms = typeof deps.syncBundleFromForms === 'function'
            ? deps.syncBundleFromForms
            : function () {};
        const relocateFilterSectionToRightGrid = typeof deps.relocateFilterSectionToRightGrid === 'function'
            ? deps.relocateFilterSectionToRightGrid
            : function () {};
        const relocateMainTaxonomyActionButtonsToFooter = typeof deps.relocateMainTaxonomyActionButtonsToFooter === 'function'
            ? deps.relocateMainTaxonomyActionButtonsToFooter
            : function () {};
        const refreshAdditionalTaxonomyActionFooters = typeof deps.refreshAdditionalTaxonomyActionFooters === 'function'
            ? deps.refreshAdditionalTaxonomyActionFooters
            : function () {};

        function rebuildBundleUiFromSortedFloorOrder() {
            if (getIsRebuildingFloorCardOrder()) {
                return false;
            }

            const topLevelRows = getTopLevelAdditionalRows();
            if (topLevelRows.length === 0) {
                return false;
            }

            const mainDraft = collectMainWorkItemDraft();
            const entries = [
                { source: 'main', row: null, data: mainDraft },
                ...topLevelRows
                    .map((row, index) => ({
                        source: 'additional',
                        row,
                        data: collectAdditionalWorkItemData(row, index + 1),
                    }))
                    .filter(entry => entry.data),
            ];
            if (entries.length <= 1) {
                return false;
            }

            const sortedData = sortBundleItemsByFloorStable(entries.map(entry => entry.data));
            const nextMainData = sortedData[0] || null;
            if (!nextMainData || nextMainData === mainDraft) {
                return false;
            }

            const candidateEntry = entries.find(entry => entry.source === 'additional' && entry.data === nextMainData);
            if (!candidateEntry || !(candidateEntry.row instanceof HTMLElement)) {
                return false;
            }

            const candidateRow = candidateEntry.row;
            const candidateData = candidateEntry.data;
            const oldMainFloor = String(mainDraft.work_floor || '').trim();
            const nextMainFloor = String(candidateData.work_floor || '').trim();

            setIsRebuildingFloorCardOrder(true);
            try {
                const mainAreaHost = getMainAreaChildrenHost();
                const candidateFloorHost = getDirectAdditionalRowHost(candidateRow, '[data-floor-children]');

                if (mainAreaHost instanceof HTMLElement && candidateFloorHost instanceof HTMLElement) {
                    // Swap the nested rows too, so the whole floor card (including its children) moves together.
                    swapDirectAdditionalChildRows(mainAreaHost, candidateFloorHost);
                }

                if (mainAreaHost instanceof HTMLElement && oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(mainAreaHost, nextMainFloor);
                }
                if (candidateFloorHost instanceof HTMLElement && oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(candidateFloorHost, oldMainFloor);
                } else if (oldMainFloor !== nextMainFloor) {
                    setAdditionalFloorValueForRowsInScope(candidateRow, oldMainFloor, { excludeRoot: true });
                }

                applyMainWorkItemFromBundleItem(candidateData);
                applyAdditionalWorkItemFromBundleItem(candidateRow, mainDraft);
                syncBundleFromForms();
                relocateFilterSectionToRightGrid();
                relocateMainTaxonomyActionButtonsToFooter();
                refreshAdditionalTaxonomyActionFooters();
            } finally {
                setIsRebuildingFloorCardOrder(false);
            }

            return true;
        }

        return rebuildBundleUiFromSortedFloorOrder();
    };
})();
