function initBrickForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#brickForm') || scope) : document;
    if (marker.__brickFormInited) { return; }
    marker.__brickFormInited = true;

    // ========== PRICE SYNC FUNCTIONS (must be defined before autocomplete) ==========

    let currentVolume = 0;
    let isUpdatingPrice = false;
    let lastEditedPriceField = null; // Track which field was edited last: 'price' or 'comparison'

    // Helper function for element selection
    function getElement(id) {
        return (scope && scope.querySelector) ? scope.querySelector('#' + id) : document.getElementById(id);
    }

    const pricePerPiece = getElement('price_per_piece');
    const pricePerPieceDisplay = getElement('price_per_piece_display');
    const comparisonPrice = getElement('comparison_price_per_m3');
    const comparisonPriceDisplay = getElement('comparison_price_display');
    const packageType = getElement('package_type');
    const packageTypeDisplay = getElement('package_type_display');
    const packageTypeList = getElement('package_type-list');
    const packageQtyDisplay = getElement('package_qty_display');
    const packageQtySuffix = getElement('package_qty_suffix');
    const pricePerPieceSuffix = getElement('price_per_piece_suffix');
    const comparisonPriceSuffix = getElement('comparison_price_suffix');

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

    function formatVolumePlain(value) {
        if (value === '' || value === null || value === undefined) return '';
        const num = Number(value);
        if (!isFinite(num)) return '';
        return num.toLocaleString('en-US', {
            useGrouping: false,
            maximumFractionDigits: 20,
        });
    }

    function formatVolumeDisplay(value) {
        const plain = formatVolumePlain(value);
        return plain || '';
    }

    function normalizeVolumePrecision(value) {
        const num = Number(value);
        if (!isFinite(num)) return NaN;

        // Keep high precision for volume (similar to NumberHelper::formatPlain default behavior).
        const precisionFactor = 10 ** 11;
        const rounded = Math.round((num + Number.EPSILON) * precisionFactor) / precisionFactor;
        return rounded;
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
            if (pricePerPiece) pricePerPiece.value = formatted.price?.plain || '';
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = formatted.price?.formatted || '';
            if (comparisonPrice) comparisonPrice.value = formatted.comparison?.plain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = formatted.comparison?.formatted || '';

            // Kubik mode: display "Harga Beli" sebagai harga per M3 (nilai comparison).
            if (getPackageTypeMode() === 'kubik' && pricePerPieceDisplay) {
                pricePerPieceDisplay.value = formatted.comparison?.formatted || '';
            }
        });
    }

    function getPackageTypeMode() {
        return packageType?.value === 'kubik' ? 'kubik' : 'eceran';
    }

    function packageTypeLabel(value) {
        return value === 'kubik' ? 'Kubik' : 'Eceran';
    }

    function updatePackageQtyPreview() {
        if (!packageQtyDisplay) return;

        const isKubik = getPackageTypeMode() === 'kubik';
        let qtyValue = '-';

        if (!isKubik) {
            qtyValue = '1';
        } else if (currentVolume > 0) {
            qtyValue = formatPlainNumber(Math.floor(1 / currentVolume), 0) || '0';
        }

        packageQtyDisplay.value = qtyValue;
        if (packageQtySuffix) {
            packageQtySuffix.textContent = 'Bh';
        }
    }

    function normalizePackageType(value) {
        const normalized = (value || '').toString().trim().toLowerCase();
        if (normalized === 'kubik' || normalized === 'm3') return 'kubik';
        if (normalized === 'eceran' || normalized === 'buah' || normalized === 'bh') return 'eceran';
        return '';
    }

    function initPackageTypeDropdown() {
        if (!packageType || !packageTypeDisplay || !packageTypeList) return;

        const setPackageType = (value, dispatch = true, updateDisplay = true) => {
            const normalized = normalizePackageType(value);
            const previous = packageType.value || '';
            packageType.value = normalized;

            if (updateDisplay && normalized) {
                packageTypeDisplay.value = packageTypeLabel(normalized);
            }

            if (dispatch && previous !== normalized) {
                packageType.dispatchEvent(new Event('change', { bubbles: true }));
            }
        };

        const initialValue = normalizePackageType(packageType.value || packageTypeDisplay.value) || 'eceran';
        setPackageType(initialValue, false, true);

        const closeList = () => {
            packageTypeList.classList.remove('package-open');
            packageTypeList.style.setProperty('display', 'none', 'important');
        };

        const openList = () => {
            packageTypeList.classList.add('package-open');
            packageTypeList.style.setProperty('display', 'block', 'important');
        };

        const getItems = () => Array.from(packageTypeList.querySelectorAll('.autocomplete-item[data-value]'));

        const filterItems = (term = '') => {
            const normalizedTerm = term.toString().trim().toLowerCase();
            const items = getItems();
            let visibleCount = 0;

            items.forEach(item => {
                const label = (item.textContent || '').toLowerCase();
                const value = (item.getAttribute('data-value') || '').toLowerCase();
                const isVisible = normalizedTerm === '' || label.includes(normalizedTerm) || value.includes(normalizedTerm);
                item.style.display = isVisible ? 'block' : 'none';
                if (isVisible) visibleCount += 1;
            });

            if (visibleCount > 0) {
                openList();
            } else {
                closeList();
            }
        };

        const selectPackageType = (value) => {
            setPackageType(value, true, true);
            closeList();
        };

        packageTypeDisplay.addEventListener('click', function(e) {
            e.stopPropagation();
            filterItems('');
        });

        packageTypeDisplay.addEventListener('focus', function() {
            filterItems('');
        });

        packageTypeDisplay.addEventListener('input', function() {
            const typed = packageTypeDisplay.value || '';
            const normalized = normalizePackageType(typed);
            if (normalized) {
                setPackageType(normalized, true, false);
            } else if (!typed.trim()) {
                setPackageType('', true, false);
            }
            filterItems(typed);
        });

        packageTypeDisplay.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeList();
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                filterItems('');
                return;
            }

            if (e.key === 'Enter') {
                const firstVisible = getItems().find(item => item.style.display !== 'none');
                if (firstVisible) {
                    e.preventDefault();
                    selectPackageType(firstVisible.getAttribute('data-value'));
                }
            }
        });

        packageTypeDisplay.addEventListener('blur', function() {
            window.setTimeout(() => {
                const normalized = normalizePackageType(packageTypeDisplay.value);
                if (normalized) {
                    setPackageType(normalized, true, true);
                } else if (!packageTypeDisplay.value.trim()) {
                    setPackageType('', true, false);
                } else if (packageType.value) {
                    packageTypeDisplay.value = packageTypeLabel(packageType.value);
                }
                closeList();
            }, 120);
        });

        getItems().forEach(item => {
            item.addEventListener('click', function() {
                selectPackageType(item.getAttribute('data-value'));
            });
        });

        document.addEventListener('click', function(e) {
            if (!packageTypeList.contains(e.target) && e.target !== packageTypeDisplay) {
                closeList();
            }
        });
    }

    function syncPackageTypeUi() {
        const isKubik = getPackageTypeMode() === 'kubik';
        if (pricePerPieceSuffix) {
            pricePerPieceSuffix.textContent = isKubik ? '/ M3' : '/ Buah';
        }
        if (comparisonPriceSuffix) {
            comparisonPriceSuffix.textContent = '/ M3';
        }
        if (comparisonPriceDisplay) {
            comparisonPriceDisplay.readOnly = isKubik;
            comparisonPriceDisplay.style.background = isKubik ? '#f8fafc' : '';
            comparisonPriceDisplay.style.cursor = isKubik ? 'not-allowed' : '';
        }
        updatePackageQtyPreview();
    }

    // Handle price per piece input
    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(pricePerPieceDisplay?.value || '');
        const numericPrice = raw ? Number(raw) : null;
        const isKubik = getPackageTypeMode() === 'kubik';

        // Mark that price was edited last
        if (raw) {
            lastEditedPriceField = 'price';
        }

        // Kubik: harga beli utama adalah per M3 (sama dengan comparison)
        if (isKubik && numericPrice) {
            const calcComparison = numericPrice;
            const calcPrice = currentVolume > 0 ? numericPrice * currentVolume : null;
            applyPriceFormatting(calcPrice, calcComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        // Eceran: harga beli utama adalah per buah
        if (!isKubik && numericPrice && currentVolume > 0) {
            const calcComparison = numericPrice / currentVolume;
            applyPriceFormatting(numericPrice, calcComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        if (!numericPrice) {
            if (pricePerPiece) pricePerPiece.value = '';
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = '';
            if (comparisonPrice) comparisonPrice.value = '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
        } else if (!isKubik) {
            applyPriceFormatting(numericPrice, null).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        } else {
            applyPriceFormatting(null, numericPrice).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        isUpdatingPrice = false;
    }

    // Handle comparison price input
    function syncComparisonFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay?.value || '');
        const numericComparison = raw ? Number(raw) : null;
        const isKubik = getPackageTypeMode() === 'kubik';

        // Mark that comparison was edited last
        if (raw) {
            lastEditedPriceField = 'comparison';
        }

        // Calculate price per piece from comparison price
        if (numericComparison && currentVolume > 0) {
            const calcPrice = numericComparison * currentVolume;
            applyPriceFormatting(calcPrice, numericComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        if (numericComparison && isKubik) {
            applyPriceFormatting(null, numericComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        if (!numericComparison) {
            if (comparisonPrice) comparisonPrice.value = '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = '';
            if (pricePerPiece) pricePerPiece.value = '';
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = '';
        } else {
            applyPriceFormatting(null, numericComparison).finally(() => {
                isUpdatingPrice = false;
            });
            return;
        }

        isUpdatingPrice = false;
    }

    // Auto-suggest
    const autosuggestInputs = (scope && scope.querySelectorAll) ? scope.querySelectorAll('.autocomplete-input') : document.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        if (!field) {
            return;
        }

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
                    if (field === 'price_per_piece') {
                        displayValue = 'Rp ' + formatRupiah(v);
                    }

                    item.textContent = displayValue;
                    item.addEventListener('click', function() {
                        isSelectingFromAutosuggest = true;
                        // Handle special fields dengan kalkulasi
                        if (field === 'price_per_piece') {
                            // Update display field dengan format Rupiah
                            input.value = formatRupiah(v);
                            // Trigger price sync
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (['dimension_length', 'dimension_width', 'dimension_height'].includes(field)) {
                            // Update dimension input
                            input.value = v;

                            // Trigger input event to update hidden field via setupDimensionInput
                            input.dispatchEvent(new Event('input', { bubbles: true }));

                            // Auto-update unit selector to 'cm' (data dari database dalam cm)
                            const unitSelectorId = field + '_unit';
                            const unitSelector = getElement(unitSelectorId);
                            if (unitSelector && unitSelector.value !== 'cm') {
                                unitSelector.value = 'cm';
                                // Trigger change event untuk update perhitungan
                                unitSelector.dispatchEvent(new Event('change'));
                            }

                            // Note: calculateVolume() will be triggered by the input event above
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

            // Special case: store field menggunakan endpoint all-stores
            if (field === 'store') {
                // Jika tidak ada search term (user baru focus), tampilkan dari brick saja
                // Jika ada search term (user mengetik), tampilkan dari semua material
                const materialType = (term === '' || term.length === 0) ? 'brick' : 'all';
                url = `/api/bricks/all-stores?search=${encodeURIComponent(term)}&limit=20&material_type=${materialType}`;
            }
            // Special case: address field menggunakan endpoint addresses-by-store
            else if (field === 'address') {
                const storeInput = getElement('store');
                if (storeInput && storeInput.value) {
                    url = `/api/bricks/addresses-by-store?search=${encodeURIComponent(term)}&limit=20&store=${encodeURIComponent(storeInput.value)}`;
                } else {
                    // Jika toko belum dipilih, gunakan field-values biasa
                    url = `/api/bricks/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
                }
            }
            else {
                // Build URL with filter parameters
                url = `/api/bricks/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;

                // Filter bentuk by merek
                if (field === 'form') {
                    const brandInput = getElement('brand');
                    if (brandInput && brandInput.value) {
                        url += `&brand=${encodeURIComponent(brandInput.value)}`;
                    }
                }

                // Filter dimensi by merek
                if (['dimension_length', 'dimension_width', 'dimension_height'].includes(field)) {
                    const brandInput = getElement('brand');
                    if (brandInput && brandInput.value) {
                        url += `&brand=${encodeURIComponent(brandInput.value)}`;
                    }
                }

                // Filter harga by dimensi
                if (field === 'price_per_piece') {
                    const dimLength = getElement('dimension_length');
                    const dimWidth = getElement('dimension_width');
                    const dimHeight = getElement('dimension_height');

                    if (dimLength && dimLength.value) {
                        url += `&dimension_length=${encodeURIComponent(dimLength.value)}`;
                    }
                    if (dimWidth && dimWidth.value) {
                        url += `&dimension_width=${encodeURIComponent(dimWidth.value)}`;
                    }
                    if (dimHeight && dimHeight.value) {
                        url += `&dimension_height=${encodeURIComponent(dimHeight.value)}`;
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

    // Photo upload functionality
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

                                // Calculate ideal height based on aspect ratio
                                let idealHeight = containerWidth * aspectRatio;

                                // Constrain to min/max bounds
                                const minHeight = 200;
                                const maxHeight = 400;
                                idealHeight = Math.max(minHeight, Math.min(maxHeight, idealHeight));

                                // Apply the calculated height with smooth transition
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
            // Reset height to default
            if (photoPreviewArea) photoPreviewArea.style.height = '320px';
            deletePhotoBtn.style.display = 'none';
        });
    }

    // ========== KALKULASI VOLUME DAN HARGA (must be defined before dimension setup) ==========

    const dimLength = getElement('dimension_length');
    const dimWidth = getElement('dimension_width');
    const dimHeight = getElement('dimension_height');
    const dimLengthInput = getElement('dimension_length_input');
    const dimWidthInput = getElement('dimension_width_input');
    const dimHeightInput = getElement('dimension_height_input');
    const dimLengthUnit = getElement('dimension_length_unit');
    const dimWidthUnit = getElement('dimension_width_unit');
    const dimHeightUnit = getElement('dimension_height_unit');

    const volumeDisplay = getElement('volume_display');
    const volumeDisplayInput = getElement('volume_display_input');
    const volumeCalculationDisplay = getElement('volume_calculation_display');
    const packageVolume = getElement('package_volume');

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

    function calculateVolume() {
        // Default: pakai hidden (edit form)
        let length = parseDecimal(dimLength?.value) || 0;
        let width = parseDecimal(dimWidth?.value) || 0;
        let height = parseDecimal(dimHeight?.value) || 0;

        // Jika tidak ada unit selector, berarti create form (angka input langsung cm)
        const usesUnitSelectors = !!(dimLengthUnit || dimWidthUnit || dimHeightUnit);
        if (!usesUnitSelectors) {
            const rawLength = parseDecimal(dimLengthInput?.value);
            const rawWidth = parseDecimal(dimWidthInput?.value);
            const rawHeight = parseDecimal(dimHeightInput?.value);

            const normalizedLength = normalizeSmartDecimal(rawLength);
            const normalizedWidth = normalizeSmartDecimal(rawWidth);
            const normalizedHeight = normalizeSmartDecimal(rawHeight);

            if (dimLength) dimLength.value = (!isNaN(normalizedLength) && normalizedLength >= 0) ? normalizedLength.toString() : '';
            if (dimWidth) dimWidth.value = (!isNaN(normalizedWidth) && normalizedWidth >= 0) ? normalizedWidth.toString() : '';
            if (dimHeight) dimHeight.value = (!isNaN(normalizedHeight) && normalizedHeight >= 0) ? normalizedHeight.toString() : '';

            length = (!isNaN(normalizedLength) && normalizedLength > 0) ? normalizedLength : 0;
            width = (!isNaN(normalizedWidth) && normalizedWidth > 0) ? normalizedWidth : 0;
            height = (!isNaN(normalizedHeight) && normalizedHeight > 0) ? normalizedHeight : 0;
        }

        if (length > 0 && width > 0 && height > 0) {
            const volumeCm3 = length * width * height;
            const volumeM3 = volumeCm3 / 1000000;
            const normalizedVolume = normalizeVolumePrecision(volumeM3);
            currentVolume = normalizedVolume;
            formatValuesWithHelper([
                { key: 'length', value: length },
                { key: 'width', value: width },
                { key: 'height', value: height },
                { key: 'volumeCm3', value: volumeCm3 },
            ]).then((formatted) => {
                const volumeText = formatVolumeDisplay(normalizedVolume);
                const volumePlain = formatVolumePlain(normalizedVolume);
                if (volumeDisplay) {
                    volumeDisplay.textContent = volumeText;
                    volumeDisplay.style.color = '#27ae60';
                }
                if (volumeDisplayInput) {
                    volumeDisplayInput.value = volumeText;
                }
                if (packageVolume) {
                    packageVolume.value = volumePlain || '';
                }
                if (volumeCalculationDisplay) {
                    const lengthText = formatted.length?.formatted || '';
                    const widthText = formatted.width?.formatted || '';
                    const heightText = formatted.height?.formatted || '';
                    const volumeCm3Text = formatted.volumeCm3?.formatted || '';
                    volumeCalculationDisplay.textContent =
                        `${lengthText} x ${widthText} x ${heightText} = ${volumeCm3Text} cm3 = ${volumeText} M3`;
                }
                updatePackageQtyPreview();
            });
            // Recalculate prices when volume changes
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentVolume = 0;
            if (volumeDisplay) {
                volumeDisplay.textContent = '-';
                volumeDisplay.style.color = '#15803d';
            }
            if (volumeDisplayInput) {
                volumeDisplayInput.value = '';
            }
            if (packageVolume) {
                packageVolume.value = '';
            }
            if (volumeCalculationDisplay) {
                volumeCalculationDisplay.textContent = '-';
            }
            updatePackageQtyPreview();
        }
    }

    function recalculatePrices() {
        if (currentVolume <= 0) return; // Need volume to calculate

        const priceValue = parseDecimal(pricePerPiece?.value) || 0;
        const compValue = parseDecimal(comparisonPrice?.value) || 0;
        const isKubik = getPackageTypeMode() === 'kubik';

        if (isKubik) {
            const comparisonBase = compValue > 0
                ? compValue
                : (priceValue > 0 && currentVolume > 0 ? priceValue / currentVolume : 0);
            if (comparisonBase > 0) {
                const calcPrice = comparisonBase * currentVolume;
                applyPriceFormatting(calcPrice, comparisonBase);
            }
            return;
        }

        // Recalculate based on which field was edited last
        if (lastEditedPriceField === 'price' && priceValue > 0) {
            // User edited price, recalculate comparison
            const calcComparison = priceValue / currentVolume;
            applyPriceFormatting(priceValue, calcComparison);
        } else if (lastEditedPriceField === 'comparison' && compValue > 0) {
            // User edited comparison, recalculate price
            const calcPrice = compValue * currentVolume;
            applyPriceFormatting(calcPrice, compValue);
        } else {
            // No field edited yet, or both are empty - try to calculate based on what exists
            if (priceValue > 0) {
                const calcComparison = priceValue / currentVolume;
                applyPriceFormatting(priceValue, calcComparison);
            } else if (compValue > 0) {
                const calcPrice = compValue * currentVolume;
                applyPriceFormatting(calcPrice, compValue);
            }
        }
    }

    // ========== VALIDASI DAN KONVERSI DIMENSI ==========

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
            case 'inch':
                return num * 2.54;
            default:
                return num;
        }
    }

    function setupDimensionInput(inputId, unitId, hiddenId, displayId) {
        const inputElement = getElement(inputId);
        const unitElement = getElement(unitId);
        const hiddenElement = getElement(hiddenId);
        const displayElement = displayId ? getElement(displayId) : null;

        if (!inputElement || !unitElement || !hiddenElement) return;

        function updateDimension() {
            const rawValue = inputElement.value.trim();
            const selectedUnit = unitElement.value;
            
            if (rawValue === '') {
                hiddenElement.value = '';
                if (displayElement) {
                    displayElement.textContent = '-';
                    displayElement.style.color = '#15803d';
                }
                inputElement.style.borderColor = '#e2e8f0';
                calculateVolume();
                return;
            }

            const cmValue = convertToCm(rawValue, selectedUnit);
            
            if (cmValue !== null) {
                const normalizedCm = normalizeSmartDecimal(cmValue);
                hiddenElement.value = isNaN(normalizedCm) ? '' : normalizedCm.toString();
                if (displayElement) {
                    displayElement.textContent = formatSmartDecimal(normalizedCm);
                    displayElement.style.color = '#15803d';
                }
                inputElement.style.borderColor = '#e2e8f0';
            } else {
                hiddenElement.value = '';
                if (displayElement) {
                    displayElement.textContent = 'Angka tidak valid';
                    displayElement.style.color = '#e74c3c';
                }
                inputElement.style.borderColor = '#e74c3c';
            }
            
            calculateVolume();
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

    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length', 'length_cm_display');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width', 'width_cm_display');
    setupDimensionInput('dimension_height_input', 'dimension_height_unit', 'dimension_height', 'height_cm_display');

    pricePerPieceDisplay?.addEventListener('input', syncPriceFromDisplay);
    comparisonPriceDisplay?.addEventListener('input', syncComparisonFromDisplay);
    packageType?.addEventListener('change', function() {
        syncPackageTypeUi();
        recalculatePrices();
    });

    // Format existing values on load
    if ((pricePerPiece && pricePerPiece.value) || (comparisonPrice && comparisonPrice.value)) {
        const priceValue = pricePerPiece?.value ? Number(pricePerPiece.value) : null;
        const comparisonValue = comparisonPrice?.value ? Number(comparisonPrice.value) : null;
        applyPriceFormatting(priceValue, comparisonValue);
    }

    initPackageTypeDropdown();
    syncPackageTypeUi();
    calculateVolume();
}
