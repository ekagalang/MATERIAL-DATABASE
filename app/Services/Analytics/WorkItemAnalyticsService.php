<?php

namespace App\Services\Analytics;

use App\Models\BrickCalculation;
use App\Services\Cache\CacheService;
use Illuminate\Support\Collection;

/**
 * Work Item Analytics Service
 *
 * Handle analytics calculations for work items/formulas
 * Now with caching for expensive aggregations (30 min cache)
 */
class WorkItemAnalyticsService
{
    protected CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }
    /**
     * Generate analytics for all formulas
     *
     * @param array $formulas Array of formula definitions from FormulaRegistry
     * @return array Analytics data grouped by work_type
     */
    public function generateAllAnalytics(array $formulas): array
    {
        $analytics = [];

        foreach ($formulas as $formula) {
            $workType = $formula['code'];
            $analytics[$workType] = $this->generateAnalyticsForWorkType($workType);
        }

        return $analytics;
    }

    /**
     * Generate analytics for a specific work type
     * Cached for 30 minutes
     *
     * @param string $workType The work type code
     * @return array Analytics data including totals, averages, and top materials
     */
    public function generateAnalyticsForWorkType(string $workType): array
    {
        return $this->cache->getAnalytics($workType, 'summary', function () use ($workType) {
            // Get all calculations for this work_type with eager loading to prevent N+1 queries
            $calculations = BrickCalculation::where('calculation_params->work_type', $workType)
                ->with(['brick', 'cement', 'sand'])
                ->get();

        $totalCalculations = $calculations->count();

        if ($totalCalculations === 0) {
            return [
                'total' => 0,
                'avg_cost_per_m2' => 0,
                'total_area' => 0,
                'top_bricks' => [],
                'top_cements' => [],
                'top_sands' => [],
            ];
        }

        // Initialize counters
        $brickCounts = [];
        $cementCounts = [];
        $sandCounts = [];
        $totalCost = 0;
        $totalArea = 0;

        // Calculate aggregations
        foreach ($calculations as $calc) {
            // Count Bricks
            if ($calc->brick) {
                $brickKey = $calc->brick->brand;
                $brickCounts[$brickKey] = ($brickCounts[$brickKey] ?? 0) + 1;
            }

            // Count Cement
            if ($calc->cement) {
                $cementKey = $calc->cement->brand;
                $cementCounts[$cementKey] = ($cementCounts[$cementKey] ?? 0) + 1;
            }

            // Count Sand
            if ($calc->sand) {
                $sandKey = $calc->sand->brand;
                $sandCounts[$sandKey] = ($sandCounts[$sandKey] ?? 0) + 1;
            }

            // Sum total cost and area
            $totalCost += $calc->total_material_cost ?? 0;
            $totalArea += $calc->wall_area ?? 0;
        }

        // Sort and get top 3
        arsort($brickCounts);
        arsort($cementCounts);
        arsort($sandCounts);

        // Calculate average cost per M2
        $avgCostPerM2 = $totalArea > 0 ? $totalCost / $totalArea : 0;

            return [
                'total' => $totalCalculations,
                'avg_cost_per_m2' => $avgCostPerM2,
                'total_area' => $totalArea,
                'top_bricks' => array_slice($brickCounts, 0, 3, true),
                'top_cements' => array_slice($cementCounts, 0, 3, true),
                'top_sands' => array_slice($sandCounts, 0, 3, true),
            ];
        });
    }

    /**
     * Generate detailed analytics for a specific work type
     * Includes full material objects and detailed cost breakdown
     * Cached for 30 minutes
     *
     * @param string $workType The work type code
     * @return array Detailed analytics including calculations collection and material stats
     */
    public function generateDetailedAnalytics(string $workType): array
    {
        return $this->cache->getAnalytics($workType, 'detailed', function () use ($workType) {
            // Get all calculations for this work_type with relationships
            $calculations = BrickCalculation::where('calculation_params->work_type', $workType)
                ->with(['brick', 'cement', 'sand'])
                ->orderBy('created_at', 'desc')
                ->get();

        $totalCalculations = $calculations->count();

        // Initialize counters with full material objects
        $brickCounts = [];
        $cementCounts = [];
        $sandCounts = [];

        // Detailed cost stats
        $totalBrickCost = 0;
        $totalCementCost = 0;
        $totalSandCost = 0;
        $totalArea = 0;

        foreach ($calculations as $calc) {
            // Count Bricks with full object
            if ($calc->brick) {
                $brickKey = $calc->brick->brand;
                if (!isset($brickCounts[$brickKey])) {
                    $brickCounts[$brickKey] = [
                        'count' => 0,
                        'brick' => $calc->brick,
                    ];
                }
                $brickCounts[$brickKey]['count']++;
            }

            // Count Cement with full object
            if ($calc->cement) {
                $cementKey = $calc->cement->brand;
                if (!isset($cementCounts[$cementKey])) {
                    $cementCounts[$cementKey] = [
                        'count' => 0,
                        'cement' => $calc->cement,
                    ];
                }
                $cementCounts[$cementKey]['count']++;
            }

            // Count Sand with full object
            if ($calc->sand) {
                $sandKey = $calc->sand->brand;
                if (!isset($sandCounts[$sandKey])) {
                    $sandCounts[$sandKey] = [
                        'count' => 0,
                        'sand' => $calc->sand,
                    ];
                }
                $sandCounts[$sandKey]['count']++;
            }

            // Sum costs and area
            $totalBrickCost += $calc->brick_total_cost ?? 0;
            $totalCementCost += $calc->cement_total_cost ?? 0;
            $totalSandCost += $calc->sand_total_cost ?? 0;
            $totalArea += $calc->wall_area ?? 0;
        }

        // Sort by count descending
        uasort($brickCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($cementCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($sandCounts, fn($a, $b) => $b['count'] <=> $a['count']);

            return [
                'calculations' => $calculations,
                'total_calculations' => $totalCalculations,
                'total_brick_cost' => $totalBrickCost,
                'total_cement_cost' => $totalCementCost,
                'total_sand_cost' => $totalSandCost,
                'total_area' => $totalArea,
                'avg_cost_per_m2' =>
                    $totalArea > 0 ? ($totalBrickCost + $totalCementCost + $totalSandCost) / $totalArea : 0,
                'brick_counts' => $brickCounts,
                'cement_counts' => $cementCounts,
                'sand_counts' => $sandCounts,
            ];
        });
    }
}
