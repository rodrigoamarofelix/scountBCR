<?php

namespace App\Filament\Resources;

use App\Enums\BasketballPosition;
use App\Filament\Pages\AthleteProfile;
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
        ])->actions([
            Tables\Actions\Action::make('profile')
                ->label('Perfil estatístico')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (Athlete $record) => AthleteProfile::getUrl(['athlete' => $record])),
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
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
