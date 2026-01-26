<?php

namespace App\Services\Calculation;

use App\Repositories\CalculationRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Material Selection Service
 *
 * Handle material selection logic for calculations:
 * - Auto-select bricks based on recommendations
 * - Select materials by price (cheapest/expensive)
 * - Fallback logic when no data available
 *
 * Extracted from MaterialCalculationController lines 300-345, 1320-1358
 */
class MaterialSelectionService
{
    protected CalculationRepository $repository;

    public function __construct(CalculationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Select bricks based on request parameters and filter type
     *
     * Logic extracted from MaterialCalculationController lines 300-344
     *
     * @param array $request Request data with brick_ids, brick_id, work_type
     * @param array $priceFilters ['best', 'cheapest', 'expensive', etc.]
     * @return EloquentCollection
     */
    public function selectBricks(array $request, array $priceFilters): EloquentCollection
    {
        $hasBrickIds = !empty($request['brick_ids']);
        $hasBrickId = !empty($request['brick_id']);
        $workType = $request['work_type'] ?? 'brick_half';

        $targetBricks = collect();

        // Case 1: No brick specified - auto-select based on filter type
        if (!$hasBrickIds && !$hasBrickId) {
            $targetBricks = $this->autoSelectBricks($priceFilters, $workType);
        }
        // Case 2: Brick(s) specified
        else {
            // Special case for 'best' filter only
            if (in_array('best', $priceFilters) && count($priceFilters) === 1) {
                $targetBricks = $this->selectBricksForBestFilter($request, $workType);
            }
            // Normal behavior for other filters
            else {
                $targetBricks = $this->selectBricksByRequest($request);
            }
        }

        // Ensure bricks with Populer history are included when requested
        if (in_array('common', $priceFilters, true)) {
            $commonBrickIds = $this->repository->getCommonBrickIdsByWorkType($workType);
            if ($commonBrickIds->isNotEmpty()) {
                $commonBricks = $this->repository->getBricksByIds($commonBrickIds->toArray());
                $targetBricks = $targetBricks->merge($commonBricks);
            }
        }

        return $targetBricks->unique('id')->values();
    }

    /**
     * Auto-select bricks when no brick specified
     *
     * @param array $priceFilters
     * @param string $workType
     * @return EloquentCollection
     */
    protected function autoSelectBricks(array $priceFilters, string $workType): EloquentCollection
    {
        $targetBricks = collect();

        // If 'best' filter, get bricks with recommendations
        if (in_array('best', $priceFilters)) {
            $recommendedBrickIds = $this->repository->getRecommendedBrickIds('best', $workType)
                ->filter(function ($brickId) use ($workType) {
                    // Additional filtering by work_type could be added here
                    return true;
                });

            if ($recommendedBrickIds->isNotEmpty()) {
                $targetBricks = $this->repository->getBricksByIds($recommendedBrickIds->toArray());
            }
        }

        // Fallback: get top 5 cheapest bricks
        if ($targetBricks->isEmpty()) {
            $targetBricks = $this->repository->getCheapestBricks(5);
        }

        return $targetBricks;
    }

    /**
     * Select bricks for 'best' filter with user-specified bricks
     *
     * @param array $request
     * @param string $workType
     * @return EloquentCollection
     */
    protected function selectBricksForBestFilter(array $request, string $workType): EloquentCollection
    {
        // Try to get recommended bricks first
        $recommendedBrickIds = $this->repository->getRecommendedBrickIds('best', $workType);

        if ($recommendedBrickIds->isNotEmpty()) {
            return $this->repository->getBricksByIds($recommendedBrickIds->toArray());
        }

        // Fallback to normal selection if no recommendations exist
        return $this->selectBricksByRequest($request);
    }

    /**
     * Select bricks by request (brick_ids or brick_id)
     *
     * @param array $request
     * @return EloquentCollection
     */
    protected function selectBricksByRequest(array $request): EloquentCollection
    {
        $hasBrickIds = !empty($request['brick_ids']);
        $hasBrickId = !empty($request['brick_id']);

        if ($hasBrickIds) {
            return $this->repository->getBricksByIds($request['brick_ids']);
        } elseif ($hasBrickId) {
            $brick = $this->repository->getBrickById($request['brick_id']);
            return $brick ? collect([$brick]) : collect();
        }

        return collect();
    }

    /**
     * Select materials (cement, sand) by price filter
     *
     * Extracted from MaterialCalculationController lines 1320-1358
     *
     * @param string $filter 'cheapest' or 'expensive'
     * @return array ['brick_id', 'cement_id', 'sand_id']
     */
    public function selectMaterialsByPrice(string $filter): array
    {
        return $this->repository->selectMaterialsByPrice($filter);
    }

    /**
     * Get cements for combination calculation
     * Based on filter type
     *
     * @param array $priceFilters ['best', 'cheapest', 'expensive', etc.]
     * @param string|null $workType
     * @param int|null $specificCementId For 'custom' filter
     * @return EloquentCollection
     */
    public function getCementsForCombination(
        array $priceFilters,
        ?string $workType = null,
        ?int $specificCementId = null
    ): EloquentCollection {
        // Custom filter - specific cement ID
        if (in_array('custom', $priceFilters) && $specificCementId) {
            return $this->repository->getCementsByIds([$specificCementId]);
        }

        // Best filter - from recommendations
        if (in_array('best', $priceFilters)) {
            $recommendations = $this->repository->getRecommendedCombinations($workType ?? 'brick_half');
            $cementIds = $recommendations->pluck('cement_id')->unique()->filter()->toArray();

            if (!empty($cementIds)) {
                return $this->repository->getCementsByIds($cementIds);
            }
        }

        // Cheapest filter
        if (in_array('cheapest', $priceFilters)) {
            return $this->repository->getCementsByPrice('asc');
        }

        // Expensive filter
        if (in_array('expensive', $priceFilters)) {
            return $this->repository->getCementsByPrice('desc');
        }

        // Medium or default - all cements with price
        return $this->repository->getCementsForCombination();
    }

    /**
     * Get sands for combination calculation
     * Based on filter type
     *
     * @param array $priceFilters ['best', 'cheapest', 'expensive', etc.]
     * @param string|null $workType
     * @param int|null $specificSandId For 'custom' filter
     * @return EloquentCollection
     */
    public function getSandsForCombination(
        array $priceFilters,
        ?string $workType = null,
        ?int $specificSandId = null
    ): EloquentCollection {
        // Custom filter - specific sand ID
        if (in_array('custom', $priceFilters) && $specificSandId) {
            return $this->repository->getSandsByIds([$specificSandId]);
        }

        // Best filter - from recommendations
        if (in_array('best', $priceFilters)) {
            $recommendations = $this->repository->getRecommendedCombinations($workType ?? 'brick_half');
            $sandIds = $recommendations->pluck('sand_id')->unique()->filter()->toArray();

            if (!empty($sandIds)) {
                return $this->repository->getSandsByIds($sandIds);
            }
        }

        // Cheapest filter
        if (in_array('cheapest', $priceFilters)) {
            return $this->repository->getSandsByPrice('asc');
        }

        // Expensive filter
        if (in_array('expensive', $priceFilters)) {
            return $this->repository->getSandsByPrice('desc');
        }

        // Medium or default - all sands with price
        return $this->repository->getSandsForCombination();
    }

    /**
     * Get default mortar formula (1:3 or first available)
     *
     * Extracted from MaterialCalculationController line 389-390
     *
     * @return \App\Models\MortarFormula|null
     */
    public function getDefaultMortarFormula(): ?\App\Models\MortarFormula
    {
        $formula = $this->repository->getMortarFormulaByRatio(1, 3);

        if (!$formula) {
            $formula = $this->repository->getFirstMortarFormula();
        }

        return $formula;
    }
}
