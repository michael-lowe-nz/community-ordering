<?php

namespace App\Providers;

use App\Services\WebScrapingService;
use App\Services\WebScrapingServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WebScrapingServiceInterface::class, WebScrapingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}