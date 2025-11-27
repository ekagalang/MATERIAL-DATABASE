<?php

namespace App\Services;

use App\Models\BrickInstallationType;
use App\Models\MortarFormula;
use App\Models\Brick;
use App\Models\Cement;

/**
 * Triple Mode Brick Calculation Service
 *
 * Mode 1: Professional (Volume Mortar) - Sistem saat ini
 * Mode 2: Field (Package Engineering) - Dari rumus 2.xlsx
 * Mode 3: Simple (Package Basic) - Rumus user dengan koreksi
 */
class BrickCalculationModes
{
    /**
     * Mode 1: PROFESSIONAL - Volume Mortar Based
     * Menggunakan sistem saat ini dengan interpolasi akurat
     */
    public static function calculateProfessionalMode(array $params): array
    {
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $wallArea = $wallLength * $wallHeight;
        $installationType = BrickInstallationType::findOrFail($params['installation_type_id']);
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();

        // Get cement data from database
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 50; // fallback to 50kg if not found

        // Hitung bricks per m²
        $bricksPerSqm = $installationType->calculateBricksPerSqm(
            $brick->dimension_length ?? 19.2,
            $brick->dimension_width ?? 9,
            $brick->dimension_height ?? 8,
            $mortarThickness
        );

        // Total bricks
        $totalBricks = $wallArea * $bricksPerSqm;

        // Volume mortar per brick
        $mortarVolumePerBrick = self::calculateMortarVolumePerBrick(
            $brick->dimension_length ?? 19.2,
            $brick->dimension_width ?? 9,
            $brick->dimension_height ?? 8,
            $mortarThickness,
            $installationType->code
        );

        // Total mortar volume
        $totalMortarVolume = $mortarVolumePerBrick * $totalBricks;

        // Material dari formula
        $cementKgPerM3 = $mortarFormula->cement_kg_per_m3;
        $sandM3PerM3 = $mortarFormula->sand_m3_per_m3;
        $waterLiterPerM3 = $mortarFormula->water_liter_per_m3;

        $cementKg = $cementKgPerM3 * $totalMortarVolume;
        $sandM3 = $sandM3PerM3 * $totalMortarVolume;
        $waterLiters = $waterLiterPerM3 * $totalMortarVolume;

        return [
            'mode' => 'Professional (Volume Mortar)',
            'method' => 'Berbasis volume mortar aktual dengan data empiris dari Excel',
            'wall_area' => $wallArea,
            'bricks_per_sqm' => $bricksPerSqm,
            'total_bricks' => $totalBricks,
            'mortar_volume_per_brick' => $mortarVolumePerBrick,
            'total_mortar_volume' => $totalMortarVolume,
            'cement_kg' => $cementKg,
            'cement_sak' => $cementKg / $cementWeightPerSak,
            'cement_weight_per_sak' => $cementWeightPerSak,
            'sand_m3' => $sandM3,
            'sand_kg' => $sandM3 * 1600, // density pasir
            'water_liters' => $waterLiters,
            'ratio_used' => "{$mortarFormula->cement_ratio}:{$mortarFormula->sand_ratio}",
        ];
    }

