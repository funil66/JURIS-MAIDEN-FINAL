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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            // Relacionamentos
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Identificação
            $table->string('invoice_number', 50)->unique();
            $table->string('description')->nullable();
            $table->string('reference')->nullable(); // Referência interna
            
            // Período de referência
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Datas
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->date('cancelled_at')->nullable();
            
            // Valores
            $table->decimal('subtotal', 15, 2)->default(0); // Total dos itens
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('interest', 15, 2)->default(0);
            $table->decimal('fine', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0); // Valor final
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0); // Saldo devedor
            
            // Pagamento
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Status
            $table->string('status')->default('draft'); // draft, pending, partial, paid, overdue, cancelled
            
            // Tipo de fatura
            $table->string('invoice_type')->default('services'); // services, time_billing, retainer, expenses, other
            $table->string('billing_type')->default('fixed'); // fixed, hourly, mixed
            
            // Dados fiscais
            $table->string('nfse_number')->nullable();
            $table->string('nfse_link')->nullable();
            $table->timestamp('nfse_emitted_at')->nullable();
            
            // Observações
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('terms')->nullable();
            
            // Metadados
            $table->json('metadata')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['client_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['issue_date']);
            $table->index('status');
        });
        
        // Tabela de itens da fatura
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('time_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_installment_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('description');
            $table->string('item_type')->default('service'); // service, time, expense, installment, other
            
            // Valores
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable(); // hora, unidade, etc
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });
        
        // Tabela de pagamentos parciais
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 20)->unique()->nullable();
            
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
