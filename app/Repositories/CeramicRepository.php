<?php

namespace App\Repositories;

use App\Models\Ceramic;
use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Ceramic Repository
 *
 * Handle semua data access untuk Ceramic model
 */
class CeramicRepository extends BaseRepository
{
    /**
     * CeramicRepository constructor
     *
     * @param Ceramic $model
     */
    public function __construct(Ceramic $model)
    {
        $this->model = $model;
    }

    /**
     * Search ceramics dengan pagination
     *
     * @param string $query
     * @param int $perPage
     * @param string|null $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('brand', 'like', "%{$query}%")
                    ->orWhere('sub_brand', 'like', "%{$query}%")
                    ->orWhere('type', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('color', 'like', "%{$query}%")
                    ->orWhere('store', 'like', "%{$query}%");
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Get paginated ceramics dengan sorting
     *
     * @param int $perPage
     * @param string|null $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */
    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->model->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Get unique field values untuk autocomplete
     *
     * @param string $field Field name
     * @param array $filters Additional filters
     * @param string|null $search Search term
     * @param int $limit Max 100
     * @return Collection
     */
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
            'sub_brand',
            'code',
            'color',
            'form',
            'store',
            'address',
            'dimension_length',
            'dimension_width',
            'packaging',
        ];

        if (!in_array($field, $allowedFields)) {
            return collect([]);
        }

        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;

        $query = $this->model->query()->whereNotNull($field)->where($field, '!=', '');

        // Apply filters
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

    /**
     * Get all unique stores
     * Supports cross-material queries for auto-suggest
     *
     * @param string|null $search
     * @param int $limit Max 100
     * @param string $materialType 'ceramic' or 'all'
     * @return Collection
     */
    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'ceramic'): Collection
    {
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $stores = collect();

        if (
            $materialType === 'ceramic' ||
            ($search === '' && $materialType === 'all') ||
            (is_null($search) && $materialType === 'all')
        ) {
            // Hanya dari Ceramic
            $query = $this->model->query()->whereNotNull('store')->where('store', '!=', '');

            if ($search) {
                $query->where('store', 'like', "%{$search}%");
            }

            return $query->select('store')->groupBy('store')->orderBy('store')->limit($limit)->pluck('store');
        } else {
            // Merge dari SEMUA materials (Termasuk Keramik)
            $materials = [
                $this->model, // Ceramic
                new Brick(),
                new Cat(),
                new Cement(),
                new Sand(),
            ];

            foreach ($materials as $model) {
                $results = $model
                    ->query()
                    ->whereNotNull('store')
                    ->where('store', '!=', '')
                    ->when($search, fn($q) => $q->where('store', 'like', "%{$search}%"))
                    ->pluck('store');

                $stores = $stores->merge($results);
            }

            return $stores->unique()->sort()->values()->take($limit);
        }
    }

    /**
     * Get addresses by store
     * Merges addresses from ALL materials for given store
     *
     * @param string $store
     * @param string|null $search
     * @param int $limit Max 100
     * @return Collection
     */
    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        $limit = $limit > 0 && $limit <= 100 ? $limit : 20;
        $addresses = collect();

        // Daftar Model yang punya kolom 'store' dan 'address' (Note: di Brick/Cat namanya 'short_address', di Ceramic 'address')
        // Kita perlu handle perbedaan nama kolom ini

        // 1. Ambil dari Ceramic (kolom: address)
        $ceramicAddresses = $this->model
            ->query()
            ->where('store', $store)
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->when($search, fn($q) => $q->where('address', 'like', "%{$search}%"))
            ->pluck('address');
        $addresses = $addresses->merge($ceramicAddresses);

        // 2. Ambil dari Material Lain (kolom: short_address atau address tergantung model)
        // Helper function kecil untuk fetch
        $fetchOther = function ($modelClass, $colName) use ($store, $search) {
            return $modelClass
                ::query()
                ->where('store', $store)
                ->whereNotNull($colName)
                ->where($colName, '!=', '')
                ->when($search, fn($q) => $q->where($colName, 'like', "%{$search}%"))
                ->pluck($colName);
        };

        $addresses = $addresses->merge($fetchOther(Brick::class, 'short_address'));
        $addresses = $addresses->merge($fetchOther(Cat::class, 'short_address'));
        $addresses = $addresses->merge($fetchOther(Cement::class, 'short_address'));
        $addresses = $addresses->merge($fetchOther(Sand::class, 'short_address'));

        return $addresses->unique()->sort()->values()->take($limit);
    }
}
