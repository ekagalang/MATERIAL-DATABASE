<?php

namespace App\Models;

use App\Helpers\NumberHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrickCalculation extends Model
{
    protected $fillable = [
        'project_name',
        'notes',
        'project_address',
        'project_latitude',
        'project_longitude',
        'project_place_id',
        'wall_length',
        'wall_height',
        'wall_area',
        'installation_type_id',
        'mortar_thickness',
        'mortar_formula_id',
        'brick_quantity',
        'brick_id',
        'brick_price_per_piece',
        'brick_total_cost',
        'mortar_volume',
        'mortar_volume_per_brick',
        'cement_quantity_40kg',
        'cement_quantity_50kg',
        'cement_kg',
        'cement_package_weight',
        'cement_quantity_sak',
        'cement_id',
        'cement_price_per_sak',
        'cement_total_cost',
        'sand_sak',
        'sand_m3',
        'sand_kg',
        'sand_id',
        'sand_price_per_m3',
        'sand_total_cost',
        'cat_id',
        'cat_quantity',
        'cat_kg',
        'paint_liters',
        'cat_price_per_package',
        'cat_total_cost',
        'water_liters',
        'total_material_cost',
        'calculation_params',
        'custom_cement_ratio',
        'custom_sand_ratio',
        'custom_water_ratio',
        'use_custom_ratio',
        'ceramic_id',
        'ceramic_quantity',
        'ceramic_packages',
        'ceramic_total_cost',
        'nat_id',
        'nat_quantity',
        'nat_kg',
        'nat_total_cost',
    ];

    protected $casts = [
        'wall_length' => 'decimal:2',
        'wall_height' => 'decimal:2',
        'wall_area' => 'decimal:2',
        'mortar_thickness' => 'decimal:2',
        'brick_quantity' => 'decimal:2',
        'brick_price_per_piece' => 'decimal:2',
        'brick_total_cost' => 'decimal:2',
        'mortar_volume' => 'decimal:6',
        'mortar_volume_per_brick' => 'decimal:6',
        'cement_quantity_40kg' => 'decimal:4',
        'cement_quantity_50kg' => 'decimal:4',
        'cement_kg' => 'decimal:2',
        'cement_package_weight' => 'decimal:2',
        'cement_quantity_sak' => 'decimal:4',
        'cement_price_per_sak' => 'decimal:2',
        'cement_total_cost' => 'decimal:2',
        'sand_sak' => 'decimal:4',
        'sand_m3' => 'decimal:6',
        'sand_kg' => 'decimal:2',
        'sand_price_per_m3' => 'decimal:2',
        'sand_total_cost' => 'decimal:2',
        'cat_quantity' => 'decimal:2',
        'cat_kg' => 'decimal:2',
        'paint_liters' => 'decimal:2',
        'cat_price_per_package' => 'decimal:2',
        'cat_total_cost' => 'decimal:2',
        'ceramic_quantity' => 'decimal:2',
        'ceramic_packages' => 'decimal:2',
        'ceramic_total_cost' => 'decimal:2',
        'nat_quantity' => 'decimal:2',
        'nat_kg' => 'decimal:2',
        'nat_total_cost' => 'decimal:2',
        'water_liters' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'calculation_params' => 'array',
        'custom_cement_ratio' => 'decimal:4',
        'custom_sand_ratio' => 'decimal:4',
        'custom_water_ratio' => 'decimal:4',
        'use_custom_ratio' => 'boolean',
        'project_latitude' => 'float',
        'project_longitude' => 'float',
    ];

    /**
     * Relationships
     */
    public function installationType(): BelongsTo
    {
        return $this->belongsTo(BrickInstallationType::class, 'installation_type_id');
    }

    public function mortarFormula(): BelongsTo
    {
        return $this->belongsTo(MortarFormula::class, 'mortar_formula_id');
    }

    public function brick(): BelongsTo
    {
        return $this->belongsTo(Brick::class);
    }

    public function cement(): BelongsTo
    {
        return $this->belongsTo(Cement::class);
    }

    public function sand(): BelongsTo
    {
        return $this->belongsTo(Sand::class);
    }

    public function cat(): BelongsTo
    {
        return $this->belongsTo(Cat::class);
    }

    public function ceramic(): BelongsTo
    {
        return $this->belongsTo(Ceramic::class);
    }

    public function nat(): BelongsTo
    {
        return $this->belongsTo(Nat::class, 'nat_id');
    }

    /**
     * Perform complete calculation using Formula Bank
     */
    public static function performCalculation(array $params): self
    {
        $n = static fn($value, $decimals = null) => (float) ($value ?? 0);

        // Get work_type from params (should match formula code)
        $formulaCode = $params['work_type'] ?? null;

        // Fallback: if work_type not provided or invalid, use first available formula
        if (!$formulaCode || !\App\Services\FormulaRegistry::has($formulaCode)) {
            $availableFormulas = \App\Services\FormulaRegistry::all();
            if (empty($availableFormulas)) {
                throw new \Exception('Tidak ada formula yang tersedia di sistem');
            }
            $formulaCode = $availableFormulas[0]['code'];
        }

        // Get formula instance from Formula Bank
        $formula = \App\Services\FormulaRegistry::instance($formulaCode);

        if (!$formula) {
            throw new \Exception(
                "Formula '{$formulaCode}' tidak ditemukan di Formula Bank. Formula yang tersedia: " .
                    implode(', ', \App\Services\FormulaRegistry::codes()),
            );
        }

        // Execute formula calculation
        $result = $formula->calculate($params);

        // Load relationships for additional data
        $installationType = BrickInstallationType::findOrFail($params['installation_type_id']);
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $brick = isset($params['brick_id']) ? Brick::find($params['brick_id']) : Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        $workType = $params['work_type'] ?? $formulaCode;
        if ($workType === 'brick_rollag') {
            $brickLength = $n($brick?->dimension_length ?? 0);
            if ($brickLength <= 0) {
                $brickLength = 19.2;
            }
            $params['wall_height'] = $n($brickLength / 100);
        }
        $ceramic = null;
        if (
            in_array($workType, ['tile_installation', 'grout_tile'], true) ||
            !empty($params['ceramic_id']) ||
            !empty($params['ceramic_length']) ||
            !empty($params['ceramic_width'])
        ) {
            $ceramic = isset($params['ceramic_id']) ? Ceramic::find($params['ceramic_id']) : Ceramic::first();
        }

        // Extract values from formula result
        $wallLength = $n($params['wall_length']);
        $wallHeight = $n($params['wall_height'] ?? 0);
        $wallArea = $n($wallLength * $wallHeight);
        $mortarThickness = $n($params['mortar_thickness'] ?? 1.0);
        $useCustomRatio = isset($params['use_custom_ratio']) && $params['use_custom_ratio'] == '1';

        // Get cement package weight for sak calculation
        $cementPackageWeight = $n($cement ? $cement->package_weight_net : 50);
        $cementQuantitySak = $n(($result['cement_kg'] ?? 0) / $cementPackageWeight);

        // Calculate 40kg and 50kg quantities for backward compatibility
        $cementQuantity40kg = $n(($result['cement_kg'] ?? 0) / 40);
        $cementQuantity50kg = $n(($result['cement_kg'] ?? 0) / 50);

        // Mortar volume per brick
        $mortarVolumePerBrick =
            ($result['total_bricks'] ?? 0) > 0
                ? $n((($result['cement_m3'] ?? 0) + ($result['sand_m3'] ?? 0)) / $result['total_bricks'])
                : 0;

        $ceramicLength = $ceramic ? $n($ceramic->dimension_length) : null;
        $ceramicWidth = $ceramic ? $n($ceramic->dimension_width) : null;
        if (isset($params['ceramic_length']) && $params['ceramic_length'] > 0) {
            $ceramicLength = $n($params['ceramic_length']);
        }
        if (isset($params['ceramic_width']) && $params['ceramic_width'] > 0) {
            $ceramicWidth = $n($params['ceramic_width']);
        }

        $groutThickness = isset($params['grout_thickness']) ? $n($params['grout_thickness']) : null;

        $calculationParams = [
            'formula_used' => $formulaCode,
            'work_type' => $params['work_type'] ?? $formulaCode,
            'brick_dimensions' => [
                'length' => $brick->dimension_length ?? 20,
                'width' => $brick->dimension_width ?? 10,
                'height' => $brick->dimension_height ?? 5,
            ],
            'installation_type_name' => $installationType->name,
            'mortar_formula_name' => $mortarFormula->name,
            'ratio_used' => $useCustomRatio
                ? "{$params['custom_cement_ratio']}:{$params['custom_sand_ratio']}"
                : "{$mortarFormula->cement_ratio}:{$mortarFormula->sand_ratio}",
            'layer_count' => $params['layer_count'] ?? 1,
            'plaster_sides' => $params['plaster_sides'] ?? 1,
            'skim_sides' => $params['skim_sides'] ?? 1,
        ];

        if ($groutThickness !== null) {
            $calculationParams['grout_thickness'] = $groutThickness;
        }

        if (!empty($params['nat_id'])) {
            $calculationParams['nat_id'] = $params['nat_id'];
        }

        if ($ceramicLength !== null || $ceramicWidth !== null) {
            $calculationParams['ceramic_dimensions'] = [
                'length' => $ceramicLength,
                'width' => $ceramicWidth,
            ];
        }

        // Create calculation record
        $calculation = new self();
        $calculation->fill([
            'project_name' => $params['project_name'] ?? null,
            'notes' => $params['notes'] ?? null,
            'project_address' => $params['project_address'] ?? null,
            'project_latitude' => $params['project_latitude'] ?? null,
            'project_longitude' => $params['project_longitude'] ?? null,
            'project_place_id' => $params['project_place_id'] ?? null,
            'wall_length' => $wallLength,
            'wall_height' => $wallHeight,
            'wall_area' => $wallArea,
            'installation_type_id' => $params['installation_type_id'],
            'mortar_thickness' => $mortarThickness,
            'mortar_formula_id' => $params['mortar_formula_id'],

            // Custom ratio fields
            'use_custom_ratio' => $useCustomRatio,
            'custom_cement_ratio' => $useCustomRatio ? $params['custom_cement_ratio'] ?? null : null,
            'custom_sand_ratio' => $useCustomRatio ? $params['custom_sand_ratio'] ?? null : null,
            'custom_water_ratio' => $useCustomRatio ? $params['custom_water_ratio'] ?? null : null,

            // Brick results
            'brick_quantity' => $result['total_bricks'] ?? 0,
            'brick_id' => $params['brick_id'] ?? null,
            'brick_price_per_piece' => $result['brick_price_per_piece'] ?? 0,
            'brick_total_cost' => $result['total_brick_price'] ?? 0,

            // Mortar volume
            'mortar_volume' => $n(($result['cement_m3'] ?? 0) + ($result['sand_m3'] ?? 0)),
            'mortar_volume_per_brick' => $mortarVolumePerBrick,

            // Cement results
            'cement_quantity_40kg' => $cementQuantity40kg,
            'cement_quantity_50kg' => $cementQuantity50kg,
            'cement_kg' => $result['cement_kg'] ?? 0,
            'cement_package_weight' => $cementPackageWeight,
            'cement_quantity_sak' => $cementQuantitySak,
            'cement_id' => $params['cement_id'] ?? null,
            'cement_price_per_sak' => $result['cement_price_per_sak'] ?? 0,
            'cement_total_cost' => $result['total_cement_price'] ?? 0,

            // Sand results
            'sand_sak' => $result['sand_sak'] ?? 0,
            'sand_m3' => $result['sand_m3'] ?? 0,
            'sand_kg' => $n(($result['sand_m3'] ?? 0) * 1600), // Sand density kg/M3
            'sand_id' => $params['sand_id'] ?? null,
            'sand_price_per_m3' => $result['sand_price_per_m3'] ?? 0,
            'sand_total_cost' => $result['total_sand_price'] ?? 0,

            // Cat results
            'cat_id' => $params['cat_id'] ?? null,
            'cat_quantity' => $result['cat_packages'] ?? 0,
            'cat_kg' => $result['cat_kg'] ?? 0,
            'paint_liters' => $result['cat_liters'] ?? 0,
            'cat_price_per_package' => $result['cat_price_per_package'] ?? 0,
            'cat_total_cost' => $result['total_cat_price'] ?? 0,

            // Ceramic results
            'ceramic_id' => $params['ceramic_id'] ?? null,
            'ceramic_quantity' => $result['total_tiles'] ?? 0,
            'ceramic_packages' => $result['tiles_packages'] ?? 0,
            'ceramic_total_cost' => $result['total_ceramic_price'] ?? 0,

            // Nat results
            'nat_id' => $params['nat_id'] ?? null,
            'nat_quantity' => $result['grout_packages'] ?? 0,
            'nat_kg' => $result['grout_kg'] ?? 0,
            'nat_total_cost' => $result['total_grout_price'] ?? 0,

            // Water
            'water_liters' => $result['total_water_liters'] ?? ($result['water_liters'] ?? 0),

            // Total cost
            'total_material_cost' => $n($result['grand_total'] ?? 0, 0),

            // Store calculation params for reference
            'calculation_params' => $calculationParams,
        ]);

        return $calculation;
    }

    /**
     * Calculate cement kg per m3 based on ratio using piecewise linear interpolation
     * Data points dari Excel untuk akurasi maksimal
     */
    private static function calculateCementKgPerM3(float $cementRatio, float $sandRatio): float
    {
        // Data points dari Excel/Seeder (rasio pasir → kg semen per M3)
        $dataPoints = [
            3 => 325.0,
            4 => 321.96875,
            5 => 275.0,
            6 => 235.0,
        ];

        return self::interpolate($sandRatio, $dataPoints);
    }

    /**
     * Calculate sand m3 per m3 based on ratio using piecewise linear interpolation
     * Data points dari Excel untuk akurasi maksimal
     */
    private static function calculateSandM3PerM3(float $cementRatio, float $sandRatio): float
    {
        // Data points dari Excel/Seeder (rasio pasir → M3 pasir per M3 adukan)
        $dataPoints = [
            3 => 0.87,
            4 => 0.86875,
            5 => 0.89,
            6 => 0.91,
        ];

        return self::interpolate($sandRatio, $dataPoints);
    }

    /**
     * Calculate water liters per m3 based on ratio using piecewise linear interpolation
     * Data points dari Excel untuk akurasi maksimal
     */
    private static function calculateWaterLiterPerM3(float $cementRatio, float $sandRatio): float
    {
        // Data points dari Excel/Seeder (rasio pasir → liter air per M3 adukan)
        $dataPoints = [
            3 => 400.0,
            4 => 347.725,
            5 => 400.0,
            6 => 400.0,
        ];

        return self::interpolate($sandRatio, $dataPoints);
    }

    /**
     * Piecewise linear interpolation/extrapolation helper
     *
     * @param  float  $x  Target value untuk interpolasi
     * @param  array  $dataPoints  Array dengan format [x => y]
     * @return float Interpolated/extrapolated value
     */
    private static function interpolate(float $x, array $dataPoints): float
    {
        // Sort by key
        ksort($dataPoints);

        $xPoints = array_keys($dataPoints);
        $yPoints = array_values($dataPoints);
        $n = count($xPoints);

        // Find bracketing points for interpolation
        for ($i = 0; $i < $n - 1; $i++) {
            if ($x >= $xPoints[$i] && $x <= $xPoints[$i + 1]) {
                // Linear interpolation between two points
                $x0 = $xPoints[$i];
                $x1 = $xPoints[$i + 1];
                $y0 = $yPoints[$i];
                $y1 = $yPoints[$i + 1];

                $result = $y0 + (($y1 - $y0) * ($x - $x0)) / ($x1 - $x0);

                return round($result, 6);
            }
        }

        // Extrapolation for values outside range
        if ($x < $xPoints[0]) {
            // Extrapolate below minimum
            $slope = ($yPoints[1] - $yPoints[0]) / ($xPoints[1] - $xPoints[0]);
            $result = $yPoints[0] + $slope * ($x - $xPoints[0]);

            return round($result, 6);
        } else {
            // Extrapolate above maximum
            $i = $n - 2;
            $slope = ($yPoints[$i + 1] - $yPoints[$i]) / ($xPoints[$i + 1] - $xPoints[$i]);
            $result = $yPoints[$i + 1] + $slope * ($x - $xPoints[$i + 1]);

            return round($result, 6);
        }
    }

    /**
     * Calculate mortar volume per brick
     *
     * @param  float  $length  Panjang bata (cm)
     * @param  float  $width  Lebar bata (cm)
     * @param  float  $height  Tinggi bata (cm)
     * @param  float  $mortarThickness  Tebal adukan (cm)
     * @param  string  $installationCode  Kode jenis pemasangan
     * @return float Volume dalam M3
     */
    private static function calculateMortarVolumePerBrick(
        float $length,
        float $width,
        float $height,
        float $mortarThickness,
        string $installationCode,
    ): float {
        // Formula: (panjang + tinggi + tebal adukan) × lebar × tebal adukan / 1000000
        // Semua dimensi dalam cm, hasil dalam M3
        return (($length + $height + $mortarThickness) * $width * $mortarThickness) / 1000000;
    }

    /**
     * Get formatted summary
     */
    public function getSummary(): array
    {
        return [
            'wall_info' => [
                'length' => $this->wall_length . ' m',
                'height' => $this->wall_height . ' m',
                'area' => $this->wall_area . ' M2',
            ],
            'brick_info' => [
                'quantity' => NumberHelper::format($this->brick_quantity) . ' buah',
                'type' => $this->installationType->name ?? '-',
                'cost' => NumberHelper::currency($this->brick_total_cost),
            ],
            'mortar_info' => [
                'volume' => NumberHelper::format($this->mortar_volume) . ' M3',
                'formula' => $this->mortarFormula->name ?? '-',
                'thickness' => $this->mortar_thickness . ' cm',
            ],
            'materials' => [
                'cement' => [
                    'package_weight' => $this->cement_package_weight ?? 50,
                    'quantity_sak' =>
                        NumberHelper::format($this->cement_quantity_sak ?? $this->cement_quantity_50kg) . ' sak',
                    '40kg' => NumberHelper::format($this->cement_quantity_40kg) . ' sak',
                    '50kg' => NumberHelper::format($this->cement_quantity_50kg) . ' sak',
                    'kg' => NumberHelper::format($this->cement_kg) . ' kg',
                    'cost' => NumberHelper::currency($this->cement_total_cost),
                ],
                'sand' => [
                    'sak' => NumberHelper::format($this->sand_sak) . ' karung',
                    'kg' => NumberHelper::format($this->sand_kg) . ' kg',
                    'm3' => NumberHelper::format($this->sand_m3) . ' M3',
                    'cost' => NumberHelper::currency($this->sand_total_cost),
                ],
                'cat' => [
                    'quantity' => NumberHelper::format($this->cat_quantity) . ' kemasan',
                    'kg' => NumberHelper::format($this->cat_kg) . ' kg',
                    'liters' => NumberHelper::format($this->paint_liters) . ' liter',
                    'cost' => NumberHelper::currency($this->cat_total_cost),
                ],
                'ceramic' => [
                    'quantity' => NumberHelper::format($this->ceramic_quantity) . ' pcs',
                    'packages' => NumberHelper::format($this->ceramic_packages) . ' dus',
                    'cost' => NumberHelper::currency($this->ceramic_total_cost),
                ],
                'nat' => [
                    'quantity' => NumberHelper::format($this->nat_quantity) . ' bks',
                    'kg' => NumberHelper::format($this->nat_kg) . ' kg',
                    'cost' => NumberHelper::currency($this->nat_total_cost),
                ],
                'water' => [
                    'liters' => NumberHelper::format($this->water_liters) . ' liter',
                ],
            ],
            'total_cost' => NumberHelper::currency($this->total_material_cost),
        ];
    }
}
