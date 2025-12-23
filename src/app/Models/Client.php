<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Authenticatable implements FilamentUser
{
    use HasFactory, SoftDeletes, LogsActivity, Notifiable, HasGlobalUid;

    /**
     * Prefixo do UID para Clientes
     */
    public static function getUidPrefix(): string
    {
        return 'CLI';
    }

    protected $fillable = [
        'type',
        'name',
        'document',
        'rg',
        'oab',
        'email',
        'password',
        'portal_access',
        'portal_last_login_at',
        'portal_token',
        'portal_token_expires_at',
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

    protected $hidden = [
        'password',
        'remember_token',
        'portal_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'portal_access' => 'boolean',
        'portal_last_login_at' => 'datetime',
        'portal_token_expires_at' => 'datetime',
        'password' => 'hashed',
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

    /**
     * Check if client can access Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'client') {
            return $this->is_active && $this->portal_access;
        }

        return false;
    }

    /**
     * Scope para clientes com acesso ao portal
     */
    public function scopeWithPortalAccess($query)
    {
        return $query->where('portal_access', true);
    }

    /**
     * Gerar token de acesso ao portal
     */
    public function generatePortalToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);
        
        $this->update([
            'portal_token' => hash('sha256', $token),
            'portal_token_expires_at' => now()->addHours(24),
        ]);

        return $token;
    }

    /**
     * Verificar token de acesso
     */
    public function verifyPortalToken(string $token): bool
    {
        if (!$this->portal_token || !$this->portal_token_expires_at) {
            return false;
        }

        if ($this->portal_token_expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->portal_token, hash('sha256', $token));
    }

    /**
     * Registrar login no portal
     */
    public function recordPortalLogin(): void
    {
        $this->update([
            'portal_last_login_at' => now(),
        ]);
    }

    /**
     * Documentos gerados do cliente
     */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
