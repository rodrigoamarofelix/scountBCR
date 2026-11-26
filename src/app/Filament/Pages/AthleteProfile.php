<?php

namespace App\Filament\Pages;

use App\Models\Athlete;
use App\Services\ReportService;
use App\Services\ShotChartService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;

class AthleteProfile extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'perfil-atleta/{athlete}';

    protected static string $view = 'filament.pages.athlete-profile';

    public ?Athlete $athlete = null;

    public ?array $filters = [];

    public function mount(Athlete $athlete): void
    {
        $this->athlete = $athlete->loadMissing('team');
        $this->form->fill([
            'date_from' => null,
            'date_to' => null,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        if ($this->athlete === null) {
            return 'Perfil estatístico do atleta';
        }

        return 'Perfil — '.$this->athlete->full_name;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('date_from')->label('Período — de')->displayFormat('d/m/Y'),
                DatePicker::make('date_to')->label('Período — até')->displayFormat('d/m/Y'),
            ])
            ->columns(2)
            ->statePath('filters');
    }

    protected function reportParams(): array
    {
        return array_merge(
            [
                'type' => 'athlete',
                'athlete_id' => $this->athlete->id,
            ],
            collect($this->filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->all()
        );
    }

    protected function buildExportUrl(string $format): string
    {
        $this->filters = $this->form->getState();
        $params = $this->reportParams();
        $url = route('filament.admin.reports.export', ['format' => $format]);
        $query = http_build_query($params);

        return $query !== '' ? $url.'?'.$query : $url;
    }

    public function openExport(string $format): void
    {
        $url = $this->buildExportUrl($format);

        if ($format === 'pdf') {
            $this->js('window.open('.json_encode($url).', "_blank")');
        } else {
            $this->js('window.location.href = '.json_encode($url));
        }
    }

    #[Computed]
    public function report(): array
    {
        if ($this->athlete === null) {
            return [
                'summary' => ['overall' => ['attempts' => 0, 'points' => 0, 'percentage' => null]],
                'match_rows' => [],
            ];
        }

        return app(ReportService::class)->athleteReport(
            $this->athlete->id,
            collect($this->filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->all()
        );
    }

    #[Computed]
    public function shotMapPoints(): array
    {
        if ($this->athlete === null) {
            return [];
        }

        return app(ShotChartService::class)->scatterPoints([
            'athlete_id' => $this->athlete->id,
        ]);
    }

    #[Computed]
    public function shotZones(): array
    {
        if ($this->athlete === null) {
            return [];
        }

        return app(ShotChartService::class)->zoneSummary([
            'athlete_id' => $this->athlete->id,
        ]);
    }

    public function updatedFilters(): void
    {
        unset($this->report, $this->shotMapPoints, $this->shotZones);
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        if (! isset($parameters['athlete'])) {
            throw new \InvalidArgumentException('Parâmetro athlete é obrigatório.');
        }

        $parameters['athlete'] = $parameters['athlete'] instanceof Athlete
            ? $parameters['athlete']->id
            : $parameters['athlete'];

        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar aos atletas')
                ->url(fn () => route('filament.admin.resources.athletes.index'))
                ->color('gray'),
            Action::make('pdf')
                ->label('Visualizar PDF')
                ->icon('heroicon-o-document-magnifying-glass')
                ->action(fn () => $this->openExport('pdf')),
            Action::make('xlsx')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->action(fn () => $this->openExport('xlsx')),
        ];
    }
}