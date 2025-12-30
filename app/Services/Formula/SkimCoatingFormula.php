<?php

namespace App\Services\Formula;

use App\Models\Cement;

/**
 * Formula - Perhitungan Acian Semen
 * Menghitung kebutuhan material untuk acian dinding (hanya semen + air)
 */
class SkimCoatingFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'skim_coating';
    }

    public static function getName(): string
    {
        return 'Aci Dinding';
    }

    public static function getDescription(): string
    {
        return 'Menghitung Aci Dinding dengan input panjang, tinggi, tebal adukan dan jumlah sisi.';
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'mortar_thickness', 'skim_sides', 'cement_id'];

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

        // ============ STEP 1: Load Input Parameters ============
        $panjang = $params['wall_length']; // m
        $tinggi = $params['wall_height']; // m
        $tebalAdukan = $params['mortar_thickness']; // cm
        $sisiAci = $params['skim_sides']; // jumlah sisi

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $panjang . ' m',
                'Tinggi Dinding' => $tinggi . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
                'Jumlah Sisi Aci' => $sisiAci,
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $cement = Cement::findOrFail($params['cement_id']);

        $satuanKemasanSemen = $cement->package_weight_net; // kg (default 50)
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
        $luasBidang = $panjang * $tinggi;

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang',
            'formula' => 'Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$panjang × $tinggi",
                'Hasil' => number_format($luasBidang, 4) . ' M2',
            ],
        ];

        // ============ STEP 4: Hitung Volume Adukan Kubik Per Kemasan ============

        // Kubik semen per kemasan
        $kubikSemenPerKemasan = $satuanKemasanSemen * (1 / $densitySemen);

        // Kubik air per kemasan (40% dari volume semen)
        $kubikAirPerKemasan = $kubikSemenPerKemasan * 0.4;

        // Total volume adukan per kemasan (semen + air)
        $volumeAdukanKubikPerKemasan = $kubikSemenPerKemasan + $kubikAirPerKemasan;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Volume Adukan Per Kemasan Semen',
            'info' => 'Ratio 1 : 40% (Semen : Air)',
            'calculations' => [
                'Kubik Semen' => number_format($kubikSemenPerKemasan, 6) . ' M3',
                'Kubik Air (40%)' => number_format($kubikAirPerKemasan, 6) . ' M3',
                'Total Volume Adukan' => number_format($volumeAdukanKubikPerKemasan, 6) . ' M3',
            ],
        ];

        // ============ STEP 5: Hitung Luas Acian Per 1 Kemasan ============
        $tebalAdukanMM = $tebalAdukan * 10; // konversi cm ke mm
        $luasAcianPer1Kemasan = $volumeAdukanKubikPerKemasan / ($tebalAdukanMM / 1000);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Luas Acian Per 1 Kemasan Semen',
            'formula' => 'Volume adukan kubik per kemasan / (tebal adukan mm / 1000)',
            'info' => 'Berapa M2 yang bisa diaci dengan 1 sak semen',
            'calculations' => [
                'Tebal Adukan' => $tebalAdukanMM . ' mm',
                'Perhitungan' => number_format($volumeAdukanKubikPerKemasan, 6) . ' / (' . $tebalAdukanMM . ' / 1000)',
                'Hasil' => number_format($luasAcianPer1Kemasan, 4) . ' M2',
            ],
        ];

        // ============ STEP 6: Hitung Koefisien Material Per 1 M2 ============

        // Sak semen per 1M2
        $sakSemenPer1M2 = 1 / $luasAcianPer1Kemasan;

        // Kg semen per 1M2
        $kgSemenPer1M2 = $satuanKemasanSemen / $luasAcianPer1Kemasan;

        // Kubik semen per 1M2
        $kubikSemenPer1M2 = $kubikSemenPerKemasan / $luasAcianPer1Kemasan;

        // Liter air per 1M2
        $literAirPer1M2 = ($kubikAirPerKemasan * 1000) / $luasAcianPer1Kemasan;

        // Kubik air per 1M2
        $kubikAirPer1M2 = $kubikAirPerKemasan / $luasAcianPer1Kemasan;

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Koefisien Material Per 1 M2',
            'calculations' => [
                'Sak Semen per 1 M2' => number_format($sakSemenPer1M2, 4) . ' sak',
                'Kg Semen per 1 M2' => number_format($kgSemenPer1M2, 4) . ' kg',
                'Kubik Semen per 1 M2' => number_format($kubikSemenPer1M2, 6) . ' M3',
                'Liter Air per 1 M2' => number_format($literAirPer1M2, 4) . ' liter',
                'Kubik Air per 1 M2' => number_format($kubikAirPer1M2, 6) . ' M3',
            ],
        ];

        // ============ STEP 7: Hitung Total Luas Acian ============
        $totalLuasAcian = $luasBidang * $sisiAci;

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Luas Acian',
            'formula' => 'Luas bidang × Jumlah sisi aci',
            'calculations' => [
                'Perhitungan' => number_format($luasBidang, 4) . " × $sisiAci",
                'Hasil' => number_format($totalLuasAcian, 4) . ' M2',
            ],
        ];

        // ============ STEP 8: Hitung Kebutuhan Material Pekerjaan ============

        // Sak semen pekerjaan
        $sakSemenPekerjaan = $sakSemenPer1M2 * $totalLuasAcian;

        // Kg semen pekerjaan
        $kgSemenPekerjaan = $kgSemenPer1M2 * $totalLuasAcian;

        // Kubik semen pekerjaan
        $kubikSemenPekerjaan = $kubikSemenPer1M2 * $totalLuasAcian;

        // Liter air pekerjaan
        $literAirPekerjaan = $literAirPer1M2 * $totalLuasAcian;

        // Kubik air pekerjaan
        $kubikAirPekerjaan = $kubikAirPer1M2 * $totalLuasAcian;

        // Volume adukan pekerjaan
        $volumeAdukanPekerjaan = $kubikSemenPekerjaan + $kubikAirPekerjaan;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Kebutuhan Material Pekerjaan',
            'info' => 'Total Luas: ' . number_format($totalLuasAcian, 4) . ' M2',
            'calculations' => [
                'Semen (Sak)' => number_format($sakSemenPekerjaan, 4),
                'Semen (Kg)' => number_format($kgSemenPekerjaan, 4),
                'Semen (M3)' => number_format($kubikSemenPekerjaan, 6),
                'Air (Liter)' => number_format($literAirPekerjaan, 2),
                'Air (M3)' => number_format($kubikAirPekerjaan, 6),
                'Volume Adukan Total' => number_format($volumeAdukanPekerjaan, 6) . ' M3',
            ],
        ];

        // ============ STEP 9: Hitung Harga ============
        $cementPrice = $cement->package_price ?? 0; // Harga per sak
        $totalCementPrice = $sakSemenPekerjaan * $cementPrice;
        $grandTotal = $totalCementPrice; // Hanya semen, tanpa pasir

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Semen per Sak' => 'Rp ' . number_format($cementPrice, 0, ',', '.'),
                'Total Harga Semen' => 'Rp ' . number_format($totalCementPrice, 0, ',', '.'),
                'Grand Total' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
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
            'area_per_side' => $luasBidang,
            'skim_sides' => $sisiAci,
        ];

        return $trace;
    }
}
