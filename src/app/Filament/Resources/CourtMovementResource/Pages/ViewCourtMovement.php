<?php

namespace App\Filament\Resources\CourtMovementResource\Pages;

use App\Filament\Resources\CourtMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourtMovement extends ViewRecord
{
    protected static string $resource = CourtMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
