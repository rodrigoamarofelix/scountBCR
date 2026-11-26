#!/usr/bin/env python3
from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"


def w(rel: str, content: str) -> None:
    p = SRC / rel
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content)
    print(rel)


w("app/Services/AnalyticsService.php", r'''<?php

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
            $labels[] = '#'.$row['athlete']->jersey_number.' '.$row['athlete']->sport_name ?? $row['athlete']->full_name;
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
''')

# Fix bad indentation in compareAthletes - PHP might fail on "  public function"
content = (SRC / "app/Services/AnalyticsService.php").read_text()
content = content.replace("  public function compareAthletes", "    public function compareAthletes")
(SRC / "app/Services/AnalyticsService.php").write_text(content)

w("app/Filament/Pages/StatsDashboard.php", r'''<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\Team;
use App\Services\AnalyticsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class StatsDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Dashboard estatístico';

    protected static ?string $title = 'Dashboard estatístico';

    protected static ?string $navigationGroup = 'Análises';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.stats-dashboard';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill([
            'team_id' => null,
            'athlete_id' => null,
            'date_from' => null,
            'date_to' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('team_id')
                    ->label('Equipe')
                    ->placeholder('Todas')
                    ->options(fn () => Team::query()->orderBy('name')->pluck('name', 'id'))
                    ->live()
                    ->afterStateUpdated(fn () => $this->filters['athlete_id'] = null),
                Select::make('athlete_id')
                    ->label('Atleta')
                    ->placeholder('Todos')
                    ->options(function () {
                        $teamId = $this->filters['team_id'] ?? null;
                        return Athlete::query()
                            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
                            ->where('is_active', true)
                            ->orderBy('jersey_number')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => '#'.$a->jersey_number.' — '.$a->full_name]);
                    })
                    ->live(),
                DatePicker::make('date_from')
                    ->label('Período — de')
                    ->displayFormat('d/m/Y'),
                DatePicker::make('date_to')
                    ->label('Período — até')
                    ->displayFormat('d/m/Y'),
            ])
            ->columns(4)
            ->statePath('filters');
    }

    protected function analytics(): AnalyticsService
    {
        return app(AnalyticsService::class);
    }

    #[Computed]
    public function cards(): array
    {
        return $this->analytics()->dashboardCards($this->filters ?? []);
    }

    #[Computed]
    public function chartFg(): array
    {
        return $this->analytics()->chartFgByType($this->filters ?? []);
    }

    #[Computed]
    public function chartAttempts(): array
    {
        return $this->analytics()->chartAttemptsVsMade($this->filters ?? []);
    }

    #[Computed]
    public function chartEvolution(): array
    {
        return $this->analytics()->chartEvolution($this->filters ?? []);
    }

    public function updatedFilters(): void
    {
        unset($this->cards, $this->chartFg, $this->chartAttempts, $this->chartEvolution);
        $this->dispatch('analytics-charts-refresh', fg: $this->chartFg, attempts: $this->chartAttempts, evolution: $this->chartEvolution);
    }
}
''')

w("app/Filament/Pages/AthleteComparison.php", r'''<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\Team;
use App\Services\AnalyticsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class AthleteComparison extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Comparativo de atletas';

    protected static ?string $title = 'Comparativo de atletas';

    protected static ?string $navigationGroup = 'Análises';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.athlete-comparison';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill([
            'team_id' => Team::query()->value('id'),
            'athlete_ids' => [],
            'date_from' => null,
            'date_to' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('team_id')
                    ->label('Equipe')
                    ->options(fn () => Team::query()->orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->live(),
                Select::make('athlete_ids')
                    ->label('Atletas (mín. 2)')
                    ->multiple()
                    ->rules(['array', 'min:2', 'max:4'])
                    ->options(function () {
                        $teamId = $this->filters['team_id'] ?? null;
                        if (! $teamId) {
                            return [];
                        }
                        return Athlete::query()
                            ->where('team_id', $teamId)
                            ->where('is_active', true)
                            ->orderBy('jersey_number')
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => '#'.$a->jersey_number.' — '.$a->full_name]);
                    })
                    ->live(),
                DatePicker::make('date_from')->label('De')->displayFormat('d/m/Y'),
                DatePicker::make('date_to')->label('Até')->displayFormat('d/m/Y'),
            ])
            ->columns(2)
            ->statePath('filters');
    }

    protected function contextFilters(): array
    {
        return collect($this->filters ?? [])
            ->except('athlete_ids')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();
    }

    #[Computed]
    public function comparisonRows(): array
    {
        $ids = $this->filters['athlete_ids'] ?? [];
        if (count($ids) < 2) {
            return [];
        }

        return app(AnalyticsService::class)->compareAthletes($ids, $this->contextFilters());
    }

    #[Computed]
    public function barChart(): array
    {
        return app(AnalyticsService::class)->comparisonBarChart($this->comparisonRows);
    }

    #[Computed]
    public function radarChart(): array
    {
        return app(AnalyticsService::class)->comparisonRadar($this->comparisonRows);
    }

    public function updatedFilters(): void
    {
        unset($this->comparisonRows, $this->barChart, $this->radarChart);
        $this->dispatch('comparison-charts-refresh', bar: $this->barChart, radar: $this->radarChart);
    }
}
''')

charts_js = r'''
function scountRenderChart(canvasId, config, type = 'bar') {
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === 'undefined') return;
    if (el._chart) { el._chart.destroy(); }
    el._chart = new Chart(el, { type, data: config, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { labels: { color: '#a1a1aa' } } }, scales: type !== 'radar' ? { x: { ticks: { color: '#a1a1aa' } }, y: { beginAtZero: true, ticks: { color: '#a1a1aa' } } } : {} } });
}
'''

