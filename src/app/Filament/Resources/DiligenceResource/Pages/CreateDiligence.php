<?php

namespace App\Filament\Resources\DiligenceResource\Pages;

use App\Filament\Resources\DiligenceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiligence extends CreateRecord
{
    protected static string $resource = DiligenceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = $data['created_by_user_id'] ?? auth()->id();

        return $data;
    }
}
