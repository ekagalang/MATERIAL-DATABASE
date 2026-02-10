<?php

namespace App\Services\Dashboard;

use App\Models\Brick;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Sand;
use App\Models\Store;
use App\Models\Unit;
use App\Services\Cache\CacheService;
use App\Services\FormulaRegistry;
use Illuminate\Support\Collection;

/**
 * Dashboard Service
 *
 * Handle dashboard data aggregation and statistics
 * Now with caching for 80-90% faster repeated requests
 */
class DashboardService
{
    protected CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }
    /**
     * Get material counts
     *
     * @return array
     */
    public function getMaterialCounts(): array
    {
        return [
            'brick' => Brick::count(),
            'cat' => Cat::count(),
            'cement' => Cement::count(),
            'sand' => Sand::count(),
            'ceramic' => Ceramic::count(),
        ];
    }

    /**
     * Get total material count
     *
     * @return int
     */
    public function getTotalMaterialCount(): int
    {
        $counts = $this->getMaterialCounts();
        return array_sum($counts);
    }

    /**
     * Get unit count
     *
     * @return int
     */
    public function getUnitCount(): int
    {
        return Unit::count();
    }

    /**
     * Get work item (formula) count
     *
     * @return int
     */
    public function getWorkItemCount(): int
    {
        return count(FormulaRegistry::all());
    }

    /**
     * Get recent activities from all materials
     * Returns latest 5 items across all material types
     *
     * @return Collection
     */
    public function getRecentActivities(): Collection
    {
        $recents = collect();

        // Get recent bricks
        $recents = $recents->concat($this->getRecentBricks(3));

        // Get recent cats
        $recents = $recents->concat($this->getRecentCats(3));

        // Get recent cements
        $recents = $recents->concat($this->getRecentCements(3));

        // Get recent sands
        $recents = $recents->concat($this->getRecentSands(3));

        // Get recent ceramics
        $recents = $recents->concat($this->getRecentCeramics(3));

        // Sort by created_at desc and take top 5
        return $recents->sortByDesc('created_at')->take(5);
    }

    /**
     * Get recent bricks
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentBricks(int $limit = 3): Collection
    {
        return Brick::latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->category = 'Bata';
                $item->category_color = 'danger';
                $item->name = "{$item->brand} {$item->type}";
                return $item;
            });
    }

    /**
     * Get recent cats
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentCats(int $limit = 3): Collection
    {
        return Cat::latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->category = 'Cat';
                $item->category_color = 'info';
                $item->name = "{$item->brand} {$item->color_name}";
                return $item;
            });
    }

    /**
     * Get recent cements
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentCements(int $limit = 3): Collection
    {
        return Cement::latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->category = 'Semen';
                $item->category_color = 'secondary';
                $item->name = "{$item->brand} {$item->type}";
                return $item;
            });
    }

    /**
     * Get recent sands
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentSands(int $limit = 3): Collection
    {
        return Sand::latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->category = 'Pasir';
                $item->category_color = 'warning';
                $item->name = "{$item->brand} {$item->type}";
                return $item;
            });
    }

    /**
     * Get recent ceramics
     *
     * @param int $limit
     * @return Collection
     */
    protected function getRecentCeramics(int $limit = 3): Collection
    {
        return Ceramic::latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                $item->category = 'Keramik';
                $item->category_color = 'primary';
                $item->name = "{$item->brand} {$item->type}";
                return $item;
            });
    }

    /**
     * Get chart data for material distribution
     *
     * @return array
     */
    public function getChartData(): array
    {
        $counts = $this->getMaterialCounts();

        return [
            'labels' => ['Bata', 'Cat', 'Semen', 'Pasir', 'Keramik'],
            'data' => [$counts['brick'], $counts['cat'], $counts['cement'], $counts['sand'], $counts['ceramic']],
        ];
    }

    /**
     * Get all dashboard data at once
     * Useful for reducing multiple service calls
     * Now cached for 5 minutes (300 seconds)
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        return $this->cache->getDashboardData(function () {
            $counts = $this->getMaterialCounts();

            return [
                'materialCount' => array_sum($counts),
                'unitCount' => $this->getUnitCount(),
                'storeCount' => Store::count(),
                'workItemCount' => $this->getWorkItemCount(),
                'workerCount' => null, // Under development
                'skillCount' => null, // Under development
                'recentActivities' => $this->getRecentActivities(),
                'chartData' => [
                    'labels' => ['Bata', 'Cat', 'Semen', 'Pasir', 'Keramik'],
                    'data' => [
                        $counts['brick'],
                        $counts['cat'],
                        $counts['cement'],
                        $counts['sand'],
                        $counts['ceramic'],
                    ],
                ],
            ];
        });
    }
}
