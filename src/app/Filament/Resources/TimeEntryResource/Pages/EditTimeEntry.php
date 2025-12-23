<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntry extends EditRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('startTimer')
                ->label('Iniciar Timer')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => !$this->record->is_running && $this->record->status === 'draft')
                ->action(fn () => $this->record->startTimer()),
            Actions\Action::make('stopTimer')
                ->label('Parar Timer')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->visible(fn () => $this->record->is_running)
                ->action(fn () => $this->record->stopTimer()),
            Actions\Action::make('submit')
                ->label('Submeter')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(fn () => $this->record->submit()),
            Actions\Action::make('approve')
                ->label('Aprovar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'submitted')
                ->requiresConfirmation()
                ->action(fn () => $this->record->approve()),
            Actions\Action::make('reject')
                ->label('Rejeitar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'submitted')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Motivo')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->record->reject($data['reason'])),
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
