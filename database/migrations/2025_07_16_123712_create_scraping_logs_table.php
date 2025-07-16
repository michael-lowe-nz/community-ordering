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
        Schema::create('scraping_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->enum('scraping_type', ['manual', 'scheduled'])->default('manual');
            $table->enum('status', ['success', 'failed', 'partial'])->default('failed');
            $table->integer('items_found')->default(0);
            $table->integer('items_created')->default(0);
            $table->integer('items_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('scraped_at');
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'scraped_at']);
            $table->index(['status', 'scraped_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_logs');
    }
};
