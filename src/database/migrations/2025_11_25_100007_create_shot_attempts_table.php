<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shot_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained()->cascadeOnDelete();
            $table->string('shot_type');
            $table->string('result');
            $table->unsignedTinyInteger('period')->default(1);
            $table->decimal('court_x', 5, 2)->nullable()->comment('Coordenada X na quadra (shot chart)');
            $table->decimal('court_y', 5, 2)->nullable()->comment('Coordenada Y na quadra (shot chart)');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['match_id', 'athlete_id']);
            $table->index(['match_id', 'shot_type']);
            $table->index(['athlete_id', 'shot_type', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_attempts');
    }
};
