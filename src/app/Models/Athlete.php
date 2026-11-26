<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id', 'full_name', 'cpf', 'sport_name', 'jersey_number', 'functional_classification',
        'photo_path', 'birth_date', 'height_cm', 'weight_kg', 'position', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'functional_classification' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function shotAttempts(): HasMany
    {
        return $this->hasMany(ShotAttempt::class);
    }

    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(GameMatch::class, 'match_athlete')
            ->withPivot('is_starter')
            ->withTimestamps();
    }
}
