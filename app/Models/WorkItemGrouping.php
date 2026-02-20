<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkItemGrouping extends Model
{
    use HasFactory;

    protected $fillable = ['formula_code', 'work_area_id', 'work_field_id'];

    public function workArea(): BelongsTo
    {
        return $this->belongsTo(WorkArea::class);
    }

    public function workField(): BelongsTo
    {
        return $this->belongsTo(WorkField::class);
    }
}

