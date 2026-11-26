<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('scout_period_length')->default(600)->after('opponent_score');
            $table->unsignedSmallInteger('scout_clock_remaining')->nullable()->after('scout_period_length');
            $table->timestamp('scout_clock_running_at')->nullable()->after('scout_clock_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['scout_period_length', 'scout_clock_remaining', 'scout_clock_running_at']);
        });
    }
};