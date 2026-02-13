<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;
use App\Models\Cement;
use App\Models\Ceramic;

/**
 * Formula - Perhitungan Pasang Keramik Saja
 * Menghitung kebutuhan keramik dan perekat (semen) untuk pemasangan keramik tanpa nat.
 */
class PlinthInstallationOnlyFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'plinth_installation_only';
    }

    public static function getName(): string
    {
        return 'Pasang Keramik Plinth Saja';
    }

    public static function getDescription(): string
    {
        return 'Menghitung kebutuhan keramik dan perekat (semen) untuk pemasangan keramik tanpa nat.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['cement', 'ceramic'];
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
        $n = static fn($value, $decimals = null) => (float) ($value ?? 0);

        // ============ STEP 1: Load Input Parameters ============
        $panjangBidang = $n($params['wall_length']); // m
        $lebarBidang = $n($params['wall_height']); // m
        $tebalAdukan = $n($params['mortar_thickness']); // cm
        $tebalNat = $n($params['grout_thickness']); // mm

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'info' => 'Parameter dasar yang dimasukkan pengguna',
            'calculations' => [
                'Panjang Bidang (P)' => NumberHelper::format($panjangBidang) . ' m',
                'Lebar Bidang (L)' => NumberHelper::format($lebarBidang) . ' m',
                'Tebal Adukan (Ta)' => NumberHelper::format($tebalAdukan) . ' cm',
                'Tebal Nat (Tn)' => NumberHelper::format($tebalNat) . ' mm',
            ],
        ];

        // ============ STEP 2: Load Material dari Database ============
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : null;
        $ceramic = isset($params['ceramic_id']) ? Ceramic::find($params['ceramic_id']) : null;

        $cement = $cement ?: Cement::query()->first();
        $ceramic = $ceramic ?: Ceramic::first();

        if (!$cement) {
            throw new \RuntimeException('Data material semen tidak tersedia di database.');
        }

        if (!$ceramic) {
            throw new \RuntimeException('Data material keramik tidak tersedia di database.');
        }

        $satuanKemasanSemen = $n($cement->package_weight_net > 0 ? $cement->package_weight_net : 50); // kg
        $densitySemen = 2250; // kg/M3

        $panjangKeramik = $n($ceramic->dimension_length); // cm
        $lebarKeramik = $n($ceramic->dimension_width); // cm
        $isiPerDus = (int) $ceramic->pieces_per_package;

        if ($panjangKeramik <= 0 || $lebarKeramik <= 0 || $isiPerDus <= 0) {
            throw new \RuntimeException('Data keramik tidak lengkap (dimensi/isi per dus).');
        }

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Material dari Database',
            'info' => 'Material yang dipilih beserta spesifikasi dan konstanta yang digunakan',
            'calculations' => [
                'Semen' =>
                    $cement->brand .
                    ' (Kemasan: ' .
                    NumberHelper::format($satuanKemasanSemen) .
                    ' kg, Harga: ' .
                    NumberHelper::currency($cement->package_price ?? 0) .
                    ')',
                'Keramik' =>
                    $ceramic->brand .
                    ' (Dimensi: ' .
                    NumberHelper::format($panjangKeramik) .
                    '×' .
                    NumberHelper::format($lebarKeramik) .
                    ' cm, Isi: ' .
                    $isiPerDus .
                    ' pcs/dus, Harga: ' .
                    NumberHelper::currency($ceramic->price_per_package ?? 0) .
                    '/dus)',
                '--- Konstanta ---' => '',
                'Densitas Semen' => $densitySemen . ' kg/M3',
                'Rasio Campuran Perekat' => '1 : 50% (Semen : Air)',
            ],
        ];

        // ============ STEP 3: Hitung Luas Bidang ============
        $luasBidang = $n($panjangBidang * $lebarBidang);

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Bidang Pekerjaan',
            'formula' => 'Luas Bidang = Panjang × Lebar',
            'calculations' => [
                'Luas Bidang' =>
                    NumberHelper::format($panjangBidang) .
                    ' m × ' .
                    NumberHelper::format($lebarBidang) .
                    ' m = ' .
                    NumberHelper::format($luasBidang) .
                    ' M2',
            ],
        ];

        // ==================== BAGIAN 1: MENGHITUNG KEBUTUHAN KERAMIK ====================

        // ============ STEP 4: Jumlah Keramik per Baris dan Kolom ============
        $tebalNatCm = $n($tebalNat / 10);
        $panjangKeramikPlusNatCm = $n($panjangKeramik + $tebalNatCm);
        $lebarKeramikPlusNatCm = $n($lebarKeramik + $tebalNatCm);
        $panjangKeramikDenganNatM = $n($panjangKeramikPlusNatCm / 100);
        $lebarKeramikDenganNatM = $n($lebarKeramikPlusNatCm / 100);

        $jumlahKeramikPerBaris = (int) ceil($panjangBidang / $panjangKeramikDenganNatM);
        $jumlahKeramikPerKolom = (int) ceil($lebarBidang / $lebarKeramikDenganNatM);
        $totalKeramikUtuh = $jumlahKeramikPerBaris * $jumlahKeramikPerKolom;

        $rawBaris = $panjangBidang / $panjangKeramikDenganNatM;
        $rawKolom = $lebarBidang / $lebarKeramikDenganNatM;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Jumlah Keramik yang Dibutuhkan',
            'formula' =>
                'Total = ceil(Panjang / ((P.Keramik + Tn/10) / 100)) × ceil(Lebar / ((L.Keramik + Tn/10) / 100))',
            'info' => 'Menghitung jumlah keramik utuh berdasarkan dimensi bidang dan keramik',
            'calculations' => [
                'Tebal Nat dalam cm' =>
                    NumberHelper::format($tebalNat) . ' mm ÷ 10 = ' . NumberHelper::format($tebalNatCm) . ' cm',
                '--- Per Baris (Panjang) ---' => '',
                'Panjang Keramik + Nat' =>
                    NumberHelper::format($panjangKeramik) .
                    ' + ' .
                    NumberHelper::format($tebalNatCm) .
                    ' = ' .
                    NumberHelper::format($panjangKeramikPlusNatCm) .
                    ' cm = ' .
                    NumberHelper::format($panjangKeramikDenganNatM) .
                    ' m',
                'Pembagian Baris' =>
                    NumberHelper::format($panjangBidang) .
                    ' m ÷ ' .
                    NumberHelper::format($panjangKeramikDenganNatM) .
                    ' m = ' .
                    NumberHelper::format($rawBaris),
                'Keramik per Baris' =>
                    'ceil(' .
                    NumberHelper::format($rawBaris) .
                    ') = ' .
                    NumberHelper::format($jumlahKeramikPerBaris) .
                    ' pcs',
                '--- Per Kolom (Lebar) ---' => '',
                'Lebar Keramik + Nat' =>
                    NumberHelper::format($lebarKeramik) .
                    ' + ' .
                    NumberHelper::format($tebalNatCm) .
                    ' = ' .
                    NumberHelper::format($lebarKeramikPlusNatCm) .
                    ' cm = ' .
                    NumberHelper::format($lebarKeramikDenganNatM) .
                    ' m',
                'Pembagian Kolom' =>
                    NumberHelper::format($lebarBidang) .
                    ' m ÷ ' .
                    NumberHelper::format($lebarKeramikDenganNatM) .
                    ' m = ' .
                    NumberHelper::format($rawKolom),
                'Keramik per Kolom' =>
                    'ceil(' .
                    NumberHelper::format($rawKolom) .
                    ') = ' .
                    NumberHelper::format($jumlahKeramikPerKolom) .
                    ' pcs',
                '--- Total ---' => '',
                'Total Keramik Utuh' =>
                    NumberHelper::format($jumlahKeramikPerBaris) .
                    ' × ' .
                    NumberHelper::format($jumlahKeramikPerKolom) .
                    ' = ' .
                    NumberHelper::format($totalKeramikUtuh) .
                    ' pcs',
            ],
        ];

        // ============ STEP 5: Kebutuhan Dus Keramik ============
        $kebutuhanDusUtuhPekerjaan = $n($totalKeramikUtuh / $isiPerDus);
        $kebutuhanDusPerM2 = $n($kebutuhanDusUtuhPekerjaan / $luasBidang);
        $kebutuhanKeramikPerM2 = $n($totalKeramikUtuh / $luasBidang);

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Kebutuhan Dus Keramik',
            'formula' => 'Dus = Total Keramik Utuh / Isi per Dus',
            'info' => 'Menghitung berapa dus keramik yang diperlukan',
            'calculations' => [
                'Total Keramik Utuh' => NumberHelper::format($totalKeramikUtuh) . ' pcs',
                'Isi per Dus' => $isiPerDus . ' pcs/dus',
                'Kebutuhan Dus per Pekerjaan' =>
                    NumberHelper::format($totalKeramikUtuh) .
                    ' ÷ ' .
                    $isiPerDus .
                    ' = ' .
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' dus',
                'Kebutuhan Dus per M2' =>
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' ÷ ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanDusPerM2) .
                    ' dus/M2',
                'Kebutuhan Keramik per M2' =>
                    NumberHelper::format($totalKeramikUtuh) .
                    ' ÷ ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kebutuhanKeramikPerM2) .
                    ' pcs/M2',
            ],
        ];

        // ==================== BAGIAN 2: MENGHITUNG KEBUTUHAN PEREKAT (SEMEN) ====================

        // ============ STEP 6: Hitung Volume Adukan Kubik Per Kemasan ============
        $kubikSemenPerKemasan = $n($satuanKemasanSemen * (1 / $densitySemen));
        $kubikAirPerKemasan = $n($kubikSemenPerKemasan * 0.5);
        $volumeAdukanKubikPerKemasan = $n($kubikSemenPerKemasan + $kubikAirPerKemasan);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Volume Adukan Perekat Per Kemasan Semen',
            'formula' => 'Semen = Kemasan × (1/Densitas); Air = 50% × Semen; Volume = Semen + Air',
            'info' => 'Rasio campuran perekat 1 : 50% (Semen : Air)',
            'calculations' => [
                'Kubik Semen per Kemasan' =>
                    NumberHelper::format($satuanKemasanSemen) .
                    ' kg × (1 ÷ ' .
                    $densitySemen .
                    ') = ' .
                    NumberHelper::format($kubikSemenPerKemasan) .
                    ' M3',
                'Kubik Air per Kemasan (50%)' =>
                    '50% × ' .
                    NumberHelper::format($kubikSemenPerKemasan) .
                    ' = ' .
                    NumberHelper::format($kubikAirPerKemasan) .
                    ' M3',
                'Total Volume Adukan per Kemasan' =>
                    NumberHelper::format($kubikSemenPerKemasan) .
                    ' + ' .
                    NumberHelper::format($kubikAirPerKemasan) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanKubikPerKemasan) .
                    ' M3',
            ],
        ];

        // ============ STEP 7: Hitung Luas Acian Per 1 Kemasan ============
        $tebalAdukanMM = $n($tebalAdukan * 10);
        $tebalAdukanM = $n($tebalAdukanMM / 1000);
        $luasAcianPer1Kemasan = $n($volumeAdukanKubikPerKemasan / $tebalAdukanM);

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Luas Perekat Per 1 Kemasan Semen',
            'formula' => 'Luas = Volume Adukan per Kemasan / (Tebal Adukan mm / 1000)',
            'info' => 'Berapa M2 yang bisa direkatkan dengan 1 kemasan semen',
            'calculations' => [
                'Konversi Tebal Adukan' =>
                    NumberHelper::format($tebalAdukan) .
                    ' cm × 10 = ' .
                    NumberHelper::format($tebalAdukanMM) .
                    ' mm = ' .
                    NumberHelper::format($tebalAdukanM) .
                    ' m',
                'Luas per Kemasan' =>
                    NumberHelper::format($volumeAdukanKubikPerKemasan) .
                    ' M3 ÷ ' .
                    NumberHelper::format($tebalAdukanM) .
                    ' m = ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' M2',
            ],
        ];

        // ============ STEP 8: Hitung Koefisien Material Per 1 M2 ============
        $sakSemenPer1M2 = $n(1 / $luasAcianPer1Kemasan);
        $kgSemenPer1M2 = $n($satuanKemasanSemen / $luasAcianPer1Kemasan);
        $kubikSemenPer1M2 = $n($kubikSemenPerKemasan / $luasAcianPer1Kemasan);
        $literAirPer1M2 = $n(($kubikAirPerKemasan * 1000) / $luasAcianPer1Kemasan);
        $kubikAirPer1M2 = $n($kubikAirPerKemasan / $luasAcianPer1Kemasan);
        $kubikAirPerKemasanLiter = $n($kubikAirPerKemasan * 1000);

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Koefisien Material Per 1 M2',
            'formula' => 'Kebutuhan per M2 = Nilai per Kemasan / Luas per Kemasan',
            'info' =>
                'Normalisasi kebutuhan perekat per M2. Luas per Kemasan = ' .
                NumberHelper::format($luasAcianPer1Kemasan) .
                ' M2',
            'calculations' => [
                '--- Semen ---' => '',
                'Semen (kemasan/M2)' =>
                    '1 ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($sakSemenPer1M2) .
                    ' sak/M2',
                'Semen (kg/M2)' =>
                    NumberHelper::format($satuanKemasanSemen) .
                    ' kg ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($kgSemenPer1M2) .
                    ' kg/M2',
                'Semen (M3/M2)' =>
                    NumberHelper::format($kubikSemenPerKemasan) .
                    ' M3 ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($kubikSemenPer1M2) .
                    ' M3/M2',
                '--- Air ---' => '',
                'Air (liter/M2)' =>
                    '(' .
                    NumberHelper::format($kubikAirPerKemasan) .
                    ' × 1000) ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($kubikAirPerKemasanLiter) .
                    ' ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($literAirPer1M2) .
                    ' liter/M2',
                'Air (M3/M2)' =>
                    NumberHelper::format($kubikAirPerKemasan) .
                    ' M3 ÷ ' .
                    NumberHelper::format($luasAcianPer1Kemasan) .
                    ' = ' .
                    NumberHelper::format($kubikAirPer1M2) .
                    ' M3/M2',
            ],
        ];

        // ============ STEP 9: Hitung Kebutuhan Material Pekerjaan ============
        $sakSemenPekerjaan = $n($sakSemenPer1M2 * $luasBidang);
        $kgSemenPekerjaan = $n($kgSemenPer1M2 * $luasBidang);
        $kubikSemenPekerjaan = $n($kubikSemenPer1M2 * $luasBidang);
        $kubikAirPekerjaan = $n($kubikAirPer1M2 * $luasBidang);
        $literAirPekerjaan = $kubikAirPekerjaan * 1000;
        $volumeAdukanPekerjaan = $n($kubikSemenPekerjaan + $kubikAirPekerjaan);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Kebutuhan Perekat per Pekerjaan',
            'formula' => 'Kebutuhan per Pekerjaan = Kebutuhan per M2 × Luas Bidang',
            'info' =>
                'Mengalikan kebutuhan per M2 dengan total luas bidang (' . NumberHelper::format($luasBidang) . ' M2)',
            'calculations' => [
                '--- Semen ---' => '',
                'Semen (kemasan)' =>
                    NumberHelper::format($sakSemenPer1M2) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($sakSemenPekerjaan) .
                    ' kemasan',
                'Semen (kg)' =>
                    NumberHelper::format($kgSemenPer1M2) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kgSemenPekerjaan) .
                    ' kg',
                'Semen (M3)' =>
                    NumberHelper::format($kubikSemenPer1M2) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kubikSemenPekerjaan) .
                    ' M3',
                '--- Air ---' => '',
                'Air (M3)' =>
                    NumberHelper::format($kubikAirPer1M2) .
                    ' × ' .
                    NumberHelper::format($luasBidang) .
                    ' = ' .
                    NumberHelper::format($kubikAirPekerjaan) .
                    ' M3',
                'Air (liter)' =>
                    NumberHelper::format($kubikAirPekerjaan) .
                    ' M3 × 1000 = ' .
                    NumberHelper::format($literAirPekerjaan) .
                    ' liter',
                '--- Total ---' => '',
                'Volume Adukan Total' =>
                    NumberHelper::format($kubikSemenPekerjaan) .
                    ' + ' .
                    NumberHelper::format($kubikAirPekerjaan) .
                    ' = ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' M3',
            ],
        ];

        // ============ STEP 10: Hitung Harga ============
        $cementPrice = $n($cement->package_price ?? 0, 0);
        $ceramicPricePerDus = $n($ceramic->price_per_package ?? 0, 0);

        $totalCementPrice = $n($sakSemenPekerjaan * $cementPrice, 0);
        $totalCeramicPrice = $n($kebutuhanDusUtuhPekerjaan * $ceramicPricePerDus, 0);
        $grandTotal = $n($totalCementPrice + $totalCeramicPrice, 0);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Perhitungan Harga Komparasi',
            'formula' => 'Total Harga = Kebutuhan per Pekerjaan × Harga Satuan',
            'info' => 'Harga komparasi total material untuk seluruh pekerjaan',
            'calculations' => [
                '--- Semen ---' => '',
                'Harga Semen per Kemasan' => NumberHelper::currency($cementPrice),
                'Total Harga Semen' =>
                    NumberHelper::format($sakSemenPekerjaan) .
                    ' kemasan × ' .
                    NumberHelper::currency($cementPrice) .
                    ' = ' .
                    NumberHelper::currency($totalCementPrice),
                '--- Keramik ---' => '',
                'Harga Keramik per Dus' => NumberHelper::currency($ceramicPricePerDus),
                'Total Harga Keramik' =>
                    NumberHelper::format($kebutuhanDusUtuhPekerjaan) .
                    ' dus × ' .
                    NumberHelper::currency($ceramicPricePerDus) .
                    ' = ' .
                    NumberHelper::currency($totalCeramicPrice),
                '--- Grand Total ---' => '',
                'Grand Total' =>
                    NumberHelper::currency($totalCementPrice) .
                    ' + ' .
                    NumberHelper::currency($totalCeramicPrice) .
                    ' = ' .
                    NumberHelper::currency($grandTotal),
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

            // Perekat (Semen)
            'cement_sak' => $sakSemenPekerjaan,
            'cement_kg' => $kgSemenPekerjaan,
            'cement_m3' => $kubikSemenPekerjaan,
            'sand_m3' => 0,
            'sand_sak' => 0,
            'water_liters' => $literAirPekerjaan,
            'water_m3' => $kubikAirPekerjaan,
            'mortar_volume_m3' => $volumeAdukanPekerjaan,

            // Prices
            'cement_price_per_sak' => $cementPrice,
            'total_cement_price' => $totalCementPrice,
            'sand_price_per_m3' => 0,
            'total_sand_price' => 0,
            'ceramic_price_per_package' => $ceramicPricePerDus,
            'total_ceramic_price' => $totalCeramicPrice,
            'grand_total' => $grandTotal,

            // Additional info
            'total_area' => $luasBidang,
        ];

        return $trace;
    }
}
