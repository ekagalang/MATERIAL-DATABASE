<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\MaterialTypeDetector;
use App\Helpers\NumberHelper;

class Sand extends Model
{
    use HasFactory;

    protected $table = 'sands';

    protected $fillable = [
        'sand_name',
        'type',
        'photo',
        'brand',
        'package_unit',
        'package_weight_gross',
        'package_weight_net',
        'dimension_length',
        'dimension_width',
        'dimension_height',
        'package_volume',
        'store',
        'address',
        'store_location_id',
        'package_price',
        'comparison_price_per_m3',
    ];

    protected $casts = [
        'package_weight_gross' => 'float',
        'package_weight_net' => 'float',
        'dimension_length' => 'float',
        'dimension_width' => 'float',
        'dimension_height' => 'float',
        'package_volume' => 'float',
        'package_price' => 'float',
        'comparison_price_per_m3' => 'float',
    ];

    protected $appends = ['photo_url'];

    /**
     * Get material type untuk model ini
     */
    public static function getMaterialType(): string
    {
        return 'sand';
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
     * Kalkulasi volume dari dimensi (p x l x t) dalam M3
     * Menggunakan NumberHelper::normalize() untuk konsistensi dengan tampilan
     */
    public function calculateVolume(): float
    {
        if ($this->dimension_length && $this->dimension_width && $this->dimension_height) {
            // Normalize dimensi terlebih dahulu
            $length = NumberHelper::normalize($this->dimension_length);
            $width = NumberHelper::normalize($this->dimension_width);
            $height = NumberHelper::normalize($this->dimension_height);

            // Langsung dalam M3
            $volumeM3 = $length * $width * $height;

            // Normalize hasil volume
            $this->package_volume = NumberHelper::normalize($volumeM3);
            return $this->package_volume;
        }

        return 0;
    }

    /**
     * Kalkulasi harga komparasi per M3
     * Menggunakan NumberHelper::normalize() untuk konsistensi dengan tampilan
     */
    public function calculateComparisonPrice(): float
    {
        if ($this->package_price && $this->package_volume && $this->package_volume > 0) {
            // Normalize volume sebelum perhitungan
            $normalizedVolume = NumberHelper::normalize($this->package_volume);

            if ($normalizedVolume > 0) {
                $comparisonPrice = $this->package_price / $normalizedVolume;
                // Normalize hasil (0 decimal untuk harga)
                $this->comparison_price_per_m3 = NumberHelper::normalize($comparisonPrice, 0);
                return $this->comparison_price_per_m3;
            }
        }

        return 0;
    }

    /**
     * Accessor untuk URL foto
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
     * Relationship: Sand bisa dipakai di banyak calculations
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(BrickCalculation::class);
    }

    /**
     * Relationship: Sand belongs to one store location (direct)
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
