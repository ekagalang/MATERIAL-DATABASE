(function () {
    'use strict';

    window.materialCalcCreateSearchEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const getMaterialCalcVueBridge = typeof safeConfig.getBridge === 'function'
            ? safeConfig.getBridge
            : function () { return window.materialCalcVueBridge || null; };

        // Search engine extracted from Blade for bridge-first execution.
        function initCalculationPageSearch() {
            const scopeEl = document.getElementById('calcCreateSearchScope');
            const searchInput = document.getElementById('calcPageSearchInput');
            const clearBtn = document.getElementById('calcPageSearchClear');
            const countEl = document.getElementById('calcPageSearchCount');
            const prevBtn = document.getElementById('calcPageSearchPrev');
            const nextBtn = document.getElementById('calcPageSearchNext');
            const searchWrapEl = document.getElementById('calcInlineSearch');
            const headerRowEl = searchWrapEl instanceof HTMLElement ? searchWrapEl.closest('.calc-header-row') : null;

            if (
                !(scopeEl instanceof HTMLElement) ||
                !(searchInput instanceof HTMLInputElement) ||
                !(clearBtn instanceof HTMLButtonElement) ||
                !(countEl instanceof HTMLElement) ||
                !(prevBtn instanceof HTMLButtonElement) ||
                !(nextBtn instanceof HTMLButtonElement)
            ) {
                return {
                    refresh() {},
                };
            }

            let results = [];
            let activeIndex = -1;
            let refreshTimer = null;
            let mutationObserver = null;
            let isObserverConnected = false;
            let isApplyingSearchDecorations = false;
            let textHighlightMap = new Map();
            let stickyOffsetRaf = null;
            let stickyPinRaf = null;
            let isSearchPinned = false;
            let searchNaturalWidth = 0;
            let searchObjectIdSeq = 1;
            const searchObjectIds = new WeakMap();
            let useVueBridgeForSearchControls = false;

            const normalizeText = value => String(value || '').toLowerCase().trim();
            const prefersReducedMotion = () =>
                !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
            const getSearchObjectId = value => {
                if (!value || (typeof value !== 'object' && typeof value !== 'function')) {
                    return 'na';
                }
                if (!searchObjectIds.has(value)) {
                    searchObjectIds.set(value, searchObjectIdSeq++);
                }
                return searchObjectIds.get(value);
            };
            const isSearchExcludedElement = el => {
                if (!(el instanceof Element)) return false;
                return !!el.closest(
                    '.calc-inline-search, .calc-scroll-fab, .calc-search-mark, script, style, noscript, template, #projectLocationMap, .project-location-map, .gm-style, .gm-style-cc, .leaflet-container, .leaflet-pane, .leaflet-control-container',
                );
            };

            const isElementVisible = el => {
                if (!(el instanceof HTMLElement)) return false;
                if (el.hidden) return false;
                const style = window.getComputedStyle(el);
                if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
                return el.getClientRects().length > 0;
            };

            const refreshStickySearchTopOffset = () => {
                let maxBottom = 0;
                const headerCandidates = document.querySelectorAll(
                    'body > header, body > nav, #globalTopbar, .global-topbar, .navbar, .topbar, .top-bar, .main-header, .app-header',
                );
                headerCandidates.forEach(el => {
                    if (!(el instanceof HTMLElement)) return;
                    if (!isElementVisible(el)) return;
                    const style = window.getComputedStyle(el);
                    if (style.position !== 'fixed' && style.position !== 'sticky') return;
                    const rect = el.getBoundingClientRect();
                    if (rect.bottom <= 0) return;
                    if (rect.top > Math.max(20, window.innerHeight * 0.05)) return;
                    maxBottom = Math.max(maxBottom, rect.bottom);
                });
                scopeEl.style.setProperty('--calc-search-sticky-top', `${Math.max(0, Math.ceil(maxBottom))}px`);
            };

            const scheduleStickySearchTopOffsetRefresh = () => {
                if (stickyOffsetRaf !== null) {
                    cancelAnimationFrame(stickyOffsetRaf);
                }
                stickyOffsetRaf = requestAnimationFrame(() => {
                    stickyOffsetRaf = null;
                    refreshStickySearchTopOffset();
                });
            };

            const getStickySearchTopPx = () => {
                refreshStickySearchTopOffset();
                const raw = scopeEl.style.getPropertyValue('--calc-search-sticky-top')
                    || getComputedStyle(scopeEl).getPropertyValue('--calc-search-sticky-top');
                const base = Number.parseFloat(String(raw || '').replace('px', '')) || 0;
                return base;
            };

            const syncStickySearchPinState = () => {
                if (!(searchWrapEl instanceof HTMLElement) || !(headerRowEl instanceof HTMLElement)) {
                    return;
                }

                const headerRect = headerRowEl.getBoundingClientRect();
                const stickyTop = getStickySearchTopPx();
                const scopeRect = scopeEl.getBoundingClientRect();
                const searchCurrentHeight = Math.max(searchWrapEl.getBoundingClientRect().height || 0, 44);
                const shouldPin =
                    headerRect.top <= stickyTop &&
                    scopeRect.bottom > stickyTop + searchCurrentHeight + 8;

                if (!isSearchPinned) {
                    const rect = searchWrapEl.getBoundingClientRect();
                    if (rect.width > 0) {
                        searchNaturalWidth = rect.width;
                    }
                }

                if (!shouldPin) {
                    if (isSearchPinned) {
                        searchWrapEl.classList.remove('is-sticky-fixed');
                        searchWrapEl.style.removeProperty('--calc-inline-search-fixed-left');
                        searchWrapEl.style.removeProperty('--calc-inline-search-fixed-width');
                        isSearchPinned = false;
                    }
                    scopeEl.style.setProperty('--calc-search-sticky-height', '0px');
                    return;
                }

                const isMobile = window.innerWidth <= 768;
                const pinLeftAligned = headerRowEl.classList.contains('calc-left-search-row');
                const rowLeft = Math.max(6, Math.round(headerRect.left));
                const rowRight = Math.min(window.innerWidth - 6, Math.round(headerRect.right));
                const availableWidth = Math.max(220, rowRight - rowLeft);

                let width;
                let left;
                if (isMobile) {
                    width = availableWidth;
                    left = rowLeft;
                } else {
                    width = Math.min(Math.max(280, Math.round(searchNaturalWidth || 520)), availableWidth);
                    left = pinLeftAligned ? rowLeft : Math.max(rowLeft, rowRight - width);
                }

                searchWrapEl.style.setProperty('--calc-inline-search-fixed-left', `${Math.round(left)}px`);
                searchWrapEl.style.setProperty('--calc-inline-search-fixed-width', `${Math.round(width)}px`);
                searchWrapEl.classList.add('is-sticky-fixed');
                isSearchPinned = true;
                const pinnedHeight = Math.max(0, Math.round(searchWrapEl.getBoundingClientRect().height || 0));
                scopeEl.style.setProperty('--calc-search-sticky-height', `${pinnedHeight}px`);
            };

            const scheduleStickySearchPinStateRefresh = () => {
                if (stickyPinRaf !== null) {
                    cancelAnimationFrame(stickyPinRaf);
                }
                stickyPinRaf = requestAnimationFrame(() => {
                    stickyPinRaf = null;
                    syncStickySearchPinState();
                });
            };

            const getSearchTargetFromNode = node => {
                if (!(node instanceof Node)) return null;
                const baseEl = node instanceof HTMLElement ? node : node.parentElement;
                if (!(baseEl instanceof HTMLElement)) return null;
                if (isSearchExcludedElement(baseEl)) return null;
                const target =
                    baseEl.closest(
                        '.additional-taxonomy-cell, .taxonomy-card-floor, .taxonomy-card-area, .taxonomy-card-field, .work-type-group, .dimension-item, .material-type-filter-item, .form-group, .additional-work-item, .tickbox-item, .ssm-row, .alert, .project-location-group, .work-item-bottom-bar',
                    ) || baseEl;
                return target instanceof HTMLElement ? target : null;
            };

            const buildCandidates = () => {
                const candidates = [];

                const pushCandidate = (text, targetEl, sourceEl, kind = 'text') => {
                    const raw = String(text || '').replace(/\s+/g, ' ').trim();
                    if (!raw) return;
                    if (!(targetEl instanceof HTMLElement) || !isElementVisible(targetEl)) return;
                    candidates.push({
                        kind,
                        text: raw,
                        norm: normalizeText(raw),
                        targetEl,
                        sourceEl: sourceEl instanceof HTMLElement ? sourceEl : targetEl,
                    });
                };

                const walker = document.createTreeWalker(scopeEl, NodeFilter.SHOW_TEXT, {
                    acceptNode(textNode) {
                        const text = String(textNode.nodeValue || '').replace(/\s+/g, ' ').trim();
                        if (!text) return NodeFilter.FILTER_REJECT;
                        const parent = textNode.parentElement;
                        if (!(parent instanceof HTMLElement)) return NodeFilter.FILTER_REJECT;
                        if (isSearchExcludedElement(parent)) {
                            return NodeFilter.FILTER_REJECT;
                        }
                        return NodeFilter.FILTER_ACCEPT;
                    },
                });

                let currentTextNode = walker.nextNode();
                while (currentTextNode) {
                    const targetEl = getSearchTargetFromNode(currentTextNode);
                    pushCandidate(currentTextNode.nodeValue, targetEl, currentTextNode.parentElement, 'text');
                    currentTextNode = walker.nextNode();
                }

                scopeEl.querySelectorAll('input, textarea, select').forEach(field => {
                    if (!(field instanceof HTMLElement)) return;
                    if (isSearchExcludedElement(field)) return;
                    if (field instanceof HTMLInputElement && field.type === 'hidden') return;
                    if (!isElementVisible(field)) return;

                    const targetEl = getSearchTargetFromNode(field);
                    if (!(targetEl instanceof HTMLElement)) return;

                    let valueText = '';
                    if (field instanceof HTMLSelectElement) {
                        const selected = field.selectedOptions && field.selectedOptions[0];
                        valueText = String(selected?.textContent || field.value || '').trim();
                    } else if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
                        valueText = String(field.value || '').trim();
                    }

                    if (valueText) {
                        pushCandidate(valueText, targetEl, field, 'field');
                    }
                });

                return candidates;
            };

            const clearActiveSearchState = () => {
                scopeEl.querySelectorAll('.calc-search-mark.is-active, .calc-search-hit-field.is-active').forEach(el => {
                    el.classList.remove('is-active');
                });
                scopeEl.querySelectorAll('.calc-search-hit-field').forEach(el => {
                    el.classList.remove('calc-search-hit-field');
                });
            };

            const removeHitClasses = () => {
                clearActiveSearchState();
                textHighlightMap = new Map();
                isApplyingSearchDecorations = true;
                try {
                    scopeEl.querySelectorAll('mark.calc-search-mark').forEach(mark => {
                        const parent = mark.parentNode;
                        if (!parent) {
                            return;
                        }
                        parent.replaceChild(document.createTextNode(mark.textContent || ''), mark);
                        if (parent instanceof HTMLElement || parent instanceof DocumentFragment) {
                            try {
                                parent.normalize();
                            } catch (error) {
                                // ignore normalize issues in unsupported nodes
                            }
                        }
                    });
                } finally {
                    isApplyingSearchDecorations = false;
                }
            };

            const getResultIdentityKey = result => {
                if (!result || typeof result !== 'object') {
                    return '';
                }
                const sourceId = getSearchObjectId(result.sourceEl || null);
                const targetId = getSearchObjectId(result.targetEl || null);
                const markIndexInSource = Number.isInteger(result.markIndexInSource) ? result.markIndexInSource : -1;
                return `${result.kind || 'unk'}::${sourceId}::${targetId}::${markIndexInSource}`;
            };

            const applyTextHighlights = query => {
                textHighlightMap = new Map();
                if (!query) {
                    return;
                }

                const textNodes = [];
                const walker = document.createTreeWalker(scopeEl, NodeFilter.SHOW_TEXT, {
                    acceptNode(textNode) {
                        const parent = textNode.parentElement;
                        if (!(parent instanceof HTMLElement)) return NodeFilter.FILTER_REJECT;
                        if (isSearchExcludedElement(parent)) return NodeFilter.FILTER_REJECT;
                        const value = String(textNode.nodeValue || '');
                        if (!value.trim()) return NodeFilter.FILTER_REJECT;
                        return NodeFilter.FILTER_ACCEPT;
                    },
                });

                let currentTextNode = walker.nextNode();
                while (currentTextNode) {
                    textNodes.push(currentTextNode);
                    currentTextNode = walker.nextNode();
                }

                isApplyingSearchDecorations = true;
                try {
                    textNodes.forEach(textNode => {
                        if (!(textNode instanceof Text)) return;
                        const parentEl = textNode.parentElement;
                        const parentNode = textNode.parentNode;
                        if (!(parentEl instanceof HTMLElement) || !(parentNode instanceof Node)) return;
                        if (isSearchExcludedElement(parentEl)) return;

                        const rawText = String(textNode.nodeValue || '');
                        const lowerText = rawText.toLowerCase();
                        if (!lowerText || !lowerText.includes(query)) return;

                        const targetEl = getSearchTargetFromNode(textNode);
                        if (!(targetEl instanceof HTMLElement) || !isElementVisible(targetEl)) return;

                        const fragment = document.createDocumentFragment();
                        const marks = [];
                        let cursor = 0;
                        let matchIndex = lowerText.indexOf(query, cursor);

                        while (matchIndex !== -1) {
                            if (matchIndex > cursor) {
                                fragment.appendChild(document.createTextNode(rawText.slice(cursor, matchIndex)));
                            }
                            const mark = document.createElement('mark');
                            mark.className = 'calc-search-mark';
                            mark.textContent = rawText.slice(matchIndex, matchIndex + query.length);
                            fragment.appendChild(mark);
                            marks.push(mark);
                            cursor = matchIndex + query.length;
                            matchIndex = lowerText.indexOf(query, cursor);
                        }

                        if (!marks.length) return;

                        if (cursor < rawText.length) {
                            fragment.appendChild(document.createTextNode(rawText.slice(cursor)));
                        }

                        parentNode.replaceChild(fragment, textNode);

                        if (!textHighlightMap.has(parentEl)) {
                            textHighlightMap.set(parentEl, []);
                        }
                        textHighlightMap.get(parentEl).push(...marks);
                    });
                } finally {
                    isApplyingSearchDecorations = false;
                }
            };

            const updateCounter = () => {
                const total = results.length;
                const current = total > 0 && activeIndex >= 0 ? activeIndex + 1 : 0;
                const bridge = getMaterialCalcVueBridge();
                if (
                    useVueBridgeForSearchControls &&
                    bridge &&
                    bridge.search &&
                    typeof bridge.search.setQuery === 'function' &&
                    typeof bridge.search.setCounter === 'function'
                ) {
                    bridge.search.setQuery(searchInput.value, {
                        emitEvent: false,
                        source: 'native-sync',
                    });
                    bridge.search.setCounter({
                        current: current,
                        total: total,
                    });
                    return;
                }
                countEl.textContent = `${current} / ${total}`;
                prevBtn.disabled = total === 0;
                nextBtn.disabled = total === 0;
                clearBtn.style.visibility = searchInput.value.trim() ? 'visible' : 'hidden';
            };

            const setMutationObserverEnabled = shouldEnable => {
                if (!mutationObserver) {
                    mutationObserver = new MutationObserver(function(mutationList) {
                        if (!searchInput.value.trim()) return;
                        if (isApplyingSearchDecorations) return;
                        const hasRelevantMutation = mutationList.some(mutation => {
                            const target = mutation.target;
                            if (!(target instanceof Node)) return false;
                            const el = target instanceof Element ? target : target.parentElement;
                            if (!(el instanceof Element)) return false;
                            return !isSearchExcludedElement(el);
                        });
                        if (!hasRelevantMutation) return;
                        scheduleRefresh({ scroll: false });
                    });
                }

                if (shouldEnable && !isObserverConnected) {
                    mutationObserver.observe(scopeEl, {
                        childList: true,
                        subtree: true,
                    });
                    isObserverConnected = true;
                    return;
                }

                if (!shouldEnable && isObserverConnected) {
                    mutationObserver.disconnect();
                    isObserverConnected = false;
                }
            };

            const navigateToResult = (index, options = {}) => {
                if (!results.length) {
                    activeIndex = -1;
                    clearActiveSearchState();
                    updateCounter();
                    return;
                }
                const total = results.length;
                const safeIndex = ((index % total) + total) % total;
                activeIndex = safeIndex;
                clearActiveSearchState();
                const result = results[safeIndex];
                const targetEl = result?.targetEl;
                if (targetEl instanceof HTMLElement) {
                    let activeHighlightEl = null;
                    if (result.kind === 'text' && result.sourceEl instanceof HTMLElement) {
                        const marks = textHighlightMap.get(result.sourceEl) || [];
                        if (marks.length) {
                            const desiredMarkIndex = Number.isInteger(result.markIndexInSource) ? result.markIndexInSource : 0;
                            activeHighlightEl = marks[desiredMarkIndex] || marks[0];
                            activeHighlightEl.classList.add('is-active');
                        }
                    }
                    if (!(activeHighlightEl instanceof HTMLElement)) {
                        const fieldSourceEl =
                            result.kind === 'field' && result.sourceEl instanceof HTMLElement && isElementVisible(result.sourceEl)
                                ? result.sourceEl
                                : null;
                        const fieldHighlightEl =
                            fieldSourceEl?.closest(
                                '.input-wrapper, .material-type-filter-body, .work-type-selector-wrapper, .taxonomy-card-floor, .taxonomy-card-area, .taxonomy-card-field',
                            ) || fieldSourceEl || targetEl;

                        fieldHighlightEl.classList.add('calc-search-hit-field', 'is-active');
                        activeHighlightEl = fieldHighlightEl;
                    }
                    if (options.scroll !== false) {
                        const scrollTarget =
                            activeHighlightEl.closest('.additional-taxonomy-cell') ||
                            activeHighlightEl.closest('.taxonomy-card-floor') ||
                            activeHighlightEl.closest('.taxonomy-card-area') ||
                            activeHighlightEl.closest('.taxonomy-card-field') ||
                            activeHighlightEl;
                        try {
                            scrollTarget.scrollIntoView({
                                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                                block: 'center',
                                inline: 'nearest',
                            });
                        } catch (error) {
                            const rect = scrollTarget.getBoundingClientRect();
                            const absoluteTop = rect.top + (window.scrollY || document.documentElement.scrollTop || 0);
                            window.scrollTo({
                                top: Math.max(0, absoluteTop - Math.max(120, window.innerHeight * 0.24)),
                                behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                            });
                        }
                    }
                    if (
                        result.kind === 'field' &&
                        result.sourceEl instanceof HTMLElement &&
                        isElementVisible(result.sourceEl)
                    ) {
                        result.sourceEl.classList.add('calc-search-hit-field', 'is-active');
                    }
                }
                updateCounter();
            };

            const runSearch = options => {
                const query = normalizeText(searchInput.value);
                const prevActiveTarget = activeIndex >= 0 ? results[activeIndex]?.targetEl : null;
                const prevActiveResultKey = activeIndex >= 0 ? getResultIdentityKey(results[activeIndex]) : '';
                removeHitClasses();

                if (!query) {
                    setMutationObserverEnabled(false);
                    results = [];
                    activeIndex = -1;
                    removeHitClasses();
                    updateCounter();
                    return;
                }
                setMutationObserverEnabled(true);

                const candidates = buildCandidates();
                applyTextHighlights(query);
                const expandedResults = [];
                const markCursorBySource = new Map();
                candidates.forEach(item => {
                    if (!item || !item.norm.includes(query)) {
                        return;
                    }
                    if (item.kind !== 'text') {
                        expandedResults.push(item);
                        return;
                    }

                    const sourceEl = item.sourceEl instanceof HTMLElement ? item.sourceEl : item.targetEl;
                    const currentCursor = sourceEl instanceof HTMLElement ? (markCursorBySource.get(sourceEl) || 0) : 0;
                    let localMatchCount = 0;
                    let fromIndex = 0;
                    while (fromIndex <= item.norm.length) {
                        const foundAt = item.norm.indexOf(query, fromIndex);
                        if (foundAt === -1) {
                            break;
                        }
                        expandedResults.push({
                            ...item,
                            markIndexInSource: currentCursor + localMatchCount,
                        });
                        localMatchCount += 1;
                        fromIndex = foundAt + Math.max(1, query.length);
                    }

                    if (sourceEl instanceof HTMLElement) {
                        markCursorBySource.set(sourceEl, currentCursor + localMatchCount);
                    }
                });
                results = expandedResults;

                if (!results.length) {
                    activeIndex = -1;
                    clearActiveSearchState();
                    updateCounter();
                    return;
                }

                const matchedPrevIndexByKey = prevActiveResultKey
                    ? results.findIndex(item => getResultIdentityKey(item) === prevActiveResultKey)
                    : -1;
                const matchedPrevIndex =
                    matchedPrevIndexByKey >= 0
                        ? matchedPrevIndexByKey
                        : prevActiveTarget instanceof HTMLElement
                            ? results.findIndex(item => item.targetEl === prevActiveTarget)
                            : -1;
                const nextIndex = matchedPrevIndex >= 0 ? matchedPrevIndex : 0;
                navigateToResult(nextIndex, options || {});
            };

            const scheduleRefresh = (options = {}) => {
                if (refreshTimer) {
                    clearTimeout(refreshTimer);
                }
                refreshTimer = setTimeout(() => {
                    refreshTimer = null;
                    runSearch(options);
                }, 90);
            };

            const connectSearchControlsToVueBridge = () => {
                const bridge = getMaterialCalcVueBridge();
                if (
                    !bridge ||
                    !bridge.search ||
                    typeof bridge.search.connectControls !== 'function'
                ) {
                    return false;
                }
                const connected = bridge.search.connectControls({
                    searchInput,
                    clearBtn,
                    prevBtn,
                    nextBtn,
                    countEl,
                });
                if (!connected) {
                    return false;
                }
                if (typeof bridge.search.setQuery === 'function') {
                    bridge.search.setQuery(searchInput.value, {
                        emitEvent: false,
                        source: 'native-init',
                    });
                }
                if (typeof bridge.search.setCounter === 'function') {
                    const total = results.length;
                    const current = total > 0 && activeIndex >= 0 ? activeIndex + 1 : 0;
                    bridge.search.setCounter({ current, total });
                }
                let driverRegistered = true;
                if (typeof bridge.search.registerDriver === 'function') {
                    driverRegistered = bridge.search.registerDriver({
                        onInput() {
                            scheduleRefresh({ scroll: false });
                        },
                        onEnter(payload) {
                            const detail = payload && typeof payload === 'object' ? payload : {};
                            if (!results.length) {
                                runSearch({ scroll: true });
                                return;
                            }
                            navigateToResult(activeIndex + (detail.shiftKey ? -1 : 1), { scroll: true });
                        },
                        onEscape() {
                            searchInput.value = '';
                            runSearch({ scroll: false });
                        },
                        onClear() {
                            onSearchClearClick();
                        },
                        onPrev() {
                            onSearchPrevClick();
                        },
                        onNext() {
                            onSearchNextClick();
                        },
                    });
                }
                if (!driverRegistered) {
                    return false;
                }
                useVueBridgeForSearchControls = true;
                return true;
            };

            const onSearchInput = function() {
                scheduleRefresh({ scroll: false });
            };

            const onSearchKeydown = function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (!results.length) {
                        runSearch({ scroll: true });
                        return;
                    }
                    navigateToResult(activeIndex + (event.shiftKey ? -1 : 1), { scroll: true });
                } else if (event.key === 'Escape') {
                    searchInput.value = '';
                    runSearch({ scroll: false });
                }
            };

            const onSearchClearClick = function() {
                searchInput.value = '';
                searchInput.focus();
                runSearch({ scroll: false });
            };

            const onSearchPrevClick = function() {
                navigateToResult(activeIndex - 1, { scroll: true });
            };

            const onSearchNextClick = function() {
                navigateToResult(activeIndex + 1, { scroll: true });
            };

            searchInput.addEventListener('input', function(event) {
                if (useVueBridgeForSearchControls) {
                    return;
                }
                onSearchInput(event);
            });

            searchInput.addEventListener('keydown', function(event) {
                if (useVueBridgeForSearchControls) {
                    return;
                }
                onSearchKeydown(event);
            });

            clearBtn.addEventListener('click', function(event) {
                if (useVueBridgeForSearchControls) {
                    return;
                }
                onSearchClearClick(event);
            });

            prevBtn.addEventListener('click', function(event) {
                if (useVueBridgeForSearchControls) {
                    return;
                }
                onSearchPrevClick(event);
            });

            nextBtn.addEventListener('click', function(event) {
                if (useVueBridgeForSearchControls) {
                    return;
                }
                onSearchNextClick(event);
            });

            window.addEventListener('material-calc-vue-bridge:ready', function() {
                if (connectSearchControlsToVueBridge()) {
                    updateCounter();
                }
            });

            scopeEl.addEventListener('input', function() {
                if (!searchInput.value.trim()) return;
                scheduleRefresh({ scroll: false });
            });

            scopeEl.addEventListener('change', function() {
                if (!searchInput.value.trim()) return;
                scheduleRefresh({ scroll: false });
            });

            scheduleStickySearchTopOffsetRefresh();
            scheduleStickySearchPinStateRefresh();
            window.addEventListener('resize', function() {
                scheduleStickySearchTopOffsetRefresh();
                scheduleStickySearchPinStateRefresh();
            }, { passive: true });
            window.addEventListener('scroll', function() {
                scheduleStickySearchTopOffsetRefresh();
                scheduleStickySearchPinStateRefresh();
            }, { passive: true });

            connectSearchControlsToVueBridge();
            runSearch({ scroll: false });

            return {
                refresh() {
                    if (!searchInput.value.trim()) return;
                    scheduleRefresh({ scroll: false });
                },
            };
        }


        return initCalculationPageSearch();
    };
})();
