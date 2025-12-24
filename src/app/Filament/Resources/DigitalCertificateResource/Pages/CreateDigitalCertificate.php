<?php

namespace App\Filament\Resources\DigitalCertificateResource\Pages;

use App\Filament\Resources\DigitalCertificateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDigitalCertificate extends CreateRecord
{
    protected static string $resource = DigitalCertificateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
