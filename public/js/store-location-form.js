function initStoreLocationForm(root) {
    // Basic initialization for Store Location Form
    const scope = root || document;
    
    // Focus on address input
    const addressInput = scope.querySelector('#address');
    if (addressInput) {
        setTimeout(() => addressInput.focus(), 300);
    }

    console.log('Store Location form initialized');
}
