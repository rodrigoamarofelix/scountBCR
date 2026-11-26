#!/usr/bin/env python3
from pathlib import Path

SRC = Path.home() / "projetos/scountBCR/src"
RES = SRC / "app/Filament/Resources"

def write(path: Path, content: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content)
    print("wrote", path.relative_to(SRC))

write(RES / "TeamResource.php", r'''<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Equipes';
    protected static ?string $modelLabel = 'equipe';
    protected static ?string $pluralModelLabel = 'equipes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados da equipe')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                Forms\Components\TextInput::make('short_name')->label('Nome abreviado')->required()->maxLength(20),
                Forms\Components\FileUpload::make('logo_path')->label('Logotipo')->image()->directory('teams/logos')->disk('public')->imageEditor()->columnSpanFull(),
                Forms\Components\TextInput::make('category')->label('Categoria')->maxLength(255),
                Forms\Components\TextInput::make('head_coach')->label('Técnico responsável')->maxLength(255),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->label('Ativa')->default(true)->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('logo_path')->label('Logo')->disk('public')->circular(),
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('short_name')->label('Sigla')->searchable(),
            Tables\Columns\TextColumn::make('category')->label('Categoria')->toggleable(),
            Tables\Columns\TextColumn::make('head_coach')->label('Técnico')->toggleable(),
            Tables\Columns\TextColumn::make('athletes_count')->label('Atletas')->counts('athletes'),
            Tables\Columns\IconColumn::make('is_active')->label('Ativa')->boolean(),
        ])->filters([
            Tables\Filters\TernaryFilter::make('is_active')->label('Ativa'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
''')

write(RES / "OpponentResource.php", r'''<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OpponentResource\Pages;
use App\Models\Opponent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OpponentResource extends Resource
{
    protected static ?string $model = Opponent::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Adversários';
    protected static ?string $modelLabel = 'adversário';
    protected static ?string $pluralModelLabel = 'adversários';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                Forms\Components\TextInput::make('short_name')->label('Nome abreviado')->maxLength(20),
                Forms\Components\FileUpload::make('logo_path')->label('Logotipo')->image()->directory('opponents/logos')->disk('public')->imageEditor()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('logo_path')->label('Logo')->disk('public')->circular(),
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('short_name')->label('Sigla')->searchable(),
            Tables\Columns\TextColumn::make('matches_count')->label('Partidas')->counts('matches'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpponents::route('/'),
            'create' => Pages\CreateOpponent::route('/create'),
            'edit' => Pages\EditOpponent::route('/{record}/edit'),
        ];
    }
}
''')

write(RES / "CompetitionResource.php", r'''<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Models\Competition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Competições';
    protected static ?string $modelLabel = 'competição';
    protected static ?string $pluralModelLabel = 'competições';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
            Forms\Components\TextInput::make('season')->label('Temporada')->placeholder('2025/2026')->maxLength(255),
            Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('season')->label('Temporada'),
            Tables\Columns\TextColumn::make('matches_count')->label('Partidas')->counts('matches'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            'edit' => Pages\EditCompetition::route('/{record}/edit'),
        ];
    }
}
''')

write(RES / "AthleteResource.php", r'''<?php

namespace App\Filament\Resources;

use App\Enums\BasketballPosition;
use App\Filament\Resources\AthleteResource\Pages;
use App\Models\Athlete;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AthleteResource extends Resource
{
    protected static ?string $model = Athlete::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Atletas';
    protected static ?string $modelLabel = 'atleta';
    protected static ?string $pluralModelLabel = 'atletas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\Select::make('team_id')->label('Equipe')->relationship('team', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('full_name')->label('Nome completo')->required()->maxLength(255),
                Forms\Components\TextInput::make('cpf')->label('CPF')->required()->unique(ignoreRecord: true)->maxLength(14),
                Forms\Components\TextInput::make('sport_name')->label('Nome esportivo')->maxLength(255),
                Forms\Components\TextInput::make('jersey_number')->label('Número da camisa')->numeric()->minValue(0)->maxValue(99),
                Forms\Components\Select::make('functional_classification')->label('Classificação funcional')->options([
                    '1.0' => '1.0', '1.5' => '1.5', '2.0' => '2.0', '2.5' => '2.5',
                    '3.0' => '3.0', '3.5' => '3.5', '4.0' => '4.0', '4.5' => '4.5',
                ]),
                Forms\Components\Select::make('position')->label('Posição')->options(
                    collect(BasketballPosition::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])->all()
                ),
                Forms\Components\FileUpload::make('photo_path')->label('Fotografia')->image()->directory('athletes/photos')->disk('public')->imageEditor()->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Dados físicos')->columns(3)->schema([
                Forms\Components\DatePicker::make('birth_date')->label('Data de nascimento')->displayFormat('d/m/Y'),
                Forms\Components\TextInput::make('height_cm')->label('Altura (cm)')->numeric()->minValue(0),
                Forms\Components\TextInput::make('weight_kg')->label('Peso (kg)')->numeric()->minValue(0),
                Forms\Components\Toggle::make('is_active')->label('Ativo')->default(true)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('photo_path')->label('Foto')->disk('public')->circular(),
            Tables\Columns\TextColumn::make('full_name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('team.name')->label('Equipe')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('jersey_number')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('position')->label('Posição')->formatStateUsing(
                fn (?string $state) => $state ? BasketballPosition::from($state)->label() : '—'
            ),
            Tables\Columns\IconColumn::make('is_active')->label('Ativo')->boolean(),
        ])->filters([
            Tables\Filters\SelectFilter::make('team_id')->label('Equipe')->relationship('team', 'name'),
            Tables\Filters\TernaryFilter::make('is_active')->label('Ativo'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('team');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAthletes::route('/'),
            'create' => Pages\CreateAthlete::route('/create'),
            'edit' => Pages\EditAthlete::route('/{record}/edit'),
        ];
    }
}
''')

