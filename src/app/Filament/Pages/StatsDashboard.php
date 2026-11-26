<?php

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
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Livewire\Attributes\Computed;

class StatsDashboard extends Page implements HasForms
{
    use HasPageShield;
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
                    ->afterStateUpdated(function () { $this->filters['athlete_id'] = null; }),
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
