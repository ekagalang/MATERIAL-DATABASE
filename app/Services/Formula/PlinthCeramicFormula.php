<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;

/**
 * Formula - Perhitungan Pasang Plint Keramik
 * Menghitung kebutuhan material untuk pemasangan plint keramik (baseboard)
 */
class PlinthCeramicFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'plinth_ceramic';
    }

    public static function getName(): string
    {
        return 'Pasang Keramik Plint Komplit';
    }

    public static function getDescription(): string
    {
        return 'Menghitung kebutuhan keramik, adukan semen, pasir, dan nat untuk pemasangan plint keramik.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['cement', 'sand', 'ceramic', 'nat'];
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'mortar_thickness', 'grout_thickness'];

        foreach ($required as $field) {
            if (! isset($params[$field]) || ! is_numeric($params[$field]) || (float) $params[$field] <= 0) {
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
        $tinggiBidangCm = $n($params['wall_height']); // cm (untuk plint, tinggi biasanya dalam cm)
        $tebalAdukan = $n($params['mortar_thickness']); // cm
        $tebalNat = $n($params['grout_thickness']); // mm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Bidang' => $panjangBidang.' m',
                'Tinggi Bidang' => $tinggiBidangCm.' cm',
                'Tebal Adukan' => $tebalAdukan.' cm',
                'Tebal Nat' => $tebalNat.' mm',
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

        if (! $cement || ! $sand || ! $ceramic) {
            throw new \RuntimeException('Data material (semen/pasir/keramik) tidak tersedia di database.');
        }

        if (! $nat) {
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
                'Semen' => $cement->brand.' ('.$kemasanSemen.' kg)',
                'Pasir' => $sand->brand,
                'Keramik' => $ceramic->brand.' ('.$panjangKeramik.'x'.$lebarKeramik.' cm)',
                'Isi per Dus Keramik' => $isiPerDus.' pcs',
                'Tebal Keramik' => $tebalKeramikMm.' mm',
                'Nat' => $nat->brand.' ('.$beratKemasanNat.' kg)',
                'Berat Kemasan Nat' => $beratKemasanNat.' kg',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus).' M3',
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Densitas Semen' => $densitySemen.' kg/M3',
                'Densitas Nat' => $densityNat.' kg/M3',
                'Rasio Adukan Semen' => '1 : 3 : 30% (Semen : Pasir : Air)',
                'Rasio Adukan Nat' => '1 : 33% (Nat : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        // Luas Bidang = Panjang (M) × Tinggi (cm) -> konversi tinggi ke meter
        $tinggiBidangM = $n($tinggiBidangCm / 100);
        $luasBidang = $n($panjangBidang * $tinggiBidangM);

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang',
            'formula' => 'Panjang × (Tinggi / 100)',
            'calculations' => [
                'Tinggi (meter)' => NumberHelper::format($tinggiBidangM).' m',
                'Perhitungan' => "$panjangBidang × $tinggiBidangM",
                'Hasil' => NumberHelper::format($luasBidang).' M2',
            ],
        ];

        // ==================== BAGIAN 1: MENGHITUNG KEBUTUHAN KERAMIK ====================

        // ============ STEP 4: Total Keramik Utuh per Panjang ============
        // Total keramik utuh per Panjang = Panjang bidang / ((Panjang keramik + (ketebalan nat / 10)) / 100)
        $panjangKeramikDenganNat = $n(($panjangKeramik + $tebalNat / 10) / 100);
        $totalKeramikUtuhPerPanjang = ceil($panjangBidang / $panjangKeramikDenganNat);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Total Keramik Utuh per Panjang',
            'formula' => 'ceil(Panjang bidang / ((Panjang keramik + (ketebalan nat / 10)) / 100))',
            'info' => 'Jumlah keramik yang dibutuhkan sepanjang bidang',
            'calculations' => [
                'Panjang Keramik + Nat' => NumberHelper::format($panjangKeramikDenganNat * 100).' cm (= '.$panjangKeramik.' + '.$tebalNat / 10 .')',
                'Perhitungan' => "$panjangBidang / ".NumberHelper::format($panjangKeramikDenganNat),
                'Hasil' => NumberHelper::format($totalKeramikUtuhPerPanjang).' pcs',
            ],
        ];

        // ============ STEP 5: Total Lembar Keramik Utuh per Panjang ============
        // Total lembar keramik utuh per panjang = Total keramik utuh per Panjang / 2
        $totalLembarKeramikUtuh = $n($totalKeramikUtuhPerPanjang / 2);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Total Lembar Keramik Utuh per Panjang',
            'formula' => 'Total keramik utuh per Panjang / 2',
            'info' => 'Pembagian untuk pola pemasangan plint',
            'calculations' => [
                'Perhitungan' => "$totalKeramikUtuhPerPanjang / 2",
                'Hasil' => NumberHelper::format($totalLembarKeramikUtuh).' pcs',
            ],
        ];

        // ============ STEP 6: Kebutuhan Dus Keramik per Pekerjaan ============
        // Kebutuhan dus keramik per pekerjaan = Total lembar keramik utuh / isi keramik per kemasan
        $kebutuhanDusUtuhPekerjaan = $n($totalLembarKeramikUtuh / $isiPerDus);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Kebutuhan Dus Keramik per Pekerjaan',
            'formula' => 'Total lembar keramik utuh / isi keramik per kemasan',
            'calculations' => [
                'Isi per Dus' => $isiPerDus.' pcs',
                'Perhitungan' => NumberHelper::format($totalLembarKeramikUtuh).' / '.$isiPerDus,
                'Hasil' => NumberHelper::format($kebutuhanDusUtuhPekerjaan).' dus',
            ],
        ];

        // ============ STEP 7: Kebutuhan per M2 ============
        // Kebutuhan dus keramik per M2 = Kebutuhan dus keramik per pekerjaan / Luas bidang
        $kebutuhanDusPerM2 = $n($kebutuhanDusUtuhPekerjaan / $luasBidang);

        // Kebutuhan lembar keramik per M2 = Kebutuhan dus keramik per M2 * isi keramik per kemasan
        $kebutuhanKeramikPerM2 = $n($kebutuhanDusPerM2 * $isiPerDus);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Kebutuhan Keramik per M2',
            'calculations' => [
                'Kebutuhan Dus per M2' => NumberHelper::format($kebutuhanDusPerM2).' dus/M2',
                'Kebutuhan Lembar per M2' => NumberHelper::format($kebutuhanKeramikPerM2).' pcs/M2',
            ],
        ];

        // ==================== BAGIAN 2: MENGHITUNG KEBUTUHAN NAT ====================

        // ============ STEP 8: Jumlah Kolom dan Baris Nat ============
        // Jumlah kolom nat per pekerjaan = (Panjang bidang / ((Panjang keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahKolomNat = (int) ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100)) + 1;

        // Jumlah baris nat per pekerjaan = (tinggi bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = (int) ceil($tinggiBidangM / (($lebarKeramik + $tebalNat / 10) / 100)) + 1;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' => 'ceil(Bidang / ((Dimensi Keramik + Tebal Nat/10) / 100)) + 1',
            'calculations' => [
                'Jumlah Kolom Nat' => NumberHelper::format($jumlahKolomNat).' garis',
                'Jumlah Baris Nat' => NumberHelper::format($jumlahBarisNat).' garis',
            ],
        ];

        // ============ STEP 9: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat per pekerjaan * tinggi bidang) + (jumlah baris nat per pekerjaan * Panjang bidang)
        $panjangBentanganNat = $n($jumlahKolomNat * $tinggiBidangM + $jumlahBarisNat * $panjangBidang);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Panjang Bentangan Nat',
            'formula' => '(Jumlah Kolom Nat × Tinggi Bidang) + (Jumlah Baris Nat × Panjang Bidang)',
            'calculations' => [
                'Perhitungan' => '('.
                    $jumlahKolomNat.
                    ' × '.
                    NumberHelper::format($tinggiBidangM).
                    ') + ('.
                    $jumlahBarisNat.
                    ' × '.
                    $panjangBidang.
                    ')',
                'Hasil' => NumberHelper::format($panjangBentanganNat).' m',
            ],
        ];

        // ============ STEP 10: Volume Nat per Pekerjaan ============
        // Volume nat per pekerjaan = Panjang bentangan nat per pekerjaan * ketebalan nat * tebal keramik / 1000000
        $volumeNatPekerjaan = $n(($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Volume Nat per Pekerjaan',
            'formula' => 'Panjang Bentangan × Tebal Nat × Tebal Keramik / 1000000',
            'calculations' => [
                'Panjang Bentangan' => NumberHelper::format($panjangBentanganNat).' m',
                'Tebal Nat' => $tebalNat.' mm',
                'Tebal Keramik' => $tebalKeramikMm.' mm',
                'Volume Nat' => NumberHelper::format($volumeNatPekerjaan).' M3',
            ],
        ];

        // ============ STEP 11: Kebutuhan Kemasan Nat ============
        // Jumlah kemasan kebutuhan nat per pekerjaan = volume nat per pekerjaan / volume kubik nat per kemasan
        $kebutuhanBungkusNat = $n($volumeNatPekerjaan / $volumePastaNatPerBungkus);

        // Jumlah kg kebutuhan nat per pekerjaan = volume nat per pekerjaan / volume kubik nat per kemasan
        $kebutuhanKgNat = $n($kebutuhanBungkusNat * $beratKemasanNat);

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Kebutuhan Kemasan Nat',
            'formula' => 'Volume Nat / Volume Pasta Nat per Bungkus',
            'calculations' => [
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus).' M3',
                'Kebutuhan Bungkus' => NumberHelper::format($kebutuhanBungkusNat).' bungkus',
                'Kebutuhan (Kg)' => NumberHelper::format($kebutuhanKgNat).' kg',
            ],
        ];

        // ============ STEP 12: Volume Adukan Nat ============
        // Kubik nat per kemasan = berat per kemasan * (1 / 1440)
        $kubikNatPerBungkus = $n((1 / $densityNat) * $beratKemasanNat);

        // kubik air dari kemasan nat = ratio air (33%) * kubik nat per kemasan
        $kubikAirNatPerBungkus = $n($kubikNatPerBungkus * 0.33);

        // Liter Air per bungkus = Kubik air per bungkus * 1000
        $literAirNatPerBungkus = $n($kubikAirNatPerBungkus * 1000);

        // Volume adukan nat per bungkus
        $volumeAdukanNatPerBungkus = $n($kubikNatPerBungkus + $kubikAirNatPerBungkus);

        // Total untuk pekerjaan
        // Kebutuhan air per pekerjaan = kubik air dari kemasan nat * jumlah kemasan kebutuhan nat per pekerjaan
        $kubikNatPekerjaan = $n($kubikNatPerBungkus * $kebutuhanBungkusNat);
        $kubikAirNatPekerjaan = $n($kubikAirNatPerBungkus * $kebutuhanBungkusNat);
        $literAirNatPekerjaan = $kubikAirNatPekerjaan * 1000;
        $volumeAdukanNatPekerjaan = $n($volumeAdukanNatPerBungkus * $kebutuhanBungkusNat);

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Volume Adukan Nat',
            'info' => 'Ratio 1 : 33% (Nat : Air)',
            'calculations' => [
                'Kubik Nat per Kemasan' => NumberHelper::format($kubikNatPerBungkus).' M3 (= (1/1440) × '.$beratKemasanNat.' kg)',
                'Kubik Air per Kemasan Nat' => NumberHelper::format($kubikAirNatPerBungkus).' M3 (= 33% × kubik nat)',
                'Liter Air per Bungkus' => NumberHelper::format($literAirNatPerBungkus).' liter',
                'Volume Adukan per Bungkus' => NumberHelper::format($volumeAdukanNatPerBungkus).' M3',
                '---' => '---',
                'Total Kubik Nat Pekerjaan' => NumberHelper::format($kubikNatPekerjaan).' M3',
                'Total Kubik Air Pekerjaan' => NumberHelper::format($kubikAirNatPekerjaan).' M3',
                'Total Liter Air Pekerjaan' => NumberHelper::format($literAirNatPekerjaan).' liter',
                'Total Volume Adukan Nat' => NumberHelper::format($volumeAdukanNatPekerjaan).' M3',
            ],
        ];

        // ==================== BAGIAN 3: MENGHITUNG KEBUTUHAN ADUKAN SEMEN ====================

        // ============ STEP 13: Kubik per Kemasan (Semen, Pasir, Air) ============
        // kubik perkemasan semen = kemasan semen * (1 / 1440)
        $kubikPerKemasanSemen = $n($kemasanSemen * (1 / $densitySemen));

        // Kubik per kemasan pasir = 3 * kubik per kemasan semen
        $kubikPerKemasanPasir = $n(3 * $kubikPerKemasanSemen);

        // kubik per kemasan air = (Kubik per kemasan semen + Kubik per kemasan pasir) * 30%
        $kubikPerKemasanAir = $n(($kubikPerKemasanSemen + $kubikPerKemasanPasir) * 0.3);

        $trace['steps'][] = [
            'step' => 13,
            'title' => 'Kubik per Kemasan (Semen, Pasir, Air)',
            'info' => 'Ratio 1 : 3 : 30% (Semen : Pasir : Air)',
            'calculations' => [
                'Kubik per Kemasan Semen' => NumberHelper::format($kubikPerKemasanSemen).' M3 (= '.$kemasanSemen.' × (1/1440))',
                'Kubik per Kemasan Pasir' => NumberHelper::format($kubikPerKemasanPasir).' M3 (= 3 × kubik semen)',
                'Kubik per Kemasan Air' => NumberHelper::format($kubikPerKemasanAir).' M3 (= 30% × (semen + pasir))',
            ],
        ];

        // ============ STEP 14: Volume Adukan per Kemasan Semen ============
        // Volume adukan per kemasan semen = Kubik perkemasan semen + pasir + air
        $volumeAdukanPerKemasan = $n($kubikPerKemasanSemen + $kubikPerKemasanPasir + $kubikPerKemasanAir);

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Volume Adukan per Kemasan Semen',
            'formula' => 'Kubik Semen + Kubik Pasir + Kubik Air',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($kubikPerKemasanSemen).
                    ' + '.
                    NumberHelper::format($kubikPerKemasanPasir).
                    ' + '.
                    NumberHelper::format($kubikPerKemasanAir),
                'Hasil' => NumberHelper::format($volumeAdukanPerKemasan).' M3',
            ],
        ];

        // ============ STEP 15: Luas Screedan per Kemasan Semen ============
        // Luas screedan per kemasan semen = Volume adukan per kemasan semen / tebal adukan * 100
        $tebalAdukanMeter = $n($tebalAdukan / 100);
        $luasScreedanPerKemasan = $n($volumeAdukanPerKemasan / $tebalAdukanMeter);

        $trace['steps'][] = [
            'step' => 15,
            'title' => 'Luas Screedan per Kemasan Semen',
            'formula' => 'Volume adukan per kemasan / (tebal adukan / 100)',
            'info' => 'Berapa M2 yang bisa di-screed dengan 1 kemasan semen',
            'calculations' => [
                'Tebal Adukan (meter)' => NumberHelper::format($tebalAdukanMeter).' m',
                'Perhitungan' => NumberHelper::format($volumeAdukanPerKemasan).' / '.NumberHelper::format($tebalAdukanMeter),
                'Hasil' => NumberHelper::format($luasScreedanPerKemasan).' M2',
            ],
        ];

        // ============ STEP 16: Kebutuhan per M2 ============
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
            'step' => 16,
            'title' => 'Kebutuhan Adukan per M2',
            'calculations' => [
                'Semen per M2 (kemasan)' => NumberHelper::format($kebutuhanSemenPerM2Kemasan).' kemasan/M2',
                'Semen per M2 (kg)' => NumberHelper::format($kebutuhanSemenPerM2Kg).' kg/M2',
                'Semen per M2 (M3)' => NumberHelper::format($kebutuhanSemenPerM2M3).' M3/M2',
                'Pasir per M2 (M3)' => NumberHelper::format($kebutuhanPasirPerM2M3).' M3/M2',
                'Pasir per M2 (sak)' => NumberHelper::format($kebutuhanPasirPerM2Sak).' sak/M2',
                'Air per M2 (liter)' => NumberHelper::format($kebutuhanAirPerM2Liter).' liter/M2',
                'Air per M2 (M3)' => NumberHelper::format($kebutuhanAirPerM2M3).' M3/M2',
            ],
        ];

        // ============ STEP 17: Kebutuhan Adukan per Pekerjaan ============
        // Kebutuhan semen per pekerjaan (kemasan) = Kebutuhan semen per M2 (kemasan / M2) * luas bidang
        $kebutuhanSemenKemasanPekerjaan = $n($kebutuhanSemenPerM2Kemasan * $luasBidang);
        $kebutuhanSemenKgPekerjaan = $n($kebutuhanSemenPerM2Kg * $luasBidang);
        $kebutuhanSemenM3Pekerjaan = $n($kebutuhanSemenPerM2M3 * $luasBidang);

        $kebutuhanPasirM3Pekerjaan = $n($kebutuhanPasirPerM2M3 * $luasBidang);
        $kebutuhanPasirSakPekerjaan = $n($kebutuhanPasirPerM2Sak * $luasBidang);

        $kebutuhanAirM3Pekerjaan = $n($kebutuhanAirPerM2M3 * $luasBidang);
        $kebutuhanAirLiterPekerjaan = $kebutuhanAirM3Pekerjaan * 1000;

        $volumeAdukanPekerjaan = $n($kebutuhanSemenM3Pekerjaan + $kebutuhanPasirM3Pekerjaan + $kebutuhanAirM3Pekerjaan);

        // Harga komparasi semen per pekerjaan = Kebutuhan semen per pekerjaan (kemasan) * Harga semen perkemasan
        $trace['steps'][] = [
            'step' => 17,
            'title' => 'Kebutuhan Adukan per Pekerjaan',
            'info' => 'Total Luas: '.NumberHelper::format($luasBidang).' M2',
            'calculations' => [
                'Semen (kemasan)' => NumberHelper::format($kebutuhanSemenKemasanPekerjaan).' kemasan',
                'Semen (kg)' => NumberHelper::format($kebutuhanSemenKgPekerjaan).' kg',
                'Semen (M3)' => NumberHelper::format($kebutuhanSemenM3Pekerjaan).' M3',
                'Pasir (M3)' => NumberHelper::format($kebutuhanPasirM3Pekerjaan).' M3',
                'Pasir (sak)' => NumberHelper::format($kebutuhanPasirSakPekerjaan).' sak',
                'Air (liter)' => NumberHelper::format($kebutuhanAirLiterPekerjaan).' liter',
                'Air (M3)' => NumberHelper::format($kebutuhanAirM3Pekerjaan).' M3',
                'Volume Adukan Total' => NumberHelper::format($volumeAdukanPekerjaan).' M3',
            ],
        ];

        // ============ STEP 18: Harga ============
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

        $totalCementPrice = $n($kebutuhanSemenKemasanPekerjaan * $cementPrice, 0);
        $totalSandPrice = $n($kebutuhanPasirM3Pekerjaan * $sandPricePerM3, 0);
        $totalCeramicPrice = $n($kebutuhanDusUtuhPekerjaan * $ceramicPricePerDus, 0);
        $totalGroutPrice = $n($kebutuhanBungkusNat * $hargaNatPerBungkus, 0);
        $grandTotal = $n($totalCementPrice + $totalSandPrice + $totalCeramicPrice + $totalGroutPrice, 0);

        $trace['steps'][] = [
            'step' => 18,
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
            'total_tiles' => $totalLembarKeramikUtuh,
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
