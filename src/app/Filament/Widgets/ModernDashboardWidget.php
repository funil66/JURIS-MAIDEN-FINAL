<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Diligence;
use App\Models\Proceeding;
use App\Models\Contract;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ModernDashboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.modern-dashboard-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function getStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Processos
        $totalProcesses = Process::active()->count();
        $newProcessesMonth = Process::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $urgentProcesses = Process::where('is_urgent', true)->where('status', 'active')->count();

        // Clientes
        $totalClients = Client::where('is_active', true)->count();
        $newClientsMonth = Client::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Financeiro
        $receivedMonth = Invoice::paid()
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $pendingInvoices = Invoice::pending()->sum('balance');
        $overdueInvoices = Invoice::overdue()->sum('balance');

        // Prazos e Diligências
        $upcomingDeadlines = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->whereBetween('deadline_date', [today(), today()->addDays(7)])
            ->count();
        $overdueDeadlines = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->where('deadline_date', '<', today())
            ->count();
        $pendingDiligences = Diligence::pending()->count();

        // Contratos
        $activeContracts = Contract::active()->count();
        $monthlyRevenue = Contract::active()->sum('monthly_fee');

        return [
            'processes' => [
                'total' => $totalProcesses,
                'new' => $newProcessesMonth,
                'urgent' => $urgentProcesses,
                'trend' => $this->calculateTrend('processes'),
            ],
            'clients' => [
                'total' => $totalClients,
                'new' => $newClientsMonth,
                'trend' => $this->calculateTrend('clients'),
            ],
            'financial' => [
                'received' => $receivedMonth,
                'pending' => $pendingInvoices,
                'overdue' => $overdueInvoices,
                'monthly_revenue' => $monthlyRevenue,
            ],
            'deadlines' => [
                'upcoming' => $upcomingDeadlines,
                'overdue' => $overdueDeadlines,
                'diligences' => $pendingDiligences,
            ],
            'contracts' => [
                'active' => $activeContracts,
            ],
        ];
    }

    protected function calculateTrend(string $type): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        switch ($type) {
            case 'processes':
                $current = Process::whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count();
                $previous = Process::whereMonth('created_at', $lastMonth->month)
                    ->whereYear('created_at', $lastMonth->year)
                    ->count();
                break;
            case 'clients':
                $current = Client::whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count();
                $previous = Client::whereMonth('created_at', $lastMonth->month)
                    ->whereYear('created_at', $lastMonth->year)
                    ->count();
                break;
            default:
                return ['direction' => 'stable', 'percentage' => 0];
        }

        if ($previous === 0) {
            return ['direction' => $current > 0 ? 'up' : 'stable', 'percentage' => 100];
        }

        $percentage = round((($current - $previous) / $previous) * 100, 1);

        return [
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'stable'),
            'percentage' => abs($percentage),
        ];
    }

    public function getRecentActivity(): array
    {
        $activities = collect();

        // Últimos processos
        $recentProcesses = Process::with('client')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($p) => [
                'type' => 'process',
                'icon' => 'heroicon-o-scale',
                'color' => 'indigo',
                'title' => "Novo processo: {$p->number}",
                'subtitle' => $p->client?->name ?? 'Cliente não vinculado',
                'time' => $p->created_at->diffForHumans(),
            ]);

        // Últimas faturas pagas
        $recentPayments = Invoice::paid()
            ->with('client')
            ->latest('payment_date')
            ->take(3)
            ->get()
            ->map(fn ($i) => [
                'type' => 'payment',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'emerald',
                'title' => 'Pagamento recebido: R$ ' . number_format($i->total, 2, ',', '.'),
                'subtitle' => $i->client?->name ?? 'Fatura avulsa',
                'time' => $i->payment_date?->diffForHumans() ?? $i->updated_at->diffForHumans(),
            ]);

        // Últimos andamentos
        $recentProceedings = Proceeding::with('process')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($p) => [
                'type' => 'proceeding',
                'icon' => 'heroicon-o-document-text',
                'color' => 'sky',
                'title' => $p->title ?? 'Andamento registrado',
                'subtitle' => $p->process?->number ?? 'Processo não identificado',
                'time' => $p->created_at->diffForHumans(),
            ]);

        return $activities
            ->merge($recentProcesses)
            ->merge($recentPayments)
            ->merge($recentProceedings)
            ->sortByDesc('time')
            ->take(6)
            ->values()
            ->toArray();
    }

    public function getQuickActions(): array
    {
        return [
            [
                'label' => 'Novo Processo',
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'indigo',
                'url' => route('filament.funil.resources.processes.create'),
            ],
            [
                'label' => 'Novo Cliente',
                'icon' => 'heroicon-o-user-plus',
                'color' => 'emerald',
                'url' => route('filament.funil.resources.clients.create'),
            ],
            [
                'label' => 'Nova Fatura',
                'icon' => 'heroicon-o-document-currency-dollar',
                'color' => 'amber',
                'url' => route('filament.funil.resources.invoices.create'),
            ],
            [
                'label' => 'Agendar Diligência',
                'icon' => 'heroicon-o-calendar',
                'color' => 'sky',
                'url' => route('filament.funil.resources.diligences.create'),
            ],
        ];
    }
}
