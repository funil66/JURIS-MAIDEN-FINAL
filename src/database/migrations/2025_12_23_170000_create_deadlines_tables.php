<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabela de Feriados para cálculo de prazos
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->enum('type', ['national', 'state', 'municipal', 'court'])->default('national');
            $table->string('state', 2)->nullable(); // Para feriados estaduais
            $table->string('city')->nullable(); // Para feriados municipais
            $table->string('court')->nullable(); // Para recesso forense
            $table->boolean('is_recurring')->default(false); // Se repete anualmente
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['date', 'state', 'type']);
        });

        // Tabela de Tipos de Prazo (configuração)
        Schema::create('deadline_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            
            // Configuração de contagem
            $table->integer('default_days'); // Prazo padrão em dias
            $table->enum('counting_type', ['business_days', 'calendar_days', 'continuous'])->default('business_days');
            $table->boolean('excludes_start_date')->default(true); // Exclui dia inicial
            $table->boolean('extends_to_next_business_day')->default(true); // Se cai no fds/feriado, prorroga
            
            // Categorização
            $table->enum('category', [
                'response',       // Resposta/Contestação
                'appeal',         // Recurso
                'manifestation',  // Manifestação
                'hearing',        // Audiência
                'execution',      // Execução
                'other'
            ])->default('other');
            
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            // Alertas automáticos (dias antes do vencimento)
            $table->json('alert_days')->nullable(); // Ex: [5, 2, 1] = alertas 5, 2 e 1 dias antes
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabela de Prazos Calculados (tracking individual)
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            
            // Vínculos
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proceeding_id')->nullable()->constrained()->nullOnDelete(); // Andamento que gerou o prazo
            $table->foreignId('deadline_type_id')->nullable()->constrained()->nullOnDelete();
            
            // Datas
            $table->date('start_date'); // Data de início (publicação/intimação)
            $table->date('due_date'); // Data de vencimento calculada
            $table->date('original_due_date')->nullable(); // Data original (antes de prorrogações)
            $table->datetime('completed_at')->nullable();
            
            // Descrição
            $table->string('title');
            $table->text('description')->nullable();
            
            // Configuração usada no cálculo
            $table->integer('days_count'); // Quantidade de dias do prazo
            $table->enum('counting_type', ['business_days', 'calendar_days', 'continuous'])->default('business_days');
            
            // Status
            $table->enum('status', [
                'pending',      // Aguardando
                'in_progress',  // Em andamento
                'completed',    // Cumprido
                'extended',     // Prorrogado
                'missed',       // Perdido
                'cancelled'     // Cancelado
            ])->default('pending');
            
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            
            // Responsável
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Alertas enviados
            $table->json('alerts_sent')->nullable(); // Tracking de alertas já enviados
            
            // Resultado
            $table->text('completion_notes')->nullable();
            $table->string('document_protocol')->nullable(); // Número do protocolo se houver
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['process_id', 'status', 'due_date']);
            $table->index(['due_date', 'status']);
            $table->index(['assigned_user_id', 'status', 'due_date']);
        });

        // Tabela de Alertas de Prazo (histórico de notificações)
        Schema::create('deadline_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deadline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', [
                'email',
                'notification',
                'whatsapp',
                'sms',
                'system'
            ])->default('notification');
            
            $table->integer('days_before'); // Quantos dias antes do vencimento
            $table->datetime('sent_at')->nullable();
            $table->datetime('read_at')->nullable();
            $table->boolean('is_sent')->default(false);
            
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['deadline_id', 'type', 'is_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deadline_alerts');
        Schema::dropIfExists('deadlines');
        Schema::dropIfExists('deadline_types');
        Schema::dropIfExists('holidays');
    }
};
