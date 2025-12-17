function initCementForm() {
    // Idempotent guard
    const form = document.getElementById('cementForm');
    if (!form || form.__cementFormInited) {
        return;
    }
    form.__cementFormInited = true;

    // Track current selections for cascading autocomplete
    let currentBrand = '';
    let currentPackageUnit = '';
    let currentStore = '';

    // Auto-suggest with cascading logic
    const autosuggestInputs = document.querySelectorAll('.autocomplete-input');
    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;
        const suggestList = document.getElementById(`${field}-list`);
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
                // Jika tidak ada search term (user baru focus), tampilkan dari cement saja
                // Jika ada search term (user mengetik), tampilkan dari semua material
                const materialType = (term === '' || term.length === 0) ? 'cement' : 'all';
                url = `/api/cements/all-stores?search=${encodeURIComponent(term)}&limit=20&material_type=${materialType}`;
            }
            // Special case: address field menggunakan endpoint addresses-by-store
            else if (field === 'address' || field === 'short_address') {
                if (currentStore) {
                    url = `/api/cements/addresses-by-store?search=${encodeURIComponent(term)}&limit=20&store=${encodeURIComponent(currentStore)}`;
                } else {
                    // Jika toko belum dipilih, gunakan field-values biasa
                    url = `/api/cements/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
                }
            }
            else {
                url = `/api/cements/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;

                // Add filter parameters for cascading autocomplete
                // Fields that depend on brand
                if (['sub_brand', 'code', 'color', 'dimension_length', 'dimension_width', 'dimension_height', 'package_weight_gross'].includes(field)) {
                    if (currentBrand) {
                        url += `&brand=${encodeURIComponent(currentBrand)}`;
                    }
                }

                // Fields that depend on package_unit
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
    const brandInput = document.getElementById('brand');
    if (brandInput) {
        brandInput.addEventListener('change', function() {
            currentBrand = this.value;

            // Clear and refresh dependent fields
            const dependentFields = ['sub_brand', 'code', 'color', 'dimension_length', 'dimension_width', 'dimension_height', 'package_weight_gross'];
            dependentFields.forEach(fieldName => {
                const fieldInput = document.getElementById(fieldName);
                if (fieldInput && fieldInput !== document.activeElement) {
                    // Don't clear if user is typing in that field
                    // fieldInput.value = '';
                }
            });
        });
    }

    // Listen for store changes to update address field
    const storeInput = document.getElementById('store');
    if (storeInput) {
        storeInput.addEventListener('change', function() {
            currentStore = this.value;

            // Clear address field when store changes
            const addressInput = document.getElementById('address');
            if (addressInput && addressInput !== document.activeElement) {
                // addressInput.value = '';
            }
        });
    }

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

    // Susun otomatis cement_name (hidden) dari field utama
    const fType = document.getElementById('type');
    const fBrand = document.getElementById('brand');
    const fSubBrand = document.getElementById('sub_brand');
    const fCode = document.getElementById('code');
    const fColor = document.getElementById('color');
    const fCementName = document.getElementById('cement_name');

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

    // Kalkulasi: Berat Kemasan (kalkulasi) + Harga Komparasi per Kg
    const grossInput = document.getElementById('package_weight_gross');
    const unitSelect = document.getElementById('package_unit');
    const netCalcDisplay = document.getElementById('net_weight_display');
    const packagePrice = document.getElementById('package_price');
    const packagePriceDisplay = document.getElementById('package_price_display');
    const comparisonPrice = document.getElementById('comparison_price_per_kg');
    const comparisonPriceDisplay = document.getElementById('comparison_price_display');
    const priceUnitInput = document.getElementById('price_unit');

    let isUpdatingPrice = false; // Flag untuk prevent circular updates

    // Listen for package_unit changes to update price field (for cascading autocomplete)
    if (unitSelect) {
        currentPackageUnit = unitSelect.value || '';
        unitSelect.addEventListener('change', function() {
            currentPackageUnit = this.value;
            // Price field will automatically use this filter when focused
        });
    }

    function updateNetCalc() {
        if (!grossInput || !unitSelect || !netCalcDisplay) return 0;
        
        // Kalkulasi dari berat kotor - berat kemasan
        const gross = parseFloat(grossInput.value) || 0;
        const tare = parseFloat(unitSelect.selectedOptions[0]?.dataset?.weight) || 0;
        const netCalc = Math.max(gross - tare, 0);
        const formattedValue = netCalc > 0 ? parseFloat(netCalc.toFixed(2)).toString() + ' Kg' : '-';
        netCalcDisplay.textContent = formattedValue;
        return netCalc;
    }

    function getCurrentWeight() {
        // Kalkulasi dari berat kotor - berat kemasan
        const gross = parseFloat(grossInput?.value) || 0;
        const tare = parseFloat(unitSelect?.selectedOptions[0]?.dataset?.weight) || 0;
        return Math.max(gross - tare, 0);
    }

    function recalculatePrices() {
        if (isUpdatingPrice) return;
        const net = getCurrentWeight();
        if (net <= 0) return;

        // Jika ada harga kemasan, kalkulasi comparison price
        const priceValue = parseFloat(packagePrice?.value) || 0;
        if (priceValue > 0) {
            const calcComparison = priceValue / net;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        }
        // Jika ada comparison price, kalkulasi harga kemasan
        else {
            const compValue = parseFloat(comparisonPrice?.value) || 0;
            if (compValue > 0) {
                const calcPrice = compValue * net;
                if (packagePrice) packagePrice.value = Math.round(calcPrice);
                if (packagePriceDisplay) packagePriceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
            }
        }
    }

    if (grossInput) grossInput.addEventListener('input', () => { updateNetCalc(); recalculatePrices(); });

    // Sinkronkan satuan harga mengikuti satuan kemasan
    const priceUnitDisplayInline = document.getElementById('price_unit_display_inline');

    function syncPriceUnit() {
        if (!unitSelect || !priceUnitInput) return;
        const unit = unitSelect.value || '';
        if (unit) {
            priceUnitInput.value = unit;
            if (priceUnitDisplayInline) {
                priceUnitDisplayInline.textContent = '/ ' + unit;
            }
        } else {
            priceUnitInput.value = '';
            if (priceUnitDisplayInline) {
                priceUnitDisplayInline.textContent = '/ -';
            }
        }
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', () => {
            updateNetCalc();
            recalculatePrices();
            syncPriceUnit();
        });
    }

    // Format Rupiah saat input harga (tampilan) + sinkron ke hidden
    function unformatRupiah(str) {
        return (str || '').toString().replace(/\./g,'').replace(/,/g,'.').replace(/[^0-9.]/g,'');
    }

    function formatRupiah(num) {
        const n = Number(num||0);
        return isNaN(n) ? '' : n.toLocaleString('id-ID');
    }

    // Handle package price input
    function syncPriceFromDisplay() {
        if (!packagePriceDisplay || !packagePrice) return;
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(packagePriceDisplay.value || '');
        packagePrice.value = raw || '';
        packagePriceDisplay.value = raw ? formatRupiah(raw) : '';

        // Calculate comparison price from package price
        const net = getCurrentWeight();
        if (raw && net > 0) {
            const calcComparison = parseFloat(raw) / net;
            if (comparisonPrice) comparisonPrice.value = Math.round(calcComparison);
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = Math.round(calcComparison).toLocaleString('id-ID');
        }

        isUpdatingPrice = false;
    }

    // Handle comparison price input
    function syncComparisonFromDisplay() {
        if (!comparisonPriceDisplay || !comparisonPrice) return;
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay.value || '');
        comparisonPrice.value = raw || '';
        comparisonPriceDisplay.value = raw ? formatRupiah(raw) : '';

        // Calculate package price from comparison price
        const net = getCurrentWeight();
        if (raw && net > 0) {
            const calcPrice = parseFloat(raw) * net;
            if (packagePrice) packagePrice.value = Math.round(calcPrice);
            if (packagePriceDisplay) packagePriceDisplay.value = Math.round(calcPrice).toLocaleString('id-ID');
        }

        isUpdatingPrice = false;
    }

    if (packagePriceDisplay) {
        packagePriceDisplay.addEventListener('input', syncPriceFromDisplay);
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

    updateNetCalc();
    recalculatePrices();
    syncPriceUnit();

    // ========================================================================
    // Kalkulasi Volume dari Dimensi Kemasan (P × L × T)
    // ========================================================================
    const dimensionLength = document.getElementById('dimension_length');
    const dimensionWidth = document.getElementById('dimension_width');
    const dimensionHeight = document.getElementById('dimension_height');
    const volumeDisplay = document.getElementById('volume_display');

    function calculateVolume() {
        if (!dimensionLength || !dimensionWidth || !dimensionHeight || !volumeDisplay) return;

        const length = parseFloat(dimensionLength.value) || 0;
        const width = parseFloat(dimensionWidth.value) || 0;
        const height = parseFloat(dimensionHeight.value) || 0;

        if (length > 0 && width > 0 && height > 0) {
            // Convert from cm to meters: divide by 100 for each dimension, so total divide by 1,000,000
            const volume = (length * width * height) / 1000000;
            volumeDisplay.value = volume.toFixed(6);
        } else {
            volumeDisplay.value = '';
        }
    }

    if (dimensionLength) dimensionLength.addEventListener('input', calculateVolume);
    if (dimensionWidth) dimensionWidth.addEventListener('input', calculateVolume);
    if (dimensionHeight) dimensionHeight.addEventListener('input', calculateVolume);

    // Initial calculation
    calculateVolume();
}
