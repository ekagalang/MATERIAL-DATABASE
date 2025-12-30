<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MortarFormula extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cement_ratio',
        'sand_ratio',
        'water_ratio',
        'cement_kg_per_m3',
        'sand_m3_per_m3',
        'water_liter_per_m3',
        'expansion_factor',
        'cement_bag_type',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'cement_ratio' => 'decimal:4',
        'sand_ratio' => 'decimal:4',
        'water_ratio' => 'decimal:4',
        'cement_kg_per_m3' => 'decimal:2',
        'sand_m3_per_m3' => 'decimal:4',
        'water_liter_per_m3' => 'decimal:2',
        'expansion_factor' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Formula punya banyak calculations
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(BrickCalculation::class, 'mortar_formula_id');
    }

    /**
     * Hitung kebutuhan material berdasarkan volume adukan
     * MENGGUNAKAN FIXED VALUES dari Excel (cement_kg_per_m3, sand_m3_per_m3, water_liter_per_m3)
     *
     * @param  float  $mortarVolume  Volume adukan dalam M3
     * @param  float|null  $cementRatio  Rasio semen (opsional, untuk custom ratio - TIDAK DIGUNAKAN di metode ini)
     * @param  float|null  $sandRatio  Rasio pasir (opsional, untuk custom ratio - TIDAK DIGUNAKAN di metode ini)
     * @param  Cement|null  $cement  Objek cement (tidak digunakan di metode ini)
     * @param  Sand|null  $sand  Objek sand (tidak digunakan di metode ini)
     */
    public function calculateMaterials(
        float $mortarVolume,
        ?float $cementRatio = null,
        ?float $sandRatio = null,
        $cement = null,
        $sand = null,
    ): array {
        // === GUNAKAN FIXED VALUES DARI EXCEL ===
        // Formula Excel sudah menghitung berapa kg semen per M3, M3 pasir per M3, dan liter air per M3
        // Kita tinggal kalikan dengan volume adukan yang dibutuhkan

        $cementKgPerM3 = $this->cement_kg_per_m3 ?? 325; // default 325 kg/M3
        $sandM3PerM3 = $this->sand_m3_per_m3 ?? 0.87; // default 0.87 M3/M3
        $waterLiterPerM3 = $this->water_liter_per_m3 ?? 400; // default 400 liter/M3

        // Hitung total kebutuhan material
        $cementKg = $cementKgPerM3 * $mortarVolume;
        $sandM3 = $sandM3PerM3 * $mortarVolume;
        $waterLiters = $waterLiterPerM3 * $mortarVolume;

        // Konversi semen ke berbagai ukuran sak
        $cementSak40kg = $cementKg / 40;
        $cementSak50kg = $cementKg / 50;

        // Hitung volume semen (untuk referensi, jika diperlukan)
        $cementDensity = 1440; // kg/M3 untuk semen Portland
        $cementVolumeM3 = $cementKg / $cementDensity;

        // Berat pasir
        $sandDensity = 1600; // kg/M3
        $sandKg = $sandM3 * $sandDensity;

        // Sand sak (anggap 1 sak = 1.5 M3 seperti di example.txt)
        $sandSak = $sandM3 / 1.5;

        // Water m3
        $waterM3 = $waterLiters / 1000;

        return [
            'cement_kg' => round($cementKg, 2),
            'cement_sak_40kg' => round($cementSak40kg, 4),
            'cement_sak_50kg' => round($cementSak50kg, 4),
            'cement_sak' => round($cementSak50kg, 4), // default ke 50kg
            'cement_volume_m3' => round($cementVolumeM3, 6),
            'cement_volume_per_bag' => round($cementVolumeM3 / $cementSak50kg, 6),
            'sand_m3' => round($sandM3, 6),
            'sand_kg' => round($sandKg, 2),
            'sand_sak' => round($sandSak, 4),
            'sand_volume_per_bag' => 1.5, // Fixed 1.5 M3 per sak
            'water_liters' => round($waterLiters, 2),
            'water_m3' => round($waterM3, 6),
            'mortar_volume_per_set' => 0, // tidak digunakan di metode ini
        ];
    }

    /**
     * Get ratio display string
     */
    public function getRatioDisplayAttribute(): string
    {
        return "1:{$this->sand_ratio}";
    }

    /**
     * Get active formulas
     */
    public static function getActive()
    {
        return self::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Get default formula
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first() ?? self::where('is_active', true)->first();
    }
}
