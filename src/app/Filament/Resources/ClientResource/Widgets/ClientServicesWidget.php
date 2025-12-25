<?php

namespace App\Filament\Resources\ClientResource\Widgets;

use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class ClientServicesWidget extends BaseWidget
{
    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = '📋 Serviços do Cliente';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->where('client_id', $this->record->id)
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('#')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Tipo')
                    ->badge(),

                Tables\Columns\TextColumn::make('process_number')
                    ->label('Processo')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scheduled_datetime')
                    ->label('Agendamento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Service::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => Service::getStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Service::getPaymentStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => Service::getPaymentStatusColors()[$state] ?? 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Service::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.funil.resources.services.edit', $record)),
            ])
            ->emptyStateHeading('Nenhum serviço cadastrado')
            ->emptyStateDescription('Este cliente ainda não possui serviços.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->paginated([5, 10, 25]);
    }
}
