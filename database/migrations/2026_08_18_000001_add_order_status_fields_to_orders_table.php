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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('verdict', ['pending', 'not_enough', 'just_right', 'too_much'])
                ->default('pending')
                ->after('participant_count');
            $table->dateTime('verdict_captured_at')->nullable()->after('verdict');
            $table->text('notes')->nullable()->after('verdict_captured_at');
            $table->dateTime('ordered_at')->nullable()->after('notes');
        });

        DB::table('orders')
            ->whereNull('ordered_at')
            ->update(['ordered_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['verdict', 'verdict_captured_at', 'notes', 'ordered_at']);
        });
    }
};
