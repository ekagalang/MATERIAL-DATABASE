function initSandForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#sandForm') || scope) : document;
    if (marker.__sandFormInited) { return; }
    marker.__sandFormInited = true;

    // Track current selections for cascading autocomplete
    let currentBrand = '';
    let currentPackageUnit = '';
    let currentStore = '';

    function formatSmartDecimal(value, maxDecimals = 8) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        if (Math.floor(num) === num) return num.toString();

        const str = num.toFixed(10);
        const decimalPart = (str.split('.')[1] || '');
        let firstNonZero = decimalPart.length;
        for (let i = 0; i < decimalPart.length; i++) {
            if (decimalPart[i] !== '0') {
                firstNonZero = i;
                break;
            }
        }

        if (firstNonZero === decimalPart.length) return num.toString();

        const precision = Math.min(firstNonZero + 2, maxDecimals);
        return num.toFixed(precision).replace(/\.?0+$/, '');
    }

    // Auto-suggest with cascading logic
    const autosuggestInputs = scope.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        const suggestList = scope.querySelector(`#${field}-list`) || document.getElementById(`${field}-list`);
        let debounceTimer;
        let isSelectingFromAutosuggest = false; // Flag to prevent reopening

        function populate(values) {
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = v;
                    item.addEventListener('click', function() {
                        // Set flag to prevent autosuggest from reopening
                        isSelectingFromAutosuggest = true;

                        // Close list immediately
                        suggestList.style.display = 'none';

                        // Handle dimension fields with unit conversion
                        if (['dimension_length', 'dimension_width', 'dimension_height'].includes(field)) {
                            // Update dimension input
                            input.value = v;

                            // Trigger input event to update hidden field via setupDimensionInput
                            input.dispatchEvent(new Event('input', { bubbles: true }));

                            // Auto-update unit selector to 'm' (data dari database dalam meter untuk sand)
                            const unitSelectorId = field + '_unit';
                            const unitSelector = scope.querySelector('#' + unitSelectorId) || document.getElementById(unitSelectorId);
                            if (unitSelector && unitSelector.value !== 'm') {
                                unitSelector.value = 'm';
                                // Trigger change event untuk update perhitungan
                                unitSelector.dispatchEvent(new Event('change'));
                            }

                            // Note: calculateVolume() will be triggered by the input event above
                        } else if (field === 'package_price') {
                            // Handle package price field - format and trigger sync
                            input.value = Number(v).toLocaleString('id-ID');
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (field === 'comparison_price_per_m3') {
                            // Handle comparison price field - format and trigger sync
                            input.value = Number(v).toLocaleString('id-ID');
                            if (typeof syncComparisonFromDisplay === 'function') {
                                syncComparisonFromDisplay();
                            }
                        } else {
                            input.value = v;
                        }

                        // Trigger change event for cascading updates (for non-dimension and non-price fields)
                        if (!['dimension_length', 'dimension_width', 'dimension_height', 'package_price', 'comparison_price_per_m3'].includes(field)) {
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        // Reset flag after a delay
                        setTimeout(() => {
                            isSelectingFromAutosuggest = false;
                        }, 300);
                    });
                    suggestList.appendChild(item);
                });
                suggestList.style.display = values.length > 0 ? 'block' : 'none';
            }
        }

        function loadSuggestions(term = '') {
            let url;

            // Special case: store field menggunakan endpoint all-stores
            if (field === 'store') {
                // Jika tidak ada search term (user baru focus), tampilkan dari sand saja
                // Jika ada search term (user mengetik), tampilkan dari semua material
                const materialType = (term === '' || term.length === 0) ? 'sand' : 'all';
                url = `/api/sands/all-stores?search=${encodeURIComponent(term)}&limit=20&material_type=${materialType}`;
            }
            // Special case: address field menggunakan endpoint addresses-by-store
            else if (field === 'address' || field === 'short_address') {
                if (currentStore) {
                    url = `/api/sands/addresses-by-store?search=${encodeURIComponent(term)}&limit=20&store=${encodeURIComponent(currentStore)}`;
                } else {
                    // Jika toko belum dipilih, gunakan field-values biasa
                    url = `/api/sands/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
                }
            }
            else {
                url = `/api/sands/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;

                // Add filter parameters for cascading autocomplete
                // Fields that depend on brand (berat dan dimensi kemasan)
                if (['package_weight_gross', 'dimension_length', 'dimension_width', 'dimension_height'].includes(field)) {
                    if (currentBrand) {
                        url += `&brand=${encodeURIComponent(currentBrand)}`;
                    }
                }

                // Fields that depend on package_unit (harga)
                if (field === 'package_price') {
                    if (currentPackageUnit) {
                        url += `&package_unit=${encodeURIComponent(currentPackageUnit)}`;
                    }
                }
            }

            fetch(url)
                .then(resp => resp.json())
                .then(populate)
                .catch(() => {});
        }

        input.addEventListener('focus', () => {
            if (!isSelectingFromAutosuggest) {
                loadSuggestions('');
            }
        });
        input.addEventListener('input', function () {
            // Don't reload suggestions if we're selecting from autosuggest
            if (isSelectingFromAutosuggest) {
                return;
            }
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

    // Listen for brand changes to update dependent fields
    const brandInput = scope.querySelector('#brand') || document.getElementById('brand');
    if (brandInput) {
        brandInput.addEventListener('change', function() {
            currentBrand = this.value;

            // Clear and refresh dependent fields (berat dan dimensi)
            const dependentFields = ['package_weight_gross', 'dimension_length', 'dimension_width', 'dimension_height'];
            dependentFields.forEach(fieldName => {
                const fieldInput = scope.querySelector(`#${fieldName}`) || document.getElementById(fieldName);
                if (fieldInput && fieldInput !== document.activeElement) {
                    // Don't clear if user is typing in that field
                    // fieldInput.value = '';
                }
            });
        });
    }

    // Listen for package_unit changes to update harga field
    const unitSelect = scope.querySelector('#package_unit') || document.getElementById('package_unit');
    if (unitSelect) {
        currentPackageUnit = unitSelect.value || '';
        unitSelect.addEventListener('change', function() {
            currentPackageUnit = this.value;
            // Price field will automatically use this filter when focused
        });
    }

    // Listen for store changes to update address field
    const storeInput = scope.querySelector('#store') || document.getElementById('store');
    if (storeInput) {
        storeInput.addEventListener('change', function() {
            currentStore = this.value;

            // Clear address field when store changes
            const addressInput = scope.querySelector('#address') || document.getElementById('address');
            if (addressInput && addressInput !== document.activeElement) {
                // addressInput.value = '';
            }
        });
    }

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

    const priceUnitDisplayInline = scope.querySelector('#price_unit_display_inline') || document.getElementById('price_unit_display_inline');
    const priceUnitInput = scope.querySelector('#price_unit') || document.getElementById('price_unit');

    function updatePriceUnitDisplay() {
        if (!unitSelect) return;
        const selectedOption = unitSelect.selectedOptions[0];
        const unitName = selectedOption?.dataset?.name || selectedOption?.text || '';

        if (unitName && unitName !== '-- Pick up, Meter Kubik, Karung --') {
            if (priceUnitInput) priceUnitInput.value = unitName;
            if (priceUnitDisplayInline) priceUnitDisplayInline.textContent = '/ ' + unitName;
        } else {
            if (priceUnitInput) priceUnitInput.value = '';
            if (priceUnitDisplayInline) priceUnitDisplayInline.textContent = '/ -';
        }
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', updatePriceUnitDisplay);
        updatePriceUnitDisplay();
    }

    // ========== VALIDASI DAN KONVERSI DIMENSI ==========

    function convertToMeters(value, unit) {
        const num = parseFloat(value);
        if (isNaN(num) || num < 0) return null;

        switch(unit) {
            case 'mm': return num / 1000;
            case 'cm': return num / 100;
            case 'm': return num;
            case 'inch': return num * 0.0254;
            default: return num;
        }
    }

    function setupDimensionInput(inputId, unitId, hiddenId) {
        const inputElement = scope.querySelector('#' + inputId) || document.getElementById(inputId);
        const unitElement = scope.querySelector('#' + unitId) || document.getElementById(unitId);
        const hiddenElement = scope.querySelector('#' + hiddenId) || document.getElementById(hiddenId);

        if (!inputElement || !unitElement || !hiddenElement) return;

        function updateDimension() {
            const rawValue = inputElement.value.trim();
            const selectedUnit = unitElement.value;

            if (rawValue === '') {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e2e8f0';
                calculateVolume();
                return;
            }

            const metersValue = convertToMeters(rawValue, selectedUnit);

            if (metersValue !== null) {
                hiddenElement.value = metersValue.toFixed(4);
                inputElement.style.borderColor = '#e2e8f0';
            } else {
                hiddenElement.value = '';
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

        // Initial update
        if (inputElement.value) updateDimension();
    }

    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width');
    setupDimensionInput('dimension_height_input', 'dimension_height_unit', 'dimension_height');

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
        const lengthM = parseFloat(dimLength?.value) || 0;
        const widthM = parseFloat(dimWidth?.value) || 0;
        const heightM = parseFloat(dimHeight?.value) || 0;

        if (lengthM > 0 && widthM > 0 && heightM > 0) {
            // Already in meters, just multiply to get M3
            const volumeM3 = lengthM * widthM * heightM;
            currentVolume = volumeM3;
            if (volumeDisplay) {
                volumeDisplay.value = formatSmartDecimal(volumeM3);
            }
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentVolume = 0;
            if (volumeDisplay) {
                volumeDisplay.value = '';
            }
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
