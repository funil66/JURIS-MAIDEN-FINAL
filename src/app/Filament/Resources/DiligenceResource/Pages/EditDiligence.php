<?php

namespace App\Filament\Resources\DiligenceResource\Pages;

use App\Filament\Resources\DiligenceResource;
use App\Models\Diligence;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditDiligence extends EditRecord
{
    protected static string $resource = DiligenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\Action::make('start')
                ->label('Iniciar')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => in_array($this->record->status, ['pending', 'scheduled']))
                ->requiresConfirmation()
                ->action(fn () => $this->record->start()),
            Actions\Action::make('complete')
                ->label('Concluir')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'in_progress')
                ->form([
                    Forms\Components\Toggle::make('was_successful')
                        ->label('Foi Bem Sucedida')
                        ->default(true),
                    Forms\Components\Textarea::make('result')
                        ->label('Resultado')
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->record->complete($data['was_successful'], $data['result'])),
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, Diligence::getActiveStatuses()))
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Motivo')
                        ->rows(2),
                ])
                ->action(fn (array $data) => $this->record->cancel($data['reason'])),
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
