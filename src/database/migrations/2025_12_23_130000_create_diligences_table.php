<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabela de Diligências
     * Registra atividades externas como idas a fóruns, cartórios, 
     * audiências presenciais, coleta de documentos, etc.
     */
    public function up(): void
    {
        Schema::create('diligences', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            // Relacionamentos
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete(); // Se vinculada a um serviço
            $table->foreignId('proceeding_id')->nullable()->constrained()->nullOnDelete(); // Se vinculada a um andamento
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Identificação
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->text('objective')->nullable(); // Objetivo da diligência
            
            // Classificação
            $table->enum('type', [
                'forum_visit',        // Ida ao fórum
                'registry_visit',     // Ida ao cartório
                'notary_visit',       // Tabelionato
                'document_pickup',    // Retirada de documentos
                'document_delivery',  // Entrega de documentos
                'hearing',            // Audiência presencial
                'meeting',            // Reunião externa
                'site_inspection',    // Vistoria/Inspeção
                'witness_interview',  // Entrevista com testemunha
                'deposition',         // Coleta de depoimento
                'service_of_process', // Citação/Intimação
                'notarization',       // Autenticação/Reconhecimento
                'filing',             // Protocolo
                'research',           // Pesquisa de bens/certidões
                'court_hearing',      // Acompanhamento de audiência
                'travel',             // Viagem
                'other',              // Outro
            ])->default('forum_visit');
            
            $table->enum('priority', [
                'low',
                'normal',
                'high',
                'urgent',
            ])->default('normal');
            
            // Status
            $table->enum('status', [
                'pending',       // Pendente
                'scheduled',     // Agendada
                'in_progress',   // Em execução
                'completed',     // Concluída
                'cancelled',     // Cancelada
                'rescheduled',   // Reagendada
                'failed',        // Falhou
            ])->default('pending');
            
            // Agendamento
            $table->date('scheduled_date')->nullable();
            $table->time('scheduled_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            
            // Execução
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            
            // Local
            $table->string('location_name', 255)->nullable(); // Nome do local (ex: "Fórum Central")
            $table->string('location_address', 500)->nullable(); // Endereço
            $table->string('location_city', 100)->nullable();
            $table->string('location_state', 2)->nullable();
            $table->string('location_zip', 10)->nullable();
            $table->decimal('location_lat', 10, 7)->nullable(); // Latitude
            $table->decimal('location_lng', 10, 7)->nullable(); // Longitude
            
            // Contato no local
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_department', 100)->nullable();
            
            // Financeiro
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->decimal('mileage_km', 8, 2)->default(0);
            $table->decimal('mileage_cost', 10, 2)->default(0);
            $table->decimal('parking_cost', 10, 2)->default(0);
            $table->decimal('toll_cost', 10, 2)->default(0);
            $table->decimal('transport_cost', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_reimbursed')->default(false);
            $table->dateTime('reimbursed_at')->nullable();
            
            // Resultado
            $table->text('result')->nullable(); // Descrição do resultado
            $table->boolean('was_successful')->nullable();
            $table->text('failure_reason')->nullable();
            
            // Documentos e comprovantes
            $table->boolean('has_receipt')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachments_count')->default(0);
            
            // Observações
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['process_id', 'scheduled_date']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['status', 'scheduled_date']);
            $table->index(['type', 'status']);
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diligences');
    }
};
