<?php

namespace App\Filament\Resources\ClientResource\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ClientTransactionsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '💰 Transações Financeiras';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('client_id', $this->record->id)
                    ->orderBy('due_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'income' ? '📈 Receita' : '📉 Despesa')
                    ->color(fn (?string $state): string => $state === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Forma Pagamento')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'paid' => '✅ Pago',
                        'pending' => '⏳ Pendente',
                        'cancelled' => '❌ Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
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
                        'paid' => 'Pago',
                        'pending' => 'Pendente',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.funil.resources.transactions.edit', $record)),
            ])
            ->emptyStateHeading('Nenhuma transação registrada')
            ->emptyStateDescription('Este cliente ainda não possui transações financeiras.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->paginated([5, 10, 25]);
    }
}
