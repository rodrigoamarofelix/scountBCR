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

class AthleteComparison extends Page implements HasForms
{
    use HasPageShield;
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
