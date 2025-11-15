<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Cement extends Model
{
    use HasFactory;

    // Beberapa inflector menganggap "cement" tak berbilang; pastikan pakai tabel plural
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
        'store',
        'address',
        'short_address',
        'package_price',
        'price_unit',
        'comparison_price_per_kg',
    ];

    protected $casts = [
        'package_weight_gross' => 'decimal:2',
        'package_weight_net' => 'decimal:2',
        'package_price' => 'decimal:2',
        'comparison_price_per_kg' => 'decimal:2',
    ];

    // Relasi ke Unit untuk package_unit
    public function packageUnit()
    {
        return $this->belongsTo(Unit::class, 'package_unit', 'code');
    }

    // Method untuk kalkulasi berat bersih
    public function calculateNetWeight()
    {
        if ($this->package_weight_gross && $this->package_unit) {
            $unit = Unit::where('code', $this->package_unit)->first();
            if ($unit) {
                $this->package_weight_net = $this->package_weight_gross - $unit->package_weight;

                return $this->package_weight_net;
            }
        }

        return $this->package_weight_gross;
    }

    // Method untuk kalkulasi harga komparasi per kg
    public function calculateComparisonPrice()
    {
        if ($this->package_weight_net && $this->package_weight_net > 0 && $this->package_price) {
            $this->comparison_price_per_kg = $this->package_price / $this->package_weight_net;

            return $this->comparison_price_per_kg;
        }

        return 0;
    }

    // Accessor URL foto
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
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

        return asset('storage/'.ltrim($path, '/'));
    }
}
