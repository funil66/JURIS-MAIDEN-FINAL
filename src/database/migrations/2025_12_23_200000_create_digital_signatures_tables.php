<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 31: Assinatura Digital
     * Tabelas para gestão de assinaturas digitais e certificados
     */
    public function up(): void
    {
        // =============================================
        // CERTIFICADOS DIGITAIS
        // =============================================
        Schema::create('digital_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Informações do certificado
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'a1',           // Arquivo (1 ano)
                'a3_token',     // Token USB
                'a3_card',      // Cartão com leitora
                'cloud'         // Certificado em nuvem
            ])->default('a1');
            
            // Dados do titular
            $table->string('holder_name');
            $table->string('holder_document')->nullable()->comment('CPF/CNPJ');
            $table->string('holder_email')->nullable();
            
            // Dados do certificado
            $table->string('serial_number')->nullable();
            $table->string('issuer')->nullable()->comment('AC emissora');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            // Arquivo do certificado (A1)
            $table->string('certificate_path')->nullable()->comment('Caminho do arquivo .pfx/.p12');
            $table->text('certificate_password')->nullable()->comment('Senha criptografada');
            
            // Status
            $table->enum('status', [
                'active',       // Ativo
                'expired',      // Expirado
                'revoked',      // Revogado
                'pending'       // Pendente de configuração
            ])->default('pending');
            
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index('status');
            $table->index('valid_until');
        });

        // =============================================
        // SOLICITAÇÕES DE ASSINATURA
        // =============================================
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            
            // Documento a ser assinado
            $table->morphs('signable'); // signable_type, signable_id
            
            // Informações do documento
            $table->string('document_name');
            $table->string('document_path');
            $table->string('signed_document_path')->nullable();
            $table->string('document_hash')->nullable()->comment('Hash SHA-256 original');
            
            // Solicitante
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            
            // Configurações
            $table->enum('signature_type', [
                'simple',       // Assinatura simples (clique)
                'electronic',   // Assinatura eletrônica (email + código)
                'digital',      // Assinatura digital (certificado ICP-Brasil)
                'qualified'     // Assinatura qualificada (certificado + carimbo do tempo)
            ])->default('electronic');
            
            $table->enum('verification_method', [
                'none',         // Sem verificação adicional
                'email',        // Código por email
                'sms',          // Código por SMS
                'whatsapp',     // Código por WhatsApp
                'selfie',       // Selfie com documento
            ])->default('email');
            
            // Status
            $table->enum('status', [
                'draft',        // Rascunho
                'pending',      // Aguardando assinaturas
                'partially_signed', // Parcialmente assinado
                'completed',    // Todas assinaturas concluídas
                'cancelled',    // Cancelado
                'expired',      // Expirado
                'rejected'      // Rejeitado
            ])->default('draft');
            
            // Prazo
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Mensagem
            $table->text('message')->nullable()->comment('Mensagem para signatários');
            
            // Ordem de assinatura
            $table->boolean('sequential_signing')->default(false);
            
            // Notificações
            $table->boolean('send_notifications')->default(true);
            $table->json('reminder_schedule')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index('status');
            $table->index('expires_at');
        });

        // =============================================
        // SIGNATÁRIOS
        // =============================================
        Schema::create('signature_signers', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('signature_request_id')->constrained()->cascadeOnDelete();
            
            // Signatário interno (usuário do sistema)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Signatário externo
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('document_number')->nullable()->comment('CPF/CNPJ');
            
            // Papel do signatário
            $table->enum('role', [
                'signer',       // Signatário
                'witness',      // Testemunha
                'approver',     // Aprovador
                'observer'      // Observador (não assina)
            ])->default('signer');
            
            // Ordem de assinatura
            $table->unsignedInteger('signing_order')->default(1);
            
            // Status da assinatura
            $table->enum('status', [
                'pending',      // Aguardando
                'viewed',       // Visualizou documento
                'signed',       // Assinou
                'rejected',     // Recusou
                'expired'       // Expirado
            ])->default('pending');
            
            // Dados da assinatura
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_ip')->nullable();
            $table->string('signature_user_agent')->nullable();
            $table->text('signature_image')->nullable()->comment('Imagem da assinatura em base64');
            $table->text('signature_data')->nullable()->comment('Dados técnicos da assinatura');
            
            // Certificado usado (se digital)
            $table->foreignId('certificate_id')->nullable()->constrained('digital_certificates')->nullOnDelete();
            
            // Verificação
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            $table->timestamp('verification_confirmed_at')->nullable();
            
            // Token de acesso
            $table->string('access_token', 100)->unique();
            $table->timestamp('token_expires_at')->nullable();
            
            // Motivo de rejeição
            $table->text('rejection_reason')->nullable();
            
            // Histórico de visualização
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('status');
            $table->index('access_token');
        });

        // =============================================
        // LOGS DE ASSINATURA (AUDIT TRAIL)
        // =============================================
        Schema::create('signature_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signer_id')->nullable()->constrained('signature_signers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Ação
            $table->enum('action', [
                'created',          // Documento criado
                'sent',             // Enviado para assinatura
                'viewed',           // Documento visualizado
                'downloaded',       // Documento baixado
                'signed',           // Documento assinado
                'rejected',         // Assinatura rejeitada
                'cancelled',        // Solicitação cancelada
                'expired',          // Expirado
                'completed',        // Todas assinaturas concluídas
                'reminder_sent',    // Lembrete enviado
                'verification_sent', // Código de verificação enviado
                'verification_confirmed', // Código confirmado
            ]);
            
            // Detalhes
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('extra_data')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['signature_request_id', 'created_at']);
        });

        // =============================================
        // MODELOS DE ASSINATURA (TEMPLATES)
        // =============================================
        Schema::create('signature_templates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            
            // Configuração padrão
            $table->enum('signature_type', ['simple', 'electronic', 'digital', 'qualified'])->default('electronic');
            $table->enum('verification_method', ['none', 'email', 'sms', 'whatsapp', 'selfie'])->default('email');
            
            // Signatários padrão
            $table->json('default_signers')->nullable();
            
            // Mensagem padrão
            $table->text('default_message')->nullable();
            
            // Prazo padrão (em dias)
            $table->unsignedInteger('default_expiry_days')->default(30);
            
            // Lembretes
            $table->json('reminder_schedule')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_templates');
        Schema::dropIfExists('signature_audit_logs');
        Schema::dropIfExists('signature_signers');
        Schema::dropIfExists('signature_requests');
        Schema::dropIfExists('digital_certificates');
    }
};
