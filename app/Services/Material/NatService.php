<?php

namespace App\Services\Material;

use App\Models\Nat;
use App\Repositories\NatRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NatService extends BaseService
{
    protected FileUploadService $fileUploadService;

    public function __construct(NatRepository $repository, FileUploadService $fileUploadService)
    {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    public function create(array $data, ?UploadedFile $photo = null): Nat
    {
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'nats');
        }

        if (empty($data['nat_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['nat_name'] = implode(' ', $parts) ?: 'Nat';
        }

        $nat = $this->repository->create($data);
        $this->syncStoreLocationAvailability($nat, $data);
        $this->calculateDerivedFields($nat);

        return $nat;
    }

    public function update(int $id, array $data, ?UploadedFile $photo = null): Nat
    {
        $nat = $this->repository->findOrFail($id);

        if ($photo) {
            if ($nat->photo) {
                $this->fileUploadService->delete($nat->photo);
            }
            $data['photo'] = $this->fileUploadService->upload($photo, 'nats');
        }

        if (empty($data['nat_name'])) {
            $parts = array_filter([
                $data['type'] ?? '',
                $data['brand'] ?? '',
                $data['sub_brand'] ?? '',
                $data['code'] ?? '',
                $data['color'] ?? '',
            ]);
            $data['nat_name'] = implode(' ', $parts) ?: 'Nat';
        }

        $nat->update($data);
        $this->syncStoreLocationAvailability($nat, $data);
        $nat = $nat->fresh();

        $this->calculateDerivedFields($nat);

        return $nat;
    }

    public function delete(int $id): bool
    {
        $nat = $this->repository->findOrFail($id);

        if ($nat->photo) {
            $this->fileUploadService->delete($nat->photo);
        }

        return $this->repository->delete($id);
    }

    public function search(
        string $query,
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->repository->search($query, $perPage, $sortBy, $sortDirection);
    }

    public function paginateWithSort(
        int $perPage = 15,
        ?string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->repository->paginateWithSort($perPage, $sortBy, $sortDirection);
    }

    public function getFieldValues(
        string $field,
        array $filters = [],
        ?string $search = null,
        int $limit = 20,
    ): Collection {
        return $this->repository->getFieldValues($field, $filters, $search, $limit);
    }

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'nat'): Collection
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    protected function calculateDerivedFields(Nat $nat): void
    {
        if (
            (!$nat->package_weight_net || $nat->package_weight_net <= 0) &&
            $nat->package_weight_gross &&
            $nat->package_unit
        ) {
            $nat->calculateNetWeight();
        }

        if ($nat->package_price && $nat->package_weight_net && $nat->package_weight_net > 0) {
            $nat->calculateComparisonPrice();
        } else {
            $nat->comparison_price_per_kg = null;
        }

        $nat->save();
    }

    protected function syncStoreLocationAvailability(Nat $nat, array $data): void
    {
        if (!array_key_exists('store_location_id', $data)) {
            return;
        }

        $storeLocationId = $data['store_location_id'] ?? null;
        if (!empty($storeLocationId)) {
            $nat->storeLocations()->sync([$storeLocationId]);
            return;
        }

        $nat->storeLocations()->sync([]);
    }
}
