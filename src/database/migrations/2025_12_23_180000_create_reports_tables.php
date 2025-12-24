<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de templates de relatório salvos
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            
            // Tipo de relatório
            $table->enum('type', [
                'processes',      // Processos
                'deadlines',      // Prazos
                'diligences',     // Diligências
                'time_entries',   // Lançamentos de Tempo
                'contracts',      // Contratos
                'invoices',       // Faturas
                'clients',        // Clientes
                'financial',      // Financeiro
                'services',       // Serviços
                'productivity',   // Produtividade
                'custom',         // Personalizado
            ]);
            
            // Filtros salvos (JSON)
            $table->json('filters')->nullable();
            
            // Colunas selecionadas (JSON)
            $table->json('columns')->nullable();
            
            // Ordenação
            $table->string('order_by')->nullable();
            $table->enum('order_direction', ['asc', 'desc'])->default('desc');
            
            // Agrupamento
            $table->string('group_by')->nullable();
            
            // Configurações de gráficos
            $table->json('charts')->nullable();
            
            // Formato de exportação preferido
            $table->enum('default_format', ['pdf', 'excel', 'csv'])->default('pdf');
            
            // Aparência
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->boolean('include_summary')->default(true);
            $table->boolean('include_charts')->default(true);
            $table->boolean('include_details')->default(true);
            
            // Visibilidade
            $table->boolean('is_public')->default(false);
            $table->boolean('is_favorite')->default(false);
            
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'type']);
            $table->index('is_favorite');
        });
        
        // Tabela de relatórios gerados (histórico)
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('name');
            $table->enum('type', [
                'processes', 'deadlines', 'diligences', 'time_entries',
                'contracts', 'invoices', 'clients', 'financial',
                'services', 'productivity', 'custom'
            ]);
            
            // Período do relatório
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            
            // Filtros aplicados
            $table->json('filters_applied')->nullable();
            
            // Formato e arquivo
            $table->enum('format', ['pdf', 'excel', 'csv']);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            
            // Estatísticas
            $table->integer('records_count')->default(0);
            $table->decimal('execution_time', 8, 3)->nullable(); // segundos
            
            // Status
            $table->enum('status', [
                'generating',
                'completed',
                'failed',
                'expired',
            ])->default('generating');
            
            $table->text('error_message')->nullable();
            
            // Download/Visualização
            $table->integer('download_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
        
        // Tabela de agendamentos de relatório
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_template_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            
            // Frequência
            $table->enum('frequency', [
                'daily',      // Diário
                'weekly',     // Semanal
                'biweekly',   // Quinzenal
                'monthly',    // Mensal
                'quarterly',  // Trimestral
            ]);
            
            // Dia da semana (para weekly)
            $table->tinyInteger('day_of_week')->nullable(); // 0=domingo, 6=sábado
            
            // Dia do mês (para monthly)
            $table->tinyInteger('day_of_month')->nullable(); // 1-31
            
            // Horário de execução
            $table->time('scheduled_time')->default('08:00:00');
            
            // Destinatários (emails separados por vírgula ou JSON)
            $table->json('recipients')->nullable();
            
            // Formato de exportação
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            
            // Período do relatório
            $table->enum('period', [
                'yesterday',     // Ontem
                'last_7_days',   // Últimos 7 dias
                'last_30_days',  // Últimos 30 dias
                'last_month',    // Mês anterior
                'current_month', // Mês atual
                'last_quarter',  // Trimestre anterior
                'current_year',  // Ano atual
            ])->default('last_30_days');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Execução
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->text('last_error')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('report_templates');
    }
};
