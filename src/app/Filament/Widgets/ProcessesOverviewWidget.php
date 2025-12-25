<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProcessesOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Processos Recentes';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Process::query()
                    ->with(['client', 'responsibleUser'])
                    ->orderBy('updated_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('cnj_number')
                    ->label('Número CNJ')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->cnj_number),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->limit(20)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'gray' => 'archived',
                        'info' => fn ($state) => in_array($state, ['closed_won', 'closed_settled']),
                        'danger' => 'closed_lost',
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'active' => 'Ativo',
                        'suspended' => 'Suspenso',
                        'archived' => 'Arquivado',
                        'closed_won' => 'Ganho',
                        'closed_lost' => 'Perdido',
                        'closed_settled' => 'Acordo',
                        default => $state,
                    } : '-'),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'knowledge' => 'info',
                        'execution' => 'warning',
                        'appeal' => 'danger',
                        'precautionary' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'knowledge' => 'Conhecimento',
                        'execution' => 'Execução',
                        'appeal' => 'Recursal',
                        'precautionary' => 'Cautelar',
                        default => $state,
                    } : '-'),

                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('Urgente')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('case_value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Process $record): string => route('filament.funil.resources.processes.view', $record))
                    ->openUrlInNewTab(false),
            ])
            ->paginated(false);
    }
}
