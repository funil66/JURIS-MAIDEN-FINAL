<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SignatureSigner extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Signatários
     */
    protected static string $uidPrefix = 'SGN';

    protected $fillable = [
        'uid',
        'signature_request_id',
        'user_id',
        'name',
        'email',
        'phone',
        'document_number',
        'role',
        'signing_order',
        'status',
        'signed_at',
        'signature_ip',
        'signature_user_agent',
        'signature_image',
        'signature_data',
        'certificate_id',
        'verification_code',
        'verification_sent_at',
        'verification_confirmed_at',
        'access_token',
        'token_expires_at',
        'rejection_reason',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
        'metadata',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'verification_confirmed_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'view_count' => 'integer',
        'signing_order' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'verification_code',
        'access_token',
        'signature_data',
    ];

    /**
     * Papéis do signatário
     */
    public const ROLE_SIGNER = 'signer';
    public const ROLE_WITNESS = 'witness';
    public const ROLE_APPROVER = 'approver';
    public const ROLE_OBSERVER = 'observer';

    public const ROLES = [
        self::ROLE_SIGNER => 'Signatário',
        self::ROLE_WITNESS => 'Testemunha',
        self::ROLE_APPROVER => 'Aprovador',
        self::ROLE_OBSERVER => 'Observador',
    ];

    public const ROLE_ICONS = [
        self::ROLE_SIGNER => 'heroicon-o-pencil',
        self::ROLE_WITNESS => 'heroicon-o-eye',
        self::ROLE_APPROVER => 'heroicon-o-check-badge',
        self::ROLE_OBSERVER => 'heroicon-o-user',
    ];

    /**
     * Status do signatário
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [
        self::STATUS_PENDING => 'Aguardando',
        self::STATUS_VIEWED => 'Visualizou',
        self::STATUS_SIGNED => 'Assinou',
        self::STATUS_REJECTED => 'Recusou',
        self::STATUS_EXPIRED => 'Expirado',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_VIEWED => 'info',
        self::STATUS_SIGNED => 'success',
        self::STATUS_REJECTED => 'danger',
        self::STATUS_EXPIRED => 'danger',
    ];

    public const STATUS_ICONS = [
        self::STATUS_PENDING => 'heroicon-o-clock',
        self::STATUS_VIEWED => 'heroicon-o-eye',
        self::STATUS_SIGNED => 'heroicon-o-check-circle',
        self::STATUS_REJECTED => 'heroicon-o-x-circle',
        self::STATUS_EXPIRED => 'heroicon-o-exclamation-triangle',
    ];

    /**
     * Boot: Gerar token de acesso automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->access_token) {
                $model->access_token = Str::random(64);
            }
            if (!$model->token_expires_at) {
                $model->token_expires_at = now()->addDays(30);
            }
        });
    }

    /**
     * Relacionamento: Solicitação de assinatura
     */
    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class);
    }

    /**
     * Relacionamento: Usuário interno
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Certificado usado
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class, 'certificate_id');
    }

    /**
     * Accessor: Label do papel
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Accessor: Ícone do papel
     */
    public function getRoleIconAttribute(): string
    {
        return self::ROLE_ICONS[$this->role] ?? 'heroicon-o-user';
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
        return self::STATUS_ICONS[$this->status] ?? 'heroicon-o-clock';
    }

    /**
     * Accessor: Nome formatado com papel
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->role_label})";
    }

    /**
     * Verificar se pode assinar
     */
    public function canSign(): bool
    {
        // Observadores não assinam
        if ($this->role === self::ROLE_OBSERVER) {
            return false;
        }

        // Só pode assinar se estiver pendente ou visualizado
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_VIEWED])) {
            return false;
        }

        // Verificar se a solicitação pode ser assinada
        if (!$this->signatureRequest->canBeSigned()) {
            return false;
        }

        // Verificar assinatura sequencial
        if ($this->signatureRequest->sequential_signing) {
            $nextSigner = $this->signatureRequest->getNextSigner();
            return $nextSigner && $nextSigner->id === $this->id;
        }

        return true;
    }

    /**
     * Verificar se o token é válido
     */
    public function isTokenValid(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isFuture();
    }

    /**
     * Registrar visualização
     */
    public function recordView(): void
    {
        $now = now();

        $updateData = [
            'last_viewed_at' => $now,
            'view_count' => $this->view_count + 1,
        ];

        if (!$this->first_viewed_at) {
            $updateData['first_viewed_at'] = $now;
        }

        if ($this->status === self::STATUS_PENDING) {
            $updateData['status'] = self::STATUS_VIEWED;
        }

        $this->update($updateData);

        // Registrar no log
        $this->signatureRequest->auditLogs()->create([
            'signer_id' => $this->id,
            'action' => 'viewed',
            'description' => "{$this->name} visualizou o documento",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Gerar código de verificação
     */
    public function generateVerificationCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'verification_code' => $code,
            'verification_sent_at' => now(),
        ]);

        // Registrar no log
        $this->signatureRequest->auditLogs()->create([
            'signer_id' => $this->id,
            'action' => 'verification_sent',
            'description' => "Código de verificação enviado para {$this->email}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $code;
    }

    /**
     * Verificar código
     */
    public function verifyCode(string $code): bool
    {
        if ($this->verification_code !== $code) {
            return false;
        }

        // Verificar se o código não expirou (15 minutos)
        if ($this->verification_sent_at->diffInMinutes(now()) > 15) {
            return false;
        }

        $this->update([
            'verification_confirmed_at' => now(),
        ]);

        // Registrar no log
        $this->signatureRequest->auditLogs()->create([
            'signer_id' => $this->id,
            'action' => 'verification_confirmed',
            'description' => "{$this->name} confirmou código de verificação",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }

    /**
     * Assinar documento
     */
    public function sign(array $signatureData = []): bool
    {
        if (!$this->canSign()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_SIGNED,
            'signed_at' => now(),
            'signature_ip' => request()->ip(),
            'signature_user_agent' => request()->userAgent(),
            'signature_image' => $signatureData['image'] ?? null,
            'signature_data' => json_encode($signatureData),
            'certificate_id' => $signatureData['certificate_id'] ?? null,
        ]);

        // Registrar no log
        $this->signatureRequest->auditLogs()->create([
            'signer_id' => $this->id,
            'action' => 'signed',
            'description' => "{$this->name} assinou o documento",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'extra_data' => [
                'role' => $this->role,
                'signing_order' => $this->signing_order,
            ],
        ]);

        // Atualizar status da solicitação
        $this->signatureRequest->updateStatus();

        return true;
    }

    /**
     * Rejeitar assinatura
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        // Registrar no log
        $this->signatureRequest->auditLogs()->create([
            'signer_id' => $this->id,
            'action' => 'rejected',
            'description' => "{$this->name} rejeitou a assinatura: {$reason}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Atualizar status da solicitação
        $this->signatureRequest->updateStatus();
    }

    /**
     * Renovar token de acesso
     */
    public function renewToken(int $days = 30): string
    {
        $newToken = Str::random(64);

        $this->update([
            'access_token' => $newToken,
            'token_expires_at' => now()->addDays($days),
        ]);

        return $newToken;
    }

    /**
     * URL de assinatura
     */
    public function getSigningUrl(): string
    {
        return url("/assinar/{$this->access_token}");
    }

    /**
     * Scope: Pendentes
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_VIEWED]);
    }

    /**
     * Scope: Assinados
     */
    public function scopeSigned($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    /**
     * Scope: Que precisam assinar (excluindo observadores)
     */
    public function scopeRequiredToSign($query)
    {
        return $query->where('role', '!=', self::ROLE_OBSERVER);
    }
}
