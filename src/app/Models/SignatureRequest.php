<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SignatureRequest extends Model
{
    use HasFactory, SoftDeletes, HasGlobalUid;

    /**
     * Prefixo do UID para Solicitações de Assinatura
     */
    protected static string $uidPrefix = 'SIG';

    protected $fillable = [
        'uid',
        'signable_type',
        'signable_id',
        'document_name',
        'document_path',
        'signed_document_path',
        'document_hash',
        'requested_by',
        'requested_at',
        'signature_type',
        'verification_method',
        'status',
        'expires_at',
        'completed_at',
        'message',
        'sequential_signing',
        'send_notifications',
        'reminder_schedule',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'sequential_signing' => 'boolean',
        'send_notifications' => 'boolean',
        'reminder_schedule' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Tipos de assinatura
     */
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_ELECTRONIC = 'electronic';
    public const TYPE_DIGITAL = 'digital';
    public const TYPE_QUALIFIED = 'qualified';

    public const SIGNATURE_TYPES = [
        self::TYPE_SIMPLE => 'Assinatura Simples',
        self::TYPE_ELECTRONIC => 'Assinatura Eletrônica',
        self::TYPE_DIGITAL => 'Assinatura Digital (ICP-Brasil)',
        self::TYPE_QUALIFIED => 'Assinatura Qualificada',
    ];

    public const SIGNATURE_TYPE_DESCRIPTIONS = [
        self::TYPE_SIMPLE => 'Clique para assinar, sem verificação adicional',
        self::TYPE_ELECTRONIC => 'Com verificação por código enviado ao signatário',
        self::TYPE_DIGITAL => 'Requer certificado digital ICP-Brasil',
        self::TYPE_QUALIFIED => 'Certificado digital + carimbo do tempo',
    ];

    /**
     * Métodos de verificação
     */
    public const VERIFICATION_NONE = 'none';
    public const VERIFICATION_EMAIL = 'email';
    public const VERIFICATION_SMS = 'sms';
    public const VERIFICATION_WHATSAPP = 'whatsapp';
    public const VERIFICATION_SELFIE = 'selfie';

    public const VERIFICATION_METHODS = [
        self::VERIFICATION_NONE => 'Nenhuma',
        self::VERIFICATION_EMAIL => 'Código por E-mail',
        self::VERIFICATION_SMS => 'Código por SMS',
        self::VERIFICATION_WHATSAPP => 'Código por WhatsApp',
        self::VERIFICATION_SELFIE => 'Selfie com Documento',
    ];

    /**
     * Status da solicitação
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_SIGNED = 'partially_signed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Rascunho',
        self::STATUS_PENDING => 'Aguardando Assinaturas',
        self::STATUS_PARTIALLY_SIGNED => 'Parcialmente Assinado',
        self::STATUS_COMPLETED => 'Concluído',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_EXPIRED => 'Expirado',
        self::STATUS_REJECTED => 'Rejeitado',
    ];

    public const STATUS_COLORS = [
        self::STATUS_DRAFT => 'gray',
        self::STATUS_PENDING => 'warning',
        self::STATUS_PARTIALLY_SIGNED => 'info',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
        self::STATUS_EXPIRED => 'danger',
        self::STATUS_REJECTED => 'danger',
    ];

    public const STATUS_ICONS = [
        self::STATUS_DRAFT => 'heroicon-o-document',
        self::STATUS_PENDING => 'heroicon-o-clock',
        self::STATUS_PARTIALLY_SIGNED => 'heroicon-o-pencil-square',
        self::STATUS_COMPLETED => 'heroicon-o-check-circle',
        self::STATUS_CANCELLED => 'heroicon-o-x-circle',
        self::STATUS_EXPIRED => 'heroicon-o-exclamation-triangle',
        self::STATUS_REJECTED => 'heroicon-o-x-mark',
    ];

    /**
     * Boot: Definir valores padrão
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->requested_at) {
                $model->requested_at = now();
            }
        });
    }

    /**
     * Relacionamento: Documento assinável (polimórfico)
     */
    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relacionamento: Usuário que solicitou
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Relacionamento: Signatários
     */
    public function signers(): HasMany
    {
        return $this->hasMany(SignatureSigner::class)->orderBy('signing_order');
    }

    /**
     * Relacionamento: Logs de auditoria
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(SignatureAuditLog::class)->orderByDesc('created_at');
    }

    /**
     * Accessor: Label do tipo de assinatura
     */
    public function getSignatureTypeLabelAttribute(): string
    {
        return self::SIGNATURE_TYPES[$this->signature_type] ?? $this->signature_type;
    }

    /**
     * Accessor: Label do método de verificação
     */
    public function getVerificationMethodLabelAttribute(): string
    {
        return self::VERIFICATION_METHODS[$this->verification_method] ?? $this->verification_method;
    }

    /**
     * Accessor: Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Accessor: Cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Accessor: Ícone do status
     */
    public function getStatusIconAttribute(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'heroicon-o-document';
    }

    /**
     * Accessor: Total de signatários
     */
    public function getTotalSignersAttribute(): int
    {
        return $this->signers()->count();
    }

    /**
     * Accessor: Total de assinaturas concluídas
     */
    public function getSignedCountAttribute(): int
    {
        return $this->signers()->where('status', SignatureSigner::STATUS_SIGNED)->count();
    }

    /**
     * Accessor: Progresso das assinaturas (porcentagem)
     */
    public function getProgressAttribute(): float
    {
        $total = $this->total_signers;
        if ($total === 0) {
            return 0;
        }

        return round(($this->signed_count / $total) * 100, 1);
    }

    /**
     * Verificar se está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar se pode ser assinado
     */
    public function canBeSigned(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PARTIALLY_SIGNED,
        ]) && !$this->isExpired();
    }

    /**
     * Verificar se está concluído
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Obter próximo signatário (para assinatura sequencial)
     */
    public function getNextSigner(): ?SignatureSigner
    {
        return $this->signers()
            ->where('status', SignatureSigner::STATUS_PENDING)
            ->where('role', '!=', SignatureSigner::ROLE_OBSERVER)
            ->orderBy('signing_order')
            ->first();
    }

    /**
     * Obter signatários pendentes
     */
    public function getPendingSigners()
    {
        return $this->signers()
            ->where('status', SignatureSigner::STATUS_PENDING)
            ->where('role', '!=', SignatureSigner::ROLE_OBSERVER);
    }

    /**
     * Calcular hash do documento
     */
    public function calculateDocumentHash(): ?string
    {
        if (!$this->document_path || !file_exists(storage_path('app/' . $this->document_path))) {
            return null;
        }

        return hash_file('sha256', storage_path('app/' . $this->document_path));
    }

    /**
     * Atualizar status automaticamente baseado nos signatários
     */
    public function updateStatus(): void
    {
        // Verificar se expirou
        if ($this->isExpired() && $this->status !== self::STATUS_COMPLETED) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return;
        }

        // Verificar se foi rejeitado
        if ($this->signers()->where('status', SignatureSigner::STATUS_REJECTED)->exists()) {
            $this->update(['status' => self::STATUS_REJECTED]);
            return;
        }

        $totalSigners = $this->signers()
            ->where('role', '!=', SignatureSigner::ROLE_OBSERVER)
            ->count();

        $signedCount = $this->signers()
            ->where('role', '!=', SignatureSigner::ROLE_OBSERVER)
            ->where('status', SignatureSigner::STATUS_SIGNED)
            ->count();

        if ($totalSigners > 0 && $signedCount === $totalSigners) {
            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        } elseif ($signedCount > 0) {
            $this->update(['status' => self::STATUS_PARTIALLY_SIGNED]);
        }
    }

    /**
     * Enviar solicitação (iniciar processo de assinatura)
     */
    public function send(): bool
    {
        if ($this->signers()->count() === 0) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PENDING,
            'document_hash' => $this->calculateDocumentHash(),
        ]);

        // Registrar no log
        $this->auditLogs()->create([
            'action' => 'sent',
            'description' => 'Documento enviado para assinatura',
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Cancelar solicitação
     */
    public function cancel(string $reason = null): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        $this->auditLogs()->create([
            'action' => 'cancelled',
            'description' => 'Solicitação cancelada' . ($reason ? ": {$reason}" : ''),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope: Pendentes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIALLY_SIGNED]);
    }

    /**
     * Scope: Concluídas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope: Prestes a expirar
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIALLY_SIGNED])
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days));
    }
}
