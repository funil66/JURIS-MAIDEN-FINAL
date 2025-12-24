<?php

namespace App\Filament\Resources\CourtResource\Pages;

use App\Filament\Resources\CourtResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCourt extends EditRecord
{
    protected static string $resource = CourtResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Verificar se está configurado
        $isConfigured = !empty($data['api_base_url']) && 
            (!empty($data['api_key']) || !empty($data['api_username']));

        $data['is_configured'] = $isConfigured;

        return $data;
    }
}
