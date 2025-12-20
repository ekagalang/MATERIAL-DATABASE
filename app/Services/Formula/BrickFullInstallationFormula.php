<?php

namespace App\Services\Formula;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

/**
 * Formula untuk perhitungan pemasangan bata 1
 * dengan volume mortar dan strip tambahan di sisi kiri & bawah
 */
class BrickFullInstallationFormula implements FormulaInterface
{
    public static function getCode(): string
    {
        return 'brick_full_installation';
    }

    public static function getName(): string
    {
        return 'Pemasangan Bata 1';
    }

    public static function getDescription(): string
    {
        return 'Perhitungan material untuk pemasangan bata 1 dengan metode Volume Mortar, termasuk strip adukan di sisi kiri dan bawah.';
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
        // Untuk calculate yang cepat, kita panggil trace dan ambil final_result saja
        $trace = $this->trace($params);

        return $trace['final_result'];
    }

    public function trace(array $params): array
    {
        $trace = [];
        $trace['mode'] = self::getName();
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
                'Luas Total Bidang' => number_format($wallArea, 2) . ' m²',
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
                    number_format($additionalLayerThickness, 4) .
                    ' = ' .
                    number_format($effectiveLength, 4) .
                    ' m',
                'Tinggi Efektif' =>
                    "$wallHeight - " .
                    number_format($additionalLayerThickness, 4) .
                    ' = ' .
                    number_format($effectiveHeight, 4) .
                    ' m',
                'Luas Efektif' =>
                    number_format($effectiveLength, 4) .
                    ' × ' .
                    number_format($effectiveHeight, 4) .
                    ' = ' .
                    number_format($effectiveArea, 4) .
                    ' m²',
                'Pengurangan Luas' => number_format($wallArea - $effectiveArea, 4) . ' m² (area strip adukan)',
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
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 50;

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

        // Step 4: Hitung Bricks per m²
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
            'title' => 'Bata per m²',
            'formula' => '1 / ((panjang + tebal) × (tinggi + tebal))',
            'calculations' => [
                'Lebar Visible' =>
                    "($brickLength + $mortarThickness) / 100 = " . number_format($visibleWidth, 4) . ' m',
                'Tinggi Visible' =>
                    "($brickHeight + $mortarThickness) / 100 = " . number_format($visibleHeight, 4) . ' m',
                'Luas per Bata' =>
                    number_format($visibleWidth, 4) .
                    ' × ' .
                    number_format($visibleHeight, 4) .
                    ' = ' .
                    number_format($areaPerBrick, 6) .
                    ' m²',
                'Bata per m²' =>
                    '1 / ' . number_format($areaPerBrick, 6) . ' = ' . number_format($bricksPerSqm, 2) . ' buah',
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
            'formula' => 'Luas Efektif × Bata per m²',
            'calculations' => [
                'Luas yang Digunakan' => number_format($wallAreaForBricks, 4) . ' m² (luas efektif)',
                'Perhitungan' => number_format($wallAreaForBricks, 4) . ' × ' . number_format($bricksPerSqm, 2),
                'Hasil (sebelum pembulatan)' => number_format($totalBricksRaw, 2) . ' buah',
                'Desimal' => number_format($decimal, 4),
                'Hasil (setelah pembulatan)' => number_format($totalBricks) . ' buah',
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
                    number_format($brickLength + $brickHeight + $mortarThickness, 2) .
                    ' × ' .
                    number_format($brickWidth, 2) .
                    ' × ' .
                    number_format($mortarThickness, 2) .
                    ' / 1000000',
                'Total per Bata' => number_format($mortarVolumePerBrick, 6) . ' m³',
            ],
        ];

        // Step 7: Total Mortar Volume
        $totalMortarVolume = $mortarVolumePerBrick * $totalBricks;
        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume per Bata × Total Bata',
            'calculations' => [
                'Perhitungan' => number_format($mortarVolumePerBrick, 6) . ' × ' . number_format($totalBricks, 2),
                'Hasil' => number_format($totalMortarVolume, 6) . ' m³',
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
                    number_format($volumeSisiKiri, 6) .
                    ' m³',
                'Volume Sisi Bawah' =>
                    "panjang × lebar_bata × tebal / 10000 = $wallLength × $brickWidth × $mortarThickness / 10000 = " .
                    number_format($volumeSisiBawah, 6) .
                    ' m³',
                'Volume Overlap (sudut)' =>
                    "tebal × tebal × lebar_bata / 1000000 = $mortarThickness × $mortarThickness × $brickWidth / 1000000 = " .
                    number_format($volumeOverlap, 8) .
                    ' m³',
                'Total Volume Strip' =>
                    number_format($volumeSisiKiri, 6) .
                    ' + ' .
                    number_format($volumeSisiBawah, 6) .
                    ' - ' .
                    number_format($volumeOverlap, 8) .
                    ' = ' .
                    number_format($volumeTambahanTotal, 6) .
                    ' m³',
            ],
        ];

