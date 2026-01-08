<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Cement;
use App\Models\Ceramic;

/**
 * Formula - Perhitungan Pekerjaan Nat Keramik
 * Menghitung kebutuhan material nat untuk pemasangan keramik
 */
class GroutTileFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'grout_tile';
    }

    public static function getName(): string
    {
        return 'Pasang Nat';
    }

    public static function getDescription(): string
    {
        return 'Menghitung kebutuhan nat (grout) berdasarkan luas bidang dan ukuran keramik.';
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'grout_thickness'];

        foreach ($required as $field) {
            if (!isset($params[$field]) || !is_numeric($params[$field]) || (float) $params[$field] <= 0) {
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
        $panjangBidang = (float) $params['wall_length']; // m
        $lebarBidang = (float) $params['wall_height']; // m
        $tebalNat = (float) $params['grout_thickness']; // mm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Bidang' => $panjangBidang . ' m',
                'Lebar Bidang' => $lebarBidang . ' m',
                'Tebal Nat' => $tebalNat . ' mm',
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $ceramic = isset($params['ceramic_id']) ? Ceramic::find($params['ceramic_id']) : null;
        $nat = isset($params['nat_id']) ? Cement::find($params['nat_id']) : null;

        $ceramic = $ceramic ?: Ceramic::first();
        $nat = $nat ?: Cement::where('type', 'Nat')->first();

        if (!$ceramic) {
            throw new \RuntimeException('Data keramik tidak tersedia di database.');
        }

        if (!$nat) {
            throw new \RuntimeException(
                'Data material nat tidak tersedia di database. Pastikan ada data cement dengan type "Nat".',
            );
        }

        $densityNat = 1440; // kg/M3

        // Use custom ceramic dimensions from input if provided, otherwise use from database
        $panjangKeramik = isset($params['ceramic_length']) && $params['ceramic_length'] > 0
            ? (float) $params['ceramic_length']
            : (float) $ceramic->dimension_length; // cm

        $lebarKeramik = isset($params['ceramic_width']) && $params['ceramic_width'] > 0
            ? (float) $params['ceramic_width']
            : (float) $ceramic->dimension_width; // cm

        $tebalKeramikCm = (float) $ceramic->dimension_thickness; // cm
        $tebalKeramikMm = $tebalKeramikCm * 10; // mm

        // Note: Isi per dus tidak krusial untuk hitungan nat murni, tapi dimensi sangat penting.

        if ($panjangKeramik <= 0 || $lebarKeramik <= 0) {
            throw new \RuntimeException('Data dimensi keramik tidak valid. Pastikan Panjang dan Lebar Keramik sudah diisi.');
        }

        // Grout parameters from database
        $beratKemasanNat = $nat->package_weight_net > 0 ? $nat->package_weight_net : 1; // kg per bungkus
        $volumePastaNatPerBungkus = $nat->package_volume > 0 ? $nat->package_volume : 0.00069444; // M3 per bungkus
        $hargaNatPerBungkus = $nat->package_price ?? 0;

        $isCustomDimension = isset($params['ceramic_length']) && $params['ceramic_length'] > 0;
        $dimensionSource = $isCustomDimension ? ' (Custom Input)' : ' (Database)';

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Keramik' => $ceramic->brand . ' (' . $panjangKeramik . 'x' . $lebarKeramik . ' cm)' . $dimensionSource,
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Nat' => $nat->brand . ' (' . $beratKemasanNat . ' kg)',
                'Berat Kemasan Nat' => $beratKemasanNat . ' kg',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Harga Nat per Bungkus' => 'Rp ' . number_format($hargaNatPerBungkus, 0, ',', '.'),
                'Densitas Nat' => $densityNat . ' kg/M3',
                'Rasio Adukan Nat' => '1 : 33% (Nat : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        $luasBidang = $panjangBidang * $lebarBidang;

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang',
            'formula' => 'Panjang × Lebar',
            'calculations' => [
                'Perhitungan' => "$panjangBidang × $lebarBidang",
                'Hasil' => NumberHelper::format($luasBidang) . ' M2',
            ],
        ];

        // ==================== BAGIAN UTAMA: MENGHITUNG KEBUTUHAN NAT ====================

        // ============ STEP 4: Jumlah Kolom dan Baris Nat ============
        // jumlah kolom nat per pekerjaan = (Panjang bidang / ((Panjang keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahKolomNat = ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100)) + 1;

        // jumlah baris nat per pekerjaan = (Lebar bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = ceil($lebarBidang / (($lebarKeramik + $tebalNat / 10) / 100)) + 1;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' => 'ceil(Bidang / ((Dimensi Keramik + Tebal Nat/10) / 100)) + 1',
            'calculations' => [
                'Jumlah Kolom Nat' => number_format($jumlahKolomNat, 0) . ' garis',
                'Jumlah Baris Nat' => number_format($jumlahBarisNat, 0) . ' garis',
            ],
        ];

        // ============ STEP 5: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat * lebar bidang) + (jumlah baris nat * Panjang bidang)
        $panjangBentanganNat = $jumlahKolomNat * $lebarBidang + $jumlahBarisNat * $panjangBidang;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Panjang Bentangan Nat',
            'formula' => '(Jumlah Kolom Nat × Lebar Bidang) + (Jumlah Baris Nat × Panjang Bidang)',
            'calculations' => [
                'Perhitungan' =>
                    '(' .
                    $jumlahKolomNat .
                    ' × ' .
                    $lebarBidang .
                    ') + (' .
                    $jumlahBarisNat .
                    ' × ' .
                    $panjangBidang .
                    ')',
                'Hasil' => NumberHelper::format($panjangBentanganNat) . ' m',
            ],
        ];

        // ============ STEP 6: Volume Nat per Pekerjaan ============
        // Volume nat per pekerjaan = Panjang bentangan nat * tebal nat * tebal keramik / 1000000
        $volumeNatPekerjaan = ($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000;

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Volume Nat per Pekerjaan',
            'formula' => 'Panjang Bentangan × Tebal Nat × Tebal Keramik / 1000000',
            'calculations' => [
                'Panjang Bentangan' => NumberHelper::format($panjangBentanganNat) . ' m',
                'Tebal Nat' => $tebalNat . ' mm',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Volume Nat' => NumberHelper::format($volumeNatPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 7: Kebutuhan Bungkus Nat ============
        // jumlah kebutuhan bungkus kemasan nat per pekerjaan = volume nat per pekerjaan / Volume pasta nat per bungkus
        $kebutuhanBungkusNat = $volumeNatPekerjaan / $volumePastaNatPerBungkus;

        // jumlah kebutuhan kg nat per pekerjaan = setara dengan jumlah kebutuhan bungkus kemasan nat per pekerjaan
        $kebutuhanKgNat = $kebutuhanBungkusNat * $beratKemasanNat;

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Kebutuhan Bungkus Nat',
            'formula' => 'Volume Nat / Volume Pasta Nat per Bungkus',
            'calculations' => [
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Kebutuhan Bungkus' => NumberHelper::format($kebutuhanBungkusNat) . ' bungkus',
                'Kebutuhan (Kg)' => NumberHelper::format($kebutuhanKgNat) . ' kg',
            ],
        ];

        // ============ STEP 8: Volume Adukan Nat ============
        // Kubik nat per bungkus = (1 / 1440) * berat kemasan nat
        $kubikNatPerBungkus = (1 / $densityNat) * $beratKemasanNat;

        // kubik air per ratio = kubik nat per bungkus * 33%
        $kubikAirNatPerBungkus = $kubikNatPerBungkus * 0.33;

        // Liter Air per ratio = Kubik air per ratio * 1000
        $literAirNatPerBungkus = $kubikAirNatPerBungkus * 1000;

        // Volume adukan nat = Kubik nat per bungkus + kubik air dari ratio 33%
        $volumeAdukanNatPerBungkus = $kubikNatPerBungkus + $kubikAirNatPerBungkus;

        // Total untuk pekerjaan
        $kubikNatPekerjaan = $kubikNatPerBungkus * $kebutuhanBungkusNat;
        $kubikAirNatPekerjaan = $kubikAirNatPerBungkus * $kebutuhanBungkusNat;

        // Kebutuhan liter air per pekerjaan = Liter ratio air dari nat (33%)
        $literAirNatPekerjaan = $literAirNatPerBungkus * $kebutuhanBungkusNat;

        $volumeAdukanNatPekerjaan = $volumeAdukanNatPerBungkus * $kebutuhanBungkusNat;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Volume Adukan Nat',
            'info' => 'Ratio 1 : 33% (Nat : Air)',
            'calculations' => [
                'Kubik Nat per Bungkus' =>
                    NumberHelper::format($kubikNatPerBungkus) . ' M3 (= (1/1440) × ' . $beratKemasanNat . ' kg)',
                'Kubik Air per Ratio' => NumberHelper::format($kubikAirNatPerBungkus) . ' M3 (= 33% × kubik nat)',
                'Liter Air per Ratio' => NumberHelper::format($literAirNatPerBungkus) . ' liter',
                'Volume Adukan per Bungkus' => NumberHelper::format($volumeAdukanNatPerBungkus) . ' M3',
                '---' => '---',
                'Total Kubik Nat Pekerjaan' => NumberHelper::format($kubikNatPekerjaan) . ' M3',
                'Total Kubik Air Pekerjaan' => NumberHelper::format($kubikAirNatPekerjaan) . ' M3',
                'Total Liter Air Pekerjaan' => NumberHelper::format($literAirNatPekerjaan) . ' liter',
                'Total Volume Adukan Nat' => NumberHelper::format($volumeAdukanNatPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 9: Harga ============
        $totalGroutPrice = $kebutuhanBungkusNat * $hargaNatPerBungkus;
        $grandTotal = $totalGroutPrice;

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Nat per Bungkus' => 'Rp ' . number_format($hargaNatPerBungkus, 0, ',', '.'),
                'Total Harga Nat' => 'Rp ' . number_format($totalGroutPrice, 0, ',', '.'),
                'Grand Total' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
            ],
        ];

        // ============ Final Result ============
        $trace['final_result'] = [
            // Nat (Grout)
            'grout_packages' => $kebutuhanBungkusNat,
            'grout_kg' => $kebutuhanKgNat,
            'grout_m3' => $kubikNatPekerjaan,
            'water_grout_liters' => $literAirNatPekerjaan,
            'water_grout_m3' => $kubikAirNatPekerjaan,
            'grout_mortar_volume_m3' => $volumeAdukanNatPekerjaan,

            // Total Air (Only from Grout)
            'total_water_liters' => $literAirNatPekerjaan,
            'total_water_m3' => $kubikAirNatPekerjaan,

            // Prices
            'grout_price_per_package' => $hargaNatPerBungkus,
            'total_grout_price' => $totalGroutPrice,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $luasBidang,
        ];

        return $trace;
    }
}
