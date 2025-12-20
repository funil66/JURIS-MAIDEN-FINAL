<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sprint 13: Campos estendidos para serviços jurídicos
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // ==========================================
            // DADOS DO TRIBUNAL/JUÍZO
            // ==========================================
            $table->string('judge_name')->nullable()->comment('Nome do Juiz(a)');
            $table->string('court_secretary')->nullable()->comment('Nome do(a) Secretário(a) da Vara');
            $table->string('court_phone')->nullable()->comment('Telefone do Fórum/Vara');
            $table->string('court_email')->nullable()->comment('E-mail do Fórum/Vara');
            
            // ==========================================
            // SOLICITANTE (quem pediu o serviço)
            // ==========================================
            $table->string('requester_name')->nullable()->comment('Nome do solicitante');
            $table->string('requester_email')->nullable()->comment('E-mail do solicitante');
            $table->string('requester_phone')->nullable()->comment('Telefone do solicitante');
            $table->string('requester_oab')->nullable()->comment('OAB do advogado solicitante');
            
            // ==========================================
            // DADOS DE VIAGEM/DESLOCAMENTO
            // ==========================================
            $table->decimal('travel_distance_km', 8, 2)->nullable()->comment('Distância em km');
            $table->decimal('travel_cost', 10, 2)->default(0)->comment('Custo de deslocamento');
            $table->enum('travel_type', [
                'none',          // Sem deslocamento
                'local',         // Local (mesma cidade)
                'regional',      // Regional (até 100km)
                'distant'        // Distante (mais de 100km)
            ])->default('none');
            $table->text('travel_notes')->nullable()->comment('Observações sobre deslocamento');
            
            // ==========================================
            // DOCUMENTOS E ANEXOS
            // ==========================================
            $table->json('attachments')->nullable()->comment('Lista de arquivos anexados');
            $table->boolean('has_substabelecimento')->default(false)->comment('Possui substabelecimento?');
            $table->boolean('has_procuracao')->default(false)->comment('Possui procuração?');
            $table->boolean('documents_received')->default(false)->comment('Documentos recebidos?');
            $table->date('documents_received_at')->nullable()->comment('Data recebimento docs');
            
            // ==========================================
            // RESULTADO E COMPROVAÇÃO
            // ==========================================
            $table->enum('result_type', [
                'pending',           // Aguardando
                'successful',        // Realizado com sucesso
                'partial',           // Parcialmente realizado
                'rescheduled',       // Redesignado
                'cancelled_court',   // Cancelado pelo juízo
                'cancelled_party',   // Cancelado pela parte
                'failed'             // Não realizado
            ])->default('pending');
            $table->datetime('actual_datetime')->nullable()->comment('Data/hora real de realização');
            $table->text('result_summary')->nullable()->comment('Resumo do resultado');
            $table->json('result_attachments')->nullable()->comment('Comprovantes do resultado');
            
            // ==========================================
            // CONTROLE DE QUALIDADE
            // ==========================================
            $table->integer('client_rating')->nullable()->comment('Avaliação do cliente (1-5)');
            $table->text('client_feedback')->nullable()->comment('Feedback do cliente');
            $table->boolean('requires_followup')->default(false)->comment('Requer acompanhamento?');
            $table->text('followup_notes')->nullable()->comment('Notas de acompanhamento');
            
            // ==========================================
            // ÍNDICES PARA PERFORMANCE
            // ==========================================
            $table->index('judge_name');
            $table->index('requester_oab');
            $table->index('result_type');
            $table->index('travel_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Tribunal
            $table->dropColumn([
                'judge_name', 'court_secretary', 'court_phone', 'court_email'
            ]);
            
            // Solicitante
            $table->dropColumn([
                'requester_name', 'requester_email', 'requester_phone', 'requester_oab'
            ]);
            
            // Viagem
            $table->dropColumn([
                'travel_distance_km', 'travel_cost', 'travel_type', 'travel_notes'
            ]);
            
            // Documentos
            $table->dropColumn([
                'attachments', 'has_substabelecimento', 'has_procuracao', 
                'documents_received', 'documents_received_at'
            ]);
            
            // Resultado
            $table->dropColumn([
                'result_type', 'actual_datetime', 'result_summary', 'result_attachments'
            ]);
            
            // Qualidade
            $table->dropColumn([
                'client_rating', 'client_feedback', 'requires_followup', 'followup_notes'
            ]);
        });
    }
};
