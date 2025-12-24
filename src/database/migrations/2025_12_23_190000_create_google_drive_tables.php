<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 29: Google Drive Integration
     * Tabelas para integração com Google Drive
     */
    public function up(): void
    {
        // =============================================
        // CONFIGURAÇÕES DO GOOGLE DRIVE POR USUÁRIO
        // =============================================
        Schema::create('google_drive_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Credenciais OAuth
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            
            // Configurações de pasta
            $table->string('root_folder_id')->nullable()->comment('ID da pasta raiz no Drive');
            $table->string('root_folder_name')->nullable()->comment('Nome da pasta raiz');
            
            // Configurações de sincronização
            $table->boolean('auto_sync')->default(false)->comment('Sincronizar automaticamente novos documentos');
            $table->boolean('sync_reports')->default(true)->comment('Sincronizar relatórios gerados');
            $table->boolean('sync_documents')->default(true)->comment('Sincronizar documentos gerados');
            $table->boolean('sync_invoices')->default(false)->comment('Sincronizar faturas em PDF');
            $table->boolean('sync_contracts')->default(false)->comment('Sincronizar contratos');
            
            // Organização
            $table->enum('folder_structure', ['flat', 'by_client', 'by_type', 'by_date'])->default('by_client');
            
            // Status
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            
            $table->timestamps();
            
            // Índice único para usuário (apenas uma configuração por usuário)
            $table->unique('user_id');
        });

        // =============================================
        // ARQUIVOS SINCRONIZADOS COM GOOGLE DRIVE
        // =============================================
        Schema::create('google_drive_files', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            
            // Relacionamento com entidade local
            $table->morphs('fileable'); // fileable_type, fileable_id
            
            // Informações do Google Drive
            $table->string('google_file_id')->unique()->comment('ID do arquivo no Google Drive');
            $table->string('google_folder_id')->nullable()->comment('ID da pasta no Google Drive');
            $table->string('web_view_link')->nullable()->comment('Link para visualizar no Drive');
            $table->string('web_content_link')->nullable()->comment('Link para download direto');
            
            // Metadados do arquivo
            $table->string('name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('md5_checksum')->nullable();
            
            // Caminhos
            $table->string('local_path')->nullable()->comment('Caminho local do arquivo');
            $table->string('drive_path')->nullable()->comment('Caminho no Google Drive');
            
            // Status de sincronização
            $table->enum('sync_status', [
                'pending',      // Aguardando sincronização
                'syncing',      // Sincronizando
                'synced',       // Sincronizado com sucesso
                'failed',       // Falha na sincronização
                'deleted',      // Deletado do Drive
                'conflict'      // Conflito de versão
            ])->default('pending');
            
            // Direção da sincronização
            $table->enum('sync_direction', [
                'upload',       // Upload para o Drive
                'download',     // Download do Drive
                'bidirectional' // Sincronização bidirecional
            ])->default('upload');
            
            // Controle de versão
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('local_modified_at')->nullable();
            $table->timestamp('drive_modified_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            
            // Informações adicionais
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices (morphs já cria índice automaticamente)
            $table->index('sync_status');
            $table->index('sync_direction');
        });

        // =============================================
        // LOG DE ATIVIDADES DO GOOGLE DRIVE
        // =============================================
        Schema::create('google_drive_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('google_drive_file_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Tipo de ação
            $table->enum('action', [
                'upload',       // Upload de arquivo
                'download',     // Download de arquivo
                'delete',       // Deleção de arquivo
                'rename',       // Renomear arquivo
                'move',         // Mover arquivo
                'share',        // Compartilhar arquivo
                'sync',         // Sincronização
                'connect',      // Conectar conta
                'disconnect',   // Desconectar conta
                'error'         // Erro na operação
            ]);
            
            // Detalhes
            $table->string('file_name')->nullable();
            $table->text('description')->nullable();
            $table->text('error_details')->nullable();
            
            // IP e User Agent
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // =============================================
        // PASTAS CRIADAS NO GOOGLE DRIVE
        // =============================================
        Schema::create('google_drive_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Informações da pasta
            $table->string('google_folder_id')->unique();
            $table->string('name');
            $table->string('parent_folder_id')->nullable()->comment('Pasta pai no Drive');
            $table->string('web_view_link')->nullable();
            
            // Relacionamento opcional com entidade (nullableMorphs já cria índice)
            $table->nullableMorphs('folderable');
            
            // Tipo de pasta
            $table->enum('folder_type', [
                'root',         // Pasta raiz
                'client',       // Pasta de cliente
                'process',      // Pasta de processo
                'documents',    // Pasta de documentos
                'reports',      // Pasta de relatórios
                'invoices',     // Pasta de faturas
                'contracts',    // Pasta de contratos
                'year',         // Pasta de ano
                'month',        // Pasta de mês
                'custom'        // Pasta customizada
            ])->default('custom');
            
            // Path completo no Drive
            $table->string('full_path')->nullable();
            
            $table->timestamps();
            
            // Índices (nullableMorphs já cria índice para folderable)
            $table->index('folder_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_drive_folders');
        Schema::dropIfExists('google_drive_activity_logs');
        Schema::dropIfExists('google_drive_files');
        Schema::dropIfExists('google_drive_settings');
    }
};
