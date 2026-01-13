<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\MaterialTypeDetector;

class Cat extends Model
{
    use HasFactory;

    protected $fillable = [
        'cat_name',
        'type',
        'photo',
        'brand',
        'sub_brand',
        'color_code',
        'color_name',
        'form',
        'package_unit',
        'package_weight_gross',
        'package_weight_net',
        'volume',
        'volume_unit',
        'store',
        'address',
        'purchase_price',
        'price_unit',
        'comparison_price_per_kg',
    ];

    protected $casts = [
        'package_weight_gross' => 'float',
        'package_weight_net' => 'float',
        'volume' => 'float',
        'purchase_price' => 'float',
        'comparison_price_per_kg' => 'float',
    ];

    protected $appends = ['photo_url'];

    /**
     * Get material type untuk model ini
     */
    public static function getMaterialType(): string
    {
        return 'cat';
    }

    /**
     * Get available units untuk material ini
     */
    public static function getAvailableUnits()
    {
        return Unit::forMaterial(self::getMaterialType())->orderBy('code')->get();
    }

    /**
     * Relasi ke Unit untuk package_unit
     */
    public function packageUnit()
    {
        return $this->belongsTo(Unit::class, 'package_unit', 'code')->whereHas('materialTypes', function ($q) {
            $q->where('material_type', self::getMaterialType());
        });
    }

    /**
     * Method untuk kalkulasi berat bersih
     */
    public function calculateNetWeight()
    {
        if ($this->package_weight_gross && $this->package_unit) {
            $unit = Unit::where('code', $this->package_unit)
                ->whereHas('materialTypes', function ($q) {
                    $q->where('material_type', self::getMaterialType());
                })
                ->first();

            if ($unit) {
                $this->package_weight_net = $this->package_weight_gross - $unit->package_weight;
                return $this->package_weight_net;
            }
        }

        return $this->package_weight_gross;
    }

    /**
     * Method untuk kalkulasi harga komparasi per kg
     */
    public function calculateComparisonPrice()
    {
        if ($this->package_weight_net && $this->package_weight_net > 0 && $this->purchase_price) {
            $this->comparison_price_per_kg = $this->purchase_price / $this->package_weight_net;
            return $this->comparison_price_per_kg;
        }

        return 0;
    }

    /**
     * Accessor URL foto
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        $path = $this->photo;

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $publicPath = public_path($path);
        if (file_exists($publicPath)) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
