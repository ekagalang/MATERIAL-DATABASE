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

    function unformatRupiah(str) {
        return (str || '').toString().replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.]/g,'');
    }

    function formatRupiah(num) {
        const n = Number(num||0);
        return isNaN(n) ? '' : n.toLocaleString('id-ID');
    }

    // Handle price per piece input
    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(pricePerPieceDisplay?.value || '');
        if (pricePerPiece) pricePerPiece.value = raw || '';
        if (pricePerPieceDisplay) pricePerPieceDisplay.value = raw ? formatRupiah(raw) : '';

        // Mark that price was edited last
        if (raw) {
            lastEditedPriceField = 'price';
        }

        // Calculate comparison price from price per piece
        if (raw && currentVolume > 0) {
            const calcComparison = parseFloat(raw) / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        } else if (!raw) {
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
        if (comparisonPrice) comparisonPrice.value = raw || '';
        if (comparisonPriceDisplay) comparisonPriceDisplay.value = raw ? formatRupiah(raw) : '';

        // Mark that comparison was edited last
        if (raw) {
            lastEditedPriceField = 'comparison';
        }

        // Calculate price per piece from comparison price
        if (raw && currentVolume > 0) {
            const calcPrice = parseFloat(raw) * currentVolume;
            if (pricePerPiece) pricePerPiece.value = Math.round(calcPrice);
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
        } else if (!raw) {
            // Clear price if comparison is cleared
            if (pricePerPiece) pricePerPiece.value = '';
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = '';
        }

        isUpdatingPrice = false;
    }

    // Auto-suggest
    const autosuggestInputs = (scope && scope.querySelectorAll) ? scope.querySelectorAll('.autocomplete-input') : document.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        const suggestList = getElement(`${field}-list`);
        let debounceTimer;

        function populate(values) {
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';

                    // Format display untuk field tertentu
                    let displayValue = v;
                    if (field === 'price_per_piece') {
                        displayValue = 'Rp ' + Number(v).toLocaleString('id-ID');
                    }

                    item.textContent = displayValue;
                    item.addEventListener('click', function() {
                        // Handle special fields dengan kalkulasi
                        if (field === 'price_per_piece') {
                            // Update display field dengan format Rupiah
                            input.value = Number(v).toLocaleString('id-ID');
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
            else if (['address', 'short_address'].includes(field)) {
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

    function formatNumberTrim(value) {
        return formatSmartDecimal(value);
    }

    function calculateVolume() {
        // Default: pakai hidden (edit form)
        let length = parseFloat(dimLength?.value) || 0;
        let width = parseFloat(dimWidth?.value) || 0;
        let height = parseFloat(dimHeight?.value) || 0;

        // Jika tidak ada unit selector, berarti create form (angka input langsung cm)
        const usesUnitSelectors = !!(dimLengthUnit || dimWidthUnit || dimHeightUnit);
        if (!usesUnitSelectors) {
            const rawLength = parseFloat(dimLengthInput?.value);
            const rawWidth = parseFloat(dimWidthInput?.value);
            const rawHeight = parseFloat(dimHeightInput?.value);

            if (dimLength) dimLength.value = (!isNaN(rawLength) && rawLength >= 0) ? rawLength.toFixed(2) : '';
            if (dimWidth) dimWidth.value = (!isNaN(rawWidth) && rawWidth >= 0) ? rawWidth.toFixed(2) : '';
            if (dimHeight) dimHeight.value = (!isNaN(rawHeight) && rawHeight >= 0) ? rawHeight.toFixed(2) : '';

            length = (!isNaN(rawLength) && rawLength > 0) ? rawLength : 0;
            width = (!isNaN(rawWidth) && rawWidth > 0) ? rawWidth : 0;
            height = (!isNaN(rawHeight) && rawHeight > 0) ? rawHeight : 0;
        }

        if (length > 0 && width > 0 && height > 0) {
            const volumeCm3 = length * width * height;
            const volumeM3 = volumeCm3 / 1000000;
            currentVolume = volumeM3;
            const volumeText = formatSmartDecimal(volumeM3);
            const volumeValue = volumeM3.toFixed(6);
            if (volumeDisplay) {
                volumeDisplay.textContent = volumeText;
                volumeDisplay.style.color = '#27ae60';
            }
            if (volumeDisplayInput) {
                volumeDisplayInput.value = volumeText;
            }
            if (packageVolume) {
                packageVolume.value = volumeValue;
            }
            if (volumeCalculationDisplay) {
                volumeCalculationDisplay.textContent =
                    `${formatNumberTrim(length)} x ${formatNumberTrim(width)} x ${formatNumberTrim(height)} = ${formatNumberTrim(volumeCm3)} cm3 = ${volumeText} M3`;
            }
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
        }
    }

    function recalculatePrices() {
        if (currentVolume <= 0) return; // Need volume to calculate

        const priceValue = parseFloat(pricePerPiece?.value) || 0;
        const compValue = parseFloat(comparisonPrice?.value) || 0;

        // Recalculate based on which field was edited last
        if (lastEditedPriceField === 'price' && priceValue > 0) {
            // User edited price, recalculate comparison
            const calcComparison = priceValue / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        } else if (lastEditedPriceField === 'comparison' && compValue > 0) {
            // User edited comparison, recalculate price
            const calcPrice = compValue * currentVolume;
            if (pricePerPiece) pricePerPiece.value = Math.round(calcPrice);
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
        } else {
            // No field edited yet, or both are empty - try to calculate based on what exists
            if (priceValue > 0) {
                const calcComparison = priceValue / currentVolume;
                if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
                if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
            } else if (compValue > 0) {
                const calcPrice = compValue * currentVolume;
                if (pricePerPiece) pricePerPiece.value = Math.round(calcPrice);
                if (pricePerPieceDisplay) pricePerPieceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
            }
        }
    }

    // ========== VALIDASI DAN KONVERSI DIMENSI ==========

    function convertToCm(value, unit) {
        const num = parseFloat(value);
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
                hiddenElement.value = cmValue.toFixed(2);
                if (displayElement) {
                    displayElement.textContent = formatSmartDecimal(cmValue);
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
                const num = parseFloat(rawValue);
                if (!isNaN(num) && num >= 0) {
                    this.value = num.toString();
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

    // Format existing values on load
    if (pricePerPieceDisplay && pricePerPiece && pricePerPiece.value) {
        pricePerPieceDisplay.value = formatRupiah(pricePerPiece.value);
    }
    if (comparisonPriceDisplay && comparisonPrice && comparisonPrice.value) {
        comparisonPriceDisplay.value = formatRupiah(comparisonPrice.value);
    }

    calculateVolume();
}
