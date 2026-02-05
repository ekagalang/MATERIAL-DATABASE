(function () {
    const AUTOCOMPLETE_SELECTOR = '.autocomplete-list';
    let rafId = null;

    function isVisible(el) {
        if (!el) return false;
        const style = window.getComputedStyle(el);
        if (style.display === 'none' || style.visibility === 'hidden') return false;
        return true;
    }

    function getAnchorElement(listEl) {
        const container = listEl.closest('.work-type-autocomplete');
        if (container) {
            const input = container.querySelector('.autocomplete-input');
            return input || container;
        }

        const prev = listEl.previousElementSibling;
        if (prev) {
            const input = prev.matches && prev.matches('input, .autocomplete-input')
                ? prev
                : prev.querySelector
                    ? prev.querySelector('input, .autocomplete-input')
                    : null;
            return input || prev;
        }

        return listEl.parentElement || listEl;
    }

    function parsePx(value, fallback) {
        const parsed = Number.parseFloat(String(value || '').replace('px', ''));
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function updateOne(listEl) {
        if (!isVisible(listEl)) {
            listEl.classList.remove('autocomplete-list--up');
            listEl.style.removeProperty('max-height');
            return;
        }

        const anchorEl = getAnchorElement(listEl);
        const anchorRect = anchorEl.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;

        const computed = window.getComputedStyle(listEl);
        const defaultMaxHeight = parsePx(computed.maxHeight, 240);
        const preferredHeight = Math.min(listEl.scrollHeight || defaultMaxHeight, defaultMaxHeight);

        const safeMargin = 12;
        const minListHeight = 120;
        const spaceAbove = Math.max(0, anchorRect.top - safeMargin);
        const spaceBelow = Math.max(0, viewportHeight - anchorRect.bottom - safeMargin);

        const shouldOpenUp = spaceBelow < Math.min(preferredHeight, 200) && spaceAbove > spaceBelow;
        listEl.classList.toggle('autocomplete-list--up', shouldOpenUp);

        const maxAvailable = shouldOpenUp ? spaceAbove : spaceBelow;
        if (maxAvailable > minListHeight) {
            listEl.style.maxHeight = Math.floor(maxAvailable) + 'px';
        } else {
            listEl.style.maxHeight = defaultMaxHeight + 'px';
        }
    }

    function updateAll() {
        document.querySelectorAll(AUTOCOMPLETE_SELECTOR).forEach(updateOne);
    }

    function scheduleUpdate() {
        if (rafId !== null) return;
        rafId = window.requestAnimationFrame(function () {
            rafId = null;
            updateAll();
        });
    }

    function bindGlobalEvents() {
        document.addEventListener('focusin', scheduleUpdate, true);
        document.addEventListener('input', scheduleUpdate, true);
        document.addEventListener('click', scheduleUpdate, true);
        window.addEventListener('resize', scheduleUpdate);
        window.addEventListener('scroll', scheduleUpdate, true);
    }

    function bindMutationObserver() {
        if (!window.MutationObserver || !document.body) return;

        const observer = new MutationObserver(function (mutations) {
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    const hasAutocomplete = Array.from(mutation.addedNodes || []).some(function (node) {
                        return node instanceof Element && (
                            node.matches?.(AUTOCOMPLETE_SELECTOR) ||
                            node.querySelector?.(AUTOCOMPLETE_SELECTOR)
                        );
                    });
                    if (hasAutocomplete) {
                        scheduleUpdate();
                        return;
                    }
                }

                if (mutation.type === 'attributes') {
                    const target = mutation.target;
                    if (!(target instanceof Element)) continue;
                    if (target.matches(AUTOCOMPLETE_SELECTOR) || target.querySelector?.(AUTOCOMPLETE_SELECTOR)) {
                        scheduleUpdate();
                        return;
                    }
                }
            }
        });

        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['style', 'class'],
        });
    }

    function init() {
        bindGlobalEvents();
        bindMutationObserver();
        scheduleUpdate();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
