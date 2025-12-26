<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabela de Processos Judiciais - Core do sistema jurídico.
     * Suporta hierarquia (subprocessos) e vinculação com clientes/serviços.
     */
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique();
            
            // Relacionamentos
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Identificação do processo
            $table->string('cnj_number', 25)->nullable(); // 0000000-00.0000.0.00.0000
            $table->string('old_number', 50)->nullable(); // Numeração antiga
            $table->string('title'); // Título descritivo
            
            // Localização
            $table->string('court')->nullable(); // Tribunal (TJSP, TRT, etc)
            $table->string('jurisdiction')->nullable(); // Comarca
            $table->string('court_division')->nullable(); // Vara
            $table->string('court_section')->nullable(); // Seção/Turma
            $table->string('state', 2)->nullable();
            
            // Partes
            $table->string('plaintiff')->nullable(); // Autor/Requerente
            $table->string('defendant')->nullable(); // Réu/Requerido
            $table->enum('client_role', [
                'plaintiff',    // Autor
                'defendant',    // Réu
                'third_party',  // Terceiro
                'interested',   // Interessado
                'other'
            ])->default('plaintiff');
            
            // Classificação
            $table->string('matter_type')->nullable(); // Área do direito
            $table->string('action_type')->nullable(); // Tipo de ação
            $table->string('procedure_type')->nullable(); // Rito processual
            $table->string('subject')->nullable(); // Assunto principal
            
            // Datas
            $table->date('distribution_date')->nullable();
            $table->date('filing_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->date('transit_date')->nullable(); // Trânsito em julgado
            
            // Valores
            $table->decimal('case_value', 15, 2)->nullable(); // Valor da causa
            $table->decimal('contingency_value', 15, 2)->nullable(); // Valor contingencial
            $table->decimal('sentence_value', 15, 2)->nullable(); // Valor da sentença
            
            // Status
            $table->enum('status', [
                'prospecting',   // Em prospecção
                'active',        // Em andamento
                'suspended',     // Suspenso
                'archived',      // Arquivado
                'closed_won',    // Encerrado - Êxito
                'closed_lost',   // Encerrado - Improcedente
                'closed_settled',// Encerrado - Acordo
                'closed_other'   // Encerrado - Outros
            ])->default('active');
            
            $table->enum('phase', [
                'knowledge',      // Conhecimento
                'execution',      // Execução
                'appeal',         // Recursal
                'precautionary',  // Cautelar
                'preliminary'     // Tutela de urgência
            ])->default('knowledge');
            
            $table->enum('instance', [
                'first',    // 1ª Instância
                'second',   // 2ª Instância
                'superior', // Tribunais Superiores
                'supreme'   // STF
            ])->default('first');
            
            // Advogado externo (se houver)
            $table->string('external_lawyer')->nullable();
            $table->string('external_lawyer_oab')->nullable();
            $table->string('external_lawyer_email')->nullable();
            $table->string('external_lawyer_phone')->nullable();
            
            // Contraparte
            $table->string('opposing_lawyer')->nullable();
            $table->string('opposing_lawyer_oab')->nullable();
            
            // Observações
            $table->text('strategy')->nullable(); // Estratégia do caso
            $table->text('risk_assessment')->nullable(); // Avaliação de risco
            $table->text('notes')->nullable();
            
            // Flags
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_confidential')->default(false);
            $table->boolean('is_pro_bono')->default(false);
            $table->boolean('has_injunction')->default(false); // Tem liminar
            
            // Controle interno
            $table->string('internal_code')->nullable(); // Código interno do escritório
            $table->string('folder_location')->nullable(); // Localização pasta física
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['status', 'client_id']);
            $table->index('cnj_number');
            $table->index('parent_id');
            $table->index('responsible_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processes');
    }
};
