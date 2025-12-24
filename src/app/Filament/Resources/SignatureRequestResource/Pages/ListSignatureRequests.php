<?php

namespace App\Filament\Resources\SignatureRequestResource\Pages;

use App\Filament\Resources\SignatureRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListSignatureRequests extends ListRecords
{
    protected static string $resource = SignatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Solicitação'),
        ];
    }
}
