<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Models\ContractInstallment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Parcelas';

    protected static ?string $recordTitleAttribute = 'description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('installment_number')
                            ->label('Nº Parcela')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Descrição')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => 
                                $set('final_amount', 
                                    (float) $state - (float) ($get('discount') ?? 0) + 
                                    (float) ($get('interest') ?? 0) + (float) ($get('fine') ?? 0)
                                )
                            ),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Vencimento')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('discount')
                            ->label('Desconto')
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

                        Forms\Components\TextInput::make('final_amount')
                            ->label('Valor Final')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ContractInstallment::getStatusOptions())
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma Pagamento')
                            ->options([
                                'pix' => 'PIX',
                                'transfer' => 'Transferência',
                                'credit_card' => 'Cartão',
                                'boleto' => 'Boleto',
                                'cash' => 'Dinheiro',
                            ])
                            ->native(false),

                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Data Pagamento')
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('installment_number')
                    ->label('Nº')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(20),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->status === 'pending' && $record->due_date->isPast() 
                            ? 'danger' 
                            : ($record->status === 'pending' && $record->due_date->diffInDays(now()) <= 7 
                                ? 'warning' 
                                : null)
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount')
                    ->label('Desc.')
                    ->money('BRL')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('interest')
                    ->label('Juros')
                    ->money('BRL')
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('fine')
                    ->label('Multa')
                    ->money('BRL')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Valor Final')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('paid_date')
                    ->label('Pago em')
                    ->date('d/m/Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (ContractInstallment::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'renegotiated' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContractInstallment::getStatusOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Calcular valor final
                        $data['final_amount'] = (float) $data['amount'] 
                            - (float) ($data['discount'] ?? 0) 
                            + (float) ($data['interest'] ?? 0) 
                            + (float) ($data['fine'] ?? 0);
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('markPaid')
                    ->label('Pagar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'overdue']))
                    ->form([
                        Forms\Components\DatePicker::make('paid_date')
                            ->label('Data Pagamento')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Forma')
                            ->options([
                                'pix' => 'PIX',
                                'transfer' => 'Transferência',
                                'credit_card' => 'Cartão',
                                'boleto' => 'Boleto',
                                'cash' => 'Dinheiro',
                            ]),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'paid',
                            'paid_date' => $data['paid_date'],
                            'payment_method' => $data['payment_method'] ?? $record->payment_method,
                        ]);
                        $this->ownerRecord->updateTotals();
                    }),

                Tables\Actions\Action::make('applyFees')
                    ->label('Aplicar Juros/Multa')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'overdue')
                    ->action(fn ($record) => $record->applyLateFees()),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('installment_number');
    }
}
