<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrickCalculation extends Model
{
    protected $fillable = [
        'project_name',
        'notes',
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
        'water_liters',
        'total_material_cost',
        'calculation_params',
        'custom_cement_ratio',
        'custom_sand_ratio',
        'custom_water_ratio',
        'use_custom_ratio',
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
        'water_liters' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'calculation_params' => 'array',
        'custom_cement_ratio' => 'decimal:4',
        'custom_sand_ratio' => 'decimal:4',
        'custom_water_ratio' => 'decimal:4',
        'use_custom_ratio' => 'boolean',
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

    /**
     * Perform complete calculation using Formula Bank
     */
    public static function performCalculation(array $params): self
    {
        // Get work_type and map to formula code
        $workType = $params['work_type'] ?? 'brick_half_installation';

        // Map work_type to formula code (for now only one formula available)
        $formulaCode = match ($workType) {
            'BrickHalfInstallation', 'brick_half_installation' => 'brick_half_installation',
            default => 'brick_half_installation', // Default to brick half installation
        };

        // Get formula instance from Formula Bank
        $formula = \App\Services\FormulaRegistry::instance($formulaCode);

        if (!$formula) {
            throw new \Exception("Formula '{$formulaCode}' tidak ditemukan di Formula Bank");
        }

        // Execute formula calculation
        $result = $formula->calculate($params);

        // Load relationships for additional data
        $installationType = BrickInstallationType::findOrFail($params['installation_type_id']);
        $mortarFormula = MortarFormula::findOrFail($params['mortar_formula_id']);
        $brick = isset($params['brick_id']) ? Brick::find($params['brick_id']) : Brick::first();
        $cement = isset($params['cement_id']) ? Cement::find($params['cement_id']) : Cement::first();
        $sand = isset($params['sand_id']) ? Sand::find($params['sand_id']) : Sand::first();

        // Extract values from formula result
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $wallArea = $wallLength * $wallHeight;
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $useCustomRatio = isset($params['use_custom_ratio']) && $params['use_custom_ratio'] == '1';

        // Get cement package weight for sak calculation
        $cementPackageWeight = $cement ? $cement->package_weight_net : 50;
        $cementQuantitySak = $result['cement_kg'] / $cementPackageWeight;

        // Calculate 40kg and 50kg quantities for backward compatibility
        $cementQuantity40kg = $result['cement_kg'] / 40;
        $cementQuantity50kg = $result['cement_kg'] / 50;

        // Mortar volume per brick
        $mortarVolumePerBrick =
            $result['total_bricks'] > 0 ? ($result['cement_m3'] + $result['sand_m3']) / $result['total_bricks'] : 0;

        // Create calculation record
        $calculation = new self();
        $calculation->fill([
            'project_name' => $params['project_name'] ?? null,
            'notes' => $params['notes'] ?? null,
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
            'brick_quantity' => $result['total_bricks'],
            'brick_id' => $params['brick_id'] ?? null,
            'brick_price_per_piece' => $result['brick_price_per_piece'],
            'brick_total_cost' => $result['total_brick_price'],

            // Mortar volume
            'mortar_volume' => $result['cement_m3'] + $result['sand_m3'],
            'mortar_volume_per_brick' => $mortarVolumePerBrick,

            // Cement results
            'cement_quantity_40kg' => $cementQuantity40kg,
            'cement_quantity_50kg' => $cementQuantity50kg,
            'cement_kg' => $result['cement_kg'],
            'cement_package_weight' => $cementPackageWeight,
            'cement_quantity_sak' => $cementQuantitySak,
            'cement_id' => $params['cement_id'] ?? null,
            'cement_price_per_sak' => $result['cement_price_per_sak'],
            'cement_total_cost' => $result['total_cement_price'],

            // Sand results
            'sand_sak' => $result['sand_sak'],
            'sand_m3' => $result['sand_m3'],
            'sand_kg' => $result['sand_m3'] * 1600, // Sand density kg/m³
            'sand_id' => $params['sand_id'] ?? null,
            'sand_price_per_m3' => $result['sand_price_per_m3'],
            'sand_total_cost' => $result['total_sand_price'],

            // Water
            'water_liters' => $result['water_liters'],

            // Total cost
            'total_material_cost' => $result['grand_total'],

            // Store calculation params for reference
            'calculation_params' => [
                'formula_used' => $formulaCode,
                'work_type' => $workType,
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
            ],
        ]);

        return $calculation;
    }

    /**
     * Calculate cement kg per m3 based on ratio using piecewise linear interpolation
     * Data points dari Excel untuk akurasi maksimal
     */
    private static function calculateCementKgPerM3(float $cementRatio, float $sandRatio): float
    {
        // Data points dari Excel/Seeder (rasio pasir → kg semen per m³)
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
        // Data points dari Excel/Seeder (rasio pasir → m³ pasir per m³ adukan)
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
        // Data points dari Excel/Seeder (rasio pasir → liter air per m³ adukan)
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
     * @return float Volume dalam m³
     */
    private static function calculateMortarVolumePerBrick(
        float $length,
        float $width,
        float $height,
        float $mortarThickness,
        string $installationCode,
    ): float {
        // Formula: (panjang + tinggi + tebal adukan) × lebar × tebal adukan / 1000000
        // Semua dimensi dalam cm, hasil dalam m³
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
                'area' => $this->wall_area . ' m²',
            ],
            'brick_info' => [
                'quantity' => number_format($this->brick_quantity, 2) . ' buah',
                'type' => $this->installationType->name ?? '-',
                'cost' => 'Rp ' . number_format($this->brick_total_cost, 0, ',', '.'),
            ],
            'mortar_info' => [
                'volume' => number_format($this->mortar_volume, 6) . ' m³',
                'formula' => $this->mortarFormula->name ?? '-',
                'thickness' => $this->mortar_thickness . ' cm',
            ],
            'materials' => [
                'cement' => [
                    'package_weight' => $this->cement_package_weight ?? 50,
                    'quantity_sak' =>
                        number_format($this->cement_quantity_sak ?? $this->cement_quantity_50kg, 2) . ' sak',
                    '40kg' => number_format($this->cement_quantity_40kg, 2) . ' sak',
                    '50kg' => number_format($this->cement_quantity_50kg, 2) . ' sak',
                    'kg' => number_format($this->cement_kg, 2) . ' kg',
                    'cost' => 'Rp ' . number_format($this->cement_total_cost, 0, ',', '.'),
                ],
                'sand' => [
                    'sak' => number_format($this->sand_sak, 2) . ' karung',
                    'kg' => number_format($this->sand_kg, 2) . ' kg',
                    'm3' => number_format($this->sand_m3, 6) . ' m³',
                    'cost' => 'Rp ' . number_format($this->sand_total_cost, 0, ',', '.'),
                ],
                'water' => [
                    'liters' => number_format($this->water_liters, 2) . ' liter',
                ],
            ],
            'total_cost' => 'Rp ' . number_format($this->total_material_cost, 0, ',', '.'),
        ];
    }
}
