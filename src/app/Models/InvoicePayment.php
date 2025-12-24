<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InvoicePayment extends Model
{
    use HasFactory, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Pagamentos
     */
    protected static string $uidPrefix = 'PGT';

    protected $fillable = [
        'uid',
        'invoice_id',
        'transaction_id',
        'recorded_by',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Pagamento {$eventName}");
    }

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($payment) {
            $payment->invoice->updatePaymentStatus();
        });

        static::deleted(function ($payment) {
            $payment->invoice->updatePaymentStatus();
        });
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getFormattedPaymentMethodAttribute(): string
    {
        return Invoice::getPaymentMethodOptions()[$this->payment_method] ?? $this->payment_method ?? '-';
    }
}
