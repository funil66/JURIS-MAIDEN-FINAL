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

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Faturas
     */
    protected static string $uidPrefix = 'FAT';

    protected $fillable = [
        'uid',
        'client_id',
        'contract_id',
        'process_id',
        'created_by',
        'invoice_number',
        'description',
        'reference',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'paid_date',
        'cancelled_at',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'interest',
        'fine',
        'total',
        'amount_paid',
        'balance',
        'payment_method',
        'transaction_id',
        'payment_reference',
        'status',
        'invoice_type',
        'billing_type',
        'nfse_number',
        'nfse_link',
        'nfse_emitted_at',
        'notes',
        'internal_notes',
        'terms',
        'metadata',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'cancelled_at' => 'date',
        'nfse_emitted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'interest' => 'decimal:2',
        'fine' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Fatura {$eventName}");
    }

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partial', 'overdue']);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->whereIn('status', ['pending', 'partial'])
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['pending', 'partial']) && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return $this->due_date->diffInDays(now());
    }

    public function getDaysUntilDueAttribute(): ?int
    {
        if (in_array($this->status, ['paid', 'cancelled'])) return null;
        return now()->diffInDays($this->due_date, false);
    }

    public function getPaidPercentageAttribute(): float
    {
        if ($this->total <= 0) return 0;
        return round(($this->amount_paid / $this->total) * 100, 2);
    }

    public function getFormattedStatusAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    public function getFormattedInvoiceTypeAttribute(): string
    {
        return self::getInvoiceTypeOptions()[$this->invoice_type] ?? $this->invoice_type;
    }

    public function getPeriodDescriptionAttribute(): string
    {
        if (!$this->period_start && !$this->period_end) {
            return 'Sem período definido';
        }
        
        $start = $this->period_start?->format('d/m/Y') ?? '-';
        $end = $this->period_end?->format('d/m/Y') ?? '-';
        
        return "{$start} a {$end}";
    }

    public function getTotalHoursAttribute(): float
    {
        return $this->items()
            ->where('item_type', 'time')
            ->sum('quantity');
    }

    // ==========================================
    // MÉTODOS DE CÁLCULO
    // ==========================================

    /**
     * Recalcular totais da fatura
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        
        // Calcular desconto
        $discountAmount = $this->discount_percentage > 0 
            ? ($subtotal * $this->discount_percentage / 100)
            : $this->discount_amount;
        
        // Calcular total
        $total = $subtotal - $discountAmount + $this->interest + $this->fine;
        
        // Calcular saldo
        $balance = $total - $this->amount_paid;
        
        $this->update([
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'total' => round($total, 2),
            'balance' => round($balance, 2),
        ]);
    }

    /**
     * Atualizar status baseado em pagamentos
     */
    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        
        $this->update([
            'amount_paid' => $totalPaid,
            'balance' => $this->total - $totalPaid,
        ]);

        if ($totalPaid >= $this->total) {
            $this->markAsPaid();
        } elseif ($totalPaid > 0) {
            $this->update(['status' => 'partial']);
        }
    }

    // ==========================================
    // MÉTODOS DE WORKFLOW
    // ==========================================

    /**
     * Enviar fatura (mudar de rascunho para pendente)
     */
    public function send(): void
    {
        if ($this->status !== 'draft') return;
        
        $this->recalculateTotals();
        $this->update(['status' => 'pending']);
    }

    /**
     * Marcar como paga
     */
    public function markAsPaid(?string $paymentMethod = null, ?\DateTimeInterface $paidDate = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => $paidDate ?? now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'amount_paid' => $this->total,
            'balance' => 0,
        ]);

        // Atualizar time entries vinculados
        $this->items()->whereNotNull('time_entry_id')->each(function ($item) {
            $item->timeEntry?->markAsBilled($this->id);
        });
    }

    /**
     * Registrar pagamento parcial
     */
    public function registerPayment(float $amount, ?string $paymentMethod = null, ?\DateTimeInterface $date = null, ?string $reference = null): InvoicePayment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'payment_date' => $date ?? now(),
            'payment_method' => $paymentMethod,
            'reference' => $reference,
            'recorded_by' => auth()->id(),
        ]);

        $this->updatePaymentStatus();
        
        return $payment;
    }

    /**
     * Cancelar fatura
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $reason,
        ]);

        // Liberar time entries
        $this->items()->whereNotNull('time_entry_id')->each(function ($item) {
            $item->timeEntry?->update([
                'invoice_id' => null,
                'billed_at' => null,
                'status' => 'approved',
            ]);
        });
    }

    /**
     * Marcar como vencida
     */
    public function markAsOverdue(): void
    {
        if (!$this->is_overdue) return;
        
        $this->update(['status' => 'overdue']);
    }

    /**
     * Aplicar juros e multa por atraso
     */
    public function applyLateFees(float $interestPercentage = 1, float $finePercentage = 2): void
    {
        if (!$this->is_overdue) return;

        $daysLate = $this->days_overdue;
        $interest = ($this->subtotal * $interestPercentage / 100) * ($daysLate / 30);
        $fine = $this->subtotal * $finePercentage / 100;

        $this->update([
            'interest' => round($interest, 2),
            'fine' => round($fine, 2),
        ]);

        $this->recalculateTotals();
    }

    // ==========================================
    // MÉTODOS DE GERAÇÃO
    // ==========================================

    /**
     * Gerar número da fatura
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return "NF{$year}-" . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Criar fatura a partir de time entries
     */
    public static function createFromTimeEntries(array $timeEntryIds, int $clientId, array $data = []): self
    {
        $timeEntries = TimeEntry::whereIn('id', $timeEntryIds)
            ->where('status', 'approved')
            ->whereNull('invoice_id')
            ->get();

        $invoice = self::create([
            'client_id' => $clientId,
            'issue_date' => $data['issue_date'] ?? now(),
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            'description' => $data['description'] ?? 'Fatura de Honorários',
            'invoice_type' => 'time_billing',
            'billing_type' => 'hourly',
            'period_start' => $timeEntries->min('work_date'),
            'period_end' => $timeEntries->max('work_date'),
            'created_by' => auth()->id(),
            'status' => 'draft',
            ...$data,
        ]);

        foreach ($timeEntries as $entry) {
            $invoice->items()->create([
                'time_entry_id' => $entry->id,
                'description' => $entry->description,
                'item_type' => 'time',
                'quantity' => $entry->duration_minutes / 60,
                'unit' => 'hora',
                'unit_price' => $entry->hourly_rate,
                'total' => $entry->total_amount,
            ]);
        }

        $invoice->recalculateTotals();
        
        return $invoice;
    }

    /**
     * Criar fatura a partir de parcela de contrato
     */
    public static function createFromInstallment(ContractInstallment $installment, array $data = []): self
    {
        $contract = $installment->contract;
        
        $invoice = self::create([
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'process_id' => $contract->process_id,
            'issue_date' => $data['issue_date'] ?? now(),
            'due_date' => $data['due_date'] ?? $installment->due_date,
            'description' => $data['description'] ?? "Parcela {$installment->installment_number} - {$contract->title}",
            'invoice_type' => 'services',
            'billing_type' => 'fixed',
            'created_by' => auth()->id(),
            'status' => 'draft',
            ...$data,
        ]);

        $invoice->items()->create([
            'contract_installment_id' => $installment->id,
            'description' => $installment->description ?: "Parcela {$installment->installment_number}/{$contract->installments_count}",
            'item_type' => 'installment',
            'quantity' => 1,
            'unit' => 'parcela',
            'unit_price' => $installment->final_amount,
            'total' => $installment->final_amount,
        ]);

        $invoice->recalculateTotals();
        
        return $invoice;
    }

    // ==========================================
    // OPÇÕES ESTÁTICAS
    // ==========================================

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'partial' => 'Parcial',
            'paid' => 'Pago',
            'overdue' => 'Vencido',
            'cancelled' => 'Cancelado',
        ];
    }

    public static function getInvoiceTypeOptions(): array
    {
        return [
            'services' => 'Serviços',
            'time_billing' => 'Horas Trabalhadas',
            'retainer' => 'Mensalidade',
            'expenses' => 'Reembolso de Despesas',
            'success_fee' => 'Honorários de Êxito',
            'other' => 'Outro',
        ];
    }

    public static function getBillingTypeOptions(): array
    {
        return [
            'fixed' => 'Valor Fixo',
            'hourly' => 'Por Hora',
            'mixed' => 'Misto',
        ];
    }

    public static function getPaymentMethodOptions(): array
    {
        return [
            'pix' => 'PIX',
            'transfer' => 'Transferência Bancária',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'boleto' => 'Boleto',
            'check' => 'Cheque',
            'cash' => 'Dinheiro',
        ];
    }
}
