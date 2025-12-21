<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    /**
     * Preenche o formulário com dados da URL (client_id)
     */
    public function mount(): void
    {
        parent::mount();

        // Se veio client_id na URL, preencher automaticamente
        if ($clientId = request()->query('client_id')) {
            $this->form->fill([
                'client_id' => (int) $clientId,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
