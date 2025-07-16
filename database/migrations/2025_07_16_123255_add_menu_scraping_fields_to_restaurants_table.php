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
            $table->string('menu_url')->nullable()->after('website');
            $table->boolean('menu_scraping_enabled')->default(false)->after('menu_url');
            $table->timestamp('last_menu_scrape')->nullable()->after('menu_scraping_enabled');
            $table->enum('menu_scrape_frequency', ['daily', 'weekly', 'monthly'])->nullable()->after('last_menu_scrape');
            $table->text('scraping_notes')->nullable()->after('menu_scrape_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
    }
};
