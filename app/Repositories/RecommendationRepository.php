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
    public const MAX_RECOMMENDATIONS_PER_WORK_TYPE = 3;

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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('work_type')
            ->map(fn($rows) => $rows->take(self::MAX_RECOMMENDATIONS_PER_WORK_TYPE)->values());
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
            $insertedCountByWorkType = [];

            foreach ($recommendations as $rec) {
                $workType = trim((string) ($rec['work_type'] ?? ''));
                $requiredMaterials = $workType ? FormulaRegistry::materialsFor($workType) : [];
                $requiredMaterials = array_values(array_diff($requiredMaterials, ['brick']));
                if ($workType === 'grout_tile') {
                    $requiredMaterials = array_values(array_diff($requiredMaterials, ['ceramic']));
                }

                if (!$workType || empty($requiredMaterials)) {
                    continue;
                }

                $missingRequired = false;
                foreach ($requiredMaterials as $material) {
                    $key = $material . '_id';
                    if ($material === 'nat') {
                        if (empty($rec['nat_id'])) {
                            $missingRequired = true;
                            break;
                        }
                        continue;
                    }

                    if (empty($rec[$key])) {
                        $missingRequired = true;
                        break;
                    }
                }

                if ($missingRequired) {
                    continue;
                }
                if (
                    (int) ($insertedCountByWorkType[$workType] ?? 0) >=
                    self::MAX_RECOMMENDATIONS_PER_WORK_TYPE
                ) {
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
                $insertedCountByWorkType[$workType] = (int) ($insertedCountByWorkType[$workType] ?? 0) + 1;
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
