<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Contrato'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos')
                ->icon('heroicon-o-document-text'),

            'active' => Tab::make('Ativos')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(Contract::where('status', 'active')->count() ?: null)
                ->badgeColor('success'),

            'pending_signature' => Tab::make('Aguardando Assinatura')
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending_signature'))
                ->badge(Contract::where('status', 'pending_signature')->count() ?: null)
                ->badgeColor('warning'),

            'draft' => Tab::make('Rascunhos')
                ->icon('heroicon-o-document')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(Contract::where('status', 'draft')->count() ?: null)
                ->badgeColor('gray'),

            'expiring' => Tab::make('Vencendo')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->expiringSoon())
                ->badge(Contract::expiringSoon()->count() ?: null)
                ->badgeColor('warning'),

            'with_overdue' => Tab::make('Parcelas Vencidas')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->withOverdueInstallments())
                ->badge(Contract::withOverdueInstallments()->count() ?: null)
                ->badgeColor('danger'),

            'completed' => Tab::make('Concluídos')
                ->icon('heroicon-o-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
        ];
    }
}
