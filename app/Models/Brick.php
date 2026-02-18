<?php

namespace App\Models;

use App\Models\Concerns\SyncsStoreLocationSnapshot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Helpers\MaterialTypeDetector;

class Brick extends Model
{
    use HasFactory;
    use SyncsStoreLocationSnapshot;

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
        'package_type',
        'store',
        'address',
        'store_location_id',
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
     * Konversi dari cM3 ke M3
     * Menggunakan nilai float murni untuk perhitungan
     */
    public function calculateVolume(): float
    {
        if ($this->dimension_length && $this->dimension_width && $this->dimension_height) {
            // Ambil dimensi sebagai float
            $length = (float) $this->dimension_length;
            $width = (float) $this->dimension_width;
            $height = (float) $this->dimension_height;

            // Volume dalam cM3
            $volumeCm3 = $length * $width * $height;

            // Konversi ke M3 (1 M3 = 1,000,000 cM3)
            $volumeM3 = $volumeCm3 / 1000000;

            // Simpan hasil volume
            $this->package_volume = (float) $volumeM3;
            return $this->package_volume;
        }

        return 0;
    }

    /**
     * Kalkulasi harga komparasi per M3
     * Menggunakan nilai float murni untuk perhitungan
     */
    public function calculateComparisonPrice(): float
    {
        if ($this->price_per_piece && $this->package_volume && $this->package_volume > 0) {
            $volume = (float) $this->package_volume;

            if ($volume > 0) {
                $comparisonPrice = (float) $this->price_per_piece / $volume;
                $this->comparison_price_per_m3 = (float) $comparisonPrice;
                return $this->comparison_price_per_m3;
            }
        }

        return 0;
    }

    /**
     * Sinkronkan harga berdasarkan tipe kemasan.
     * - eceran: harga beli utama = price_per_piece, comparison dihitung dari volume.
     * - kubik: harga beli utama = comparison_price_per_m3, price_per_piece dihitung dari volume.
     */
    public function syncPricingByPackageType(): void
    {
        $packageType = strtolower(trim((string) ($this->package_type ?? '')));
        $isKubik = $packageType === 'kubik';
        $volume = (float) ($this->package_volume ?? 0);
        $piecePrice = is_numeric($this->price_per_piece) ? (float) $this->price_per_piece : 0.0;
        $comparisonPrice = is_numeric($this->comparison_price_per_m3) ? (float) $this->comparison_price_per_m3 : 0.0;

        if ($isKubik) {
            if ($comparisonPrice > 0) {
                $this->price_per_piece = $volume > 0 ? (float) ($comparisonPrice * $volume) : null;
                return;
            }

            if ($piecePrice > 0 && $volume > 0) {
                $this->comparison_price_per_m3 = (float) ($piecePrice / $volume);
                return;
            }

            $this->price_per_piece = null;
            $this->comparison_price_per_m3 = null;
            return;
        }

        if ($piecePrice > 0) {
            $this->comparison_price_per_m3 = $volume > 0 ? (float) ($piecePrice / $volume) : null;
            return;
        }

        if ($comparisonPrice > 0 && $volume > 0) {
            $this->price_per_piece = (float) ($comparisonPrice * $volume);
            return;
        }

        $this->comparison_price_per_m3 = null;
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
     * Relationship: Recommended combinations for this brick
     */
    public function recommendedCombinations(): HasMany
    {
        return $this->hasMany(RecommendedCombination::class);
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

    /**
     * Relationship: Brick belongs to one store location (direct)
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
