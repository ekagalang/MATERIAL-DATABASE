function initMaterialCalculationForm(root, formData) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#calculationForm') || scope) : document;
    if (marker.__materialCalculationFormInited) { return; }
    marker.__materialCalculationFormInited = true;

    // Extract formula descriptions and material data from formData
    const formulaDescriptions = formData?.formulaDescriptions || {};
    const formulasData = Array.isArray(formData?.formulas) ? formData.formulas : [];
    const bricksData = formData?.bricks || [];
    const cementsData = formData?.cements || [];
    const sandsData = formData?.sands || [];
    const catsData = formData?.cats || [];

    function getSmartPrecision(num) {
        if (!isFinite(num)) return 0;
        if (Math.floor(num) === num) return 0;

        const str = num.toFixed(30);
        const decimalPart = (str.split('.')[1] || '');
        let firstNonZero = decimalPart.length;
        for (let i = 0; i < decimalPart.length; i++) {
            if (decimalPart[i] !== '0') {
                firstNonZero = i;
                break;
            }
        }

        if (firstNonZero === decimalPart.length) return 0;
        return firstNonZero + 2;
    }

    function normalizeSmartDecimal(value) {
        const num = Number(value);
        if (!isFinite(num)) return NaN;
        const precision = getSmartPrecision(num);
        return precision ? Number(num.toFixed(precision)) : num;
    }

    function formatSmartDecimal(value) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        const precision = getSmartPrecision(num);
        if (!precision) return num.toString();
        return num.toFixed(precision).replace(/\.?0+$/, '');
    }

    // Helper function to calculate area
    function calculateArea() {
        const lengthEl = scope.querySelector('#wallLength') || document.getElementById('wallLength');
        const heightEl = scope.querySelector('#wallHeight') || document.getElementById('wallHeight');
        const areaEl = scope.querySelector('#wallArea') || document.getElementById('wallArea');

        if (lengthEl && heightEl && areaEl) {
            const length = parseFloat(lengthEl.value) || 0;
            const height = parseFloat(heightEl.value) || 0;
            const area = length * height;
            const normalizedArea = normalizeSmartDecimal(area);
            areaEl.value = normalizedArea > 0 ? formatSmartDecimal(normalizedArea) : '';
        }
    }

    function setupWorkTypeAutocomplete() {
        const displayInput = scope.querySelector('#workTypeDisplay') || document.getElementById('workTypeDisplay');
        const hiddenInput = scope.querySelector('#workTypeSelector') || document.getElementById('workTypeSelector');
        const listEl = scope.querySelector('#workType-list') || document.getElementById('workType-list');

        if (!displayInput || !hiddenInput || !listEl || formulasData.length === 0) {
            return;
        }

        const options = formulasData
            .filter(option => option && option.code && option.name)
            .map(option => ({
                code: String(option.code),
                name: String(option.name),
            }));

        if (options.length === 0) {
            return;
        }

        function normalize(text) {
            return (text || '').toLowerCase();
        }

        function closeList() {
            listEl.style.display = 'none';
        }

        function renderList(items) {
            listEl.innerHTML = '';
            items.forEach(option => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = option.name;
                item.addEventListener('click', function() {
                    applySelection(option);
                });
                listEl.appendChild(item);
            });
            listEl.style.display = items.length > 0 ? 'block' : 'none';
        }

        function filterOptions(term) {
            const query = normalize(term);
            if (!query) {
                return options;
            }
            return options.filter(option => {
                const name = normalize(option.name);
                const code = normalize(option.code);
                return name.includes(query) || code.includes(query);
            });
        }

        function findExactMatch(term) {
            const query = normalize(term);
            if (!query) return null;
            return options.find(option => normalize(option.name) === query || normalize(option.code) === query) || null;
        }

        function applySelection(option) {
            displayInput.value = option.name;
            hiddenInput.value = option.code;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeList();
        }

        displayInput.addEventListener('focus', function() {
            if (displayInput.readOnly || displayInput.disabled) return;
            renderList(filterOptions(''));
        });

        displayInput.addEventListener('input', function() {
            if (displayInput.readOnly || displayInput.disabled) return;
            const term = this.value || '';
            renderList(filterOptions(term));

            if (!term.trim()) {
                if (hiddenInput.value) {
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                return;
            }

            const exactMatch = findExactMatch(term);
            if (exactMatch) {
                hiddenInput.value = exactMatch.code;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        displayInput.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter') return;
            const exactMatch = findExactMatch(displayInput.value);
            if (exactMatch) {
                applySelection(exactMatch);
                event.preventDefault();
            }
        });

        displayInput.addEventListener('blur', function() {
            setTimeout(closeList, 150);
        });

        document.addEventListener('click', function(event) {
            if (event.target === displayInput || listEl.contains(event.target)) return;
            closeList();
        });

        hiddenInput.addEventListener('change', function() {
            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected && displayInput.value !== selected.name) {
                displayInput.value = selected.name;
            }
        });

        if (hiddenInput.value && !displayInput.value) {
            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected) {
                displayInput.value = selected.name;
            }
        }
    }

    // Work type selector change handler
    const workTypeSelector = scope.querySelector('#workTypeSelector') || document.getElementById('workTypeSelector');
    if (workTypeSelector) {
        const handleWorkTypeChange = function(value) {
            const workType = value || (workTypeSelector ? workTypeSelector.value : '');
            const descriptionEl = scope.querySelector('#workTypeDescription') || document.getElementById('workTypeDescription');
            const inputContainer = scope.querySelector('#inputFormContainer') || document.getElementById('inputFormContainer');

            if (workType) {
                if (descriptionEl) {
                    descriptionEl.textContent = formulaDescriptions[workType] || '';
                }
                if (inputContainer) {
                    inputContainer.style.display = 'block';
                }

                const allForms = scope.querySelectorAll('.work-type-form');
                allForms.forEach(form => {
                    form.style.display = 'none';
                });

                const brickForm = scope.querySelector('#brickForm') || document.getElementById('brickForm');
                const otherForm = scope.querySelector('#otherForm') || document.getElementById('otherForm');

                if (workType.includes('brick') || workType.includes('plaster') || workType.includes('skim') || workType.includes('painting') || workType.includes('floor') || workType.includes('tile') || workType.includes('grout')) {
                    if (brickForm) brickForm.style.display = 'block';
                } else if (otherForm) {
                    otherForm.style.display = 'block';
                }
            } else {
                if (descriptionEl) descriptionEl.textContent = '';
                if (inputContainer) inputContainer.style.display = 'none';
            }
        };

        workTypeSelector.addEventListener('change', function() {
            handleWorkTypeChange(this.value);
        });

        // Set initial state on load (in case value already selected)
        handleWorkTypeChange(workTypeSelector.value);
    }

    setupWorkTypeAutocomplete();

    // Auto-calculate area when length or height changes
    const wallLength = scope.querySelector('#wallLength') || document.getElementById('wallLength');
    const wallHeight = scope.querySelector('#wallHeight') || document.getElementById('wallHeight');
    if (wallLength) {
        wallLength.addEventListener('input', calculateArea);
    }
    if (wallHeight) {
        wallHeight.addEventListener('input', calculateArea);
    }

    // Toggle custom ratio fields
    const mortarFormulaType = scope.querySelector('#mortarFormulaType') || document.getElementById('mortarFormulaType');
    if (mortarFormulaType) {
        mortarFormulaType.addEventListener('change', function() {
            const customFields = scope.querySelector('#customRatioFields') || document.getElementById('customRatioFields');
            if (customFields) {
                if (this.value === 'custom') {
                    customFields.style.display = 'flex';
                } else {
                    customFields.style.display = 'none';
                }
            }
        });
    }

    // Toggle custom material selection form
    const priceFilter = scope.querySelector('#priceFilter') || document.getElementById('priceFilter');
    if (priceFilter) {
        priceFilter.addEventListener('change', function() {
            const customForm = scope.querySelector('#customMaterialForm') || document.getElementById('customMaterialForm');
            if (customForm) {
                if (this.value === 'custom') {
                    customForm.style.display = 'block';
                } else {
                    customForm.style.display = 'none';
                }
            }
        });
    }

    // Cement type change handler
    const customCementType = scope.querySelector('#customCementType') || document.getElementById('customCementType');
    if (customCementType) {
        customCementType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customCementBrand') || document.getElementById('customCementBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredCements = cementsData.filter(c => c.cement_name === type);
                    filteredCements.forEach(cement => {
                        const option = document.createElement('option');
                        option.value = cement.id;
                        const price = Math.round(Number(cement.package_price || 0));
                        option.textContent = `${cement.brand} (${cement.package_weight_net}kg) - Rp ${price.toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Sand type change handler
    const customSandType = scope.querySelector('#customSandType') || document.getElementById('customSandType');
    if (customSandType) {
        customSandType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customSandBrand') || document.getElementById('customSandBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredSands = sandsData.filter(s => s.sand_name === type);
                    const uniqueBrands = [...new Set(filteredSands.map(s => s.brand))];

                    uniqueBrands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Sand brand change handler
    const customSandBrand = scope.querySelector('#customSandBrand') || document.getElementById('customSandBrand');
    if (customSandBrand) {
        customSandBrand.addEventListener('change', function() {
            const brand = this.value;
            const typeEl = scope.querySelector('#customSandType') || document.getElementById('customSandType');
            const packageSelect = scope.querySelector('#customSandPackage') || document.getElementById('customSandPackage');

            if (packageSelect && typeEl) {
                const type = typeEl.value;
                packageSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';

                if (brand && type) {
                    const filteredSands = sandsData.filter(s => s.brand === brand && s.sand_name === type);
                    filteredSands.forEach(sand => {
                        const option = document.createElement('option');
                        option.value = sand.id;
                        const pricePerM3 = sand.comparison_price_per_m3 || (sand.package_price / sand.package_volume);
                        const roundedPrice = Math.round(Number(pricePerM3 || 0));
                        option.textContent = `${sand.package_volume} M3 - Rp ${roundedPrice.toLocaleString('id-ID', { maximumFractionDigits: 0 })}/M3`;
                        packageSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Cat type change handler
    const customCatType = scope.querySelector('#customCatType') || document.getElementById('customCatType');
    if (customCatType) {
        customCatType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customCatBrand') || document.getElementById('customCatBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredCats = catsData.filter(c => c.cat_name === type);
                    const uniqueBrands = [...new Set(filteredCats.map(c => c.brand))];

                    uniqueBrands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Cat brand change handler
    const customCatBrand = scope.querySelector('#customCatBrand') || document.getElementById('customCatBrand');
    if (customCatBrand) {
        customCatBrand.addEventListener('change', function() {
            const brand = this.value;
            const typeEl = scope.querySelector('#customCatType') || document.getElementById('customCatType');
            const packageSelect = scope.querySelector('#customCatPackage') || document.getElementById('customCatPackage');

            if (packageSelect && typeEl) {
                const type = typeEl.value;
                packageSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';

                if (brand && type) {
                    const filteredCats = catsData.filter(c => c.brand === brand && c.cat_name === type);
                    filteredCats.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        const price = Math.round(Number(cat.purchase_price || 0));
                        option.textContent = `${cat.package_weight_net} kg - Rp ${price.toLocaleString('id-ID', { maximumFractionDigits: 0 })}`;
                        packageSelect.appendChild(option);
                    });
                }
            }
        });
    }
}
