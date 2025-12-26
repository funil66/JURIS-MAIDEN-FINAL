<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabela de Andamentos Processuais
     * Registra todas as movimentações e atualizações de um processo
     */
    public function up(): void
    {
        Schema::create('proceedings', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            // Relacionamentos
            $table->foreignId('process_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Quem registrou
            
            // Identificação
            $table->string('title', 500);
            $table->text('content')->nullable(); // Conteúdo completo do andamento
            
            // Data e Hora
            $table->date('proceeding_date'); // Data do andamento
            $table->time('proceeding_time')->nullable(); // Hora (se aplicável)
            $table->dateTime('published_at')->nullable(); // Quando foi publicado no DJE
            
            // Classificação
            $table->enum('type', [
                'movement',       // Movimentação simples
                'decision',       // Decisão
                'sentence',       // Sentença
                'dispatch',       // Despacho
                'petition',       // Petição (nossa ou contrária)
                'hearing',        // Audiência
                'publication',    // Publicação no DJE
                'citation',       // Citação/Intimação
                'deadline',       // Prazo
                'appeal',         // Recurso
                'transit',        // Trânsito em julgado
                'archive',        // Arquivamento
                'unarchive',      // Desarquivamento
                'distribution',   // Distribuição
                'conclusion',     // Conclusão ao juiz
                'other',          // Outro
            ])->default('movement');
            
            $table->enum('source', [
                'manual',         // Cadastro manual
                'datajud',        // API DataJud
                'escavador',      // Escavador
                'projudi',        // ProJudi
                'pje',            // PJe
                'esaj',           // E-SAJ
                'eproc',          // E-Proc
                'sei',            // SEI
                'tjdft',          // TJDFT
                'other_api',      // Outra API
                'import',         // Importação de arquivo
            ])->default('manual');
            
            // Prazos
            $table->boolean('has_deadline')->default(false);
            $table->date('deadline_date')->nullable();
            $table->integer('deadline_days')->nullable(); // Dias úteis do prazo
            $table->boolean('deadline_completed')->default(false);
            $table->dateTime('deadline_completed_at')->nullable();
            
            // Ação necessária
            $table->boolean('requires_action')->default(false);
            $table->text('action_description')->nullable();
            $table->boolean('action_completed')->default(false);
            $table->dateTime('action_completed_at')->nullable();
            $table->foreignId('action_responsible_id')->nullable()
                ->constrained('users')->nullOnDelete();
            
            // Status
            $table->enum('status', [
                'pending',     // Pendente de análise
                'analyzed',    // Analisado
                'actioned',    // Ação tomada
                'archived',    // Arquivado
            ])->default('pending');
            
            // Metadados
            $table->boolean('is_important')->default(false);
            $table->boolean('is_favorable')->nullable(); // Decisão favorável?
            $table->string('external_id', 100)->nullable(); // ID da API externa
            $table->json('metadata')->nullable(); // Dados extras da API
            
            // Observações
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable(); // Notas internas (não visíveis ao cliente)
            
            // Anexos (referência - anexos reais em outra tabela ou storage)
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachments_count')->default(0);
            
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['process_id', 'proceeding_date']);
            $table->index(['process_id', 'type']);
            $table->index(['has_deadline', 'deadline_date']);
            $table->index(['requires_action', 'action_completed']);
            $table->index('status');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proceedings');
    }
};
