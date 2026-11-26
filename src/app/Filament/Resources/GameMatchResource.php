<?php

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
        ])->actions([
                Tables\Actions\Action::make("scout")
                    ->label("Scouting ao vivo")
                    ->icon("heroicon-o-bolt")
                    ->color("warning")
                    ->url(fn (GameMatch $record) => route("scout.match", $record)),
                Tables\Actions\EditAction::make(),
            ])->bulkActions([
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
