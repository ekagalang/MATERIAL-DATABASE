<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format angka: hilangkan trailing zeros
     * 50.00 → 50
     * 50.50 → 50.5
     * 50.25 → 50.25
     * 
     * @param float|null $number
     * @param int $decimals
     * @param string $decimalSeparator
     * @param string $thousandsSeparator
     * @return string
     */
    public static function format(
        ?float $number, 
        int $decimals = 2, 
        string $decimalSeparator = ',', 
        string $thousandsSeparator = '.'
    ): string {
        if ($number === null) {
            return '0';
        }

        // Format dengan desimal
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
        
        // Hilangkan trailing zeros setelah koma
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

        return 'Rp ' . self::format($number, 2, ',', '.');
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

        return self::format($number, 2, ',', '.') . ' Kg';
    }

    /**
     * Format untuk volume (m³)
     * 
     * @param float|null $number
     * @return string
     */
    public static function volume(?float $number): string
    {
        if ($number === null) {
            return '0 m³';
        }

        return self::format($number, 2, ',', '.') . ' m³';
    }
}