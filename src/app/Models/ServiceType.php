<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Tipos de Serviço
     */
    public static function getUidPrefix(): string
    {
        return 'TPS';
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_price',
        'default_deadline_days',
        'icon',
        'color',
        'requires_deadline',
        'requires_location',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'default_deadline_days' => 'integer',
        'requires_deadline' => 'boolean',
        'requires_location' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Serviços deste tipo
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Scope para tipos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Retorna preço formatado
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->default_price, 2, ',', '.');
    }
}
