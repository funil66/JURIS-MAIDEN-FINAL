<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'description',
        'service_type',
        'unit_price',
        'quantity',
        'total',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Calcula o total do item
     */
    public function calculateTotal(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total = $item->calculateTotal();
        });
    }

    // ==========================================
    // OPÇÕES ESTÁTICAS
    // ==========================================

    public static function getServiceTypeOptions(): array
    {
        return [
            'consultation' => 'Consulta',
            'contract_drafting' => 'Elaboração de Contrato',
            'petition' => 'Petição',
            'defense' => 'Defesa',
            'appeal' => 'Recurso',
            'hearing' => 'Audiência',
            'due_diligence' => 'Due Diligence',
            'legal_opinion' => 'Parecer Jurídico',
            'negotiation' => 'Negociação',
            'other' => 'Outro',
        ];
    }
}
