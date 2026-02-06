<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Ceramic extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_name',
        'type',
        'brand',
        'sub_brand',
        'code',
        'color',
        'form',
        'surface',
        'dimension_length',
        'dimension_width',
        'dimension_thickness',
        'packaging',
        'pieces_per_package',
        'coverage_per_package',
        'store',
        'address',
        'store_location_id',
        'price_per_package',
        'comparison_price_per_m2',
        'photo',
    ];

    protected $appends = ['photo_url', 'area_per_piece'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimension_length' => 'decimal:2',
            'dimension_width' => 'decimal:2',
            'dimension_thickness' => 'decimal:2',
            'pieces_per_package' => 'integer',
            'coverage_per_package' => 'decimal:4',
            'price_per_package' => 'decimal:2',
            'comparison_price_per_m2' => 'decimal:2',
        ];
    }

    /**
     * Get material type untuk model ini
     */
    public static function getMaterialType(): string
    {
        return 'ceramic';
    }

    /**
     * Accessor: Nama lengkap produk untuk display
     * Contoh: "Roman (Granit) - Putih Polos 40x40"
     */
    public function getFullNameAttribute(): string
    {
        $dim = "{$this->dimension_length}x{$this->dimension_width}";
        $sub = $this->sub_brand ? "({$this->sub_brand})" : '';

        return "{$this->brand} {$sub} - {$this->color} {$dim}";
    }

    /**
     * Accessor: URL untuk foto
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        $path = $this->photo;

        // Jika sudah URL lengkap atau absolute path
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        // Cek di storage/public
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // Cek di public folder
        $publicPath = public_path($path);
        if (file_exists($publicPath)) {
            return asset($path);
        }

        // Default: assume it's in storage
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Accessor: Luas per piece (M² / Lbr)
     * Dimensi dalam CM, hasil dalam M²
     */
    public function getAreaPerPieceAttribute(): ?float
    {
        if ($this->dimension_length && $this->dimension_width) {
            // Konversi dimensi dari CM ke M
            $lengthM = $this->dimension_length / 100;
            $widthM = $this->dimension_width / 100;

            // Luas satu piece dalam M²
            return $lengthM * $widthM;
        }

        return null;
    }

    /**
     * Kalkulasi coverage per package dari dimensi dan jumlah pieces
     * Dimensi dalam CM, hasil dalam M²
     * Menggunakan nilai float murni untuk perhitungan
     *
     * @return float
     */
    public function calculateCoverage(): float
    {
        if ($this->dimension_length && $this->dimension_width && $this->pieces_per_package) {
            // Ambil dimensi sebagai float
            $length = (float) $this->dimension_length;
            $width = (float) $this->dimension_width;

            // Konversi dimensi dari CM ke M
            $lengthM = $length / 100;
            $widthM = $width / 100;

            // Luas satu piece dalam M²
            $areaPerPiece = $lengthM * $widthM;

            // Total coverage = luas per piece × jumlah pieces
            $coverage = $areaPerPiece * $this->pieces_per_package;

            // Simpan hasil coverage
            $this->coverage_per_package = (float) $coverage;
            return $this->coverage_per_package;
        }

        return 0;
    }

    /**
     * Kalkulasi harga komparasi per M²
     * Menggunakan nilai float murni untuk perhitungan
     *
     * @return float
     */
    public function calculateComparisonPrice(): float
    {
        if ($this->price_per_package && $this->coverage_per_package && $this->coverage_per_package > 0) {
            // Gunakan coverage sebagai float sebelum perhitungan
            $coverage = (float) $this->coverage_per_package;

            if ($coverage > 0) {
                $comparisonPrice = (float) $this->price_per_package / $coverage;
                $this->comparison_price_per_m2 = (float) $comparisonPrice;
                return $this->comparison_price_per_m2;
            }
        }

        return 0;
    }

    /**
     * Relationship: Ceramic belongs to one store location (direct)
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
