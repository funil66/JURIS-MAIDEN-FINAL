<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingServices extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Serviços Pendentes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->inProgress()
                    ->orderBy('deadline_date')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->limit(25),

                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Tipo')
                    ->badge(),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Service::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Service::getStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Service::getPaymentStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Service::getPaymentStatusColors()[$state] ?? 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Service $record): string => route('filament.funil.resources.services.edit', $record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nenhum serviço pendente')
            ->emptyStateDescription('Todos os serviços foram concluídos!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
