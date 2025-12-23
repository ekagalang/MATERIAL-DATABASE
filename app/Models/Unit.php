<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\MaterialTypeDetector;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'package_weight', 'description'];

    protected $casts = [
        'package_weight' => 'float',
    ];

    /**
     * Get the material types associated with the unit.
     */
    public function materialTypes()
    {
        // Karena tidak ada model MaterialType, kita relasikan ke tabel pivot saja
        // Tapi untuk mempermudah, kita bisa akses via DB di accessor
        // Atau buat relationship hasMany ke model UnitMaterialType (kalau kita buat modelnya)
        // Disini kita pakai query builder manual atau relationship standard jika ada model Pivot.
        // Mari kita buat simpel: ambil dari tabel pivot.
        return $this->hasMany(UnitMaterialType::class);
    }

    /**
     * Scope untuk filter units berdasarkan material type
     *
     * Usage: Unit::forMaterial('cat')->get()
     */
    public function scopeForMaterial($query, string $materialType)
    {
        return $query->whereHas('materialTypes', function ($q) use ($materialType) {
            $q->where('material_type', $materialType);
        });
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
        // Ambil semua tipe material yang tersedia
        $types = self::getMaterialTypes();
        $grouped = [];

        foreach ($types as $type) {
            // Untuk setiap tipe, ambil unit yang relevan
            // Kita gunakan array_values untuk reset keys
            $grouped[$type] = array_values(self::forMaterial($type)->orderBy('code')->get()->toArray());
        }

        return $grouped;
    }
}
