<?php

namespace App\Console\Commands;

use Database\Seeders\FictionalRestaurantSeeder;
use Illuminate\Console\Command;

class SeedFictionalRestaurants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:fictional-restaurants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with fictional restaurants and menus for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding fictional restaurants...');
        
        $seeder = new FictionalRestaurantSeeder();
        $seeder->setContainer($this->laravel);
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Fictional restaurants seeded successfully!');
        
        return Command::SUCCESS;
    }
}