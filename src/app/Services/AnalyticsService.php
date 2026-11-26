<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\ShotAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    public function __construct(
        protected ShotStatisticsService $shots,
    ) {}

    /** @param array{team_id?: ?int, athlete_id?: ?int, date_from?: ?string, date_to?: ?string} $filters */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = ShotAttempt::query();

        if (! empty($filters['team_id'])) {
            $teamId = (int) $filters['team_id'];
            $query->whereHas('athlete', fn (Builder $q) => $q->where('team_id', $teamId));
        }

        if (! empty($filters['athlete_id'])) {
            $query->where('athlete_id', (int) $filters['athlete_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query;
    }

    public function summarizeFiltered(array $filters): array
    {
        return $this->shots->summarize($this->filteredQuery($filters));
    }

    public function dashboardCards(array $filters): array
    {
        $summary = $this->summarizeFiltered($filters);

        $matchQuery = GameMatch::query();
        if (! empty($filters['team_id'])) {
            $matchQuery->where('team_id', (int) $filters['team_id']);
        }
        if (! empty($filters['date_from'])) {
            $matchQuery->whereDate('match_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $matchQuery->whereDate('match_date', '<=', $filters['date_to']);
        }

        $athleteCount = Athlete::query()
            ->when(! empty($filters['team_id']), fn ($q) => $q->where('team_id', (int) $filters['team_id']))
            ->when(! empty($filters['athlete_id']), fn ($q) => $q->where('id', (int) $filters['athlete_id']))
            ->where('is_active', true)
            ->count();

        return [
            'matches' => $matchQuery->count(),
            'athletes' => $athleteCount,
            'attempts' => $summary['overall']['attempts'],
            'points' => $summary['overall']['points'],
            'fg_percentage' => $summary['overall']['percentage'],
        ];
    }

    public function chartFgByType(array $filters): array
    {
        $summary = $this->summarizeFiltered($filters);

        return [
            'labels' => ['2 pontos', '3 pontos', 'Lance livre'],
            'datasets' => [
                [
                    'label' => 'Aproveitamento (%)',
                    'data' => [
                        $summary['two_point']['percentage'] ?? 0,
                        $summary['three_point']['percentage'] ?? 0,
                        $summary['free_throw']['percentage'] ?? 0,
                    ],
                    'backgroundColor' => ['#10b981', '#0ea5e9', '#8b5cf6'],
                ],
            ],
        ];
    }

    public function chartAttemptsVsMade(array $filters): array
    {
        $summary = $this->summarizeFiltered($filters);

        return [
            'labels' => ['2 pontos', '3 pontos', 'Lance livre'],
            'datasets' => [
                [
                    'label' => 'Convertidos',
                    'data' => [
                        $summary['two_point']['made'],
                        $summary['three_point']['made'],
                        $summary['free_throw']['made'],
                    ],
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Errados',
                    'data' => [
                        $summary['two_point']['missed'],
                        $summary['three_point']['missed'],
                        $summary['free_throw']['missed'],
                    ],
                    'backgroundColor' => '#ef4444',
                ],
            ],
        ];
    }

    public function chartEvolution(array $filters): array
    {
        $attempts = $this->filteredQuery($filters)->with('match.team')->get();
        $grouped = $attempts->groupBy('match_id')->sortBy(
            fn ($group) => $group->first()?->match?->match_date?->timestamp ?? 0
        );

        $labels = [];
        $data = [];

        foreach ($grouped as $group) {
            $match = $group->first()?->match;
            if (! $match) {
                continue;
            }
            $summary = $this->shots->summarize(ShotAttempt::query()->whereIn('id', $group->pluck('id')));
            $labels[] = $match->match_date->format('d/m/Y').' ('.$match->team?->short_name.')';
            $data[] = $summary['overall']['percentage'] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Aproveitamento geral (%)',
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
        ];
    }

    public function compareAthletes(array $athleteIds, array $filters): array
    {
        $athletes = Athlete::query()->whereIn('id', $athleteIds)->orderBy('jersey_number')->get();
        $rows = [];

        foreach ($athletes as $athlete) {
            $f = array_merge($filters, ['athlete_id' => $athlete->id]);
            $summary = $this->summarizeFiltered($f);

            $rows[] = [
                'athlete' => $athlete,
                'two_point' => $summary['two_point']['percentage'],
                'three_point' => $summary['three_point']['percentage'],
                'free_throw' => $summary['free_throw']['percentage'],
                'points' => $summary['overall']['points'],
                'attempts' => $summary['overall']['attempts'],
            ];
        }

        return $rows;
    }

    public function comparisonBarChart(array $rows): array
    {
        $labels = [];
        foreach ($rows as $row) {
            $labels[] = '#'.$row['athlete']->jersey_number.' '.($row['athlete']->sport_name ?? $row['athlete']->full_name);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => '2 pts (%)', 'data' => array_map(fn ($r) => $r['two_point'] ?? 0, $rows), 'backgroundColor' => '#10b981'],
                ['label' => '3 pts (%)', 'data' => array_map(fn ($r) => $r['three_point'] ?? 0, $rows), 'backgroundColor' => '#0ea5e9'],
                ['label' => 'LL (%)', 'data' => array_map(fn ($r) => $r['free_throw'] ?? 0, $rows), 'backgroundColor' => '#8b5cf6'],
            ],
        ];
    }

    public function comparisonRadar(array $rows): array
    {
        if ($rows === []) {
            return ['labels' => [], 'datasets' => []];
        }

        $datasets = [];
        $colors = ['#f59e0b', '#0ea5e9', '#10b981', '#ec4899'];

        foreach ($rows as $i => $row) {
            $datasets[] = [
                'label' => '#'.$row['athlete']->jersey_number,
                'data' => [
                    $row['two_point'] ?? 0,
                    $row['three_point'] ?? 0,
                    $row['free_throw'] ?? 0,
                ],
                'borderColor' => $colors[$i % count($colors)],
                'backgroundColor' => $colors[$i % count($colors)].'33',
            ];
        }

        return [
            'labels' => ['2 pontos', '3 pontos', 'Lance livre'],
            'datasets' => $datasets,
        ];
    }
}
