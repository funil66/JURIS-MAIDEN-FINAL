<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use App\Models\SignatureAuditLog;
use App\Models\SignatureTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DigitalSignatureService
{
    /**
     * Criar nova solicitação de assinatura
     */
    public function createRequest(
        string $signableType,
        int $signableId,
        string $documentPath,
        string $documentName,
        array $signers,
        array $options = []
    ): SignatureRequest {
        // Validar documento existe
        if (!Storage::exists($documentPath)) {
            throw new Exception("Documento não encontrado: {$documentPath}");
        }

        // Calcular hash do documento
        $documentHash = hash_file('sha256', Storage::path($documentPath));

        // Criar solicitação
        $request = SignatureRequest::create([
            'signable_type' => $signableType,
            'signable_id' => $signableId,
            'document_name' => $documentName,
            'document_path' => $documentPath,
            'document_hash' => $documentHash,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'signature_type' => $options['signature_type'] ?? SignatureRequest::TYPE_ELECTRONIC,
            'verification_method' => $options['verification_method'] ?? SignatureRequest::VERIFICATION_EMAIL,
            'message' => $options['message'] ?? null,
            'expires_at' => $options['expires_at'] ?? now()->addDays(30),
            'sequential_signing' => $options['sequential_signing'] ?? false,
            'send_notifications' => $options['send_notifications'] ?? true,
            'reminder_schedule' => $options['reminder_schedule'] ?? null,
            'status' => SignatureRequest::STATUS_DRAFT,
        ]);

        // Adicionar signatários
        $order = 1;
        foreach ($signers as $signerData) {
            $request->signers()->create([
                'name' => $signerData['name'],
                'email' => $signerData['email'],
                'phone' => $signerData['phone'] ?? null,
                'document_number' => $signerData['document_number'] ?? null,
                'role' => $signerData['role'] ?? SignatureSigner::ROLE_SIGNER,
                'signing_order' => $signerData['signing_order'] ?? $order++,
                'user_id' => $signerData['user_id'] ?? null,
            ]);
        }

        // Log
        $this->logAction($request, SignatureAuditLog::ACTION_CREATED, 'Solicitação de assinatura criada');

        return $request;
    }

    /**
     * Criar solicitação a partir de template
     */
    public function createFromTemplate(
        SignatureTemplate $template,
        string $signableType,
        int $signableId,
        string $documentPath,
        string $documentName,
        array $additionalSigners = [],
        ?string $message = null
    ): SignatureRequest {
        return $template->createRequest(
            $signableType,
            $signableId,
            $documentName,
            $documentPath,
            $additionalSigners,
            $message
        );
    }

    /**
     * Enviar solicitação para assinatura
     */
    public function sendForSigning(SignatureRequest $request): bool
    {
        if ($request->signers()->count() === 0) {
            throw new Exception('A solicitação deve ter pelo menos um signatário');
        }

        // Atualizar status
        $request->update([
            'status' => SignatureRequest::STATUS_PENDING,
        ]);

        // Log
        $this->logAction($request, SignatureAuditLog::ACTION_SENT, 'Documento enviado para assinatura');

        // Enviar notificações
        if ($request->send_notifications) {
            $this->sendNotifications($request);
        }

        return true;
    }

    /**
     * Enviar notificações para signatários
     */
    protected function sendNotifications(SignatureRequest $request): void
    {
        $signersToNotify = $request->sequential_signing
            ? collect([$request->getNextSigner()])->filter()
            : $request->signers()->pending()->get();

        foreach ($signersToNotify as $signer) {
            $this->sendSignerNotification($signer);
        }
    }

    /**
     * Enviar notificação individual para signatário
     */
    public function sendSignerNotification(SignatureSigner $signer): void
    {
        // TODO: Implementar envio de email usando Mail facade
        // Mail::to($signer->email)->send(new SignatureRequestMail($signer));

        // Por enquanto, apenas registrar que seria enviado
        $this->logAction(
            $signer->signatureRequest,
            SignatureAuditLog::ACTION_REMINDER_SENT,
            "Notificação enviada para {$signer->name} ({$signer->email})",
            $signer
        );
    }

    /**
     * Gerar e enviar código de verificação
     */
    public function sendVerificationCode(SignatureSigner $signer): string
    {
        $code = $signer->generateVerificationCode();

        // TODO: Implementar envio por email/SMS baseado no método de verificação
        // switch ($signer->signatureRequest->verification_method) {
        //     case SignatureRequest::VERIFICATION_EMAIL:
        //         Mail::to($signer->email)->send(new VerificationCodeMail($code));
        //         break;
        //     case SignatureRequest::VERIFICATION_SMS:
        //         // Enviar SMS
        //         break;
        // }

        return $code;
    }

    /**
     * Verificar código de verificação
     */
    public function verifyCode(SignatureSigner $signer, string $code): bool
    {
        return $signer->verifyCode($code);
    }

    /**
     * Processar assinatura simples (clique)
     */
    public function processSimpleSignature(SignatureSigner $signer, ?string $signatureImage = null): bool
    {
        if (!$signer->canSign()) {
            throw new Exception('Este signatário não pode assinar neste momento');
        }

        return $signer->sign([
            'image' => $signatureImage,
            'type' => 'simple',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Processar assinatura eletrônica (com verificação)
     */
    public function processElectronicSignature(
        SignatureSigner $signer,
        string $verificationCode,
        ?string $signatureImage = null
    ): bool {
        if (!$signer->canSign()) {
            throw new Exception('Este signatário não pode assinar neste momento');
        }

        // Verificar código
        if (!$this->verifyCode($signer, $verificationCode)) {
            throw new Exception('Código de verificação inválido ou expirado');
        }

        return $signer->sign([
            'image' => $signatureImage,
            'type' => 'electronic',
            'verification_confirmed' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Processar assinatura digital (certificado ICP-Brasil)
     */
    public function processDigitalSignature(
        SignatureSigner $signer,
        DigitalCertificate $certificate,
        string $signatureData
    ): bool {
        if (!$signer->canSign()) {
            throw new Exception('Este signatário não pode assinar neste momento');
        }

        if (!$certificate->isValid()) {
            throw new Exception('Certificado digital inválido ou expirado');
        }

        return $signer->sign([
            'type' => 'digital',
            'certificate_id' => $certificate->id,
            'certificate_serial' => $certificate->serial_number,
            'certificate_holder' => $certificate->holder_name,
            'signature_data' => $signatureData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Rejeitar assinatura
     */
    public function rejectSignature(SignatureSigner $signer, string $reason): void
    {
        $signer->reject($reason);
    }

    /**
     * Cancelar solicitação
     */
    public function cancelRequest(SignatureRequest $request, ?string $reason = null): void
    {
        $request->cancel($reason);

        // Notificar signatários sobre cancelamento
        foreach ($request->signers as $signer) {
            // TODO: Enviar email de cancelamento
        }
    }

    /**
     * Gerar documento assinado (com marca d'água de assinaturas)
     */
    public function generateSignedDocument(SignatureRequest $request): ?string
    {
        if (!$request->isCompleted()) {
            throw new Exception('Todas as assinaturas devem estar concluídas');
        }

        $originalPath = $request->document_path;
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $signedPath = str_replace(".{$extension}", "_signed.{$extension}", $originalPath);

        // Copiar documento original
        if (!Storage::copy($originalPath, $signedPath)) {
            throw new Exception('Erro ao gerar documento assinado');
        }

        // TODO: Adicionar marcas d'água de assinatura se for PDF
        // Isso requer biblioteca como TCPDF ou FPDF

        // Atualizar caminho do documento assinado
        $request->update(['signed_document_path' => $signedPath]);

        // Log
        $this->logAction(
            $request,
            SignatureAuditLog::ACTION_COMPLETED,
            'Documento assinado gerado com sucesso'
        );

        return $signedPath;
    }

    /**
     * Verificar integridade do documento
     */
    public function verifyDocumentIntegrity(SignatureRequest $request): bool
    {
        if (!$request->document_hash) {
            return false;
        }

        $currentHash = $request->calculateDocumentHash();
        return $currentHash === $request->document_hash;
    }

    /**
     * Verificar certificados expirando
     */
    public function getExpiringCertificates(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return DigitalCertificate::expiringSoon($days)->get();
    }

    /**
     * Atualizar status de certificados expirados
     */
    public function updateExpiredCertificates(): int
    {
        return DigitalCertificate::where('status', DigitalCertificate::STATUS_ACTIVE)
            ->where('valid_until', '<', now())
            ->update(['status' => DigitalCertificate::STATUS_EXPIRED]);
    }

    /**
     * Verificar e atualizar solicitações expiradas
     */
    public function updateExpiredRequests(): int
    {
        $count = 0;

        SignatureRequest::whereIn('status', [
            SignatureRequest::STATUS_PENDING,
            SignatureRequest::STATUS_PARTIALLY_SIGNED,
        ])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->each(function ($request) use (&$count) {
                $request->update(['status' => SignatureRequest::STATUS_EXPIRED]);
                
                $this->logAction(
                    $request,
                    SignatureAuditLog::ACTION_EXPIRED,
                    'Solicitação expirada automaticamente'
                );

                // Atualizar status dos signatários pendentes
                $request->signers()
                    ->whereIn('status', [SignatureSigner::STATUS_PENDING, SignatureSigner::STATUS_VIEWED])
                    ->update(['status' => SignatureSigner::STATUS_EXPIRED]);

                $count++;
            });

        return $count;
    }

    /**
     * Enviar lembretes para solicitações pendentes
     */
    public function sendReminders(): int
    {
        $count = 0;

        // Buscar solicitações com lembretes configurados
        SignatureRequest::pending()
            ->whereNotNull('reminder_schedule')
            ->each(function ($request) use (&$count) {
                $schedule = $request->reminder_schedule;
                
                // Verificar se é hora de enviar lembrete
                // TODO: Implementar lógica de verificação de schedule
                
                foreach ($request->getPendingSigners()->get() as $signer) {
                    $this->sendSignerNotification($signer);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Obter estatísticas de assinatura
     */
    public function getStatistics(?int $userId = null): array
    {
        $query = SignatureRequest::query();
        
        if ($userId) {
            $query->where('requested_by', $userId);
        }

        return [
            'total' => $query->count(),
            'draft' => (clone $query)->where('status', SignatureRequest::STATUS_DRAFT)->count(),
            'pending' => (clone $query)->whereIn('status', [
                SignatureRequest::STATUS_PENDING,
                SignatureRequest::STATUS_PARTIALLY_SIGNED,
            ])->count(),
            'completed' => (clone $query)->where('status', SignatureRequest::STATUS_COMPLETED)->count(),
            'expired' => (clone $query)->where('status', SignatureRequest::STATUS_EXPIRED)->count(),
            'rejected' => (clone $query)->where('status', SignatureRequest::STATUS_REJECTED)->count(),
            'cancelled' => (clone $query)->where('status', SignatureRequest::STATUS_CANCELLED)->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'completed_this_month' => (clone $query)
                ->where('status', SignatureRequest::STATUS_COMPLETED)
                ->whereMonth('completed_at', now()->month)
                ->count(),
        ];
    }

    /**
     * Obter signatários pendentes do usuário atual
     */
    public function getPendingSignaturesForCurrentUser(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect([]);
        }

        return SignatureSigner::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->whereIn('status', [SignatureSigner::STATUS_PENDING, SignatureSigner::STATUS_VIEWED])
            ->whereHas('signatureRequest', function ($q) {
                $q->whereIn('status', [
                    SignatureRequest::STATUS_PENDING,
                    SignatureRequest::STATUS_PARTIALLY_SIGNED,
                ]);
            })
            ->with('signatureRequest')
            ->get();
    }

    /**
     * Registrar ação no log de auditoria
     */
    protected function logAction(
        SignatureRequest $request,
        string $action,
        string $description,
        ?SignatureSigner $signer = null
    ): SignatureAuditLog {
        return $request->auditLogs()->create([
            'action' => $action,
            'description' => $description,
            'signer_id' => $signer?->id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Validar certificado digital
     */
    public function validateCertificate(string $certificatePath, string $password): array
    {
        if (!file_exists($certificatePath)) {
            return [
                'valid' => false,
                'error' => 'Arquivo do certificado não encontrado',
            ];
        }

        $certificateContent = file_get_contents($certificatePath);
        
        // Tentar ler certificado PKCS#12
        $certs = [];
        if (!openssl_pkcs12_read($certificateContent, $certs, $password)) {
            return [
                'valid' => false,
                'error' => 'Não foi possível ler o certificado. Verifique a senha.',
            ];
        }

        // Extrair informações do certificado
        $certData = openssl_x509_parse($certs['cert']);
        
        if (!$certData) {
            return [
                'valid' => false,
                'error' => 'Não foi possível analisar o certificado',
            ];
        }

        $validFrom = Carbon::createFromTimestamp($certData['validFrom_time_t']);
        $validTo = Carbon::createFromTimestamp($certData['validTo_time_t']);
        $isValid = $validFrom->isPast() && $validTo->isFuture();

        // Extrair informações do titular
        $subject = $certData['subject'] ?? [];
        $holderName = $subject['CN'] ?? $subject['O'] ?? 'Desconhecido';
        
        // Tentar extrair CPF/CNPJ do certificado ICP-Brasil
        $documentNumber = null;
        if (isset($certData['extensions']['subjectAltName'])) {
            // ICP-Brasil armazena CPF/CNPJ nas extensões
            preg_match('/\d{11}|\d{14}/', $certData['extensions']['subjectAltName'], $matches);
            $documentNumber = $matches[0] ?? null;
        }

        return [
            'valid' => $isValid,
            'expired' => $validTo->isPast(),
            'not_yet_valid' => $validFrom->isFuture(),
            'holder_name' => $holderName,
            'holder_email' => $subject['emailAddress'] ?? null,
            'holder_document' => $documentNumber,
            'serial_number' => $certData['serialNumber'] ?? null,
            'issuer' => $certData['issuer']['CN'] ?? $certData['issuer']['O'] ?? null,
            'valid_from' => $validFrom,
            'valid_until' => $validTo,
            'days_remaining' => $validTo->isFuture() ? (int) now()->diffInDays($validTo) : 0,
        ];
    }

    /**
     * Importar certificado digital
     */
    public function importCertificate(
        string $certificatePath,
        string $password,
        string $name,
        ?int $userId = null
    ): DigitalCertificate {
        $validation = $this->validateCertificate($certificatePath, $password);

        if (!$validation['valid']) {
            throw new Exception($validation['error'] ?? 'Certificado inválido');
        }

        // Mover certificado para storage seguro
        $storedPath = 'certificates/' . Str::random(40) . '.pfx';
        Storage::put($storedPath, file_get_contents($certificatePath));

        // Criar registro do certificado
        return DigitalCertificate::create([
            'user_id' => $userId ?? auth()->id(),
            'name' => $name,
            'type' => DigitalCertificate::TYPE_A1,
            'holder_name' => $validation['holder_name'],
            'holder_document' => $validation['holder_document'],
            'holder_email' => $validation['holder_email'],
            'serial_number' => $validation['serial_number'],
            'issuer' => $validation['issuer'],
            'valid_from' => $validation['valid_from'],
            'valid_until' => $validation['valid_until'],
            'certificate_path' => $storedPath,
            'certificate_password' => $password, // Será criptografado pelo mutator
            'status' => DigitalCertificate::STATUS_ACTIVE,
        ]);
    }

    /**
     * Buscar signatário por token de acesso
     */
    public function findSignerByToken(string $token): ?SignatureSigner
    {
        return SignatureSigner::where('access_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();
    }
}
