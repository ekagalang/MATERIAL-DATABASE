<?php

namespace App\Services\Material;

use App\Models\Ceramic;
use App\Repositories\CeramicRepository;
use App\Services\BaseService;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Ceramic Service
 *
 * Handle semua business logic untuk Ceramic
 */
class CeramicService extends BaseService
{
    /**
     * @var FileUploadService
     */
    protected $fileUploadService;

    /**
     * CeramicService constructor
     *
     * @param CeramicRepository $repository
     * @param FileUploadService $fileUploadService
     */
    public function __construct(CeramicRepository $repository, FileUploadService $fileUploadService)
    {
        $this->repository = $repository;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create new ceramic
     *
     * @param array $data
     * @param UploadedFile|null $photo
     * @return Ceramic
     */
    public function create(array $data, ?UploadedFile $photo = null): Ceramic
    {
        // Set default material_name
        $data['material_name'] = 'Keramik';

        // Upload photo jika ada (disimpan di folder ceramics)
        if ($photo) {
            $data['photo'] = $this->fileUploadService->upload($photo, 'ceramics');
        }

        // Create via repository
        $ceramic = $this->repository->create($data);

        // Auto-calculate derived fields (Harga per m2, Coverage)
        $this->calculateDerivedFields($ceramic);

        return $ceramic;
    }

    /**
     * Update existing ceramic
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $photo
     * @return Ceramic
     */
    public function update(int $id, array $data, ?UploadedFile $photo = null): Ceramic
    {
        $ceramic = $this->repository->findOrFail($id);

        $data['material_name'] = 'Keramik';

        // Handle photo upload/update
        if ($photo) {
            // Hapus foto lama jika ada
            if ($ceramic->photo) {
                $this->fileUploadService->delete($ceramic->photo);
            }
            $data['photo'] = $this->fileUploadService->upload($photo, 'ceramics');
        }

        $ceramic->update($data);
        $ceramic = $ceramic->fresh();

        $this->calculateDerivedFields($ceramic);

        return $ceramic;
    }

    /**
     * Delete ceramic
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $ceramic = $this->repository->findOrFail($id);

        if ($ceramic->photo) {
            $this->fileUploadService->delete($ceramic->photo);
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

    public function getAllStores(?string $search = null, int $limit = 20, string $materialType = 'ceramic'): Collection
    {
        return $this->repository->getAllStores($search, $limit, $materialType);
    }

    public function getAddressesByStore(string $store, ?string $search = null, int $limit = 20): Collection
    {
        return $this->repository->getAddressesByStore($store, $search, $limit);
    }

    /**
     * Calculate derived fields (Comparison Price per M2)
     *
     * @param Ceramic $ceramic
     * @return void
     */
    protected function calculateDerivedFields(Ceramic $ceramic): void
    {
        // Calculate Comparison Price per M2
        // Rumus: Harga per Dus / Luas Coverage per Dus
        if ($ceramic->price_per_package > 0 && $ceramic->coverage_per_package > 0) {
            $ceramic->comparison_price_per_m2 = $ceramic->price_per_package / $ceramic->coverage_per_package;
        } else {
            // Fallback: Jika Luas tidak diisi, coba hitung dari dimensi (jika ada)
            // Asumsi dimensi dalam CM
            if ($ceramic->dimension_length > 0 && $ceramic->dimension_width > 0 && $ceramic->pieces_per_package > 0) {
                // Luas (m2) = (P/100 * L/100) * Isi
                $calculatedCoverage =
                    ($ceramic->dimension_length / 100) *
                    ($ceramic->dimension_width / 100) *
                    $ceramic->pieces_per_package;

                // Update coverage jika kosong
                if ($ceramic->coverage_per_package <= 0) {
                    $ceramic->coverage_per_package = $calculatedCoverage;
                }

                if ($ceramic->price_per_package > 0) {
                    $ceramic->comparison_price_per_m2 = $ceramic->price_per_package / $calculatedCoverage;
                }
            } else {
                $ceramic->comparison_price_per_m2 = null;
            }
        }

        $ceramic->save();
    }
}
