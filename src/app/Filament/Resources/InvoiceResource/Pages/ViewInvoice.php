<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('send')
                ->label('Enviar')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(fn () => $this->record->send()),

            Actions\Action::make('markAsPaid')
                ->label('Marcar como Pago')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['pending', 'partial', 'overdue']))
                ->form([
                    Forms\Components\DatePicker::make('paid_date')
                        ->label('Data Pagamento')
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('payment_method')
                        ->label('Forma')
                        ->options(Invoice::getPaymentMethodOptions()),
                ])
                ->action(fn (array $data) => 
                    $this->record->markAsPaid($data['payment_method'], $data['paid_date'])
                ),

            Actions\Action::make('registerPayment')
                ->label('Registrar Pagamento')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, ['pending', 'partial', 'overdue']))
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Valor')
                        ->numeric()
                        ->prefix('R$')
                        ->required()
                        ->default(fn () => $this->record->balance),
                    Forms\Components\DatePicker::make('payment_date')
                        ->label('Data')
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('payment_method')
                        ->label('Forma')
                        ->options(Invoice::getPaymentMethodOptions()),
                    Forms\Components\TextInput::make('reference')
                        ->label('Referência'),
                ])
                ->action(fn (array $data) => 
                    $this->record->registerPayment(
                        $data['amount'],
                        $data['payment_method'] ?? null,
                        $data['payment_date'],
                        $data['reference'] ?? null
                    )
                ),

            Actions\Action::make('printPdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Resumo
                Components\Section::make()
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('total')
                            ->label('Total')
                            ->money('BRL')
                            ->size('lg')
                            ->weight('bold'),

                        Components\TextEntry::make('amount_paid')
                            ->label('Pago')
                            ->money('BRL')
                            ->size('lg')
                            ->color('success'),

                        Components\TextEntry::make('balance')
                            ->label('Saldo')
                            ->money('BRL')
                            ->size('lg')
                            ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                        Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->size('lg')
                            ->formatStateUsing(fn (string $state): string => Invoice::getStatusOptions()[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'pending' => 'warning',
                                'partial' => 'info',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                // Identificação
                Components\Section::make('Identificação')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('uid')
                            ->label('ID')
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('invoice_number')
                            ->label('Número'),

                        Components\TextEntry::make('reference')
                            ->label('Referência')
                            ->placeholder('-'),

                        Components\TextEntry::make('client.name')
                            ->label('Cliente')
                            ->url(fn ($record) => route('filament.funil.resources.clients.view', $record->client_id)),

                        Components\TextEntry::make('contract.title')
                            ->label('Contrato')
                            ->url(fn ($record) => $record->contract_id 
                                ? route('filament.funil.resources.contracts.view', $record->contract_id) 
                                : null)
                            ->placeholder('Não vinculado'),

                        Components\TextEntry::make('process.title')
                            ->label('Processo')
                            ->url(fn ($record) => $record->process_id 
                                ? route('filament.funil.resources.processes.view', $record->process_id) 
                                : null)
                            ->placeholder('Não vinculado'),

                        Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                // Tipo e Período
                Components\Section::make('Classificação')
                    ->icon('heroicon-o-tag')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('invoice_type')
                            ->label('Tipo')
                            ->formatStateUsing(fn (string $state): string => Invoice::getInvoiceTypeOptions()[$state] ?? $state),

                        Components\TextEntry::make('billing_type')
                            ->label('Cobrança')
                            ->formatStateUsing(fn (string $state): string => Invoice::getBillingTypeOptions()[$state] ?? $state),

                        Components\TextEntry::make('period_description')
                            ->label('Período')
                            ->columnSpan(2),
                    ]),

                // Datas
                Components\Section::make('Datas')
                    ->icon('heroicon-o-calendar')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('issue_date')
                            ->label('Emissão')
                            ->date('d/m/Y'),

                        Components\TextEntry::make('due_date')
                            ->label('Vencimento')
                            ->date('d/m/Y')
                            ->color(fn ($record) => 
                                $record->is_overdue 
                                    ? 'danger' 
                                    : ($record->days_until_due <= 7 && $record->days_until_due >= 0 
                                        ? 'warning' 
                                        : null)
                            ),

                        Components\TextEntry::make('days_until_due')
                            ->label('Dias p/ Vencimento')
                            ->suffix(' dias')
                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 7 ? 'warning' : 'success'))
                            ->placeholder('-'),

                        Components\TextEntry::make('paid_date')
                            ->label('Pagamento')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ]),

                // Valores
                Components\Section::make('Valores')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('subtotal')
                            ->label('Subtotal')
                            ->money('BRL'),

                        Components\TextEntry::make('discount_amount')
                            ->label('Desconto')
                            ->money('BRL')
                            ->color('success'),

                        Components\TextEntry::make('interest')
                            ->label('Juros')
                            ->money('BRL')
                            ->color('warning')
                            ->visible(fn ($record) => $record->interest > 0),

                        Components\TextEntry::make('fine')
                            ->label('Multa')
                            ->money('BRL')
                            ->color('danger')
                            ->visible(fn ($record) => $record->fine > 0),

                        Components\TextEntry::make('paid_percentage')
                            ->label('% Pago')
                            ->suffix('%'),

                        Components\TextEntry::make('total_hours')
                            ->label('Total Horas')
                            ->suffix('h')
                            ->visible(fn ($record) => $record->billing_type === 'hourly'),
                    ]),

                // Pagamento
                Components\Section::make('Pagamento')
                    ->icon('heroicon-o-credit-card')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('payment_method')
                            ->label('Forma')
                            ->formatStateUsing(fn (?string $state): string => 
                                $state ? Invoice::getPaymentMethodOptions()[$state] ?? $state : '-'
                            ),

                        Components\TextEntry::make('payment_reference')
                            ->label('Referência')
                            ->placeholder('-'),

                        Components\TextEntry::make('transaction_id')
                            ->label('ID Transação')
                            ->placeholder('-'),
                    ]),

                // Dados Fiscais
                Components\Section::make('Dados Fiscais')
                    ->icon('heroicon-o-document-check')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record->nfse_number)
                    ->schema([
                        Components\TextEntry::make('nfse_number')
                            ->label('NFS-e'),

                        Components\TextEntry::make('nfse_emitted_at')
                            ->label('Emitida em')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('nfse_link')
                            ->label('Link')
                            ->url(fn ($record) => $record->nfse_link)
                            ->openUrlInNewTab(),
                    ]),

                // Observações
                Components\Section::make('Observações')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('-'),

                        Components\TextEntry::make('internal_notes')
                            ->label('Notas Internas')
                            ->placeholder('-'),

                        Components\TextEntry::make('terms')
                            ->label('Termos')
                            ->placeholder('-'),
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
                    ]),
            ]);
    }
}
