<?php

namespace App\Support\Material;

class MaterialIndexSpec
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const SPECS = [
        'brick' => [
            'search_columns' => ['type', 'brand', 'form', 'package_type', 'store', 'address'],
            'allowed_sorts' => [
                'material_name',
                'type',
                'brand',
                'form',
                'package_type',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'price_per_piece',
                'comparison_price_per_m3',
                'created_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'asc',
        ],
        'cement' => [
            'search_columns' => ['cement_name', 'type', 'brand', 'sub_brand', 'code', 'color', 'store', 'address'],
            'allowed_sorts' => [
                'cement_name',
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'package_unit',
                'package_weight_gross',
                'package_weight_net',
                'store',
                'address',
                'package_price',
                'comparison_price_per_kg',
                'created_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'asc',
        ],
        'sand' => [
            'search_columns' => ['sand_name', 'type', 'brand', 'store', 'address'],
            'allowed_sorts' => [
                'sand_name',
                'type',
                'brand',
                'package_unit',
                'package_weight_gross',
                'package_weight_net',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_volume',
                'store',
                'address',
                'package_price',
                'comparison_price_per_m3',
                'created_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'asc',
        ],
        'cat' => [
            'search_columns' => ['cat_name', 'type', 'brand', 'color_name', 'store', 'address'],
            'allowed_sorts' => [
                'cat_name',
                'type',
                'brand',
                'sub_brand',
                'color_name',
                'color_code',
                'form',
                'package_unit',
                'package_weight_gross',
                'package_weight_net',
                'volume',
                'volume_unit',
                'store',
                'address',
                'purchase_price',
                'comparison_price_per_kg',
                'created_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'asc',
        ],
        'nat' => [
            'search_columns' => ['type', 'nat_name', 'brand', 'sub_brand', 'code', 'color', 'store', 'address'],
            'sort_map' => [
                'type' => 'type',
                'nat_name' => 'nat_name',
                'brand' => 'brand',
                'sub_brand' => 'sub_brand',
                'code' => 'code',
                'color' => 'color',
                'package_weight' => 'package_weight_net',
                'package_weight_net' => 'package_weight_net',
                'store' => 'store',
                'address' => 'address',
                'price_per_bag' => 'package_price',
                'package_price' => 'package_price',
                'comparison_price_per_kg' => 'comparison_price_per_kg',
                'created_at' => 'created_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'asc',
        ],
        'ceramic' => [
            'search_columns' => [],
            'allowed_sorts' => [
                'material_name',
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'form',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'pieces_per_package',
                'coverage_per_package',
                'store',
                'address',
                'price_per_package',
                'comparison_price_per_m2',
                'created_at',
                'updated_at',
            ],
            'default_sort_by' => 'created_at',
            'default_sort_direction' => 'desc',
            'invalid_sort_direction' => 'desc',
        ],
    ];

    public static function searchColumns(string $materialType): array
    {
        return self::SPECS[$materialType]['search_columns'] ?? [];
    }

    public static function defaultSortBy(string $materialType): string
    {
        return self::SPECS[$materialType]['default_sort_by'] ?? 'created_at';
    }

    public static function defaultSortDirection(string $materialType): string
    {
        return self::SPECS[$materialType]['default_sort_direction'] ?? 'desc';
    }

    public static function invalidSortDirection(string $materialType): string
    {
        return self::SPECS[$materialType]['invalid_sort_direction'] ?? 'asc';
    }

    public static function allowedSorts(string $materialType): array
    {
        return self::SPECS[$materialType]['allowed_sorts'] ?? [];
    }

    public static function sortMap(string $materialType): array
    {
        return self::SPECS[$materialType]['sort_map'] ?? [];
    }
}
