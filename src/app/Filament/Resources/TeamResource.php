<?php

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
