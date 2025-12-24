<?php

namespace App\Http\Controllers;

use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use App\Services\DigitalSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    protected DigitalSignatureService $signatureService;

    public function __construct(DigitalSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Página pública de assinatura
     */
    public function sign(string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer) {
            abort(404, 'Link de assinatura inválido ou expirado.');
        }

        // Registrar visualização
        $signer->recordView();

        $request = $signer->signatureRequest;

        // Verificar se a solicitação pode ser assinada
        if (!$request->canBeSigned()) {
            return view('signatures.expired', [
                'request' => $request,
                'signer' => $signer,
            ]);
        }

        // Verificar se é a vez do signatário (assinatura sequencial)
        $canSign = $signer->canSign();

        return view('signatures.sign', [
            'request' => $request,
            'signer' => $signer,
            'canSign' => $canSign,
        ]);
    }

    /**
     * Solicitar código de verificação
     */
    public function requestCode(Request $request, string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer || !$signer->canSign()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível solicitar código neste momento.',
            ], 400);
        }

        $code = $this->signatureService->sendVerificationCode($signer);

        return response()->json([
            'success' => true,
            'message' => 'Código de verificação enviado para ' . $this->maskEmail($signer->email),
        ]);
    }

    /**
     * Processar assinatura
     */
    public function processSignature(Request $request, string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer || !$signer->canSign()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível assinar neste momento.',
            ], 400);
        }

        $signatureRequest = $signer->signatureRequest;

        try {
            switch ($signatureRequest->signature_type) {
                case SignatureRequest::TYPE_SIMPLE:
                    $this->signatureService->processSimpleSignature(
                        $signer,
                        $request->input('signature_image')
                    );
                    break;

                case SignatureRequest::TYPE_ELECTRONIC:
                    $this->signatureService->processElectronicSignature(
                        $signer,
                        $request->input('verification_code'),
                        $request->input('signature_image')
                    );
                    break;

                case SignatureRequest::TYPE_DIGITAL:
                case SignatureRequest::TYPE_QUALIFIED:
                    // Assinatura digital requer certificado - tratado diferentemente
                    return response()->json([
                        'success' => false,
                        'message' => 'Assinatura digital deve ser feita pelo painel.',
                    ], 400);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipo de assinatura não suportado.',
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documento assinado com sucesso!',
                'redirect' => route('signatures.success', $token),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rejeitar assinatura
     */
    public function reject(Request $request, string $token)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer || !$signer->canSign()) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível rejeitar neste momento.',
            ], 400);
        }

        $this->signatureService->rejectSignature($signer, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Assinatura recusada.',
            'redirect' => route('signatures.rejected', $token),
        ]);
    }

    /**
     * Página de sucesso após assinatura
     */
    public function success(string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer) {
            abort(404);
        }

        return view('signatures.success', [
            'signer' => $signer,
            'request' => $signer->signatureRequest,
        ]);
    }

    /**
     * Página de rejeição
     */
    public function rejected(string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer) {
            abort(404);
        }

        return view('signatures.rejected', [
            'signer' => $signer,
            'request' => $signer->signatureRequest,
        ]);
    }

    /**
     * Download do documento para visualização
     */
    public function viewDocument(string $token)
    {
        $signer = $this->signatureService->findSignerByToken($token);

        if (!$signer) {
            abort(404);
        }

        $request = $signer->signatureRequest;

        if (!Storage::exists($request->document_path)) {
            abort(404, 'Documento não encontrado.');
        }

        // Registrar log de visualização/download
        $request->auditLogs()->create([
            'signer_id' => $signer->id,
            'action' => 'downloaded',
            'description' => "{$signer->name} visualizou/baixou o documento",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Storage::download(
            $request->document_path,
            $request->document_name
        );
    }

    /**
     * Download do documento assinado (autenticado)
     */
    public function download(SignatureRequest $request)
    {
        $path = $request->signed_document_path ?? $request->document_path;

        if (!Storage::exists($path)) {
            abort(404, 'Documento não encontrado.');
        }

        return Storage::download($path, $request->document_name);
    }

    /**
     * Verificar status de uma solicitação
     */
    public function status(string $uid)
    {
        $request = SignatureRequest::where('uid', $uid)->first();

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitação não encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uid' => $request->uid,
                'document_name' => $request->document_name,
                'status' => $request->status,
                'status_label' => $request->status_label,
                'total_signers' => $request->total_signers,
                'signed_count' => $request->signed_count,
                'progress' => $request->progress,
                'signers' => $request->signers->map(fn ($s) => [
                    'name' => $s->name,
                    'role' => $s->role_label,
                    'status' => $s->status_label,
                    'signed_at' => $s->signed_at?->format('d/m/Y H:i'),
                ]),
            ],
        ]);
    }

    /**
     * Mascarar email para exibição
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 3) {
            $masked = $name[0] . '***';
        } else {
            $masked = substr($name, 0, 2) . str_repeat('*', strlen($name) - 3) . substr($name, -1);
        }

        return $masked . '@' . $domain;
    }
}
