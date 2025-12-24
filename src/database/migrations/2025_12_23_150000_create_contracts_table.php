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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            // Relacionamentos
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Identificação
            $table->string('contract_number', 50)->unique()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            
            // Tipo e Natureza
            $table->string('type')->default('legal_services'); // legal_services, consulting, due_diligence, compliance, other
            $table->string('area')->nullable(); // civil, criminal, labor, tax, business, family, administrative
            $table->string('fee_type')->default('fixed'); // fixed, hourly, per_act, success, hybrid, retainer
            
            // Valores e Honorários
            $table->decimal('total_value', 15, 2)->default(0); // Valor total do contrato
            $table->decimal('success_fee_percentage', 5, 2)->nullable(); // % de êxito
            $table->decimal('success_fee_base', 15, 2)->nullable(); // Base de cálculo do êxito
            $table->decimal('hourly_rate', 10, 2)->nullable(); // Taxa hora padrão
            $table->decimal('minimum_fee', 15, 2)->nullable(); // Honorário mínimo
            $table->decimal('estimated_hours', 10, 2)->nullable(); // Horas estimadas
            
            // Pagamento
            $table->string('payment_method')->nullable(); // pix, transfer, credit_card, boleto, check
            $table->string('payment_frequency')->nullable(); // single, monthly, quarterly, biannual, annual
            $table->integer('installments_count')->default(1);
            $table->integer('day_of_payment')->nullable(); // Dia preferencial de pagamento
            $table->decimal('entry_value', 15, 2)->nullable(); // Valor de entrada
            
            // Datas
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('signature_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->integer('renewal_months')->nullable();
            $table->date('renewal_date')->nullable();
            
            // Status e Workflow
            $table->string('status')->default('draft'); // draft, pending_signature, active, suspended, completed, cancelled, expired
            $table->boolean('is_signed')->default(false);
            $table->string('signature_type')->nullable(); // physical, digital, docusign, clicksign
            $table->timestamp('signed_at')->nullable();
            
            // Valores Calculados
            $table->decimal('total_billed', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('total_pending', 15, 2)->default(0);
            
            // Reajuste
            $table->string('adjustment_index')->nullable(); // IPCA, IGPM, INPC, custom
            $table->decimal('adjustment_percentage', 5, 2)->nullable();
            $table->date('next_adjustment_date')->nullable();
            $table->date('last_adjustment_date')->nullable();
            
            // Documentos
            $table->json('attachments')->nullable();
            $table->string('signed_document_path')->nullable();
            
            // Observações
            $table->text('scope_of_work')->nullable(); // Escopo do trabalho
            $table->text('exclusions')->nullable(); // O que não está incluso
            $table->text('special_conditions')->nullable(); // Condições especiais
            $table->text('internal_notes')->nullable();
            
            // Metadados
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['client_id', 'status']);
            $table->index(['process_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index('fee_type');
            $table->index('end_date');
        });
        
        // Tabela de parcelas do contrato
        Schema::create('contract_installments', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            
            $table->integer('installment_number');
            $table->string('description')->nullable();
            
            // Valores
            $table->decimal('amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('interest', 15, 2)->default(0);
            $table->decimal('fine', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2);
            
            // Datas
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            
            // Status
            $table->string('status')->default('pending'); // pending, paid, overdue, cancelled, renegotiated
            
            // Pagamento
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            
            // Referência a fatura/transação
            $table->foreignId('invoice_id')->nullable();
            $table->foreignId('transaction_id_ref')->nullable()->constrained('transactions')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['contract_id', 'installment_number']);
            $table->index(['due_date', 'status']);
            $table->index('status');
            $table->unique(['contract_id', 'installment_number']);
        });
        
        // Tabela de serviços contratados (itens do contrato)
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            
            $table->string('description');
            $table->string('service_type')->nullable(); // Tipo do serviço
            
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('contract_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_items');
        Schema::dropIfExists('contract_installments');
        Schema::dropIfExists('contracts');
    }
};
