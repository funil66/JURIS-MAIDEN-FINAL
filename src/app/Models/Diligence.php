<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Diligence extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Diligências
     */
    public static function getUidPrefix(): string
    {
        return 'DLG';
    }

    protected $fillable = [
        'process_id',
        'client_id',
        'service_id',
        'proceeding_id',
        'assigned_user_id',
        'created_by_user_id',
        'title',
        'description',
        'objective',
        'type',
        'priority',
        'status',
        'scheduled_date',
        'scheduled_time',
        'scheduled_end_time',
        'estimated_duration_minutes',
        'started_at',
        'completed_at',
        'actual_duration_minutes',
        'location_name',
        'location_address',
        'location_city',
        'location_state',
        'location_zip',
        'location_lat',
        'location_lng',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_department',
        'estimated_cost',
        'actual_cost',
        'mileage_km',
        'mileage_cost',
        'parking_cost',
        'toll_cost',
        'transport_cost',
        'other_costs',
        'is_billable',
        'is_reimbursed',
        'reimbursed_at',
        'result',
        'was_successful',
        'failure_reason',
        'has_receipt',
        'has_attachments',
        'attachments_count',
        'notes',
        'internal_notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reimbursed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'mileage_km' => 'decimal:2',
        'mileage_cost' => 'decimal:2',
        'parking_cost' => 'decimal:2',
        'toll_cost' => 'decimal:2',
        'transport_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_reimbursed' => 'boolean',
        'has_receipt' => 'boolean',
        'has_attachments' => 'boolean',
        'was_successful' => 'boolean',
        'location_lat' => 'decimal:7',
        'location_lng' => 'decimal:7',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'scheduled_date', 'assigned_user_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Processo da diligência
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Cliente da diligência
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Serviço relacionado
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Andamento relacionado
     */
    public function proceeding(): BelongsTo
    {
        return $this->belongsTo(Proceeding::class);
    }

    /**
     * Usuário designado
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Usuário que criou
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Agendadas
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Em andamento
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Concluídas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Esta semana
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Próximos dias
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('scheduled_date', [
            today(),
            today()->addDays($days),
        ]);
    }

    /**
     * Atrasadas
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['pending', 'scheduled'])
            ->whereDate('scheduled_date', '<', today());
    }

    /**
     * Por responsável
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Faturáveis
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Não reembolsadas
     */
    public function scopeNotReimbursed($query)
    {
        return $query->where('is_billable', true)
            ->where('is_reimbursed', false)
            ->where('status', 'completed');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeOptions()[$this->type] ?? $this->type;
    }

    /**
     * Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Label da prioridade
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::getPriorityOptions()[$this->priority] ?? $this->priority;
    }

    /**
     * Custo total
     */
    public function getTotalCostAttribute(): float
    {
        return $this->mileage_cost +
            $this->parking_cost +
            $this->toll_cost +
            $this->transport_cost +
            $this->other_costs;
    }

    /**
     * Endereço completo
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->location_address,
            $this->location_city,
            $this->location_state,
            $this->location_zip,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Está atrasada?
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!in_array($this->status, ['pending', 'scheduled'])) {
            return false;
        }

        return $this->scheduled_date && $this->scheduled_date->isPast();
    }

    /**
     * Duração formatada
     */
    public function getFormattedDurationAttribute(): ?string
    {
        $minutes = $this->actual_duration_minutes ?? $this->estimated_duration_minutes;

        if (!$minutes) {
            return null;
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h " . ($mins > 0 ? "{$mins}min" : '');
        }

        return "{$mins}min";
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Iniciar diligência
     */
    public function start(): bool
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Concluir diligência
     */
    public function complete(bool $wasSuccessful = true, ?string $result = null): bool
    {
        $startedAt = $this->started_at ?? now();
        $duration = $startedAt->diffInMinutes(now());

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'was_successful' => $wasSuccessful,
            'actual_duration_minutes' => $duration,
            'result' => $result,
        ]);

        return true;
    }

    /**
     * Cancelar diligência
     */
    public function cancel(?string $reason = null): bool
    {
        $this->update([
            'status' => 'cancelled',
            'failure_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Reagendar
     */
    public function reschedule(\DateTime $newDate, ?\DateTime $newTime = null): bool
    {
        $this->update([
            'status' => 'rescheduled',
            'scheduled_date' => $newDate,
            'scheduled_time' => $newTime?->format('H:i:s'),
        ]);

        return true;
    }

    /**
     * Marcar como reembolsada
     */
    public function markAsReimbursed(): bool
    {
        $this->update([
            'is_reimbursed' => true,
            'reimbursed_at' => now(),
        ]);

        return true;
    }

    /**
     * Calcular custo de quilometragem
     */
    public function calculateMileageCost(float $costPerKm = 1.50): float
    {
        $cost = $this->mileage_km * $costPerKm;
        $this->update(['mileage_cost' => $cost]);

        return $cost;
    }

    // ==========================================
    // OPTIONS
    // ==========================================

    /**
     * Opções de tipo
     */
    public static function getTypeOptions(): array
    {
        return [
            'forum_visit' => 'Ida ao Fórum',
            'registry_visit' => 'Ida ao Cartório',
            'notary_visit' => 'Tabelionato',
            'document_pickup' => 'Retirada de Documentos',
            'document_delivery' => 'Entrega de Documentos',
            'hearing' => 'Audiência Presencial',
            'meeting' => 'Reunião Externa',
            'site_inspection' => 'Vistoria/Inspeção',
            'witness_interview' => 'Entrevista com Testemunha',
            'deposition' => 'Coleta de Depoimento',
            'service_of_process' => 'Citação/Intimação',
            'notarization' => 'Autenticação/Reconhecimento',
            'filing' => 'Protocolo',
            'research' => 'Pesquisa de Bens/Certidões',
            'court_hearing' => 'Acompanhamento de Audiência',
            'travel' => 'Viagem',
            'other' => 'Outro',
        ];
    }

    /**
     * Opções de status
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'scheduled' => 'Agendada',
            'in_progress' => 'Em Execução',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            'rescheduled' => 'Reagendada',
            'failed' => 'Falhou',
        ];
    }

    /**
     * Opções de prioridade
     */
    public static function getPriorityOptions(): array
    {
        return [
            'low' => 'Baixa',
            'normal' => 'Normal',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];
    }

    /**
     * Status ativos (não finalizados)
     */
    public static function getActiveStatuses(): array
    {
        return ['pending', 'scheduled', 'in_progress', 'rescheduled'];
    }
}
