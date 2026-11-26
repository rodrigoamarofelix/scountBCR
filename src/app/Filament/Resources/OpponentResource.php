<?php

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
