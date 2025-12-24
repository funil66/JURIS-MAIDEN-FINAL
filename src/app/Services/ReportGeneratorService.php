<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Deadline;
use App\Models\Diligence;
use App\Models\GeneratedReport;
use App\Models\Invoice;
use App\Models\Process;
use App\Models\ReportTemplate;
use App\Models\Service;
use App\Models\TimeEntry;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReportGeneratorService
{
    protected array $filters = [];
    protected array $columns = [];
    protected ?string $orderBy = null;
    protected string $orderDirection = 'desc';
    protected ?string $groupBy = null;

    public function __construct(
        protected ?ReportTemplate $template = null
    ) {
        if ($template) {
            $this->filters = $template->filters ?? [];
            $this->columns = $template->columns ?? [];
            $this->orderBy = $template->order_by;
            $this->orderDirection = $template->order_direction ?? 'desc';
            $this->groupBy = $template->group_by;
        }
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function setOrder(string $orderBy, string $direction = 'desc'): self
    {
        $this->orderBy = $orderBy;
        $this->orderDirection = $direction;
        return $this;
    }

    public function setGroupBy(?string $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Gera o relatório
     */
    public function generate(string $type, string $format = 'pdf'): GeneratedReport
    {
        $startTime = microtime(true);

        // Cria registro de relatório gerado
        $report = GeneratedReport::create([
            'user_id' => Auth::id(),
            'report_template_id' => $this->template?->id,
            'name' => $this->getReportName($type),
            'type' => $type,
            'date_from' => $this->filters['date_from'] ?? null,
            'date_to' => $this->filters['date_to'] ?? null,
            'filters_applied' => $this->filters,
            'format' => $format,
            'status' => GeneratedReport::STATUS_GENERATING,
        ]);

        try {
            // Obtém dados
            $data = $this->getData($type);
            $summary = $this->getSummary($type, $data);

            // Gera arquivo
            $result = match($format) {
                'pdf' => $this->generatePdf($type, $data, $summary),
                'excel' => $this->generateExcel($type, $data, $summary),
                'csv' => $this->generateCsv($type, $data, $summary),
                default => throw new \Exception("Formato não suportado: {$format}"),
            };

            $executionTime = microtime(true) - $startTime;

            $report->markAsCompleted(
                $result['path'],
                $result['filename'],
                $data->count(),
                $executionTime
            );

            // Incrementa uso do template
            $this->template?->incrementUsage();

        } catch (\Exception $e) {
            $report->markAsFailed($e->getMessage());
            throw $e;
        }

        return $report->fresh();
    }

    /**
     * Gera preview do relatório (sem salvar)
     */
    public function preview(string $type): array
    {
        $data = $this->getData($type);
        $summary = $this->getSummary($type, $data);

        return [
            'data' => $data->take(50), // Limita preview a 50 registros
            'summary' => $summary,
            'total_records' => $data->count(),
            'columns' => $this->getColumnHeaders($type),
        ];
    }

    /**
     * Obtém dados baseado no tipo
     */
    protected function getData(string $type): Collection
    {
        $query = $this->getQuery($type);
        
        // Aplica filtros
        $this->applyFilters($query, $type);

        // Aplica ordenação
        if ($this->orderBy) {
            $query->orderBy($this->orderBy, $this->orderDirection);
        }

        return $query->get();
    }

    /**
     * Obtém query base para cada tipo
     */
    protected function getQuery(string $type): Builder
    {
        return match($type) {
            'processes' => Process::with(['client', 'responsible']),
            'deadlines' => Deadline::with(['process', 'type', 'responsible']),
            'diligences' => Diligence::with(['client', 'process', 'service', 'assignedUser']),
            'time_entries' => TimeEntry::with(['user', 'client', 'process']),
            'contracts' => Contract::with(['client', 'installments']),
            'invoices' => Invoice::with(['client', 'items', 'payments']),
            'clients' => Client::withCount(['processes', 'services', 'invoices'])
                ->withSum('invoices', 'total'),
            'financial' => Transaction::with(['client', 'service', 'paymentMethod']),
            'services' => Service::with(['client', 'serviceType']),
            'productivity' => $this->getProductivityQuery(),
            default => throw new \Exception("Tipo de relatório não suportado: {$type}"),
        };
    }

    /**
     * Query especial para produtividade
     */
    protected function getProductivityQuery(): Builder
    {
        return User::query()
            ->withCount(['timeEntries as total_entries' => function ($q) {
                $this->applyDateFilter($q, 'activity_date');
            }])
            ->withSum(['timeEntries as total_minutes' => function ($q) {
                $this->applyDateFilter($q, 'activity_date');
            }], 'duration_minutes')
            ->withSum(['timeEntries as billable_minutes' => function ($q) {
                $this->applyDateFilter($q, 'activity_date');
                $q->where('billable', true);
            }], 'duration_minutes')
            ->withSum(['timeEntries as billed_value' => function ($q) {
                $this->applyDateFilter($q, 'activity_date');
                $q->where('billed', true);
            }], 'total_value');
    }

    /**
     * Aplica filtros na query
     */
    protected function applyFilters(Builder $query, string $type): void
    {
        // Filtro de data
        $dateColumn = $this->getDateColumn($type);
        if ($dateColumn) {
            $this->applyDateFilter($query, $dateColumn);
        }

        // Filtro de cliente
        if (!empty($this->filters['client_id'])) {
            $query->where('client_id', $this->filters['client_id']);
        }

        // Filtro de processo
        if (!empty($this->filters['process_id']) && in_array($type, ['deadlines', 'diligences', 'time_entries'])) {
            $query->where('process_id', $this->filters['process_id']);
        }

        // Filtro de status
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filtro de fase (processos)
        if (!empty($this->filters['phase']) && $type === 'processes') {
            $query->where('phase', $this->filters['phase']);
        }

        // Filtro de prioridade (prazos)
        if (!empty($this->filters['priority']) && $type === 'deadlines') {
            $query->where('priority', $this->filters['priority']);
        }

        // Filtro de usuário
        if (!empty($this->filters['user_id'])) {
            $userColumn = match($type) {
                'time_entries' => 'user_id',
                'deadlines' => 'responsible_user_id',
                'diligences' => 'assigned_user_id',
                'processes' => 'responsible_user_id',
                default => null,
            };
            if ($userColumn) {
                $query->where($userColumn, $this->filters['user_id']);
            }
        }

        // Filtro de tribunal
        if (!empty($this->filters['court']) && $type === 'processes') {
            $query->where('court', 'like', '%' . $this->filters['court'] . '%');
        }

        // Filtro de faturável (time entries)
        if (isset($this->filters['billable']) && $type === 'time_entries') {
            $query->where('billable', $this->filters['billable']);
        }
    }

    /**
     * Aplica filtro de data
     */
    protected function applyDateFilter(Builder $query, string $column): void
    {
        if (!empty($this->filters['date_from'])) {
            $query->where($column, '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where($column, '<=', $this->filters['date_to']);
        }
    }

    /**
     * Obtém coluna de data para cada tipo
     */
    protected function getDateColumn(string $type): ?string
    {
        return match($type) {
            'processes' => 'distribution_date',
            'deadlines' => 'due_date',
            'diligences' => 'scheduled_at',
            'time_entries' => 'activity_date',
            'contracts' => 'start_date',
            'invoices' => 'issue_date',
            'financial' => 'due_date',
            'services' => 'scheduled_datetime',
            default => 'created_at',
        };
    }

    /**
     * Gera resumo estatístico
     */
    protected function getSummary(string $type, Collection $data): array
    {
        $summary = [
            'total_records' => $data->count(),
            'generated_at' => now()->format('d/m/Y H:i'),
            'period' => $this->getPeriodDescription(),
        ];

        $summary = array_merge($summary, match($type) {
            'processes' => $this->getProcessesSummary($data),
            'deadlines' => $this->getDeadlinesSummary($data),
            'diligences' => $this->getDiligencesSummary($data),
            'time_entries' => $this->getTimeEntriesSummary($data),
            'contracts' => $this->getContractsSummary($data),
            'invoices' => $this->getInvoicesSummary($data),
            'clients' => $this->getClientsSummary($data),
            'financial' => $this->getFinancialSummary($data),
            'services' => $this->getServicesSummary($data),
            'productivity' => $this->getProductivitySummary($data),
            default => [],
        });

        return $summary;
    }

    protected function getProcessesSummary(Collection $data): array
    {
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'by_phase' => $data->groupBy('phase')->map->count(),
            'total_value' => $data->sum('case_value'),
            'active_count' => $data->where('status', 'active')->count(),
        ];
    }

    protected function getDeadlinesSummary(Collection $data): array
    {
        $now = now();
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'by_priority' => $data->groupBy('priority')->map->count(),
            'overdue' => $data->where('due_date', '<', $now)->where('status', 'pending')->count(),
            'due_this_week' => $data->whereBetween('due_date', [$now, $now->copy()->addWeek()])->count(),
        ];
    }

    protected function getDiligencesSummary(Collection $data): array
    {
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'by_type' => $data->groupBy('type')->map->count(),
            'total_estimated' => $data->sum('estimated_cost'),
            'total_actual' => $data->sum('actual_cost'),
        ];
    }

    protected function getTimeEntriesSummary(Collection $data): array
    {
        $totalMinutes = $data->sum('duration_minutes');
        $billableMinutes = $data->where('billable', true)->sum('duration_minutes');
        
        return [
            'total_hours' => round($totalMinutes / 60, 2),
            'billable_hours' => round($billableMinutes / 60, 2),
            'non_billable_hours' => round(($totalMinutes - $billableMinutes) / 60, 2),
            'total_value' => $data->sum('total_value'),
            'billed_value' => $data->where('billed', true)->sum('total_value'),
            'by_user' => $data->groupBy('user.name')->map(fn($items) => round($items->sum('duration_minutes') / 60, 2)),
        ];
    }

    protected function getContractsSummary(Collection $data): array
    {
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'by_type' => $data->groupBy('type')->map->count(),
            'total_value' => $data->sum('total_value'),
            'paid_value' => $data->sum('paid_value'),
            'pending_value' => $data->sum('pending_value'),
        ];
    }

    protected function getInvoicesSummary(Collection $data): array
    {
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'total' => $data->sum('total'),
            'paid' => $data->where('status', 'paid')->sum('total'),
            'pending' => $data->where('status', 'pending')->sum('total'),
            'overdue' => $data->where('status', 'overdue')->sum('total'),
        ];
    }

    protected function getClientsSummary(Collection $data): array
    {
        return [
            'by_type' => $data->groupBy('type')->map->count(),
            'total_invoiced' => $data->sum('invoices_sum_total'),
            'with_processes' => $data->where('processes_count', '>', 0)->count(),
        ];
    }

    protected function getFinancialSummary(Collection $data): array
    {
        $income = $data->where('type', 'income')->sum('amount');
        $expense = $data->where('type', 'expense')->sum('amount');

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
            'by_status' => $data->groupBy('status')->map->sum('amount'),
            'paid' => $data->where('status', 'paid')->sum('amount'),
            'pending' => $data->where('status', 'pending')->sum('amount'),
        ];
    }

    protected function getServicesSummary(Collection $data): array
    {
        return [
            'by_status' => $data->groupBy('status')->map->count(),
            'by_type' => $data->groupBy('serviceType.name')->map->count(),
            'total_value' => $data->sum('value'),
            'completed' => $data->where('status', 'completed')->count(),
        ];
    }

    protected function getProductivitySummary(Collection $data): array
    {
        return [
            'total_users' => $data->count(),
            'total_hours' => round($data->sum('total_minutes') / 60, 2),
            'billable_hours' => round($data->sum('billable_minutes') / 60, 2),
            'total_billed' => $data->sum('billed_value'),
        ];
    }

    /**
     * Gera PDF
     */
    protected function generatePdf(string $type, Collection $data, array $summary): array
    {
        $viewName = "reports.advanced.{$type}";
        
        // Usa view genérica se específica não existir
        if (!view()->exists($viewName)) {
            $viewName = 'reports.advanced.generic';
        }

        $pdf = Pdf::loadView($viewName, [
            'data' => $data,
            'summary' => $summary,
            'columns' => $this->getColumnHeaders($type),
            'type' => $type,
            'typeName' => ReportTemplate::getTypeOptions()[$type] ?? $type,
            'filters' => $this->filters,
            'template' => $this->template,
            'orientation' => $this->template?->orientation ?? 'portrait',
            'include_summary' => $this->template?->include_summary ?? true,
            'include_charts' => $this->template?->include_charts ?? false,
            'include_details' => $this->template?->include_details ?? true,
        ])->setPaper('a4', $this->template?->orientation ?? 'portrait');

        $filename = $this->generateFilename($type, 'pdf');
        $path = "reports/{$filename}";

        Storage::put($path, $pdf->output());

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * Gera Excel
     */
    protected function generateExcel(string $type, Collection $data, array $summary): array
    {
        $filename = $this->generateFilename($type, 'xlsx');
        $path = "reports/{$filename}";

        $export = new \App\Exports\AdvancedReportExport($data, $summary, $this->getColumnHeaders($type), $type);
        
        Excel::store($export, $path);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * Gera CSV
     */
    protected function generateCsv(string $type, Collection $data, array $summary): array
    {
        $filename = $this->generateFilename($type, 'csv');
        $path = "reports/{$filename}";

        $export = new \App\Exports\AdvancedReportExport($data, $summary, $this->getColumnHeaders($type), $type);
        
        Excel::store($export, $path, null, \Maatwebsite\Excel\Excel::CSV);

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    /**
     * Gera nome do arquivo
     */
    protected function generateFilename(string $type, string $extension): string
    {
        $date = now()->format('Y-m-d_His');
        $typeName = str_replace('_', '-', $type);
        
        return "relatorio-{$typeName}-{$date}.{$extension}";
    }

    /**
     * Obtém nome do relatório
     */
    protected function getReportName(string $type): string
    {
        $typeName = ReportTemplate::getTypeOptions()[$type] ?? $type;
        return "Relatório de {$typeName}";
    }

    /**
     * Obtém descrição do período
     */
    protected function getPeriodDescription(): string
    {
        if (empty($this->filters['date_from']) && empty($this->filters['date_to'])) {
            return 'Todos os registros';
        }

        $from = $this->filters['date_from'] ?? null;
        $to = $this->filters['date_to'] ?? null;

        if ($from && $to) {
            return \Carbon\Carbon::parse($from)->format('d/m/Y') . ' a ' . \Carbon\Carbon::parse($to)->format('d/m/Y');
        }

        if ($from) {
            return 'A partir de ' . \Carbon\Carbon::parse($from)->format('d/m/Y');
        }

        return 'Até ' . \Carbon\Carbon::parse($to)->format('d/m/Y');
    }

    /**
     * Obtém cabeçalhos das colunas
     */
    protected function getColumnHeaders(string $type): array
    {
        // Se colunas foram especificadas, usa elas
        if (!empty($this->columns)) {
            $available = ReportTemplate::getAvailableColumns($type);
            return array_intersect_key($available, array_flip($this->columns));
        }

        // Retorna todas as colunas padrão
        return ReportTemplate::getAvailableColumns($type);
    }
}
