<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Add columns as nullable first so existing rows don't violate the constraint
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('name')->nullable()->after('order_id');
            $table->decimal('price', 10, 2)->nullable()->after('quantity');
        });

        // Backfill name and price from the related menu_items record
        DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->update([
                'order_items.name' => DB::raw('menu_items.name'),
                'order_items.price' => DB::raw('menu_items.price'),
            ]);

        // Now tighten name to non-nullable
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['menu_item_id']);
            $table->dropColumn('menu_item_id');
        });
    }
};
