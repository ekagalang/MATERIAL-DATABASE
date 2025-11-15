function initCementForm() {
    // Idempotent guard
    const form = document.getElementById('cementForm');
    if (!form || form.__cementFormInited) {
        return;
    }
    form.__cementFormInited = true;

    // Auto-suggest
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
                    });
                    suggestList.appendChild(item);
                });
                suggestList.style.display = values.length > 0 ? 'block' : 'none';
            }
        }

        function loadSuggestions(term = '') {
            const url = `/api/cements/field-values/${field}?search=${encodeURIComponent(term)}&limit=20`;
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
    const netInput = document.getElementById('package_weight_net');
    const unitSelect = document.getElementById('package_unit');
    const netCalcDisplay = document.getElementById('net_weight_display');
    const packagePrice = document.getElementById('package_price');
    const packagePriceDisplay = document.getElementById('package_price_display');
    const comparisonPrice = document.getElementById('comparison_price_per_kg');
    const comparisonPriceDisplay = document.getElementById('comparison_price_display');
    const priceUnitInput = document.getElementById('price_unit');

    let isUpdatingPrice = false; // Flag untuk prevent circular updates

    function updateNetCalc() {
        if (!grossInput || !unitSelect || !netCalcDisplay) return 0;
        const gross = parseFloat(grossInput.value) || 0;
        const tare = parseFloat(unitSelect.selectedOptions[0]?.dataset?.weight) || 0;
        const netCalc = Math.max(gross - tare, 0);
        netCalcDisplay.textContent = netCalc > 0 ? netCalc.toFixed(2) + ' Kg' : '-';
        return netCalc;
    }

    function getCurrentWeight() {
        const netManual = parseFloat(netInput?.value) || 0;
        const netCalc = updateNetCalc();
        return netManual > 0 ? netManual : netCalc;
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
    if (netInput) netInput.addEventListener('input', () => { updateNetCalc(); recalculatePrices(); });

    // Sinkronkan satuan harga mengikuti satuan kemasan
    let priceUnitDirty = false;
    if (priceUnitInput) {
        priceUnitInput.addEventListener('input', () => { priceUnitDirty = true; });
    }

    function syncPriceUnit() {
        if (!unitSelect || !priceUnitInput) return;
        const unit = unitSelect.value || '';
        if (!priceUnitDirty || !priceUnitInput.value) {
            if (unit) priceUnitInput.value = unit;
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
}