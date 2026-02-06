<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Nat extends Model
{
    use HasFactory;

    protected $table = 'nats';

    protected $fillable = [
        'nat_name',
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

    public static function getMaterialType(): string
    {
        return 'nat';
    }

    public static function getAvailableUnits()
    {
        return Unit::forMaterial(self::getMaterialType())->orderBy('code')->get();
    }

    public function packageUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'package_unit', 'code');
    }

    public function storeLocation(): BelongsTo
    {
        return $this->belongsTo(StoreLocation::class);
    }

    public function storeLocations(): MorphToMany
    {
        return $this->morphToMany(StoreLocation::class, 'materialable', 'store_material_availabilities');
    }

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

    public function calculateNetWeight(): float
    {
        if ($this->package_weight_gross && $this->package_unit) {
            $unit = Unit::where('code', $this->package_unit)
                ->whereHas('materialTypes', function ($q) {
                    $q->where('material_type', self::getMaterialType());
                })
                ->first();

            if (!$unit) {
                $unit = Unit::where('code', $this->package_unit)->first();
            }

            if ($unit) {
                $netWeight = (float) $this->package_weight_gross - (float) ($unit->package_weight ?? 0);
                $this->package_weight_net = max($netWeight, 0.0);

                return (float) $this->package_weight_net;
            }
        }

        return (float) ($this->package_weight_gross ?? 0);
    }

    public function calculateComparisonPrice(): float
    {
        if ($this->package_weight_net && $this->package_weight_net > 0 && $this->package_price) {
            $weight = (float) $this->package_weight_net;

            if ($weight > 0) {
                $comparisonPrice = (float) $this->package_price / $weight;
                $this->comparison_price_per_kg = (float) $comparisonPrice;

                return (float) $this->comparison_price_per_kg;
            }
        }

        return 0;
    }
}
