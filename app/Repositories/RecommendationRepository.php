<?php

namespace App\Repositories;

use App\Models\RecommendedCombination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Recommendation Repository
 *
 * Handles data access for recommended material combinations
 * Extracted from RecommendedCombinationController
 */
class RecommendationRepository
{
    /**
     * Get all recommendations grouped by work_type
     *
     * @return SupportCollection
     */
    public function getRecommendationsGroupedByWorkType(): SupportCollection
    {
        return RecommendedCombination::where('type', 'best')
            ->with(['brick', 'cement', 'sand'])
            ->orderBy('work_type')
            ->get()
            ->groupBy('work_type');
    }

    /**
     * Get all recommendations (not grouped)
     *
     * @return Collection
     */
    public function getAllRecommendations(): Collection
    {
        return RecommendedCombination::where('type', 'best')
            ->with(['brick', 'cement', 'sand'])
            ->orderBy('work_type')
            ->get();
    }

    /**
     * Bulk update recommendations using transaction
     * Deletes all existing 'best' recommendations and inserts new ones
     *
     * Extracted from RecommendedCombinationController::store() lines 50-85
     *
     * @param array $recommendations
     * @return void
     * @throws \Exception
     */
    public function bulkUpdateRecommendations(array $recommendations): void
    {
        DB::beginTransaction();

        try {
            // 1. Delete all existing 'best' recommendations
            // This ensures that removed rows in the UI are removed from the DB
            RecommendedCombination::where('type', 'best')->delete();

            // 2. Prepare data to insert
            $dataToInsert = [];

            foreach ($recommendations as $rec) {
                // Skip if work_type, brick_id, cement_id, or sand_id are empty
                if (
                    empty($rec['work_type']) ||
                    empty($rec['brick_id']) ||
                    empty($rec['cement_id']) ||
                    empty($rec['sand_id'])
                ) {
                    continue;
                }

                $dataToInsert[] = [
                    'work_type' => $rec['work_type'],
                    'brick_id' => $rec['brick_id'],
                    'cement_id' => $rec['cement_id'],
                    'sand_id' => $rec['sand_id'],
                    'type' => 'best',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3. Insert new recommendations
            if (!empty($dataToInsert)) {
                RecommendedCombination::insert($dataToInsert);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
