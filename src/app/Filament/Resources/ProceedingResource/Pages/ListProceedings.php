<?php

namespace App\Filament\Resources\ProceedingResource\Pages;

use App\Filament\Resources\ProceedingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProceedings extends ListRecords
{
    protected static string $resource = ProceedingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => $this->getModel()::count()),

            'pending' => Tab::make('Pendentes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'with_deadline' => Tab::make('Com Prazo')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('has_deadline', true)->where('deadline_completed', false))
                ->badge(fn () => $this->getModel()::where('has_deadline', true)->where('deadline_completed', false)->count())
                ->badgeColor('info'),

            'overdue' => Tab::make('Prazos Vencidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdueDeadlines())
                ->badge(fn () => $this->getModel()::overdueDeadlines()->count())
                ->badgeColor('danger'),

            'expiring' => Tab::make('Vencendo (5 dias)')
                ->modifyQueryUsing(fn (Builder $query) => $query->deadlinesExpiring(5))
                ->badge(fn () => $this->getModel()::deadlinesExpiring(5)->count())
                ->badgeColor('warning'),

            'requires_action' => Tab::make('Requer Ação')
                ->modifyQueryUsing(fn (Builder $query) => $query->requiresAction())
                ->badge(fn () => $this->getModel()::requiresAction()->count())
                ->badgeColor('primary'),
        ];
    }
}
