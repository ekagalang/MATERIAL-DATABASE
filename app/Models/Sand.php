<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Sand extends Model
{
    use HasFactory;

    protected $fillable = [
        'sand_name',
        'type',
        'photo',
        'brand',
        'dimension_length',
        'dimension_width',
        'dimension_height',
        'package_volume',
        'store',
        'address',
        'short_address',
        'package_price',
        'comparison_price_per_m3'
    ];

    protected $casts = [
        'dimension_length' => 'decimal:2',
        'dimension_width' => 'decimal:2',
        'dimension_height' => 'decimal:2',
        'package_volume' => 'decimal:6',
        'package_price' => 'decimal:2',
        'comparison_price_per_m3' => 'decimal:2'
    ];

    /**
     * Kalkulasi volume dari dimensi (p x l x t) dalam m³
     */
    public function calculateVolume(): float
    {
        if ($this->dimension_length && $this->dimension_width && $this->dimension_height) {
            // Langsung dalam m³
            $volumeM3 = $this->dimension_length * $this->dimension_width * $this->dimension_height;
            
            $this->package_volume = $volumeM3;
            return $volumeM3;
        }
        
        return 0;
    }

    /**
     * Kalkulasi harga komparasi per m³
     * Harga per kemasan / volume kemasan
     */
    public function calculateComparisonPrice(): float
    {
        if ($this->package_price && $this->package_volume && $this->package_volume > 0) {
            $this->comparison_price_per_m3 = $this->package_price / $this->package_volume;
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
}