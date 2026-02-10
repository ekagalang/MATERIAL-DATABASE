<?php

namespace App\Repositories;

use App\Models\Sand;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SandRepository extends BaseRepository
{
    public function __construct(Sand $model)
    {
        $this->model = $model;
    }

    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('sand_name', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%")
                    ->orWhere('store', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->model->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20,
    ): Collection {
        // Allowed fields whitelist (security)
        $allowedFields = [
            'type',
            'brand',
            'store',
            'address',
            'package_weight_gross',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'package_price',
        ];

        // Return empty if field not allowed
        if (! in_array($field, $allowedFields)) {
            return collect([]);
        }

        // Validate and cap limit
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = $this->model->query()->whereNotNull($field)->where($field, '!=', '');
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }
        if ($search) {
            $query->where($field, 'like', "%{$search}%");
        }

        return $query->select($field)->groupBy($field)->orderBy($field)->limit($limit)->pluck($field);
    }

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'sand'): Collection
    {
        // Validate and cap limit
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $stores = collect();

        if (
            $materialType === 'sand' ||
            ($search === '' && $materialType === 'all') ||
            (is_null($search) && $materialType === 'all')
        ) {
            $query = $this->model->query()->whereNotNull('store')->where('store', '!=', '');
            if ($search) {
                $query->where('store', 'like', "%{$search}%");
            }

            return $query->select('store')->groupBy('store')->orderBy('store')->limit($limit)->pluck('store');
        } else {
            // Merge dari SEMUA materials
            $sandStores = $this->model
                ->query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn ($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $brickStores = \App\Models\Brick::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn ($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $catStores = \App\Models\Cat::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn ($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            $cementStores = \App\Models\Cement::query()
                ->whereNotNull('store')
                ->where('store', '!=', '')
                ->when($search, fn ($q) => $q->where('store', 'like', "%{$search}%"))
                ->pluck('store');

            return $stores
                ->merge($catStores)
                ->merge($brickStores)
                ->merge($cementStores)
                ->merge($sandStores)
                ->unique()
                ->sort()
                ->values()
                ->take($limit);
        }
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        // Validate and cap limit
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $addresses = collect();

        // Merge addresses dari SEMUA materials
        $sandAddresses = $this->model
            ->query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn ($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $brickAddresses = \App\Models\Brick::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn ($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $catAddresses = \App\Models\Cat::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn ($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        $cementAddresses = \App\Models\Cement::query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn ($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');

        return $addresses
            ->merge($brickAddresses)
            ->merge($catAddresses)
            ->merge($cementAddresses)
            ->merge($sandAddresses)
            ->unique()
            ->sort()
            ->values()
            ->take($limit);
    }
}
