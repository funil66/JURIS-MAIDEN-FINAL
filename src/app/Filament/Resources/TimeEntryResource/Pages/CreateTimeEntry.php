<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeEntry extends CreateRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantir user_id
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        // Calcular valor total se tiver taxa horária
        if (!empty($data['hourly_rate']) && !empty($data['duration_minutes'])) {
            $hours = $data['duration_minutes'] / 60;
            $data['total_amount'] = round($data['hourly_rate'] * $hours, 2);
        }

        return $data;
    }
}
