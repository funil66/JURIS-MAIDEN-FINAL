<?php

namespace App\Filament\Resources\ProceedingResource\Pages;

use App\Filament\Resources\ProceedingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProceeding extends EditRecord
{
    protected static string $resource = ProceedingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('completeDeadline')
                ->label('Cumprir Prazo')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->has_deadline && !$this->record->deadline_completed)
                ->requiresConfirmation()
                ->action(fn () => $this->record->completeDeadline()),
            Actions\Action::make('completeAction')
                ->label('Concluir Ação')
                ->icon('heroicon-o-check')
                ->color('info')
                ->visible(fn () => $this->record->requires_action && !$this->record->action_completed)
                ->requiresConfirmation()
                ->action(fn () => $this->record->completeAction()),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
