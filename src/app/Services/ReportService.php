<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Carbon;

class ReportService
{
    public function __construct(
        protected AnalyticsService $analytics,
        protected ShotStatisticsService $shots,
    ) {}

    public function build(string $type, array $params): array
    {
        return match ($type) {
            'match' => $this->matchReport((int) $params['match_id']),
            'athlete' => $this->athleteReport((int) $params['athlete_id'], $this->periodFilters($params)),
            'team' => $this->teamReport((int) $params['team_id'], $this->periodFilters($params)),
            'comparison' => $this->comparisonReport($params['athlete_ids'] ?? [], $this->contextFilters($params)),
            'evolution' => $this->evolutionReport($this->contextFilters($params)),
            default => throw new \InvalidArgumentException('Tipo de relatório inválido.'),
        };
    }

    /** @return array{team_id?: ?int, athlete_id?: ?int, date_from?: ?string, date_to?: ?string} */
    protected function contextFilters(array $params): array
    {
        return collect($params)
            ->only(['team_id', 'athlete_id', 'date_from', 'date_to'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => $k === 'team_id' || $k === 'athlete_id' ? (int) $v : $v)
            ->all();
    }

    protected function periodFilters(array $params): array
    {
        return collect($params)
            ->only(['date_from', 'date_to'])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();
    }

    protected function meta(string $title, string $type): array
    {
        return [
            'title' => $title,
            'type' => $type,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    protected function formatPct(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 1, ',', '.').'%';
    }

    public function matchReport(int $matchId): array
    {
        $match = GameMatch::query()
            ->with(['team', 'opponent', 'competition'])
            ->findOrFail($matchId);

        $summary = $this->shots->summarize($match->shotAttempts()->getQuery());
        $athletes = $match->athletes()->orderBy('jersey_number')->get();
        $rows = [];

        foreach ($athletes as $athlete) {
            $stats = $this->shots->summarize(
                $this->shots->queryForContext($match, $athlete)
            );
            $rows[] = $this->athleteStatsRow($athlete, $stats);
        }

        $label = ($match->team?->name ?? '—').' x '.($match->opponent?->name ?? '—');

        return array_merge($this->meta('Relatório da partida', 'match'), [
            'match' => $match,
            'summary' => $summary,
            'athlete_rows' => $rows,
            'excel' => $this->excelFromAthleteRows($rows, $label),
        ]);
    }

    public function athleteReport(int $athleteId, array $filters): array
    {
        $athlete = Athlete::query()->with('team')->findOrFail($athleteId);
        $context = array_merge($filters, ['athlete_id' => $athleteId]);
        $summary = $this->analytics->summarizeFiltered($context);

        $matches = GameMatch::query()
            ->whereHas('shotAttempts', fn ($q) => $q->where('athlete_id', $athleteId))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('match_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('match_date', '<=', $filters['date_to']))
            ->with(['opponent', 'team'])
            ->orderBy('match_date')
            ->get();

        $matchRows = [];
        foreach ($matches as $gameMatch) {
            $stats = $this->shots->summarize(
                $this->shots->queryForContext($gameMatch, $athlete)
            );
            $overall = $stats['overall'];
            $matchRows[] = [
                'date' => $gameMatch->match_date->format('d/m/Y'),
                'opponent' => $gameMatch->opponent?->name ?? '—',
                'points' => $overall['points'],
                'attempts' => $overall['attempts'],
                'fg' => $this->formatPct($overall['percentage']),
                'fg_raw' => $overall['percentage'],
            ];
        }

        $headings = ['Data', 'Adversário', 'Pontos', 'Arremessos', 'Aproveitamento'];
        $excelRows = array_map(fn ($r) => [$r['date'], $r['opponent'], $r['points'], $r['attempts'], $r['fg_raw']], $matchRows);

        return array_merge($this->meta('Perfil estatístico — '.$athlete->full_name, 'athlete'), [
            'athlete' => $athlete,
            'summary' => $summary,
            'match_rows' => $matchRows,
            'excel' => ['headings' => $headings, 'rows' => $excelRows],
        ]);
    }

    public function teamReport(int $teamId, array $filters): array
    {
        $team = Team::query()->findOrFail($teamId);
        $context = array_merge($filters, ['team_id' => $teamId]);
        $summary = $this->analytics->summarizeFiltered($context);

        $athletes = Athlete::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('jersey_number')
            ->get();

        $rows = [];
        foreach ($athletes as $athlete) {
            $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
            $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null;
            $stats = $this->shots->summarize(
                $this->shots->queryForContext(null, $athlete, $from, $to)
            );
            $rows[] = $this->athleteStatsRow($athlete, $stats);
        }

        return array_merge($this->meta('Relatório da equipe — '.$team->name, 'team'), [
            'team' => $team,
            'summary' => $summary,
            'athlete_rows' => $rows,
            'excel' => $this->excelFromAthleteRows($rows, $team->name),
        ]);
    }

    public function comparisonReport(array $athleteIds, array $filters): array
    {
        $ids = array_values(array_filter(array_map('intval', $athleteIds)));
        if (count($ids) < 2) {
            throw new \InvalidArgumentException('Selecione pelo menos 2 atletas para o comparativo.');
        }

        $rows = $this->analytics->compareAthletes($ids, $filters);
        $headings = ['Atleta', '2 pts', '3 pts', 'LL', 'Pontos', 'Arremessos'];
        $excelRows = [];
        foreach ($rows as $row) {
            $name = '#'.$row['athlete']->jersey_number.' '.($row['athlete']->sport_name ?? $row['athlete']->full_name);
            $excelRows[] = [
                $name,
                $row['two_point'],
                $row['three_point'],
                $row['free_throw'],
                $row['points'],
                $row['attempts'],
            ];
        }

        return array_merge($this->meta('Relatório comparativo de atletas', 'comparison'), [
            'comparison_rows' => $rows,
            'excel' => ['headings' => $headings, 'rows' => $excelRows],
        ]);
    }

    public function evolutionReport(array $filters): array
    {
        $chart = $this->analytics->chartEvolution($filters);
        $labels = $chart['labels'] ?? [];
        $values = $chart['datasets'][0]['data'] ?? [];
        $rows = [];
        foreach ($labels as $i => $label) {
            $rows[] = [
                'match' => $label,
                'fg' => $this->formatPct($values[$i] ?? null),
                'fg_raw' => $values[$i] ?? null,
            ];
        }

        $headings = ['Partida', 'Aproveitamento'];
        $excelRows = array_map(fn ($r) => [$r['match'], $r['fg_raw']], $rows);

        return array_merge($this->meta('Evolução do aproveitamento', 'evolution'), [
            'filters' => $filters,
            'evolution_rows' => $rows,
            'summary' => $this->analytics->summarizeFiltered($filters),
            'excel' => ['headings' => $headings, 'rows' => $excelRows],
        ]);
    }

    protected function athleteStatsRow(Athlete $athlete, array $stats): array
    {
        $overall = $stats['overall'];

        return [
            'jersey' => $athlete->jersey_number,
            'name' => $athlete->sport_name ?? $athlete->full_name,
            'two_point' => $this->formatPct($stats['two_point']['percentage']),
            'three_point' => $this->formatPct($stats['three_point']['percentage']),
            'free_throw' => $this->formatPct($stats['free_throw']['percentage']),
            'points' => $overall['points'],
            'attempts' => $overall['attempts'],
            'fg' => $this->formatPct($overall['percentage']),
            'fg_raw' => $overall['percentage'],
            'two_raw' => $stats['two_point']['percentage'],
            'three_raw' => $stats['three_point']['percentage'],
            'ft_raw' => $stats['free_throw']['percentage'],
        ];
    }

    protected function excelFromAthleteRows(array $rows, string $contextLabel): array
    {
        $headings = ['#', 'Atleta', '2 pts', '3 pts', 'LL', 'Pontos', 'Arremessos', 'Aproveit.'];

        $excelRows = array_map(fn ($r) => [
            $r['jersey'],
            $r['name'],
            $r['two_raw'],
            $r['three_raw'],
            $r['ft_raw'],
            $r['points'],
            $r['attempts'],
            $r['fg_raw'],
        ], $rows);

        return [
            'headings' => $headings,
            'rows' => $excelRows,
            'title' => $contextLabel,
        ];
    }
}