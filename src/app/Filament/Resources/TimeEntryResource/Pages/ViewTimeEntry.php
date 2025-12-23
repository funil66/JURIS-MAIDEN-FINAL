<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewTimeEntry extends ViewRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Identificação')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('uid')
                            ->label('ID')
                            ->badge()
                            ->color('primary'),

                        Components\TextEntry::make('status_label')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => match($record->status) {
                                'draft' => 'gray',
                                'submitted' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'billed', 'paid' => 'info',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('activity_type_label')
                            ->label('Tipo')
                            ->badge()
                            ->color('gray'),

                        Components\IconEntry::make('is_running')
                            ->label('Timer Ativo')
                            ->boolean()
                            ->trueIcon('heroicon-s-play')
                            ->trueColor('success'),

                        Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Vínculos')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('Colaborador'),

                        Components\TextEntry::make('process.title')
                            ->label('Processo')
                            ->url(fn ($record) => $record->process_id 
                                ? route('filament.funil.resources.processes.view', $record->process_id) 
                                : null
                            )
                            ->placeholder('Sem processo'),

                        Components\TextEntry::make('client.name')
                            ->label('Cliente')
                            ->url(fn ($record) => $record->client_id 
                                ? route('filament.funil.resources.clients.view', $record->client_id) 
                                : null
                            ),

                        Components\TextEntry::make('service.title')
                            ->label('Serviço')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Tempo')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('work_date')
                            ->label('Data')
                            ->date('d/m/Y'),

                        Components\TextEntry::make('start_time')
                            ->label('Início')
                            ->time('H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('end_time')
                            ->label('Término')
                            ->time('H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('formatted_duration')
                            ->label('Duração')
                            ->weight('bold'),

                        Components\TextEntry::make('duration_decimal')
                            ->label('Horas Decimais')
                            ->suffix(' h'),

                        Components\TextEntry::make('timer_started_at')
                            ->label('Timer Iniciado')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->is_running),
                    ]),

                Components\Section::make('Faturamento')
                    ->columns(4)
                    ->schema([
                        Components\IconEntry::make('is_billable')
                            ->label('Faturável')
                            ->boolean(),

                        Components\TextEntry::make('hourly_rate')
                            ->label('Taxa Horária')
                            ->money('BRL')
                            ->placeholder('-'),

                        Components\TextEntry::make('total_amount')
                            ->label('Valor Total')
                            ->money('BRL')
                            ->weight('bold')
                            ->placeholder('-'),

                        Components\TextEntry::make('billed_at')
                            ->label('Faturado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Aprovação')
                    ->columns(3)
                    ->visible(fn ($record) => in_array($record->status, ['approved', 'rejected']))
                    ->schema([
                        Components\TextEntry::make('approvedBy.name')
                            ->label('Aprovado/Rejeitado por')
                            ->placeholder('-'),

                        Components\TextEntry::make('approved_at')
                            ->label('Data')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('rejection_reason')
                            ->label('Motivo da Rejeição')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('Observações')
                            ->columnSpanFull()
                            ->placeholder('Nenhuma observação'),
                    ]),

                Components\Section::make('Metadados')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
