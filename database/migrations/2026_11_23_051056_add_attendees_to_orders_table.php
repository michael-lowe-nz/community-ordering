<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who was at an order, and any remarks about them. Only shown to signed-in
 * users, so names stay off the public pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('attendees')->nullable();
            $table->text('attendee_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['attendees', 'attendee_notes']);
        });
    }
};