        // Step 7c: Total Volume Mortar
        $totalMortarVolumeWithAddition = $totalMortarVolume + $volumeTambahanTotal;

        $trace['steps'][] = [
            'step' => '7c',
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume Mortar Bata + Volume Strip',
            'calculations' => [
                'Volume Mortar Bata' => number_format($totalMortarVolume, 6) . ' m³',
                'Volume Strip' => number_format($volumeTambahanTotal, 6) . ' m³',
                'Total Volume Mortar' => number_format($totalMortarVolumeWithAddition, 6) . ' m³',
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
            'title' => 'Volume Satuan Kemasan Semen (dalam m³)',
            'formula' => 'Panjang × Lebar × Tinggi / 1000000',
            'calculations' => [
                'Semen Dipilih' => $cement ? $cement->cement_name . ' - ' . $cement->brand : 'Default',
                'Dimensi Kemasan' => "{$cementPackageLength}cm × {$cementPackageWidth}cm × {$cementPackageHeight}cm",
                'Perhitungan' => "($cementPackageLength × $cementPackageWidth × $cementPackageHeight) / 1000000",
                'Volume Sak' => number_format($volumeSakM3, 6) . ' m³',
            ],
        ];

        // Step 8b: Volume Adukan per Pasangan Bata (sudah dihitung di step 6)
        $trace['steps'][] = [
            'step' => '8b',
            'title' => 'Volume Adukan per Pasangan Bata',
            'info' => 'Sudah dihitung di Step 6',
            'calculations' => [
                'Volume per Pasangan' => number_format($mortarVolumePerBrick, 6) . ' m³',
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
                    number_format($waterRatio, 2),
                'Kontribusi Air' => $waterContribution . ' (20%)',
                'Penyusutan' => $shrinkage . ' (15%)',
                'Perhitungan' =>
                    "($cementRatio + $sandRatio + ($waterContribution × 0.3 × ($cementRatio + $sandRatio))) × " .
                    number_format($volumeSakM3, 6) .
                    " × (1 - $shrinkage)",
                'Detail' =>
                    '(' .
                    ($cementRatio + $sandRatio) .
                    ' + ' .
                    number_format($waterContribution * $waterRatio, 2) .
                    ') × ' .
                    number_format($volumeSakM3, 6) .
                    ' × ' .
                    number_format(1 - $shrinkage, 2),
                'Detail 2' =>
                    number_format($cementRatio + $sandRatio + $waterContribution * $waterRatio, 4) .
                    ' × ' .
                    number_format($volumeSakM3, 6) .
                    ' × ' .
                    number_format(1 - $shrinkage, 2),
                'Volume Adukan dari 1 Sak' => number_format($volumeAdukanM3, 6) . ' m³',
            ],
        ];

        // Step 8d: Jumlah Pasangan Bata dari 1 Sak Semen
        // Volume strip per m² bidang
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
                'Volume Adukan dari 1 Sak' => number_format($volumeAdukanM3, 6) . ' m³',
                'Estimasi Luas dari 1 Sak' => number_format($estimatedAreaFromOneSak, 4) . ' m²',
                'Volume Strip untuk Luas Tersebut' =>
                    number_format($estimatedAreaFromOneSak, 4) .
                    ' × ' .
                    number_format($volumeStripPerM2, 8) .
                    ' = ' .
                    number_format($volumeStripForEstimatedArea, 6) .
                    ' m³',
                'Volume Tersisa untuk Bata' =>
                    number_format($volumeAdukanM3, 6) .
                    ' - ' .
                    number_format($volumeStripForEstimatedArea, 6) .
                    ' = ' .
                    number_format($volumeForBricks, 6) .
                    ' m³',
                'Perhitungan' => number_format($volumeForBricks, 6) . ' / ' . number_format($mortarVolumePerBrick, 6),
                'Jumlah Pasangan' => number_format($jumlahPasanganBata) . ' pasangan bata',
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
                    number_format($brickLength + $mortarThickness, 2) .
                    ' × ' .
                    number_format($brickHeight + $mortarThickness, 2) .
                    ' / 10000',
                'Luas per Bata' => number_format($luasPasanganPerBata, 6) . ' m²',
            ],
        ];

        // Step 8f: Luas Pasangan dari 1 Sak Semen
        $luasPasanganDari1Sak = $jumlahPasanganBata * $luasPasanganPerBata;

        $trace['steps'][] = [
            'step' => '8f',
            'title' => 'Luas Pasangan dari 1 Sak Semen',
            'formula' => 'Jumlah Pasangan Bata × Luas per Bata',
            'calculations' => [
                'Perhitungan' => number_format($jumlahPasanganBata, 2) . ' × ' . number_format($luasPasanganPerBata, 6),
                'Luas dari 1 Sak' => number_format($luasPasanganDari1Sak, 4) . ' m²',
                'Arti' =>
                    '1 sak semen bisa untuk ' . number_format($luasPasanganDari1Sak, 2) . ' m² luas bidang dinding',
            ],
        ];

        // Step 8g: Kebutuhan Semen per m²
        $cementSakPerM2 = 1 / $luasPasanganDari1Sak;

        $trace['steps'][] = [
            'step' => '8g',
            'title' => 'Kebutuhan Semen per m² Dinding',
            'formula' => '1 sak / Luas Pasangan dari 1 Sak',
            'calculations' => [
                'Perhitungan' => '1 / ' . number_format($luasPasanganDari1Sak, 4),
                'Kebutuhan per m²' => number_format($cementSakPerM2, 6) . ' sak/m²',
            ],
        ];

        // Step 9: Total Kebutuhan Semen (dalam sak)
        $totalCementSakRaw = $cementSakPerM2 * $wallArea;

        // Pembulatan semen sak ke 2 desimal: >= 0.005 ke atas, < 0.005 ke bawah
        $totalCementSak = round($totalCementSakRaw, 2);

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Total Kebutuhan Semen',
            'formula' => 'Kebutuhan Semen per m² × Luas Bangunan',
            'calculations' => [
                'Perhitungan' => number_format($cementSakPerM2, 6) . ' sak/m² × ' . number_format($wallArea, 2) . ' m²',
                'Total Semen (sak sebelum pembulatan)' => number_format($totalCementSakRaw, 4) . ' sak',
                'Total Semen (sak setelah pembulatan)' => number_format($totalCementSak, 2) . ' sak',
                'Total Semen (kg)' =>
                    number_format($totalCementSak * $cementWeightPerSak, 2) .
                    ' kg (' .
                    number_format($totalCementSak, 2) .
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
                    number_format($totalCementSak) .
                    ' × ' .
                    $sandRatio .
                    ' = ' .
                    number_format($totalSandSak, 4) .
                    ' sak',
                'Pasir (m³)' =>
                    number_format($totalSandSak, 4) .
                    ' × ' .
                    number_format($volumeSakM3, 6) .
                    ' = ' .
                    number_format($sandM3, 6) .
                    ' m³',
                'Air (liter sebelum pembulatan)' =>
                    '(' .
                    number_format($totalCementSak) .
                    ' + ' .
                    number_format($totalSandSak, 4) .
                    ') × ' .
                    number_format($volumeSakM3, 6) .
                    ' × 30% × 1000 = ' .
                    number_format($waterLitersRaw, 2) .
                    ' liter',
                'Air (liter setelah pembulatan)' => number_format($waterLiters) . ' liter',
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
        $cementDensity = 1440; // kg/m³
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
