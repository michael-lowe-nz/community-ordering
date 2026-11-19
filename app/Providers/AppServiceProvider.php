<?php

namespace App\Providers;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->checkDatabaseOnHealthCheck();
    }

    /**
     * Make the /up health route fail when the database can't be reached, so a
     * deploy with a broken database connection doesn't report healthy.
     */
    private function checkDatabaseOnHealthCheck(): void
    {
        Event::listen(DiagnosingHealth::class, function (): void {
            DB::connection()->select('select 1');
        });
    }
}
