<?php

namespace App\Repositories;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Unit Repository
 *
 * Handles data access for units (satuan)
 * Extracted from UnitController for clean architecture
 */
class UnitRepository
{
    /**
     * Get paginated units with optional filters and sorting
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUnits(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Unit::query()->with('materialTypes');

        // Filter by material type
        if (!empty($filters['material_type'])) {
            $query->whereHas('materialTypes', function ($q) use ($filters) {
                $q->where('material_type', $filters['material_type']);
            });
        }

        // Sorting with whitelist
        $allowedSorts = ['code', 'name', 'package_weight', 'created_at'];
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        // Validate sort column
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
            $sortDirection = 'asc';
        } else {
            // Validate direction
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Find unit by ID
     *
     * @param int $id
     * @return Unit|null
     */
    public function findUnit(int $id): ?Unit
    {
        return Unit::with('materialTypes')->find($id);
    }

    /**
     * Create new unit with material types
     *
     * @param array $data
     * @return Unit
     */
    public function createUnit(array $data): Unit
    {
        $unit = Unit::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'package_weight' => $data['package_weight'],
            'description' => $data['description'] ?? null,
        ]);

        // Create material type relationships
        if (!empty($data['material_types'])) {
            foreach ($data['material_types'] as $type) {
                $unit->materialTypes()->create(['material_type' => $type]);
            }
        }

        return $unit->load('materialTypes');
    }

    /**
     * Update unit and sync material types
     *
     * @param Unit $unit
     * @param array $data
     * @return Unit
     */
    public function updateUnit(Unit $unit, array $data): Unit
    {
        $unit->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'package_weight' => $data['package_weight'],
            'description' => $data['description'] ?? null,
        ]);

        // Sync material types (delete all then insert new)
        $unit->materialTypes()->delete();

        if (!empty($data['material_types'])) {
            foreach ($data['material_types'] as $type) {
                $unit->materialTypes()->create(['material_type' => $type]);
            }
        }

        return $unit->load('materialTypes');
    }

    /**
     * Delete unit
     *
     * @param Unit $unit
     * @return bool
     */
    public function deleteUnit(Unit $unit): bool
    {
        return $unit->delete();
    }

    /**
     * Get units grouped by material type
     *
     * @return array
     */
    public function getUnitsGroupedByMaterialType(): array
    {
        return Unit::getGroupedByMaterialType();
    }

    /**
     * Get available material types with labels
     *
     * @return array
     */
    public function getMaterialTypesWithLabels(): array
    {
        return Unit::getMaterialTypesWithLabels();
    }
}
