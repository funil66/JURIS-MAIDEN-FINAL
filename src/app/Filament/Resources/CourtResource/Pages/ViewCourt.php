<?php

namespace App\Filament\Resources\CourtResource\Pages;

use App\Filament\Resources\CourtResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCourt extends ViewRecord
{
    protected static string $resource = CourtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
