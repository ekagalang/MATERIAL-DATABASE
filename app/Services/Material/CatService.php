<?php

namespace App\Services\Material;

use App\Models\Cat;
use App\Repositories\CatRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;

class CatService extends BaseService
{
    protected $fileUploadService;

    public function __construct(CatRepository $repository, FileUploadService $fileUploadService)
    {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    public function create(array $data, ?UploadedFile $photo = null): Cat
    {
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'cats');
        }
        $cat = $this->repository->create($data);
        $this->calculateDerivedFields($cat);
        return $cat;
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Cat
    {
        $cat = $this->repository->findOrFail($id);
        if ($photo) {
            if ($cat->photo) {
                $this->fileUploadService->delete($cat->photo);
            }
            $data['photo'] = $this->fileUploadService->upload($photo, 'cats');
        }
        $cat->update($data);
        $cat = $cat->fresh();
        $this->calculateDerivedFields($cat);
        return $cat;
    }

    public function delete(int $id): bool
    {
        $cat = $this->repository->findOrFail($id);
        if ($cat->photo) {
            $this->fileUploadService->delete($cat->photo);
        }
        return $this->repository->delete($id);
    }

    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ) {
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

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'cat')
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20)
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    protected function calculateDerivedFields(Cat $cat): void
    {
        if (
            (!$cat->package_weight_net || $cat->package_weight_net <= 0) &&
            $cat->package_weight_gross &&
            $cat->package_unit
        ) {
            $cat->calculateNetWeight();
        }
        // IMPORTANT: Set to NULL if conditions not met (same as old controller)
        if ($cat->purchase_price && $cat->package_weight_net && $cat->package_weight_net > 0) {
            $cat->calculateComparisonPrice();
        } else {
            $cat->comparison_price_per_kg = null;
        }
        $cat->save();
    }
}
