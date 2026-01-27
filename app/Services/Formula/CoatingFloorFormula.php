<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Cement;

/**
 * Formula - Perhitungan Acian Semen
 * Menghitung kebutuhan material untuk acian dinding (hanya semen + air)
 */
class CoatingFloorFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'coating_floor';
    }

    public static function getName(): string
    {
        return 'Aci Lantai';
    }

    public static function getDescription(): string
    {
        return 'Menghitung Aci Lantai dengan input panjang, tinggi, dan tebal adukan.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['cement'];
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'mortar_thickness'];

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
        $n = static fn ($value, $decimals = null) => NumberHelper::normalize($value, $decimals);

        // ============ STEP 1: Load Input Parameters ============
        $panjang = $n($params['wall_length']); // m
        $tinggi = $n($params['wall_height']); // m
        $tebalAdukan = $n($params['mortar_thickness']); // cm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $panjang . ' m',
                'Tinggi Dinding' => $tinggi . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $cementId = !empty($params['cement_id']) ? $params['cement_id'] : Cement::first()->id;
        $cement = Cement::findOrFail($cementId);

        $satuanKemasanSemen = $n($cement->package_weight_net); // kg (default 50)
        $densitySemen = 1440; // kg/M3

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Semen' => $cement->brand . ' (' . $satuanKemasanSemen . ' kg)',
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Rasio Campuran' => '1 : 40% (Semen : Air)',
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

        // Kubik air per kemasan (40% dari volume semen)
        $kubikAirPerKemasan = $n($kubikSemenPerKemasan * 0.4);

        // Total volume adukan per kemasan (semen + air)
        $volumeAdukanKubikPerKemasan = $n($kubikSemenPerKemasan + $kubikAirPerKemasan);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Volume Adukan Per Kemasan Semen',
            'info' => 'Ratio 1 : 40% (Semen : Air)',
            'calculations' => [
                'Kubik Semen' => NumberHelper::format($kubikSemenPerKemasan) . ' M3',
                'Kubik Air (40%)' => NumberHelper::format($kubikAirPerKemasan) . ' M3',
                'Total Volume Adukan' => NumberHelper::format($volumeAdukanKubikPerKemasan) . ' M3',
            ],
        ];

        // ============ STEP 5: Hitung Luas Acian Per 1 Kemasan ============
        $tebalAdukanMM = $n($tebalAdukan * 10); // konversi cm ke mm
        $luasAcianPer1Kemasan = $n($volumeAdukanKubikPerKemasan / ($tebalAdukanMM / 1000));

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Luas Acian Per 1 Kemasan Semen',
            'formula' => 'Volume adukan kubik per kemasan / (tebal adukan mm / 1000)',
            'info' => 'Berapa M2 yang bisa diaci dengan 1 sak semen',
            'calculations' => [
                'Tebal Adukan' => $tebalAdukanMM . ' mm',
                'Perhitungan' => NumberHelper::format($volumeAdukanKubikPerKemasan) . ' / (' . $tebalAdukanMM . ' / 1000)',
                'Hasil' => NumberHelper::format($luasAcianPer1Kemasan) . ' M2',
            ],
        ];

        // ============ STEP 6: Hitung Koefisien Material Per 1 M2 ============

        // Sak semen per 1M2
        $sakSemenPer1M2 = $n(1 / $luasAcianPer1Kemasan);

        // Kg semen per 1M2
        $kgSemenPer1M2 = $n($satuanKemasanSemen / $luasAcianPer1Kemasan);

        // Kubik semen per 1M2
        $kubikSemenPer1M2 = $n($kubikSemenPerKemasan / $luasAcianPer1Kemasan);

        // Liter air per 1M2
        $literAirPer1M2 = $n(($kubikAirPerKemasan * 1000) / $luasAcianPer1Kemasan);

        // Kubik air per 1M2
        $kubikAirPer1M2 = $n($kubikAirPerKemasan / $luasAcianPer1Kemasan);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Koefisien Material Per 1 M2',
            'calculations' => [
                'Sak Semen per 1 M2' => NumberHelper::format($sakSemenPer1M2) . ' sak',
                'Kg Semen per 1 M2' => NumberHelper::format($kgSemenPer1M2) . ' kg',
                'Kubik Semen per 1 M2' => NumberHelper::format($kubikSemenPer1M2) . ' M3',
                'Liter Air per 1 M2' => NumberHelper::format($literAirPer1M2) . ' liter',
                'Kubik Air per 1 M2' => NumberHelper::format($kubikAirPer1M2) . ' M3',
            ],
        ];

        // ============ STEP 7: Hitung Total Luas Acian ============
        $totalLuasAcian = $n($luasBidang);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Luas Acian',
            'formula' => 'Luas bidang',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($luasBidang),
                'Hasil' => NumberHelper::format($totalLuasAcian) . ' M2',
            ],
        ];

        // ============ STEP 8: Hitung Kebutuhan Material Pekerjaan ============

        // Sak semen pekerjaan
        $sakSemenPekerjaan = $n($sakSemenPer1M2 * $totalLuasAcian);

        // Kg semen pekerjaan
        $kgSemenPekerjaan = $n($kgSemenPer1M2 * $totalLuasAcian);

        // Kubik semen pekerjaan
        $kubikSemenPekerjaan = $n($kubikSemenPer1M2 * $totalLuasAcian);

        // Kubik air pekerjaan
        $kubikAirPekerjaan = $n($kubikAirPer1M2 * $totalLuasAcian);

        // Liter air pekerjaan (derived from M3 normalized)
        $literAirPekerjaan = $kubikAirPekerjaan * 1000;

        // Volume adukan pekerjaan
        $volumeAdukanPekerjaan = $n($kubikSemenPekerjaan + $kubikAirPekerjaan);

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Kebutuhan Material Pekerjaan',
            'info' => 'Total Luas: ' . NumberHelper::format($totalLuasAcian) . ' M2',
            'calculations' => [
                'Semen (Sak)' => NumberHelper::format($sakSemenPekerjaan),
                'Semen (Kg)' => NumberHelper::format($kgSemenPekerjaan),
                'Semen (M3)' => NumberHelper::format($kubikSemenPekerjaan),
                'Air (Liter)' => NumberHelper::format($literAirPekerjaan),
                'Air (M3)' => NumberHelper::format($kubikAirPekerjaan),
                'Volume Adukan Total' => NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 9: Hitung Harga ============
        $cementPrice = $n($cement->package_price ?? 0, 0); // Harga per sak
        $totalCementPrice = $n($sakSemenPekerjaan * $cementPrice, 0);
        $grandTotal = $n($totalCementPrice, 0); // Hanya semen, tanpa pasir

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Semen per Sak' => NumberHelper::currency($cementPrice),
                'Total Harga Semen' => NumberHelper::currency($totalCementPrice),
                'Grand Total' => NumberHelper::currency($grandTotal),
            ],
        ];

        // ============ Final Result ============
        $trace['final_result'] = [
            'total_bricks' => 0, // Not applicable for skim coating
            'brick_price_per_piece' => 0,
            'total_brick_price' => 0,
            'cement_sak' => $sakSemenPekerjaan,
            'cement_kg' => $kgSemenPekerjaan,
            'cement_m3' => $kubikSemenPekerjaan,
            'sand_m3' => 0, // No sand in skim coating
            'sand_sak' => 0,
            'water_liters' => $literAirPekerjaan,
            'water_m3' => $kubikAirPekerjaan,
            'mortar_volume_m3' => $volumeAdukanPekerjaan,

            // Prices
            'cement_price_per_sak' => $cementPrice,
            'total_cement_price' => $totalCementPrice,
            'sand_price_per_m3' => 0,
            'total_sand_price' => 0,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $totalLuasAcian,
        ];

        return $trace;
    }
}
