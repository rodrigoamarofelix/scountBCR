<?php

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
