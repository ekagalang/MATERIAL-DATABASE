<?php

namespace App\Providers;

use App\Models\Brick;
use App\Models\BrickCalculation;
use App\Models\Cat;
use App\Models\Cement;
use App\Models\Sand;
use App\Observers\BrickCalculationObserver;
use App\Observers\MaterialObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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

        // Register BrickCalculation Observer for analytics cache invalidation
        BrickCalculation::observe(BrickCalculationObserver::class);
    }
}
