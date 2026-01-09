<?php

namespace App\Providers;

use App\Services\MenuParsingService;
use App\Services\MenuParsingServiceInterface;
use App\Services\MenuScrapingService;
use App\Services\MenuScrapingServiceInterface;
use App\Services\MenuStorageService;
use App\Services\MenuStorageServiceInterface;
use App\Services\ScrapingLogService;
use App\Services\ScrapingLogServiceInterface;
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
        $this->app->bind(MenuStorageServiceInterface::class, MenuStorageService::class);
        $this->app->bind(MenuParsingServiceInterface::class, MenuParsingService::class);
        $this->app->bind(ScrapingLogServiceInterface::class, ScrapingLogService::class);
        $this->app->bind(MenuScrapingServiceInterface::class, MenuScrapingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}