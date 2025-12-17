function initSandForm(root) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#sandForm') || scope) : document;
    if (marker.__sandFormInited) { return; }
    marker.__sandFormInited = true;

    // Track current selections for cascading autocomplete
    let currentBrand = '';
    let currentPackageUnit = '';
    let currentStore = '';

    // Auto-suggest with cascading logic
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

                        // Trigger change event for cascading updates
                        input.dispatchEvent(new Event('change', { bubbles: true }));
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

        if (unitName && unitName !== '-- Satuan --') {
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

    // ========== SETUP DIMENSI KEMASAN LISTENERS ==========
    // Dimensi sekarang langsung dalam cm, tidak ada unit selector lagi

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
            // Convert from cm to M3: divide by 100 for each dimension = divide by 1,000,000 total
            const volumeM3 = (length * width * height) / 1000000;
            currentVolume = volumeM3;
            if (volumeDisplay) {
                volumeDisplay.value = volumeM3.toFixed(6);
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

    // Add event listeners for dimension inputs
    if (dimLength) dimLength.addEventListener('input', calculateVolume);
    if (dimWidth) dimWidth.addEventListener('input', calculateVolume);
    if (dimHeight) dimHeight.addEventListener('input', calculateVolume);

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