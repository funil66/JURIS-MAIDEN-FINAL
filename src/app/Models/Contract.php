<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contract extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Contratos
     */
    protected static string $uidPrefix = 'CTR';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uid',
        'client_id',
        'process_id',
        'responsible_user_id',
        'contract_number',
        'title',
        'description',
        'type',
        'area',
        'fee_type',
        'total_value',
        'success_fee_percentage',
        'success_fee_base',
        'hourly_rate',
        'minimum_fee',
        'estimated_hours',
        'payment_method',
        'payment_frequency',
        'installments_count',
        'day_of_payment',
        'entry_value',
        'start_date',
        'end_date',
        'signature_date',
        'first_payment_date',
        'auto_renew',
        'renewal_months',
        'renewal_date',
        'status',
        'is_signed',
        'signature_type',
        'signed_at',
        'total_billed',
        'total_paid',
        'total_pending',
        'adjustment_index',
        'adjustment_percentage',
        'next_adjustment_date',
        'last_adjustment_date',
        'attachments',
        'signed_document_path',
        'scope_of_work',
        'exclusions',
        'special_conditions',
        'internal_notes',
        'created_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_value' => 'decimal:2',
        'success_fee_percentage' => 'decimal:2',
        'success_fee_base' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'minimum_fee' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'entry_value' => 'decimal:2',
        'total_billed' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_pending' => 'decimal:2',
        'adjustment_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signature_date' => 'date',
        'first_payment_date' => 'date',
        'renewal_date' => 'date',
        'next_adjustment_date' => 'date',
        'last_adjustment_date' => 'date',
        'signed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_signed' => 'boolean',
        'installments_count' => 'integer',
        'day_of_payment' => 'integer',
        'renewal_months' => 'integer',
        'attachments' => 'array',
    ];

    /**
     * Configure activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Contrato {$eventName}");
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Cliente do contrato
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Processo vinculado
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Responsável pelo contrato
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Criador do contrato
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Quem cancelou o contrato
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Parcelas do contrato
     */
    public function installments(): HasMany
    {
        return $this->hasMany(ContractInstallment::class)->orderBy('installment_number');
    }

    /**
     * Itens do contrato
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    /**
     * Time entries vinculados
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Contratos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Contratos pendentes de assinatura
     */
    public function scopePendingSignature($query)
    {
        return $query->where('status', 'pending_signature');
    }

    /**
     * Contratos que vencem em breve
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Contratos expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now())
            ->whereIn('status', ['active', 'pending_signature']);
    }

    /**
     * Contratos com parcelas vencidas
     */
    public function scopeWithOverdueInstallments($query)
    {
        return $query->whereHas('installments', function ($q) {
            $q->where('status', 'overdue');
        });
    }

    /**
     * Contratos por tipo de honorário
     */
    public function scopeByFeeType($query, string $type)
    {
        return $query->where('fee_type', $type);
    }

    /**
     * Contratos assinados
     */
    public function scopeSigned($query)
    {
        return $query->where('is_signed', true);
    }

    /**
     * Contratos por cliente
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Valor restante a pagar
     */
    public function getRemainingValueAttribute(): float
    {
        return (float) $this->total_value - (float) $this->total_paid;
    }

    /**
     * Percentual pago
     */
    public function getPaidPercentageAttribute(): float
    {
        if ($this->total_value <= 0) return 0;
        return round(($this->total_paid / $this->total_value) * 100, 2);
    }

    /**
     * Número de parcelas pagas
     */
    public function getPaidInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'paid')->count();
    }

    /**
     * Número de parcelas pendentes
     */
    public function getPendingInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'pending')->count();
    }

    /**
     * Número de parcelas vencidas
     */
    public function getOverdueInstallmentsCountAttribute(): int
    {
        return $this->installments()->where('status', 'overdue')->count();
    }

    /**
     * Verifica se está vencendo
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->end_date) return false;
        return $this->end_date->isBetween(now(), now()->addDays(30));
    }

    /**
     * Verifica se está expirado
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->end_date) return false;
        return $this->end_date->isPast();
    }

    /**
     * Dias para vencimento
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->end_date) return null;
        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Tipo formatado
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::getTypeOptions()[$this->type] ?? $this->type;
    }

    /**
     * Tipo de honorário formatado
     */
    public function getFormattedFeeTypeAttribute(): string
    {
        return self::getFeeTypeOptions()[$this->fee_type] ?? $this->fee_type;
    }

    /**
     * Status formatado
     */
    public function getFormattedStatusAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    // ==========================================
    // MÉTODOS DE WORKFLOW
    // ==========================================

    /**
     * Gerar parcelas do contrato
     */
    public function generateInstallments(): void
    {
        // Remove parcelas existentes não pagas
        $this->installments()->where('status', '!=', 'paid')->delete();

        $value = (float) $this->total_value - (float) $this->entry_value;
        $installmentValue = $value / $this->installments_count;
        
        $startDate = $this->first_payment_date ?? $this->start_date ?? now();

        for ($i = 1; $i <= $this->installments_count; $i++) {
            $dueDate = $startDate->copy();
            
            if ($this->payment_frequency === 'monthly') {
                $dueDate = $startDate->copy()->addMonths($i - 1);
            } elseif ($this->payment_frequency === 'quarterly') {
                $dueDate = $startDate->copy()->addMonths(($i - 1) * 3);
            } elseif ($this->payment_frequency === 'biannual') {
                $dueDate = $startDate->copy()->addMonths(($i - 1) * 6);
            } elseif ($this->payment_frequency === 'annual') {
                $dueDate = $startDate->copy()->addYears($i - 1);
            }

            if ($this->day_of_payment) {
                $dueDate = $dueDate->setDay(min($this->day_of_payment, $dueDate->daysInMonth));
            }

            ContractInstallment::create([
                'contract_id' => $this->id,
                'installment_number' => $i,
                'description' => "Parcela {$i}/{$this->installments_count}",
                'amount' => round($installmentValue, 2),
                'final_amount' => round($installmentValue, 2),
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }

        // Gerar parcela de entrada se houver
        if ($this->entry_value > 0) {
            ContractInstallment::create([
                'contract_id' => $this->id,
                'installment_number' => 0,
                'description' => 'Entrada',
                'amount' => $this->entry_value,
                'final_amount' => $this->entry_value,
                'due_date' => $this->signature_date ?? now(),
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Ativar contrato
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'is_signed' => true,
            'signed_at' => now(),
        ]);

        if ($this->installments()->count() === 0) {
            $this->generateInstallments();
        }
    }

    /**
     * Suspender contrato
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Reativar contrato
     */
    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Cancelar contrato
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Cancelar parcelas pendentes
        $this->installments()->whereIn('status', ['pending', 'overdue'])->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Concluir contrato
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Atualizar totais
     */
    public function updateTotals(): void
    {
        $paidInstallments = $this->installments()->where('status', 'paid');
        
        $this->update([
            'total_paid' => $paidInstallments->sum('final_amount'),
            'total_pending' => $this->installments()
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('final_amount'),
        ]);
    }

    /**
     * Atualizar status de parcelas vencidas
     */
    public function updateOverdueInstallments(): void
    {
        $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);
    }

    // ==========================================
    // OPÇÕES ESTÁTICAS
    // ==========================================

    /**
     * Tipos de contrato
     */
    public static function getTypeOptions(): array
    {
        return [
            'legal_services' => 'Serviços Jurídicos',
            'consulting' => 'Consultoria',
            'due_diligence' => 'Due Diligence',
            'compliance' => 'Compliance',
            'representation' => 'Representação',
            'advisory' => 'Assessoria Contínua',
            'other' => 'Outro',
        ];
    }

    /**
     * Áreas de atuação
     */
    public static function getAreaOptions(): array
    {
        return [
            'civil' => 'Cível',
            'criminal' => 'Criminal',
            'labor' => 'Trabalhista',
            'tax' => 'Tributário',
            'business' => 'Empresarial',
            'family' => 'Família',
            'consumer' => 'Consumidor',
            'administrative' => 'Administrativo',
            'environmental' => 'Ambiental',
            'real_estate' => 'Imobiliário',
            'intellectual_property' => 'Propriedade Intelectual',
            'general' => 'Geral',
        ];
    }

    /**
     * Tipos de honorário
     */
    public static function getFeeTypeOptions(): array
    {
        return [
            'fixed' => 'Fixo',
            'hourly' => 'Por Hora',
            'per_act' => 'Por Ato',
            'success' => 'Êxito',
            'hybrid' => 'Híbrido',
            'retainer' => 'Mensal/Retenção',
        ];
    }

    /**
     * Métodos de pagamento
     */
    public static function getPaymentMethodOptions(): array
    {
        return [
            'pix' => 'PIX',
            'transfer' => 'Transferência Bancária',
            'credit_card' => 'Cartão de Crédito',
            'boleto' => 'Boleto',
            'check' => 'Cheque',
            'cash' => 'Dinheiro',
        ];
    }

    /**
     * Frequências de pagamento
     */
    public static function getPaymentFrequencyOptions(): array
    {
        return [
            'single' => 'Única',
            'monthly' => 'Mensal',
            'quarterly' => 'Trimestral',
            'biannual' => 'Semestral',
            'annual' => 'Anual',
        ];
    }

    /**
     * Status do contrato
     */
    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'pending_signature' => 'Aguardando Assinatura',
            'active' => 'Ativo',
            'suspended' => 'Suspenso',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'expired' => 'Expirado',
        ];
    }

    /**
     * Tipos de assinatura
     */
    public static function getSignatureTypeOptions(): array
    {
        return [
            'physical' => 'Física',
            'digital' => 'Digital',
            'docusign' => 'DocuSign',
            'clicksign' => 'ClickSign',
            'd4sign' => 'D4Sign',
            'adobe_sign' => 'Adobe Sign',
        ];
    }

    /**
     * Índices de reajuste
     */
    public static function getAdjustmentIndexOptions(): array
    {
        return [
            'IPCA' => 'IPCA',
            'IGPM' => 'IGP-M',
            'INPC' => 'INPC',
            'IPC' => 'IPC',
            'custom' => 'Personalizado',
        ];
    }
}
