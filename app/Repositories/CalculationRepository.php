<?php

namespace App\Repositories;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\BrickInstallationType;
use App\Models\Cement;
use App\Models\MortarFormula;
use App\Models\RecommendedCombination;
use App\Models\Sand;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Calculation Repository
 *
 * Handle semua data access untuk calculation-related queries
 * Extracted from MaterialCalculationController
 */
class CalculationRepository
{
    /**
     * Get all materials for calculation form
     *
     * @return array ['bricks' => Collection, 'cements' => Collection, 'sands' => Collection]
     */
    public function getAllMaterials(): array
    {
        return [
            'bricks' => Brick::orderBy('brand')->get(),
            'cements' => Cement::orderBy('brand')->get(),
            'sands' => Sand::orderBy('brand')->get(),
        ];
    }

    /**
     * Get installation types and mortar formulas
     *
     * @return array ['installationTypes' => Collection, 'mortarFormulas' => Collection]
     */
    public function getInstallationTypesAndFormulas(): array
    {
        return [
            'installationTypes' => BrickInstallationType::getActive(),
            'mortarFormulas' => MortarFormula::getActive(),
        ];
    }

    /**
     * Get default installation type and mortar formula
     *
     * @return array ['installationType' => BrickInstallationType|null, 'mortarFormula' => MortarFormula|null]
     */
    public function getDefaults(): array
    {
        return [
            'installationType' => BrickInstallationType::getDefault(),
            'mortarFormula' => MortarFormula::getDefault(),
        ];
    }

    /**
     * Get selected bricks by IDs
     *
     * @param array $brickIds
     * @return EloquentCollection
     */
    public function getBricksByIds(array $brickIds): EloquentCollection
    {
        return Brick::whereIn('id', $brickIds)->get();
    }

    /**
     * Get single brick by ID
     *
     * @param int $brickId
     * @return Brick|null
     */
    public function getBrickById(int $brickId): ?Brick
    {
        return Brick::find($brickId);
    }

    /**
     * Get cements ordered by price
     *
     * @param string $direction 'asc' or 'desc'
     * @return EloquentCollection
     */
    public function getCementsByPrice(string $direction = 'asc'): EloquentCollection
    {
        return Cement::orderBy('package_price', $direction)->get();
    }

    /**
     * Get sands ordered by price
     *
     * @param string $direction 'asc' or 'desc'
     * @return EloquentCollection
     */
    public function getSandsByPrice(string $direction = 'asc'): EloquentCollection
    {
        return Sand::orderBy('package_price', $direction)->get();
    }

    /**
     * Get cements with non-null brand (for combinations)
     *
     * @return EloquentCollection
     */
    public function getCementsForCombination(): EloquentCollection
    {
        return Cement::whereNotNull('brand')
            ->whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('brand')
            ->get();
    }

    /**
     * Get sands with non-null brand (for combinations)
     *
     * @return EloquentCollection
     */
    public function getSandsForCombination(): EloquentCollection
    {
        return Sand::whereNotNull('brand')
            ->whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('brand')
            ->get();
    }

