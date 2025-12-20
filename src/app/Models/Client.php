<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'type',
        'name',
        'document',
        'rg',
        'oab',
        'email',
        'phone',
        'whatsapp',
        'cep',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'company_name',
        'trading_name',
        'contact_person',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'document', 'email', 'phone', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Serviços do cliente
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Eventos do cliente
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Transações do cliente
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Verifica se é pessoa física
     */
    public function isPessoaFisica(): bool
    {
        return $this->type === 'pf';
    }

    /**
     * Verifica se é pessoa jurídica
     */
    public function isPessoaJuridica(): bool
    {
        return $this->type === 'pj';
    }

    /**
     * Retorna documento formatado
     */
    public function getFormattedDocumentAttribute(): string
    {
        $doc = preg_replace('/[^0-9]/', '', $this->document);
        
        if (strlen($doc) === 11) {
            // CPF: 000.000.000-00
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        } elseif (strlen($doc) === 14) {
            // CNPJ: 00.000.000/0000-00
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }
        
        return $this->document;
    }

    /**
     * Retorna endereço completo
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->number,
            $this->complement,
            $this->neighborhood,
            $this->city,
            $this->state,
            $this->cep,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Scope para clientes ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para pessoa física
     */
    public function scopePessoaFisica($query)
    {
        return $query->where('type', 'pf');
    }

    /**
     * Scope para pessoa jurídica
     */
    public function scopePessoaJuridica($query)
    {
        return $query->where('type', 'pj');
    }
}
