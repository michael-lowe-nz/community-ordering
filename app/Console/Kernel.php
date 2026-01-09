<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\MenuScrapingJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule daily scraping job
        $schedule->job(new MenuScrapingJob(null, 'daily'))
                ->daily()
                ->name('menu-scraping-daily')
                ->withoutOverlapping();

        // Schedule weekly scraping job
        $schedule->job(new MenuScrapingJob(null, 'weekly'))
                ->weekly()
                ->name('menu-scraping-weekly')
                ->withoutOverlapping();

        // Schedule monthly scraping job
        $schedule->job(new MenuScrapingJob(null, 'monthly'))
                ->monthly()
                ->name('menu-scraping-monthly')
                ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}