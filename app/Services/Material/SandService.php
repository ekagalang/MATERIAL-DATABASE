<?php

namespace App\Services\Material;

use App\Models\Sand;
use App\Repositories\SandRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

class SandService extends BaseService
{
    protected $fileUploadService;

    public function __construct(SandRepository $repository, FileUploadService $fileUploadService)
    {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    public function create(array $data, ?UploadedFile $photo = null): Sand
    {
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'sands');
        }
        $sand = $this->repository->create($data);
        $this->calculateDerivedFields($sand);
        return $sand;
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Sand
    {
        $sand = $this->repository->findOrFail($id);
        if ($photo) {
            if ($sand->photo) $this->fileUploadService->delete($sand->photo);
            $data['photo'] = $this->fileUploadService->upload($photo, 'sands');
        }
        $sand->update($data);
        $sand = $sand->fresh();
        $this->calculateDerivedFields($sand);
        return $sand;
    }

    public function delete(int $id): bool
    {
        $sand = $this->repository->findOrFail($id);
        if ($sand->photo) $this->fileUploadService->delete($sand->photo);
        return $this->repository->delete($id);
    }

    public function search(string $query, int $perPage = 15, ?string $sortBy = 'created_at', string $sortDirection = 'desc')
    {
        return $this->repository->search($query, $perPage, $sortBy, $sortDirection);
    }

    public function paginateWithSort(int $perPage = 15, ?string $sortBy = 'created_at', string $sortDirection = 'desc')
    {
        return $this->repository->paginateWithSort($perPage, $sortBy, $sortDirection);
    }

    public function getFieldValues(string $field, array $filters = [], ?string $search = null, int $limit = 20)
    {
        return $this->repository->getFieldValues($field, $filters, $search, $limit);
    }

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'sand')
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20)
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    protected function calculateDerivedFields(Sand $sand): void
    {
        if ((!$sand->package_weight_net || $sand->package_weight_net <= 0) && $sand->package_weight_gross && $sand->package_unit) {
            $sand->calculateNetWeight();
        }
        if ($sand->dimension_length && $sand->dimension_width && $sand->dimension_height) {
            $sand->calculateVolume();
        }
        // IMPORTANT: Set to NULL if conditions not met (same as old controller)
        if ($sand->package_price && $sand->package_volume && $sand->package_volume > 0) {
            $sand->calculateComparisonPrice();
        } else {
            $sand->comparison_price_per_m3 = null;
        }
        $sand->save();
    }
}
