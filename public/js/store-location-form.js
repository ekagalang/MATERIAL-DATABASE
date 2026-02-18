function initStoreLocationContactRepeater(scope) {
    const root = scope || document;
    const list = root.querySelector('[data-contact-list]');
    const addBtn = root.querySelector('[data-contact-add]');

    if (!list || !addBtn || list.dataset.repeaterInit === '1') {
        return;
    }

    const template =
        root.querySelector('[data-contact-template]') ||
        root.querySelector('#store-location-contact-row-template') ||
        root.querySelector('#store-location-contact-row-template-edit');

    if (!template) {
        return;
    }

    list.dataset.repeaterInit = '1';

    const updateRowsState = function () {
        const rows = list.querySelectorAll('[data-contact-row]');
        rows.forEach(function (row) {
            const removeBtn = row.querySelector('[data-contact-remove]');
            if (!removeBtn) {
                return;
            }
            removeBtn.disabled = rows.length === 1;
        });
    };

    const buildRowNode = function () {
        if (template.content) {
            return template.content.cloneNode(true);
        }

        return template.cloneNode(true);
    };

    addBtn.addEventListener('click', function () {
        list.appendChild(buildRowNode());
        initPhoneInputGuard(root);
        updateRowsState();

        const rows = list.querySelectorAll('[data-contact-row]');
        const latestRow = rows[rows.length - 1];
        const nameInput = latestRow ? latestRow.querySelector('input[name="contact_name[]"]') : null;
        if (nameInput) {
            nameInput.focus();
        }
    });

    list.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('[data-contact-remove]');
        if (!removeBtn) {
            return;
        }

        const rows = list.querySelectorAll('[data-contact-row]');
        if (rows.length <= 1) {
            return;
        }

        const row = removeBtn.closest('[data-contact-row]');
        if (row) {
            row.remove();
            updateRowsState();
        }
    });

    updateRowsState();
}

function initPhoneInputGuard(scope) {
    const root = scope || document;
    const phoneInputs = root.querySelectorAll('input[name="contact_phone[]"], input[name="contact_phone"]');

    if (!phoneInputs.length) {
        return;
    }

    const sanitize = function (value) {
        return String(value || '').replace(/[^0-9+\-\s]/g, '');
    };

    const insertAtCursor = function (input, text) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const value = input.value || '';
        input.value = value.slice(0, start) + text + value.slice(end);
        const nextPos = start + text.length;
        input.setSelectionRange(nextPos, nextPos);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    };

    phoneInputs.forEach(function (input) {
        if (input.dataset.phoneGuardInit === '1') {
            return;
        }
        input.dataset.phoneGuardInit = '1';

        input.setAttribute('inputmode', 'tel');
        input.setAttribute('pattern', '[0-9+\\- ]*');
        input.setAttribute('autocomplete', 'off');

        input.addEventListener('keydown', function (event) {
            if (
                event.ctrlKey ||
                event.metaKey ||
                event.altKey ||
                ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)
            ) {
                return;
            }

            if (!/^[0-9+\-\s]$/.test(event.key)) {
                event.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            const cleaned = sanitize(input.value);
            if (cleaned !== input.value) {
                const cursorPos = input.selectionStart;
                input.value = cleaned;
                if (cursorPos !== null) {
                    const nextPos = Math.min(cursorPos - 1, input.value.length);
                    input.setSelectionRange(Math.max(nextPos, 0), Math.max(nextPos, 0));
                }
            }
        });

        input.addEventListener('paste', function (event) {
            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData).getData('text');
            const cleaned = sanitize(pasted);
            insertAtCursor(input, cleaned);
        });
    });
}

function initStoreLocationForm(root) {
    const scope = root || document;
    initStoreLocationContactRepeater(scope);
    initPhoneInputGuard(scope);

    const addressInput = scope.querySelector('#address');
    if (addressInput) {
        setTimeout(() => addressInput.focus(), 300);
    }

    const mapElement = scope.querySelector('#storeLocationMap');
    if (!mapElement) {
        return;
    }

    if (!window.GoogleMapsPicker || typeof window.GoogleMapsPicker.initAddressPicker !== 'function') {
        console.warn('GoogleMapsPicker helper is not available.');
        return;
    }

    window.GoogleMapsPicker.initAddressPicker({
        scope,
        apiKey: mapElement.dataset.googleMapsApiKey || '',
        searchInput: '#storeLocationSearch',
        mapElement: '#storeLocationMap',
        addressInput: '#address',
        districtInput: '#district',
        cityInput: '#city',
        provinceInput: '#province',
        latitudeInput: '#latitude',
        longitudeInput: '#longitude',
        placeIdInput: '#place_id',
        formattedAddressInput: '#formatted_address',
        radiusInput: '#service_radius_km',
    }).catch((error) => {
        console.error('Failed to initialize store location map picker:', error);
    });
}
