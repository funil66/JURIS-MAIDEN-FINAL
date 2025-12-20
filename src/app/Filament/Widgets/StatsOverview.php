<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Event;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Serviços
        $pendingServices = Service::whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();
        $overdueServices = Service::overdue()->count();
        $completedThisMonth = Service::where('status', 'completed')
            ->whereMonth('completion_date', now()->month)
            ->whereYear('completion_date', now()->year)
            ->count();

        // Financeiro
        $pendingPayment = Service::whereIn('payment_status', ['pending', 'partial'])
            ->sum('total_price');
        $receivedThisMonth = Service::where('payment_status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->sum('total_price');

        // Eventos
        $todayEvents = Event::today()->count();
        $weekEvents = Event::thisWeek()->count();

        return [
            Stat::make('Serviços Pendentes', $pendingServices)
                ->description($overdueServices > 0 ? $overdueServices . ' atrasados!' : 'Em andamento')
                ->descriptionIcon($overdueServices > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock')
                ->color($overdueServices > 0 ? 'danger' : 'warning')
                ->chart([7, 4, 6, 8, 5, 3, $pendingServices]),

            Stat::make('Concluídos (Mês)', $completedThisMonth)
                ->description('Serviços finalizados')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Clientes', Client::active()->count())
                ->description(Client::count() . ' total cadastrados')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('A Receber', 'R$ ' . number_format($pendingPayment, 2, ',', '.'))
                ->description('Pagamentos pendentes')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color($pendingPayment > 0 ? 'warning' : 'success'),

            Stat::make('Recebido (Mês)', 'R$ ' . number_format($receivedThisMonth, 2, ',', '.'))
                ->description('Este mês')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Eventos Hoje', $todayEvents)
                ->description($weekEvents . ' esta semana')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color($todayEvents > 0 ? 'info' : 'gray'),
        ];
    }
}
