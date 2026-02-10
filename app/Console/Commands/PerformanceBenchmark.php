<?php

namespace App\Console\Commands;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;
use App\Services\Analytics\WorkItemAnalyticsService;
use App\Services\Cache\CacheService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceBenchmark extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:benchmark
                            {--iterations=5 : Number of iterations for each test}
                            {--clear-cache : Clear cache before running benchmarks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run performance benchmarks to measure optimization improvements';

    protected DashboardService $dashboardService;

    protected WorkItemAnalyticsService $analyticsService;

    protected CacheService $cacheService;

    public function __construct(
        DashboardService $dashboardService,
        WorkItemAnalyticsService $analyticsService,
        CacheService $cacheService,
    ) {
        parent::__construct();
        $this->dashboardService = $dashboardService;
        $this->analyticsService = $analyticsService;
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $iterations = (int) $this->option('iterations');

        $this->info('');
        $this->info('===========================================');
        $this->info('  PERFORMANCE BENCHMARK REPORT');
        $this->info('===========================================');
        $this->info('Date: '.now()->format('Y-m-d H:i:s'));
        $this->info('Iterations: '.$iterations);
        $this->info('');

        if ($this->option('clear-cache')) {
            $this->warn('Clearing all cache...');
            Cache::flush();
            $this->info('Cache cleared!');
            $this->info('');
        }

        // Run all benchmarks
        $results = [];

        $results['database'] = $this->benchmarkDatabaseQueries($iterations);
        $results['cache'] = $this->benchmarkCachePerformance($iterations);
        $results['dashboard'] = $this->benchmarkDashboard($iterations);
        $results['analytics'] = $this->benchmarkAnalytics($iterations);

        // Display summary
        $this->displaySummary($results);

        return Command::SUCCESS;
    }

    /**
     * Benchmark database queries with indexes
     */
    protected function benchmarkDatabaseQueries(int $iterations): array
    {
        $this->info('');
        $this->info('-------------------------------------------');
        $this->info('1. DATABASE QUERY PERFORMANCE');
        $this->info('-------------------------------------------');

        $results = [];

        // Test 1: Simple material queries
        $this->line('Testing: Material queries with indexes...');
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            Brick::where('type', 'Bata Merah')->get();
            Cement::where('brand', 'Semen Gresik')->get();
            Sand::where('type', 'Pasir Halus')->get();
            Cat::where('type', 'Cat Tembok')->get();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['material_queries'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['material_queries']['avg']}ms | Min: {$results['material_queries']['min']}ms | Max: {$results['material_queries']['max']}ms",
        );

        // Test 2: Complex queries with joins (eager loading)
        $this->line('Testing: Complex queries with eager loading...');
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            BrickCalculation::with(['brick', 'cement', 'sand'])
                ->limit(20)
                ->get();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['eager_loading'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['eager_loading']['avg']}ms | Min: {$results['eager_loading']['min']}ms | Max: {$results['eager_loading']['max']}ms",
        );

        // Test 3: JSON virtual column query
        $this->line('Testing: JSON virtual column queries...');
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            DB::table('brick_calculations')
                ->whereRaw('work_type_virtual = ?', ['PASANG_BATA'])
                ->limit(20)
                ->get();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['json_virtual_column'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['json_virtual_column']['avg']}ms | Min: {$results['json_virtual_column']['min']}ms | Max: {$results['json_virtual_column']['max']}ms",
        );

        return $results;
    }

    /**
     * Benchmark cache performance
     */
    protected function benchmarkCachePerformance(int $iterations): array
    {
        $this->info('');
        $this->info('-------------------------------------------');
        $this->info('2. CACHE PERFORMANCE');
        $this->info('-------------------------------------------');

        $results = [];

        // Test 1: Cold cache (first load)
        $this->line('Testing: Cold cache (no cache)...');
        Cache::flush();
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush();
            $start = microtime(true);
            $this->dashboardService->getDashboardData();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['cold_cache'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['cold_cache']['avg']}ms | Min: {$results['cold_cache']['min']}ms | Max: {$results['cold_cache']['max']}ms",
        );

        // Test 2: Warm cache (cached data)
        $this->line('Testing: Warm cache (cached data)...');
        Cache::flush();
        $this->dashboardService->getDashboardData(); // Prime the cache
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $this->dashboardService->getDashboardData();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['warm_cache'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['warm_cache']['avg']}ms | Min: {$results['warm_cache']['min']}ms | Max: {$results['warm_cache']['max']}ms",
        );

        // Calculate cache improvement
        $improvement = round(
            (($results['cold_cache']['avg'] - $results['warm_cache']['avg']) / $results['cold_cache']['avg']) * 100,
            1,
        );
        $this->comment("  Cache Improvement: {$improvement}% faster with warm cache");

        return $results;
    }

    /**
     * Benchmark dashboard data generation
     */
    protected function benchmarkDashboard(int $iterations): array
    {
        $this->info('');
        $this->info('-------------------------------------------');
        $this->info('3. DASHBOARD PERFORMANCE');
        $this->info('-------------------------------------------');

        $results = [];

        $this->line('Testing: Dashboard data generation...');
        Cache::flush();
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            if ($i > 0) {
                Cache::flush(); // Clear cache between iterations
            }
            $start = microtime(true);
            $this->dashboardService->getDashboardData();
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['dashboard_generation'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['dashboard_generation']['avg']}ms | Min: {$results['dashboard_generation']['min']}ms | Max: {$results['dashboard_generation']['max']}ms",
        );

        return $results;
    }

    /**
     * Benchmark analytics generation
     */
    protected function benchmarkAnalytics(int $iterations): array
    {
        $this->info('');
        $this->info('-------------------------------------------');
        $this->info('4. ANALYTICS PERFORMANCE');
        $this->info('-------------------------------------------');

        $results = [];

        // Check if we have data
        $hasData = BrickCalculation::exists();
        if (! $hasData) {
            $this->warn('  No calculation data found - skipping analytics benchmark');

            return [];
        }

        // Test 1: Analytics summary (cold cache)
        $this->line('Testing: Analytics summary generation...');
        Cache::flush();
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            if ($i > 0) {
                Cache::flush();
            }
            $start = microtime(true);
            $this->analyticsService->generateAnalyticsForWorkType('PASANG_BATA');
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['analytics_summary'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['analytics_summary']['avg']}ms | Min: {$results['analytics_summary']['min']}ms | Max: {$results['analytics_summary']['max']}ms",
        );

        // Test 2: Analytics detail (cold cache)
        $this->line('Testing: Analytics detail generation...');
        Cache::flush();
        $times = [];
        for ($i = 0; $i < $iterations; $i++) {
            if ($i > 0) {
                Cache::flush();
            }
            $start = microtime(true);
            $this->analyticsService->generateDetailedAnalytics('PASANG_BATA');
            $times[] = (microtime(true) - $start) * 1000;
        }
        $results['analytics_detail'] = [
            'avg' => round(array_sum($times) / count($times), 2),
            'min' => round(min($times), 2),
            'max' => round(max($times), 2),
        ];
        $this->info(
            "  Avg: {$results['analytics_detail']['avg']}ms | Min: {$results['analytics_detail']['min']}ms | Max: {$results['analytics_detail']['max']}ms",
        );

        return $results;
    }

    /**
     * Display summary of all benchmarks
     */
    protected function displaySummary(array $results): void
    {
        $this->info('');
        $this->info('===========================================');
        $this->info('  SUMMARY & RECOMMENDATIONS');
        $this->info('===========================================');

        // Database performance
        if (isset($results['database']['material_queries'])) {
            $this->info('');
            $this->comment('Database Optimization:');
            $this->line("  - Material queries: {$results['database']['material_queries']['avg']}ms avg");
            $this->line("  - Eager loading: {$results['database']['eager_loading']['avg']}ms avg");
            $this->line("  - JSON virtual column: {$results['database']['json_virtual_column']['avg']}ms avg");

            if ($results['database']['material_queries']['avg'] < 50) {
                $this->info('  Status: EXCELLENT - Queries are very fast!');
            } elseif ($results['database']['material_queries']['avg'] < 100) {
                $this->info('  Status: GOOD - Queries are performing well');
            } else {
                $this->warn('  Status: NEEDS IMPROVEMENT - Consider adding more indexes');
            }
        }

        // Cache performance
        if (isset($results['cache']['cold_cache'])) {
            $this->info('');
            $this->comment('Cache Optimization:');
            $this->line("  - Cold cache: {$results['cache']['cold_cache']['avg']}ms avg");
            $this->line("  - Warm cache: {$results['cache']['warm_cache']['avg']}ms avg");

            $improvement = round(
                (($results['cache']['cold_cache']['avg'] - $results['cache']['warm_cache']['avg']) /
                    $results['cache']['cold_cache']['avg']) *
                    100,
                1,
            );
            $this->info("  Improvement: {$improvement}% faster with cache");

            if ($improvement > 80) {
                $this->info('  Status: EXCELLENT - Cache is highly effective!');
            } elseif ($improvement > 60) {
                $this->info('  Status: GOOD - Cache provides significant benefit');
            } else {
                $this->warn('  Status: Cache improvement is modest - consider longer TTL');
            }
        }

        // Overall assessment
        $this->info('');
        $this->comment('Overall Performance Status:');
        $avgDbTime = isset($results['database']['material_queries'])
            ? $results['database']['material_queries']['avg']
            : 0;
        $avgCacheTime = isset($results['cache']['warm_cache']) ? $results['cache']['warm_cache']['avg'] : 0;

        if ($avgDbTime < 50 && $avgCacheTime < 5) {
            $this->info('  Status: OPTIMAL - Application is performing excellently!');
        } elseif ($avgDbTime < 100 && $avgCacheTime < 10) {
            $this->info('  Status: GOOD - Application performance is solid');
        } else {
            $this->warn('  Status: ACCEPTABLE - There is room for optimization');
        }

        $this->info('');
        $this->info('===========================================');
        $this->info('');
    }
}
