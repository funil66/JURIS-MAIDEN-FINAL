<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'signature_request_id',
        'signer_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    /**
     * Ações disponíveis
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_SENT = 'sent';
    public const ACTION_VIEWED = 'viewed';
    public const ACTION_DOWNLOADED = 'downloaded';
    public const ACTION_SIGNED = 'signed';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_CANCELLED = 'cancelled';
    public const ACTION_EXPIRED = 'expired';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_REMINDER_SENT = 'reminder_sent';
    public const ACTION_VERIFICATION_SENT = 'verification_sent';
    public const ACTION_VERIFICATION_CONFIRMED = 'verification_confirmed';

    public const ACTIONS = [
        self::ACTION_CREATED => 'Documento criado',
        self::ACTION_SENT => 'Enviado para assinatura',
        self::ACTION_VIEWED => 'Documento visualizado',
        self::ACTION_DOWNLOADED => 'Documento baixado',
        self::ACTION_SIGNED => 'Documento assinado',
        self::ACTION_REJECTED => 'Assinatura rejeitada',
        self::ACTION_CANCELLED => 'Solicitação cancelada',
        self::ACTION_EXPIRED => 'Expirado',
        self::ACTION_COMPLETED => 'Concluído',
        self::ACTION_REMINDER_SENT => 'Lembrete enviado',
        self::ACTION_VERIFICATION_SENT => 'Código de verificação enviado',
        self::ACTION_VERIFICATION_CONFIRMED => 'Código de verificação confirmado',
    ];

    public const ACTION_ICONS = [
        self::ACTION_CREATED => 'heroicon-o-document-plus',
        self::ACTION_SENT => 'heroicon-o-paper-airplane',
        self::ACTION_VIEWED => 'heroicon-o-eye',
        self::ACTION_DOWNLOADED => 'heroicon-o-arrow-down-tray',
        self::ACTION_SIGNED => 'heroicon-o-check-circle',
        self::ACTION_REJECTED => 'heroicon-o-x-circle',
        self::ACTION_CANCELLED => 'heroicon-o-x-mark',
        self::ACTION_EXPIRED => 'heroicon-o-exclamation-triangle',
        self::ACTION_COMPLETED => 'heroicon-o-trophy',
        self::ACTION_REMINDER_SENT => 'heroicon-o-bell',
        self::ACTION_VERIFICATION_SENT => 'heroicon-o-key',
        self::ACTION_VERIFICATION_CONFIRMED => 'heroicon-o-shield-check',
    ];

    public const ACTION_COLORS = [
        self::ACTION_CREATED => 'gray',
        self::ACTION_SENT => 'info',
        self::ACTION_VIEWED => 'info',
        self::ACTION_DOWNLOADED => 'info',
        self::ACTION_SIGNED => 'success',
        self::ACTION_REJECTED => 'danger',
        self::ACTION_CANCELLED => 'danger',
        self::ACTION_EXPIRED => 'danger',
        self::ACTION_COMPLETED => 'success',
        self::ACTION_REMINDER_SENT => 'warning',
        self::ACTION_VERIFICATION_SENT => 'warning',
        self::ACTION_VERIFICATION_CONFIRMED => 'success',
    ];

    /**
     * Relacionamento: Solicitação de assinatura
     */
    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class);
    }

    /**
     * Relacionamento: Signatário
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(SignatureSigner::class, 'signer_id');
    }

    /**
     * Relacionamento: Usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: Label da ação
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Accessor: Ícone da ação
     */
    public function getActionIconAttribute(): string
    {
        return self::ACTION_ICONS[$this->action] ?? 'heroicon-o-information-circle';
    }

    /**
     * Accessor: Cor da ação
     */
    public function getActionColorAttribute(): string
    {
        return self::ACTION_COLORS[$this->action] ?? 'gray';
    }

    /**
     * Accessor: Ator da ação
     */
    public function getActorNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        if ($this->signer) {
            return $this->signer->name;
        }

        return 'Sistema';
    }

    /**
     * Scope: Por ação
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Últimos N dias
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
