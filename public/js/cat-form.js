function initCatForm() {
    // Idempotent guard
    const form = document.getElementById('catForm');
    if (!form || form.__catFormInited) {
        return;
    }
    form.__catFormInited = true;

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

    // Parse decimal value handling both dot and comma as decimal separator
    function parseDecimal(value) {
        if (typeof value === 'number') return isFinite(value) ? value : 0;
        if (typeof value !== 'string' || value.trim() === '') return 0;
        return parseFloat(value.replace(',', '.')) || 0;
    }

    // Auto-suggest dengan cascading logic
    const autosuggestInputs = document.querySelectorAll('.autocomplete-input');

    // Helper function untuk mendapatkan filter values
    function getFilterParams(field) {
        const params = new URLSearchParams();

        // Fields yang bergantung pada brand
        const brandDependentFields = ['sub_brand', 'color_name', 'color_code', 'volume', 'package_weight_gross', 'package_weight_net'];
        if (brandDependentFields.includes(field)) {
            const brandInput = document.getElementById('brand');
            if (brandInput && brandInput.value) {
                params.append('brand', brandInput.value);
            }
        }

        // purchase_price bergantung pada package_unit
        if (field === 'purchase_price') {
            const packageUnitInput = document.getElementById('package_unit');
            if (packageUnitInput && packageUnitInput.value) {
                params.append('package_unit', packageUnitInput.value);
            }
        }

        // address bergantung pada store
        if (field === 'address') {
            const storeInput = document.getElementById('store');
            if (storeInput && storeInput.value) {
                params.append('store', storeInput.value);
            }
        }

        return params;
    }

    autosuggestInputs.forEach(input => {
        const field = input.dataset.field;

        // Skip store and address fields - handled by store-autocomplete.js
        if (field === 'store' || field === 'address') {
            return;
        }

        const suggestList = document.getElementById(`${field}-list`);
        let debounceTimer;
        let isSelectingFromAutosuggest = false; // Flag to prevent reopening

        function populate(values) {
            if (suggestList) {
                suggestList.innerHTML = '';
                values.forEach(v => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';

                    // Format display untuk field tertentu
                    let displayValue = v;
                    if (field === 'purchase_price') {
                        // Format angka sebagai Rupiah
                        displayValue = 'Rp ' + formatRupiah(v);
                    }

                    item.textContent = displayValue;
                    item.addEventListener('click', function() {
                        // Set flag to prevent autosuggest from reopening
                        isSelectingFromAutosuggest = true;

                        // Set value first
                        input.value = v;

                        // Close list
                        suggestList.style.display = 'none';

                        // Handle special fields
                        if (field === 'purchase_price') {
                            // Update both display and hidden field
                            const purchasePriceInput = document.getElementById('purchase_price');
                            const purchasePriceDisplay = document.getElementById('purchase_price_display');
                            const normalized = formatPlainNumber(v, 0);
                            if (purchasePriceInput) purchasePriceInput.value = normalized || '';
                            if (purchasePriceDisplay) purchasePriceDisplay.value = normalized ? formatRupiah(normalized) : '';

                            // Trigger price calculation
                            if (typeof syncPriceFromDisplay === 'function') {
                                syncPriceFromDisplay();
                            }
                        } else if (field === 'package_weight_gross') {
                            // Trigger net weight calculation
                            if (typeof updateNetCalc === 'function') {
                                updateNetCalc();
                            }
                            if (typeof recalculatePrices === 'function') {
                                recalculatePrices();
                            }
                        } else if (field === 'package_weight_net') {
                            // Trigger price recalculation when net weight changes
                            if (typeof updateNetCalc === 'function') {
                                updateNetCalc();
                            }
                            if (typeof recalculatePrices === 'function') {
                                recalculatePrices();
                            }
                        } else if (field === 'comparison_price_per_kg') {
                            // Handle comparison price field
                            const comparisonPriceDisplay = document.getElementById('comparison_price_display');
                            const normalized = formatPlainNumber(v, 0);
                            if (comparisonPriceDisplay) comparisonPriceDisplay.value = normalized ? formatRupiah(normalized) : '';
                            if (typeof syncComparisonFromDisplay === 'function') {
                                syncComparisonFromDisplay();
                            }
                        }

                        // Trigger dependent fields reload
                        triggerDependentFieldsReload(field);

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
            const filterParams = getFilterParams(field);
            filterParams.append('search', term);
            filterParams.append('limit', '20');

            // Untuk field store, gunakan endpoint getAllStores
            if (field === 'store') {
                // Jika tidak ada search term (user baru focus), tampilkan dari cat saja
                // Jika ada search term (user mengetik), tampilkan dari semua material
                if (term === '' || term.length === 0) {
                    filterParams.append('material_type', 'cat');
                } else {
                    filterParams.append('material_type', 'all');
                }
                url = `/api/cats/all-stores?${filterParams.toString()}`;
            }
            // Untuk field address, gunakan endpoint getAddressesByStore
            else if (field === 'address') {
                const storeInput = document.getElementById('store');
                if (storeInput && storeInput.value) {
                    filterParams.append('store', storeInput.value);
                    url = `/api/cats/addresses-by-store?${filterParams.toString()}`;
                } else {
                    // Jika toko belum dipilih, gunakan field-values biasa
                    url = `/api/cats/field-values/${field}?${filterParams.toString()}`;
                }
            }
            else {
                url = `/api/cats/field-values/${field}?${filterParams.toString()}`;
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

        // Reload suggestions saat field berubah (untuk mendukung cascading)
        input.addEventListener('change', function() {
            triggerDependentFieldsReload(field);
        });

        document.addEventListener('click', function(e) {
            if (suggestList && e.target !== input && !suggestList.contains(e.target)) {
                suggestList.style.display = 'none';
            }
        });
    });

    // Function untuk trigger reload dependent fields
    function triggerDependentFieldsReload(changedField) {
        // Jika brand berubah, clear dan reload dependent fields
        if (changedField === 'brand') {
            const dependentFields = ['sub_brand', 'color_name', 'color_code', 'volume', 'package_weight_gross', 'package_weight_net'];
            dependentFields.forEach(fieldName => {
                const input = document.getElementById(fieldName);
                if (input && input.classList.contains('autocomplete-input')) {
                    // Clear existing value jika tidak cocok dengan filter baru
                    // Tapi kita biarkan user mempertahankan input mereka untuk flexibility
                }
            });
        }

        // Jika package_unit berubah, reload purchase_price suggestions
        if (changedField === 'package_unit') {
            const priceInput = document.getElementById('purchase_price_display');
            if (priceInput) {
                // Trigger reload jika ada autocomplete untuk price
            }
        }

        // Jika store berubah, clear dan reload address
        if (changedField === 'store') {
            const addressInput = document.getElementById('address');
            if (addressInput && addressInput.classList.contains('autocomplete-input')) {
                // Clear existing value jika tidak cocok dengan filter baru
            }
        }
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

    // Susun otomatis cat_name (hidden) dari field utama
    const fType = document.getElementById('type');
    const fBrand = document.getElementById('brand');
    const fSubBrand = document.getElementById('sub_brand');
    const fColor = document.getElementById('color_name');
    const fVol = document.getElementById('volume');
    const fVolUnit = document.getElementById('volume_unit');
    const fMatName = document.getElementById('cat_name');

    function composeName() {
        const parts = [];
        if (fType && fType.value) parts.push(fType.value);
        if (fBrand && fBrand.value) parts.push(fBrand.value);
        if (fSubBrand && fSubBrand.value) parts.push(fSubBrand.value);
        if (fColor && fColor.value) parts.push(fColor.value);
        const volPart = (fVol && fVol.value ? fVol.value : '') + (fVolUnit && fVolUnit.value ? fVolUnit.value : '');
        if (volPart.trim()) parts.push(volPart.trim());
        if (fMatName) fMatName.value = parts.join(' ').replace(/\s+/g,' ').trim();
    }

    [fType, fBrand, fSubBrand, fColor, fVol, fVolUnit].forEach(el => {
        if (el) el.addEventListener('input', composeName);
    });
    composeName();

    // Update volume_unit value (hardcoded to L)
    if (fVolUnit) {
        fVolUnit.value = 'L';
    }

    // Kalkulasi: Berat Kemasan (kalkulasi) + Harga Komparasi per Kg
    const grossInput = document.getElementById('package_weight_gross');
    const netInput = document.getElementById('package_weight_net');
    const unitSelect = document.getElementById('package_unit');
    const netWeightLabel = document.getElementById('net_weight_label');
    const purchasePrice = document.getElementById('purchase_price');
    const purchasePriceDisplay = document.getElementById('purchase_price_display');
    const comparisonPrice = document.getElementById('comparison_price_per_kg');
    const comparisonPriceDisplay = document.getElementById('comparison_price_display');
    const priceUnitInput = document.getElementById('price_unit');

    let isUpdatingPrice = false; // Flag untuk prevent circular updates

    function updateNetCalc() {
        if (!grossInput || !unitSelect || !netInput) return 0;

        const gross = parseDecimal(grossInput.value);
        const tare = parseFloat(unitSelect.selectedOptions[0]?.dataset?.weight) || 0;
        const netCalc = Math.max(gross - tare, 0);
        const normalizedNetCalc = normalizeSmartDecimal(netCalc);

        // Cek apakah user sedang mengetik di input berat bersih
        const isUserTyping = document.activeElement === netInput;
        const netManual = parseDecimal(netInput.value);

        // Update label berdasarkan kondisi
        if (netWeightLabel) {
            // Jika diisi manual oleh user
            if (netManual > 0 && isUserTyping) {
                netWeightLabel.textContent = 'Berat Bersih (Kg)';
            }
            // Jika hasil kalkulasi dari kemasan
            else if (netCalc > 0 && gross > 0 && tare > 0) {
                netWeightLabel.textContent = 'Berat Bersih Kalkulasi (Kg)';
                // Auto-fill nilai kalkulasi jika user tidak sedang mengetik
                if (!isUserTyping) {
                    netInput.value = formatSmartDecimal(normalizedNetCalc);
                }
            }
            // Default
            else {
                netWeightLabel.textContent = 'Berat Bersih (Kg)';
            }
        }

        // Validasi: Berat bersih tidak boleh melebihi berat kotor
        if (netManual > 0 && gross > 0 && netManual > gross) {
            if (netInput) {
                netInput.style.borderColor = '#e74c3c';
            }
            return 0;
        } else {
            if (netInput) {
                netInput.style.borderColor = '';
            }
        }

        // Prioritas: Jika berat bersih diisi manual, gunakan itu
        if (netManual > 0) {
            return netManual;
        }

        // Jika tidak, gunakan kalkulasi
        return normalizedNetCalc;
    }

    function getCurrentWeight() {
        const gross = parseDecimal(grossInput?.value);
        const netManual = parseDecimal(netInput?.value);
        
        // Validasi: Berat bersih tidak boleh melebihi berat kotor
        if (netManual > 0 && gross > 0 && netManual > gross) {
            return 0; // Return 0 jika invalid
        }
        
        // Prioritas: Berat bersih manual > Berat kalkulasi
        if (netManual > 0) {
            return netManual;
        }
        
        // Kalkulasi dari berat kotor - berat kemasan
        const tare = parseFloat(unitSelect?.selectedOptions[0]?.dataset?.weight) || 0;
        return Math.max(gross - tare, 0);
    }

    function recalculatePrices() {
        if (isUpdatingPrice) return;
        const net = getCurrentWeight();
        if (net <= 0) return;

        // Jika ada harga satuan, kalkulasi comparison price
        const priceValue = parseFloat(purchasePrice?.value) || 0;
        if (priceValue > 0) {
            const calcComparison = priceValue / net;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        }
        // Jika ada comparison price, kalkulasi harga satuan
        else {
            const compValue = parseFloat(comparisonPrice?.value) || 0;
            if (compValue > 0) {
                const calcPrice = compValue * net;
                const calcPlain = formatPlainNumber(calcPrice, 0);
                if (purchasePrice) purchasePrice.value = calcPlain || '';
                if (purchasePriceDisplay) purchasePriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
            }
        }
    }

    if (grossInput) grossInput.addEventListener('input', () => { updateNetCalc(); recalculatePrices(); });
    if (netInput) netInput.addEventListener('input', () => { updateNetCalc(); recalculatePrices(); });

    // Sinkronkan satuan harga mengikuti satuan kemasan
    const priceUnitDisplay = document.getElementById('price_unit_display_inline');

    function syncPriceUnit() {
        if (!unitSelect || !priceUnitInput) return;
        const unit = unitSelect.value || '';
        if (unit) {
            priceUnitInput.value = unit;
            if (priceUnitDisplay) {
                priceUnitDisplay.textContent = '/ ' + unit;
            }
        } else {
            priceUnitInput.value = '';
            if (priceUnitDisplay) {
                priceUnitDisplay.textContent = '/ -';
            }
        }
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', () => {
            updateNetCalc();
            recalculatePrices();
            syncPriceUnit();
            // Trigger reload untuk purchase_price suggestions
            triggerDependentFieldsReload('package_unit');
        });
    }

    // Format Rupiah saat input harga (tampilan) + sinkron ke hidden
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

    // Handle purchase price input
    function syncPriceFromDisplay() {
        if (!purchasePriceDisplay || !purchasePrice) return;
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(purchasePriceDisplay.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        purchasePrice.value = normalized || '';
        purchasePriceDisplay.value = normalized ? formatRupiah(normalized) : '';

        // Calculate comparison price from purchase price
        const net = getCurrentWeight();
        if (normalized && net > 0) {
            const calcComparison = Number(normalized) / net;
            const calcPlain = formatPlainNumber(calcComparison, 0);
            if (comparisonPrice) comparisonPrice.value = calcPlain || '';
            if (comparisonPriceDisplay) comparisonPriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        }

        isUpdatingPrice = false;
    }

    // Handle comparison price input
    function syncComparisonFromDisplay() {
        if (!comparisonPriceDisplay || !comparisonPrice) return;
        if (isUpdatingPrice) return;
        isUpdatingPrice = true;

        const raw = unformatRupiah(comparisonPriceDisplay.value || '');
        const normalized = raw ? formatPlainNumber(raw, 0) : '';
        comparisonPrice.value = normalized || '';
        comparisonPriceDisplay.value = normalized ? formatRupiah(normalized) : '';

        // Calculate purchase price from comparison price
        const net = getCurrentWeight();
        if (normalized && net > 0) {
            const calcPrice = Number(normalized) * net;
            const calcPlain = formatPlainNumber(calcPrice, 0);
            if (purchasePrice) purchasePrice.value = calcPlain || '';
            if (purchasePriceDisplay) purchasePriceDisplay.value = calcPlain ? formatRupiah(calcPlain) : '';
        }

        isUpdatingPrice = false;
    }

    if (purchasePriceDisplay) {
        purchasePriceDisplay.addEventListener('input', syncPriceFromDisplay);
        if (purchasePrice && purchasePrice.value) {
            purchasePriceDisplay.value = formatRupiah(purchasePrice.value);
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

    // Convert comma decimals to dot decimals before form submission
    if (form && !form.__catSubmitHandlerAttached) {
        form.__catSubmitHandlerAttached = true;
        form.addEventListener('submit', function(e) {
            try {
                console.log('[CatForm] Form submitting, converting decimals...');

                // Convert package_weight_gross
                if (grossInput && grossInput.value) {
                    const original = grossInput.value;
                    grossInput.value = grossInput.value.replace(/,/g, '.');
                    console.log('[CatForm] Converted package_weight_gross:', original, '→', grossInput.value);
                }

                // Convert package_weight_net
                if (netInput && netInput.value) {
                    const original = netInput.value;
                    netInput.value = netInput.value.replace(/,/g, '.');
                    console.log('[CatForm] Converted package_weight_net:', original, '→', netInput.value);
                }

                // Convert volume
                const volumeInput = document.getElementById('volume');
                if (volumeInput && volumeInput.value) {
                    const original = volumeInput.value;
                    volumeInput.value = volumeInput.value.replace(/,/g, '.');
                    console.log('[CatForm] Converted volume:', original, '→', volumeInput.value);
                }

                console.log('[CatForm] Decimal conversion complete, form will submit');
            } catch (error) {
                console.error('[CatForm] Error in submit handler:', error);
                // Don't prevent submission even if there's an error
            }
        });
    }
}
