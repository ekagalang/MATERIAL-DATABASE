<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Cement extends Model
{
    use HasFactory;

    protected $table = 'cements';

    protected $fillable = [
        'cement_name',
        'type',
        'photo',
        'brand',
        'sub_brand',
        'code',
        'color',
        'package_unit',
        'package_weight_gross',
        'package_weight_net',
        'package_volume',
        'store',
        'address',
        'store_location_id',
        'package_price',
        'price_unit',
        'comparison_price_per_kg',
    ];

    protected $casts = [
        'package_weight_gross' => 'float',
        'package_weight_net' => 'float',
        'package_volume' => 'float',
        'package_price' => 'float',
        'comparison_price_per_kg' => 'float',
    ];

    protected $appends = ['photo_url'];

    /**
     * Get material type untuk model ini
     */
    public static function getMaterialType(): string
    {
        return 'cement';
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
     * Menggunakan nilai float murni untuk perhitungan
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
                $netWeight = (float) $this->package_weight_gross - (float) $unit->package_weight;
                $this->package_weight_net = (float) $netWeight;

                return $this->package_weight_net;
            }
        }

        return $this->package_weight_gross;
    }

    /**
     * Method untuk kalkulasi harga komparasi per kg
     * Menggunakan nilai float murni untuk perhitungan
     */
    public function calculateComparisonPrice()
    {
        if ($this->package_weight_net && $this->package_weight_net > 0 && $this->package_price) {
            $weight = (float) $this->package_weight_net;

            if ($weight > 0) {
                $comparisonPrice = (float) $this->package_price / $weight;
                $this->comparison_price_per_kg = (float) $comparisonPrice;
                return $this->comparison_price_per_kg;
            }
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

    /**
     * Relationship: Cement bisa dipakai di banyak calculations
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(BrickCalculation::class);
    }

    /**
     * Relationship: Cement belongs to one store location (direct)
     */
    public function storeLocation(): BelongsTo
    {
        return $this->belongsTo(StoreLocation::class);
    }

    /**
     * Relationship: Brick tersedia di banyak store locations (Polymorphic Many-to-Many)
     */
    public function storeLocations(): MorphToMany
    {
        return $this->morphToMany(StoreLocation::class, 'materialable', 'store_material_availabilities');
    }
}
