<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sprint 14: Sistema de Templates de Documentos Jurídicos
     */
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            
            // Identificação
            $table->string('name')->comment('Nome do template (ex: Procuração Ad Judicia)');
            $table->string('slug')->unique()->comment('Identificador único para uso em código');
            
            // Categorização
            $table->enum('category', [
                'procuracao',           // Procurações
                'substabelecimento',    // Substabelecimentos
                'peticao',              // Petições diversas
                'contrato',             // Contratos
                'declaracao',           // Declarações
                'recibo',               // Recibos
                'relatorio',            // Relatórios
                'correspondencia',      // Correspondências/Ofícios
                'outro'                 // Outros documentos
            ])->default('outro');
            
            // Conteúdo
            $table->longText('content')->comment('Conteúdo HTML/Markdown com variáveis {{variavel}}');
            $table->json('variables')->nullable()->comment('Lista de variáveis disponíveis com descrição');
            
            // Metadados
            $table->text('description')->nullable()->comment('Descrição de uso do template');
            $table->string('format')->default('A4')->comment('Formato do papel (A4, Letter, etc)');
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            
            // Controle
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false)->comment('Templates do sistema não podem ser deletados');
            $table->integer('usage_count')->default(0)->comment('Contador de uso');
            
            // Auditoria
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('category');
            $table->index('is_active');
            $table->index('is_system');
        });

        // Tabela de documentos gerados a partir de templates
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos
            $table->foreignId('document_template_id')->constrained()->onDelete('restrict');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Usuário que gerou');
            
            // Documento
            $table->string('title')->comment('Título do documento gerado');
            $table->longText('content')->comment('Conteúdo final após substituição de variáveis');
            $table->json('variables_used')->nullable()->comment('Variáveis e valores utilizados');
            
            // Arquivo
            $table->string('file_path')->nullable()->comment('Caminho do PDF gerado');
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable()->comment('Tamanho em bytes');
            
            // Status
            $table->enum('status', [
                'draft',        // Rascunho
                'generated',    // Gerado (PDF criado)
                'sent',         // Enviado ao cliente
                'signed',       // Assinado
                'archived'      // Arquivado
            ])->default('draft');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('document_templates');
    }
};
