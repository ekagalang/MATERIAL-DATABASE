<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Cache Service
 *
 * Centralized caching logic untuk performance optimization
 * Target: 80-90% faster repeated requests
 */
class CacheService
{
    /**
     * Cache TTL (Time To Live) in seconds
     */
    protected const CACHE_TTL = [
        'materials' => 3600, // 1 hour - material lists don't change often
        'dashboard' => 300, // 5 minutes - dashboard needs fresher data
        'analytics' => 1800, // 30 minutes - analytics can be slightly stale
        'calculations' => 600, // 10 minutes - calculation history
        'combinations' => 3600, // 1 hour - recommended combinations
    ];

    /**
     * Cache key prefixes
     */
    protected const CACHE_KEYS = [
        'materials' => 'materials',
        'dashboard' => 'dashboard',
        'analytics' => 'analytics',
        'calculations' => 'calculations',
        'combinations' => 'combinations',
    ];

    /**
     * Get or cache material index
     *
     * @param  string  $materialType  'bricks', 'cements', 'sands', 'cats'
     * @return mixed
     */
    public function getMaterialIndex(string $materialType, array $filters, callable $callback)
    {
        $cacheKey = $this->buildCacheKey(self::CACHE_KEYS['materials'], $materialType, $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL['materials'], $callback);
    }

    /**
     * Get or cache dashboard data
     *
     * @return mixed
     */
    public function getDashboardData(callable $callback)
    {
        $cacheKey = $this->buildCacheKey(self::CACHE_KEYS['dashboard'], 'main');

        return Cache::remember($cacheKey, self::CACHE_TTL['dashboard'], $callback);
    }

    /**
     * Get or cache analytics data
     *
     * @param  string  $type  'summary' or 'detailed'
     * @return mixed
     */
    public function getAnalytics(string $workType, string $type, callable $callback)
    {
        $cacheKey = $this->buildCacheKey(self::CACHE_KEYS['analytics'], $workType, ['type' => $type]);

        return Cache::remember($cacheKey, self::CACHE_TTL['analytics'], $callback);
    }

    /**
     * Get or cache calculation log
     *
     * @return mixed
     */
    public function getCalculationLog(array $filters, int $page, callable $callback)
    {
        $cacheKey = $this->buildCacheKey(
            self::CACHE_KEYS['calculations'],
            'log',
            array_merge($filters, ['page' => $page]),
        );

        return Cache::remember($cacheKey, self::CACHE_TTL['calculations'], $callback);
    }

    /**
     * Get or cache recommended combinations
     *
     * @return mixed
     */
    public function getRecommendedCombinations(string $workType, callable $callback)
    {
        $cacheKey = $this->buildCacheKey(self::CACHE_KEYS['combinations'], $workType);

        return Cache::remember($cacheKey, self::CACHE_TTL['combinations'], $callback);
    }

    /**
     * Invalidate material cache
     *
     * @param  string|null  $materialType  Specific type or null for all
     */
    public function invalidateMaterialCache(?string $materialType = null): void
    {
        if ($materialType) {
            // Invalidate specific material type
            Cache::forget($this->buildCacheKey(self::CACHE_KEYS['materials'], $materialType));

            // Also use tags if available (Redis)
            if ($this->supportsTagging()) {
                Cache::tags([self::CACHE_KEYS['materials'], $materialType])->flush();
            }
        } else {
            // Invalidate all materials
            if ($this->supportsTagging()) {
                Cache::tags([self::CACHE_KEYS['materials']])->flush();
            } else {
                // Flush by pattern (not all drivers support this)
                $this->flushByPattern(self::CACHE_KEYS['materials'] . ':*');
            }
        }

        // Also invalidate dashboard since it shows material counts
        $this->invalidateDashboardCache();
    }

    /**
     * Invalidate dashboard cache
     */
    public function invalidateDashboardCache(): void
    {
        Cache::forget($this->buildCacheKey(self::CACHE_KEYS['dashboard'], 'main'));

        if ($this->supportsTagging()) {
            Cache::tags([self::CACHE_KEYS['dashboard']])->flush();
        }
    }

    /**
     * Invalidate analytics cache
     *
     * @param  string|null  $workType  Specific work type or null for all
     */
    public function invalidateAnalyticsCache(?string $workType = null): void
    {
        if ($workType) {
            Cache::forget($this->buildCacheKey(self::CACHE_KEYS['analytics'], $workType, ['type' => 'summary']));
            Cache::forget($this->buildCacheKey(self::CACHE_KEYS['analytics'], $workType, ['type' => 'detailed']));
        }

        if ($this->supportsTagging()) {
            $tags = $workType ? [self::CACHE_KEYS['analytics'], $workType] : [self::CACHE_KEYS['analytics']];
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Invalidate calculation cache
     */
    public function invalidateCalculationCache(): void
    {
        if ($this->supportsTagging()) {
            Cache::tags([self::CACHE_KEYS['calculations']])->flush();
        } else {
            $this->flushByPattern(self::CACHE_KEYS['calculations'] . ':*');
        }

        // Also invalidate analytics since calculations affect analytics
        $this->invalidateAnalyticsCache();
    }

    /**
     * Invalidate recommended combinations cache
     */
    public function invalidateCombinationsCache(?string $workType = null): void
    {
        if ($workType) {
            Cache::forget($this->buildCacheKey(self::CACHE_KEYS['combinations'], $workType));
        }

        if ($this->supportsTagging()) {
            $tags = $workType ? [self::CACHE_KEYS['combinations'], $workType] : [self::CACHE_KEYS['combinations']];
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Build cache key from components
     */
    protected function buildCacheKey(string $prefix, string $identifier = '', array $params = []): string
    {
        $key = $prefix;

        if ($identifier) {
            $key .= ':' . $identifier;
        }

        if (!empty($params)) {
            // Sort params for consistent cache keys
            ksort($params);
            $key .= ':' . md5(serialize($params));
        }

        return $key;
    }

    /**
     * Check if cache driver supports tagging
     */
    protected function supportsTagging(): bool
    {
        $driver = config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    /**
     * Flush cache by pattern
     * Note: Only works with some drivers
     */
    protected function flushByPattern(string $pattern): void
    {
        // This is a simplified version
        // In production with Redis, you'd use SCAN to find and delete keys
        // For database cache, we can query the cache table

        $driver = config('cache.default');

        if ($driver === 'database') {
            // For database cache, delete matching keys
            \DB::table('cache')->where('key', 'LIKE', str_replace('*', '%', $pattern))->delete();
        }
    }

    /**
     * Clear all application cache
     * Use with caution!
     */
    public function clearAllCache(): void
    {
        Cache::flush();
    }
}
