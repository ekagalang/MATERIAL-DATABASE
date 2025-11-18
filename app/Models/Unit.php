<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\MaterialTypeDetector;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'material_type',
        'name',
        'package_weight',
        'description'
    ];

    protected $casts = [
        'package_weight' => 'float'
    ];

    /**
     * Scope untuk filter units berdasarkan material type
     * 
     * Usage: Unit::forMaterial('cat')->get()
     */
    public function scopeForMaterial($query, string $materialType)
    {
        return $query->where('material_type', $materialType);
    }

    /**
     * Get all available material types
     */
    public static function getMaterialTypes(): array
    {
        return MaterialTypeDetector::getAvailableTypes();
    }

    /**
     * Get material types with labels
     */
    public static function getMaterialTypesWithLabels(): array
    {
        return MaterialTypeDetector::getTypesWithLabels();
    }

    /**
     * Get units grouped by material type
     */
    public static function getGroupedByMaterialType(): array
    {
        $units = self::orderBy('material_type')->orderBy('code')->get();
        return $units->groupBy('material_type')->toArray();
    }
}