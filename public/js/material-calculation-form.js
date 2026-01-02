function initMaterialCalculationForm(root, formData) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#calculationForm') || scope) : document;
    if (marker.__materialCalculationFormInited) { return; }
    marker.__materialCalculationFormInited = true;

    // Extract formula descriptions and material data from formData
    const formulaDescriptions = formData?.formulaDescriptions || {};
    const bricksData = formData?.bricks || [];
    const cementsData = formData?.cements || [];
    const sandsData = formData?.sands || [];
    const catsData = formData?.cats || [];

    // Helper function to calculate area
    function calculateArea() {
        const lengthEl = scope.querySelector('#wallLength') || document.getElementById('wallLength');
        const heightEl = scope.querySelector('#wallHeight') || document.getElementById('wallHeight');
        const areaEl = scope.querySelector('#wallArea') || document.getElementById('wallArea');

        if (lengthEl && heightEl && areaEl) {
            const length = parseFloat(lengthEl.value) || 0;
            const height = parseFloat(heightEl.value) || 0;
            const area = length * height;
            areaEl.value = area > 0 ? area.toFixed(2) : '';
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

                if (workType.includes('brick') || workType.includes('plaster') || workType.includes('skim') || workType.includes('painting') || workType.includes('floor')) {
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
                        option.textContent = `${cement.brand} (${cement.package_weight_net}kg) - Rp ${Number(cement.package_price).toLocaleString('id-ID')}`;
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
                        option.textContent = `${sand.package_volume} M3 - Rp ${Number(pricePerM3).toLocaleString('id-ID')}/M3`;
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
                        const price = cat.purchase_price || 0;
                        option.textContent = `${cat.package_weight_net} kg - Rp ${Number(price).toLocaleString('id-ID')}`;
                        packageSelect.appendChild(option);
                    });
                }
            }
        });
    }
}
