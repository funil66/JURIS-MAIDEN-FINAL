<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Service;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingEvents extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Próximos Compromissos';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Event::query()
                    ->upcoming()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label(''),

                Tables\Columns\TextColumn::make('title')
                    ->label('Evento')
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Event::getTypeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match($state) {
                        'hearing' => 'danger',
                        'deadline' => 'warning',
                        'meeting' => 'info',
                        'task' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->limit(20)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Local')
                    ->limit(25)
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Event $record): string => route('filament.funil.resources.events.edit', $record)),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nenhum evento próximo')
            ->emptyStateDescription('Você não tem compromissos agendados.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
