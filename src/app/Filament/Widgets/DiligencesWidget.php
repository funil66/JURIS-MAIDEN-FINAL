<?php

namespace App\Filament\Widgets;

use App\Models\Diligence;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DiligencesWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = '📋 Diligências da Semana';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Diligence::query()
                    ->with(['client', 'process', 'assignedUser'])
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->where(function ($query) {
                        $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                            ->orWhereBetween('deadline', [now()->startOfWeek(), now()->endOfWeek()])
                            ->orWhere(function ($q) {
                                $q->where('deadline', '<', now())
                                    ->whereIn('status', ['pending', 'in_progress']);
                            });
                    })
                    ->orderByRaw('CASE WHEN deadline < NOW() THEN 0 ELSE 1 END')
                    ->orderBy('deadline', 'asc')
                    ->orderBy('scheduled_at', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Diligência')
                    ->limit(25)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'citation' => 'danger',
                        'hearing' => 'warning',
                        'subpoena' => 'info',
                        'protocol' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'citation' => 'Citação',
                        'subpoena' => 'Intimação',
                        'hearing' => 'Audiência',
                        'protocol' => 'Protocolo',
                        'copy_extraction' => 'Cópias',
                        'research' => 'Pesquisa',
                        'meeting' => 'Reunião',
                        'travel' => 'Viagem',
                        default => 'Outro',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        'rescheduled' => 'Reagendada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Prazo')
                    ->date('d/m')
                    ->color(fn ($record) => $record->deadline && $record->deadline < now() ? 'danger' : 'gray')
                    ->description(fn ($record) => $record->deadline ? ($record->deadline < now() ? 'Vencida!' : $record->deadline->diffForHumans()) : null),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Diligence $record): string => route('filament.funil.resources.diligences.view', $record)),
            ])
            ->emptyStateHeading('Nenhuma diligência')
            ->emptyStateDescription('Sem diligências para esta semana')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->paginated(false);
    }
}
