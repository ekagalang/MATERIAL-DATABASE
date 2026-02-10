<?php

namespace App\Services\Material;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use Illuminate\Database\Eloquent\Model;

class MaterialDuplicateService
{
    /**
     * @var array<string, array{model: class-string<Model>, fields: string[], numeric: string[]}>
     */
    private const MATERIALS = [
        'brick' => [
            'model' => Brick::class,
            'fields' => [
                'type',
                'brand',
                'form',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'store',
                'address',
                'price_per_piece',
            ],
            'numeric' => ['dimension_length', 'dimension_width', 'dimension_height', 'price_per_piece'],
        ],
        'cement' => [
            'model' => Cement::class,
            'fields' => [
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
                'price_unit',
            ],
            'numeric' => ['package_weight_gross', 'package_weight_net', 'package_price'],
        ],
        'sand' => [
            'model' => Sand::class,
            'fields' => [
                'type',
                'brand',
                'package_unit',
                'package_weight_gross',
                'package_weight_net',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'store',
                'address',
                'package_price',
            ],
            'numeric' => [
                'package_weight_gross',
                'package_weight_net',
                'dimension_length',
                'dimension_width',
                'dimension_height',
                'package_price',
            ],
        ],
        'cat' => [
            'model' => Cat::class,
            'fields' => [
                'type',
                'brand',
                'sub_brand',
                'color_code',
                'color_name',
                'form',
                'package_unit',
                'package_weight_gross',
                'package_weight_net',
                'volume',
                'volume_unit',
                'store',
                'address',
                'purchase_price',
                'price_unit',
            ],
            'numeric' => ['package_weight_gross', 'package_weight_net', 'volume', 'purchase_price'],
        ],
        'ceramic' => [
            'model' => Ceramic::class,
            'fields' => [
                'type',
                'brand',
                'sub_brand',
                'code',
                'color',
                'form',
                'surface',
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'packaging',
                'pieces_per_package',
                'coverage_per_package',
                'store',
                'address',
                'price_per_package',
                'comparison_price_per_m2',
            ],
            'numeric' => [
                'dimension_length',
                'dimension_width',
                'dimension_thickness',
                'pieces_per_package',
                'coverage_per_package',
                'price_per_package',
                'comparison_price_per_m2',
            ],
        ],
        'nat' => [
            'model' => Nat::class,
            'fields' => [
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
                'price_unit',
            ],
            'numeric' => ['package_weight_gross', 'package_weight_net', 'package_price'],
        ],
    ];

    public function findDuplicate(string $materialType, array $data, ?int $ignoreId = null): ?Model
    {
        $config = self::MATERIALS[$materialType] ?? null;
        if (!$config) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $query = $modelClass::query();

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        $numericFields = $config['numeric'];

        foreach ($config['fields'] as $field) {
            $isNumeric = in_array($field, $numericFields, true);
            $value = $data[$field] ?? null;

            if ($isNumeric) {
                if ($value === null || $value === '') {
                    $query->whereNull($field);
                } else {
                    $query->where($field, $this->normalizeNumeric($value));
                }
                continue;
            }

            $normalized = $this->normalizeString($value);
            if ($normalized === '') {
                $query->where(function ($q) use ($field) {
                    $q->whereNull($field)->orWhereRaw("TRIM(COALESCE({$field}, '')) = ''");
                });
            } else {
                $query->whereRaw("LOWER(TRIM(COALESCE({$field}, ''))) = ?", [$normalized]);
            }
        }

        return $query->first();
    }

    private function normalizeString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = trim((string) $value);

        return mb_strtolower($normalized);
    }

    private function normalizeNumeric(mixed $value): float
    {
        if (is_string($value)) {
            $value = trim($value);
            if (str_contains($value, ',') && str_contains($value, '.')) {
                $value = str_replace('.', '', $value);
            }
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