    /**
     * Mode 2: FIELD MODE - Package Engineering Based
     * Dari rumus 2.xlsx dengan engineering factors
     */
    public static function calculateFieldMode(array $params): array
    {
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $wallArea = $wallLength * $wallHeight;
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();

        // Get cement data from database
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 40; // fallback to 40kg if not found (rumus 2.xlsx uses 40kg)

        // Ratio dari parameter atau default
        $cementRatio = $params['custom_cement_ratio'] ?? 1;
        $sandRatio = $params['custom_sand_ratio'] ?? 3; // default dari rumus 2.xlsx

        // Konstanta dari rumus 2.xlsx
        $CEMENT_SAK_VOLUME = 0.036; // m³ (dari AJ7*AM7*AP7/1000000)
        $SHRINKAGE_FACTOR = 0.15; // 15%
        $WATER_PERCENTAGE = 0.30; // 30%
        $WATER_FACTOR = 0.2; // dari AW7

        // Hitung bricks per m²
        $brickLength = $brick->dimension_length ?? 19.2;
        $brickWidth = $brick->dimension_width ?? 9;
        $brickHeight = $brick->dimension_height ?? 8;

        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;
        $bricksPerSqm = 1 / $areaPerBrick;
        $totalBricks = $wallArea * $bricksPerSqm;

        // Volume adukan menggunakan formula dari rumus 2.xlsx
        // Formula: (S7+Y7+(AW7*AE6))*AS7*(1-AU7)
        $totalSakRatio = $cementRatio + $sandRatio + ($WATER_FACTOR * $WATER_PERCENTAGE);
        $volumePerLuasPasangan = $totalSakRatio * $CEMENT_SAK_VOLUME * (1 - $SHRINKAGE_FACTOR);

        // Luas pasangan per m² dinding (dari Excel: 3.882375 untuk ratio tertentu)
        // Untuk simplifikasi, kita hitung dinamis
        $luasPasanganPerM2 = $bricksPerSqm * $areaPerBrick; // ≈ 1

        // Volume per m² dinding
        $volumePerM2Dinding = $volumePerLuasPasangan / 3.882375; // normalize dari Excel

        // Total volume untuk wall area
        $totalMortarVolume = $volumePerM2Dinding * $wallArea;

        // Convert ke sak-sak
        $totalSak = $totalMortarVolume / $CEMENT_SAK_VOLUME;
        $totalSakActual = $cementRatio + $sandRatio; // tanpa water

        $cementSak = $totalSak * ($cementRatio / $totalSakRatio);
        $sandSak = $totalSak * ($sandRatio / $totalSakRatio);
        $waterLiters = $totalSak * $CEMENT_SAK_VOLUME * $WATER_PERCENTAGE * 1000;

        // Convert sak ke kg using database weight
        $cementKg = $cementSak * $cementWeightPerSak;
        $sandM3 = $sandSak * $CEMENT_SAK_VOLUME; // asumsi sak pasir sama volume

        return [
            'mode' => 'Field Mode (Package Engineering)',
            'method' => 'Berbasis kemasan dengan shrinkage 15% dan water factor (dari rumus 2.xlsx)',
            'wall_area' => $wallArea,
            'bricks_per_sqm' => $bricksPerSqm,
            'total_bricks' => $totalBricks,
            'total_mortar_volume' => $totalMortarVolume,
            'cement_sak' => $cementSak,
            'cement_weight_per_sak' => $cementWeightPerSak,
            'cement_kg' => $cementKg,
            'sand_sak' => $sandSak,
            'sand_m3' => $sandM3,
            'sand_kg' => $sandM3 * 1600,
            'water_liters' => $waterLiters,
            'ratio_used' => "{$cementRatio}:{$sandRatio}",
            'engineering_factors' => [
                'sak_volume' => $CEMENT_SAK_VOLUME,
                'shrinkage' => $SHRINKAGE_FACTOR,
                'water_percentage' => $WATER_PERCENTAGE,
            ],
        ];
    }

