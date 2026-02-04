<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialSetting extends Model
{
    protected $fillable = ['material_type', 'is_visible', 'display_order'];

    protected $casts = [
        'is_visible' => 'boolean',
        'display_order' => 'integer',
    ];

    public static function getVisibleMaterials()
    {
        return self::where('is_visible', true)->orderBy('display_order')->get();
    }

    public static function getMaterialLabel($type)
    {
        $labels = [
            'brick' => 'Bata',
            'cat' => 'Cat',
            'cement' => 'Semen',
            'nat' => 'Nat',
            'sand' => 'Pasir',
            'ceramic' => 'Keramik',
        ];

        return $labels[$type] ?? ucfirst($type);
    }
}
