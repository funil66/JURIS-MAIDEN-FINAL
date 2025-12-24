<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use App\Models\Proceeding;
use App\Models\Diligence;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\ContractInstallment;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class CriticalAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.critical-alerts-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function getAlerts(): Collection
    {
        $alerts = collect();

        // 1. Prazos vencidos
        $overdueDeadlines = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->where('deadline_date', '<', today())
            ->count();

        if ($overdueDeadlines > 0) {
            $alerts->push([
                'type' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Prazos Vencidos',
                'message' => "{$overdueDeadlines} prazo(s) processual(is) vencido(s)!",
                'link' => route('filament.funil.pages.juridical-dashboard'),
                'priority' => 1,
            ]);
        }

        // 2. Prazos para hoje
        $todayDeadlines = Proceeding::where('has_deadline', true)
            ->where('deadline_completed', false)
            ->whereDate('deadline_date', today())
            ->count();

        if ($todayDeadlines > 0) {
            $alerts->push([
                'type' => 'warning',
                'icon' => 'heroicon-o-fire',
                'title' => 'Prazos Hoje',
                'message' => "{$todayDeadlines} prazo(s) para HOJE!",
                'link' => route('filament.funil.pages.juridical-dashboard'),
                'priority' => 2,
            ]);
        }

        // 3. Diligências atrasadas
        $overdueDiligences = Diligence::where('status', 'pending')
            ->where('scheduled_date', '<', today())
            ->count();

        if ($overdueDiligences > 0) {
            $alerts->push([
                'type' => 'danger',
                'icon' => 'heroicon-o-clipboard-document-check',
                'title' => 'Diligências Atrasadas',
                'message' => "{$overdueDiligences} diligência(s) com prazo vencido!",
                'link' => route('filament.funil.resources.diligences.index'),
                'priority' => 3,
            ]);
        }

        // 4. Faturas vencidas
        $overdueInvoices = Invoice::overdue()->count();
        $overdueValue = Invoice::overdue()->sum('balance');

        if ($overdueInvoices > 0) {
            $alerts->push([
                'type' => 'danger',
                'icon' => 'heroicon-o-banknotes',
                'title' => 'Faturas Vencidas',
                'message' => "{$overdueInvoices} fatura(s) vencida(s) - R$ " . number_format($overdueValue, 2, ',', '.'),
                'link' => route('filament.funil.resources.invoices.index'),
                'priority' => 4,
            ]);
        }

        // 5. Parcelas de contrato vencidas
        $overdueInstallments = ContractInstallment::where('status', 'pending')
            ->where('due_date', '<', today())
            ->count();

        if ($overdueInstallments > 0) {
            $alerts->push([
                'type' => 'warning',
                'icon' => 'heroicon-o-document-text',
                'title' => 'Parcelas Vencidas',
                'message' => "{$overdueInstallments} parcela(s) de contrato vencida(s)!",
                'link' => route('filament.funil.resources.contracts.index'),
                'priority' => 5,
            ]);
        }

        // 6. Processos urgentes sem atividade recente
        $urgentNoActivity = Process::where('is_urgent', true)
            ->where('status', 'active')
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        if ($urgentNoActivity > 0) {
            $alerts->push([
                'type' => 'warning',
                'icon' => 'heroicon-o-clock',
                'title' => 'Processos Urgentes Parados',
                'message' => "{$urgentNoActivity} processo(s) urgente(s) sem atividade há mais de 7 dias!",
                'link' => route('filament.funil.resources.processes.index'),
                'priority' => 6,
            ]);
        }

        // 7. Contratos a vencer (próximos 30 dias)
        $expiringContracts = Contract::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>=', today())
            ->count();

        if ($expiringContracts > 0) {
            $alerts->push([
                'type' => 'info',
                'icon' => 'heroicon-o-document-text',
                'title' => 'Contratos a Vencer',
                'message' => "{$expiringContracts} contrato(s) vencem nos próximos 30 dias.",
                'link' => route('filament.funil.resources.contracts.index'),
                'priority' => 7,
            ]);
        }

        // 8. Audiências esta semana
        $weekHearings = Diligence::where('type', 'hearing')
            ->where('status', 'pending')
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        if ($weekHearings > 0) {
            $alerts->push([
                'type' => 'info',
                'icon' => 'heroicon-o-calendar',
                'title' => 'Audiências da Semana',
                'message' => "{$weekHearings} audiência(s) agendada(s) para esta semana.",
                'link' => route('filament.funil.resources.diligences.index'),
                'priority' => 8,
            ]);
        }

        return $alerts->sortBy('priority');
    }

    public function hasAlerts(): bool
    {
        return $this->getAlerts()->isNotEmpty();
    }

    public function getCriticalCount(): int
    {
        return $this->getAlerts()->where('type', 'danger')->count();
    }

    public function getWarningCount(): int
    {
        return $this->getAlerts()->where('type', 'warning')->count();
    }
}
