<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Contrato';

    protected static ?string $pluralModelLabel = 'Contratos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Dados básicos do contrato')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome')
                                    ->required(),
                                Forms\Components\TextInput::make('document')
                                    ->label('CPF/CNPJ')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('process_id')
                            ->label('Processo Vinculado')
                            ->relationship('process', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Opcional'),

                        Forms\Components\Select::make('responsible_user_id')
                            ->label('Responsável')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\TextInput::make('title')
                            ->label('Título do Contrato')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Contrato de Honorários Advocatícios - João Silva'),

                        Forms\Components\TextInput::make('contract_number')
                            ->label('Número do Contrato')
                            ->maxLength(50)
                            ->placeholder('Ex: CTR-2025/001'),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Contract::getTypeOptions())
                            ->default('legal_services')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('area')
                            ->label('Área')
                            ->options(Contract::getAreaOptions())
                            ->searchable()
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // Seção: Honorários
                Forms\Components\Section::make('Honorários')
                    ->description('Configuração de valores e tipos de cobrança')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('fee_type')
                            ->label('Tipo de Honorário')
                            ->options(Contract::getFeeTypeOptions())
                            ->default('fixed')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                // Reset campos específicos quando muda o tipo
                                if ($state !== 'hourly') {
                                    $set('hourly_rate', null);
                                    $set('estimated_hours', null);
                                }
                                if ($state !== 'success' && $state !== 'hybrid') {
                                    $set('success_fee_percentage', null);
                                    $set('success_fee_base', null);
                                }
                            }),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Valor Total')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('minimum_fee')
                            ->label('Honorário Mínimo')
                            ->numeric()
                            ->prefix('R$')
                            ->visible(fn (Get $get) => in_array($get('fee_type'), ['success', 'hybrid'])),

                        // Campos para Hora
                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Taxa por Hora')
                            ->numeric()
                            ->prefix('R$')
                            ->visible(fn (Get $get) => in_array($get('fee_type'), ['hourly', 'hybrid'])),

                        Forms\Components\TextInput::make('estimated_hours')
                            ->label('Horas Estimadas')
                            ->numeric()
                            ->suffix('horas')
                            ->visible(fn (Get $get) => in_array($get('fee_type'), ['hourly', 'hybrid'])),

                        // Campos para Êxito
                        Forms\Components\TextInput::make('success_fee_percentage')
                            ->label('% de Êxito')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn (Get $get) => in_array($get('fee_type'), ['success', 'hybrid'])),

                        Forms\Components\TextInput::make('success_fee_base')
                            ->label('Base de Cálculo do Êxito')
                            ->numeric()
                            ->prefix('R$')
                            ->helperText('Valor sobre o qual será calculado o êxito')
                            ->visible(fn (Get $get) => in_array($get('fee_type'), ['success', 'hybrid'])),
                    ]),

                // Seção: Pagamento
                Forms\Components\Section::make('Condições de Pagamento')
                    ->description('Forma e parcelamento')
                    ->icon('heroicon-o-credit-card')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('entry_value')
                            ->label('Valor de Entrada')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pagamento')
                            ->options(Contract::getPaymentMethodOptions())
                            ->native(false),

                        Forms\Components\Select::make('payment_frequency')
                            ->label('Frequência')
                            ->options(Contract::getPaymentFrequencyOptions())
                            ->default('monthly')
                            ->native(false)
                            ->live(),

                        Forms\Components\TextInput::make('installments_count')
                            ->label('Número de Parcelas')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->visible(fn (Get $get) => $get('payment_frequency') !== 'single'),

                        Forms\Components\TextInput::make('day_of_payment')
                            ->label('Dia de Vencimento')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->placeholder('Ex: 10'),

                        Forms\Components\DatePicker::make('first_payment_date')
                            ->label('Data do 1º Pagamento')
                            ->native(false),
                    ]),

                // Seção: Vigência
                Forms\Components\Section::make('Vigência')
                    ->description('Período de validade do contrato')
                    ->icon('heroicon-o-calendar')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Data de Término')
                            ->native(false)
                            ->afterOrEqual('start_date'),

                        Forms\Components\DatePicker::make('signature_date')
                            ->label('Data de Assinatura')
                            ->native(false),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renovação Automática')
                            ->live(),

                        Forms\Components\TextInput::make('renewal_months')
                            ->label('Meses de Renovação')
                            ->numeric()
                            ->default(12)
                            ->visible(fn (Get $get) => $get('auto_renew')),

                        Forms\Components\DatePicker::make('renewal_date')
                            ->label('Próxima Renovação')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('auto_renew')),
                    ]),

                // Seção: Reajuste
                Forms\Components\Section::make('Reajuste')
                    ->description('Configuração de reajuste anual')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('adjustment_index')
                            ->label('Índice de Reajuste')
                            ->options(Contract::getAdjustmentIndexOptions())
                            ->native(false),

                        Forms\Components\TextInput::make('adjustment_percentage')
                            ->label('% de Reajuste Fixo')
                            ->numeric()
                            ->suffix('%')
                            ->helperText('Usado quando índice é "Personalizado"'),

                        Forms\Components\DatePicker::make('next_adjustment_date')
                            ->label('Próximo Reajuste')
                            ->native(false),
                    ]),

                // Seção: Status
                Forms\Components\Section::make('Status e Assinatura')
                    ->icon('heroicon-o-check-badge')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Contract::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_signed')
                            ->label('Assinado')
                            ->live(),

                        Forms\Components\Select::make('signature_type')
                            ->label('Tipo de Assinatura')
                            ->options(Contract::getSignatureTypeOptions())
                            ->native(false)
                            ->visible(fn (Get $get) => $get('is_signed')),
                    ]),

                // Seção: Escopo
                Forms\Components\Section::make('Escopo e Condições')
                    ->icon('heroicon-o-document-check')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\RichEditor::make('scope_of_work')
                            ->label('Escopo do Trabalho')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'orderedList',
                            ]),

                        Forms\Components\Textarea::make('exclusions')
                            ->label('Exclusões (O que não está incluso)')
                            ->rows(3),

                        Forms\Components\Textarea::make('special_conditions')
                            ->label('Condições Especiais')
                            ->rows(3),
                    ]),

                // Seção: Documentos
                Forms\Components\Section::make('Documentos')
                    ->icon('heroicon-o-paper-clip')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Anexos')
                            ->multiple()
                            ->directory('contracts/attachments')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),

                        Forms\Components\FileUpload::make('signed_document_path')
                            ->label('Documento Assinado')
                            ->directory('contracts/signed')
                            ->acceptedFileTypes(['application/pdf']),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações Internas')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('')
                            ->rows(4)
                            ->placeholder('Notas internas sobre este contrato...'),
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

                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('fee_type')
                    ->label('Tipo Hon.')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Contract::getFeeTypeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'info',
                        'hourly' => 'warning',
                        'success' => 'success',
                        'hybrid' => 'primary',
                        'retainer' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Pago')
                    ->money('BRL')
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_value')
                    ->label('Saldo')
                    ->money('BRL')
                    ->color('warning')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Término')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->is_expiring_soon ? 'warning' : ($record->is_expired ? 'danger' : null))
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_signed')
                    ->label('Assinado')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Contract::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_signature' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
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
                    ->options(Contract::getStatusOptions()),

                Tables\Filters\SelectFilter::make('fee_type')
                    ->label('Tipo Honorário')
                    ->options(Contract::getFeeTypeOptions()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo Contrato')
                    ->options(Contract::getTypeOptions()),

                Tables\Filters\TernaryFilter::make('is_signed')
                    ->label('Assinado'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Vencendo em 30 dias')
                    ->query(fn (Builder $query) => $query->expiringSoon()),

                Tables\Filters\Filter::make('with_overdue_installments')
                    ->label('Com parcelas vencidas')
                    ->query(fn (Builder $query) => $query->withOverdueInstallments()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('activate')
                        ->label('Ativar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'pending_signature']))
                        ->requiresConfirmation()
                        ->modalHeading('Ativar Contrato')
                        ->modalDescription('Isso marcará o contrato como assinado e ativo, e gerará as parcelas automaticamente.')
                        ->action(fn ($record) => $record->activate()),

                    Tables\Actions\Action::make('suspend')
                        ->label('Suspender')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->suspend()),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['active', 'suspended', 'pending_signature']))
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo do Cancelamento')
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->cancel($data['reason'])),

                    Tables\Actions\Action::make('generateInstallments')
                        ->label('Gerar Parcelas')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->modalDescription('Isso irá regenerar as parcelas não pagas do contrato.')
                        ->action(fn ($record) => $record->generateInstallments()),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InstallmentsRelationManager::class,
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\TimeEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
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
        return ['uid', 'contract_number', 'title', 'client.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
