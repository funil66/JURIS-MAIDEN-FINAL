<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ContractInstallment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Parcelas
     */
    protected static string $uidPrefix = 'PAR';

    protected $fillable = [
        'uid',
        'contract_id',
        'installment_number',
        'description',
        'amount',
        'discount',
        'interest',
        'fine',
        'final_amount',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'transaction_id',
        'notes',
        'invoice_id',
        'transaction_id_ref',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'interest' => 'decimal:2',
        'fine' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'installment_number' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Parcela {$eventName}");
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id_ref');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'overdue']);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeDueToday($query)
    {
        return $query->where('status', 'pending')
            ->whereDate('due_date', today());
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return $this->due_date->diffInDays(now());
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if ($this->status !== 'pending') return null;
        return now()->diffInDays($this->due_date, false);
    }

    public function getFormattedStatusAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: "Parcela {$this->installment_number}";
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Registrar pagamento
     */
    public function markAsPaid(?string $paymentMethod = null, ?string $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'transaction_id' => $transactionId,
        ]);

        $this->contract->updateTotals();
    }

    /**
     * Calcular valor final com juros e multa
     */
    public function calculateFinalAmount(): float
    {
        $amount = (float) $this->amount;
        $discount = (float) $this->discount;
        $interest = (float) $this->interest;
        $fine = (float) $this->fine;

        return $amount - $discount + $interest + $fine;
    }

    /**
     * Aplicar juros de atraso
     */
    public function applyLateFees(float $interestPercentage = 1, float $finePercentage = 2): void
    {
        if ($this->status !== 'overdue') return;

        $daysLate = $this->days_overdue;
        $interest = ($this->amount * $interestPercentage / 100) * ($daysLate / 30);
        $fine = $this->amount * $finePercentage / 100;

        $this->update([
            'interest' => round($interest, 2),
            'fine' => round($fine, 2),
            'final_amount' => $this->calculateFinalAmount(),
        ]);
    }

    /**
     * Cancelar parcela
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
        $this->contract->updateTotals();
    }

    // ==========================================
    // OPÇÕES ESTÁTICAS
    // ==========================================

    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'overdue' => 'Vencido',
            'cancelled' => 'Cancelado',
            'renegotiated' => 'Renegociado',
        ];
    }
}
