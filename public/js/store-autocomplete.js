// public/js/store-autocomplete.js
// Store autocomplete integration for material forms
// Integrates with existing autocomplete-input pattern

(function() {
    'use strict';

    /**
     * Initialize store autocomplete for material forms
     * Call this after the form's own init function
     *
     * @param {HTMLElement|Document} scope - The form container or document
     */
    function initStoreAutocomplete(scope) {
        scope = scope || document;

        const storeInput = scope.querySelector('#store');
        const addressInput = scope.querySelector('#address');
        const storeList = scope.querySelector('#store-list');
        const addressList = scope.querySelector('#address-list');

        if (!storeInput || !storeList) {
            return; // No store fields found
        }

        // Find or create hidden input for store_location_id
        let storeLocationIdInput = scope.querySelector('input[name="store_location_id"]');
        if (!storeLocationIdInput) {
            const form = storeInput.closest('form');
            if (form) {
                storeLocationIdInput = document.createElement('input');
                storeLocationIdInput.type = 'hidden';
                storeLocationIdInput.name = 'store_location_id';
                storeLocationIdInput.id = 'store_location_id';
                form.appendChild(storeLocationIdInput);
            }
        }

        let storeDebounceTimer = null;
        let addressDebounceTimer = null;
        let isSelectingStore = false;
        let isSelectingAddress = false;
        let suppressAddressSuggest = false;

        // ========== HELPER FUNCTIONS ==========

        /**
         * Get or create store_location_id for current store+address
         */
        function resolveStoreLocationId(storeName, address, callback) {
            if (!storeName) {
                if (callback) callback(null);
                return;
            }

            const input = storeName + (address ? ' - ' + address : '');

            fetch('/api/stores/quick-create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ input: input })
            })
            .then(resp => resp.json())
            .then(result => {
                if (result.id && storeLocationIdInput) {
                    storeLocationIdInput.value = result.id;
                    console.log('Store location resolved:', result.id, result.display_text);
                }
                if (callback) callback(result.id);
            })
            .catch(err => {
                console.error('Resolve store location error:', err);
                if (callback) callback(null);
            });
        }

        /**
         * Fetch addresses for a store and auto-fill if only 1 address exists
         */
        function fetchAndAutoFillAddress(storeName) {
            if (!addressInput || !storeName) return;

            const url = '/api/stores/addresses-by-store?store=' + encodeURIComponent(storeName) + '&limit=20';

            fetch(url)
                .then(resp => resp.json())
                .then(addresses => {
                    if (addresses.length === 1) {
                        // Only 1 address - auto-fill
                        suppressAddressSuggest = true;
                        addressInput.value = addresses[0];
                        if (addressList) addressList.style.display = 'none';
                        // Trigger input event for any listeners
                        addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                        // Resolve store_location_id
                        resolveStoreLocationId(storeName, addresses[0]);
                    } else if (addresses.length === 0) {
                        // No addresses - clear
                        addressInput.value = '';
                        if (storeLocationIdInput) storeLocationIdInput.value = '';
                    } else {
                        // Multiple addresses - leave empty for manual selection
                        addressInput.value = '';
                        if (storeLocationIdInput) storeLocationIdInput.value = '';
                    }
                })
                .catch(err => {
                    console.error('Auto-fill address error:', err);
                    addressInput.value = '';
                });
        }

        // ========== STORE FIELD ==========

        function populateStoreList(stores, searchTerm) {
            storeList.innerHTML = '';

            // Add existing stores
            stores.forEach(storeName => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = storeName;
                item.addEventListener('click', function() {
                    isSelectingStore = true;
                    storeInput.value = storeName;
                    storeList.style.display = 'none';

                    // Auto-fill address if store has only 1 address
                    fetchAndAutoFillAddress(storeName);

                    setTimeout(() => { isSelectingStore = false; }, 300);
                });
                storeList.appendChild(item);
            });

            storeList.style.display = (storeList.children.length > 0) ? 'block' : 'none';
        }

        function loadStores(searchTerm) {
            const url = '/api/stores/all-stores?search=' + encodeURIComponent(searchTerm || '') + '&limit=20';

            fetch(url)
                .then(resp => resp.json())
                .then(stores => populateStoreList(stores, searchTerm))
                .catch(err => console.error('Store search error:', err));
        }

        // Store input events
        storeInput.addEventListener('focus', function() {
            if (!isSelectingStore) {
                loadStores('');
            }
        });

        storeInput.addEventListener('input', function() {
            if (isSelectingStore) return;

            clearTimeout(storeDebounceTimer);
            const term = this.value || '';
            storeDebounceTimer = setTimeout(() => loadStores(term), 200);

            // Clear store_location_id when store changes
            if (storeLocationIdInput) storeLocationIdInput.value = '';
        });

        document.addEventListener('click', function(e) {
            if (e.target !== storeInput && !storeList.contains(e.target)) {
                storeList.style.display = 'none';
            }
        });

        // ========== ADDRESS FIELD ==========

        if (addressInput && addressList) {
            function populateAddressList(addresses, searchTerm, storeName) {
                addressList.innerHTML = '';

                // Add existing addresses
                addresses.forEach(addr => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = addr;
                    item.addEventListener('click', function() {
                        isSelectingAddress = true;
                        addressInput.value = addr;
                        addressList.style.display = 'none';

                        // Resolve store_location_id when address is selected
                        resolveStoreLocationId(storeName, addr);

                        setTimeout(() => { isSelectingAddress = false; }, 300);
                    });
                    addressList.appendChild(item);
                });

                // If no store selected, show hint
                if (!storeName) {
                    const hintItem = document.createElement('div');
                    hintItem.className = 'autocomplete-item';
                    hintItem.style.color = '#94a3b8';
                    hintItem.style.fontStyle = 'italic';
                    hintItem.textContent = 'Pilih toko terlebih dahulu...';
                    addressList.innerHTML = '';
                    addressList.appendChild(hintItem);
                }

                addressList.style.display = (addressList.children.length > 0) ? 'block' : 'none';
            }

            function loadAddresses(searchTerm) {
                const storeName = storeInput.value.trim();

                if (!storeName) {
                    populateAddressList([], searchTerm, '');
                    return;
                }

                const url = '/api/stores/addresses-by-store?store=' + encodeURIComponent(storeName) +
                           '&search=' + encodeURIComponent(searchTerm || '') + '&limit=20';

                fetch(url)
                    .then(resp => resp.json())
                    .then(addresses => populateAddressList(addresses, searchTerm, storeName))
                    .catch(err => console.error('Address search error:', err));
            }

            // Address input events
            addressInput.addEventListener('focus', function() {
                if (!isSelectingAddress) {
                    if ((addressInput.value || '').trim().length > 0) {
                        return;
                    }
                    loadAddresses('');
                }
            });

            addressInput.addEventListener('input', function() {
                if (isSelectingAddress) return;
                if (suppressAddressSuggest) {
                    suppressAddressSuggest = false;
                    return;
                }

                clearTimeout(addressDebounceTimer);
                const term = this.value || '';
                addressDebounceTimer = setTimeout(() => loadAddresses(term), 200);

                // Clear store_location_id when address changes (will be resolved on submit)
                if (storeLocationIdInput) storeLocationIdInput.value = '';
            });

            document.addEventListener('click', function(e) {
                if (e.target !== addressInput && !addressList.contains(e.target)) {
                    addressList.style.display = 'none';
                }
            });
        }

        // ========== RESOLVE STORE_LOCATION_ID ON SUBMIT ==========

        const form = storeInput.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const storeName = storeInput.value.trim();
                const address = addressInput ? addressInput.value.trim() : '';

                // Always resolve store_location_id before submit if we have store name
                if (storeName && (!storeLocationIdInput || !storeLocationIdInput.value)) {
                    // Note: We DO NOT preventDefault() here because we want other submit handlers
                    // (like the confirmation dialog in index.blade.php) to run after this.
                    // We use synchronous XHR to ensure the ID is resolved BEFORE those handlers run.

                    const input = storeName + (address ? ' - ' + address : '');

                    // Use sync XHR to ensure store_location_id is set before form submits
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '/api/stores/quick-create', false); // sync
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');

                    try {
                        xhr.send(JSON.stringify({ input: input }));
                        const result = JSON.parse(xhr.responseText);

                        if (result.id && storeLocationIdInput) {
                            storeLocationIdInput.value = result.id;
                            console.log('Store location resolved on submit:', result.id);
                        }
                    } catch (err) {
                        console.error('Store resolve on submit failed:', err);
                    }

                    // No form.submit() call here - let the event propagate
                }
            });
        }
    }

    // Export to window
    window.initStoreAutocomplete = initStoreAutocomplete;

    // Auto-init on DOMContentLoaded if forms exist
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there are store fields that need initialization
        const storeForms = document.querySelectorAll('form:has(#store)');
        storeForms.forEach(form => {
            // Delay to let form-specific JS run first
            setTimeout(() => initStoreAutocomplete(form), 100);
        });
    });
})();
