function initBrickForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#brickForm') || scope) : document;
    if (marker.__brickFormInited) { return; }
    marker.__brickFormInited = true;

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
            const url = `/api/bricks/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
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
                    deletePhotoBtn.style.display = 'flex';
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
                displayElement.style.color = '#15803d';
                inputElement.style.borderColor = '#e2e8f0';
                calculateVolume();
                return;
            }

            const cmValue = convertToCm(rawValue, selectedUnit);
            
            if (cmValue !== null) {
                hiddenElement.value = cmValue.toFixed(2);
                // Format angka tanpa trailing zeros
                const formattedValue = parseFloat(cmValue.toFixed(2)).toString();
                displayElement.textContent = formattedValue;
                displayElement.style.color = '#15803d';
                inputElement.style.borderColor = '#e2e8f0';
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

    setupDimensionInput('dimension_length_input', 'dimension_length_unit', 'dimension_length', 'length_cm_display');
    setupDimensionInput('dimension_width_input', 'dimension_width_unit', 'dimension_width', 'width_cm_display');
    setupDimensionInput('dimension_height_input', 'dimension_height_unit', 'dimension_height', 'height_cm_display');

    // ========== KALKULASI VOLUME DAN HARGA ==========
    
    const dimLength = scope.querySelector('#dimension_length') || document.getElementById('dimension_length');
    const dimWidth = scope.querySelector('#dimension_width') || document.getElementById('dimension_width');
    const dimHeight = scope.querySelector('#dimension_height') || document.getElementById('dimension_height');
    const volumeDisplay = scope.querySelector('#volume_display') || document.getElementById('volume_display');
    const pricePerPiece = scope.querySelector('#price_per_piece') || document.getElementById('price_per_piece');
    const pricePerPieceDisplay = scope.querySelector('#price_per_piece_display') || document.getElementById('price_per_piece_display');
    const comparisonPrice = scope.querySelector('#comparison_price_per_m3') || document.getElementById('comparison_price_per_m3');
    const comparisonPriceDisplay = scope.querySelector('#comparison_price_display') || document.getElementById('comparison_price_display');

    let currentVolume = 0;
    let isUpdatingPrice = false; // Flag untuk prevent circular updates

    function calculateVolume() {
        const length = parseFloat(dimLength?.value) || 0;
        const width = parseFloat(dimWidth?.value) || 0;
        const height = parseFloat(dimHeight?.value) || 0;

        if (length > 0 && width > 0 && height > 0) {
            const volumeCm3 = length * width * height;
            const volumeM3 = volumeCm3 / 1000000;
            currentVolume = volumeM3;
            volumeDisplay.innerHTML = volumeM3.toFixed(6);
            volumeDisplay.style.color = '#27ae60';
            // Recalculate prices when volume changes
            if (!isUpdatingPrice) {
                recalculatePrices();
            }
        } else {
            currentVolume = 0;
            volumeDisplay.textContent = '-';
            volumeDisplay.style.color = '#15803d';
        }
    }

    function recalculatePrices() {
        // Jika ada harga per buah, kalkulasi comparison price
        const priceValue = parseFloat(pricePerPiece?.value) || 0;
        if (priceValue > 0 && currentVolume > 0) {
            const calcComparison = priceValue / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        }
        // Jika ada comparison price, kalkulasi harga per buah
        else {
            const compValue = parseFloat(comparisonPrice?.value) || 0;
            if (compValue > 0 && currentVolume > 0) {
                const calcPrice = compValue * currentVolume;
                if (pricePerPiece) pricePerPiece.value = Math.round(calcPrice);
                if (pricePerPieceDisplay) pricePerPieceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
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

    // Handle price per piece input
    function syncPriceFromDisplay() {
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(pricePerPieceDisplay?.value || '');
        if (pricePerPiece) pricePerPiece.value = raw || '';
        if (pricePerPieceDisplay) pricePerPieceDisplay.value = raw ? formatRupiah(raw) : '';

        // Calculate comparison price from price per piece
        if (raw && currentVolume > 0) {
            const calcComparison = parseFloat(raw) / currentVolume;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
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

        // Calculate price per piece from comparison price
        if (raw && currentVolume > 0) {
            const calcPrice = parseFloat(raw) * currentVolume;
            if (pricePerPiece) pricePerPiece.value = Math.round(calcPrice);
            if (pricePerPieceDisplay) pricePerPieceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
        }

        isUpdatingPrice = false;
    }

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
