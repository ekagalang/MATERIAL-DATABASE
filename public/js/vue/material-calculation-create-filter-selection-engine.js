(function () {
    'use strict';

    window.materialCalcCreateFilterSelectionEngine = function (config) {
        const safeConfig = config && typeof config === 'object' ? config : {};
        const availableBestRecommendations = Array.isArray(safeConfig.availableBestRecommendations)
            ? safeConfig.availableBestRecommendations
            : [];

        // Filter selection logic extracted from Blade for bridge-first execution.
        function ensureCustomFormVisible() {
            const customForm = document.getElementById('customMaterialForm');
            if (customForm instanceof HTMLElement) {
                customForm.style.display = 'none';
            }
        }

        function shouldIncludeBest() {
            if (availableBestRecommendations.length === 0) {
                return false;
            }

            const selectedTypes = new Set();
            const mainWorkType = String(document.getElementById('workTypeSelector')?.value || '').trim();
            if (mainWorkType !== '') {
                selectedTypes.add(mainWorkType);
            }

            document.querySelectorAll('#additionalWorkItemsList [data-field="work_type"]').forEach(input => {
                const value = String(input?.value || '').trim();
                if (value !== '') {
                    selectedTypes.add(value);
                }
            });

            for (const workType of selectedTypes) {
                if (availableBestRecommendations.includes(workType)) {
                    return true;
                }
            }

            return false;
        }

        function handleAllCheckbox() {
            const filterAll = document.getElementById('filter_all');
            if (!(filterAll instanceof HTMLInputElement)) {
                return;
            }

            const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
            if (filterAll.checked) {
                const includeBest = shouldIncludeBest();
                filterCheckboxes.forEach(checkbox => {
                    if (!(checkbox instanceof HTMLInputElement) || checkbox === filterAll) return;

                    if (checkbox.value === 'best') {
                        checkbox.checked = includeBest;
                        return;
                    }

                    checkbox.checked = true;
                });
                return;
            }

            filterCheckboxes.forEach(checkbox => {
                if (!(checkbox instanceof HTMLInputElement) || checkbox === filterAll) return;
                checkbox.checked = false;
            });
        }

        function handleOtherCheckboxes() {
            const filterAll = document.getElementById('filter_all');
            if (!(filterAll instanceof HTMLInputElement)) {
                return;
            }
            const includeBest = shouldIncludeBest();
            const filterCheckboxes = document.querySelectorAll('input[name="price_filters[]"]');
            const allOthersChecked = Array.from(filterCheckboxes).every(checkbox => {
                if (!(checkbox instanceof HTMLInputElement)) return true;
                if (checkbox === filterAll) return true;
                if (checkbox.value === 'best' && !includeBest) return true;
                return checkbox.checked;
            });

            if (!allOthersChecked) {
                filterAll.checked = false;
            }
        }

        return {
            ensureCustomFormVisible,
            shouldIncludeBest,
            handleAllCheckbox,
            handleOtherCheckboxes,
        };
    };
})();
