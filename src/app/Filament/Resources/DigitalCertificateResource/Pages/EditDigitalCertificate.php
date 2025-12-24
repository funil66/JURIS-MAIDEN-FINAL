<?php

namespace App\Filament\Resources\DigitalCertificateResource\Pages;

use App\Filament\Resources\DigitalCertificateResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditDigitalCertificate extends EditRecord
{
    protected static string $resource = DigitalCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
