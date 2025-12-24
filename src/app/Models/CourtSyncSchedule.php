<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CourtSyncSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'process_id',
        'sync_type',
        'frequency',
        'cron_expression',
        'preferred_time',
        'is_active',
        'last_run_at',
        'next_run_at',
        'last_run_status',
        'last_run_message',
        'last_run_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'preferred_time' => 'datetime:H:i',
        'last_run_count' => 'integer',
    ];

    /**
     * Tipos de sincronização
     */
    public const TYPE_ALL = 'all_processes';
    public const TYPE_SINGLE = 'single_process';
    public const TYPE_ACTIVE = 'active_processes';
    public const TYPE_CUSTOM = 'custom_query';

    public const SYNC_TYPES = [
        self::TYPE_ALL => 'Todos os Processos',
        self::TYPE_SINGLE => 'Processo Específico',
        self::TYPE_ACTIVE => 'Processos Ativos',
        self::TYPE_CUSTOM => 'Consulta Customizada',
    ];

    /**
     * Frequências
     */
    public const FREQ_HOURLY = 'hourly';
    public const FREQ_EVERY_4_HOURS = 'every_4_hours';
    public const FREQ_EVERY_8_HOURS = 'every_8_hours';
    public const FREQ_TWICE_DAILY = 'twice_daily';
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MANUAL = 'manual';

    public const FREQUENCIES = [
        self::FREQ_HOURLY => 'A cada hora',
        self::FREQ_EVERY_4_HOURS => 'A cada 4 horas',
        self::FREQ_EVERY_8_HOURS => 'A cada 8 horas',
        self::FREQ_TWICE_DAILY => 'Duas vezes ao dia',
        self::FREQ_DAILY => 'Diariamente',
        self::FREQ_WEEKLY => 'Semanalmente',
        self::FREQ_MANUAL => 'Apenas Manual',
    ];

    /**
     * Status da última execução
     */
    public const RUN_STATUS_SUCCESS = 'success';
    public const RUN_STATUS_PARTIAL = 'partial';
    public const RUN_STATUS_ERROR = 'error';

    public const RUN_STATUSES = [
        self::RUN_STATUS_SUCCESS => 'Sucesso',
        self::RUN_STATUS_PARTIAL => 'Parcial',
        self::RUN_STATUS_ERROR => 'Erro',
    ];

    public const RUN_STATUS_COLORS = [
        self::RUN_STATUS_SUCCESS => 'success',
        self::RUN_STATUS_PARTIAL => 'warning',
        self::RUN_STATUS_ERROR => 'danger',
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
     * Accessor: Label do tipo
     */
    public function getSyncTypeLabelAttribute(): string
    {
        return self::SYNC_TYPES[$this->sync_type] ?? $this->sync_type;
    }

    /**
     * Accessor: Label da frequência
     */
    public function getFrequencyLabelAttribute(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    /**
     * Accessor: Label do status da última execução
     */
    public function getLastRunStatusLabelAttribute(): ?string
    {
        return self::RUN_STATUSES[$this->last_run_status] ?? $this->last_run_status;
    }

    /**
     * Calcular próxima execução
     */
    public function calculateNextRun(): ?Carbon
    {
        if ($this->frequency === self::FREQ_MANUAL) {
            return null;
        }

        $baseTime = $this->last_run_at ?? now();

        return match ($this->frequency) {
            self::FREQ_HOURLY => $baseTime->addHour(),
            self::FREQ_EVERY_4_HOURS => $baseTime->addHours(4),
            self::FREQ_EVERY_8_HOURS => $baseTime->addHours(8),
            self::FREQ_TWICE_DAILY => $baseTime->addHours(12),
            self::FREQ_DAILY => $baseTime->addDay(),
            self::FREQ_WEEKLY => $baseTime->addWeek(),
            default => null,
        };
    }

    /**
     * Verificar se está na hora de executar
     */
    public function shouldRun(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->frequency === self::FREQ_MANUAL) {
            return false;
        }

        if (!$this->next_run_at) {
            return true;
        }

        return $this->next_run_at->isPast();
    }

    /**
     * Registrar execução
     */
    public function recordRun(string $status, int $count, ?string $message = null): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_run_status' => $status,
            'last_run_count' => $count,
            'last_run_message' => $message,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    /**
     * Scope: Ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Prontos para executar
     */
    public function scopeReadyToRun($query)
    {
        return $query->where('is_active', true)
            ->where('frequency', '!=', self::FREQ_MANUAL)
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            });
    }
}
