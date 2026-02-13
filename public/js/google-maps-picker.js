(function () {
    'use strict';

    const mapsApiState = {
        promise: null,
    };

    function ensureFallbackAutocompleteStyle() {
        if (document.getElementById('maps-geocode-autocomplete-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'maps-geocode-autocomplete-style';
        style.textContent = `
            .maps-geocode-autocomplete-list {
                position: absolute;
                top: calc(100% + 6px);
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid #dbe3ee;
                border-radius: 10px;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
                z-index: 2147483003;
                max-height: 220px;
                overflow-y: auto;
            }
            .maps-geocode-autocomplete-item {
                display: block;
                width: 100%;
                padding: 10px 12px;
                border: none;
                background: transparent;
                text-align: left;
                font-size: 13px;
                color: #0f172a;
                cursor: pointer;
                line-height: 1.45;
            }
            .maps-geocode-autocomplete-item + .maps-geocode-autocomplete-item {
                border-top: 1px solid #eef2f7;
            }
            .maps-geocode-autocomplete-item:hover {
                background: #f8fafc;
            }
        `;
        document.head.appendChild(style);
    }

    function resolveElement(target, scope) {
        if (!target) return null;
        if (typeof HTMLElement !== 'undefined' && target instanceof HTMLElement) {
            return target;
        }
        if (typeof target === 'string') {
            const scoped = scope && scope.querySelector ? scope.querySelector(target) : null;
            return scoped || document.querySelector(target);
        }
        return null;
    }

    function toNumber(value) {
        const num = Number.parseFloat(value);
        return Number.isFinite(num) ? num : null;
    }

    function triggerChange(input) {
        if (!input) return;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function setInputValue(input, value) {
        if (!input) return;
        const next = value == null ? '' : String(value);
        if (input.value !== next) {
            input.value = next;
            triggerChange(input);
        }
    }

    function buildStreetAddress(place) {
        const components = Array.isArray(place?.address_components)
            ? place.address_components
            : Array.isArray(place?.addressComponents)
              ? place.addressComponents
              : [];
        let streetNumber = '';
        let route = '';

        components.forEach((component) => {
            const types = component.types || [];
            if (types.includes('street_number')) {
                streetNumber = component.long_name || component.longText || '';
            }
            if (types.includes('route')) {
                route = component.long_name || component.longText || '';
            }
        });

        const street = [route, streetNumber].filter(Boolean).join(' ').trim();
        return street || (place?.name || '');
    }

    function applyAddressComponents(place, fields) {
        const components = Array.isArray(place?.address_components)
            ? place.address_components
            : Array.isArray(place?.addressComponents)
              ? place.addressComponents
              : [];
        let district = '';
        let city = '';
        let province = '';

        components.forEach((component) => {
            const types = component.types || [];

            if (
                !district &&
                (types.includes('sublocality_level_1') ||
                    types.includes('sublocality') ||
                    types.includes('administrative_area_level_3'))
            ) {
                district = component.long_name || component.longText || '';
            }

            if (!city && (types.includes('locality') || types.includes('administrative_area_level_2'))) {
                city = component.long_name || component.longText || '';
            }

            if (!province && types.includes('administrative_area_level_1')) {
                province = component.long_name || component.longText || '';
            }
        });

        setInputValue(fields.districtInput, district);
        setInputValue(fields.cityInput, city);
        setInputValue(fields.provinceInput, province);
    }

    function showMapError(mapElement, message) {
        if (!mapElement) return;
        mapElement.innerHTML = `<div style="padding: 12px; font-size: 13px; color: #7f1d1d; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px;">${message}</div>`;
    }

    function ensurePlacesLibrary() {
        if (!window.google?.maps) {
            return Promise.resolve();
        }

        if (window.google.maps.places) {
            return Promise.resolve();
        }

        if (typeof window.google.maps.importLibrary !== 'function') {
            return Promise.resolve();
        }

        return window.google.maps
            .importLibrary('places')
            .then(() => undefined)
            .catch((error) => {
                console.warn('Google Places library tidak dapat dimuat:', error);
            });
    }

    function loadApi(apiKey) {
        if (window.google && window.google.maps) {
            return ensurePlacesLibrary();
        }

        if (!apiKey) {
            return Promise.reject(new Error('Google Maps API key belum diatur.'));
        }

        if (mapsApiState.promise) {
            return mapsApiState.promise;
        }

        mapsApiState.promise = new Promise((resolve, reject) => {
            const callbackName = `__googleMapsInit_${Date.now()}`;

            window[callbackName] = () => {
                delete window[callbackName];
                ensurePlacesLibrary().then(resolve);
            };

            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&loading=async&callback=${callbackName}`;
            script.async = true;
            script.defer = true;
            script.dataset.googleMapsApi = '1';
            script.onerror = () => {
                delete window[callbackName];
                reject(new Error('Gagal memuat Google Maps API.'));
            };

            document.head.appendChild(script);
        });

        return mapsApiState.promise;
    }

    function initAddressPicker(options) {
        const scope = options?.scope || document;
        const searchInput = resolveElement(options?.searchInput, scope);
        const mapElement = resolveElement(options?.mapElement, scope);

        if (!searchInput || !mapElement) {
            return Promise.resolve(null);
        }

        if (mapElement.dataset.mapPickerReady === '1') {
            return Promise.resolve(null);
        }

        mapElement.dataset.mapPickerReady = '1';

        const addressInput = resolveElement(options?.addressInput, scope);
        const districtInput = resolveElement(options?.districtInput, scope);
        const cityInput = resolveElement(options?.cityInput, scope);
        const provinceInput = resolveElement(options?.provinceInput, scope);
        const latitudeInput = resolveElement(options?.latitudeInput, scope);
        const longitudeInput = resolveElement(options?.longitudeInput, scope);
        const placeIdInput = resolveElement(options?.placeIdInput, scope);
        const formattedAddressInput = resolveElement(options?.formattedAddressInput, scope);

        const apiKey =
            options?.apiKey ||
            mapElement.dataset.googleMapsApiKey ||
            searchInput.dataset.googleMapsApiKey ||
            '';

        const defaultLat = toNumber(latitudeInput?.value) ?? toNumber(options?.defaultLat) ?? -6.297466598729796;
        const defaultLng = toNumber(longitudeInput?.value) ?? toNumber(options?.defaultLng) ?? 106.64094911979295;

        return loadApi(apiKey)
            .then(() => {
                const center = { lat: defaultLat, lng: defaultLng };
                const map = new google.maps.Map(mapElement, {
                    center,
                    zoom: 18,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                });

                const marker = new google.maps.Marker({
                    map,
                    position: center,
                    draggable: true,
                });

                const geocoder = new google.maps.Geocoder();
                const hasLegacyAutocomplete =
                    !!window.google?.maps?.places &&
                    typeof window.google.maps.places.Autocomplete === 'function';
                const hasPlaceAutocompleteElement =
                    !!window.google?.maps?.places &&
                    typeof window.google.maps.places.PlaceAutocompleteElement === 'function';

                let autocomplete = null;
                let placeAutocompleteElement = null;
                if (hasLegacyAutocomplete) {
                    autocomplete = new google.maps.places.Autocomplete(searchInput, {
                        fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id'],
                    });
                    autocomplete.bindTo('bounds', map);
                } else if (hasPlaceAutocompleteElement) {
                    placeAutocompleteElement = new google.maps.places.PlaceAutocompleteElement({});
                    placeAutocompleteElement.style.display = 'block';
                    placeAutocompleteElement.style.width = '100%';
                    if (searchInput.placeholder) {
                        placeAutocompleteElement.setAttribute('placeholder', searchInput.placeholder);
                    }
                    if (searchInput.value) {
                        try {
                            placeAutocompleteElement.value = searchInput.value;
                        } catch (_) {
                            // Ignore when browser or API does not expose writable value.
                        }
                    }

                    if (searchInput.parentNode) {
                        searchInput.parentNode.insertBefore(placeAutocompleteElement, searchInput);
                    }
                    searchInput.style.display = 'none';
                } else {
                    console.warn(
                        'Google Places autocomplete tidak tersedia. Fallback ke geocoding manual (Enter).'
                    );
                }

                const applyLocation = (place) => {
                    if (!place) return;

                    const location =
                        place.geometry?.location ||
                        place.location ||
                        null;
                    const lat =
                        typeof location?.lat === 'function'
                            ? location.lat()
                            : Number.isFinite(location?.lat)
                              ? location.lat
                              : null;
                    const lng =
                        typeof location?.lng === 'function'
                            ? location.lng()
                            : Number.isFinite(location?.lng)
                              ? location.lng
                              : null;

                    if (lat !== null && lng !== null) {
                        const latLng = new google.maps.LatLng(lat, lng);
                        marker.setPosition(latLng);
                        map.setCenter(latLng);
                        map.setZoom(16);
                        setInputValue(latitudeInput, lat.toFixed(7));
                        setInputValue(longitudeInput, lng.toFixed(7));
                    }

                    const formattedAddress = place.formatted_address || place.formattedAddress || '';
                    setInputValue(placeIdInput, place.place_id || place.id || '');
                    setInputValue(formattedAddressInput, formattedAddress);

                    if (formattedAddress) {
                        searchInput.value = formattedAddress;
                        if (placeAutocompleteElement) {
                            try {
                                placeAutocompleteElement.value = formattedAddress;
                            } catch (_) {
                                // Ignore when API element does not allow direct value assignment.
                            }
                        }
                    }

                    const streetAddress = buildStreetAddress(place) || formattedAddress;
                    if (streetAddress) {
                        setInputValue(addressInput, streetAddress);
                    }

                    applyAddressComponents(place, {
                        districtInput,
                        cityInput,
                        provinceInput,
                    });
                };

                const reverseGeocode = (latLng) => {
                    geocoder.geocode({ location: latLng }, (results, status) => {
                        if (status !== 'OK' || !Array.isArray(results) || results.length === 0) {
                            return;
                        }

                        applyLocation(results[0]);
                    });
                };

                const geocodeAddressText = (addressText) => {
                    const text = (addressText || '').trim();
                    if (!text) {
                        return;
                    }

                    geocoder.geocode({ address: text }, (results, status) => {
                        if (status !== 'OK' || !Array.isArray(results) || results.length === 0) {
                            return;
                        }

                        applyLocation(results[0]);
                    });
                };

                if (autocomplete) {
                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (!place || !place.geometry) {
                            return;
                        }
                        applyLocation(place);
                    });
                }

                const handleNewAutocompleteSelect = async (event) => {
                    const prediction =
                        event?.placePrediction ||
                        event?.detail?.placePrediction ||
                        event?.place ||
                        event?.detail?.place ||
                        null;
                    if (!prediction) {
                        return;
                    }

                    try {
                        let selectedPlace = null;
                        if (typeof prediction.toPlace === 'function') {
                            selectedPlace = prediction.toPlace();
                        } else {
                            selectedPlace = prediction;
                        }

                        if (selectedPlace && typeof selectedPlace.fetchFields === 'function') {
                            await selectedPlace.fetchFields({
                                fields: ['displayName', 'formattedAddress', 'location', 'addressComponents', 'id'],
                            });
                        }

                        applyLocation(selectedPlace);
                    } catch (error) {
                        console.warn('Gagal memproses Place Autocomplete element selection:', error);
                    }
                };

                if (placeAutocompleteElement) {
                    placeAutocompleteElement.addEventListener('gmp-select', handleNewAutocompleteSelect);
                    placeAutocompleteElement.addEventListener('gmp-placeselect', handleNewAutocompleteSelect);
                }

                let fallbackSuggestionResults = [];
                let fallbackListEl = null;
                let fallbackTimer = null;

                const hideFallbackList = () => {
                    if (fallbackListEl) {
                        fallbackListEl.remove();
                        fallbackListEl = null;
                    }
                };

                const renderFallbackList = (results) => {
                    hideFallbackList();

                    if (!Array.isArray(results) || results.length === 0) {
                        return;
                    }

                    ensureFallbackAutocompleteStyle();
                    const host = searchInput.parentElement;
                    if (!host) {
                        return;
                    }

                    if (window.getComputedStyle(host).position === 'static') {
                        host.style.position = 'relative';
                    }

                    fallbackListEl = document.createElement('div');
                    fallbackListEl.className = 'maps-geocode-autocomplete-list';

                    results.forEach((item) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'maps-geocode-autocomplete-item';
                        button.textContent = item.formatted_address || item.formattedAddress || item.name || '';
                        button.addEventListener('click', () => {
                            applyLocation(item);
                            hideFallbackList();
                        });
                        fallbackListEl.appendChild(button);
                    });

                    host.appendChild(fallbackListEl);
                };

                if (!autocomplete && !placeAutocompleteElement) {
                    searchInput.addEventListener('input', () => {
                        const text = (searchInput.value || '').trim();
                        clearTimeout(fallbackTimer);

                        if (text.length < 3) {
                            fallbackSuggestionResults = [];
                            hideFallbackList();
                            return;
                        }

                        fallbackTimer = setTimeout(() => {
                            geocoder.geocode({ address: text }, (results, status) => {
                                if (status !== 'OK' || !Array.isArray(results) || results.length === 0) {
                                    fallbackSuggestionResults = [];
                                    hideFallbackList();
                                    return;
                                }

                                fallbackSuggestionResults = results.slice(0, 5);
                                renderFallbackList(fallbackSuggestionResults);
                            });
                        }, 220);
                    });

                    document.addEventListener('click', (event) => {
                        if (fallbackListEl && !event.target.closest('.maps-geocode-autocomplete-list') && event.target !== searchInput) {
                            hideFallbackList();
                        }
                    });
                }

                searchInput.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') {
                        return;
                    }
                    event.preventDefault();
                    if (fallbackSuggestionResults.length > 0 && !autocomplete && !placeAutocompleteElement) {
                        applyLocation(fallbackSuggestionResults[0]);
                        hideFallbackList();
                        return;
                    }
                    geocodeAddressText(searchInput.value);
                });

                map.addListener('click', (event) => {
                    if (!event.latLng) return;
                    marker.setPosition(event.latLng);
                    map.panTo(event.latLng);
                    setInputValue(latitudeInput, event.latLng.lat().toFixed(7));
                    setInputValue(longitudeInput, event.latLng.lng().toFixed(7));
                    reverseGeocode(event.latLng);
                });

                marker.addListener('dragend', () => {
                    const pos = marker.getPosition();
                    if (!pos) return;
                    setInputValue(latitudeInput, pos.lat().toFixed(7));
                    setInputValue(longitudeInput, pos.lng().toFixed(7));
                    reverseGeocode(pos);
                });

                if (!searchInput.value) {
                    searchInput.value = formattedAddressInput?.value || addressInput?.value || '';
                }

                const initialLat = toNumber(latitudeInput?.value);
                const initialLng = toNumber(longitudeInput?.value);
                if (initialLat !== null && initialLng !== null) {
                    const initialPos = new google.maps.LatLng(initialLat, initialLng);
                    marker.setPosition(initialPos);
                    map.setCenter(initialPos);
                    map.setZoom(16);

                    if (!formattedAddressInput?.value && !searchInput.value) {
                        reverseGeocode(initialPos);
                    }
                }

                return { map, marker, autocomplete };
            })
            .catch((error) => {
                showMapError(mapElement, error.message || 'Google Maps tidak dapat dimuat.');
                throw error;
            });
    }

    window.GoogleMapsPicker = {
        loadApi,
        initAddressPicker,
    };
})();
