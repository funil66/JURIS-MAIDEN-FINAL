<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Lançamentos de Tempo
     */
    public static function getUidPrefix(): string
    {
        return 'TIM';
    }

    protected $fillable = [
        'user_id',
        'process_id',
        'client_id',
        'service_id',
        'proceeding_id',
        'diligence_id',
        'description',
        'notes',
        'activity_type',
        'work_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_running',
        'timer_started_at',
        'is_billable',
        'hourly_rate',
        'total_amount',
        'status',
        'approved_by_id',
        'approved_at',
        'rejection_reason',
        'invoice_id',
        'billed_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'timer_started_at' => 'datetime',
        'approved_at' => 'datetime',
        'billed_at' => 'datetime',
        'is_running' => 'boolean',
        'is_billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'duration_minutes', 'is_billable'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Calcular valor total
            if ($model->hourly_rate && $model->duration_minutes) {
                $hours = $model->duration_minutes / 60;
                $model->total_amount = round($model->hourly_rate * $hours, 2);
            }
        });
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Usuário que trabalhou
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Processo
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Serviço
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Andamento
     */
    public function proceeding(): BelongsTo
    {
        return $this->belongsTo(Proceeding::class);
    }

    /**
     * Diligência
     */
    public function diligence(): BelongsTo
    {
        return $this->belongsTo(Diligence::class);
    }

    /**
     * Usuário que aprovou
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Por usuário
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Meus lançamentos
     */
    public function scopeMine($query)
    {
        return $query->where('user_id', auth()->id());
    }

    /**
     * Faturáveis
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Não faturados
     */
    public function scopeUnbilled($query)
    {
        return $query->where('is_billable', true)
            ->whereNotIn('status', ['billed', 'paid']);
    }

    /**
     * Aprovados
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Pendentes de aprovação
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Rascunho
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('work_date', today());
    }

    /**
     * Esta semana
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('work_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Este mês
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('work_date', now()->month)
            ->whereYear('work_date', now()->year);
    }

    /**
     * Período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    /**
     * Com timer ativo
     */
    public function scopeRunning($query)
    {
        return $query->where('is_running', true);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Label do tipo de atividade
     */
    public function getActivityTypeLabelAttribute(): string
    {
        return self::getActivityTypeOptions()[$this->activity_type] ?? $this->activity_type;
    }

    /**
     * Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Duração formatada
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes);
        }

        return sprintf('%dmin', $minutes);
    }

    /**
     * Duração decimal (para cálculos)
     */
    public function getDurationDecimalAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }

    /**
     * Horário de início e fim formatado
     */
    public function getTimeRangeAttribute(): ?string
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->start_time . ' - ' . $this->end_time;
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Iniciar timer
     */
    public function startTimer(): bool
    {
        if ($this->is_running) {
            return false;
        }

        $this->update([
            'is_running' => true,
            'timer_started_at' => now(),
        ]);

        return true;
    }

    /**
     * Parar timer
     */
    public function stopTimer(): bool
    {
        if (!$this->is_running || !$this->timer_started_at) {
            return false;
        }

        $additionalMinutes = $this->timer_started_at->diffInMinutes(now());

        $this->update([
            'is_running' => false,
            'duration_minutes' => $this->duration_minutes + $additionalMinutes,
            'timer_started_at' => null,
            'end_time' => now()->format('H:i:s'),
        ]);

        return true;
    }

    /**
     * Submeter para aprovação
     */
    public function submit(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        // Parar timer se estiver rodando
        if ($this->is_running) {
            $this->stopTimer();
        }

        $this->update(['status' => 'submitted']);

        return true;
    }

    /**
     * Aprovar
     */
    public function approve(?int $approvedById = null): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by_id' => $approvedById ?? auth()->id(),
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Rejeitar
     */
    public function reject(string $reason, ?int $rejectedById = null): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by_id' => $rejectedById ?? auth()->id(),
            'approved_at' => now(),
        ]);

        return true;
    }

    /**
     * Marcar como faturado
     */
    public function markAsBilled(?int $invoiceId = null): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->update([
            'status' => 'billed',
            'invoice_id' => $invoiceId,
            'billed_at' => now(),
        ]);

        return true;
    }

    /**
     * Calcular valor
     */
    public function calculateAmount(): float
    {
        if (!$this->hourly_rate) {
            return 0;
        }

        $hours = $this->duration_minutes / 60;
        return round($this->hourly_rate * $hours, 2);
    }

    // ==========================================
    // OPTIONS
    // ==========================================

    /**
     * Opções de tipo de atividade
     */
    public static function getActivityTypeOptions(): array
    {
        return [
            'research' => 'Pesquisa',
            'drafting' => 'Elaboração de Peça',
            'review' => 'Revisão',
            'meeting' => 'Reunião',
            'hearing' => 'Audiência',
            'phone_call' => 'Telefonema',
            'email' => 'E-mail',
            'consultation' => 'Consulta',
            'analysis' => 'Análise',
            'negotiation' => 'Negociação',
            'court_visit' => 'Ida ao Fórum',
            'administrative' => 'Administrativo',
            'travel' => 'Deslocamento',
            'other' => 'Outro',
        ];
    }

    /**
     * Opções de status
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'submitted' => 'Submetido',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'billed' => 'Faturado',
            'paid' => 'Pago',
        ];
    }

    /**
     * Durações comuns em minutos
     */
    public static function getCommonDurations(): array
    {
        return [
            6 => '6 min (0.1h)',
            15 => '15 min (0.25h)',
            30 => '30 min (0.5h)',
            45 => '45 min (0.75h)',
            60 => '1 hora',
            90 => '1h 30min',
            120 => '2 horas',
            180 => '3 horas',
            240 => '4 horas',
            480 => '8 horas',
        ];
    }
}
