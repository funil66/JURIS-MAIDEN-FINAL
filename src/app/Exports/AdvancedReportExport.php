<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdvancedReportExport implements WithMultipleSheets
{
    public function __construct(
        protected Collection $data,
        protected array $summary,
        protected array $columns,
        protected string $type
    ) {}

    public function sheets(): array
    {
        return [
            new AdvancedReportDataSheet($this->data, $this->columns, $this->type),
            new AdvancedReportSummarySheet($this->summary, $this->type),
        ];
    }
}

class AdvancedReportDataSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected Collection $data,
        protected array $columns,
        protected string $type
    ) {}

    public function collection(): Collection
    {
        return $this->data->map(function ($item) {
            $row = [];
            foreach (array_keys($this->columns) as $column) {
                $row[] = $this->getColumnValue($item, $column);
            }
            return $row;
        });
    }

    protected function getColumnValue($item, string $column): mixed
    {
        // Suporta notação de ponto (ex: client.name)
        $parts = explode('.', $column);
        $value = $item;

        foreach ($parts as $part) {
            if (is_array($value)) {
                $value = $value[$part] ?? null;
            } elseif (is_object($value)) {
                $value = $value->{$part} ?? null;
            } else {
                return null;
            }
        }

        // Formata valores especiais
        if ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
            return $value->format('d/m/Y H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Sim' : 'Não';
        }

        // Traduz status/enums comuns
        if (in_array($column, ['status', 'phase', 'type', 'priority', 'result'])) {
            return $this->translateEnum($column, $value);
        }

        // Formata valores monetários
        if (str_contains($column, 'value') || str_contains($column, 'cost') || str_contains($column, 'amount') || str_contains($column, 'total') || str_contains($column, 'rate')) {
            return is_numeric($value) ? 'R$ ' . number_format($value, 2, ',', '.') : $value;
        }

        return $value;
    }

    protected function translateEnum(string $column, ?string $value): string
    {
        if (!$value) return '-';

        $translations = [
            'status' => [
                'active' => 'Ativo',
                'pending' => 'Pendente',
                'in_progress' => 'Em Andamento',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado',
                'suspended' => 'Suspenso',
                'archived' => 'Arquivado',
                'closed_won' => 'Encerrado - Ganho',
                'closed_lost' => 'Encerrado - Perdido',
                'closed_settled' => 'Encerrado - Acordo',
                'paid' => 'Pago',
                'overdue' => 'Vencido',
                'partial' => 'Parcial',
                'draft' => 'Rascunho',
                'missed' => 'Perdido',
                'extended' => 'Prorrogado',
            ],
            'phase' => [
                'knowledge' => 'Conhecimento',
                'execution' => 'Execução',
                'appeal' => 'Recursal',
                'precautionary' => 'Cautelar',
            ],
            'priority' => [
                'low' => 'Baixa',
                'normal' => 'Normal',
                'high' => 'Alta',
                'critical' => 'Crítica',
            ],
            'result' => [
                'positive' => 'Positivo',
                'negative' => 'Negativo',
                'partial' => 'Parcial',
                'rescheduled' => 'Reagendado',
                'cancelled' => 'Cancelado',
            ],
            'type' => [
                'individual' => 'Pessoa Física',
                'company' => 'Pessoa Jurídica',
                'income' => 'Receita',
                'expense' => 'Despesa',
                'citation' => 'Citação',
                'subpoena' => 'Intimação',
                'hearing' => 'Audiência',
                'protocol' => 'Protocolo',
                'copy_extraction' => 'Extração de Cópias',
                'research' => 'Pesquisa',
                'meeting' => 'Reunião',
                'travel' => 'Viagem',
                'other' => 'Outro',
                'fixed' => 'Valor Fixo',
                'hourly' => 'Por Hora',
                'success_fee' => 'Êxito',
                'mixed' => 'Misto',
            ],
        ];

        return $translations[$column][$value] ?? $value;
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Dados';
    }
}

class AdvancedReportSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected array $summary,
        protected string $type
    ) {}

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->summary as $key => $value) {
            if (is_array($value)) {
                // Para arrays, adiciona cada item
                foreach ($value as $subKey => $subValue) {
                    $rows->push([
                        $this->translateKey($key) . ' - ' . $this->translateKey($subKey),
                        $this->formatValue($subValue),
                    ]);
                }
            } else {
                $rows->push([
                    $this->translateKey($key),
                    $this->formatValue($value),
                ]);
            }
        }

        return $rows;
    }

    protected function translateKey(string $key): string
    {
        $translations = [
            'total_records' => 'Total de Registros',
            'generated_at' => 'Gerado em',
            'period' => 'Período',
            'by_status' => 'Por Status',
            'by_phase' => 'Por Fase',
            'by_type' => 'Por Tipo',
            'by_priority' => 'Por Prioridade',
            'by_user' => 'Por Usuário',
            'total_value' => 'Valor Total',
            'total_hours' => 'Total de Horas',
            'billable_hours' => 'Horas Faturáveis',
            'non_billable_hours' => 'Horas Não Faturáveis',
            'billed_value' => 'Valor Faturado',
            'total_income' => 'Total Receitas',
            'total_expense' => 'Total Despesas',
            'balance' => 'Saldo',
            'paid' => 'Pago',
            'pending' => 'Pendente',
            'overdue' => 'Vencido',
            'active_count' => 'Ativos',
            'due_this_week' => 'Vence esta Semana',
            'total_estimated' => 'Custo Estimado Total',
            'total_actual' => 'Custo Real Total',
            'paid_value' => 'Valor Pago',
            'pending_value' => 'Valor Pendente',
            'total_users' => 'Total de Usuários',
            'total_billed' => 'Total Faturado',
            'completed' => 'Concluídos',
            'with_processes' => 'Com Processos',
            'total_invoiced' => 'Total Faturado',
            // Status
            'active' => 'Ativo',
            'suspended' => 'Suspenso',
            'archived' => 'Arquivado',
            'closed_won' => 'Encerrado - Ganho',
            'closed_lost' => 'Encerrado - Perdido',
            'closed_settled' => 'Encerrado - Acordo',
            'in_progress' => 'Em Andamento',
            'cancelled' => 'Cancelado',
            'missed' => 'Perdido',
            'extended' => 'Prorrogado',
            // Fases
            'knowledge' => 'Conhecimento',
            'execution' => 'Execução',
            'appeal' => 'Recursal',
            'precautionary' => 'Cautelar',
            // Prioridades
            'low' => 'Baixa',
            'normal' => 'Normal',
            'high' => 'Alta',
            'critical' => 'Crítica',
        ];

        return $translations[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected function formatValue(mixed $value): string
    {
        if (is_numeric($value)) {
            // Se parece valor monetário
            if ($value >= 100) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return number_format($value, 2, ',', '.');
        }

        return (string) $value;
    }

    public function headings(): array
    {
        return ['Métrica', 'Valor'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Resumo';
    }
}
