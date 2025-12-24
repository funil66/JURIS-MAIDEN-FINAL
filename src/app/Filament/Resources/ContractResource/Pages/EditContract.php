<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('activate')
                ->label('Ativar Contrato')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending_signature']))
                ->requiresConfirmation()
                ->modalHeading('Ativar Contrato')
                ->modalDescription('Isso marcará o contrato como assinado e ativo, e gerará as parcelas automaticamente.')
                ->action(function () {
                    $this->record->activate();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\Action::make('generateInstallments')
                ->label('Gerar Parcelas')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(fn () => $this->record->generateInstallments()),

            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
