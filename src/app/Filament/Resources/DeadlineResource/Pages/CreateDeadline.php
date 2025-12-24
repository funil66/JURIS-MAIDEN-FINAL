<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeadline extends CreateRecord
{
    protected static string $resource = DeadlineResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        
        return $data;
    }
}
