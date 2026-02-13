function initStoreLocationForm(root) {
    const scope = root || document;

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
    }).catch((error) => {
        console.error('Failed to initialize store location map picker:', error);
    });
}