write(RES / "GameMatchResource.php", r'''<?php

namespace App\Filament\Resources;

use App\Enums\MatchStatus;
use App\Filament\Resources\GameMatchResource\Pages;
use App\Filament\Resources\GameMatchResource\RelationManagers;
use App\Models\GameMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameMatchResource extends Resource
{
    protected static ?string $model = GameMatch::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Competição';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Partidas';
    protected static ?string $modelLabel = 'partida';
    protected static ?string $pluralModelLabel = 'partidas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\Select::make('team_id')->label('Equipe')->relationship('team', 'name')->searchable()->preload()->required()->live(),
                Forms\Components\Select::make('opponent_id')->label('Adversário')->relationship('opponent', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('competition_id')->label('Competição')->relationship('competition', 'name')->searchable()->preload(),
                Forms\Components\Select::make('status')->label('Situação')->options(
                    collect(MatchStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()
                )->default(MatchStatus::Scheduled->value)->required(),
                Forms\Components\DatePicker::make('match_date')->label('Data')->required()->displayFormat('d/m/Y'),
                Forms\Components\TimePicker::make('match_time')->label('Horário')->seconds(false),
                Forms\Components\TextInput::make('location')->label('Local')->maxLength(255),
                Forms\Components\TextInput::make('phase')->label('Fase / rodada')->maxLength(255),
                Forms\Components\TextInput::make('match_type')->label('Tipo de partida')->maxLength(255),
            ]),
            Forms\Components\Section::make('Placar')->columns(2)->schema([
                Forms\Components\TextInput::make('team_score')->label('Placar da equipe')->numeric()->default(0)->minValue(0),
                Forms\Components\TextInput::make('opponent_score')->label('Placar do adversário')->numeric()->default(0)->minValue(0),
            ]),
            Forms\Components\Textarea::make('notes')->label('Observações')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('match_date', 'desc')->columns([
            Tables\Columns\TextColumn::make('match_date')->label('Data')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('team.short_name')->label('Equipe'),
            Tables\Columns\TextColumn::make('opponent.name')->label('Adversário')->limit(20),
            Tables\Columns\TextColumn::make('competition.name')->label('Competição')->toggleable(),
            Tables\Columns\TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn ($state) => $state instanceof MatchStatus ? $state->label() : MatchStatus::from($state)->label()),
            Tables\Columns\TextColumn::make('score')->label('Placar')->state(fn (GameMatch $r) => "{$r->team_score} x {$r->opponent_score}"),
            Tables\Columns\TextColumn::make('athletes_count')->label('Atletas')->counts('athletes'),
        ])->filters([
            Tables\Filters\SelectFilter::make('team_id')->label('Equipe')->relationship('team', 'name'),
            Tables\Filters\SelectFilter::make('status')->label('Situação')->options(
                collect(MatchStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()
            ),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['team', 'opponent', 'competition']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\AthletesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameMatches::route('/'),
            'create' => Pages\CreateGameMatch::route('/create'),
            'edit' => Pages\EditGameMatch::route('/{record}/edit'),
        ];
    }
}
''')

write(RES / "GameMatchResource/RelationManagers/AthletesRelationManager.php", r'''<?php

namespace App\Filament\Resources\GameMatchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AthletesRelationManager extends RelationManager
{
    protected static string $relationship = 'athletes';
    protected static ?string $title = 'Atletas convocados';
    protected static ?string $modelLabel = 'atleta';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Toggle::make('is_starter')->label('Titular')->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('full_name')->columns([
            Tables\Columns\TextColumn::make('jersey_number')->label('#'),
            Tables\Columns\TextColumn::make('full_name')->label('Nome')->searchable(),
            Tables\Columns\TextColumn::make('sport_name')->label('Nome esportivo'),
            Tables\Columns\IconColumn::make('is_starter')->label('Titular')->boolean(),
        ])->headerActions([
            Tables\Actions\AttachAction::make()->label('Adicionar atleta')->preloadRecordSelect()
                ->recordSelectOptionsQuery(fn (Builder $query) => $query
                    ->where('team_id', $this->getOwnerRecord()->team_id)
                    ->where('is_active', true)
                    ->orderBy('jersey_number'))
                ->form(fn (Tables\Actions\AttachAction $action): array => [
                    $action->getRecordSelect()->label('Atleta'),
                    Forms\Components\Toggle::make('is_starter')->label('Titular')->default(false),
                ]),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Editar convocação'),
            Tables\Actions\DetachAction::make()->label('Remover'),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DetachBulkAction::make()]),
        ]);
    }
}
''')

print("done")