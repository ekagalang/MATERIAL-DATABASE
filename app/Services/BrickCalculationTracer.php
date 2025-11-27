<?php

namespace App\Services;

use App\Models\Brick;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\Sand;

/**
 * Trace setiap step perhitungan seperti di Excel
 * Untuk debugging dan verifikasi rumus
 */
class BrickCalculationTracer
{
    /**
     * Trace Mode 1: Professional (Volume Mortar)
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
                'Panjang Dinding' => $wallLength.' m',
                'Tinggi Dinding' => $wallHeight.' m',
                'Tebal Adukan' => $mortarThickness.' cm',
            ],
        ];

        // Step 2: Luas Dinding
        $wallArea = $wallLength * $wallHeight;
        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Luas Dinding',
            'formula' => 'Luas = Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$wallLength × $wallHeight",
                'Hasil' => number_format($wallArea, 2).' m²',
            ],
        ];

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
                'Formula Mortar' => $mortarFormula->cement_ratio.':'.$mortarFormula->sand_ratio,
                'Berat Semen per Sak' => $cementWeightPerSak.' kg',
            ],
        ];

        // Step 4: Hitung Bricks per m²
        $bricksPerSqm = $installationType->calculateBricksPerSqm(
            $brickLength,
            $brickWidth,
            $brickHeight,
            $mortarThickness
        );

        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Bata per m²',
            'formula' => '1 / ((panjang + tebal) × (tinggi + tebal))',
            'calculations' => [
                'Lebar Visible' => "($brickLength + $mortarThickness) / 100 = ".number_format($visibleWidth, 4).' m',
                'Tinggi Visible' => "($brickHeight + $mortarThickness) / 100 = ".number_format($visibleHeight, 4).' m',
                'Luas per Bata' => number_format($visibleWidth, 4).' × '.number_format($visibleHeight, 4).' = '.number_format($areaPerBrick, 6).' m²',
                'Bata per m²' => '1 / '.number_format($areaPerBrick, 6).' = '.number_format($bricksPerSqm, 2).' buah',
            ],
        ];

        // Step 5: Total Bricks
        $totalBricks = $wallArea * $bricksPerSqm;
        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Total Bata',
            'formula' => 'Luas Dinding × Bata per m²',
            'calculations' => [
                'Perhitungan' => number_format($wallArea, 2).' × '.number_format($bricksPerSqm, 2),
                'Hasil' => number_format($totalBricks, 2).' buah',
            ],
        ];

        // Step 6: Volume Mortar per Brick
        // Formula: (p + t + tebal adukan) × lebar × tebal adukan / 1000000
        $mortarVolumePerBrick = (($brickLength + $brickHeight + $mortarThickness) * $brickWidth * $mortarThickness) / 1000000;

        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Volume Mortar per Bata',
            'formula' => '(panjang + tinggi + tebal adukan) × lebar × tebal adukan / 1000000',
            'calculations' => [
                'Dimensi' => "p={$brickLength}cm, t={$brickHeight}cm, l={$brickWidth}cm, tebal={$mortarThickness}cm",
                'Perhitungan' => "({$brickLength} + {$brickHeight} + {$mortarThickness}) × {$brickWidth} × {$mortarThickness} / 1000000",
                'Detail' => number_format($brickLength + $brickHeight + $mortarThickness, 2).' × '.number_format($brickWidth, 2).' × '.number_format($mortarThickness, 2).' / 1000000',
                'Total per Bata' => number_format($mortarVolumePerBrick, 6).' m³',
            ],
        ];

        // Step 7: Total Mortar Volume
        $totalMortarVolume = $mortarVolumePerBrick * $totalBricks;
        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume per Bata × Total Bata',
            'calculations' => [
                'Perhitungan' => number_format($mortarVolumePerBrick, 6).' × '.number_format($totalBricks, 2),
                'Hasil' => number_format($totalMortarVolume, 6).' m³',
            ],
        ];

        // Step 8: Hitung Volume Sak Semen (dari dimensi kemasan di database)
        $cementPackageLength = $cement->dimension_length ?? 40; // cm
        $cementPackageWidth = $cement->dimension_width ?? 30; // cm
        $cementPackageHeight = $cement->dimension_height ?? 10; // cm
        $volumeSakM3 = $cement ? $cement->package_volume : (($cementPackageLength * $cementPackageWidth * $cementPackageHeight) / 1000000);

        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Volume Satuan Kemasan Semen (dalam m³)',
            'formula' => 'Panjang × Lebar × Tinggi / 1000000',
            'calculations' => [
                'Semen Dipilih' => $cement ? $cement->cement_name.' - '.$cement->brand : 'Default',
                'Dimensi Kemasan' => "{$cementPackageLength}cm × {$cementPackageWidth}cm × {$cementPackageHeight}cm",
                'Perhitungan' => "($cementPackageLength × $cementPackageWidth × $cementPackageHeight) / 1000000",
                'Volume Sak' => number_format($volumeSakM3, 6).' m³',
            ],
        ];

        // Step 8b: Volume Adukan per Pasangan Bata (sudah dihitung di step 6)
        $trace['steps'][] = [
            'step' => '8b',
            'title' => 'Volume Adukan per Pasangan Bata',
            'info' => 'Sudah dihitung di Step 6',
            'calculations' => [
                'Volume per Pasangan' => number_format($mortarVolumePerBrick, 6).' m³',
            ],
        ];

        // Step 8c: Volume Adukan Total dari 1 Sak Semen
        $cementRatio = 1;
        $sandRatio = $mortarFormula->sand_ratio;
        $waterContribution = 0.2; // 20%
        $waterRatio = 0.3 * ($cementRatio + $sandRatio); // 30% dari (semen + pasir) dalam desimal
        $shrinkage = 0.15; // 15%

        // Rumus: (semen + pasir + (kontribusi air × ratio air)) × volume sak × (1 - penyusutan)
        $volumeAdukanM3 = ($cementRatio + $sandRatio + ($waterContribution * 0.3)) * $volumeSakM3 * (1 - $shrinkage);

        $trace['steps'][] = [
            'step' => '8c',
            'title' => 'Volume Adukan dari 1 Sak Semen',
            'formula' => '(ratio semen + ratio pasir + (kontribusi air × ratio air)) × volume sak × (1 - penyusutan)',
            'calculations' => [
                'Ratio Semen' => $cementRatio,
                'Ratio Pasir' => $sandRatio,
                'Ratio Air' => "30% dari ($cementRatio + $sandRatio) = 0.3 × ".($cementRatio + $sandRatio).' = '.number_format($waterRatio, 2),
                'Kontribusi Air' => $waterContribution.' (20%)',
                'Penyusutan' => $shrinkage.' (15%)',
                'Perhitungan' => "($cementRatio + $sandRatio + ($waterContribution × 0.3 × ($cementRatio + $sandRatio))) × ".number_format($volumeSakM3, 6)." × (1 - $shrinkage)",
                'Detail' => '('.($cementRatio + $sandRatio).' + '.number_format($waterContribution * $waterRatio, 2).') × '.number_format($volumeSakM3, 6).' × '.number_format(1 - $shrinkage, 2),
                'Detail 2' => number_format($cementRatio + $sandRatio + ($waterContribution * $waterRatio), 4).' × '.number_format($volumeSakM3, 6).' × '.number_format(1 - $shrinkage, 2),
                'Volume Adukan dari 1 Sak' => number_format($volumeAdukanM3, 6).' m³',
            ],
        ];

        // Step 8d: Jumlah Pasangan Bata dari 1 Sak
        $jumlahPasanganBata = $volumeAdukanM3 / $mortarVolumePerBrick;

        // Pembulatan: jika decimal > 0.50 maka bulatkan ke atas, jika <= 0.50 bulatkan ke bawah
        $decimal = $jumlahPasanganBata - floor($jumlahPasanganBata);
        if ($decimal > 0.50) {
            $jumlahPasanganBata = ceil($jumlahPasanganBata);
        } else {
            $jumlahPasanganBata = floor($jumlahPasanganBata);
        }

        $trace['steps'][] = [
            'step' => '8d',
            'title' => 'Jumlah Pasangan Bata dari 1 Sak Semen',
            'formula' => 'Volume Adukan / Volume Adukan per Pasangan',
            'calculations' => [
                'Perhitungan' => number_format($volumeAdukanM3, 4).' / '.number_format($mortarVolumePerBrick, 6),
                'Jumlah Pasangan' => number_format($jumlahPasanganBata).' pasangan bata',
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
                'Detail' => number_format($brickLength + $mortarThickness, 2).' × '.number_format($brickHeight + $mortarThickness, 2).' / 10000',
                'Luas per Bata' => number_format($luasPasanganPerBata, 6).' m²',
            ],
        ];

        // Step 8f: Luas Pasangan dari 1 Sak Semen
        $luasPasanganDari1Sak = $jumlahPasanganBata * $luasPasanganPerBata;

        $trace['steps'][] = [
            'step' => '8f',
            'title' => 'Luas Pasangan dari 1 Sak Semen',
            'formula' => 'Jumlah Pasangan Bata × Luas per Bata',
            'calculations' => [
                'Perhitungan' => number_format($jumlahPasanganBata, 2).' × '.number_format($luasPasanganPerBata, 6),
                'Luas dari 1 Sak' => number_format($luasPasanganDari1Sak, 4).' m²',
                'Arti' => '1 sak semen bisa untuk '.number_format($luasPasanganDari1Sak, 2).' m² luas adukan',
            ],
        ];

        // Step 8g: Kebutuhan Semen per m²
        $cementSakPerM2 = 1 / $luasPasanganDari1Sak;

        $trace['steps'][] = [
            'step' => '8g',
            'title' => 'Kebutuhan Semen per m² Dinding',
            'formula' => '1 sak / Luas Pasangan dari 1 Sak',
            'calculations' => [
                'Perhitungan' => '1 / '.number_format($luasPasanganDari1Sak, 4),
                'Kebutuhan per m²' => number_format($cementSakPerM2, 6).' sak/m²',
            ],
        ];

        // Step 9: Total Kebutuhan Semen (dalam sak)
        $totalCementSak = $cementSakPerM2 * $wallArea;

        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Total Kebutuhan Semen',
            'formula' => 'Kebutuhan Semen per m² × Luas Bangunan',
            'calculations' => [
                'Perhitungan' => number_format($cementSakPerM2, 6).' sak/m² × '.number_format($wallArea, 2).' m²',
                'Total Semen (sak)' => number_format($totalCementSak, 4).' sak',
                'Total Semen (kg)' => number_format($totalCementSak * $cementWeightPerSak, 2).' kg ('.number_format($totalCementSak, 4).' × '.$cementWeightPerSak.' kg/sak)',
            ],
        ];

        // Step 9b: Hitung Pasir & Air
        // Pasir = ratio pasir × cement sak × volume sak
        $totalSandSak = $totalCementSak * $sandRatio;
        $sandM3 = $totalSandSak * $volumeSakM3;

        // Air = 30% dari total volume (cement + sand)
        $totalSak = $totalCementSak + $totalSandSak;
        $waterLiters = $totalSak * $volumeSakM3 * 0.3 * 1000;

        $trace['steps'][] = [
            'step' => '9b',
            'title' => 'Total Kebutuhan Pasir & Air',
            'calculations' => [
                'Pasir (sak)' => number_format($totalCementSak, 4).' × '.$sandRatio.' = '.number_format($totalSandSak, 4).' sak',
                'Pasir (m³)' => number_format($totalSandSak, 4).' × '.number_format($volumeSakM3, 6).' = '.number_format($sandM3, 6).' m³',
                'Pasir (kg)' => number_format($sandM3, 6).' × 1600 = '.number_format($sandM3 * 1600, 2).' kg',
                'Air (liter)' => '('.number_format($totalCementSak, 4).' + '.number_format($totalSandSak, 4).') × '.number_format($volumeSakM3, 6).' × 30% × 1000 = '.number_format($waterLiters, 2).' liter',
            ],
        ];

        $cementKg = $totalCementSak * $cementWeightPerSak;
        $sandKg = $sandM3 * 1600;

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
            'sand_m3' => $sandM3,
            'sand_kg' => $sandKg,
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

    /**
     * Trace Mode 2: Field (Package Engineering)
     */
    public static function traceFieldMode(array $params): array
    {
        $trace = [];
        $trace['mode'] = 'Mode 2: FIELD (Package Engineering)';
        $trace['steps'] = [];

        // Step 1: Input Parameters
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $cementRatio = $params['custom_cement_ratio'] ?? 1;
        $sandRatio = $params['custom_sand_ratio'] ?? 3;

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $wallLength.' m',
                'Tinggi Dinding' => $wallHeight.' m',
                'Tebal Adukan' => $mortarThickness.' cm',
                'Ratio' => "$cementRatio:$sandRatio",
            ],
        ];

        // Step 2: Konstanta Engineering
        $CEMENT_SAK_VOLUME = 0.036;
        $SHRINKAGE_FACTOR = 0.15;
        $WATER_PERCENTAGE = 0.30;
        $WATER_FACTOR = 0.2;

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Konstanta Engineering (dari rumus 2.xlsx)',
            'calculations' => [
                'Volume Sak Semen' => $CEMENT_SAK_VOLUME.' m³',
                'Shrinkage Factor' => ($SHRINKAGE_FACTOR * 100).'%',
                'Water Percentage' => ($WATER_PERCENTAGE * 100).'%',
                'Water Factor' => $WATER_FACTOR,
            ],
        ];

        // Step 3: Luas Dinding
        $wallArea = $wallLength * $wallHeight;
        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Dinding',
            'formula' => 'Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$wallLength × $wallHeight",
                'Hasil' => number_format($wallArea, 2).' m²',
            ],
        ];

        // Step 4: Load Brick & Cement Data
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        $brickLength = $brick->dimension_length ?? 19.2;
        $brickWidth = $brick->dimension_width ?? 9;
        $brickHeight = $brick->dimension_height ?? 8;
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 40;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Data Bata & Semen',
            'calculations' => [
                'Dimensi Bata' => "$brickLength × $brickWidth × $brickHeight cm",
                'Berat Semen per Sak' => $cementWeightPerSak.' kg',
            ],
        ];

        // Step 5: Bricks per m²
        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;
        $bricksPerSqm = 1 / $areaPerBrick;
        $totalBricks = $wallArea * $bricksPerSqm;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Jumlah Bata',
            'formula' => '1 / ((panjang + tebal) × (tinggi + tebal))',
            'calculations' => [
                'Lebar Visible' => number_format($visibleWidth, 4).' m',
                'Tinggi Visible' => number_format($visibleHeight, 4).' m',
                'Luas per Bata' => number_format($areaPerBrick, 6).' m²',
                'Bata per m²' => number_format($bricksPerSqm, 2).' buah',
                'Total Bata' => number_format($totalBricks, 2).' buah',
            ],
        ];

        // Step 6: Formula Excel - Total Sak Ratio
        $totalSakRatio = $cementRatio + $sandRatio + ($WATER_FACTOR * $WATER_PERCENTAGE);
        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Total Sak Ratio (dengan Water)',
            'formula' => 'Cement + Sand + (Water Factor × Water %)',
            'calculations' => [
                'Perhitungan' => "$cementRatio + $sandRatio + ($WATER_FACTOR × $WATER_PERCENTAGE)",
                'Hasil' => number_format($totalSakRatio, 4),
            ],
        ];

        // Step 7: Volume per Luas Pasangan
        $volumePerLuasPasangan = $totalSakRatio * $CEMENT_SAK_VOLUME * (1 - $SHRINKAGE_FACTOR);
        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Volume per Luas Pasangan',
            'formula' => 'Total Sak Ratio × Vol Sak × (1 - Shrinkage)',
            'calculations' => [
                'Perhitungan' => number_format($totalSakRatio, 4).' × '.$CEMENT_SAK_VOLUME.' × (1 - '.$SHRINKAGE_FACTOR.')',
                'Hasil' => number_format($volumePerLuasPasangan, 6).' m³',
            ],
        ];

        // Step 8: Volume per m² Dinding (normalisasi)
        $volumePerM2Dinding = $volumePerLuasPasangan / 3.882375;
        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Volume per m² Dinding',
            'formula' => 'Volume per Luas Pasangan / 3.882375 (normalisasi dari Excel)',
            'calculations' => [
                'Perhitungan' => number_format($volumePerLuasPasangan, 6).' / 3.882375',
                'Hasil' => number_format($volumePerM2Dinding, 6).' m³/m²',
            ],
        ];

        // Step 9: Total Mortar Volume
        $totalMortarVolume = $volumePerM2Dinding * $wallArea;
        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Total Volume Mortar',
            'formula' => 'Volume per m² × Luas Dinding',
            'calculations' => [
                'Perhitungan' => number_format($volumePerM2Dinding, 6).' × '.number_format($wallArea, 2),
                'Hasil' => number_format($totalMortarVolume, 6).' m³',
            ],
        ];

        // Step 10: Total Sak
        $totalSak = $totalMortarVolume / $CEMENT_SAK_VOLUME;
        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Total Sak (Cement + Sand)',
            'formula' => 'Total Volume / Vol Sak',
            'calculations' => [
                'Perhitungan' => number_format($totalMortarVolume, 6).' / '.$CEMENT_SAK_VOLUME,
                'Hasil' => number_format($totalSak, 2).' sak',
            ],
        ];

        // Step 11: Distribusi per Material
        $cementSak = $totalSak * ($cementRatio / $totalSakRatio);
        $sandSak = $totalSak * ($sandRatio / $totalSakRatio);
        $waterLiters = $totalSak * $CEMENT_SAK_VOLUME * $WATER_PERCENTAGE * 1000;

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Distribusi Material',
            'formula' => 'Total Sak × (Ratio Material / Total Ratio)',
            'calculations' => [
                'Semen (sak)' => number_format($totalSak, 2).' × ('.$cementRatio.' / '.number_format($totalSakRatio, 4).') = '.number_format($cementSak, 2).' sak',
                'Pasir (sak)' => number_format($totalSak, 2).' × ('.$sandRatio.' / '.number_format($totalSakRatio, 4).') = '.number_format($sandSak, 2).' sak',
                'Air (liter)' => number_format($totalSak, 2).' × '.$CEMENT_SAK_VOLUME.' × '.$WATER_PERCENTAGE.' × 1000 = '.number_format($waterLiters, 2).' liter',
            ],
        ];

        // Step 12: Convert ke kg/m³
        $cementKg = $cementSak * $cementWeightPerSak;
        $sandM3 = $sandSak * $CEMENT_SAK_VOLUME;
        $sandKg = $sandM3 * 1600;

        $trace['steps'][] = [
            'step' => 12,
            'title' => 'Konversi ke kg/m³',
            'calculations' => [
                'Semen (kg)' => number_format($cementSak, 2).' sak × '.$cementWeightPerSak.' kg/sak = '.number_format($cementKg, 2).' kg',
                'Pasir (m³)' => number_format($sandSak, 2).' sak × '.$CEMENT_SAK_VOLUME.' m³/sak = '.number_format($sandM3, 6).' m³',
                'Pasir (kg)' => number_format($sandM3, 6).' m³ × 1600 kg/m³ = '.number_format($sandKg, 2).' kg',
            ],
        ];

        // Harga
        $brickPrice = $brick->price_per_piece ?? 0;
        $cementPrice = $cement->package_price ?? 0;
        $sandPricePerM3 = $sand->comparison_price_per_m3 ?? 0;
        if ($sandPricePerM3 == 0 && $sand->package_price && $sand->package_volume) {
            $sandPricePerM3 = $sand->package_price / $sand->package_volume;
        }

        $totalBrickPrice = $totalBricks * $brickPrice;
        $totalCementPrice = $cementSak * $cementPrice;
        $totalSandPrice = $sandM3 * $sandPricePerM3;
        $grandTotal = $totalBrickPrice + $totalCementPrice + $totalSandPrice;

        $trace['final_result'] = [
            'total_bricks' => $totalBricks,
            'cement_kg' => $cementKg,
            'cement_sak' => $cementSak,
            'sand_m3' => $sandM3,
            'sand_kg' => $sandKg,
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

    /**
     * Trace Mode 3: Simple (Package Basic)
     */
    public static function traceSimpleMode(array $params): array
    {
        $trace = [];
        $trace['mode'] = 'Mode 3: SIMPLE (Package Basic)';
        $trace['steps'] = [];

        // Step 1: Input Parameters
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $cementRatio = $params['custom_cement_ratio'] ?? 1;
        $sandRatio = $params['custom_sand_ratio'] ?? 4;

        $trace['steps'][] = [
            'step' => 1,
            'title' => 'Input Parameters',
            'calculations' => [
                'Panjang Dinding' => $wallLength.' m',
                'Tinggi Dinding' => $wallHeight.' m',
                'Tebal Adukan' => $mortarThickness.' cm',
                'Ratio' => "$cementRatio:$sandRatio",
            ],
        ];

        // Step 2: Get Cement Data & Calculate Sak Volume
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 50;
        $cementDensity = 1440;
        $CEMENT_SAK_VOLUME_CORRECTED = $cementWeightPerSak / $cementDensity;
        $WATER_PERCENTAGE = 0.30;

        $trace['steps'][] = [
            'step' => 2,
            'title' => 'Data Semen & Volume Sak',
            'formula' => 'Volume Sak = Berat Semen / Density',
            'calculations' => [
                'Berat Semen per Sak' => $cementWeightPerSak.' kg',
                'Density Semen' => $cementDensity.' kg/m³',
                'Volume Sak' => "$cementWeightPerSak / $cementDensity = ".number_format($CEMENT_SAK_VOLUME_CORRECTED, 6).' m³',
                'Water %' => ($WATER_PERCENTAGE * 100).'%',
            ],
        ];

        // Step 3: Luas Dinding
        $wallArea = $wallLength * $wallHeight;
        $trace['steps'][] = [
            'step' => 3,
            'title' => 'Luas Dinding',
            'formula' => 'Panjang × Tinggi',
            'calculations' => [
                'Perhitungan' => "$wallLength × $wallHeight",
                'Hasil' => number_format($wallArea, 2).' m²',
            ],
        ];

        // Step 4: Load Brick Data
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();
        $brickLength = $brick->dimension_length ?? 19.2;
        $brickWidth = $brick->dimension_width ?? 9;
        $brickHeight = $brick->dimension_height ?? 8;

        $trace['steps'][] = [
            'step' => 4,
            'title' => 'Data Bata',
            'calculations' => [
                'Dimensi Bata' => "$brickLength × $brickWidth × $brickHeight cm",
            ],
        ];

        // Step 5: Bricks per m²
        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;
        $bricksPerSqm = 1 / $areaPerBrick;
        $totalBricks = $wallArea * $bricksPerSqm;

        $trace['steps'][] = [
            'step' => 5,
            'title' => 'Jumlah Bata',
            'calculations' => [
                'Bata per m²' => number_format($bricksPerSqm, 2).' buah',
                'Total Bata' => number_format($totalBricks, 2).' buah',
            ],
        ];

        // Step 6: Kebutuhan Semen per m² (Asumsi)
        $cementSakPerM2 = 0.35;
        $trace['steps'][] = [
            'step' => 6,
            'title' => 'Asumsi Kebutuhan Semen',
            'calculations' => [
                'Semen per m²' => $cementSakPerM2.' sak/m² (asumsi realistis)',
            ],
        ];

        // Step 7: Total Cement Sak
        $totalCementSak = $cementSakPerM2 * $wallArea;
        $trace['steps'][] = [
            'step' => 7,
            'title' => 'Total Semen',
            'formula' => 'Semen per m² × Luas Dinding',
            'calculations' => [
                'Perhitungan' => "$cementSakPerM2 × ".number_format($wallArea, 2),
                'Hasil' => number_format($totalCementSak, 2).' sak',
            ],
        ];

        // Step 8: Total Sand Sak
        $totalSandSak = $totalCementSak * $sandRatio;
        $trace['steps'][] = [
            'step' => 8,
            'title' => 'Total Pasir',
            'formula' => 'Semen Sak × Sand Ratio',
            'calculations' => [
                'Perhitungan' => number_format($totalCementSak, 2).' × '.$sandRatio,
                'Hasil' => number_format($totalSandSak, 2).' sak',
            ],
        ];

        // Step 9: Sand Volume
        $sandM3 = $totalSandSak * $CEMENT_SAK_VOLUME_CORRECTED;
        $trace['steps'][] = [
            'step' => 9,
            'title' => 'Volume Pasir',
            'formula' => 'Sand Sak × Volume Sak',
            'calculations' => [
                'Perhitungan' => number_format($totalSandSak, 2).' × '.number_format($CEMENT_SAK_VOLUME_CORRECTED, 6),
                'Hasil' => number_format($sandM3, 6).' m³',
            ],
        ];

        // Step 10: Water
        $totalSak = $totalCementSak + $totalSandSak;
        $waterLiters = $totalSak * $CEMENT_SAK_VOLUME_CORRECTED * $WATER_PERCENTAGE * 1000;

        $trace['steps'][] = [
            'step' => 10,
            'title' => 'Kebutuhan Air',
            'formula' => '(Cement Sak + Sand Sak) × Vol Sak × Water % × 1000',
            'calculations' => [
                'Total Sak' => number_format($totalCementSak, 2).' + '.number_format($totalSandSak, 2).' = '.number_format($totalSak, 2).' sak',
                'Perhitungan' => number_format($totalSak, 2).' × '.number_format($CEMENT_SAK_VOLUME_CORRECTED, 6).' × '.$WATER_PERCENTAGE.' × 1000',
                'Hasil' => number_format($waterLiters, 2).' liter',
            ],
        ];

        // Step 11: Convert to kg
        $cementKg = $totalCementSak * $cementWeightPerSak;
        $sandKg = $sandM3 * 1600;

        $trace['steps'][] = [
            'step' => 11,
            'title' => 'Konversi ke kg',
            'calculations' => [
                'Semen (kg)' => number_format($totalCementSak, 2).' sak × '.$cementWeightPerSak.' kg/sak = '.number_format($cementKg, 2).' kg',
                'Pasir (kg)' => number_format($sandM3, 6).' m³ × 1600 kg/m³ = '.number_format($sandKg, 2).' kg',
            ],
        ];

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
            'sand_m3' => $sandM3,
            'sand_kg' => $sandKg,
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

    /**
     * Helper: Explain interpolation calculation
     */
    private static function explainInterpolation(float $x, array $dataPoints, string $unit): string
    {
        ksort($dataPoints);
        $xPoints = array_keys($dataPoints);
        $yPoints = array_values($dataPoints);
        $n = count($xPoints);

        // Check if exact match
        if (isset($dataPoints[$x])) {
            return "Ratio $x ada di data → ".number_format($dataPoints[$x], 6)." $unit (langsung, tidak perlu interpolasi)";
        }

        // Find bracketing points
        for ($i = 0; $i < $n - 1; $i++) {
            if ($x >= $xPoints[$i] && $x <= $xPoints[$i + 1]) {
                $x0 = $xPoints[$i];
                $x1 = $xPoints[$i + 1];
                $y0 = $yPoints[$i];
                $y1 = $yPoints[$i + 1];

                $result = $y0 + ($y1 - $y0) * ($x - $x0) / ($x1 - $x0);

                return sprintf(
                    'Interpolasi antara ratio %s→%s dan %s→%s: y = %s + (%s - %s) × (%s - %s) / (%s - %s) = %s + %s × %s / %s = %s %s',
                    $x0, number_format($y0, 6),
                    $x1, number_format($y1, 6),
                    number_format($y0, 6),
                    number_format($y1, 6),
                    number_format($y0, 6),
                    number_format($x, 6),
                    number_format($x0, 6),
                    number_format($x1, 6),
                    number_format($x0, 6),
                    number_format($y0, 6),
                    number_format($y1 - $y0, 6),
                    number_format($x - $x0, 6),
                    number_format($x1 - $x0, 6),
                    number_format($result, 6),
                    $unit
                );
            }
        }

        // Extrapolation
        if ($x < $xPoints[0]) {
            return "Ratio $x < ".$xPoints[0].' (extrapolasi ke bawah) → '.number_format($yPoints[0], 6)." $unit";
        } else {
            return "Ratio $x > ".$xPoints[$n - 1].' (extrapolasi ke atas) → '.number_format($yPoints[$n - 1], 6)." $unit";
        }
    }
}
