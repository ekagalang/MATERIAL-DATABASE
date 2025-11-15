<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'comparison_price_per_m3'
    ];

    protected function casts(): array
    {
        return [
            'dimension_length' => 'decimal:2',
            'dimension_width' => 'decimal:2',
            'dimension_height' => 'decimal:2',
            'package_volume' => 'decimal:6',
            'price_per_piece' => 'decimal:2',
            'comparison_price_per_m3' => 'decimal:2'
        ];
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
     * Harga per buah / volume per buah
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
}