<?php

namespace App\Services\Material;

use App\Models\Brick;
use App\Repositories\BrickRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Brick Service
 *
 * Handle semua business logic untuk Brick
 * Reusable - bisa dipanggil dari Controller, Command, Job, dll
 */
class BrickService extends BaseService
{
    /**
     * @var FileUploadService
     */
    protected $fileUploadService;

    /**
     * BrickService constructor
     *
     * @param BrickRepository $repository
     * @param FileUploadService $fileUploadService
     */
    public function __construct(BrickRepository $repository, FileUploadService $fileUploadService)
    {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create new brick
     *
     * @param array $data
     * @param UploadedFile|null $photo
     * @return Brick
     */
    public function create(array $data, ?UploadedFile $photo = null): Brick
    {
        // Set material_name (always "Bata")
        $data['material_name'] = 'Bata';

        // Upload photo jika ada
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'bricks');
        }

        // Create brick via repository
        $brick = $this->repository->create($data);

        // Auto-calculate derived fields
        $this->calculateDerivedFields($brick);

        return $brick;
    }

    /**
     * Update existing brick
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $photo
     * @return Brick
     */
    public function update(int $id, array $data, ?UploadedFile $photo = null): Brick
    {
        $brick = $this->repository->findOrFail($id);

        // Set material_name (always "Bata")
        $data['material_name'] = 'Bata';

        // Handle photo upload/update
        if ($photo) {
            // Delete old photo if exists
            if ($brick->photo) {
                $this->fileUploadService->delete($brick->photo);
            }

            // Upload new photo
            $data['photo'] = $this->fileUploadService->upload($photo, 'bricks');
        }

        // Update brick
        $brick->update($data);
        $brick = $brick->fresh();

        // Recalculate derived fields
        $this->calculateDerivedFields($brick);

        return $brick;
    }

    /**
     * Delete brick
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $brick = $this->repository->findOrFail($id);

        // Delete photo if exists
        if ($brick->photo) {
            $this->fileUploadService->delete($brick->photo);
        }

        return $this->repository->delete($id);
    }

    /**
     * Search bricks
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
        return $this->repository->search($query, $perPage, $sortBy, $sortDirection);
    }

    /**
     * Get paginated bricks with sorting
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
        return $this->repository->paginateWithSort($perPage, $sortBy, $sortDirection);
    }

    /**
     * Get field values untuk autocomplete
     *
     * @param string $field
     * @param array $filters
     * @param string|null $search
     * @param int $limit
     * @return Collection
     */
    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20,
    ): Collection {
        return $this->repository->getFieldValues($field, $filters, $search, $limit);
    }

    /**
     * Get all stores
     *
     * @param string|null $search
     * @param int $limit
     * @param string $materialType 'brick' or 'all'
     * @return Collection
     */
    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'brick'): Collection
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    /**
     * Get addresses by store
     *
     * @param string $store
     * @param string|null $search
     * @param int $limit
     * @return Collection
     */
    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    /**
     * Calculate derived fields (volume, comparison price)
     *
     * @param Brick $brick
     * @return void
     */
    protected function calculateDerivedFields(Brick $brick): void
    {
        // Calculate volume if dimensions are provided
        if ($brick->dimension_length && $brick->dimension_width && $brick->dimension_height) {
            $brick->calculateVolume();
        }

        $brick->syncPricingByPackageType();

        $brick->save();
    }
}
