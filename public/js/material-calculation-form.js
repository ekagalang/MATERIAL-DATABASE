function initMaterialCalculationForm(root, formData) {
    const scope = root || document;
    const marker = scope.querySelector ? (scope.querySelector('#calculationForm') || scope) : document;
    if (marker.__materialCalculationFormInited) { return; }
    marker.__materialCalculationFormInited = true;

    // Extract formula descriptions and material data from formData
    const formulaDescriptions = formData?.formulaDescriptions || {};
    const formulasData = Array.isArray(formData?.formulas) ? formData.formulas : [];
    const bricksData = formData?.bricks || [];
    const cementsData = formData?.cements || [];
    const sandsData = formData?.sands || [];
    const catsData = formData?.cats || [];
    const materialTypeLabels = {
        brick: 'Bata',
        cement: 'Semen',
        sand: 'Pasir',
        cat: 'Cat',
        ceramic_type: 'Keramik',
        ceramic: 'Keramik',
        nat: 'Nat'
    };
    const ceramicsData = formData?.ceramics || [];
    const natsData = formData?.nats || [];

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

    function formatFixedLocale(value, decimals = 2) {
        const plain = formatPlainNumber(value, decimals);
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

    // Helper function to calculate area
    function calculateArea() {
        const lengthEl = scope.querySelector('#wallLength') || document.getElementById('wallLength');
        const heightEl = scope.querySelector('#wallHeight') || document.getElementById('wallHeight');
        const areaEl = scope.querySelector('#wallArea') || document.getElementById('wallArea');

        if (lengthEl && heightEl && areaEl) {
            const length = parseDecimal(lengthEl.value) || 0;
            const height = parseDecimal(heightEl.value) || 0;
            const area = length * height;
            const normalizedArea = normalizeSmartDecimal(area);
            areaEl.value = normalizedArea > 0 ? formatSmartDecimal(normalizedArea) : '';
        }
    }

    function setupProjectLocationMap() {
        const mapElement = scope.querySelector('#projectLocationMap') || document.getElementById('projectLocationMap');
        if (!mapElement) {
            return;
        }

        if (!window.GoogleMapsPicker || typeof window.GoogleMapsPicker.initAddressPicker !== 'function') {
            console.warn('GoogleMapsPicker helper is not available for material calculation form.');
            return;
        }

        window.GoogleMapsPicker.initAddressPicker({
            scope,
            apiKey: mapElement.dataset.googleMapsApiKey || '',
            searchInput: '#projectLocationSearch',
            mapElement: '#projectLocationMap',
            addressInput: '#projectAddress',
            latitudeInput: '#projectLatitude',
            longitudeInput: '#projectLongitude',
            placeIdInput: '#projectPlaceId',
            formattedAddressInput: '#projectAddress',
            gestureHandling: 'greedy',
            scrollwheel: true,
        }).catch((error) => {
            console.error('Failed to initialize project location map picker:', error);
        });
    }

    function setupWorkTypeAutocomplete() {
        const displayInput = scope.querySelector('#workTypeDisplay') || document.getElementById('workTypeDisplay');
        const hiddenInput = scope.querySelector('#workTypeSelector') || document.getElementById('workTypeSelector');
        const listEl = scope.querySelector('#workType-list') || document.getElementById('workType-list');

        if (!displayInput || !hiddenInput || !listEl || formulasData.length === 0) {
            return;
        }

        const options = formulasData
            .filter(option => option && option.code && option.name)
            .map(option => ({
                code: String(option.code),
                name: String(option.name),
            }));

        if (options.length === 0) {
            return;
        }

        if (!listEl.__scrollLockBound) {
            listEl.__scrollLockBound = true;
            listEl.addEventListener('wheel', function(event) {
                const deltaY = event.deltaY || 0;
                if (!deltaY) return;

                const canScroll = listEl.scrollHeight > listEl.clientHeight + 1;
                if (!canScroll) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                // Keep wheel focus inside dropdown list and prevent page scroll chaining.
                listEl.scrollTop += deltaY;
                event.preventDefault();
                event.stopPropagation();
            }, { passive: false });
        }

        function normalize(text) {
            return (text || '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/gi, '')
                .trim();
        }

        function closeList() {
            listEl.style.display = 'none';
        }

        function renderList(items) {
            listEl.innerHTML = '';
            items.forEach(option => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = option.name;
                item.addEventListener('click', function() {
                    applySelection(option);
                });
                listEl.appendChild(item);
            });
            listEl.style.display = items.length > 0 ? 'block' : 'none';
        }

        function filterOptions(term) {
            const query = normalize(term);
            if (!query) {
                return options;
            }
            return options.filter(option => {
                const name = normalize(option.name);
                const code = normalize(option.code);
                return name.includes(query) || code.includes(query);
            });
        }

        function findExactMatch(term) {
            const query = normalize(term);
            if (!query) return null;
            return options.find(option => normalize(option.name) === query || normalize(option.code) === query) || null;
        }

        function applySelection(option) {
            displayInput.value = option.name;
            hiddenInput.value = option.code;
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            closeList();
        }

        displayInput.addEventListener('focus', function() {
            if (displayInput.readOnly || displayInput.disabled) return;
            renderList(filterOptions(''));
        });

        displayInput.addEventListener('input', function() {
            if (displayInput.readOnly || displayInput.disabled) return;
            const term = this.value || '';
            renderList(filterOptions(term));

            if (!term.trim()) {
                if (hiddenInput.value) {
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                return;
            }

            const exactMatch = findExactMatch(term);
            if (exactMatch) {
                hiddenInput.value = exactMatch.code;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        displayInput.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter') return;
            const exactMatch = findExactMatch(displayInput.value);
            if (exactMatch) {
                applySelection(exactMatch);
                event.preventDefault();
            }
        });

        displayInput.addEventListener('blur', function() {
            setTimeout(closeList, 150);
        });

        document.addEventListener('click', function(event) {
            if (event.target === displayInput || listEl.contains(event.target)) return;
            closeList();
        });

        hiddenInput.addEventListener('change', function() {
            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected && displayInput.value !== selected.name) {
                displayInput.value = selected.name;
            }
        });

        if (hiddenInput.value && !displayInput.value) {
            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected) {
                displayInput.value = selected.name;
            }
        }
    }

    function setupMaterialTypeFilters() {
        const groupEl = scope.querySelector('#materialTypeFilterGroup') || document.getElementById('materialTypeFilterGroup');
        const workTypeSelector = scope.querySelector('#workTypeSelector') || document.getElementById('workTypeSelector');
        const materialTypes = ['brick', 'cement', 'sand', 'cat', 'ceramic_type', 'ceramic', 'nat'];
        const optionsByType = {};

        function normalize(text) {
            return (text || '').toLowerCase();
        }

        function uniqueSorted(values) {
            const set = new Set();
            values.forEach(value => {
                const cleaned = String(value || '').trim();
                if (cleaned) {
                    set.add(cleaned);
                }
            });
            return Array.from(set).sort((a, b) => a.localeCompare(b, 'id-ID'));
        }

        function formatCeramicSize(length, width) {
            const len = Number(length);
            const wid = Number(width);
            if (!isFinite(len) || !isFinite(wid) || len <= 0 || wid <= 0) {
                return '';
            }
            const minVal = Math.min(len, wid);
            const maxVal = Math.max(len, wid);
            const minText = formatSmartDecimal(minVal);
            const maxText = formatSmartDecimal(maxVal);
            if (!minText || !maxText) return '';
            return `${minText} x ${maxText}`;
        }

        function resolveMaterialTypeValue(type, item) {
            if (!item) return '';
            switch (type) {
                case 'brick':
                    return item.type || '';
                case 'cement':
                    return item.type || '';
                case 'sand':
                    return item.type || '';
                case 'cat':
                    return item.type || '';
                case 'ceramic_type':
                    return item.type || '';
                case 'ceramic':
                    return formatCeramicSize(item.dimension_length, item.dimension_width);
                case 'nat':
                    return item.type || '';
                default:
                    return '';
            }
        }

        function buildOptions(type) {
            let source = [];
            if (type === 'brick') source = bricksData;
            if (type === 'cement') source = cementsData;
            if (type === 'sand') source = sandsData;
            if (type === 'cat') source = catsData;
            if (type === 'ceramic_type') source = ceramicsData;
            if (type === 'ceramic') source = ceramicsData;
            if (type === 'nat') source = natsData;

            const values = source.map(item => resolveMaterialTypeValue(type, item)).filter(Boolean);
            return uniqueSorted(values);
        }

        materialTypes.forEach(type => {
            optionsByType[type] = buildOptions(type);
        });

        function setupAutocomplete(type) {
            const displayInput = scope.querySelector(`#materialTypeDisplay-${type}`) || document.getElementById(`materialTypeDisplay-${type}`);
            const hiddenInput = scope.querySelector(`#materialTypeSelector-${type}`) || document.getElementById(`materialTypeSelector-${type}`);
            const listEl = scope.querySelector(`#materialType-list-${type}`) || document.getElementById(`materialType-list-${type}`);
            const containerEl = scope.querySelector(`.material-type-filter-item[data-material-type="${type}"]`);
            const options = optionsByType[type] || [];

            if (!displayInput || !hiddenInput || !listEl || !containerEl) return;

            function closeList() {
                listEl.style.display = 'none';
            }

            function renderList(items) {
                listEl.innerHTML = '';
                const emptyItem = document.createElement('div');
                emptyItem.className = 'autocomplete-item';
                emptyItem.textContent = '- Tidak Pilih -';
                emptyItem.addEventListener('click', function() {
                    applySelection('');
                });
                listEl.appendChild(emptyItem);
                items.forEach(option => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = option;
                    item.addEventListener('click', function() {
                        applySelection(option);
                    });
                    listEl.appendChild(item);
                });
                listEl.style.display = 'block';
            }

            function filterOptions(term) {
                const query = normalize(term);
                if (!query) {
                    return options;
                }
                return options.filter(option => normalize(option).includes(query));
            }

            function findExactMatch(term) {
                const query = normalize(term);
                if (!query) return null;
                return options.find(option => normalize(option) === query) || null;
            }

            function applySelection(option) {
                displayInput.value = option;
                hiddenInput.value = option;
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeList();
            }

            displayInput.addEventListener('focus', function() {
                if (displayInput.readOnly || displayInput.disabled) return;
                renderList(filterOptions(''));
            });

            displayInput.addEventListener('input', function() {
                if (displayInput.readOnly || displayInput.disabled) return;
                const term = this.value || '';
                renderList(filterOptions(term));

                if (!term.trim()) {
                    if (hiddenInput.value) {
                        hiddenInput.value = '';
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }

                const exactMatch = findExactMatch(term);
                if (exactMatch) {
                    hiddenInput.value = exactMatch;
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (hiddenInput.value) {
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            displayInput.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                const exactMatch = findExactMatch(displayInput.value);
                if (exactMatch) {
                    applySelection(exactMatch);
                    event.preventDefault();
                }
            });

            displayInput.addEventListener('blur', function() {
                setTimeout(closeList, 150);
            });

            document.addEventListener('click', function(event) {
                if (event.target === displayInput || listEl.contains(event.target)) return;
                closeList();
            });

            hiddenInput.addEventListener('change', function() {
                if (displayInput.value !== hiddenInput.value) {
                    displayInput.value = hiddenInput.value;
                }
            });

            if (options.length === 0) {
                displayInput.disabled = true;
                displayInput.placeholder = `Tidak ada data ${materialTypeLabels[type] || type}`;
            }
        }

        materialTypes.forEach(type => {
            setupAutocomplete(type);
        });

        function updateVisibility() {
            const workType = workTypeSelector ? workTypeSelector.value : '';
            const formula = formulasData.find(entry => String(entry?.code) === String(workType));
            const required = Array.isArray(formula?.materials) ? formula.materials : [];
            const hasRequired = workType && required.length > 0;

            if (groupEl) {
                groupEl.style.display = hasRequired ? '' : 'none';
            }

            materialTypes.forEach(type => {
                const itemEl = scope.querySelector(`.material-type-filter-item[data-material-type="${type}"]`);
                const displayInput = scope.querySelector(`#materialTypeDisplay-${type}`) || document.getElementById(`materialTypeDisplay-${type}`);
                const hiddenInput = scope.querySelector(`#materialTypeSelector-${type}`) || document.getElementById(`materialTypeSelector-${type}`);
                let shouldShow = hasRequired && required.includes(type);
                if (type === 'ceramic_type') {
                    shouldShow = hasRequired && (workType === 'tile_installation' || workType === 'plinth_ceramic' || workType === 'adhesive_mix' || workType === 'plinth_adhesive_mix');
                }
                if (workType === 'grout_tile' && type === 'ceramic') {
                    shouldShow = false;
                }

                if (itemEl) {
                    itemEl.style.display = shouldShow ? '' : 'none';
                }
                if (!shouldShow && displayInput && hiddenInput) {
                    displayInput.value = '';
                    hiddenInput.value = '';
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        updateVisibility();
        if (workTypeSelector) {
            workTypeSelector.addEventListener('change', updateVisibility);
        }
    }

    // Work type selector change handler
    const workTypeSelector = scope.querySelector('#workTypeSelector') || document.getElementById('workTypeSelector');
    if (workTypeSelector) {
        const handleWorkTypeChange = function(value) {
            const workType = value || (workTypeSelector ? workTypeSelector.value : '');
            const descriptionEl = scope.querySelector('#workTypeDescription') || document.getElementById('workTypeDescription');
            const inputContainer = scope.querySelector('#inputFormContainer') || document.getElementById('inputFormContainer');

            if (workType) {
                if (descriptionEl) {
                    descriptionEl.textContent = formulaDescriptions[workType] || '';
                }
                if (inputContainer) {
                    inputContainer.style.display = 'block';
                }

                const allForms = scope.querySelectorAll('.work-type-form');
                allForms.forEach(form => {
                    form.style.display = 'none';
                });

                const brickForm = scope.querySelector('#brickForm') || document.getElementById('brickForm');
                const otherForm = scope.querySelector('#otherForm') || document.getElementById('otherForm');

                const usePrimaryForm = workType.includes('brick')
                    || workType.includes('plaster')
                    || workType.includes('skim')
                    || workType.includes('painting')
                    || workType.includes('floor')
                    || workType.includes('screed')
                    || workType.includes('adhesive')
                    || workType.includes('tile')
                    || workType.includes('grout')
                    || workType.includes('plinth')
                    || !otherForm;

                if (usePrimaryForm) {
                    if (brickForm) brickForm.style.display = 'block';
                } else if (otherForm) {
                    otherForm.style.display = 'block';
                }
            } else {
                if (descriptionEl) descriptionEl.textContent = '';
                if (inputContainer) inputContainer.style.display = 'none';
            }
        };

        workTypeSelector.addEventListener('change', function() {
            handleWorkTypeChange(this.value);
        });

        // Set initial state on load (in case value already selected)
        handleWorkTypeChange(workTypeSelector.value);
    }

    function setupCustomFormLinking() {
        // Brick Linking
        const brickFilter = scope.querySelector('#materialTypeSelector-brick') || document.getElementById('materialTypeSelector-brick');
        const customBrick = scope.querySelector('#customBrick') || document.getElementById('customBrick');
        
        if (brickFilter && customBrick) {
            brickFilter.addEventListener('change', function() {
                const selectedType = this.value;
                const currentVal = customBrick.value;
                
                customBrick.innerHTML = '<option value="">-- Semua Bata (Auto) --</option>';
                
                let filtered = bricksData;
                if (selectedType) {
                    filtered = bricksData.filter(b => b.type === selectedType);
                }
                
                filtered.forEach(brick => {
                    const option = document.createElement('option');
                    option.value = brick.id;
                    const dims = `${brick.dimension_length}x${brick.dimension_width}x${brick.dimension_height}`;
                    const price = formatFixedLocale(brick.price_per_piece || 0, 0);
                    option.textContent = `${brick.brand} - ${brick.type} (${dims} cm) - Rp ${price}`;
                    if (brick.id == currentVal) option.selected = true;
                    customBrick.appendChild(option);
                });
            });
        }

        // Cement, Sand, Cat Linking
        ['cement', 'sand', 'cat'].forEach(type => {
            const filter = scope.querySelector(`#materialTypeSelector-${type}`) || document.getElementById(`materialTypeSelector-${type}`);
            const typeKey = type.charAt(0).toUpperCase() + type.slice(1);
            const customType = scope.querySelector(`#custom${typeKey}Type`) || document.getElementById(`custom${typeKey}Type`);
            
            if (filter && customType) {
                filter.addEventListener('change', function() {
                    const val = this.value;
                    if (customType.value !== val) {
                        customType.value = val;
                        if (val === '' || customType.value === val) {
                             customType.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });
            }
        });
    }

    setupWorkTypeAutocomplete();
    setupMaterialTypeFilters();
    setupCustomFormLinking();
    setupProjectLocationMap();

    // Auto-calculate area when length or height changes
    const wallLength = scope.querySelector('#wallLength') || document.getElementById('wallLength');
    const wallHeight = scope.querySelector('#wallHeight') || document.getElementById('wallHeight');
    if (wallLength) {
        wallLength.addEventListener('input', calculateArea);
    }
    if (wallHeight) {
        wallHeight.addEventListener('input', calculateArea);
    }

    // Toggle custom ratio fields
    const mortarFormulaType = scope.querySelector('#mortarFormulaType') || document.getElementById('mortarFormulaType');
    if (mortarFormulaType) {
        mortarFormulaType.addEventListener('change', function() {
            const customFields = scope.querySelector('#customRatioFields') || document.getElementById('customRatioFields');
            if (customFields) {
                if (this.value === 'custom') {
                    customFields.style.display = 'flex';
                } else {
                    customFields.style.display = 'none';
                }
            }
        });
    }

    // Toggle custom material selection form
    const priceFilter = scope.querySelector('#priceFilter') || document.getElementById('priceFilter');
    if (priceFilter) {
        priceFilter.addEventListener('change', function() {
            const customForm = scope.querySelector('#customMaterialForm') || document.getElementById('customMaterialForm');
            if (customForm) {
                if (this.value === 'custom') {
                    customForm.style.display = 'block';
                } else {
                    customForm.style.display = 'none';
                }
            }
        });
    }

    // Cement type change handler
    const customCementType = scope.querySelector('#customCementType') || document.getElementById('customCementType');
    if (customCementType) {
        customCementType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customCementBrand') || document.getElementById('customCementBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredCements = cementsData.filter(c => c.cement_name === type);
                    filteredCements.forEach(cement => {
                        const option = document.createElement('option');
                        option.value = cement.id;
                        const price = formatFixedLocale(cement.package_price || 0, 0);
                        option.textContent = `${cement.brand} (${cement.package_weight_net}kg) - Rp ${price}`;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Sand type change handler
    const customSandType = scope.querySelector('#customSandType') || document.getElementById('customSandType');
    if (customSandType) {
        customSandType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customSandBrand') || document.getElementById('customSandBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredSands = sandsData.filter(s => s.sand_name === type);
                    const uniqueBrands = [...new Set(filteredSands.map(s => s.brand))];

                    uniqueBrands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Sand brand change handler
    const customSandBrand = scope.querySelector('#customSandBrand') || document.getElementById('customSandBrand');
    if (customSandBrand) {
        customSandBrand.addEventListener('change', function() {
            const brand = this.value;
            const typeEl = scope.querySelector('#customSandType') || document.getElementById('customSandType');
            const packageSelect = scope.querySelector('#customSandPackage') || document.getElementById('customSandPackage');

            if (packageSelect && typeEl) {
                const type = typeEl.value;
                packageSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';

                if (brand && type) {
                    const filteredSands = sandsData.filter(s => s.brand === brand && s.sand_name === type);
                    filteredSands.forEach(sand => {
                        const option = document.createElement('option');
                        option.value = sand.id;
                        const pricePerM3 = sand.comparison_price_per_m3 || (sand.package_price / sand.package_volume);
                        const roundedPrice = formatFixedLocale(pricePerM3 || 0, 0);
                        option.textContent = `${sand.package_volume} M3 - Rp ${roundedPrice}/M3`;
                        packageSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Cat type change handler
    const customCatType = scope.querySelector('#customCatType') || document.getElementById('customCatType');
    if (customCatType) {
        customCatType.addEventListener('change', function() {
            const type = this.value;
            const brandSelect = scope.querySelector('#customCatBrand') || document.getElementById('customCatBrand');

            if (brandSelect) {
                brandSelect.innerHTML = '<option value="">-- Pilih Merk --</option>';

                if (type) {
                    const filteredCats = catsData.filter(c => c.cat_name === type);
                    const uniqueBrands = [...new Set(filteredCats.map(c => c.brand))];

                    uniqueBrands.forEach(brand => {
                        const option = document.createElement('option');
                        option.value = brand;
                        option.textContent = brand;
                        brandSelect.appendChild(option);
                    });
                }
            }
        });
    }

    // Cat brand change handler
    const customCatBrand = scope.querySelector('#customCatBrand') || document.getElementById('customCatBrand');
    if (customCatBrand) {
        customCatBrand.addEventListener('change', function() {
            const brand = this.value;
            const typeEl = scope.querySelector('#customCatType') || document.getElementById('customCatType');
            const packageSelect = scope.querySelector('#customCatPackage') || document.getElementById('customCatPackage');

            if (packageSelect && typeEl) {
                const type = typeEl.value;
                packageSelect.innerHTML = '<option value="">-- Pilih Kemasan --</option>';

                if (brand && type) {
                    const filteredCats = catsData.filter(c => c.brand === brand && c.cat_name === type);
                    filteredCats.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        const price = formatFixedLocale(cat.purchase_price || 0, 0);
                        option.textContent = `${cat.package_weight_net} kg - Rp ${price}`;
                        packageSelect.appendChild(option);
                    });
                }
            }
        });
    }
}
