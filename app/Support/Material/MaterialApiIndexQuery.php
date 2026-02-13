<?php

namespace App\Support\Material;

use Illuminate\Http\Request;

class MaterialApiIndexQuery
{
    /**
     * @return array{0: mixed, 1: mixed, 2: mixed, 3: mixed}
     */
    public static function resolve(
        Request $request,
        int $defaultPerPage = 15,
        string $defaultSortBy = 'created_at',
        string $defaultSortDirection = 'desc',
    ): array {
        return [
            $request->get('search'),
            $request->get('per_page', $defaultPerPage),
            $request->get('sort_by', $defaultSortBy),
            $request->get('sort_direction', $defaultSortDirection),
        ];
    }

    /**
     * Execute index retrieval using the same API query contract.
     *
     * @param  callable(string, mixed, mixed, mixed): mixed  $searchCallback
     * @param  callable(mixed, mixed, mixed): mixed  $paginateCallback
     */
    public static function execute(
        Request $request,
        callable $searchCallback,
        callable $paginateCallback,
        int $defaultPerPage = 15,
        string $defaultSortBy = 'created_at',
        string $defaultSortDirection = 'desc',
    ): mixed {
        [$search, $perPage, $sortBy, $sortDirection] = self::resolve(
            $request,
            $defaultPerPage,
            $defaultSortBy,
            $defaultSortDirection,
        );

        if ($search) {
            return $searchCallback($search, $perPage, $sortBy, $sortDirection);
        }

        return $paginateCallback($perPage, $sortBy, $sortDirection);
    }
}
