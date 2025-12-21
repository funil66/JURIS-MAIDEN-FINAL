<?php

namespace App\Filament\ClientPanel\Pages;

use App\Models\Service;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MyServices extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Meus Serviços';
    protected static ?string $title = 'Meus Serviços';
    protected static string $view = 'filament.client-panel.pages.my-services';
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $clientId = Auth::guard('client')->id();

        return $table
            ->query(Service::query()->where('client_id', $clientId))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Nº Ordem')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => '#' . str_pad($state, 5, '0', STR_PAD_LEFT)),

                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serviceType.name')
                    ->label('Tipo')
                    ->sortable(),

                TextColumn::make('process_number')
                    ->label('Processo')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->process_number),

                TextColumn::make('court')
                    ->label('Comarca')
                    ->searchable()
                    ->limit(15),

                TextColumn::make('scheduled_datetime')
                    ->label('Data Agendada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Service::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'agendado' => 'info',
                        'em_andamento' => 'primary',
                        'concluido' => 'success',
                        'cancelado' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Service::getPaymentStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'pago' => 'success',
                        'parcial' => 'warning',
                        'pendente' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Service::getStatusOptions()),

                SelectFilter::make('payment_status')
                    ->label('Pagamento')
                    ->options(Service::getPaymentStatusOptions()),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading(fn (Service $record) => "Serviço {$record->code}")
                    ->modalContent(fn (Service $record) => view('filament.client-panel.partials.service-details', ['service' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
