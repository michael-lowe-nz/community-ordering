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
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('menu_item_id')->nullable()->after('order_id')->constrained('menu_items')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['name', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('name')->after('order_id');
            $table->decimal('price', 10, 2)->nullable()->after('quantity');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['menu_item_id']);
            $table->dropColumn('menu_item_id');
        });
    }
};
