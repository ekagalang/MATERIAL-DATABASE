(function () {
    'use strict';

    window.materialCalcCreateTaxonomyTrackerEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const normalizeTaxonomyValue = typeof deps.normalizeTaxonomyValue === 'function'
            ? deps.normalizeTaxonomyValue
            : function (value) { return String(value || '').trim().toLowerCase(); };
        const getAllAdditionalWorkRows = typeof deps.getAllAdditionalWorkRows === 'function'
            ? deps.getAllAdditionalWorkRows
            : function () { return []; };
        const normalizeBundleRowKind = typeof deps.normalizeBundleRowKind === 'function'
            ? deps.normalizeBundleRowKind
            : function (value) { return String(value || 'area').trim().toLowerCase(); };
        const getAdditionalFieldValue = typeof deps.getAdditionalFieldValue === 'function'
            ? deps.getAdditionalFieldValue
            : function () { return ''; };
        const showTaxonomyActionError = typeof deps.showTaxonomyActionError === 'function'
            ? deps.showTaxonomyActionError
            : function () {};
        const getMainTaxonomyValue = typeof deps.getMainTaxonomyValue === 'function'
            ? deps.getMainTaxonomyValue
            : function () { return ''; };
        const createAndFocusAdditionalWorkItem = typeof deps.createAndFocusAdditionalWorkItem === 'function'
            ? deps.createAndFocusAdditionalWorkItem
            : function () {};
        const findLastAdditionalRowByTaxonomy = typeof deps.findLastAdditionalRowByTaxonomy === 'function'
            ? deps.findLastAdditionalRowByTaxonomy
            : function () { return null; };
        const getMainAreaChildrenHost = typeof deps.getMainAreaChildrenHost === 'function'
            ? deps.getMainAreaChildrenHost
            : function () { return null; };
        const mainTaxonomyGroupCard = deps.mainTaxonomyGroupCard instanceof HTMLElement
            ? deps.mainTaxonomyGroupCard
            : null;

        // Tracker engine extracted from Blade for bridge-first execution.
        function initTaxonomyScrollTracker() {
            const trackerWrap = document.getElementById('calcTaxonomyScrollTracker');
            if (!(trackerWrap instanceof HTMLElement)) {
                return {
                    refresh() {},
                };
            }

            const floorValueEl = trackerWrap.querySelector('[data-scroll-active-floor]');
            const areaValueEl = trackerWrap.querySelector('[data-scroll-active-area]');
            const fieldValueEl = trackerWrap.querySelector('[data-scroll-active-field]');
            const addAreaMirrorBtn = trackerWrap.querySelector('[data-scroll-action="add-area"]');
            const addFieldMirrorBtn = trackerWrap.querySelector('[data-scroll-action="add-field"]');
            if (
                !(floorValueEl instanceof HTMLElement) ||
                !(areaValueEl instanceof HTMLElement) ||
                !(fieldValueEl instanceof HTMLElement)
            ) {
                return {
                    refresh() {},
                };
            }

            const emptyToken = '-';
            let stepItems = [];
            let animationFrameId = null;
            let hasAnyTrackerValue = false;
            let lockedStepIndex = -1;
            let lastThresholdTop = 0;
            let activeTrackerContext = { floor: '', area: '', field: '' };
            const calcFormEl = document.getElementById('calculationForm');
            const STEP_SWITCH_MARGIN_PX = 24;

            const readPxCssVar = (hostEl, propertyName, fallback = 0) => {
                if (!(hostEl instanceof HTMLElement) || !propertyName) {
                    return fallback;
                }
                const inlineValue = hostEl.style.getPropertyValue(propertyName);
                const computedValue = window.getComputedStyle(hostEl).getPropertyValue(propertyName);
                const parsed = Number.parseFloat(String(inlineValue || computedValue || '').replace('px', '').trim());
                return Number.isFinite(parsed) ? parsed : fallback;
            };

            const getStickyTopOffset = () => {
                const fallback = window.innerWidth <= 768 ? 64 : 72;
                return readPxCssVar(calcFormEl, '--calc-search-sticky-top', fallback);
            };

            const getStickySearchHeight = () => {
                return readPxCssVar(calcFormEl, '--calc-search-sticky-height', 0);
            };

            const syncMirrorActionButtonState = (mirrorBtn, sourceButtonId, contextAllowed = true) => {
                if (!(mirrorBtn instanceof HTMLButtonElement)) {
                    return;
                }
                const sourceBtn = document.getElementById(sourceButtonId);
                if (!(sourceBtn instanceof HTMLButtonElement)) {
                    mirrorBtn.hidden = true;
                    mirrorBtn.disabled = true;
                    return;
                }
                mirrorBtn.hidden = false;
                mirrorBtn.disabled = !!sourceBtn.disabled || !contextAllowed;
            };

            const findLastAreaRowByFloorContext = workFloor => {
                const targetFloor = normalizeTaxonomyValue(workFloor);
                if (!targetFloor) {
                    return null;
                }

                let floorRootMatch = null;
                let anyAreaMatch = null;
                getAllAdditionalWorkRows().forEach(row => {
                    const rowKind = normalizeBundleRowKind(
                        row.getAttribute('data-row-kind') || getAdditionalFieldValue(row, 'row_kind') || 'area',
                    );
                    if (rowKind !== 'area') {
                        return;
                    }
                    const rowFloor = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_floor'));
                    if (rowFloor !== targetFloor) {
                        return;
                    }
                    anyAreaMatch = row;
                    const rowArea = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_area'));
                    if (!rowArea) {
                        floorRootMatch = row;
                    }
                });

                return floorRootMatch || anyAreaMatch;
            };

            const findLastAreaRowByFloorAndAreaContext = (workFloor, workArea) => {
                const targetFloor = normalizeTaxonomyValue(workFloor);
                const targetArea = normalizeTaxonomyValue(workArea);
                if (!targetArea) {
                    return null;
                }

                let matched = null;
                getAllAdditionalWorkRows().forEach(row => {
                    const rowKind = normalizeBundleRowKind(
                        row.getAttribute('data-row-kind') || getAdditionalFieldValue(row, 'row_kind') || 'area',
                    );
                    if (rowKind !== 'area') {
                        return;
                    }
                    const rowFloor = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_floor'));
                    const rowArea = normalizeTaxonomyValue(getAdditionalFieldValue(row, 'work_area'));
                    if (targetFloor && rowFloor !== targetFloor) {
                        return;
                    }
                    if (rowArea !== targetArea) {
                        return;
                    }
                    matched = row;
                });
                return matched;
            };

            const addAreaFromTrackerContext = () => {
                const contextFloor = String(activeTrackerContext.floor || '').trim();
                if (!contextFloor) {
                    showTaxonomyActionError(
                        'Isi Lantai terlebih dahulu sebelum menambah Area.',
                        document.getElementById('workFloorDisplay'),
                    );
                    return;
                }

                const mainFloor = normalizeTaxonomyValue(getMainTaxonomyValue('floor'));
                const contextFloorNorm = normalizeTaxonomyValue(contextFloor);
                if (contextFloorNorm && contextFloorNorm === mainFloor) {
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: contextFloor,
                            work_area: '',
                            work_field: '',
                            work_type: '',
                            row_kind: 'area',
                        },
                        null,
                        'work_area',
                        { rowKind: 'area', targetMainArea: true },
                    );
                    return;
                }

                const targetFloorHost = findLastAreaRowByFloorContext(contextFloor);
                createAndFocusAdditionalWorkItem(
                    {
                        work_floor: contextFloor,
                        work_area: '',
                        work_field: '',
                        work_type: '',
                        row_kind: 'area',
                    },
                    null,
                    'work_area',
                    targetFloorHost instanceof HTMLElement
                        ? { rowKind: 'area', targetFloorHost }
                        : { rowKind: 'area' },
                );
            };

            const addFieldFromTrackerContext = () => {
                const contextFloor = String(activeTrackerContext.floor || '').trim();
                const contextArea = String(activeTrackerContext.area || '').trim();
                if (!contextFloor) {
                    showTaxonomyActionError(
                        'Isi Lantai terlebih dahulu sebelum menambah Bidang.',
                        document.getElementById('workFloorDisplay'),
                    );
                    return;
                }
                if (!contextArea) {
                    showTaxonomyActionError(
                        'Isi Area terlebih dahulu sebelum menambah Bidang.',
                        document.getElementById('workAreaDisplay'),
                    );
                    return;
                }

                const targetAreaRow = findLastAreaRowByFloorAndAreaContext(contextFloor, contextArea);
                if (targetAreaRow instanceof HTMLElement) {
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: contextFloor,
                            work_area: contextArea,
                            work_field: '',
                            work_type: '',
                            row_kind: 'field',
                        },
                        null,
                        'work_field',
                        { rowKind: 'field', targetAreaHost: targetAreaRow },
                    );
                    return;
                }

                const mainFloorNorm = normalizeTaxonomyValue(getMainTaxonomyValue('floor'));
                const mainAreaNorm = normalizeTaxonomyValue(getMainTaxonomyValue('area'));
                const contextFloorNorm = normalizeTaxonomyValue(contextFloor);
                const contextAreaNorm = normalizeTaxonomyValue(contextArea);
                if (contextFloorNorm && contextFloorNorm === mainFloorNorm && contextAreaNorm === mainAreaNorm) {
                    const afterTarget = findLastAdditionalRowByTaxonomy(contextFloor, contextArea, '', false);
                    const mainAreaHost = getMainAreaChildrenHost();
                    const firstMainAreaRow =
                        mainAreaHost instanceof HTMLElement
                            ? Array.from(mainAreaHost.children).find(el =>
                                  el instanceof HTMLElement && el.matches('[data-additional-work-item="true"]'),
                              ) || null
                            : null;
                    createAndFocusAdditionalWorkItem(
                        {
                            work_floor: contextFloor,
                            work_area: contextArea,
                            work_field: '',
                            work_type: '',
                            row_kind: 'field',
                        },
                        afterTarget,
                        'work_field',
                        { rowKind: 'field', beforeElement: afterTarget ? null : firstMainAreaRow, targetMainArea: true },
                    );
                    return;
                }

                showTaxonomyActionError(
                    'Area aktif tidak ditemukan. Pilih Area yang benar lalu coba tambah Bidang lagi.',
                    document.getElementById('workAreaDisplay'),
                );
            };

            if (addAreaMirrorBtn instanceof HTMLButtonElement) {
                addAreaMirrorBtn.addEventListener('click', function() {
                    addAreaFromTrackerContext();
                });
            }

            if (addFieldMirrorBtn instanceof HTMLButtonElement) {
                addFieldMirrorBtn.addEventListener('click', function() {
                    addFieldFromTrackerContext();
                });
            }

            const getAbsoluteTop = el => {
                const rect = el.getBoundingClientRect();
                const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
                return rect.top + scrollTop;
            };

            const isTrackableElement = el => {
                if (!(el instanceof HTMLElement)) {
                    return false;
                }
                if (el.hidden || el.getAttribute('aria-hidden') === 'true') {
                    return false;
                }
                if (el.getClientRects().length === 0) {
                    return false;
                }
                const style = window.getComputedStyle(el);
                return style.display !== 'none' && style.visibility !== 'hidden';
            };

            const getMainAnchorElement = () => {
                if (!(mainTaxonomyGroupCard instanceof HTMLElement)) {
                    return null;
                }
                const node = mainTaxonomyGroupCard.querySelector('.taxonomy-card-floor');
                return node instanceof HTMLElement ? node : null;
            };

            const getAdditionalRowAnchorElement = row => {
                if (!(row instanceof HTMLElement)) {
                    return null;
                }
                const cells = row.querySelectorAll('.additional-taxonomy-cell[data-taxonomy-cell]:not(.is-inherited)');
                for (const cell of cells) {
                    if (cell instanceof HTMLElement && isTrackableElement(cell)) {
                        return cell;
                    }
                }
                return row;
            };

            const buildNextContext = (previous, rawFloor, rawArea, rawField) => {
                const priorFloor = String(previous?.floor || '').trim();
                const priorArea = String(previous?.area || '').trim();
                const priorField = String(previous?.field || '').trim();
                const nextFloorRaw = String(rawFloor || '').trim();
                const nextAreaRaw = String(rawArea || '').trim();
                const nextFieldRaw = String(rawField || '').trim();

                const nextFloor = nextFloorRaw || priorFloor;
                const nextArea = nextAreaRaw || (nextFloorRaw ? '' : priorArea);
                const nextField = nextFieldRaw || (nextAreaRaw || nextFloorRaw ? '' : priorField);

                return {
                    floor: nextFloor,
                    area: nextArea,
                    field: nextField,
                };
            };

            const rebuildSteps = () => {
                stepItems = [];

                let context = buildNextContext(
                    { floor: '', area: '', field: '' },
                    getMainTaxonomyValue('floor'),
                    getMainTaxonomyValue('area'),
                    getMainTaxonomyValue('field'),
                );
                const mainAnchorEl = getMainAnchorElement();
                if ((context.floor || context.area || context.field) && mainAnchorEl instanceof HTMLElement) {
                    stepItems.push({
                        ...context,
                        el: mainAnchorEl,
                    });
                }

                getAllAdditionalWorkRows().forEach(row => {
                    const nextContext = buildNextContext(
                        context,
                        getAdditionalFieldValue(row, 'work_floor'),
                        getAdditionalFieldValue(row, 'work_area'),
                        getAdditionalFieldValue(row, 'work_field'),
                    );
                    context = nextContext;
                    const rowAnchorEl = getAdditionalRowAnchorElement(row);
                    if (!(rowAnchorEl instanceof HTMLElement)) {
                        return;
                    }
                    if (!(nextContext.floor || nextContext.area || nextContext.field)) {
                        return;
                    }
                    stepItems.push({
                        ...nextContext,
                        el: rowAnchorEl,
                    });
                });
            };

            const setTrackerValues = (floorValue, areaValue, fieldValue) => {
                floorValueEl.textContent = floorValue || emptyToken;
                areaValueEl.textContent = areaValue || emptyToken;
                fieldValueEl.textContent = fieldValue || emptyToken;
                hasAnyTrackerValue = !!(floorValue || areaValue || fieldValue);
            };

            const syncTrackerVisibility = () => {
                if (!hasAnyTrackerValue) {
                    trackerWrap.hidden = true;
                    return;
                }
                if (!(mainTaxonomyGroupCard instanceof HTMLElement)) {
                    trackerWrap.hidden = true;
                    return;
                }

                const stickyThreshold = getStickyTopOffset() + getStickySearchHeight() + 10;
                const cardRect = mainTaxonomyGroupCard.getBoundingClientRect();
                const formRect = calcFormEl instanceof HTMLElement ? calcFormEl.getBoundingClientRect() : null;
                const isWithinFormViewport = !(formRect && formRect.bottom <= stickyThreshold + 24);
                const shouldShow = isWithinFormViewport && cardRect.top <= stickyThreshold;
                trackerWrap.hidden = !shouldShow;
            };

            const computeAndRender = () => {
                const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
                const stickyTop = getStickyTopOffset();
                const stickySearchHeight = getStickySearchHeight();
                const thresholdTop = scrollTop + stickyTop + stickySearchHeight + 18;

                let activeFloor = '';
                let activeArea = '';
                let activeField = '';
                const orderedSteps = stepItems
                    .filter(step => step && step.el instanceof HTMLElement && isTrackableElement(step.el))
                    .map(step => ({
                        ...step,
                        __top: getAbsoluteTop(step.el),
                    }))
                    .sort((left, right) => left.__top - right.__top);

                if (!orderedSteps.length) {
                    lockedStepIndex = -1;
                } else {
                    if (lockedStepIndex < 0 || lockedStepIndex >= orderedSteps.length) {
                        const initialIndex = orderedSteps.findIndex(step => step.__top > thresholdTop);
                        lockedStepIndex = initialIndex <= 0 ? 0 : initialIndex - 1;
                    }

                    const scrollingDown = thresholdTop >= lastThresholdTop;
                    if (scrollingDown) {
                        while (
                            lockedStepIndex + 1 < orderedSteps.length &&
                            thresholdTop >= orderedSteps[lockedStepIndex + 1].__top + STEP_SWITCH_MARGIN_PX
                        ) {
                            lockedStepIndex += 1;
                        }
                    } else {
                        while (
                            lockedStepIndex > 0 &&
                            thresholdTop < orderedSteps[lockedStepIndex].__top - STEP_SWITCH_MARGIN_PX
                        ) {
                            lockedStepIndex -= 1;
                        }
                    }

                    if (lockedStepIndex < 0) {
                        lockedStepIndex = 0;
                    } else if (lockedStepIndex >= orderedSteps.length) {
                        lockedStepIndex = orderedSteps.length - 1;
                    }
                }

                const activeStep =
                    lockedStepIndex >= 0 && lockedStepIndex < orderedSteps.length ? orderedSteps[lockedStepIndex] : null;
                if (activeStep && typeof activeStep === 'object') {
                    activeFloor = String(activeStep.floor || '').trim();
                    activeArea = String(activeStep.area || '').trim();
                    activeField = String(activeStep.field || '').trim();
                }
                activeTrackerContext = {
                    floor: activeFloor,
                    area: activeArea,
                    field: activeField,
                };

                setTrackerValues(activeFloor, activeArea, activeField);
                syncTrackerVisibility();
                syncMirrorActionButtonState(addAreaMirrorBtn, 'addAreaFromMainBtn', !!activeFloor);
                syncMirrorActionButtonState(addFieldMirrorBtn, 'addFieldFromMainBtn', !!activeFloor && !!activeArea);
                lastThresholdTop = thresholdTop;
            };

            const scheduleRender = () => {
                if (animationFrameId) {
                    cancelAnimationFrame(animationFrameId);
                }
                animationFrameId = window.requestAnimationFrame(() => {
                    animationFrameId = null;
                    computeAndRender();
                });
            };

            const refresh = () => {
                rebuildSteps();
                computeAndRender();
            };

            const formEl = document.getElementById('calculationForm');
            if (formEl instanceof HTMLElement) {
                formEl.addEventListener('input', function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    if (
                        target.matches('#workFloorDisplay, #workAreaDisplay, #workFieldDisplay') ||
                        target.matches('[data-field-display="work_floor"], [data-field-display="work_area"], [data-field-display="work_field"]') ||
                        target.matches('[data-field="work_floor"], [data-field="work_area"], [data-field="work_field"]')
                    ) {
                        refresh();
                    }
                });
                formEl.addEventListener('change', function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    if (
                        target.matches('#workFloorDisplay, #workAreaDisplay, #workFieldDisplay') ||
                        target.matches('[data-field-display="work_floor"], [data-field-display="work_area"], [data-field-display="work_field"]') ||
                        target.matches('[data-field="work_floor"], [data-field="work_area"], [data-field="work_field"]')
                    ) {
                        refresh();
                    }
                });
            }

            window.addEventListener('scroll', scheduleRender, { passive: true });
            window.addEventListener('resize', scheduleRender);

            refresh();

            return {
                refresh,
            };
        }


        return initTaxonomyScrollTracker();
    };
})();
