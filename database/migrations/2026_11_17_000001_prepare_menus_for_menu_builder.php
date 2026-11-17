<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shapes the menu tables for the menu builder.
 *
 * Runs after 2026_08_28_102813, which made order lines reference menu items
 * (menu_item_id) instead of storing a name.
 *
 * - Menu items gain the metadata the builder's chips need: filter tags,
 *   variants (e.g. "Pork" / "Fish Fillet") and a unit with its step and
 *   first quantity (e.g. half a whole duck, 0.5 kg of char siu, 5 dumplings).
 * - Order lines can now hold fractional quantities, a variant and a stable
 *   position.
 * - The one-row-per-menu-item constraint on menu_item_order is dropped, since
 *   the same dish can now appear once per variant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->json('tags')->nullable();
            $table->json('variants')->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_step', 8, 2)->nullable();
            $table->decimal('unit_start', 8, 2)->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('quantity', 8, 2)->default(1)->change();
            $table->string('variant', 100)->nullable();
            $table->unsignedInteger('position')->default(0);
        });

        Schema::table('menu_item_order', function (Blueprint $table) {
            $table->dropUnique(['menu_item_id', 'order_id']);
            $table->decimal('quantity', 8, 2)->default(1)->change();
            $table->string('variant', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('menu_item_order', function (Blueprint $table) {
            $table->dropColumn('variant');
            $table->integer('quantity')->default(1)->change();
            $table->unique(['menu_item_id', 'order_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant', 'position']);
            $table->integer('quantity')->default(1)->change();
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['tags', 'variants', 'unit', 'unit_step', 'unit_start']);
        });
    }
};
