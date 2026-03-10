(function () {
    'use strict';

    function normalizeFieldName(target) {
        if (!(target instanceof HTMLElement)) {
            return '';
        }

        const explicitName = target.getAttribute('name');
        if (explicitName) {
            return explicitName;
        }

        const explicitId = target.getAttribute('id');
        if (explicitId) {
            return explicitId;
        }

        return target.tagName.toLowerCase();
    }

    function initMaterialCalculationVueBridge() {
        const root = document.querySelector('[data-vue-bridge="material-calculation-create"]');
        if (!(root instanceof HTMLElement)) {
            return;
        }

        if (!window.Vue || typeof window.Vue.createApp !== 'function') {
            console.warn('[material-calc-vue-bridge] Vue runtime not found.');
            return;
        }

        const formSelector = root.dataset.formSelector || '#calculationForm';
        const form = document.querySelector(formSelector);
        if (!(form instanceof HTMLFormElement)) {
            console.warn('[material-calc-vue-bridge] Form not found for selector:', formSelector);
            return;
        }

        const {
            createApp,
            reactive,
            onMounted,
            onBeforeUnmount,
        } = window.Vue;

        const app = createApp({
            setup: function () {
                const state = reactive({
                    isDirty: false,
                    lastChangedField: '',
                    lastSyncedAt: null,
                    inputCount: 0,
                    invalidRequiredCount: 0,
                    isLoading: false,
                    loadingProgress: 0,
                    loadingPercentText: '0%',
                    loadingTitle: 'Memulai Perhitungan...',
                    loadingSubtitle: 'Mohon tunggu, kami sedang menyiapkan data Anda.',
                    searchQuery: '',
                    searchCurrent: 0,
                    searchTotal: 0,
                });

                let teardown = function () {};
                let loadingInterval = null;
                let searchControls = null;
                let searchControlsTeardown = function () {};
                let searchDriver = null;

                const loadingMessagesDefault = [
                    { p: 5, t: 'Menganalisis Permintaan...', s: 'Memvalidasi input dan preferensi filter.' },
                    { p: 20, t: 'Mengambil Data Material...', s: 'Memuat database harga bata, semen, dan pasir terbaru.' },
                    { p: 40, t: 'Menjalankan Algoritma...', s: 'Menghitung volume dan kebutuhan material presisi.' },
                    { p: 60, t: 'Komparasi Harga...', s: 'Membandingkan efisiensi biaya antar merek material.' },
                    { p: 80, t: 'Menyusun Laporan...', s: 'Membuat ringkasan rekomendasi terbaik untuk Anda.' },
                    { p: 95, t: 'Finalisasi...', s: 'Sedang mengalihkan ke halaman hasil...' },
                ];

                const loadingMessagesFast = [
                    { p: 10, t: 'Memuat hasil tersimpan...', s: 'Mengambil data perhitungan sebelumnya.' },
                    { p: 55, t: 'Menyiapkan tampilan...', s: 'Merapikan tabel dan ringkasan hasil.' },
                    { p: 90, t: 'Finalisasi...', s: 'Sedang mengalihkan ke halaman hasil...' },
                ];

                const syncSnapshot = function () {
                    const controls = form.querySelectorAll('input, select, textarea');
                    const requiredInvalid = form.querySelectorAll(':required:invalid');

                    state.inputCount = controls.length;
                    state.invalidRequiredCount = requiredInvalid.length;
                    state.lastSyncedAt = new Date().toISOString();
                };

                const dispatchBridgeEvent = function (name, detail) {
                    window.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
                };

                const markDirtyFromEvent = function (event) {
                    const target = event && event.target instanceof HTMLElement ? event.target : null;
                    state.isDirty = true;
                    state.lastChangedField = normalizeFieldName(target);
                    syncSnapshot();

                    dispatchBridgeEvent('material-calc-vue-bridge:change', {
                        eventType: event ? event.type : '',
                        isTrusted: !!(event && event.isTrusted),
                        fieldName: state.lastChangedField,
                        changedAt: state.lastSyncedAt,
                    });
                };

                const applySearchControlsUi = function () {
                    if (!searchControls) {
                        return;
                    }
                    const current = Math.max(0, Number(state.searchCurrent || 0));
                    const total = Math.max(0, Number(state.searchTotal || 0));
                    const query = String(state.searchQuery || '');
                    const countText = current + ' / ' + total;
                    const hasQuery = query.trim().length > 0;

                    if (searchControls.countEl instanceof HTMLElement) {
                        searchControls.countEl.textContent = countText;
                    }
                    if (searchControls.prevBtn instanceof HTMLButtonElement) {
                        searchControls.prevBtn.disabled = total === 0;
                    }
                    if (searchControls.nextBtn instanceof HTMLButtonElement) {
                        searchControls.nextBtn.disabled = total === 0;
                    }
                    if (searchControls.clearBtn instanceof HTMLButtonElement) {
                        searchControls.clearBtn.style.visibility = hasQuery ? 'visible' : 'hidden';
                    }
                    if (
                        searchControls.searchInput instanceof HTMLInputElement &&
                        searchControls.searchInput.value !== query
                    ) {
                        searchControls.searchInput.value = query;
                    }
                };

                const setSearchQuery = function (query, options) {
                    const safeOptions = options && typeof options === 'object' ? options : {};
                    state.searchQuery = String(query || '');
                    applySearchControlsUi();
                    if (safeOptions.emitEvent === false) {
                        return;
                    }
                    dispatchBridgeEvent('material-calc-vue-bridge:search-input', {
                        query: state.searchQuery,
                        source: safeOptions.source || 'bridge',
                    });
                };

                const setSearchCounter = function (counter) {
                    const safeCounter = counter && typeof counter === 'object' ? counter : {};
                    const nextCurrent = Number(safeCounter.current);
                    const nextTotal = Number(safeCounter.total);

                    state.searchCurrent = Number.isFinite(nextCurrent) ? Math.max(0, Math.floor(nextCurrent)) : 0;
                    state.searchTotal = Number.isFinite(nextTotal) ? Math.max(0, Math.floor(nextTotal)) : 0;
                    applySearchControlsUi();
                };

                const disconnectSearchControls = function () {
                    searchControlsTeardown();
                    searchControlsTeardown = function () {};
                    searchControls = null;
                };

                const invokeSearchDriver = function (methodName, payload) {
                    if (!searchDriver || typeof searchDriver !== 'object') {
                        return false;
                    }
                    if (typeof searchDriver[methodName] !== 'function') {
                        return false;
                    }
                    searchDriver[methodName](payload || {});
                    return true;
                };

                const connectSearchControls = function (controls) {
                    const safeControls = controls && typeof controls === 'object' ? controls : {};
                    const searchInput = safeControls.searchInput;
                    const clearBtn = safeControls.clearBtn;
                    const prevBtn = safeControls.prevBtn;
                    const nextBtn = safeControls.nextBtn;
                    const countEl = safeControls.countEl;

                    if (
                        !(searchInput instanceof HTMLInputElement) ||
                        !(clearBtn instanceof HTMLButtonElement) ||
                        !(prevBtn instanceof HTMLButtonElement) ||
                        !(nextBtn instanceof HTMLButtonElement) ||
                        !(countEl instanceof HTMLElement)
                    ) {
                        return false;
                    }

                    disconnectSearchControls();
                    searchControls = {
                        searchInput: searchInput,
                        clearBtn: clearBtn,
                        prevBtn: prevBtn,
                        nextBtn: nextBtn,
                        countEl: countEl,
                    };

                    const onInput = function (event) {
                        const nextQuery = event && event.target ? event.target.value : '';
                        setSearchQuery(nextQuery, {
                            source: 'input',
                            emitEvent: false,
                        });
                        if (invokeSearchDriver('onInput', { query: nextQuery })) {
                            return;
                        }
                        dispatchBridgeEvent('material-calc-vue-bridge:search-input', {
                            query: state.searchQuery,
                            source: 'input',
                        });
                    };

                    const onKeyDown = function (event) {
                        if (!event || !event.key) {
                            return;
                        }
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            if (invokeSearchDriver('onEnter', { shiftKey: !!event.shiftKey })) {
                                return;
                            }
                            dispatchBridgeEvent('material-calc-vue-bridge:search-enter', {
                                shiftKey: !!event.shiftKey,
                            });
                            return;
                        }
                        if (event.key === 'Escape') {
                            event.preventDefault();
                            setSearchQuery('', {
                                source: 'escape',
                                emitEvent: false,
                            });
                            if (invokeSearchDriver('onEscape', {})) {
                                return;
                            }
                            dispatchBridgeEvent('material-calc-vue-bridge:search-escape', {});
                        }
                    };

                    const onClearClick = function () {
                        setSearchQuery('', {
                            source: 'clear',
                            emitEvent: false,
                        });
                        if (invokeSearchDriver('onClear', {})) {
                            return;
                        }
                        dispatchBridgeEvent('material-calc-vue-bridge:search-clear', {});
                    };

                    const onPrevClick = function () {
                        if (invokeSearchDriver('onPrev', {})) {
                            return;
                        }
                        dispatchBridgeEvent('material-calc-vue-bridge:search-prev', {});
                    };

                    const onNextClick = function () {
                        if (invokeSearchDriver('onNext', {})) {
                            return;
                        }
                        dispatchBridgeEvent('material-calc-vue-bridge:search-next', {});
                    };

                    searchInput.addEventListener('input', onInput);
                    searchInput.addEventListener('keydown', onKeyDown);
                    clearBtn.addEventListener('click', onClearClick);
                    prevBtn.addEventListener('click', onPrevClick);
                    nextBtn.addEventListener('click', onNextClick);

                    searchControlsTeardown = function () {
                        searchInput.removeEventListener('input', onInput);
                        searchInput.removeEventListener('keydown', onKeyDown);
                        clearBtn.removeEventListener('click', onClearClick);
                        prevBtn.removeEventListener('click', onPrevClick);
                        nextBtn.removeEventListener('click', onNextClick);
                    };

                    state.searchQuery = searchInput.value || '';
                    applySearchControlsUi();
                    return true;
                };

                const getLoadingDom = function () {
                    return {
                        overlay: document.getElementById('loadingOverlay'),
                        bar: document.getElementById('loadingProgressBar'),
                        percent: document.getElementById('loadingPercent'),
                        title: document.getElementById('loadingTitle'),
                        subtitle: document.getElementById('loadingSubtitle'),
                    };
                };

                const stopLoadingInterval = function () {
                    if (loadingInterval) {
                        clearInterval(loadingInterval);
                        loadingInterval = null;
                    }
                };

                const formatProgressPercentText = function (progress) {
                    const scaled = Math.floor(progress * 100);
                    const intPart = Math.floor(scaled / 100);
                    const decPart = (scaled % 100).toString().padStart(2, '0');
                    return intPart + '.' + decPart + '%';
                };

                const applyLoadingDomFromState = function () {
                    const dom = getLoadingDom();

                    if (dom.overlay) {
                        dom.overlay.style.display = state.isLoading ? 'flex' : 'none';
                    }
                    if (dom.bar) {
                        dom.bar.style.width = state.loadingProgress + '%';
                    }
                    if (dom.percent) {
                        dom.percent.textContent = state.loadingPercentText;
                    }
                    if (dom.title) {
                        dom.title.textContent = state.loadingTitle;
                    }
                    if (dom.subtitle) {
                        dom.subtitle.textContent = state.loadingSubtitle;
                    }
                };

                const resetLoadingUi = function (submitButton) {
                    stopLoadingInterval();

                    state.isLoading = false;
                    state.loadingProgress = 0;
                    state.loadingPercentText = '0%';
                    state.loadingTitle = 'Memulai Perhitungan...';
                    state.loadingSubtitle = 'Mohon tunggu, kami sedang menyiapkan data Anda.';
                    applyLoadingDomFromState();

                    if (submitButton instanceof HTMLButtonElement) {
                        submitButton.disabled = false;
                        const originalText = submitButton.getAttribute('data-original-text');
                        submitButton.innerHTML = originalText || '<i class="bi bi-search"></i> Hitung';
                    }

                    const dom = getLoadingDom();
                    if (dom.bar) {
                        dom.bar.classList.add('progress-bar-animated');
                    }
                };

                const startLoadingUi = function (options) {
                    const safeOptions = options && typeof options === 'object' ? options : {};
                    const isFastCachePath = !!safeOptions.isFastCachePath;
                    const submitButton = safeOptions.submitButton instanceof HTMLButtonElement
                        ? safeOptions.submitButton
                        : null;

                    if (submitButton && !submitButton.getAttribute('data-original-text')) {
                        submitButton.setAttribute('data-original-text', submitButton.innerHTML);
                    }
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
                    }

                    stopLoadingInterval();

                    state.isLoading = true;
                    state.loadingProgress = 0;
                    state.loadingPercentText = '0%';
                    state.loadingTitle = 'Memulai Perhitungan...';
                    state.loadingSubtitle = 'Mohon tunggu, kami sedang menyiapkan data Anda.';
                    applyLoadingDomFromState();

                    const messages = isFastCachePath ? loadingMessagesFast : loadingMessagesDefault;
                    const intervalMs = isFastCachePath ? 35 : 50;

                    loadingInterval = setInterval(function () {
                        let increment = 0;
                        if (state.loadingProgress < 60) {
                            increment = isFastCachePath
                                ? Math.random() * 6 + 4
                                : Math.random() * 4 + 1;
                        } else if (state.loadingProgress < 85) {
                            increment = isFastCachePath
                                ? Math.random() * 2.5 + 0.5
                                : Math.random() * 1.5 + 0.2;
                        } else if (state.loadingProgress < 98) {
                            increment = isFastCachePath ? 0.12 : 0.05;
                        }

                        state.loadingProgress = Math.min(state.loadingProgress + increment, 98);
                        state.loadingPercentText = formatProgressPercentText(state.loadingProgress);

                        let currentMessage = null;
                        for (let i = messages.length - 1; i >= 0; i -= 1) {
                            if (state.loadingProgress >= messages[i].p) {
                                currentMessage = messages[i];
                                break;
                            }
                        }
                        if (currentMessage) {
                            state.loadingTitle = currentMessage.t;
                            state.loadingSubtitle = currentMessage.s;
                        }

                        applyLoadingDomFromState();
                    }, intervalMs);
                };

                const completeLoadingForNavigation = function () {
                    const dom = getLoadingDom();
                    if (!dom.overlay || dom.overlay.style.display === 'none') {
                        return;
                    }

                    stopLoadingInterval();

                    state.isLoading = true;
                    state.loadingProgress = 100;
                    state.loadingPercentText = '100.00%';
                    state.loadingTitle = 'Selesai!';
                    state.loadingSubtitle = 'Memuat hasil perhitungan...';
                    applyLoadingDomFromState();

                    if (dom.bar) {
                        dom.bar.classList.remove('progress-bar-animated');
                    }
                };

                onMounted(function () {
                    syncSnapshot();

                    const onInput = function (event) {
                        markDirtyFromEvent(event);
                    };
                    const onChange = function (event) {
                        markDirtyFromEvent(event);
                    };

                    form.addEventListener('input', onInput);
                    form.addEventListener('change', onChange);

                    teardown = function () {
                        form.removeEventListener('input', onInput);
                        form.removeEventListener('change', onChange);
                    };

                    window.materialCalcVueBridge = {
                        state: state,
                        syncNow: syncSnapshot,
                        markClean: function () {
                            state.isDirty = false;
                            state.lastChangedField = '';
                            syncSnapshot();
                        },
                        search: {
                            connectControls: connectSearchControls,
                            disconnectControls: disconnectSearchControls,
                            registerDriver: function (driver) {
                                if (!driver || typeof driver !== 'object') {
                                    return false;
                                }
                                searchDriver = driver;
                                return true;
                            },
                            unregisterDriver: function () {
                                searchDriver = null;
                            },
                            setQuery: function (query, options) {
                                setSearchQuery(query, options || {});
                            },
                            setCounter: function (counter) {
                                setSearchCounter(counter || {});
                            },
                            syncUi: applySearchControlsUi,
                        },
                        loading: {
                            start: startLoadingUi,
                            reset: resetLoadingUi,
                            completeForNavigation: completeLoadingForNavigation,
                        },
                    };

                    dispatchBridgeEvent('material-calc-vue-bridge:ready', {});
                });

                onBeforeUnmount(function () {
                    teardown();
                    stopLoadingInterval();
                    disconnectSearchControls();
                    searchDriver = null;
                    if (window.materialCalcVueBridge && window.materialCalcVueBridge.state === state) {
                        delete window.materialCalcVueBridge;
                    }
                });

                return function () {
                    return null;
                };
            },
        });

        app.mount(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMaterialCalculationVueBridge);
        return;
    }

    initMaterialCalculationVueBridge();
})();
