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
     * Perform complete calculation
     */
    /**
     * Perform complete calculation
     */
    public static function performCalculation(array $params): self
    {
        // Ambil data dari params
        $wallLength = $params['wall_length'];
        $wallHeight = $params['wall_height'];
        $installationTypeId = $params['installation_type_id'];
        $mortarThickness = $params['mortar_thickness'] ?? 1.0;
        $mortarFormulaId = $params['mortar_formula_id'];
        $brickId = $params['brick_id'] ?? null;
        $cementId = $params['cement_id'] ?? null;
        $sandId = $params['sand_id'] ?? null;

        // Custom ratio
        $useCustomRatio = isset($params['use_custom_ratio']) && $params['use_custom_ratio'] == '1';
        $customCementRatio = $params['custom_cement_ratio'] ?? null;
        $customSandRatio = $params['custom_sand_ratio'] ?? null;
        $customWaterRatio = $params['custom_water_ratio'] ?? null;

        // Load relationships
        $installationType = BrickInstallationType::findOrFail($installationTypeId);
        $mortarFormula = MortarFormula::findOrFail($mortarFormulaId);

        // Jika pakai custom ratio, override formula
        if ($useCustomRatio && $customCementRatio && $customSandRatio) {
            // Buat temporary formula object dengan custom ratio
            $customFormula = clone $mortarFormula;
            $customFormula->cement_ratio = $customCementRatio;
            $customFormula->sand_ratio = $customSandRatio;
            $customFormula->water_ratio = $customWaterRatio ?? $mortarFormula->water_ratio;

            // Recalculate material requirements based on custom ratio
            $customFormula->cement_kg_per_m3 = self::calculateCementKgPerM3(
                $customCementRatio,
                $customSandRatio
            );
            $customFormula->sand_m3_per_m3 = self::calculateSandM3PerM3(
                $customCementRatio,
                $customSandRatio
            );
            $customFormula->water_liter_per_m3 = self::calculateWaterLiterPerM3(
                $customCementRatio,
                $customSandRatio
            );

            $mortarFormula = $customFormula;
        }

        // Hitung luas dinding
        $wallArea = $wallLength * $wallHeight;

        // Ambil dimensi bata
        $brick = $brickId ? Brick::find($brickId) : Brick::first();

        if (! $brick) {
            throw new \Exception('Tidak ada data bata di database. Silakan tambahkan data bata terlebih dahulu.');
        }

        $brickLength = $brick->dimension_length ?? 20; // cm
        $brickWidth = $brick->dimension_width ?? 10; // cm
        $brickHeight = $brick->dimension_height ?? 5; // cm

        // Hitung jumlah bata per m²
        $bricksPerSqm = $installationType->calculateBricksPerSqm(
            $brickLength,
            $brickWidth,
            $brickHeight,
            $mortarThickness
        );

        // Hitung total bata
        $brickQuantity = $wallArea * $bricksPerSqm;

        // Hitung volume adukan - menggunakan rumus matematis + waste factor
        // Rumus: (panjang × lebar × tebal) + (tinggi × lebar × tebal) × waste_factor

        // Hitung volume adukan per bata menggunakan rumus matematis
        $mortarVolumePerBrick = self::calculateMortarVolumePerBrick(
            $brickLength,
            $brickWidth,
            $brickHeight,
            $mortarThickness,
            $installationType->code
        );

        // Hitung total volume tanpa waste factor dulu
        $mortarVolumeRaw = $mortarVolumePerBrick * $brickQuantity;

        // Aplikasikan waste factor untuk mendapatkan volume final
        // Waste factor mencakup: shrinkage, spillage, waste, dan lapisan dasar
        $wasteFactor = $installationType->waste_factor ?? 1.0;
        $mortarVolume = $mortarVolumeRaw * $wasteFactor;

        // Update mortar volume per brick dengan waste factor
        $mortarVolumePerBrick = $brickQuantity > 0 ? ($mortarVolume / $brickQuantity) : 0;

        // Load cement dan sand objects untuk perhitungan
        $cement = $cementId ? Cement::find($cementId) : Cement::first();
        $sand = $sandId ? Sand::find($sandId) : Sand::first();

        // Hitung material dari formula (pass custom ratio + cement/sand objects)
        if ($useCustomRatio && $customCementRatio && $customSandRatio) {
            $materials = $mortarFormula->calculateMaterials(
                $mortarVolume,
                $customCementRatio,
                $customSandRatio,
                $cement,
                $sand
            );
        } else {
            $materials = $mortarFormula->calculateMaterials(
                $mortarVolume,
                null,
                null,
                $cement,
                $sand
            );
        }

        // Hitung biaya
        $brickPrice = $brick->price_per_piece ?? 0;
        $brickTotalCost = $brickQuantity * $brickPrice;

        // Hitung biaya cement (sudah di-load di atas)
        $cementPricePerSak = $cement ? $cement->package_price : 0;
        $cementTotalCost = $materials['cement_sak_50kg'] * $cementPricePerSak;

        // Hitung biaya sand (sudah di-load di atas)
        // Gunakan comparison_price_per_m3 jika ada, kalau tidak hitung dari package_price dan volume
        $sandPricePerM3 = 0;
        if ($sand) {
            if ($sand->comparison_price_per_m3 && $sand->comparison_price_per_m3 > 0) {
                $sandPricePerM3 = $sand->comparison_price_per_m3;
            } elseif ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
                $sandPricePerM3 = $sand->package_price / $sand->package_volume;
            }
        }
        $sandTotalCost = $materials['sand_m3'] * $sandPricePerM3;

        $totalCost = $brickTotalCost + $cementTotalCost + $sandTotalCost;

        // Create calculation record
        $calculation = new self;
        $calculation->fill([
            'project_name' => $params['project_name'] ?? null,
            'notes' => $params['notes'] ?? null,
            'wall_length' => $wallLength,
            'wall_height' => $wallHeight,
            'wall_area' => $wallArea,
            'installation_type_id' => $installationTypeId,
            'mortar_thickness' => $mortarThickness,
            'mortar_formula_id' => $mortarFormulaId,

            // Custom ratio fields
            'use_custom_ratio' => $useCustomRatio,
            'custom_cement_ratio' => $useCustomRatio ? $customCementRatio : null,
            'custom_sand_ratio' => $useCustomRatio ? $customSandRatio : null,
            'custom_water_ratio' => $useCustomRatio ? $customWaterRatio : null,

            'brick_quantity' => $brickQuantity,
            'brick_id' => $brickId,
            'brick_price_per_piece' => $brickPrice,
            'brick_total_cost' => $brickTotalCost,
            'mortar_volume' => $mortarVolume,
            'mortar_volume_per_brick' => $mortarVolumePerBrick,
            'cement_quantity_40kg' => $materials['cement_sak_40kg'],
            'cement_quantity_50kg' => $materials['cement_sak_50kg'],
            'cement_kg' => $materials['cement_kg'],
            'cement_id' => $cementId,
            'cement_price_per_sak' => $cementPricePerSak,
            'cement_total_cost' => $cementTotalCost,
            'sand_sak' => $materials['sand_sak'],
            'sand_m3' => $materials['sand_m3'],
            'sand_kg' => $materials['sand_kg'],
            'sand_id' => $sandId,
            'sand_price_per_m3' => $sandPricePerM3,
            'sand_total_cost' => $sandTotalCost,
            'water_liters' => $materials['water_liters'],
            'total_material_cost' => $totalCost,
            'calculation_params' => [
                'brick_dimensions' => [
                    'length' => $brickLength,
                    'width' => $brickWidth,
                    'height' => $brickHeight,
                ],
                'bricks_per_sqm' => $bricksPerSqm,
                'mortar_formula_name' => $mortarFormula->name,
                'installation_type_name' => $installationType->name,
                'ratio_used' => $useCustomRatio ?
                    "{$customCementRatio}:{$customSandRatio}" :
                    "{$mortarFormula->cement_ratio}:{$mortarFormula->sand_ratio}",
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
     * @param float $x Target value untuk interpolasi
     * @param array $dataPoints Array dengan format [x => y]
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

                $result = $y0 + ($y1 - $y0) * ($x - $x0) / ($x1 - $x0);
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
        string $installationCode
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
                'length' => $this->wall_length.' m',
                'height' => $this->wall_height.' m',
                'area' => $this->wall_area.' m²',
            ],
            'brick_info' => [
                'quantity' => number_format($this->brick_quantity, 2).' buah',
                'type' => $this->installationType->name ?? '-',
                'cost' => 'Rp '.number_format($this->brick_total_cost, 0, ',', '.'),
            ],
            'mortar_info' => [
                'volume' => number_format($this->mortar_volume, 6).' m³',
                'formula' => $this->mortarFormula->name ?? '-',
                'thickness' => $this->mortar_thickness.' cm',
            ],
            'materials' => [
                'cement' => [
                    '40kg' => number_format($this->cement_quantity_40kg, 2).' sak',
                    '50kg' => number_format($this->cement_quantity_50kg, 2).' sak',
                    'kg' => number_format($this->cement_kg, 2).' kg',
                    'cost' => 'Rp '.number_format($this->cement_total_cost, 0, ',', '.'),
                ],
                'sand' => [
                    'sak' => number_format($this->sand_sak, 2).' karung',
                    'kg' => number_format($this->sand_kg, 2).' kg',
                    'm3' => number_format($this->sand_m3, 6).' m³',
                    'cost' => 'Rp '.number_format($this->sand_total_cost, 0, ',', '.'),
                ],
                'water' => [
                    'liters' => number_format($this->water_liters, 2).' liter',
                ],
            ],
            'total_cost' => 'Rp '.number_format($this->total_material_cost, 0, ',', '.'),
        ];
    }
}
