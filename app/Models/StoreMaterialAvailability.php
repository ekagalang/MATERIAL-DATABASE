<?php
// app/Models/StoreMaterialAvailability.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoreMaterialAvailability extends Model
{
    use HasFactory;

    protected $fillable = ['store_location_id', 'materialable_id', 'materialable_type'];

    /**
     * Relationship: Polymorphic relation ke material (Brick, Cement, Sand, dll)
     */
    public function materialable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship: Availability milik store location
     */
    public function storeLocation(): BelongsTo
    {
        return $this->belongsTo(StoreLocation::class);
    }
}
