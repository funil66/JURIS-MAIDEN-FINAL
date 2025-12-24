<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopClientsWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = '🏆 Top Clientes (Faturamento)';

    protected static ?string $pollingInterval = '120s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Client::query()
                    ->withSum(['invoices' => fn ($q) => $q->whereIn('status', ['paid', 'partial'])], 'total')
                    ->withCount(['processes' => fn ($q) => $q->where('status', 'active')])
                    ->having('invoices_sum_total', '>', 0)
                    ->orderByDesc('invoices_sum_total')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->limit(25)
                    ->searchable()
                    ->description(fn ($record) => $record->type === 'pj' ? $record->company_name : null),

                Tables\Columns\TextColumn::make('processes_count')
                    ->label('Processos')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('invoices_sum_total')
                    ->label('Faturado')
                    ->money('BRL')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Client $record): string => route('filament.funil.resources.clients.view', $record)),
            ])
            ->emptyStateHeading('Sem faturamento')
            ->emptyStateDescription('Nenhum cliente com faturas pagas')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->paginated(false);
    }
}
