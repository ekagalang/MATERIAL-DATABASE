document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const priceInput = document.getElementById('price_per_package');
    const piecesInput = document.getElementById('pieces_per_package');
    const lengthInput = document.getElementById('dimension_length');
    const widthInput = document.getElementById('dimension_width');
    const coverageInput = document.getElementById('coverage_per_package');
    const resultM2Price = document.getElementById('comparison_price_per_m2');
    
    // Auto-Calculate Logic
    function calculateMetrics() {
        const price = parseFloat(priceInput.value) || 0;
        const pieces = parseFloat(piecesInput.value) || 0;
        const length = parseFloat(lengthInput.value) || 0;
        const width = parseFloat(widthInput.value) || 0;
        let coverage = parseFloat(coverageInput.value) || 0;

        // 1. Auto-hitung coverage jika kosong tapi dimensi & isi ada
        if (coverage === 0 && length > 0 && width > 0 && pieces > 0) {
            // Asumsi dimensi dalam CM, convert ke M2
            // Rumus: (P/100 * L/100) * Isi
            coverage = (length / 100) * (width / 100) * pieces;
            
            // Tampilkan hasil hitungan coverage (opsional, bisa di-set ke input)
            // coverageInput.value = coverage.toFixed(4); 
        }

        // 2. Hitung Harga per M2
        if (price > 0 && coverage > 0) {
            const pricePerM2 = price / coverage;
            resultM2Price.value = pricePerM2.toFixed(2);
        } else {
            resultM2Price.value = '';
        }
    }

    // Attach Listeners
    if(priceInput) priceInput.addEventListener('input', calculateMetrics);
    if(piecesInput) piecesInput.addEventListener('input', calculateMetrics);
    if(lengthInput) lengthInput.addEventListener('input', calculateMetrics);
    if(widthInput) widthInput.addEventListener('input', calculateMetrics);
    if(coverageInput) coverageInput.addEventListener('input', calculateMetrics);
});