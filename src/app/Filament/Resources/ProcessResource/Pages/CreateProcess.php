<?php

namespace App\Filament\Resources\ProcessResource\Pages;

use App\Filament\Resources\ProcessResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcess extends CreateRecord
{
    protected static string $resource = ProcessResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se veio de um processo pai via URL, preencher automaticamente
        if (request()->has('parent_id')) {
            $data['parent_id'] = request()->get('parent_id');
        }
        
        if (request()->has('client_id') && empty($data['client_id'])) {
            $data['client_id'] = request()->get('client_id');
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
