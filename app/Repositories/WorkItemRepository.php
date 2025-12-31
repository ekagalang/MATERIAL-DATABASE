<?php

namespace App\Repositories;

use App\Models\WorkItem;
use App\Models\BrickCalculation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * WorkItem Repository
 *
 * Handles data access for work items (Item Pekerjaan)
 * Extracted from WorkItemController for clean architecture
 */
class WorkItemRepository
{
    /**
     * Get paginated work items with optional search and sorting
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getWorkItems(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WorkItem::query();

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * Find work item by ID
     *
     * @param int $id
     * @return WorkItem|null
     */
    public function findWorkItem(int $id): ?WorkItem
    {
        return WorkItem::find($id);
    }

    /**
     * Create new work item
     *
     * @param array $data
     * @return WorkItem
     */
    public function createWorkItem(array $data): WorkItem
    {
        return WorkItem::create($data);
    }

    /**
     * Update work item
     *
     * @param WorkItem $workItem
     * @param array $data
     * @return bool
     */
    public function updateWorkItem(WorkItem $workItem, array $data): bool
    {
        return $workItem->update($data);
    }

    /**
     * Delete work item
     *
     * @param WorkItem $workItem
     * @return bool
     */
    public function deleteWorkItem(WorkItem $workItem): bool
    {
        return $workItem->delete();
    }

    /**
     * Get all calculations for a specific work type
     *
     * @param string $workType
     * @return Collection
     */
    public function getCalculationsByWorkType(string $workType): Collection
    {
        return BrickCalculation::where('calculation_params->work_type', $workType)
            ->with(['brick', 'cement', 'sand'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all calculations for all work types
     *
     * @return Collection
     */
    public function getAllCalculations(): Collection
    {
        return BrickCalculation::with(['brick', 'cement', 'sand'])->get();
    }
}
