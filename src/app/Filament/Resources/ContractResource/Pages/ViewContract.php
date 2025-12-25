<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('activate')
                ->label('Ativar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'pending_signature']))
                ->requiresConfirmation()
                ->action(fn () => $this->record->activate()),

            Actions\Action::make('suspend')
                ->label('Suspender')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(fn () => $this->record->suspend()),

            Actions\Action::make('reactivate')
                ->label('Reativar')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'suspended')
                ->requiresConfirmation()
                ->action(fn () => $this->record->reactivate()),

            Actions\Action::make('complete')
                ->label('Concluir')
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(fn () => $this->record->complete()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Identificação
                Components\Section::make('Identificação')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('uid')
                            ->label('ID')
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('contract_number')
                            ->label('Número'),

                        Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state ? (Contract::getStatusOptions()[$state] ?? $state) : '-')
                            ->color(fn (?string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending_signature' => 'warning',
                                'active' => 'success',
                                'suspended' => 'danger',
                                'completed' => 'info',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('title')
                            ->label('Título')
                            ->columnSpanFull(),

                        Components\TextEntry::make('client.name')
                            ->label('Cliente')
                            ->url(fn ($record) => route('filament.funil.resources.clients.view', $record->client_id)),

                        Components\TextEntry::make('process.title')
                            ->label('Processo')
                            ->url(fn ($record) => $record->process_id 
                                ? route('filament.funil.resources.processes.view', $record->process_id) 
                                : null)
                            ->placeholder('Não vinculado'),

                        Components\TextEntry::make('responsibleUser.name')
                            ->label('Responsável'),

                        Components\TextEntry::make('type')
                            ->label('Tipo')
                            ->formatStateUsing(fn (?string $state): string => $state ? (Contract::getTypeOptions()[$state] ?? $state) : '-'),

                        Components\TextEntry::make('area')
                            ->label('Área')
                            ->formatStateUsing(fn (?string $state): string => $state ? Contract::getAreaOptions()[$state] ?? $state : '-'),

                        Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->placeholder('Sem descrição'),
                    ]),

                // Valores
                Components\Section::make('Valores e Honorários')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('fee_type')
                            ->label('Tipo de Honorário')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $state ? (Contract::getFeeTypeOptions()[$state] ?? $state) : '-')
                            ->color('primary'),

                        Components\TextEntry::make('total_value')
                            ->label('Valor Total')
                            ->money('BRL')
                            ->size('lg'),

                        Components\TextEntry::make('total_paid')
                            ->label('Total Pago')
                            ->money('BRL')
                            ->color('success'),

                        Components\TextEntry::make('remaining_value')
                            ->label('Saldo Devedor')
                            ->money('BRL')
                            ->color('warning'),

                        Components\TextEntry::make('hourly_rate')
                            ->label('Taxa/Hora')
                            ->money('BRL')
                            ->visible(fn ($record) => in_array($record->fee_type, ['hourly', 'hybrid'])),

                        Components\TextEntry::make('estimated_hours')
                            ->label('Horas Estimadas')
                            ->suffix(' horas')
                            ->visible(fn ($record) => in_array($record->fee_type, ['hourly', 'hybrid'])),

                        Components\TextEntry::make('success_fee_percentage')
                            ->label('% Êxito')
                            ->suffix('%')
                            ->visible(fn ($record) => in_array($record->fee_type, ['success', 'hybrid'])),

                        Components\TextEntry::make('success_fee_base')
                            ->label('Base Êxito')
                            ->money('BRL')
                            ->visible(fn ($record) => in_array($record->fee_type, ['success', 'hybrid'])),

                        Components\TextEntry::make('minimum_fee')
                            ->label('Honorário Mínimo')
                            ->money('BRL')
                            ->visible(fn ($record) => $record->minimum_fee > 0),

                        Components\TextEntry::make('entry_value')
                            ->label('Entrada')
                            ->money('BRL')
                            ->visible(fn ($record) => $record->entry_value > 0),
                    ]),

                // Pagamento
                Components\Section::make('Condições de Pagamento')
                    ->icon('heroicon-o-credit-card')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('payment_method')
                            ->label('Forma')
                            ->formatStateUsing(fn (?string $state): string => $state ? Contract::getPaymentMethodOptions()[$state] ?? $state : '-'),

                        Components\TextEntry::make('payment_frequency')
                            ->label('Frequência')
                            ->formatStateUsing(fn (?string $state): string => $state ? Contract::getPaymentFrequencyOptions()[$state] ?? $state : '-'),

                        Components\TextEntry::make('installments_count')
                            ->label('Parcelas'),

                        Components\TextEntry::make('day_of_payment')
                            ->label('Dia Vencimento')
                            ->placeholder('-'),

                        Components\TextEntry::make('paid_installments_count')
                            ->label('Parcelas Pagas')
                            ->badge()
                            ->color('success'),

                        Components\TextEntry::make('pending_installments_count')
                            ->label('Parcelas Pendentes')
                            ->badge()
                            ->color('warning'),

                        Components\TextEntry::make('overdue_installments_count')
                            ->label('Parcelas Vencidas')
                            ->badge()
                            ->color('danger'),

                        Components\TextEntry::make('paid_percentage')
                            ->label('% Pago')
                            ->suffix('%'),
                    ]),

                // Vigência
                Components\Section::make('Vigência')
                    ->icon('heroicon-o-calendar')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('start_date')
                            ->label('Início')
                            ->date('d/m/Y'),

                        Components\TextEntry::make('end_date')
                            ->label('Término')
                            ->date('d/m/Y')
                            ->color(fn ($record) => $record?->is_expiring_soon ? 'warning' : ($record?->is_expired ? 'danger' : null)),

                        Components\TextEntry::make('signature_date')
                            ->label('Assinatura')
                            ->date('d/m/Y'),

                        Components\TextEntry::make('days_until_expiration')
                            ->label('Dias p/ Vencimento')
                            ->suffix(' dias')
                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 30 ? 'warning' : 'success')),

                        Components\IconEntry::make('is_signed')
                            ->label('Assinado')
                            ->boolean(),

                        Components\TextEntry::make('signature_type')
                            ->label('Tipo Assinatura')
                            ->formatStateUsing(fn (?string $state): string => $state ? Contract::getSignatureTypeOptions()[$state] ?? $state : '-'),

                        Components\IconEntry::make('auto_renew')
                            ->label('Renovação Auto.')
                            ->boolean(),

                        Components\TextEntry::make('renewal_date')
                            ->label('Próx. Renovação')
                            ->date('d/m/Y')
                            ->visible(fn ($record) => $record->auto_renew),
                    ]),

                // Escopo
                Components\Section::make('Escopo e Condições')
                    ->icon('heroicon-o-document-check')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('scope_of_work')
                            ->label('Escopo do Trabalho')
                            ->html()
                            ->placeholder('Não definido'),

                        Components\TextEntry::make('exclusions')
                            ->label('Exclusões')
                            ->placeholder('Não definido'),

                        Components\TextEntry::make('special_conditions')
                            ->label('Condições Especiais')
                            ->placeholder('Não definido'),
                    ]),

                // Metadados
                Components\Section::make('Metadados')
                    ->icon('heroicon-o-information-circle')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('creator.name')
                            ->label('Criado por'),

                        Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('internal_notes')
                            ->label('Observações Internas')
                            ->columnSpanFull()
                            ->placeholder('Sem observações'),
                    ]),
            ]);
    }
}
