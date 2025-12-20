/**
 * Global Form Sanitizer
 * Membersihkan input paste pada field angka (menghapus Rp, teks, titik ribuan, dll)
 * Contoh: "Rp. 65.000 /kg" -> "65000"
 */

document.addEventListener('DOMContentLoaded', function() {
    
    function sanitizePaste(e) {
        const input = e.target;
        
        // Target hanya input yang relevan:
        // 1. type="number"
        // 2. inputmode="numeric" (biasanya text field dengan format rupiah)
        // 3. Class "numeric-input" (opsional)
        if (input.tagName === 'INPUT' && (
            input.type === 'number' || 
            input.inputmode === 'numeric' || 
            input.classList.contains('numeric-input')
        )) {
            // Mencegah paste default browser agar kita bisa memproses datanya dulu
            e.preventDefault();
            
            // Ambil data text dari clipboard
            const clipboardData = (e.clipboardData || window.clipboardData);
            const pastedText = clipboardData.getData('text');
            
            if (!pastedText) return;

            // --- LOGIKA PEMBERSIHAN (Sesuai format Indonesia) ---
            // 1. Hapus semua titik (.) yang biasanya adalah pemisah ribuan di ID
            // 2. Ganti koma (,) menjadi titik (.) agar menjadi desimal format standar pemrograman
            // 3. Hapus semua karakter yang bukan angka 0-9 atau titik desimal
            
            let cleanText = pastedText
                .replace(/\./g, '')       // Hapus titik ribuan (10.000 -> 10000)
                .replace(/,/g, '.')       // Ubah koma desimal jadi titik (10,5 -> 10.5)
                .replace(/[^0-9.]/g, ''); // Hapus mata uang dan teks (Rp, /kg, spasi)

            // Validasi hasil akhir
            if (cleanText === '' || isNaN(parseFloat(cleanText))) {
                // Fallback: Jika logika di atas gagal total, ambil digitnya saja
                cleanText = pastedText.replace(/\D/g, '');
            }

            // --- PENANGANAN KHUSUS TYPE NUMBER ---
            if (input.type === 'number') {
                // Input number tidak support selectionStart/End di beberapa browser
                // dan biasanya user ingin mengganti nilai jika paste harga
                input.value = cleanText;
            } 
            // --- PENANGANAN INPUT TEXT (Rupiah Display, dll) ---
            else {
                try {
                    // Coba sisipkan teks di posisi kursor
                    const start = input.selectionStart || 0;
                    const end = input.selectionEnd || 0;
                    const currentValue = input.value;
                    
                    const newValue = currentValue.substring(0, start) + cleanText + currentValue.substring(end);
                    input.value = newValue;
                    
                    // Kembalikan posisi kursor ke akhir angka yang di-paste
                    const newCursorPos = start + cleanText.length;
                    input.setSelectionRange(newCursorPos, newCursorPos);
                } catch (err) {
                    // Fallback jika selection API error
                    input.value = cleanText;
                }
            }

            // --- TRIGGER EVENT ---
            // Penting: Trigger event 'input' agar script lain (kalkulasi volume/harga) berjalan
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // Gunakan Event Delegation (listener di document) 
    // agar input di dalam Modal atau yang dibuat dinamis tetap terdeteksi
    document.addEventListener('paste', sanitizePaste, true); // Use capture phase
});
