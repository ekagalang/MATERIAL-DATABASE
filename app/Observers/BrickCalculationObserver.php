<?php

namespace App\Observers;

use App\Models\BrickCalculation;
use App\Services\Cache\CacheService;

/**
 * Brick Calculation Observer
 *
 * Auto-invalidate cache when calculations are created/updated/deleted
 * Ensures analytics and calculation caches stay fresh
 */
class BrickCalculationObserver
{
    protected CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the created event
     *
     * @param BrickCalculation $calculation
     * @return void
     */
    public function created(BrickCalculation $calculation): void
    {
        $this->invalidateCache($calculation);
    }

    /**
     * Handle the updated event
     *
     * @param BrickCalculation $calculation
     * @return void
     */
    public function updated(BrickCalculation $calculation): void
    {
        $this->invalidateCache($calculation);
    }

    /**
     * Handle the deleted event
     *
     * @param BrickCalculation $calculation
     * @return void
     */
    public function deleted(BrickCalculation $calculation): void
    {
        $this->invalidateCache($calculation);
    }

    /**
     * Invalidate relevant caches
     *
     * @param BrickCalculation $calculation
     * @return void
     */
    protected function invalidateCache(BrickCalculation $calculation): void
    {
        // Invalidate calculation log cache
        $this->cache->invalidateCalculationCache();

        // Invalidate analytics cache for the work_type
        $workType = $calculation->calculation_params['work_type'] ?? null;
        if ($workType) {
            $this->cache->invalidateAnalyticsCache($workType);
        } else {
            // If no work_type, invalidate all analytics
            $this->cache->invalidateAnalyticsCache();
        }

        // Dashboard also shows some calculation-related data
        $this->cache->invalidateDashboardCache();
    }
}