    /**
     * Get recommended combinations by work type
     *
     * @param string $workType
     * @return EloquentCollection
     */
    public function getRecommendedCombinations(string $workType): EloquentCollection
    {
        return RecommendedCombination::where('work_type', $workType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get recommended brick IDs by type
     *
     * @param string $type 'best', 'common', etc.
     * @return Collection
     */
    public function getRecommendedBrickIds(string $type): Collection
    {
        return RecommendedCombination::where('type', $type)
            ->where('is_active', true)
            ->pluck('brick_id')
            ->unique()
            ->filter();
    }

    /**
     * Get cheapest bricks (for recommendations fallback)
     *
     * @param int $limit
     * @return EloquentCollection
     */
    public function getCheapestBricks(int $limit = 5): EloquentCollection
    {
        return Brick::orderBy('price_per_piece', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get calculation log with pagination and filters
     *
     * @param array $filters ['search', 'work_type', 'date_from', 'date_to']
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCalculationLog(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BrickCalculation::with([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ]);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Work type filter (from JSON field)
        if (!empty($filters['work_type'])) {
            $query->whereRaw("JSON_EXTRACT(calculation_params, '$.work_type') = ?", [$filters['work_type']]);
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find calculation by ID with relationships
     *
     * @param int $id
     * @return BrickCalculation|null
     */
    public function findCalculation(int $id): ?BrickCalculation
    {
        return BrickCalculation::with([
            'installationType',
            'mortarFormula',
            'brick',
            'cement',
            'sand',
        ])->find($id);
    }

    /**
     * Get mortar formula by cement and sand ratio
     *
     * @param int $cementRatio
     * @param int $sandRatio
     * @return MortarFormula|null
     */
    public function getMortarFormulaByRatio(int $cementRatio, int $sandRatio): ?MortarFormula
    {
        return MortarFormula::where('cement_ratio', $cementRatio)
            ->where('sand_ratio', $sandRatio)
            ->first();
    }

    /**
     * Get first active mortar formula (fallback)
     *
     * @return MortarFormula|null
     */
    public function getFirstMortarFormula(): ?MortarFormula
    {
        return MortarFormula::first();
    }

    /**
     * Get first active installation type (fallback)
     *
     * @return BrickInstallationType|null
     */
    public function getFirstInstallationType(): ?BrickInstallationType
    {
        return BrickInstallationType::where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * Select materials by price filter
     * Helper for auto-selection based on cheapest/expensive filter
     *
     * @param string $filter 'cheapest' or 'expensive'
     * @return array ['brick_id', 'cement_id', 'sand_id']
     */
    public function selectMaterialsByPrice(string $filter): array
    {
        $orderDirection = $filter === 'cheapest' ? 'asc' : 'desc';

        // Get brick based on price_per_piece
        $brick = Brick::whereNotNull('price_per_piece')
            ->where('price_per_piece', '>', 0)
            ->orderBy('price_per_piece', $orderDirection)
            ->first();

        // Get cement based on package_price
        $cement = Cement::whereNotNull('package_price')
            ->where('package_price', '>', 0)
            ->orderBy('package_price', $orderDirection)
            ->first();

        // Get sand based on comparison_price_per_m3
        $sand = Sand::whereNotNull('comparison_price_per_m3')
            ->where('comparison_price_per_m3', '>', 0)
            ->orderBy('comparison_price_per_m3', $orderDirection)
            ->first();

        // Fallback to first available if no price data
        if (!$brick) {
            $brick = Brick::first();
        }
        if (!$cement) {
            $cement = Cement::first();
        }
        if (!$sand) {
            $sand = Sand::first();
        }

        return [
            'brick_id' => $brick?->id,
            'cement_id' => $cement?->id,
            'sand_id' => $sand?->id,
        ];
    }

    /**
     * Get cements by specific IDs
     *
     * @param array $cementIds
     * @return EloquentCollection
     */
    public function getCementsByIds(array $cementIds): EloquentCollection
    {
        return Cement::whereIn('id', $cementIds)->get();
    }

    /**
     * Get sands by specific IDs
     *
     * @param array $sandIds
     * @return EloquentCollection
     */
    public function getSandsByIds(array $sandIds): EloquentCollection
    {
        return Sand::whereIn('id', $sandIds)->get();
    }

    /**
     * Find cement by ID
     *
     * @param int $cementId
     * @return Cement|null
     */
    public function findCement(int $cementId): ?Cement
    {
        return Cement::find($cementId);
    }

    /**
     * Find sand by ID
     *
     * @param int $sandId
     * @return Sand|null
     */
    public function findSand(int $sandId): ?Sand
    {
        return Sand::find($sandId);
    }
}
