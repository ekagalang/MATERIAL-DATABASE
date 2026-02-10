<?php

namespace App\Services\Material\Calculations;

use Illuminate\Database\Eloquent\Model;

class MaterialCalculationStrategyRegistry
{
    public static function resolve(string $materialType): MaterialCalculationStrategyInterface
    {
        $map = config('material_calculation_strategies', []);
        $className = is_array($map) ? $map[$materialType] ?? null : null;

        if (is_string($className) && class_exists($className)) {
            $strategy = app($className);
            if ($strategy instanceof MaterialCalculationStrategyInterface) {
                return $strategy;
            }
        }

        return app(BaseMaterialCalculationStrategy::class);
    }

    public static function applyFor(string $materialType, array $data, ?Model $existing = null): array
    {
        return self::resolve($materialType)->apply($data, $existing);
    }
}
