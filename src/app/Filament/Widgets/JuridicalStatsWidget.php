<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Process;
use App\Models\Diligence;
use App\Models\TimeEntry;
use App\Models\Contract;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class JuridicalStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Processos
        $activeProcesses = Process::active()->count();
        $urgentProcesses = Process::active()->where('is_urgent', true)->count();
        $processesThisMonth = Process::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Diligências
        $pendingDiligences = Diligence::pending()->count();
        $overdueDiligences = Diligence::where('status', 'pending')
            ->where('deadline', '<', now())
            ->count();
        $todayDiligences = Diligence::whereDate('scheduled_at', today())->count();

        // Prazos (andamentos com deadline)
        $upcomingDeadlines = \App\Models\Proceeding::where('is_deadline', true)
            ->where('deadline_completed', false)
            ->where('deadline_date', '>=', today())
            ->where('deadline_date', '<=', today()->addDays(7))
            ->count();
        
        $overdueDeadlines = \App\Models\Proceeding::where('is_deadline', true)
            ->where('deadline_completed', false)
            ->where('deadline_date', '<', today())
            ->count();

        // Time Tracking - Mês Atual
        $hoursThisMonth = TimeEntry::whereMonth('entry_date', now()->month)
            ->whereYear('entry_date', now()->year)
            ->sum('duration_minutes') / 60;

        $billableHours = TimeEntry::billable()
            ->whereMonth('entry_date', now()->month)
            ->whereYear('entry_date', now()->year)
            ->sum('duration_minutes') / 60;

        // Financeiro
        $activeContracts = Contract::active()->count();
        $monthlyContractValue = Contract::active()->sum('monthly_fee');

        $pendingInvoices = Invoice::pending()->sum('balance');
        $overdueInvoices = Invoice::overdue()->sum('balance');
        $receivedThisMonth = Invoice::paid()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('total');

        // Clientes
        $totalClients = Client::count();
        $clientsWithProcesses = Client::whereHas('processes')->count();

        return [
            // Linha 1: Processos e Diligências
            Stat::make('Processos Ativos', $activeProcesses)
                ->description($urgentProcesses > 0 ? $urgentProcesses . ' urgentes' : $processesThisMonth . ' novos este mês')
                ->descriptionIcon($urgentProcesses > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-scale')
                ->color($urgentProcesses > 0 ? 'danger' : 'primary')
                ->chart($this->getProcessesChart()),

            Stat::make('Diligências Pendentes', $pendingDiligences)
                ->description($overdueDiligences > 0 ? $overdueDiligences . ' atrasadas!' : $todayDiligences . ' para hoje')
                ->descriptionIcon($overdueDiligences > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clipboard-document-check')
                ->color($overdueDiligences > 0 ? 'danger' : 'warning'),

            Stat::make('Prazos Próximos', $upcomingDeadlines)
                ->description($overdueDeadlines > 0 ? $overdueDeadlines . ' vencidos!' : 'Próximos 7 dias')
                ->descriptionIcon($overdueDeadlines > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar')
                ->color($overdueDeadlines > 0 ? 'danger' : ($upcomingDeadlines > 5 ? 'warning' : 'success')),

            // Linha 2: Horas e Contratos
            Stat::make('Horas Trabalhadas (Mês)', number_format($hoursThisMonth, 1) . 'h')
                ->description(number_format($billableHours, 1) . 'h faturáveis')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info')
                ->chart($this->getHoursChart()),

            Stat::make('Contratos Ativos', $activeContracts)
                ->description('R$ ' . number_format($monthlyContractValue, 2, ',', '.') . '/mês')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),

            Stat::make('Clientes Ativos', $clientsWithProcesses)
                ->description($totalClients . ' total cadastrados')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            // Linha 3: Financeiro
            Stat::make('A Receber', 'R$ ' . number_format($pendingInvoices, 2, ',', '.'))
                ->description($overdueInvoices > 0 ? 'R$ ' . number_format($overdueInvoices, 2, ',', '.') . ' vencidos' : 'Faturas pendentes')
                ->descriptionIcon($overdueInvoices > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-currency-dollar')
                ->color($overdueInvoices > 0 ? 'danger' : 'warning'),

            Stat::make('Recebido (Mês)', 'R$ ' . number_format($receivedThisMonth, 2, ',', '.'))
                ->description('Faturas pagas')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }

    protected function getProcessesChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Process::whereDate('created_at', $date)->count();
        }
        return $data;
    }

    protected function getHoursChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = round(TimeEntry::whereDate('entry_date', $date)->sum('duration_minutes') / 60, 1);
        }
        return $data;
    }
}
