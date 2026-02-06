<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Nat;

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

    public static function getMaterialRequirements(): array
    {
        // Ceramic is still required for technical reasons (signature matching in preview)
        // but dimensions are taken from input parameters, not from ceramic selection
        return ['ceramic', 'nat'];
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'grout_thickness'];

        foreach ($required as $field) {
            if (!isset($params[$field]) || !is_numeric($params[$field]) || (float) $params[$field] <= 0) {
                return false;
            }
        }

        // Ceramic dimensions are required
        $ceramicRequired = ['ceramic_length', 'ceramic_width', 'ceramic_thickness'];
        foreach ($ceramicRequired as $field) {
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
        $n = static fn ($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjangBidang = $n($params['wall_length']); // m
        $lebarBidang = $n($params['wall_height']); // m
        $tebalNat = $n($params['grout_thickness']); // mm

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
        $nat = isset($params['nat_id']) ? Nat::find($params['nat_id']) : null;
        $nat = $nat ?: Nat::query()->orderBy('id')->first();

        if (!$nat) {
            throw new \RuntimeException(
                'Data material nat tidak tersedia di database. Pastikan ada data di tabel nats.',
            );
        }

        $densityNat = 1440; // kg/M3

        // Load ceramic dimensions from input parameters
        $panjangKeramik = $n($params['ceramic_length']); // cm
        $lebarKeramik = $n($params['ceramic_width']); // cm
        $tebalKeramikMm = $n($params['ceramic_thickness']); // mm
        $tebalKeramikCm = $n($tebalKeramikMm / 10); // cm

        if ($panjangKeramik <= 0 || $lebarKeramik <= 0 || $tebalKeramikMm <= 0) {
            throw new \RuntimeException('Data dimensi keramik tidak valid. Pastikan Panjang, Lebar, dan Tebal Keramik sudah diisi.');
        }

        // Grout parameters from database
        $beratKemasanNat = $n($nat->package_weight_net > 0 ? $nat->package_weight_net : 1); // kg per bungkus
        $volumePastaNatPerBungkus = $n($nat->package_volume > 0 ? $nat->package_volume : 0.00069444); // M3 per bungkus
        $hargaNatPerBungkus = $n($nat->package_price ?? 0, 0);

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Dimensi Keramik' => $panjangKeramik . ' x ' . $lebarKeramik . ' cm',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Nat' => $nat->brand . ' (' . $beratKemasanNat . ' kg)',
                'Berat Kemasan Nat' => $beratKemasanNat . ' kg',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Densitas Nat' => $densityNat . ' kg/M3',
                'Rasio Adukan Nat' => '1 : 33% (Nat : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        $luasBidang = $n($panjangBidang * $lebarBidang);

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
        $jumlahKolomNat = (int) ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100)) + 1;

        // jumlah baris nat per pekerjaan = (Lebar bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = (int) ceil($lebarBidang / (($lebarKeramik + $tebalNat / 10) / 100)) + 1;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' => 'ceil(Bidang / ((Dimensi Keramik + Tebal Nat/10) / 100)) + 1',
            'calculations' => [
                'Jumlah Kolom Nat' => NumberHelper::format($jumlahKolomNat) . ' garis',
                'Jumlah Baris Nat' => NumberHelper::format($jumlahBarisNat) . ' garis',
            ],
        ];

        // ============ STEP 5: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat * lebar bidang) + (jumlah baris nat * Panjang bidang)
        $panjangBentanganNat = $n($jumlahKolomNat * $lebarBidang + $jumlahBarisNat * $panjangBidang);

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
        $volumeNatPekerjaan = $n(($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000);

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
        $kebutuhanBungkusNat = $n($volumeNatPekerjaan / $volumePastaNatPerBungkus);

        // jumlah kebutuhan kg nat per pekerjaan = setara dengan jumlah kebutuhan bungkus kemasan nat per pekerjaan
        $kebutuhanKgNat = $n($kebutuhanBungkusNat * $beratKemasanNat);

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
        $kubikNatPerBungkus = $n((1 / $densityNat) * $beratKemasanNat);

        // kubik air per ratio = kubik nat per bungkus * 33%
        $kubikAirNatPerBungkus = $n($kubikNatPerBungkus * 0.33);

        // Liter Air per ratio = Kubik air per ratio * 1000
        $literAirNatPerBungkus = $n($kubikAirNatPerBungkus * 1000);

        // Volume adukan nat = Kubik nat per bungkus + kubik air dari ratio 33%
        $volumeAdukanNatPerBungkus = $n($kubikNatPerBungkus + $kubikAirNatPerBungkus);

        // Total untuk pekerjaan
        $kubikNatPekerjaan = $n($kubikNatPerBungkus * $kebutuhanBungkusNat);
        $kubikAirNatPekerjaan = $n($kubikAirNatPerBungkus * $kebutuhanBungkusNat);

        // Kebutuhan liter air per pekerjaan = Kubik Air * 1000
        $literAirNatPekerjaan = $kubikAirNatPekerjaan * 1000;

        $volumeAdukanNatPekerjaan = $n($volumeAdukanNatPerBungkus * $kebutuhanBungkusNat);

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
        $totalGroutPrice = $n($kebutuhanBungkusNat * $hargaNatPerBungkus, 0);
        $grandTotal = $n($totalGroutPrice, 0);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Total Harga Nat' => NumberHelper::currency($totalGroutPrice),
                'Grand Total' => NumberHelper::currency($grandTotal),
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
