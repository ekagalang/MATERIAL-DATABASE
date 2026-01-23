function initSandForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#sandForm') || scope) : document;
    if (marker.__sandFormInited) { return; }
    marker.__sandFormInited = true;

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
                            input.value = formatRupiah(v);
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (field === 'comparison_price_per_m3') {
                            // Handle comparison price field - format and trigger sync
                            input.value = formatRupiah(v);
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
            else if (field === 'address') {
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

    // ========== KALKULASI VOLUME DAN HARGA ==========
    // NOTE: These must be declared BEFORE setupDimensionInput because calculateVolume() uses them

    const dimLength = scope.querySelector('#dimension_length') || document.getElementById('dimension_length');
    const dimWidth = scope.querySelector('#dimension_width') || document.getElementById('dimension_width');
    const dimHeight = scope.querySelector('#dimension_height') || document.getElementById('dimension_height');

    const volumeDisplay = scope.querySelector('#volume_display') || document.getElementById('volume_display');
    const packageVolume = scope.querySelector('#package_volume') || document.getElementById('package_volume');
    const packagePrice = scope.querySelector('#package_price') || document.getElementById('package_price');
    const packagePriceDisplay = scope.querySelector('#package_price_display') || document.getElementById('package_price_display');
    const comparisonPrice = scope.querySelector('#comparison_price_per_m3') || document.getElementById('comparison_price_per_m3');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');

    // Debug: Log element detection
    console.log('[SandForm] dimLength found:', !!dimLength);
    console.log('[SandForm] dimWidth found:', !!dimWidth);
    console.log('[SandForm] dimHeight found:', !!dimHeight);
    console.log('[SandForm] packagePrice found:', !!packagePrice);
    console.log('[SandForm] packagePriceDisplay found:', !!packagePriceDisplay);
    console.log('[SandForm] comparisonPrice found:', !!comparisonPrice);
    console.log('[SandForm] comparisonPriceDisplay found:', !!comparisonPriceDisplay);

    let currentVolume = 0;
    let isUpdatingPrice = false;

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

    function calculateVolume() {
        const lengthM = parseFloat(dimLength?.value) || 0;
        const widthM = parseFloat(dimWidth?.value) || 0;
        const heightM = parseFloat(dimHeight?.value) || 0;

        if (lengthM > 0 && widthM > 0 && heightM > 0) {
            // Already in meters, just multiply to get M3
            const volumeM3 = lengthM * widthM * heightM;
            const normalizedVolume = normalizeSmartDecimal(volumeM3);
            currentVolume = normalizedVolume;
            formatValuesWithHelper([
                { key: 'volumeM3', value: normalizedVolume },
            ]).then((formatted) => {
                const volumeText = formatted.volumeM3?.formatted || '';
                if (volumeDisplay) {
                    volumeDisplay.value = volumeText;
                }
                // Also update the hidden package_volume field
                if (packageVolume) {
                    packageVolume.value = formatted.volumeM3?.plain || '';
                }
            });
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentVolume = 0;
            if (volumeDisplay) {
                volumeDisplay.value = '';
            }
            if (packageVolume) {
                packageVolume.value = '';
            }
        }
    }

    function recalculatePrices() {
        const priceValue = parseFloat(packagePrice?.value) || 0;
        const compValue = parseFloat(comparisonPrice?.value) || 0;
        if (priceValue > 0 && currentVolume > 0) {
            const calcComparison = priceValue / currentVolume;
            applyPriceFormatting(priceValue, calcComparison);
        } else if (compValue > 0 && currentVolume > 0) {
            const calcPrice = compValue * currentVolume;
            applyPriceFormatting(calcPrice, compValue);
        }
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

    function formatValuesWithHelper(items) {
        if (window.NumberHelperClient && typeof window.NumberHelperClient.formatValues === 'function') {
            return window.NumberHelperClient.formatValues(items);
        }

        const results = {};
        items.forEach((item) => {
            const value = item.value;
            if (value === null || value === undefined || value === '') {
                results[item.key] = { formatted: '', plain: '', normalized: 0 };
                return;
            }
            const num = Number(value);
            if (!isFinite(num)) {
                results[item.key] = { formatted: '', plain: '', normalized: 0 };
                return;
            }
            const decimals = item.decimals;
            let formatted = '';
            let plain = '';
            if (decimals === 0) {
                formatted = formatRupiah(num);
                plain = formatPlainNumber(num, 0);
            } else {
                formatted = formatSmartDecimal(num);
                plain = formatDynamicPlain(num);
            }
            results[item.key] = { formatted, plain, normalized: num };
        });
        return Promise.resolve(results);
    }

    function applyPriceFormatting(priceValue, comparisonValue) {
        return formatValuesWithHelper([
            { key: 'price', value: priceValue, decimals: 0 },
            { key: 'comparison', value: comparisonValue, decimals: 0 },
        ]).then((formatted) => {
            if (packagePrice) packagePrice.value = formatted.price?.plain || '';
            if (packagePriceDisplay) packagePriceDisplay.value = formatted.price?.formatted || '';
            if (comparisonPrice) comparisonPrice.value = formatted.comparison?.plain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatted.comparison?.formatted || '';
        });
    }

    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(packagePriceDisplay?.value || '');
        const hasValue = raw !== '';
        const numericPrice = hasValue ? Number(raw) : null;

        if (!hasValue || !isFinite(numericPrice)) {
            if (packagePrice) packagePrice.value = '';
            if (packagePriceDisplay) packagePriceDisplay.value = '';
            if (comparisonPrice) comparisonPrice.value = '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
            isUpdatingPrice = false;
            return;
        }

        if (currentVolume > 0) {
            const calcComparison = numericPrice / currentVolume;
            applyPriceFormatting(numericPrice, calcComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        applyPriceFormatting(numericPrice, null).finally(() => {
            isUpdatingPrice = false;
        });
    }

    function syncComparisonFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay?.value || '');
        const hasValue = raw !== '';
        const numericComparison = hasValue ? Number(raw) : null;

        if (!hasValue || !isFinite(numericComparison)) {
            if (comparisonPrice) comparisonPrice.value = '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
            if (packagePrice) packagePrice.value = '';
            if (packagePriceDisplay) packagePriceDisplay.value = '';
            isUpdatingPrice = false;
            return;
        }

        if (currentVolume > 0) {
            const calcPrice = numericComparison * currentVolume;
            applyPriceFormatting(calcPrice, numericComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        applyPriceFormatting(null, numericComparison).finally(() => {
            isUpdatingPrice = false;
        });
    }

    packagePriceDisplay?.addEventListener('input', syncPriceFromDisplay);
    comparisonPriceDisplay?.addEventListener('input', syncComparisonFromDisplay);

    // Also sync on blur to ensure value is saved when user clicks away
    packagePriceDisplay?.addEventListener('blur', syncPriceFromDisplay);
    comparisonPriceDisplay?.addEventListener('blur', syncComparisonFromDisplay);

    if ((packagePrice && packagePrice.value) || (comparisonPrice && comparisonPrice.value)) {
        const priceValue = packagePrice?.value ? Number(packagePrice.value) : null;
        const comparisonValue = comparisonPrice?.value ? Number(comparisonPrice.value) : null;
        applyPriceFormatting(priceValue, comparisonValue);
    }

    // Add form submit handler to sync all values before submission
    const sandForm = scope.querySelector('#sandForm') || document.getElementById('sandForm');
    if (sandForm && !sandForm.__sandFormSubmitHandled) {
        sandForm.__sandFormSubmitHandled = true;
        sandForm.addEventListener('submit', function(e) {
            console.log('[SandForm] Form submitting, syncing values...');

            // Sync price fields before submit
            if (packagePriceDisplay && packagePrice) {
                const rawPrice = unformatRupiah(packagePriceDisplay.value || '');
                const normalized = rawPrice ? formatPlainNumber(rawPrice, 0) : '';
                packagePrice.value = normalized || '';
                console.log('[SandForm] Synced package_price:', packagePrice.value);
            }

            if (comparisonPriceDisplay && comparisonPrice) {
                const rawComparison = unformatRupiah(comparisonPriceDisplay.value || '');
                const normalized = rawComparison ? formatPlainNumber(rawComparison, 0) : '';
                comparisonPrice.value = normalized || '';
                console.log('[SandForm] Synced comparison_price:', comparisonPrice.value);
            }

            // Sync volume field before submit
            if (packageVolume) {
                packageVolume.value = currentVolume > 0 ? formatDynamicPlain(currentVolume) : '';
                console.log('[SandForm] Synced package_volume:', packageVolume.value);
            }

            // Log all hidden field values
            console.log('[SandForm] Final form values:');
            console.log('  - package_price:', packagePrice?.value);
            console.log('  - comparison_price_per_m3:', comparisonPrice?.value);
            console.log('  - package_volume:', packageVolume?.value);
            console.log('  - dimension_length:', dimLength?.value);
            console.log('  - dimension_width:', dimWidth?.value);
            console.log('  - dimension_height:', dimHeight?.value);
        });
    }

    calculateVolume();
}
