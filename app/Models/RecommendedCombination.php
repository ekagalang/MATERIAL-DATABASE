<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendedCombination extends Model
{
    use HasFactory;

    protected $fillable = [
        'brick_id',
        'cement_id',
        'sand_id',
        'cat_id',
        'ceramic_id',
        'nat_id',
        'type',
        'work_type',
        'is_active',
        'sort_order',
    ];

    public function brick(): BelongsTo
    {
        return $this->belongsTo(Brick::class);
    }

    public function cement(): BelongsTo
    {
        return $this->belongsTo(Cement::class);
    }

    public function sand(): BelongsTo
    {
        return $this->belongsTo(Sand::class);
    }

    public function cat(): BelongsTo
    {
        return $this->belongsTo(Cat::class);
    }

    public function ceramic(): BelongsTo
    {
        return $this->belongsTo(Ceramic::class);
    }

    public function nat(): BelongsTo
    {
        return $this->belongsTo(Cement::class, 'nat_id');
    }
}
