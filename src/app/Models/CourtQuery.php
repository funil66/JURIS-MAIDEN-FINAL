<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtQuery extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Consultas
     */
    protected static string $uidPrefix = 'CQY';

    protected $fillable = [
        'uid',
        'court_id',
        'process_id',
        'user_id',
        'query_type',
        'process_number',
        'query_params',
        'status',
        'response_data',
        'error_message',
        'response_time_ms',
        'results_count',
        'queried_at',
        'completed_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'queried_at' => 'datetime',
        'completed_at' => 'datetime',
        'response_time_ms' => 'integer',
        'results_count' => 'integer',
    ];

    /**
     * Tipos de consulta
     */
    public const TYPE_PROCESS_SEARCH = 'process_search';
    public const TYPE_PROCESS_DETAILS = 'process_details';
    public const TYPE_MOVEMENTS = 'movements';
    public const TYPE_PARTIES = 'parties';
    public const TYPE_DOCUMENTS = 'documents';
    public const TYPE_DEADLINES = 'deadlines';
    public const TYPE_HEARINGS = 'hearings';
    public const TYPE_ATTACHED = 'attached_processes';
    public const TYPE_DISTRIBUTION = 'distribution';

    public const QUERY_TYPES = [
        self::TYPE_PROCESS_SEARCH => 'Busca de Processo',
        self::TYPE_PROCESS_DETAILS => 'Detalhes do Processo',
        self::TYPE_MOVEMENTS => 'Movimentações',
        self::TYPE_PARTIES => 'Partes',
        self::TYPE_DOCUMENTS => 'Documentos',
        self::TYPE_DEADLINES => 'Prazos',
        self::TYPE_HEARINGS => 'Audiências',
        self::TYPE_ATTACHED => 'Processos Apensados',
        self::TYPE_DISTRIBUTION => 'Distribuição',
    ];

    /**
     * Status
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_NO_RESULTS = 'no_results';

    public const STATUSES = [
        self::STATUS_PENDING => 'Aguardando',
        self::STATUS_PROCESSING => 'Processando',
        self::STATUS_SUCCESS => 'Sucesso',
        self::STATUS_ERROR => 'Erro',
        self::STATUS_NO_RESULTS => 'Sem Resultados',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_PROCESSING => 'info',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_ERROR => 'danger',
        self::STATUS_NO_RESULTS => 'gray',
    ];

    /**
     * Relacionamento: Tribunal
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Relacionamento: Processo
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Relacionamento: Usuário que realizou
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Movimentações encontradas
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CourtMovement::class);
    }

    /**
     * Accessor: Label do tipo
     */
    public function getQueryTypeLabelAttribute(): string
    {
        return self::QUERY_TYPES[$this->query_type] ?? $this->query_type;
    }

    /**
     * Accessor: Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Accessor: Cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Accessor: Dados da resposta parseados
     */
    public function getParsedResponseAttribute(): ?array
    {
        if (!$this->response_data) {
            return null;
        }
        
        return json_decode($this->response_data, true);
    }

    /**
     * Marcar como em processamento
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'queried_at' => now(),
        ]);
    }

    /**
     * Marcar como sucesso
     */
    public function markAsSuccess(string $responseData, int $resultsCount, int $responseTimeMs): void
    {
        $this->update([
            'status' => $resultsCount > 0 ? self::STATUS_SUCCESS : self::STATUS_NO_RESULTS,
            'response_data' => $responseData,
            'results_count' => $resultsCount,
            'response_time_ms' => $responseTimeMs,
            'completed_at' => now(),
        ]);
    }

    /**
     * Marcar como erro
     */
    public function markAsError(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope: Por status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Consultas recentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
