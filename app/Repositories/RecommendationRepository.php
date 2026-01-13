<?php

namespace App\Repositories;

use App\Models\RecommendedCombination;
use App\Services\FormulaRegistry;
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
            ->with(['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'])
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
            ->with(['brick', 'cement', 'sand', 'cat', 'ceramic', 'nat'])
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
                $workType = $rec['work_type'] ?? null;
                $requiredMaterials = $workType ? FormulaRegistry::materialsFor($workType) : [];
                $requiredMaterials = array_values(array_diff($requiredMaterials, ['brick']));

                if (!$workType || empty($requiredMaterials)) {
                    continue;
                }

                $missingRequired = false;
                foreach ($requiredMaterials as $material) {
                    $key = $material . '_id';
                    if (empty($rec[$key])) {
                        $missingRequired = true;
                        break;
                    }
                }

                if ($missingRequired) {
                    continue;
                }

                $dataToInsert[] = [
                    'work_type' => $workType,
                    'brick_id' => $rec['brick_id'] ?? null,
                    'cement_id' => $rec['cement_id'] ?? null,
                    'sand_id' => $rec['sand_id'] ?? null,
                    'cat_id' => $rec['cat_id'] ?? null,
                    'ceramic_id' => $rec['ceramic_id'] ?? null,
                    'nat_id' => $rec['nat_id'] ?? null,
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
