function initCementForm(root) {
    const scope = root || document;
    const form = scope.querySelector('#cementForm');
    
    // Idempotent guard
    if (!form || form.__cementFormInited) {
        return;
    }
    form.__cementFormInited = true;

    // Helper untuk selector aman
    const getEl = (id) => scope.querySelector(`#${id}`) || document.getElementById(id);

    // Track current selections for cascading autocomplete
    let currentBrand = '';
    let currentPackageUnit = '';
    let currentStore = '';

    function normalizeSmartDecimal(value) {
        const plain = formatDynamicPlain(value);
        const num = plain ? Number(plain) : NaN;
        return isFinite(num) ? num : NaN;
    }

    function formatSmartDecimal(value) {
        const plain = formatDynamicPlain(value);
        if (!plain) return '';
        return plain.replace('.', ',');
    }

    // Auto-suggest with cascading logic
    const autosuggestInputs = scope.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;

        // Skip store and address fields - handled by store-autocomplete.js
        if (field === 'store' || field === 'address') {
            return;
        }

        const suggestList = getEl(`${field}-list`);
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

                            // Auto-update unit selector to 'cm' (data dari autosuggest sudah dalam cm)
                            const unitSelectorId = field + '_unit';
                            const unitSelector = getEl(unitSelectorId);
                            if (unitSelector && unitSelector.value !== 'cm') {
                                unitSelector.value = 'cm';
                                // Trigger change event untuk update perhitungan
                                unitSelector.dispatchEvent(new Event('change'));
                            }

                            // Note: calculateVolume() will be triggered by the input event above
                        } else if (field === 'package_price') {
                            // Handle package price field - format and trigger sync
                            input.value = formatRupiah(v);
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (field === 'comparison_price_per_kg') {
                            // Handle comparison price field - format and trigger sync
                            input.value = formatRupiah(v);
                            if (typeof syncComparisonFromDisplay === 'function') {
                                syncComparisonFromDisplay();
                            }
                        } else {
                            input.value = v;
                        }

                        // Trigger change event for cascading updates (for non-dimension and non-price fields)
                        if (!['dimension_length', 'dimension_width', 'dimension_height', 'package_price', 'comparison_price_per_kg'].includes(field)) {
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

            if (field === 'store') {
                const materialType = (term === '' || term.length === 0) ? 'cement' : 'all';
                url = `/api/cements/all-stores?search=${encodeURIComponent(term)}&limit=20&material_type=${materialType}`;
            }
            else if (field === 'address') {
                const storeInput = getEl('store');
                const storeVal = storeInput ? storeInput.value : '';
                if (storeVal) {
                    url = `/api/cements/addresses-by-store?search=${encodeURIComponent(term)}&limit=20&store=${encodeURIComponent(storeVal)}`;
                } else {
                    url = `/api/cements/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
                }
            }
            else {
                url = `/api/cements/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;

                if (['sub_brand', 'code', 'color', 'dimension_length', 'dimension_width', 'dimension_height', 'package_weight_gross'].includes(field)) {
                    const brandInput = getEl('brand');
                    if (brandInput && brandInput.value) {
                        url += `&brand=${encodeURIComponent(brandInput.value)}`;
                    }
                }

                if (field === 'package_price') {
                    const unitSelect = getEl('package_unit');
                    if (unitSelect && unitSelect.value) {
                        url += `&package_unit=${encodeURIComponent(unitSelect.value)}`;
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

    // Listen for brand changes
    const brandInput = getEl('brand');
    if (brandInput) {
        brandInput.addEventListener('change', function() {
            // Optional: Clear dependents
        });
    }

    // Photo upload functionality
    const photoInput = getEl('photo');
    const photoPreview = getEl('photoPreview');
    const photoPlaceholder = getEl('photoPlaceholder');
    const photoPreviewArea = getEl('photoPreviewArea');
    const uploadBtn = getEl('uploadBtn');
    const deletePhotoBtn = getEl('deletePhotoBtn');

    if (photoPreviewArea) {
        photoPreviewArea.addEventListener('click', function() {
            if (photoInput) photoInput.click();
        });
    }
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (photoInput) photoInput.click();
        });
    }
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (photoPreview) {
                        photoPreview.src = e.target.result;
                        photoPreview.style.display = 'block';
                        
                        // Auto resize preview logic here if needed
                    }
                    if (photoPlaceholder) photoPlaceholder.style.display = 'none';
                    if (deletePhotoBtn) deletePhotoBtn.style.display = 'inline';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (photoInput) photoInput.value = '';
            if (photoPreview) {
                photoPreview.src = '';
                photoPreview.style.display = 'none';
            }
            if (photoPlaceholder) photoPlaceholder.style.display = 'block';
            deletePhotoBtn.style.display = 'none';
        });
    }

    // Susun otomatis cement_name
    const fType = getEl('type');
    const fBrand = getEl('brand');
    const fSubBrand = getEl('sub_brand');
    const fCode = getEl('code');
    const fColor = getEl('color');
    const fCementName = getEl('cement_name');

    function composeName() {
        const parts = [];
        if (fType && fType.value) parts.push(fType.value);
        if (fBrand && fBrand.value) parts.push(fBrand.value);
        if (fSubBrand && fSubBrand.value) parts.push(fSubBrand.value);
        if (fCode && fCode.value) parts.push(fCode.value);
        if (fColor && fColor.value) parts.push(fColor.value);
        if (fCementName) fCementName.value = parts.join(' ').replace(/\s+/g,' ').trim();
    }

    [fType, fBrand, fSubBrand, fCode, fColor].forEach(el => {
        if (el) el.addEventListener('input', composeName);
    });
    composeName();

    // Kalkulasi Harga
    const grossInput = getEl('package_weight_gross');
    const unitSelect = getEl('package_unit');
    const netCalcDisplay = getEl('net_weight_display');
    const packagePrice = getEl('package_price');
    const packagePriceDisplay = getEl('package_price_display');
    const comparisonPrice = getEl('comparison_price_per_kg');
    const comparisonPriceDisplay = getEl('comparison_price_display');
    const priceUnitInput = getEl('price_unit');
    const priceUnitDisplayInline = getEl('price_unit_display_inline');

    let isUpdatingPrice = false;

    function getCurrentWeight() {
        const gross = parseFloat(grossInput?.value) || 0;
        const tare = parseFloat(unitSelect?.selectedOptions[0]?.dataset?.weight) || 0;
        return normalizeSmartDecimal(Math.max(gross - tare, 0));
    }

    function updateNetCalc() {
        // Hanya update display jika elemennya ada
        if (!grossInput || !unitSelect) return;
        
        const netCalc = getCurrentWeight();
        
        if (netCalcDisplay) {
            const formattedValue = netCalc > 0 ? formatSmartDecimal(netCalc) + ' Kg' : '-';
            netCalcDisplay.textContent = formattedValue;
        }
    }

    function recalculatePrices() {
        if (isUpdatingPrice) return;
        const net = getCurrentWeight();
        
        // Tidak bisa hitung jika berat 0
        if (net <= 0) return;

        // Priority 1: Harga Kemasan -> Hitung Komparasi
        // Kita anggap jika user mengisi harga kemasan, dia mau itu yang jadi acuan
        const priceRaw = unformatRupiah(packagePriceDisplay?.value || '');
        const priceValue = parseFloat(priceRaw);

        if (!isNaN(priceValue) && priceValue > 0) {
            const calcComparison = priceValue / net;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } 
        // Priority 2: Jika harga kemasan kosong, tapi ada harga komparasi -> Hitung Harga Kemasan
        else {
            const compRaw = unformatRupiah(comparisonPriceDisplay?.value || '');
            const compValue = parseFloat(compRaw);
            
            if (!isNaN(compValue) && compValue > 0) {
                const calcPrice = compValue * net;
                const calcPlain = formatPlainNumber(calcPrice, 0);
                if (packagePrice) packagePrice.value = calcPlain || '';
                if (packagePriceDisplay) packagePriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
            }
        }
    }

    function syncPriceUnit() {
        if (!unitSelect || !priceUnitInput) return;
        const unit = unitSelect.value || '';
        priceUnitInput.value = unit;
        if (priceUnitDisplayInline) {
            priceUnitDisplayInline.textContent = unit ? ('/ ' + unit) : '/ -';
        }
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', () => {
            updateNetCalc();
            recalculatePrices();
            syncPriceUnit();
        });
    }

    if (grossInput) {
        grossInput.addEventListener('input', () => {
            updateNetCalc();
            recalculatePrices();
        });
    }

    function unformatRupiah(str) {
        return (str || '').toString().replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.]/g, '');
    }

    function truncateNumber(value, decimals = 2) {
        const num = Number(value);
        if (!isFinite(num)) return NaN;
        const factor = 10 ** decimals;
        const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
        return truncated / factor;
    }

    function formatPlainNumber(value, decimals = 2) {
        if (value === '' || value === null || value === undefined) return '';
        const num = Number(value);
        if (!isFinite(num)) return '';
        const resolvedDecimals = Math.max(0, decimals);
        const factor = 10 ** resolvedDecimals;
        const truncated = num >= 0 ? Math.floor(num * factor) : Math.ceil(num * factor);
        const sign = truncated < 0 ? '-' : '';
        const abs = Math.abs(truncated);
        const intPart = Math.floor(abs / factor).toString();
        if (resolvedDecimals === 0) {
            return `${sign}${intPart}`;
        }
        const decPart = (abs % factor).toString().padStart(resolvedDecimals, '0');
        return `${sign}${intPart}.${decPart}`;
    }

    function formatDynamicPlain(value) {
        if (value === '' || value === null || value === undefined) return '';
        const num = Number(value);
        if (!isFinite(num)) return '';
        if (num === 0) return '0';

        const absValue = Math.abs(num);
        const epsilon = Math.min(absValue * 1e-12, 1e-6);
        const adjusted = num + (num >= 0 ? epsilon : -epsilon);
        const sign = adjusted < 0 ? '-' : '';
        const abs = Math.abs(adjusted);
        const intPart = Math.trunc(abs);

        if (intPart > 0) {
            const scaled = Math.trunc(abs * 100);
            const intDisplay = Math.trunc(scaled / 100).toString();
            let decPart = String(scaled % 100).padStart(2, '0');
            decPart = decPart.replace(/0+$/, '');
            return decPart ? `${sign}${intDisplay}.${decPart}` : `${sign}${intDisplay}`;
        }

        let fraction = abs;
        let digits = '';
        let firstNonZeroIndex = null;
        const maxDigits = 30;

        for (let i = 0; i < maxDigits; i++) {
            fraction *= 10;
            const digit = Math.floor(fraction + 1e-12);
            fraction -= digit;
            digits += String(digit);

            if (digit !== 0 && firstNonZeroIndex === null) {
                firstNonZeroIndex = i;
            }

            if (firstNonZeroIndex !== null && i >= firstNonZeroIndex + 1) {
                break;
            }
        }

        digits = digits.replace(/0+$/, '');
        if (!digits) return '0';
        return `${sign}0.${digits}`;
    }

    function formatRupiah(num) {
        const plain = formatPlainNumber(num, 0);
        if (!plain) return '';
        const parts = plain.split('.');
        const intPart = parts[0] || '0';
        const decPart = parts[1] || '';
        const sign = intPart.startsWith('-') ? '-' : '';
        const digits = sign ? intPart.slice(1) : intPart;
        const withThousands = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        if (!decPart || /^0+$/.test(decPart)) {
            return `${sign}${withThousands}`;
        }
        return `${sign}${withThousands},${decPart}`;
    }

    // Sync Handlers
    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(packagePriceDisplay?.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        if (packagePrice) packagePrice.value = normalized || '';
        if (packagePriceDisplay) packagePriceDisplay.value = normalized ? formatRupiah(normalized) : '';

        // Trigger recalc partial (hanya update lawan)
        const net = getCurrentWeight();
        if (normalized && net > 0) {
            const calcComparison = Number(normalized) / net;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } else if (!normalized) {
             // Jika dikosongkan, kosongkan juga lawannya? Opsional.
             // if (comparisonPrice) comparisonPrice.value = '';
             // if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
        }

        isUpdatingPrice = false;
    }

    function syncComparisonFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay?.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        if (comparisonPrice) comparisonPrice.value = normalized || '';
        if (comparisonPriceDisplay) comparisonPriceDisplay.value = normalized ? formatRupiah(normalized) : '';

        const net = getCurrentWeight();
        if (normalized && net > 0) {
            const calcPrice = Number(normalized) * net;
            const calcPlain = formatPlainNumber(calcPrice, 0);
            if (packagePrice) packagePrice.value = calcPlain || '';
            if (packagePriceDisplay) packagePriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        }

        isUpdatingPrice = false;
    }

    if (packagePriceDisplay) {
        packagePriceDisplay.addEventListener('input', syncPriceFromDisplay);
        // Format initial
        if (packagePrice && packagePrice.value) {
            packagePriceDisplay.value = formatRupiah(packagePrice.value);
        }
    }

    if (comparisonPriceDisplay) {
        comparisonPriceDisplay.addEventListener('input', syncComparisonFromDisplay);
        if (comparisonPrice && comparisonPrice.value) {
            comparisonPriceDisplay.value = formatRupiah(comparisonPrice.value);
        }
    }

    // Initialize
    updateNetCalc();
    recalculatePrices();
    syncPriceUnit();


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
        const inputElement = getEl(inputId);
        const unitElement = getEl(unitId);
        const hiddenElement = getEl(hiddenId);

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
                const normalizedMeters = normalizeSmartDecimal(metersValue);
                hiddenElement.value = isNaN(normalizedMeters) ? '' : normalizedMeters.toString();
                inputElement.style.borderColor = '#e2e8f0';
            } else {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e74c3c';
            }
            calculateVolume();
        }

        inputElement.addEventListener('input', updateDimension);
        unitElement.addEventListener('change', updateDimension);

        // Initial update
        if (inputElement.value) updateDimension();
    }

    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width');
    setupDimensionInput('dimension_height_input', 'dimension_height_unit', 'dimension_height');

    // Volume Calc
    const dimensionLength = getEl('dimension_length');
    const dimensionWidth = getEl('dimension_width');
    const dimensionHeight = getEl('dimension_height');
    const volumeDisplay = getEl('volume_display');

    function calculateVolume() {
        if (!dimensionLength || !dimensionWidth || !dimensionHeight || !volumeDisplay) return;

        const lengthM = parseFloat(dimensionLength.value) || 0;
        const widthM = parseFloat(dimensionWidth.value) || 0;
        const heightM = parseFloat(dimensionHeight.value) || 0;

        if (lengthM > 0 && widthM > 0 && heightM > 0) {
            const volume = lengthM * widthM * heightM;
            const normalizedVolume = normalizeSmartDecimal(volume);
            volumeDisplay.value = formatSmartDecimal(normalizedVolume);
        } else {
            volumeDisplay.value = '';
        }
    }
    
    calculateVolume();

    // Convert comma decimals to dot decimals before form submission
    if (form && !form.__cementSubmitHandlerAttached) {
        form.__cementSubmitHandlerAttached = true;
        form.addEventListener('submit', function(e) {
            try {
                console.log('[CementForm] Form submitting, converting decimals...');

                // Convert package_weight_gross
                if (grossInput && grossInput.value) {
                    const original = grossInput.value;
                    grossInput.value = grossInput.value.replace(/,/g, '.');
                    console.log('[CementForm] Converted package_weight_gross:', original, '→', grossInput.value);
                }

                // Convert dimension fields
                if (dimensionLength && dimensionLength.value) {
                    const original = dimensionLength.value;
                    dimensionLength.value = dimensionLength.value.replace(/,/g, '.');
                    console.log('[CementForm] Converted dimension_length:', original, '→', dimensionLength.value);
                }
                if (dimensionWidth && dimensionWidth.value) {
                    const original = dimensionWidth.value;
                    dimensionWidth.value = dimensionWidth.value.replace(/,/g, '.');
                    console.log('[CementForm] Converted dimension_width:', original, '→', dimensionWidth.value);
                }
                if (dimensionHeight && dimensionHeight.value) {
                    const original = dimensionHeight.value;
                    dimensionHeight.value = dimensionHeight.value.replace(/,/g, '.');
                    console.log('[CementForm] Converted dimension_height:', original, '→', dimensionHeight.value);
                }

                console.log('[CementForm] Decimal conversion complete, form will submit');
            } catch (error) {
                console.error('[CementForm] Error in submit handler:', error);
                // Don't prevent submission even if there's an error
            }
        });
    }
}
