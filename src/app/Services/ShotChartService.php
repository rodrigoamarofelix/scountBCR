<?php

namespace App\Services;

use App\Enums\ShotResult;
use App\Models\GameMatch;
use App\Models\ShotAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShotChartService
{
    /** Cesta ofensiva: centro inferior da meia quadra (0–100). */
    public const BASKET_X = 50.0;

    public const BASKET_Y = 92.0;

    public function query(array $filters = []): Builder
    {
        $query = ShotAttempt::query()->with(['athlete', 'match']);

        if (! empty($filters['match_id'])) {
            $query->where('match_id', (int) $filters['match_id']);
        }

        if (! empty($filters['athlete_id'])) {
            $query->where('athlete_id', (int) $filters['athlete_id']);
        }

        if (! empty($filters['team_id'])) {
            $teamId = (int) $filters['team_id'];
            $query->whereHas('athlete', fn (Builder $q) => $q->where('team_id', $teamId));
        }

        if (! empty($filters['period'])) {
            $query->where('period', (int) $filters['period']);
        }

        return $query;
    }

    public function scatterPoints(array $filters = []): array
    {
        return $this->query($filters)
            ->whereNotNull('court_x')
            ->whereNotNull('court_y')
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (ShotAttempt $s) => [
                'x' => (float) $s->court_x,
                'y' => (float) $s->court_y,
                'made' => $s->result === ShotResult::Made,
                'type' => $s->shot_type->value,
                'athlete' => '#'.($s->athlete?->jersey_number ?? '—'),
                'label' => ($s->athlete?->sport_name ?? $s->athlete?->full_name ?? '—').' · '.$s->shot_type->label(),
            ])
            ->values()
            ->all();
    }

    public function zoneSummary(array $filters = []): array
    {
        $attempts = $this->query($filters)
            ->whereNotNull('court_x')
            ->whereNotNull('court_y')
            ->get();

        $zones = [
            'raio' => ['label' => 'Raio / garrafão', 'made' => 0, 'missed' => 0],
            'media' => ['label' => 'Média distância', 'made' => 0, 'missed' => 0],
            'tres' => ['label' => '3 pontos', 'made' => 0, 'missed' => 0],
        ];

        foreach ($attempts as $shot) {
            $zone = $this->zoneForPoint((float) $shot->court_x, (float) $shot->court_y);
            $key = $shot->result === ShotResult::Made ? 'made' : 'missed';
            $zones[$zone][$key]++;
        }

        $out = [];
        foreach ($zones as $key => $row) {
            $total = $row['made'] + $row['missed'];
            $out[] = [
                'key' => $key,
                'label' => $row['label'],
                'made' => $row['made'],
                'missed' => $row['missed'],
                'attempts' => $total,
                'percentage' => $total > 0 ? round(($row['made'] / $total) * 100, 1) : null,
            ];
        }

        return $out;
    }

    public function chartZonesFg(array $filters): array
    {
        $zones = $this->zoneSummary($filters);

        return [
            'labels' => array_column($zones, 'label'),
            'datasets' => [
                [
                    'label' => 'Aproveitamento (%)',
                    'data' => array_map(fn ($z) => $z['percentage'] ?? 0, $zones),
                    'backgroundColor' => ['#3b82f6', '#f59e0b', '#22c55e'],
                ],
            ],
        ];
    }

    public function hasCoordinates(array $filters = []): bool
    {
        return $this->query($filters)
            ->whereNotNull('court_x')
            ->whereNotNull('court_y')
            ->exists();
    }

    public function zoneForPoint(float $x, float $y): string
    {
        $dist = hypot($x - self::BASKET_X, $y - self::BASKET_Y);

        if ($y >= 72 && abs($x - self::BASKET_X) <= 18) {
            return 'raio';
        }

        if ($dist <= 38 || $y >= 55) {
            return 'media';
        }

        return 'tres';
    }

    /** Gera coordenadas plausíveis para demo/backfill. */
    public static function randomCoordinatesForType(string $shotType): array
    {
        return match ($shotType) {
            'FT' => [self::BASKET_X + (mt_rand(-20, 20) / 10), 78 + mt_rand(0, 8) / 10],
            '3PT' => [
                self::BASKET_X + (mt_rand(-45, 45) / 10),
                15 + mt_rand(0, 35) / 10,
            ],
            default => [
                self::BASKET_X + (mt_rand(-30, 30) / 10),
                55 + mt_rand(0, 35) / 10,
            ],
        };
    }
}