w("resources/views/filament/pages/stats-dashboard.blade.php", r'''@php
    $cards = $this->cards;
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach([
            ['Partidas', $cards['matches']],
            ['Atletas', $cards['athletes']],
            ['Arremessos', $cards['attempts']],
            ['Pontos', $cards['points']],
            ['Aproveitamento', ($cards['fg_percentage'] ?? 'N/A').(is_numeric($cards['fg_percentage']) ? '%' : '')],
        ] as [$label, $value])
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-filament::section heading="Aproveitamento por tipo">
            <canvas id="chart-fg" class="max-h-64"></canvas>
        </x-filament::section>
        <x-filament::section heading="Tentativas vs convertidos">
            <canvas id="chart-attempts" class="max-h-64"></canvas>
        </x-filament::section>
        <x-filament::section heading="Evolução por partida">
            <canvas id="chart-evolution" class="max-h-64"></canvas>
        </x-filament::section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        {charts_js}
        document.addEventListener('DOMContentLoaded', () => {
            scountRenderChart('chart-fg', @js($this->chartFg));
            scountRenderChart('chart-attempts', @js($this->chartAttempts));
            scountRenderChart('chart-evolution', @js($this->chartEvolution), 'line');
        });
        window.addEventListener('analytics-charts-refresh', (e) => {
            scountRenderChart('chart-fg', e.detail.fg);
            scountRenderChart('chart-attempts', e.detail.attempts);
            scountRenderChart('chart-evolution', e.detail.evolution, 'line');
        });
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                setTimeout(() => {
                    scountRenderChart('chart-fg', @js($this->chartFg));
                    scountRenderChart('chart-attempts', @js($this->chartAttempts));
                    scountRenderChart('chart-evolution', @js($this->chartEvolution), 'line');
                }, 50);
            });
        });
    </script>
</x-filament-panels::page>
'''.replace('{charts_js}', charts_js.strip()))

w("resources/views/filament/pages/athlete-comparison.blade.php", r'''@php
    $rows = $this->comparisonRows;
@endphp
<x-filament-panels::page>
    {{ $this->form }}

    @if(count($rows) < 2)
        <x-filament::section class="mt-6">
            <p class="text-gray-600 dark:text-gray-400">Selecione pelo menos <strong>dois atletas</strong> da mesma equipe para comparar.</p>
        </x-filament::section>
    @else
        <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">Indicador</th>
                        @foreach($rows as $row)
                            <th class="px-4 py-3">#{{ $row['athlete']->jersey_number }} {{ $row['athlete']->full_name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach([
                        'two_point' => '2 pontos (%)',
                        'three_point' => '3 pontos (%)',
                        'free_throw' => 'Lances livres (%)',
                        'points' => 'Total de pontos',
                        'attempts' => 'Tentativas',
                    ] as $key => $label)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $label }}</td>
                            @foreach($rows as $row)
                                <td class="px-4 py-3 tabular-nums">
                                    @if(str_ends_with($key, 'point'))
                                        {{ $row[$key] !== null ? $row[$key].'%' : 'N/A' }}
                                    @else
                                        {{ $row[$key] }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-filament::section heading="Comparativo de aproveitamento">
                <canvas id="chart-compare-bar" class="max-h-80"></canvas>
            </x-filament::section>
            <x-filament::section heading="Radar (2pts / 3pts / LL)">
                <canvas id="chart-compare-radar" class="max-h-80"></canvas>
            </x-filament::section>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        {charts_js}
        function refreshComparisonCharts() {
            scountRenderChart('chart-compare-bar', @js($this->barChart));
            scountRenderChart('chart-compare-radar', @js($this->radarChart), 'radar');
        }
        document.addEventListener('DOMContentLoaded', refreshComparisonCharts);
        Livewire.hook('commit', ({ succeed }) => { succeed(() => setTimeout(refreshComparisonCharts, 50)); });
    </script>
</x-filament-panels::page>
'''.replace('{charts_js}', charts_js.strip()))

# Hide default Filament dashboard from nav or replace - update AdminPanelProvider to not show default dashboard first
panel = (SRC / "app/Providers/Filament/AdminPanelProvider.php").read_text()
if "StatsDashboard" not in panel:
    panel = panel.replace(
        "->pages([\n                Pages\\Dashboard::class,\n            ])",
        "->pages([\n                \\App\\Filament\\Pages\\StatsDashboard::class,\n            ])",
    )
    (SRC / "app/Providers/Filament/AdminPanelProvider.php").write_text(panel)
    print("AdminPanelProvider")

# Fix StatsDashboard updatedFilters - athlete reset bug
sd = (SRC / "app/Filament/Pages/StatsDashboard.php").read_text()
sd = sd.replace(
    "->afterStateUpdated(fn () => $this->filters['athlete_id'] = null),",
    "->afterStateUpdated(function () { $this->filters['athlete_id'] = null; }),",
)
(SRC / "app/Filament/Pages/StatsDashboard.php").write_text(sd)

# Fix comparison label bug in AnalyticsService
svc = (SRC / "app/Services/AnalyticsService.php").read_text()
svc = svc.replace(
    "$labels[] = '#'.$row['athlete']->jersey_number.' '.$row['athlete']->sport_name ?? $row['athlete']->full_name;",
    "$labels[] = '#'.$row['athlete']->jersey_number.' '.($row['athlete']->sport_name ?? $row['athlete']->full_name);",
)
(SRC / "app/Services/AnalyticsService.php").write_text(svc)

print("etapa 4 done")