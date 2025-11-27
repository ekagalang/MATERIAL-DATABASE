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
     * Sesuai dengan rumus di Excel sheet "Adukan Semen"
     * 
     * @param float $mortarVolume Volume adukan dalam m³
     * @param float|null $cementRatio Rasio semen (opsional, untuk custom ratio)
     * @param float|null $sandRatio Rasio pasir (opsional, untuk custom ratio)
     * @return array
     */
    public function calculateMaterials(
        float $mortarVolume, 
        ?float $cementRatio = null, 
        ?float $sandRatio = null
    ): array {
        // Gunakan custom ratio jika disediakan, kalau tidak pakai dari formula
        $cementRatio = $cementRatio ?? $this->cement_ratio;
        $sandRatio = $sandRatio ?? $this->sand_ratio;
        
        // Total ratio
        $totalRatio = $cementRatio + $sandRatio;
        
        // === PERHITUNGAN SEMEN ===
        // Volume semen dalam adukan (m³)
        // Berdasarkan perbandingan ratio
        $cementVolumeRatio = $cementRatio / $totalRatio;
        
        // Berat jenis semen Portland = 1440 kg/m³
        // Tapi karena ada void space dan compaction, kita gunakan faktor koreksi
        $cementDensity = 1440; // kg/m³
        
        // Volume semen murni (sebelum compaction)
        // Menggunakan formula: V_cement = (ratio_cement / total_ratio) * V_total * correction_factor
        $correctionFactor = 1.25; // Faktor koreksi untuk void space
        $cementVolumeM3 = ($cementVolumeRatio * $mortarVolume * $correctionFactor);
        
        // Kebutuhan semen dalam kg
        $cementKg = $cementVolumeM3 * $cementDensity;
        
        // Konversi ke sak
        $cementSak40kg = $cementKg / 40;
        $cementSak50kg = $cementKg / 50;
        
        // === PERHITUNGAN PASIR ===
        // Volume pasir dalam adukan (m³)
        $sandVolumeRatio = $sandRatio / $totalRatio;
        
        // Volume pasir murni
        $sandVolumeM3 = ($sandVolumeRatio * $mortarVolume * $correctionFactor);
        
        // Berat jenis pasir kering = 1600 kg/m³
        $sandDensity = 1600; // kg/m³
        
        // Kebutuhan pasir dalam kg
        $sandKg = $sandVolumeM3 * $sandDensity;
        
        // Konversi pasir ke karung
        // Asumsi: 1 karung pasir = 40 kg (standar Indonesia)
        $sandSak = $sandKg / 40;
        
        // === PERHITUNGAN AIR ===
        // Water-Cement Ratio (W/C) standar
        // Untuk adukan pasangan bata biasanya 0.4 - 0.6
        $waterCementRatio = $this->water_ratio ?? 0.5;
        
        // Kebutuhan air dalam liter (1 kg air = 1 liter)
        $waterLiters = $cementKg * $waterCementRatio;
        
        return [
            'cement_kg' => round($cementKg, 2),
            'cement_sak_40kg' => round($cementSak40kg, 4),
            'cement_sak_50kg' => round($cementSak50kg, 4),
            'sand_m3' => round($sandVolumeM3, 6),
            'sand_kg' => round($sandKg, 2),
            'sand_sak' => round($sandSak, 4),
            'water_liters' => round($waterLiters, 2),
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
        return self::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get default formula
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first() 
            ?? self::where('is_active', true)->first();
    }
}