<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDeadlines extends ListRecords
{
    protected static string $resource = DeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Prazo'),

            Actions\Action::make('calculate')
                ->label('Calcular Prazo')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->url(route('filament.funil.resources.deadlines.calculate')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->badge(fn () => $this->getModel()::pending()->count())
                ->badgeColor('gray'),

            'overdue' => Tab::make('Vencidos')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(fn () => $this->getModel()::overdue()->count())
                ->badgeColor('danger'),

            'today' => Tab::make('Hoje')
                ->icon('heroicon-o-fire')
                ->modifyQueryUsing(fn (Builder $query) => $query->dueToday())
                ->badge(fn () => $this->getModel()::dueToday()->count())
                ->badgeColor('danger'),

            'this_week' => Tab::make('Esta Semana')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->dueSoon(7))
                ->badge(fn () => $this->getModel()::dueSoon(7)->count())
                ->badgeColor('warning'),

            'critical' => Tab::make('Críticos')
                ->icon('heroicon-o-bolt')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending()->critical())
                ->badge(fn () => $this->getModel()::pending()->critical()->count())
                ->badgeColor('danger'),

            'completed' => Tab::make('Cumpridos')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->completed())
                ->badge(fn () => $this->getModel()::completed()->count())
                ->badgeColor('success'),

            'missed' => Tab::make('Perdidos')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->missed())
                ->badge(fn () => $this->getModel()::missed()->count())
                ->badgeColor('danger'),
        ];
    }
}
