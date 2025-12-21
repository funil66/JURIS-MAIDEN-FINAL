<?php

namespace App\Filament\ClientPanel\Pages;

use App\Models\Event;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class MyEvents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Minha Agenda';
    protected static ?string $title = 'Minha Agenda';
    protected static string $view = 'filament.client-panel.pages.my-events';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        $clientId = Auth::guard('client')->id();

        return $table
            ->query(Event::query()->where('client_id', $clientId))
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Event::getTypeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'hearing' => 'danger',
                        'deadline' => 'warning',
                        'meeting' => 'info',
                        'task' => 'success',
                        'reminder' => 'purple',
                        'appointment' => 'cyan',
                        default => 'gray',
                    }),

                TextColumn::make('starts_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (Event $record): string => $record->all_day ? 'Dia inteiro' : ($record->ends_at ? 'até ' . $record->ends_at->format('H:i') : '')),

                TextColumn::make('location')
                    ->label('Local')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->location_address),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Event::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('service.code')
                    ->label('Serviço')
                    ->url(fn (Event $record): ?string => $record->service_id 
                        ? route('filament.client.pages.my-services') 
                        : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Event::getTypeOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Event::getStatusOptions()),
            ])
            ->defaultSort('starts_at', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
