<?php

namespace App\Helpers;

class NumberHelper
{
    private const DEFAULT_DECIMALS = 15;
    private const RESULT_DECIMALS = 15;
    private const FIXED_DECIMALS = 2;

    private static function resolveDecimals(?int $decimals, int $fallback): int
    {
        return max(0, $decimals ?? $fallback);
    }

    private static function formatNumber(
        ?float $number,
        int $decimals,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): string {
        if ($number === null || !is_finite($number)) {
            return '0';
        }

        $decimals = max(0, $decimals);
        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);

        if ($decimals === 0) {
            return $formatted;
        }

        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, $decimalSeparator);

        return $formatted;
    }

    /**
     * Format angka untuk tampilan umum.
     * Default: 2 desimal, nol di belakang disembunyikan.
     */
    public static function format(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::DEFAULT_DECIMALS);
        return self::formatPlain($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format angka untuk hasil perhitungan (lebih detail).
     * Default: 6 desimal, nol di belakang disembunyikan.
     */
    public static function formatResult(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::RESULT_DECIMALS);
        return self::formatPlain($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format angka dengan behavior lama (number_format + trim nol).
     * Cocok untuk currency/angka yang tidak butuh presisi panjang.
     */
    public static function formatFixed(
        mixed $number,
        ?int $decimals = null,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $decimals = self::resolveDecimals($decimals, self::FIXED_DECIMALS);
        $parsed = self::parseNumberValue($number, $decimalSeparator, $thousandsSeparator);
        return self::formatNumber($parsed, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format angka tanpa notasi ilmiah, menjaga presisi float.
     * Default: desimal titik, tanpa pemisah ribuan.
     */
    public static function formatPlain(
        mixed $number,
        int $maxDecimals = 15,
        string $decimalSeparator = ',',
        string $thousandsSeparator = '.',
    ): string {
        $parsed = self::parseNumberValue($number, $decimalSeparator, $thousandsSeparator);
        if ($parsed === null || !is_finite($parsed)) {
            return '0';
        }

        $maxDecimals = max(0, $maxDecimals);
        $base = $parsed;
        $bestDecimals = $maxDecimals;
        $bestValue = round($base, $maxDecimals);
        $tolerance = max(abs($base) * 1e-12, 1e-12);

        for ($d = $maxDecimals - 1; $d >= 0; $d--) {
            $candidate = round($base, $d);
            if (abs($candidate - $base) <= $tolerance) {
                $bestDecimals = $d;
                $bestValue = $candidate;
                continue;
            }
            break;
        }

        $formatted = number_format($bestValue, $bestDecimals, '.', '');
        if ($bestDecimals > 0) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.');
        }

        if ($formatted === '' || $formatted === '-0') {
            $formatted = '0';
        }

        $sign = '';
        if (str_starts_with($formatted, '-')) {
            $sign = '-';
            $formatted = substr($formatted, 1);
        }

        $parts = explode('.', $formatted, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';

        if ($thousandsSeparator !== '') {
            $intPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandsSeparator, $intPart);
        }

        $formatted = $sign . $intPart;
        if ($decPart !== '') {
            $formatted .= $decimalSeparator . $decPart;
        }

        return $formatted;
    }

    /**
     * Normalisasi angka sederhana (cast/round).
     * Dipertahankan untuk kebutuhan API, bukan untuk perhitungan internal.
     */
    public static function normalize(?float $number, ?int $decimals = null): float
    {
        if ($number === null || !is_finite($number)) {
            return 0.0;
        }

        if ($decimals === null) {
            return (float) $number;
        }

        return (float) round($number, max(0, $decimals));
    }

    /**
     * Format untuk currency (Rupiah)
     */
    public static function currency(mixed $number): string
    {
        if ($number === null || $number === '') {
            return 'Rp 0';
        }

        return 'Rp ' . self::formatFixed($number, 0, ',', '.');
    }

    /**
     * Format untuk weight (Kg)
     */
    public static function weight(mixed $number): string
    {
        if ($number === null || $number === '') {
            return '0 Kg';
        }

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.') . ' Kg';
    }

    /**
     * Format untuk volume (M3)
     */
    public static function volume(mixed $number): string
    {
        if ($number === null || $number === '') {
            return '0 M3';
        }

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.') . ' M3';
    }

    private static function parseNumberValue(
        mixed $value,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        $string = str_replace(['Rp', 'rp', ' '], '', $string);

        $negative = false;
        if (str_starts_with($string, '-')) {
            $negative = true;
            $string = substr($string, 1);
        }

        $hasComma = str_contains($string, ',');
        $hasDot = str_contains($string, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($string, ',');
            $lastDot = strrpos($string, '.');
            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                // Comma as decimal, dot as thousands
                $string = str_replace('.', '', $string);
                $string = str_replace(',', '.', $string);
            } else {
                // Dot as decimal, comma as thousands
                $string = str_replace(',', '', $string);
            }
        } elseif ($hasComma) {
            $lastComma = strrpos($string, ',');
            $digitsAfter = $lastComma === false ? 0 : (strlen($string) - $lastComma - 1);
            if ($digitsAfter === 3 && preg_match('/^\d{1,3}(,\d{3})+$/', $string)) {
                $string = str_replace(',', '', $string);
            } else {
                $string = str_replace(',', '.', $string);
            }
        } elseif ($hasDot) {
            $lastDot = strrpos($string, '.');
            $digitsAfter = $lastDot === false ? 0 : (strlen($string) - $lastDot - 1);
            if ($digitsAfter === 3 && preg_match('/^\d{1,3}(\.\d{3})+$/', $string)) {
                $string = str_replace('.', '', $string);
            }
        }

        $string = preg_replace('/[^0-9.]/', '', $string);
        if ($string === '' || $string === '.') {
            return null;
        }

        if ($negative) {
            $string = '-' . $string;
        }

        if (!is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }
}
