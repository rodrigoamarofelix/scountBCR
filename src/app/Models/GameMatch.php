<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'team_id', 'opponent_id', 'competition_id', 'match_date', 'match_time', 'location',
        'phase', 'match_type', 'team_score', 'opponent_score', 'status', 'notes',
        'scout_period_length', 'scout_clock_remaining', 'scout_clock_running_at',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
            'status' => MatchStatus::class,
            'scout_clock_running_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(Opponent::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'match_athlete', 'match_id', 'athlete_id')
            ->withPivot('is_starter')
            ->withTimestamps();
    }

    public function shotAttempts(): HasMany
    {
        return $this->hasMany(ShotAttempt::class, 'match_id');
    }
}