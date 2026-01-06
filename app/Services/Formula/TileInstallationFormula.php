<?php

namespace App\Services\Formula;

use App\Models\Cement;
use App\Models\Sand;
use App\Models\Ceramic;

/**
 * Formula - Perhitungan Pasang Keramik
 * Menghitung kebutuhan material untuk pemasangan keramik
 */
class TileInstallationFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'tile_installation';
    }

    public static function getName(): string
    {
        return 'Pasang Keramik';
    }

    public static function getDescription(): string
    {
        return 'Menghitung kebutuhan keramik, adukan semen, dan nat untuk pemasangan keramik.';
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'mortar_thickness', 'grout_thickness'];

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
        $tebalAdukan = (float) $params['mortar_thickness']; // cm
        $tebalNat = (float) $params['grout_thickness']; // mm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Bidang' => $panjangBidang . ' m',
                'Lebar Bidang' => $lebarBidang . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
                'Tebal Nat' => $tebalNat . ' mm',
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : null;
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : null;
        $ceramic = isset($params['ceramic_id']) ? Ceramic::find($params['ceramic_id']) : null;

        $cement = $cement ?: Cement::first();
        $sand = $sand ?: Sand::first();
        $ceramic = $ceramic ?: Ceramic::first();

        if (!$cement || !$sand || !$ceramic) {
            throw new \RuntimeException('Data material (semen/pasir/keramik) tidak tersedia di database.');
        }

        $kemasanSemen = $cement->package_weight_net > 0 ? $cement->package_weight_net : 50; // kg
        $densitySemen = 1440; // kg/M3
        $densityNat = 1440; // kg/M3

        $panjangKeramik = (float) $ceramic->dimension_length; // cm
        $lebarKeramik = (float) $ceramic->dimension_width; // cm
        $tebalKeramikCm = (float) $ceramic->dimension_thickness; // cm
        $tebalKeramikMm = $tebalKeramikCm * 10; // mm
        $isiPerDus = (int) $ceramic->pieces_per_package;

        if ($panjangKeramik <= 0 || $lebarKeramik <= 0 || $isiPerDus <= 0) {
            throw new \RuntimeException('Data keramik tidak lengkap (dimensi/isi per dus).');
        }

        // Grout parameters
        $beratKemasanNat = $params['grout_package_weight'] ?? 5; // kg per bungkus
        $volumePastaNatPerBungkus = $params['grout_volume_per_package'] ?? 0.0035; // M3 per bungkus
        $hargaNatPerBungkus = $params['grout_price_per_package'] ?? 0;

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Semen' => $cement->brand . ' (' . $kemasanSemen . ' kg)',
                'Pasir' => $sand->brand,
                'Keramik' => $ceramic->brand . ' (' . $panjangKeramik . 'x' . $lebarKeramik . ' cm)',
                'Isi per Dus Keramik' => $isiPerDus . ' pcs',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Berat Kemasan Nat' => $beratKemasanNat . ' kg',
                'Volume Pasta Nat per Bungkus' => number_format($volumePastaNatPerBungkus, 6) . ' M3',
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Rasio Adukan Semen' => '1 : 3 : 30% (Semen : Pasir : Air)',
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
                'Hasil' => number_format($luasBidang, 4) . ' M2',
            ],
        ];

        // ==================== BAGIAN 1: MENGHITUNG KEBUTUHAN ADUKAN ====================

        // ============ STEP 4: Kubik per Kemasan (Semen, Pasir, Air) ============
        // kubik perkemasan semen = kemasan semen * (1 / 1440)
        $kubikPerKemasanSemen = $kemasanSemen * (1 / $densitySemen);

        // Kubik per kemasan pasir = 3 * kubik per kemasan semen
        $kubikPerKemasanPasir = 3 * $kubikPerKemasanSemen;

        // kubik per kemasan air = (Kubik per kemasan semen + Kubik per kemasan pasir) * 30%
        $kubikPerKemasanAir = ($kubikPerKemasanSemen + $kubikPerKemasanPasir) * 0.3;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Kubik per Kemasan (Semen, Pasir, Air)',
            'info' => 'Ratio 1 : 3 : 30% (Semen : Pasir : Air)',
            'calculations' => [
                'Kubik per Kemasan Semen' => number_format($kubikPerKemasanSemen, 6) . ' M3 (= ' . $kemasanSemen . ' × (1/1440))',
                'Kubik per Kemasan Pasir' => number_format($kubikPerKemasanPasir, 6) . ' M3 (= 3 × kubik semen)',
                'Kubik per Kemasan Air' => number_format($kubikPerKemasanAir, 6) . ' M3 (= 30% × (semen + pasir))',
            ],
        ];

        // ============ STEP 5: Volume Adukan per Kemasan Semen ============
        // Volume adukan per kemasan semen = Kubik perkemasan semen + pasir + air
        $volumeAdukanPerKemasan = $kubikPerKemasanSemen + $kubikPerKemasanPasir + $kubikPerKemasanAir;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Volume Adukan per Kemasan Semen',
            'formula' => 'Kubik Semen + Kubik Pasir + Kubik Air',
            'calculations' => [
                'Perhitungan' => number_format($kubikPerKemasanSemen, 6) . ' + ' . number_format($kubikPerKemasanPasir, 6) . ' + ' . number_format($kubikPerKemasanAir, 6),
                'Hasil' => number_format($volumeAdukanPerKemasan, 6) . ' M3',
            ],
        ];

        // ============ STEP 6: Luas Screedan per Kemasan Semen ============
        // Luas screedan per kemasan semen = Volume adukan per kemasan semen / (tebal adukan / 100)
        $luasScreedanPerKemasan = $volumeAdukanPerKemasan / ($tebalAdukan / 100);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Luas Screedan per Kemasan Semen',
            'formula' => 'Volume adukan per kemasan / (tebal adukan / 100)',
            'info' => 'Berapa M2 yang bisa di-screed dengan 1 kemasan semen',
            'calculations' => [
                'Tebal Adukan (meter)' => number_format($tebalAdukan / 100, 4) . ' m',
                'Perhitungan' => number_format($volumeAdukanPerKemasan, 6) . ' / ' . number_format($tebalAdukan / 100, 4),
                'Hasil' => number_format($luasScreedanPerKemasan, 4) . ' M2',
            ],
        ];

        // ============ STEP 7: Kebutuhan per M2 ============
        // Kebutuhan semen per M2 (kemasan / M2) = 1 kemasan semen / Luas screedan per kemasan semen
        $kebutuhanSemenPerM2Kemasan = 1 / $luasScreedanPerKemasan;
        $kebutuhanSemenPerM2Kg = $kemasanSemen / $luasScreedanPerKemasan;
        $kebutuhanSemenPerM2M3 = $kubikPerKemasanSemen / $luasScreedanPerKemasan;

        // Kebutuhan pasir per M2 = Kubik pasir per kemasan / Luas screedan per kemasan
        $kebutuhanPasirPerM2M3 = $kubikPerKemasanPasir / $luasScreedanPerKemasan;
        $kebutuhanPasirPerM2Sak = 3 / $luasScreedanPerKemasan;

        // Kebutuhan air per M2
        $kebutuhanAirPerM2Liter = ($kubikPerKemasanAir * 1000) / $luasScreedanPerKemasan;
        $kebutuhanAirPerM2M3 = $kubikPerKemasanAir / $luasScreedanPerKemasan;

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Kebutuhan Adukan per M2',
            'calculations' => [
                'Semen per M2 (kemasan)' => number_format($kebutuhanSemenPerM2Kemasan, 4) . ' kemasan/M2',
                'Semen per M2 (kg)' => number_format($kebutuhanSemenPerM2Kg, 4) . ' kg/M2',
                'Semen per M2 (M3)' => number_format($kebutuhanSemenPerM2M3, 6) . ' M3/M2',
                'Pasir per M2 (M3)' => number_format($kebutuhanPasirPerM2M3, 6) . ' M3/M2',
                'Pasir per M2 (sak)' => number_format($kebutuhanPasirPerM2Sak, 4) . ' sak/M2',
                'Air per M2 (liter)' => number_format($kebutuhanAirPerM2Liter, 4) . ' liter/M2',
                'Air per M2 (M3)' => number_format($kebutuhanAirPerM2M3, 6) . ' M3/M2',
            ],
        ];

        // ============ STEP 8: Kebutuhan Adukan per Pekerjaan ============
        // Kebutuhan semen per pekerjaan (kemasan) = Kebutuhan semen per M2 (kemasan / M2) * luas bidang
        $kebutuhanSemenKemasanPekerjaan = $kebutuhanSemenPerM2Kemasan * $luasBidang;
        $kebutuhanSemenKgPekerjaan = $kebutuhanSemenPerM2Kg * $luasBidang;
        $kebutuhanSemenM3Pekerjaan = $kebutuhanSemenPerM2M3 * $luasBidang;

        $kebutuhanPasirM3Pekerjaan = $kebutuhanPasirPerM2M3 * $luasBidang;
        $kebutuhanPasirSakPekerjaan = $kebutuhanPasirPerM2Sak * $luasBidang;

        $kebutuhanAirLiterPekerjaan = $kebutuhanAirPerM2Liter * $luasBidang;
        $kebutuhanAirM3Pekerjaan = $kebutuhanAirPerM2M3 * $luasBidang;

        $volumeAdukanPekerjaan = $kebutuhanSemenM3Pekerjaan + $kebutuhanPasirM3Pekerjaan + $kebutuhanAirM3Pekerjaan;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Kebutuhan Adukan per Pekerjaan',
            'info' => 'Total Luas: ' . number_format($luasBidang, 4) . ' M2',
            'calculations' => [
                'Semen (kemasan)' => number_format($kebutuhanSemenKemasanPekerjaan, 4) . ' kemasan',
                'Semen (kg)' => number_format($kebutuhanSemenKgPekerjaan, 4) . ' kg',
                'Semen (M3)' => number_format($kebutuhanSemenM3Pekerjaan, 6) . ' M3',
                'Pasir (M3)' => number_format($kebutuhanPasirM3Pekerjaan, 6) . ' M3',
                'Pasir (sak)' => number_format($kebutuhanPasirSakPekerjaan, 4) . ' sak',
                'Air (liter)' => number_format($kebutuhanAirLiterPekerjaan, 2) . ' liter',
                'Air (M3)' => number_format($kebutuhanAirM3Pekerjaan, 6) . ' M3',
                'Volume Adukan Total' => number_format($volumeAdukanPekerjaan, 6) . ' M3',
            ],
        ];

        // ==================== BAGIAN 2: MENGHITUNG KEBUTUHAN KERAMIK ====================

        // ============ STEP 9: Jumlah Keramik per Baris dan Kolom ============
        // jumlah keramik utuh per baris pekerjaan = Panjang bidang / ((panjang dimensi keramik + (tebal nat / 10)) / 100)
        $jumlahKeramikPerBaris = ceil($panjangBidang / (($panjangKeramik + ($tebalNat / 10)) / 100));

        // jumlah keramik utuh per kolom pekerjaan = Lebar bidang / ((lebar dimensi keramik + (tebal nat / 10)) / 100)
        $jumlahKeramikPerKolom = ceil($lebarBidang / (($lebarKeramik + ($tebalNat / 10)) / 100));

        // Total keramik utuh = jumlah keramik utuh per baris pekerjaan * jumlah keramik utuh per kolom pekerjaan
        $totalKeramikUtuh = $jumlahKeramikPerBaris * $jumlahKeramikPerKolom;

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Jumlah Keramik yang Dibutuhkan',
            'formula' => 'ceil(Panjang / ((Panjang Keramik + Tebal Nat/10) / 100)) × ceil(Lebar / ((Lebar Keramik + Tebal Nat/10) / 100))',
            'calculations' => [
                'Dimensi Keramik + Nat' => '(' . $panjangKeramik . ' + ' . ($tebalNat / 10) . ') cm × (' . $lebarKeramik . ' + ' . ($tebalNat / 10) . ') cm',
                'Jumlah Keramik per Baris' => number_format($jumlahKeramikPerBaris, 0) . ' pcs',
                'Jumlah Keramik per Kolom' => number_format($jumlahKeramikPerKolom, 0) . ' pcs',
                'Total Keramik Utuh' => number_format($totalKeramikUtuh, 0) . ' pcs',
            ],
        ];

        // ============ STEP 10: Kebutuhan Dus Keramik ============
        // Kebutuhan keramik utuh per pekerjaan = Total keramik utuh / isi keramik per kemasan
        $kebutuhanDusUtuhPekerjaan = ceil($totalKeramikUtuh / $isiPerDus);

        // Kebutuhan keramik dus per m2 = kebutuhan keramik dus per pekerjaan / Luas bidang
        $kebutuhanDusPerM2 = $kebutuhanDusUtuhPekerjaan / $luasBidang;

        // Kebutuhan keramik per M2 = Kebutuhan keramik dus per M2 / Banyak keramik per dus
        $kebutuhanKeramikPerM2 = $totalKeramikUtuh / $luasBidang;

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Kebutuhan Dus Keramik',
            'formula' => 'ceil(Total Keramik Utuh / Isi per Dus)',
            'calculations' => [
                'Isi per Dus' => $isiPerDus . ' pcs',
                'Kebutuhan Dus per Pekerjaan' => number_format($kebutuhanDusUtuhPekerjaan, 0) . ' dus',
                'Kebutuhan Dus per M2' => number_format($kebutuhanDusPerM2, 4) . ' dus/M2',
                'Kebutuhan Keramik per M2' => number_format($kebutuhanKeramikPerM2, 4) . ' pcs/M2',
            ],
        ];

        // ==================== BAGIAN 3: MENGHITUNG KEBUTUHAN NAT ====================

        // ============ STEP 11: Jumlah Kolom dan Baris Nat ============
        // jumlah kolom nat per pekerjaan = (Panjang bidang / ((Panjang keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahKolomNat = ceil($panjangBidang / (($panjangKeramik + ($tebalNat / 10)) / 100)) + 1;

        // jumlah baris nat per pekerjaan = (Lebar bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = ceil($lebarBidang / (($lebarKeramik + ($tebalNat / 10)) / 100)) + 1;

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' => 'ceil(Bidang / ((Dimensi Keramik + Tebal Nat/10) / 100)) + 1',
            'calculations' => [
                'Jumlah Kolom Nat' => number_format($jumlahKolomNat, 0) . ' garis',
                'Jumlah Baris Nat' => number_format($jumlahBarisNat, 0) . ' garis',
            ],
        ];

        // ============ STEP 12: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat * lebar bidang) + (jumlah baris nat * Panjang bidang)
        $panjangBentanganNat = ($jumlahKolomNat * $lebarBidang) + ($jumlahBarisNat * $panjangBidang);

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Panjang Bentangan Nat',
            'formula' => '(Jumlah Kolom Nat × Lebar Bidang) + (Jumlah Baris Nat × Panjang Bidang)',
            'calculations' => [
                'Perhitungan' => '(' . $jumlahKolomNat . ' × ' . $lebarBidang . ') + (' . $jumlahBarisNat . ' × ' . $panjangBidang . ')',
                'Hasil' => number_format($panjangBentanganNat, 4) . ' m',
            ],
        ];

        // ============ STEP 13: Volume Nat per Pekerjaan ============
        // Volume nat per pekerjaan = Panjang bentangan nat * tebal nat * tebal keramik / 1000000
        $volumeNatPekerjaan = ($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000;

        $trace['steps'][] = [
            'step' => 13,
            'title' => 'Volume Nat per Pekerjaan',
            'formula' => 'Panjang Bentangan × Tebal Nat × Tebal Keramik / 1000000',
            'calculations' => [
                'Panjang Bentangan' => number_format($panjangBentanganNat, 4) . ' m',
                'Tebal Nat' => $tebalNat . ' mm',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Volume Nat' => number_format($volumeNatPekerjaan, 6) . ' M3',
            ],
        ];

        // ============ STEP 14: Kebutuhan Bungkus Nat ============
        // jumlah kebutuhan bungkus kemasan nat per pekerjaan = volume nat per pekerjaan / Volume pasta nat per bungkus
        $kebutuhanBungkusNat = $volumeNatPekerjaan / $volumePastaNatPerBungkus;

        // jumlah kebutuhan kg nat per pekerjaan = setara dengan jumlah kebutuhan bungkus kemasan nat per pekerjaan
        $kebutuhanKgNat = $kebutuhanBungkusNat * $beratKemasanNat;

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Kebutuhan Bungkus Nat',
            'formula' => 'Volume Nat / Volume Pasta Nat per Bungkus',
            'calculations' => [
                'Volume Pasta Nat per Bungkus' => number_format($volumePastaNatPerBungkus, 6) . ' M3',
                'Kebutuhan Bungkus' => number_format($kebutuhanBungkusNat, 4) . ' bungkus',
                'Kebutuhan (Kg)' => number_format($kebutuhanKgNat, 4) . ' kg',
            ],
        ];

        // ============ STEP 15: Volume Adukan Nat ============
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
            'step' => 15,
            'title' => 'Volume Adukan Nat',
            'info' => 'Ratio 1 : 33% (Nat : Air)',
            'calculations' => [
                'Kubik Nat per Bungkus' => number_format($kubikNatPerBungkus, 6) . ' M3 (= (1/1440) × ' . $beratKemasanNat . ' kg)',
                'Kubik Air per Ratio' => number_format($kubikAirNatPerBungkus, 6) . ' M3 (= 33% × kubik nat)',
                'Liter Air per Ratio' => number_format($literAirNatPerBungkus, 4) . ' liter',
                'Volume Adukan per Bungkus' => number_format($volumeAdukanNatPerBungkus, 6) . ' M3',
                '---' => '---',
                'Total Kubik Nat Pekerjaan' => number_format($kubikNatPekerjaan, 6) . ' M3',
                'Total Kubik Air Pekerjaan' => number_format($kubikAirNatPekerjaan, 6) . ' M3',
                'Total Liter Air Pekerjaan' => number_format($literAirNatPekerjaan, 4) . ' liter',
                'Total Volume Adukan Nat' => number_format($volumeAdukanNatPekerjaan, 6) . ' M3',
            ],
        ];

        // ============ STEP 16: Harga ============
        $cementPrice = $cement->package_price ?? 0;
        $sandPricePerM3 = $sand->comparison_price_per_m3 ?? 0;
        if ($sandPricePerM3 == 0 && isset($sand->package_price) && isset($sand->package_volume) && $sand->package_volume > 0) {
            $sandPricePerM3 = $sand->package_price / $sand->package_volume;
        }
        $ceramicPricePerDus = $ceramic->price_per_package ?? 0;

        // Harga komparasi semen per pekerjaan = Kebutuhan semen per pekerjaan (kemasan) * Harga semen perkemasan
        $totalCementPrice = $kebutuhanSemenKemasanPekerjaan * $cementPrice;
        $totalSandPrice = $kebutuhanPasirM3Pekerjaan * $sandPricePerM3;
        $totalCeramicPrice = $kebutuhanDusUtuhPekerjaan * $ceramicPricePerDus;
        $totalGroutPrice = $kebutuhanBungkusNat * $hargaNatPerBungkus;
        $grandTotal = $totalCementPrice + $totalSandPrice + $totalCeramicPrice + $totalGroutPrice;

        $trace['steps'][] = [
            'step' => 16,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Semen per Kemasan' => 'Rp ' . number_format($cementPrice, 0, ',', '.'),
                'Total Harga Semen' => 'Rp ' . number_format($totalCementPrice, 0, ',', '.'),
                'Harga Pasir per M3' => 'Rp ' . number_format($sandPricePerM3, 0, ',', '.'),
                'Total Harga Pasir' => 'Rp ' . number_format($totalSandPrice, 0, ',', '.'),
                'Harga Keramik per Dus' => 'Rp ' . number_format($ceramicPricePerDus, 0, ',', '.'),
                'Total Harga Keramik' => 'Rp ' . number_format($totalCeramicPrice, 0, ',', '.'),
                'Harga Nat per Bungkus' => 'Rp ' . number_format($hargaNatPerBungkus, 0, ',', '.'),
                'Total Harga Nat' => 'Rp ' . number_format($totalGroutPrice, 0, ',', '.'),
                'Grand Total' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
            ],
        ];

        // ============ Final Result ============
        $trace['final_result'] = [
            // Keramik
            'total_tiles' => $totalKeramikUtuh,
            'tiles_per_package' => $isiPerDus,
            'tiles_packages' => $kebutuhanDusUtuhPekerjaan,
            'tiles_per_m2' => $kebutuhanKeramikPerM2,
            'tiles_packages_per_m2' => $kebutuhanDusPerM2,

            // Adukan Semen
            'cement_sak' => $kebutuhanSemenKemasanPekerjaan,
            'cement_kg' => $kebutuhanSemenKgPekerjaan,
            'cement_m3' => $kebutuhanSemenM3Pekerjaan,
            'sand_m3' => $kebutuhanPasirM3Pekerjaan,
            'sand_sak' => $kebutuhanPasirSakPekerjaan,
            'water_cement_liters' => $kebutuhanAirLiterPekerjaan,
            'water_cement_m3' => $kebutuhanAirM3Pekerjaan,
            'mortar_volume_m3' => $volumeAdukanPekerjaan,

            // Nat (Grout)
            'grout_packages' => $kebutuhanBungkusNat,
            'grout_kg' => $kebutuhanKgNat,
            'grout_m3' => $kubikNatPekerjaan,
            'water_grout_liters' => $literAirNatPekerjaan,
            'water_grout_m3' => $kubikAirNatPekerjaan,
            'grout_mortar_volume_m3' => $volumeAdukanNatPekerjaan,

            // Total Air
            'total_water_liters' => $kebutuhanAirLiterPekerjaan + $literAirNatPekerjaan,
            'total_water_m3' => $kebutuhanAirM3Pekerjaan + $kubikAirNatPekerjaan,

            // Prices
            'cement_price_per_sak' => $cementPrice,
            'total_cement_price' => $totalCementPrice,
            'sand_price_per_m3' => $sandPricePerM3,
            'total_sand_price' => $totalSandPrice,
            'ceramic_price_per_package' => $ceramicPricePerDus,
            'total_ceramic_price' => $totalCeramicPrice,
            'grout_price_per_package' => $hargaNatPerBungkus,
            'total_grout_price' => $totalGroutPrice,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $luasBidang,
        ];

        return $trace;
    }
}
