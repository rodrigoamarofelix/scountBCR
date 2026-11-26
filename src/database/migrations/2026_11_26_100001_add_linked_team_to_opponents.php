<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opponents', function (Blueprint $table) {
            $table->foreignId('linked_team_id')
                ->nullable()
                ->after('short_name')
                ->constrained('teams')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opponents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('linked_team_id');
        });
    }
};