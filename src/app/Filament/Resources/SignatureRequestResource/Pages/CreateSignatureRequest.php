<?php

namespace App\Filament\Resources\SignatureRequestResource\Pages;

use App\Filament\Resources\SignatureRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSignatureRequest extends CreateRecord
{
    protected static string $resource = SignatureRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        $data['requested_at'] = now();
        $data['status'] = 'draft';

        return $data;
    }
}
