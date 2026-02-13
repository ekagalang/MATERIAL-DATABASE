<?php

namespace App\Repositories;

use App\Models\Nat;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NatRepository extends BaseRepository
{
    public function __construct(Nat $model)
    {
        $this->model = $model;
    }

    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        [$sortColumn, $sortDir] = $this->resolveSort($sortBy, $sortDirection);

        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('type', 'like', "%{$query}%")
                    ->orWhere('nat_name', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%")
                    ->orWhere('sub_brand', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('color', 'like', "%{$query}%")
                    ->orWhere('store', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            })
            ->orderBy($sortColumn, $sortDir)
            ->paginate($perPage);
    }

    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        [$sortColumn, $sortDir] = $this->resolveSort($sortBy, $sortDirection);

        return $this->model->orderBy($sortColumn, $sortDir)->paginate($perPage);
    }

    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20,
    ): Collection {
        $fieldMap = $this->fieldMap();
        if (!isset($fieldMap[$field])) {
            return collect([]);
        }

        $column = $fieldMap[$field];
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = $this->model->query()->whereNotNull($column)->where($column, '!=', '');

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (!isset($fieldMap[$key])) {
                continue;
            }
            $query->where($fieldMap[$key], $value);
        }

        if ($search) {
            $query->where($column, 'like', "%{$search}%");
        }

        return $query->select($column)->groupBy($column)->orderBy($column)->limit($limit)->pluck($column);
    }

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'nat'): Collection
    {
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        if (
            $materialType === 'nat' ||
            ($search === '' && $materialType === 'all') ||
            (is_null($search) && $materialType === 'all')
        ) {
            $query = $this->model->query()->whereNotNull('store')->where('store', '!=', '');

            if ($search) {
                $query->where('store', 'like', "%{$search}%");
            }

            return $query->select('store')->groupBy('store')->orderBy('store')->limit($limit)->pluck('store');
        }

        $natStores = $this->model
            ->query()
            ->whereNotNull('store')
            ->where('store', '!=', '')
            ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
            ->pluck('store');

        $brickStores = \App\Models\Brick::query()
            ->whereNotNull('store')
            ->where('store', '!=', '')
            ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
            ->pluck('store');

        $catStores = \App\Models\Cat::query()
            ->whereNotNull('store')
            ->where('store', '!=', '')
            ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
            ->pluck('store');

        $cementStores = \App\Models\Cement::query()
            ->whereNotNull('store')
            ->where('store', '!=', '')
            ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
            ->pluck('store');

        $sandStores = \App\Models\Sand::query()
            ->whereNotNull('store')
            ->where('store', '!=', '')
            ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
            ->pluck('store');

        return collect()
            ->merge($catStores)
            ->merge($brickStores)
            ->merge($cementStores)
            ->merge($sandStores)
            ->merge($natStores)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $natAddresses = $this->model
            ->query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $brickAddresses = \App\Models\Brick::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $catAddresses = \App\Models\Cat::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $cementAddresses = \App\Models\Cement::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $sandAddresses = \App\Models\Sand::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        return collect()
            ->merge($brickAddresses)
            ->merge($catAddresses)
            ->merge($cementAddresses)
            ->merge($sandAddresses)
            ->merge($natAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);
    }

    private function resolveSort(?string $sortBy, string $sortDirection): array
    {
        $sortMap = [
            'type' => 'type',
            'nat_name' => 'nat_name',
            'brand' => 'brand',
            'sub_brand' => 'sub_brand',
            'code' => 'code',
            'color' => 'color',
            'package_unit' => 'package_unit',
            'package_weight' => 'package_weight_net',
            'package_weight_net' => 'package_weight_net',
            'store' => 'store',
            'address' => 'address',
            'price_per_bag' => 'package_price',
            'package_price' => 'package_price',
            'comparison_price_per_kg' => 'comparison_price_per_kg',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        $column = $sortMap[$sortBy] ?? 'created_at';
        $direction = in_array(strtolower($sortDirection), ['asc', 'desc'], true) ? strtolower($sortDirection) : 'desc';

        return [$column, $direction];
    }

    private function fieldMap(): array
    {
        return [
            'type' => 'type',
            'nat_name' => 'nat_name',
            'brand' => 'brand',
            'sub_brand' => 'sub_brand',
            'code' => 'code',
            'color' => 'color',
            'package_unit' => 'package_unit',
            'store' => 'store',
            'address' => 'address',
            'price_unit' => 'price_unit',
            'package_weight_gross' => 'package_weight_gross',
            'package_price' => 'package_price',
        ];
    }
}
