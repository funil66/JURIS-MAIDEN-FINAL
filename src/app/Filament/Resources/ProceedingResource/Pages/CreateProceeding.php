<?php

namespace App\Filament\Resources\ProceedingResource\Pages;

use App\Filament\Resources\ProceedingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProceeding extends CreateRecord
{
    protected static string $resource = ProceedingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantir que user_id está preenchido
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        return $data;
    }
}
