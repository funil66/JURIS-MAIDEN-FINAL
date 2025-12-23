<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Financeiro';
    
    protected static ?string $modelLabel = 'Transação';
    
    protected static ?string $pluralModelLabel = 'Transações';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tipo e Identificação')
                    ->columns(3)
                    ->schema([
                        Forms\Components\ToggleButtons::make('type')
                            ->label('Tipo')
                            ->options([
                                'income' => 'Receita',
                                'expense' => 'Despesa',
                            ])
                            ->icons([
                                'income' => 'heroicon-o-arrow-trending-up',
                                'expense' => 'heroicon-o-arrow-trending-down',
                            ])
                            ->colors([
                                'income' => 'success',
                                'expense' => 'danger',
                            ])
                            ->default('income')
                            ->inline()
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category')
                            ->label('Categoria')
                            ->options(fn (Get $get) => $get('type') === 'expense' 
                                ? Transaction::getExpenseCategories() 
                                : Transaction::getIncomeCategories()
                            )
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Transaction::getStatusOptions())
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Vinculação')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('service_id')
                            ->label('Serviço')
                            ->relationship('service', 'code')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Vincule a um serviço existente'),
                    ]),

                Forms\Components\Section::make('Valores')
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor Bruto')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->step(0.01)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('discount')
                            ->label('Desconto')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('fees')
                            ->label('Taxas')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->step(0.01)
                            ->helperText('Taxas bancárias')
                            ->live(onBlur: true),

                        Forms\Components\Placeholder::make('net_display')
                            ->label('Valor Líquido')
                            ->content(function (Get $get): string {
                                $net = floatval($get('amount') ?? 0) 
                                    - floatval($get('discount') ?? 0) 
                                    - floatval($get('fees') ?? 0);
                                return 'R$ ' . number_format($net, 2, ',', '.');
                            }),
                    ]),

                Forms\Components\Section::make('Datas')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Vencimento')
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Data do Pagamento')
                            ->native(false),

                        Forms\Components\DatePicker::make('competence_date')
                            ->label('Competência')
                            ->native(false)
                            ->helperText('Mês de referência'),
                    ]),

                Forms\Components\Section::make('Pagamento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Forma de Pagamento')
                            ->relationship('paymentMethod', 'name', fn (Builder $query) => $query->active()->ordered())
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('bank_reference')
                            ->label('Referência Bancária')
                            ->maxLength(255)
                            ->placeholder('ID da transação, comprovante, etc'),
                    ]),

                Forms\Components\Section::make('Parcelamento')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('installment_number')
                            ->label('Parcela Nº')
                            ->numeric()
                            ->placeholder('1'),

                        Forms\Components\TextInput::make('total_installments')
                            ->label('Total de Parcelas')
                            ->numeric()
                            ->placeholder('12'),

                        Forms\Components\TextInput::make('installment_group')
                            ->label('Grupo')
                            ->maxLength(255)
                            ->helperText('Identificador para agrupar parcelas'),
                    ]),

                Forms\Components\Section::make('Documentos e Notas')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nº Nota Fiscal')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_reconciled')
                            ->label('Conciliado')
                            ->helperText('Verificado no extrato bancário'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('ID copiado!')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'income' ? 'Receita' : 'Despesa')
                    ->color(fn (string $state): string => $state === 'income' ? 'success' : 'danger')
                    ->icon(fn (string $state): string => $state === 'income' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(function (string $state, $record): string {
                        $categories = $record->type === 'expense' 
                            ? Transaction::getExpenseCategories() 
                            : Transaction::getIncomeCategories();
                        return $categories[$state] ?? $state;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Pago em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Transaction::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Transaction::getStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Forma')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_reconciled')
                    ->label('✓')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('installment_label')
                    ->label('Parcela')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Transaction::getTypeOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Transaction::getStatusOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(array_merge(
                        Transaction::getIncomeCategories(),
                        Transaction::getExpenseCategories()
                    ))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->label('Forma de Pagamento')
                    ->relationship('paymentMethod', 'name'),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('overdue')
                    ->label('Vencidos')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->toggle(),

                Filter::make('this_month')
                    ->label('Este Mês')
                    ->query(fn (Builder $query): Builder => $query->thisMonth())
                    ->toggle()
                    ->default(),

                Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->where('competence_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->where('competence_date', '<=', $date));
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('mark_paid')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(fn ($record) => $record->update([
                            'status' => 'paid',
                            'paid_date' => now(),
                        ])),
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
            ->emptyStateHeading('Nenhuma transação')
            ->emptyStateDescription('Registre receitas e despesas.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::pending()->count();
        return $pending > 0 ? $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        return $overdue > 0 ? 'danger' : 'warning';
    }
}
