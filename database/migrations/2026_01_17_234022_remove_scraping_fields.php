<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'menu_url',
                'menu_scraping_enabled',
                'last_menu_scrape',
                'menu_scrape_frequency',
                'scraping_notes'
            ]);
        });

        Schema::dropIfExists('scraping_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
