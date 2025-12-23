<?php

namespace App\Filament\Resources\DiligenceResource\Pages;

use App\Filament\Resources\DiligenceResource;
use App\Models\Diligence;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewDiligence extends ViewRecord
{
    protected static string $resource = DiligenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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
            Actions\Action::make('markReimbursed')
                ->label('Marcar Reembolsada')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->is_billable && !$this->record->is_reimbursed && $this->record->status === 'completed')
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsReimbursed()),
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
                                'pending' => 'warning',
                                'scheduled' => 'info',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'cancelled', 'failed' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('type_label')
                            ->label('Tipo')
                            ->badge()
                            ->color('gray'),

                        Components\TextEntry::make('priority_label')
                            ->label('Prioridade')
                            ->badge()
                            ->color(fn ($record) => match($record->priority) {
                                'urgent' => 'danger',
                                'high' => 'warning',
                                'normal' => 'info',
                                'low' => 'gray',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('title')
                            ->label('Título')
                            ->columnSpanFull()
                            ->weight('bold'),

                        Components\TextEntry::make('objective')
                            ->label('Objetivo')
                            ->columnSpanFull()
                            ->placeholder('Não informado'),
                    ]),

                Components\Section::make('Vínculos')
                    ->columns(4)
                    ->schema([
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

                        Components\TextEntry::make('assignedUser.name')
                            ->label('Responsável')
                            ->placeholder('Não atribuído'),

                        Components\TextEntry::make('createdByUser.name')
                            ->label('Criado por'),
                    ]),

                Components\Section::make('Agendamento')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('scheduled_date')
                            ->label('Data')
                            ->date('d/m/Y')
                            ->color(fn ($record) => $record->is_overdue ? 'danger' : null),

                        Components\TextEntry::make('scheduled_time')
                            ->label('Hora Início')
                            ->time('H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('scheduled_end_time')
                            ->label('Hora Término')
                            ->time('H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('formatted_duration')
                            ->label('Duração')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Local')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('location_name')
                            ->label('Nome do Local')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('full_address')
                            ->label('Endereço Completo')
                            ->columnSpan(2)
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('contact_name')
                            ->label('Contato')
                            ->placeholder('-'),

                        Components\TextEntry::make('contact_phone')
                            ->label('Telefone')
                            ->placeholder('-'),

                        Components\TextEntry::make('contact_department')
                            ->label('Setor')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Custos')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('mileage_km')
                            ->label('Quilometragem')
                            ->suffix(' km'),

                        Components\TextEntry::make('mileage_cost')
                            ->label('Custo km')
                            ->money('BRL'),

                        Components\TextEntry::make('parking_cost')
                            ->label('Estacionamento')
                            ->money('BRL'),

                        Components\TextEntry::make('toll_cost')
                            ->label('Pedágios')
                            ->money('BRL'),

                        Components\TextEntry::make('transport_cost')
                            ->label('Transporte')
                            ->money('BRL'),

                        Components\TextEntry::make('other_costs')
                            ->label('Outros')
                            ->money('BRL'),

                        Components\TextEntry::make('total_cost')
                            ->label('Custo Total')
                            ->money('BRL')
                            ->weight('bold'),

                        Components\IconEntry::make('is_billable')
                            ->label('Faturável')
                            ->boolean(),

                        Components\IconEntry::make('is_reimbursed')
                            ->label('Reembolsada')
                            ->boolean(),

                        Components\TextEntry::make('reimbursed_at')
                            ->label('Reembolsada em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Resultado')
                    ->visible(fn ($record) => $record->status === 'completed' || $record->was_successful !== null)
                    ->columns(3)
                    ->schema([
                        Components\IconEntry::make('was_successful')
                            ->label('Bem Sucedida')
                            ->boolean(),

                        Components\TextEntry::make('started_at')
                            ->label('Iniciada em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('completed_at')
                            ->label('Concluída em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        Components\TextEntry::make('result')
                            ->label('Descrição do Resultado')
                            ->columnSpanFull()
                            ->placeholder('Nenhum resultado registrado'),

                        Components\TextEntry::make('failure_reason')
                            ->label('Motivo da Falha')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->was_successful === false)
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->placeholder('Nenhuma descrição'),

                        Components\TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('Nenhuma observação'),

                        Components\TextEntry::make('internal_notes')
                            ->label('Notas Internas')
                            ->columnSpanFull()
                            ->placeholder('Nenhuma nota'),
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
