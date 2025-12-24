<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 32: Integração com APIs de Tribunais
     * Tabelas para gestão de tribunais, consultas e movimentações via API
     */
    public function up(): void
    {
        // =============================================
        // TRIBUNAIS CONFIGURADOS
        // =============================================
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            
            // Identificação
            $table->string('name');
            $table->string('acronym', 20)->unique()->comment('Ex: TJSP, STF, TRF1');
            $table->string('full_name')->nullable();
            
            // Tipo de tribunal
            $table->enum('type', [
                'stf',          // Supremo Tribunal Federal
                'stj',          // Superior Tribunal de Justiça
                'tst',          // Tribunal Superior do Trabalho
                'tse',          // Tribunal Superior Eleitoral
                'stm',          // Superior Tribunal Militar
                'trf',          // Tribunal Regional Federal
                'tre',          // Tribunal Regional Eleitoral
                'trt',          // Tribunal Regional do Trabalho
                'tjm',          // Tribunal de Justiça Militar
                'tj',           // Tribunal de Justiça Estadual
                '1grau_federal', // 1º Grau Federal
                '1grau_estadual', // 1º Grau Estadual
                '1grau_trabalho', // 1º Grau Trabalho
                'jef',          // Juizado Especial Federal
                'jec',          // Juizado Especial Cível
                'outro'         // Outros
            ])->default('tj');
            
            // Jurisdição
            $table->enum('jurisdiction', [
                'federal',
                'estadual',
                'trabalhista',
                'eleitoral',
                'militar'
            ])->default('estadual');
            
            $table->string('state', 2)->nullable()->comment('UF');
            $table->unsignedInteger('region')->nullable()->comment('Região para TRFs');
            
            // Configuração da API
            $table->string('api_type')->default('datajud')->comment('datajud, pje, esaj, projudi, outros');
            $table->string('api_base_url')->nullable();
            $table->text('api_key')->nullable()->comment('Chave de API criptografada');
            $table->string('api_username')->nullable();
            $table->text('api_password')->nullable()->comment('Senha criptografada');
            $table->text('api_certificate_path')->nullable();
            $table->text('api_certificate_password')->nullable();
            
            // Configurações de consulta
            $table->json('supported_operations')->nullable()->comment('Operações suportadas pela API');
            $table->json('request_headers')->nullable();
            $table->json('authentication_config')->nullable();
            
            // Rate limiting
            $table->unsignedInteger('requests_per_minute')->default(60);
            $table->unsignedInteger('requests_per_day')->default(5000);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_configured')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('type');
            $table->index('jurisdiction');
            $table->index('state');
        });

        // =============================================
        // CONSULTAS REALIZADAS
        // =============================================
        Schema::create('court_queries', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Dados da consulta
            $table->enum('query_type', [
                'process_search',      // Busca por processo
                'process_details',     // Detalhes do processo
                'movements',           // Movimentações
                'parties',             // Partes
                'documents',           // Documentos
                'deadlines',           // Prazos
                'hearings',            // Audiências
                'attached_processes',  // Processos apensados
                'distribution'         // Distribuição
            ])->default('movements');
            
            // Parâmetros
            $table->string('process_number', 25)->nullable();
            $table->json('query_params')->nullable();
            
            // Resultado
            $table->enum('status', [
                'pending',      // Aguardando
                'processing',   // Processando
                'success',      // Sucesso
                'error',        // Erro
                'no_results'    // Sem resultados
            ])->default('pending');
            
            $table->longText('response_data')->nullable()->comment('Resposta JSON da API');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('results_count')->default(0);
            
            // Timestamps
            $table->timestamp('queried_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('process_number');
            $table->index('status');
            $table->index('queried_at');
        });

        // =============================================
        // MOVIMENTAÇÕES IMPORTADAS
        // =============================================
        Schema::create('court_movements', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('court_query_id')->nullable()->constrained()->nullOnDelete();
            
            // Dados da movimentação
            $table->string('process_number', 25);
            $table->string('movement_code')->nullable();
            $table->text('description');
            $table->text('complement')->nullable();
            
            // Data e hora
            $table->timestamp('movement_date');
            $table->string('source')->default('api')->comment('api, manual, import');
            
            // Importação para andamentos
            $table->foreignId('proceeding_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_imported')->default(false);
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Hash para evitar duplicatas
            $table->string('movement_hash', 64)->nullable()->comment('Hash único da movimentação');
            
            $table->json('raw_data')->nullable()->comment('Dados brutos da API');
            
            $table->timestamps();
            
            // Índices
            $table->index('process_number');
            $table->index('movement_date');
            $table->index('movement_hash');
            $table->index('is_imported');
        });

        // =============================================
        // AGENDAMENTOS DE SINCRONIZAÇÃO
        // =============================================
        Schema::create('court_sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            
            // Tipo de sincronização
            $table->enum('sync_type', [
                'all_processes',    // Todos os processos do tribunal
                'single_process',   // Processo específico
                'active_processes', // Processos ativos
                'custom_query'      // Consulta customizada
            ])->default('active_processes');
            
            // Frequência
            $table->enum('frequency', [
                'hourly',       // A cada hora
                'every_4_hours', // A cada 4 horas
                'every_8_hours', // A cada 8 horas
                'twice_daily',  // Duas vezes ao dia
                'daily',        // Diariamente
                'weekly',       // Semanalmente
                'manual'        // Apenas manual
            ])->default('daily');
            
            $table->string('cron_expression')->nullable();
            $table->time('preferred_time')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->enum('last_run_status', ['success', 'partial', 'error'])->nullable();
            $table->text('last_run_message')->nullable();
            $table->unsignedInteger('last_run_count')->default(0);
            
            $table->timestamps();
        });

        // =============================================
        // LOG DE SINCRONIZAÇÃO
        // =============================================
        Schema::create('court_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('court_sync_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Detalhes da sincronização
            $table->enum('sync_type', [
                'manual',       // Sincronização manual
                'scheduled',    // Agendada
                'webhook',      // Via webhook
                'bulk'          // Em lote
            ])->default('scheduled');
            
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            
            // Métricas
            $table->unsignedInteger('processes_queried')->default(0);
            $table->unsignedInteger('movements_found')->default(0);
            $table->unsignedInteger('movements_new')->default(0);
            $table->unsignedInteger('movements_imported')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            
            // Status
            $table->enum('status', ['running', 'success', 'partial', 'error'])->default('running');
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            
            // Duração
            $table->unsignedInteger('duration_seconds')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('started_at');
            $table->index('status');
        });

        // =============================================
        // TEMPLATES DE CÓDIGOS DE MOVIMENTAÇÃO
        // =============================================
        Schema::create('court_movement_codes', function (Blueprint $table) {
            $table->id();
            
            // Código e descrição
            $table->string('code', 50);
            $table->string('description');
            $table->string('category')->nullable();
            
            // Mapeamento para tipo de andamento interno
            $table->string('internal_type')->nullable();
            $table->boolean('is_important')->default(false);
            $table->boolean('creates_deadline')->default(false);
            $table->unsignedInteger('deadline_days')->nullable();
            
            // Notificação
            $table->boolean('notify_responsible')->default(false);
            $table->boolean('notify_client')->default(false);
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Índices
            $table->unique('code');
            $table->index('is_important');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_movement_codes');
        Schema::dropIfExists('court_sync_logs');
        Schema::dropIfExists('court_sync_schedules');
        Schema::dropIfExists('court_movements');
        Schema::dropIfExists('court_queries');
        Schema::dropIfExists('courts');
    }
};
