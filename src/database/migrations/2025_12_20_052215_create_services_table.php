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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // Código único do serviço
            $table->string('code', 20)->unique()->comment('Código: SRV-2025-0001');
            
            // Relacionamentos
            $table->foreignId('client_id')->constrained()->onDelete('restrict');
            $table->foreignId('service_type_id')->constrained()->onDelete('restrict');
            
            // Dados do processo
            $table->string('process_number', 30)->nullable()->comment('Número do processo CNJ');
            $table->string('court')->nullable()->comment('Vara/Tribunal');
            $table->string('jurisdiction')->nullable()->comment('Comarca');
            $table->string('state', 2)->nullable()->comment('UF');
            
            // Partes do processo
            $table->string('plaintiff')->nullable()->comment('Autor/Requerente');
            $table->string('defendant')->nullable()->comment('Réu/Requerido');
            
            // Datas e prazos
            $table->date('request_date')->comment('Data da solicitação');
            $table->date('deadline_date')->nullable()->comment('Data limite/Prazo');
            $table->datetime('scheduled_datetime')->nullable()->comment('Data/hora agendada (audiências)');
            $table->date('completion_date')->nullable()->comment('Data de conclusão');
            
            // Local (para diligências presenciais)
            $table->string('location_name')->nullable()->comment('Nome do local');
            $table->string('location_address')->nullable();
            $table->string('location_city')->nullable();
            $table->string('location_state', 2)->nullable();
            $table->string('location_cep', 10)->nullable();
            
            // Valores
            $table->decimal('agreed_price', 10, 2)->default(0)->comment('Valor acordado');
            $table->decimal('expenses', 10, 2)->default(0)->comment('Despesas (custas, deslocamento)');
            $table->decimal('total_price', 10, 2)->default(0)->comment('Total: acordado + despesas');
            
            // Status
            $table->enum('status', [
                'pending',      // Pendente
                'confirmed',    // Confirmado
                'in_progress',  // Em andamento
                'completed',    // Concluído
                'cancelled',    // Cancelado
                'rescheduled'   // Reagendado
            ])->default('pending');
            
            $table->enum('payment_status', [
                'pending',      // Pendente
                'partial',      // Parcial
                'paid',         // Pago
                'overdue'       // Vencido
            ])->default('pending');
            
            // Prioridade
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Detalhes
            $table->text('description')->nullable()->comment('Descrição do serviço');
            $table->text('instructions')->nullable()->comment('Instruções específicas');
            $table->text('result_notes')->nullable()->comment('Resultado/Observações finais');
            $table->text('internal_notes')->nullable()->comment('Notas internas');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('status');
            $table->index('payment_status');
            $table->index('priority');
            $table->index('request_date');
            $table->index('deadline_date');
            $table->index('scheduled_datetime');
            $table->index('process_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
