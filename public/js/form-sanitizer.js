/**
 * Global Form Sanitizer
 * Membersihkan input paste pada field angka (menghapus Rp, teks, titik ribuan, dll)
 * Contoh: "Rp. 65.000 /kg" -> "65000"
 */

document.addEventListener('DOMContentLoaded', function() {

    function parseDecimal(value) {
        if (value === null || value === undefined) return NaN;
        if (typeof value === 'number') return isFinite(value) ? value : NaN;
        if (typeof value !== 'string') return NaN;
        let str = value.trim();
        if (str === '') return NaN;

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
                str = str.replace(/\./g, '');
                str = str.replace(/,/g, '.');
            } else {
                str = str.replace(/,/g, '');
            }
        } else if (hasComma) {
            if (/^\d{1,3}(,\d{3})+$/.test(str)) {
                str = str.replace(/,/g, '');
            } else {
                str = str.replace(/,/g, '.');
            }
        } else if (hasDot) {
            if (/^\d{1,3}(\.\d{3})+$/.test(str)) {
                str = str.replace(/\./g, '');
            }
        }

        str = str.replace(/[^0-9.]/g, '');
        if (str === '' || str === '.') return NaN;
        const num = Number(str);
        if (!isFinite(num)) return NaN;
        return negative ? -num : num;
    }

    function formatPlain(value, maxDecimals = 15) {
        if (!isFinite(value)) return '';
        const resolved = Math.max(0, maxDecimals);
        let formatted = value.toFixed(resolved);
        if (resolved > 0) {
            formatted = formatted.replace(/0+$/, '').replace(/\.$/, '');
        }
        if (formatted === '' || formatted === '-0') {
            formatted = '0';
        }
        return formatted;
    }
    function sanitizePaste(e) {
        const input = e.target;
        
        // Target hanya input yang relevan:
        // 1. type="number"
        // 2. inputmode="numeric" (biasanya text field dengan format rupiah)
        // 3. Class "numeric-input" (opsional)
        if (input.tagName === 'INPUT' && (
            input.type === 'number' || 
            input.inputmode === 'numeric' || 
            input.inputmode === 'decimal' ||
            input.classList.contains('numeric-input')
        )) {
            // Mencegah paste default browser agar kita bisa memproses datanya dulu
            e.preventDefault();
            
            // Ambil data text dari clipboard
            const clipboardData = (e.clipboardData || window.clipboardData);
            const pastedText = clipboardData.getData('text');
            
            if (!pastedText) return;

            // --- LOGIKA PEMBERSIHAN (Fleksibel: koma atau titik) ---
            let cleanText = '';
            const parsed = parseDecimal(pastedText);
            if (!isNaN(parsed)) {
                cleanText = formatPlain(parsed);
            } else {
                // Fallback: ambil digitnya saja
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

    // Normalize numeric inputs on submit (comma/dot flexible)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        const inputs = form.querySelectorAll('input');
        inputs.forEach((input) => {
            if (input.disabled) return;

            const id = input.id || '';
            const name = input.name || '';
            const isNumericField = input.type === 'number' ||
                input.inputmode === 'numeric' ||
                input.inputmode === 'decimal' ||
                input.classList.contains('numeric-input') ||
                /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count/i.test(id) ||
                /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga|length|width|height|thickness|ratio|count/i.test(name);

            if (!isNumericField) return;
            if (!input.value) return;

            const parsed = parseDecimal(input.value);
            if (isNaN(parsed)) return;

            input.value = formatPlain(parsed);
        });
    }, false);
});
