<?php

namespace App\Filament\Resources\DigitalCertificateResource\Pages;

use App\Filament\Resources\DigitalCertificateResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListDigitalCertificates extends ListRecords
{
    protected static string $resource = DigitalCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Certificado'),
        ];
    }
}
