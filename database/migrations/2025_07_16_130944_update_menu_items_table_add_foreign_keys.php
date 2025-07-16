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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('section')->nullable();
            $table->integer('order_index')->nullable();
            $table->boolean('is_available')->default(true);
            
            $table->index(['menu_id', 'section']);
            $table->index(['menu_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropIndex(['menu_id', 'section']);
            $table->dropIndex(['menu_id', 'order_index']);
            $table->dropColumn([
                'menu_id',
                'name',
                'description',
                'price',
                'section',
                'order_index',
                'is_available'
            ]);
        });
    }
};
