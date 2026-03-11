<?php

namespace App\Providers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Ceramic;
use App\Models\Nat;
use App\Models\Sand;
use App\Models\Store;
use App\Models\Unit;
use App\Observers\BrickCalculationObserver;
use App\Observers\DashboardCountObserver;
use App\Observers\MaterialObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\NumberHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Register Material Observers for auto cache invalidation
        Brick::observe(MaterialObserver::class);
        Cement::observe(MaterialObserver::class);
        Sand::observe(MaterialObserver::class);
        Cat::observe(MaterialObserver::class);
        Ceramic::observe(MaterialObserver::class);
        Nat::observe(MaterialObserver::class);

        // Register observers for dashboard count cards
        Store::observe(DashboardCountObserver::class);
        Unit::observe(DashboardCountObserver::class);

        // Register BrickCalculation Observer for analytics cache invalidation
        BrickCalculation::observe(BrickCalculationObserver::class);

        // Register custom Blade directive for number formatting
        Blade::directive('format', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::format($expression); ?>";
        });

        // Alias for @format
        Blade::directive('number', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::format($expression); ?>";
        });

        // Formatter for calculation results (scrollable extra decimals)
        Blade::directive('formatResult', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::formatResult($expression); ?>";
        });

        // Alias for @formatResult
        Blade::directive('numberResult', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::formatResult($expression); ?>";
        });

        // Currency formatter (Rp prefix, no decimals)
        Blade::directive('currency', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::currency($expression); ?>";
        });

        // Price formatter (no prefix, no decimals)
        Blade::directive('price', function ($expression) {
            return "<?php echo \App\Helpers\NumberHelper::formatFixed($expression, 0); ?>";
        });
    }
}
