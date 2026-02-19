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
class PlinthInstallationFormula
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
        $trace['mode'] = static::getName();
        $trace['steps'] = [];
        $n = static fn($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjangBidang = $n($params['wall_length']); // m
        $tinggiBidangCm = $n($params['wall_height']); // cm (untuk plint, tinggi biasanya dalam cm)
        $tebalAdukan = $n($params['mortar_thickness']); // cm
        $tebalNat = $n($params['grout_thickness']); // mm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'info' => 'Parameter dasar yang dimasukkan pengguna untuk perhitungan plint keramik',
            'calculations' => [
                'Panjang Bidang (P)' => NumberHelper::format($panjangBidang) . ' m',
                'Tinggi Bidang (T)' => NumberHelper::format($tinggiBidangCm) . ' cm',
                'Tebal Adukan (Ta)' => NumberHelper::format($tebalAdukan) . ' cm',
                'Tebal Nat (Tn)' => NumberHelper::format($tebalNat) . ' mm',
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
                'Data material nat tidak tersedia di database. Pastikan ada data di tabel cements dengan material_kind=nat.',
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
            'title' => 'Data Material dari Database',
            'info' => 'Material yang dipilih beserta spesifikasi dan konstanta yang digunakan',
            'calculations' => [
                'Semen' =>
                    $cement->brand .
                    ' (Kemasan: ' .
                    NumberHelper::format($kemasanSemen) .
                    ' kg, Harga: ' .
                    NumberHelper::currency($cement->package_price ?? 0) .
                    ')',
                'Pasir' =>
                    $sand->brand . ' (Harga/M3: ' . NumberHelper::currency($sand->comparison_price_per_m3 ?? 0) . ')',
                'Keramik' =>
                    $ceramic->brand .
                    ' (Dimensi: ' .
                    NumberHelper::format($panjangKeramik) .
                    '×' .
                    NumberHelper::format($lebarKeramik) .
                    '×' .
                    NumberHelper::format($tebalKeramikCm) .
                    ' cm, Isi: ' .
                    $isiPerDus .
                    ' pcs/dus, Harga: ' .
                    NumberHelper::currency($ceramic->price_per_package ?? 0) .
                    '/dus)',
                'Nat' =>
                    $nat->brand .
                    ' (Kemasan: ' .
                    NumberHelper::format($beratKemasanNat) .
                    ' kg, Harga: ' .
                    NumberHelper::currency($hargaNatPerBungkus) .
                    '/bungkus)',
                '--- Konstanta ---' => '',
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Densitas Nat' => $densityNat . ' kg/M3',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3',
                'Tebal Keramik' =>
                    NumberHelper::format($tebalKeramikCm) . ' cm = ' . NumberHelper::format($tebalKeramikMm) . ' mm',
                '--- Rasio Adukan ---' => '',
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
            'title' => 'Luas Bidang Pekerjaan',
            'formula' => 'Luas Bidang = Panjang Bidang × (Tinggi Bidang / 100)',
            'info' => 'Konversi tinggi dari cm ke meter, lalu kalikan dengan panjang',
            'calculations' => [
                'Konversi Tinggi' =>
                    NumberHelper::format($tinggiBidangCm) .
                    ' cm ÷ 100 = ' .
                    NumberHelper::format($tinggiBidangM) .
                    ' m',
                'Luas Bidang' =>
                    NumberHelper::format($panjangBidang) .
                    ' m × ' .
                    NumberHelper::format($tinggiBidangM) .
                    ' m = ' .
                    NumberHelper::format($luasBidang) .
                    ' M2',
            ],
        ];

        // ==================== BAGIAN 1: MENGHITUNG KEBUTUHAN KERAMIK ====================

        // ============ STEP 4: Total Keramik Utuh per Panjang ============
        // Total keramik utuh per Panjang = Panjang bidang / ((Panjang keramik + (ketebalan nat / 10)) / 100)
        $panjangKeramikDenganNat = $n(($panjangKeramik + $tebalNat / 10) / 100);
        $totalKeramikUtuhPerPanjang = ceil($panjangBidang / $panjangKeramikDenganNat);

        $tebalNatCm = $n($tebalNat / 10);
        $panjangKeramikPlusNatCm = $n($panjangKeramik + $tebalNatCm);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Total Keramik Utuh per Panjang',
            'formula' => 'Total Keramik Utuh = ceil(Panjang Bidang / ((Panjang Keramik + (Tebal Nat / 10)) / 100))',
            'info' =>
                'Menghitung jumlah potongan keramik yang dibutuhkan sepanjang bidang, dengan memperhitungkan celah nat',
            'calculations' => [
                'Tebal Nat dalam cm' =>
                    NumberHelper::format($tebalNat) . ' mm ÷ 10 = ' . NumberHelper::format($tebalNatCm) . ' cm',
                'Panjang Keramik + Nat' =>
                    NumberHelper::format($panjangKeramik) .
                    ' cm + ' .
                    NumberHelper::format($tebalNatCm) .
                    ' cm = ' .
                    NumberHelper::format($panjangKeramikPlusNatCm) .
                    ' cm',
                'Konversi ke meter' =>
                    NumberHelper::format($panjangKeramikPlusNatCm) .
                    ' cm ÷ 100 = ' .
                    NumberHelper::format($panjangKeramikDenganNat) .
                    ' m',
                'Pembagian' =>
                    NumberHelper::format($panjangBidang) .
                    ' m ÷ ' .
                    NumberHelper::format($panjangKeramikDenganNat) .
                    ' m = ' .
                    NumberHelper::format($panjangBidang / $panjangKeramikDenganNat),
                'Pembulatan ke atas (ceil)' =>
                    'ceil(' .
                    NumberHelper::format($panjangBidang / $panjangKeramikDenganNat) .
                    ') = ' .
                    NumberHelper::format($totalKeramikUtuhPerPanjang) .
                    ' pcs',
            ],
        ];

        // ============ STEP 5: Total Lembar Keramik Utuh per Panjang ============
        // Total lembar keramik utuh per panjang = Total keramik utuh per Panjang / 2
        $totalLembarKeramikUtuh = $n($totalKeramikUtuhPerPanjang / 2);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Total Lembar Keramik Utuh per Panjang',
            'formula' => 'Total Lembar = Total Keramik Utuh per Panjang / 2',
            'info' =>
                '1 lembar keramik utuh dipotong menjadi 2 potongan plint (separuh lebar), sehingga kebutuhan lembar utuh = setengah dari total potongan',
            'calculations' => [
                'Total Keramik Utuh per Panjang' => NumberHelper::format($totalKeramikUtuhPerPanjang) . ' pcs',
                'Dibagi 2' =>
                    NumberHelper::format($totalKeramikUtuhPerPanjang) .
                    ' ÷ 2 = ' .
                    NumberHelper::format($totalLembarKeramikUtuh) .
                    ' lembar',
            ],
        ];

        // ============ STEP 6: Kebutuhan Dus Keramik per Pekerjaan ============
        // Kebutuhan dus keramik per pekerjaan = Total lembar keramik utuh / isi keramik per kemasan
        $kebutuhanDusUtuhPekerjaan = $n($totalLembarKeramikUtuh / $isiPerDus);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Kebutuhan Dus Keramik per Pekerjaan',
            'formula' => 'Kebutuhan Dus = Total Lembar Keramik Utuh / Isi Keramik per Kemasan',
            'info' => 'Menghitung berapa dus keramik yang diperlukan berdasarkan jumlah lembar utuh',
            'calculations' => [
                'Total Lembar Keramik Utuh' => NumberHelper::format($totalLembarKeramikUtuh) . ' lembar',
                'Isi per Dus' => $isiPerDus . ' pcs/dus',
                'Kebutuhan Dus' =>
                    NumberHelper::format($totalLembarKeramikUtuh) .
                    ' ÷ ' .
                    $isiPerDus .
                    ' = ' .
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' dus',
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
            'formula' => 'Dus/M2 = Kebutuhan Dus per Pekerjaan / Luas Bidang; Lembar/M2 = Dus/M2 × Isi per Dus',
            'info' => 'Normalisasi kebutuhan keramik ke satuan per M2',
            'calculations' => [
                'Kebutuhan Dus per M2' =>
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' dus ÷ ' .
                    NumberHelper::format($luasBidang) .
                    ' M2 = ' .
                    NumberHelper::format($kebutuhanDusPerM2) .
                    ' dus/M2',
                'Kebutuhan Lembar per M2' =>
                    NumberHelper::format($kebutuhanDusPerM2) .
                    ' dus/M2 × ' .
                    $isiPerDus .
                    ' pcs/dus = ' .
                    NumberHelper::format($kebutuhanKeramikPerM2) .
                    ' pcs/M2',
            ],
        ];

        // ==================== BAGIAN 2: MENGHITUNG KEBUTUHAN NAT ====================

        // ============ STEP 8: Jumlah Kolom dan Baris Nat ============
        // Jumlah kolom nat per pekerjaan = (Panjang bidang / ((Panjang keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahKolomNat = (int) ceil($panjangBidang / (($panjangKeramik + $tebalNat / 10) / 100)) + 1;

        // Jumlah baris nat per pekerjaan = (tinggi bidang / ((lebar keramik + (tebal nat / 10)) / 100)) + 1
        $jumlahBarisNat = (int) ceil($tinggiBidangM / (($lebarKeramik + $tebalNat / 10) / 100)) + 1;

        $lebarKeramikPlusNatCm = $n($lebarKeramik + $tebalNatCm);
        $lebarKeramikDenganNatM = $n(($lebarKeramik + $tebalNatCm) / 100);

        $rawKolom = $panjangBidang / $panjangKeramikDenganNat;
        $rawBaris = $tinggiBidangM / $lebarKeramikDenganNatM;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Jumlah Kolom dan Baris Nat',
            'formula' =>
                'Kolom = ceil(Panjang Bidang / ((Panjang Keramik + Tebal Nat/10) / 100)) + 1; Baris = ceil(Tinggi Bidang / ((Lebar Keramik + Tebal Nat/10) / 100)) + 1',
            'info' =>
                'Jumlah garis nat vertikal (kolom) dan horizontal (baris). Ditambah 1 karena nat ada di kedua sisi keramik',
            'calculations' => [
                '--- Kolom Nat (Vertikal) ---' => '',
                'Panjang Keramik + Nat' =>
                    NumberHelper::format($panjangKeramik) .
                    ' + ' .
                    NumberHelper::format($tebalNatCm) .
                    ' = ' .
                    NumberHelper::format($panjangKeramikPlusNatCm) .
                    ' cm = ' .
                    NumberHelper::format($panjangKeramikDenganNat) .
                    ' m',
                'Pembagian Kolom' =>
                    NumberHelper::format($panjangBidang) .
                    ' m ÷ ' .
                    NumberHelper::format($panjangKeramikDenganNat) .
                    ' m = ' .
                    NumberHelper::format($rawKolom),
                'Jumlah Kolom Nat' =>
                    'ceil(' .
                    NumberHelper::format($rawKolom) .
                    ') + 1 = ' .
                    NumberHelper::format($jumlahKolomNat) .
                    ' garis',
                '--- Baris Nat (Horizontal) ---' => '',
                'Lebar Keramik + Nat' =>
                    NumberHelper::format($lebarKeramik) .
                    ' + ' .
                    NumberHelper::format($tebalNatCm) .
                    ' = ' .
                    NumberHelper::format($lebarKeramikPlusNatCm) .
                    ' cm = ' .
                    NumberHelper::format($lebarKeramikDenganNatM) .
                    ' m',
                'Pembagian Baris' =>
                    NumberHelper::format($tinggiBidangM) .
                    ' m ÷ ' .
                    NumberHelper::format($lebarKeramikDenganNatM) .
                    ' m = ' .
                    NumberHelper::format($rawBaris),
                'Jumlah Baris Nat' =>
                    'ceil(' .
                    NumberHelper::format($rawBaris) .
                    ') + 1 = ' .
                    NumberHelper::format($jumlahBarisNat) .
                    ' garis',
            ],
        ];

        // ============ STEP 9: Panjang Bentangan Nat ============
        // Panjang bentangan nat per pekerjaan = (jumlah kolom nat per pekerjaan * tinggi bidang) + (jumlah baris nat per pekerjaan * Panjang bidang)
        $panjangBentanganNat = $n($jumlahKolomNat * $tinggiBidangM + $jumlahBarisNat * $panjangBidang);

        $panjangNatVertikal = $n($jumlahKolomNat * $tinggiBidangM);
        $panjangNatHorizontal = $n($jumlahBarisNat * $panjangBidang);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Panjang Bentangan Nat per Pekerjaan',
            'formula' => 'Panjang Bentangan = (Jumlah Kolom Nat × Tinggi Bidang) + (Jumlah Baris Nat × Panjang Bidang)',
            'info' => 'Total panjang seluruh garis nat (vertikal + horizontal) dalam meter',
            'calculations' => [
                'Panjang Nat Vertikal' =>
                    $jumlahKolomNat .
                    ' garis × ' .
                    NumberHelper::format($tinggiBidangM) .
                    ' m = ' .
                    NumberHelper::format($panjangNatVertikal) .
                    ' m',
                'Panjang Nat Horizontal' =>
                    $jumlahBarisNat .
                    ' garis × ' .
                    NumberHelper::format($panjangBidang) .
                    ' m = ' .
                    NumberHelper::format($panjangNatHorizontal) .
                    ' m',
                'Total Panjang Bentangan' =>
                    NumberHelper::format($panjangNatVertikal) .
                    ' + ' .
                    NumberHelper::format($panjangNatHorizontal) .
                    ' = ' .
                    NumberHelper::format($panjangBentanganNat) .
                    ' m',
            ],
        ];

        // ============ STEP 10: Volume Nat per Pekerjaan ============
        // Volume nat per pekerjaan = Panjang bentangan nat per pekerjaan * ketebalan nat * tebal keramik / 1000000
        $volumeNatPekerjaan = $n(($panjangBentanganNat * $tebalNat * $tebalKeramikMm) / 1000000);

        $volumeNatSebelumKonversi = $n($panjangBentanganNat * $tebalNat * $tebalKeramikMm);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Volume Nat per Pekerjaan',
            'formula' => 'Volume Nat = Panjang Bentangan (m) × Tebal Nat (mm) × Tebal Keramik (mm) / 1.000.000',
            'info' => 'Konversi dari m×mm×mm ke M3: bagi 1.000.000 (1m = 1000mm, jadi mm² ke m² = /1.000.000)',
            'calculations' => [
                'Panjang Bentangan' => NumberHelper::format($panjangBentanganNat) . ' m',
                'Tebal Nat' => NumberHelper::format($tebalNat) . ' mm',
                'Tebal Keramik' => NumberHelper::format($tebalKeramikMm) . ' mm',
                'Perkalian' =>
                    NumberHelper::format($panjangBentanganNat) .
                    ' × ' .
                    NumberHelper::format($tebalNat) .
                    ' × ' .
                    NumberHelper::format($tebalKeramikMm) .
                    ' = ' .
                    NumberHelper::format($volumeNatSebelumKonversi),
                'Volume Nat' =>
                    NumberHelper::format($volumeNatSebelumKonversi) .
                    ' ÷ 1.000.000 = ' .
                    NumberHelper::format($volumeNatPekerjaan) .
                    ' M3',
            ],
        ];

        // ============ STEP 11: Kebutuhan Kemasan Nat ============
        // Jumlah kemasan kebutuhan nat per pekerjaan = volume nat per pekerjaan / volume kubik nat per kemasan
        $kebutuhanBungkusNat = $n($volumeNatPekerjaan / $volumePastaNatPerBungkus);

        // Jumlah kg kebutuhan nat per pekerjaan = volume nat per pekerjaan / volume kubik nat per kemasan
        $kebutuhanKgNat = $n($kebutuhanBungkusNat * $beratKemasanNat);

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Kebutuhan Kemasan Nat per Pekerjaan',
            'formula' => 'Bungkus = Volume Nat / Volume Pasta Nat per Bungkus; Kg = Bungkus × Berat per Kemasan',
            'info' => 'Menghitung berapa bungkus nat yang diperlukan berdasarkan volume celah nat',
            'calculations' => [
                'Volume Nat per Pekerjaan' => NumberHelper::format($volumeNatPekerjaan) . ' M3',
                'Volume Pasta Nat per Bungkus' => NumberHelper::format($volumePastaNatPerBungkus) . ' M3/bungkus',
                'Kebutuhan Bungkus' =>
                    NumberHelper::format($volumeNatPekerjaan) .
                    ' ÷ ' .
                    NumberHelper::format($volumePastaNatPerBungkus) .
                    ' = ' .
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' bungkus',
                'Kebutuhan Kg' =>
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' bungkus × ' .
                    NumberHelper::format($beratKemasanNat) .
                    ' kg/bungkus = ' .
                    NumberHelper::format($kebutuhanKgNat) .
                    ' kg',
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
            'formula' => 'Kubik Nat = (1/Densitas) × Berat; Air = 33% × Kubik Nat; Volume Adukan = Kubik Nat + Air',
            'info' => 'Rasio adukan nat 1 : 33% (Nat : Air). Perhitungan per kemasan lalu dikalikan kebutuhan bungkus.',
            'calculations' => [
                '--- Per Kemasan ---' => '',
                'Kubik Nat per Kemasan' =>
                    '(1 ÷ ' .
                    $densityNat .
                    ') × ' .
                    NumberHelper::format($beratKemasanNat) .
                    ' kg = ' .
                    NumberHelper::format($kubikNatPerBungkus) .
                    ' M3',
                'Kubik Air per Kemasan' =>
                    '33% × ' .
                    NumberHelper::format($kubikNatPerBungkus) .
                    ' M3 = ' .
                    NumberHelper::format($kubikAirNatPerBungkus) .
                    ' M3',
                'Liter Air per Bungkus' =>
                    NumberHelper::format($kubikAirNatPerBungkus) .
                    ' M3 × 1000 = ' .
                    NumberHelper::format($literAirNatPerBungkus) .
                    ' liter',
                'Volume Adukan per Bungkus' =>
                    NumberHelper::format($kubikNatPerBungkus) .
                    ' + ' .
                    NumberHelper::format($kubikAirNatPerBungkus) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanNatPerBungkus) .
                    ' M3',
                '--- Per Pekerjaan (× ' . NumberHelper::format($kebutuhanBungkusNat) . ' bungkus) ---' => '',
                'Total Kubik Nat' =>
                    NumberHelper::format($kubikNatPerBungkus) .
                    ' × ' .
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' = ' .
                    NumberHelper::format($kubikNatPekerjaan) .
                    ' M3',
                'Total Kubik Air' =>
                    NumberHelper::format($kubikAirNatPerBungkus) .
                    ' × ' .
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' = ' .
                    NumberHelper::format($kubikAirNatPekerjaan) .
                    ' M3',
                'Total Liter Air' =>
                    NumberHelper::format($kubikAirNatPekerjaan) .
                    ' M3 × 1000 = ' .
                    NumberHelper::format($literAirNatPekerjaan) .
                    ' liter',
                'Total Volume Adukan Nat' =>
                    NumberHelper::format($volumeAdukanNatPerBungkus) .
                    ' × ' .
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanNatPekerjaan) .
                    ' M3',
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

        $kubikSemenPlusPasir = $n($kubikPerKemasanSemen + $kubikPerKemasanPasir);

        $trace['steps'][] = [
            'step' => 13,
            'title' => 'Kubik per Kemasan (Semen, Pasir, Air)',
            'formula' => 'Semen = Kemasan × (1/Densitas); Pasir = 3 × Semen; Air = 30% × (Semen + Pasir)',
            'info' => 'Rasio adukan semen 1 : 3 : 30% (Semen : Pasir : Air)',
            'calculations' => [
                'Kubik Semen per Kemasan' =>
                    NumberHelper::format($kemasanSemen) .
                    ' kg × (1 ÷ ' .
                    $densitySemen .
                    ') = ' .
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' M3',
                'Kubik Pasir per Kemasan' =>
                    '3 × ' .
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' M3 = ' .
                    NumberHelper::format($kubikPerKemasanPasir) .
                    ' M3',
                'Kubik Air per Kemasan' =>
                    '30% × (' .
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' + ' .
                    NumberHelper::format($kubikPerKemasanPasir) .
                    ') = 30% × ' .
                    NumberHelper::format($kubikSemenPlusPasir) .
                    ' = ' .
                    NumberHelper::format($kubikPerKemasanAir) .
                    ' M3',
            ],
        ];

        // ============ STEP 14: Volume Adukan per Kemasan Semen ============
        // Volume adukan per kemasan semen = Kubik perkemasan semen + pasir + air
        $volumeAdukanPerKemasan = $n($kubikPerKemasanSemen + $kubikPerKemasanPasir + $kubikPerKemasanAir);

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Volume Adukan per Kemasan Semen',
            'formula' => 'Volume Adukan = Kubik Semen + Kubik Pasir + Kubik Air',
            'info' => 'Total volume campuran adukan yang dihasilkan dari 1 kemasan semen',
            'calculations' => [
                'Kubik Semen' => NumberHelper::format($kubikPerKemasanSemen) . ' M3',
                'Kubik Pasir' => NumberHelper::format($kubikPerKemasanPasir) . ' M3',
                'Kubik Air' => NumberHelper::format($kubikPerKemasanAir) . ' M3',
                'Volume Adukan' =>
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' + ' .
                    NumberHelper::format($kubikPerKemasanPasir) .
                    ' + ' .
                    NumberHelper::format($kubikPerKemasanAir) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanPerKemasan) .
                    ' M3',
            ],
        ];

        // ============ STEP 15: Luas Screedan per Kemasan Semen ============
        // Luas screedan per kemasan semen = Volume adukan per kemasan semen / tebal adukan * 100
        $tebalAdukanMeter = $n($tebalAdukan / 100);
        $luasScreedanPerKemasan = $n($volumeAdukanPerKemasan / $tebalAdukanMeter);

        $trace['steps'][] = [
            'step' => 15,
            'title' => 'Luas Screedan per Kemasan Semen',
            'formula' => 'Luas Screedan = Volume Adukan per Kemasan / (Tebal Adukan / 100)',
            'info' => 'Berapa M2 bidang yang bisa di-screed (dilapis adukan) dengan 1 kemasan semen',
            'calculations' => [
                'Konversi Tebal Adukan' =>
                    NumberHelper::format($tebalAdukan) .
                    ' cm ÷ 100 = ' .
                    NumberHelper::format($tebalAdukanMeter) .
                    ' m',
                'Luas Screedan' =>
                    NumberHelper::format($volumeAdukanPerKemasan) .
                    ' M3 ÷ ' .
                    NumberHelper::format($tebalAdukanMeter) .
                    ' m = ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' M2',
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

        $kubikAirPerKemasanLiter = $n($kubikPerKemasanAir * 1000);

        $trace['steps'][] = [
            'step' => 16,
            'title' => 'Kebutuhan Adukan per M2',
            'formula' => 'Kebutuhan per M2 = Nilai per Kemasan / Luas Screedan per Kemasan',
            'info' =>
                'Normalisasi kebutuhan adukan dari per-kemasan menjadi per-M2. Luas Screedan = ' .
                NumberHelper::format($luasScreedanPerKemasan) .
                ' M2',
            'calculations' => [
                '--- Semen ---' => '',
                'Semen (kemasan/M2)' =>
                    '1 ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenPerM2Kemasan) .
                    ' kemasan/M2',
                'Semen (kg/M2)' =>
                    NumberHelper::format($kemasanSemen) .
                    ' kg ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenPerM2Kg) .
                    ' kg/M2',
                'Semen (M3/M2)' =>
                    NumberHelper::format($kubikPerKemasanSemen) .
                    ' M3 ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenPerM2M3) .
                    ' M3/M2',
                '--- Pasir ---' => '',
                'Pasir (M3/M2)' =>
                    NumberHelper::format($kubikPerKemasanPasir) .
                    ' M3 ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanPasirPerM2M3) .
                    ' M3/M2',
                'Pasir (sak/M2)' =>
                    '3 ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanPasirPerM2Sak) .
                    ' sak/M2',
                '--- Air ---' => '',
                'Air (liter/M2)' =>
                    '(' .
                    NumberHelper::format($kubikPerKemasanAir) .
                    ' × 1000) ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kubikAirPerKemasanLiter) .
                    ' ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanAirPerM2Liter) .
                    ' liter/M2',
                'Air (M3/M2)' =>
                    NumberHelper::format($kubikPerKemasanAir) .
                    ' M3 ÷ ' .
                    NumberHelper::format($luasScreedanPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kebutuhanAirPerM2M3) .
                    ' M3/M2',
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
            'formula' => 'Kebutuhan per Pekerjaan = Kebutuhan per M2 × Luas Bidang',
            'info' =>
                'Mengalikan kebutuhan per M2 dengan total luas bidang (' . NumberHelper::format($luasBidang) . ' M2)',
            'calculations' => [
                '--- Semen ---' => '',
                'Semen (kemasan)' =>
                    NumberHelper::format($kebutuhanSemenPerM2Kemasan) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenKemasanPekerjaan) .
                    ' kemasan',
                'Semen (kg)' =>
                    NumberHelper::format($kebutuhanSemenPerM2Kg) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenKgPekerjaan) .
                    ' kg',
                'Semen (M3)' =>
                    NumberHelper::format($kebutuhanSemenPerM2M3) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanSemenM3Pekerjaan) .
                    ' M3',
                '--- Pasir ---' => '',
                'Pasir (M3)' =>
                    NumberHelper::format($kebutuhanPasirPerM2M3) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanPasirM3Pekerjaan) .
                    ' M3',
                'Pasir (sak)' =>
                    NumberHelper::format($kebutuhanPasirPerM2Sak) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanPasirSakPekerjaan) .
                    ' sak',
                '--- Air ---' => '',
                'Air (M3)' =>
                    NumberHelper::format($kebutuhanAirPerM2M3) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanAirM3Pekerjaan) .
                    ' M3',
                'Air (liter)' =>
                    NumberHelper::format($kebutuhanAirM3Pekerjaan) .
                    ' M3 × 1000 = ' .
                    NumberHelper::format($kebutuhanAirLiterPekerjaan) .
                    ' liter',
                '--- Total ---' => '',
                'Volume Adukan Total' =>
                    NumberHelper::format($kebutuhanSemenM3Pekerjaan) .
                    ' + ' .
                    NumberHelper::format($kebutuhanPasirM3Pekerjaan) .
                    ' + ' .
                    NumberHelper::format($kebutuhanAirM3Pekerjaan) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' M3',
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
            'title' => 'Perhitungan Harga Komparasi',
            'formula' => 'Total Harga = Kebutuhan per Pekerjaan × Harga Satuan',
            'info' => 'Harga komparasi total material untuk seluruh pekerjaan',
            'calculations' => [
                '--- Semen ---' => '',
                'Harga Semen per Kemasan' => NumberHelper::currency($cementPrice),
                'Total Harga Semen' =>
                    NumberHelper::format($kebutuhanSemenKemasanPekerjaan) .
                    ' kemasan × ' .
                    NumberHelper::currency($cementPrice) .
                    ' = ' .
                    NumberHelper::currency($totalCementPrice),
                '--- Pasir ---' => '',
                'Harga Pasir per M3' => NumberHelper::currency($sandPricePerM3),
                'Total Harga Pasir' =>
                    NumberHelper::format($kebutuhanPasirM3Pekerjaan) .
                    ' M3 × ' .
                    NumberHelper::currency($sandPricePerM3) .
                    ' = ' .
                    NumberHelper::currency($totalSandPrice),
                '--- Keramik ---' => '',
                'Harga Keramik per Dus' => NumberHelper::currency($ceramicPricePerDus),
                'Total Harga Keramik' =>
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' dus × ' .
                    NumberHelper::currency($ceramicPricePerDus) .
                    ' = ' .
                    NumberHelper::currency($totalCeramicPrice),
                '--- Nat ---' => '',
                'Harga Nat per Bungkus' => NumberHelper::currency($hargaNatPerBungkus),
                'Total Harga Nat' =>
                    NumberHelper::format($kebutuhanBungkusNat) .
                    ' bungkus × ' .
                    NumberHelper::currency($hargaNatPerBungkus) .
                    ' = ' .
                    NumberHelper::currency($totalGroutPrice),
                '--- Grand Total ---' => '',
                'Grand Total' =>
                    NumberHelper::currency($totalCementPrice) .
                    ' + ' .
                    NumberHelper::currency($totalSandPrice) .
                    ' + ' .
                    NumberHelper::currency($totalCeramicPrice) .
                    ' + ' .
                    NumberHelper::currency($totalGroutPrice) .
                    ' = ' .
                    NumberHelper::currency($grandTotal),
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
