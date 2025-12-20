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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Código único
            $table->string('code', 20)->unique()->comment('TRX-2025-0001');
            
            // Tipo
            $table->enum('type', ['income', 'expense'])->comment('Receita ou Despesa');
            
            // Relacionamentos
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            
            // Categoria
            $table->string('category')->nullable()->comment('Honorários, Custas, Deslocamento, etc');
            
            // Valores
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('fees', 10, 2)->default(0)->comment('Taxas bancárias');
            $table->decimal('net_amount', 10, 2)->comment('Valor líquido');
            
            // Datas
            $table->date('due_date')->nullable()->comment('Data de vencimento');
            $table->date('paid_date')->nullable()->comment('Data do pagamento');
            $table->date('competence_date')->nullable()->comment('Data de competência');
            
            // Status
            $table->enum('status', [
                'pending',      // Pendente
                'paid',         // Pago/Recebido
                'partial',      // Parcial
                'overdue',      // Vencido
                'cancelled'     // Cancelado
            ])->default('pending');
            
            // Parcelamento
            $table->integer('installment_number')->nullable()->comment('Número da parcela');
            $table->integer('total_installments')->nullable()->comment('Total de parcelas');
            $table->string('installment_group')->nullable()->comment('Identificador do grupo de parcelas');
            
            // Detalhes
            $table->string('description');
            $table->text('notes')->nullable();
            
            // Comprovante
            $table->string('receipt_path')->nullable()->comment('Caminho do comprovante');
            $table->string('invoice_number')->nullable()->comment('Número da NF');
            
            // Conciliação bancária
            $table->string('bank_reference')->nullable()->comment('Referência bancária');
            $table->boolean('is_reconciled')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('type');
            $table->index('status');
            $table->index('due_date');
            $table->index('paid_date');
            $table->index('category');
            $table->index('competence_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
