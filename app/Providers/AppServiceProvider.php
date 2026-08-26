<?php

namespace App\Providers;

use App\Services\OrderGuidanceCalculator;
use App\Services\SimpleAverageOrderGuidanceCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(OrderGuidanceCalculator::class, SimpleAverageOrderGuidanceCalculator::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
