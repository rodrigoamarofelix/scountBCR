<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->date('match_date');
            $table->time('match_time')->nullable();
            $table->string('location')->nullable();
            $table->string('phase')->nullable();
            $table->string('match_type')->nullable();
            $table->unsignedSmallInteger('team_score')->default(0);
            $table->unsignedSmallInteger('opponent_score')->default(0);
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'match_date']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
