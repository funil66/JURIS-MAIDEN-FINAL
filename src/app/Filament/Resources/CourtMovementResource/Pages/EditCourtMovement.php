<?php

namespace App\Filament\Resources\CourtMovementResource\Pages;

use App\Filament\Resources\CourtMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourtMovement extends EditRecord
{
    protected static string $resource = CourtMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
