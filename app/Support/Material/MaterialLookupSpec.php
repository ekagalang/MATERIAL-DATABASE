<?php

namespace App\Support\Material;

class MaterialLookupSpec
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_FIELDS = [
        'brick' => [
            'type',
            'brand',
            'form',
            'package_type',
            'store',
            'address',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'price_per_piece',
        ],
        'cement' => [
            'cement_name',
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'store',
            'address',
            'price_unit',
            'package_weight_gross',
            'package_price',
        ],
        'sand' => [
            'type',
            'brand',
            'store',
            'address',
            'package_weight_gross',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'package_price',
        ],
        'cat' => [
            'cat_name',
            'type',
            'brand',
            'sub_brand',
            'color_code',
            'color_name',
            'form',
            'volume',
            'volume_unit',
            'package_weight_gross',
            'package_weight_net',
            'package_unit',
            'store',
            'address',
            'price_unit',
            'purchase_price',
        ],
        'ceramic' => [
            'type',
            'brand',
            'sub_brand',
            'code',
            'color',
            'form',
            'surface',
            'packaging',
            'pieces_per_package',
            'dimension_length',
            'dimension_width',
            'dimension_thickness',
            'price_per_package',
            'comparison_price_per_m2',
            'store',
            'address',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const FIELD_MAPS = [
        'nat' => [
            'type' => 'type',
            'nat_name' => 'nat_name',
            'brand' => 'brand',
            'sub_brand' => 'sub_brand',
            'code' => 'code',
            'color' => 'color',
            'store' => 'store',
            'address' => 'address',
            'price_unit' => 'price_unit',
            'package_weight_gross' => 'package_weight_gross',
            'package_price' => 'package_price',
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const API_FILTER_KEYS = [
        'brick' => ['brand', 'store', 'package_type'],
        'cement' => ['brand', 'store', 'package_unit'],
        'sand' => ['brand', 'store'],
        'cat' => ['brand', 'store', 'package_unit'],
        'nat' => ['brand', 'store', 'package_unit'],
        'ceramic' => ['brand', 'store', 'type', 'packaging'],
    ];

    public static function allowedFields(string $materialType): array
    {
        return self::ALLOWED_FIELDS[$materialType] ?? [];
    }

    public static function fieldMap(string $materialType): array
    {
        return self::FIELD_MAPS[$materialType] ?? [];
    }

    public static function apiFilterKeys(string $materialType): array
    {
        return self::API_FILTER_KEYS[$materialType] ?? [];
    }
}
