<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

class ReportsHub extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Central de relatórios';

    protected static ?string $navigationGroup = 'Análises';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.reports-hub';

    public ?array $filters = [];

    public function mount(): void
    {
        $teamId = Team::query()->value('id');

        $this->form->fill([
            'report_type' => 'match',
            'match_id' => GameMatch::query()->orderByDesc('match_date')->value('id'),
            'team_id' => $teamId,
            'athlete_id' => null,
            'athlete_ids' => [],
            'date_from' => null,
            'date_to' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('report_type')
                    ->label('Tipo de relatório')
                    ->options([
                        'match' => 'Por partida',
                        'athlete' => 'Por atleta',
                        'team' => 'Por equipe',
                        'comparison' => 'Comparativo',
                        'evolution' => 'Evolução',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        $set('match_id', $state === 'match'
                            ? GameMatch::query()->orderByDesc('match_date')->value('id')
                            : null);
                        $set('athlete_id', null);
                        $set('athlete_ids', []);

                        if ($state === 'athlete' && $get('team_id')) {
                            $set('athlete_id', $this->defaultAthleteId((int) $get('team_id')));
                        }
                    }),
                Select::make('match_id')
                    ->label('Partida')
                    ->visible(fn (Get $get) => $get('report_type') === 'match')
                    ->options(fn () => GameMatch::query()
                        ->with(['team', 'opponent'])
                        ->orderByDesc('match_date')
                        ->get()
                        ->mapWithKeys(fn ($m) => [
                            $m->id => $m->match_date->format('d/m/Y').' — '.($m->team?->name ?? '—').' x '.($m->opponent?->name ?? '—'),
                        ]))
                    ->searchable()
                    ->required(fn (Get $get) => $get('report_type') === 'match'),
                Select::make('team_id')
                    ->label('Equipe')
                    ->visible(fn (Get $get) => in_array($get('report_type'), ['team', 'comparison', 'evolution', 'athlete'], true))
                    ->options(fn () => Team::query()->orderBy('name')->pluck('name', 'id'))
                    ->live()
                    ->required(fn (Get $get) => in_array($get('report_type'), ['team', 'comparison'], true))
                    ->afterStateUpdated(function (?int $state, Get $get, Set $set): void {
                        $set('athlete_id', null);
                        $set('athlete_ids', []);

                        if ($get('report_type') === 'athlete' && $state) {
                            $set('athlete_id', $this->defaultAthleteId($state));
                        }
                    }),
                Select::make('athlete_id')
                    ->label('Atleta')
                    ->visible(fn (Get $get) => $get('report_type') === 'athlete')
                    ->options(fn (Get $get) => $this->athleteOptions($get('team_id')))
                    ->searchable()
                    ->required(fn (Get $get) => $get('report_type') === 'athlete'),
                Select::make('athlete_ids')
                    ->label('Atletas (2 a 4)')
                    ->visible(fn (Get $get) => $get('report_type') === 'comparison')
                    ->multiple()
                    ->options(fn (Get $get) => $this->athleteOptions($get('team_id')))
                    ->required(fn (Get $get) => $get('report_type') === 'comparison'),
                DatePicker::make('date_from')
                    ->label('Período — de')
                    ->displayFormat('d/m/Y')
                    ->visible(fn (Get $get) => $get('report_type') !== 'match'),
                DatePicker::make('date_to')
                    ->label('Período — até')
                    ->displayFormat('d/m/Y')
                    ->visible(fn (Get $get) => $get('report_type') !== 'match'),
            ])
            ->columns(2)
            ->statePath('filters');
    }

    protected function defaultAthleteId(int $teamId): ?int
    {
        return Athlete::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('jersey_number')
            ->value('id');
    }

    protected function athleteOptions(?int $teamId): array
    {
        return Athlete::query()
            ->when($teamId, fn ($q) => $q->where('team_id', $teamId))
            ->where('is_active', true)
            ->orderBy('jersey_number')
            ->get()
            ->mapWithKeys(fn ($a) => [$a->id => '#'.$a->jersey_number.' — '.$a->full_name])
            ->all();
    }

    protected function reportParams(): array
    {
        $f = $this->filters ?? [];
        $type = $f['report_type'] ?? 'match';

        $pick = static function (array $keys) use ($f): array {
            $out = [];
            foreach ($keys as $key) {
                $value = $f[$key] ?? null;
                if ($value === null || $value === '' || $value === []) {
                    continue;
                }
                $out[$key] = $value;
            }

            return $out;
        };

        return match ($type) {
            'match' => array_merge(['type' => 'match'], $pick(['match_id'])),
            'athlete' => array_merge(['type' => 'athlete'], $pick(['athlete_id', 'date_from', 'date_to'])),
            'team' => array_merge(['type' => 'team'], $pick(['team_id', 'date_from', 'date_to'])),
            'comparison' => array_merge(['type' => 'comparison'], $pick(['athlete_ids', 'date_from', 'date_to'])),
            'evolution' => array_merge(['type' => 'evolution'], $pick(['team_id', 'date_from', 'date_to'])),
            default => ['type' => $type],
        };
    }

    protected function syncFiltersFromForm(): void
    {
        $this->filters = $this->form->getState();
    }

    public function buildExportUrl(string $format): string
    {
        $this->syncFiltersFromForm();
        $this->assertCanExport();

        $params = $this->reportParams();
        $url = route('filament.admin.reports.export', ['format' => $format]);
        $query = http_build_query($params);

        return $query !== '' ? $url.'?'.$query : $url;
    }

    public function openExport(string $format): void
    {
        try {
            $url = $this->buildExportUrl($format);

            if ($format === 'pdf') {
                $this->js('window.open('.json_encode($url).', "_blank")');
            } else {
                $this->js('window.location.href = '.json_encode($url));
            }
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Não foi possível exportar')
                ->body(collect($e->errors())->flatten()->first() ?? 'Verifique os filtros.')
                ->danger()
                ->send();
        }
    }

    protected function assertCanExport(): void
    {
        $type = $this->filters['report_type'] ?? null;
        $f = $this->filters ?? [];

        $message = match ($type) {
            'match' => empty($f['match_id'] ?? null) ? 'Selecione uma partida.' : null,
            'athlete' => empty($f['athlete_id'] ?? null) ? 'Selecione um atleta.' : null,
            'team' => empty($f['team_id'] ?? null) ? 'Selecione uma equipe.' : null,
            'comparison' => count($f['athlete_ids'] ?? []) < 2 ? 'Selecione pelo menos 2 atletas.' : null,
            'evolution' => null,
            default => 'Selecione o tipo de relatório.',
        };

        if ($message !== null) {
            throw ValidationException::withMessages(['filters' => $message]);
        }
    }

    public function canExport(): bool
    {
        try {
            $this->assertCanExport();

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    #[Computed]
    public function preview(): ?array
    {
        try {
            $type = $this->filters['report_type'] ?? null;
            if (! $type || ! $this->canExport()) {
                return null;
            }

            return app(ReportService::class)->build($type, $this->reportParams());
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatedFilters(): void
    {
        unset($this->preview);
    }

    public function updatedFiltersReportType(mixed $value): void
    {
        if ($value === 'athlete' && empty($this->filters['athlete_id'] ?? null)) {
            $teamId = (int) ($this->filters['team_id'] ?? Team::query()->value('id'));
            if ($teamId) {
                $this->filters['team_id'] = $teamId;
                $this->filters['athlete_id'] = $this->defaultAthleteId($teamId);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Visualizar PDF')
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('warning')
                ->action(fn () => $this->openExport('pdf')),
            Action::make('xlsx')
                ->label('Baixar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn () => $this->openExport('xlsx')),
        ];
    }
}