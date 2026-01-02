<?php

namespace App\Observers;

use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Material Observer
 *
 * Auto-invalidate cache when materials are created/updated/deleted
 * Ensures cache consistency with database
 */
class MaterialObserver
{
    protected CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Handle the created event
     *
     * @param Model $material
     * @return void
     */
    public function created(Model $material): void
    {
        $this->invalidateCache($material);
    }

    /**
     * Handle the updated event
     *
     * @param Model $material
     * @return void
     */
    public function updated(Model $material): void
    {
        $this->invalidateCache($material);
    }

    /**
     * Handle the deleted event
     *
     * @param Model $material
     * @return void
     */
    public function deleted(Model $material): void
    {
        $this->invalidateCache($material);
    }

    /**
     * Invalidate relevant caches based on material type
     *
     * @param Model $material
     * @return void
     */
    protected function invalidateCache(Model $material): void
    {
        // Get material type from class name
        $materialType = strtolower(class_basename($material)) . 's'; // e.g., "bricks"

        // Invalidate material cache for this type
        $this->cache->invalidateMaterialCache($materialType);

        // Dashboard shows material counts, so invalidate it too
        $this->cache->invalidateDashboardCache();
    }
}
