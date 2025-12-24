<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'court_sync_schedule_id',
        'user_id',
        'sync_type',
        'started_at',
        'finished_at',
        'processes_queried',
        'movements_found',
        'movements_new',
        'movements_imported',
        'errors_count',
        'status',
        'error_message',
        'error_details',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'error_details' => 'array',
        'processes_queried' => 'integer',
        'movements_found' => 'integer',
        'movements_new' => 'integer',
        'movements_imported' => 'integer',
        'errors_count' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * Tipos de sincronização
     */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_BULK = 'bulk';

    public const SYNC_TYPES = [
        self::TYPE_MANUAL => 'Manual',
        self::TYPE_SCHEDULED => 'Agendada',
        self::TYPE_WEBHOOK => 'Webhook',
        self::TYPE_BULK => 'Em Lote',
    ];

    /**
     * Status
     */
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_RUNNING => 'Em Execução',
        self::STATUS_SUCCESS => 'Sucesso',
        self::STATUS_PARTIAL => 'Parcial',
        self::STATUS_ERROR => 'Erro',
    ];

    public const STATUS_COLORS = [
        self::STATUS_RUNNING => 'info',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_PARTIAL => 'warning',
        self::STATUS_ERROR => 'danger',
    ];

    /**
     * Relacionamento: Tribunal
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Relacionamento: Agendamento
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(CourtSyncSchedule::class, 'court_sync_schedule_id');
    }

    /**
     * Relacionamento: Usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: Label do tipo
     */
    public function getSyncTypeLabelAttribute(): string
    {
        return self::SYNC_TYPES[$this->sync_type] ?? $this->sync_type;
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
     * Accessor: Duração formatada
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        if ($this->duration_seconds < 60) {
            return "{$this->duration_seconds}s";
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Iniciar log de sincronização
     */
    public static function start(
        string $syncType,
        ?int $courtId = null,
        ?int $scheduleId = null,
        ?int $userId = null
    ): static {
        return static::create([
            'court_id' => $courtId,
            'court_sync_schedule_id' => $scheduleId,
            'user_id' => $userId ?? auth()->id(),
            'sync_type' => $syncType,
            'started_at' => now(),
            'status' => self::STATUS_RUNNING,
        ]);
    }

    /**
     * Finalizar com sucesso
     */
    public function finish(
        int $processesQueried,
        int $movementsFound,
        int $movementsNew,
        int $movementsImported,
        int $errorsCount = 0
    ): void {
        $status = match (true) {
            $errorsCount > 0 && $movementsNew === 0 => self::STATUS_ERROR,
            $errorsCount > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_SUCCESS,
        };

        $this->update([
            'status' => $status,
            'finished_at' => now(),
            'processes_queried' => $processesQueried,
            'movements_found' => $movementsFound,
            'movements_new' => $movementsNew,
            'movements_imported' => $movementsImported,
            'errors_count' => $errorsCount,
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
        ]);
    }

    /**
     * Finalizar com erro
     */
    public function finishWithError(string $errorMessage, ?array $errorDetails = null): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'finished_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'duration_seconds' => $this->started_at->diffInSeconds(now()),
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
     * Scope: Recentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Ordenar por mais recente
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('started_at');
    }
}
