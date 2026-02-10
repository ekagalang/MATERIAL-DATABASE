<?php

namespace App\Services\Material\Calculations;

use Illuminate\Database\Eloquent\Model;

interface MaterialCalculationStrategyInterface
{
    /**
     * Apply derived-field calculation before save/update.
     */
    public function apply(array $data, ?Model $existing = null): array;
}
