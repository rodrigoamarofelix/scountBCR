<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\ShotChartService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class ShotCharts extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Mapa de arremessos';

    protected static ?string $title = 'Mapa de arremessos';

    protected static ?string $navigationGroup = 'Análises';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.shot-charts';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill([
            'team_id' => Team::query()->value('id'),
            'match_id' => GameMatch::query()->orderByDesc('match_date')->value('id'),
            'athlete_id' => null,
            'period' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('team_id')
                ->label('Equipe')
                ->options(fn () => Team::query()->orderBy('name')->pluck('name', 'id'))
                ->live(),
            Select::make('match_id')
                ->label('Partida')
                ->options(fn (Get $get) => GameMatch::query()
                    ->when($get('team_id'), fn ($q, $id) => $q->where('team_id', $id))
                    ->orderByDesc('match_date')
                    ->with(['opponent', 'team'])
                    ->get()
                    ->mapWithKeys(fn ($m) => [
                        $m->id => $m->match_date->format('d/m/Y').' — '.($m->opponent?->name ?? '—'),
                    ]))
                ->searchable(),
            Select::make('athlete_id')
                ->label('Atleta')
                ->placeholder('Todos')
                ->options(fn (Get $get) => Athlete::query()
                    ->when($get('team_id'), fn ($q, $id) => $q->where('team_id', $id))
                    ->orderBy('jersey_number')
                    ->get()
                    ->mapWithKeys(fn ($a) => [$a->id => '#'.$a->jersey_number.' — '.$a->full_name])),
            Select::make('period')
                ->label('Período')
                ->placeholder('Todos')
                ->options(collect(range(1, 4))->mapWithKeys(fn ($p) => [$p => $p.'º'])->all()),
        ])->columns(4)->statePath('filters');
    }

    protected function chartFilters(): array
    {
        return collect($this->filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->all();
    }

    #[Computed]
    public function scatter(): array
    {
        return app(ShotChartService::class)->scatterPoints($this->chartFilters());
    }

    #[Computed]
    public function zones(): array
    {
        return app(ShotChartService::class)->zoneSummary($this->chartFilters());
    }

    #[Computed]
    public function zoneChart(): array
    {
        return app(ShotChartService::class)->chartZonesFg($this->chartFilters());
    }

    #[Computed]
    public function hasPoints(): bool
    {
        return app(ShotChartService::class)->hasCoordinates($this->chartFilters());
    }

    public function updatedFilters(): void
    {
        unset($this->scatter, $this->zones, $this->zoneChart, $this->hasPoints);
        $this->dispatch('shot-charts-refresh', chart: $this->zoneChart);
    }
}