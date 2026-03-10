(function () {
    window.materialCalcCreateSortAdditionalItemsEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getIsRebuildingFloorCardOrder = typeof deps.getIsRebuildingFloorCardOrder === 'function'
            ? deps.getIsRebuildingFloorCardOrder
            : function () { return false; };
        const setHasPendingFloorSort = typeof deps.setHasPendingFloorSort === 'function'
            ? deps.setHasPendingFloorSort
            : function () {};
        const rebuildBundleUiFromSortedFloorOrder = typeof deps.rebuildBundleUiFromSortedFloorOrder === 'function'
            ? deps.rebuildBundleUiFromSortedFloorOrder
            : function () { return false; };
        const sortMainFloorCards = typeof deps.sortMainFloorCards === 'function'
            ? deps.sortMainFloorCards
            : function () { return false; };
        const additionalWorkItemsList = deps.additionalWorkItemsList || null;
        const getTopLevelAdditionalRows = typeof deps.getTopLevelAdditionalRows === 'function'
            ? deps.getTopLevelAdditionalRows
            : function () { return []; };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) { return Array.isArray(values) ? values : []; };
        const refreshAdditionalWorkItemHeader = typeof deps.refreshAdditionalWorkItemHeader === 'function'
            ? deps.refreshAdditionalWorkItemHeader
            : function () {};

        function sortAdditionalWorkItems() {
            if (getIsRebuildingFloorCardOrder()) {
                return;
            }
            setHasPendingFloorSort(false);
            const mainCardSwapped = rebuildBundleUiFromSortedFloorOrder();
            const mainFloorCardsSorted = sortMainFloorCards();
            if (!additionalWorkItemsList) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }
            const items = getTopLevelAdditionalRows();
            if (items.length <= 1) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }

            const floorValues = items.map(item => getAdditionalFieldValue(item, 'work_floor'));
            const sortedFloorValues = sortFloors([...floorValues]);

            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const sortedItems = [...items].sort((a, b) => {
                const floorA = getAdditionalFieldValue(a, 'work_floor');
                const floorB = getAdditionalFieldValue(b, 'work_floor');
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                return priorityA - priorityB;
            });

            const alreadySorted = items.every((item, i) => item === sortedItems[i]);
            if (alreadySorted) {
                if (mainCardSwapped || mainFloorCardsSorted) {
                    refreshAdditionalWorkItemHeader();
                }
                return;
            }

            sortedItems.forEach(item => additionalWorkItemsList.appendChild(item));
            refreshAdditionalWorkItemHeader();
        }

        return sortAdditionalWorkItems();
    };
})();
