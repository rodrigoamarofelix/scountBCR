<?php

namespace App\Services;

use App\Enums\ShotResult;
use App\Enums\ShotType;
use App\Models\Athlete;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShotStatisticsService
{
    public function queryForContext(?GameMatch $match = null, ?Athlete $athlete = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): Builder
    {
        $query = \App\Models\ShotAttempt::query();

        if ($match) {
            $query->where('match_id', $match->id);
        }

        if ($athlete) {
            $query->where('athlete_id', $athlete->id);
        }

        if ($from) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('occurred_at', '<=', $to);
        }

        return $query;
    }

    public function summarize(Builder $query): array
    {
        $attempts = (clone $query)->get();

        return [
            'two_point' => $this->summarizeType($attempts, ShotType::TwoPoint),
            'three_point' => $this->summarizeType($attempts, ShotType::ThreePoint),
            'free_throw' => $this->summarizeType($attempts, ShotType::FreeThrow),
            'overall' => $this->summarizeOverall($attempts),
        ];
    }

  private function summarizeType(Collection $attempts, ShotType $type): array
    {
        $subset = $attempts->where('shot_type', $type);
        $made = $subset->where('result', ShotResult::Made)->count();
        $missed = $subset->where('result', ShotResult::Missed)->count();
        $total = $made + $missed;

        return [
            'attempts' => $total,
            'made' => $made,
            'missed' => $missed,
            'points' => $made * $type->pointsValue(),
            'percentage' => $this->percentage($made, $total),
        ];
    }

    private function summarizeOverall(Collection $attempts): array
    {
        $made = $attempts->where('result', ShotResult::Made)->count();
        $total = $attempts->count();
        $points = $attempts
            ->where('result', ShotResult::Made)
            ->sum(fn ($row) => $row->shot_type->pointsValue());

        return [
            'attempts' => $total,
            'made' => $made,
            'missed' => $total - $made,
            'points' => $points,
            'percentage' => $this->percentage($made, $total),
        ];
    }

    public function percentage(int $made, int $attempts): ?float
    {
        if ($attempts === 0) {
            return null;
        }

        return round(($made / $attempts) * 100, 1);
    }
}
