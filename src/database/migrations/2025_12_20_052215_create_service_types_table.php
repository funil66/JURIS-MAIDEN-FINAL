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
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            
            $table->string('name')->comment('Nome do tipo: Audiência, Protocolo, Cópias, etc');
            $table->string('code', 20)->unique()->comment('Código curto: AUD, PROT, COP');
            $table->text('description')->nullable();
            
            // Valores padrão
            $table->decimal('default_price', 10, 2)->default(0)->comment('Valor padrão cobrado');
            $table->integer('default_deadline_days')->default(1)->comment('Prazo padrão em dias');
            
            // Configurações
            $table->string('icon')->nullable()->comment('Ícone heroicon');
            $table->string('color', 20)->default('primary')->comment('Cor do badge');
            $table->boolean('requires_deadline')->default(true);
            $table->boolean('requires_location')->default(true);
            $table->boolean('is_active')->default(true);
            
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
