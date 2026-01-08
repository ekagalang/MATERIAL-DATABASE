<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

/**
 * Formula Trial - Perhitungan Material Bata
 * Dibuat sesuai ketentuan perhitungan volume adukan pekerjaan
 */
class BrickFullFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'brick_full';
    }

    public static function getName(): string
    {
        return 'Pasang Dinding Bata Merah (1 Bata)';
    }

    public static function getDescription(): string
    {
        return 'Menghitung pemasangan Bata 1 dengan metode Volume Mortar, termasuk strip adukan di sisi kiri dan bawah.';
    }

    public function validate(array $params): bool
    {
        $required = ['wall_length', 'wall_height', 'installation_type_id', 'mortar_formula_id'];

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
        $panjangDinding = $params['wall_length'];
        $tinggiDinding = $params['wall_height'];
        $tebalAdukan = $params['mortar_thickness'] ?? 1.0;

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $panjangDinding . ' m',
                'Tinggi Dinding' => $tinggiDinding . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
            ],
        ];

        // ============ STEP 2: Load Data dari Database ============
        $installationType = BrickInstallationType::findOrFail($params['installation_type_id']);
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        $panjangBata = $brick->dimension_length ?? 19.2;
        $lebarBata = $brick->dimension_width ?? 9;
        $tinggiBata = $brick->dimension_height ?? 8;
        $beratSemenPerSak = $cement && $cement->package_weight_net > 0 ? $cement->package_weight_net : 50;

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data dari Database',
            'calculations' => [
                'Dimensi Bata' => "$panjangBata × $lebarBata × $tinggiBata cm",
                'Berat Semen per Sak' => $beratSemenPerSak . ' kg',
                'Rasio Mortar' => $mortarFormula->cement_ratio . ':' . $mortarFormula->sand_ratio,
            ],
        ];

        // ============ STEP 3: Hitung kolom vertikal bata ============
        // kolom vertikal bata = (tinggi dinding - (tebal adukan/100)) / ((tinggi bata + tebal adukan)/100). (jika hasilnya desimal maka dibulatkan keatas)
        $kolomVertikalBataRaw = ($tinggiDinding - $tebalAdukan / 100) / (($tinggiBata + $tebalAdukan) / 100);
        $decimal = $kolomVertikalBataRaw - floor($kolomVertikalBataRaw);
        $kolomVertikalBata = floor($kolomVertikalBataRaw);
        if ($decimal > 0) {
            $kolomVertikalBata = ceil($kolomVertikalBataRaw);
        }

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Kolom Vertikal Bata',
            'formula' => 'tinggi dinding / ((tinggi bata + tebal adukan)/100)',
            'info' => 'Jika hasilnya desimal, dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "$tinggiDinding / (($tinggiBata + $tebalAdukan) / 100)",
                'Raw' => NumberHelper::format($kolomVertikalBataRaw),
                'Desimal' => NumberHelper::format($decimal),
                'Hasil' => $kolomVertikalBata . ' baris',
            ],
        ];

        // ============ STEP 4: Hitung baris horizontal bata ============
        // baris horizontal bata = Panjang dinding / ((Panjang bata + tebal adukan)/100). (jika hasilnya desimal maka dibulatkan keatas)
        $barisHorizontalBataRaw = ($panjangDinding - $tebalAdukan / 100) / (($lebarBata + $tebalAdukan) / 100);
        $decimal = $barisHorizontalBataRaw - floor($barisHorizontalBataRaw);
        $barisHorizontalBata = floor($barisHorizontalBataRaw);
        if ($decimal > 0) {
            $barisHorizontalBata = ceil($barisHorizontalBataRaw);
        }

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Baris Horizontal Bata',
            'formula' => '(Panjang dinding - (tebal adukan / 100))/ ((Panjang bata + tebal adukan)/100)',
            'info' => 'Jika hasilnya desimal, dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "($panjangDinding - ($tebalAdukan / 100)) / (($lebarBata + $tebalAdukan) / 100)",
                'Raw' => NumberHelper::format($barisHorizontalBataRaw),
                'Desimal' => NumberHelper::format($decimal),
                'Hasil' => $barisHorizontalBata . ' kolom',
            ],
        ];

        // ============ STEP 5: Hitung Jumlah Bata ============
        // Jumlah Bata = baris horizontal bata * kolom vertikal bata
        $jumlahBata = $barisHorizontalBata * $kolomVertikalBata;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Jumlah Bata',
            'formula' => 'baris horizontal bata × kolom vertikal bata',
            'calculations' => [
                'Perhitungan' => "$barisHorizontalBata × $kolomVertikalBata",
                'Hasil' => number_format($jumlahBata) . ' buah',
            ],
        ];

        // ============ STEP 6: Hitung baris horizontal adukan ============
        // baris horizontal adukan = (tinggi dinding / ((tinggi bata + tebal adukan) / 100)) + 1. (jika hasilnya desimal maka dibulatkan keatas)
        $barisHorizontalAdukanRaw = ($tinggiDinding - $tebalAdukan / 100) / (($tinggiBata + $tebalAdukan) / 100) + 1;
        $decimal = $barisHorizontalAdukanRaw - floor($barisHorizontalAdukanRaw);
        $barisHorizontalAdukan = floor($barisHorizontalAdukanRaw);
        if ($decimal > 0) {
            $barisHorizontalAdukan = ceil($barisHorizontalAdukanRaw);
        }

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Baris Horizontal Adukan',
            'formula' => '(tinggi dinding - (tebal adukan / 100)) / ((tinggi bata + tebal adukan) / 100) + 1',
            'info' => 'Jika hasilnya desimal, dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "($tinggiDinding - ($tebalAdukan / 100)) / (($tinggiBata + $tebalAdukan) / 100) + 1",
                'Raw' => NumberHelper::format($barisHorizontalAdukanRaw),
                'Desimal' => NumberHelper::format($decimal),
                'Hasil' => $barisHorizontalAdukan . ' baris',
            ],
        ];

        // ============ STEP 7: Hitung kolom vertikal adukan ============
        // kolom vertikal adukan = (Panjang dinding / ((Panjang bata + tebal adukan) / 100)) + 1. (jika hasilnya desimal maka dibulatkan keatas)
        $kolomVertikalAdukanRaw = ($panjangDinding - $tebalAdukan / 100) / (($lebarBata + $tebalAdukan) / 100) + 1;
        $decimal = $kolomVertikalAdukanRaw - floor($kolomVertikalAdukanRaw);
        $kolomVertikalAdukan = floor($kolomVertikalAdukanRaw);
        if ($decimal > 0) {
            $kolomVertikalAdukan = ceil($kolomVertikalAdukanRaw);
        }

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Kolom Vertikal Adukan',
            'formula' => '(Panjang dinding - (tebal adukan / 100)) / ((Lebar bata + tebal adukan) / 100) + 1',
            'info' => 'Jika hasilnya desimal, dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "($panjangDinding - ($tebalAdukan / 100)) / (($lebarBata + $tebalAdukan) / 100) + 1",
                'Raw' => NumberHelper::format($kolomVertikalAdukanRaw),
                'Desimal' => NumberHelper::format($decimal),
                'Hasil' => $kolomVertikalAdukan . ' kolom',
            ],
        ];

        // ============ STEP 8: Hitung Panjang Adukan ============
        // Panjang Adukan = (baris horizontal adukan * Panjang dinding) +
        //                  (kolom vertical adukan * (kolom vertikal jumlah bata * (tinggi bata / 100))) +
        //                  ((Kolom vertikal jumlah bata / 2) * (tinggi bata / 100))
        $part1 = $barisHorizontalAdukan * $panjangDinding;
        $part2 = $kolomVertikalAdukan * ($kolomVertikalBata * ($tinggiBata / 100));
        $part3 = ($kolomVertikalBata / 2) * ($tinggiBata / 100);
        $panjangAdukan = $part1 + $part2 + $part3;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Panjang Adukan',
            'formula' =>
                '(baris horizontal adukan × Panjang dinding) + (kolom vertical adukan × (kolom vertikal bata × (tinggi bata / 100))) + ((kolom vertikal bata / 2) × (tinggi bata / 100))',
            'calculations' => [
                'Part 1' => "($barisHorizontalAdukan × $panjangDinding) = " . NumberHelper::format($part1) . ' m',
                'Part 2' =>
                    "($kolomVertikalAdukan × ($kolomVertikalBata × ($tinggiBata / 100))) = " .
                    NumberHelper::format($part2) .
                    ' m',
                'Part 3' => "(($kolomVertikalBata / 2) × ($tinggiBata / 100)) = " . NumberHelper::format($part3) . ' m',
                'Hasil Panjang Adukan' => NumberHelper::format($panjangAdukan) . ' m',
            ],
        ];

        // ============ STEP 9: Hitung Luas Adukan ============
        // Luas Adukan = Panjang adukan * tebal adukan / 100
        $luasAdukan = ($panjangAdukan * $tebalAdukan) / 100;

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Luas Adukan',
            'formula' => 'Panjang adukan × tebal adukan / 100',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($panjangAdukan) . " × $tebalAdukan / 100",
                'Hasil Luas Adukan' => NumberHelper::format($luasAdukan) . ' M2',
            ],
        ];

        // ============ STEP 10: Hitung Volume Adukan Pekerjaan ============
        // Volume adukan pekerjaan = Luas Adukan * lebar bata / 100
        $volumeAdukanPekerjaan = $luasAdukan * ($panjangBata / 100);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Volume Adukan Pekerjaan',
            'formula' => 'Luas Adukan × panjang bata / 100',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($luasAdukan) . " × $panjangBata / 100",
                'Hasil Volume Adukan Pekerjaan' => NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 11: Hitung kubik semen ============
        // kubik semen = berat 1 sak semen sesuai kemasan * (1 / density semen(1440))
        $densitySemen = 1440;
        $kubikSemen = $beratSemenPerSak * (1 / $densitySemen);

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Kubik Semen',
            'formula' => 'berat 1 sak semen × (1 / density semen)',
            'calculations' => [
                'Density Semen' => $densitySemen . ' kg/M3',
                'Perhitungan' => "$beratSemenPerSak × (1 / $densitySemen)",
                'Hasil Kubik Semen' => NumberHelper::format($kubikSemen) . ' M3',
            ],
        ];

        // ============ STEP 12: Hitung kubik pasir ============
        // kubik pasir = kubik semen * ratio pasir terhadap semen (1 semen : 3 pasir)
        $ratioPasir = $mortarFormula->sand_ratio;
        $kubikPasir = $kubikSemen * $ratioPasir;

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Kubik Pasir',
            'formula' => 'kubik semen × ratio pasir',
            'calculations' => [
                'Ratio Pasir' => $ratioPasir,
                'Perhitungan' => NumberHelper::format($kubikSemen) . " × $ratioPasir",
                'Hasil Kubik Pasir' => NumberHelper::format($kubikPasir) . ' M3',
            ],
        ];

        // ============ STEP 13: Hitung kubik air ============
        // kubik air = 0.3 * (kubik semen + kubik pasir)
        $kubikAir = 0.3 * ($kubikSemen + $kubikPasir);

        $trace['steps'][] = [
            'step' => 13,
            'title' => 'Kubik Air',
            'formula' => '0.3 × (kubik semen + kubik pasir)',
            'calculations' => [
                'Perhitungan' =>
                    '0.3 × (' . NumberHelper::format($kubikSemen) . ' + ' . NumberHelper::format($kubikPasir) . ')',
                'Hasil Kubik Air' => NumberHelper::format($kubikAir) . ' M3',
            ],
        ];

        // ============ STEP 14: Hitung kebutuhan air ============
        // kebutuhan air = kubik air * 1000
        $kebutuhanAir = $kubikAir * 1000;

        $trace['steps'][] = [
            'step' => 14,
            'title' => 'Kebutuhan Air',
            'formula' => 'kubik air × 1000',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($kubikAir) . ' × 1000',
                'Hasil Kebutuhan Air' => NumberHelper::format($kebutuhanAir) . ' liter',
            ],
        ];

        // ============ STEP 15: Hitung Volume Adukan ============
        // Volume adukan = kubik semen + kubik pasir + kubik air
        $volumeAdukan = $kubikSemen + $kubikPasir + $kubikAir;

        $trace['steps'][] = [
            'step' => 15,
            'title' => 'Volume Adukan',
            'formula' => 'kubik semen + kubik pasir + kubik air',
            'calculations' => [
                'Perhitungan' =>
                    NumberHelper::format($kubikSemen) .
                    ' + ' .
                    NumberHelper::format($kubikPasir) .
                    ' + ' .
                    NumberHelper::format($kubikAir),
                'Hasil Volume Adukan' => NumberHelper::format($volumeAdukan) . ' M3',
            ],
        ];

        // Guard clause: Pastikan volume adukan tidak 0 untuk mencegah division by zero
        if ($volumeAdukan <= 0) {
            throw new \Exception(
                'Volume adukan tidak valid (bernilai 0 atau negatif). Periksa data material (semen, pasir) dan formula mortar.',
            );
        }

        // ============ STEP 16: Kebutuhan untuk 1 M3 ============
        $sakSemen1M3 = 1 / $volumeAdukan;
        $kgSemen1M3 = $beratSemenPerSak / $volumeAdukan;
        $kubikSemen1M3 = $kubikSemen / $volumeAdukan;
        $sakPasir1M3 = 3 / $volumeAdukan;
        $kubikPasir1M3 = $kubikPasir / $volumeAdukan;
        $literAir1M3 = $kebutuhanAir / $volumeAdukan;
        $kubikAir1M3 = $kubikAir / $volumeAdukan;

        $trace['steps'][] = [
            'step' => 16,
            'title' => 'Kebutuhan Volume Adukan untuk 1 M3',
            'calculations' => [
                'Sak Semen 1 M3' =>
                    '1 / ' . NumberHelper::format($volumeAdukan) . ' = ' . NumberHelper::format($sakSemen1M3) . ' sak',
                'Kg Semen 1 M3' =>
                    "$beratSemenPerSak / " .
                    NumberHelper::format($volumeAdukan) .
                    ' = ' .
                    NumberHelper::format($kgSemen1M3) .
                    ' kg',
                'Kubik Semen 1 M3' =>
                    NumberHelper::format($kubikSemen) .
                    ' / ' .
                    NumberHelper::format($volumeAdukan) .
                    ' = ' .
                    NumberHelper::format($kubikSemen1M3) .
                    ' M3',
                'Sak Pasir 1 M3' =>
                    '3 / ' . NumberHelper::format($volumeAdukan) . ' = ' . NumberHelper::format($sakPasir1M3) . ' sak',
                'Kubik Pasir 1 M3' =>
                    NumberHelper::format($kubikPasir) .
                    ' / ' .
                    NumberHelper::format($volumeAdukan) .
                    ' = ' .
                    NumberHelper::format($kubikPasir1M3) .
                    ' M3',
                'Liter Air 1 M3' =>
                    NumberHelper::format($kebutuhanAir) .
                    ' / ' .
                    NumberHelper::format($volumeAdukan) .
                    ' = ' .
                    NumberHelper::format($literAir1M3) .
                    ' liter',
                'Kubik Air 1 M3' =>
                    NumberHelper::format($kubikAir) .
                    ' / ' .
                    NumberHelper::format($volumeAdukan) .
                    ' = ' .
                    NumberHelper::format($kubikAir1M3) .
                    ' M3',
            ],
        ];

        // ============ STEP 17: Kebutuhan Volume Adukan Pekerjaan ============
        $sakSemenPekerjaan = $sakSemen1M3 * $volumeAdukanPekerjaan;
        $kgSemenPekerjaan = $kgSemen1M3 * $volumeAdukanPekerjaan;
        $kubikSemenPekerjaan = $kubikSemen1M3 * $volumeAdukanPekerjaan;
        $sakPasirPekerjaan = $sakPasir1M3 * $volumeAdukanPekerjaan;
        $kubikPasirPekerjaan = $kubikPasir1M3 * $volumeAdukanPekerjaan;
        $literAirPekerjaan = $literAir1M3 * $volumeAdukanPekerjaan;
        $kubikAirPekerjaan = $kubikAir1M3 * $volumeAdukanPekerjaan;

        $trace['steps'][] = [
            'step' => 17,
            'title' => 'Kebutuhan Volume Adukan Pekerjaan',
            'info' => 'Volume Adukan Pekerjaan = ' . NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            'calculations' => [
                'Sak Semen Pekerjaan' =>
                    NumberHelper::format($sakSemen1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($sakSemenPekerjaan) .
                    ' sak',
                'Kg Semen Pekerjaan' =>
                    NumberHelper::format($kgSemen1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($kgSemenPekerjaan) .
                    ' kg',
                'Kubik Semen Pekerjaan' =>
                    NumberHelper::format($kubikSemen1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($kubikSemenPekerjaan) .
                    ' M3',
                'Sak Pasir Pekerjaan' =>
                    NumberHelper::format($sakPasir1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($sakPasirPekerjaan) .
                    ' sak',
                'Kubik Pasir Pekerjaan' =>
                    NumberHelper::format($kubikPasir1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($kubikPasirPekerjaan) .
                    ' M3',
                'Liter Air Pekerjaan' =>
                    NumberHelper::format($literAir1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($literAirPekerjaan) .
                    ' liter',
                'Kubik Air Pekerjaan' =>
                    NumberHelper::format($kubikAir1M3) .
                    ' × ' .
                    NumberHelper::format($volumeAdukanPekerjaan) .
                    ' = ' .
                    NumberHelper::format($kubikAirPekerjaan) .
                    ' M3',
            ],
        ];

        // ============ Pembulatan Final ============
        $totalCementSak = round($sakSemenPekerjaan, 2);

        $decimal = $kgSemenPekerjaan - floor($kgSemenPekerjaan);
        if ($decimal >= 0.5) {
            $cementKg = ceil($kgSemenPekerjaan);
        } else {
            $cementKg = floor($kgSemenPekerjaan);
        }

        $decimalWater = $literAirPekerjaan - floor($literAirPekerjaan);
        if ($decimalWater > 0.5) {
            $waterLiters = floor($literAirPekerjaan);
        } else {
            $waterLiters = round($literAirPekerjaan);
        }

        // ============ Hitung Harga ============
        $cementM3 = $cementKg / $densitySemen;

        // Sand sak calculation - gunakan hasil perhitungan yang sudah ada
        $sandSakUnit = $sakPasirPekerjaan;

        $brickPrice = $brick->price_per_piece ?? 0;
        $cementPrice = $cement->package_price ?? 0;
        $sandPricePerM3 = $sand->comparison_price_per_m3 ?? 0;
        if ($sandPricePerM3 == 0 && $sand->package_price && $sand->package_volume > 0) {
            $sandPricePerM3 = $sand->package_price / $sand->package_volume;
        }

        $totalBrickPrice = $jumlahBata * $brickPrice;
        $totalCementPrice = $totalCementSak * $cementPrice;
        $totalSandPrice = $kubikPasirPekerjaan * $sandPricePerM3;
        $grandTotal = $totalBrickPrice + $totalCementPrice + $totalSandPrice;

        // ============ Final Result ============
        $trace['final_result'] = [
            'total_bricks' => $jumlahBata,
            'cement_kg' => $cementKg,
            'cement_sak' => $totalCementSak,
            'cement_m3' => $cementM3,
            'sand_m3' => $kubikPasirPekerjaan,
            'sand_sak' => $sandSakUnit,
            'water_liters' => $waterLiters,
            // Harga
            'brick_price_per_piece' => $brickPrice,
            'total_brick_price' => $totalBrickPrice,
            'cement_price_per_sak' => $cementPrice,
            'total_cement_price' => $totalCementPrice,
            'sand_price_per_m3' => $sandPricePerM3,
            'total_sand_price' => $totalSandPrice,
            'grand_total' => $grandTotal,
        ];

        return $trace;
    }
}
