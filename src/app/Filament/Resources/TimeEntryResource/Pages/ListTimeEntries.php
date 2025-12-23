<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\TimeEntry;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('quickEntry')
                ->label('Lançamento Rápido')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('process_id')
                        ->label('Processo')
                        ->relationship('process', 'title')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (\Filament\Forms\Set $set, ?string $state) {
                            if ($state) {
                                $process = \App\Models\Process::find($state);
                                if ($process) {
                                    $set('client_id', $process->client_id);
                                }
                            }
                        }),

                    \Filament\Forms\Components\Select::make('client_id')
                        ->label('Cliente')
                        ->relationship('client', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('description')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(1000),

                    \Filament\Forms\Components\Select::make('activity_type')
                        ->label('Tipo')
                        ->options(TimeEntry::getActivityTypeOptions())
                        ->default('other')
                        ->required(),

                    \Filament\Forms\Components\Select::make('duration_minutes')
                        ->label('Duração')
                        ->options(TimeEntry::getCommonDurations())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    TimeEntry::create([
                        ...$data,
                        'user_id' => auth()->id(),
                        'work_date' => now(),
                        'is_billable' => true,
                        'status' => 'draft',
                    ]);
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => $this->getModel()::count()),

            'today' => Tab::make('Hoje')
                ->modifyQueryUsing(fn (Builder $query) => $query->today())
                ->badge(fn () => $this->getModel()::today()->count())
                ->badgeColor('primary'),

            'this_week' => Tab::make('Esta Semana')
                ->modifyQueryUsing(fn (Builder $query) => $query->thisWeek())
                ->badge(fn () => $this->getModel()::thisWeek()->count())
                ->badgeColor('info'),

            'running' => Tab::make('Timer Ativo')
                ->modifyQueryUsing(fn (Builder $query) => $query->running())
                ->badge(fn () => $this->getModel()::running()->count())
                ->badgeColor('success'),

            'draft' => Tab::make('Rascunhos')
                ->modifyQueryUsing(fn (Builder $query) => $query->draft())
                ->badge(fn () => $this->getModel()::draft()->count())
                ->badgeColor('gray'),

            'pending_approval' => Tab::make('Pendente Aprovação')
                ->modifyQueryUsing(fn (Builder $query) => $query->pendingApproval())
                ->badge(fn () => $this->getModel()::pendingApproval()->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Aprovados')
                ->modifyQueryUsing(fn (Builder $query) => $query->approved())
                ->badge(fn () => $this->getModel()::approved()->count())
                ->badgeColor('success'),

            'unbilled' => Tab::make('Não Faturados')
                ->modifyQueryUsing(fn (Builder $query) => $query->unbilled())
                ->badge(fn () => $this->getModel()::unbilled()->count())
                ->badgeColor('warning'),
        ];
    }
}
