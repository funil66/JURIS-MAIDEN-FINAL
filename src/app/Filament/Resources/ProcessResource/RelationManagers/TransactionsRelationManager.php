<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transações Financeiras';

    protected static ?string $recordTitleAttribute = 'description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'income' => 'Receita',
                                'expense' => 'Despesa',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->default(fn () => $this->ownerRecord->client_id)
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('category')
                            ->label('Categoria')
                            ->options(fn (Forms\Get $get) => match ($get('type')) {
                                'income' => [
                                    'honorarios' => 'Honorários',
                                    'custas_ressarcidas' => 'Custas Ressarcidas',
                                    'exito' => 'Êxito',
                                    'consultoria' => 'Consultoria',
                                    'outros_receita' => 'Outros',
                                ],
                                'expense' => [
                                    'custas_judiciais' => 'Custas Judiciais',
                                    'diligencias' => 'Diligências',
                                    'copias_autenticacoes' => 'Cópias/Autenticações',
                                    'correios' => 'Correios',
                                    'viagens' => 'Viagens',
                                    'peritos' => 'Peritos',
                                    'outros_despesa' => 'Outros',
                                ],
                                default => [],
                            })
                            ->native(false)
                            ->searchable(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->minValue(0.01),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Data')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Forma de Pagamento')
                            ->relationship('paymentMethod', 'name')
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('service_id')
                            ->label('Serviço Relacionado')
                            ->relationship(
                                'service',
                                'title',
                                fn (Builder $query) => $query->where('process_id', $this->ownerRecord->id)
                            )
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder('Nenhum'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'paid' => 'Pago',
                                'cancelled' => 'Cancelado',
                                'refunded' => 'Estornado',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('Reembolsável pelo Cliente')
                            ->default(false)
                            ->helperText('Marque se esta despesa deve ser cobrada do cliente'),

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

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                        default => $state,
                    } : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'honorarios' => 'Honorários',
                        'custas_ressarcidas' => 'Custas Ressarcidas',
                        'exito' => 'Êxito',
                        'consultoria' => 'Consultoria',
                        'outros_receita' => 'Outros',
                        'custas_judiciais' => 'Custas Judiciais',
                        'diligencias' => 'Diligências',
                        'copias_autenticacoes' => 'Cópias/Autenticações',
                        'correios' => 'Correios',
                        'viagens' => 'Viagens',
                        'peritos' => 'Peritos',
                        'outros_despesa' => 'Outros',
                        default => $state ?? '-',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Estornado',
                        default => $state,
                    } : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('Reemb.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Forma Pgto')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'income' => 'Receita',
                        'expense' => 'Despesa',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Estornado',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['client_id'])) {
                            $data['client_id'] = $this->ownerRecord->client_id;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.transactions.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
