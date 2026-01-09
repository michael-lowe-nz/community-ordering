<?php

namespace App\Providers;

use App\Services\MenuContentValidationService;
use App\Services\MenuParsingService;
use App\Services\MenuParsingServiceInterface;
use App\Services\MenuScrapingService;
use App\Services\MenuScrapingServiceInterface;
use App\Services\MenuStorageService;
use App\Services\MenuStorageServiceInterface;
use App\Services\PdfParsingService;
use App\Services\ScrapingLogService;
use App\Services\ScrapingLogServiceInterface;
use App\Services\WebScrapingService;
use App\Services\WebScrapingServiceInterface;
use Illuminate\Support\ServiceProvider;

class MenuScrapingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register interfaces to implementations
        $this->app->bind(WebScrapingServiceInterface::class, WebScrapingService::class);
        $this->app->bind(MenuParsingServiceInterface::class, MenuParsingService::class);
        $this->app->bind(MenuStorageServiceInterface::class, MenuStorageService::class);
        $this->app->bind(ScrapingLogServiceInterface::class, ScrapingLogService::class);
        
        // Register concrete classes
        $this->app->singleton(PdfParsingService::class);
        $this->app->singleton(MenuContentValidationService::class);
        
        // Register the main service with all dependencies
        $this->app->bind(MenuScrapingServiceInterface::class, function ($app) {
            return new MenuScrapingService(
                $app->make(WebScrapingServiceInterface::class),
                $app->make(MenuParsingServiceInterface::class),
                $app->make(MenuStorageServiceInterface::class),
                $app->make(ScrapingLogServiceInterface::class),
                $app->make(MenuContentValidationService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}