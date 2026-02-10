<?php

namespace App\Services\Formula;

use App\Helpers\NumberHelper;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

/**
 * Formula - Perhitungan Material Bata Rollag (1/4 Bata)
 * Dibuat sesuai ketentuan perhitungan volume adukan pekerjaan
 */
class BrickRollagFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'brick_rollag';
    }

    public static function getName(): string
    {
        return 'Pasang Pondasi Bata Merah (Rollag)';
    }

    public static function getDescription(): string
    {
        return 'Menghitung pemasangan Bata Rollag dengan input tingkat adukan dan tingkat bata.';
    }

    public static function getMaterialRequirements(): array
    {
        return ['brick', 'cement', 'sand'];
    }

    public function validate(array $params): bool
    {
        // Validasi input standar + input baru
        $required = [
            'wall_length',
            'installation_type_id',
            'mortar_formula_id',
            'layer_count', // Input tunggal untuk tingkat
        ];

        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] < 0) {
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
        $panjangRollag = $n($params['wall_length']);
        $tebalAdukan = $n($params['mortar_thickness'] ?? 1.0); // cm
        $jumlahTingkat = $n($params['layer_count'] ?? 1);

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Rollag' => $panjangRollag . ' m',
                'Tebal Adukan' => $tebalAdukan . ' cm',
                'Jumlah Tingkat' => $jumlahTingkat,
            ],
        ];

        // ============ STEP 2: Load Data dari Database ============
        // Note: installation_type_id dan mortar_formula_id digunakan untuk mengambil data tapi tidak langsung di rumus baru ini
        // kecuali ratio pasir (mortar formula)
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        // Dimensi dalam cm
        $panjangBata = $n($brick->dimension_length ?? 19.2);
        $lebarBata = $n($brick->dimension_width ?? 9);
        $tinggiBata = $n($brick->dimension_height ?? 8);

        // Lebar rollag mengikuti panjang bata
        $lebarRollag = $n($panjangBata); // cm

        $beratSemenPerSak = $n($cement && $cement->package_weight_net > 0 ? $cement->package_weight_net : 50);

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data dari Database',
            'calculations' => [
                'Dimensi Bata (P x L x T)' => "$panjangBata x $lebarBata x $tinggiBata cm",
                'Lebar Rollag' => $lebarRollag . ' cm',
                'Berat Semen per Sak' => $beratSemenPerSak . ' kg',
                'Rasio Mortar' => $mortarFormula->cement_ratio . ':' . $mortarFormula->sand_ratio,
            ],
        ];

        // ============ STEP 3: Hitung baris horizontal adukan ============
        // Rumus: (Panjang Rollag - (Tinggi bata / 100)) / ((Tinggi Bata + Tebal adukan) / 100) (jika desimal bulatkan keatas)

        $pembilang = $n($panjangRollag - $tinggiBata / 100);
        $penyebut = $n(($tinggiBata + $tebalAdukan) / 100);

        $barisHorizontalAdukanRaw = 0;
        if ($penyebut > 0) {
            $barisHorizontalAdukanRaw = $n($pembilang / $penyebut);
        }

        $barisHorizontalAdukan = ceil($barisHorizontalAdukanRaw);

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Baris Horizontal Adukan',
            'formula' => '(Panjang Rollag - (Tinggi bata / 100)) / ((Tinggi Bata + Tebal adukan) / 100)',
            'info' => 'Dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "($panjangRollag - ($tinggiBata / 100)) / (($tinggiBata + $tebalAdukan) / 100)",
                'Raw' => NumberHelper::format($barisHorizontalAdukanRaw),
                'Hasil' => $barisHorizontalAdukan,
            ],
        ];

        // ============ STEP 4: Hitung kolom vertikal bata ============
        // Rumus: ((Panjang Rollag - (Tinggi bata / 100)) / ((Tinggi bata + Tebal adukan) / 100)) + 1 (jika desimal bulatkan keatas)

        $kolomVertikalBataRaw = 0;
        if ($penyebut > 0) {
            $kolomVertikalBataRaw = $n($pembilang / $penyebut + 1);
        }

        $kolomVertikalBata = ceil($kolomVertikalBataRaw);

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Kolom Vertikal Bata',
            'formula' => '((Panjang Rollag - (Tinggi bata / 100)) / ((Tinggi bata + Tebal adukan) / 100)) + 1',
            'info' => 'Dibulatkan keatas',
            'calculations' => [
                'Perhitungan' => "(($panjangRollag - ($tinggiBata / 100)) / (($tinggiBata + $tebalAdukan) / 100)) + 1",
                'Raw' => NumberHelper::format($kolomVertikalBataRaw),
                'Hasil' => $kolomVertikalBata,
            ],
        ];

        // ============ STEP 5: Hitung Jumlah Bata ============
        // Rumus: Jumlah tingkat bata * Kolom vertikal bata
        $jumlahBata = $jumlahTingkat * $kolomVertikalBata;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Jumlah Bata',
            'formula' => 'Jumlah tingkat * Kolom vertikal bata',
            'calculations' => [
                'Perhitungan' => "$jumlahTingkat * $kolomVertikalBata",
                'Hasil' => NumberHelper::format($jumlahBata),
            ],
        ];

        // ============ STEP 6: Hitung Panjang Adukan ============
        // Rumus: Panjang bata * (baris horizontal adukan / 100) * jumlah tingkat adukan
        // Interpretasi: (Panjang bata * baris * tingkat) / 100 -> Meter

        $panjangAdukan = $n($panjangBata * ($barisHorizontalAdukan / 100) * $jumlahTingkat);

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Panjang Adukan',
            'formula' => 'Panjang bata * (baris horizontal adukan / 100) * jumlah tingkat',
            'calculations' => [
                'Perhitungan' => "$panjangBata * ($barisHorizontalAdukan / 100) * $jumlahTingkat",
                'Hasil' => NumberHelper::format($panjangAdukan) . ' m',
            ],
        ];

        // ============ STEP 7: Hitung Luas Adukan ============
        // Rumus: Panjang adukan * tebal adukan / 100
        $luasAdukan = $n($panjangAdukan * ($tebalAdukan / 100));

        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Luas Adukan',
            'formula' => 'Panjang adukan * tebal adukan / 100',
            'calculations' => [
                'Perhitungan' => "$panjangAdukan * $tebalAdukan / 100",
                'Hasil' => NumberHelper::format($luasAdukan) . ' M2',
            ],
        ];

        // ============ STEP 8: Hitung Luas Rollag ============
        // Rumus: Panjang Rollag * lebar rollag
        $luasRollag = $n($panjangRollag * ($lebarRollag / 100));

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Luas Rollag',
            'formula' => 'Panjang Rollag * lebar rollag',
            'info' => 'Lebar rollag dikonversi ke meter',
            'calculations' => [
                'Perhitungan' => "$panjangRollag * ($lebarRollag / 100)",
                'Hasil' => NumberHelper::format($luasRollag) . ' M2',
            ],
        ];

        // ============ STEP 9: Hitung Volume Adukan Pekerjaan ============
        // Rumus: (Luas Adukan * (lebar bata / 100)) + ((Luas Rollag * (tebal adukan / 100)) * Jumlah tingkat bata)

        $part1 = $n($luasAdukan * ($lebarBata / 100));
        $part2 = $n($luasRollag * ($tebalAdukan / 100) * $jumlahTingkat);

        $volumeAdukanPekerjaan = $n($part1 + $part2);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Volume Adukan Pekerjaan',
            'formula' =>
                '(Luas Adukan * (lebar bata / 100)) + ((Luas Rollag * (tebal adukan / 100)) * Jumlah tingkat bata)',
            'calculations' => [
                'Part 1' => "$luasAdukan * ($lebarBata / 100) = " . NumberHelper::format($part1),
                'Part 2' => "($luasRollag * ($tebalAdukan / 100)) * $jumlahTingkat = " . NumberHelper::format($part2),
                'Hasil' => NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            ],
        ];

        // ============ STEP 10: Komposisi Volume Adukan (1 M3) ============
        $densitySemen = 1440;

        // kubik semen
        $kubikSemenPerSak = $n($beratSemenPerSak * (1 / $densitySemen));

        // kubik pasir
        $ratioPasir = $n($mortarFormula->sand_ratio);
        $kubikPasirPerSakSemen = $n($kubikSemenPerSak * $ratioPasir);

        // kubik air (asumsi 30% dari volume padat)
        $kubikAirPerSakSemen = $n(0.3 * ($kubikSemenPerSak + $kubikPasirPerSakSemen));

        // kebutuhan air
        $kebutuhanAirLiterPerSakSemen = $n($kubikAirPerSakSemen * 1000);

        // Volume adukan yield per sak
        $volumeAdukanPerSakSemen = $n($kubikSemenPerSak + $kubikPasirPerSakSemen + $kubikAirPerSakSemen);

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Analisa Campuran per 1 Sak Semen',
            'calculations' => [
                'Kubik Semen' => NumberHelper::format($kubikSemenPerSak) . ' M3',
                'Kubik Pasir' => NumberHelper::format($kubikPasirPerSakSemen) . ' M3',
                'Kubik Air' => NumberHelper::format($kubikAirPerSakSemen) . ' M3',
                'Total Volume Adukan (Yield)' => NumberHelper::format($volumeAdukanPerSakSemen) . ' M3',
            ],
        ];

        // ============ STEP 11: Menghitung kebutuhan volume adukan untuk 1 M3 ============
        if ($volumeAdukanPerSakSemen > 0) {
            $sakSemen1M3 = $n(1 / $volumeAdukanPerSakSemen);
        } else {
            $sakSemen1M3 = 0;
        }

        $kgSemen1M3 = $n($beratSemenPerSak / $volumeAdukanPerSakSemen);
        $kubikSemen1M3 = $n($kubikSemenPerSak / $volumeAdukanPerSakSemen);
        $sakPasir1M3 = $n($ratioPasir / $volumeAdukanPerSakSemen); // Asumsi sak pasir mengikuti rasio semen
        $kubikPasir1M3 = $n($kubikPasirPerSakSemen / $volumeAdukanPerSakSemen);
        $literAir1M3 = $n($kebutuhanAirLiterPerSakSemen / $volumeAdukanPerSakSemen);
        $kubikAir1M3 = $n($kubikAirPerSakSemen / $volumeAdukanPerSakSemen);

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Koefisien Material per 1 M3 Adukan',
            'calculations' => [
                'Sak Semen 1 M3' => NumberHelper::format($sakSemen1M3) . ' sak',
                'Kg Semen 1 M3' => NumberHelper::format($kgSemen1M3) . ' kg',
                'Kubik Pasir 1 M3' => NumberHelper::format($kubikPasir1M3) . ' M3',
                'Liter Air 1 M3' => NumberHelper::format($literAir1M3) . ' liter',
            ],
        ];

        // ============ STEP 12: Menghitung kebutuhan volume adukan pekerjaan ============
        $sakSemenPekerjaan = $n($sakSemen1M3 * $volumeAdukanPekerjaan);
        $kgSemenPekerjaan = $n($kgSemen1M3 * $volumeAdukanPekerjaan);
        $kubikSemenPekerjaan = $n($kubikSemen1M3 * $volumeAdukanPekerjaan);

        $sakPasirPekerjaan = $n($sakPasir1M3 * $volumeAdukanPekerjaan);
        $kubikPasirPekerjaan = $n($kubikPasir1M3 * $volumeAdukanPekerjaan);

        $kubikAirPekerjaan = $n($kubikAir1M3 * $volumeAdukanPekerjaan);
        $literAirPekerjaan = $kubikAirPekerjaan * 1000;

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Kebutuhan Material Pekerjaan',
            'info' => 'Volume Pekerjaan: ' . NumberHelper::format($volumeAdukanPekerjaan) . ' M3',
            'calculations' => [
                'Semen (Sak)' => NumberHelper::format($sakSemenPekerjaan),
                'Semen (Kg)' => NumberHelper::format($kgSemenPekerjaan),
                'Pasir (M3)' => NumberHelper::format($kubikPasirPekerjaan),
                'Air (Liter)' => NumberHelper::format($literAirPekerjaan),
            ],
        ];

        // ============ Final Result Calculation ============

        // Harga
        $brickPrice = $n($brick->price_per_piece ?? 0, 0);
        $cementPrice = $n($cement->package_price ?? 0, 0); // Harga per sak

        $sandPricePerM3 = $n($sand->comparison_price_per_m3 ?? 0, 0);
        if ($sandPricePerM3 == 0 && $sand->package_price && $sand->package_volume > 0) {
            $sandPricePerM3 = $n($sand->package_price / $sand->package_volume, 0);
        }

        $totalBrickPrice = $n($jumlahBata * $brickPrice, 0);
        $totalCementPrice = $n($sakSemenPekerjaan * $cementPrice, 0);
        $totalSandPrice = $n($kubikPasirPekerjaan * $sandPricePerM3, 0);

        $grandTotal = $n($totalBrickPrice + $totalCementPrice + $totalSandPrice, 0);

        $trace['final_result'] = [
            'total_bricks' => $jumlahBata,
            'cement_sak' => $sakSemenPekerjaan,
            'cement_kg' => $kgSemenPekerjaan,
            'cement_m3' => $kubikSemenPekerjaan,
            'sand_m3' => $kubikPasirPekerjaan,
            'sand_sak' => $sakPasirPekerjaan,
            'water_liters' => $literAirPekerjaan,

            // Prices
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
