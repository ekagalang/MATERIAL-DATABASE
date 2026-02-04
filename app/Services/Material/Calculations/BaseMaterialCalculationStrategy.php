<?php

namespace App\Services\Material\Calculations;

use Illuminate\Database\Eloquent\Model;

class BaseMaterialCalculationStrategy implements MaterialCalculationStrategyInterface
{
    public function apply(array $data, ?Model $existing = null): array
    {
        return $data;
    }
}

