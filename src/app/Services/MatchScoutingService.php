<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\ShotAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchScoutingService
{
    public function __construct(
        protected ShotStatisticsService $statistics
    ) {}

    public function ensureAthleteOnMatch(GameMatch $match, Athlete $athlete): void
    {
        if (! $match->athletes()->where('athletes.id', $athlete->id)->exists()) {
            throw ValidationException::withMessages([
                'athlete' => 'Atleta não está convocado nesta partida.',
            ]);
        }

        if ($athlete->team_id !== $match->team_id) {
            throw ValidationException::withMessages([
                'athlete' => 'Atleta não pertence à equipe desta partida.',
            ]);
        }
    }

    public function record(
        GameMatch $match,
        Athlete $athlete,
        ShotType $shotType,
        ShotResult $result,
        int $period,
        User $recorder,
        ?float $courtX = null,
        ?float $courtY = null,
    ): ShotAttempt {
        $this->ensureAthleteOnMatch($match, $athlete);

        return DB::transaction(function () use ($match, $athlete, $shotType, $result, $period, $recorder, $courtX, $courtY) {
            if ($match->status === MatchStatus::Scheduled) {
                $match->update(['status' => MatchStatus::InProgress]);
            }

            $attempt = ShotAttempt::create([
                'match_id' => $match->id,
                'athlete_id' => $athlete->id,
                'shot_type' => $shotType,
                'result' => $result,
                'period' => $period,
                'court_x' => $courtX,
                'court_y' => $courtY,
                'recorded_by' => $recorder->id,
                'occurred_at' => now(),
            ]);

            if ($result === ShotResult::Made) {
                $match->increment('team_score', $shotType->pointsValue());
            }

            return $attempt;
        });
    }

    public function undoLast(GameMatch $match): ?ShotAttempt
    {
        $attempt = ShotAttempt::query()
            ->where('match_id', $match->id)
            ->latest('id')
            ->first();

        if (! $attempt) {
            return null;
        }

        return DB::transaction(function () use ($match, $attempt) {
            if ($attempt->result === ShotResult::Made) {
                $match->decrement('team_score', $attempt->shot_type->pointsValue());
            }

            $attempt->delete();

            return $attempt;
        });
    }

    public function deleteAttempt(ShotAttempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            $match = $attempt->match;

            if ($attempt->result === ShotResult::Made) {
                $match->decrement('team_score', $attempt->shot_type->pointsValue());
            }

            $attempt->delete();
        });
    }

    public function statsForAthlete(GameMatch $match, Athlete $athlete): array
    {
        $query = $this->statistics->queryForContext($match, $athlete);

        return $this->statistics->summarize($query);
    }

    public function statsForMatch(GameMatch $match): array
    {
        $query = $this->statistics->queryForContext($match);

        return $this->statistics->summarize($query);
    }
}
