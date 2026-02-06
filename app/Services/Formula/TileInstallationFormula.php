<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Cement;
use App\Models\Sand;
use App\Models\Ceramic;
use App\Models\Nat;

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

    public static function getMaterialRequirements(): array
    {
        return ['cement', 'sand', 'ceramic', 'nat'];
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
        $n = static fn ($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjangBidang = $n($params['wall_length']); // m
        $lebarBidang = $n($params['wall_height']); // m
        $tebalAdukan = $n($params['mortar_thickness']); // cm
        $tebalNat = $n($params['grout_thickness']); // mm

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
        $nat = isset($params['nat_id']) ? Nat::find($params['nat_id']) : null;

        $cement = $cement ?: Cement::query()->first();
        $sand = $sand ?: Sand::first();
        $ceramic = $ceramic ?: Ceramic::first();
        $nat = $nat ?: Nat::query()->orderBy('id')->first();

        if (!$cement || !$sand || !$ceramic) {
            throw new \RuntimeException('Data material (semen/pasir/keramik) tidak tersedia di database.');
        }

        if (!$nat) {
            throw new \RuntimeException(
                'Data material nat tidak tersedia di database. Pastikan ada data di tabel nats.',
            );
        }

        $kemasanSemen = $n($cement->package_weight_net > 0 ? $cement->package_weight_net : 50); // kg
        $densitySemen = 1440; // kg/M3
        $densityNat = 1440; // kg/M3

        $panjangKeramik = $n($ceramic->dimension_length); // cm
        $lebarKeramik = $n($ceramic->dimension_width); // cm
        $tebalKeramikCm = $n($ceramic->dimension_thickness); // cm
        $tebalKeramikMm = $n($tebalKeramikCm * 10); // mm
        $isiPerDus = (int) $ceramic->pieces_per_package;

        if ($panjangKeramik <= 0 || $lebarKeramik <= 0 || $isiPerDus <= 0) {
            throw new \RuntimeException('Data keramik tidak lengkap (dimensi/isi per dus).');
        }

        // Grout parameters from database
        $beratKemasanNat = $n($nat->package_weight_net > 0 ? $nat->package_weight_net : 5); // kg per bungkus
        $volumePastaNatPerBungkus = $n($nat->package_volume > 0 ? $nat->package_volume : 0.00069444); // M3 per bungkus
        $hargaNatPerBungkus = $n($nat->package_price ?? 0, 0);

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material',
            'calculations' => [
                'Semen' => $cement->brand . ' (' . $kemasanSemen . ' kg)',
                'Pasir' => $sand->brand,
                'Keramik' => $ceramic->brand . ' (' . $panjangKeramik . 'x' . $lebarKeramik . ' cm)',
                'Isi per Dus Keramik' => $isiPerDus . ' pcs',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Nat' => $nat->brand . ' (' . $beratKemasanNat . ' kg)',
                'Berat Kemasan Nat' => $beratKemasanNat . ' kg',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Densitas Nat' => $densityNat . ' kg/M3',
                'Rasio Adukan Semen' => '1 : 3 : 30% (Semen : Pasir : Air)',
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

        // ==================== BAGIAN 1: MENGHITUNG KEBUTUHAN ADUKAN ====================

        // ============ STEP 4: Kubik per Kemasan (Semen, Pasir, Air) ============
        // kubik perkemasan semen = kemasan semen * (1 / 1440)
        $kubikPerKemasanSemen = $n($kemasanSemen * (1 / $densitySemen));

        // Kubik per kemasan pasir = 3 * kubik per kemasan semen
        $kubikPerKemasanPasir = $n(3 * $kubikPerKemasanSemen);

        // kubik per kemasan air = (Kubik per kemasan semen + Kubik per kemasan pasir) * 30%
        $kubikPerKemasanAir = $n(($kubikPerKemasanSemen + $kubikPerKemasanPasir) * 0.3);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Kubik per Kemasan (Semen, Pasir, Air)',
            'info' => 'Ratio 1 : 3 : 30% (Semen : Pasir : Air)',
            'calculations' => [
                'Kubik per Kemasan Semen' =>
                    NumberHelper::format($kubikPerKemasanSemen) . ' M3 (= ' . $kemasanSemen . ' × (1/1440))',
                'Kubik per Kemasan Pasir' => NumberHelper::format($kubikPerKemasanPasir) . ' M3 (= 3 × kubik semen)',
                'Kubik per Kemasan Air' => NumberHelper::format($kubikPerKemasanAir) . ' M3 (= 30% × (semen + pasir))',
            ],
        ];

        // ============ STEP 5: Volume Adukan per Kemasan Semen ============
        // Volume adukan per kemasan semen = Kubik perkemasan semen + pasir + air
        $volumeAdukanPerKemasan = $n($kubikPerKemasanSemen + $kubikPerKemasanPasir + $kubikPerKemasanAir);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Volume Adukan per Kemasan Semen',
            'formula' => 'Kubik Semen + Kubik Pasir + Kubik Air',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' + ' .
                    NumberHelper::format($kubikPerKemasanPasir) .
                    ' + ' .
                    NumberHelper::format($kubikPerKemasanAir),
                'Hasil' => NumberHelper::format($volumeAdukanPerKemasan) . ' M3',
            ],
        ];

        // ============ STEP 6: Luas Screedan per Kemasan Semen ============
        // Luas screedan per kemasan semen = Volume adukan per kemasan semen / (tebal adukan / 100)
        $tebalAdukanMeter = $n($tebalAdukan / 100);
        $luasScreedanPerKemasan = $n($volumeAdukanPerKemasan / $tebalAdukanMeter);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Luas Screedan per Kemasan Semen',
            'formula' => 'Volume adukan per kemasan / (tebal adukan / 100)',
            'info' => 'Berapa M2 yang bisa di-screed dengan 1 kemasan semen',
            'calculations' => [
                'Tebal Adukan (meter)' => NumberHelper::format($tebalAdukanMeter) . ' m',
                'Perhitungan' =>
                    NumberHelper::format($volumeAdukanPerKemasan) . ' / ' . NumberHelper::format($tebalAdukanMeter),
                'Hasil' => NumberHelper::format($luasScreedanPerKemasan) . ' M2',
            ],
        ];

        // ============ STEP 7: Kebutuhan per M2 ============
        // Kebutuhan semen per M2 (kemasan / M2) = 1 kemasan semen / Luas screedan per kemasan semen
        $kebutuhanSemenPerM2Kemasan = $n(1 / $luasScreedanPerKemasan);
        $kebutuhanSemenPerM2Kg = $n($kemasanSemen / $luasScreedanPerKemasan);
        $kebutuhanSemenPerM2M3 = $n($kubikPerKemasanSemen / $luasScreedanPerKemasan);

        // Kebutuhan pasir per M2 = Kubik pasir per kemasan / Luas screedan per kemasan
        $kebutuhanPasirPerM2M3 = $n($kubikPerKemasanPasir / $luasScreedanPerKemasan);
        $kebutuhanPasirPerM2Sak = $n(3 / $luasScreedanPerKemasan);

        // Kebutuhan air per M2
        $kebutuhanAirPerM2Liter = $n(($kubikPerKemasanAir * 1000) / $luasScreedanPerKemasan);
        $kebutuhanAirPerM2M3 = $n($kubikPerKemasanAir / $luasScreedanPerKemasan);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Kebutuhan Adukan per M2',
            'calculations' => [
                'Semen per M2 (kemasan)' => NumberHelper::format($kebutuhanSemenPerM2Kemasan) . ' kemasan/M2',
                'Semen per M2 (kg)' => NumberHelper::format($kebutuhanSemenPerM2Kg) . ' kg/M2',
                'Semen per M2 (M3)' => NumberHelper::format($kebutuhanSemenPerM2M3) . ' M3/M2',
                'Pasir per M2 (M3)' => NumberHelper::format($kebutuhanPasirPerM2M3) . ' M3/M2',
                'Pasir per M2 (sak)' => NumberHelper::format($kebutuhanPasirPerM2Sak) . ' sak/M2',
                'Air per M2 (liter)' => NumberHelper::format($kebutuhanAirPerM2Liter) . ' liter/M2',
                'Air per M2 (M3)' => NumberHelper::format($kebutuhanAirPerM2M3) . ' M3/M2',
            ],
        ];

        // ============ STEP 8: Kebutuhan Adukan per Pekerjaan ============
        // Kebutuhan semen per pekerjaan (kemasan) = Kebutuhan semen per M2 (kemasan / M2) * luas bidang
        $kebutuhanSemenKemasanPekerjaan = $n($kebutuhanSemenPerM2Kemasan * $luasBidang);
        $kebutuhanSemenKgPekerjaan = $n($kebutuhanSemenPerM2Kg * $luasBidang);
        $kebutuhanSemenM3Pekerjaan = $n($kebutuhanSemenPerM2M3 * $luasBidang);

        $kebutuhanPasirM3Pekerjaan = $n($kebutuhanPasirPerM2M3 * $luasBidang);
        $kebutuhanPasirSakPekerjaan = $n($kebutuhanPasirPerM2Sak * $luasBidang);

        $kebutuhanAirM3Pekerjaan = $n($kebutuhanAirPerM2M3 * $luasBidang);
        $kebutuhanAirLiterPekerjaan = $kebutuhanAirM3Pekerjaan * 1000;

        $volumeAdukanPekerjaan = $n($kebutuhanSemenM3Pekerjaan + $kebutuhanPasirM3Pekerjaan + $kebutuhanAirM3Pekerjaan);

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Kebutuhan Adukan per Pekerjaan',
            'info' => 'Total Luas: ' . NumberHelper::format($luasBidang) . ' M2',
            'calculations' => [
                'Semen (kemasan)' => NumberHelper::format($kebutuhanSemenKemasanPekerjaan) . ' kemasan',
                'Semen (kg)' => NumberHelper::format($kebutuhanSemenKgPekerjaan) . ' kg',
                'Semen (M3)' => NumberHelper::format($kebutuhanSemenM3Pekerjaan) . ' M3',
                'Pasir (M3)' => NumberHelper::format($kebutuhanPasirM3Pekerjaan) . ' M3',
                'Pasir (sak)' => NumberHelper::format($kebutuhanPasirSakPekerjaan) . ' sak',
                'Air (liter)' => NumberHelper::format($kebutuhanAirLiterPekerjaan) . ' liter',
                'Air (M3)' => NumberHelper::format($kebutuhanAirM3Pekerjaan) . ' M3',
                'Volume Adukan Total' => NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            ],
        ];

        // ==================== BAGIAN 2: MENGHITUNG KEBUTUHAN KERAMIK ====================

        // ============ STEP 9: Jumlah Keramik per Baris dan Kolom ============
        // jumlah keramik utuh per baris pekerjaan = Panjang bidang / ((panjang dimensi keramik + (tebal nat / 10)) / 100)
        $jumlahKeramikPerBaris = (int) ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100));

        // jumlah keramik utuh per kolom pekerjaan = Lebar bidang / ((lebar dimensi keramik + (tebal nat / 10)) / 100)
        $jumlahKeramikPerKolom = (int) ceil($lebarBidang / (($lebarKeramik + $tebalNat / 10) / 100));

        // Total keramik utuh = jumlah keramik utuh per baris pekerjaan * jumlah keramik utuh per kolom pekerjaan
        $totalKeramikUtuh = $jumlahKeramikPerBaris * $jumlahKeramikPerKolom;

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Jumlah Keramik yang Dibutuhkan',
            'formula' =>
                'ceil(Panjang / ((Panjang Keramik + Tebal Nat/10) / 100)) × ceil(Lebar / ((Lebar Keramik + Tebal Nat/10) / 100))',
            'calculations' => [
                'Dimensi Keramik + Nat' =>
                    '(' .
                    $panjangKeramik .
                    ' + ' .
                    $tebalNat / 10 .
                    ') cm × (' .
                    $lebarKeramik .
                    ' + ' .
                    $tebalNat / 10 .
                    ') cm',
                'Jumlah Keramik per Baris' => NumberHelper::format($jumlahKeramikPerBaris) . ' pcs',
                'Jumlah Keramik per Kolom' => NumberHelper::format($jumlahKeramikPerKolom) . ' pcs',
                'Total Keramik Utuh' => NumberHelper::format($totalKeramikUtuh) . ' pcs',
            ],
        ];

        // ============ STEP 10: Kebutuhan Dus Keramik ============
        // Kebutuhan keramik utuh per pekerjaan = Total keramik utuh / isi keramik per kemasan
        $kebutuhanDusUtuhPekerjaan = $n($totalKeramikUtuh / $isiPerDus);

        // Kebutuhan keramik dus per m2 = kebutuhan keramik dus per pekerjaan / Luas bidang
        $kebutuhanDusPerM2 = $n($kebutuhanDusUtuhPekerjaan / $luasBidang);

        // Kebutuhan keramik per M2 = Kebutuhan keramik dus per M2 / Banyak keramik per dus
        $kebutuhanKeramikPerM2 = $n($totalKeramikUtuh / $luasBidang);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Kebutuhan Dus Keramik',
            'formula' => 'ceil(Total Keramik Utuh / Isi per Dus)',
            'calculations' => [
                'Isi per Dus' => $isiPerDus . ' pcs',
                'Kebutuhan Dus per Pekerjaan' => NumberHelper::format($kebutuhanDusUtuhPekerjaan) . ' dus',
                'Kebutuhan Dus per M2' => NumberHelper::format($kebutuhanDusPerM2) . ' dus/M2',
                'Kebutuhan Keramik per M2' => NumberHelper::format($kebutuhanKeramikPerM2) . ' pcs/M2',
            ],
        ];

        // ==================== BAGIAN 3: MENGHITUNG KEBUTUHAN NAT ====================

        // ============ STEP 11: Jumlah Kolom dan Baris Nat ============
        // jumlah kolom nat per pekerjaan = (Panjang bidang / ((Panjang keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahKolomNat = (int) ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100)) + 1;

        // jumlah baris nat per pekerjaan = (Lebar bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = (int) ceil($lebarBidang / (($lebarKeramik + $tebalNat / 10) / 100)) + 1;

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' => 'ceil(Bidang / ((Dimensi Keramik + Tebal Nat/10) / 100)) + 1',
            'calculations' => [
                'Jumlah Kolom Nat' => NumberHelper::format($jumlahKolomNat) . ' garis',
                'Jumlah Baris Nat' => NumberHelper::format($jumlahBarisNat) . ' garis',
            ],
        ];

        // ============ STEP 12: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat * lebar bidang) + (jumlah baris nat * Panjang bidang)
        $panjangBentanganNat = $n($jumlahKolomNat * $lebarBidang + $jumlahBarisNat * $panjangBidang);

        $trace['steps'][] = [
            'step' => 12,
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

        // ============ STEP 13: Volume Nat per Pekerjaan ============
        // Volume nat per pekerjaan = Panjang bentangan nat * tebal nat * tebal keramik / 1000000
        $volumeNatPekerjaan = $n(($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000);

        $trace['steps'][] = [
            'step' => 13,
            'title' => 'Volume Nat per Pekerjaan',
            'formula' => 'Panjang Bentangan × Tebal Nat × Tebal Keramik / 1000000',
            'calculations' => [
                'Panjang Bentangan' => NumberHelper::format($panjangBentanganNat) . ' m',
                'Tebal Nat' => $tebalNat . ' mm',
                'Tebal Keramik' => $tebalKeramikMm . ' mm',
                'Volume Nat' => NumberHelper::format($volumeNatPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 14: Kebutuhan Bungkus Nat ============
        // jumlah kebutuhan bungkus kemasan nat per pekerjaan = volume nat per pekerjaan / Volume pasta nat per bungkus
        $kebutuhanBungkusNat = $n($volumeNatPekerjaan / $volumePastaNatPerBungkus);

        // jumlah kebutuhan kg nat per pekerjaan = setara dengan jumlah kebutuhan bungkus kemasan nat per pekerjaan
        $kebutuhanKgNat = $n($kebutuhanBungkusNat * $beratKemasanNat);

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Kebutuhan Bungkus Nat',
            'formula' => 'Volume Nat / Volume Pasta Nat per Bungkus',
            'calculations' => [
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Kebutuhan Bungkus' => NumberHelper::format($kebutuhanBungkusNat) . ' bungkus',
                'Kebutuhan (Kg)' => NumberHelper::format($kebutuhanKgNat) . ' kg',
            ],
        ];

        // ============ STEP 15: Volume Adukan Nat ============
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
            'step' => 15,
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

        // ============ STEP 16: Harga ============
        $cementPrice = $n($cement->package_price ?? 0, 0);
        $sandPricePerM3 = $n($sand->comparison_price_per_m3 ?? 0, 0);
        if (
            $sandPricePerM3 == 0 &&
            isset($sand->package_price) &&
            isset($sand->package_volume) &&
            $sand->package_volume > 0
        ) {
            $sandPricePerM3 = $n($sand->package_price / $sand->package_volume, 0);
        }
        $ceramicPricePerDus = $n($ceramic->price_per_package ?? 0, 0);

        // Harga komparasi semen per pekerjaan = Kebutuhan semen per pekerjaan (kemasan) * Harga semen perkemasan
        $totalCementPrice = $n($kebutuhanSemenKemasanPekerjaan * $cementPrice, 0);
        $totalSandPrice = $n($kebutuhanPasirM3Pekerjaan * $sandPricePerM3, 0);
        $totalCeramicPrice = $n($kebutuhanDusUtuhPekerjaan * $ceramicPricePerDus, 0);
        $totalGroutPrice = $n($kebutuhanBungkusNat * $hargaNatPerBungkus, 0);
        $grandTotal = $n($totalCementPrice + $totalSandPrice + $totalCeramicPrice + $totalGroutPrice, 0);

        $trace['steps'][] = [
            'step' => 16,
            'title' => 'Perhitungan Harga',
            'calculations' => [
                'Harga Semen per Kemasan' => NumberHelper::currency($cementPrice),
                'Total Harga Semen' => NumberHelper::currency($totalCementPrice),
                'Harga Pasir per M3' => NumberHelper::currency($sandPricePerM3),
                'Total Harga Pasir' => NumberHelper::currency($totalSandPrice),
                'Harga Keramik per Dus' => NumberHelper::currency($ceramicPricePerDus),
                'Total Harga Keramik' => NumberHelper::currency($totalCeramicPrice),
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Total Harga Nat' => NumberHelper::currency($totalGroutPrice),
                'Grand Total' => NumberHelper::currency($grandTotal),
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
            'total_water_liters' => $n($kebutuhanAirLiterPekerjaan + $literAirNatPekerjaan),
            'total_water_m3' => $n($kebutuhanAirM3Pekerjaan + $kubikAirNatPekerjaan),

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
