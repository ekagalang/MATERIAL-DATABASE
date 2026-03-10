<?php

namespace App\Support\Material;

use App\Helpers\NumberHelper;
use Illuminate\Support\Collection;

class MaterialAutocompleteValueFormatter
{
    public static function formatValues(string $materialType, string $field, Collection|array $values): Collection
    {
        $collection = $values instanceof Collection ? $values : collect($values);
        $decimals = MaterialLookupSpec::autocompleteDecimals($materialType, $field);

        if ($decimals === null) {
            return $collection
                ->map(fn($value) => is_string($value) ? trim($value) : $value)
                ->values();
        }

        return $collection
            ->map(fn($value) => self::normalizeNumericValue($value, $decimals))
            ->filter(fn($value) => $value !== null && $value !== '')
            ->uniqueStrict()
            ->values();
    }

    private static function normalizeNumericValue(mixed $value, int $decimals): int|float|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = NumberHelper::parseNullable($value);
        if ($parsed === null || !is_finite($parsed)) {
            return is_string($value) ? trim($value) : $value;
        }

        if ($decimals === 0) {
            return (int) round($parsed);
        }

        return (float) NumberHelper::formatPlain($parsed, $decimals, '.', '');
    }
}
