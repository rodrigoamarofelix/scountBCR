<?php

namespace App\Filament\Resources\GameMatchResource\Pages;

use App\Filament\Resources\GameMatchResource;
use App\Models\GameMatch;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameMatch extends EditRecord
{
    protected static string $resource = GameMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scout')
                ->label('Scouting ao vivo')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->url(fn (GameMatch $record) => route('scout.match', $record)),
            Actions\DeleteAction::make(),
        ];
    }
}
