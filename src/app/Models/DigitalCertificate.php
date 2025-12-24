<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class DigitalCertificate extends Model
{
    use HasFactory, SoftDeletes, HasGlobalUid;

    /**
     * Prefixo do UID para Certificados Digitais
     */
    protected static string $uidPrefix = 'CRT';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'description',
        'type',
        'holder_name',
        'holder_document',
        'holder_email',
        'serial_number',
        'issuer',
        'valid_from',
        'valid_until',
        'certificate_path',
        'certificate_password',
        'status',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Tipos de certificado disponíveis
     */
    public const TYPE_A1 = 'a1';
    public const TYPE_A3_TOKEN = 'a3_token';
    public const TYPE_A3_CARD = 'a3_card';
    public const TYPE_CLOUD = 'cloud';

    public const TYPES = [
        self::TYPE_A1 => 'A1 (Arquivo)',
        self::TYPE_A3_TOKEN => 'A3 (Token USB)',
        self::TYPE_A3_CARD => 'A3 (Cartão)',
        self::TYPE_CLOUD => 'Nuvem',
    ];

    /**
     * Status do certificado
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_PENDING = 'pending';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Ativo',
        self::STATUS_EXPIRED => 'Expirado',
        self::STATUS_REVOKED => 'Revogado',
        self::STATUS_PENDING => 'Pendente',
    ];

    public const STATUS_COLORS = [
        self::STATUS_ACTIVE => 'success',
        self::STATUS_EXPIRED => 'danger',
        self::STATUS_REVOKED => 'danger',
        self::STATUS_PENDING => 'warning',
    ];

    /**
     * Relacionamento: Usuário proprietário do certificado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Assinaturas feitas com este certificado
     */
    public function signers(): HasMany
    {
        return $this->hasMany(SignatureSigner::class, 'certificate_id');
    }

    /**
     * Accessor: Senha descriptografada
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        if (!$this->certificate_password) {
            return null;
        }

        try {
            return Crypt::decryptString($this->certificate_password);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mutator: Criptografar senha antes de salvar
     */
    public function setCertificatePasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['certificate_password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['certificate_password'] = null;
        }
    }

    /**
     * Verificar se o certificado está válido
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (!$this->valid_until) {
            return false;
        }

        return $this->valid_until->isFuture();
    }

    /**
     * Verificar se está prestes a expirar (30 dias)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        return $this->valid_until->diffInDays(now()) <= $days && $this->valid_until->isFuture();
    }

    /**
     * Dias restantes de validade
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        if ($this->valid_until->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->valid_until);
    }

    /**
     * Label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Scope: Certificados ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Certificados válidos
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('valid_until', '>', now());
    }

    /**
     * Scope: Certificados prestes a expirar
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('valid_until', '>', now())
            ->where('valid_until', '<=', now()->addDays($days));
    }

    /**
     * Definir como padrão (remove padrão de outros)
     */
    public function setAsDefault(): void
    {
        // Remover padrão de outros certificados do mesmo usuário
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Atualizar status baseado na validade
     */
    public function updateStatusFromValidity(): void
    {
        if ($this->valid_until && $this->valid_until->isPast()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }
    }
}
