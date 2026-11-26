<?php

namespace App\Models;

use App\Enums\ShotResult;
use App\Enums\ShotType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShotAttempt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'match_id', 'athlete_id', 'shot_type', 'result', 'period', 'court_x', 'court_y',
        'recorded_by', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'shot_type' => ShotType::class,
            'result' => ShotResult::class,
            'occurred_at' => 'datetime',
            'court_x' => 'decimal:2',
            'court_y' => 'decimal:2',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
