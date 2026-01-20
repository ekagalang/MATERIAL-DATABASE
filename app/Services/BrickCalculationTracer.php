<?php

namespace App\Services;

use App\Helpers\NumberHelper;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

/**
 * Trace setiap step perhitungan seperti di Excel
 * Untuk debugging dan verifikasi rumus
 *
 * Mode 1: Professional (Volume Mortar) - Base fitur utama
 */
class BrickCalculationTracer
{
    /**
     * Trace Mode 1: Professional (Volume Mortar)
     * Base fitur utama untuk perhitungan material bata
     */
    public static function traceProfessionalMode(array $params): array
    {
        $trace = [];
        $trace['mode'] = 'Mode 1: PROFESSIONAL (Volume Mortar)';
        $trace['steps'] = [];

        // Step 1: Input Parameters
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $wallLength . ' m',
                'Tinggi Dinding' => $wallHeight . ' m',
                'Tebal Adukan' => $mortarThickness . ' cm',
            ],
        ];

        // Step 2: Luas Dinding
        $wallArea = $wallLength * $wallHeight;

        // Selalu gunakan lapisan tambahan di sisi kiri & bawah
        $additionalLayerThickness = $mortarThickness / 100; // Convert cm to m

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Luas Dinding',
            'formula' => 'Luas = Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$wallLength × $wallHeight",
                'Luas Total Bidang' => NumberHelper::format($wallArea) . ' M2',
            ],
        ];

        // Step 2b: Luas Efektif untuk Bata (dengan lapisan tambahan di kiri & bawah)
        $effectiveLength = $wallLength - $additionalLayerThickness;
        $effectiveHeight = $wallHeight - $additionalLayerThickness;
        $effectiveArea = $effectiveLength * $effectiveHeight;

        $trace['steps'][] = [
            'step' => '2b',
            'title' => 'Luas Efektif untuk Bata',
            'formula' => 'Luas Efektif = (Panjang - tebal) × (Tinggi - tebal)',
            'info' => 'Dikurangi area strip adukan di sisi kiri dan bawah',
            'calculations' => [
                'Panjang Efektif' =>
                    "$wallLength - " .
                    NumberHelper::format($additionalLayerThickness) .
                    ' = ' .
                    NumberHelper::format($effectiveLength) .
                    ' m',
                'Tinggi Efektif' =>
                    "$wallHeight - " .
                    NumberHelper::format($additionalLayerThickness) .
                    ' = ' .
                    NumberHelper::format($effectiveHeight) .
                    ' m',
                'Luas Efektif' =>
                    NumberHelper::format($effectiveLength) .
                    ' × ' .
                    NumberHelper::format($effectiveHeight) .
                    ' = ' .
                    NumberHelper::format($effectiveArea) .
                    ' M2',
                'Pengurangan Luas' => NumberHelper::format($wallArea - $effectiveArea) . ' M2 (area strip adukan)',
            ],
        ];

        // Use effective area for brick calculation
        $wallAreaForBricks = $effectiveArea;

        // Step 3: Load Data
        $installationType = BrickInstallationType::findOrFail($params['installation_type_id']);
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        $brickLength = $brick->dimension_length ?? 19.2;
        $brickWidth = $brick->dimension_width ?? 9;
        $brickHeight = $brick->dimension_height ?? 8;
        $cementWeightPerSak = $cement && $cement->package_weight_net > 0 ? $cement->package_weight_net : 50;

        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Data dari Database',
            'calculations' => [
                'Jenis Pemasangan' => $installationType->name,
                'Dimensi Bata' => "$brickLength × $brickWidth × $brickHeight cm",
                'Formula Mortar' => $mortarFormula->cement_ratio . ':' . $mortarFormula->sand_ratio,
                'Berat Semen per Sak' => $cementWeightPerSak . ' kg',
            ],
        ];

        // Step 4: Hitung Bricks per M2
        $bricksPerSqm = $installationType->calculateBricksPerSqm(
            $brickLength,
            $brickWidth,
            $brickHeight,
            $mortarThickness,
        );

        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Bata per M2',
            'formula' => '1 / ((panjang + tebal) × (tinggi + tebal))',
            'calculations' => [
                'Lebar Visible' =>
                    "($brickLength + $mortarThickness) / 100 = " . NumberHelper::format($visibleWidth) . ' m',
                'Tinggi Visible' =>
                    "($brickHeight + $mortarThickness) / 100 = " . NumberHelper::format($visibleHeight) . ' m',
                'Luas per Bata' =>
                    NumberHelper::format($visibleWidth) .
                    ' × ' .
                    NumberHelper::format($visibleHeight) .
                    ' = ' .
                    NumberHelper::format($areaPerBrick) .
                    ' M2',
                'Bata per M2' =>
                    '1 / ' . NumberHelper::format($areaPerBrick) . ' = ' . NumberHelper::format($bricksPerSqm) . ' buah',
            ],
        ];

        // Pre-calculate volume tambahan strip di sisi kiri & bawah
        $volumeSisiKiri = ($wallHeight * $brickWidth * $mortarThickness) / 10000;
        $volumeSisiBawah = ($wallLength * $brickWidth * $mortarThickness) / 10000;
        $volumeOverlap = ($mortarThickness * $mortarThickness * $brickWidth) / 1000000;
        $volumeTambahanTotal = $volumeSisiKiri + $volumeSisiBawah - $volumeOverlap;

        // Step 5: Total Bricks
        $totalBricksRaw = $wallAreaForBricks * $bricksPerSqm;

        // Pembulatan: >= 0.50 ke atas, < 0.50 ke bawah
        $decimal = $totalBricksRaw - floor($totalBricksRaw);
        if ($decimal >= 0.5) {
            $totalBricks = ceil($totalBricksRaw);
        } else {
            $totalBricks = floor($totalBricksRaw);
        }

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Total Bata',
            'formula' => 'Luas Efektif × Bata per M2',
            'calculations' => [
                'Luas yang Digunakan' => NumberHelper::format($wallAreaForBricks) . ' M2 (luas efektif)',
                'Perhitungan' => NumberHelper::format($wallAreaForBricks) . ' × ' . NumberHelper::format($bricksPerSqm),
                'Hasil (sebelum pembulatan)' => NumberHelper::format($totalBricksRaw) . ' buah',
                'Desimal' => NumberHelper::format($decimal),
                'Hasil (setelah pembulatan)' => NumberHelper::format($totalBricks) . ' buah',
            ],
        ];

        // Step 6: Volume Mortar per Brick
        // Formula: (p + t + tebal adukan) × lebar × tebal adukan / 1000000
        $mortarVolumePerBrick =
            (($brickLength + $brickHeight + $mortarThickness) * $brickWidth * $mortarThickness) / 1000000;

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Volume Mortar per Bata',
            'formula' => '(panjang + tinggi + tebal adukan) × lebar × tebal adukan / 1000000',
            'calculations' => [
                'Dimensi' => "p={$brickLength}cm, t={$brickHeight}cm, l={$brickWidth}cm, tebal={$mortarThickness}cm",
                'Perhitungan' => "({$brickLength} + {$brickHeight} + {$mortarThickness}) × {$brickWidth} × {$mortarThickness} / 1000000",
                'Detail' =>
                    NumberHelper::format($brickLength + $brickHeight + $mortarThickness) .
                    ' × ' .
                    NumberHelper::format($brickWidth) .
                    ' × ' .
                    NumberHelper::format($mortarThickness) .
                    ' / 1000000',
                'Total per Bata' => NumberHelper::format($mortarVolumePerBrick) . ' M3',
            ],
        ];

        // Step 7: Total Mortar Volume
        $totalMortarVolume = $mortarVolumePerBrick * $totalBricks;
        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume per Bata × Total Bata',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($mortarVolumePerBrick) . ' × ' . NumberHelper::format($totalBricks),
                'Hasil' => NumberHelper::format($totalMortarVolume) . ' M3',
            ],
        ];

        // Step 7b: Volume Strip Adukan Sisi Kiri & Bawah
        $trace['steps'][] = [
            'step' => '7b',
            'title' => 'Volume Strip Adukan Sisi Kiri & Bawah',
            'formula' => 'Volume Sisi Kiri + Volume Sisi Bawah - Volume Overlap',
            'calculations' => [
                'Volume Sisi Kiri' =>
                    "tinggi × lebar_bata × tebal / 10000 = $wallHeight × $brickWidth × $mortarThickness / 10000 = " .
                    NumberHelper::format($volumeSisiKiri) .
                    ' M3',
                'Volume Sisi Bawah' =>
                    "panjang × lebar_bata × tebal / 10000 = $wallLength × $brickWidth × $mortarThickness / 10000 = " .
                    NumberHelper::format($volumeSisiBawah) .
                    ' M3',
                'Volume Overlap (sudut)' =>
                    "tebal × tebal × lebar_bata / 1000000 = $mortarThickness × $mortarThickness × $brickWidth / 1000000 = " .
                    NumberHelper::format($volumeOverlap) .
                    ' M3',
                'Total Volume Strip' =>
                    NumberHelper::format($volumeSisiKiri) .
                    ' + ' .
                    NumberHelper::format($volumeSisiBawah) .
                    ' - ' .
                    NumberHelper::format($volumeOverlap) .
                    ' = ' .
                    NumberHelper::format($volumeTambahanTotal) .
                    ' M3',
            ],
        ];

        // Step 7c: Total Volume Mortar
        $totalMortarVolumeWithAddition = $totalMortarVolume + $volumeTambahanTotal;

        $trace['steps'][] = [
            'step' => '7c',
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume Mortar Bata + Volume Strip',
            'calculations' => [
                'Volume Mortar Bata' => NumberHelper::format($totalMortarVolume) . ' M3',
                'Volume Strip' => NumberHelper::format($volumeTambahanTotal) . ' M3',
                'Total Volume Mortar' => NumberHelper::format($totalMortarVolumeWithAddition) . ' M3',
            ],
        ];

        // Step 8: Hitung Volume Sak Semen (dari dimensi kemasan di database)
        $cementPackageLength = $cement->dimension_length ?? 40; // cm
        $cementPackageWidth = $cement->dimension_width ?? 30; // cm
        $cementPackageHeight = $cement->dimension_height ?? 10; // cm
        $volumeSakM3 = $cement
            ? $cement->package_volume
            : ($cementPackageLength * $cementPackageWidth * $cementPackageHeight) / 1000000;

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Volume Satuan Kemasan Semen (dalam M3)',
            'formula' => 'Panjang × Lebar × Tinggi / 1000000',
            'calculations' => [
                'Semen Dipilih' => $cement ? $cement->cement_name . ' - ' . $cement->brand : 'Default',
                'Dimensi Kemasan' => "{$cementPackageLength}cm × {$cementPackageWidth}cm × {$cementPackageHeight}cm",
                'Perhitungan' => "($cementPackageLength × $cementPackageWidth × $cementPackageHeight) / 1000000",
                'Volume Sak' => NumberHelper::format($volumeSakM3) . ' M3',
            ],
        ];

        // Step 8b: Volume Adukan per Pasangan Bata (sudah dihitung di step 6)
        $trace['steps'][] = [
            'step' => '8b',
            'title' => 'Volume Adukan per Pasangan Bata',
            'info' => 'Sudah dihitung di Step 6',
            'calculations' => [
                'Volume per Pasangan' => NumberHelper::format($mortarVolumePerBrick) . ' M3',
            ],
        ];

        // Step 8c: Volume Adukan Total dari 1 Sak Semen
        $cementRatio = 1;
        $sandRatio = $mortarFormula->sand_ratio;
        $waterContribution = 0.2; // 20%
        $waterRatio = 0.3 * ($cementRatio + $sandRatio); // 30% dari (semen + pasir) dalam desimal
        $shrinkage = 0.15; // 15%

        // Rumus: (semen + pasir + (kontribusi air × ratio air)) × volume sak × (1 - penyusutan)
        $volumeAdukanM3 = ($cementRatio + $sandRatio + $waterContribution * 0.3) * $volumeSakM3 * (1 - $shrinkage);

        $trace['steps'][] = [
            'step' => '8c',
            'title' => 'Volume Adukan dari 1 Sak Semen',
            'formula' => '(ratio semen + ratio pasir + (kontribusi air × ratio air)) × volume sak × (1 - penyusutan)',
            'calculations' => [
                'Ratio Semen' => $cementRatio,
                'Ratio Pasir' => $sandRatio,
                'Ratio Air' =>
                    "30% dari ($cementRatio + $sandRatio) = 0.3 × " .
                    ($cementRatio + $sandRatio) .
                    ' = ' .
                    NumberHelper::format($waterRatio),
                'Kontribusi Air' => $waterContribution . ' (20%)',
                'Penyusutan' => $shrinkage . ' (15%)',
                'Perhitungan' =>
                    "($cementRatio + $sandRatio + ($waterContribution × 0.3 × ($cementRatio + $sandRatio))) × " .
                    NumberHelper::format($volumeSakM3) .
                    " × (1 - $shrinkage)",
                'Detail' =>
                    '(' .
                    ($cementRatio + $sandRatio) .
                    ' + ' .
                    NumberHelper::format($waterContribution * $waterRatio) .
                    ') × ' .
                    NumberHelper::format($volumeSakM3) .
                    ' × ' .
                    NumberHelper::format(1 - $shrinkage),
                'Detail 2' =>
                    NumberHelper::format($cementRatio + $sandRatio + $waterContribution * $waterRatio) .
                    ' × ' .
                    NumberHelper::format($volumeSakM3) .
                    ' × ' .
                    NumberHelper::format(1 - $shrinkage),
                'Volume Adukan dari 1 Sak' => NumberHelper::format($volumeAdukanM3) . ' M3',
            ],
        ];

        // Step 8d: Jumlah Pasangan Bata dari 1 Sak Semen
        // Volume strip per M2 bidang
        $volumeStripPerM2 = $volumeTambahanTotal / $wallArea;

        // Hitung luas yang bisa dikerjakan 1 sak (estimasi tanpa strip)
        $estimatedBricksFromOneSak = $volumeAdukanM3 / $mortarVolumePerBrick;
        $estimatedAreaFromOneSak =
            $estimatedBricksFromOneSak *
            ((($brickLength + $mortarThickness) * ($brickHeight + $mortarThickness)) / 10000);

        // Volume strip yang dibutuhkan untuk luas tersebut
        $volumeStripForEstimatedArea = $estimatedAreaFromOneSak * $volumeStripPerM2;

        // Volume yang tersisa untuk pasangan bata
        $volumeForBricks = $volumeAdukanM3 - $volumeStripForEstimatedArea;

        // Jumlah pasangan bata dari volume yang tersisa
        $jumlahPasanganBataRaw = $volumeForBricks / $mortarVolumePerBrick;

        // Pembulatan
        $decimal = $jumlahPasanganBataRaw - floor($jumlahPasanganBataRaw);
        if ($decimal > 0.5) {
            $jumlahPasanganBata = ceil($jumlahPasanganBataRaw);
        } else {
            $jumlahPasanganBata = floor($jumlahPasanganBataRaw);
        }

        $trace['steps'][] = [
            'step' => '8d',
            'title' => 'Jumlah Pasangan Bata dari 1 Sak Semen',
            'formula' => '(Volume Adukan - Volume Strip) / Volume Adukan per Pasangan',
            'calculations' => [
                'Volume Adukan dari 1 Sak' => NumberHelper::format($volumeAdukanM3) . ' M3',
                'Estimasi Luas dari 1 Sak' => NumberHelper::format($estimatedAreaFromOneSak) . ' M2',
                'Volume Strip untuk Luas Tersebut' =>
                    NumberHelper::format($estimatedAreaFromOneSak) .
                    ' × ' .
                    NumberHelper::format($volumeStripPerM2) .
                    ' = ' .
                    NumberHelper::format($volumeStripForEstimatedArea) .
                    ' M3',
                'Volume Tersisa untuk Bata' =>
                    NumberHelper::format($volumeAdukanM3) .
                    ' - ' .
                    NumberHelper::format($volumeStripForEstimatedArea) .
                    ' = ' .
                    NumberHelper::format($volumeForBricks) .
                    ' M3',
                'Perhitungan' => NumberHelper::format($volumeForBricks) . ' / ' . NumberHelper::format($mortarVolumePerBrick),
                'Jumlah Pasangan' => NumberHelper::format($jumlahPasanganBata) . ' pasangan bata',
            ],
        ];

        // Step 8e: Luas Pasangan per Bata 1/2
        $luasPasanganPerBata = (($brickLength + $mortarThickness) * ($brickHeight + $mortarThickness)) / 10000;

        $trace['steps'][] = [
            'step' => '8e',
            'title' => 'Luas Pasangan per Bata',
            'formula' => '(Panjang bata + tebal) × (Tinggi bata + tebal) / 10000',
            'calculations' => [
                'Perhitungan' => "($brickLength + $mortarThickness) × ($brickHeight + $mortarThickness) / 10000",
                'Detail' =>
                    NumberHelper::format($brickLength + $mortarThickness) .
                    ' × ' .
                    NumberHelper::format($brickHeight + $mortarThickness) .
                    ' / 10000',
                'Luas per Bata' => NumberHelper::format($luasPasanganPerBata) . ' M2',
            ],
        ];

        // Step 8f: Luas Pasangan dari 1 Sak Semen
        $luasPasanganDari1Sak = $jumlahPasanganBata * $luasPasanganPerBata;

        $trace['steps'][] = [
            'step' => '8f',
            'title' => 'Luas Pasangan dari 1 Sak Semen',
            'formula' => 'Jumlah Pasangan Bata × Luas per Bata',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($jumlahPasanganBata) . ' × ' . NumberHelper::format($luasPasanganPerBata),
                'Luas dari 1 Sak' => NumberHelper::format($luasPasanganDari1Sak) . ' M2',
                'Arti' =>
                    '1 sak semen bisa untuk ' . NumberHelper::format($luasPasanganDari1Sak) . ' M2 luas bidang dinding',
            ],
        ];

        // Guard clause: Pastikan luas pasangan tidak 0 untuk mencegah division by zero
        if ($luasPasanganDari1Sak <= 0) {
            throw new \Exception(
                'Luas pasangan dari 1 sak semen tidak valid (bernilai 0 atau negatif). Periksa data bata (dimensi), tebal adukan, dan formula mortar.',
            );
        }

        // Step 8g: Kebutuhan Semen per M2
        $cementSakPerM2 = 1 / $luasPasanganDari1Sak;

        $trace['steps'][] = [
            'step' => '8g',
            'title' => 'Kebutuhan Semen per M2 Dinding',
            'formula' => '1 sak / Luas Pasangan dari 1 Sak',
            'calculations' => [
                'Perhitungan' => '1 / ' . NumberHelper::format($luasPasanganDari1Sak),
                'Kebutuhan per M2' => NumberHelper::format($cementSakPerM2) . ' sak/M2',
            ],
        ];

        // Step 9: Total Kebutuhan Semen (dalam sak)
        $totalCementSakRaw = $cementSakPerM2 * $wallArea;

        // Pembulatan semen sak ke 2 desimal: >= 0.005 ke atas, < 0.005 ke bawah
        $totalCementSak = round($totalCementSakRaw, 2);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Total Kebutuhan Semen',
            'formula' => 'Kebutuhan Semen per M2 × Luas Bangunan',
            'calculations' => [
                'Perhitungan' => NumberHelper::format($cementSakPerM2) . ' sak/M2 × ' . NumberHelper::format($wallArea) . ' M2',
                'Total Semen (sak sebelum pembulatan)' => NumberHelper::format($totalCementSakRaw) . ' sak',
                'Total Semen (sak setelah pembulatan)' => NumberHelper::format($totalCementSak) . ' sak',
                'Total Semen (kg)' =>
                    NumberHelper::format($totalCementSak * $cementWeightPerSak) .
                    ' kg (' .
                    NumberHelper::format($totalCementSak) .
                    ' × ' .
                    $cementWeightPerSak .
                    ' kg/sak)',
            ],
        ];

        // Step 9b: Hitung Pasir & Air
        // Pasir = ratio pasir × cement sak × volume sak
        $totalSandSak = $totalCementSak * $sandRatio;
        $sandM3 = $totalSandSak * $volumeSakM3;

        // Air = 30% dari total volume (cement + sand)
        $totalSak = $totalCementSak + $totalSandSak;
        $waterLitersRaw = $totalSak * $volumeSakM3 * 0.3 * 1000;

        // Pembulatan air: ke bawah jika > 0.5
        $decimalWater = $waterLitersRaw - floor($waterLitersRaw);
        if ($decimalWater > 0.5) {
            $waterLiters = floor($waterLitersRaw);
        } else {
            $waterLiters = round($waterLitersRaw);
        }

        $trace['steps'][] = [
            'step' => '9b',
            'title' => 'Total Kebutuhan Pasir & Air',
            'calculations' => [
                'Pasir (sak)' =>
                    NumberHelper::format($totalCementSak) .
                    ' × ' .
                    $sandRatio .
                    ' = ' .
                    NumberHelper::format($totalSandSak) .
                    ' sak',
                'Pasir (M3)' =>
                    NumberHelper::format($totalSandSak) .
                    ' × ' .
                    NumberHelper::format($volumeSakM3) .
                    ' = ' .
                    NumberHelper::format($sandM3) .
                    ' M3',
                'Air (liter sebelum pembulatan)' =>
                    '(' .
                    NumberHelper::format($totalCementSak) .
                    ' + ' .
                    NumberHelper::format($totalSandSak) .
                    ') × ' .
                    NumberHelper::format($volumeSakM3) .
                    ' × 30% × 1000 = ' .
                    NumberHelper::format($waterLitersRaw) .
                    ' liter',
                'Air (liter setelah pembulatan)' => NumberHelper::format($waterLiters) . ' liter',
            ],
        ];

        // Pembulatan semen kg: >= 0.50 ke atas, < 0.50 ke bawah
        $cementKgRaw = $totalCementSak * $cementWeightPerSak;
        $decimalCement = $cementKgRaw - floor($cementKgRaw);
        if ($decimalCement >= 0.5) {
            $cementKg = ceil($cementKgRaw);
        } else {
            $cementKg = floor($cementKgRaw);
        }

        $sandKg = $sandM3 * 1600;

        // Calculate cement m3 from kg
        $cementDensity = 1440; // kg/M3
        $cementM3 = $cementKg / $cementDensity;

        // Calculate sand packaging units (assuming same volume as cement sak)
        $sandSakUnit = $sandM3 / $volumeSakM3;

        // Harga
        $brickPrice = $brick->price_per_piece ?? 0;
        $cementPrice = $cement->package_price ?? 0;
        $sandPricePerM3 = $sand->comparison_price_per_m3 ?? 0;
        if ($sandPricePerM3 == 0 && $sand->package_price && $sand->package_volume) {
            $sandPricePerM3 = $sand->package_price / $sand->package_volume;
        }

        $totalBrickPrice = $totalBricks * $brickPrice;
        $totalCementPrice = $totalCementSak * $cementPrice;
        $totalSandPrice = $sandM3 * $sandPricePerM3;
        $grandTotal = $totalBrickPrice + $totalCementPrice + $totalSandPrice;

        $trace['final_result'] = [
            'total_bricks' => $totalBricks,
            'cement_kg' => $cementKg,
            'cement_sak' => $totalCementSak,
            'cement_m3' => $cementM3,
            'sand_m3' => $sandM3,
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
