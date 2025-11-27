<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrickInstallationType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'mortar_volume_per_m2',
        'waste_factor',
        'visible_side_width',
        'visible_side_height',
        'orientation',
        'bricks_per_sqm',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'bricks_per_sqm' => 'decimal:2',
        'mortar_volume_per_m2' => 'decimal:6',
        'waste_factor' => 'decimal:6',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Relationship: Installation type punya banyak calculations
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(BrickCalculation::class, 'installation_type_id');
    }

    /**
     * Hitung jumlah bata per m² berdasarkan dimensi bata tertentu
     *
     * @param  float  $brickLength  Panjang bata (cm)
     * @param  float  $brickWidth  Lebar bata (cm)
     * @param  float  $brickHeight  Tinggi bata (cm)
     * @param  float  $mortarThickness  Tebal adukan (cm)
     */
    public function calculateBricksPerSqm(
        float $brickLength,
        float $brickWidth,
        float $brickHeight,
        float $mortarThickness
    ): float {
        // Konversi cm ke meter
        $length = $brickLength / 100;
        $width = $brickWidth / 100;
        $height = $brickHeight / 100;
        $mortar = $mortarThickness / 100;

        // Tentukan dimensi yang terlihat berdasarkan jenis pemasangan
        switch ($this->code) {
            case 'half': // 1/2 Bata
                // Terlihat: panjang × tinggi
                $visibleWidth = $length + $mortar;
                $visibleHeight = $height + $mortar;
                break;

            case 'one': // 1 Bata
                // Terlihat: lebar × tinggi
                $visibleWidth = $width + $mortar;
                $visibleHeight = $height + $mortar;
                break;

            case 'quarter': // 1/4 Bata
                // Terlihat: panjang × lebar
                $visibleWidth = $length + $mortar;
                $visibleHeight = $width + $mortar;
                break;

            case 'rollag': // Rollag
                // Terlihat: tinggi × lebar
                $visibleWidth = $height + $mortar;
                $visibleHeight = $width + $mortar;
                break;

            default:
                return 0;
        }

        // Luas 1 bata + adukan (m²)
        $areaBrickWithMortar = $visibleWidth * $visibleHeight;

        // Jumlah bata per m²
        return $areaBrickWithMortar > 0 ? (1 / $areaBrickWithMortar) : 0;
    }

    /**
     * Get active installation types
     */
    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    /**
     * Get default installation type
     */
    public static function getDefault()
    {
        return self::where('code', 'half')->first();
    }
}
