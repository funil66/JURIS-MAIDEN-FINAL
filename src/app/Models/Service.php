<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Service extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'client_id',
        'service_type_id',
        'process_number',
        'court',
        'jurisdiction',
        'state',
        'plaintiff',
        'defendant',
        'request_date',
        'deadline_date',
        'scheduled_datetime',
        'completion_date',
        'location_name',
        'location_address',
        'location_city',
        'location_state',
        'location_cep',
        'agreed_price',
        'expenses',
        'total_price',
        'status',
        'payment_status',
        'priority',
        'description',
        'instructions',
        'result_notes',
        'internal_notes',
        // Sprint 13: Campos estendidos
        'judge_name',
        'court_secretary',
        'court_phone',
        'court_email',
        'requester_name',
        'requester_email',
        'requester_phone',
        'requester_oab',
        'travel_distance_km',
        'travel_cost',
        'travel_type',
        'travel_notes',
        'attachments',
        'has_substabelecimento',
        'has_procuracao',
        'documents_received',
        'documents_received_at',
        'result_type',
        'actual_datetime',
        'result_summary',
        'result_attachments',
        'client_rating',
        'client_feedback',
        'requires_followup',
        'followup_notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'deadline_date' => 'date',
        'scheduled_datetime' => 'datetime',
        'completion_date' => 'date',
        'agreed_price' => 'decimal:2',
        'expenses' => 'decimal:2',
        'total_price' => 'decimal:2',
        // Sprint 13: Novos casts
        'travel_distance_km' => 'decimal:2',
        'travel_cost' => 'decimal:2',
        'attachments' => 'array',
        'result_attachments' => 'array',
        'has_substabelecimento' => 'boolean',
        'has_procuracao' => 'boolean',
        'documents_received' => 'boolean',
        'documents_received_at' => 'date',
        'actual_datetime' => 'datetime',
        'client_rating' => 'integer',
        'requires_followup' => 'boolean',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'payment_status', 'scheduled_datetime', 'completion_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot do model para gerar código automático
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (empty($service->code)) {
                $year = date('Y');
                $lastService = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $nextNumber = $lastService 
                    ? (int) substr($lastService->code, -4) + 1 
                    : 1;
                
                $service->code = sprintf('SRV-%s-%04d', $year, $nextNumber);
            }

            // Calcular total
            $service->total_price = ($service->agreed_price ?? 0) + ($service->expenses ?? 0);
        });

        static::updating(function ($service) {
            $service->total_price = ($service->agreed_price ?? 0) + ($service->expenses ?? 0);
        });
    }

    /**
     * Cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Tipo de serviço
     */
    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    /**
     * Eventos relacionados
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Transações relacionadas
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Status labels
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'rescheduled' => 'Reagendado',
        ];
    }

    /**
     * Payment status labels
     */
    public static function getPaymentStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'partial' => 'Parcial',
            'paid' => 'Pago',
            'overdue' => 'Vencido',
        ];
    }

    /**
     * Priority labels
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
     * Status colors
     */
    public static function getStatusColors(): array
    {
        return [
            'pending' => 'warning',
            'confirmed' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'rescheduled' => 'gray',
        ];
    }

    /**
     * Payment status colors
     */
    public static function getPaymentStatusColors(): array
    {
        return [
            'pending' => 'warning',
            'partial' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
        ];
    }

    /**
     * Priority colors
     */
    public static function getPriorityColors(): array
    {
        return [
            'low' => 'gray',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
        ];
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->whereBetween('deadline_date', [now(), now()->addDays($days)])
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['pending', 'partial', 'overdue']);
    }

    /**
     * Verifica se está atrasado
     */
    public function isOverdue(): bool
    {
        return $this->deadline_date 
            && $this->deadline_date->isPast() 
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Retorna valor formatado
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total_price, 2, ',', '.');
    }

    /**
     * Endereço completo do local
     */
    public function getFullLocationAttribute(): string
    {
        $parts = array_filter([
            $this->location_name,
            $this->location_address,
            $this->location_city,
            $this->location_state,
        ]);
        
        return implode(' - ', $parts);
    }

    // ==========================================
    // Sprint 13: Métodos auxiliares para campos estendidos
    // ==========================================

    /**
     * Travel type labels
     */
    public static function getTravelTypeOptions(): array
    {
        return [
            'none' => 'Sem Deslocamento',
            'local' => 'Local (mesma cidade)',
            'regional' => 'Regional (até 100km)',
            'distant' => 'Distante (mais de 100km)',
        ];
    }

    /**
     * Result type labels
     */
    public static function getResultTypeOptions(): array
    {
        return [
            'pending' => 'Aguardando',
            'successful' => 'Realizado com Sucesso',
            'partial' => 'Parcialmente Realizado',
            'rescheduled' => 'Redesignado',
            'cancelled_court' => 'Cancelado pelo Juízo',
            'cancelled_party' => 'Cancelado pela Parte',
            'failed' => 'Não Realizado',
        ];
    }

    /**
     * Result type colors
     */
    public static function getResultTypeColors(): array
    {
        return [
            'pending' => 'warning',
            'successful' => 'success',
            'partial' => 'info',
            'rescheduled' => 'gray',
            'cancelled_court' => 'danger',
            'cancelled_party' => 'danger',
            'failed' => 'danger',
        ];
    }

    /**
     * Travel type colors
     */
    public static function getTravelTypeColors(): array
    {
        return [
            'none' => 'gray',
            'local' => 'info',
            'regional' => 'warning',
            'distant' => 'danger',
        ];
    }

    /**
     * Retorna custo total de viagem formatado
     */
    public function getFormattedTravelCostAttribute(): string
    {
        return 'R$ ' . number_format($this->travel_cost ?? 0, 2, ',', '.');
    }

    /**
     * Verifica se tem documentos necessários
     */
    public function hasRequiredDocuments(): bool
    {
        return $this->documents_received;
    }

    /**
     * Verifica se precisa de substabelecimento ou procuração
     */
    public function needsLegalDocuments(): bool
    {
        return $this->has_substabelecimento || $this->has_procuracao;
    }

    /**
     * Retorna informações do solicitante formatadas
     */
    public function getRequesterInfoAttribute(): ?string
    {
        if (!$this->requester_name) {
            return null;
        }

        $info = $this->requester_name;
        if ($this->requester_oab) {
            $info .= " (OAB: {$this->requester_oab})";
        }
        return $info;
    }

    /**
     * Retorna informações do juízo formatadas
     */
    public function getCourtInfoAttribute(): ?string
    {
        if (!$this->judge_name) {
            return null;
        }

        $info = "Juiz(a): {$this->judge_name}";
        if ($this->court_secretary) {
            $info .= " | Secretário(a): {$this->court_secretary}";
        }
        return $info;
    }

    /**
     * Scope para serviços com follow-up pendente
     */
    public function scopeNeedsFollowup($query)
    {
        return $query->where('requires_followup', true)
            ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope para serviços sem documentos
     */
    public function scopeMissingDocuments($query)
    {
        return $query->where(function ($q) {
            $q->where('has_substabelecimento', true)
              ->orWhere('has_procuracao', true);
        })->where('documents_received', false);
    }

    /**
     * Rating options
     */
    public static function getRatingOptions(): array
    {
        return [
            1 => '⭐ Ruim',
            2 => '⭐⭐ Regular',
            3 => '⭐⭐⭐ Bom',
            4 => '⭐⭐⭐⭐ Muito Bom',
            5 => '⭐⭐⭐⭐⭐ Excelente',
        ];
    }
}
