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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            
            // Tipo de pessoa
            $table->enum('type', ['pf', 'pj'])->default('pf')->comment('PF=Pessoa Física, PJ=Pessoa Jurídica');
            
            // Dados básicos
            $table->string('name');
            $table->string('document', 18)->unique()->comment('CPF ou CNPJ');
            $table->string('rg', 20)->nullable()->comment('RG para PF');
            $table->string('oab', 20)->nullable()->comment('Número OAB se for advogado');
            
            // Contato
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            // Endereço
            $table->string('cep', 10)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            
            // Para PJ
            $table->string('company_name')->nullable()->comment('Razão Social');
            $table->string('trading_name')->nullable()->comment('Nome Fantasia');
            $table->string('contact_person')->nullable()->comment('Pessoa de contato na empresa');
            
            // Observações e controle
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('type');
            $table->index('is_active');
            $table->index('city');
            $table->index('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
