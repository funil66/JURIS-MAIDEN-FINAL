<?php

namespace App\Filament\ClientPanel\Pages;

use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class MyTransactions extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Financeiro';
    protected static ?string $title = 'Extrato Financeiro';
    protected static string $view = 'filament.client-panel.pages.my-transactions';
    protected static ?int $navigationSort = 5;

    public $totalPending = 0;
    public $totalPaid = 0;

    public function mount(): void
    {
        $clientId = Auth::guard('client')->id();

        $this->totalPending = Transaction::where('client_id', $clientId)
            ->where('type', 'income')
            ->where('status', 'pending')
            ->sum('amount');

        $this->totalPaid = Transaction::where('client_id', $clientId)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function table(Table $table): Table
    {
        $clientId = Auth::guard('client')->id();

        return $table
            ->query(
                Transaction::query()
                    ->where('client_id', $clientId)
                    ->where('type', 'income') // Apenas cobranças ao cliente
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('service.code')
                    ->label('Serviço')
                    ->searchable(),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->status === 'pending' ? 'danger' : null),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendente',
                        'completed' => 'Pago',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Forma de Pagamento')
                    ->formatStateUsing(fn ($state) => Transaction::getPaymentMethodOptions()[$state] ?? $state)
                    ->visible(fn ($record) => $record->status === 'completed'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'completed' => 'Pago',
                        'cancelled' => 'Cancelado',
                    ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
