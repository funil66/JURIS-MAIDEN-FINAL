<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Fatura';

    protected static ?string $pluralModelLabel = 'Faturas';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Dados básicos da fatura')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('contract_id', null)),

                        Forms\Components\Select::make('contract_id')
                            ->label('Contrato')
                            ->relationship(
                                'contract',
                                'title',
                                fn (Builder $query, Get $get) => 
                                    $query->when($get('client_id'), fn ($q, $clientId) => 
                                        $q->where('client_id', $clientId)
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Opcional'),

                        Forms\Components\Select::make('process_id')
                            ->label('Processo')
                            ->relationship(
                                'process',
                                'title',
                                fn (Builder $query, Get $get) => 
                                    $query->when($get('client_id'), fn ($q, $clientId) => 
                                        $q->where('client_id', $clientId)
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Opcional'),

                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Número da Fatura')
                            ->placeholder('Auto-gerado')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referência Interna')
                            ->maxLength(100),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Invoice::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // Seção: Tipo e Período
                Forms\Components\Section::make('Classificação')
                    ->icon('heroicon-o-tag')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('invoice_type')
                            ->label('Tipo de Fatura')
                            ->options(Invoice::getInvoiceTypeOptions())
                            ->default('services')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('billing_type')
                            ->label('Tipo de Cobrança')
                            ->options(Invoice::getBillingTypeOptions())
                            ->default('fixed')
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('period_start')
                            ->label('Período Início')
                            ->native(false),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Período Fim')
                            ->native(false)
                            ->afterOrEqual('period_start'),
                    ]),

                // Seção: Datas
                Forms\Components\Section::make('Datas')
                    ->icon('heroicon-o-calendar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Data de Emissão')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data de Vencimento')
                            ->required()
                            ->native(false)
                            ->default(now()->addDays(30))
                            ->afterOrEqual('issue_date'),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Data de Pagamento')
                            ->native(false),
                    ]),

                // Seção: Valores
                Forms\Components\Section::make('Valores')
                    ->description('Os valores são calculados automaticamente a partir dos itens')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Desconto %')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state > 0) {
                                    $subtotal = (float) ($get('subtotal') ?? 0);
                                    $discount = $subtotal * (float) $state / 100;
                                    $set('discount_amount', round($discount, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Desconto R$')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('interest')
                            ->label('Juros')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('fine')
                            ->label('Multa')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Valor Pago')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('balance')
                            ->label('Saldo')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                // Seção: Pagamento
                Forms\Components\Section::make('Pagamento')
                    ->icon('heroicon-o-credit-card')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pagamento')
                            ->options(Invoice::getPaymentMethodOptions())
                            ->native(false),

                        Forms\Components\TextInput::make('payment_reference')
                            ->label('Referência do Pagamento'),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID da Transação'),
                    ]),

                // Seção: Dados Fiscais
                Forms\Components\Section::make('Dados Fiscais')
                    ->icon('heroicon-o-document-check')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('nfse_number')
                            ->label('Número NFS-e'),

                        Forms\Components\TextInput::make('nfse_link')
                            ->label('Link NFS-e')
                            ->url(),

                        Forms\Components\DateTimePicker::make('nfse_emitted_at')
                            ->label('Data Emissão NFS-e')
                            ->native(false),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações (visíveis ao cliente)')
                            ->rows(3),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(3),

                        Forms\Components\Textarea::make('terms')
                            ->label('Termos e Condições')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('invoice_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Invoice::getInvoiceTypeOptions()[$state] ?? $state)
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->is_overdue 
                            ? 'danger' 
                            : ($record->days_until_due <= 7 && $record->days_until_due >= 0 
                                ? 'warning' 
                                : null)
                    ),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Pago')
                    ->money('BRL')
                    ->color('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('BRL')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Invoice::getStatusOptions()),

                Tables\Filters\SelectFilter::make('invoice_type')
                    ->label('Tipo')
                    ->options(Invoice::getInvoiceTypeOptions()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\Filter::make('this_month')
                    ->label('Este mês')
                    ->query(fn (Builder $query) => $query->thisMonth()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('send')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->modalDescription('Isso mudará o status para Pendente.')
                        ->action(fn ($record) => $record->send()),

                    Tables\Actions\Action::make('markAsPaid')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'partial', 'overdue']))
                        ->form([
                            Forms\Components\DatePicker::make('paid_date')
                                ->label('Data Pagamento')
                                ->default(now())
                                ->required(),
                            Forms\Components\Select::make('payment_method')
                                ->label('Forma')
                                ->options(Invoice::getPaymentMethodOptions()),
                        ])
                        ->action(fn ($record, array $data) => 
                            $record->markAsPaid($data['payment_method'], $data['paid_date'])
                        ),

                    Tables\Actions\Action::make('registerPayment')
                        ->label('Registrar Pagamento')
                        ->icon('heroicon-o-banknotes')
                        ->color('info')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'partial', 'overdue']))
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Valor')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
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
                        ->action(fn ($record, array $data) => 
                            $record->registerPayment(
                                $data['amount'],
                                $data['payment_method'] ?? null,
                                $data['payment_date'],
                                $data['reference'] ?? null
                            )
                        ),

                    Tables\Actions\Action::make('applyLateFees')
                        ->label('Aplicar Juros/Multa')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->visible(fn ($record) => $record->is_overdue)
                        ->action(fn ($record) => $record->applyLateFees()),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !in_array($record->status, ['paid', 'cancelled']))
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->cancel($data['reason'])),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'create-from-time' => Pages\CreateFromTimeEntries::route('/create-from-time'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'invoice_number', 'description', 'client.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::unpaid()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        return $overdue > 0 ? 'danger' : 'warning';
    }
}
