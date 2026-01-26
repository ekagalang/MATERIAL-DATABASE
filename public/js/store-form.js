function initStoreForm(root) {
    // Basic initialization for Store Form
    const scope = root || document;
    
    // Focus on name input
    const nameInput = scope.querySelector('#name');
    if (nameInput) {
        setTimeout(() => nameInput.focus(), 300);
    }

    console.log('Store form initialized');
}
