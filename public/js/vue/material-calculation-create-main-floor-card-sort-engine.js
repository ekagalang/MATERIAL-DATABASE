(function () {
    window.materialCalcCreateMainFloorCardSortEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const mainTaxonomyGroupCard = deps.mainTaxonomyGroupCard || null;
        const getDirectAdditionalChildRows = typeof deps.getDirectAdditionalChildRows === 'function'
            ? deps.getDirectAdditionalChildRows
            : function () { return []; };
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) { return Array.isArray(values) ? values : []; };

        function sortMainFloorCards() {
            const mainAreaHost =
                mainTaxonomyGroupCard instanceof HTMLElement
                    ? mainTaxonomyGroupCard.querySelector('[data-main-area-children]')
                    : null;
            if (!(mainAreaHost instanceof HTMLElement)) {
                return false;
            }

            const directRows = getDirectAdditionalChildRows(mainAreaHost);
            if (directRows.length <= 1) {
                return false;
            }

            const getRowKind = row =>
                normalizeBundleRowKind(getAdditionalFieldValue(row, 'row_kind') || row.dataset.rowKind || 'area');
            const floorRows = directRows.filter(row => getRowKind(row) === 'area');
            if (floorRows.length <= 1) {
                return false;
            }

            const floorValues = floorRows.map(row => getAdditionalFieldValue(row, 'work_floor'));
            const sortedFloorValues = sortFloors([...floorValues]);
            const floorPriority = new Map();
            sortedFloorValues.forEach((floor, index) => {
                if (!floorPriority.has(floor)) {
                    floorPriority.set(floor, index);
                }
            });

            const originalIndex = new Map(floorRows.map((row, index) => [row, index]));
            const sortedFloorRows = [...floorRows].sort((a, b) => {
                const floorA = getAdditionalFieldValue(a, 'work_floor');
                const floorB = getAdditionalFieldValue(b, 'work_floor');
                const priorityA = floorPriority.has(floorA) ? floorPriority.get(floorA) : Infinity;
                const priorityB = floorPriority.has(floorB) ? floorPriority.get(floorB) : Infinity;
                if (priorityA !== priorityB) {
                    return priorityA - priorityB;
                }
                return (originalIndex.get(a) ?? 0) - (originalIndex.get(b) ?? 0);
            });

            let floorIndex = 0;
            const nextRows = directRows.map(row => (getRowKind(row) === 'area' ? sortedFloorRows[floorIndex++] : row));
            const alreadySorted = directRows.every((row, index) => row === nextRows[index]);
            if (alreadySorted) {
                return false;
            }

            nextRows.forEach(row => mainAreaHost.appendChild(row));
            return true;
        }

        return sortMainFloorCards();
    };
})();
