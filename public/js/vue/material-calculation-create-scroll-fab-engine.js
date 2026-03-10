(function () {
    'use strict';

    window.materialCalcCreateScrollFabEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const getMainTaxonomyValue = typeof deps.getMainTaxonomyValue === 'function'
            ? deps.getMainTaxonomyValue
            : function () { return ''; };
        const getAllAdditionalWorkRows = typeof deps.getAllAdditionalWorkRows === 'function'
            ? deps.getAllAdditionalWorkRows
            : function () { return []; };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const sortFloors = typeof deps.sortFloors === 'function'
            ? deps.sortFloors
            : function (values) { return Array.isArray(values) ? values.slice() : []; };
        const uniqueFilterTokens = typeof deps.uniqueFilterTokens === 'function'
            ? deps.uniqueFilterTokens
            : function (values) { return Array.from(new Set(Array.isArray(values) ? values : [])); };
        const sortAlphabetic = typeof deps.sortAlphabetic === 'function'
            ? deps.sortAlphabetic
            : function (values) { return Array.isArray(values) ? values.slice().sort() : []; };

        // Scroll FAB engine extracted from Blade for bridge-first execution.
        function initCalculationScrollFab() {
            const fabWrap = document.getElementById('calcScrollFabWrap');
            const fabBtn = document.getElementById('calcScrollFabBtn');
            const fabIcon = document.getElementById('calcScrollFabIcon');
            if (!(fabWrap instanceof HTMLElement) || !(fabBtn instanceof HTMLButtonElement) || !(fabIcon instanceof HTMLElement)) {
                return {
                    refresh() {},
                };
            }

            const treeHost = fabWrap.querySelector('[data-scroll-summary-tree]');

            const setFabMode = mode => {
                const normalized = mode === 'up' ? 'up' : 'down';
                fabWrap.dataset.scrollMode = normalized;
                fabIcon.classList.remove('bi-arrow-up', 'bi-arrow-down');
                fabIcon.classList.add(normalized === 'up' ? 'bi-arrow-up' : 'bi-arrow-down');
                const label = normalized === 'up' ? 'Kembali ke atas' : 'Scroll ke bawah';
                fabBtn.setAttribute('aria-label', label);
                fabBtn.setAttribute('title', label);
            };

            const navigateToTarget = targetEl => {
                if (!(targetEl instanceof HTMLElement)) {
                    return;
                }
                const scrollTarget =
                    targetEl.closest('.additional-taxonomy-cell') ||
                    targetEl.closest('.taxonomy-card-floor') ||
                    targetEl.closest('.taxonomy-card-area') ||
                    targetEl.closest('.taxonomy-card-field') ||
                    targetEl;
                const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                try {
                    scrollTarget.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'center',
                        inline: 'nearest',
                    });
                } catch (error) {
                    const rect = scrollTarget.getBoundingClientRect();
                    const absoluteTop = rect.top + (window.scrollY || document.documentElement.scrollTop || 0);
                    window.scrollTo({
                        top: Math.max(0, absoluteTop - Math.max(120, window.innerHeight * 0.25)),
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                    });
                }
            };

            const collectSummaryTree = () => {
                const combos = [];
                const pushCombo = (floor, area, field, targets = {}) => {
                    const work_floor = String(floor || '').trim();
                    const work_area = String(area || '').trim();
                    const work_field = String(field || '').trim();
                    if (!work_floor && !work_area && !work_field) {
                        return;
                    }
                    combos.push({
                        work_floor,
                        work_area,
                        work_field,
                        floorTargetEl: targets.floorTargetEl instanceof HTMLElement ? targets.floorTargetEl : null,
                        areaTargetEl: targets.areaTargetEl instanceof HTMLElement ? targets.areaTargetEl : null,
                        fieldTargetEl: targets.fieldTargetEl instanceof HTMLElement ? targets.fieldTargetEl : null,
                    });
                };

                pushCombo(getMainTaxonomyValue('floor'), getMainTaxonomyValue('area'), getMainTaxonomyValue('field'), {
                    floorTargetEl: document.getElementById('workFloorDisplay'),
                    areaTargetEl: document.getElementById('workAreaDisplay'),
                    fieldTargetEl: document.getElementById('workFieldDisplay'),
                });
                getAllAdditionalWorkRows().forEach(row => {
                    pushCombo(
                        getAdditionalFieldValue(row, 'work_floor'),
                        getAdditionalFieldValue(row, 'work_area'),
                        getAdditionalFieldValue(row, 'work_field'),
                        {
                            floorTargetEl: row.querySelector('[data-field-display="work_floor"]'),
                            areaTargetEl: row.querySelector('[data-field-display="work_area"]'),
                            fieldTargetEl: row.querySelector('[data-field-display="work_field"]'),
                        },
                    );
                });

                const normalized = combos.filter(item => item.work_floor);
                const floorNames = sortFloors(uniqueFilterTokens(normalized.map(item => item.work_floor)));

                const floorMap = new Map();
                floorNames.forEach(name => {
                    floorMap.set(name, { label: name, targetEl: null, areas: new Map() });
                });

                normalized.forEach(item => {
                    const floorNode = floorMap.get(item.work_floor);
                    if (!floorNode) {
                        return;
                    }
                    if (!(floorNode.targetEl instanceof HTMLElement) && item.floorTargetEl instanceof HTMLElement) {
                        floorNode.targetEl = item.floorTargetEl;
                    }
                    const areaLabel = item.work_area || '(Tanpa Area)';
                    if (!floorNode.areas.has(areaLabel)) {
                        floorNode.areas.set(areaLabel, {
                            label: areaLabel,
                            targetEl: item.areaTargetEl || item.floorTargetEl || null,
                            fields: new Map(),
                        });
                    }
                    const areaNode = floorNode.areas.get(areaLabel);
                    if (!(areaNode.targetEl instanceof HTMLElement)) {
                        areaNode.targetEl = item.areaTargetEl || item.floorTargetEl || null;
                    }
                    if (item.work_field) {
                        if (!areaNode.fields.has(item.work_field)) {
                            areaNode.fields.set(item.work_field, {
                                label: item.work_field,
                                targetEl: item.fieldTargetEl || item.areaTargetEl || item.floorTargetEl || null,
                            });
                        }
                    }
                });

                return floorNames.map(floorName => {
                    const floorNode = floorMap.get(floorName);
                    const areaNames = sortAlphabetic(Array.from(floorNode && floorNode.areas ? floorNode.areas.keys() : []));
                    return {
                        label: floorName,
                        targetEl: floorNode?.targetEl || null,
                        children: areaNames.map(areaName => {
                            const areaNode = floorNode.areas.get(areaName);
                            const fieldNames = sortAlphabetic(Array.from(areaNode && areaNode.fields ? areaNode.fields.keys() : []));
                            return {
                                label: areaName,
                                targetEl: areaNode?.targetEl || null,
                                children: fieldNames.map(fieldName => ({
                                    ...(areaNode.fields.get(fieldName) || { label: fieldName, targetEl: null }),
                                    children: [],
                                })),
                            };
                        }),
                    };
                });
            };

            const createMenuNode = (node, level = 0) => {
                const li = document.createElement('li');
                li.className = 'calc-scroll-fab-menu-item';
                if (Array.isArray(node.children) && node.children.length > 0) {
                    li.classList.add('has-children');
                }

                const label = document.createElement('button');
                label.type = 'button';
                label.className = 'calc-scroll-fab-menu-item-label';
                if (node.targetEl instanceof HTMLElement) {
                    label.classList.add('is-clickable');
                    label.setAttribute('title', `Buka ${String(node.label || '')}`);
                    const handleNavActivate = event => {
                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        navigateToTarget(node.targetEl);
                    };
                    label.addEventListener('pointerdown', function(event) {
                        if (event.pointerType === 'mouse' && event.button !== 0) {
                            return;
                        }
                        handleNavActivate(event);
                    });
                    label.addEventListener('click', function(event) {
                        handleNavActivate(event);
                    });
                    label.addEventListener('keydown', function(event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }
                        handleNavActivate(event);
                    });
                }
                const textEl = document.createElement('span');
                textEl.className = 'calc-scroll-fab-menu-text';
                textEl.textContent = String(node.label || '');
                label.appendChild(textEl);
                li.appendChild(label);

                if (Array.isArray(node.children) && node.children.length > 0) {
                    const ul = document.createElement('ul');
                    ul.className = level === 0 ? 'calc-scroll-fab-submenu' : 'calc-scroll-fab-submenu';
                    const submenuTitle =
                        level === 0
                            ? 'Area'
                            : level === 1
                              ? 'Bidang'
                              : '';
                    if (submenuTitle) {
                        const titleEl = document.createElement('li');
                        titleEl.className = 'calc-scroll-fab-submenu-title';
                        if (level === 0) {
                            titleEl.classList.add('is-area');
                        } else if (level === 1) {
                            titleEl.classList.add('is-field');
                        }
                        titleEl.textContent = submenuTitle;
                        ul.appendChild(titleEl);
                    }
                    node.children.forEach(child => ul.appendChild(createMenuNode(child, level + 1)));
                    li.appendChild(ul);
                }

                return li;
            };

            const renderTree = tree => {
                if (!(treeHost instanceof HTMLElement)) {
                    return;
                }
                treeHost.innerHTML = '';

                const items = Array.isArray(tree) ? tree : [];
                if (!items.length) {
                    const emptyEl = document.createElement('div');
                    emptyEl.className = 'calc-scroll-fab-menu-empty';
                    emptyEl.textContent = 'Belum ada lantai terinput';
                    treeHost.appendChild(emptyEl);
                    return;
                }

                const rootMenu = document.createElement('ul');
                rootMenu.className = 'calc-scroll-fab-menu';
                items.forEach(node => rootMenu.appendChild(createMenuNode(node, 0)));
                treeHost.appendChild(rootMenu);
            };

            const refreshSummary = () => {
                renderTree(collectSummaryTree());
            };

            const updateVisibilityAndIcon = () => {
                const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
                const docHeight = Math.max(
                    document.body.scrollHeight || 0,
                    document.documentElement.scrollHeight || 0,
                );
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const scrollable = docHeight - viewportHeight;
                const hasScroll = scrollable > 64;

                fabWrap.hidden = !hasScroll;
                if (!hasScroll) {
                    return;
                }

                const upThreshold = Math.max(120, scrollable * 0.75);
                const showUp = scrollTop >= upThreshold;
                setFabMode(showUp ? 'up' : 'down');
            };

            let refreshTimer = null;
            const scheduleRefreshSummary = () => {
                if (refreshTimer) {
                    clearTimeout(refreshTimer);
                }
                refreshTimer = setTimeout(() => {
                    refreshTimer = null;
                    refreshSummary();
                }, 100);
            };

            fabBtn.addEventListener('click', function() {
                const mode = fabWrap.dataset.scrollMode === 'up' ? 'up' : 'down';
                const targetTop =
                    mode === 'up'
                        ? 0
                        : Math.max(
                              0,
                              Math.max(document.body.scrollHeight || 0, document.documentElement.scrollHeight || 0) -
                                  (window.innerHeight || document.documentElement.clientHeight || 0),
                          );
                const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                window.scrollTo({
                    top: targetTop,
                    behavior: prefersReducedMotion ? 'auto' : 'smooth',
                });
                fabBtn.blur();
            });

            ['mouseenter', 'focusin', 'touchstart'].forEach(eventName => {
                fabWrap.addEventListener(eventName, refreshSummary, { passive: true });
            });

            const calculationForm = document.getElementById('calculationForm');
            if (calculationForm instanceof HTMLElement) {
                calculationForm.addEventListener('change', scheduleRefreshSummary);
                calculationForm.addEventListener('input', function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    if (
                        target.matches('#workFloorDisplay, #workAreaDisplay, #workFieldDisplay') ||
                        target.matches('[data-field="work_floor"], [data-field="work_area"], [data-field="work_field"]') ||
                        target.matches('[data-field-display="work_floor"], [data-field-display="work_area"], [data-field-display="work_field"]')
                    ) {
                        scheduleRefreshSummary();
                    }
                });
            }

            window.addEventListener('scroll', updateVisibilityAndIcon, { passive: true });
            window.addEventListener('resize', updateVisibilityAndIcon);

            refreshSummary();
            updateVisibilityAndIcon();

            return {
                refresh() {
                    refreshSummary();
                    updateVisibilityAndIcon();
                },
            };
        }


        return initCalculationScrollFab();
    };
})();
