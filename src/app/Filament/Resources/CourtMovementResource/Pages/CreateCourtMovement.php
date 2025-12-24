<?php

namespace App\Filament\Resources\CourtMovementResource\Pages;

use App\Filament\Resources\CourtMovementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourtMovement extends CreateRecord
{
    protected static string $resource = CourtMovementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
