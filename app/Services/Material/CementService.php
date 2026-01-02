<?php

namespace App\Services\Material;

use App\Models\Cement;
use App\Repositories\CementRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Cement Service
 */
class CementService extends BaseService
{
    protected $fileUploadService;

    public function __construct(
        CementRepository $repository,
        FileUploadService $fileUploadService
    ) {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    public function create(array $data, ?UploadedFile $photo = null): Cement
    {
        // Upload photo
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'cements');
        }

        // Create cement
        $cement = $this->repository->create($data);

        // Auto-calculate
        $this->calculateDerivedFields($cement);

        return $cement;
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Cement
    {
        $cement = $this->repository->findOrFail($id);

        // Handle photo
        if ($photo) {
            if ($cement->photo) {
                $this->fileUploadService->delete($cement->photo);
            }
            $data['photo'] = $this->fileUploadService->upload($photo, 'cements');
        }

        $cement->update($data);
        $cement = $cement->fresh();

        // Recalculate
        $this->calculateDerivedFields($cement);

        return $cement;
    }

    public function delete(int $id): bool
    {
        $cement = $this->repository->findOrFail($id);

        if ($cement->photo) {
            $this->fileUploadService->delete($cement->photo);
        }

        return $this->repository->delete($id);
    }

    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->search($query, $perPage, $sortBy, $sortDirection);
    }

    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {
        return $this->repository->paginateWithSort($perPage, $sortBy, $sortDirection);
    }

    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20
    ): Collection {
        return $this->repository->getFieldValues($field, $filters, $search, $limit);
    }

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'cement')
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    protected function calculateDerivedFields(Cement $cement): void
    {
        // Calculate net weight
        if ((!$cement->package_weight_net || $cement->package_weight_net <= 0)
            && $cement->package_weight_gross
            && $cement->package_unit) {
            $cement->calculateNetWeight();
        }

        // Calculate volume
        if ($cement->dimension_length && $cement->dimension_width && $cement->dimension_height) {
            $cement->calculateVolume();
        }

        // Calculate comparison price
        // IMPORTANT: Set to NULL if conditions not met (same as old controller)
        if ($cement->package_price && $cement->package_weight_net && $cement->package_weight_net > 0) {
            $cement->calculateComparisonPrice();
        } else {
            $cement->comparison_price_per_kg = null;
        }

        $cement->save();
    }
}
