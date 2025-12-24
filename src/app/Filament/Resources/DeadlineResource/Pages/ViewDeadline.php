<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use App\Models\Deadline;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeadline extends ViewRecord
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('complete')
                ->label('Marcar Cumprido')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Marcar Prazo como Cumprido')
                ->form([
                    \Filament\Forms\Components\TextInput::make('protocol')
                        ->label('Número do Protocolo')
                        ->maxLength(100),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->record->complete($data['notes'] ?? null, $data['protocol'] ?? null))
                ->visible(fn () => $this->record->isPending()),

            Actions\Action::make('extend')
                ->label('Prorrogar')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('new_due_date')
                        ->label('Nova Data de Vencimento')
                        ->required()
                        ->afterOrEqual('today'),
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Motivo da Prorrogação')
                        ->rows(2),
                ])
                ->action(fn (array $data) => $this->record->extend(
                    \Carbon\Carbon::parse($data['new_due_date']),
                    $data['reason'] ?? null
                ))
                ->visible(fn () => $this->record->isPending()),

            Actions\Action::make('missed')
                ->label('Marcar Perdido')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Marcar Prazo como Perdido')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Justificativa')
                        ->required()
                        ->rows(3),
                ])
                ->action(fn (array $data) => $this->record->markAsMissed($data['notes']))
                ->visible(fn () => $this->record->isPending()),

            Actions\DeleteAction::make(),
        ];
    }
}
