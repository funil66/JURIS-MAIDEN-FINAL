<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureTemplate extends Model
{
    use HasFactory, HasGlobalUid;

    /**
     * Prefixo do UID para Templates de Assinatura
     */
    protected static string $uidPrefix = 'STM';

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'description',
        'signature_type',
        'verification_method',
        'default_signers',
        'default_message',
        'default_expiry_days',
        'reminder_schedule',
        'is_active',
    ];

    protected $casts = [
        'default_signers' => 'array',
        'reminder_schedule' => 'array',
        'default_expiry_days' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento: Usuário que criou o template
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor: Label do tipo de assinatura
     */
    public function getSignatureTypeLabelAttribute(): string
    {
        return SignatureRequest::SIGNATURE_TYPES[$this->signature_type] ?? $this->signature_type;
    }

    /**
     * Accessor: Label do método de verificação
     */
    public function getVerificationMethodLabelAttribute(): string
    {
        return SignatureRequest::VERIFICATION_METHODS[$this->verification_method] ?? $this->verification_method;
    }

    /**
     * Scope: Ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Criar solicitação de assinatura a partir do template
     */
    public function createRequest(
        string $signableType,
        int $signableId,
        string $documentName,
        string $documentPath,
        array $additionalSigners = [],
        ?string $message = null
    ): SignatureRequest {
        $request = SignatureRequest::create([
            'signable_type' => $signableType,
            'signable_id' => $signableId,
            'document_name' => $documentName,
            'document_path' => $documentPath,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'signature_type' => $this->signature_type,
            'verification_method' => $this->verification_method,
            'message' => $message ?? $this->default_message,
            'expires_at' => now()->addDays($this->default_expiry_days),
            'reminder_schedule' => $this->reminder_schedule,
            'send_notifications' => true,
            'status' => SignatureRequest::STATUS_DRAFT,
        ]);

        // Adicionar signatários padrão do template
        $signers = array_merge($this->default_signers ?? [], $additionalSigners);
        $order = 1;

        foreach ($signers as $signer) {
            $request->signers()->create([
                'name' => $signer['name'],
                'email' => $signer['email'],
                'phone' => $signer['phone'] ?? null,
                'document_number' => $signer['document_number'] ?? null,
                'role' => $signer['role'] ?? SignatureSigner::ROLE_SIGNER,
                'signing_order' => $order++,
                'user_id' => $signer['user_id'] ?? null,
            ]);
        }

        // Registrar no log
        $request->auditLogs()->create([
            'action' => SignatureAuditLog::ACTION_CREATED,
            'description' => "Solicitação de assinatura criada a partir do template '{$this->name}'",
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $request;
    }
}
