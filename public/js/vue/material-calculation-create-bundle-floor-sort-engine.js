(function () {
    window.materialCalcCreateBundleFloorSortEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const items = Array.isArray(safeConfig.items) ? safeConfig.items : [];

        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) { return Array.isArray(values) ? values : []; };

        function sortBundleItemsByFloorStable(items) {
            const list = Array.isArray(items) ? [...items] : [];
            if (list.length <= 1) {
                return list;
            }

            const floorValues = list.map(item => String(item?.work_floor || '').trim());
            const sortedFloorValues = sortFloors([...floorValues]);
            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const originalIndex = new Map(list.map((item, index) => [item, index]));
            return list.sort((a, b) => {
                const floorA = String(a?.work_floor || '').trim();
                const floorB = String(b?.work_floor || '').trim();
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                if (priorityA !== priorityB) {
                    return priorityA - priorityB;
                }
                return (originalIndex.get(a) ?? 0) - (originalIndex.get(b) ?? 0);
            });
        }

        return sortBundleItemsByFloorStable(items);
    };
})();
