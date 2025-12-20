<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\MaterialTypeDetector;

class Brick extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_name',
        'type',
        'photo',
        'brand',
        'form',
        'dimension_length',
        'dimension_width',
        'dimension_height',
        'package_volume',
        'store',
        'address',
        'short_address',
        'price_per_piece',
        'comparison_price_per_m3',
    ];

    protected function casts(): array
    {
        return [
            'dimension_length' => 'float',
            'dimension_width' => 'float',
            'dimension_height' => 'float',
            'package_volume' => 'float',
            'price_per_piece' => 'float',
            'comparison_price_per_m3' => 'float',
        ];
    }

    protected $appends = ['photo_url'];

    /**
     * Get material type untuk model ini
     */
    public static function getMaterialType(): string
    {
        return 'brick';
    }

    /**
     * Get available units untuk material ini
     */
    public static function getAvailableUnits()
    {
        return Unit::forMaterial(self::getMaterialType())->orderBy('code')->get();
    }

    /**
     * Kalkulasi volume dari dimensi (p x l x t)
     * Konversi dari cm³ ke m³
     */
    public function calculateVolume(): float
    {
        if ($this->dimension_length && $this->dimension_width && $this->dimension_height) {
            // Volume dalam cm³
            $volumeCm3 = $this->dimension_length * $this->dimension_width * $this->dimension_height;

            // Konversi ke m³ (1 m³ = 1,000,000 cm³)
            $volumeM3 = $volumeCm3 / 1000000;

            $this->package_volume = $volumeM3;
            return $volumeM3;
        }

        return 0;
    }

    /**
     * Kalkulasi harga komparasi per m³
     */
    public function calculateComparisonPrice(): float
    {
        if ($this->price_per_piece && $this->package_volume && $this->package_volume > 0) {
            $this->comparison_price_per_m3 = $this->price_per_piece / $this->package_volume;
            return $this->comparison_price_per_m3;
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
     * Relationship: Brick bisa dipakai di banyak calculations
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(BrickCalculation::class);
    }

    /**
     * Get default brick dimensions (untuk kalkulator)
     */
    public static function getDefaultDimensions(): array
    {
        $brick = self::first();

        return [
            'length' => $brick->dimension_length ?? 20,
            'width' => $brick->dimension_width ?? 10,
            'height' => $brick->dimension_height ?? 5,
        ];
    }
}
