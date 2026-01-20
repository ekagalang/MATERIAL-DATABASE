<?php

namespace App\Helpers;

class NumberHelper
{
    private const DEFAULT_DECIMALS = 2;
    private const MAX_DECIMAL_PRECISION = 30;

    private static function resolveDecimals(?int $decimals): int
    {
        if ($decimals === null) {
            return self::DEFAULT_DECIMALS;
        }

        return max(0, $decimals);
    }

    private static function toPlainString(float $number, int $precision = self::MAX_DECIMAL_PRECISION): string
    {
        $precision = max(0, $precision);
        return sprintf('%.' . $precision . 'F', $number);
    }

    private static function stabilizeFloat(float $number): float
    {
        if ($number == 0.0 || !is_finite($number)) {
            return $number;
        }

        $abs = abs($number);
        $epsilon = min($abs * 1.0e-12, 1.0e-6);
        if ($epsilon === 0.0) {
            return $number;
        }

        return $number + ($number >= 0 ? $epsilon : -$epsilon);
    }

    private static function truncateString(float $number, int $decimals): string
    {
        if (!is_finite($number)) {
            return $decimals > 0 ? '0.' . str_repeat('0', $decimals) : '0';
        }

        $decimals = max(0, $decimals);
        $sign = $number < 0 ? '-' : '';
        $number = abs($number);

        $plain = sprintf('%.20F', $number);
        $parts = explode('.', $plain, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';
        $decPart = substr($decPart, 0, $decimals);

        if ($decimals === 0) {
            return $sign . $intPart;
        }

        $decPart = str_pad($decPart, $decimals, '0');
        return $sign . $intPart . '.' . $decPart;
    }

    private static function formatPlain(
        string $plain,
        int $decimals,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): string {
        $decimals = max(0, $decimals);
        $sign = '';
        if (str_starts_with($plain, '-')) {
            $sign = '-';
            $plain = substr($plain, 1);
        }

        $parts = explode('.', $plain, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';
        $intPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandsSeparator, $intPart);

        if ($decimals === 0) {
            return $sign . $intPart;
        }

        $decPart = str_pad(substr($decPart, 0, $decimals), $decimals, '0');
        if (preg_match('/^0+$/', $decPart)) {
            return $sign . $intPart;
        }
        return $sign . $intPart . $decimalSeparator . $decPart;
    }

    private static function formatDynamic(
        ?float $number,
        string $decimalSeparator,
        string $thousandsSeparator,
    ): string {
        $plain = self::dynamicPlain($number);
        $sign = '';
        if (str_starts_with($plain, '-')) {
            $sign = '-';
            $plain = substr($plain, 1);
        }

        $parts = explode('.', $plain, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';
        $intPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandsSeparator, $intPart);

        if ($decPart === '') {
            return $sign . $intPart;
        }

        return $sign . $intPart . $decimalSeparator . $decPart;
    }

    private static function dynamicPlain(?float $number): string
    {
        if ($number === null || !is_finite($number)) {
            return '0';
        }

        $number = self::stabilizeFloat($number);
        $sign = $number < 0 ? '-' : '';
        $plain = self::toPlainString(abs($number));
        $parts = explode('.', $plain, 2);
        $intPart = $parts[0] ?? '0';
        $decPart = $parts[1] ?? '';

        $intPart = ltrim($intPart, '0');
        if ($intPart === '') {
            $intPart = '0';
        }

        $decPart = rtrim($decPart, '0');

        if ($intPart !== '0') {
            $decPart = substr($decPart, 0, self::DEFAULT_DECIMALS);
            $decPart = rtrim($decPart, '0');
            if ($decPart === '') {
                return $sign . $intPart;
            }
            return $sign . $intPart . '.' . $decPart;
        }

        if ($decPart === '') {
            return $sign . '0';
        }

        $leadingZeros = strspn($decPart, '0');
        if ($leadingZeros >= strlen($decPart)) {
            return $sign . '0';
        }

        $cutLength = min(strlen($decPart), $leadingZeros + 2);
        $decPart = substr($decPart, 0, $cutLength);
        $decPart = rtrim($decPart, '0');

        if ($decPart === '') {
            return $sign . '0';
        }

        return $sign . '0.' . $decPart;
    }

    /**
     * Format angka tanpa pembulatan.
     * Default: dinamis (>=1 tampil 2 digit, <1 sampai 2 digit setelah digit non-zero pertama).
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
        if ($decimals === null) {
            return self::formatDynamic($number, $decimalSeparator, $thousandsSeparator);
        }

        if ($number === null) {
            $decimals = self::resolveDecimals($decimals);
            return self::formatPlain('0', $decimals, $decimalSeparator, $thousandsSeparator);
        }

        $decimals = self::resolveDecimals($decimals);
        $plain = self::truncateString($number, $decimals);

        return self::formatPlain($plain, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Normalisasi angka tanpa pembulatan (dipakai untuk perhitungan).
     * Default: dinamis mengikuti aturan format().
     *
     * @param float|null $number
     * @param int|null $decimals (Opsional: paksa jumlah desimal)
     * @return float
     */
    public static function normalize(?float $number, ?int $decimals = null): float
    {
        if ($number === null || !is_finite($number)) {
            return 0.0;
        }

        if ($decimals === null) {
            $plain = self::dynamicPlain($number);
            if ($plain === '' || $plain === '-' || $plain === '-0') {
                return 0.0;
            }
            return (float) $plain;
        }

        $resolvedDecimals = self::resolveDecimals($decimals);
        $plain = self::truncateString($number, $resolvedDecimals);
        return (float) $plain;
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

        return 'Rp ' . self::format($number, 0, ',', '.');
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

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.') . ' Kg';
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

        return self::format($number, self::DEFAULT_DECIMALS, ',', '.') . ' M3';
    }
}
