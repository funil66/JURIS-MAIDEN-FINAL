<?php

namespace App\Filament\Resources\CourtResource\Pages;

use App\Filament\Resources\CourtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourt extends CreateRecord
{
    protected static string $resource = CourtResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Verificar se está configurado
        $isConfigured = !empty($data['api_base_url']) && 
            (!empty($data['api_key']) || !empty($data['api_username']));

        $data['is_configured'] = $isConfigured;

        return $data;
    }
}
