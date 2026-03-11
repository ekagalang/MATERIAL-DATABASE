<?php

namespace App\Observers;

use App\Services\Cache\CacheService;
use Illuminate\Database\Eloquent\Model;

class DashboardCountObserver
{
    public function __construct(
        protected CacheService $cache,
    ) {}

    public function created(Model $model): void
    {
        $this->cache->invalidateDashboardCache();
    }

    public function updated(Model $model): void
    {
        $this->cache->invalidateDashboardCache();
    }

    public function deleted(Model $model): void
    {
        $this->cache->invalidateDashboardCache();
    }
}
