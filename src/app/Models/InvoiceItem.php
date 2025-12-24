<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'time_entry_id',
        'contract_installment_id',
        'description',
        'item_type',
        'quantity',
        'unit',
        'unit_price',
        'discount',
        'total',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total = ($item->quantity * $item->unit_price) - $item->discount;
        });

        static::saved(function ($item) {
            $item->invoice->recalculateTotals();
        });

        static::deleted(function ($item) {
            $item->invoice->recalculateTotals();
        });
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function contractInstallment(): BelongsTo
    {
        return $this->belongsTo(ContractInstallment::class);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getFormattedItemTypeAttribute(): string
    {
        return self::getItemTypeOptions()[$this->item_type] ?? $this->item_type;
    }

    // ==========================================
    // OPÇÕES ESTÁTICAS
    // ==========================================

    public static function getItemTypeOptions(): array
    {
        return [
            'service' => 'Serviço',
            'time' => 'Hora Trabalhada',
            'expense' => 'Despesa',
            'installment' => 'Parcela',
            'other' => 'Outro',
        ];
    }
}
