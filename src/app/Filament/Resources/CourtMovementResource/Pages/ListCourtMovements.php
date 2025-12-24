<?php

namespace App\Filament\Resources\CourtMovementResource\Pages;

use App\Filament\Resources\CourtMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCourtMovements extends ListRecords
{
    protected static string $resource = CourtMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Movimentação'),
        ];
    }
}
