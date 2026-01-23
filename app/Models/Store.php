<?php
// app/Models/Store.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'logo'];

    /**
     * Relationship: Store memiliki banyak lokasi
     */
    public function locations(): HasMany
    {
        return $this->hasMany(StoreLocation::class);
    }

    /**
     * Get primary/first location (untuk quick access)
     */
    public function primaryLocation()
    {
        return $this->locations()->first();
    }
}
