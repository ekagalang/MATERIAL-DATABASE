<?php

namespace App\Support\Material;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MaterialIndexQuery
{
    public static function searchValue(Request $request): string
    {
        $value = $request->input('search', '');

        return is_scalar($value) ? (string) $value : '';
    }

    public static function applySearch(Builder $query, string $search, array $columns): void
    {
        if ($search === '' || empty($columns)) {
            return;
        }

        $query->where(function ($builder) use ($search, $columns) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $builder->where($column, 'like', "%{$search}%");
                    continue;
                }

                $builder->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function resolveSort(Request $request, string $materialType): array
    {
        $sortBy = (string) $request->get('sort_by', '');
        $sortDirection = (string) $request->get('sort_direction', '');

        $defaultSortBy = MaterialIndexSpec::defaultSortBy($materialType);
        $defaultSortDirection = MaterialIndexSpec::defaultSortDirection($materialType);
        $invalidSortDirection = MaterialIndexSpec::invalidSortDirection($materialType);
        $sortMap = MaterialIndexSpec::sortMap($materialType);

        if (!empty($sortMap)) {
            if (!isset($sortMap[$sortBy])) {
                return [$defaultSortBy, $defaultSortDirection];
            }

            if (!in_array($sortDirection, ['asc', 'desc'], true)) {
                $sortDirection = $invalidSortDirection;
            }

            return [$sortMap[$sortBy], $sortDirection];
        }

        $allowedSorts = MaterialIndexSpec::allowedSorts($materialType);
        if ($sortBy === '' || !in_array($sortBy, $allowedSorts, true)) {
            return [$defaultSortBy, $defaultSortDirection];
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = $invalidSortDirection;
        }

        return [$sortBy, $sortDirection];
    }
}
