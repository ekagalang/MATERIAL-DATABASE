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
    const storeLocationsDataRaw = Array.isArray(formData?.storeLocations) ? formData.storeLocations : [];
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
    const storeLocationsData = storeLocationsDataRaw
        .map(function (location) {
            const lat = Number(location?.latitude);
            const lng = Number(location?.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return null;
            }

            return {
                id: Number(location?.id || 0),
                storeName: String(location?.store_name || 'Toko'),
                address: String(location?.address || ''),
                latitude: lat,
                longitude: lng,
                serviceRadiusKm: location?.service_radius_km !== null && location?.service_radius_km !== undefined
                    ? Number(location.service_radius_km)
                    : null,
            };
        })
        .filter(Boolean);

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildStoreInfoContent(location) {
        const addressText = location.address ? escapeHtml(location.address) : '-';
        const radiusText = Number.isFinite(location.serviceRadiusKm)
            ? `<div style="font-size:12px;color:#475569;">Radius layanan: ${location.serviceRadiusKm} km</div>`
            : '';

        return `
            <div style="min-width:220px;line-height:1.45;">
                <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">${escapeHtml(location.storeName)}</div>
                <div style="font-size:12px;color:#64748b;">${addressText}</div>
                ${radiusText}
            </div>
        `;
    }

    function createStoreMarkerIcon(mapElement) {
        const iconUrl = mapElement?.dataset?.storeMarkerIcon || '/images/store-marker.svg';
        return {
            url: iconUrl,
            scaledSize: new google.maps.Size(30, 30),
            anchor: new google.maps.Point(15, 30),
        };
    }

    function addStoreMarkersToMap(map, mapElement) {
        if (!map || !window.google?.maps || map.__storeMarkersBound) {
            return;
        }

        map.__storeMarkersBound = true;
        if (!storeLocationsData.length) {
            return;
        }

        const infoWindow = new google.maps.InfoWindow();
        const icon = createStoreMarkerIcon(mapElement);
        const bounds = new google.maps.LatLngBounds();
        const projectLatInput = scope.querySelector('#projectLatitude') || document.getElementById('projectLatitude');
        const projectLngInput = scope.querySelector('#projectLongitude') || document.getElementById('projectLongitude');
        const projectLatRaw = (projectLatInput?.value ?? '').toString().trim();
        const projectLngRaw = (projectLngInput?.value ?? '').toString().trim();
        const hasProjectLocation =
            projectLatRaw !== '' &&
            projectLngRaw !== '' &&
            Number.isFinite(Number(projectLatRaw)) &&
            Number.isFinite(Number(projectLngRaw));

        const markers = storeLocationsData.map(function (location) {
            const position = { lat: location.latitude, lng: location.longitude };
            bounds.extend(position);

            const marker = new google.maps.Marker({
                map,
                position,
                title: location.storeName,
                icon,
                zIndex: 10,
            });

            marker.addListener('click', function () {
                infoWindow.setContent(buildStoreInfoContent(location));
                infoWindow.open(map, marker);
            });

            return marker;
        });

        map.__storeMarkers = markers;

        if (!hasProjectLocation && markers.length > 0) {
            map.fitBounds(bounds, 70);
            const listener = google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                if (map.getZoom() > 14) {
                    map.setZoom(14);
                }
            });
            if (listener) {
                map.__storeBoundsListener = listener;
            }
        }
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
        }).then((picker) => {
            addStoreMarkersToMap(picker?.map, mapElement);
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

        const baseOptions = formulasData
            .filter(option => option && option.code && option.name)
            .map(option => ({
                code: String(option.code),
                name: String(option.name),
            }));

        if (baseOptions.length === 0) {
            return;
        }

        function getScopedOptions() {
            const provider = window.MaterialCalculationWorkTypeOptionsProvider;
            if (typeof provider === 'function') {
                try {
                    const external = provider();
                    if (Array.isArray(external) && external.length > 0) {
                        const normalized = external
                            .filter(option => option && option.code && option.name)
                            .map(option => ({
                                code: String(option.code),
                                name: String(option.name),
                            }));
                        if (normalized.length > 0) {
                            return normalized;
                        }
                    }
                } catch (error) {
                    console.warn('MaterialCalculationWorkTypeOptionsProvider failed:', error);
                }
            }

            return baseOptions;
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
            const options = getScopedOptions();
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
            const options = getScopedOptions();
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
            const options = getScopedOptions();
            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected && displayInput.value !== selected.name) {
                displayInput.value = selected.name;
            }
        });

        const refreshOptions = function() {
            const options = getScopedOptions();
            if (!hiddenInput.value && !displayInput.value) {
                return;
            }

            const selected = options.find(option => option.code === hiddenInput.value);
            if (selected) {
                if (displayInput.value !== selected.name) {
                    displayInput.value = selected.name;
                }
                return;
            }

            if (listEl.style.display === 'block') {
                renderList(filterOptions(displayInput.value || ''));
            }
        };

        document.addEventListener('material-calculation:refresh-work-type-options', refreshOptions);
        displayInput.__refreshWorkTypeOptions = refreshOptions;

        if (hiddenInput.value && !displayInput.value) {
            const selected = getScopedOptions().find(option => option.code === hiddenInput.value);
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

    function setupCustomMaterialAdvancedFilters() {
        const uniqueSorted = values => Array.from(new Set(values.filter(Boolean).map(value => String(value).trim())))
            .sort((a, b) => String(a).localeCompare(String(b), 'id-ID', { sensitivity: 'base', numeric: true }));
        let customizeUiSequence = 0;

        const toPlainNumber = value => {
            const num = Number(value);
            if (!isFinite(num) || num <= 0) return '';
            return formatDynamicPlain(num);
        };

        const formatDim2 = item => {
            const l = toPlainNumber(item?.dimension_length);
            const w = toPlainNumber(item?.dimension_width);
            if (!l || !w) return '';
            return `${l} x ${w} cm`;
        };

        const formatDim3 = item => {
            const l = toPlainNumber(item?.dimension_length);
            const w = toPlainNumber(item?.dimension_width);
            const h = toPlainNumber(item?.dimension_height);
            if (!l || !w || !h) return '';
            return `${l} x ${w} x ${h} cm`;
        };

        const formatWeight = item => {
            const value = toPlainNumber(item?.package_weight_net);
            return value ? `${value} kg` : '';
        };

        const formatCatSummary = item => {
            const unit = String(item?.package_unit || '').trim() || '-';
            const volVal = toPlainNumber(item?.volume);
            const volUnit = String(item?.volume_unit || '').trim();
            const volume = volVal ? `${volVal} ${volUnit || ''}`.trim() : '-';
            const weight = formatWeight(item) || '-';
            return `${unit} | ${volume} | ${weight}`;
        };

        const formatVolume = item => {
            const value = toPlainNumber(item?.volume);
            if (!value) return '';
            const unit = String(item?.volume_unit || '').trim();
            return `${value}${unit ? ` ${unit}` : ''}`;
        };

        const materialConfigs = {
            brick: {
                data: Array.isArray(bricksData) ? bricksData : [],
                selectId: 'customBrick',
                emptyOption: '-- Semua Bata (Auto) --',
                materialTypeKeys: ['brick'],
                fields: ['brand', 'dimension'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'brick') return String(item?.type || '').trim();
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'brand') return String(item?.brand || '').trim();
                    if (key === 'dimension') return formatDim3(item);
                    return '';
                },
                optionLabel(item) {
                    const dim = formatDim3(item) || '-';
                    const type = String(item?.type || '').trim();
                    const price = formatFixedLocale(item?.price_per_piece || 0, 0);
                    return `${item?.brand || '-'}${type ? ` - ${type}` : ''} (${dim}) - Rp ${price}`;
                },
            },
            cement: {
                data: Array.isArray(cementsData) ? cementsData : [],
                selectId: 'customCement',
                emptyOption: '-- Semua Semen (Auto) --',
                materialTypeKeys: ['cement'],
                fields: ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'cement') return String(item?.type || '').trim();
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'package_weight_net') return formatWeight(item);
                    return String(item?.[key] || '').trim();
                },
                optionLabel(item) {
                    const parts = [
                        item?.brand || '-',
                        item?.sub_brand || '',
                        item?.code || '',
                        item?.color || '',
                    ].filter(Boolean);
                    const suffix = `${item?.package_unit || '-'}, ${formatWeight(item) || '-'}`;
                    return `${parts.join(' - ')} (${suffix})`;
                },
            },
            sand: {
                data: Array.isArray(sandsData) ? sandsData : [],
                selectId: 'customSand',
                emptyOption: '-- Semua Pasir (Auto) --',
                materialTypeKeys: ['sand'],
                fields: ['brand'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'sand') return String(item?.type || '').trim();
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'brand') return String(item?.brand || '').trim();
                    return '';
                },
                optionLabel(item) {
                    const volume = toPlainNumber(item?.package_volume);
                    return `${item?.brand || '-'} (${item?.package_unit || '-'}${volume ? `, ${volume} M3` : ''})`;
                },
            },
            cat: {
                data: Array.isArray(catsData) ? catsData : [],
                selectId: 'customCat',
                emptyOption: '-- Semua Cat (Auto) --',
                materialTypeKeys: ['cat'],
                fields: ['brand', 'sub_brand', 'color_code', 'color_name', 'package_unit', 'volume_display', 'package_weight_net'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'cat') return String(item?.type || '').trim();
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'volume_display') return formatVolume(item);
                    if (key === 'package_weight_net') return formatWeight(item);
                    return String(item?.[key] || '').trim();
                },
                optionLabel(item) {
                    const parts = [
                        item?.brand || '-',
                        item?.sub_brand || '',
                        item?.color_code || '',
                        item?.color_name || '',
                    ].filter(Boolean);
                    return `${parts.join(' - ')} (${formatCatSummary(item)})`;
                },
            },
            ceramic: {
                data: Array.isArray(ceramicsData) ? ceramicsData : [],
                selectId: 'customCeramic',
                emptyOption: '-- Semua Keramik (Auto) --',
                materialTypeKeys: ['ceramic_type', 'ceramic'],
                fields: ['brand', 'dimension', 'sub_brand', 'surface', 'code', 'color'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'ceramic_type') return String(item?.type || '').trim();
                    if (typeKey === 'ceramic') return formatDim2(item);
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'dimension') return formatDim2(item);
                    return String(item?.[key] || '').trim();
                },
                optionLabel(item) {
                    const base = [
                        item?.brand || '-',
                        item?.sub_brand || '',
                        item?.surface || '',
                        item?.code || '',
                        item?.color || '',
                    ].filter(Boolean).join(' - ');
                    return `${base} (${formatDim2(item) || '-'})`;
                },
            },
            nat: {
                data: Array.isArray(natsData) ? natsData : [],
                selectId: 'customNat',
                emptyOption: '-- Semua Nat (Auto) --',
                materialTypeKeys: ['nat'],
                fields: ['brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net'],
                resolveMaterialTypeValue(item, typeKey) {
                    if (typeKey === 'nat') return String(item?.type || '').trim();
                    return '';
                },
                resolveFieldValue(item, key) {
                    if (key === 'package_weight_net') return formatWeight(item);
                    return String(item?.[key] || '').trim();
                },
                optionLabel(item) {
                    const parts = [
                        item?.brand || '-',
                        item?.sub_brand || '',
                        item?.code || '',
                        item?.color || '',
                    ].filter(Boolean);
                    const suffix = `${item?.package_unit || '-'}, ${formatWeight(item) || '-'}`;
                    return `${parts.join(' - ')} (${suffix})`;
                },
            },
        };

                Object.entries(materialConfigs).forEach(([materialKey, config]) => {
            const mainSelect = scope.querySelector(`#${config.selectId}`) || document.getElementById(config.selectId);
            if (!mainSelect || !Array.isArray(config.data) || config.data.length === 0) {
                return;
            }

            const getToggleButtons = () => {
                const toggleBtnSet = new Set([
                    ...Array.from(scope.querySelectorAll(`[data-customize-toggle="${materialKey}"]`)),
                    ...Array.from(document.querySelectorAll(`[data-customize-toggle="${materialKey}"]`)),
                ]);
                return Array.from(toggleBtnSet);
            };

            const getAllPanels = () => {
                const panelSet = new Set([
                    ...Array.from(scope.querySelectorAll(`[data-customize-panel="${materialKey}"]`)),
                    ...Array.from(document.querySelectorAll(`[data-customize-panel="${materialKey}"]`)),
                ]);
                const idPanel = scope.querySelector(`#customizePanel-${materialKey}`) || document.getElementById(`customizePanel-${materialKey}`);
                if (idPanel) {
                    panelSet.add(idPanel);
                }
                return Array.from(panelSet);
            };

            const getPanelForToggle = toggleBtn => {
                if (!(toggleBtn instanceof HTMLElement)) {
                    return null;
                }
                const explicitPanelId = String(toggleBtn.dataset.customizePanelId || '').trim();
                if (explicitPanelId) {
                    const explicitPanel = document.getElementById(explicitPanelId);
                    if (explicitPanel) {
                        return explicitPanel;
                    }
                }
                const wrap = toggleBtn.closest('.material-type-filter-item, .additional-material-filter-item, [data-material-wrap]');
                if (wrap) {
                    const panelInWrap = wrap.querySelector(`[data-customize-panel="${materialKey}"]`);
                    if (panelInWrap) {
                        return panelInWrap;
                    }
                }
                return scope.querySelector(`#customizePanel-${materialKey}`) || document.getElementById(`customizePanel-${materialKey}`);
            };

            const getToggleButtonsForPanel = panelEl => {
                if (!(panelEl instanceof HTMLElement)) {
                    return getToggleButtons();
                }
                const panelId = String(panelEl.id || '').trim();
                if (panelId) {
                    const idMatchedButtons = Array.from(document.querySelectorAll(`[data-customize-panel-id="${panelId}"]`));
                    if (idMatchedButtons.length > 0) {
                        return idMatchedButtons;
                    }
                }
                const wrap = panelEl.closest('.material-type-filter-item, .additional-material-filter-item, [data-material-wrap]');
                if (!wrap) {
                    return getToggleButtons();
                }
                return Array.from(wrap.querySelectorAll(`[data-customize-toggle="${materialKey}"]`));
            };

            const setupCustomizeAutocomplete = selectEl => {
                if (!selectEl || selectEl.dataset.customizeAutocompleteBound === '1') return;

                const inputWrapper = selectEl.closest('.input-wrapper');
                if (!inputWrapper) return;

                const autocompleteEl = document.createElement('div');
                autocompleteEl.className = 'work-type-autocomplete customize-filter-autocomplete';

                const inputShellEl = document.createElement('div');
                inputShellEl.className = 'work-type-input';

                const displayEl = document.createElement('input');
                displayEl.type = 'text';
                displayEl.className = 'autocomplete-input customize-filter-display';
                displayEl.autocomplete = 'off';

                const listEl = document.createElement('div');
                listEl.className = 'autocomplete-list';
                listEl.id = `${selectEl.id || `customize-filter-${materialKey}-${++customizeUiSequence}`}-list`;

                inputShellEl.appendChild(displayEl);
                autocompleteEl.appendChild(inputShellEl);
                autocompleteEl.appendChild(listEl);

                inputWrapper.appendChild(autocompleteEl);
                selectEl.style.display = 'none';
                selectEl.tabIndex = -1;
                selectEl.dataset.customizeAutocompleteBound = '1';

                const closeList = () => {
                    listEl.style.display = 'none';
                };

                const getVisibleOptions = (term = '') => {
                    const query = String(term || '').trim().toLowerCase();
                    const options = Array.from(selectEl.options)
                        .filter(option => String(option.value || '').trim() !== '');

                    if (!query) return options;
                    return options.filter(option => {
                        return String(option.textContent || '').toLowerCase().includes(query);
                    });
                };

                const syncDisplayFromSelect = () => {
                    const selectedOption = selectEl.options[selectEl.selectedIndex];
                    displayEl.value = selectedOption && selectedOption.value ? String(selectedOption.textContent || '') : '';
                    const firstOption = selectEl.options[0];
                    displayEl.placeholder = String(firstOption?.textContent || '-- Semua --');
                };

                const applyValue = value => {
                    const nextValue = String(value || '').trim();
                    if (selectEl.value !== nextValue) {
                        selectEl.value = nextValue;
                        selectEl.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        syncDisplayFromSelect();
                    }
                    closeList();
                };

                const renderList = term => {
                    listEl.innerHTML = '';

                    const clearItem = document.createElement('div');
                    clearItem.className = 'autocomplete-item';
                    clearItem.textContent = '- Tidak Pilih -';
                    clearItem.addEventListener('click', () => applyValue(''));
                    listEl.appendChild(clearItem);

                    getVisibleOptions(term).forEach(option => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = String(option.textContent || '');
                        item.addEventListener('click', () => applyValue(option.value));
                        listEl.appendChild(item);
                    });

                    listEl.style.display = 'block';
                };

                const findExactMatch = term => {
                    const query = String(term || '').trim().toLowerCase();
                    if (!query) return null;

                    return getVisibleOptions('').find(option => {
                        return String(option.textContent || '').trim().toLowerCase() === query;
                    }) || null;
                };

                displayEl.addEventListener('focus', function() {
                    renderList(displayEl.value || '');
                });

                displayEl.addEventListener('input', function() {
                    const term = String(displayEl.value || '');
                    renderList(term);

                    if (!term.trim()) {
                        applyValue('');
                        return;
                    }

                    const exact = findExactMatch(term);
                    if (exact) {
                        applyValue(exact.value);
                    } else if (selectEl.value) {
                        applyValue('');
                    }
                });

                displayEl.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        const exact = findExactMatch(displayEl.value || '');
                        if (exact) {
                            applyValue(exact.value);
                            event.preventDefault();
                        }
                    } else if (event.key === 'Escape') {
                        closeList();
                    }
                });

                displayEl.addEventListener('blur', function() {
                    setTimeout(() => {
                        syncDisplayFromSelect();
                        closeList();
                    }, 150);
                });

                document.addEventListener('click', function(event) {
                    if (event.target === displayEl || listEl.contains(event.target)) return;
                    closeList();
                });

                selectEl.addEventListener('change', function() {
                    syncDisplayFromSelect();
                });

                selectEl.__customizeAutocompleteUi = {
                    syncDisplayFromSelect,
                    renderList,
                    listEl,
                    displayEl,
                };

                syncDisplayFromSelect();
            };

            const resolveFieldValue = (item, key) => String(config.resolveFieldValue(item, key) || '').trim();
            const materialTypeKeys = Array.isArray(config.materialTypeKeys) ? config.materialTypeKeys : [];
            const resolveMaterialTypeValue = (item, typeKey) => {
                if (typeof config.resolveMaterialTypeValue === 'function') {
                    return String(config.resolveMaterialTypeValue(item, typeKey) || '').trim();
                }
                return String(item?.type || '').trim();
            };

            const tokenize = value => String(value || '')
                .split('|')
                .map(part => String(part || '').trim())
                .filter(Boolean);

            const pushTokenValues = (collector, seen, rawValue) => {
                tokenize(rawValue).forEach(token => {
                    const norm = token.toLowerCase();
                    if (!seen.has(norm)) {
                        seen.add(norm);
                        collector.push(token);
                    }
                });
            };

            const resolveContextRoot = contextRow => {
                if (!(contextRow instanceof HTMLElement)) {
                    return null;
                }
                const additionalItem = contextRow.closest('[data-additional-work-item="true"]');
                if (additionalItem) {
                    return additionalItem;
                }
                const mainContainer = contextRow.closest('#inputFormContainer');
                if (mainContainer) {
                    return mainContainer;
                }
                return null;
            };

            const collectContextInputsByType = (rootEl, typeKey) => {
                if (!rootEl) return [];
                const selectorParts = [
                    `input[name="material_type_filters[${typeKey}]"]`,
                    `input[name="material_type_filters_extra[${typeKey}][]"]`,
                    `input[data-field="material_type_${typeKey}"][data-material-type-hidden="1"]`,
                    `#materialTypeSelector-${typeKey}`,
                ];
                return Array.from(rootEl.querySelectorAll(selectorParts.join(', ')));
            };

            const collectRowInputsByType = (rowEl, typeKey) => {
                if (!rowEl) return [];
                const selectorParts = [
                    `input[name="material_type_filters[${typeKey}]"]`,
                    `input[name="material_type_filters_extra[${typeKey}][]"]`,
                    `input[data-field="material_type_${typeKey}"][data-material-type-hidden="1"]`,
                    'input[data-material-type-hidden="1"]',
                ];
                return Array.from(rowEl.querySelectorAll(selectorParts.join(', ')));
            };

            const getMaterialTypeSelectedValues = (typeKey, contextRow = null) => {
                const values = [];
                const seen = new Set();

                if (contextRow instanceof HTMLElement) {
                    const rowType = String(contextRow.dataset.materialType || '').trim();
                    if (rowType === typeKey) {
                        collectRowInputsByType(contextRow, typeKey).forEach(inputEl => {
                            pushTokenValues(values, seen, inputEl?.value || '');
                        });
                        if (values.length > 0) {
                            return values;
                        }
                    }

                    const contextRoot = resolveContextRoot(contextRow);
                    collectContextInputsByType(contextRoot, typeKey).forEach(inputEl => {
                        pushTokenValues(values, seen, inputEl?.value || '');
                    });
                    return values;
                }

                const directInputs = [
                    ...(scope.querySelectorAll(`#materialTypeSelector-${typeKey}`) || []),
                    ...(scope.querySelectorAll(`input[name="material_type_filters[${typeKey}]"]`) || []),
                    ...(scope.querySelectorAll(`input[name="material_type_filters_extra[${typeKey}][]"]`) || []),
                    ...(document.querySelectorAll(`#materialTypeSelector-${typeKey}`) || []),
                    ...(document.querySelectorAll(`input[name="material_type_filters[${typeKey}]"]`) || []),
                    ...(document.querySelectorAll(`input[name="material_type_filters_extra[${typeKey}][]"]`) || []),
                ];

                directInputs.forEach(inputEl => {
                    pushTokenValues(values, seen, inputEl?.value || '');
                });

                return values;
            };

            const applyMaterialTypeBaseFilter = (rows, contextRow = null) => {
                let filtered = Array.isArray(rows) ? rows : [];

                materialTypeKeys.forEach(typeKey => {
                    const selectedValues = getMaterialTypeSelectedValues(typeKey, contextRow);
                    if (!selectedValues.length) return;

                    const selectedSet = new Set(selectedValues.map(value => String(value || '').trim().toLowerCase()));
                    filtered = filtered.filter(item => {
                        const itemTokens = tokenize(resolveMaterialTypeValue(item, typeKey))
                            .map(token => token.toLowerCase());
                        if (itemTokens.length === 0) return false;
                        return itemTokens.some(token => selectedSet.has(token));
                    });
                });

                return filtered;
            };

            const panelStates = new WeakMap();

            const getDataByPrefixSelection = (state, fieldIndex) => {
                const activeFilters = {};
                state.fieldSelects.slice(0, fieldIndex).forEach(selectEl => {
                    const key = selectEl.dataset.filterKey;
                    const value = String(selectEl.value || '').trim();
                    if (key && value) {
                        activeFilters[key] = value;
                    }
                });

                const baseRows = applyMaterialTypeBaseFilter(config.data, state.contextRow);
                return baseRows.filter(item => Object.entries(activeFilters).every(([key, value]) => {
                    return resolveFieldValue(item, key) === value;
                }));
            };

            const getDataByAllSelections = state => {
                const activeFilters = {};
                state.fieldSelects.forEach(selectEl => {
                    const key = selectEl.dataset.filterKey;
                    const value = String(selectEl.value || '').trim();
                    if (key && value) {
                        activeFilters[key] = value;
                    }
                });
                const baseRows = applyMaterialTypeBaseFilter(config.data, state.contextRow);
                return baseRows.filter(item => Object.entries(activeFilters).every(([key, value]) => {
                    return resolveFieldValue(item, key) === value;
                }));
            };

            const renderFieldOptions = (selectEl, sourceRows, keepCurrent = true) => {
                if (!selectEl) return;
                const filterKey = selectEl.dataset.filterKey;
                const existingValue = keepCurrent ? String(selectEl.value || '').trim() : '';
                const values = uniqueSorted(sourceRows.map(item => resolveFieldValue(item, filterKey)));
                const placeholder = selectEl.dataset.placeholder || '-- Semua --';

                selectEl.innerHTML = '';
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = placeholder;
                selectEl.appendChild(emptyOption);

                values.forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    if (existingValue && existingValue === value) {
                        option.selected = true;
                    }
                    selectEl.appendChild(option);
                });

                if (existingValue && !values.includes(existingValue)) {
                    selectEl.value = '';
                }

                if (selectEl.__customizeAutocompleteUi?.syncDisplayFromSelect) {
                    selectEl.__customizeAutocompleteUi.syncDisplayFromSelect();
                }
            };

            const updateButtonState = state => {
                const hasActiveFilter = state.fieldSelects.some(selectEl => String(selectEl.value || '').trim() !== '');
                getToggleButtonsForPanel(state.panelEl).forEach(btn => btn.classList.toggle('is-active', hasActiveFilter));
            };

            const updateProgressiveFieldVisibility = state => {
                if (!state || !Array.isArray(state.fieldSelects)) {
                    return;
                }

                let lastFilledIndex = -1;
                state.fieldSelects.forEach((selectEl, index) => {
                    if (String(selectEl.value || '').trim() !== '') {
                        lastFilledIndex = index;
                    }
                });

                const maxVisibleIndex = Math.min(
                    state.fieldSelects.length - 1,
                    Math.max(0, lastFilledIndex + 1),
                );

                state.fieldSelects.forEach((selectEl, index) => {
                    const groupEl = selectEl.closest('.form-group');
                    if (!groupEl) {
                        return;
                    }
                    const shouldShow = index <= maxVisibleIndex;
                    groupEl.style.display = shouldShow ? '' : 'none';
                });
            };

            const panelHasActiveFilter = state => {
                if (!state || !Array.isArray(state.fieldSelects)) {
                    return false;
                }
                return state.fieldSelects.some(selectEl => String(selectEl.value || '').trim() !== '');
            };

            const closePanelIfEmpty = panelEl => {
                if (!(panelEl instanceof HTMLElement) || panelEl.hidden) {
                    return;
                }
                const state = ensurePanelState(panelEl);
                if (!state) {
                    return;
                }
                if (panelHasActiveFilter(state)) {
                    updateProgressiveFieldVisibility(state);
                    return;
                }
                panelEl.hidden = true;
                updateButtonState(state);
            };

            const collapseEmptyOpenPanels = (exceptPanel = null) => {
                getAllPanels().forEach(panelEl => {
                    if (!(panelEl instanceof HTMLElement)) {
                        return;
                    }
                    if (exceptPanel && panelEl === exceptPanel) {
                        return;
                    }
                    closePanelIfEmpty(panelEl);
                });
            };

            const renderMaterialOptions = state => {
                const selectedRows = getDataByAllSelections(state);
                const currentValue = String(mainSelect.value || '').trim();

                mainSelect.innerHTML = '';
                const emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = config.emptyOption;
                mainSelect.appendChild(emptyOption);

                selectedRows.forEach(item => {
                    const option = document.createElement('option');
                    option.value = String(item?.id ?? '');
                    option.textContent = config.optionLabel(item);
                    if (currentValue && option.value === currentValue) {
                        option.selected = true;
                    }
                    mainSelect.appendChild(option);
                });

                if (currentValue && !selectedRows.some(item => String(item?.id ?? '') === currentValue)) {
                    mainSelect.value = '';
                }
            };

            const refreshFromFieldIndex = (state, index = 0) => {
                for (let i = index; i < state.fieldSelects.length; i++) {
                    const selectEl = state.fieldSelects[i];
                    renderFieldOptions(selectEl, getDataByPrefixSelection(state, i), true);
                }
                renderMaterialOptions(state);
                updateButtonState(state);
                updateProgressiveFieldVisibility(state);
            };

            const ensurePanelState = panelEl => {
                if (!(panelEl instanceof HTMLElement)) {
                    return null;
                }
                const existing = panelStates.get(panelEl);
                if (existing) {
                    return existing;
                }

                const fieldSelects = Array.from(panelEl.querySelectorAll(`[data-customize-filter="${materialKey}"][data-filter-key]`));
                const state = {
                    panelEl,
                    fieldSelects,
                    contextRow: null,
                };

                fieldSelects.forEach((selectEl, index) => {
                    if (!selectEl.dataset.placeholder) {
                        const labelEl = selectEl.closest('.form-group')?.querySelector('label');
                        const labelText = labelEl ? labelEl.textContent.replace(':', '').trim() : 'Filter';
                        selectEl.dataset.placeholder = `-- Semua ${labelText} --`;
                    }

                    setupCustomizeAutocomplete(selectEl);
                    selectEl.addEventListener('change', function() {
                        for (let i = index + 1; i < fieldSelects.length; i++) {
                            fieldSelects[i].value = '';
                        }
                        refreshFromFieldIndex(state, index + 1);
                    });
                });

                panelStates.set(panelEl, state);
                return state;
            };

            if (materialTypeKeys.length > 0) {
                document.addEventListener('change', function(event) {
                    const target = event?.target;
                    if (!(target instanceof HTMLElement)) return;

                    const targetId = String(target.id || '');
                    const targetName = String(target.getAttribute('name') || '');
                    const shouldRefresh = materialTypeKeys.some(typeKey => {
                        if (targetId === `materialTypeSelector-${typeKey}`) return true;
                        if (targetName === `material_type_filters[${typeKey}]`) return true;
                        if (targetName === `material_type_filters_extra[${typeKey}][]`) return true;
                        return false;
                    });

                    if (!shouldRefresh) {
                        return;
                    }
                    getAllPanels().forEach(panelEl => {
                        if (!(panelEl instanceof HTMLElement) || panelEl.hidden) {
                            return;
                        }
                        const state = ensurePanelState(panelEl);
                        if (state) {
                            refreshFromFieldIndex(state, 0);
                        }
                    });
                });
            }

            document.addEventListener('click', function(event) {
                const target = event?.target;
                if (!(target instanceof HTMLElement)) return;
                const toggleBtn = target.closest(`[data-customize-toggle="${materialKey}"]`);
                if (!toggleBtn) {
                    const clickedInsidePanel = target.closest(`.customize-panel[data-customize-panel="${materialKey}"]`);
                    if (!clickedInsidePanel) {
                        collapseEmptyOpenPanels();
                    }
                    return;
                }

                const panel = getPanelForToggle(toggleBtn);
                if (!panel) return;
                const panelState = ensurePanelState(panel);
                if (!panelState) return;

                const nextContextRow = toggleBtn.closest('.material-type-row');
                const isSameOpenContext =
                    !panel.hidden &&
                    panelState.contextRow &&
                    nextContextRow &&
                    panelState.contextRow === nextContextRow;

                if (isSameOpenContext) {
                    panel.hidden = true;
                    return;
                }

                const hasContextChanged = panelState.contextRow !== nextContextRow;
                panelState.contextRow = nextContextRow instanceof HTMLElement ? nextContextRow : null;

                if (hasContextChanged) {
                    panelState.fieldSelects.forEach(selectEl => {
                        selectEl.value = '';
                    });
                }

                panel.hidden = false;
                refreshFromFieldIndex(panelState, 0);
                collapseEmptyOpenPanels(panel);
            });

            document.addEventListener('focusin', function(event) {
                const target = event?.target;
                if (!(target instanceof HTMLElement)) return;
                if (target.closest(`[data-customize-toggle="${materialKey}"]`)) return;
                if (target.closest(`.customize-panel[data-customize-panel="${materialKey}"]`)) return;
                collapseEmptyOpenPanels();
            });
        });
    }

    setupCustomMaterialAdvancedFilters();
}