    /**
     * Mode 3: SIMPLE MODE - Package Basic (Corrected)
     * Rumus user awal dengan koreksi volume sak
     */
    public static function calculateSimpleMode(array $params): array
    {
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $wallArea = $wallLength * $wallHeight;
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $brick = Brick::find($params['brick_id'] ?? null) ?? Brick::first();

        // Get cement data from database
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $cementWeightPerSak = $cement ? $cement->package_weight_net : 50; // fallback to 50kg if not found

        // Ratio
        $cementRatio = $params['custom_cement_ratio'] ?? 1;
        $sandRatio = $params['custom_sand_ratio'] ?? 4;

        // Volume sak - Calculate from cement weight and density
        // Cement density typically 1440 kg/m³
        $cementDensity = 1440; // kg/m³
        $CEMENT_SAK_VOLUME_CORRECTED = $cementWeightPerSak / $cementDensity; // m³
        $WATER_PERCENTAGE = 0.30; // 30%

        // Hitung bricks per m²
        $brickLength = $brick->dimension_length ?? 19.2;
        $brickWidth = $brick->dimension_width ?? 9;
        $brickHeight = $brick->dimension_height ?? 8;

        $visibleWidth = ($brickLength + $mortarThickness) / 100;
        $visibleHeight = ($brickHeight + $mortarThickness) / 100;
        $areaPerBrick = $visibleWidth * $visibleHeight;
        $bricksPerSqm = 1 / $areaPerBrick;

        // Total bricks
        $totalBricks = $wallArea * $bricksPerSqm;

        // Kebutuhan per m² (koreksi dari asumsi 1 sak = 1 m²)
        // Berdasarkan analisa: sekitar 0.3-0.4 sak per m² lebih realistis
        $cementSakPerM2 = 0.35; // estimasi yang lebih realistis
        $totalCementSak = $cementSakPerM2 * $wallArea;

        // Pasir berdasarkan ratio
        $totalSandSak = $totalCementSak * $sandRatio;

        // Volume pasir
        $sandM3 = $totalSandSak * $CEMENT_SAK_VOLUME_CORRECTED;

        // Air: (total sak) × volume × 30% × 1000
        $totalSak = $totalCementSak + $totalSandSak;
        $waterLiters = $totalSak * $CEMENT_SAK_VOLUME_CORRECTED * $WATER_PERCENTAGE * 1000;

        // Cement kg using database weight
        $cementKg = $totalCementSak * $cementWeightPerSak;

        return [
            'mode' => 'Simple Mode (Package Basic - Corrected)',
            'method' => 'Berbasis kemasan sederhana dengan volume sak dihitung dari berat dan density semen',
            'wall_area' => $wallArea,
            'bricks_per_sqm' => $bricksPerSqm,
            'total_bricks' => $totalBricks,
            'cement_sak' => $totalCementSak,
            'cement_weight_per_sak' => $cementWeightPerSak,
            'cement_kg' => $cementKg,
            'sand_sak' => $totalSandSak,
            'sand_m3' => $sandM3,
            'sand_kg' => $sandM3 * 1600,
            'water_liters' => $waterLiters,
            'ratio_used' => "{$cementRatio}:{$sandRatio}",
            'assumptions' => [
                'cement_sak_per_m2' => $cementSakPerM2,
                'sak_volume' => $CEMENT_SAK_VOLUME_CORRECTED,
                'cement_density' => $cementDensity,
            ],
        ];
    }

    /**
     * Calculate all three modes at once for comparison
     */
    public static function calculateAllModes(array $params): array
    {
        return [
            'mode_1_professional' => self::calculateProfessionalMode($params),
            'mode_2_field' => self::calculateFieldMode($params),
            'mode_3_simple' => self::calculateSimpleMode($params),
            'input_params' => [
                'wall_length' => $params['wall_length'],
                'wall_height' => $params['wall_height'],
                'wall_area' => $params['wall_length'] * $params['wall_height'],
                'mortar_thickness' => $params['mortar_thickness'] ?? 1.0,
                'ratio' => ($params['custom_cement_ratio'] ?? 1) . ':' . ($params['custom_sand_ratio'] ?? 4),
            ],
        ];
    }

    /**
     * Helper: Calculate mortar volume per brick
     * Formula: (p + t + tebal adukan) × lebar × tebal adukan / 1000000
     */
    private static function calculateMortarVolumePerBrick(
        float $length,
        float $width,
        float $height,
        float $mortarThickness,
        string $installationCode
    ): float {
        // Formula: (panjang + tinggi + tebal adukan) × lebar × tebal adukan / 1000000
        return (($length + $height + $mortarThickness) * $width * $mortarThickness) / 1000000;
    }
}
