<?php
// app/Models/StoreLocation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StoreLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'address',
        'district',
        'city',
        'province',
        'latitude',
        'longitude',
        'place_id',
        'formatted_address',
        'service_radius_km',
        'contact_name',
        'contact_phone',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'service_radius_km' => 'float',
    ];

    /**
     * Relationship: Lokasi milik satu toko
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relationship: Lokasi memiliki banyak material availability
     */
    public function materialAvailabilities(): HasMany
    {
        return $this->hasMany(StoreMaterialAvailability::class);
    }

    /**
     * Accessor: Cek apakah data lengkap
     * Return true jika ada field penting yang kosong
     */
    protected function isIncomplete(): Attribute
    {
        return Attribute::make(
            get: fn() => empty($this->city) || empty($this->province) || empty($this->contact_phone),
        );
    }

    /**
     * Accessor: Full address dalam 1 string
     */
    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([$this->address, $this->district, $this->city, $this->province]);

                return implode(', ', $parts);
            },
        );
    }
}
