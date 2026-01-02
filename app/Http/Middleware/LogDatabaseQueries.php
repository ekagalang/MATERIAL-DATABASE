<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log Database Queries Middleware
 *
 * Monitors and logs slow database queries for performance optimization
 * Helps identify queries that need indexing or optimization
 */
class LogDatabaseQueries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enable in local/development environment
        if (!app()->environment('local', 'development')) {
            return $next($request);
        }

        // Start query logging
        DB::enableQueryLog();

        $response = $next($request);

        // Get all executed queries
        $queries = DB::getQueryLog();

        // Define slow query threshold (milliseconds)
        $slowQueryThreshold = 100; // 100ms

        // Log slow queries
        foreach ($queries as $query) {
            $time = $query['time']; // in milliseconds

            if ($time > $slowQueryThreshold) {
                Log::warning('Slow Query Detected', [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $time . 'ms',
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
            }
        }

        // Log total query count and time for the request
        $totalTime = collect($queries)->sum('time');
        $queryCount = count($queries);

        if ($queryCount > 10) {
            // Log if there are too many queries (potential N+1 issue)
            Log::info('High Query Count', [
                'query_count' => $queryCount,
                'total_time' => $totalTime . 'ms',
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        }

        return $response;
    }
}
