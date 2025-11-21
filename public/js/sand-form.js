function initSandForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#sandForm') || scope) : document;
    if (marker.__sandFormInited) { return; }
    marker.__sandFormInited = true;

    // Auto-suggest
    const autosuggestInputs = scope.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        const suggestList = scope.querySelector(`#${field}-list`) || document.getElementById(`${field}-list`);
        let debounceTimer;

        function populate(values) {
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = v;
                    item.addEventListener('click', function() {
                        input.value = v;
                        suggestList.style.display = 'none';
                    });
                    suggestList.appendChild(item);
                });
                suggestList.style.display = values.length > 0 ? 'block' : 'none';
            }
        }

        function loadSuggestions(term = '') {
            const url = `/api/sands/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
            fetch(url)
                .then(resp => resp.json())
                .then(populate)
                .catch(() => {});
        }

        input.addEventListener('focus', () => loadSuggestions(''));
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const term = this.value || '';
            debounceTimer = setTimeout(() => loadSuggestions(term), 200);
        });

        document.addEventListener('click', function(e) {
            if (suggestList && e.target !== input && !suggestList.contains(e.target)) {
                suggestList.style.display = 'none';
            }
        });
    });

    // Photo upload
    const photoInput = scope.querySelector('#photo') || document.getElementById('photo');
    const photoPreview = scope.querySelector('#photoPreview') || document.getElementById('photoPreview');
    const photoPlaceholder = scope.querySelector('#photoPlaceholder') || document.getElementById('photoPlaceholder');
    const photoPreviewArea = scope.querySelector('#photoPreviewArea') || document.getElementById('photoPreviewArea');
    const uploadBtn = scope.querySelector('#uploadBtn') || document.getElementById('uploadBtn');
    const deletePhotoBtn = scope.querySelector('#deletePhotoBtn') || document.getElementById('deletePhotoBtn');

    if (photoPreviewArea) {
        photoPreviewArea.addEventListener('click', function() { photoInput.click(); });
    }
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) { e.preventDefault(); photoInput.click(); });
    }
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                    photoPlaceholder.style.display = 'none';
                    deletePhotoBtn.style.display = 'inline';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            photoInput.value = '';
            photoPreview.src = '';
            photoPreview.style.display = 'none';
            photoPlaceholder.style.display = 'block';
            deletePhotoBtn.style.display = 'none';
        });
    }

    // ========== UPDATE PRICE UNIT DISPLAY ==========

    const unitSelect = scope.querySelector('#package_unit') || document.getElementById('package_unit');
    const priceUnitDisplay = scope.querySelector('#price_unit_display') || document.getElementById('price_unit_display');
    const priceUnitInput = scope.querySelector('#price_unit') || document.getElementById('price_unit');

    function updatePriceUnitDisplay() {
        if (!unitSelect) return;
        const selectedOption = unitSelect.selectedOptions[0];
        const unitName = selectedOption?.dataset?.name || selectedOption?.text || '';

        if (unitName && unitName !== '-- Satuan --') {
            if (priceUnitInput) priceUnitInput.value = unitName;
            if (priceUnitDisplay) priceUnitDisplay.textContent = unitName;
        } else {
            if (priceUnitInput) priceUnitInput.value = '';
            if (priceUnitDisplay) priceUnitDisplay.textContent = '-';
        }
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', updatePriceUnitDisplay);
        updatePriceUnitDisplay();
    }

    // ========== VALIDASI DAN KONVERSI DIMENSI ==========

    function convertToM(value, unit) {
        const num = parseFloat(value);
        if (isNaN(num) || num < 0) {
            return null;
        }

        switch(unit) {
            case 'mm':
                return num / 1000;
            case 'cm':
                return num / 100;
            case 'm':
                return num;
            default:
                return num;
        }
    }

    function setupDimensionInput(inputId, unitId, hiddenId, displayId) {
        const inputElement = scope.querySelector('#' + inputId) || document.getElementById(inputId);
        const unitElement = scope.querySelector('#' + unitId) || document.getElementById(unitId);
        const hiddenElement = scope.querySelector('#' + hiddenId) || document.getElementById(hiddenId);
        const displayElement = scope.querySelector('#' + displayId) || document.getElementById(displayId);
        
        if (!inputElement || !unitElement || !hiddenElement || !displayElement) return;

        function updateDimension() {
            const rawValue = inputElement.value.trim();
            const selectedUnit = unitElement.value;
            
            if (rawValue === '') {
                hiddenElement.value = '';
                displayElement.textContent = '-';
                displayElement.style.color = '#7f8c8d';
                inputElement.style.borderColor = '#999';
                calculateVolume();
                return;
            }

            const mValue = convertToM(rawValue, selectedUnit);
            
            if (mValue !== null) {
                hiddenElement.value = mValue.toFixed(2);
                displayElement.textContent = mValue.toFixed(2);
                displayElement.style.color = '#27ae60';
                inputElement.style.borderColor = '#999';
            } else {
                hiddenElement.value = '';
                displayElement.textContent = 'Angka tidak valid';
                displayElement.style.color = '#e74c3c';
                inputElement.style.borderColor = '#e74c3c';
            }
            
            calculateVolume();
        }

        inputElement.addEventListener('input', updateDimension);
        unitElement.addEventListener('change', updateDimension);

        inputElement.addEventListener('blur', function() {
            const rawValue = this.value.trim();
            if (rawValue !== '') {
                const num = parseFloat(rawValue);
                if (!isNaN(num) && num >= 0) {
                    this.value = num.toString();
                }
            }
        });
    }

    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length', 'length_m_display');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width', 'width_m_display');
    setupDimensionInput('dimension_height_input', 'dimension_height_unit', 'dimension_height', 'height_m_display');

    // ========== KALKULASI VOLUME DAN HARGA ==========
    
    const dimLength = scope.querySelector('#dimension_length') || document.getElementById('dimension_length');
    const dimWidth = scope.querySelector('#dimension_width') || document.getElementById('dimension_width');
    const dimHeight = scope.querySelector('#dimension_height') || document.getElementById('dimension_height');
    const volumeDisplay = scope.querySelector('#volume_display') || document.getElementById('volume_display');
    const packagePrice = scope.querySelector('#package_price') || document.getElementById('package_price');
    const packagePriceDisplay = scope.querySelector('#package_price_display') || document.getElementById('package_price_display');
    const comparisonPrice = scope.querySelector('#comparison_price_per_m3') || document.getElementById('comparison_price_per_m3');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');

    let currentVolume = 0;
    let isUpdatingPrice = false;

    function calculateVolume() {
        const length = parseFloat(dimLength?.value) || 0;
        const width = parseFloat(dimWidth?.value) || 0;
        const height = parseFloat(dimHeight?.value) || 0;

        if (length > 0 && width > 0 && height > 0) {
            const volumeM3 = length * width * height;
            currentVolume = volumeM3;
            volumeDisplay.innerHTML = volumeM3.toFixed(6);
            volumeDisplay.style.color = '#27ae60';
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentVolume = 0;
            volumeDisplay.textContent = '-';
            volumeDisplay.style.color = '#7f8c8d';
        }
    }

    function recalculatePrices() {
        const priceValue = parseFloat(packagePrice?.value) || 0;
        if (priceValue > 0 && currentVolume > 0) {
            const calcComparison = priceValue / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        } else {
            const compValue = parseFloat(comparisonPrice?.value) || 0;
            if (compValue > 0 && currentVolume > 0) {
                const calcPrice = compValue * currentVolume;
                if (packagePrice) packagePrice.value = Math.round(calcPrice);
                if (packagePriceDisplay) packagePriceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
            }
        }
    }

    function unformatRupiah(str) {
        return (str || '').toString().replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.]/g,'');
    }

    function formatRupiah(num) {
        const n = Number(num||0);
        return isNaN(n) ? '' : n.toLocaleString('id-ID');
    }

    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(packagePriceDisplay?.value || '');
        if (packagePrice) packagePrice.value = raw || '';
        if (packagePriceDisplay) packagePriceDisplay.value = raw ? formatRupiah(raw) : '';

        if (raw && currentVolume > 0) {
            const calcComparison = parseFloat(raw) / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        }

        isUpdatingPrice = false;
    }

    function syncComparisonFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay?.value || '');
        if (comparisonPrice) comparisonPrice.value = raw || '';
        if (comparisonPriceDisplay) comparisonPriceDisplay.value = raw ? formatRupiah(raw) : '';

        if (raw && currentVolume > 0) {
            const calcPrice = parseFloat(raw) * currentVolume;
            if (packagePrice) packagePrice.value = Math.round(calcPrice);
            if (packagePriceDisplay) packagePriceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
        }

        isUpdatingPrice = false;
    }

    packagePriceDisplay?.addEventListener('input', syncPriceFromDisplay);
    comparisonPriceDisplay?.addEventListener('input', syncComparisonFromDisplay);

    if (packagePriceDisplay && packagePrice && packagePrice.value) {
        packagePriceDisplay.value = formatRupiah(packagePrice.value);
    }
    if (comparisonPriceDisplay && comparisonPrice && comparisonPrice.value) {
        comparisonPriceDisplay.value = formatRupiah(comparisonPrice.value);
    }

    calculateVolume();
}