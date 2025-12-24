<?php

namespace App\Filament\Widgets;

use App\Models\TimeEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TimeTrackingWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = '⏱️ Horas Trabalhadas Hoje';

    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TimeEntry::query()
                    ->with(['user', 'client', 'process'])
                    ->whereDate('entry_date', today())
                    ->orderBy('start_time', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->limit(15),

                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duração')
                    ->getStateUsing(fn ($record) => $this->formatDuration($record->duration_minutes))
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Atividade')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'hearing' => 'danger',
                        'meeting' => 'warning',
                        'drafting' => 'info',
                        'research' => 'primary',
                        'review' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'hearing' => 'Audiência',
                        'meeting' => 'Reunião',
                        'drafting' => 'Redação',
                        'research' => 'Pesquisa',
                        'review' => 'Revisão',
                        'call' => 'Ligação',
                        'email' => 'E-mail',
                        'travel' => 'Deslocamento',
                        'court' => 'Fórum',
                        default => 'Outro',
                    }),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('Fat.')
                    ->boolean()
                    ->trueIcon('heroicon-o-currency-dollar')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'billed' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Rascunho',
                        'submitted' => 'Enviado',
                        'approved' => 'Aprovado',
                        'billed' => 'Faturado',
                        'rejected' => 'Rejeitado',
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TimeEntry $record): string => route('filament.funil.resources.time-entries.view', $record)),
            ])
            ->emptyStateHeading('Nenhum lançamento hoje')
            ->emptyStateDescription('Registre suas horas trabalhadas')
            ->emptyStateIcon('heroicon-o-clock')
            ->paginated(false);
    }

    protected function formatDuration(?int $minutes): string
    {
        if (!$minutes) return '0min';
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        }
        
        return $mins . 'min';
    }

    public function getTableHeading(): string
    {
        $totalToday = TimeEntry::whereDate('entry_date', today())->sum('duration_minutes');
        $formatted = $this->formatDuration($totalToday);
        
        return "⏱️ Horas Trabalhadas Hoje: {$formatted}";
    }
}
