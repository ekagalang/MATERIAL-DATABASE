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
     * 
     * @param array $params
     * @return self
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
            
            $mortarFormula = $customFormula;
        }

        // Hitung luas dinding
        $wallArea = $wallLength * $wallHeight;

        // Ambil dimensi bata
        $brick = $brickId ? Brick::find($brickId) : Brick::first();
        
        if (!$brick) {
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

        // Hitung volume adukan per bata
        $mortarVolumePerBrick = self::calculateMortarVolumePerBrick(
            $brickLength,
            $brickWidth,
            $brickHeight,
            $mortarThickness,
            $installationType->code
        );

        // Hitung total volume adukan
        $mortarVolume = $mortarVolumePerBrick * $brickQuantity;

        // Hitung material dari formula (pass custom ratio jika ada)
        if ($useCustomRatio && $customCementRatio && $customSandRatio) {
            $materials = $mortarFormula->calculateMaterials(
                $mortarVolume,
                $customCementRatio,
                $customSandRatio
            );
        } else {
            $materials = $mortarFormula->calculateMaterials($mortarVolume);
        }

        // Hitung biaya
        $brickPrice = $brick->price_per_piece ?? 0;
        $brickTotalCost = $brickQuantity * $brickPrice;

        $cement = $cementId ? Cement::find($cementId) : null;
        $cementPricePerSak = $cement ? $cement->package_price : 0;
        $cementTotalCost = $materials['cement_sak_50kg'] * $cementPricePerSak;

        $sand = $sandId ? Sand::find($sandId) : null;
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
        $calculation = new self();
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
     * Calculate cement kg per m3 based on ratio
     */
    private static function calculateCementKgPerM3(float $cementRatio, float $sandRatio): float
    {
        // Formula sederhana: untuk setiap m³ adukan
        // Semakin banyak semen (ratio tinggi), semakin banyak kg semen yang dibutuhkan
        
        // Rumus dasar: 
        // Untuk 1:4 = sekitar 325 kg/m³
        // Untuk 1:5 = sekitar 275 kg/m³
        // Untuk 1:6 = sekitar 235 kg/m³
        
        // Formula interpolasi
        $totalRatio = $cementRatio + $sandRatio;
        $cementPercentage = $cementRatio / $totalRatio;
        
        // Base calculation (bisa disesuaikan dengan data empiris)
        return round(1300 * $cementPercentage, 2);
    }

    /**
     * Calculate sand m3 per m3 based on ratio
     */
    private static function calculateSandM3PerM3(float $cementRatio, float $sandRatio): float
    {
        // Formula sederhana berdasarkan perbandingan volume
        $totalRatio = $cementRatio + $sandRatio;
        $sandPercentage = $sandRatio / $totalRatio;
        
        return round($sandPercentage * 0.9, 4); // Sekitar 90% dari persentase (karena ada void space)
    }

    /**
     * Calculate mortar volume per brick
     * 
     * @param float $length Panjang bata (cm)
     * @param float $width Lebar bata (cm)
     * @param float $height Tinggi bata (cm)
     * @param float $mortarThickness Tebal adukan (cm)
     * @param string $installationCode Kode jenis pemasangan
     * @return float Volume dalam m³
     */
    private static function calculateMortarVolumePerBrick(
        float $length,
        float $width,
        float $height,
        float $mortarThickness,
        string $installationCode
    ): float {
        // Konversi cm ke meter
        $l = $length / 100;
        $w = $width / 100;
        $h = $height / 100;
        $t = $mortarThickness / 100;

        // Adukan diterapkan di bagian ATAS dan KANAN bata
        // Tergantung jenis pemasangan
        
        switch ($installationCode) {
            case 'half': // 1/2 Bata
                // Terlihat: panjang × tinggi
                // Adukan atas: panjang × lebar × tebal
                // Adukan kanan: tinggi × lebar × tebal
                $volumeTop = $l * $w * $t;
                $volumeRight = $h * $w * $t;
                break;

            case 'one': // 1 Bata
                // Terlihat: lebar × tinggi
                // Adukan atas: lebar × panjang × tebal
                // Adukan kanan: tinggi × panjang × tebal
                $volumeTop = $w * $l * $t;
                $volumeRight = $h * $l * $t;
                break;

            case 'quarter': // 1/4 Bata
                // Terlihat: panjang × lebar
                // Adukan atas: panjang × tinggi × tebal
                // Adukan kanan: lebar × tinggi × tebal
                $volumeTop = $l * $h * $t;
                $volumeRight = $w * $h * $t;
                break;

            case 'rollag': // Rollag
                // Terlihat: tinggi × lebar
                // Adukan atas: tinggi × panjang × tebal
                // Adukan kanan: lebar × panjang × tebal
                $volumeTop = $h * $l * $t;
                $volumeRight = $w * $l * $t;
                break;

            default:
                return 0;
        }

        return $volumeTop + $volumeRight;
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