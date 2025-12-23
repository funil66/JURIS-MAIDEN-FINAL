<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Transações
     */
    public static function getUidPrefix(): string
    {
        return 'TRX';
    }

    protected $fillable = [
        'code',
        'type',
        'service_id',
        'client_id',
        'payment_method_id',
        'category',
        'amount',
        'discount',
        'fees',
        'net_amount',
        'due_date',
        'paid_date',
        'competence_date',
        'status',
        'installment_number',
        'total_installments',
        'installment_group',
        'description',
        'notes',
        'receipt_path',
        'invoice_number',
        'bank_reference',
        'is_reconciled',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'competence_date' => 'date',
        'is_reconciled' => 'boolean',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'paid_date', 'amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot do model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Gerar código automático
            if (empty($transaction->code)) {
                $year = date('Y');
                $lastTransaction = static::whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->first();
                
                $nextNumber = $lastTransaction 
                    ? (int) substr($lastTransaction->code, -4) + 1 
                    : 1;
                
                $transaction->code = sprintf('TRX-%s-%04d', $year, $nextNumber);
            }

            // Calcular valor líquido
            $transaction->net_amount = ($transaction->amount ?? 0) 
                - ($transaction->discount ?? 0) 
                - ($transaction->fees ?? 0);

            // Data de competência padrão
            if (empty($transaction->competence_date)) {
                $transaction->competence_date = $transaction->due_date ?? now();
            }
        });

        static::updating(function ($transaction) {
            $transaction->net_amount = ($transaction->amount ?? 0) 
                - ($transaction->discount ?? 0) 
                - ($transaction->fees ?? 0);
        });
    }

    /**
     * Serviço relacionado
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Cliente relacionado
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Método de pagamento
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Categorias de receita
     */
    public static function getIncomeCategories(): array
    {
        return [
            'honorarios' => 'Honorários',
            'audiencia' => 'Audiência',
            'protocolo' => 'Protocolo',
            'diligencia' => 'Diligência',
            'copias' => 'Cópias',
            'consultoria' => 'Consultoria',
            'outros_receita' => 'Outros (Receita)',
        ];
    }

    /**
     * Categorias de despesa
     */
    public static function getExpenseCategories(): array
    {
        return [
            'custas' => 'Custas Processuais',
            'deslocamento' => 'Deslocamento',
            'combustivel' => 'Combustível',
            'estacionamento' => 'Estacionamento',
            'alimentacao' => 'Alimentação',
            'hospedagem' => 'Hospedagem',
            'xerox' => 'Xerox/Cópias',
            'correios' => 'Correios',
            'cartorio' => 'Cartório',
            'taxas' => 'Taxas',
            'outros_despesa' => 'Outros (Despesa)',
        ];
    }

    /**
     * Status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'paid' => 'Pago/Recebido',
            'partial' => 'Parcial',
            'overdue' => 'Vencido',
            'cancelled' => 'Cancelado',
        ];
    }

    /**
     * Status colors
     */
    public static function getStatusColors(): array
    {
        return [
            'pending' => 'warning',
            'paid' => 'success',
            'partial' => 'info',
            'overdue' => 'danger',
            'cancelled' => 'gray',
        ];
    }

    /**
     * Type options
     */
    public static function getTypeOptions(): array
    {
        return [
            'income' => 'Receita',
            'expense' => 'Despesa',
        ];
    }

    /**
     * Scopes
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

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
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('competence_date', now()->month)
            ->whereYear('competence_date', now()->year);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('competence_date', [$startDate, $endDate]);
    }

    /**
     * Verifica se está vencido
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' 
            && $this->due_date 
            && $this->due_date->isPast();
    }

    /**
     * Retorna valor formatado
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->type === 'expense' ? '-' : '+';
        return $prefix . ' R$ ' . number_format($this->net_amount, 2, ',', '.');
    }

    /**
     * Retorna label de parcela
     */
    public function getInstallmentLabelAttribute(): ?string
    {
        if ($this->total_installments && $this->installment_number) {
            return $this->installment_number . '/' . $this->total_installments;
        }
        return null;
    }
}
