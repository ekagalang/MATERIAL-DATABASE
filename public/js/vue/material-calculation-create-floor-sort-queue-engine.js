(function () {
    window.materialCalcMarkFloorSortPendingEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getIsRebuildingFloorCardOrder = typeof deps.getIsRebuildingFloorCardOrder === 'function'
            ? deps.getIsRebuildingFloorCardOrder
            : function () { return false; };
        const setHasPendingFloorSort = typeof deps.setHasPendingFloorSort === 'function'
            ? deps.setHasPendingFloorSort
            : function () {};

        function markFloorSortPending() {
            if (getIsRebuildingFloorCardOrder()) {
                return;
            }
            setHasPendingFloorSort(true);
        }

        return markFloorSortPending();
    };

    window.materialCalcFlushPendingFloorSortEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getIsRebuildingFloorCardOrder = typeof deps.getIsRebuildingFloorCardOrder === 'function'
            ? deps.getIsRebuildingFloorCardOrder
            : function () { return false; };
        const getHasPendingFloorSort = typeof deps.getHasPendingFloorSort === 'function'
            ? deps.getHasPendingFloorSort
            : function () { return false; };
        const setHasPendingFloorSort = typeof deps.setHasPendingFloorSort === 'function'
            ? deps.setHasPendingFloorSort
            : function () {};
        const sortAdditionalWorkItems = typeof deps.sortAdditionalWorkItems === 'function'
            ? deps.sortAdditionalWorkItems
            : function () {};

        function flushPendingFloorSort() {
            if (getIsRebuildingFloorCardOrder() || !getHasPendingFloorSort()) {
                return;
            }
            setHasPendingFloorSort(false);
            sortAdditionalWorkItems();
        }

        return flushPendingFloorSort();
    };

    window.materialCalcFlushFloorSortWhenFocusLeavesEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const scopeEl = safeConfig.scopeEl || null;

        const getHasPendingFloorSort = typeof deps.getHasPendingFloorSort === 'function'
            ? deps.getHasPendingFloorSort
            : function () { return false; };
        const getLastPointerDownTarget = typeof deps.getLastPointerDownTarget === 'function'
            ? deps.getLastPointerDownTarget
            : function () { return null; };
        const getLastPointerDownAt = typeof deps.getLastPointerDownAt === 'function'
            ? deps.getLastPointerDownAt
            : function () { return 0; };
        const flushPendingFloorSort = typeof deps.flushPendingFloorSort === 'function'
            ? deps.flushPendingFloorSort
            : function () {};

        function flushFloorSortWhenFocusLeaves(scopeEl) {
            if (!(scopeEl instanceof HTMLElement) || !getHasPendingFloorSort()) {
                return;
            }
            setTimeout(() => {
                const lastPointerDownTarget = getLastPointerDownTarget();
                const lastPointerDownAt = getLastPointerDownAt();
                const recentlyPointerDownInsideScope =
                    lastPointerDownTarget instanceof HTMLElement &&
                    scopeEl.contains(lastPointerDownTarget) &&
                    Date.now() - lastPointerDownAt < 500;
                if (recentlyPointerDownInsideScope) {
                    return;
                }
                const activeEl = document.activeElement;
                if (activeEl instanceof HTMLElement && scopeEl.contains(activeEl)) {
                    return;
                }
                flushPendingFloorSort();
            }, 0);
        }

        return flushFloorSortWhenFocusLeaves(scopeEl);
    };
})();
