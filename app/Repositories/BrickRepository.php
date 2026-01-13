<?php

namespace App\Repositories;

use App\Models\Brick;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Brick Repository
 *
 * Handle semua data access untuk Brick model
 * Memisahkan database logic dari business logic
 */
class BrickRepository extends BaseRepository
{
    /**
     * BrickRepository constructor
     *
     * @param Brick $model
     */
    public function __construct(Brick $model)
    {
        $this->model = $model;
    }

    /**
     * Search bricks dengan pagination
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
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->where('type', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%")
                    ->orWhere('form', 'like', "%{$query}%")
                    ->orWhere('store', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            })
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Get paginated bricks dengan sorting
     *
     * @param int $perPage
     * @param string|null $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */
    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->model
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Get unique field values untuk autocomplete
     *
     * @param string $field Field name (e.g., 'brand', 'type', 'form')
     * @param array $filters Additional filters (e.g., ['brand' => 'Merah'])
     * @param string|null $search Search term
     * @param int $limit Max 100
     * @return Collection
     */
    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20
    ): Collection {
        // Allowed fields whitelist (security)
        $allowedFields = [
            'type',
            'brand',
            'form',
            'store',
            'address',
            'dimension_length',
            'dimension_width',
            'dimension_height',
            'price_per_piece',
        ];

        // Return empty if field not allowed
        if (!in_array($field, $allowedFields)) {
            return collect([]);
        }

        // Validate and cap limit
        $limit = ($limit > 0 && $limit <= 100) ? $limit : 20;

        $query = $this->model->query()
            ->whereNotNull($field)
            ->where($field, '!=', '');

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        // Apply search
        if ($search) {
            $query->where($field, 'like', "%{$search}%");
        }

        return $query->select($field)
            ->groupBy($field)
            ->orderBy($field)
            ->limit($limit)
            ->pluck($field);
    }

    /**
     * Get all unique stores
     * Supports cross-material queries for auto-suggest
     *
     * @param string|null $search
     * @param int $limit Max 100
     * @param string $materialType 'brick' or 'all' (default: 'brick')
     * @return Collection
     */
    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'brick'): Collection
    {
        // Validate and cap limit
        $limit = ($limit > 0 && $limit <= 100) ? $limit : 20;

        $stores = collect();

        // Logic sama seperti old controller:
        // Jika material_type = 'brick' ATAU (no search AND 'all'): hanya dari Brick
        // Jika material_type = 'all' AND ada search: merge dari semua materials
        if ($materialType === 'brick' || ($search === '' && $materialType === 'all') || (is_null($search) && $materialType === 'all')) {
            // Hanya dari Brick
            $query = $this->model->query()
                ->whereNotNull('store')
                ->where('store', '!=', '');

            if ($search) {
                $query->where('store', 'like', "%{$search}%");
            }

            return $query->select('store')
                ->groupBy('store')
                ->orderBy('store')
                ->limit($limit)
                ->pluck('store');
        } else {
            // Merge dari SEMUA materials (Cat, Brick, Cement, Sand)
            $brickStores = $this->model->query()
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

    /**
     * Get addresses by store
     * Merges addresses from ALL materials (Brick, Cat, Cement, Sand) for given store
     *
     * @param string $store
     * @param string|null $search
     * @param int $limit Max 100
     * @return Collection
     */
    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        // Validate and cap limit
        $limit = ($limit > 0 && $limit <= 100) ? $limit : 20;

        $addresses = collect();

        // Merge addresses dari SEMUA materials seperti old controller
        $brickAddresses = $this->model->query()
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
