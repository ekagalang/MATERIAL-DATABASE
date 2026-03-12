(function () {
    'use strict';

    window.materialCalcCreateStoreModeEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const deps = safeConfig.deps && typeof safeConfig.deps === 'object' ? safeConfig.deps : {};

        const isRestoringCalculationSessionState = typeof deps.isRestoringCalculationSessionState === 'function'
            ? deps.isRestoringCalculationSessionState
            : function () { return false; };
        const markUserChangedSincePreviewResume = typeof deps.markUserChangedSincePreviewResume === 'function'
            ? deps.markUserChangedSincePreviewResume
            : function () {};

        // Store search mode engine extracted from Blade for bridge-first execution.
        function initStoreSearchModeControls() {
            const box = document.getElementById('storeSearchModeBox');
            if (!(box instanceof HTMLElement)) {
                return;
            }

            const useStoreFilterHidden = box.querySelector('input[type="hidden"][name="use_store_filter"]');
            const allowMixedStoreHidden = box.querySelector('input[type="hidden"][name="allow_mixed_store"]');
            const storeRadiusScopeHidden = document.getElementById('storeRadiusScopeValue');
            const modeValueHidden = document.getElementById('storeSearchModeValue');
            const primaryRadiusInput = document.getElementById('projectStoreRadiusKm');
            const finalRadiusInput = document.getElementById('projectStoreRadiusFinalKm');
            const completeGroupCheck = document.getElementById('storeModeCompleteGroupCheck');
            const completeWithinCheck = document.getElementById('storeModeCompleteWithinCheck');
            const completeOutsideCheck = document.getElementById('storeModeCompleteOutsideCheck');
            const incompleteCheck = document.getElementById('storeModeIncompleteCheck');
            const completeWithinDesc = document.getElementById('storeModeCompleteWithinDesc');
            const completeOutsideDesc = document.getElementById('storeModeCompleteOutsideDesc');
            const incompleteDesc = document.getElementById('storeModeIncompleteDesc');

            if (
                !(useStoreFilterHidden instanceof HTMLInputElement) ||
                !(allowMixedStoreHidden instanceof HTMLInputElement) ||
                !(storeRadiusScopeHidden instanceof HTMLInputElement) ||
                !(modeValueHidden instanceof HTMLInputElement) ||
                !(completeGroupCheck instanceof HTMLInputElement) ||
                !(completeWithinCheck instanceof HTMLInputElement) ||
                !(completeOutsideCheck instanceof HTMLInputElement) ||
                !(incompleteCheck instanceof HTMLInputElement)
            ) {
                return;
            }

            const formatRadiusLabel = (rawValue, fallbackText = '-') => {
                const parsed = Number.parseFloat(String(rawValue ?? '').replace(',', '.').trim());
                if (!Number.isFinite(parsed) || parsed <= 0) {
                    return fallbackText;
                }
                const normalized = Number.isInteger(parsed)
                    ? String(parsed)
                    : String(parsed).replace(/(\.\d*?[1-9])0+$/, '$1').replace(/\.0+$/, '');
                return `${normalized} Km`;
            };

            const syncDescriptions = () => {
                const primaryLabel = formatRadiusLabel(primaryRadiusInput?.value ?? '');
                const finalLabel = formatRadiusLabel(finalRadiusInput?.value ?? '');

                if (completeWithinDesc instanceof HTMLElement) {
                    completeWithinDesc.textContent = `: Mencari Toko Dengan Material Lengkap Dalam Radius ${primaryLabel} dari Proyek.`;
                }
                if (completeOutsideDesc instanceof HTMLElement) {
                    completeOutsideDesc.textContent = `: Mencari Toko Dengan Material Lengkap Sampai Radius ${finalLabel} dari Proyek.`;
                }
                if (incompleteDesc instanceof HTMLElement) {
                    incompleteDesc.textContent = `: Mencari Material Sedapatnya Secara Bertahap Mulai Dari Toko Terdekat Sampai Lengkap`;
                }
            };

            const syncCompleteGroupCheckState = () => {
                completeGroupCheck.checked = completeWithinCheck.checked || completeOutsideCheck.checked;
                completeGroupCheck.indeterminate = false;
            };

            const syncState = source => {
                const checks = [completeWithinCheck, completeOutsideCheck, incompleteCheck];

                if (source === completeGroupCheck) {
                    if (completeGroupCheck.checked) {
                        completeWithinCheck.checked = true;
                        completeOutsideCheck.checked = false;
                        incompleteCheck.checked = false;
                    } else {
                        completeWithinCheck.checked = false;
                        completeOutsideCheck.checked = false;
                        incompleteCheck.checked = true;
                    }
                } else if (source && source.checked) {
                    checks.forEach(checkEl => {
                        if (checkEl !== source) {
                            checkEl.checked = false;
                        }
                    });
                }

                // Keep exactly one mode active by default.
                if (!checks.some(checkEl => checkEl.checked)) {
                    completeWithinCheck.checked = true;
                }

                let activeMode = 'complete_within';
                if (incompleteCheck.checked) {
                    activeMode = 'incomplete';
                } else if (completeOutsideCheck.checked) {
                    activeMode = 'complete_outside';
                } else {
                    activeMode = 'complete_within';
                }

                if (activeMode === 'incomplete') {
                    useStoreFilterHidden.value = '1';
                    allowMixedStoreHidden.value = '1';
                    storeRadiusScopeHidden.value = 'outside';
                } else if (activeMode === 'complete_outside') {
                    useStoreFilterHidden.value = '1';
                    allowMixedStoreHidden.value = '0';
                    storeRadiusScopeHidden.value = 'outside';
                } else {
                    useStoreFilterHidden.value = '1';
                    allowMixedStoreHidden.value = '0';
                    storeRadiusScopeHidden.value = 'within';
                }

                modeValueHidden.value = activeMode;
                box.dataset.storeSearchMode = activeMode;
                box.dataset.storeRadiusScope = storeRadiusScopeHidden.value || 'off';
                box.dataset.allowMixedStore = allowMixedStoreHidden.value === '1' ? '1' : '0';
                syncCompleteGroupCheckState();
            };

            const syncFromHiddenState = () => {
                const modeFromHiddenValue = String(modeValueHidden.value || '').trim().toLowerCase();
                const scope = String(storeRadiusScopeHidden.value || '').trim().toLowerCase();
                const useStoreFilterEnabled = String(useStoreFilterHidden.value || '0') === '1';
                const allowMixedEnabled = String(allowMixedStoreHidden.value || '0') === '1';
                let activeMode = 'complete_within';
                if (modeFromHiddenValue === 'incomplete') {
                    activeMode = 'incomplete';
                } else if (modeFromHiddenValue === 'complete_outside') {
                    activeMode = 'complete_outside';
                } else if (modeFromHiddenValue === 'complete_within') {
                    activeMode = 'complete_within';
                } else if (!useStoreFilterEnabled) {
                    activeMode = 'complete_within';
                } else if (allowMixedEnabled) {
                    activeMode = 'incomplete';
                } else if (scope === 'outside') {
                    activeMode = 'complete_outside';
                }

                completeWithinCheck.checked = activeMode === 'complete_within';
                completeOutsideCheck.checked = activeMode === 'complete_outside';
                incompleteCheck.checked = activeMode === 'incomplete';
                modeValueHidden.value = activeMode;

                syncState(null);
            };

            completeGroupCheck.addEventListener('change', (event) => {
                syncState(completeGroupCheck);
                if (event?.isTrusted && !isRestoringCalculationSessionState()) {
                    markUserChangedSincePreviewResume();
                }
            });

            [completeWithinCheck, completeOutsideCheck, incompleteCheck].forEach(checkEl => {
                checkEl.addEventListener('change', (event) => {
                    syncState(checkEl);
                    if (event?.isTrusted && !isRestoringCalculationSessionState()) {
                        markUserChangedSincePreviewResume();
                    }
                });
            });

            [primaryRadiusInput, finalRadiusInput].forEach(inputEl => {
                if (!(inputEl instanceof HTMLInputElement)) {
                    return;
                }
                inputEl.addEventListener('input', syncDescriptions);
                inputEl.addEventListener('change', syncDescriptions);
            });

            box.__syncStoreSearchModeControls = syncFromHiddenState;
            box.__commitStoreSearchModeControls = () => syncState(null);
            syncFromHiddenState();
            syncDescriptions();
        }


        return initStoreSearchModeControls();
    };
})();
