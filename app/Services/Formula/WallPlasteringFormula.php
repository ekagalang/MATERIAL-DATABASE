<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Cement;
use App\Models\Sand;

/**
 * Formula - Perhitungan Plaster Dinding
 * Menghitung kebutuhan material untuk plester dinding
 */
class WallPlasteringFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'wall_plastering';
    }

    public static function getName(): string
    {
        return 'Plester Dinding';
    }

    public static function getDescription(): string
    {
        return 'Menghitung Plester Dinding dengan input panjang, tinggi, tebal adukan dan jumlah sisi.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['cement', 'sand'];
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'mortar_thickness', 'plaster_sides', 'cement_id', 'sand_id'];

        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] <= 0) {
                return false;
            }
        }

        return true;
    }

    public function calculate(array $params): array
    {
        $trace = $this->trace($params);
        return $trace['final_result'];
    }

    public function trace(array $params): array
    {
        $trace = [];
        $trace['mode'] = self::getName();
        $trace['steps'] = [];
        $n = static fn ($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjang = $n($params['wall_length']); // m
        $tinggi = $n($params['wall_height']); // m
        $tebalAdukan = $n($params['mortar_thickness']); // cm
        $sisiPlesteran = $n($params['plaster_sides']); // jumlah sisi

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $panjang . ' m',
                'Tinggi Dinding' => $tinggi . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
                'Jumlah Sisi Plesteran' => $sisiPlesteran,
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $cement = Cement::findOrFail($params['cement_id']);
        $sand = Sand::findOrFail($params['sand_id']);

        $satuanKemasanSemen = $n($cement->package_weight_net); // kg (default 50)
        $densitySemen = 1440; // kg/M3

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Semen' => $cement->brand . ' (' . $satuanKemasanSemen . ' kg)',
                'Pasir' => $sand->brand,
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Rasio Campuran' => '1 : 4 : 30% (Semen : Pasir : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        $luasBidang = $n($panjang * $tinggi);

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang',
            'formula' => 'Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$panjang × $tinggi",
                'Hasil' => NumberHelper::format($luasBidang) . ' M2',
            ],
        ];

        // ============ STEP 4: Hitung Volume Adukan Kubik Per Kemasan ============

        // Kubik semen per kemasan
        $kubikSemenPerKemasan = $n($satuanKemasanSemen * (1 / $densitySemen));

        // Kubik pasir per kemasan (ratio 1:4)
        $kubikPasirPerKemasan = $n($kubikSemenPerKemasan * 4);

        // Kubik air per kemasan (30% dari volume padat)
        $kubikAirPerKemasan = $n(($kubikSemenPerKemasan + $kubikPasirPerKemasan) * 0.3);

        // Total volume adukan per kemasan
        $volumeAdukanKubikPerKemasan = $n($kubikSemenPerKemasan + $kubikPasirPerKemasan + $kubikAirPerKemasan);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Volume Adukan Per Kemasan Semen',
            'info' => 'Ratio 1 : 4 : 30% (Semen : Pasir : Air)',
            'calculations' => [
                'Kubik Semen' => NumberHelper::format($kubikSemenPerKemasan) . ' M3',
                'Kubik Pasir' => NumberHelper::format($kubikPasirPerKemasan) . ' M3',
                'Kubik Air' => NumberHelper::format($kubikAirPerKemasan) . ' M3',
                'Total Volume Adukan' => NumberHelper::format($volumeAdukanKubikPerKemasan) . ' M3',
            ],
        ];

        // ============ STEP 5: Hitung Luas Plesteran Per 1 Kemasan ============
        $tebalAdukanMeter = $n($tebalAdukan / 100); // konversi cm ke meter
        $luasPlesteranPer1Kemasan = $n($volumeAdukanKubikPerKemasan / $tebalAdukanMeter);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Luas Plesteran Per 1 Kemasan Semen',
            'formula' => 'Volume adukan kubik per kemasan / (tebal adukan / 100)',
            'info' => 'Berapa M2 yang bisa diplester dengan 1 sak semen',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($volumeAdukanKubikPerKemasan) . ' / ' . NumberHelper::format($tebalAdukanMeter),
                'Hasil' => NumberHelper::format($luasPlesteranPer1Kemasan) . ' M2',
            ],
        ];

        // ============ STEP 6: Hitung Koefisien Material Per 1 M2 ============

        // Sak semen per 1M2
        $sakSemenPer1M2 = $n(1 / $luasPlesteranPer1Kemasan);

        // Kg semen per 1M2
        $kgSemenPer1M2 = $n($satuanKemasanSemen / $luasPlesteranPer1Kemasan);

        // Kubik semen per 1M2
        $kubikSemenPer1M2 = $n($kubikSemenPerKemasan / $luasPlesteranPer1Kemasan);

        // Sak pasir per 1M2 (mengikuti ratio 4x dari semen)
        $sakPasirPer1M2 = $n(4 / $luasPlesteranPer1Kemasan);

        // Kubik pasir per 1M2
        $kubikPasirPer1M2 = $n($kubikPasirPerKemasan / $luasPlesteranPer1Kemasan);

        // Liter air per 1M2
        $literAirPer1M2 = $n(($kubikAirPerKemasan * 1000) / $luasPlesteranPer1Kemasan);

        // Kubik air per 1M2
        $kubikAirPer1M2 = $n($kubikAirPerKemasan / $luasPlesteranPer1Kemasan);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Koefisien Material Per 1 M2',
            'calculations' => [
                'Sak Semen per 1 M2' => NumberHelper::format($sakSemenPer1M2) . ' sak',
                'Kg Semen per 1 M2' => NumberHelper::format($kgSemenPer1M2) . ' kg',
                'Kubik Semen per 1 M2' => NumberHelper::format($kubikSemenPer1M2) . ' M3',
                'Sak Pasir per 1 M2' => NumberHelper::format($sakPasirPer1M2) . ' sak',
                'Kubik Pasir per 1 M2' => NumberHelper::format($kubikPasirPer1M2) . ' M3',
                'Liter Air per 1 M2' => NumberHelper::format($literAirPer1M2) . ' liter',
                'Kubik Air per 1 M2' => NumberHelper::format($kubikAirPer1M2) . ' M3',
            ],
        ];

        // ============ STEP 7: Hitung Total Luas Plesteran ============
        $totalLuasPlesteran = $n($luasBidang * $sisiPlesteran);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Luas Plesteran',
            'formula' => 'Luas bidang × Jumlah sisi plesteran',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($luasBidang) . " × $sisiPlesteran",
                'Hasil' => NumberHelper::format($totalLuasPlesteran) . ' M2',
            ],
        ];

        // ============ STEP 8: Hitung Kebutuhan Material Pekerjaan ============

        // Sak semen pekerjaan
        $sakSemenPekerjaan = $n($sakSemenPer1M2 * $totalLuasPlesteran);

        // Kg semen pekerjaan
        $kgSemenPekerjaan = $n($kgSemenPer1M2 * $totalLuasPlesteran);

        // Kubik semen pekerjaan
        $kubikSemenPekerjaan = $n($kubikSemenPer1M2 * $totalLuasPlesteran);

        // Sak pasir pekerjaan
        $sakPasirPekerjaan = $n($sakPasirPer1M2 * $totalLuasPlesteran);

        // Kubik pasir pekerjaan
        $kubikPasirPekerjaan = $n($kubikPasirPer1M2 * $totalLuasPlesteran);

        // Kubik air pekerjaan
        $kubikAirPekerjaan = $n($kubikAirPer1M2 * $totalLuasPlesteran);

        // Liter air pekerjaan (derived from M3)
        $literAirPekerjaan = $kubikAirPekerjaan * 1000;

        // Volume adukan pekerjaan
        $volumeAdukanPekerjaan = $n($kubikSemenPekerjaan + $kubikPasirPekerjaan + $kubikAirPekerjaan);

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Kebutuhan Material Pekerjaan',
            'info' => 'Total Luas: ' . NumberHelper::format($totalLuasPlesteran) . ' M2',
            'calculations' => [
                'Semen (Sak)' => NumberHelper::format($sakSemenPekerjaan),
                'Semen (Kg)' => NumberHelper::format($kgSemenPekerjaan),
                'Semen (M3)' => NumberHelper::format($kubikSemenPekerjaan),
                'Pasir (Sak)' => NumberHelper::format($sakPasirPekerjaan),
                'Pasir (M3)' => NumberHelper::format($kubikPasirPekerjaan),
                'Air (Liter)' => NumberHelper::format($literAirPekerjaan),
                'Air (M3)' => NumberHelper::format($kubikAirPekerjaan),
                'Volume Adukan Total' => NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 9: Hitung Harga ============
        $cementPrice = $n($cement->package_price ?? 0, 0); // Harga per sak
        $sandPricePerM3 = $n($sand->comparison_price_per_m3 ?? 0, 0);

        if ($sandPricePerM3 == 0 && $sand->package_price && $sand->package_volume > 0) {
            $sandPricePerM3 = $n($sand->package_price / $sand->package_volume, 0);
        }

        $totalCementPrice = $n($sakSemenPekerjaan * $cementPrice, 0);
        $totalSandPrice = $n($kubikPasirPekerjaan * $sandPricePerM3, 0);
        $grandTotal = $n($totalCementPrice + $totalSandPrice, 0);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Semen per Sak' => NumberHelper::currency($cementPrice),
                'Total Harga Semen' => NumberHelper::currency($totalCementPrice),
                'Harga Pasir per M3' => NumberHelper::currency($sandPricePerM3),
                'Total Harga Pasir' => NumberHelper::currency($totalSandPrice),
                'Grand Total' => NumberHelper::currency($grandTotal),
            ],
        ];

        // ============ Final Result ============
        $trace['final_result'] = [
            'total_bricks' => 0, // Not applicable for plastering
            'brick_price_per_piece' => 0,
            'total_brick_price' => 0,
            'cement_sak' => $sakSemenPekerjaan,
            'cement_kg' => $kgSemenPekerjaan,
            'cement_m3' => $kubikSemenPekerjaan,
            'sand_m3' => $kubikPasirPekerjaan,
            'sand_sak' => $sakPasirPekerjaan,
            'water_liters' => $literAirPekerjaan,
            'water_m3' => $kubikAirPekerjaan,
            'mortar_volume_m3' => $volumeAdukanPekerjaan,

            // Prices
            'cement_price_per_sak' => $cementPrice,
            'total_cement_price' => $totalCementPrice,
            'sand_price_per_m3' => $sandPricePerM3,
            'total_sand_price' => $totalSandPrice,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $totalLuasPlesteran,
            'area_per_side' => $luasBidang,
            'plaster_sides' => $sisiPlesteran,
        ];

        return $trace;
    }
}
