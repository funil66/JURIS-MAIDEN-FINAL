<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabela de Time Entries (Lançamentos de Tempo)
     * Registra o tempo trabalhado por advogados/colaboradores
     */
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            // Relacionamentos
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quem trabalhou
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('proceeding_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('diligence_id')->nullable()->constrained()->nullOnDelete();
            
            // Descrição
            $table->string('description', 1000);
            $table->text('notes')->nullable();
            
            // Classificação
            $table->enum('activity_type', [
                'research',           // Pesquisa
                'drafting',           // Elaboração de peça
                'review',             // Revisão
                'meeting',            // Reunião
                'hearing',            // Audiência
                'phone_call',         // Telefonema
                'email',              // E-mail
                'consultation',       // Consulta
                'analysis',           // Análise
                'negotiation',        // Negociação
                'court_visit',        // Ida ao fórum
                'administrative',     // Administrativo
                'travel',             // Deslocamento
                'other',              // Outro
            ])->default('other');
            
            // Tempo
            $table->date('work_date'); // Data do trabalho
            $table->time('start_time')->nullable(); // Hora de início
            $table->time('end_time')->nullable(); // Hora de término
            $table->integer('duration_minutes'); // Duração em minutos
            
            // Timer
            $table->boolean('is_running')->default(false); // Timer ativo?
            $table->dateTime('timer_started_at')->nullable(); // Início do timer
            
            // Faturamento
            $table->boolean('is_billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable(); // Taxa horária
            $table->decimal('total_amount', 10, 2)->nullable(); // Valor total (calculado)
            
            // Status
            $table->enum('status', [
                'draft',        // Rascunho
                'submitted',    // Submetido para aprovação
                'approved',     // Aprovado
                'rejected',     // Rejeitado
                'billed',       // Faturado
                'paid',         // Pago
            ])->default('draft');
            
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Fatura
            $table->foreignId('invoice_id')->nullable(); // FK para tabela de faturas (futuro)
            $table->dateTime('billed_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'work_date']);
            $table->index(['process_id', 'work_date']);
            $table->index(['client_id', 'work_date']);
            $table->index(['status', 'work_date']);
            $table->index('is_billable');
            $table->index('is_running');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
