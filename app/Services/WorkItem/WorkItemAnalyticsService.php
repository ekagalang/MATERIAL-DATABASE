<?php

namespace App\Services\WorkItem;

use App\Models\BrickCalculation;
use App\Repositories\WorkItemRepository;
use App\Services\FormulaRegistry;
use Illuminate\Support\Collection;

/**
 * WorkItem Analytics Service
 *
 * Handles analytics aggregation for work items
 * Extracted from WorkItemController lines 38-107 and 115-207
 */
class WorkItemAnalyticsService
{
    public function __construct(private WorkItemRepository $repository) {}

    /**
     * Generate analytics for all work types
     * Extracted from WorkItemController::index() lines 38-107
     *
     * @return array
     */
    public function generateAnalyticsForAllWorkTypes(): array
    {
        $formulas = FormulaRegistry::all();
        $analytics = [];

        foreach ($formulas as $formula) {
            $workType = $formula['code'];
            $calculations = $this->repository->getCalculationsByWorkType($workType);

            $analytics[$workType] = $this->aggregateAnalytics($calculations);
        }

        return $analytics;
    }

    /**
     * Generate detailed analytics for specific work type
     * Extracted from WorkItemController::analytics() lines 126-204
     *
     * @param string $workType
     * @return array
     */
    public function generateDetailedAnalytics(string $workType): array
    {
        $calculations = $this->repository->getCalculationsByWorkType($workType);
        $totalCalculations = $calculations->count();

        if ($totalCalculations === 0) {
            return [
                'total_calculations' => 0,
                'total_brick_cost' => 0,
                'total_cement_cost' => 0,
                'total_sand_cost' => 0,
                'total_area' => 0,
                'avg_cost_per_m2' => 0,
                'brick_counts' => [],
                'cement_counts' => [],
                'sand_counts' => [],
                'monthly_trends' => [],
                'cost_breakdown_labels' => [],
                'cost_breakdown_data' => [],
                'area_distribution' => [],
            ];
        }

        // Initialize counters
        $brickCounts = [];
        $cementCounts = [];
        $sandCounts = [];
        $totalBrickCost = 0;
        $totalCementCost = 0;
        $totalSandCost = 0;
        $totalArea = 0;
        $monthlyData = [];

        foreach ($calculations as $calc) {
            // Count Bricks (with full object reference)
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

            // Count Cement (with full object reference)
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

            // Count Sand (with full object reference)
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

            // Monthly trend data
            if ($calc->created_at) {
                $monthKey = $calc->created_at->format('Y-m');
                if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                        'count' => 0,
                        'total_cost' => 0,
                        'total_area' => 0,
                    ];
                }
                $monthlyData[$monthKey]['count']++;
                $monthlyData[$monthKey]['total_cost'] +=
                    ($calc->brick_total_cost ?? 0) + ($calc->cement_total_cost ?? 0) + ($calc->sand_total_cost ?? 0);
                $monthlyData[$monthKey]['total_area'] += $calc->wall_area ?? 0;
            }
        }

        // Sort by count descending
        uasort($brickCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($cementCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($sandCounts, fn($a, $b) => $b['count'] <=> $a['count']);

        // Sort monthly data by date
        ksort($monthlyData);

        // Prepare chart data
        $monthlyTrends = [
            'labels' => array_keys($monthlyData),
            'calculations' => array_column($monthlyData, 'count'),
            'costs' => array_column($monthlyData, 'total_cost'),
            'areas' => array_column($monthlyData, 'total_area'),
        ];

        // Cost breakdown for pie chart
        $costBreakdownLabels = ['Bata', 'Semen', 'Pasir'];
        $costBreakdownData = [$totalBrickCost, $totalCementCost, $totalSandCost];

        // Area distribution (top calculations)
        $areaDistribution = $calculations
            ->sortByDesc('wall_area')
            ->take(10)
            ->map(function ($calc) {
                return [
                    'label' => 'Calc #' . $calc->id,
                    'area' => $calc->wall_area ?? 0,
                ];
            })
            ->values()
            ->toArray();

        return [
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
            'monthly_trends' => $monthlyTrends,
            'cost_breakdown_labels' => $costBreakdownLabels,
            'cost_breakdown_data' => $costBreakdownData,
            'area_distribution' => $areaDistribution,
        ];
    }

    /**
     * Aggregate analytics for a collection of calculations (summary version)
     * Used by generateAnalyticsForAllWorkTypes()
     *
     * @param Collection $calculations
     * @return array
     */
    private function aggregateAnalytics(Collection $calculations): array
    {
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

        // Count materials
        $brickCounts = [];
        $cementCounts = [];
        $sandCounts = [];
        $totalCost = 0;
        $totalArea = 0;

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
    }
}
