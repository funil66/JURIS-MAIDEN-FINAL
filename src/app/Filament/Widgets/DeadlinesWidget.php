<?php

namespace App\Filament\Widgets;

use App\Models\Proceeding;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DeadlinesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '📅 Prazos e Audiências';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Proceeding::query()
                    ->with(['process.client'])
                    ->where('is_deadline', true)
                    ->where('deadline_completed', false)
                    ->where('deadline_date', '>=', today()->subDays(7)) // Include overdue up to 7 days
                    ->orderBy('deadline_date', 'asc')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $this->getDeadlineColor($record->deadline_date))
                    ->weight('bold')
                    ->description(fn ($record) => $this->getDeadlineDescription($record->deadline_date)),

                Tables\Columns\TextColumn::make('process.uid')
                    ->label('Processo')
                    ->badge()
                    ->color('gray')
                    ->url(fn ($record) => $record->process ? route('filament.funil.resources.processes.view', $record->process) : null),

                Tables\Columns\TextColumn::make('process.cnj_number')
                    ->label('Número CNJ')
                    ->limit(25)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Descrição')
                    ->limit(40)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'hearing' => 'danger',
                        'decision' => 'warning',
                        'subpoena' => 'info',
                        'appeal' => 'primary',
                        'sentence' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'distribution' => 'Distribuição',
                        'citation' => 'Citação',
                        'subpoena' => 'Intimação',
                        'hearing' => 'Audiência',
                        'decision' => 'Decisão',
                        'sentence' => 'Sentença',
                        'appeal' => 'Recurso',
                        'transit' => 'Trânsito',
                        default => 'Outro',
                    }),

                Tables\Columns\TextColumn::make('process.client.name')
                    ->label('Cliente')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('status_icon')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $this->getStatusIcon($record->deadline_date))
                    ->icon(fn ($state) => $state)
                    ->color(fn ($record) => $this->getDeadlineColor($record->deadline_date)),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar Prazo como Concluído')
                    ->modalDescription('Tem certeza que deseja marcar este prazo como concluído?')
                    ->action(fn (Proceeding $record) => $record->update(['deadline_completed' => true])),

                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Proceeding $record) => $record->process 
                        ? route('filament.funil.resources.processes.view', $record->process) 
                        : null),
            ])
            ->emptyStateHeading('Nenhum prazo pendente')
            ->emptyStateDescription('Todos os prazos estão em dia!')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }

    protected function getDeadlineColor(?Carbon $date): string
    {
        if (!$date) return 'gray';
        
        $days = now()->startOfDay()->diffInDays($date, false);
        
        if ($days < 0) return 'danger'; // Vencido
        if ($days === 0) return 'danger'; // Hoje
        if ($days <= 2) return 'warning'; // Até 2 dias
        if ($days <= 5) return 'info'; // Até 5 dias
        return 'success'; // Mais de 5 dias
    }

    protected function getDeadlineDescription(?Carbon $date): string
    {
        if (!$date) return '';
        
        $days = now()->startOfDay()->diffInDays($date, false);
        
        if ($days < 0) return abs($days) . ' dia(s) vencido!';
        if ($days === 0) return 'HOJE!';
        if ($days === 1) return 'Amanhã';
        if ($days <= 7) return "Em $days dias";
        return $date->diffForHumans();
    }

    protected function getStatusIcon(?Carbon $date): string
    {
        if (!$date) return 'heroicon-o-question-mark-circle';
        
        $days = now()->startOfDay()->diffInDays($date, false);
        
        if ($days < 0) return 'heroicon-o-exclamation-triangle';
        if ($days === 0) return 'heroicon-o-fire';
        if ($days <= 2) return 'heroicon-o-clock';
        return 'heroicon-o-calendar';
    }
}
