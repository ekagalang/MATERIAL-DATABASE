(function () {
    window.materialCalcCreateInlineLayoutEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};
        const methodName = String(safeConfig.methodName || '').trim();
        const args = Array.isArray(safeConfig.args) ? safeConfig.args : [];

        const getDirectChildMatching = typeof deps.getDirectChildMatching === 'function'
            ? deps.getDirectChildMatching
            : function () { return null; };
        const clearInlineStyles = typeof deps.clearInlineStyles === 'function'
            ? deps.clearInlineStyles
            : function () {};
        const setInlineStylesImportant = typeof deps.setInlineStylesImportant === 'function'
            ? deps.setInlineStylesImportant
            : function () {};

        function getDirectAdditionalRowHost(rowEl, hostSelector) {
            if (!(rowEl instanceof HTMLElement)) {
                return null;
            }
            const grid = getDirectChildMatching(rowEl, '.additional-work-item-grid');
            if (!(grid instanceof HTMLElement)) {
                return null;
            }
            return getDirectChildMatching(grid, hostSelector);
        }

        function getAdditionalRowLayoutParts(itemEl) {
            const grid = getDirectChildMatching(itemEl, '.additional-work-item-grid');
            const floorNode = getDirectChildMatching(grid, '.taxonomy-node-floor');
            const floorCard = getDirectChildMatching(floorNode, '.additional-work-floor-group');
            const floorChildren = getDirectChildMatching(floorNode, '.taxonomy-node-children');
            const areaNode = getDirectChildMatching(floorChildren, '.taxonomy-node-area');
            const areaCard = getDirectChildMatching(areaNode, '.additional-work-area-group');
            const areaChildren = getDirectChildMatching(areaNode, '.taxonomy-node-children');
            const fieldNode = getDirectChildMatching(areaChildren, '.taxonomy-node-field');
            const fieldCard = getDirectChildMatching(fieldNode, '.additional-work-field-group');
            const fieldChildren = getDirectChildMatching(fieldNode, '.taxonomy-node-children');
            const itemNode = getDirectChildMatching(fieldChildren, '.taxonomy-node-item');
            const itemGroup = getDirectChildMatching(itemNode, '.additional-worktype-group');
            const itemInputWrapper = getDirectChildMatching(itemGroup, '.input-wrapper');
            const topFloorChildren = getDirectChildMatching(grid, '[data-floor-children]');
            const topAreaChildren = getDirectChildMatching(grid, '[data-area-children]');
            return {
                grid,
                floorNode,
                floorCard,
                floorChildren,
                areaNode,
                areaCard,
                areaChildren,
                fieldNode,
                fieldCard,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            };
        }

        function applyAdditionalInlineTaxonomyRowLayout(itemEl, mode = 'none') {
            const parts = getAdditionalRowLayoutParts(itemEl);
            if (!parts.grid) {
                return;
            }

            const {
                grid,
                floorNode,
                floorCard,
                floorChildren,
                areaNode,
                areaCard,
                areaChildren,
                fieldNode,
                fieldCard,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            } = parts;

            const resetElements = [
                grid,
                floorNode,
                floorChildren,
                areaNode,
                areaChildren,
                fieldNode,
                fieldChildren,
                itemNode,
                itemGroup,
                itemInputWrapper,
                topFloorChildren,
                topAreaChildren,
            ];
            resetElements.forEach(el =>
                clearInlineStyles(el, [
                    'display',
                    'flex-direction',
                    'grid-template-columns',
                    'align-items',
                    'gap',
                    'grid-column',
                    'width',
                    'min-width',
                    'margin-left',
                    'padding-left',
                    'border-left',
                    'max-width',
                ]),
            );

            const cardElements = [floorCard, areaCard, fieldCard];
            cardElements.forEach(card => {
                clearInlineStyles(card, [
                    'display',
                    'grid-template-columns',
                    'align-items',
                    'gap',
                    'grid-column',
                    'width',
                    'min-width',
                    'margin-bottom',
                    'visibility',
                    'pointer-events',
                ]);
                const label = getDirectChildMatching(card, 'label');
                const body = getDirectChildMatching(card, '.material-type-filter-body');
                const actions = getDirectChildMatching(card, '.taxonomy-level-actions');
                clearInlineStyles(label, ['grid-column', 'width', 'margin-bottom']);
                clearInlineStyles(body, ['grid-column', 'width', 'min-width']);
                clearInlineStyles(actions, ['grid-column', 'margin', 'align-self']);
            });

            if (mode === 'none') {
                // Force normal stacked layout (important) so nested item rows do not inherit inline grid effects.
                setInlineStylesImportant(grid, {
                    display: 'flex',
                    'flex-direction': 'column',
                    gap: '0',
                    width: '100%',
                });
                [floorNode, floorChildren, areaNode, areaChildren, fieldNode, fieldChildren, topFloorChildren, topAreaChildren].forEach(
                    el => setInlineStylesImportant(el, { display: 'block', width: '100%', 'min-width': '0' }),
                );
                return;
            }

            // Keep item rows and nested children full-width unless this row itself is inline taxonomy row.
            setInlineStylesImportant(grid, {
                display: 'flex',
                'flex-direction': 'column',
                gap: '0',
                width: '100%',
            });

            setInlineStylesImportant(floorNode, {
                display: 'grid',
                'grid-template-columns': 'repeat(3, minmax(0, 1fr))',
                'align-items': 'start',
                gap: '8px 10px',
                width: '100%',
            });

            [floorChildren, areaNode, areaChildren, fieldNode].forEach(el => {
                setInlineStylesImportant(el, { display: 'contents' });
            });

            setInlineStylesImportant(floorCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '1',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });
            setInlineStylesImportant(areaCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '2',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });
            setInlineStylesImportant(fieldCard, {
                display: 'grid',
                'grid-template-columns': 'minmax(0, 1fr) auto',
                'align-items': 'center',
                gap: '6px 8px',
                'grid-column': '3',
                width: '100%',
                'min-width': '0',
                'margin-bottom': '0',
            });

            [floorCard, areaCard, fieldCard].forEach(card => {
                const label = getDirectChildMatching(card, 'label');
                const body = getDirectChildMatching(card, '.material-type-filter-body');
                const actions = getDirectChildMatching(card, '.taxonomy-level-actions');
                setInlineStylesImportant(label, {
                    'grid-column': '1 / -1',
                    width: 'auto',
                    'margin-bottom': '0',
                });
                setInlineStylesImportant(body, {
                    'grid-column': '1',
                    width: '100%',
                    'min-width': '0',
                });
                setInlineStylesImportant(actions, {
                    'grid-column': '2',
                    margin: '0',
                    'align-self': 'end',
                });
            });

            [fieldChildren, topFloorChildren, topAreaChildren].forEach(el => {
                setInlineStylesImportant(el, {
                    display: 'block',
                    'grid-column': '1 / -1',
                    width: '100%',
                    'min-width': '0',
                    'margin-left': '0',
                    'padding-left': '0',
                    'border-left': '0',
                });
            });

            // Prevent the built-in item slot from being auto-placed into a grid column in inline rows.
            setInlineStylesImportant(itemNode, {
                display: 'block',
                'grid-column': '1 / -1',
                width: '100%',
                'min-width': '0',
            });
            setInlineStylesImportant(itemGroup, {
                display: 'flex',
                'align-items': 'center',
                width: '100%',
                'min-width': '0',
                'max-width': '100%',
            });
            setInlineStylesImportant(itemInputWrapper, {
                display: 'block',
                width: '100%',
                'min-width': '0',
                'max-width': '100%',
            });

            const floorIsPlaceholder = floorCard instanceof HTMLElement && floorCard.classList.contains('is-inline-placeholder');
            const areaIsPlaceholder = areaCard instanceof HTMLElement && areaCard.classList.contains('is-inline-placeholder');
            if (floorIsPlaceholder) {
                setInlineStylesImportant(floorCard, { visibility: 'hidden', 'pointer-events': 'none' });
            }
            if (areaIsPlaceholder) {
                setInlineStylesImportant(areaCard, { visibility: 'hidden', 'pointer-events': 'none' });
            }
        }

        if (methodName === 'getDirectAdditionalRowHost') {
            return getDirectAdditionalRowHost(args[0], args[1]);
        }
        if (methodName === 'getAdditionalRowLayoutParts') {
            return getAdditionalRowLayoutParts(args[0]);
        }
        if (methodName === 'applyAdditionalInlineTaxonomyRowLayout') {
            return applyAdditionalInlineTaxonomyRowLayout(args[0], args[1]);
        }
    };
})();
