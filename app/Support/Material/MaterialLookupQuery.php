<?php

namespace App\Support\Material;

use Illuminate\Http\Request;

class MaterialLookupQuery
{
    public static function rawSearch(Request $request): mixed
    {
        return $request->get('search');
    }

    public static function stringSearch(Request $request, string $default = ''): string
    {
        $value = $request->query('search', $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function rawLimit(Request $request, int $default = 20): mixed
    {
        return $request->get('limit', $default);
    }

    public static function intLimit(Request $request, int $default = 20): int
    {
        return (int) $request->get('limit', $default);
    }

    public static function normalizedLimit(Request $request, int $default = 20): int
    {
        $limit = self::intLimit($request, $default);

        return $limit > 0 && $limit <= 100 ? $limit : $default;
    }

    public static function rawStore(Request $request): mixed
    {
        return $request->get('store');
    }

    public static function stringStore(Request $request, string $default = ''): string
    {
        $value = $request->query('store', $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function rawMaterialType(Request $request, string $default): mixed
    {
        return $request->get('material_type', $default);
    }

    public static function queryMaterialType(Request $request, string $default): mixed
    {
        return $request->query('material_type', $default);
    }

    public static function stringMaterialType(Request $request, string $default): string
    {
        $value = $request->query('material_type', $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function onlyFilters(Request $request, array $keys): array
    {
        return $request->only($keys);
    }
}
