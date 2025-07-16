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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Main Menu');
            $table->enum('menu_type', ['scraped', 'manual', 'seasonal'])->default('scraped');
            $table->boolean('is_active')->default(true);
            $table->timestamp('scraped_at')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
