<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use SoftDeletes, HasGlobalUid;

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'description',
        'type',
        'filters',
        'columns',
        'order_by',
        'order_direction',
        'group_by',
        'charts',
        'default_format',
        'orientation',
        'include_summary',
        'include_charts',
        'include_details',
        'is_public',
        'is_favorite',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'charts' => 'array',
        'include_summary' => 'boolean',
        'include_charts' => 'boolean',
        'include_details' => 'boolean',
        'is_public' => 'boolean',
        'is_favorite' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public static function getUidPrefix(): string
    {
        return 'RPT';
    }

    // === CONSTANTES ===
    
    public const TYPE_PROCESSES = 'processes';
    public const TYPE_DEADLINES = 'deadlines';
    public const TYPE_DILIGENCES = 'diligences';
    public const TYPE_TIME_ENTRIES = 'time_entries';
    public const TYPE_CONTRACTS = 'contracts';
    public const TYPE_INVOICES = 'invoices';
    public const TYPE_CLIENTS = 'clients';
    public const TYPE_FINANCIAL = 'financial';
    public const TYPE_SERVICES = 'services';
    public const TYPE_PRODUCTIVITY = 'productivity';
    public const TYPE_CUSTOM = 'custom';

    public const FORMAT_PDF = 'pdf';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_CSV = 'csv';

    // === RELATIONSHIPS ===

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    // === ACCESSORS ===

    public function getTypeNameAttribute(): string
    {
        return self::getTypeOptions()[$this->type] ?? $this->type;
    }

    public function getFormatNameAttribute(): string
    {
        return match($this->default_format) {
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
            default => strtoupper($this->default_format),
        };
    }

    // === STATIC METHODS ===

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_PROCESSES => 'Processos',
            self::TYPE_DEADLINES => 'Prazos',
            self::TYPE_DILIGENCES => 'Diligências',
            self::TYPE_TIME_ENTRIES => 'Lançamentos de Tempo',
            self::TYPE_CONTRACTS => 'Contratos',
            self::TYPE_INVOICES => 'Faturas',
            self::TYPE_CLIENTS => 'Clientes',
            self::TYPE_FINANCIAL => 'Financeiro',
            self::TYPE_SERVICES => 'Serviços',
            self::TYPE_PRODUCTIVITY => 'Produtividade',
            self::TYPE_CUSTOM => 'Personalizado',
        ];
    }

    public static function getTypeIcons(): array
    {
        return [
            self::TYPE_PROCESSES => 'heroicon-o-scale',
            self::TYPE_DEADLINES => 'heroicon-o-clock',
            self::TYPE_DILIGENCES => 'heroicon-o-clipboard-document-check',
            self::TYPE_TIME_ENTRIES => 'heroicon-o-play',
            self::TYPE_CONTRACTS => 'heroicon-o-document-text',
            self::TYPE_INVOICES => 'heroicon-o-document-currency-dollar',
            self::TYPE_CLIENTS => 'heroicon-o-users',
            self::TYPE_FINANCIAL => 'heroicon-o-banknotes',
            self::TYPE_SERVICES => 'heroicon-o-briefcase',
            self::TYPE_PRODUCTIVITY => 'heroicon-o-chart-bar',
            self::TYPE_CUSTOM => 'heroicon-o-cog-6-tooth',
        ];
    }

    public static function getFormatOptions(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_EXCEL => 'Excel (.xlsx)',
            self::FORMAT_CSV => 'CSV',
        ];
    }

    // === METHODS ===

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function toggleFavorite(): void
    {
        $this->update(['is_favorite' => !$this->is_favorite]);
    }

    public function duplicate(): self
    {
        $copy = $this->replicate(['uid', 'usage_count', 'last_used_at', 'is_favorite']);
        $copy->name = $this->name . ' (cópia)';
        $copy->is_public = false;
        $copy->save();

        return $copy;
    }

    /**
     * Obtém colunas disponíveis para o tipo de relatório
     */
    public static function getAvailableColumns(string $type): array
    {
        return match($type) {
            self::TYPE_PROCESSES => [
                'uid' => 'Código',
                'cnj_number' => 'Número CNJ',
                'title' => 'Título',
                'client.name' => 'Cliente',
                'court' => 'Tribunal',
                'jurisdiction' => 'Comarca',
                'status' => 'Status',
                'phase' => 'Fase',
                'case_value' => 'Valor da Causa',
                'distribution_date' => 'Data Distribuição',
                'responsible.name' => 'Responsável',
            ],
            self::TYPE_DEADLINES => [
                'uid' => 'Código',
                'title' => 'Título',
                'process.cnj_number' => 'Processo',
                'type.name' => 'Tipo',
                'start_date' => 'Data Início',
                'due_date' => 'Data Vencimento',
                'status' => 'Status',
                'priority' => 'Prioridade',
                'responsible.name' => 'Responsável',
            ],
            self::TYPE_DILIGENCES => [
                'uid' => 'Código',
                'title' => 'Título',
                'client.name' => 'Cliente',
                'process.cnj_number' => 'Processo',
                'type' => 'Tipo',
                'scheduled_date' => 'Agendamento',
                'status' => 'Status',
                'result' => 'Resultado',
                'estimated_cost' => 'Custo Estimado',
                'actual_cost' => 'Custo Real',
            ],
            self::TYPE_TIME_ENTRIES => [
                'uid' => 'Código',
                'description' => 'Descrição',
                'client.name' => 'Cliente',
                'process.cnj_number' => 'Processo',
                'user.name' => 'Usuário',
                'work_date' => 'Data',
                'duration_minutes' => 'Duração (min)',
                'billable' => 'Faturável',
                'billed' => 'Faturado',
                'hourly_rate' => 'Valor/Hora',
                'total_value' => 'Valor Total',
            ],
            self::TYPE_CONTRACTS => [
                'uid' => 'Código',
                'title' => 'Título',
                'client.name' => 'Cliente',
                'type' => 'Tipo',
                'start_date' => 'Início',
                'end_date' => 'Término',
                'status' => 'Status',
                'total_value' => 'Valor Total',
                'paid_value' => 'Valor Pago',
                'pending_value' => 'Valor Pendente',
            ],
            self::TYPE_INVOICES => [
                'uid' => 'Código',
                'invoice_number' => 'Número',
                'client.name' => 'Cliente',
                'issue_date' => 'Emissão',
                'due_date' => 'Vencimento',
                'status' => 'Status',
                'subtotal' => 'Subtotal',
                'discount' => 'Desconto',
                'total' => 'Total',
                'paid_amount' => 'Valor Pago',
            ],
            self::TYPE_CLIENTS => [
                'uid' => 'Código',
                'name' => 'Nome',
                'type' => 'Tipo',
                'document' => 'CPF/CNPJ',
                'email' => 'Email',
                'phone' => 'Telefone',
                'city' => 'Cidade',
                'state' => 'Estado',
                'processes_count' => 'Qtd. Processos',
                'invoices_total' => 'Total Faturado',
            ],
            self::TYPE_FINANCIAL => [
                'uid' => 'Código',
                'type' => 'Tipo',
                'category' => 'Categoria',
                'description' => 'Descrição',
                'client.name' => 'Cliente',
                'due_date' => 'Vencimento',
                'payment_date' => 'Pagamento',
                'amount' => 'Valor',
                'status' => 'Status',
            ],
            self::TYPE_SERVICES => [
                'uid' => 'Código',
                'client.name' => 'Cliente',
                'serviceType.name' => 'Tipo',
                'scheduled_datetime' => 'Agendamento',
                'status' => 'Status',
                'value' => 'Valor',
                'result' => 'Resultado',
            ],
            self::TYPE_PRODUCTIVITY => [
                'user.name' => 'Usuário',
                'total_hours' => 'Total Horas',
                'billable_hours' => 'Horas Faturáveis',
                'non_billable_hours' => 'Horas Não Faturáveis',
                'billed_value' => 'Valor Faturado',
                'processes_count' => 'Processos',
                'diligences_count' => 'Diligências',
            ],
            default => [],
        };
    }

    /**
     * Obtém filtros disponíveis para o tipo de relatório
     */
    public static function getAvailableFilters(string $type): array
    {
        $commonFilters = [
            'date_from' => ['type' => 'date', 'label' => 'Data Inicial'],
            'date_to' => ['type' => 'date', 'label' => 'Data Final'],
        ];

        $typeFilters = match($type) {
            self::TYPE_PROCESSES => [
                'client_id' => ['type' => 'select', 'label' => 'Cliente', 'model' => 'Client'],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => [
                    'active' => 'Ativo', 'suspended' => 'Suspenso', 'archived' => 'Arquivado',
                    'closed_won' => 'Encerrado - Ganho', 'closed_lost' => 'Encerrado - Perdido',
                ]],
                'phase' => ['type' => 'select', 'label' => 'Fase', 'options' => [
                    'knowledge' => 'Conhecimento', 'execution' => 'Execução',
                    'appeal' => 'Recursal', 'precautionary' => 'Cautelar',
                ]],
                'court' => ['type' => 'text', 'label' => 'Tribunal'],
            ],
            self::TYPE_DEADLINES => [
                'process_id' => ['type' => 'select', 'label' => 'Processo', 'model' => 'Process'],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => [
                    'pending' => 'Pendente', 'in_progress' => 'Em Andamento',
                    'completed' => 'Cumprido', 'missed' => 'Perdido',
                ]],
                'priority' => ['type' => 'select', 'label' => 'Prioridade', 'options' => [
                    'low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta', 'critical' => 'Crítica',
                ]],
            ],
            self::TYPE_INVOICES => [
                'client_id' => ['type' => 'select', 'label' => 'Cliente', 'model' => 'Client'],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => [
                    'draft' => 'Rascunho', 'pending' => 'Pendente', 'paid' => 'Paga',
                    'partial' => 'Parcial', 'overdue' => 'Vencida', 'cancelled' => 'Cancelada',
                ]],
            ],
            default => [
                'client_id' => ['type' => 'select', 'label' => 'Cliente', 'model' => 'Client'],
            ],
        };

        return array_merge($commonFilters, $typeFilters);
    }
}
