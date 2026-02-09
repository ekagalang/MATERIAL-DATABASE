function initCeramicForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#ceramicForm') || scope) : document;
    if (marker.__ceramicFormInited) { return; }
    marker.__ceramicFormInited = true;

    // ========== HELPER FUNCTIONS ==========

    let currentCoverage = 0;
    let isUpdatingPrice = false;
    let lastEditedPriceField = null; // 'price' or 'comparison'

    function getElement(id) {
        return (scope && scope.querySelector) ? scope.querySelector('#' + id) : document.getElementById(id);
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

    // Parse decimal value handling dot/comma and thousands separators (flexible input)
    function parseDecimal(value) {
        if (typeof value === 'number') return isFinite(value) ? value : NaN;
        if (typeof value !== 'string') return NaN;
        let str = value.trim();
        if (str === '') return NaN;

        // Remove spaces and NBSP
        str = str.replace(/[\s\u00A0]/g, '');

        let negative = false;
        if (str.startsWith('-')) {
            negative = true;
            str = str.slice(1);
        }

        const hasComma = str.includes(',');
        const hasDot = str.includes('.');

        if (hasComma && hasDot) {
            if (str.lastIndexOf(',') > str.lastIndexOf('.')) {
                // Indo: 1.234,56
                str = str.replace(/\./g, '');
                str = str.replace(/,/g, '.');
            } else {
                // US: 1,234.56
                str = str.replace(/,/g, '');
            }
        } else if (hasComma) {
            if (/^\d{1,3}(,\d{3})+$/.test(str)) {
                // US thousands with comma
                str = str.replace(/,/g, '');
            } else {
                // Comma as decimal
                str = str.replace(/,/g, '.');
            }
        } else if (hasDot) {
            if (/^\d{1,3}(\.\d{3})+$/.test(str)) {
                // Indo thousands with dot
                str = str.replace(/\./g, '');
            }
            // else dot as decimal
        }

        str = str.replace(/[^0-9.]/g, '');
        if (str === '' || str === '.') return NaN;
        const num = Number(str);
        if (!isFinite(num)) return NaN;
        return negative ? -num : num;
    }

    function formatNumberTrim(value) {
        return formatSmartDecimal(value);
    }

    function formatSuggestionNumber(raw) {
        if (raw === null || raw === undefined) return '';
        const num = Number(raw);
        if (!isFinite(num)) return String(raw);
        return formatSmartDecimal(num);
    }

    // ========== PACKAGING DROPDOWN ==========

    const packagingSelect = getElement('packaging');
    const volumeSuffixSpan = getElement('volume_suffix');
    const priceUnitSuffixSpan = getElement('price_unit_display_inline');

    function updateVolumeSuffix() {
        if (!volumeSuffixSpan) return;

        const selectedPackaging = packagingSelect?.value || '';
        volumeSuffixSpan.textContent = `Lbr / ${selectedPackaging || '-'}`;

        if (priceUnitSuffixSpan) {
            priceUnitSuffixSpan.textContent = `/ ${selectedPackaging || '-'}`;
        }
    }

    if (packagingSelect) {
        // Trigger change to update volume suffix on load
        updateVolumeSuffix();
        packagingSelect.addEventListener('change', updateVolumeSuffix);
        packagingSelect.addEventListener('input', updateVolumeSuffix);
    }

    // ========== AREA PER PIECE CALCULATION (LUAS) ==========

    const areaPerPieceDisplay = getElement('area_per_piece_display');

    function calculateAreaPerPiece() {
        const dimLength = getElement('dimension_length');
        const dimWidth = getElement('dimension_width');
        const dimLengthInput = getElement('dimension_length_input');
        const dimWidthInput = getElement('dimension_width_input');

        // Get length and width in cm
        let length = parseDecimal(dimLength?.value) || 0;
        let width = parseDecimal(dimWidth?.value) || 0;

        // If using input fields with unit selectors
        const usesUnitSelectors = !!dimLengthInput;
        if (usesUnitSelectors && !length && !width) {
            const rawLength = parseDecimal(dimLengthInput?.value);
            const rawWidth = parseDecimal(dimWidthInput?.value);
            const normalizedLength = normalizeSmartDecimal(rawLength);
            const normalizedWidth = normalizeSmartDecimal(rawWidth);
            length = (!isNaN(normalizedLength) && normalizedLength > 0) ? normalizedLength : 0;
            width = (!isNaN(normalizedWidth) && normalizedWidth > 0) ? normalizedWidth : 0;
        }

        if (length > 0 && width > 0) {
            // Convert from cm to m
            const lengthM = length / 100;
            const widthM = width / 100;

            // Calculate area per piece in M²
            const areaPerPiece = lengthM * widthM;
            const normalizedArea = normalizeSmartDecimal(areaPerPiece);

            if (areaPerPieceDisplay) {
                areaPerPieceDisplay.value = formatSmartDecimal(normalizedArea);
            }
        } else {
            if (areaPerPieceDisplay) {
                areaPerPieceDisplay.value = '';
            }
        }
    }

    // ========== PRICE SYNC FUNCTIONS ==========

    const pricePerPackage = getElement('price_per_package');
    const pricePerPackageDisplay = getElement('price_per_package_display');
    const comparisonPrice = getElement('comparison_price_per_m2');
    const comparisonPriceDisplay = getElement('comparison_price_display');

    // Handle price per package input
    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(pricePerPackageDisplay?.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        if (pricePerPackage) pricePerPackage.value = normalized || '';
        if (pricePerPackageDisplay) pricePerPackageDisplay.value = normalized ? formatRupiah(normalized) : '';

        // Mark that price was edited last
        if (raw) {
            lastEditedPriceField = 'price';
        }

        // Calculate comparison price from price per package
        if (normalized && currentCoverage > 0) {
            const calcComparison = Number(normalized) / currentCoverage;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } else if (!normalized) {
            // Clear comparison if price is cleared
            if (comparisonPrice) comparisonPrice.value = '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
        }

        isUpdatingPrice = false;
    }

    // Handle comparison price input
    function syncComparisonFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay?.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        if (comparisonPrice) comparisonPrice.value = normalized || '';
        if (comparisonPriceDisplay) comparisonPriceDisplay.value = normalized ? formatRupiah(normalized) : '';

        // Mark that comparison was edited last
        if (raw) {
            lastEditedPriceField = 'comparison';
        }

        // Calculate price per package from comparison price
        if (normalized && currentCoverage > 0) {
            const calcPrice = Number(normalized) * currentCoverage;
            const calcPlain = formatPlainNumber(calcPrice, 0);
            if (pricePerPackage) pricePerPackage.value = calcPlain || '';
            if (pricePerPackageDisplay) pricePerPackageDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } else if (!normalized) {
            // Clear price if comparison is cleared
            if (pricePerPackage) pricePerPackage.value = '';
            if (pricePerPackageDisplay) pricePerPackageDisplay.value = '';
        }

        isUpdatingPrice = false;
    }

    function recalculatePrices() {
        if (currentCoverage <= 0) return;

        const priceValue = parseDecimal(pricePerPackage?.value) || 0;
        const compValue = parseDecimal(comparisonPrice?.value) || 0;

        // Recalculate based on which field was edited last
        if (lastEditedPriceField === 'price' && priceValue > 0) {
            const calcComparison = priceValue / currentCoverage;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } else if (lastEditedPriceField === 'comparison' && compValue > 0) {
            const calcPrice = compValue * currentCoverage;
            const calcPlain = formatPlainNumber(calcPrice, 0);
            if (pricePerPackage) pricePerPackage.value = calcPlain || '';
            if (pricePerPackageDisplay) pricePerPackageDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        } else {
            if (priceValue > 0) {
                const calcComparison = priceValue / currentCoverage;
                const calcPlain = formatPlainNumber(calcComparison, 0);
                if (comparisonPrice) comparisonPrice.value = calcPlain || '';
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
            } else if (compValue > 0) {
                const calcPrice = compValue * currentCoverage;
                const calcPlain = formatPlainNumber(calcPrice, 0);
                if (pricePerPackage) pricePerPackage.value = calcPlain || '';
                if (pricePerPackageDisplay) pricePerPackageDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
            }
        }
    }

    // ========== COVERAGE CALCULATION ==========

    const dimLength = getElement('dimension_length');
    const dimWidth = getElement('dimension_width');
    const dimLengthInput = getElement('dimension_length_input');
    const dimWidthInput = getElement('dimension_width_input');
    const piecesPerPackage = getElement('pieces_per_package');
    const coverageDisplay = getElement('coverage_display');
    const coverageCalculationDisplay = getElement('coverage_calculation_display');
    const coveragePerPackage = getElement('coverage_per_package');

    function calculateCoverage() {
        // Get dimension values
        let length = parseDecimal(dimLength?.value) || 0;
        let width = parseDecimal(dimWidth?.value) || 0;
        let pieces = parseDecimal(piecesPerPackage?.value) || 0;

        // If using unit selectors
        const usesUnitSelectors = !!dimLengthInput;
        if (usesUnitSelectors && !length && !width) {
            const rawLength = parseDecimal(dimLengthInput?.value);
            const rawWidth = parseDecimal(dimWidthInput?.value);
            const normalizedLength = normalizeSmartDecimal(rawLength);
            const normalizedWidth = normalizeSmartDecimal(rawWidth);

            if (dimLength) dimLength.value = (!isNaN(normalizedLength) && normalizedLength >= 0) ? normalizedLength.toString() : '';
            if (dimWidth) dimWidth.value = (!isNaN(normalizedWidth) && normalizedWidth >= 0) ? normalizedWidth.toString() : '';

            length = (!isNaN(normalizedLength) && normalizedLength > 0) ? normalizedLength : 0;
            width = (!isNaN(normalizedWidth) && normalizedWidth > 0) ? normalizedWidth : 0;
        }

        // Calculate area per piece first
        calculateAreaPerPiece();

        if (length > 0 && width > 0 && pieces > 0) {
            // Convert from CM to M
            const lengthM = length / 100;
            const widthM = width / 100;

            // Area per piece
            const areaPerPiece = lengthM * widthM;

            // Total coverage = area per piece × pieces
            const coverage = areaPerPiece * pieces;
            const normalizedCoverage = normalizeSmartDecimal(coverage);
            currentCoverage = normalizedCoverage;

            const coverageText = formatSmartDecimal(normalizedCoverage);
            if (coverageDisplay) {
                coverageDisplay.textContent = coverageText + ' M²';
                coverageDisplay.style.color = '#27ae60';
            }
            if (coveragePerPackage) {
                coveragePerPackage.value = isNaN(normalizedCoverage) ? '' : normalizedCoverage.toString();
            }
            if (coverageCalculationDisplay) {
                coverageCalculationDisplay.textContent =
                    `(${formatNumberTrim(length)}/100 × ${formatNumberTrim(width)}/100) × ${pieces} = ${coverageText} M²`;
            }

            // Recalculate prices when coverage changes
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentCoverage = 0;
            if (coverageDisplay) {
                coverageDisplay.textContent = '-';
                coverageDisplay.style.color = '#15803d';
            }
            if (coveragePerPackage) {
                coveragePerPackage.value = '';
            }
            if (coverageCalculationDisplay) {
                coverageCalculationDisplay.textContent = '-';
            }
        }
    }

    // ========== UNIT CONVERSION ==========

    function convertToCm(value, unit) {
        const num = parseDecimal(value);
        if (isNaN(num) || num < 0) {
            return null;
        }

        switch(unit) {
            case 'mm':
                return num / 10;
            case 'cm':
                return num;
            case 'm':
                return num * 100;
            default:
                return num;
        }
    }

    function setupDimensionInput(inputId, unitId, hiddenId) {
        const inputElement = getElement(inputId);
        const unitElement = getElement(unitId);
        const hiddenElement = getElement(hiddenId);

        if (!inputElement || !unitElement || !hiddenElement) return;

        function updateDimension() {
            const rawValue = inputElement.value.trim();
            const selectedUnit = unitElement.value;

            if (rawValue === '') {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e2e8f0';
                calculateCoverage();
                return;
            }

            const cmValue = convertToCm(rawValue, selectedUnit);

            if (cmValue !== null) {
                const normalizedCm = normalizeSmartDecimal(cmValue);
                hiddenElement.value = isNaN(normalizedCm) ? '' : normalizedCm.toString();
                inputElement.style.borderColor = '#e2e8f0';
            } else {
                hiddenElement.value = '';
                inputElement.style.borderColor = '#e74c3c';
            }

            calculateCoverage();
        }

        inputElement.addEventListener('input', updateDimension);
        unitElement.addEventListener('change', updateDimension);

        inputElement.addEventListener('blur', function() {
            const rawValue = this.value.trim();
            if (rawValue !== '') {
                const num = parseDecimal(rawValue);
                if (!isNaN(num) && num >= 0) {
                    this.value = formatSmartDecimal(num);
                }
            }
        });

        // Initial update
        if (inputElement.value) updateDimension();
    }

    // Setup dimension inputs with unit selectors
    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width');
    setupDimensionInput('dimension_thickness_input', 'dimension_thickness_unit', 'dimension_thickness');

    // Ensure hidden fields are synced before submit
    function syncDimensionHidden(inputId, unitId, hiddenId) {
        const inputElement = getElement(inputId);
        const unitElement = getElement(unitId);
        const hiddenElement = getElement(hiddenId);
        if (!inputElement || !hiddenElement) return;

        const rawValue = (inputElement.value || '').trim();
        if (!rawValue) return;

        const cmValue = unitElement ? convertToCm(rawValue, unitElement.value) : parseDecimal(rawValue);
        if (cmValue !== null && !isNaN(cmValue)) {
            const normalizedCm = normalizeSmartDecimal(cmValue);
            hiddenElement.value = isNaN(normalizedCm) ? '' : normalizedCm.toString();
        }
    }

    const ceramicForm = marker.matches && marker.matches('form') ? marker : marker.querySelector('#ceramicForm');
    if (ceramicForm) {
        ceramicForm.addEventListener('submit', function() {
            syncDimensionHidden('dimension_length_input', 'dimension_length_unit', 'dimension_length');
            syncDimensionHidden('dimension_width_input', 'dimension_width_unit', 'dimension_width');
            syncDimensionHidden('dimension_thickness_input', 'dimension_thickness_unit', 'dimension_thickness');
            calculateCoverage();

            const priceDisplayValue = (pricePerPackageDisplay?.value || '').trim();
            const comparisonDisplayValue = (comparisonPriceDisplay?.value || '').trim();
            if (!priceDisplayValue && comparisonDisplayValue) {
                syncComparisonFromDisplay();
            } else {
                syncPriceFromDisplay();
            }
        });
    }

    // ========== AUTO-SUGGEST ==========

    const autosuggestInputs = (scope && scope.querySelectorAll) ? scope.querySelectorAll('.autocomplete-input') : document.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        if (!field) { return; }

        // Skip store and address fields - handled by store-autocomplete.js
        if (field === 'store' || field === 'address') {
            return;
        }

        const suggestList = getElement(`${field}-list`);
        let debounceTimer;
        let isSelectingFromAutosuggest = false;

        function populate(values) {
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';

                    // Format display untuk field tertentu
                    let displayValue = v;
                    if (field === 'price_per_package' || field === 'comparison_price_per_m2') {
                        displayValue = 'Rp ' + formatRupiah(v);
                    } else if (['dimension_length', 'dimension_width', 'dimension_thickness'].includes(field)) {
                        displayValue = formatSuggestionNumber(v);
                    }

                    item.textContent = displayValue;
                    item.addEventListener('click', function() {
                        isSelectingFromAutosuggest = true;
                        // Handle special fields dengan kalkulasi
                        if (field === 'price_per_package') {
                            input.value = formatRupiah(v);
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (field === 'comparison_price_per_m2') {
                            input.value = formatRupiah(v);
                            if (typeof syncComparisonFromDisplay === 'function') {
                                syncComparisonFromDisplay();
                            }
                        } else if (field === 'packaging') {
                            input.value = v;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        } else if (field === 'pieces_per_package') {
                            input.value = v;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        } else if (['dimension_length', 'dimension_width', 'dimension_thickness'].includes(field)) {
                            input.value = formatSuggestionNumber(v);
                            input.dispatchEvent(new Event('input', { bubbles: true }));

                            // Auto-update unit selector to 'cm'
                            const unitSelectorId = field + '_unit';
                            const unitSelector = getElement(unitSelectorId);
                            if (unitSelector && unitSelector.value !== 'cm') {
                                unitSelector.value = 'cm';
                                unitSelector.dispatchEvent(new Event('change'));
                            }
                        } else {
                            input.value = v;
                        }
                        suggestList.style.display = 'none';
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

            const typeInput = getElement('type');
            const typeValue = (typeInput?.value || '').trim();
            const brandInput = getElement('brand');
            const brandValue = (brandInput?.value || '').trim();
            const packagingInput = getElement('packaging');
            const packagingValue = (packagingInput?.value || '').trim();

            // Fields that MUST be filtered by brand (rule-based).
            // If brand isn't filled yet, do not show suggestions.
            const brandFilteredFields = new Set([
                'sub_brand',
                'color',
                'code',
                'form',
                'surface',
                'pieces_per_package',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
            ]);
            if (brandFilteredFields.has(field) && !brandValue) {
                populate([]);
                return;
            }

            // Fields that MUST be filtered by packaging (rule-based).
            // If packaging isn't filled yet, do not show suggestions.
            const packagingFilteredFields = new Set([
                'price_per_package',
                'comparison_price_per_m2',
            ]);
            if (packagingFilteredFields.has(field) && !packagingValue) {
                populate([]);
                return;
            }

            if (field === 'store') {
                const materialType = (term === '' || term.length === 0) ? 'ceramic' : 'all';
                url = `/api/ceramics/all-stores?search=${encodeURIComponent(term)}&limit=20&material_type=${materialType}`;
            } else if (field === 'address') {
                const storeInput = getElement('store');
                if (storeInput && storeInput.value) {
                    url = `/api/ceramics/addresses-by-store?search=${encodeURIComponent(term)}&limit=20&store=${encodeURIComponent(storeInput.value)}`;
                } else {
                    populate([]);
                    return;
                }
            } else {
                url = `/api/ceramics/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;

                // Filter brand by selected type (rule-based)
                if (field === 'brand' && typeValue) {
                    url += `&type=${encodeURIComponent(typeValue)}`;
                }

                // Filter by brand (rule-based)
                if (brandFilteredFields.has(field) && brandValue) {
                    url += `&brand=${encodeURIComponent(brandValue)}`;
                }

                // Filter price by packaging (rule-based)
                if (packagingFilteredFields.has(field) && packagingValue) {
                    url += `&packaging=${encodeURIComponent(packagingValue)}`;
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

    // ========== PHOTO UPLOAD ==========

    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoPreviewArea = document.getElementById('photoPreviewArea');
    const uploadBtn = document.getElementById('uploadBtn');
    const deletePhotoBtn = document.getElementById('deletePhotoBtn');

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

                        // Adjust preview area height based on image aspect ratio
                        const img = new Image();
                        img.onload = function() {
                            if (photoPreviewArea) {
                                const containerWidth = photoPreviewArea.offsetWidth;
                                const aspectRatio = img.height / img.width;

                                let idealHeight = containerWidth * aspectRatio;
                                const minHeight = 200;
                                const maxHeight = 400;
                                idealHeight = Math.max(minHeight, Math.min(maxHeight, idealHeight));

                                photoPreviewArea.style.height = idealHeight + 'px';
                            }
                        };
                        img.src = e.target.result;
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
            if (photoPreviewArea) photoPreviewArea.style.height = '320px';
            deletePhotoBtn.style.display = 'none';
        });
    }

    // ========== EVENT LISTENERS ==========

    // Attach price display handlers
    pricePerPackageDisplay?.addEventListener('input', syncPriceFromDisplay);
    comparisonPriceDisplay?.addEventListener('input', syncComparisonFromDisplay);

    // Attach pieces input handler
    if (piecesPerPackage) {
        piecesPerPackage.addEventListener('input', calculateCoverage);
    }

    // ========== INITIALIZATION ==========

    // Format existing values on load
    if (pricePerPackageDisplay && pricePerPackage && pricePerPackage.value) {
        pricePerPackageDisplay.value = formatRupiah(pricePerPackage.value);
    }
    if (comparisonPriceDisplay && comparisonPrice && comparisonPrice.value) {
        comparisonPriceDisplay.value = formatRupiah(comparisonPrice.value);
    }

    // Initial calculations
    calculateCoverage();
    calculateAreaPerPiece();
}

// Auto-init on DOM ready if not in modal context
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('ceramicForm')) {
                initCeramicForm(document);
            }
        });
    } else {
        if (document.getElementById('ceramicForm')) {
            initCeramicForm(document);
        }
    }
}
