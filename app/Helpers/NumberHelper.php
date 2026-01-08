<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format angka secara cerdas (Smart Decimal Formatting)
     * - Jika bulat: 10.00 -> 10
     * - Jika desimal standar: 0.22 -> 0,22 | 10.5 -> 10,5
     * - Jika desimal kecil: 0.000021 -> 0,000021 (tampilkan sampai 2 digit signifikan)
     * - Max desimal: 8 digit
     *
     * @param float|null $number
     * @param int|null $decimals (Opsional: paksa jumlah desimal)
     * @param string $decimalSeparator
     * @param string $thousandsSeparator
     * @return string
     */
    public static function format(
        ?float $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        if ($number === null) {
            return '0';
        }

        // 1. Cek jika integer murni
        if (floor($number) == $number && $decimals === null) {
            return number_format($number, 0, $decimalSeparator, $thousandsSeparator);
        }

        // 2. Tentukan jumlah desimal secara otomatis jika tidak dipaksa
        if ($decimals === null) {
            // Konversi ke string dengan presisi tinggi untuk analisis (hindari E notation)
            $str = number_format($number, 10, '.', '');
            $parts = explode('.', $str);
            $decimalPart = $parts[1] ?? '';

            // Cari posisi angka bukan nol pertama
            $firstNonZeroPos = 0;
            for ($i = 0; $i < strlen($decimalPart); $i++) {
                if ($decimalPart[$i] !== '0') {
                    $firstNonZeroPos = $i;
                    break;
                }
            }

            // Logika: Ambil sampai ketemu angka, tambah 1 digit lagi (total 2 digit signifikan)
            // Contoh: 0.00123 -> Posisi 2 (angka 1). Precision = 2 + 2 = 4 (0.0012)
            $calculatedPrecision = $firstNonZeroPos + 2;

            // Batasi maks 8 digit
            $decimals = min($calculatedPrecision, 8);
        }

        // 3. Format angka
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);

        // 4. Hilangkan trailing zeros (0 dibelakang koma yang tidak perlu)
        if (str_contains($formatted, $decimalSeparator)) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, $decimalSeparator);
        }

        return $formatted;
    }

    /**
     * Format untuk currency (Rupiah)
     *
     * @param float|null $number
     * @return string
     */
    public static function currency(?float $number): string
    {
        if ($number === null) {
            return 'Rp 0';
        }

        // Gunakan format smart (null) agar 10000 -> 10.000, bukan 10.000,00
        return 'Rp ' . self::format($number, null, ',', '.');
    }

    /**
     * Format untuk weight (Kg)
     *
     * @param float|null $number
     * @return string
     */
    public static function weight(?float $number): string
    {
        if ($number === null) {
            return '0 Kg';
        }

        return self::format($number, null, ',', '.') . ' Kg';
    }

    /**
     * Format untuk volume (M3)
     *
     * @param float|null $number
     * @return string
     */
    public static function volume(?float $number): string
    {
        if ($number === null) {
            return '0 M3';
        }

        return self::format($number, null, ',', '.') . ' M3';
    }
}
