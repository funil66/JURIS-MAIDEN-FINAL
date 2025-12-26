<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Fatura'),

            Actions\Action::make('createFromTimeEntries')
                ->label('Faturar Horas')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->url(route('filament.funil.resources.invoices.create-from-time')),
        ];
    }

    protected function getTableContentView(): ?View
    {
        return view('filament.resources.invoices.grid');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->icon('heroicon-o-document-currency-dollar'),

            'pending' => Tab::make('Pendentes')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(Invoice::where('status', 'pending')->count() ?: null)
                ->badgeColor('warning'),

            'overdue' => Tab::make('Vencidas')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue())
                ->badge(Invoice::overdue()->count() ?: null)
                ->badgeColor('danger'),

            'partial' => Tab::make('Parciais')
                ->icon('heroicon-o-arrow-path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'partial'))
                ->badge(Invoice::where('status', 'partial')->count() ?: null)
                ->badgeColor('info'),

            'paid' => Tab::make('Pagas')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid')),

            'draft' => Tab::make('Rascunhos')
                ->icon('heroicon-o-document')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(Invoice::where('status', 'draft')->count() ?: null)
                ->badgeColor('gray'),

            'this_month' => Tab::make('Este Mês')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->thisMonth()),

            'cancelled' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}
