<?php

namespace App\Filament\Resources\DiligenceResource\Pages;

use App\Filament\Resources\DiligenceResource;
use App\Models\Diligence;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDiligences extends ListRecords
{
    protected static string $resource = DiligenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->badge(fn () => $this->getModel()::count()),

            'today' => Tab::make('Hoje')
                ->modifyQueryUsing(fn (Builder $query) => $query->today()->whereIn('status', Diligence::getActiveStatuses()))
                ->badge(fn () => $this->getModel()::today()->whereIn('status', Diligence::getActiveStatuses())->count())
                ->badgeColor('primary'),

            'this_week' => Tab::make('Esta Semana')
                ->modifyQueryUsing(fn (Builder $query) => $query->thisWeek()->whereIn('status', Diligence::getActiveStatuses()))
                ->badge(fn () => $this->getModel()::thisWeek()->whereIn('status', Diligence::getActiveStatuses())->count())
                ->badgeColor('info'),

            'overdue' => Tab::make('Atrasadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(fn () => $this->getModel()::overdue()->count())
                ->badgeColor('danger'),

            'pending' => Tab::make('Pendentes')
                ->modifyQueryUsing(fn (Builder $query) => $query->pending())
                ->badge(fn () => $this->getModel()::pending()->count())
                ->badgeColor('warning'),

            'in_progress' => Tab::make('Em Execução')
                ->modifyQueryUsing(fn (Builder $query) => $query->inProgress())
                ->badge(fn () => $this->getModel()::inProgress()->count())
                ->badgeColor('primary'),

            'completed' => Tab::make('Concluídas')
                ->modifyQueryUsing(fn (Builder $query) => $query->completed())
                ->badge(fn () => $this->getModel()::completed()->count())
                ->badgeColor('success'),

            'not_reimbursed' => Tab::make('A Reembolsar')
                ->modifyQueryUsing(fn (Builder $query) => $query->notReimbursed())
                ->badge(fn () => $this->getModel()::notReimbursed()->count())
                ->badgeColor('warning'),
        ];
    }
}
