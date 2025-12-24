<?php

namespace App\Filament\Resources\ReportTemplateResource\Pages;

use App\Filament\Resources\ReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReportTemplates extends ListRecords
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('advanced_reports')
                ->label('Gerar Relatório')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(route('filament.funil.pages.advanced-reports')),

            Actions\CreateAction::make()
                ->label('Novo Template'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Resources\Components\Tab::make('Todos')
                ->badge($this->getAllCount()),

            'favorites' => \Filament\Resources\Components\Tab::make('Favoritos')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_favorite', true))
                ->badge($this->getFavoritesCount()),

            'processes' => \Filament\Resources\Components\Tab::make('Processos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'processes'))
                ->badge($this->getTypeCount('processes')),

            'deadlines' => \Filament\Resources\Components\Tab::make('Prazos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'deadlines'))
                ->badge($this->getTypeCount('deadlines')),

            'financial' => \Filament\Resources\Components\Tab::make('Financeiro')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', ['invoices', 'financial', 'contracts']))
                ->badge($this->getFinancialCount()),

            'productivity' => \Filament\Resources\Components\Tab::make('Produtividade')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', ['time_entries', 'productivity']))
                ->badge($this->getProductivityCount()),
        ];
    }

    protected function getAllCount(): int
    {
        return $this->getModel()::query()
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->count();
    }

    protected function getFavoritesCount(): int
    {
        return $this->getModel()::query()
            ->where('is_favorite', true)
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->count();
    }

    protected function getTypeCount(string $type): int
    {
        return $this->getModel()::query()
            ->where('type', $type)
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->count();
    }

    protected function getFinancialCount(): int
    {
        return $this->getModel()::query()
            ->whereIn('type', ['invoices', 'financial', 'contracts'])
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->count();
    }

    protected function getProductivityCount(): int
    {
        return $this->getModel()::query()
            ->whereIn('type', ['time_entries', 'productivity'])
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('is_public', true);
            })
            ->count();
    }
}
